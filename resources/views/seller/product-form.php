<?php
declare(strict_types=1);
$isEdit = isset($editProduct) && is_array($editProduct);
?>
<div class="profile-card">
    <span class="hero-section__eyebrow"><?= $isEdit ? 'Edit listing' : 'Create listing' ?></span>
    <h2 class="auth-card__title"><?= $isEdit ? e((string) $editProduct['name']) : 'New product' ?></h2>

    <?php if (!empty($notice)): ?>
        <div class="alert alert-success" role="alert"><?= e((string) $notice) ?></div>
    <?php endif; ?>

    <?php if (!empty($formError)): ?>
        <div class="alert alert-danger" role="alert"><?= e((string) $formError) ?></div>
    <?php endif; ?>

    <form class="auth-form" method="post" action="/seller/product-edit.php<?= $isEdit ? '?id=' . e((string) $editProduct['id']) : '' ?>">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <?php if ($isEdit): ?>
            <input type="hidden" name="product_id" value="<?= e((string) $editProduct['id']) ?>">
        <?php endif; ?>

        <div class="form-group">
            <label for="product-category">Category</label>
            <select id="product-category" class="form-select" name="category_id" required>
                <option value="">Select category</option>
                <?php foreach ($categories as $category): ?>
                    <option value="<?= e((string) $category['id']) ?>" <?= (string) $formValues['category_id'] === (string) $category['id'] ? 'selected' : '' ?>>
                        <?= e((string) $category['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="row g-3">
            <div class="col-md-6 form-group">
                <label for="product-name">Product name</label>
                <input id="product-name" class="form-control" type="text" name="name" value="<?= e((string) $formValues['name']) ?>" required maxlength="150">
            </div>
            <div class="col-md-6 form-group">
                <label for="product-sku">SKU</label>
                <input id="product-sku" class="form-control" type="text" name="sku" value="<?= e((string) $formValues['sku']) ?>" required maxlength="50">
            </div>
        </div>

        <div class="row g-3">
            <div class="col-md-6 form-group">
                <label for="product-slug">Slug</label>
                <input id="product-slug" class="form-control" type="text" name="slug" value="<?= e((string) $formValues['slug']) ?>" maxlength="150" placeholder="Auto-generated if left blank">
            </div>
            <div class="col-md-6 form-group">
                <label for="product-image-url">Primary image URL</label>
                <input id="product-image-url" class="form-control" type="url" name="image_url" value="<?= e((string) $formValues['image_url']) ?>" placeholder="https://">
            </div>
        </div>

        <div class="form-group">
            <label for="product-short-description">Short description</label>
            <input id="product-short-description" class="form-control" type="text" name="short_description" value="<?= e((string) $formValues['short_description']) ?>" required maxlength="255">
        </div>

        <div class="form-group">
            <label for="product-description">Description</label>
            <textarea id="product-description" class="form-control" name="description" rows="5" required><?= e((string) $formValues['description']) ?></textarea>
        </div>

        <div class="row g-3">
            <div class="col-md-4 form-group">
                <label for="product-price">Price</label>
                <input id="product-price" class="form-control" type="number" name="price" value="<?= e((string) $formValues['price']) ?>" min="0.01" step="0.01" required>
            </div>
            <div class="col-md-4 form-group">
                <label for="product-compare-price">Compare price</label>
                <input id="product-compare-price" class="form-control" type="number" name="compare_price" value="<?= e((string) ($formValues['compare_price'] ?? '')) ?>" min="0" step="0.01">
            </div>
            <div class="col-md-4 form-group">
                <label for="product-stock">Stock quantity</label>
                <input id="product-stock" class="form-control" type="number" name="stock_quantity" value="<?= e((string) $formValues['stock_quantity']) ?>" min="0" step="1" required>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-md-6 form-group">
                <label class="toggle-field" for="product-active">
                    <input type="hidden" name="is_active" value="0">
                    <input id="product-active" type="checkbox" name="is_active" value="1" <?= !empty($formValues['is_active']) ? 'checked' : '' ?>>
                    <span>Listing is active</span>
                </label>
            </div>
            <div class="col-md-6 form-group">
                <label class="toggle-field" for="product-featured">
                    <input type="hidden" name="is_featured" value="0">
                    <input id="product-featured" type="checkbox" name="is_featured" value="1" <?= !empty($formValues['is_featured']) ? 'checked' : '' ?>>
                    <span>Show as featured</span>
                </label>
            </div>
        </div>

        <button class="btn btn-brand w-100" type="submit"><?= $isEdit ? 'Save product' : 'Create product' ?></button>
    </form>

    <p class="auth-card__footer"><a href="/seller/products.php">Back to product list</a></p>
</div>
