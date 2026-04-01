<?php

declare(strict_types=1);

$reviewContext = $reviewContext ?? [];
$reviews = $reviewContext['reviews'] ?? [];
$isLoggedIn = !empty($_SESSION['user_id']);
$isCustomer = $isLoggedIn && ($_SESSION['user_role'] ?? '') === 'customer';
$reviewCount = max((int) ($product['review_count'] ?? 0), count($reviews));
$averageRating = number_format((float) ($product['rating'] ?? 0), 1);
$roundedRating = (int) round((float) ($product['rating'] ?? 0));
$ordersUrl = '/customer/orders.php';
$ordersLoginUrl = '/login.php?redirect=' . rawurlencode($ordersUrl);
$mediaCount = 0;
$ratingBuckets = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];

$reviewerInitials = static function (string $name): string {
    $parts = preg_split('/\s+/', trim($name)) ?: [];
    $parts = array_values(array_filter($parts));

    if ($parts === []) {
        return 'CU';
    }

    if (count($parts) === 1) {
        return strtoupper(substr($parts[0], 0, 2));
    }

    return strtoupper(substr($parts[0], 0, 1) . substr($parts[count($parts) - 1], 0, 1));
};

usort($reviews, static function (array $left, array $right): int {
    $leftRating = (int) ($left['rating'] ?? 0);
    $rightRating = (int) ($right['rating'] ?? 0);

    if ($leftRating !== $rightRating) {
        return $rightRating <=> $leftRating;
    }

    $leftUpdatedAt = strtotime((string) ($left['updated_at'] ?? '')) ?: 0;
    $rightUpdatedAt = strtotime((string) ($right['updated_at'] ?? '')) ?: 0;

    return $rightUpdatedAt <=> $leftUpdatedAt;
});

foreach ($reviews as $review) {
    $rating = (int) ($review['rating'] ?? 0);

    if (isset($ratingBuckets[$rating])) {
        $ratingBuckets[$rating]++;
    }

    if (!empty($review['media'])) {
        $mediaCount++;
    }
}

$filterChips = [
    ['key' => 'all', 'label' => 'All', 'count' => $reviewCount],
    ['key' => 'rating-5', 'label' => '5 Star', 'count' => $ratingBuckets[5]],
    ['key' => 'rating-4', 'label' => '4 Star', 'count' => $ratingBuckets[4]],
    ['key' => 'rating-3', 'label' => '3 Star', 'count' => $ratingBuckets[3]],
    ['key' => 'rating-2', 'label' => '2 Star', 'count' => $ratingBuckets[2]],
    ['key' => 'rating-1', 'label' => '1 Star', 'count' => $ratingBuckets[1]],
    ['key' => 'media', 'label' => 'With Media', 'count' => $mediaCount],
];
?>

<section class="product-detail__reviews-card" id="product-reviews" data-product-reviews data-product-id="<?= e((string) $product['id']) ?>">
    <div class="product-detail__section-heading">
        <span class="hero-section__eyebrow">Customer feedback</span>
        <h2>Product ratings</h2>
        <p class="product-reviews__section-copy">
            Verified buyers review products from their delivered orders, so the feedback here stays tied to real purchases.
            <?php if ($isCustomer): ?>
                <a href="<?= e($ordersUrl) ?>">Open my orders</a>
            <?php elseif (!$isLoggedIn): ?>
                <a href="<?= e($ordersLoginUrl) ?>">Sign in to view orders</a>
            <?php endif; ?>
        </p>
    </div>

    <div class="product-reviews product-reviews--catalog">
        <div class="product-reviews__toolbar">
            <div class="product-reviews__headline">
                <div class="product-reviews__headline-score">
                    <strong><?= e($averageRating) ?></strong>
                    <span>out of 5</span>
                </div>
                <div class="product-reviews__headline-stars" aria-label="<?= e($averageRating) ?> out of 5 stars">
                    <?= e(str_repeat('★', $roundedRating)) ?><span><?= e(str_repeat('☆', max(0, 5 - $roundedRating))) ?></span>
                </div>
            </div>

            <div class="product-reviews__filters" role="tablist" aria-label="Review filters">
                <?php foreach ($filterChips as $index => $chip): ?>
                    <button
                        class="product-reviews__filter<?= $index === 0 ? ' is-active' : '' ?>"
                        type="button"
                        data-review-filter="<?= e($chip['key']) ?>"
                        aria-pressed="<?= $index === 0 ? 'true' : 'false' ?>"
                    >
                        <?= e($chip['label']) ?> (<?= e((string) $chip['count']) ?>)
                    </button>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="product-reviews__feed" data-review-list>
            <?php if ($reviews === []): ?>
                <section class="empty-state empty-state--compact product-reviews__prompt" data-review-empty-state>
                    <h3>No written reviews yet.</h3>
                    <p>Ratings and comments from verified buyers will appear here once customers start sharing their experience.</p>
                </section>
            <?php else: ?>
                <section class="empty-state empty-state--compact product-reviews__prompt" data-review-empty-state hidden>
                    <h3>No reviews match this filter.</h3>
                    <p>Try another rating or switch back to all reviews.</p>
                </section>

                <?php foreach ($reviews as $review): ?>
                    <?php
                    $postedAt = date('d M Y', strtotime((string) $review['updated_at']));
                    $userName = (string) ($review['user_name'] ?? 'Customer');
                    $hasComment = trim((string) ($review['comment'] ?? '')) !== '';
                    $hasMedia = !empty($review['media']);
                    ?>
                    <article
                        class="product-review-card product-review-card--catalog"
                        data-review-card
                        data-review-rating="<?= e((string) ((int) $review['rating'])) ?>"
                        data-review-has-media="<?= $hasMedia ? 'true' : 'false' ?>"
                    >
                        <div class="product-review-card__catalog-header">
                            <div class="product-review-card__identity">
                                <span class="product-review-card__avatar" aria-hidden="true"><?= e($reviewerInitials($userName)) ?></span>
                                <div>
                                    <h3><?= e($userName) ?></h3>
                                    <p><?= e($postedAt) ?> | Verified purchase</p>
                                </div>
                            </div>
                            <strong class="product-review-card__stars">
                                <?= e(str_repeat('★', (int) $review['rating'])) ?><span><?= e(str_repeat('☆', max(0, 5 - (int) $review['rating']))) ?></span>
                            </strong>
                        </div>

                        <?php if (!empty($review['title'])): ?>
                            <h4><?= e((string) $review['title']) ?></h4>
                        <?php endif; ?>

                        <?php if ($hasComment): ?>
                            <p class="product-review-card__comment"><?= nl2br(e((string) $review['comment'])) ?></p>
                        <?php endif; ?>

                        <?php if ($hasMedia): ?>
                            <div class="product-review-card__media">
                                <?php foreach ($review['media'] as $media): ?>
                                    <?php if (($media['media_type'] ?? '') === 'photo'): ?>
                                        <img
                                            src="<?= e((string) $media['file_path']) ?>"
                                            alt="Review photo"
                                            class="product-review-card__image"
                                        >
                                    <?php elseif (($media['media_type'] ?? '') === 'video'): ?>
                                        <video controls class="product-review-card__video">
                                            <source src="<?= e((string) $media['file_path']) ?>">
                                            Your browser does not support video playback.
                                        </video>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($review['seller_reply'])): ?>
                            <div class="product-review-card__reply">
                                <strong>Seller response</strong>
                                <p><?= nl2br(e((string) $review['seller_reply'])) ?></p>
                            </div>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>
