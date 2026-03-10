<?php declare(strict_types=1); ?>
<div class="auth-card">
    <span class="hero-section__eyebrow">Account access</span>
    <h2 class="auth-card__title">Sign in to your account</h2>

    <div class="auth-card__error" data-auth-error style="display:none;"></div>

    <form class="auth-form" data-auth-form data-action="login" novalidate>
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="action" value="login">

        <div class="form-group">
            <label for="login-email">Email address</label>
            <input id="login-email" class="form-control" type="email" name="email" required autocomplete="email" placeholder="you@example.com">
        </div>

        <div class="form-group">
            <label for="login-password">Password</label>
            <input id="login-password" class="form-control" type="password" name="password" required autocomplete="current-password" placeholder="Min. 8 characters">
        </div>

        <button class="btn btn-brand w-100" type="submit">Sign in</button>
    </form>

    <p class="auth-card__footer">Don't have an account? <a href="/register.php">Create one here</a></p>
</div>
