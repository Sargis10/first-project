    <footer>
        <div class="container">
            <p>&copy; <?= date('Y') ?> Libris. <?= htmlspecialchars(t('footer.rights')) ?></p>
        </div>
    </footer>
    <?php if (isset($pageScripts) && is_array($pageScripts)): ?>
        <?php foreach ($pageScripts as $scriptPath): ?>
            <?php
                $resolvedScriptPath = $scriptPath;
                if (!preg_match('/^https?:\/\//', $resolvedScriptPath) && strpos($resolvedScriptPath, '/') !== 0) {
                    $resolvedScriptPath = '/' . ltrim($resolvedScriptPath, '/');
                }
            ?>
            <script src="<?= htmlspecialchars($resolvedScriptPath) ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>