<?php

declare(strict_types=1);

$isEdit = isset($editCoupon) && is_array($editCoupon);
?>
<div class="profile-card profile-card--editor coupon-form-card">
    <span class="hero-section__eyebrow"><?= $isEdit ? 'Edit coupon' : 'Create coupon' ?></span>
    <h2 class="auth-card__title"><?= $isEdit ? e((string) $editCoupon['title']) : 'New coupon' ?></h2>

    <?php if (!empty($notice)): ?>
        <div class="alert alert-success" role="alert"><?= e((string) $notice) ?></div>
    <?php endif; ?>

    <?php if (!empty($formError)): ?>
        <div class="alert alert-danger" role="alert"><?= e((string) $formError) ?></div>
    <?php endif; ?>

    <form class="auth-form coupon-form" method="post" action="/admin/coupon-edit.php<?= $isEdit ? '?id=' . e((string) $editCoupon['id']) : '' ?>">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <?php if ($isEdit): ?>
            <input type="hidden" name="coupon_id" value="<?= e((string) $editCoupon['id']) ?>">
        <?php endif; ?>

        <div class="row g-3">
            <div class="col-md-4 form-group">
                <label for="coupon-code">Coupon code</label>
                <input id="coupon-code" class="form-control" type="text" name="code" value="<?= e((string) $formValues['code']) ?>" required maxlength="40">
            </div>
            <div class="col-md-8 form-group">
                <label for="coupon-title">Title</label>
                <input id="coupon-title" class="form-control" type="text" name="title" value="<?= e((string) $formValues['title']) ?>" required maxlength="160">
            </div>
        </div>

        <div class="form-group">
            <label for="coupon-description">Description</label>
            <textarea id="coupon-description" class="form-control" name="description" rows="4" required><?= e((string) $formValues['description']) ?></textarea>
        </div>

        <div class="row g-3">
            <div class="col-md-4 form-group">
                <label for="coupon-type">Coupon type</label>
                <select id="coupon-type" class="form-select" name="coupon_type" required>
                    <option value="limited_time" <?= (string) $formValues['coupon_type'] === 'limited_time' ? 'selected' : '' ?>>Limited time</option>
                    <option value="free_shipping" <?= (string) $formValues['coupon_type'] === 'free_shipping' ? 'selected' : '' ?>>Free shipping</option>
                    <option value="shop" <?= (string) $formValues['coupon_type'] === 'shop' ? 'selected' : '' ?>>Shop</option>
                </select>
            </div>
            <div class="col-md-4 form-group">
                <label for="discount-type">Discount type</label>
                <select id="discount-type" class="form-select" name="discount_type" required>
                    <option value="percentage" <?= (string) $formValues['discount_type'] === 'percentage' ? 'selected' : '' ?>>Percentage</option>
                    <option value="fixed_amount" <?= (string) $formValues['discount_type'] === 'fixed_amount' ? 'selected' : '' ?>>Fixed amount</option>
                    <option value="shipping" <?= (string) $formValues['discount_type'] === 'shipping' ? 'selected' : '' ?>>Shipping</option>
                </select>
            </div>
            <div class="col-md-4 form-group">
                <label for="discount-value">Discount value</label>
                <input id="discount-value" class="form-control" type="number" name="discount_value" value="<?= e((string) $formValues['discount_value']) ?>" min="0" step="0.01">
            </div>
        </div>

        <div class="row g-3">
            <div class="col-md-6 form-group">
                <label for="coupon-minimum-spend">Minimum spend</label>
                <input id="coupon-minimum-spend" class="form-control" type="number" name="minimum_spend" value="<?= e((string) ($formValues['minimum_spend'] ?? '')) ?>" min="0" step="0.01">
            </div>
            <div class="col-md-6 form-group">
                <label for="coupon-seller">Seller</label>
                <p class="coupon-form__helper">Leave this blank for sitewide campaigns. Select a seller only for shop coupons.</p>
                <select id="coupon-seller" class="form-select" name="seller_id">
                    <option value="">Sitewide / no specific seller</option>
                    <?php foreach ($sellers as $seller): ?>
                        <option value="<?= e((string) $seller['id']) ?>" <?= (string) ($formValues['seller_id'] ?? '') === (string) $seller['id'] ? 'selected' : '' ?>>
                            <?= e((string) $seller['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-md-6 form-group">
                <label for="coupon-starts-at">Starts at</label>
                <input id="coupon-starts-at" class="form-control" type="datetime-local" name="starts_at" value="<?= e((string) $formValues['starts_at']) ?>" required>
            </div>
            <div class="col-md-6 form-group">
                <label for="coupon-ends-at">Ends at</label>
                <input id="coupon-ends-at" class="form-control" type="datetime-local" name="ends_at" value="<?= e((string) $formValues['ends_at']) ?>" required>
            </div>
        </div>

        <div class="row g-3">
            <div class="col-md-6 form-group">
                <label class="toggle-field" for="coupon-featured">
                    <input type="hidden" name="is_featured" value="0">
                    <input id="coupon-featured" type="checkbox" name="is_featured" value="1" <?= !empty($formValues['is_featured']) ? 'checked' : '' ?>>
                    <span>Feature this coupon</span>
                </label>
            </div>
            <div class="col-md-6 form-group">
                <label class="toggle-field" for="coupon-active">
                    <input type="hidden" name="is_active" value="0">
                    <input id="coupon-active" type="checkbox" name="is_active" value="1" <?= !empty($formValues['is_active']) ? 'checked' : '' ?>>
                    <span>Coupon is active</span>
                </label>
            </div>
        </div>

        <button class="btn btn-brand w-100" type="submit"><?= $isEdit ? 'Save coupon' : 'Create coupon' ?></button>
    </form>

    <p class="auth-card__footer"><a href="/admin/coupons.php">Back to coupon list</a></p>
</div>
