/**
 * CSP-friendly UI helpers (no inline event handlers).
 * - Image fallback via data-ssk-placeholder
 * - Form confirm via data-ssk-confirm
 * - Auto-submit selects with .ssk-autosubmit
 */
(function () {
    'use strict';

    document.addEventListener(
        'error',
        function (e) {
            var t = e.target;
            if (!t || t.tagName !== 'IMG') {
                return;
            }
            var ph = t.getAttribute('data-ssk-placeholder');
            if (!ph || t.dataset.sskPhApplied === '1') {
                return;
            }
            t.dataset.sskPhApplied = '1';
            t.setAttribute('src', ph);
        },
        true
    );

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('select.ssk-autosubmit').forEach(function (sel) {
            sel.addEventListener('change', function () {
                if (sel.form) {
                    sel.form.submit();
                }
            });
        });

        document.querySelectorAll('form[data-ssk-confirm]').forEach(function (form) {
            form.addEventListener('submit', function (e) {
                if (form.dataset.sskConfirmed === '1') {
                    return;
                }
                e.preventDefault();
                var msg = form.getAttribute('data-ssk-confirm');
                if (!msg || window.confirm(msg)) {
                    form.dataset.sskConfirmed = '1';
                    if (typeof form.requestSubmit === 'function') {
                        form.requestSubmit();
                    } else {
                        form.submit();
                    }
                }
            });
        });
    });
})();
