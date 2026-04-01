<?php declare(strict_types=1); ?>
<div class="section-block">
    <div class="section-block__header">
        <div>
            <span class="results-header__eyebrow">User management</span>
            <h2><?= e((string) ($pagination['total_items'] ?? count($users))) ?> users</h2>
        </div>
        <a class="btn btn-brand" href="/admin/user-edit.php">Add new user</a>
    </div>

    <?php if (!empty($notice)): ?>
        <div class="alert alert-success" role="alert"><?= e((string) $notice) ?></div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger" role="alert"><?= e((string) $error) ?></div>
    <?php endif; ?>

    <form class="catalog-sortbar" method="get" action="/admin/users.php" style="margin-bottom:1rem;">
        <input class="form-control" type="search" name="search" value="<?= e($filters['search'] ?? '') ?>" placeholder="Search by name or email" style="max-width:300px;">
        <div class="catalog-sortbar__controls">
            <select class="form-select" name="role" onchange="this.form.submit()" style="min-width:150px;">
                <option value="">All roles</option>
                <option value="admin" <?= ($filters['role'] ?? '') === 'admin' ? 'selected' : '' ?>>Admin</option>
                <option value="seller" <?= ($filters['role'] ?? '') === 'seller' ? 'selected' : '' ?>>Seller</option>
                <option value="customer" <?= ($filters['role'] ?? '') === 'customer' ? 'selected' : '' ?>>Customer</option>
            </select>
            <button class="btn btn-brand-outline" type="submit">Filter</button>
        </div>
    </form>

    <?php if ($users === []): ?>
        <div class="empty-state">
            <h3>No users found.</h3>
            <p>Try adjusting your search or filter criteria.</p>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table admin-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u): ?>
                        <tr>
                            <td><?= e((string) $u['id']) ?></td>
                            <td><strong><?= e($u['name']) ?></strong></td>
                            <td><?= e($u['email']) ?></td>
                            <td><span class="pill-badge pill-badge--soft"><?= e(ucfirst($u['role'])) ?></span></td>
                            <td>
                                <?php if ($u['is_active']): ?>
                                    <span class="pill-badge pill-badge--soft" style="color:#245c61;">Active</span>
                                <?php else: ?>
                                    <span class="pill-badge pill-badge--dark">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td><?= e(date('M j, Y', strtotime($u['created_at']))) ?></td>
                            <td>
                                <div class="d-flex gap-2 flex-wrap">
                                    <a class="btn btn-brand-outline" href="/admin/user-edit.php?id=<?= e((string) $u['id']) ?>" style="padding:0.4rem 0.8rem;font-size:0.85rem;">Edit</a>
                                    <?php if ((int) $u['id'] !== (int) ($actingUserId ?? 0)): ?>
                                        <form method="post" action="/admin/users.php" onsubmit="return confirm('Delete this user?');">
                                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="user_id" value="<?= e((string) $u['id']) ?>">
                                            <button class="btn btn-outline-danger" type="submit" style="padding:0.4rem 0.8rem;font-size:0.85rem;">Delete</button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?= render('partials/pagination', ['pagination' => $pagination ?? null]) ?>
    <?php endif; ?>
</div>
