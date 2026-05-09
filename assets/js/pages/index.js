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
/** Avoid wiping SSR book grid on spurious input (browser autofill, extensions) before user focuses search. */
let searchLiveEnabled = false;
let userInteractedWithSearch = false;
let activeController = null;
let requestSerial = 0;
const responseCache = new Map();
const MAX_CACHE_ENTRIES = 40;

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

function cacheGet(key) {
    if (!responseCache.has(key)) return null;
    const value = responseCache.get(key);
    // Keep most recently used entries warm.
    responseCache.delete(key);
    responseCache.set(key, value);
    return value;
}

function cacheSet(key, value) {
    if (responseCache.has(key)) {
        responseCache.delete(key);
    }
    responseCache.set(key, value);
    if (responseCache.size > MAX_CACHE_ENTRIES) {
        const oldestKey = responseCache.keys().next().value;
        responseCache.delete(oldestKey);
    }
}

function applyBooksResponse(data) {
    if (!booksGrid) return;
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
}

async function loadMoreBooks() {
    if (!booksGrid || loading || !hasMore) return;
    loading = true;

    const url = buildUrl();
    const cacheKey = `${currentCategory}|${currentSearch}|${offset}|${limit}`;
    const cached = cacheGet(cacheKey);
    if (cached) {
        applyBooksResponse(cached);
        loading = false;
        if (loadMoreBtn) loadMoreBtn.disabled = false;
        return;
    }

    try {
        if (loadMoreBtn) loadMoreBtn.disabled = true;
        if (activeController) {
            activeController.abort();
        }
        activeController = new AbortController();
        const thisRequest = ++requestSerial;

        const resp = await fetch(url, {
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            signal: activeController.signal,
        });
        if (!resp.ok) throw new Error(`HTTP ${resp.status}`);

        const data = await resp.json();
        // Ignore stale responses that arrive after a newer request.
        if (thisRequest !== requestSerial) {
            return;
        }
        cacheSet(cacheKey, data);
        applyBooksResponse(data);
    } catch (e) {
        if (e && e.name === 'AbortError') {
            return;
        }
        // Keep UI usable even if loading fails.
        console.error('Failed to load more books', e);
        hasMore = false;
        if (sentinel) sentinel.style.display = 'none';
        if (loadMoreBtn) loadMoreBtn.style.display = 'none';
    } finally {
        loading = false;
        activeController = null;
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
    searchInput.addEventListener('focus', () => {
        userInteractedWithSearch = true;
        searchLiveEnabled = true;
    });
    searchInput.addEventListener('keydown', () => {
        userInteractedWithSearch = true;
        searchLiveEnabled = true;
    });
    searchInput.addEventListener('paste', () => {
        userInteractedWithSearch = true;
        searchLiveEnabled = true;
    });
    searchInput.addEventListener('search', () => {
        userInteractedWithSearch = true;
        searchLiveEnabled = true;
        currentSearch = (searchInput.value || '').trim().toLowerCase();
        resetAndReloadBooks();
    });
    searchInput.addEventListener('input', (e) => {
        const typedValue = (e.target.value || '');
        if (!searchLiveEnabled && (userInteractedWithSearch || typedValue.length > 0)) {
            searchLiveEnabled = true;
        }
        if (!searchLiveEnabled) {
            return;
        }
        currentSearch = typedValue.trim().toLowerCase();
        if (searchDebounce) clearTimeout(searchDebounce);
        searchDebounce = setTimeout(() => {
            resetAndReloadBooks();
        }, 320);
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
