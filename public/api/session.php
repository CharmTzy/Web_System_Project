<?php

declare(strict_types=1);

header('Content-Type: application/json');

$config = require dirname(__DIR__, 2) . '/bootstrap.php';

$loggedIn = !empty($_SESSION['user_id']);
$notificationCount = 0;
$notifications = [];

if ($loggedIn) {
    $database = new \App\Support\Database($config['database']);
    $connection = $database->connection();

    if ($connection) {
        try {
            $chatService = new \App\Services\ChatService(
                new \App\Repositories\ChatRepository($connection),
                new \App\Repositories\ProductRepository($connection),
                new \App\Repositories\UserRepository($connection),
            );
            $viewerId = (int) $_SESSION['user_id'];
            $viewerRole = (string) ($_SESSION['user_role'] ?? 'customer');

            $notificationCount = $chatService->unreadCount($viewerId, $viewerRole);
            $notifications = $chatService->notifications($viewerId, $viewerRole);
        } catch (Throwable $exception) {
            report_exception($exception, 'api.session.chat');
        }
    }
}

echo json_encode([
    'ok' => true,
    'logged_in' => $loggedIn,
    'notification_count' => $notificationCount,
    'notifications' => $notifications,
    'user' => $loggedIn ? [
        'id' => $_SESSION['user_id'],
        'name' => $_SESSION['user_name'] ?? '',
        'role' => $_SESSION['user_role'] ?? '',
    ] : null,
]);
