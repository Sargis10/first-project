(function () {
    document.addEventListener('DOMContentLoaded', function () {
        var wrap = document.querySelector('.lang-menu');
        if (!wrap) return;
        var btn = wrap.querySelector('.lang-menu__trigger');
        var panel = wrap.querySelector('.lang-menu__panel');
        if (!btn || !panel) return;

        btn.addEventListener('click', function (e) {
            e.stopPropagation();
            var open = wrap.classList.toggle('lang-menu--open');
            btn.setAttribute('aria-expanded', open ? 'true' : 'false');
        });

        document.addEventListener('click', function () {
            wrap.classList.remove('lang-menu--open');
            btn.setAttribute('aria-expanded', 'false');
        });

        wrap.addEventListener('click', function (e) {
            e.stopPropagation();
        });

        panel.querySelectorAll('a[role="menuitem"]').forEach(function (a) {
            a.addEventListener('click', function () {
                wrap.classList.remove('lang-menu--open');
                btn.setAttribute('aria-expanded', 'false');
            });
        });
    });
})();
