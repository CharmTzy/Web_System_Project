<?php

declare(strict_types=1);

$onSale = $product['compare_price'] !== null && $product['compare_price'] > $product['price'];
$discountPercentage = $onSale
    ? (int) round((1 - ($product['price'] / $product['compare_price'])) * 100)
    : 0;
$categoryUrl = '/?category=' . urlencode($product['category_slug']);
$maxQuantity = max(1, (int) $product['stock_quantity']);
$mediaItems = array_values(array_filter(
    $product['media'] ?? [],
    static fn(mixed $media): bool => is_array($media) && !empty($media['url'])
));
$reviewContext = $reviewContext ?? [
    'reviews' => [],
    'existing_review' => null,
    'can_review' => false,
    'requires_sign_in' => true,
];

if ($mediaItems === []) {
    $mediaItems = [
        [
            'id' => 0,
            'type' => 'image',
            'url' => $product['image_url'],
            'thumbnail_url' => $product['image_url'],
            'alt_text' => $product['name'],
            'sort_order' => 1,
            'is_primary' => true,
        ]
    ];
}

$primaryMediaIndex = 0;

foreach ($mediaItems as $index => $media) {
    if (!empty($media['is_primary'])) {
        $primaryMediaIndex = $index;
        break;
    }
}
?>
<article class="product-detail">
    <nav class="product-detail__breadcrumb" aria-label="Breadcrumb">
        <a href="/">Shop</a>
        <span aria-hidden="true">/</span>
        <a href="<?= e($categoryUrl) ?>"><?= e($product['category_name']) ?></a>
        <span aria-hidden="true">/</span>
        <span><?= e($product['name']) ?></span>
    </nav>

    <section class="product-detail__hero">
        <div class="product-detail__media-card">
            <div class="product-detail__media-shell">
                <?php foreach ($mediaItems as $index => $media): ?>
                    <?php
                    $mediaType = ($media['type'] ?? 'image') === 'video' ? 'video' : 'image';
                    $isActiveMedia = $index === $primaryMediaIndex;
                    $mediaUrl = (string) $media['url'];
                    $thumbnailUrl = (string) ($media['thumbnail_url'] ?? ($mediaType === 'image' ? $mediaUrl : $product['image_url']));
                    $altText = (string) ($media['alt_text'] ?? $product['name']);
                    ?>
                    <div class="product-detail__media-frame<?= $isActiveMedia ? ' is-active' : '' ?>"
                        data-product-media-panel="<?= e((string) $index) ?>" <?= $isActiveMedia ? '' : 'hidden' ?>>
                        <?php if ($mediaType === 'video'): ?>
                            <video class="product-detail__video" controls preload="metadata" poster="<?= e($thumbnailUrl) ?>">
                                <source src="<?= e($mediaUrl) ?>">
                                Your browser does not support embedded video.
                            </video>
                        <?php else: ?>
                            <img class="product-detail__image" src="<?= e($mediaUrl) ?>" alt="<?= e($altText) ?>"
                                loading="<?= $isActiveMedia ? 'eager' : 'lazy' ?>">
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if (count($mediaItems) > 1): ?>
                <div class="product-detail__thumb-row" aria-label="Product media gallery">
                    <?php foreach ($mediaItems as $index => $media): ?>
                        <?php
                        $mediaType = ($media['type'] ?? 'image') === 'video' ? 'video' : 'image';
                        $isActiveMedia = $index === $primaryMediaIndex;
                        $mediaUrl = (string) $media['url'];
                        $thumbnailUrl = (string) ($media['thumbnail_url'] ?? ($mediaType === 'image' ? $mediaUrl : $product['image_url']));
                        $altText = (string) ($media['alt_text'] ?? $product['name']);
                        ?>
                        <button class="product-detail__thumb<?= $isActiveMedia ? ' is-active' : '' ?>" type="button"
                            data-product-media-thumb="<?= e((string) $index) ?>"
                            aria-pressed="<?= $isActiveMedia ? 'true' : 'false' ?>"
                            aria-label="Show <?= e($mediaType) ?> <?= e((string) ($index + 1)) ?>">
                            <img class="product-detail__thumb-media" src="<?= e($thumbnailUrl) ?>" alt="<?= e($altText) ?>"
                                loading="lazy">
                            <?php if ($mediaType === 'video'): ?>
                                <span class="product-detail__thumb-badge">Video</span>
                            <?php endif; ?>
                        </button>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
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
                <span class="product-detail__rating">&#9733;
                    <?= e(number_format((float) $product['rating'], 1)) ?></span>
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
                            <button class="quantity-picker__button" type="button" data-quantity-button="decrement"
                                aria-label="Decrease quantity">
                                -
                            </button>
                            <label class="visually-hidden"
                                for="detail-quantity-<?= e((string) $product['id']) ?>">Quantity for
                                <?= e($product['name']) ?></label>
                            <input id="detail-quantity-<?= e((string) $product['id']) ?>" class="quantity-picker__input"
                                type="number" name="quantity" value="1" min="1" max="<?= e((string) $maxQuantity) ?>"
                                inputmode="numeric" data-quantity-input>
                            <button class="quantity-picker__button" type="button" data-quantity-button="increment"
                                aria-label="Increase quantity">
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
                <a class="product-detail__secondary-link" href="<?= e($categoryUrl) ?>">Back to
                    <?= e($product['category_name']) ?></a>
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

    <?= render('partials/product-reviews', [
        'product' => $product,
        'reviewContext' => $reviewContext,
    ]) ?>
</article>
