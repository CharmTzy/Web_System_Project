<?php declare(strict_types=1); ?>
<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="hero-stat-card">
            <span class="hero-stat-card__label">Total users</span>
            <strong><?= e((string) $stats['total']) ?></strong>
        </div>
    </div>
    <div class="col-md-3">
        <div class="hero-stat-card">
            <span class="hero-stat-card__label">Sellers</span>
            <strong><?= e((string) $stats['sellers']) ?></strong>
        </div>
    </div>
    <div class="col-md-3">
        <div class="hero-stat-card">
            <span class="hero-stat-card__label">Customers</span>
            <strong><?= e((string) $stats['customers']) ?></strong>
        </div>
    </div>
    <div class="col-md-3">
        <div class="hero-stat-card">
            <span class="hero-stat-card__label">Coupons</span>
            <strong><?= e((string) ($stats['coupons'] ?? 0)) ?></strong>
        </div>
    </div>
    <div class="col-md-3">
        <div class="hero-stat-card">
            <span class="hero-stat-card__label">Products</span>
            <strong><?= e((string) ($stats['products'] ?? 0)) ?></strong>
        </div>
    </div>
    <div class="col-md-3">
        <div class="hero-stat-card">
            <span class="hero-stat-card__label">Addresses</span>
            <strong><?= e((string) ($stats['addresses'] ?? 0)) ?></strong>
        </div>
    </div>
    <div class="col-md-3">
        <div class="hero-stat-card">
            <span class="hero-stat-card__label">Help questions</span>
            <strong><?= e((string) ($stats['help_questions'] ?? 0)) ?></strong>
        </div>
    </div>
</div>
