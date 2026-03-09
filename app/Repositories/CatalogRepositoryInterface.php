<?php

declare(strict_types=1);

namespace App\Repositories;

interface CatalogRepositoryInterface
{
    public function categories(): array;

    public function listProducts(array $filters): array;

    public function findById(int $id): ?array;

    public function findByIds(array $ids): array;
}

