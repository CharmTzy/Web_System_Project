<?php

declare(strict_types=1);

header('Content-Type: application/json');

try {
    $config = require dirname(__DIR__, 2) . '/bootstrap.php';

    if (empty($_SESSION['user_id']) || !in_array($_SESSION['user_role'] ?? '', ['customer', 'seller', 'admin'], true)) {
        respond([
            'ok' => false,
            'message' => 'Sign in to access chat.',
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

    $userId = (int) $_SESSION['user_id'];
    $role = (string) $_SESSION['user_role'];
    $chatService = new \App\Services\ChatService(
        new \App\Repositories\ChatRepository($connection),
        new \App\Repositories\ProductRepository($connection),
        new \App\Repositories\UserRepository($connection),
    );

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $conversationId = filter_input(INPUT_GET, 'conversation_id', FILTER_VALIDATE_INT) ?: null;

        respond([
            'ok' => true,
        ] + $chatService->overview($userId, $role, $conversationId));
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

    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'start') {
        if ($role !== 'customer') {
            respond([
                'ok' => false,
                'message' => 'Only customer accounts can start seller chats.',
            ], 403);
        }

        $conversation = $chatService->ensureConversationForCustomer(
            $userId,
            filter_input(INPUT_POST, 'seller_id', FILTER_VALIDATE_INT) ?: 0,
            filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT) ?: null
        );

        respond([
            'ok' => true,
            'conversation_id' => (int) $conversation['id'],
            'redirect' => '/customer/chat.php?conversation=' . urlencode((string) $conversation['id']),
        ]);
    }

    $conversationId = filter_input(INPUT_POST, 'conversation_id', FILTER_VALIDATE_INT) ?: 0;

    if ($conversationId < 1) {
        respond([
            'ok' => false,
            'message' => 'Choose a conversation first.',
        ], 422);
    }

    if ($action === 'mark_read') {
        $chatService->markRead($conversationId, $userId, $role);

        respond([
            'ok' => true,
            'unread_total' => $chatService->unreadCount($userId, $role),
        ]);
    }

    if ($action === 'send_message') {
        $payload = $chatService->sendMessage(
            $conversationId,
            $userId,
            $role,
            (string) ($_POST['body'] ?? '')
        );

        respond([
            'ok' => true,
            'message' => $payload['message'],
            'conversation' => $payload['conversation'],
            'unread_total' => $chatService->unreadCount($userId, $role),
        ]);
    }

    respond([
        'ok' => false,
        'message' => 'Unknown action.',
    ], 400);
} catch (\InvalidArgumentException | \RuntimeException $exception) {
    report_exception($exception, 'api.chat.expected');
    respond([
        'ok' => false,
        'message' => safe_exception_message($exception),
    ], 422);
} catch (Throwable $exception) {
    report_exception($exception, 'api.chat.unexpected');
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
