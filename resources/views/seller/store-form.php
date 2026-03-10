<?php declare(strict_types=1); ?>
<div class="profile-card">
    <span class="hero-section__eyebrow">Store settings</span>
    <h2 class="auth-card__title">Edit Store Profile</h2>

    <div class="auth-card__error" data-store-error style="display:none;"></div>
    <div class="auth-card__success" data-store-success style="display:none;"></div>

    <form class="auth-form" data-store-form novalidate>
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

        <div class="form-group">
            <label for="store-name">Store name</label>
            <input id="store-name" class="form-control" type="text" name="store_name" value="<?= e($profile['seller_profile']['store_name'] ?? '') ?>" required maxlength="120">
        </div>

        <div class="form-group">
            <label for="store-slug">Store slug</label>
            <input id="store-slug" class="form-control" type="text" name="store_slug" value="<?= e($profile['seller_profile']['store_slug'] ?? '') ?>" required maxlength="120" pattern="[a-z0-9-]+">
            <small class="text-muted">URL-friendly identifier (lowercase, hyphens only)</small>
        </div>

        <div class="form-group">
            <label for="store-support-email">Support email</label>
            <input id="store-support-email" class="form-control" type="email" name="support_email" value="<?= e($profile['seller_profile']['support_email'] ?? '') ?>" placeholder="support@yourstore.com">
        </div>

        <button class="btn btn-brand w-100" type="submit">Save store profile</button>
    </form>
</div>
