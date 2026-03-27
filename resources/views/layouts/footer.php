<?php

declare(strict_types=1);
?>
    <footer class="site-footer">
        <div class="container">
            <p class="site-footer__copyright mb-0">Copyright &copy; 2026 NovaMarket. All rights reserved.</p>
        </div>
    </footer>

    <div class="status-toast" data-status-toast role="status" aria-live="polite"></div>
    <div class="visually-hidden" id="cart-live-region" aria-live="polite"></div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script src="<?= e(asset('js/store.js')) ?>"></script>
    <?php if (!empty($pageScript)): ?>
        <script src="<?= e(asset('js/' . $pageScript)) ?>"></script>
    <?php endif; ?>
</body>
</html>
