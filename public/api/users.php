<?php

declare(strict_types=1);

header('Content-Type: application/json');

$config = require dirname(__DIR__, 2) . '/bootstrap.php';

if (empty($_SESSION['user_id'])) {
    respond(['ok' => false, 'message' => 'Authentication required.'], 401);
}

$database = new \App\Support\Database($config['database']);
$connection = $database->connection();

if (!$connection) {
    respond(['ok' => false, 'message' => 'Database connection required.'], 503);
}

$userRepository = new \App\Repositories\UserRepository($connection);
$userService = new \App\Services\UserService($userRepository);

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = (string) ($_GET['action'] ?? 'profile');

    if ($action === 'profile') {
        $user = $userService->getProfile((int) $_SESSION['user_id']);
        respond(['ok' => true, 'user' => $user]);
    }

    respond(['ok' => false, 'message' => 'Unknown action.'], 400);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(['ok' => false, 'message' => 'Method not allowed.'], 405);
}

if (!verify_csrf($_POST['csrf_token'] ?? null)) {
    respond(['ok' => false, 'message' => 'Session expired. Please refresh and try again.'], 419);
}

$action = (string) ($_POST['action'] ?? 'update-profile');

try {
    $result = match ($action) {
        'update-profile' => $userService->updateProfile((int) $_SESSION['user_id'], $_POST),
        'admin-create' => adminOnly() ?? $userService->adminCreateUser($_POST),
        'admin-update' => adminOnly() ?? $userService->adminUpdateUser(
            (int) ($_POST['user_id'] ?? 0),
            $_POST
        ),
        'toggle-active' => adminOnly() ?? $userService->toggleActive((int) ($_POST['user_id'] ?? 0)),
        'update-store' => sellerOnly() ?? $userService->updateSellerStore((int) $_SESSION['user_id'], $_POST),
        default => throw new \InvalidArgumentException('Unknown action.'),
    };

    respond([
        'ok' => true,
        'message' => 'Changes saved successfully.',
        'user' => $result,
    ]);
} catch (\Throwable $exception) {
    respond(['ok' => false, 'message' => $exception->getMessage()], 422);
}

function adminOnly(): ?array
{
    if (($_SESSION['user_role'] ?? '') !== 'admin') {
        respond(['ok' => false, 'message' => 'Admin access required.'], 403);
    }
    return null;
}

function sellerOnly(): ?array
{
    if (($_SESSION['user_role'] ?? '') !== 'seller') {
        respond(['ok' => false, 'message' => 'Seller access required.'], 403);
    }
    return null;
}

function respond(array $payload, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($payload);
    exit;
}
