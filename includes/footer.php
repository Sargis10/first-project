<?php
$footerLoggedIn = function_exists('isLoggedIn') && isLoggedIn();
$footerAdmin = $footerLoggedIn && function_exists('isAdmin') && isAdmin();
?>
    <footer class="site-footer">
        <div class="container site-footer__main">
            <div class="site-footer__brand">
                <a href="<?= htmlspecialchars(sskUrl('home')) ?>" class="site-footer__logo">Libris</a>
                <p class="site-footer__tagline"><?= htmlspecialchars(t('footer.tagline')) ?></p>
                <p class="site-footer__made"><?= htmlspecialchars(t('footer.made_with')) ?></p>
            </div>
            <div class="site-footer__col">
                <h4 class="site-footer__heading"><?= htmlspecialchars(t('footer.col_explore')) ?></h4>
                <ul class="site-footer__links">
                    <li><a href="<?= htmlspecialchars(sskUrl('home')) ?>"><?= htmlspecialchars(t('footer.link_catalog')) ?></a></li>
                    <li><a href="<?= htmlspecialchars(sskUrl('about')) ?>"><?= htmlspecialchars(t('footer.link_about')) ?></a></li>
                    <li><a href="<?= htmlspecialchars(sskUrl('contact')) ?>"><?= htmlspecialchars(t('footer.link_contact')) ?></a></li>
                </ul>
            </div>
            <div class="site-footer__col">
                <h4 class="site-footer__heading"><?= htmlspecialchars(t('footer.col_product')) ?></h4>
                <ul class="site-footer__links">
                    <?php if ($footerLoggedIn): ?>
                        <li><a href="<?= htmlspecialchars(sskUrl('shelf')) ?>"><?= htmlspecialchars(t('nav.my_library')) ?></a></li>
                        <li><a href="<?= htmlspecialchars(sskUrl('activity')) ?>"><?= htmlspecialchars(t('footer.link_stats')) ?></a></li>
                        <?php if ($footerAdmin): ?>
                            <li><a href="<?= htmlspecialchars(sskUrl('manage')) ?>"><?= htmlspecialchars(t('nav.dashboard')) ?></a></li>
                            <li><a href="<?= htmlspecialchars(sskUrl('write')) ?>"><?= htmlspecialchars(t('nav.add_book')) ?></a></li>
                        <?php endif; ?>
                    <?php else: ?>
                        <li><a href="<?= htmlspecialchars(sskUrl('sign_in')) ?>"><?= htmlspecialchars(t('footer.link_login')) ?></a></li>
                        <li><a href="<?= htmlspecialchars(sskUrl('sign_up')) ?>"><?= htmlspecialchars(t('footer.link_register')) ?></a></li>
                    <?php endif; ?>
                </ul>
            </div>
            <div class="site-footer__col">
                <h4 class="site-footer__heading"><?= htmlspecialchars(t('footer.col_trust')) ?></h4>
                <ul class="site-footer__checks">
                    <li><?= htmlspecialchars(t('footer.trust_csrf')) ?></li>
                    <li><?= htmlspecialchars(t('footer.trust_passwords')) ?></li>
                    <li><?= htmlspecialchars(t('footer.trust_env')) ?></li>
                </ul>
            </div>
            <div class="site-footer__col site-footer__col--wide">
                <h4 class="site-footer__heading"><?= htmlspecialchars(t('footer.col_contact')) ?></h4>
                <p class="site-footer__blurb"><?= htmlspecialchars(t('footer.contact_line')) ?></p>
            </div>
        </div>
        <div class="site-footer__bottom">
            <div class="container">
                <p class="site-footer__copyright">&copy; <?= date('Y') ?> Libris. <?= htmlspecialchars(t('footer.rights')) ?></p>
            </div>
        </div>
    </footer>
    <?php if (isset($pageScripts) && is_array($pageScripts)): ?>
        <?php foreach ($pageScripts as $scriptPath): ?>
            <script src="<?= htmlspecialchars(sskAssetHref((string) $scriptPath)) ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>
