<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\ChatRepository;
use App\Repositories\ProductRepository;
use App\Repositories\UserRepository;
use InvalidArgumentException;
use RuntimeException;

final class ChatService
{
    public function __construct(
        private readonly ChatRepository $chatRepository,
        private readonly ProductRepository $productRepository,
        private readonly UserRepository $userRepository,
    ) {
    }

    public function overview(int $viewerId, string $viewerRole, ?int $requestedConversationId = null): array
    {
        $conversations = array_map(
            fn (array $conversation): array => $this->formatConversationItem($conversation, $viewerRole),
            $this->chatRepository->listConversationsForViewer($viewerId, $viewerRole)
        );

        $activeConversation = null;
        $messages = [];

        if ($requestedConversationId !== null && $requestedConversationId > 0) {
            try {
                $rawConversation = $this->conversationForViewer($requestedConversationId, $viewerId, $viewerRole);
                $activeConversation = $this->formatConversationDetail($rawConversation, $viewerRole);
                $messages = $this->formatMessages($this->chatRepository->listMessages($rawConversation['id']), $viewerId);
                $this->chatRepository->markRead($rawConversation['id'], $viewerId);
            } catch (RuntimeException) {
                $requestedConversationId = null;
            }
        } elseif ($conversations !== []) {
            $activeConversation = $this->formatConversationDetail(
                $this->conversationForViewer((int) $conversations[0]['id'], $viewerId, $viewerRole),
                $viewerRole
            );
            $messages = $this->formatMessages($this->chatRepository->listMessages($activeConversation['id']), $viewerId);
            $this->chatRepository->markRead($activeConversation['id'], $viewerId);
        }

        if ($activeConversation === null && $conversations !== []) {
            $activeConversation = $this->formatConversationDetail(
                $this->conversationForViewer((int) $conversations[0]['id'], $viewerId, $viewerRole),
                $viewerRole
            );
            $messages = $this->formatMessages($this->chatRepository->listMessages($activeConversation['id']), $viewerId);
            $this->chatRepository->markRead($activeConversation['id'], $viewerId);
        }

        if ($activeConversation !== null) {
            foreach ($conversations as &$conversation) {
                if ((int) $conversation['id'] === (int) $activeConversation['id']) {
                    $conversation['unread_count'] = 0;
                    break;
                }
            }
            unset($conversation);
        }

        return [
            'conversations' => $conversations,
            'active_conversation_id' => $activeConversation['id'] ?? null,
            'active_conversation' => $activeConversation,
            'messages' => $messages,
            'unread_total' => array_sum(array_map(static fn (array $conversation): int => (int) $conversation['unread_count'], $conversations)),
        ];
    }

    public function ensureConversationForCustomer(int $customerId, int $sellerId, ?int $productId = null): array
    {
        $seller = $this->userRepository->findById($sellerId);

        if ($seller === null || $seller['role'] !== 'seller') {
            throw new RuntimeException('Choose a valid seller to start chatting.');
        }

        $product = null;
        if ($productId !== null && $productId > 0) {
            $product = $this->productRepository->findById($productId);

            if ($product === null || (int) $product['seller_id'] !== $sellerId) {
                throw new RuntimeException('The selected product is not available for seller chat.');
            }
        }

        $existingConversation = $this->chatRepository->findExistingConversation($customerId, $sellerId, $productId);

        if ($existingConversation !== null) {
            return $existingConversation;
        }

        $subject = $product !== null
            ? 'About ' . $product['name']
            : 'Chat with ' . $seller['name'];

        $conversationId = $this->chatRepository->createConversation($customerId, $sellerId, $productId, $subject);

        $conversation = $this->chatRepository->findConversationById($conversationId);

        if ($conversation === null) {
            throw new RuntimeException('We could not open the conversation right now.');
        }

        return $conversation;
    }

    public function sendMessage(int $conversationId, int $senderId, string $senderRole, string $body): array
    {
        $conversation = $this->conversationForViewer($conversationId, $senderId, $senderRole);
        $body = preg_replace("/\r\n?/", "\n", trim($body)) ?? '';

        if ($body === '') {
            throw new InvalidArgumentException('Write a message before sending it.');
        }

        if (mb_strlen($body) > 2000) {
            throw new InvalidArgumentException('Messages must be 2000 characters or fewer.');
        }

        if ($conversation['status'] !== 'open') {
            throw new RuntimeException('This conversation is closed.');
        }

        $message = $this->chatRepository->createMessage($conversationId, $senderId, $body);

        return [
            'conversation' => $this->formatConversationDetail(
                $this->conversationForViewer($conversationId, $senderId, $senderRole),
                $senderRole
            ),
            'message' => $this->formatMessage($message, $senderId),
        ];
    }

