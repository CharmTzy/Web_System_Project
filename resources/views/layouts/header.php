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
                    <a class="nav-link <?= $currentPath === '/' || $currentPath === '/index.php' ? 'active' : '' ?>" href="/">Shop</a>
                    <a class="nav-link <?= $currentPath === '/cart.php' ? 'active' : '' ?>" href="/cart.php">Cart</a>
                    <?php if ($isLoggedIn && $sessionRole === 'admin'): ?>
                        <a class="nav-link <?= str_starts_with($currentPath, '/admin') ? 'active' : '' ?>" href="/admin/">Dashboard</a>
                        <a class="nav-link <?= $currentPath === '/admin/users.php' ? 'active' : '' ?>" href="/admin/users.php">Users</a>
                        <a class="nav-link <?= $currentPath === '/admin/products.php' ? 'active' : '' ?>" href="/admin/products.php">Products</a>
                    <?php elseif ($isLoggedIn && $sessionRole === 'seller'): ?>
                        <a class="nav-link <?= str_starts_with($currentPath, '/seller') ? 'active' : '' ?>" href="/seller/">Dashboard</a>
                        <a class="nav-link <?= $currentPath === '/seller/store-profile.php' ? 'active' : '' ?>" href="/seller/store-profile.php">Store</a>
                        <a class="nav-link <?= $currentPath === '/seller/products.php' ? 'active' : '' ?>" href="/seller/products.php">Products</a>
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
