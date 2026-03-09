<?php

declare(strict_types=1);

namespace App\Support;

use App\Repositories\ProductRepository;
use App\Repositories\SampleProductRepository;
use App\Services\CartService;
use App\Services\CatalogService;

final class AppFactory
{
    public static function storefront(array $config): array
    {
        $database = new Database($config['database']);
        $connection = $database->connection();
        $repository = $connection ? new ProductRepository($connection) : new SampleProductRepository();

        return [
            'catalog' => new CatalogService($repository, $connection ? 'mysql' : 'sample'),
            'cart' => new CartService($repository, $config['app']),
        ];
    }
}

