<?php

declare(strict_types=1);

$orders = $orders ?? [];
$reviewedProductIds = array_map('intval', $reviewedProductIds ?? []);
$notice = $notice ?? null;
$error = $error ?? null;
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
                        <p>Grand total <?= e((string) $order['total_formatted']) ?></p>
                    </div>
                </div>

                <div class="order-card__items">
                    <?php foreach ($order['items'] as $item): ?>
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
                                <a class="btn btn-brand-outline btn-sm" href="<?= e($productLink) ?>#product-reviews">
                                    <?= $isReviewed ? 'Update review' : 'Write review' ?>
                                </a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
