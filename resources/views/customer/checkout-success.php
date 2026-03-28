<?php

declare(strict_types=1);

$order = $order ?? [];
$sessionId = (string) ($sessionId ?? '');
$isPaid = (string) ($order['status'] ?? '') === 'paid';
?>

<?php if ($isPaid): ?>
    <section class="customer-panel" style="max-width: 48rem; margin: 0 auto;">
        <div class="customer-panel__header">
            <div>
                <span class="hero-section__eyebrow">Payment successful</span>
                <h2 class="customer-panel__title">Thanks! Your order is confirmed.</h2>
            </div>
        </div>
        <div class="summary-card__rows mb-4">
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
        <div class="d-flex flex-wrap gap-3">
            <a class="btn btn-brand" href="/customer/orders.php">View orders</a>
            <a class="btn btn-brand-outline" href="/">Continue shopping</a>
        </div>
    </section>
<?php else: ?>
    <section class="customer-panel" style="max-width: 48rem; margin: 0 auto;">
        <div class="customer-panel__header">
            <div>
                <span class="hero-section__eyebrow">Processing payment</span>
                <h2 class="customer-panel__title">We’re finalizing your order.</h2>
            </div>
        </div>
        <p class="summary-card__note mb-3">
            Your payment was submitted to Stripe and we are waiting for confirmation from the webhook. This usually takes a few seconds.
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
