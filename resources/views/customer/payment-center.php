<?php

declare(strict_types=1);

$cards = $cards ?? [];
$notice = $notice ?? null;
$formError = $formError ?? null;
$formValues = $formValues ?? [];
?>
<div class="customer-grid customer-grid--payments">
    <section class="customer-panel">
        <div class="customer-panel__header">
            <div>
                <span class="hero-section__eyebrow">Wallet</span>
                <h2 class="customer-panel__title">Saved cards</h2>
            </div>
        </div>

        <?php if (!empty($notice)): ?>
            <div class="alert alert-success" role="alert"><?= e((string) $notice) ?></div>
        <?php endif; ?>

        <?php if ($cards === []): ?>
            <section class="empty-state empty-state--compact customer-panel__empty">
                <h3>No cards saved yet.</h3>
                <p>Add a payment method here so checkout can be completed in one step.</p>
            </section>
        <?php else: ?>
            <div class="payment-card-list">
                <?php foreach ($cards as $card): ?>
                    <?php
                    $brandLabel = strtoupper((string) $card['card_brand']);
                    $expiry = sprintf('%02d/%d', (int) $card['expiry_month'], (int) $card['expiry_year']);
                    ?>
                    <article class="payment-card-item<?= $card['is_default'] ? ' is-default' : '' ?>">
                        <div class="payment-card-item__content">
                            <div class="payment-card-item__top">
                                <div>
                                    <h3><?= e((string) $card['label']) ?></h3>
                                    <p><?= e((string) $card['cardholder_name']) ?></p>
                                </div>
                                <?php if ($card['is_default']): ?>
                                    <span class="pill-badge pill-badge--accent">Default</span>
                                <?php endif; ?>
                            </div>
                            <p class="payment-card-item__number"><?= e($brandLabel) ?> ending in <?= e((string) $card['card_last_four']) ?></p>
                            <p class="payment-card-item__meta">Expires <?= e($expiry) ?></p>
                        </div>
                        <div class="payment-card-item__actions">
                            <?php if (!$card['is_default']): ?>
                                <form method="post" action="/customer/payments.php">
                                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                    <input type="hidden" name="action" value="set_default">
                                    <input type="hidden" name="card_id" value="<?= e((string) $card['id']) ?>">
                                    <button class="btn btn-brand-outline btn-sm" type="submit">Make default</button>
                                </form>
                            <?php endif; ?>
                            <form method="post" action="/customer/payments.php" onsubmit="return confirm('Remove this saved card?');">
                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="card_id" value="<?= e((string) $card['id']) ?>">
                                <button class="btn btn-link btn-remove px-0" type="submit">Remove</button>
                            </form>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <section class="customer-panel">
        <div class="customer-panel__header">
            <div>
                <span class="hero-section__eyebrow">Add a card</span>
                <h2 class="customer-panel__title">Save a payment method</h2>
            </div>
        </div>

        <?php if (!empty($formError)): ?>
            <div class="alert alert-danger" role="alert"><?= e((string) $formError) ?></div>
        <?php endif; ?>

        <form class="customer-form" method="post" action="/customer/payments.php">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="action" value="create">

            <div class="form-group">
                <label for="payment-label">Label</label>
                <input id="payment-label" class="form-control" type="text" name="label" maxlength="50"
                    value="<?= e((string) ($formValues['label'] ?? 'My Card')) ?>" placeholder="Personal Visa">
            </div>

            <div class="form-group">
                <label for="payment-cardholder-name">Cardholder name</label>
                <input id="payment-cardholder-name" class="form-control" type="text" name="cardholder_name" maxlength="120"
                    value="<?= e((string) ($formValues['cardholder_name'] ?? '')) ?>" required>
            </div>

            <div class="row g-3">
                <div class="col-md-4 form-group">
                    <label for="payment-card-brand">Brand</label>
                    <select id="payment-card-brand" class="form-select" name="card_brand">
                        <?php foreach (['visa' => 'Visa', 'mastercard' => 'Mastercard', 'amex' => 'Amex', 'discover' => 'Discover', 'other' => 'Other'] as $value => $label): ?>
                            <option value="<?= e($value) ?>" <?= (string) ($formValues['card_brand'] ?? 'visa') === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-8 form-group">
                    <label for="payment-card-number">Card number</label>
                    <input id="payment-card-number" class="form-control" type="text" name="card_number"
                        value="<?= e((string) ($formValues['card_number'] ?? '')) ?>" inputmode="numeric"
                        autocomplete="cc-number" placeholder="4111 1111 1111 1111" required>
                </div>
            </div>

            <div class="row g-3">
                <div class="col-md-6 form-group">
                    <label for="payment-expiry-month">Expiry month</label>
                    <select id="payment-expiry-month" class="form-select" name="expiry_month" required>
                        <option value="">Select month</option>
                        <?php for ($month = 1; $month <= 12; $month++): ?>
                            <option value="<?= e((string) $month) ?>" <?= (string) ($formValues['expiry_month'] ?? '') === (string) $month ? 'selected' : '' ?>>
                                <?= e(sprintf('%02d', $month)) ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-md-6 form-group">
                    <label for="payment-expiry-year">Expiry year</label>
                    <select id="payment-expiry-year" class="form-select" name="expiry_year" required>
                        <option value="">Select year</option>
                        <?php $currentYear = (int) date('Y'); ?>
                        <?php for ($year = $currentYear; $year <= $currentYear + 12; $year++): ?>
                            <option value="<?= e((string) $year) ?>" <?= (string) ($formValues['expiry_year'] ?? '') === (string) $year ? 'selected' : '' ?>>
                                <?= e((string) $year) ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                </div>
            </div>

            <label class="toggle-field" for="payment-default-card">
                <input type="hidden" name="is_default" value="0">
                <input id="payment-default-card" type="checkbox" name="is_default" value="1" <?= !empty($formValues['is_default']) ? 'checked' : '' ?>>
                <span>Set as my default payment method</span>
            </label>

            <button class="btn btn-brand w-100" type="submit">Save card</button>
        </form>
    </section>
</div>
