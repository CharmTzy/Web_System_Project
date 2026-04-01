<?php declare(strict_types=1); ?>
<div class="section-block">
    <div class="section-block__header">
        <div>
            <span class="results-header__eyebrow">Coupon management</span>
            <h2><?= e((string) ($pagination['total_items'] ?? count($coupons))) ?> coupons</h2>
        </div>
        <a class="btn btn-brand" href="/admin/coupon-edit.php">Create coupon</a>
    </div>

    <?php if (!empty($notice)): ?>
        <div class="alert alert-success" role="alert"><?= e((string) $notice) ?></div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger" role="alert"><?= e((string) $error) ?></div>
    <?php endif; ?>

    <form class="catalog-sortbar" method="get" action="/admin/coupons.php" style="margin-bottom:1rem;">
        <input class="form-control" type="search" name="search" value="<?= e((string) ($filters['search'] ?? '')) ?>" placeholder="Search by code or title" style="max-width:320px;">
        <div class="catalog-sortbar__controls">
            <select class="form-select" name="coupon_type" onchange="this.form.submit()" style="min-width:180px;">
                <option value="">All coupon types</option>
                <option value="limited_time" <?= ($filters['coupon_type'] ?? '') === 'limited_time' ? 'selected' : '' ?>>Limited time</option>
                <option value="free_shipping" <?= ($filters['coupon_type'] ?? '') === 'free_shipping' ? 'selected' : '' ?>>Free shipping</option>
                <option value="shop" <?= ($filters['coupon_type'] ?? '') === 'shop' ? 'selected' : '' ?>>Shop</option>
            </select>
            <button class="btn btn-brand-outline" type="submit">Filter</button>
        </div>
    </form>

    <?php if ($coupons === []): ?>
        <div class="empty-state">
            <h3>No coupons found.</h3>
            <p>Create a coupon to start promoting new deals.</p>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table admin-table">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Title</th>
                        <th>Type</th>
                        <th>Discount</th>
                        <th>Scope</th>
                        <th>Status</th>
                        <th>Valid until</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($coupons as $coupon): ?>
                        <tr>
                            <td><strong><?= e((string) $coupon['code']) ?></strong></td>
                            <td>
                                <strong><?= e((string) $coupon['title']) ?></strong><br>
                                <small class="text-muted"><?= e((string) $coupon['description']) ?></small>
                            </td>
                            <td><span class="pill-badge pill-badge--soft"><?= e(ucwords(str_replace('_', ' ', (string) $coupon['coupon_type']))) ?></span></td>
                            <td>
                                <?php if ($coupon['discount_type'] === 'percentage'): ?>
                                    <?= e(rtrim(rtrim(number_format((float) $coupon['discount_value'], 2), '0'), '.')) ?>% off
                                <?php elseif ($coupon['discount_type'] === 'fixed_amount'): ?>
                                    <?= e(money((float) $coupon['discount_value'])) ?> off
                                <?php else: ?>
                                    Free shipping
                                <?php endif; ?>
                            </td>
                            <td><?= e((string) ($coupon['seller_name'] ?? 'Sitewide')) ?></td>
                            <td>
                                <?php if ($coupon['is_active']): ?>
                                    <span class="pill-badge pill-badge--soft" style="color:#245c61;">Active</span>
                                <?php else: ?>
                                    <span class="pill-badge pill-badge--dark">Inactive</span>
                                <?php endif; ?>
                                <?php if ($coupon['is_featured']): ?>
                                    <br><span class="pill-badge pill-badge--soft" style="margin-top:0.35rem;">Featured</span>
                                <?php endif; ?>
                            </td>
                            <td><?= e(date('M j, Y g:i A', strtotime((string) $coupon['ends_at']))) ?></td>
                            <td>
                                <div class="d-flex gap-2 flex-wrap">
                                    <a class="btn btn-brand-outline" href="/admin/coupon-edit.php?id=<?= e((string) $coupon['id']) ?>" style="padding:0.4rem 0.8rem;font-size:0.85rem;">Edit</a>
                                    <form method="post" action="/admin/coupons.php" onsubmit="return confirm('Delete this coupon?');">
                                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="coupon_id" value="<?= e((string) $coupon['id']) ?>">
                                        <button class="btn btn-outline-danger" type="submit" style="padding:0.4rem 0.8rem;font-size:0.85rem;">Delete</button>
                                    </form>
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
