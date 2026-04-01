<?php

declare(strict_types=1);

$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$pageTitle = $pageTitle ?? 'Shop';
$appName = $appName ?? 'NovaMarket';
$headerSearchValue = $headerSearchValue ?? '';
$cartSummary = $cartSummary ?? [
    'total_items' => 0,
];

$isLoggedIn = !empty($_SESSION['user_id']);
$sessionRole = $_SESSION['user_role'] ?? '';
$sessionName = $_SESSION['user_name'] ?? '';
<<<<<<< Updated upstream
=======
$notificationsUrl = '/profile.php#notifications';
$isCustomer = $isLoggedIn && $sessionRole === 'customer';
$isSeller = $isLoggedIn && $sessionRole === 'seller';
$isAdmin = $isLoggedIn && $sessionRole === 'admin';
$isAdminArea = $isAdmin && str_starts_with($currentPath, '/admin/');
$isSellerArea = $isSeller && str_starts_with($currentPath, '/seller/');
$isConsoleArea = $isAdminArea || $isSellerArea;

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
    $marketNavLinks[] = ['label' => 'Orders', 'href' => '/customer/orders.php', 'active' => $currentPath === '/customer/orders.php'];
    $marketNavLinks[] = ['label' => 'Chat', 'href' => '/customer/chat.php', 'active' => $currentPath === '/customer/chat.php'];
    $marketNavLinks[] = ['label' => 'Addresses', 'href' => '/customer/addresses.php', 'active' => $currentPath === '/customer/addresses.php'];
} else {
    $profileShortcut = ['label' => 'Registry', 'href' => '/register.php', 'active' => $currentPath === '/register.php'];

    if ($isSeller) {
        $profileShortcut = ['label' => 'Store', 'href' => '/seller/store-profile.php', 'active' => $currentPath === '/seller/store-profile.php'];
    } elseif ($isAdmin) {
        $profileShortcut = ['label' => 'Dashboard', 'href' => '/admin/', 'active' => str_starts_with($currentPath, '/admin')];
    }

    $marketNavLinks[] = $profileShortcut;

    if ($isSeller) {
        $marketNavLinks[] = ['label' => 'Orders', 'href' => '/seller/orders.php', 'active' => $currentPath === '/seller/orders.php' || $currentPath === '/seller/order-view.php'];
        $marketNavLinks[] = ['label' => 'Chat', 'href' => '/seller/chat.php', 'active' => $currentPath === '/seller/chat.php'];
    } elseif ($isAdmin) {
        $marketNavLinks[] = ['label' => 'Chat', 'href' => '/admin/chat.php', 'active' => $currentPath === '/admin/chat.php'];
    }
}

$marketNavLinks[] = ['label' => 'Help', 'href' => '/help.php', 'active' => $currentPath === '/help.php'];

$paymentsInTestMode = payments_use_test_mode(isset($config['app']) ? $config['app'] : null);
$showPaymentTestModeNotice = !$isConsoleArea && $paymentsInTestMode;

$mobileAccountLinks = [];
$pageSkeletonVariant = $pageSkeletonVariant ?? match (true) {
    $isAdminArea && ($currentPath === '/admin/' || $currentPath === '/admin/index.php') => 'admin-dashboard',
    $isAdminArea && in_array($currentPath, [
        '/admin/profile.php',
        '/admin/user-edit.php',
        '/admin/product-edit.php',
        '/admin/address-edit.php',
        '/admin/coupon-edit.php',
        '/admin/help-question-edit.php',
    ], true) => 'admin-form',
    $isAdminArea && $currentPath === '/admin/chat.php' => 'admin-chat',
    $isAdminArea => 'admin-table',
    $isSellerArea && ($currentPath === '/seller/' || $currentPath === '/seller/index.php') => 'admin-dashboard',
    $isSellerArea && in_array($currentPath, [
        '/seller/product-edit.php',
        '/seller/store-profile.php',
        '/seller/order-view.php',
    ], true) => 'admin-form',
    $isSellerArea && $currentPath === '/seller/chat.php' => 'admin-chat',
    $isSellerArea => 'admin-table',
    $currentPath === '/customer/orders.php' => 'orders',
    $currentPath === '/customer/addresses.php' => 'addresses',
    $currentPath === '/customer/checkout.php' => 'checkout',
    $currentPath === '/seller/index.php' => 'seller-dashboard',
    $currentPath === '/seller/products.php' => 'market-table',
    $currentPath === '/seller/product-edit.php' => 'form',
    in_array($currentPath, ['/privacy.php', '/terms.php', '/contact.php', '/about.php'], true) => 'legal',
    $currentPath === '/profile.php',
    $currentPath === '/seller/store-profile.php' => 'form',
    default => 'panel',
};
$bodyClasses = trim($bodyClasses . ($isAdminArea ? ' admin-body' : '') . ($isSellerArea ? ' admin-body seller-body' : ''));

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

