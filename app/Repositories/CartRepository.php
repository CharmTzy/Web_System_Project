<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class CartRepository
{
    public function __construct(private readonly PDO $connection)
    {
    }

    public function findActiveCartIdForUser(int $userId): ?int
    {
        $statement = $this->connection->prepare(
            <<<SQL
            SELECT id
            FROM carts
            WHERE customer_id = :customer_id
              AND status = 'active'
            ORDER BY updated_at DESC, id DESC
            LIMIT 1
            SQL
        );
        $statement->execute(['customer_id' => $userId]);
        $cartId = $statement->fetchColumn();

        return $cartId === false ? null : (int) $cartId;
    }

    public function ensureActiveCartIdForUser(int $userId, string $sessionToken): int
    {
        $cartId = $this->findActiveCartIdForUser($userId);

        if ($cartId !== null) {
            $statement = $this->connection->prepare(
                <<<SQL
                UPDATE carts
                SET session_token = :session_token,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = :id
                SQL
            );
            $statement->execute([
                'id' => $cartId,
                'session_token' => $sessionToken,
            ]);

            return $cartId;
        }

        $statement = $this->connection->prepare(
            <<<SQL
            INSERT INTO carts (customer_id, session_token, status)
            VALUES (:customer_id, :session_token, 'active')
            SQL
        );
        $statement->execute([
            'customer_id' => $userId,
            'session_token' => $sessionToken,
        ]);

        return (int) $this->connection->lastInsertId();
    }

    public function itemsForCart(int $cartId): array
    {
        $statement = $this->connection->prepare(
            <<<SQL
            SELECT product_id, quantity
            FROM cart_items
            WHERE cart_id = :cart_id
            ORDER BY id ASC
            SQL
        );
        $statement->execute(['cart_id' => $cartId]);

        $items = [];

        foreach ($statement->fetchAll() as $row) {
            $items[(int) $row['product_id']] = (int) $row['quantity'];
        }

        return $items;
    }

    public function replaceItems(int $cartId, array $items): void
    {
        $this->connection->beginTransaction();

        try {
            $deleteStatement = $this->connection->prepare(
                'DELETE FROM cart_items WHERE cart_id = :cart_id'
            );
            $deleteStatement->execute(['cart_id' => $cartId]);

            if ($items !== []) {
                $insertStatement = $this->connection->prepare(
                    <<<SQL
                    INSERT INTO cart_items (cart_id, product_id, quantity, unit_price)
                    VALUES (:cart_id, :product_id, :quantity, :unit_price)
                    SQL
                );

                foreach ($items as $productId => $item) {
                    $insertStatement->execute([
                        'cart_id' => $cartId,
                        'product_id' => (int) $productId,
                        'quantity' => (int) $item['quantity'],
                        'unit_price' => (float) $item['unit_price'],
                    ]);
                }
            }

            $touchStatement = $this->connection->prepare(
                'UPDATE carts SET updated_at = CURRENT_TIMESTAMP WHERE id = :id'
            );
            $touchStatement->execute(['id' => $cartId]);

            $this->connection->commit();
        } catch (\Throwable $exception) {
            if ($this->connection->inTransaction()) {
                $this->connection->rollBack();
            }

            throw $exception;
        }
    }
}
