<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Support\SampleCatalog;

final class SampleProductRepository implements CatalogRepositoryInterface
{
    private array $categories;

    private array $products;

    public function __construct()
    {
        $this->categories = SampleCatalog::categories();
        $this->products = SampleCatalog::products();
    }

    public function categories(): array
    {
        return array_map(function (array $category): array {
            $count = count(array_filter(
                $this->products,
                static fn (array $product): bool => $product['category_slug'] === $category['slug'] && $product['is_active']
            ));

            $category['product_count'] = $count;

            return $category;
        }, $this->categories);
    }

    public function listProducts(array $filters): array
    {
        $products = array_values(array_filter(
            $this->products,
            fn (array $product): bool => $this->matchesFilters($product, $filters)
        ));

        usort($products, fn (array $left, array $right): int => $this->compareProducts($left, $right, $filters['sort']));

        return $products;
    }

    public function findById(int $id): ?array
    {
        foreach ($this->products as $product) {
            if ($product['id'] === $id && $product['is_active']) {
                return $product;
            }
        }

        return null;
    }

    public function findBySlug(string $slug): ?array
    {
        foreach ($this->products as $product) {
            if ($product['slug'] === $slug && $product['is_active']) {
                return $product;
            }
        }

        return null;
    }

    public function findByIds(array $ids): array
    {
        $lookup = array_flip(array_map('intval', $ids));
        $products = [];

        foreach ($this->products as $product) {
            if (!$product['is_active'] || !isset($lookup[$product['id']])) {
                continue;
            }

            $products[$product['id']] = $product;
        }

        return $products;
    }

    private function matchesFilters(array $product, array $filters): bool
    {
        if (!$product['is_active']) {
            return false;
        }

        if ($filters['category'] !== '' && $product['category_slug'] !== $filters['category']) {
            return false;
        }

        if ($filters['in_stock'] && $product['stock_quantity'] < 1) {
            return false;
        }

        if ($filters['min_price'] !== null && $product['price'] < $filters['min_price']) {
            return false;
        }

        if ($filters['max_price'] !== null && $product['price'] > $filters['max_price']) {
            return false;
        }

        if ($filters['search'] === '') {
            return true;
        }

        $needle = mb_strtolower($filters['search']);
        $haystack = mb_strtolower(implode(' ', [
            $product['name'],
            $product['short_description'],
            $product['category_name'],
            $product['seller_name'],
        ]));

        return str_contains($haystack, $needle);
    }

    private function compareProducts(array $left, array $right, string $sort): int
    {
        return match ($sort) {
            'price_asc' => $left['price'] <=> $right['price'],
            'price_desc' => $right['price'] <=> $left['price'],
            'rating_desc' => [$right['rating'], $right['review_count']] <=> [$left['rating'], $left['review_count']],
            'newest' => strcmp($right['created_at'], $left['created_at']),
            default => [$right['is_featured'], $right['created_at'], $left['name']] <=> [$left['is_featured'], $left['created_at'], $right['name']],
        };
    }
}
