<?php declare(strict_types=1); ?>
<div class="address-modal-backdrop" data-address-modal style="display:none;">
    <div class="address-modal">
        <div class="address-modal__header">
            <h3 data-address-modal-title>Add address</h3>
            <button class="btn-close" type="button" data-address-modal-close aria-label="Close"></button>
        </div>

        <div class="auth-card__error" data-address-error style="display:none;"></div>

        <form class="auth-form" data-address-form novalidate>
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="create" data-address-action>
            <input type="hidden" name="address_id" value="" data-address-id>

            <div class="form-group">
                <label for="addr-label">Address name</label>
                <input
                    id="addr-label"
                    class="form-control"
                    type="text"
                    name="label"
                    required
                    maxlength="50"
                    placeholder="e.g. Mom's house, Uni hostel, Partner office">
            </div>

            <div class="form-group">
                <label for="addr-recipient">Recipient name</label>
                <input id="addr-recipient" class="form-control" type="text" name="recipient" required maxlength="120">
            </div>

            <div class="form-group">
                <label for="addr-line1">Address line 1</label>
                <input id="addr-line1" class="form-control" type="text" name="line_1" required maxlength="255">
            </div>

            <div class="form-group">
                <label for="addr-line2">Address line 2</label>
                <input id="addr-line2" class="form-control" type="text" name="line_2" maxlength="255" placeholder="Apt, suite, unit (optional)">
            </div>

            <div class="row g-3">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="addr-city">City</label>
                        <input id="addr-city" class="form-control" type="text" name="city" required maxlength="100">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="addr-state">State / Region</label>
                        <input id="addr-state" class="form-control" type="text" name="state" required maxlength="100">
                    </div>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="addr-postal">Postal code</label>
                        <input id="addr-postal" class="form-control" type="text" name="postal_code" required maxlength="20">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="addr-country">Country</label>
                        <input id="addr-country" class="form-control" type="text" name="country" value="Singapore" maxlength="80">
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label for="addr-phone">Phone (optional)</label>
                <input id="addr-phone" class="form-control" type="tel" name="phone" maxlength="30">
            </div>

            <button class="btn btn-brand w-100" type="submit" data-address-submit-btn>Save address</button>
        </form>
    </div>
</div>
