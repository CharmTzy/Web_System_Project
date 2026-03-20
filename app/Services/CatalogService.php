<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\CatalogRepositoryInterface;

final class CatalogService
{
    public function __construct(
        private readonly CatalogRepositoryInterface $repository,
        private readonly string $source
    ) {
    }

    public function browse(array $input): array
    {
        $filters = $this->sanitizeFilters($input);
        $products = $this->repository->listProducts($filters);

        return [
            'filters' => $filters,
            'products' => $products,
            'categories' => $this->repository->categories(),
            'activeFilterCount' => $this->activeFilterCount($filters),
            'source' => $this->source,
        ];
    }

    public function findProduct(int $id): ?array
    {
        return $this->repository->findById($id);
    }

    public function findProductBySlug(string $slug): ?array
    {
        $slug = preg_replace('/[^a-z0-9-]/i', '', trim($slug)) ?? '';

        if ($slug === '') {
            return null;
        }

        return $this->repository->findBySlug($slug);
    }

    private function sanitizeFilters(array $input): array
    {
        $allowedSorts = ['featured', 'newest', 'price_asc', 'price_desc', 'rating_desc'];
        $search = trim((string) ($input['search'] ?? ''));
        $search = mb_substr($search, 0, 80);

        $category = trim((string) ($input['category'] ?? ''));
        $category = preg_replace('/[^a-z0-9-]/i', '', $category) ?? '';

        $sort = (string) ($input['sort'] ?? 'featured');
        $sort = in_array($sort, $allowedSorts, true) ? $sort : 'featured';

        $minPrice = $this->sanitizePrice($input['min_price'] ?? null);
        $maxPrice = $this->sanitizePrice($input['max_price'] ?? null);

        if ($minPrice !== null && $maxPrice !== null && $minPrice > $maxPrice) {
            [$minPrice, $maxPrice] = [$maxPrice, $minPrice];
        }

        return [
            'search' => $search,
            'category' => $category,
            'sort' => $sort,
            'min_price' => $minPrice,
            'max_price' => $maxPrice,
            'in_stock' => bool_from_input($input['in_stock'] ?? false),
        ];
    }

    private function sanitizePrice(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        $filtered = filter_var($value, FILTER_VALIDATE_FLOAT);

        if ($filtered === false || $filtered < 0) {
            return null;
        }

        return round((float) $filtered, 2);
    }

    private function activeFilterCount(array $filters): int
    {
        $count = 0;

        foreach (['search', 'category', 'min_price', 'max_price'] as $key) {
            if ($filters[$key] !== '' && $filters[$key] !== null) {
                $count++;
            }
        }

        if ($filters['in_stock']) {
            $count++;
        }

        if ($filters['sort'] !== 'featured') {
            $count++;
        }

        return $count;
    }
}
