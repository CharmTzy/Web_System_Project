<?php

declare(strict_types=1);

namespace App\Support;

use App\Repositories\ProductRepository;
use App\Services\CartService;
use App\Services\CatalogService;
use RuntimeException;

final class AppFactory
{
    public static function storefront(array $config): array
    {
        $database = new Database($config['database']);
        $connection = $database->connection();

        if (!$connection) {
            throw new RuntimeException('Database connection required.');
        }

        $repository = new ProductRepository($connection);

        return [
            'catalog' => new CatalogService($repository, 'mysql'),
            'cart' => new CartService($repository, $config['app']),
        ];
    }
}
