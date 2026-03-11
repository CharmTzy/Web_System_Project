<?php declare(strict_types=1); ?>
<div class="profile-card">
    <span class="hero-section__eyebrow">Your profile</span>
    <h2 class="auth-card__title"><?= e($user['name']) ?></h2>
    <span class="pill-badge pill-badge--soft"><?= e(ucfirst($user['role'])) ?></span>

    <div class="auth-card__error" data-profile-error style="display:none;"></div>
    <div class="auth-card__success" data-profile-success style="display:none;"></div>

    <form class="auth-form" data-profile-form novalidate>
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

        <div class="form-group">
            <label for="profile-name">Full name</label>
            <input id="profile-name" class="form-control" type="text" name="name" value="<?= e($user['name']) ?>" required maxlength="120">
        </div>

        <div class="form-group">
            <label for="profile-email">Email address</label>
            <input id="profile-email" class="form-control" type="email" name="email" value="<?= e($user['email']) ?>" required>
        </div>

        <div class="form-group">
            <label for="profile-phone">Phone</label>
            <input id="profile-phone" class="form-control" type="tel" name="phone" value="<?= e($user['phone'] ?? '') ?>" placeholder="+65 9123 4567">
        </div>

        <hr>
        <p class="text-muted" style="font-size:0.9rem;">Leave password fields blank to keep your current password.</p>

        <div class="form-group">
            <label for="profile-new-password">New password</label>
            <input id="profile-new-password" class="form-control" type="password" name="new_password" placeholder="Min. 8 characters" minlength="8" autocomplete="new-password">
        </div>

        <button class="btn btn-brand w-100" type="submit">Save changes</button>
    </form>
</div>
