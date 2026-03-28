<?php

declare(strict_types=1);

header('Content-Type: application/json');

try {
    $config = require dirname(__DIR__, 2) . '/bootstrap.php';
    $database = new \App\Support\Database($config['database']);
    $connection = $database->connection();

    if (!$connection) {
        respond([
            'ok' => false,
            'message' => service_unavailable_message(),
        ], 503);
    }

    $catalogRepository = new \App\Repositories\ProductRepository($connection);
    $catalog = new \App\Services\CatalogService($catalogRepository, 'mysql');
    $reviewService = new \App\Services\ReviewService(
        new \App\Repositories\ReviewRepository($connection),
        new \App\Repositories\OrderRepository($connection),
        new \App\Repositories\ProductRepository($connection),
    );

    $productId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
    $slug = trim((string) ($_GET['slug'] ?? ''));

    if ($productId < 1 && $slug === '') {
        respond([
            'ok' => false,
            'message' => 'Choose a product to view its details.',
        ], 422);
    }

    $product = $productId > 0
        ? $catalog->findProduct($productId)
        : null;

    if ($product === null && $slug !== '') {
        $product = $catalog->findProductBySlug($slug);
    }

    if ($product === null) {
        respond([
            'ok' => false,
            'message' => 'Product not found.',
        ], 404);
    }

    respond([
        'ok' => true,
        'title' => $product['name'] . ' | NovaMarket',
        'html' => render('partials/product-detail', [
            'product' => $product,
            'reviewContext' => $reviewService->forProduct(
                (int) $product['id'],
                !empty($_SESSION['user_id']) && ($_SESSION['user_role'] ?? '') === 'customer'
                    ? (int) $_SESSION['user_id']
                    : null
            ),
        ]),
    ]);
} catch (Throwable $exception) {
    report_exception($exception, 'api.product');
    respond([
        'ok' => false,
        'message' => 'Unable to load this product right now.',
    ], 500);
}

function respond(array $payload, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($payload);
    exit;
}
