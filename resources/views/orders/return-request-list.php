<?php

declare(strict_types=1);

$requests = $requests ?? [];
$pagination = $pagination ?? null;
$viewer = (string) ($viewer ?? 'seller');
$notice = $notice ?? null;
$error = $error ?? null;
$featureAvailable = $featureAvailable ?? true;
$isAdmin = $viewer === 'admin';
$detailHref = $isAdmin ? '/admin/return-view.php' : '/seller/return-view.php';
?>
<div class="section-block">
    <div class="section-block__header">
        <div>
            <span class="results-header__eyebrow"><?= $isAdmin ? 'Marketplace returns' : 'Store returns' ?></span>
            <h2><?= e((string) ($pagination['total_items'] ?? count($requests))) ?> return &amp; refund requests</h2>
        </div>
    </div>

    <?php if (!empty($notice)): ?>
        <div class="alert alert-success" role="alert"><?= e((string) $notice) ?></div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger" role="alert"><?= e((string) $error) ?></div>
    <?php endif; ?>

    <?php if (!$featureAvailable): ?>
        <div class="alert alert-warning" role="alert">
            Return and refund workflows will appear here once the latest order return migration is applied.
        </div>
    <?php endif; ?>

    <?php if ($requests === []): ?>
        <div class="empty-state">
            <h3>No return or refund requests yet.</h3>
            <p><?= $isAdmin ? 'Customer return requests will appear here once buyers submit them.' : 'Customer requests for your store will appear here once buyers submit them.' ?></p>
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
                        <th>Request</th>
                        <th>Reason</th>
                        <th>Status</th>
                        <th>Opened</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($requests as $request): ?>
                        <tr>
                            <td>
                                <strong><?= e((string) $request['order_number']) ?></strong><br>
                                <small class="text-muted">Request #<?= e((string) $request['id']) ?></small>
                            </td>
                            <?php if ($isAdmin): ?>
                                <td><?= e((string) $request['seller_name']) ?></td>
                            <?php endif; ?>
                            <td>
                                <strong><?= e((string) $request['customer_name']) ?></strong><br>
                                <small class="text-muted"><?= e((string) $request['customer_email']) ?></small>
                            </td>
                            <td><?= e((string) $request['request_type_label']) ?></td>
                            <td>
                                <strong><?= e((string) $request['reason_label']) ?></strong>
                                <?php if (!empty($request['reason_details'])): ?>
                                    <br><small class="text-muted"><?= e((string) mb_strimwidth((string) $request['reason_details'], 0, 90, '…')) ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="<?= e((string) $request['status_badge_class']) ?>">
                                    <?= e((string) $request['status_label']) ?>
                                </span>
                            </td>
                            <td><?= e((string) ($request['created_at_formatted'] ?? '')) ?></td>
                            <td>
                                <a class="btn btn-brand-outline" href="<?= e($detailHref) ?>?id=<?= e((string) $request['id']) ?>" style="padding:0.4rem 0.8rem;font-size:0.85rem;">View</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?= render('partials/pagination', ['pagination' => $pagination]) ?>
    <?php endif; ?>
</div>
