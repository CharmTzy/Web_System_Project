<?php declare(strict_types=1); ?>
<div class="section-block">
    <div class="section-block__header">
        <div>
            <span class="results-header__eyebrow">Review moderation</span>
            <h2><?= e((string) ($pagination['total_items'] ?? count($reviews))) ?> reviews</h2>
        </div>
    </div>

    <?php if (!empty($notice)): ?>
        <div class="alert alert-success" role="alert"><?= e((string) $notice) ?></div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger" role="alert"><?= e((string) $error) ?></div>
    <?php endif; ?>

    <?php if ($reviews === []): ?>
        <div class="empty-state">
            <h3>No reviews found.</h3>
            <p>Customer reviews will appear here once they are submitted.</p>
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

                    <?php if (!empty($review['seller_reply'])): ?>
                        <div class="product-review-card__reply">
                            <strong>Seller reply</strong>
                            <p><?= nl2br(e((string) $review['seller_reply'])) ?></p>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($review['is_flagged'])): ?>
                        <div class="product-review-card__reply">
                            <strong>Flagged review</strong>
                            <p><?= e((string) ($review['flagged_reason'] ?? 'No reason provided.')) ?></p>

                            <?php if (!empty($review['hide_reason'])): ?>
                                <strong>Hide reason</strong>
                                <p><?= e((string) $review['hide_reason']) ?></p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <div class="product-review-card__admin-actions">
                        <form method="post" action="/admin/reviews.php">
                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="action" value="flag">
                            <input type="hidden" name="review_id" value="<?= e((string) $review['id']) ?>">

                            <div class="form-group">
                                <label for="flag-reason-<?= e((string) $review['id']) ?>">Flag reason</label>
                                <textarea
                                    id="flag-reason-<?= e((string) $review['id']) ?>"
                                    class="form-control"
                                    name="reason"
                                    rows="2"
                                    maxlength="255"
                                    placeholder="Why should this review be flagged?"
                                ><?= e((string) ($review['flagged_reason'] ?? '')) ?></textarea>
                            </div>

                            <div class="product-review-form__actions">
                                <button class="btn btn-brand-outline" type="submit">Flag review</button>
                            </div>
                        </form>

                        <form method="post" action="/admin/reviews.php">
                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="action" value="hide">
                            <input type="hidden" name="review_id" value="<?= e((string) $review['id']) ?>">

                            <div class="form-group">
                                <label for="hide-reason-<?= e((string) $review['id']) ?>">Hide reason</label>
                                <textarea
                                    id="hide-reason-<?= e((string) $review['id']) ?>"
                                    class="form-control"
                                    name="reason"
                                    rows="2"
                                    maxlength="255"
                                    placeholder="Why should this review be hidden?"
                                ><?= e((string) ($review['hide_reason'] ?? '')) ?></textarea>
                            </div>

                            <div class="product-review-form__actions">
                                <button class="btn btn-brand-outline" type="submit">Hide review</button>
                            </div>
                        </form>

                        <?php if (!empty($review['is_flagged']) || empty($review['is_visible'])): ?>
                            <form method="post" action="/admin/reviews.php">
                                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                                <input type="hidden" name="action" value="restore">
                                <input type="hidden" name="review_id" value="<?= e((string) $review['id']) ?>">

                                <div class="product-review-form__actions">
                                    <button class="btn btn-brand-outline" type="submit">Restore review</button>
                                </div>
                            </form>
                        <?php endif; ?>

                        <form method="post" action="/admin/reviews.php" onsubmit="return confirm('Delete this review?');">
                            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                            <input type="hidden" name="action" value="admin-delete">
                            <input type="hidden" name="review_id" value="<?= e((string) $review['id']) ?>">

                            <div class="product-review-form__actions">
                                <button class="btn btn-outline-danger" type="submit">Delete review</button>
                            </div>
                        </form>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>

        <?= render('partials/pagination', ['pagination' => $pagination ?? null]) ?>
    <?php endif; ?>
</div>