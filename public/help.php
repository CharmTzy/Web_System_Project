<?php

declare(strict_types=1);

$config = require dirname(__DIR__) . '/bootstrap.php';

$database = new \App\Support\Database($config['database']);
$connection = $database->connection();

$helpRepository = $connection
    ? new \App\Repositories\HelpCenterRepository($connection)
    : new \App\Repositories\SampleHelpCenterRepository();
$helpService = new \App\Services\HelpCenterService(
    $helpRepository,
    $connection ? 'mysql' : 'sample'
);

try {
    $helpData = $helpService->browse($_GET);
} catch (Throwable) {
    $helpData = (new \App\Services\HelpCenterService(
        new \App\Repositories\SampleHelpCenterRepository(),
        'sample'
    ))->browse($_GET);
}

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
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display&amp;family=Sora:wght@400;500;600;700&amp;family=Source+Sans+3:wght@400;600;700&amp;display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body class="help-page--market page-loading">
    <?= render('partials/page-skeleton') ?>
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
    <header class="site-header site-header--market">
        <div class="container">
            <div class="site-header__main site-header__main--market">
                <div class="site-header__start">
                    <button
                        class="mobile-menu-toggle"
                        type="button"
                        data-bs-toggle="offcanvas"
                        data-bs-target="#mobileNavDrawer"
                        aria-controls="mobileNavDrawer"
                        aria-label="Open navigation menu"
                    >
                        <span></span>
                        <span></span>
                        <span></span>
                    </button>
                    <a class="site-nav__brand-link site-nav__brand-link--market" href="/index.html" aria-label="NovaMarket home">
                        <span class="site-nav__brand site-nav__brand--market">NovaMarket</span>
                    </a>
                </div>

                <form class="header-search header-search--market" action="/index.html" method="get" role="search">
                    <label class="visually-hidden" for="header-search-input">Search the product catalog</label>
                    <input
                        id="header-search-input"
                        class="header-search__input"
                        type="search"
                        name="search"
                        placeholder="Search for anything"
                    >
                    <button class="header-search__button header-search__button--market" type="submit" aria-label="Search">&#8981;</button>
                </form>

                <div class="site-header__actions site-header__actions--market">
                    <a class="header-action-link" href="/login.php">Sign in</a>
                    <a class="header-action-link" href="/index.html#discover-shops">Favorites</a>
                    <a class="header-action-link" href="/index.html?sort=featured#catalog-feed">Gift ideas</a>
                    <a class="header-cart-link header-cart-link--market" href="/cart.html">
                        <span>Cart</span>
                        <strong data-cart-count>0</strong>
                    </a>
                </div>
            </div>
            <nav class="market-subnav" aria-label="Featured links">
                <a href="/index.html?sort=featured#catalog-feed">Gift Mode</a>
                <a href="/index.html?sort=newest#catalog-feed">Halloween Shop</a>
                <a href="/index.html?category=home-living#catalog-feed">Home Favorites</a>
                <a href="/index.html?category=lifestyle#catalog-feed">Fashion Finds</a>
                <a href="/register.php">Registry</a>
                <a href="/help.php">Help</a>
            </nav>
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

    <div class="offcanvas offcanvas-start mobile-drawer" tabindex="-1" id="mobileNavDrawer" aria-labelledby="mobileNavDrawerLabel">
        <div class="offcanvas-header">
            <div>
                <span class="hero-section__eyebrow">NovaMarket</span>
                <h2 class="offcanvas-title" id="mobileNavDrawerLabel">Browse menu</h2>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">
            <nav class="mobile-drawer__nav" aria-label="Mobile site navigation">
                <a href="/index.html?sort=featured#catalog-feed" data-bs-dismiss="offcanvas">Gift Mode</a>
                <a href="/index.html?sort=newest#catalog-feed" data-bs-dismiss="offcanvas">Halloween Shop</a>
                <a href="/index.html?category=home-living#catalog-feed" data-bs-dismiss="offcanvas">Home Favorites</a>
                <a href="/index.html?category=lifestyle#catalog-feed" data-bs-dismiss="offcanvas">Fashion Finds</a>
                <a href="/register.php" data-bs-dismiss="offcanvas">Registry</a>
                <a href="/help.php" data-bs-dismiss="offcanvas">Help</a>
            </nav>
            <div class="mobile-drawer__links">
                <a href="/login.php" data-bs-dismiss="offcanvas">Sign in</a>
                <a href="/index.html#discover-shops" data-bs-dismiss="offcanvas">Favorites</a>
                <a href="/index.html?sort=featured#catalog-feed" data-bs-dismiss="offcanvas">Gift ideas</a>
                <a href="/cart.html">Cart</a>
            </div>
        </div>
    </div>

    <footer class="site-footer">
        <div class="container">
            <p class="mb-0">Copyright &copy; 2026 NovaMarket. All rights reserved.</p>
        </div>
    </footer>

    <div class="status-toast" data-status-toast role="status" aria-live="polite"></div>
    <div class="visually-hidden" id="cart-live-region" aria-live="polite"></div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script src="/assets/js/session-header.js"></script>
    <script src="/assets/js/store.js"></script>
</body>
</html>
