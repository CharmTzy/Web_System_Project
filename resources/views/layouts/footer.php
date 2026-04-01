<?php

declare(strict_types=1);
?>
    <?php if (!empty($isConsoleArea)): ?>
        <footer class="site-footer site-footer--admin">
            <div class="container-fluid">
                <p class="site-footer__copyright mb-0">Copyright &copy; 2026 NovaMarket. All rights reserved.</p>
            </div>
        </footer>
            </div>
        </div>
    <?php else: ?>
    <footer class="site-footer">
        <div class="container">
            <nav class="site-footer__links" aria-label="Legal and contact links">
                <a href="/privacy.php"<?= ($currentPath ?? '') === '/privacy.php' ? ' aria-current="page"' : '' ?>>Privacy Policy</a>
                <a href="/terms.php"<?= ($currentPath ?? '') === '/terms.php' ? ' aria-current="page"' : '' ?>>Terms</a>
                <a href="/about.php"<?= ($currentPath ?? '') === '/about.php' ? ' aria-current="page"' : '' ?>>About Us</a>
                <a href="/contact.php"<?= ($currentPath ?? '') === '/contact.php' ? ' aria-current="page"' : '' ?>>Contact</a>
            </nav>
            <p class="site-footer__copyright mb-0">Copyright &copy; 2026 NovaMarket. All rights reserved.</p>
        </div>
    </footer>
    <?php endif; ?>

    <div class="status-toast" data-status-toast role="status" aria-live="polite"></div>
    <div class="visually-hidden" id="cart-live-region" aria-live="polite"></div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <?php if (empty($isConsoleArea)): ?>
        <script src="<?= e(asset('js/store.js')) ?>"></script>
    <?php endif; ?>
    <?php if (!empty($isConsoleArea)): ?>
        <script src="<?= e(asset('js/admin-shell.js')) ?>"></script>
    <?php endif; ?>
    <?php if (!empty($pageScript)): ?>
        <script src="<?= e(asset('js/' . $pageScript)) ?>"></script>
    <?php endif; ?>
</body>
</html>
