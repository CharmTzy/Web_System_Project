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
                <h3>You need at least one delivery address.</h3>
                <p>Add your delivery address first, then you can continue to Stripe for secure payment.</p>
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
                    <span class="hero-section__eyebrow">Complete payment</span>
                    <h2 class="customer-panel__title">Choose your delivery and payment details</h2>
                </div>
            </div>

            <?php if (!empty($formError)): ?>
                <div class="alert alert-danger" role="alert"><?= e((string) $formError) ?></div>
            <?php endif; ?>

            <form class="customer-form" method="post" action="/api/stripe-checkout.php">
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

                <p class="summary-card__note mb-3">You’ll be redirected to Stripe’s secure checkout page to complete payment.</p>
                <button class="btn btn-brand w-100" type="submit">Continue to Stripe · <?= e((string) $cart['grand_total_formatted']) ?></button>
            </form>
        </section>

        <aside class="summary-card summary-card--checkout">
            <span class="summary-card__eyebrow">Order summary</span>
            <h3>What you’re paying for</h3>
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
            <p class="summary-card__note">Payment is processed securely by Stripe. Your order will be confirmed after payment succeeds.</p>
        </aside>
    </div>
<?php endif; ?>
