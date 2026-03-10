<?php declare(strict_types=1); ?>
<div class="row g-4 mb-4">
    <div class="col-md-6">
        <div class="hero-stat-card">
            <span class="hero-stat-card__label">Store name</span>
            <strong><?= e($profile['seller_profile']['store_name'] ?? 'Not set') ?></strong>
        </div>
    </div>
    <div class="col-md-6">
        <div class="hero-stat-card">
            <span class="hero-stat-card__label">Store slug</span>
            <strong><?= e($profile['seller_profile']['store_slug'] ?? '-') ?></strong>
        </div>
    </div>
</div>
