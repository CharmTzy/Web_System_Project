<?php

declare(strict_types=1);

header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

$config = require dirname(__DIR__, 2) . '/bootstrap.php';

$database = new \App\Support\Database($config['database']);
$connection = $database->connection();

if (!$connection) {
    respond(['ok' => false, 'message' => service_unavailable_message()], 503);
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
$email = mb_strtolower(trim((string) ($_POST['email'] ?? '')));
$loginLimit = 5;
$loginWindowSeconds = 900;
$loginRateLimitKey = 'auth-login|' . client_ip() . '|' . hash('sha256', $email !== '' ? $email : 'anonymous');

if ($action === 'login') {
    $rateLimit = rate_limit_status($loginRateLimitKey, $loginLimit, $loginWindowSeconds);

    if ($rateLimit['is_limited']) {
        header('Retry-After: ' . (string) $rateLimit['retry_after']);
        respond([
            'ok' => false,
            'message' => 'Too many sign-in attempts. Please wait a few minutes and try again.',
        ], 429);
    }
}

try {
    $result = match ($action) {
        'login' => $authService->login(
            $email,
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
        default => '/profile.php',
    };

    if ($requestedRedirect !== '' && str_starts_with($requestedRedirect, '/') && !str_starts_with($requestedRedirect, '//')) {
        $redirect = $requestedRedirect;
    }

    if ($action === 'login') {
        clear_rate_limit($loginRateLimitKey);
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
} catch (\InvalidArgumentException $exception) {
    report_exception($exception, 'auth.validation');
    respond(['ok' => false, 'message' => $exception->getMessage()], 422);
} catch (\RuntimeException $exception) {
    report_exception($exception, 'auth.runtime');

    if ($action === 'login') {
        rate_limit_record_failure($loginRateLimitKey, $loginWindowSeconds);
        respond(['ok' => false, 'message' => 'Invalid email or password.'], 422);
    }

    respond(['ok' => false, 'message' => $exception->getMessage()], 422);
} catch (\Throwable $exception) {
    report_exception($exception, 'auth.unexpected');
    respond(['ok' => false, 'message' => service_unavailable_message()], 500);
}

function respond(array $payload, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($payload);
    exit;
}
