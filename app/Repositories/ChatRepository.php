<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class ChatRepository
{
    public function __construct(private readonly PDO $connection)
    {
    }

    public function findExistingConversation(int $customerId, int $sellerId, ?int $productId): ?array
    {
        $sql = <<<SQL
            SELECT id
            FROM chat_conversations
            WHERE customer_id = :customer_id
              AND seller_id = :seller_id
              AND (
                    (:product_id IS NULL AND product_id IS NULL)
                    OR product_id = :product_id_exact
                  )
            ORDER BY id DESC
            LIMIT 1
        SQL;

        $statement = $this->connection->prepare($sql);
        $statement->execute([
            'customer_id' => $customerId,
            'seller_id' => $sellerId,
            'product_id' => $productId,
            'product_id_exact' => $productId,
        ]);

        $row = $statement->fetch();

        return is_array($row)
            ? $this->findConversationById((int) $row['id'])
            : null;
    }

    public function createConversation(int $customerId, int $sellerId, ?int $productId, string $subject): int
    {
        $statement = $this->connection->prepare(
            <<<SQL
            INSERT INTO chat_conversations (customer_id, seller_id, product_id, subject, status, last_message_at)
            VALUES (:customer_id, :seller_id, :product_id, :subject, 'open', NOW())
            SQL
        );
        $statement->execute([
            'customer_id' => $customerId,
            'seller_id' => $sellerId,
            'product_id' => $productId,
            'subject' => $subject,
        ]);

        return (int) $this->connection->lastInsertId();
    }

    public function findConversationForViewer(int $conversationId, int $viewerId, string $viewerRole): ?array
    {
        $params = [
            'viewer_id_read' => $viewerId,
            'viewer_id_unread' => $viewerId,
            'conversation_id' => $conversationId,
        ];
        $sql = $this->conversationSelectSql() . ' WHERE c.id = :conversation_id';

        if ($viewerRole === 'customer') {
            $sql .= ' AND c.customer_id = :viewer_id_access';
            $params['viewer_id_access'] = $viewerId;
        } elseif ($viewerRole === 'seller') {
            $sql .= ' AND c.seller_id = :viewer_id_access';
            $params['viewer_id_access'] = $viewerId;
        }

        $statement = $this->connection->prepare($sql . ' LIMIT 1');
        $statement->execute($params);
        $row = $statement->fetch();

        return is_array($row) ? $this->normalizeConversation($row) : null;
    }

    public function findConversationById(int $conversationId): ?array
    {
        $statement = $this->connection->prepare(
            <<<SQL
            SELECT
                c.id,
                c.customer_id,
                c.seller_id,
                c.product_id,
                c.subject,
                c.status,
                c.last_message_at,
                c.created_at,
                c.updated_at,
                customer.name AS customer_name,
                seller.name AS seller_user_name,
                COALESCE(sp.store_name, seller.name) AS seller_name,
                p.name AS product_name,
                p.slug AS product_slug,
                p.image_url AS product_image_url
            FROM chat_conversations c
            INNER JOIN users customer
                ON customer.id = c.customer_id
            LEFT JOIN users seller
                ON seller.id = c.seller_id
            LEFT JOIN seller_profiles sp
                ON sp.user_id = seller.id
            LEFT JOIN products p
                ON p.id = c.product_id
            WHERE c.id = :conversation_id
            LIMIT 1
            SQL
        );
        $statement->execute(['conversation_id' => $conversationId]);
        $row = $statement->fetch();

        return is_array($row) ? $this->normalizeConversation($row) : null;
    }

    public function listConversationsForViewer(int $viewerId, string $viewerRole): array
    {
        $params = [
            'viewer_id_read' => $viewerId,
            'viewer_id_unread' => $viewerId,
        ];
        $sql = $this->conversationSelectSql();

        if ($viewerRole === 'customer') {
            $sql .= ' WHERE c.customer_id = :viewer_id_access';
            $params['viewer_id_access'] = $viewerId;
        } elseif ($viewerRole === 'seller') {
            $sql .= ' WHERE c.seller_id = :viewer_id_access';
            $params['viewer_id_access'] = $viewerId;
        }

        $sql .= ' ORDER BY COALESCE(lm.created_at, c.last_message_at, c.created_at) DESC, c.id DESC';

        $statement = $this->connection->prepare($sql);
        $statement->execute($params);

        return array_map([$this, 'normalizeConversation'], $statement->fetchAll());
    }

    public function listMessages(int $conversationId): array
    {
        $statement = $this->connection->prepare(
            <<<SQL
            SELECT
                m.id,
                m.conversation_id,
                m.sender_id,
                m.body,
                m.created_at,
                u.name AS sender_name,
                u.role AS sender_role,
                COALESCE(sp.store_name, u.name) AS sender_display_name
            FROM chat_messages m
            LEFT JOIN users u
                ON u.id = m.sender_id
            LEFT JOIN seller_profiles sp
                ON sp.user_id = u.id
            WHERE m.conversation_id = :conversation_id
            ORDER BY m.id ASC
            SQL
        );
        $statement->execute(['conversation_id' => $conversationId]);

        return array_map([$this, 'normalizeMessage'], $statement->fetchAll());
    }

    public function createMessage(int $conversationId, int $senderId, string $body): array
    {
        $statement = $this->connection->prepare(
            'INSERT INTO chat_messages (conversation_id, sender_id, body) VALUES (:conversation_id, :sender_id, :body)'
        );
        $statement->execute([
            'conversation_id' => $conversationId,
            'sender_id' => $senderId,
            'body' => $body,
        ]);

        $messageId = (int) $this->connection->lastInsertId();

        $updateConversation = $this->connection->prepare(
            'UPDATE chat_conversations SET last_message_at = NOW() WHERE id = :conversation_id'
        );
        $updateConversation->execute(['conversation_id' => $conversationId]);

        $this->markRead($conversationId, $senderId, $messageId);

        $message = $this->findMessageById($messageId);

        if ($message === null) {
            throw new \RuntimeException('Unable to load the saved chat message.');
        }

        return $message;
    }

    public function markRead(int $conversationId, int $userId, ?int $lastReadMessageId = null): void
    {
        $lastReadMessageId ??= $this->latestMessageId($conversationId);

        $statement = $this->connection->prepare(
            <<<SQL
            INSERT INTO chat_reads (conversation_id, user_id, last_read_message_id, last_read_at)
            VALUES (:conversation_id, :user_id, :last_read_message_id, NOW())
            ON DUPLICATE KEY UPDATE
                last_read_message_id = VALUES(last_read_message_id),
                last_read_at = VALUES(last_read_at)
            SQL
        );
        $statement->execute([
            'conversation_id' => $conversationId,
            'user_id' => $userId,
            'last_read_message_id' => $lastReadMessageId,
        ]);
    }

    public function unreadCountForViewer(int $viewerId, string $viewerRole): int
    {
        $total = 0;

        foreach ($this->listConversationsForViewer($viewerId, $viewerRole) as $conversation) {
            $total += (int) $conversation['unread_count'];
        }

        return $total;
    }

    private function latestMessageId(int $conversationId): ?int
    {
        $statement = $this->connection->prepare(
            'SELECT id FROM chat_messages WHERE conversation_id = :conversation_id ORDER BY id DESC LIMIT 1'
        );
        $statement->execute(['conversation_id' => $conversationId]);
        $value = $statement->fetchColumn();

        return $value !== false ? (int) $value : null;
    }

    private function findMessageById(int $messageId): ?array
    {
        $statement = $this->connection->prepare(
            <<<SQL
            SELECT
                m.id,
                m.conversation_id,
                m.sender_id,
                m.body,
                m.created_at,
                u.name AS sender_name,
                u.role AS sender_role,
                COALESCE(sp.store_name, u.name) AS sender_display_name
            FROM chat_messages m
            LEFT JOIN users u
                ON u.id = m.sender_id
            LEFT JOIN seller_profiles sp
                ON sp.user_id = u.id
            WHERE m.id = :message_id
            LIMIT 1
            SQL
        );
        $statement->execute(['message_id' => $messageId]);
        $row = $statement->fetch();

        return is_array($row) ? $this->normalizeMessage($row) : null;
    }

    private function conversationSelectSql(): string
    {
        return <<<SQL
            SELECT
                c.id,
                c.customer_id,
                c.seller_id,
                c.product_id,
                c.subject,
                c.status,
                c.last_message_at,
                c.created_at,
                c.updated_at,
                customer.name AS customer_name,
                seller.name AS seller_user_name,
                COALESCE(sp.store_name, seller.name) AS seller_name,
                p.name AS product_name,
                p.slug AS product_slug,
                p.image_url AS product_image_url,
                lm.id AS last_message_id,
                lm.body AS last_message_body,
                lm.created_at AS last_message_created_at,
                lm.sender_id AS last_message_sender_id,
                COALESCE(cr.last_read_message_id, 0) AS viewer_last_read_message_id,
                (
                    SELECT COUNT(*)
                    FROM chat_messages unread_messages
                    WHERE unread_messages.conversation_id = c.id
                      AND unread_messages.id > COALESCE(cr.last_read_message_id, 0)
                      AND (unread_messages.sender_id IS NULL OR unread_messages.sender_id <> :viewer_id_unread)
                ) AS unread_count
            FROM chat_conversations c
            INNER JOIN users customer
                ON customer.id = c.customer_id
            LEFT JOIN users seller
                ON seller.id = c.seller_id
            LEFT JOIN seller_profiles sp
                ON sp.user_id = seller.id
            LEFT JOIN products p
                ON p.id = c.product_id
            LEFT JOIN chat_reads cr
                ON cr.conversation_id = c.id
               AND cr.user_id = :viewer_id_read
            LEFT JOIN chat_messages lm
                ON lm.id = (
                    SELECT latest_message.id
                    FROM chat_messages latest_message
                    WHERE latest_message.conversation_id = c.id
                    ORDER BY latest_message.id DESC
                    LIMIT 1
                )
            SQL;
    }

    private function normalizeConversation(array $row): array
    {
        return [
            'id' => (int) $row['id'],
            'customer_id' => (int) $row['customer_id'],
            'seller_id' => isset($row['seller_id']) ? (int) $row['seller_id'] : null,
            'product_id' => isset($row['product_id']) ? (int) $row['product_id'] : null,
            'subject' => (string) $row['subject'],
            'status' => (string) $row['status'],
            'last_message_at' => (string) ($row['last_message_at'] ?? $row['created_at']),
            'created_at' => (string) $row['created_at'],
            'updated_at' => (string) $row['updated_at'],
            'customer_name' => (string) $row['customer_name'],
            'seller_name' => isset($row['seller_name']) ? (string) $row['seller_name'] : null,
            'seller_user_name' => isset($row['seller_user_name']) ? (string) $row['seller_user_name'] : null,
            'product_name' => isset($row['product_name']) ? (string) $row['product_name'] : null,
            'product_slug' => isset($row['product_slug']) ? (string) $row['product_slug'] : null,
            'product_image_url' => isset($row['product_image_url']) ? (string) $row['product_image_url'] : null,
            'last_message_id' => isset($row['last_message_id']) ? (int) $row['last_message_id'] : null,
            'last_message_body' => isset($row['last_message_body']) ? (string) $row['last_message_body'] : null,
            'last_message_created_at' => isset($row['last_message_created_at']) ? (string) $row['last_message_created_at'] : null,
            'last_message_sender_id' => isset($row['last_message_sender_id']) ? (int) $row['last_message_sender_id'] : null,
            'unread_count' => (int) ($row['unread_count'] ?? 0),
        ];
    }

    private function normalizeMessage(array $row): array
    {
        return [
            'id' => (int) $row['id'],
            'conversation_id' => (int) $row['conversation_id'],
            'sender_id' => isset($row['sender_id']) ? (int) $row['sender_id'] : null,
            'body' => (string) $row['body'],
            'created_at' => (string) $row['created_at'],
            'sender_name' => isset($row['sender_name']) ? (string) $row['sender_name'] : 'NovaMarket',
            'sender_role' => isset($row['sender_role']) ? (string) $row['sender_role'] : 'system',
            'sender_display_name' => isset($row['sender_display_name']) ? (string) $row['sender_display_name'] : 'NovaMarket',
        ];
    }
}
