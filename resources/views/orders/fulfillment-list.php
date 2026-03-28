<?php

declare(strict_types=1);

$fulfillments = $fulfillments ?? [];
$pagination = $pagination ?? null;
$viewer = (string) ($viewer ?? 'admin');
$notice = $notice ?? null;
$error = $error ?? null;
$isAdmin = $viewer === 'admin';
$detailHref = $isAdmin ? '/admin/order-view.php' : '/seller/order-view.php';
?>
<div class="section-block">
    <div class="section-block__header">
        <div>
            <span class="results-header__eyebrow"><?= $isAdmin ? 'Marketplace fulfillment' : 'Store fulfillment' ?></span>
            <h2><?= e((string) ($pagination['total_items'] ?? count($fulfillments))) ?> delivery packages</h2>
        </div>
    </div>

    <?php if (!empty($notice)): ?>
        <div class="alert alert-success" role="alert"><?= e((string) $notice) ?></div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger" role="alert"><?= e((string) $error) ?></div>
    <?php endif; ?>

    <?php if ($fulfillments === []): ?>
        <div class="empty-state">
            <h3>No delivery packages yet.</h3>
            <p><?= $isAdmin ? 'Seller packages will appear here once customers place paid orders.' : 'Your store deliveries will appear here once customers place paid orders.' ?></p>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table admin-table">
                <thead>
                    <tr>
                        <th>Order</th>
                        <?php if ($isAdmin): ?>
                            <th>Seller</th>
                        <?php endif; ?>
                        <th>Customer</th>
                        <th>Status</th>
                        <th>Package</th>
                        <th>Tracking</th>
                        <th>ETA</th>
                        <th>Subtotal</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($fulfillments as $fulfillment): ?>
                        <?php
                        $orderPlaced = !empty($fulfillment['order_created_at'])
                            ? date('d M Y, g:i A', strtotime((string) $fulfillment['order_created_at']))
                            : null;
                        $updatedAt = !empty($fulfillment['updated_at'])
                            ? date('d M Y, g:i A', strtotime((string) $fulfillment['updated_at']))
                            : null;
                        ?>
                        <tr>
                            <td>
                                <strong><?= e((string) $fulfillment['order_number']) ?></strong><br>
                                <?php if ($orderPlaced !== null): ?>
                                    <small class="text-muted">Placed <?= e($orderPlaced) ?></small>
                                <?php endif; ?>
                            </td>
                            <?php if ($isAdmin): ?>
                                <td><?= e((string) $fulfillment['seller_name']) ?></td>
                            <?php endif; ?>
                            <td>
                                <strong><?= e((string) ($fulfillment['customer_name'] ?: $fulfillment['shipping_recipient'])) ?></strong><br>
                                <small class="text-muted"><?= e((string) ($fulfillment['customer_email'] ?: $fulfillment['shipping_phone'] ?: 'Delivery contact available in detail view')) ?></small>
                            </td>
                            <td>
                                <span class="<?= e(delivery_status_badge_class((string) $fulfillment['status'])) ?>">
                                    <?= e((string) $fulfillment['status_label']) ?>
                                </span>
                                <?php if ($updatedAt !== null): ?>
                                    <div><small class="text-muted">Updated <?= e($updatedAt) ?></small></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong><?= e((string) $fulfillment['item_count']) ?> items</strong><br>
                                <small class="text-muted"><?= e((string) $fulfillment['item_quantity']) ?> units in this package</small>
                            </td>
                            <td>
                                <?php if (!empty($fulfillment['tracking_number'])): ?>
                                    <strong><?= e((string) $fulfillment['tracking_number']) ?></strong><br>
                                    <small class="text-muted"><?= e((string) ($fulfillment['courier_name'] ?? 'Courier pending')) ?></small>
                                <?php else: ?>
                                    <span class="text-muted">Not assigned yet</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($fulfillment['estimated_delivery_date_formatted'])): ?>
                                    <?= e((string) $fulfillment['estimated_delivery_date_formatted']) ?>
                                <?php else: ?>
                                    <span class="text-muted">Not set</span>
                                <?php endif; ?>
                            </td>
                            <td><?= e((string) $fulfillment['seller_subtotal_formatted']) ?></td>
                            <td>
                                <a class="btn btn-brand-outline" href="<?= e($detailHref) ?>?id=<?= e((string) $fulfillment['id']) ?>" style="padding:0.4rem 0.8rem;font-size:0.85rem;">View</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?= render('partials/pagination', ['pagination' => $pagination]) ?>
    <?php endif; ?>
</div>
