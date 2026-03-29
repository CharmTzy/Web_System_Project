<?php

declare(strict_types=1);

$orders = $orders ?? [];
$reviewedProductIds = array_map('intval', $reviewedProductIds ?? []);
$returnRequestsByPackage = $returnRequestsByPackage ?? [];
$returnRequestsEnabled = $returnRequestsEnabled ?? false;
$notice = $notice ?? null;
$error = $error ?? null;
$pagination = $pagination ?? null;
?>
<?php if (!empty($notice)): ?>
    <div class="alert alert-success" role="alert"><?= e((string) $notice) ?></div>
<?php endif; ?>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger" role="alert"><?= e((string) $error) ?></div>
<?php endif; ?>

<?php if ($orders === []): ?>
    <section class="empty-state">
        <h3>No orders yet.</h3>
        <p>Once you place your first order, it will appear here with its items, delivery details, and current status.</p>
        <a class="btn btn-brand" href="/">Start shopping</a>
    </section>
<?php else: ?>
    <div class="order-list">
        <?php foreach ($orders as $order): ?>
            <?php
            $statusLabel = ucfirst((string) $order['status']);
            $placedAt = date('d M Y, g:i A', strtotime((string) $order['created_at']));
            ?>
            <article class="order-card">
                <div class="order-card__header">
                    <div>
                        <span class="hero-section__eyebrow">Order <?= e((string) $order['order_number']) ?></span>
                        <h2><?= e($statusLabel) ?></h2>
                        <p>Placed on <?= e($placedAt) ?></p>
                    </div>
                    <div class="order-card__summary">
                        <strong><?= e((string) $order['total_formatted']) ?></strong>
                    </div>
                </div>

                <div class="order-card__meta">
                    <div>
                        <span>Ship to</span>
                        <strong><?= e((string) $order['shipping_recipient']) ?></strong>
                        <p><?= e((string) $order['shipping_line_1']) ?></p>
                    </div>
                    <div>
                        <span>Order totals</span>
                        <strong><?= e((string) $order['subtotal_formatted']) ?> + <?= e((string) $order['shipping_fee_formatted']) ?> shipping</strong>
                        <?php if (!empty($order['discount_amount'])): ?>
                            <p>Promotional savings -<?= e((string) $order['discount_amount_formatted']) ?></p>
                        <?php endif; ?>
                        <p>Grand total <?= e((string) $order['total_formatted']) ?></p>
                    </div>
                </div>

                <div class="order-card__fulfillments">
                    <?php foreach (($order['fulfillments'] ?? []) as $fulfillment): ?>
                        <?php
                        $returnRequestKey = (int) ($fulfillment['order_id'] ?? 0) . ':' . (int) ($fulfillment['seller_id'] ?? 0);
                        $linkedReturnRequest = $returnRequestsByPackage[$returnRequestKey] ?? null;
                        $canOpenReturnRequest = $returnRequestsEnabled
                            && (string) ($fulfillment['status'] ?? '') === 'delivered'
                            && !(
                                is_array($linkedReturnRequest)
                                && in_array((string) ($linkedReturnRequest['status'] ?? ''), ['pending', 'approved', 'received'], true)
                            );
                        ?>
                        <section class="order-fulfillment">
                            <div class="order-fulfillment__header">
                                <div>
                                    <span class="hero-section__eyebrow">Seller package</span>
                                    <h3><?= e((string) $fulfillment['seller_name']) ?></h3>
                                    <p>
                                        <?= e((string) $fulfillment['item_count']) ?> items ·
                                        <?= e((string) $fulfillment['seller_subtotal_formatted']) ?>
                                    </p>
                                </div>
                                <span class="<?= e(delivery_status_badge_class((string) $fulfillment['status'])) ?>">
                                    <?= e((string) $fulfillment['status_label']) ?>
                                </span>
                            </div>

                            <div class="order-fulfillment__meta">
                                <div>
                                    <span>Tracking</span>
                                    <strong><?= e((string) ($fulfillment['tracking_number'] ?: 'Pending assignment')) ?></strong>
                                    <p><?= e((string) ($fulfillment['courier_name'] ?: 'Courier not assigned yet')) ?></p>
                                </div>
                                <div>
                                    <span>Estimated delivery</span>
                                    <strong><?= e((string) ($fulfillment['estimated_delivery_date_formatted'] ?: 'We’ll update this soon')) ?></strong>
                                    <?php if (!empty($fulfillment['status_note'])): ?>
                                        <p><?= e((string) $fulfillment['status_note']) ?></p>
                                    <?php else: ?>
                                        <p>Delivery updates from the seller will appear here.</p>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="order-card__items">
                                <?php foreach (($fulfillment['items'] ?? []) as $item): ?>
                                    <?php
                                    $productLink = product_url([
                                        'id' => $item['product_id'],
                                        'slug' => $item['slug'],
                                    ]);
                                    $isReviewed = in_array((int) $item['product_id'], $reviewedProductIds, true);
                                    ?>
                                    <article class="order-card__item">
                                        <a class="order-card__item-image" href="<?= e($productLink) ?>">
                                            <img src="<?= e((string) $item['image_url']) ?>" alt="<?= e((string) $item['product_name']) ?>" loading="lazy">
                                        </a>
                                        <div class="order-card__item-copy">
                                            <h3><a href="<?= e($productLink) ?>"><?= e((string) $item['product_name']) ?></a></h3>
                                            <p><?= e((string) $item['unit_price_formatted']) ?> × <?= e((string) $item['quantity']) ?></p>
                                        </div>
                                        <div class="order-card__item-actions">
                                            <strong><?= e((string) $item['line_total_formatted']) ?></strong>
                                            <div class="order-card__item-actions-row">
                                                <?php if ($returnRequestsEnabled && (is_array($linkedReturnRequest) || $canOpenReturnRequest)): ?>
                                                    <a class="btn btn-brand-outline btn-sm" href="/customer/return-request.php?order_id=<?= e((string) $order['id']) ?>&seller_id=<?= e((string) $fulfillment['seller_id']) ?>">
                                                        <?= is_array($linkedReturnRequest) ? 'View return / refund' : 'Return / refund' ?>
                                                    </a>
                                                <?php endif; ?>
                                                <a class="btn btn-brand-outline btn-sm" href="<?= e($productLink) ?>#product-reviews">
                                                    <?= $isReviewed ? 'Update review' : 'Write review' ?>
                                                </a>
                                            </div>
                                        </div>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        </section>
                    <?php endforeach; ?>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
    <?= render('partials/pagination', ['pagination' => $pagination]) ?>
<?php endif; ?>
