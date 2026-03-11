<?php
declare(strict_types=1);
$isEdit = isset($editUser);
?>
<div class="profile-card">
    <span class="hero-section__eyebrow"><?= $isEdit ? 'Edit user' : 'Create user' ?></span>
    <h2 class="auth-card__title"><?= $isEdit ? e($editUser['name']) : 'New user' ?></h2>

    <div class="auth-card__error" data-admin-user-error style="display:none;"></div>
    <div class="auth-card__success" data-admin-user-success style="display:none;"></div>

    <form class="auth-form" data-admin-user-form novalidate>
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <?php if ($isEdit): ?>
            <input type="hidden" name="user_id" value="<?= e((string) $editUser['id']) ?>">
        <?php endif; ?>

        <div class="form-group">
            <label for="admin-user-name">Full name</label>
            <input id="admin-user-name" class="form-control" type="text" name="name" value="<?= e($isEdit ? $editUser['name'] : '') ?>" required maxlength="120">
        </div>

        <div class="form-group">
            <label for="admin-user-email">Email address</label>
            <input id="admin-user-email" class="form-control" type="email" name="email" value="<?= e($isEdit ? $editUser['email'] : '') ?>" required>
        </div>

        <div class="form-group">
            <label for="admin-user-phone">Phone</label>
            <input id="admin-user-phone" class="form-control" type="tel" name="phone" value="<?= e($isEdit ? ($editUser['phone'] ?? '') : '') ?>">
        </div>

        <?php if (!$isEdit): ?>
            <div class="form-group">
                <label for="admin-user-role">Role</label>
                <select id="admin-user-role" class="form-select" name="role" required>
                    <option value="">Select role</option>
                    <option value="admin">Admin</option>
                    <option value="seller">Seller</option>
                    <option value="customer">Customer</option>
                </select>
            </div>
        <?php else: ?>
            <div class="form-group">
                <label>Role</label>
                <input class="form-control" type="text" value="<?= e(ucfirst($editUser['role'])) ?>" disabled>
            </div>
        <?php endif; ?>

        <div id="seller-fields" style="<?= ($isEdit && $editUser['role'] === 'seller') || !$isEdit ? '' : 'display:none;' ?>">
            <div class="form-group">
                <label for="admin-store-name">Store name</label>
                <input id="admin-store-name" class="form-control" type="text" name="store_name" value="<?= e($isEdit && isset($editUser['seller_profile']) ? ($editUser['seller_profile']['store_name'] ?? '') : '') ?>">
            </div>
            <div class="form-group">
                <label for="admin-store-slug">Store slug</label>
                <input id="admin-store-slug" class="form-control" type="text" name="store_slug" value="<?= e($isEdit && isset($editUser['seller_profile']) ? ($editUser['seller_profile']['store_slug'] ?? '') : '') ?>">
            </div>
            <div class="form-group">
                <label for="admin-support-email">Support email</label>
                <input id="admin-support-email" class="form-control" type="email" name="support_email" value="<?= e($isEdit && isset($editUser['seller_profile']) ? ($editUser['seller_profile']['support_email'] ?? '') : '') ?>">
            </div>
        </div>

        <div class="form-group">
            <label for="admin-user-password"><?= $isEdit ? 'New password (leave blank to keep current)' : 'Password' ?></label>
            <input id="admin-user-password" class="form-control" type="password" name="<?= $isEdit ? 'new_password' : 'password' ?>" <?= $isEdit ? '' : 'required' ?> minlength="8" placeholder="Min. 8 characters">
        </div>

        <?php if ($isEdit): ?>
            <div class="form-group">
                <label class="toggle-field" for="admin-user-active">
                    <input id="admin-user-active" type="checkbox" name="is_active" value="1" <?= $editUser['is_active'] ? 'checked' : '' ?>>
                    <span>Account is active</span>
                </label>
            </div>
        <?php endif; ?>

        <button class="btn btn-brand w-100" type="submit"><?= $isEdit ? 'Save changes' : 'Create user' ?></button>
    </form>

    <p class="auth-card__footer"><a href="/admin/users.php">Back to user list</a></p>
</div>
