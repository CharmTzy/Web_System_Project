<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\ProductRepository;
use InvalidArgumentException;
use RuntimeException;

final class ProductMediaService
{
    public function __construct(
        private readonly ProductRepository $repository,
        private readonly array $appConfig
    ) {
    }

    public function syncProductImages(int $productId, string $productName, array $input, array $files, array $existingProduct): void
    {
        $bucket = google_cloud_storage_bucket($this->appConfig);
        $prefix = google_cloud_storage_product_prefix($this->appConfig);
        $currentMedia = $this->repository->listProductMedia(
            $productId,
            (string) ($existingProduct['image_url'] ?? ''),
            $productName
        );

        $removeMediaIds = array_values(array_filter(
            array_map('intval', (array) ($input['remove_media_ids'] ?? [])),
            static fn (int $id): bool => $id > 0
        ));
        $primaryMediaId = (int) ($input['primary_media_id'] ?? 0);
        $currentPrimaryUrl = (string) ($existingProduct['image_url'] ?? '');

        if ($this->hasAnyUpload($files) && $bucket === '') {
            throw new RuntimeException('Google Cloud Storage is not configured for product image uploads yet.');
        }

        $this->deleteSelectedMedia($productId, $removeMediaIds, $bucket);

        $uploadedPrimaryId = $this->handleSingleUpload(
            $productId,
            $productName,
            $files['primary_image_upload'] ?? null,
            $bucket,
            $prefix,
            true
        );
        $uploadedGalleryIds = $this->handleMultipleUploads(
            $productId,
            $productName,
            $files['gallery_image_uploads'] ?? null,
            $bucket,
            $prefix
        );

        $finalPrimaryId = $uploadedPrimaryId > 0
            ? $uploadedPrimaryId
            : ($primaryMediaId > 0 && !in_array($primaryMediaId, $removeMediaIds, true) ? $primaryMediaId : 0);

        if ($finalPrimaryId > 0) {
            $this->repository->setPrimaryProductMedia($productId, $finalPrimaryId);
        } else {
            $firstImage = $this->repository->firstProductMedia($productId, 'image');

            if (is_array($firstImage) && isset($firstImage['id'])) {
                $this->repository->setPrimaryProductMedia($productId, (int) $firstImage['id']);
            }
        }

        $fallbackUrl = $currentPrimaryUrl;

        if (
            $removeMediaIds !== []
            && count(array_filter(
                $currentMedia,
                static fn (array $media): bool => ($media['type'] ?? 'image') === 'image' && !in_array((int) ($media['id'] ?? 0), $removeMediaIds, true)
            )) === 0
            && $uploadedPrimaryId < 1
            && $uploadedGalleryIds === []
        ) {
            $fallbackUrl = null;
        }

        $this->repository->syncProductImageUrlFromMedia($productId, $fallbackUrl);
    }

    public function purgeProductImages(int $productId, array $existingProduct): void
    {
        $bucket = google_cloud_storage_bucket($this->appConfig);
        $media = $this->repository->listProductMedia(
            $productId,
            (string) ($existingProduct['image_url'] ?? ''),
            (string) ($existingProduct['name'] ?? 'Product image')
        );
        $deletableIds = [];

        foreach ($media as $item) {
            $mediaId = (int) ($item['id'] ?? 0);

            if ($mediaId < 1) {
                continue;
            }

            $deletableIds[] = $mediaId;

            if ($bucket === '') {
                continue;
            }

            foreach ([(string) ($item['url'] ?? ''), (string) ($item['thumbnail_url'] ?? '')] as $url) {
                $this->deleteObjectIfManaged($bucket, $url);
            }
        }

        if ($deletableIds !== []) {
            $this->repository->deleteProductMediaByIds($productId, $deletableIds);
        }
    }

    private function hasAnyUpload(array $files): bool
    {
        $singleUpload = $files['primary_image_upload']['error'] ?? UPLOAD_ERR_NO_FILE;

        if ($singleUpload !== UPLOAD_ERR_NO_FILE) {
            return true;
        }

        $galleryErrors = $files['gallery_image_uploads']['error'] ?? null;

        if (!is_array($galleryErrors)) {
            return false;
        }

        foreach ($galleryErrors as $error) {
            if ((int) $error !== UPLOAD_ERR_NO_FILE) {
                return true;
            }
        }

        return false;
    }

    private function deleteSelectedMedia(int $productId, array $removeMediaIds, string $bucket): void
    {
        if ($removeMediaIds === []) {
            return;
        }

        $mediaRows = $this->repository->findProductMediaByIds($productId, $removeMediaIds);

        foreach ($mediaRows as $media) {
            foreach ([(string) ($media['media_url'] ?? ''), (string) ($media['thumbnail_url'] ?? '')] as $url) {
                if ($bucket === '') {
                    continue;
                }

                $this->deleteObjectIfManaged($bucket, $url);
            }
        }

        $this->repository->deleteProductMediaByIds($productId, $removeMediaIds);
    }

