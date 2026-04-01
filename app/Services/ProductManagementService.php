<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\ProductRepository;
use InvalidArgumentException;
use RuntimeException;

final class ProductManagementService
{
    public function __construct(
        private readonly ProductRepository $repository,
        private readonly ?ProductMediaService $mediaService = null
    )
    {
    }

    public function categories(): array
    {
        return $this->repository->categories();
    }

    public function listSellerProducts(int $sellerId): array
    {
        return $this->repository->listManagedProducts($sellerId);
    }

    public function getSellerProduct(int $productId, int $sellerId): ?array
    {
        $product = $this->repository->findManagedById($productId);

        if ($product === null || (int) $product['seller_id'] !== $sellerId) {
            return null;
        }

        return $product;
    }

    public function createForSeller(int $sellerId, array $input, array $files = []): array
    {
        $data = $this->validate($input);
        $data['seller_id'] = $sellerId;
        $connection = $this->repository->connection();
        $connection->beginTransaction();

        try {
            $productId = $this->repository->createManagedProduct($data);

            if ($this->mediaService !== null) {
                $this->mediaService->syncProductImages($productId, (string) $data['name'], $input, $files, $data);
            }

            $product = $this->repository->findManagedById($productId);

            if ($product === null) {
                throw new RuntimeException('Product could not be created.');
            }

            $connection->commit();

            return $product;
        } catch (\Throwable $exception) {
            if ($connection->inTransaction()) {
                $connection->rollBack();
            }

            throw $exception;
        }
    }

    public function updateForSeller(int $productId, int $sellerId, array $input, array $files = []): array
    {
        $existing = $this->getSellerProduct($productId, $sellerId);

        if ($existing === null) {
            throw new RuntimeException('Product not found.');
        }

        $data = $this->validate($input, $existing);
        $connection = $this->repository->connection();
        $connection->beginTransaction();

        try {
            $this->repository->updateManagedProduct($productId, $data);

            if ($this->mediaService !== null) {
                $this->mediaService->syncProductImages($productId, (string) ($data['name'] ?? $existing['name']), $input, $files, $existing);
            }

            $updated = $this->repository->findManagedById($productId) ?? $existing;
            $connection->commit();

            return $updated;
        } catch (\Throwable $exception) {
            if ($connection->inTransaction()) {
                $connection->rollBack();
            }

            throw $exception;
        }
    }

    public function deleteForSeller(int $productId, int $sellerId): void
    {
        $existing = $this->getSellerProduct($productId, $sellerId);

        if ($existing === null) {
            throw new RuntimeException('Product not found.');
        }

        if ($this->mediaService !== null) {
            $this->mediaService->purgeProductImages($productId, $existing);
        }

        $this->repository->deleteManagedProduct($productId);
    }

    private function validate(array $input, ?array $existing = null): array
    {
        $name = sanitize_single_line($input['name'] ?? '', 150);
        $sku = sanitize_single_line($input['sku'] ?? '', 50);
        $slug = sanitize_single_line($input['slug'] ?? '', 150);
        $shortDescription = sanitize_single_line($input['short_description'] ?? '', 255);
        $description = sanitize_multiline_text($input['description'] ?? '');
        $imageUrl = trim((string) ($input['image_url'] ?? ''));
        $categoryId = (int) ($input['category_id'] ?? 0);
        $price = is_numeric($input['price'] ?? null) ? (float) $input['price'] : null;
        $comparePrice = trim((string) ($input['compare_price'] ?? ''));
        $stockQuantity = filter_var($input['stock_quantity'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);

        if ($name === '') {
            throw new InvalidArgumentException('Product name is required.');
        }

        if ($categoryId < 1) {
            throw new InvalidArgumentException('Please select a category.');
        }

        if ($sku === '') {
            throw new InvalidArgumentException('SKU is required.');
        }

        if ($slug === '') {
            $slug = $this->slugify($name);
        }

        if ($shortDescription === '') {
            throw new InvalidArgumentException('Short description is required.');
        }

        if ($description === '') {
            throw new InvalidArgumentException('Description is required.');
        }

        if ($price === null || $price <= 0) {
            throw new InvalidArgumentException('Price must be greater than 0.');
        }

        if ($stockQuantity === false) {
            throw new InvalidArgumentException('Stock quantity must be 0 or greater.');
        }

        $comparePriceValue = $comparePrice === '' ? null : (float) $comparePrice;
        if ($comparePriceValue !== null && $comparePriceValue < $price) {
            $comparePriceValue = max($comparePriceValue, $price);
        }

        return [
            'category_id' => $categoryId,
            'sku' => $sku,
            'name' => $name,
            'slug' => $slug,
            'short_description' => $shortDescription,
            'description' => $description,
            'price' => $price,
            'compare_price' => $comparePriceValue,
            'stock_quantity' => (int) $stockQuantity,
            'image_url' => $imageUrl !== '' ? $imageUrl : ($existing['image_url'] ?? null),
            'is_active' => bool_from_input($input['is_active'] ?? true) ? 1 : 0,
            'is_featured' => bool_from_input($input['is_featured'] ?? false) ? 1 : 0,
        ];
    }

    private function slugify(string $text): string
    {
        $slug = mb_strtolower($text);
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug) ?? '';

        return trim($slug, '-');
    }
}
