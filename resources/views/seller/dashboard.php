<?php declare(strict_types=1); ?>
<div class="row g-4 mb-4">
    <div class="col-md-4">
        <div class="hero-stat-card">
            <span class="hero-stat-card__label">Total products</span>
            <strong><?= e((string) ($stats['total_products'] ?? 0)) ?></strong>
        </div>
    </div>
    <div class="col-md-4">
        <div class="hero-stat-card">
            <span class="hero-stat-card__label">Active listings</span>
            <strong><?= e((string) ($stats['active_products'] ?? 0)) ?></strong>
        </div>
    </div>
    <div class="col-md-4">
        <div class="hero-stat-card">
            <span class="hero-stat-card__label">Out of stock</span>
            <strong><?= e((string) ($stats['out_of_stock_products'] ?? 0)) ?></strong>
        </div>
    </div>
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
