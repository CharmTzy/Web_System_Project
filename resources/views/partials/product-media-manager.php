<?php

declare(strict_types=1);

$mediaFieldPrefix = trim((string) ($fieldPrefix ?? 'product'));
$existingMedia = array_values(array_filter(
    $media ?? [],
    static fn (mixed $item): bool => is_array($item) && (($item['type'] ?? 'image') === 'image')
));
$selectedPrimaryMediaId = (string) ($selectedPrimaryMediaId ?? '');
$selectedRemovalIds = array_map('strval', array_values(array_filter(
    (array) ($selectedRemovalIds ?? []),
    static fn (mixed $value): bool => (int) $value > 0
)));

if ($selectedPrimaryMediaId === '') {
    foreach ($existingMedia as $item) {
        if (!empty($item['is_primary']) && (int) ($item['id'] ?? 0) > 0) {
            $selectedPrimaryMediaId = (string) $item['id'];
            break;
        }
    }
}
?>
<div class="form-group">
    <label>Product image library</label>
    <div class="product-media-manager">
        <?php if ($existingMedia !== []): ?>
            <div class="product-media-grid">
                <?php foreach ($existingMedia as $item): ?>
                    <?php
                    $mediaId = (int) ($item['id'] ?? 0);
                    $isPersisted = $mediaId > 0;
                    $isSelectedPrimary = $isPersisted && $selectedPrimaryMediaId === (string) $mediaId;
                    $isFallbackPrimary = !$isPersisted && !empty($item['is_primary']);
                    ?>
                    <article class="product-media-card<?= $isSelectedPrimary || $isFallbackPrimary ? ' is-primary' : '' ?>">
                        <div class="product-media-card__preview">
                            <img src="<?= e((string) ($item['thumbnail_url'] ?? $item['url'] ?? '')) ?>" alt="<?= e((string) ($item['alt_text'] ?? 'Product image')) ?>">
                        </div>
                        <div class="product-media-card__body">
                            <strong class="product-media-card__title">
                                <?= $isSelectedPrimary || $isFallbackPrimary ? 'Primary storefront image' : 'Gallery image' ?>
                            </strong>
                            <p class="product-media-card__meta">
                                <?= $isPersisted ? 'Stored in Google Cloud Storage' : 'Current saved image' ?>
                            </p>
                            <?php if ($isPersisted): ?>
                                <div class="product-media-card__actions">
                                    <label class="product-media-card__choice">
                                        <input type="radio" name="primary_media_id" value="<?= e((string) $mediaId) ?>" <?= $isSelectedPrimary ? 'checked' : '' ?>>
                                        <span>Use as primary</span>
                                    </label>
                                    <label class="product-media-card__choice">
                                        <input type="checkbox" name="remove_media_ids[]" value="<?= e((string) $mediaId) ?>" <?= in_array((string) $mediaId, $selectedRemovalIds, true) ? 'checked' : '' ?>>
                                        <span>Remove image</span>
                                    </label>
                                </div>
                            <?php else: ?>
                                <p class="product-media-card__helper">Upload a new primary image if you want to replace this legacy image.</p>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="product-media-empty">
                <strong>No saved product images yet.</strong>
                <p>Upload one primary image and optional gallery images to populate this product across the storefront.</p>
            </div>
        <?php endif; ?>

        <div class="row g-3">
            <div class="col-md-6 form-group">
                <label for="<?= e($mediaFieldPrefix) ?>-primary-image-upload">Upload primary image</label>
                <input
                    id="<?= e($mediaFieldPrefix) ?>-primary-image-upload"
                    class="form-control"
                    type="file"
                    name="primary_image_upload"
                    accept="image/jpeg,image/png,image/webp,image/gif,image/avif">
            </div>
            <div class="col-md-6 form-group">
                <label for="<?= e($mediaFieldPrefix) ?>-gallery-image-upload">Upload gallery images</label>
                <input
                    id="<?= e($mediaFieldPrefix) ?>-gallery-image-upload"
                    class="form-control"
                    type="file"
                    name="gallery_image_uploads[]"
                    accept="image/jpeg,image/png,image/webp,image/gif,image/avif"
                    multiple>
            </div>
        </div>
        <p class="product-media-manager__helper">Accepted formats: JPG, PNG, WebP, GIF, AVIF. Maximum 8 MB per image.</p>
    </div>
</div>
