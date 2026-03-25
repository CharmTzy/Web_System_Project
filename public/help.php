<?php

declare(strict_types=1);

$config = require dirname(__DIR__) . '/bootstrap.php';

$database = new \App\Support\Database($config['database']);
$connection = $database->connection();

try {
    if (!$connection) {
        throw new RuntimeException('Database connection required.');
    }

    $helpData = (new \App\Services\HelpCenterService(
        new \App\Repositories\HelpCenterRepository($connection),
        'mysql'
    ))->browse($_GET);
} catch (Throwable) {
    $search = trim((string) ($_GET['search'] ?? ''));
    $search = mb_substr($search, 0, 80);
    $category = trim((string) ($_GET['category'] ?? ''));
    $category = preg_replace('/[^a-z0-9-]/i', '', $category) ?? '';

    $helpData = [
        'filters' => [
            'search' => $search,
            'category' => $category,
        ],
        'categories' => [],
        'hot_questions' => [],
        'source' => 'unavailable',
    ];
}

$hasHelpFilters = trim((string) ($_GET['category'] ?? '')) !== ''
    || trim((string) ($_GET['search'] ?? '')) !== '';
$usePageSkeleton = !$hasHelpFilters;
$pageTitle = 'Help Center';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?> | <?= e($config['app']['name']) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&amp;family=Plus+Jakarta+Sans:wght@500;600;700;800&amp;display=swap"
        rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="/assets/css/app.css">
</head>

<body class="help-page--market<?= $usePageSkeleton ? ' page-loading' : '' ?>">
    <?php if ($usePageSkeleton): ?>
        <?= render('partials/page-skeleton', ['variant' => 'help']) ?>
        <script>
            (() => {
                const minimumDelay = 900;
                const startedAt = window.performance?.now?.() ?? Date.now();
                let revealScheduled = false;

                const revealPage = () => {
                    const body = document.body;

                    if (!body) {
                        return;
                    }

                    body.classList.remove('page-loading');
                    body.classList.add('page-ready');

                    window.setTimeout(() => {
                        document.querySelectorAll('[data-page-skeleton]').forEach((node) => node.remove());
                    }, 320);
                };

                const scheduleReveal = () => {
                    if (revealScheduled) {
                        return;
                    }

                    revealScheduled = true;

                    const now = window.performance?.now?.() ?? Date.now();
                    const remaining = Math.max(0, minimumDelay - (now - startedAt));

                    window.setTimeout(revealPage, remaining);
                };

                window.__novaRevealPageShell = revealPage;

                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', scheduleReveal, { once: true });
                } else {
                    scheduleReveal();
                }

                window.addEventListener('load', scheduleReveal, { once: true });
            })();
        </script>
    <?php endif; ?>
    <header class="site-header site-header--market site-header--help-simple">
        <div class="container">
            <div class="site-header__main--help">
                <a class="site-nav__brand-link site-nav__brand-link--market" href="/" aria-label="NovaMarket home">
                    <span class="site-nav__eyebrow">Everyday style. Smart prices.</span>
                    <span class="site-nav__brand-row">
                        <span class="site-nav__brand-mark" aria-hidden="true">NM</span>
                        <span class="site-nav__brand site-nav__brand--market">NovaMarket</span>
                    </span>
                </a>
            </div>
        </div>
    </header>

    <main class="storefront-home storefront-home--market help-page-shell">
        <?= render('help/center', $helpData) ?>
    </main>

    <div class="offcanvas offcanvas-end cart-drawer" tabindex="-1" id="cartDrawer" aria-labelledby="cartDrawerLabel">
        <div class="offcanvas-header">
            <div>
                <span class="hero-section__eyebrow">Cart preview</span>
                <h2 class="offcanvas-title" id="cartDrawerLabel">Your cart</h2>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body" data-cart-drawer></div>
    </div>

    <footer class="site-footer">
        <div class="container">
            <p class="mb-0">Copyright &copy; 2026 NovaMarket. All rights reserved.</p>
        </div>
    </footer>

    <div class="status-toast" data-status-toast role="status" aria-live="polite"></div>
    <div class="visually-hidden" id="cart-live-region" aria-live="polite"></div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
        crossorigin="anonymous"></script>
    <script src="/assets/js/store.js"></script>
</body>

</html>