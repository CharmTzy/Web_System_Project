<?php declare(strict_types=1); ?>
<?php $orderStats = $orderStats ?? []; ?>
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
    <div class="col-md-3">
        <div class="hero-stat-card">
            <span class="hero-stat-card__label">Delivery packages</span>
            <strong><?= e((string) ($orderStats['total'] ?? 0)) ?></strong>
        </div>
    </div>
    <div class="col-md-3">
        <div class="hero-stat-card">
            <span class="hero-stat-card__label">Awaiting action</span>
            <strong><?= e((string) ($orderStats['awaiting_action'] ?? 0)) ?></strong>
        </div>
    </div>
    <div class="col-md-3">
        <div class="hero-stat-card">
            <span class="hero-stat-card__label">In transit</span>
            <strong><?= e((string) ($orderStats['in_transit'] ?? 0)) ?></strong>
        </div>
    </div>
    <div class="col-md-3">
        <div class="hero-stat-card">
            <span class="hero-stat-card__label">Delivered</span>
            <strong><?= e((string) ($orderStats['delivered'] ?? 0)) ?></strong>
        </div>
    </div>
</div>
