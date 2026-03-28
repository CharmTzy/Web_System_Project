<?php

declare(strict_types=1);

$reviewContext = $reviewContext ?? [];
$reviews = $reviewContext['reviews'] ?? [];
$existingReview = $reviewContext['existing_review'] ?? null;
$canReview = !empty($reviewContext['can_review']);
$requiresSignIn = !empty($reviewContext['requires_sign_in']);
$isLoggedIn = !empty($_SESSION['user_id']);
$isCustomer = !empty($_SESSION['user_id']) && ($_SESSION['user_role'] ?? '') === 'customer';
$isAdmin = !empty($_SESSION['user_id']) && ($_SESSION['user_role'] ?? '') === 'admin';
$loginUrl = '/login.php?redirect=' . rawurlencode(product_url($product));
?>

<section class="product-detail__reviews-card" id="product-reviews" data-product-reviews data-product-id="<?= e((string) $product['id']) ?>">
    <div class="product-detail__section-heading">
        <span class="hero-section__eyebrow">Customer reviews</span>
        <h2>What customers are saying</h2>
    </div>

    <div class="product-reviews">
        <div class="product-reviews__composer">
            <div class="product-review-form__feedback" data-review-feedback></div>

            <?php if ($requiresSignIn && !$isLoggedIn): ?>
                <section class="empty-state empty-state--compact product-reviews__prompt">
                    <h3>Sign in to leave a review.</h3>
                    <p>Purchase this product with your customer account, then come back here to rate and review it.</p>
                    <a class="btn btn-brand" href="<?= e($loginUrl) ?>">Go to sign in</a>
                </section>
            <?php elseif (!$isCustomer): ?>
                <section class="empty-state empty-state--compact product-reviews__prompt">
                    <h3>Reviews are available for customer accounts.</h3>
                    <p>Seller and admin accounts can still browse customer feedback here.</p>
                </section>
            <?php elseif (!$canReview && $existingReview === null): ?>
                <section class="empty-state empty-state--compact product-reviews__prompt">
                    <h3>Review this after placing an order.</h3>
                    <p>Once this product is part of one of your confirmed orders, you can come back to leave a rating and comment.</p>
                    <a class="btn btn-brand-outline" href="/customer/orders.php">View my orders</a>
                </section>
            <?php else: ?>
                <form class="product-review-form" data-review-form enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="product_id" value="<?= e((string) $product['id']) ?>">
                    <input type="hidden" name="action" value="save">

                    <?php if (!empty($existingReview['id'])): ?>
                        <input type="hidden" name="review_id" value="<?= e((string) $existingReview['id']) ?>">
                    <?php endif; ?>

                    <div class="row g-3">
                        <div class="col-sm-4 form-group">
                            <label for="review-rating-<?= e((string) $product['id']) ?>">Rating</label>
                            <select id="review-rating-<?= e((string) $product['id']) ?>" class="form-select" name="rating" required>
                                <option value="">Choose</option>
                                <?php for ($rating = 5; $rating >= 1; $rating--): ?>
                                    <option value="<?= e((string) $rating) ?>" <?= (string) ($existingReview['rating'] ?? '') === (string) $rating ? 'selected' : '' ?>>
                                        <?= e((string) $rating) ?> star<?= $rating === 1 ? '' : 's' ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-sm-8 form-group">
                            <label for="review-title-<?= e((string) $product['id']) ?>">Title</label>
                            <input
                                id="review-title-<?= e((string) $product['id']) ?>"
                                class="form-control"
                                type="text"
                                name="title"
                                maxlength="120"
                                placeholder="Summarize your experience"
                                value="<?= e((string) ($existingReview['title'] ?? '')) ?>"
                            >
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="review-comment-<?= e((string) $product['id']) ?>">Comment</label>
                        <textarea
                            id="review-comment-<?= e((string) $product['id']) ?>"
                            class="form-control"
                            name="comment"
                            rows="4"
                            maxlength="1200"
                            required
                            placeholder="Share what you liked, what to expect, or anything another customer should know."
                        ><?= e((string) ($existingReview['comment'] ?? '')) ?></textarea>
                    </div>

                    <div class="row g-3">
                        <div class="col-sm-6 form-group">
                            <label for="review-photo-<?= e((string) $product['id']) ?>">Photo (optional)</label>
                            <input
                                id="review-photo-<?= e((string) $product['id']) ?>"
                                class="form-control"
                                type="file"
                                name="photo"
                                accept=".jpg,.jpeg,.png,.webp,image/*"
                            >
                        </div>
                        <div class="col-sm-6 form-group">
                            <label for="review-video-<?= e((string) $product['id']) ?>">Video (optional)</label>
                            <input
                                id="review-video-<?= e((string) $product['id']) ?>"
                                class="form-control"
                                type="file"
                                name="video"
                                accept=".mp4,.webm,video/*"
                            >
                        </div>
                    </div>

                    <?php if (!empty($existingReview['media'])): ?>
                        <div class="product-review-form__existing-media">
                            <h4>Existing media</h4>
                            <div class="product-review-card__media">
                                <?php foreach ($existingReview['media'] as $media): ?>
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
                        </div>
                    <?php endif; ?>

                    <div class="product-review-form__actions">
                        <button class="btn btn-brand" type="submit">
                            <?= $existingReview ? 'Update review' : 'Submit review' ?>
                        </button>

                        <?php if ($existingReview): ?>
                            <button class="btn btn-brand-outline" type="button" data-review-delete>
                                Delete review
                            </button>
                        <?php endif; ?>
                    </div>
                </form>
            <?php endif; ?>
        </div>

        <div class="product-reviews__list" data-review-list>
            <?php if ($reviews === []): ?>
                <section class="empty-state empty-state--compact product-reviews__prompt">
                    <h3>No written reviews yet.</h3>
                    <p>Star ratings may already be shown above, and written reviews will start appearing here as customers post them.</p>
                </section>
            <?php else: ?>
                <?php foreach ($reviews as $review): ?>
                    <?php $postedAt = date('d M Y', strtotime((string) $review['updated_at'])); ?>
                    <article class="product-review-card<?= !empty($_SESSION['user_id']) && (int) $_SESSION['user_id'] === (int) $review['user_id'] ? ' is-owned' : '' ?>">
                        <div class="product-review-card__header">
                            <div>
                                <h3><?= e((string) ($review['user_name'] ?? 'Customer')) ?></h3>
                                <p><?= e($postedAt) ?></p>
                            </div>
                            <strong>
                                <?= e(str_repeat('★', (int) $review['rating'])) ?><span><?= e(str_repeat('☆', max(0, 5 - (int) $review['rating']))) ?></span>
                            </strong>
                        </div>

                        <?php if (!empty($review['title'])): ?>
                            <h4><?= e((string) $review['title']) ?></h4>
                        <?php endif; ?>

                        <p><?= nl2br(e((string) $review['comment'])) ?></p>

                        <?php if (!empty($review['media'])): ?>
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
                                <strong>Seller reply</strong>
                                <p><?= nl2br(e((string) $review['seller_reply'])) ?></p>
                            </div>
                        <?php endif; ?>

                        <?php if ($isAdmin && !empty($review['is_flagged'])): ?>
                            <div class="product-review-card__reply">
                                <strong>Flagged review</strong>
                                <p><?= e((string) ($review['flagged_reason'] ?? 'No reason provided.')) ?></p>
                            </div>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>