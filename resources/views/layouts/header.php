<?php

declare(strict_types=1);

$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$pageTitle = $pageTitle ?? 'Shop';
$appName = $appName ?? 'NovaMarket';
$headerSearchValue = $headerSearchValue ?? '';
$cartSummary = $cartSummary ?? [
    'total_items' => 0,
];
$notificationCount = $notificationCount ?? 0;
$bodyClasses = trim(((string) ($bodyClass ?? '')) . ' page-loading');
$guestCartUrl = '/login.php?redirect=' . rawurlencode('/cart.html');

$isLoggedIn = !empty($_SESSION['user_id']);
$sessionRole = $_SESSION['user_role'] ?? '';
$sessionName = $_SESSION['user_name'] ?? '';
$notificationsUrl = '/profile.php#notifications';
$isCustomer = $isLoggedIn && $sessionRole === 'customer';
$isSeller = $isLoggedIn && $sessionRole === 'seller';
$isAdmin = $isLoggedIn && $sessionRole === 'admin';

$dashboardUrl = '/profile.php';
if ($isAdmin) {
    $dashboardUrl = '/admin/';
} elseif ($isSeller) {
    $dashboardUrl = '/seller/';
}

$marketNavLinks = [
    ['label' => 'Home Favorites', 'href' => '/?category=home-living', 'active' => false],
    ['label' => 'Fashion Finds', 'href' => '/?category=lifestyle', 'active' => false],
];

if ($isCustomer) {
    $marketNavLinks[] = ['label' => 'Coupons', 'href' => '/customer/coupons.php', 'active' => $currentPath === '/customer/coupons.php'];
    $marketNavLinks[] = ['label' => 'Addresses', 'href' => '/customer/addresses.php', 'active' => $currentPath === '/customer/addresses.php'];
} else {
    $profileShortcut = ['label' => 'Registry', 'href' => '/register.php', 'active' => $currentPath === '/register.php'];

    if ($isSeller) {
        $profileShortcut = ['label' => 'Store', 'href' => '/seller/store-profile.php', 'active' => $currentPath === '/seller/store-profile.php'];
    } elseif ($isAdmin) {
        $profileShortcut = ['label' => 'Dashboard', 'href' => '/admin/', 'active' => str_starts_with($currentPath, '/admin')];
    }

    $marketNavLinks[] = $profileShortcut;
}

$marketNavLinks[] = ['label' => 'Help', 'href' => '/help.php', 'active' => $currentPath === '/help.php'];

$mobileAccountLinks = [];
$pageSkeletonVariant = $pageSkeletonVariant ?? match (true) {
    $currentPath === '/profile.php',
    $currentPath === '/seller/store-profile.php',
    $currentPath === '/admin/user-edit.php' => 'form',
    default => 'panel',
};

if ($isLoggedIn) {
    if ($isCustomer) {
        $mobileAccountLinks = [
            ['label' => 'Account', 'href' => '/profile.php'],
            ['label' => 'Notification', 'href' => $notificationsUrl],
            ['label' => 'Cart', 'href' => '/cart.html'],
            ['label' => 'Sign out', 'href' => '/logout.php'],
        ];
    } else {
        $mobileAccountLinks = [
            ['label' => sprintf('%s (%s)', $sessionName, ucfirst($sessionRole)), 'href' => $dashboardUrl],
            ['label' => 'Profile', 'href' => '/profile.php'],
            ['label' => 'Sign out', 'href' => '/logout.php'],
            ['label' => 'Cart', 'href' => '/cart.html'],
        ];
    }
} else {
    $mobileAccountLinks = [
        ['label' => 'Cart', 'href' => $guestCartUrl],
        ['label' => 'Sign in', 'href' => '/login.php'],
    ];
}

$renderHeaderIcon = static function (string $icon): string {
    return match ($icon) {
        'account' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 12a4.25 4.25 0 1 0 0-8.5 4.25 4.25 0 0 0 0 8.5Zm-7 8a7 7 0 0 1 14 0" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"/></svg>',
        'notification' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 4.25a4.25 4.25 0 0 1 4.25 4.25v2.15c0 .95.32 1.88.9 2.64l1.1 1.46a.75.75 0 0 1-.6 1.2H6.35a.75.75 0 0 1-.6-1.2l1.1-1.46a4.38 4.38 0 0 0 .9-2.64V8.5A4.25 4.25 0 0 1 12 4.25Zm-1.8 13.5a1.8 1.8 0 0 0 3.6 0" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"/></svg>',
        'cart' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3.5 5.5h2.2l1.5 8.2a1 1 0 0 0 1 .8h8.4a1 1 0 0 0 1-.75l1.4-5.25H7.2M9 19a1.25 1.25 0 1 0 0-2.5A1.25 1.25 0 0 0 9 19Zm7 0a1.25 1.25 0 1 0 0-2.5A1.25 1.25 0 0 0 16 19Z" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"/></svg>',
        'signout' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M10 6.5H7.5A1.5 1.5 0 0 0 6 8v8a1.5 1.5 0 0 0 1.5 1.5H10M13 16.5 17.5 12 13 7.5M17.5 12H9" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"/></svg>',
        default => '',
    };
};
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <title><?= e($pageTitle) ?> | <?= e($appName) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&amp;family=Plus+Jakarta+Sans:wght@500;600;700;800&amp;display=swap"
        rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
</head>