    private function handleSingleUpload(
        int $productId,
        string $productName,
        mixed $file,
        string $bucket,
        string $prefix,
        bool $isPrimary
    ): int {
        if (!is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return 0;
        }

        $upload = $this->validateUpload($file);
        $publicUrl = $this->uploadImage($productId, $upload, $bucket, $prefix, $isPrimary ? 'primary' : 'gallery');

        return $this->repository->createProductMedia($productId, [
            'media_type' => 'image',
            'media_url' => $publicUrl,
            'thumbnail_url' => $publicUrl,
            'alt_text' => $productName,
            'sort_order' => $this->repository->nextProductMediaSortOrder($productId),
            'is_primary' => $isPrimary,
        ]);
    }

    private function handleMultipleUploads(
        int $productId,
        string $productName,
        mixed $files,
        string $bucket,
        string $prefix
    ): array {
        if (
            !is_array($files)
            || !isset($files['name'], $files['tmp_name'], $files['error'], $files['size'])
            || !is_array($files['name'])
        ) {
            return [];
        }

        $createdIds = [];
        $fileCount = count($files['name']);

        for ($index = 0; $index < $fileCount; $index++) {
            $file = [
                'name' => $files['name'][$index] ?? '',
                'tmp_name' => $files['tmp_name'][$index] ?? '',
                'error' => $files['error'][$index] ?? UPLOAD_ERR_NO_FILE,
                'size' => $files['size'][$index] ?? 0,
            ];

            if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                continue;
            }

            $upload = $this->validateUpload($file);
            $publicUrl = $this->uploadImage($productId, $upload, $bucket, $prefix, 'gallery');

            $createdIds[] = $this->repository->createProductMedia($productId, [
                'media_type' => 'image',
                'media_url' => $publicUrl,
                'thumbnail_url' => $publicUrl,
                'alt_text' => $productName,
                'sort_order' => $this->repository->nextProductMediaSortOrder($productId),
                'is_primary' => false,
            ]);
        }

        return $createdIds;
    }

    private function validateUpload(array $file): array
    {
        if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            throw new InvalidArgumentException('One of the selected product images could not be uploaded.');
        }

        $size = (int) ($file['size'] ?? 0);

        if ($size < 1) {
            throw new InvalidArgumentException('One of the selected product images is empty.');
        }

        if ($size > 8 * 1024 * 1024) {
            throw new InvalidArgumentException('Product images must be 8 MB or smaller.');
        }

        $tmpPath = (string) ($file['tmp_name'] ?? '');

        if ($tmpPath === '' || !is_uploaded_file($tmpPath)) {
            throw new InvalidArgumentException('One of the selected product images could not be validated.');
        }

        $extension = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp', 'gif', 'avif'];

        if (!in_array($extension, $allowedExtensions, true)) {
            throw new InvalidArgumentException('Product images must be JPG, PNG, WebP, GIF, or AVIF.');
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $contentType = (string) ($finfo->file($tmpPath) ?: 'application/octet-stream');
        $allowedMimeTypes = [
            'image/jpeg',
            'image/png',
            'image/webp',
            'image/gif',
            'image/avif',
        ];

        if (!in_array($contentType, $allowedMimeTypes, true)) {
            throw new InvalidArgumentException('One of the selected product images has an unsupported file type.');
        }

        return [
            'tmp_path' => $tmpPath,
            'extension' => $extension,
            'content_type' => $contentType,
        ];
    }

    private function uploadImage(int $productId, array $upload, string $bucket, string $prefix, string $kind): string
    {
        if ($bucket === '') {
            throw new RuntimeException('Google Cloud Storage is not configured for product image uploads yet.');
        }

        $filename = sprintf(
            '%s_%s.%s',
            $kind,
            bin2hex(random_bytes(12)),
            $upload['extension']
        );
        $objectPath = sprintf(
            '%s/product-%d/%s',
            $prefix,
            $productId,
            $filename
        );

        return google_cloud_storage_upload_object(
            $bucket,
            $objectPath,
            $upload['tmp_path'],
            $upload['content_type']
        );
    }

    private function deleteObjectIfManaged(string $bucket, string $publicUrl): void
    {
        $objectPath = google_cloud_storage_public_url_path($bucket, $publicUrl);

        if ($objectPath === null) {
            return;
        }

        try {
            google_cloud_storage_delete_object($bucket, $objectPath);
        } catch (RuntimeException $exception) {
            report_exception($exception, 'gcs.delete.product_media');
        }
    }
}
