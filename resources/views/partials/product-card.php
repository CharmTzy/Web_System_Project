<?php

declare(strict_types=1);

$onSale = $product['compare_price'] !== null && $product['compare_price'] > $product['price'];
$discountPercentage = $onSale
    ? (int) round((1 - ($product['price'] / $product['compare_price'])) * 100)
    : 0;
?>
<article class="product-card h-100">
    <div class="product-card__media">
        <img src="<?= e($product['image_url']) ?>" alt="<?= e($product['name']) ?>" loading="lazy">
        <div class="product-card__badges">
            <?php if ($product['is_featured']): ?>
                <span class="pill-badge pill-badge--accent">Featured</span>
            <?php endif; ?>
            <?php if ($onSale): ?>
                <span class="pill-badge pill-badge--dark"><?= e((string) $discountPercentage) ?>% off</span>
            <?php endif; ?>
        </div>
    </div>
    <div class="product-card__body">
        <div class="product-card__meta">
            <span><?= e($product['category_name']) ?></span>
            <span>Seller: <?= e($product['seller_name']) ?></span>
        </div>
        <h3 class="product-card__title"><?= e($product['name']) ?></h3>
        <p class="product-card__description"><?= e($product['short_description']) ?></p>

        <dl class="product-card__details">
            <div>
                <dt>Rating</dt>
                <dd><?= e(number_format((float) $product['rating'], 1)) ?>/5</dd>
            </div>
            <div>
                <dt>Reviews</dt>
                <dd><?= e((string) $product['review_count']) ?></dd>
            </div>
            <div>
                <dt>Stock</dt>
                <dd><?= e((string) $product['stock_quantity']) ?> left</dd>
            </div>
        </dl>

        <div class="product-card__price-row">
            <div>
                <span class="product-card__price"><?= e(money($product['price'])) ?></span>
                <?php if ($onSale): ?>
                    <span class="product-card__compare"><?= e(money($product['compare_price'])) ?></span>
                <?php endif; ?>
            </div>
            <span class="product-card__sku"><?= e($product['sku']) ?></span>
        </div>

        <form class="product-card__form" data-cart-form data-open-cart="true">
            <input type="hidden" name="action" value="add">
            <input type="hidden" name="product_id" value="<?= e((string) $product['id']) ?>">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

            <div class="quantity-picker" data-quantity-picker>
                <button class="quantity-picker__button" type="button" data-quantity-button="decrement" aria-label="Decrease quantity">
                    -
                </button>
                <label class="visually-hidden" for="quantity-<?= e((string) $product['id']) ?>">Quantity for <?= e($product['name']) ?></label>
                <input
                    id="quantity-<?= e((string) $product['id']) ?>"
                    class="quantity-picker__input"
                    type="number"
                    name="quantity"
                    value="1"
                    min="1"
                    max="<?= e((string) $product['stock_quantity']) ?>"
                    inputmode="numeric"
                    data-quantity-input
                >
                <button class="quantity-picker__button" type="button" data-quantity-button="increment" aria-label="Increase quantity">
                    +
                </button>
            </div>

            <button class="btn btn-brand w-100" type="submit">
                Add to cart
            </button>
        </form>
    </div>
</article>

