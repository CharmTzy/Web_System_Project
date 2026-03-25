<?php declare(strict_types=1); ?>
<?php
$featured = $featured ?? [];
$limitedTime = $limited_time ?? [];
$freeShipping = $free_shipping ?? [];
$shopCoupons = $shop_coupons ?? [];
$stats = $stats ?? ['available_count' => 0, 'ending_soon_count' => 0, 'shipping_count' => 0];
$hasCoupons = $featured !== []
    || $limitedTime !== []
    || $freeShipping !== []
    || $shopCoupons !== [];
?>
<section class="hero-section hero-section--compact">
    <div class="container">
        <span class="hero-section__eyebrow">Rewards wallet</span>
        <h1 class="hero-section__title" style="max-width:16ch;">My Coupons</h1>
        <p class="hero-section__copy coupon-hero__copy">Keep your limited-time discounts, shipping perks, and shop
            offers in one place before checkout.</p>
    </div>
</section>

<section class="catalog-section customer-coupons-page">
    <div class="container">
        <div class="coupon-overview-grid">
            <article class="coupon-overview-card">
                <span class="results-header__eyebrow">Available now</span>
                <strong><?= e((string) $stats['available_count']) ?></strong>
                <p>Ready to use on eligible orders.</p>
            </article>
            <article class="coupon-overview-card">
                <span class="results-header__eyebrow">Ending soon</span>
                <strong><?= e((string) $stats['ending_soon_count']) ?></strong>
                <p>Campaigns closing within 7 days.</p>
            </article>
            <article class="coupon-overview-card">
                <span class="results-header__eyebrow">Shipping perks</span>
                <strong><?= e((string) $stats['shipping_count']) ?></strong>
                <p>Delivery discounts for qualifying baskets.</p>
            </article>
        </div>

        <?php if (!$hasCoupons): ?>
            <div class="section-block">
                <div class="empty-state">
                    <h3>No coupons available yet.</h3>
                    <p>Your coupon wallet will appear here once discount campaigns are added to the database.</p>
                    <a class="btn btn-brand" href="/">Continue shopping</a>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($featured !== []): ?>
            <div class="section-block coupon-featured-block">
                <div class="section-block__header">
                    <div>
                        <span class="results-header__eyebrow">Featured picks</span>
                        <h2>Best savings to use first</h2>
                    </div>
                </div>

                <div class="row g-4">
                    <?php foreach ($featured as $coupon): ?>
                        <div class="col-lg-4">
                            <article class="coupon-card coupon-card--featured coupon-card--<?= e($coupon['accent']) ?>">
                                <div class="coupon-card__top">
                                    <span class="pill-badge pill-badge--accent"><?= e($coupon['type_label']) ?></span>
                                    <span class="coupon-card__expiry"><?= e($coupon['expiry_label']) ?></span>
                                </div>
                                <div class="coupon-card__value"><?= e($coupon['discount_label']) ?></div>
                                <h3><?= e($coupon['title']) ?></h3>
                                <p><?= e($coupon['description']) ?></p>
                                <div class="coupon-card__meta">
                                    <span><?= e($coupon['scope_label']) ?></span>
                                    <span><?= e($coupon['minimum_spend_label']) ?></span>
                                    <span><?= e($coupon['validity_label']) ?></span>
                                </div>
                                <div class="coupon-card__footer">
                                    <code><?= e($coupon['code']) ?></code>
                                    <a class="btn btn-brand btn-sm" href="/cart.html">Use at checkout</a>
                                </div>
                            </article>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($limitedTime !== []): ?>
            <div class="section-block">
                <div class="section-block__header">
                    <div>
                        <span class="results-header__eyebrow">Limited-time deals</span>
                        <h2>Use before they expire</h2>
                    </div>
                </div>

                <div class="row g-4">
                    <?php foreach ($limitedTime as $coupon): ?>
                        <div class="col-lg-6">
                            <article class="coupon-card coupon-card--limited">
                                <div class="coupon-card__top">
                                    <span class="pill-badge pill-badge--soft"><?= e($coupon['type_label']) ?></span>
                                    <span class="coupon-card__expiry"><?= e($coupon['expiry_label']) ?></span>
                                </div>
                                <div class="coupon-card__value"><?= e($coupon['discount_label']) ?></div>
                                <h3><?= e($coupon['title']) ?></h3>
                                <p><?= e($coupon['description']) ?></p>
                                <div class="coupon-card__meta">
                                    <span><?= e($coupon['scope_label']) ?></span>
                                    <span><?= e($coupon['minimum_spend_label']) ?></span>
                                </div>
                                <div class="coupon-card__footer">
                                    <code><?= e($coupon['code']) ?></code>
                                    <span class="coupon-card__validity"><?= e($coupon['validity_label']) ?></span>
                                </div>
                            </article>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($freeShipping !== [] || $shopCoupons !== []): ?>
            <div class="row g-4">
                <?php if ($freeShipping !== []): ?>
                    <div class="col-xl-5">
                        <div class="section-block h-100">
                            <div class="section-block__header">
                                <div>
                                    <span class="results-header__eyebrow">Shipping offers</span>
                                    <h2>Delivery savings</h2>
                                </div>
                            </div>

                            <div class="coupon-stack">
                                <?php foreach ($freeShipping as $coupon): ?>
                                    <article class="coupon-card coupon-card--shipping">
                                        <div class="coupon-card__top">
                                            <span class="pill-badge pill-badge--accent"><?= e($coupon['discount_label']) ?></span>
                                            <span class="coupon-card__expiry"><?= e($coupon['expiry_label']) ?></span>
                                        </div>
                                        <h3><?= e($coupon['title']) ?></h3>
                                        <p><?= e($coupon['description']) ?></p>
                                        <div class="coupon-card__meta">
                                            <span><?= e($coupon['scope_label']) ?></span>
                                            <span><?= e($coupon['minimum_spend_label']) ?></span>
                                        </div>
                                        <div class="coupon-card__footer">
                                            <code><?= e($coupon['code']) ?></code>
                                            <span class="coupon-card__validity"><?= e($coupon['validity_label']) ?></span>
                                        </div>
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ($shopCoupons !== []): ?>
                    <div class="col-xl-<?= $freeShipping !== [] ? '7' : '12' ?>">
                        <div class="section-block h-100">
                            <div class="section-block__header">
                                <div>
                                    <span class="results-header__eyebrow">Shop offers</span>
                                    <h2>Specific shop discounts</h2>
                                </div>
                            </div>

                            <div class="row g-4">
                                <?php foreach ($shopCoupons as $coupon): ?>
                                    <div class="col-md-6">
                                        <article class="coupon-card coupon-card--<?= e($coupon['accent']) ?>">
                                            <div class="coupon-card__top">
                                                <span class="pill-badge pill-badge--soft"><?= e($coupon['type_label']) ?></span>
                                                <span class="coupon-card__expiry"><?= e($coupon['expiry_label']) ?></span>
                                            </div>
                                            <div class="coupon-card__value"><?= e($coupon['discount_label']) ?></div>
                                            <h3><?= e($coupon['title']) ?></h3>
                                            <p><?= e($coupon['description']) ?></p>
                                            <div class="coupon-card__meta">
                                                <span><?= e($coupon['scope_label']) ?></span>
                                                <span><?= e($coupon['minimum_spend_label']) ?></span>
                                            </div>
                                            <div class="coupon-card__footer">
                                                <code><?= e($coupon['code']) ?></code>
                                                <span class="coupon-card__validity"><?= e($coupon['validity_label']) ?></span>
                                            </div>
                                        </article>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</section>