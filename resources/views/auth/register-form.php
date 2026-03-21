<?php declare(strict_types=1); ?>
<div class="auth-card auth-card--guest">
    <span class="auth-card__eyebrow">Join NovaMarket</span>
    <h2 class="auth-card__title">Create your account</h2>

    <div class="auth-card__error" data-auth-error style="display:none;"></div>
    <div class="auth-card__success" data-auth-success style="display:none;"></div>

    <form class="auth-form auth-form--guest" data-auth-form data-action="register" novalidate>
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="action" value="register">

        <div class="form-group">
            <label for="reg-account-type">I want to</label>
            <select id="reg-account-type" class="form-select" name="account_type">
                <option value="customer" selected>Shop on NovaMarket</option>
                <option value="seller">Sell on NovaMarket</option>
            </select>
        </div>

        <div class="form-group">
            <label for="reg-name">Full name</label>
            <input id="reg-name" class="form-control" type="text" name="name" required autocomplete="name" placeholder="Your full name" maxlength="120">
        </div>

        <div class="form-group">
            <label for="reg-email">Email address</label>
            <input id="reg-email" class="form-control" type="email" name="email" required autocomplete="email" placeholder="you@example.com">
        </div>

        <div id="seller-reg-fields" style="display:none;">
            <div class="form-group">
                <label for="reg-store-name">Store name</label>
                <input id="reg-store-name" class="form-control" type="text" name="store_name" placeholder="e.g. My Awesome Store" maxlength="120">
            </div>
        </div>

        <div class="form-group">
            <label for="reg-phone">Phone (optional)</label>
            <input id="reg-phone" class="form-control" type="tel" name="phone" autocomplete="tel" placeholder="+65 9123 4567">
        </div>

        <div class="auth-form__split">
            <div class="form-group">
                <label for="reg-password">Password</label>
                <input id="reg-password" class="form-control" type="password" name="password" required autocomplete="new-password" placeholder="Min. 8 characters" minlength="8">
            </div>

            <div class="form-group">
                <label for="reg-password-confirm">Confirm password</label>
                <input id="reg-password-confirm" class="form-control" type="password" name="password_confirm" required autocomplete="new-password" placeholder="Re-enter your password">
            </div>
        </div>

        <button class="btn btn-brand w-100 auth-submit-button" type="submit">Create account</button>
    </form>
</div>

<script>
(() => {
    const typeSelect = document.getElementById('reg-account-type');
    const sellerFields = document.getElementById('seller-reg-fields');
    const sellerNotice = document.getElementById('register-seller-notice');
    if (!typeSelect) return;

    const syncSellerFields = () => {
        const isSeller = typeSelect.value === 'seller';
        sellerFields.style.display = isSeller ? '' : 'none';
        if (sellerNotice) {
            sellerNotice.hidden = !isSeller;
        }
    };

    typeSelect.addEventListener('change', syncSellerFields);
    syncSellerFields();
})();
</script>
