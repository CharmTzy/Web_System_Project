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
            $message = 'Thanks for sharing your review.';

            $existingReview = $reviewRepository->findByProductAndUser($productId, $userId);

            if ($existingReview !== null) {
                $mediaMessage = handleReviewMediaUpload(
                    $config['app'],
                    $reviewRepository,
                    (int) $existingReview['id'],
                    $productId
                );
                $result = $service->forProduct($productId, $userId);

                if ($mediaMessage !== null) {
                    $message = 'Review saved. ' . $mediaMessage;
                }
            }

            respond([
                'ok' => true,
                'message' => $message,
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

function handleReviewMediaUpload(
    array $appConfig,
    \App\Repositories\ReviewRepository $reviewRepository,
    int $reviewId,
    int $productId
): ?string
{
    $uploadMap = [
        'photo' => [
            'field' => 'photo',
            'extensions' => ['jpg', 'jpeg', 'png', 'webp'],
            'max_size' => 3 * 1024 * 1024,
            'mime_prefix' => 'image/',
        ],
        'video' => [
            'field' => 'video',
            'extensions' => ['mp4', 'webm'],
            'max_size' => 15 * 1024 * 1024,
            'mime_prefix' => 'video/',
        ],
    ];
    $warnings = [];
    $bucket = google_cloud_storage_bucket($appConfig);
    $prefix = google_cloud_storage_review_prefix($appConfig);

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
            $warnings[] = ucfirst($mediaType) . ' upload could not be processed.';
            continue;
        }

        if (($file['size'] ?? 0) > $config['max_size']) {
            $warnings[] = ucfirst($mediaType) . ' is too large.';
            continue;
        }

        $originalName = (string) ($file['name'] ?? '');
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

        if (!in_array($extension, $config['extensions'], true)) {
            $warnings[] = ucfirst($mediaType) . ' has an unsupported format.';
            continue;
        }

        if ($bucket === '') {
            $warnings[] = ucfirst($mediaType) . ' upload is not configured yet.';
            continue;
        }

        $tmpPath = (string) ($file['tmp_name'] ?? '');

        if ($tmpPath === '' || !is_uploaded_file($tmpPath)) {
            $warnings[] = ucfirst($mediaType) . ' upload could not be validated.';
            continue;
        }

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $contentType = (string) ($finfo->file($tmpPath) ?: 'application/octet-stream');

        if (!str_starts_with($contentType, $config['mime_prefix'])) {
            $warnings[] = ucfirst($mediaType) . ' file type could not be verified.';
            continue;
        }

        $filename = sprintf(
            '%s_%s.%s',
            $mediaType,
            bin2hex(random_bytes(12)),
            $extension
        );

        $objectPath = sprintf(
            '%s/product-%d/review-%d/%s',
            $prefix,
            $productId,
            $reviewId,
            $filename
        );

        try {
            $publicUrl = google_cloud_storage_upload_object(
                $bucket,
                $objectPath,
                $tmpPath,
                $contentType
            );
        } catch (\RuntimeException $exception) {
            $warnings[] = ucfirst($mediaType) . ' upload failed.';
            continue;
        }

        $reviewRepository->deleteMediaByType($reviewId, $mediaType);
        $reviewRepository->addMedia($reviewId, $mediaType, $publicUrl);
    }

    return $warnings === [] ? null : implode(' ', $warnings);
}

function respond(array $payload, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($payload);
    exit;
}
