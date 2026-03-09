<?php

declare(strict_types=1);

$config = require dirname(__DIR__) . '/bootstrap.php';
$services = \App\Support\AppFactory::storefront($config);
$catalogView = $services['catalog']->browse($_GET);
$cartSummary = $services['cart']->summary();

$pageTitle = 'Customer Storefront';
$appName = $config['app']['name'];
$pageScript = 'catalog.js';
$headerSearchValue = $catalogView['filters']['search'];

$catalogQuery = [
    'search' => $catalogView['filters']['search'] !== '' ? $catalogView['filters']['search'] : null,
    'category' => $catalogView['filters']['category'] !== '' ? $catalogView['filters']['category'] : null,
    'sort' => $catalogView['filters']['sort'] !== 'featured' ? $catalogView['filters']['sort'] : null,
    'min_price' => $catalogView['filters']['min_price'],
    'max_price' => $catalogView['filters']['max_price'],
    'in_stock' => $catalogView['filters']['in_stock'] ? '1' : null,
];

$buildCatalogUrl = static function (array $overrides = []) use ($catalogQuery): string {
    $params = array_merge($catalogQuery, $overrides);

    foreach ($params as $key => $value) {
        if ($value === null || $value === '' || $value === false) {
            unset($params[$key]);
        }
    }

    $query = http_build_query($params);

    return '/' . ($query !== '' ? '?' . $query : '') . '#catalog-feed';
};

$showSidebarFilters = $catalogView['filters']['search'] !== ''
    || $catalogView['filters']['category'] !== ''
    || $catalogView['filters']['min_price'] !== null
    || $catalogView['filters']['max_price'] !== null
    || $catalogView['filters']['in_stock'];

$featuredDeals = array_slice(
    array_values(array_filter(
        $catalogView['products'],
        static fn (array $product): bool => $product['is_featured']
    )),
    0,
    4
);

$categoryShortcuts = array_slice($catalogView['categories'], 0, 6);

