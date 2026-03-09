<?php

declare(strict_types=1);

$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$pageTitle = $pageTitle ?? 'Storefront';
$appName = $appName ?? 'Meridian Mart';
$headerSearchValue = $headerSearchValue ?? '';
$cartSummary = $cartSummary ?? [
    'total_items' => 0,
];
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
    <div class="utility-bar">
        <div class="container utility-bar__inner">
            <div class="utility-bar__links">
                <a href="/">Shop discovery</a>
                <span class="utility-bar__dot" aria-hidden="true"></span>
                <a href="/cart.php">Shopping cart</a>
                <span class="utility-bar__dot" aria-hidden="true"></span>
                <span>INF1005 customer module</span>
            </div>
            <div class="utility-bar__meta">
                <span>Responsive Bootstrap + PHP storefront</span>
                <span class="pill-badge pill-badge--soft">Cart <?= e((string) $cartSummary['total_items']) ?></span>
            </div>
        </div>
    </div>

    <header class="site-header">
        <div class="container">
            <div class="site-header__main">
                <a class="site-nav__brand-link" href="/">
                    <span class="site-nav__eyebrow">INF1005 B2C Module</span>
                    <span class="site-nav__brand"><?= e($appName) ?></span>
                </a>

                <form class="header-search" action="/" method="get" role="search">
                    <label class="visually-hidden" for="header-search-input">Search the product catalog</label>
                    <input
                        id="header-search-input"
                        class="header-search__input"
                        type="search"
                        name="search"
                        value="<?= e($headerSearchValue) ?>"
                        placeholder="Search products, sellers, or categories"
                    >
                    <button class="btn btn-brand header-search__button" type="submit">Search</button>
                </form>

                <div class="site-header__actions">
                    <a class="header-cart-link" href="/cart.php">
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
                </nav>
                <div class="header-highlights" aria-label="Store highlights">
                    <span>Curated offers</span>
                    <span>Live filters</span>
                    <span>Secure cart actions</span>
                </div>
            </div>
        </div>
    </header>
