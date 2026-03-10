<?php

declare(strict_types=1);

$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$pageTitle = $pageTitle ?? 'Shop';
$appName = $appName ?? 'NovaMarket';
$headerSearchValue = $headerSearchValue ?? '';
$cartSummary = $cartSummary ?? [
    'total_items' => 0,
];
$shopPaths = ['/', '/index.html', '/index.php'];
$cartPaths = ['/cart.html', '/cart.php'];
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

        </div>
    </header>
