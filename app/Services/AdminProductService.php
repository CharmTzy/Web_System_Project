<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\ProductRepository;
use App\Repositories\UserRepository;
use InvalidArgumentException;
use RuntimeException;

final class AdminProductService
{
    public function __construct(
        private readonly ProductRepository $productRepository,
        private readonly UserRepository $userRepository,
        private readonly ?ProductMediaService $mediaService = null
    ) {
    }

    public function categories(): array
    {
        return $this->productRepository->categories();
    }

    public function sellerOptions(): array
    {
        $sellers = $this->userRepository->listAll(['role' => 'seller']);

        return array_map(function (array $seller): array {
            $profile = $this->userRepository->findSellerProfile((int) $seller['id']);

            return [
                'id' => (int) $seller['id'],
                'name' => (string) ($profile['store_name'] ?? $seller['name']),
            ];
        }, $sellers);
    }

    public function listProducts(): array
    {
        return $this->productRepository->listManagedProducts();
    }

    public function getProduct(int $id): ?array
    {
        return $this->productRepository->findManagedById($id);
    }

    public function create(array $input, array $files = []): array
    {
        $data = $this->validate($input);
        $connection = $this->productRepository->connection();
        $connection->beginTransaction();

        try {
            $id = $this->productRepository->createManagedProduct($data);

            if ($this->mediaService !== null) {
                $this->mediaService->syncProductImages($id, (string) $data['name'], $input, $files, $data);
            }

            $product = $this->productRepository->findManagedById($id);

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

    public function update(int $id, array $input, array $files = []): array
    {
        $existing = $this->productRepository->findManagedById($id);

        if ($existing === null) {
            throw new RuntimeException('Product not found.');
        }

        $data = $this->validate($input, $existing);
        $connection = $this->productRepository->connection();
        $connection->beginTransaction();

        try {
            $this->productRepository->updateManagedProduct($id, $data);

            if ($this->mediaService !== null) {
                $this->mediaService->syncProductImages($id, (string) ($data['name'] ?? $existing['name']), $input, $files, $existing);
            }

            $product = $this->productRepository->findManagedById($id) ?? $existing;
            $connection->commit();

            return $product;
        } catch (\Throwable $exception) {
            if ($connection->inTransaction()) {
                $connection->rollBack();
            }

            throw $exception;
        }
    }

    public function delete(int $id): void
    {
        $existing = $this->productRepository->findManagedById($id);

        if ($existing === null) {
            throw new RuntimeException('Product not found.');
        }

        if ($this->mediaService !== null) {
            $this->mediaService->purgeProductImages($id, $existing);
        }

        $this->productRepository->deleteManagedProduct($id);
    }

    private function validate(array $input, ?array $existing = null): array
    {
        $sellerId = filter_var($input['seller_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $name = trim((string) ($input['name'] ?? ''));
        $sku = trim((string) ($input['sku'] ?? ''));
        $slug = trim((string) ($input['slug'] ?? ''));
        $shortDescription = trim((string) ($input['short_description'] ?? ''));
        $description = trim((string) ($input['description'] ?? ''));
        $imageUrl = trim((string) ($input['image_url'] ?? ''));
        $categoryId = (int) ($input['category_id'] ?? 0);
        $price = is_numeric($input['price'] ?? null) ? (float) $input['price'] : null;
        $comparePrice = trim((string) ($input['compare_price'] ?? ''));
        $stockQuantity = filter_var($input['stock_quantity'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);

        if ($sellerId === false) {
            throw new InvalidArgumentException('Please select a seller.');
        }

        $seller = $this->userRepository->findById($sellerId);

        if ($seller === null || $seller['role'] !== 'seller') {
            throw new InvalidArgumentException('Selected seller is invalid.');
        }

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
            $comparePriceValue = $price;
        }

        return [
            'seller_id' => $sellerId,
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
