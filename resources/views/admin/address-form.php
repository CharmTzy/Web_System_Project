<?php
declare(strict_types=1);
$isEdit = isset($editAddress) && is_array($editAddress);
?>
<div class="profile-card">
    <span class="hero-section__eyebrow"><?= $isEdit ? 'Edit customer address' : 'Create customer address' ?></span>
    <h2 class="auth-card__title"><?= $isEdit ? e((string) $editAddress['recipient']) : 'New address' ?></h2>

    <?php if (!empty($notice)): ?>
        <div class="alert alert-success" role="alert"><?= e((string) $notice) ?></div>
    <?php endif; ?>

    <?php if (!empty($formError)): ?>
        <div class="alert alert-danger" role="alert"><?= e((string) $formError) ?></div>
    <?php endif; ?>

    <form class="auth-form" method="post" action="/admin/address-edit.php<?= $isEdit ? '?id=' . e((string) $editAddress['id']) : '' ?>">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <?php if ($isEdit): ?>
            <input type="hidden" name="address_id" value="<?= e((string) $editAddress['id']) ?>">
        <?php endif; ?>

        <div class="form-group">
            <label for="admin-address-user">Customer</label>
            <select id="admin-address-user" class="form-select" name="user_id" required <?= $isEdit ? 'disabled' : '' ?>>
                <option value="">Select customer</option>
                <?php foreach ($customers as $customer): ?>
                    <option value="<?= e((string) $customer['id']) ?>" <?= (string) $formValues['user_id'] === (string) $customer['id'] ? 'selected' : '' ?>>
                        <?= e((string) $customer['name']) ?> (<?= e((string) $customer['email']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if ($isEdit): ?>
                <input type="hidden" name="user_id" value="<?= e((string) $formValues['user_id']) ?>">
            <?php endif; ?>
        </div>

        <div class="row g-3">
            <div class="col-md-4 form-group">
                <label for="admin-address-label">Address name</label>
                <input
                    id="admin-address-label"
                    class="form-control"
                    type="text"
                    name="label"
                    value="<?= e((string) $formValues['label']) ?>"
                    required
                    maxlength="50"
                    placeholder="e.g. Parent's house, Branch office">
            </div>
            <div class="col-md-8 form-group">
                <label for="admin-address-recipient">Recipient</label>
                <input id="admin-address-recipient" class="form-control" type="text" name="recipient" value="<?= e((string) $formValues['recipient']) ?>" required maxlength="120">
            </div>
        </div>

        <div class="form-group">
            <label for="admin-address-line1">Address line 1</label>
            <input id="admin-address-line1" class="form-control" type="text" name="line_1" value="<?= e((string) $formValues['line_1']) ?>" required maxlength="255">
        </div>

        <div class="form-group">
            <label for="admin-address-line2">Address line 2</label>
            <input id="admin-address-line2" class="form-control" type="text" name="line_2" value="<?= e((string) ($formValues['line_2'] ?? '')) ?>" maxlength="255">
        </div>

        <div class="row g-3">
            <div class="col-md-4 form-group">
                <label for="admin-address-city">City</label>
                <input id="admin-address-city" class="form-control" type="text" name="city" value="<?= e((string) $formValues['city']) ?>" required maxlength="100">
            </div>
            <div class="col-md-4 form-group">
                <label for="admin-address-state">State</label>
                <input id="admin-address-state" class="form-control" type="text" name="state" value="<?= e((string) $formValues['state']) ?>" required maxlength="100">
            </div>
            <div class="col-md-4 form-group">
                <label for="admin-address-postal-code">Postal code</label>
                <input id="admin-address-postal-code" class="form-control" type="text" name="postal_code" value="<?= e((string) $formValues['postal_code']) ?>" required maxlength="20">
            </div>
        </div>

        <div class="row g-3">
            <div class="col-md-6 form-group">
                <label for="admin-address-country">Country</label>
                <input id="admin-address-country" class="form-control" type="text" name="country" value="<?= e((string) $formValues['country']) ?>" required maxlength="80">
            </div>
            <div class="col-md-6 form-group">
                <label for="admin-address-phone">Phone</label>
                <input id="admin-address-phone" class="form-control" type="text" name="phone" value="<?= e((string) ($formValues['phone'] ?? '')) ?>" maxlength="30">
            </div>
        </div>

        <div class="form-group">
            <label class="toggle-field" for="admin-address-default">
                <input type="hidden" name="is_default" value="0">
                <input id="admin-address-default" type="checkbox" name="is_default" value="1" <?= !empty($formValues['is_default']) ? 'checked' : '' ?>>
                <span>Set as default address</span>
            </label>
        </div>

        <button class="btn btn-brand w-100" type="submit"><?= $isEdit ? 'Save address' : 'Create address' ?></button>
    </form>

    <p class="auth-card__footer"><a href="/admin/addresses.php">Back to address list</a></p>
</div>