<body class="<?= e($bodyClasses) ?>">
    <?= render('partials/page-skeleton', ['variant' => $pageSkeletonVariant]) ?>
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
                    <button class="mobile-menu-toggle" type="button" data-bs-toggle="offcanvas"
                        data-bs-target="#mobileNavDrawer" aria-controls="mobileNavDrawer"
                        aria-label="Open navigation menu">
                        <span></span>
                        <span></span>
                        <span></span>
                    </button>
                    <a class="site-nav__brand-link site-nav__brand-link--market" href="/" aria-label="NovaMarket home">
                        <span class="site-nav__eyebrow">Everyday style. Smart prices.</span>
                        <span class="site-nav__brand-row">
                            <span class="site-nav__brand-mark" aria-hidden="true">NM</span>
                            <span class="site-nav__brand site-nav__brand--market"><?= e($appName) ?></span>
                        </span>
                    </a>
                </div>

                <form id="header-search-form" class="header-search header-search--market" action="/" method="get"
                    role="search">
                    <label class="visually-hidden" for="header-search-input">Search the product catalog</label>
                    <input id="header-search-input" class="header-search__input" type="search" name="search"
                        value="<?= e($headerSearchValue) ?>" placeholder="Search for anything">
                    <button class="header-search__button header-search__button--market" type="submit"
                        aria-label="Search">&#8981;</button>
                </form>

                <div class="site-header__actions site-header__actions--market">
                    <?php if ($isLoggedIn): ?>
                        <?php if ($isCustomer): ?>
                            <a class="header-icon-action" href="/profile.php">
                                <span class="header-icon-action__icon-wrap">
                                    <?= $renderHeaderIcon('account') ?>
                                </span>
                                <span class="header-icon-action__label">Account</span>
                            </a>
                            <a class="header-icon-action header-icon-action--with-badge" href="<?= e($notificationsUrl) ?>">
                                <span class="header-icon-action__icon-wrap">
                                    <?= $renderHeaderIcon('notification') ?>
                                    <strong class="header-icon-action__badge"
                                        data-notification-count><?= e((string) $notificationCount) ?></strong>
                                </span>
                                <span class="header-icon-action__label">Notification</span>
                            </a>
                            <a class="header-icon-action header-icon-action--with-badge" href="/cart.html"
                                data-open-cart-drawer="true" aria-controls="cartDrawer" aria-haspopup="dialog">
                                <span class="header-icon-action__icon-wrap">
                                    <?= $renderHeaderIcon('cart') ?>
                                    <strong class="header-icon-action__badge"
                                        data-cart-count><?= e((string) $cartSummary['total_items']) ?></strong>
                                </span>
                                <span class="header-icon-action__label">Cart</span>
                            </a>
                            <a class="header-icon-action" href="/logout.php">
                                <span class="header-icon-action__icon-wrap">
                                    <?= $renderHeaderIcon('signout') ?>
                                </span>
                                <span class="header-icon-action__label">Sign out</span>
                            </a>
                        <?php else: ?>
                            <a class="header-action-link" href="<?= e($dashboardUrl) ?>"><?= e($sessionName) ?></a>
                            <span class="header-action-badge"><?= e(ucfirst($sessionRole)) ?></span>
                            <a class="header-action-link" href="/logout.php">Sign out</a>
                            <a class="header-cart-link header-cart-link--market" href="/cart.html" data-open-cart-drawer="true"
                                aria-controls="cartDrawer" aria-haspopup="dialog">
                                <span>Cart</span>
                                <strong data-cart-count><?= e((string) $cartSummary['total_items']) ?></strong>
                            </a>
                        <?php endif; ?>
                    <?php else: ?>
                        <a class="header-icon-action header-icon-action--with-badge" href="<?= e($guestCartUrl) ?>"
                            data-open-cart-drawer="true" aria-controls="cartDrawer" aria-haspopup="dialog">
                            <span class="header-icon-action__icon-wrap">
                                <?= $renderHeaderIcon('cart') ?>
                                <strong class="header-icon-action__badge"
                                    data-cart-count><?= e((string) $cartSummary['total_items']) ?></strong>
                            </span>
                            <span class="header-icon-action__label">Cart</span>
                        </a>
                        <a class="header-icon-action" href="/login.php">
                            <span class="header-icon-action__icon-wrap">
                                <?= $renderHeaderIcon('account') ?>
                            </span>
                            <span class="header-icon-action__label">Sign in</span>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <nav class="market-subnav" aria-label="Featured links">
                <?php foreach ($marketNavLinks as $link): ?>
                    <a href="<?= e((string) $link['href']) ?>" <?= !empty($link['active']) ? ' aria-current="page"' : '' ?>><?= e((string) $link['label']) ?></a>
                <?php endforeach; ?>
            </nav>
        </div>
    </header>

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

    <div class="offcanvas offcanvas-start mobile-drawer" tabindex="-1" id="mobileNavDrawer"
        aria-labelledby="mobileNavDrawerLabel">
        <div class="offcanvas-header">
            <div>
                <span class="hero-section__eyebrow">Browse menu</span>
                <h2 class="offcanvas-title" id="mobileNavDrawerLabel">Browse menu</h2>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">
            <nav class="mobile-drawer__nav" aria-label="Mobile site navigation">
                <?php foreach ($marketNavLinks as $link): ?>
                    <a href="<?= e((string) $link['href']) ?>"><?= e((string) $link['label']) ?></a>
                <?php endforeach; ?>
            </nav>
            <div class="mobile-drawer__links">
                <?php foreach ($mobileAccountLinks as $link): ?>
                    <a href="<?= e((string) $link['href']) ?>"><?= e((string) $link['label']) ?></a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
