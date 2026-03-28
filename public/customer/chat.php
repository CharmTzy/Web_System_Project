<?php

declare(strict_types=1);

$config = require dirname(__DIR__, 2) . '/bootstrap.php';

require_role('customer');

$database = new \App\Support\Database($config['database']);
$connection = $database->connection();

if (!$connection) {
    render_error_page(503, 'Service temporarily unavailable', service_unavailable_message());
}

$chatService = new \App\Services\ChatService(
    new \App\Repositories\ChatRepository($connection),
    new \App\Repositories\ProductRepository($connection),
    new \App\Repositories\UserRepository($connection),
);
$userId = (int) $_SESSION['user_id'];
$requestedConversationId = filter_input(INPUT_GET, 'conversation', FILTER_VALIDATE_INT) ?: null;
$chatError = null;

try {
    $sellerId = filter_input(INPUT_GET, 'seller', FILTER_VALIDATE_INT) ?: 0;
    $productId = filter_input(INPUT_GET, 'product', FILTER_VALIDATE_INT) ?: null;

    if ($sellerId > 0) {
        $conversation = $chatService->ensureConversationForCustomer($userId, $sellerId, $productId);
        $requestedConversationId = (int) $conversation['id'];
    }

    $chatData = $chatService->overview($userId, 'customer', $requestedConversationId);
} catch (Throwable $exception) {
    report_exception($exception, 'customer.chat');
    $chatError = safe_exception_message($exception, 'We could not load your chats right now.');
    $chatData = [
        'conversations' => [],
        'active_conversation_id' => null,
        'active_conversation' => null,
        'messages' => [],
        'unread_total' => 0,
    ];
}

$pageTitle = 'Messages';
$appName = $config['app']['name'];
$pageScript = 'chat.js';
$pageSkeletonVariant = 'chat';
$cartSummary = ['total_items' => 0];
$chatWebSocketUrl = chat_websocket_url($config['app']);

require dirname(__DIR__, 2) . '/resources/views/layouts/header.php';
?>
<main>
    <section class="hero-section hero-section--compact">
        <div class="container">
            <span class="hero-section__eyebrow">Customer chat</span>
            <h1 class="hero-section__title" style="max-width:18ch;">Messages</h1>
            <p class="hero-section__copy">Talk with sellers in real time and keep product questions in one place.</p>
        </div>
    </section>
    <section class="catalog-section">
        <div class="container">
            <?= render('chat/center', [
                'role' => 'customer',
                'chatData' => $chatData,
                'chatError' => $chatError,
                'chatWebSocketUrl' => $chatWebSocketUrl,
            ]) ?>
        </div>
    </section>
</main>
<?php require dirname(__DIR__, 2) . '/resources/views/layouts/footer.php'; ?>
