<?php declare(strict_types=1); ?>
<div class="auth-card">
    <span class="hero-section__eyebrow">New customer</span>
    <h2 class="auth-card__title">Create your account</h2>

    <div class="auth-card__error" data-auth-error style="display:none;"></div>

    <form class="auth-form" data-auth-form data-action="register" novalidate>
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="action" value="register">

        <div class="form-group">
            <label for="reg-name">Full name</label>
            <input id="reg-name" class="form-control" type="text" name="name" required autocomplete="name" placeholder="Your full name" maxlength="120">
        </div>

        <div class="form-group">
            <label for="reg-email">Email address</label>
            <input id="reg-email" class="form-control" type="email" name="email" required autocomplete="email" placeholder="you@example.com">
        </div>

        <div class="form-group">
            <label for="reg-phone">Phone (optional)</label>
            <input id="reg-phone" class="form-control" type="tel" name="phone" autocomplete="tel" placeholder="+65 9123 4567">
        </div>

        <div class="form-group">
            <label for="reg-password">Password</label>
            <input id="reg-password" class="form-control" type="password" name="password" required autocomplete="new-password" placeholder="Min. 8 characters" minlength="8">
        </div>

        <div class="form-group">
            <label for="reg-password-confirm">Confirm password</label>
            <input id="reg-password-confirm" class="form-control" type="password" name="password_confirm" required autocomplete="new-password" placeholder="Re-enter your password">
        </div>

        <button class="btn btn-brand w-100" type="submit">Create account</button>
    </form>

    <p class="auth-card__footer">Already have an account? <a href="/login.php">Sign in</a></p>
</div>
