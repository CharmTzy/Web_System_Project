<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;
use PDOException;

final class OrderRepository
{
    public function __construct(private readonly PDO $connection)
    {
    }

    public function transaction(callable $callback): mixed
    {
        $ownsTransaction = !$this->connection->inTransaction();

        if ($ownsTransaction) {
            $this->connection->beginTransaction();
        }

        try {
            $result = $callback();

            if ($ownsTransaction) {
                $this->connection->commit();
            }

            return $result;
        } catch (\Throwable $exception) {
            if ($ownsTransaction && $this->connection->inTransaction()) {
                $this->connection->rollBack();
            }

            throw $exception;
        }
    }

    public function create(array $data, array $items): int
    {
        $statement = $this->connection->prepare(
            <<<SQL
            INSERT INTO orders (
                customer_id,
                order_number,
                status,
                shipping_recipient,
                shipping_line_1,
                shipping_line_2,
                shipping_city,
                shipping_state,
                shipping_postal_code,
                shipping_country,
                shipping_phone,
                payment_card_brand,
                payment_card_last_four,
                subtotal,
                shipping_fee,
                total
            ) VALUES (
                :customer_id,
                :order_number,
                :status,
                :shipping_recipient,
                :shipping_line_1,
                :shipping_line_2,
                :shipping_city,
                :shipping_state,
                :shipping_postal_code,
                :shipping_country,
                :shipping_phone,
                :payment_card_brand,
                :payment_card_last_four,
                :subtotal,
                :shipping_fee,
                :total
            )
            SQL
        );
        $statement->execute([
            'customer_id' => $data['customer_id'],
            'order_number' => $data['order_number'],
            'status' => $data['status'],
            'shipping_recipient' => $data['shipping_recipient'],
            'shipping_line_1' => $data['shipping_line_1'],
            'shipping_line_2' => $data['shipping_line_2'],
            'shipping_city' => $data['shipping_city'],
            'shipping_state' => $data['shipping_state'],
            'shipping_postal_code' => $data['shipping_postal_code'],
            'shipping_country' => $data['shipping_country'],
            'shipping_phone' => $data['shipping_phone'],
            'payment_card_brand' => $data['payment_card_brand'],
            'payment_card_last_four' => $data['payment_card_last_four'],
            'subtotal' => $data['subtotal'],
            'shipping_fee' => $data['shipping_fee'],
            'total' => $data['total'],
        ]);

        $orderId = (int) $this->connection->lastInsertId();

        if ($items !== []) {
            $insertItem = $this->connection->prepare(
                <<<SQL
                INSERT INTO order_items (
                    order_id,
                    product_id,
                    product_name,
                    quantity,
                    unit_price,
                    line_total
                ) VALUES (
                    :order_id,
                    :product_id,
                    :product_name,
                    :quantity,
                    :unit_price,
                    :line_total
                )
                SQL
            );

            foreach ($items as $item) {
                $insertItem->execute([
                    'order_id' => $orderId,
                    'product_id' => $item['product_id'],
                    'product_name' => $item['product_name'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'line_total' => $item['line_total'],
                ]);
            }
        }

        return $orderId;
    }

    public function findByIdForCustomer(int $orderId, int $customerId): ?array
    {
        $statement = $this->connection->prepare(
            'SELECT * FROM orders WHERE id = :id AND customer_id = :customer_id LIMIT 1'
        );
        $statement->execute([
            'id' => $orderId,
            'customer_id' => $customerId,
        ]);
        $row = $statement->fetch();

        if (!is_array($row)) {
            return null;
        }

        return $this->normalizeOrder($row) + [
            'items' => $this->itemsForOrderIds([$orderId])[$orderId] ?? [],
        ];
    }

    public function listByCustomer(int $customerId): array
    {
        $statement = $this->connection->prepare(
            'SELECT * FROM orders WHERE customer_id = :customer_id ORDER BY created_at DESC, id DESC'
        );
        $statement->execute(['customer_id' => $customerId]);
        $orders = array_map(fn (array $row): array => $this->normalizeOrder($row), $statement->fetchAll());

        if ($orders === []) {
            return [];
        }

        $itemsByOrder = $this->itemsForOrderIds(array_column($orders, 'id'));

        return array_map(
            fn (array $order): array => $order + [
                'items' => $itemsByOrder[$order['id']] ?? [],
            ],
            $orders
        );
    }

    public function userHasPurchasedProduct(int $customerId, int $productId): bool
    {
        try {
            $statement = $this->connection->prepare(
                <<<SQL
                SELECT COUNT(*)
                FROM order_items oi
                INNER JOIN orders o
                    ON o.id = oi.order_id
                WHERE o.customer_id = :customer_id
                  AND oi.product_id = :product_id
                  AND o.status IN ('pending', 'paid', 'shipped', 'delivered')
                SQL
            );
            $statement->execute([
                'customer_id' => $customerId,
                'product_id' => $productId,
            ]);

            return (int) $statement->fetchColumn() > 0;
        } catch (PDOException) {
            return false;
        }
    }

    private function itemsForOrderIds(array $orderIds): array
    {
        $orderIds = array_values(array_filter(array_map('intval', $orderIds)));

        if ($orderIds === []) {
            return [];
        }

        $placeholders = implode(', ', array_fill(0, count($orderIds), '?'));
        $statement = $this->connection->prepare(
            <<<SQL
            SELECT
                oi.order_id,
                oi.product_id,
                oi.product_name,
                oi.quantity,
                oi.unit_price,
                oi.line_total,
                p.slug,
                p.image_url
            FROM order_items oi
            LEFT JOIN products p
                ON p.id = oi.product_id
            WHERE oi.order_id IN ($placeholders)
            ORDER BY oi.id ASC
            SQL
        );
        $statement->execute($orderIds);

        $itemsByOrder = [];

        foreach ($statement->fetchAll() as $row) {
            $orderId = (int) $row['order_id'];
            $itemsByOrder[$orderId] ??= [];
            $itemsByOrder[$orderId][] = [
                'product_id' => (int) $row['product_id'],
                'product_name' => (string) $row['product_name'],
                'slug' => (string) ($row['slug'] ?? ''),
                'image_url' => (string) ($row['image_url'] ?: '/assets/images/products/product-fallback.svg'),
                'quantity' => (int) $row['quantity'],
                'unit_price' => (float) $row['unit_price'],
                'unit_price_formatted' => money((float) $row['unit_price']),
                'line_total' => (float) $row['line_total'],
                'line_total_formatted' => money((float) $row['line_total']),
            ];
        }

        return $itemsByOrder;
    }

    private function normalizeOrder(array $row): array
    {
        return [
            'id' => (int) $row['id'],
            'customer_id' => (int) $row['customer_id'],
            'order_number' => (string) $row['order_number'],
            'status' => (string) $row['status'],
            'shipping_recipient' => (string) $row['shipping_recipient'],
            'shipping_line_1' => (string) $row['shipping_line_1'],
            'shipping_line_2' => $row['shipping_line_2'],
            'shipping_city' => (string) $row['shipping_city'],
            'shipping_state' => (string) $row['shipping_state'],
            'shipping_postal_code' => (string) $row['shipping_postal_code'],
            'shipping_country' => (string) $row['shipping_country'],
            'shipping_phone' => $row['shipping_phone'],
            'payment_card_brand' => $row['payment_card_brand'] !== null ? (string) $row['payment_card_brand'] : null,
            'payment_card_last_four' => $row['payment_card_last_four'] !== null ? (string) $row['payment_card_last_four'] : null,
            'subtotal' => (float) $row['subtotal'],
            'subtotal_formatted' => money((float) $row['subtotal']),
            'shipping_fee' => (float) $row['shipping_fee'],
            'shipping_fee_formatted' => (float) $row['shipping_fee'] > 0 ? money((float) $row['shipping_fee']) : 'FREE',
            'total' => (float) $row['total'],
            'total_formatted' => money((float) $row['total']),
            'created_at' => (string) $row['created_at'],
            'updated_at' => (string) $row['updated_at'],
        ];
    }
}
