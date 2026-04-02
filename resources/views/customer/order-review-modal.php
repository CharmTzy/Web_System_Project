<?php

declare(strict_types=1);

$reviewDataByProduct = $reviewDataByProduct ?? [];
$reviewStateJson = json_encode(
    $reviewDataByProduct,
    JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES
) ?: '{}';
?>

<script type="application/json" data-order-review-state><?= $reviewStateJson ?></script>

<div
    class="modal fade order-review-modal"
    id="orderReviewModal"
    tabindex="-1"
    aria-labelledby="orderReviewModalLabel"
    aria-hidden="true"
    data-order-review-modal
>
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content order-review-modal__content">
            <div class="modal-header order-review-modal__header">
                <div>
                    <span class="hero-section__eyebrow">Rate product</span>
                    <h2 id="orderReviewModalLabel">Share your review</h2>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close review dialog"></button>
            </div>

            <div class="modal-body order-review-modal__body">
                <div class="order-review-modal__product">
                    <div class="order-review-modal__product-image-shell">
                        <img src="" alt="" data-order-review-product-image>
                    </div>
                    <div class="order-review-modal__product-copy">
                        <span class="hero-section__eyebrow">Delivered order item</span>
                        <h3 data-order-review-product-name>Product</h3>
                        <p data-order-review-seller-name>Seller</p>
                    </div>
                </div>

                <div class="product-review-form__feedback" data-review-feedback></div>

                <form class="order-review-modal__form" data-order-review-form>
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                    <input type="hidden" name="product_id" value="">
                    <input type="hidden" name="action" value="save">

                    <fieldset class="order-review-modal__rating-group">
                        <legend>Product quality</legend>
                        <div class="order-review-modal__stars" data-order-review-stars>
                            <?php for ($rating = 5; $rating >= 1; $rating--): ?>
                                <input
                                    type="radio"
                                    id="order-review-rating-<?= e((string) $rating) ?>"
                                    name="rating"
                                    value="<?= e((string) $rating) ?>"
                                    <?= $rating === 5 ? 'required' : '' ?>
                                >
                                <label for="order-review-rating-<?= e((string) $rating) ?>" aria-label="<?= e((string) $rating) ?> stars">★</label>
                            <?php endfor; ?>
                        </div>
                    </fieldset>

                    <div class="order-review-modal__form-grid">
                        <div class="form-group">
                            <label for="order-review-title">Review title</label>
                            <input
                                id="order-review-title"
                                class="form-control"
                                type="text"
                                name="title"
                                maxlength="120"
                                placeholder="Sum up your experience"
                            >
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="order-review-comment">Your review</label>
                        <textarea
                            id="order-review-comment"
                            class="form-control"
                            name="comment"
                            rows="6"
                            maxlength="1200"
                            required
                            placeholder="Tell other shoppers about the quality, fit, performance, or what stood out after using this product."
                        ></textarea>
                    </div>

                    <div class="order-review-modal__media-grid">
                        <div class="form-group">
                            <label for="order-review-photo">Photo (optional)</label>
                            <input
                                id="order-review-photo"
                                class="form-control"
                                type="file"
                                name="photo"
                                accept=".jpg,.jpeg,.png,.webp,image/*"
                            >
                            <small class="form-text">Upload one review image. It will be stored in Google Cloud Storage.</small>
                        </div>
                        <div class="form-group">
                            <label for="order-review-video">Video (optional)</label>
                            <input
                                id="order-review-video"
                                class="form-control"
                                type="file"
                                name="video"
                                accept=".mp4,.webm,.mov,.m4v,video/*"
                            >
                            <small class="form-text">Upload one review video in MP4, WebM, MOV, or M4V format. Maximum 25 MB. It will also be stored in Google Cloud Storage.</small>
                        </div>
                    </div>

                    <div class="order-review-modal__existing-media" data-order-review-existing-media hidden>
                        <h4>Current media</h4>
                        <div class="product-review-card__media" data-order-review-existing-media-list></div>
                    </div>

                    <div class="order-review-modal__note">
                        <strong>Helpful reviews build trust.</strong>
                        <p>Focus on the product itself so other shoppers know what to expect before they buy.</p>
                    </div>

                    <div class="order-review-modal__actions">
                        <button class="btn btn-brand-outline" type="button" data-bs-dismiss="modal">Cancel</button>
                        <button class="btn btn-brand-outline" type="button" data-review-delete hidden>Delete review</button>
                        <button class="btn btn-brand" type="submit" data-review-submit>Submit review</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