if (
    $robotsMeta === ''
    && (
        str_starts_with($currentPath, '/customer/')
        || str_starts_with($currentPath, '/seller/')
        || str_starts_with($currentPath, '/admin/')
        || $currentPath === '/profile.php'
    )
) {
    $robotsMeta = 'noindex, nofollow, noarchive';
}

if ($robotsMeta !== '' && !headers_sent()) {
    header('X-Robots-Tag: ' . $robotsMeta);
}
>>>>>>> Stashed changes
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
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;500;600;700&amp;family=Source+Sans+3:wght@400;600;700&amp;display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
</head>
<body class="<?= e($bodyClass ?? '') ?>">
    <header class="site-header">
        <div class="container">
            <div class="site-header__main">
                <a class="site-nav__brand-link" href="/index.html">
                    <span class="site-nav__eyebrow">Everyday style. Smart prices.</span>
                    <span class="site-nav__brand"><?= e($appName) ?></span>
                </a>

                <form class="header-search" action="/index.html" method="get" role="search">
                    <label class="visually-hidden" for="header-search-input">Search the product catalog</label>
                    <input
                        id="header-search-input"
                        class="header-search__input"
                        type="search"
                        name="search"
                        value="<?= e($headerSearchValue) ?>"
                        placeholder="Search products, brands, or categories"
                    >
                    <button class="btn btn-brand header-search__button" type="submit">Search</button>
                </form>

                <div class="site-header__actions">
                    <a class="header-cart-link" href="/cart.html">
                        <span>Cart</span>
                        <strong data-cart-count><?= e((string) $cartSummary['total_items']) ?></strong>
                    </a>
                    <button class="btn btn-brand-outline" type="button" data-bs-toggle="offcanvas" data-bs-target="#cartDrawer" aria-controls="cartDrawer">
                        Quick cart
                    </button>
                </div>
            </div>

            <div class="site-header__navrow">
                <nav class="header-nav" aria-label="Primary">
                    <a class="nav-link <?= $currentPath === '/' || $currentPath === '/index.html' ? 'active' : '' ?>" href="/index.html">Shop</a>
                    <a class="nav-link <?= $currentPath === '/cart.html' ? 'active' : '' ?>" href="/cart.html">Cart</a>
                    <?php if ($isLoggedIn && $sessionRole === 'admin'): ?>
                        <a class="nav-link <?= str_starts_with($currentPath, '/admin') ? 'active' : '' ?>" href="/admin/">Dashboard</a>
                        <a class="nav-link <?= $currentPath === '/admin/users.php' ? 'active' : '' ?>" href="/admin/users.php">Users</a>
                    <?php elseif ($isLoggedIn && $sessionRole === 'seller'): ?>
                        <a class="nav-link <?= str_starts_with($currentPath, '/seller') ? 'active' : '' ?>" href="/seller/">Dashboard</a>
                        <a class="nav-link <?= $currentPath === '/seller/store-profile.php' ? 'active' : '' ?>" href="/seller/store-profile.php">Store</a>
                    <?php elseif ($isLoggedIn && $sessionRole === 'customer'): ?>
                        <a class="nav-link <?= $currentPath === '/customer/addresses.php' ? 'active' : '' ?>" href="/customer/addresses.php">Addresses</a>
                    <?php endif; ?>
                </nav>
                <div class="header-auth" aria-label="Account">
                    <?php if ($isLoggedIn): ?>
                        <a class="nav-link <?= $currentPath === '/profile.php' ? 'active' : '' ?>" href="/profile.php"><?= e($sessionName) ?></a>
                        <span class="pill-badge pill-badge--soft"><?= e(ucfirst($sessionRole)) ?></span>
                        <a class="nav-link" href="/logout.php">Sign out</a>
                    <?php else: ?>
                        <a class="nav-link <?= $currentPath === '/login.php' ? 'active' : '' ?>" href="/login.php">Sign in</a>
                        <a class="btn btn-brand-outline" href="/register.php" style="padding:0.5rem 1rem;font-size:0.9rem;">Register</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>
