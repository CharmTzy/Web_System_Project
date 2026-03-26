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
                <h3>No legacy cards stored.</h3>
                <p>Online card entry is disabled. If you add a real payment gateway later, collect payment methods there instead of on this site.</p>
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
                <span class="hero-section__eyebrow">Disabled</span>
                <h2 class="customer-panel__title">Direct card collection has been removed</h2>
            </div>
        </div>

        <?php if (!empty($formError)): ?>
            <div class="alert alert-danger" role="alert"><?= e((string) $formError) ?></div>
        <?php endif; ?>

        <div class="alert alert-warning mb-0" role="alert">
            <strong>Do not enter real card details here.</strong>
            <span class="d-block mt-2">This project does not use a verified payment processor yet. Until one is integrated, all card-entry and automatic payment behavior should remain disabled.</span>
        </div>
    </section>
</div>
