const searchInput = document.getElementById('searchInput');
const categoryButtons = document.querySelectorAll('.cat-filter');

let currentSearch = '';
let currentCategory = 'all';

let items = [];
function refreshItems() {
    items = document.querySelectorAll('.card-item');
}

function filterBooks() {
    refreshItems();
    items.forEach((item) => {
        const title = item.getAttribute('data-title');
        const author = item.getAttribute('data-author');
        const category = item.getAttribute('data-category');

        const matchesSearch = title.includes(currentSearch) || author.includes(currentSearch);
        const matchesCategory = currentCategory === 'all' || category === currentCategory;

        item.style.display = matchesSearch && matchesCategory ? 'block' : 'none';
    });
}

if (searchInput) {
    searchInput.addEventListener('input', (e) => {
        currentSearch = e.target.value.toLowerCase();
        filterBooks();
    });
}

categoryButtons.forEach((btn) => {
    btn.addEventListener('click', () => {
        categoryButtons.forEach((b) => {
            b.classList.remove('btn-primary');
            b.classList.add('btn-outline');
        });
        btn.classList.remove('btn-outline');
        btn.classList.add('btn-primary');

        currentCategory = btn.getAttribute('data-category');
        filterBooks();
    });
});

// --- Lazy load / load-more (20 at a time) ---
const booksGrid = document.getElementById('booksGrid');
const sentinel = document.getElementById('loadMoreSentinel');
const loadMoreBtn = document.getElementById('loadMoreBtn');

let loading = false;
let hasMore = true;
let offset = 0;
let limit = 20;

if (booksGrid) {
    offset = parseInt(booksGrid.dataset.offset || '0', 10);
    limit = parseInt(booksGrid.dataset.limit || '20', 10);
}

async function loadMoreBooks() {
    if (!booksGrid || loading || !hasMore) return;
    loading = true;

    const url = `/library/load-books.php?offset=${encodeURIComponent(offset)}&limit=${encodeURIComponent(limit)}`;

    try {
        if (loadMoreBtn) loadMoreBtn.disabled = true;

        const resp = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
        if (!resp.ok) throw new Error(`HTTP ${resp.status}`);

        const data = await resp.json();
        if (data.html) {
            booksGrid.insertAdjacentHTML('beforeend', data.html);
        }

        hasMore = !!data.has_more;
        if (data.next_offset !== undefined) {
            offset = parseInt(data.next_offset, 10);
        } else {
            offset += limit;
        }

        filterBooks();

        if (!hasMore) {
            if (sentinel) sentinel.style.display = 'none';
            if (loadMoreBtn) loadMoreBtn.style.display = 'none';
        }
    } catch (e) {
        // Keep UI usable even if loading fails.
        console.error('Failed to load more books', e);
        hasMore = false;
        if (sentinel) sentinel.style.display = 'none';
        if (loadMoreBtn) loadMoreBtn.style.display = 'none';
    } finally {
        loading = false;
        if (loadMoreBtn) loadMoreBtn.disabled = false;
    }
}

if (loadMoreBtn) {
    loadMoreBtn.addEventListener('click', loadMoreBooks);
}

if (sentinel && 'IntersectionObserver' in window) {
    const io = new IntersectionObserver((entries) => {
        if (entries.some((e) => e.isIntersecting)) {
            loadMoreBooks();
        }
    }, { rootMargin: '600px 0px' });
    io.observe(sentinel);
}

// Initial filter state.
refreshItems();
filterBooks();
