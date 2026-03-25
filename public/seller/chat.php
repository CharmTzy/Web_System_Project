<?php

declare(strict_types=1);

$config = require dirname(__DIR__, 2) . '/bootstrap.php';

if (empty($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'seller') {
    header('Location: /login.php');
    exit;
}

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
    $chatData = $chatService->overview($userId, 'seller', $requestedConversationId);
} catch (Throwable $exception) {
    report_exception($exception, 'seller.chat');
    $chatError = safe_exception_message($exception, 'We could not load your chats right now.');
    $chatData = [
        'conversations' => [],
        'active_conversation_id' => null,
        'active_conversation' => null,
        'messages' => [],
        'unread_total' => 0,
    ];
}

$pageTitle = 'Seller Chat';
$appName = $config['app']['name'];
$pageScript = 'chat.js';
$pageSkeletonVariant = 'panel';
$cartSummary = ['total_items' => 0];
$chatWebSocketUrl = chat_websocket_url($config['app']);

require dirname(__DIR__, 2) . '/resources/views/layouts/header.php';
?>
<main>
    <section class="hero-section hero-section--compact">
        <div class="container">
            <span class="hero-section__eyebrow">Seller chat</span>
            <h1 class="hero-section__title" style="max-width:18ch;">Customer Messages</h1>
            <p class="hero-section__copy">Reply to customer questions as they arrive and keep your order conversations organized.</p>
        </div>
    </section>
    <section class="catalog-section">
        <div class="container">
            <?= render('chat/center', [
                'role' => 'seller',
                'chatData' => $chatData,
                'chatError' => $chatError,
                'chatWebSocketUrl' => $chatWebSocketUrl,
            ]) ?>
        </div>
    </section>
</main>
<?php require dirname(__DIR__, 2) . '/resources/views/layouts/footer.php'; ?>
