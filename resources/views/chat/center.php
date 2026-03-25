<?php

declare(strict_types=1);

$role = $role ?? 'customer';
$chatData = $chatData ?? [
    'conversations' => [],
    'active_conversation_id' => null,
    'active_conversation' => null,
    'messages' => [],
    'unread_total' => 0,
];
$chatError = $chatError ?? null;
$chatWebSocketUrl = $chatWebSocketUrl ?? '';
$bootstrapJson = json_encode($chatData, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
?>
<div class="chat-shell"
    data-chat-app
    data-chat-role="<?= e($role) ?>"
    data-chat-api="/api/chat.php"
    data-chat-websocket-url="<?= e($chatWebSocketUrl) ?>">
    <aside class="customer-panel chat-shell__sidebar">
        <div class="chat-shell__sidebar-header">
            <div>
                <span class="hero-section__eyebrow">Live conversations</span>
                <h2 class="customer-panel__title">Inbox</h2>
            </div>
            <span class="chat-shell__counter" data-chat-unread-total><?= e((string) ($chatData['unread_total'] ?? 0)) ?></span>
        </div>
        <p class="chat-shell__sidebar-copy">
            <?= e(match ($role) {
                'seller' => 'Stay on top of customer questions and order-related messages.',
                'admin' => 'Monitor every active chat and jump in when support is needed.',
                default => 'Open product conversations and continue seller replies in one inbox.',
            }) ?>
        </p>
        <?php if (!empty($chatError)): ?>
            <div class="alert alert-danger" role="alert"><?= e((string) $chatError) ?></div>
        <?php endif; ?>
        <div class="chat-conversation-list" data-chat-conversation-list></div>
    </aside>

    <section class="customer-panel chat-shell__thread">
        <div class="chat-shell__status" data-chat-status>Connecting to live chat...</div>
        <div class="chat-thread" data-chat-thread></div>
    </section>
</div>
<script type="application/json" data-chat-bootstrap><?= $bootstrapJson !== false ? $bootstrapJson : '{}' ?></script>