    public function markRead(int $conversationId, int $viewerId, string $viewerRole): void
    {
        $conversation = $this->conversationForViewer($conversationId, $viewerId, $viewerRole);
        $this->chatRepository->markRead($conversation['id'], $viewerId);
    }

    public function unreadCount(int $viewerId, string $viewerRole): int
    {
        return $this->chatRepository->unreadCountForViewer($viewerId, $viewerRole);
    }

    public function conversationForViewer(int $conversationId, int $viewerId, string $viewerRole): array
    {
        $conversation = $this->chatRepository->findConversationForViewer($conversationId, $viewerId, $viewerRole);

        if ($conversation === null) {
            throw new RuntimeException('Conversation not found.');
        }

        return $conversation;
    }

    public function websocketPayload(int $conversationId, int $viewerId, string $viewerRole): array
    {
        $conversation = $this->conversationForViewer($conversationId, $viewerId, $viewerRole);

        return $this->formatConversationDetail($conversation, $viewerRole);
    }

    private function formatConversationItem(array $conversation, string $viewerRole): array
    {
        return [
            'id' => (int) $conversation['id'],
            'title' => $this->conversationTitle($conversation, $viewerRole),
            'subtitle' => $this->conversationSubtitle($conversation, $viewerRole),
            'preview' => $conversation['last_message_body'] !== null && $conversation['last_message_body'] !== ''
                ? mb_substr($conversation['last_message_body'], 0, 90)
                : 'No messages yet.',
            'subject' => $conversation['subject'],
            'status' => $conversation['status'],
            'updated_label' => $this->formatConversationDate($conversation['last_message_created_at'] ?: $conversation['last_message_at']),
            'unread_count' => (int) $conversation['unread_count'],
            'product' => $this->formatConversationProduct($conversation),
        ];
    }

    private function formatConversationDetail(array $conversation, string $viewerRole): array
    {
        return [
            'id' => (int) $conversation['id'],
            'title' => $this->conversationTitle($conversation, $viewerRole),
            'subtitle' => $this->conversationSubtitle($conversation, $viewerRole),
            'subject' => $conversation['subject'],
            'status' => $conversation['status'],
            'customer_name' => $conversation['customer_name'],
            'seller_name' => $conversation['seller_name'],
            'product' => $this->formatConversationProduct($conversation),
        ];
    }

    private function formatConversationProduct(array $conversation): ?array
    {
        if (empty($conversation['product_id'])) {
            return null;
        }

        return [
            'id' => (int) $conversation['product_id'],
            'name' => $conversation['product_name'],
            'slug' => $conversation['product_slug'],
            'image_url' => $conversation['product_image_url'] ?: '/assets/images/products/product-fallback.svg',
            'url' => product_url([
                'id' => $conversation['product_id'],
                'slug' => $conversation['product_slug'],
            ]),
        ];
    }

    private function formatMessages(array $messages, int $viewerId): array
    {
        return array_map(
            fn (array $message): array => $this->formatMessage($message, $viewerId),
            $messages
        );
    }

    private function formatMessage(array $message, int $viewerId): array
    {
        $senderRole = (string) ($message['sender_role'] ?? 'system');
        $displayName = (string) ($message['sender_display_name'] ?? $message['sender_name'] ?? 'NovaMarket');

        return [
            'id' => (int) $message['id'],
            'conversation_id' => (int) $message['conversation_id'],
            'sender_id' => $message['sender_id'],
            'sender_name' => $message['sender_name'],
            'sender_role' => $senderRole,
            'sender_label' => $displayName . ($senderRole !== 'system' ? ' · ' . ucfirst($senderRole) : ''),
            'body' => $message['body'],
            'created_at' => $message['created_at'],
            'created_label' => date('d M, g:i A', strtotime((string) $message['created_at'])),
            'is_own' => $message['sender_id'] !== null && (int) $message['sender_id'] === $viewerId,
        ];
    }

    private function conversationTitle(array $conversation, string $viewerRole): string
    {
        return match ($viewerRole) {
            'customer' => $conversation['seller_name'] ?? 'NovaMarket Seller',
            'seller' => $conversation['customer_name'],
            'admin' => trim(($conversation['customer_name'] ?? 'Customer') . ($conversation['seller_name'] ? ' → ' . $conversation['seller_name'] : '')),
            default => $conversation['subject'],
        };
    }

    private function conversationSubtitle(array $conversation, string $viewerRole): string
    {
        if (!empty($conversation['product_name'])) {
            return 'About ' . $conversation['product_name'];
        }

        return match ($viewerRole) {
            'customer' => 'Seller chat',
            'seller' => 'Customer inquiry',
            'admin' => 'Conversation overview',
            default => $conversation['subject'],
        };
    }

    private function formatConversationDate(string $value): string
    {
        $timestamp = strtotime($value);

        if ($timestamp === false) {
            return 'Just now';
        }

        return date('d M, g:i A', $timestamp);
    }
}