require dirname(__DIR__) . '/resources/views/layouts/header.php';
?>
<main class="storefront-home">
    <section class="market-hero">
        <div class="container">
            <div class="market-hero__grid">
                <article class="market-hero__banner">
                    <div class="market-hero__content">
                        <span class="hero-section__eyebrow">System 2 / Customer-side commerce flow</span>
                        <h1 class="hero-section__title">A marketplace-style storefront layout with fast discovery and quick cart actions.</h1>
                        <p class="hero-section__copy">
                            The layout now follows a commerce-first pattern: promo banners, shortcut categories, a wide product feed,
                            and a sidebar filter rail, while keeping your existing colors, PHP backend, and cart functionality.
                        </p>

                        <div class="market-hero__actions">
                            <a class="btn btn-brand" href="#catalog-feed">Browse products</a>
                            <a class="btn btn-brand-outline" href="/cart.php">Open full cart</a>
                        </div>
                    </div>
                    <div class="market-hero__metrics">
                        <div class="market-metric">
                            <span class="hero-stat-card__label">Products shown</span>
                            <strong><?= e((string) count($catalogView['products'])) ?></strong>
                        </div>
                        <div class="market-metric">
                            <span class="hero-stat-card__label">Categories</span>
                            <strong><?= e((string) count($catalogView['categories'])) ?></strong>
                        </div>
                        <div class="market-metric">
                            <span class="hero-stat-card__label">Cart items</span>
                            <strong><?= e((string) $cartSummary['total_items']) ?></strong>
                        </div>
                    </div>
                </article>

                <div class="market-hero__stack">
                    <article class="promo-tile promo-tile--tall">
                        <span class="hero-section__eyebrow">Shopping benefits</span>
                        <h2>Filter, compare, and add to cart without leaving the product feed.</h2>
                        <p>Customers can update quantities, preview totals, and move straight into the order module later.</p>
                    </article>
                    <article class="promo-tile">
                        <span class="hero-section__eyebrow">Data source</span>
                        <h2><?= e($catalogView['source'] === 'sample' ? 'Sample fallback active' : 'Connected to MySQL') ?></h2>
                        <p>Prepared queries and server-side validation stay in place regardless of the data source.</p>
                    </article>
                </div>
            </div>
        </div>
    </section>

    <section class="shortcut-section">
        <div class="container">
            <div class="section-block">
                <div class="section-block__header">
                    <div>
                        <span class="results-header__eyebrow">Shortcut categories</span>
                        <h2>Jump into the storefront the way shoppers expect.</h2>
                    </div>
                    <a class="btn btn-link" href="#catalog-feed">Go to product feed</a>
                </div>

                <div class="shortcut-grid" aria-label="Category shortcuts">
                    <?php foreach ($categoryShortcuts as $category): ?>
                        <a class="shortcut-tile" href="/?category=<?= e($category['slug']) ?>#catalog-feed">
                            <span class="shortcut-tile__icon"><?= e(strtoupper(substr($category['name'], 0, 1))) ?></span>
                            <span class="shortcut-tile__title"><?= e($category['name']) ?></span>
                            <span class="shortcut-tile__meta"><?= e((string) $category['product_count']) ?> products</span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </section>

    <?php if ($featuredDeals !== []): ?>
        <section class="deal-strip-section">
            <div class="container">
                <div class="section-block">
                    <div class="section-block__header">
                        <div>
                            <span class="results-header__eyebrow">Featured picks</span>
                            <h2>Highlight products in a horizontal deal strip before the full feed.</h2>
                        </div>
                        <button class="btn btn-brand-outline" type="button" data-bs-toggle="offcanvas" data-bs-target="#cartDrawer" aria-controls="cartDrawer">
                            View quick cart
                        </button>
                    </div>

                    <div class="deal-strip">
                        <?php foreach ($featuredDeals as $product): ?>
                            <article class="deal-card">
                                <img src="<?= e($product['image_url']) ?>" alt="<?= e($product['name']) ?>" loading="lazy">
                                <div class="deal-card__body">
                                    <span class="pill-badge pill-badge--soft"><?= e($product['category_name']) ?></span>
                                    <h3><?= e($product['name']) ?></h3>
                                    <p><?= e($product['short_description']) ?></p>
                                    <div class="deal-card__footer">
                                        <strong><?= e(money($product['price'])) ?></strong>
                                        <span><?= e((string) $product['stock_quantity']) ?> left</span>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <section id="catalog-feed" class="catalog-feed-section">
        <div class="container">
            <div class="catalog-shell <?= $showSidebarFilters ? 'catalog-shell--with-sidebar' : 'catalog-shell--without-sidebar' ?>">
                <?php if ($showSidebarFilters): ?>
                    <aside class="catalog-sidebar" data-catalog-sidebar>
                        <div class="catalog-sidebar__panel">
                            <div class="catalog-sidebar__section">
                                <span class="results-header__eyebrow">Browse categories</span>
                                <h2 class="catalog-sidebar__title">All categories</h2>
                                <nav class="sidebar-category-list" aria-label="Catalog categories">
                                    <a class="sidebar-category-link <?= $catalogView['filters']['category'] === '' ? 'is-active' : '' ?>" href="<?= e($buildCatalogUrl(['category' => null])) ?>" data-category-link data-category-value="">
                                        All products
                                    </a>
                                    <?php foreach ($catalogView['categories'] as $category): ?>
                                        <a class="sidebar-category-link <?= $catalogView['filters']['category'] === $category['slug'] ? 'is-active' : '' ?>" href="<?= e($buildCatalogUrl(['category' => $category['slug']])) ?>" data-category-link data-category-value="<?= e($category['slug']) ?>">
                                            <span><?= e($category['name']) ?></span>
                                            <span><?= e((string) $category['product_count']) ?></span>
                                        </a>
                                    <?php endforeach; ?>
                                </nav>
                            </div>

                            <form id="catalog-filter-form" class="sidebar-filter-form" action="/" method="get">
                                <input type="hidden" name="category" value="<?= e($catalogView['filters']['category']) ?>">

                                <div class="catalog-sidebar__section">
                                    <h3 class="catalog-sidebar__heading">Search Filter</h3>
                                    <div class="form-group">
                                        <label for="search">Search</label>
                                        <input
                                            id="search"
                                            class="form-control"
                                            type="search"
                                            name="search"
                                            value="<?= e($catalogView['filters']['search']) ?>"
                                            placeholder="Search products, sellers, or categories"
                                        >
                                    </div>
                                </div>

                                <div class="catalog-sidebar__section">
                                    <h3 class="catalog-sidebar__heading">Price Range</h3>
                                    <div class="sidebar-price-grid">
                                        <div class="form-group">
                                            <label for="min_price">Min price</label>
                                            <input
                                                id="min_price"
                                                class="form-control"
                                                type="number"
                                                name="min_price"
                                                min="0"
                                                step="0.01"
                                                value="<?= e($catalogView['filters']['min_price'] !== null ? (string) $catalogView['filters']['min_price'] : '') ?>"
                                                placeholder="0.00"
                                            >
                                        </div>

                                        <div class="form-group">
                                            <label for="max_price">Max price</label>
                                            <input
                                                id="max_price"
                                                class="form-control"
                                                type="number"
                                                name="max_price"
                                                min="0"
                                                step="0.01"
                                                value="<?= e($catalogView['filters']['max_price'] !== null ? (string) $catalogView['filters']['max_price'] : '') ?>"
                                                placeholder="200.00"
                                            >
                                        </div>
                                    </div>
                                </div>

                                <div class="catalog-sidebar__section">
                                    <h3 class="catalog-sidebar__heading">Availability</h3>
                                    <label class="toggle-field" for="in_stock">
                                        <input id="in_stock" type="checkbox" name="in_stock" value="1" <?= $catalogView['filters']['in_stock'] ? 'checked' : '' ?>>
                                        <span>Show in-stock items only</span>
                                    </label>
                                </div>

                                <div class="catalog-sidebar__actions">
                                    <button class="btn btn-brand w-100" type="submit">Apply filters</button>
                                    <a class="btn btn-link" href="<?= e($buildCatalogUrl([
                                        'search' => null,
                                        'category' => null,
                                        'sort' => null,
                                        'min_price' => null,
                                        'max_price' => null,
                                        'in_stock' => null,
                                    ])) ?>" data-clear-link>Clear all</a>
                                </div>
                            </form>
                        </div>
                    </aside>
                <?php else: ?>
                    <form id="catalog-filter-form" class="catalog-hidden-form" action="/" method="get">
                        <input type="hidden" name="search" value="<?= e($catalogView['filters']['search']) ?>">
                        <input type="hidden" name="category" value="<?= e($catalogView['filters']['category']) ?>">
                        <input type="hidden" name="min_price" value="<?= e($catalogView['filters']['min_price'] !== null ? (string) $catalogView['filters']['min_price'] : '') ?>">
                        <input type="hidden" name="max_price" value="<?= e($catalogView['filters']['max_price'] !== null ? (string) $catalogView['filters']['max_price'] : '') ?>">
                        <input type="hidden" name="in_stock" value="<?= $catalogView['filters']['in_stock'] ? '1' : '' ?>">
                    </form>
                <?php endif; ?>

                <div class="catalog-main <?= $showSidebarFilters ? '' : 'catalog-main--full' ?>">
                    <div class="catalog-main__header">
                        <div>
                            <span class="results-header__eyebrow">Product feed</span>
                            <h2 data-results-count aria-live="polite"><?= e((string) count($catalogView['products'])) ?> products available</h2>
                        </div>
                        <div class="results-header__meta">
                            <?php if ($catalogView['source'] === 'sample'): ?>
                                <span class="pill-badge pill-badge--soft">Running in sample-data fallback mode</span>
                            <?php else: ?>
                                <span class="pill-badge pill-badge--soft">Connected to MySQL</span>
                            <?php endif; ?>
                            <span class="pill-badge pill-badge--soft"><span data-active-filter-count><?= e((string) $catalogView['activeFilterCount']) ?></span> active filters</span>
                        </div>
                    </div>

                    <div class="catalog-sortbar">
                        <div class="catalog-sortbar__label">Sort by</div>
                        <div class="catalog-sortbar__controls">
                            <select id="sort" class="form-select catalog-sortbar__select" name="sort" form="catalog-filter-form" data-sort-select>
                                <option value="featured" <?= $catalogView['filters']['sort'] === 'featured' ? 'selected' : '' ?>>Featured</option>
                                <option value="newest" <?= $catalogView['filters']['sort'] === 'newest' ? 'selected' : '' ?>>Newest</option>
                                <option value="rating_desc" <?= $catalogView['filters']['sort'] === 'rating_desc' ? 'selected' : '' ?>>Top rated</option>
                                <option value="price_asc" <?= $catalogView['filters']['sort'] === 'price_asc' ? 'selected' : '' ?>>Price: Low to high</option>
                                <option value="price_desc" <?= $catalogView['filters']['sort'] === 'price_desc' ? 'selected' : '' ?>>Price: High to low</option>
                            </select>
                        </div>
                    </div>

                    <div id="catalog-results" class="catalog-results">
                        <?= render('partials/catalog-grid', ['products' => $catalogView['products']]) ?>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

<div class="offcanvas offcanvas-end cart-drawer" tabindex="-1" id="cartDrawer" aria-labelledby="cartDrawerLabel">
    <div class="offcanvas-header">
        <div>
            <span class="hero-section__eyebrow">Cart preview</span>
            <h2 class="offcanvas-title" id="cartDrawerLabel">Customer cart</h2>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body" data-cart-drawer>
        <?= render('partials/cart-panel', ['cart' => $cartSummary]) ?>
    </div>
</div>

<?php require dirname(__DIR__) . '/resources/views/layouts/footer.php'; ?>
