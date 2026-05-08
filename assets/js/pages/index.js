const booksGrid = document.getElementById('booksGrid');
const sentinel = document.getElementById('loadMoreSentinel');
const loadMoreBtn = document.getElementById('loadMoreBtn');
const searchInput = document.getElementById('searchInput');
const categoryButtons = document.querySelectorAll('.cat-filter');
const dynamicNoResults = document.getElementById('dynamicNoResults');

let loading = false;
let hasMore = true;
let offset = 0;
let limit = 20;
let currentSearch = '';
let currentCategory = 'all';
let searchDebounce = null;

if (booksGrid) {
    offset = parseInt(booksGrid.dataset.offset || '20', 10);
    limit = parseInt(booksGrid.dataset.limit || '20', 10);
}

function updateNoResultsVisibility() {
    if (!dynamicNoResults || !booksGrid) return;
    dynamicNoResults.style.display = booksGrid.children.length === 0 ? 'block' : 'none';
}

function buildUrl() {
    const params = new URLSearchParams({
        offset: String(offset),
        limit: String(limit),
        category: currentCategory,
        q: currentSearch,
    });
    return `/library/load-books.php?${params.toString()}`;
}

async function loadMoreBooks() {
    if (!booksGrid || loading || !hasMore) return;
    loading = true;

    const url = buildUrl();

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
        updateNoResultsVisibility();

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

async function resetAndReloadBooks() {
    if (!booksGrid) return;
    offset = 0;
    hasMore = true;
    booksGrid.innerHTML = '';
    if (sentinel) sentinel.style.display = '';
    if (loadMoreBtn) {
        loadMoreBtn.style.display = '';
        loadMoreBtn.disabled = false;
    }
    await loadMoreBooks();
}

if (searchInput) {
    searchInput.addEventListener('input', (e) => {
        currentSearch = (e.target.value || '').trim().toLowerCase();
        if (searchDebounce) clearTimeout(searchDebounce);
        searchDebounce = setTimeout(() => {
            resetAndReloadBooks();
        }, 220);
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
        currentCategory = btn.getAttribute('data-category') || 'all';
        resetAndReloadBooks();
    });
});

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
updateNoResultsVisibility();
