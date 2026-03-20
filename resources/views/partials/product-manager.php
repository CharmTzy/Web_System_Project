<?php declare(strict_types=1); ?>
<div
    class="product-manager"
    data-product-manager
    data-storage-key="<?= e($storageKey) ?>"
    data-role-label="<?= e($roleLabel) ?>"
    data-scope-label="<?= e($scopeLabel ?? '') ?>"
    data-currency="<?= e($currency ?? 'USD') ?>"
>
    <script type="application/json" data-product-manager-seed><?= json_encode($seedProducts, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?></script>

    <section class="product-manager__hero">
        <div>
            <span class="results-header__eyebrow"><?= e($roleLabel) ?> workspace</span>
            <h2>Product Management</h2>
            <p>
                Clean frontend-only workspace for adding, editing, and deleting products.
                <?php if (!empty($scopeLabel)): ?>
                    Viewing products for <strong><?= e($scopeLabel) ?></strong>.
                <?php else: ?>
                    Changes stay in this browser until a backend is connected.
                <?php endif; ?>
            </p>
        </div>
        <div class="product-manager__hero-actions">
            <button class="btn btn-brand" type="button" data-product-create>New product</button>
            <span class="pill-badge pill-badge--soft">No backend yet</span>
        </div>
    </section>

    <div class="product-manager__stats" data-product-stats></div>

    <div class="product-manager__layout">
        <section class="product-manager__panel product-manager__panel--form">
            <div class="product-manager__panel-head">
                <div>
                    <span class="results-header__eyebrow">Editor</span>
                    <h3 data-product-form-title>Add Product</h3>
                </div>
                <button class="btn btn-brand-outline" type="button" data-product-reset>Clear</button>
            </div>

            <div class="auth-card__success" data-product-success style="display:none;"></div>
            <div class="auth-card__error" data-product-error style="display:none;"></div>

            <form class="product-manager__form" data-product-form novalidate>
                <input type="hidden" name="product_id">

                <div class="product-manager__form-grid">
                    <div class="form-group">
                        <label for="product-title">Product name</label>
                        <input id="product-title" class="form-control" type="text" name="name" maxlength="120" required placeholder="Aura Ceramic Vase">
                    </div>

                    <div class="form-group">
                        <label for="product-category">Category</label>
                        <select id="product-category" class="form-select" name="category" required>
                            <option value="">Select a category</option>
                            <?php foreach ($categoryOptions as $categoryOption): ?>
                                <option><?= e($categoryOption) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="product-price">Price</label>
                        <input id="product-price" class="form-control" type="number" name="price" min="0" step="0.01" required placeholder="49.90">
                    </div>

                    <div class="form-group">
                        <label for="product-stock">Stock</label>
                        <input id="product-stock" class="form-control" type="number" name="stock" min="0" step="1" required placeholder="24">
                    </div>

                    <div class="form-group">
                        <label for="product-sku">SKU</label>
                        <input id="product-sku" class="form-control" type="text" name="sku" maxlength="40" required placeholder="AURA-VASE-01">
                    </div>

                    <div class="form-group">
                        <label for="product-status">Status</label>
                        <select id="product-status" class="form-select" name="status" required>
                            <option value="Draft">Draft</option>
                            <option value="Active">Active</option>
                            <option value="Out of Stock">Out of Stock</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="product-image">Image URL</label>
                    <input id="product-image" class="form-control" type="url" name="image" placeholder="https://images.example.com/product.jpg">
                </div>

                <div class="form-group">
                    <label for="product-description">Description</label>
                    <textarea id="product-description" class="form-control" name="description" rows="4" maxlength="400" required placeholder="Short product description for the admin preview card."></textarea>
                </div>

                <label class="toggle-field">
                    <input type="checkbox" name="featured" value="1">
                    <span>Feature this product on the dashboard</span>
                </label>

                <div class="filter-form__actions">
                    <button class="btn btn-brand" type="submit" data-product-submit>Save product</button>
                    <button class="btn btn-brand-outline" type="button" data-product-cancel style="display:none;">Cancel edit</button>
                </div>
            </form>
        </section>

        <section class="product-manager__panel product-manager__panel--list">
            <div class="product-manager__panel-head">
                <div>
                    <span class="results-header__eyebrow">Inventory</span>
                    <h3>Products</h3>
                </div>
                <div class="product-manager__filters">
                    <input class="form-control" type="search" placeholder="Search products" data-product-search>
                    <select class="form-select" data-product-filter>
                        <option value="all">All status</option>
                        <option value="Active">Active</option>
                        <option value="Draft">Draft</option>
                        <option value="Out of Stock">Out of Stock</option>
                    </select>
                </div>
            </div>

            <div class="product-manager__collection" data-product-list></div>
        </section>
    </div>
</div>
