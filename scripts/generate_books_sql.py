#!/usr/bin/env python3
import json
import re
import sys
import time
import unicodedata
import urllib.parse
import urllib.request
import urllib.error
from pathlib import Path


SUBJECTS = [
    ("Fantasy", "fantasy"),
    ("Science Fiction", "science_fiction"),
    ("Mystery", "mystery"),
    ("Romance", "romance"),
    ("History", "history"),
    ("Business", "business"),
]

BOOKS_PER_CATEGORY = 50

UI_LANGS = ["en", "hy", "ru", "fr", "de", "it"]


def fetch_json(url: str, retries: int = 4):
    req = urllib.request.Request(url, headers={"User-Agent": "ssk-seeder/1.0"})
    last_err = None
    for attempt in range(retries):
        try:
            with urllib.request.urlopen(req, timeout=30) as resp:
                return json.loads(resp.read().decode("utf-8"))
        except (urllib.error.HTTPError, urllib.error.URLError, TimeoutError) as err:
            last_err = err
            time.sleep(0.6 * (attempt + 1))
    raise last_err


def sql_escape(value: str) -> str:
    return value.replace("\\", "\\\\").replace("'", "\\'")


def slugify_ascii(label: str) -> str:
    s = unicodedata.normalize("NFKD", label).encode("ascii", "ignore").decode("ascii")
    s = s.lower()
    s = re.sub(r"[^a-z0-9]+", "-", s).strip("-")
    return s if s else "category"


def category_i18n_json(name: str) -> str:
    payload = {lc: name for lc in UI_LANGS}
    return sql_escape(json.dumps(payload, ensure_ascii=False))


def clean_text(value, fallback=""):
    if value is None:
        return fallback
    if isinstance(value, dict):
        value = value.get("value", fallback)
    value = str(value).strip()
    return value if value else fallback


def cover_url(cover_id):
    if not cover_id:
        return ""
    return f"https://covers.openlibrary.org/b/id/{cover_id}-L.jpg"


def collect_books():
    all_data = []
    for category_name, subject_slug in SUBJECTS:
        category_books = []
        seen_titles = set()
        offset = 0
        while len(category_books) < BOOKS_PER_CATEGORY and offset < 1200:
            url = f"https://openlibrary.org/subjects/{urllib.parse.quote(subject_slug)}.json?limit=100&offset={offset}"
            payload = fetch_json(url)
            works = payload.get("works", [])
            if not works:
                break
            for work in works:
                title = clean_text(work.get("title"))
                if not title:
                    continue
                key = title.lower()
                if key in seen_titles:
                    continue
                seen_titles.add(key)

                authors = work.get("authors") or []
                author_name = "Unknown Author"
                if authors:
                    author_name = clean_text(authors[0].get("name"), "Unknown Author")

                first_publish_year = work.get("first_publish_year")
                if first_publish_year is None:
                    first_publish_year = "NULL"

                subjects = work.get("subject") or []
                subject_tail = ", ".join(subjects[:6]) if subjects else category_name
                description = clean_text(
                    work.get("description"),
                    f"{title} by {author_name}. Category focus: {subject_tail}.",
                )
                description = description[:4000]

                isbn = ""
                identifiers = work.get("availability", {}).get("isbn")
                if isinstance(identifiers, list) and identifiers:
                    isbn = clean_text(identifiers[0])[:20]

                publishers = work.get("publishers") or []
                publisher = clean_text(publishers[0], "OpenLibrary") if publishers else "OpenLibrary"

                language = "English"
                if "armenian" in " ".join(subjects).lower():
                    language = "Armenian"

                record = {
                    "category": category_name,
                    "title": title[:255],
                    "author": author_name[:255],
                    "description": description,
                    "cover_url": cover_url(work.get("cover_id"))[:255],
                    "isbn": isbn,
                    "publisher": publisher[:255],
                    "publish_year": first_publish_year,
                    "language": language[:100],
                    "page_count": "NULL",
                    "author_bio": "",
                    "format": "Paperback",
                    "edition": "Standard Edition",
                }
                category_books.append(record)
                if len(category_books) >= BOOKS_PER_CATEGORY:
                    break
            offset += 100
            time.sleep(0.15)
        all_data.extend(category_books)
    return all_data


def build_sql(owner_id: int, books):
    lines = []
    lines.append("USE ssk;")
    lines.append("SET FOREIGN_KEY_CHECKS=0;")
    lines.append("TRUNCATE TABLE user_books;")
    lines.append("TRUNCATE TABLE books;")
    lines.append("TRUNCATE TABLE categories;")
    lines.append("SET FOREIGN_KEY_CHECKS=1;")
    lines.append("")

    for category_name, _ in SUBJECTS:
        slug = slugify_ascii(category_name)
        lines.append(
            "INSERT INTO categories (name, slug, name_i18n) VALUES ('{name}', '{slug}', '{i18n}');".format(
                name=sql_escape(category_name),
                slug=sql_escape(slug),
                i18n=category_i18n_json(category_name),
            )
        )
    lines.append("")

    for b in books:
        publish_year = b["publish_year"]
        publish_year_sql = str(publish_year) if isinstance(publish_year, int) else "NULL"
        lines.append(
            "INSERT INTO books (user_id, title, author, description, cover_url, category_id, isbn, publisher, publish_year, language, page_count, author_bio, format, edition) "
            "VALUES ({owner_id}, '{title}', '{author}', '{description}', '{cover_url}', "
            "(SELECT id FROM categories WHERE name='{category}' LIMIT 1), '{isbn}', '{publisher}', {publish_year}, '{language}', {page_count}, '{author_bio}', '{format}', '{edition}');".format(
                owner_id=owner_id,
                title=sql_escape(b["title"]),
                author=sql_escape(b["author"]),
                description=sql_escape(b["description"]),
                cover_url=sql_escape(b["cover_url"]),
                category=sql_escape(b["category"]),
                isbn=sql_escape(b["isbn"]),
                publisher=sql_escape(b["publisher"]),
                publish_year=publish_year_sql,
                language=sql_escape(b["language"]),
                page_count=b["page_count"],
                author_bio=sql_escape(b["author_bio"]),
                format=sql_escape(b["format"]),
                edition=sql_escape(b["edition"]),
            )
        )
    return "\n".join(lines) + "\n"


def main():
    if len(sys.argv) != 2:
        print("Usage: generate_books_sql.py <owner_user_id>")
        sys.exit(1)

    owner_id = int(sys.argv[1])
    books = collect_books()
    if len(books) < len(SUBJECTS) * BOOKS_PER_CATEGORY:
        print(f"Warning: collected only {len(books)} books.")

    out_dir = Path("scripts/generated")
    out_dir.mkdir(parents=True, exist_ok=True)
    dataset_path = out_dir / "books_dataset.json"
    sql_path = out_dir / "seed_books.sql"

    dataset_path.write_text(json.dumps(books, ensure_ascii=False, indent=2), encoding="utf-8")
    sql_path.write_text(build_sql(owner_id, books), encoding="utf-8")
    print(f"Wrote dataset: {dataset_path} ({len(books)} books)")
    print(f"Wrote SQL: {sql_path}")


if __name__ == "__main__":
    main()
