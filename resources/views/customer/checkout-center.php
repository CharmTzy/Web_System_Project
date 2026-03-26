<?php

declare(strict_types=1);

$cart = $cart ?? ['items' => [], 'is_empty' => true];
$addresses = $addresses ?? [];
$formError = $formError ?? null;
$selectedAddressId = (int) ($selectedAddressId ?? 0);
?>
<?php if ($addresses === []): ?>
    <div class="customer-grid customer-grid--checkout">
        <section class="customer-panel">
            <div class="customer-panel__header">
                <div>
                    <span class="hero-section__eyebrow">Setup required</span>
                    <h2 class="customer-panel__title">Finish your checkout setup</h2>
                </div>
            </div>
            <section class="empty-state empty-state--compact customer-panel__empty">
                <h3>You need one delivery address.</h3>
                <p>Online card payment is disabled. Add a delivery address first, then place your order for manual confirmation.</p>
                <div class="d-flex flex-wrap gap-3 justify-content-center">
                    <a class="btn btn-brand" href="/customer/addresses.php">Add address</a>
                </div>
            </section>
        </section>

        <aside class="summary-card summary-card--checkout">
            <span class="summary-card__eyebrow">Order summary</span>
            <h3>Ready when you are</h3>
            <div class="summary-card__rows">
                <div class="summary-row">
                    <span>Total items</span>
                    <strong><?= e((string) $cart['total_items']) ?></strong>
                </div>
                <div class="summary-row">
                    <span>Subtotal</span>
                    <strong><?= e((string) $cart['subtotal_formatted']) ?></strong>
                </div>
                <div class="summary-row">
                    <span>Shipping</span>
                    <strong><?= e((string) $cart['shipping_formatted']) ?></strong>
                </div>
                <div class="summary-row summary-row--grand">
                    <span>Grand total</span>
                    <strong><?= e((string) $cart['grand_total_formatted']) ?></strong>
                </div>
            </div>
        </aside>
    </div>
<?php else: ?>
    <div class="customer-grid customer-grid--checkout">
        <section class="customer-panel">
            <div class="customer-panel__header">
                <div>
                    <span class="hero-section__eyebrow">Manual order flow</span>
                    <h2 class="customer-panel__title">Choose your delivery details</h2>
                </div>
            </div>

            <?php if (!empty($formError)): ?>
                <div class="alert alert-danger" role="alert"><?= e((string) $formError) ?></div>
            <?php endif; ?>

            <div class="alert alert-warning" role="alert">
                <strong>No online payment is collected on this site.</strong>
                <span class="d-block mt-2">Submitting this form creates a pending order only. Any real payment processing must happen through a verified external provider.</span>
            </div>

            <form class="customer-form" method="post" action="/customer/checkout.php">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

                <div class="checkout-group">
                    <div class="checkout-group__heading">
                        <span class="hero-section__eyebrow">Shipping address</span>
                        <a href="/customer/addresses.php">Manage</a>
                    </div>
                    <div class="checkout-choice-grid">
                        <?php foreach ($addresses as $address): ?>
                            <?php $isSelected = $selectedAddressId === (int) $address['id']; ?>
                            <label class="checkout-choice<?= $isSelected ? ' is-selected' : '' ?>">
                                <input type="radio" name="address_id" value="<?= e((string) $address['id']) ?>" <?= $isSelected ? 'checked' : '' ?>>
                                <span class="checkout-choice__body">
                                    <strong><?= e((string) $address['label']) ?><?= $address['is_default'] ? ' · Default' : '' ?></strong>
                                    <span><?= e((string) $address['recipient']) ?></span>
                                    <span><?= e((string) $address['line_1']) ?></span>
                                    <span><?= e(trim((string) ($address['city'] . ', ' . $address['state'] . ' ' . $address['postal_code']))) ?></span>
                                </span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <button class="btn btn-brand w-100" type="submit">Place order request</button>
            </form>
        </section>

        <aside class="summary-card summary-card--checkout">
            <span class="summary-card__eyebrow">Order summary</span>
            <h3>What you’re ordering</h3>
            <div class="summary-card__rows">
                <?php foreach ($cart['items'] as $item): ?>
                    <div class="summary-row summary-row--item">
                        <span><?= e((string) $item['product']['name']) ?> × <?= e((string) $item['quantity']) ?></span>
                        <strong><?= e((string) $item['line_total_formatted']) ?></strong>
                    </div>
                <?php endforeach; ?>
                <div class="summary-row">
                    <span>Subtotal</span>
                    <strong><?= e((string) $cart['subtotal_formatted']) ?></strong>
                </div>
                <div class="summary-row">
                    <span>Shipping</span>
                    <strong><?= e((string) $cart['shipping_formatted']) ?></strong>
                </div>
                <div class="summary-row summary-row--grand">
                    <span>Grand total</span>
                    <strong><?= e((string) $cart['grand_total_formatted']) ?></strong>
                </div>
            </div>
            <p class="summary-card__note">This total is shown for reference only. Orders placed here stay pending until you confirm payment through a verified external process.</p>
        </aside>
    </div>
<?php endif; ?>
