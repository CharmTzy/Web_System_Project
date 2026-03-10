<?php

declare(strict_types=1);
?>
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

        <form id="catalog-filter-form" class="sidebar-filter-form" action="/index.html" method="get">
            <input type="hidden" name="category" value="<?= e($catalogView['filters']['category']) ?>">

            <div class="catalog-sidebar__section">
                <h3 class="catalog-sidebar__heading">Search filter</h3>
                <div class="form-group">
                    <label for="search">Search</label>
                    <input
                        id="search"
                        class="form-control"
                        type="search"
                        name="search"
                        value="<?= e($catalogView['filters']['search']) ?>"
                        placeholder="Search products, brands, or categories"
                    >
                </div>
            </div>

            <div class="catalog-sidebar__section">
                <h3 class="catalog-sidebar__heading">Price range</h3>
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
