<?php

declare(strict_types=1);

header('Content-Type: application/json');

$config = require dirname(__DIR__, 2) . '/bootstrap.php';

$loggedIn = !empty($_SESSION['user_id']);
$notificationCount = 0;

if ($loggedIn) {
    $database = new \App\Support\Database($config['database']);
    $connection = $database->connection();

    if ($connection) {
        try {
            $notificationCount = (new \App\Services\ChatService(
                new \App\Repositories\ChatRepository($connection),
                new \App\Repositories\ProductRepository($connection),
                new \App\Repositories\UserRepository($connection),
            ))->unreadCount((int) $_SESSION['user_id'], (string) ($_SESSION['user_role'] ?? 'customer'));
        } catch (Throwable $exception) {
            report_exception($exception, 'api.session.chat');
        }
    }
}

echo json_encode([
    'ok' => true,
    'logged_in' => $loggedIn,
    'notification_count' => $notificationCount,
    'user' => $loggedIn ? [
        'id' => $_SESSION['user_id'],
        'name' => $_SESSION['user_name'] ?? '',
        'role' => $_SESSION['user_role'] ?? '',
    ] : null,
]);
