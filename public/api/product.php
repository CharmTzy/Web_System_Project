<?php

declare(strict_types=1);

header('Content-Type: application/json');

try {
    $config = require dirname(__DIR__, 2) . '/bootstrap.php';
    $services = \App\Support\AppFactory::storefront($config);
    $catalog = $services['catalog'];

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
        'html' => render('partials/product-detail', ['product' => $product]),
    ]);
} catch (Throwable $exception) {
    respond([
        'ok' => false,
        'message' => $exception->getMessage() !== '' ? $exception->getMessage() : 'Unable to load this product right now.',
    ], 500);
}

function respond(array $payload, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($payload);
    exit;
}
