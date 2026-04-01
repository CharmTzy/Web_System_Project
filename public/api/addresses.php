<?php

declare(strict_types=1);

header('Content-Type: application/json');

$config = require dirname(__DIR__, 2) . '/bootstrap.php';

if (empty($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'customer') {
    respond(['ok' => false, 'message' => 'Customer authentication required.'], 401);
}

$database = new \App\Support\Database($config['database']);
$connection = $database->connection();

if (!$connection) {
    respond(['ok' => false, 'message' => service_unavailable_message()], 503);
}

$addressService = new \App\Services\AddressService(
    new \App\Repositories\AddressRepository($connection)
);

$userId = (int) $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $addresses = $addressService->listForUser($userId);
    respond([
        'ok' => true,
        'addresses' => $addresses,
        'html' => render('customer/address-list', ['addresses' => $addresses]),
    ]);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(['ok' => false, 'message' => 'Method not allowed.'], 405);
}

if (!verify_csrf($_POST['csrf_token'] ?? null)) {
    respond(['ok' => false, 'message' => 'Session expired. Please refresh and try again.'], 419);
}

$action = (string) ($_POST['action'] ?? '');
$addressId = (int) ($_POST['address_id'] ?? 0);

try {
    $result = match ($action) {
        'create' => $addressService->create($userId, $_POST),
        'update' => $addressService->update($addressId, $userId, $_POST),
        'delete' => (function () use ($addressService, $addressId, $userId) {
            $addressService->delete($addressId, $userId);
            return null;
        })(),
        'set-default' => $addressService->setDefault($addressId, $userId),
        default => throw new \InvalidArgumentException('Unknown address action.'),
    };

    $addresses = $addressService->listForUser($userId);

    respond([
        'ok' => true,
        'message' => match ($action) {
            'create' => 'Address added.',
            'update' => 'Address updated.',
            'delete' => 'Address removed.',
            'set-default' => 'Default address updated.',
        },
        'address' => $result,
        'html' => render('customer/address-list', ['addresses' => $addresses]),
    ]);
} catch (\InvalidArgumentException | \RuntimeException $exception) {
    report_exception($exception, 'api.addresses.expected');
    respond(['ok' => false, 'message' => $exception->getMessage()], 422);
} catch (\Throwable $exception) {
    report_exception($exception, 'api.addresses.unexpected');
    respond(['ok' => false, 'message' => service_unavailable_message()], 500);
}

function respond(array $payload, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($payload);
    exit;
}
