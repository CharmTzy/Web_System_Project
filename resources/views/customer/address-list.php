<?php declare(strict_types=1); ?>
<div class="section-block">
    <div class="section-block__header">
        <div>
            <span class="results-header__eyebrow">Address book</span>
            <h2><?= e((string) count($addresses)) ?> saved addresses</h2>
        </div>
        <?php if (count($addresses) < 5): ?>
            <button class="btn btn-brand" type="button" data-address-add>Add new address</button>
        <?php else: ?>
            <span class="pill-badge pill-badge--soft">Maximum 5 addresses reached</span>
        <?php endif; ?>
    </div>

    <?php if ($addresses === []): ?>
        <div class="empty-state">
            <h3>No addresses yet.</h3>
            <p>Add a delivery address to keep your account details organized.</p>
            <button class="btn btn-brand" type="button" data-address-add>Add your first address</button>
        </div>
    <?php else: ?>
        <div class="row g-4" data-address-list>
            <?php foreach ($addresses as $addr): ?>
                <div class="col-md-6">
                    <article class="address-card">
                        <div class="address-card__header">
                            <span class="pill-badge pill-badge--soft"><?= e($addr['label']) ?></span>
                            <?php if ($addr['is_default']): ?>
                                <span class="pill-badge pill-badge--accent">Default</span>
                            <?php endif; ?>
                        </div>
                        <h3 class="address-card__name"><?= e($addr['recipient']) ?></h3>
                        <p class="address-card__line"><?= e($addr['line_1']) ?></p>
                        <?php if ($addr['line_2']): ?>
                            <p class="address-card__line"><?= e($addr['line_2']) ?></p>
                        <?php endif; ?>
                        <p class="address-card__line"><?= e($addr['city']) ?>, <?= e($addr['state']) ?> <?= e($addr['postal_code']) ?></p>
                        <p class="address-card__line"><?= e($addr['country']) ?></p>
                        <?php if ($addr['phone']): ?>
                            <p class="address-card__line"><?= e($addr['phone']) ?></p>
                        <?php endif; ?>
                        <div class="address-card__actions">
                            <?php if (!$addr['is_default']): ?>
                                <button class="btn btn-brand-outline btn-sm" data-address-default="<?= e((string) $addr['id']) ?>">Set default</button>
                            <?php endif; ?>
                            <button class="btn btn-brand-outline btn-sm" data-address-edit="<?= e((string) $addr['id']) ?>"
                                data-label="<?= e($addr['label']) ?>"
                                data-recipient="<?= e($addr['recipient']) ?>"
                                data-line-1="<?= e($addr['line_1']) ?>"
                                data-line-2="<?= e($addr['line_2'] ?? '') ?>"
                                data-city="<?= e($addr['city']) ?>"
                                data-state="<?= e($addr['state']) ?>"
                                data-postal-code="<?= e($addr['postal_code']) ?>"
                                data-country="<?= e($addr['country']) ?>"
                                data-phone="<?= e($addr['phone'] ?? '') ?>"
                            >Edit</button>
                            <button class="btn btn-link btn-remove btn-sm" data-address-delete="<?= e((string) $addr['id']) ?>">Delete</button>
                        </div>
                    </article>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
