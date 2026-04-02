<?php declare(strict_types=1); ?>
<div class="section-block">
    <div class="section-block__header">
        <div>
            <span class="results-header__eyebrow">Customer feedback</span>
            <h2><?= e((string) ($pagination['total_items'] ?? count($reviews))) ?> reviews</h2>
        </div>
    </div>

    <?php if (!empty($notice)): ?>
        <div class="alert alert-success" role="alert"><?= e((string) $notice) ?></div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger" role="alert"><?= e((string) $error) ?></div>
    <?php endif; ?>

    <form method="get" action="/seller/reviews.php" class="mb-4">
        <div class="row g-3 align-items-end">
            <div class="col-md-6 form-group">
                <label for="seller-review-search">Search reviews</label>
                <input
                    id="seller-review-search"
                    class="form-control"
                    type="text"
                    name="search"
                    value="<?= e((string) ($search ?? '')) ?>"
                    placeholder="Search by customer, product, title, comment, or reply"
                >
            </div>

            <div class="col-md-3 form-group">
                <label for="seller-review-rating">Filter by rating</label>
                <select id="seller-review-rating" class="form-select" name="rating">
                    <option value="">All ratings</option>
                    <?php for ($i = 5; $i >= 1; $i--): ?>
                        <option value="<?= e((string) $i) ?>" <?= (string) ($ratingFilter ?? '') === (string) $i ? 'selected' : '' ?>>
                            <?= e((string) $i) ?> star<?= $i === 1 ? '' : 's' ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </div>

            <div class="col-md-3 d-flex gap-2">
                <button class="btn btn-brand" type="submit">Apply</button>
                <a class="btn btn-brand-outline" href="/seller/reviews.php">Reset</a>
            </div>
        </div>
    </form>

    <?php if ($reviews === []): ?>
        <div class="empty-state">
            <h3>No reviews yet.</h3>
            <p>Customer reviews on your products will appear here.</p>
        </div>
    <?php else: ?>
        <div class="d-grid gap-4">
            <?php foreach ($reviews as $review): ?>
                <?php $postedAt = date('d M Y', strtotime((string) $review['updated_at'])); ?>
                <article class="product-review-card">
                    <div class="product-review-card__header">
                        <div>
                            <h3><?= e((string) ($review['user_name'] ?? 'Customer')) ?></h3>
                            <p>
                                <?= e($postedAt) ?>
                                ·
                                <?= e((string) ($review['product_name'] ?? 'Unknown product')) ?>
                            </p>
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

                    <form method="post" action="/seller/reviews.php">
                        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                        <input type="hidden" name="action" value="reply">
                        <input type="hidden" name="review_id" value="<?= e((string) $review['id']) ?>">

                        <div class="form-group">
                            <label for="seller-reply-<?= e((string) $review['id']) ?>">Reply as seller</label>
                            <textarea
                                id="seller-reply-<?= e((string) $review['id']) ?>"
                                class="form-control"
                                name="seller_reply"
                                rows="3"
                                maxlength="1200"
                                placeholder="Reply to this customer review"
                            ><?= e((string) ($review['seller_reply'] ?? '')) ?></textarea>
                        </div>

                        <div class="product-review-form__actions">
                            <button class="btn btn-brand-outline" type="submit">
                                <?= !empty($review['seller_reply']) ? 'Update seller reply' : 'Post seller reply' ?>
                            </button>
                        </div>
                    </form>

                    <?php if (!empty($review['seller_reply'])): ?>
                        <div class="product-review-card__reply">
                            <strong>Seller reply</strong>
                            <p><?= nl2br(e((string) $review['seller_reply'])) ?></p>
                        </div>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>

        <?= render('partials/pagination', ['pagination' => $pagination ?? null]) ?>
    <?php endif; ?>
</div>