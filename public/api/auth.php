<?php

declare(strict_types=1);

header('Content-Type: application/json');

$config = require dirname(__DIR__, 2) . '/bootstrap.php';

$database = new \App\Support\Database($config['database']);
$connection = $database->connection();

if (!$connection) {
    respond(['ok' => false, 'message' => 'Database connection required.'], 503);
}

$userRepository = new \App\Repositories\UserRepository($connection);
$authService = new \App\Services\AuthService($userRepository);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(['ok' => false, 'message' => 'Method not allowed.'], 405);
}

if (!verify_csrf($_POST['csrf_token'] ?? null)) {
    respond(['ok' => false, 'message' => 'Session expired. Please refresh and try again.'], 419);
}

$action = (string) ($_POST['action'] ?? '');
$requestedRedirect = trim((string) ($_POST['redirect'] ?? ''));

try {
    $result = match ($action) {
        'login' => $authService->login(
            trim((string) ($_POST['email'] ?? '')),
            (string) ($_POST['password'] ?? '')
        ),
        'register' => $authService->register($_POST),
        default => throw new \InvalidArgumentException('Unknown action.'),
    };

    // Seller registration: pending approval, don't redirect to dashboard
    if (!empty($result['pending_approval'])) {
        respond([
            'ok' => true,
            'pending_approval' => true,
            'message' => 'Your seller account has been created and is pending admin approval. You will be able to sign in once approved.',
            'redirect' => '/login.php',
            'user' => [
                'id' => $result['id'],
                'name' => $result['name'],
                'role' => $result['role'],
            ],
        ]);
    }

    $redirect = match ($result['role']) {
        'admin' => '/admin/',
        'seller' => '/seller/',
        default => '/',
    };

    if ($requestedRedirect !== '' && str_starts_with($requestedRedirect, '/') && !str_starts_with($requestedRedirect, '//')) {
        $redirect = $requestedRedirect;
    }

    respond([
        'ok' => true,
        'message' => $action === 'register' ? 'Account created successfully.' : 'Signed in successfully.',
        'redirect' => $redirect,
        'user' => [
            'id' => $result['id'],
            'name' => $result['name'],
            'role' => $result['role'],
        ],
    ]);
} catch (\Throwable $exception) {
    respond(['ok' => false, 'message' => $exception->getMessage()], 422);
}

function respond(array $payload, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($payload);
    exit;
}
