<?php

declare(strict_types=1);

$order = $order ?? [];
$sessionId = (string) ($sessionId ?? '');
$isPaid = (string) ($order['status'] ?? '') === 'paid';
$paymentsInTestMode = !empty($paymentsInTestMode);
?>

<?php if ($isPaid): ?>
    <section class="customer-panel" style="max-width: 48rem; margin: 0 auto;">
        <div class="customer-panel__header">
            <div>
                <span class="hero-section__eyebrow"><?= $paymentsInTestMode ? 'Test payment successful' : 'Payment successful' ?></span>
                <h2 class="customer-panel__title"><?= $paymentsInTestMode ? 'Test checkout completed.' : 'Thanks! Your order is confirmed.' ?></h2>
            </div>
        </div>
        <?php if ($paymentsInTestMode): ?>
            <div class="alert alert-warning mb-4" role="alert">
                Stripe test mode is active on this environment. This checkout flow is for testing only and should not be treated as a live charge.
            </div>
        <?php endif; ?>
        <div class="summary-card__rows mb-4">
            <div class="summary-row">
                <span>Subtotal</span>
                <strong><?= e((string) ($order['subtotal_formatted'] ?? '')) ?></strong>
            </div>
            <div class="summary-row">
                <span>Shipping</span>
                <strong><?= e((string) ($order['shipping_fee_formatted'] ?? '')) ?></strong>
            </div>
            <?php if (!empty($order['discount_amount'])): ?>
                <div class="summary-row summary-row--discount">
                    <span>Coupon savings</span>
                    <strong>-<?= e((string) ($order['discount_amount_formatted'] ?? '')) ?></strong>
                </div>
            <?php endif; ?>
            <div class="summary-row">
                <span>Order number</span>
                <strong><?= e((string) ($order['order_number'] ?? '')) ?></strong>
            </div>
            <div class="summary-row">
                <span>Amount paid</span>
                <strong><?= e((string) ($order['total_formatted'] ?? '')) ?></strong>
            </div>
            <div class="summary-row">
                <span>Status</span>
                <strong><?= e(ucfirst((string) ($order['status'] ?? ''))) ?></strong>
            </div>
        </div>
        <?php if (($order['fulfillments'] ?? []) !== []): ?>
            <div class="checkout-success-packages mb-4">
                <span class="hero-section__eyebrow">Delivery packages</span>
                <?php foreach ($order['fulfillments'] as $fulfillment): ?>
                    <article class="checkout-success-packages__item">
                        <div>
                            <strong><?= e((string) $fulfillment['seller_name']) ?></strong>
                            <p><?= e((string) $fulfillment['item_count']) ?> items · <?= e((string) ($fulfillment['tracking_number'] ?: 'Tracking to follow')) ?></p>
                        </div>
                        <span class="<?= e(delivery_status_badge_class((string) $fulfillment['status'])) ?>">
                            <?= e((string) $fulfillment['status_label']) ?>
                        </span>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <div class="d-flex flex-wrap gap-3">
            <a class="btn btn-brand" href="/customer/orders.php">View orders</a>
            <a class="btn btn-brand-outline" href="/">Continue shopping</a>
        </div>
    </section>
<?php else: ?>
    <section class="customer-panel" style="max-width: 48rem; margin: 0 auto;">
        <div class="customer-panel__header">
            <div>
                <span class="hero-section__eyebrow"><?= $paymentsInTestMode ? 'Processing test payment' : 'Processing payment' ?></span>
                <h2 class="customer-panel__title">We’re finalizing your order.</h2>
            </div>
        </div>
        <p class="summary-card__note mb-3">
            <?= $paymentsInTestMode
                ? 'Your Stripe test payment was submitted and we are waiting for confirmation from the webhook. This usually takes a few seconds.'
                : 'Your payment was submitted to Stripe and we are waiting for confirmation from the webhook. This usually takes a few seconds.' ?>
        </p>
        <div class="summary-card__rows mb-4">
            <div class="summary-row">
                <span>Order number</span>
                <strong><?= e((string) ($order['order_number'] ?? '')) ?></strong>
            </div>
            <div class="summary-row">
                <span>Current status</span>
                <strong><?= e(ucfirst((string) ($order['status'] ?? 'Pending'))) ?></strong>
            </div>
        </div>
        <div class="d-flex flex-wrap gap-3">
            <a class="btn btn-brand" href="/customer/checkout-success.php?order=<?= rawurlencode((string) ($order['order_number'] ?? '')) ?>&session_id=<?= rawurlencode($sessionId) ?>">Refresh status</a>
            <a class="btn btn-brand-outline" href="/customer/orders.php">Go to orders</a>
        </div>
    </section>
<?php endif; ?>
