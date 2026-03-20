<?php

declare(strict_types=1);

$onSale = $product['compare_price'] !== null && $product['compare_price'] > $product['price'];
$discountPercentage = $onSale
    ? (int) round((1 - ($product['price'] / $product['compare_price'])) * 100)
    : 0;
$categoryUrl = '/index.html?category=' . urlencode($product['category_slug']) . '#catalog-feed';
$maxQuantity = max(1, (int) $product['stock_quantity']);
?>
<article class="product-detail">
    <nav class="product-detail__breadcrumb" aria-label="Breadcrumb">
        <a href="/index.html">Shop</a>
        <span aria-hidden="true">/</span>
        <a href="<?= e($categoryUrl) ?>"><?= e($product['category_name']) ?></a>
        <span aria-hidden="true">/</span>
        <span><?= e($product['name']) ?></span>
    </nav>

    <section class="product-detail__hero">
        <div class="product-detail__media-card">
            <div class="product-detail__media-shell">
                <img
                    class="product-detail__image"
                    src="<?= e($product['image_url']) ?>"
                    alt="<?= e($product['name']) ?>"
                    loading="eager"
                >
            </div>
        </div>

        <div class="product-detail__summary-card">
            <div class="product-detail__eyebrow-row">
                <span class="pill-badge pill-badge--soft"><?= e($product['category_name']) ?></span>
                <?php if ($onSale): ?>
                    <span class="pill-badge pill-badge--accent"><?= e((string) $discountPercentage) ?>% off</span>
                <?php endif; ?>
            </div>

            <h1 class="product-detail__title"><?= e($product['name']) ?></h1>

            <div class="product-detail__meta">
                <span class="product-detail__seller">Sold by <?= e($product['seller_name']) ?></span>
                <span class="product-detail__meta-dot" aria-hidden="true"></span>
                <span class="product-detail__rating">&#9733; <?= e(number_format((float) $product['rating'], 1)) ?></span>
                <span class="product-detail__reviews">(<?= e((string) $product['review_count']) ?> reviews)</span>
            </div>

            <div class="product-detail__price-panel">
                <div class="product-detail__price-row">
                    <strong class="product-detail__price"><?= e(money($product['price'])) ?></strong>
                    <?php if ($onSale): ?>
                        <span class="product-detail__compare"><?= e(money($product['compare_price'])) ?></span>
                    <?php endif; ?>
                </div>
                <p class="product-detail__short-copy"><?= e($product['short_description']) ?></p>
            </div>

            <dl class="product-detail__facts">
                <div>
                    <dt>Availability</dt>
                    <dd><?= $product['stock_quantity'] > 0 ? 'In stock' : 'Out of stock' ?></dd>
                </div>
                <div>
                    <dt>Stock left</dt>
                    <dd><?= e((string) $product['stock_quantity']) ?></dd>
                </div>
                <div>
                    <dt>SKU</dt>
                    <dd><?= e($product['sku']) ?></dd>
                </div>
            </dl>

            <form class="product-detail__form" data-cart-form data-open-cart="true">
                <input type="hidden" name="action" value="add">
                <input type="hidden" name="product_id" value="<?= e((string) $product['id']) ?>">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

                <div class="product-detail__purchase-row">
                    <div class="product-detail__quantity-group">
                        <span class="product-detail__quantity-label">Quantity</span>
                        <div class="quantity-picker quantity-picker--detail" data-quantity-picker>
                            <button class="quantity-picker__button" type="button" data-quantity-button="decrement" aria-label="Decrease quantity">
                                -
                            </button>
                            <label class="visually-hidden" for="detail-quantity-<?= e((string) $product['id']) ?>">Quantity for <?= e($product['name']) ?></label>
                            <input
                                id="detail-quantity-<?= e((string) $product['id']) ?>"
                                class="quantity-picker__input"
                                type="number"
                                name="quantity"
                                value="1"
                                min="1"
                                max="<?= e((string) $maxQuantity) ?>"
                                inputmode="numeric"
                                data-quantity-input
                            >
                            <button class="quantity-picker__button" type="button" data-quantity-button="increment" aria-label="Increase quantity">
                                +
                            </button>
                        </div>
                    </div>

                    <button class="btn btn-brand product-detail__submit" type="submit" <?= $product['stock_quantity'] < 1 ? 'disabled' : '' ?>>
                        Add to cart
                    </button>
                </div>
            </form>

            <div class="product-detail__actions">
                <a class="product-detail__secondary-link" href="<?= e($categoryUrl) ?>">Back to <?= e($product['category_name']) ?></a>
            </div>
        </div>
    </section>

    <section class="product-detail__description-card">
        <div class="product-detail__section-heading">
            <span class="hero-section__eyebrow">Product details</span>
            <h2>What to expect</h2>
        </div>
        <p><?= nl2br(e($product['description'])) ?></p>
    </section>
</article>
