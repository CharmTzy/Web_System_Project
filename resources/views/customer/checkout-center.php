<?php

declare(strict_types=1);

$cart = $cart ?? ['items' => [], 'is_empty' => true];
$addresses = $addresses ?? [];
$formError = $formError ?? null;
$selectedAddressId = (int) ($selectedAddressId ?? 0);
$paymentsInTestMode = !empty($paymentsInTestMode);
$availableCoupons = $availableCoupons ?? [];
$selectedCouponCode = strtoupper(trim((string) ($selectedCouponCode ?? '')));
$appliedCoupon = $appliedCoupon ?? ($cart['applied_coupon'] ?? null);
?>
<?php if ($addresses === []): ?>
    <div class="customer-grid customer-grid--checkout">
        <section class="customer-panel">
            <div class="customer-panel__header">
                <div>
                    <span class="hero-section__eyebrow">Delivery details required</span>
                    <h2 class="customer-panel__title">Add a delivery address</h2>
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
                <?php if (!empty($cart['has_discount'])): ?>
                    <div class="summary-row summary-row--discount">
                        <span>Coupon savings</span>
                        <strong>-<?= e((string) $cart['discount_amount_formatted']) ?></strong>
                    </div>
                <?php endif; ?>
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
                    <span class="hero-section__eyebrow">Checkout</span>
                    <h2 class="customer-panel__title">Choose your delivery details</h2>
                </div>
            </div>

            <?php if (!empty($formError)): ?>
                <div class="alert alert-danger" role="alert"><?= e((string) $formError) ?></div>
            <?php endif; ?>

            <?php if ($paymentsInTestMode): ?>
                <div class="alert alert-warning" role="alert">
                    Stripe test mode is active on this environment. Use Stripe test cards only. No live charge should be expected here.
                </div>
            <?php endif; ?>

            <?php if ($availableCoupons !== []): ?>
                <div class="checkout-group">
                    <div class="checkout-group__heading">
                        <span class="hero-section__eyebrow">Coupon</span>
                    </div>
                    <form class="checkout-coupon-form" method="get" action="/customer/checkout.php">
                        <input type="hidden" name="address_id" value="<?= e((string) $selectedAddressId) ?>">
                        <div class="checkout-coupon-form__controls">
                            <label class="visually-hidden" for="checkout-coupon-code">Select a coupon</label>
                            <select id="checkout-coupon-code" class="form-select" name="coupon_code">
                                <option value="">Choose a coupon</option>
                                <?php foreach ($availableCoupons as $coupon): ?>
                                    <option value="<?= e((string) $coupon['code']) ?>" <?= $selectedCouponCode === strtoupper((string) $coupon['code']) ? 'selected' : '' ?><?= empty($coupon['is_eligible']) ? ' disabled' : '' ?>>
                                        <?= e((string) $coupon['option_label']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <button class="btn btn-brand-outline" type="submit">Apply</button>
                            <?php if ($selectedCouponCode !== ''): ?>
                                <a class="btn btn-link btn-remove" href="/customer/checkout.php?address_id=<?= e((string) $selectedAddressId) ?>">Remove</a>
                            <?php endif; ?>
                        </div>
                    </form>

                    <?php if (!empty($appliedCoupon)): ?>
                        <div class="checkout-coupon-card">
                            <div>
                                <strong><?= e((string) $appliedCoupon['code']) ?></strong>
                                <p><?= e((string) ($appliedCoupon['title'] ?? 'Coupon applied')) ?></p>
                                <span><?= e((string) ($appliedCoupon['scope_label'] ?? '')) ?></span>
                            </div>
                            <div class="checkout-coupon-card__value">
                                <?= e((string) ($appliedCoupon['discount_amount_formatted'] ?? $appliedCoupon['discount_label'] ?? 'Applied')) ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <form class="customer-form" method="post" action="/api/stripe-checkout.php">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <?php if ($selectedCouponCode !== ''): ?>
                    <input type="hidden" name="coupon_code" value="<?= e($selectedCouponCode) ?>">
                <?php endif; ?>

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

                <p class="summary-card__note mb-3">
                    <?= $paymentsInTestMode
                        ? 'You’ll be redirected to Stripe test checkout to verify the payment flow. Do not use real payment details on this environment.'
                        : 'You’ll be redirected to Stripe’s secure checkout page to complete payment.' ?>
                </p>
                <button class="btn btn-brand w-100" type="submit">
                    <?= $paymentsInTestMode ? 'Continue to Stripe test checkout' : 'Continue to Stripe' ?>
                    · <?= e((string) $cart['grand_total_formatted']) ?>
                </button>
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
                <?php if (!empty($cart['has_discount'])): ?>
                    <div class="summary-row summary-row--discount">
                        <span>Coupon savings</span>
                        <strong>-<?= e((string) $cart['discount_amount_formatted']) ?></strong>
                    </div>
                <?php endif; ?>
                <div class="summary-row summary-row--grand">
                    <span>Grand total</span>
                    <strong><?= e((string) $cart['grand_total_formatted']) ?></strong>
                </div>
            </div>
            <p class="summary-card__note">
                <?= $paymentsInTestMode
                    ? 'This environment is using Stripe test mode. Payments here are for checkout testing and verification only.'
                    : 'Payment is processed securely by Stripe. Your order will be confirmed after payment succeeds.' ?>
            </p>
        </aside>
    </div>
<?php endif; ?>
