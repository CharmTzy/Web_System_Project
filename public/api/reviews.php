<?php

declare(strict_types=1);

header('Content-Type: application/json');

try {
    $config = require dirname(__DIR__, 2) . '/bootstrap.php';

    if (empty($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'customer') {
        respond([
            'ok' => false,
            'message' => 'Sign in with a customer account to review products.',
            'login_url' => '/login.php?redirect=' . rawurlencode((string) ($_SERVER['HTTP_REFERER'] ?? '/product.html')),
        ], 403);
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        respond([
            'ok' => false,
            'message' => 'Method not allowed.',
        ], 405);
    }

    if (!verify_csrf($_POST['csrf_token'] ?? null)) {
        respond([
            'ok' => false,
            'message' => 'Session expired. Please refresh and try again.',
        ], 419);
    }

    $database = new \App\Support\Database($config['database']);
    $connection = $database->connection();

    if (!$connection) {
        respond([
            'ok' => false,
            'message' => service_unavailable_message(),
        ], 503);
    }

    $productRepository = new \App\Repositories\ProductRepository($connection);
    $productId = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT) ?: 0;
    $product = $productRepository->findById($productId);

    if ($product === null) {
        respond([
            'ok' => false,
            'message' => 'Product not found.',
        ], 404);
    }

    $service = new \App\Services\ReviewService(
        new \App\Repositories\ReviewRepository($connection),
        new \App\Repositories\OrderRepository($connection),
    );

    $action = (string) ($_POST['action'] ?? 'save');

    if ($action === 'delete') {
        $service->deleteForUser($productId, (int) $_SESSION['user_id']);
        respond([
            'ok' => true,
            'message' => 'Your review was removed.',
        ]);
    }

    $service->saveForUser($productId, (int) $_SESSION['user_id'], $_POST);

    respond([
        'ok' => true,
        'message' => 'Thanks for sharing your review.',
    ]);
} catch (\InvalidArgumentException | \RuntimeException $exception) {
    report_exception($exception, 'api.reviews.expected');
    respond([
        'ok' => false,
        'message' => $exception->getMessage(),
    ], 422);
} catch (Throwable $exception) {
    report_exception($exception, 'api.reviews.unexpected');
    respond([
        'ok' => false,
        'message' => service_unavailable_message(),
    ], 500);
}

function respond(array $payload, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($payload);
    exit;
}
