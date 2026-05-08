const searchInput = document.getElementById('searchInput');
const categoryButtons = document.querySelectorAll('.cat-filter');
const items = document.querySelectorAll('.card-item');

let currentSearch = '';
let currentCategory = 'all';

function filterBooks() {
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
