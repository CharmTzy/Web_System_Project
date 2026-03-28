<?php

declare(strict_types=1);

header('Content-Type: application/json');

try {
    $config = require dirname(__DIR__, 2) . '/bootstrap.php';

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

    if (empty($_SESSION['user_id']) || empty($_SESSION['user_role'])) {
        respond([
            'ok' => false,
            'message' => 'Please sign in first.',
            'login_url' => '/login.php?redirect=' . rawurlencode((string) ($_SERVER['HTTP_REFERER'] ?? '/product.html')),
        ], 403);
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
    $reviewRepository = new \App\Repositories\ReviewRepository($connection);
    $orderRepository = new \App\Repositories\OrderRepository($connection);

    $service = new \App\Services\ReviewService(
        $reviewRepository,
        $orderRepository,
        $productRepository,
    );

    $userId = (int) $_SESSION['user_id'];
    $userRole = (string) $_SESSION['user_role'];
    $action = (string) ($_POST['action'] ?? 'save');

    switch ($action) {
        case 'save':
            if ($userRole !== 'customer') {
                respond([
                    'ok' => false,
                    'message' => 'Sign in with a customer account to review products.',
                ], 403);
            }

            $productId = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT) ?: 0;
            $product = $productRepository->findById($productId);

            if ($product === null) {
                respond([
                    'ok' => false,
                    'message' => 'Product not found.',
                ], 404);
            }

            $result = $service->saveForUser($productId, $userId, $_POST);

            $existingReview = $reviewRepository->findByProductAndUser($productId, $userId);

            if ($existingReview !== null) {
                handleReviewMediaUpload($reviewRepository, (int) $existingReview['id']);
                $result = $service->forProduct($productId, $userId);
            }

            respond([
                'ok' => true,
                'message' => 'Thanks for sharing your review.',
                'data' => $result,
            ]);
            break;

        case 'delete':
            if ($userRole !== 'customer') {
                respond([
                    'ok' => false,
                    'message' => 'Only customers can delete their own reviews.',
                ], 403);
            }

            $productId = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT) ?: 0;
            $product = $productRepository->findById($productId);

            if ($product === null) {
                respond([
                    'ok' => false,
                    'message' => 'Product not found.',
                ], 404);
            }

            $result = $service->deleteForUser($productId, $userId);

            respond([
                'ok' => true,
                'message' => 'Your review was removed.',
                'data' => $result,
            ]);
            break;

        case 'reply':
            if ($userRole !== 'seller') {
                respond([
                    'ok' => false,
                    'message' => 'Only sellers can reply to reviews.',
                ], 403);
            }

            $reviewId = filter_input(INPUT_POST, 'review_id', FILTER_VALIDATE_INT) ?: 0;

            if ($reviewId <= 0) {
                respond([
                    'ok' => false,
                    'message' => 'Invalid review.',
                ], 422);
            }

            $result = $service->replyAsSeller(
                $reviewId,
                $userId,
                (string) ($_POST['seller_reply'] ?? '')
            );

            respond([
                'ok' => true,
                'message' => 'Reply added successfully.',
                'data' => $result,
            ]);
            break;

        case 'flag':
            if ($userRole !== 'admin') {
                respond([
                    'ok' => false,
                    'message' => 'Only admins can flag reviews.',
                ], 403);
            }

            $reviewId = filter_input(INPUT_POST, 'review_id', FILTER_VALIDATE_INT) ?: 0;

            if ($reviewId <= 0) {
                respond([
                    'ok' => false,
                    'message' => 'Invalid review.',
                ], 422);
            }

            $result = $service->flagAsAdmin(
                $reviewId,
                $userId,
                (string) ($_POST['reason'] ?? '')
            );

            respond([
                'ok' => true,
                'message' => 'Review flagged successfully.',
                'data' => $result,
            ]);
            break;

        case 'hide':
            if ($userRole !== 'admin') {
                respond([
                    'ok' => false,
                    'message' => 'Only admins can hide reviews.',
                ], 403);
            }

            $reviewId = filter_input(INPUT_POST, 'review_id', FILTER_VALIDATE_INT) ?: 0;

            if ($reviewId <= 0) {
                respond([
                    'ok' => false,
                    'message' => 'Invalid review.',
                ], 422);
            }

            $result = $service->hideAsAdmin(
                $reviewId,
                $userId,
                isset($_POST['reason']) ? (string) $_POST['reason'] : null
            );

            respond([
                'ok' => true,
                'message' => 'Review hidden successfully.',
                'data' => $result,
            ]);
            break;

        case 'admin-delete':
            if ($userRole !== 'admin') {
                respond([
                    'ok' => false,
                    'message' => 'Only admins can delete reviews.',
                ], 403);
            }

            $reviewId = filter_input(INPUT_POST, 'review_id', FILTER_VALIDATE_INT) ?: 0;

            if ($reviewId <= 0) {
                respond([
                    'ok' => false,
                    'message' => 'Invalid review.',
                ], 422);
            }

            $result = $service->deleteAsAdmin($reviewId);

            respond([
                'ok' => true,
                'message' => 'Review deleted successfully.',
                'data' => $result,
            ]);
            break;

        default:
            respond([
                'ok' => false,
                'message' => 'Unknown action.',
            ], 400);
    }
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

function handleReviewMediaUpload(\App\Repositories\ReviewRepository $reviewRepository, int $reviewId): void
{
    $uploadMap = [
        'photo' => [
            'field' => 'photo',
            'extensions' => ['jpg', 'jpeg', 'png', 'webp'],
            'max_size' => 3 * 1024 * 1024,
            'directory' => dirname(__DIR__) . '/uploads/reviews/photos',
        ],
        'video' => [
            'field' => 'video',
            'extensions' => ['mp4', 'webm'],
            'max_size' => 15 * 1024 * 1024,
            'directory' => dirname(__DIR__) . '/uploads/reviews/videos',
        ],
    ];

    foreach ($uploadMap as $mediaType => $config) {
        $field = $config['field'];

        if (!isset($_FILES[$field]) || !is_array($_FILES[$field])) {
            continue;
        }

        $file = $_FILES[$field];

        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            continue;
        }

        if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            continue;
        }

        if (($file['size'] ?? 0) > $config['max_size']) {
            continue;
        }

        $originalName = (string) ($file['name'] ?? '');
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        if (!in_array($extension, $config['extensions'], true)) {
            continue;
        }

        if (!is_dir($config['directory'])) {
            mkdir($config['directory'], 0775, true);
        }

        $filename = sprintf(
            '%s_%s.%s',
            $mediaType,
            bin2hex(random_bytes(12)),
            $extension
        );

        $destination = $config['directory'] . DIRECTORY_SEPARATOR . $filename;

        if (!move_uploaded_file((string) $file['tmp_name'], $destination)) {
            continue;
        }

        $publicPath = str_replace(dirname(__DIR__), '', $destination);
        $publicPath = str_replace('\\', '/', $publicPath);

        $reviewRepository->addMedia($reviewId, $mediaType, $publicPath);
    }
}

function respond(array $payload, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($payload);
    exit;
}