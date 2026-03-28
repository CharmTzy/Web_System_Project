<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;
use PDOException;
use RuntimeException;

final class OrderRepository
{
    private const SYNTHETIC_FULFILLMENT_SCALE = 1000000;

    private ?bool $fulfillmentsTableAvailable = null;

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

            $this->insertFulfillmentsForOrder($orderId, $data['status'] ?? 'pending', $items);
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

        $orders = $this->attachOrderRelations([$this->normalizeOrder($row)]);

        return $orders[0] ?? null;
    }

    public function findByOrderNumber(string $orderNumber): ?array
    {
        $statement = $this->connection->prepare(
            'SELECT * FROM orders WHERE order_number = :order_number LIMIT 1'
        );
        $statement->execute(['order_number' => $orderNumber]);
        $row = $statement->fetch();

        if (!is_array($row)) {
            return null;
        }

        $orders = $this->attachOrderRelations([$this->normalizeOrder($row)]);

        return $orders[0] ?? null;
    }

    public function findByOrderNumberForCustomer(string $orderNumber, int $customerId): ?array
    {
        $statement = $this->connection->prepare(
            'SELECT * FROM orders WHERE order_number = :order_number AND customer_id = :customer_id LIMIT 1'
        );
        $statement->execute([
            'order_number' => $orderNumber,
            'customer_id' => $customerId,
        ]);
        $row = $statement->fetch();

        if (!is_array($row)) {
            return null;
        }

        $orders = $this->attachOrderRelations([$this->normalizeOrder($row)]);

        return $orders[0] ?? null;
    }

    public function markPaidByOrderNumber(string $orderNumber): bool
    {
        $statement = $this->connection->prepare(
            <<<SQL
            UPDATE orders
            SET status = 'paid',
                updated_at = CURRENT_TIMESTAMP
            WHERE order_number = :order_number
              AND status = 'pending'
            SQL
        );
        $statement->execute([
            'order_number' => $orderNumber,
        ]);

        if ($this->fulfillmentsTableAvailable()) {
            $fulfillmentStatement = $this->connection->prepare(
                <<<SQL
                UPDATE order_fulfillments f
                INNER JOIN orders o
                    ON o.id = f.order_id
                SET f.status = CASE
                        WHEN f.status = 'pending' THEN 'paid'
                        ELSE f.status
                    END,
                    f.updated_at = CURRENT_TIMESTAMP
                WHERE o.order_number = :order_number
                SQL
            );
            $fulfillmentStatement->execute([
                'order_number' => $orderNumber,
            ]);
        }

        return $statement->rowCount() > 0;
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

        return $this->attachOrderRelations($orders);
    }

    public function findMatchingPendingOrderForCustomer(int $customerId, array $snapshot, array $items): ?array
    {
        foreach ($this->pendingOrdersForCustomer($customerId) as $order) {
            if ($this->orderMatchesSnapshot($order, $snapshot, $items)) {
                return $order;
            }
        }

        return null;
    }

    public function deleteMatchingPendingOrdersForCustomerExcept(int $customerId, int $keepOrderId, array $snapshot, array $items): void
    {
        $duplicateIds = [];

        foreach ($this->pendingOrdersForCustomer($customerId) as $order) {
            $orderId = (int) ($order['id'] ?? 0);

            if ($orderId === $keepOrderId) {
                continue;
            }

            if ($this->orderMatchesSnapshot($order, $snapshot, $items)) {
                $duplicateIds[] = $orderId;
            }
        }

        if ($duplicateIds === []) {
            return;
        }

        $placeholders = implode(', ', array_fill(0, count($duplicateIds), '?'));
        $statement = $this->connection->prepare(
            "DELETE FROM orders WHERE customer_id = ? AND status = 'pending' AND id IN ($placeholders)"
        );
        $statement->execute(array_merge([$customerId], $duplicateIds));
    }

    public function listFulfillmentsForAdmin(): array
    {
        if (!$this->fulfillmentsTableAvailable()) {
            return $this->listFallbackFulfillments();
        }

        $statement = $this->connection->query(
            $this->fulfillmentSelectSql()
                . ' ORDER BY f.updated_at DESC, f.created_at DESC, f.id DESC'
        );

        return array_map([$this, 'normalizeFulfillment'], $statement->fetchAll());
    }

    public function listFulfillmentsForSeller(int $sellerId): array
    {
        if (!$this->fulfillmentsTableAvailable()) {
            return $this->listFallbackFulfillments($sellerId);
        }

        $statement = $this->connection->prepare(
            $this->fulfillmentSelectSql() . ' WHERE f.seller_id = :seller_id ORDER BY f.updated_at DESC, f.created_at DESC, f.id DESC'
        );
        $statement->execute(['seller_id' => $sellerId]);

        return array_map([$this, 'normalizeFulfillment'], $statement->fetchAll());
    }

    public function findFulfillmentForAdmin(int $fulfillmentId): ?array
    {
        if (!$this->fulfillmentsTableAvailable()) {
            return $this->findFallbackFulfillmentById($fulfillmentId);
        }

        return $this->findFulfillment($fulfillmentId);
    }

    public function findFulfillmentForSeller(int $fulfillmentId, int $sellerId): ?array
    {
        if (!$this->fulfillmentsTableAvailable()) {
            $fulfillment = $this->findFallbackFulfillmentById($fulfillmentId);

            return $fulfillment !== null && (int) ($fulfillment['seller_id'] ?? 0) === $sellerId
                ? $fulfillment
                : null;
        }

        return $this->findFulfillment($fulfillmentId, $sellerId);
    }

    public function updateFulfillment(int $fulfillmentId, array $attributes): ?array
    {
        if (!$this->fulfillmentsTableAvailable()) {
            throw new RuntimeException('Order delivery records are not available until the fulfillment migration is applied.');
        }

        $current = $this->findFulfillmentRaw($fulfillmentId);

        if ($current === null) {
            return null;
        }

        $fields = [];
        $params = ['id' => $fulfillmentId];

        foreach ([
            'status',
            'courier_name',
            'tracking_number',
            'status_note',
            'estimated_delivery_date',
            'shipped_at',
            'out_for_delivery_at',
            'delivered_at',
        ] as $column) {
            if (array_key_exists($column, $attributes)) {
                $fields[] = $column . ' = :' . $column;
                $params[$column] = $attributes[$column];
            }
        }

        if ($fields !== []) {
            $fields[] = 'updated_at = CURRENT_TIMESTAMP';

            $statement = $this->connection->prepare(
                'UPDATE order_fulfillments SET ' . implode(', ', $fields) . ' WHERE id = :id'
            );
            $statement->execute($params);
        }

        $this->refreshOrderStatus((int) $current['order_id']);

        return $this->findFulfillment((int) $fulfillmentId);
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

    private function attachOrderRelations(array $orders): array
    {
        if ($orders === []) {
            return [];
        }

        $orderIds = array_column($orders, 'id');
        $itemsByOrder = $this->itemsForOrderIds($orderIds);
        $fulfillmentsByOrder = $this->fulfillmentsForOrderIds($orderIds);

        foreach ($orders as &$order) {
            $items = $itemsByOrder[$order['id']] ?? [];
            $itemsBySeller = [];

            foreach ($items as $item) {
                $itemsBySeller[(int) ($item['seller_id'] ?? 0)][] = $item;
            }

            $fulfillments = $fulfillmentsByOrder[$order['id']] ?? [];

            if ($fulfillments === []) {
                $fulfillments = $this->buildFallbackFulfillments($order, $items);
            } else {
                $knownSellers = [];

                foreach ($fulfillments as &$fulfillment) {
                    $sellerId = (int) ($fulfillment['seller_id'] ?? 0);
                    $knownSellers[$sellerId] = true;
                    $fulfillment['items'] = $itemsBySeller[$sellerId] ?? [];
                    $fulfillment['item_count'] = $fulfillment['item_count'] ?: count($fulfillment['items']);
                    $fulfillment['item_quantity'] = $fulfillment['item_quantity'] ?: array_sum(
                        array_map(static fn (array $item): int => (int) ($item['quantity'] ?? 0), $fulfillment['items'])
                    );
                }
                unset($fulfillment);

                foreach ($itemsBySeller as $sellerId => $sellerItems) {
                    if (isset($knownSellers[$sellerId])) {
                        continue;
                    }

                    $fulfillments[] = $this->buildFallbackFulfillment($order, $sellerId, $sellerItems);
                }
            }

            $order['items'] = $items;
            $order['fulfillments'] = $fulfillments;
        }
        unset($order);

        return $orders;
    }

    private function pendingOrdersForCustomer(int $customerId): array
    {
        $statement = $this->connection->prepare(
            <<<SQL
            SELECT *
            FROM orders
            WHERE customer_id = :customer_id
              AND status = 'pending'
            ORDER BY created_at DESC, id DESC
            LIMIT 25
            SQL
        );
        $statement->execute(['customer_id' => $customerId]);
        $orders = array_map(fn (array $row): array => $this->normalizeOrder($row), $statement->fetchAll());

        return $this->attachOrderRelations($orders);
    }

    private function orderMatchesSnapshot(array $order, array $snapshot, array $items): bool
    {
        foreach ([
            'customer_id',
            'shipping_recipient',
            'shipping_line_1',
            'shipping_line_2',
            'shipping_city',
            'shipping_state',
            'shipping_postal_code',
            'shipping_country',
            'shipping_phone',
        ] as $field) {
            if ($this->normalizeSnapshotValue($order[$field] ?? null) !== $this->normalizeSnapshotValue($snapshot[$field] ?? null)) {
                return false;
            }
        }

        foreach (['subtotal', 'shipping_fee', 'total'] as $field) {
            if (!$this->sameMoneyValue($order[$field] ?? 0, $snapshot[$field] ?? 0)) {
                return false;
            }
        }

        return $this->orderItemSignature($order['items'] ?? []) === $this->orderItemSignature($items);
    }

    private function orderItemSignature(array $items): array
    {
        $signature = array_map(
            static function (array $item): string {
                return implode(':', [
                    (int) ($item['product_id'] ?? 0),
                    (int) ($item['seller_id'] ?? 0),
                    (int) ($item['quantity'] ?? 0),
                    number_format((float) ($item['unit_price'] ?? 0), 2, '.', ''),
                    number_format((float) ($item['line_total'] ?? 0), 2, '.', ''),
                ]);
            },
            $items
        );

        sort($signature);

        return $signature;
    }

    private function normalizeSnapshotValue(mixed $value): string
    {
        return trim((string) ($value ?? ''));
    }

    private function sameMoneyValue(mixed $left, mixed $right): bool
    {
        return round((float) $left, 2) === round((float) $right, 2);
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
                p.image_url,
                p.seller_id,
                COALESCE(sp.store_name, seller.name) AS seller_name
            FROM order_items oi
            LEFT JOIN products p
                ON p.id = oi.product_id
            LEFT JOIN users seller
                ON seller.id = p.seller_id
            LEFT JOIN seller_profiles sp
                ON sp.user_id = seller.id
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
                'seller_id' => (int) ($row['seller_id'] ?? 0),
                'seller_name' => trim((string) ($row['seller_name'] ?? '')) ?: 'Marketplace seller',
                'quantity' => (int) $row['quantity'],
                'unit_price' => (float) $row['unit_price'],
                'unit_price_formatted' => money((float) $row['unit_price']),
                'line_total' => (float) $row['line_total'],
                'line_total_formatted' => money((float) $row['line_total']),
            ];
        }

        return $itemsByOrder;
    }

    private function fulfillmentsForOrderIds(array $orderIds): array
    {
        if (!$this->fulfillmentsTableAvailable()) {
            return [];
        }

        $orderIds = array_values(array_filter(array_map('intval', $orderIds)));

        if ($orderIds === []) {
            return [];
        }

        $placeholders = implode(', ', array_fill(0, count($orderIds), '?'));
        $statement = $this->connection->prepare(
            <<<SQL
            SELECT
                f.id,
                f.order_id,
                f.seller_id,
                f.status,
                f.seller_subtotal,
                f.item_count,
                f.item_quantity,
                f.courier_name,
                f.tracking_number,
                f.status_note,
                f.estimated_delivery_date,
                f.shipped_at,
                f.out_for_delivery_at,
                f.delivered_at,
                f.created_at,
                f.updated_at,
                COALESCE(sp.store_name, seller.name) AS seller_name
            FROM order_fulfillments f
            INNER JOIN users seller
                ON seller.id = f.seller_id
            LEFT JOIN seller_profiles sp
                ON sp.user_id = seller.id
            WHERE f.order_id IN ($placeholders)
            ORDER BY f.created_at ASC, f.id ASC
            SQL
        );
        $statement->execute($orderIds);

        $fulfillmentsByOrder = [];

        foreach ($statement->fetchAll() as $row) {
            $orderId = (int) $row['order_id'];
            $fulfillmentsByOrder[$orderId] ??= [];
            $fulfillmentsByOrder[$orderId][] = $this->normalizeFulfillment($row);
        }

        return $fulfillmentsByOrder;
    }

    private function buildFallbackFulfillments(array $order, array $items): array
    {
        if ($items === []) {
            return [];
        }

        $grouped = [];

        foreach ($items as $item) {
            $sellerId = (int) ($item['seller_id'] ?? 0);
            $grouped[$sellerId][] = $item;
        }

        $fulfillments = [];

        foreach ($grouped as $sellerId => $sellerItems) {
            $fulfillments[] = $this->buildFallbackFulfillment($order, $sellerId, $sellerItems);
        }

        return $fulfillments;
    }

    private function buildFallbackFulfillment(array $order, int $sellerId, array $items): array
    {
        $subtotal = array_sum(array_map(static fn (array $item): float => (float) ($item['line_total'] ?? 0), $items));
        $quantity = array_sum(array_map(static fn (array $item): int => (int) ($item['quantity'] ?? 0), $items));
        $sellerName = trim((string) ($items[0]['seller_name'] ?? '')) ?: 'Marketplace seller';

        return [
            'id' => 0,
            'order_id' => (int) $order['id'],
            'order_number' => (string) $order['order_number'],
            'order_status' => (string) $order['status'],
            'customer_id' => (int) $order['customer_id'],
            'customer_name' => '',
            'customer_email' => '',
            'seller_id' => $sellerId,
            'seller_name' => $sellerName,
            'status' => $this->fulfillmentStatusFromOrderStatus((string) $order['status']),
            'status_label' => delivery_status_label($this->fulfillmentStatusFromOrderStatus((string) $order['status'])),
            'seller_subtotal' => $subtotal,
            'seller_subtotal_formatted' => money($subtotal),
            'item_count' => count($items),
            'item_quantity' => $quantity,
            'courier_name' => null,
            'tracking_number' => null,
            'status_note' => null,
            'estimated_delivery_date' => null,
            'shipped_at' => null,
            'out_for_delivery_at' => null,
            'delivered_at' => null,
            'shipping_recipient' => (string) $order['shipping_recipient'],
            'shipping_line_1' => (string) $order['shipping_line_1'],
            'shipping_line_2' => $order['shipping_line_2'],
            'shipping_city' => (string) $order['shipping_city'],
            'shipping_state' => (string) $order['shipping_state'],
            'shipping_postal_code' => (string) $order['shipping_postal_code'],
            'shipping_country' => (string) $order['shipping_country'],
            'shipping_phone' => $order['shipping_phone'],
            'created_at' => (string) $order['created_at'],
            'updated_at' => (string) $order['updated_at'],
            'order_created_at' => (string) $order['created_at'],
            'items' => $items,
        ];
    }

    private function insertFulfillmentsForOrder(int $orderId, string $initialStatus, array $items): void
    {
        if (!$this->fulfillmentsTableAvailable()) {
            return;
        }

        $items = $this->ensureItemsHaveSellerIds($items);

        $grouped = [];

        foreach ($items as $item) {
            $sellerId = (int) ($item['seller_id'] ?? 0);

            if ($sellerId < 1) {
                continue;
            }

            $grouped[$sellerId] ??= [
                'seller_subtotal' => 0.0,
                'item_count' => 0,
                'item_quantity' => 0,
            ];
            $grouped[$sellerId]['seller_subtotal'] += (float) ($item['line_total'] ?? 0);
            $grouped[$sellerId]['item_count']++;
            $grouped[$sellerId]['item_quantity'] += (int) ($item['quantity'] ?? 0);
        }

        if ($grouped === []) {
            return;
        }

        $statement = $this->connection->prepare(
            <<<SQL
            INSERT INTO order_fulfillments (
                order_id,
                seller_id,
                status,
                seller_subtotal,
                item_count,
                item_quantity
            ) VALUES (
                :order_id,
                :seller_id,
                :status,
                :seller_subtotal,
                :item_count,
                :item_quantity
            )
            ON DUPLICATE KEY UPDATE
                status = VALUES(status),
                seller_subtotal = VALUES(seller_subtotal),
                item_count = VALUES(item_count),
                item_quantity = VALUES(item_quantity),
                updated_at = CURRENT_TIMESTAMP
            SQL
        );

        foreach ($grouped as $sellerId => $aggregate) {
            $statement->execute([
                'order_id' => $orderId,
                'seller_id' => $sellerId,
                'status' => $this->fulfillmentStatusFromOrderStatus($initialStatus),
                'seller_subtotal' => round((float) $aggregate['seller_subtotal'], 2),
                'item_count' => (int) $aggregate['item_count'],
                'item_quantity' => (int) $aggregate['item_quantity'],
            ]);
        }
    }

    private function ensureItemsHaveSellerIds(array $items): array
    {
        $missingProductIds = [];

        foreach ($items as $item) {
            if ((int) ($item['seller_id'] ?? 0) < 1) {
                $missingProductIds[] = (int) ($item['product_id'] ?? 0);
            }
        }

        $missingProductIds = array_values(array_unique(array_filter($missingProductIds)));

        if ($missingProductIds === []) {
            return $items;
        }

        $placeholders = implode(', ', array_fill(0, count($missingProductIds), '?'));
        $statement = $this->connection->prepare(
            "SELECT id, seller_id FROM products WHERE id IN ($placeholders)"
        );
        $statement->execute($missingProductIds);
        $sellerLookup = [];

        foreach ($statement->fetchAll() as $row) {
            $sellerLookup[(int) $row['id']] = (int) $row['seller_id'];
        }

        foreach ($items as &$item) {
            if ((int) ($item['seller_id'] ?? 0) > 0) {
                continue;
            }

            $productId = (int) ($item['product_id'] ?? 0);
            $item['seller_id'] = $sellerLookup[$productId] ?? 0;
        }
        unset($item);

        return $items;
    }

    private function listFallbackFulfillments(?int $sellerId = null): array
    {
        $sql = $this->fallbackFulfillmentSelectSql();
        $params = [];

        if ($sellerId !== null) {
            $sql .= ' WHERE p.seller_id = :seller_id';
            $params['seller_id'] = $sellerId;
        }

        $sql .= "\n" . <<<SQL
            GROUP BY
                o.id,
                o.order_number,
                o.status,
                o.customer_id,
                customer.id,
                customer.name,
                customer.email,
                p.seller_id,
                seller.id,
                seller.name,
                sp.store_name,
                o.shipping_recipient,
                o.shipping_line_1,
                o.shipping_line_2,
                o.shipping_city,
                o.shipping_state,
                o.shipping_postal_code,
                o.shipping_country,
                o.shipping_phone,
                o.created_at,
                o.updated_at
            ORDER BY o.updated_at DESC, o.created_at DESC, o.id DESC
            SQL;

        $statement = $this->connection->prepare($sql);
        $statement->execute($params);

        return array_map(
            fn (array $row): array => $this->normalizeFallbackFulfillment($row),
            $statement->fetchAll()
        );
    }

    private function findFallbackFulfillmentById(int $fulfillmentId): ?array
    {
        $decoded = $this->decodeSyntheticFulfillmentId($fulfillmentId);

        if ($decoded === null) {
            return null;
        }

        return $this->findFallbackFulfillment($decoded['order_id'], $decoded['seller_id']);
    }

    private function findFallbackFulfillment(int $orderId, int $sellerId): ?array
    {
        $statement = $this->connection->prepare(
            $this->fallbackFulfillmentSelectSql()
                . ' WHERE o.id = :order_id AND p.seller_id = :seller_id'
                . "\n" . <<<SQL
                    GROUP BY
                        o.id,
                        o.order_number,
                        o.status,
                        o.customer_id,
                        customer.id,
                        customer.name,
                        customer.email,
                        p.seller_id,
                        seller.id,
                        seller.name,
                        sp.store_name,
                        o.shipping_recipient,
                        o.shipping_line_1,
                        o.shipping_line_2,
                        o.shipping_city,
                        o.shipping_state,
                        o.shipping_postal_code,
                        o.shipping_country,
                        o.shipping_phone,
                        o.created_at,
                        o.updated_at
                    LIMIT 1
                    SQL
        );
        $statement->execute([
            'order_id' => $orderId,
            'seller_id' => $sellerId,
        ]);
        $row = $statement->fetch();

        return is_array($row) ? $this->normalizeFallbackFulfillment($row) : null;
    }

    private function fallbackFulfillmentSelectSql(): string
    {
        return <<<SQL
            SELECT
                o.id AS order_id,
                o.order_number,
                o.status AS order_status,
                o.customer_id,
                customer.name AS customer_name,
                customer.email AS customer_email,
                p.seller_id,
                COALESCE(sp.store_name, seller.name) AS seller_name,
                ROUND(COALESCE(SUM(oi.line_total), 0), 2) AS seller_subtotal,
                COUNT(oi.id) AS item_count,
                COALESCE(SUM(oi.quantity), 0) AS item_quantity,
                o.shipping_recipient,
                o.shipping_line_1,
                o.shipping_line_2,
                o.shipping_city,
                o.shipping_state,
                o.shipping_postal_code,
                o.shipping_country,
                o.shipping_phone,
                o.created_at,
                o.updated_at,
                o.created_at AS order_created_at
            FROM orders o
            INNER JOIN order_items oi
                ON oi.order_id = o.id
            INNER JOIN products p
                ON p.id = oi.product_id
            INNER JOIN users customer
                ON customer.id = o.customer_id
            INNER JOIN users seller
                ON seller.id = p.seller_id
            LEFT JOIN seller_profiles sp
                ON sp.user_id = seller.id
            SQL;
    }

    private function normalizeFallbackFulfillment(array $row): array
    {
        $normalized = $this->normalizeFulfillment([
            'id' => $this->encodeSyntheticFulfillmentId((int) $row['order_id'], (int) $row['seller_id']),
            'order_id' => $row['order_id'],
            'seller_id' => $row['seller_id'],
            'status' => $this->fulfillmentStatusFromOrderStatus((string) ($row['order_status'] ?? 'pending')),
            'seller_subtotal' => $row['seller_subtotal'],
            'item_count' => $row['item_count'],
            'item_quantity' => $row['item_quantity'],
            'courier_name' => null,
            'tracking_number' => null,
            'status_note' => null,
            'estimated_delivery_date' => null,
            'shipped_at' => null,
            'out_for_delivery_at' => null,
            'delivered_at' => null,
            'created_at' => $row['created_at'],
            'updated_at' => $row['updated_at'],
            'order_number' => $row['order_number'],
            'order_status' => $row['order_status'],
            'customer_id' => $row['customer_id'],
            'shipping_recipient' => $row['shipping_recipient'],
            'shipping_line_1' => $row['shipping_line_1'],
            'shipping_line_2' => $row['shipping_line_2'],
            'shipping_city' => $row['shipping_city'],
            'shipping_state' => $row['shipping_state'],
            'shipping_postal_code' => $row['shipping_postal_code'],
            'shipping_country' => $row['shipping_country'],
            'shipping_phone' => $row['shipping_phone'],
            'order_created_at' => $row['order_created_at'],
            'customer_name' => $row['customer_name'],
            'customer_email' => $row['customer_email'],
            'seller_name' => $row['seller_name'],
        ]);
        $normalized['items'] = $this->itemsForFulfillment((int) $normalized['order_id'], (int) $normalized['seller_id']);

        return $normalized;
    }

    private function encodeSyntheticFulfillmentId(int $orderId, int $sellerId): int
    {
        return -((($orderId * self::SYNTHETIC_FULFILLMENT_SCALE)) + $sellerId);
    }

    private function decodeSyntheticFulfillmentId(int $fulfillmentId): ?array
    {
        if ($fulfillmentId >= 0) {
            return null;
        }

        $encoded = abs($fulfillmentId);
        $sellerId = $encoded % self::SYNTHETIC_FULFILLMENT_SCALE;
        $orderId = intdiv($encoded, self::SYNTHETIC_FULFILLMENT_SCALE);

        if ($orderId < 1 || $sellerId < 1) {
            return null;
        }

        return [
            'order_id' => $orderId,
            'seller_id' => $sellerId,
        ];
    }

    private function findFulfillment(int $fulfillmentId, ?int $sellerId = null): ?array
    {
        if (!$this->fulfillmentsTableAvailable()) {
            return null;
        }

        $sql = $this->fulfillmentSelectSql() . ' WHERE f.id = :id';
        $params = ['id' => $fulfillmentId];

        if ($sellerId !== null) {
            $sql .= ' AND f.seller_id = :seller_id';
            $params['seller_id'] = $sellerId;
        }

        $sql .= ' LIMIT 1';

        $statement = $this->connection->prepare($sql);
        $statement->execute($params);
        $row = $statement->fetch();

        if (!is_array($row)) {
            return null;
        }

        $fulfillment = $this->normalizeFulfillment($row);
        $fulfillment['items'] = $this->itemsForFulfillment((int) $fulfillment['order_id'], (int) $fulfillment['seller_id']);

        return $fulfillment;
    }

    private function findFulfillmentRaw(int $fulfillmentId): ?array
    {
        $statement = $this->connection->prepare(
            'SELECT id, order_id, seller_id, status FROM order_fulfillments WHERE id = :id LIMIT 1'
        );
        $statement->execute(['id' => $fulfillmentId]);
        $row = $statement->fetch();

        return is_array($row) ? $row : null;
    }

    private function itemsForFulfillment(int $orderId, int $sellerId): array
    {
        $statement = $this->connection->prepare(
            <<<SQL
            SELECT
                oi.product_id,
                oi.product_name,
                oi.quantity,
                oi.unit_price,
                oi.line_total,
                p.slug,
                p.image_url,
                p.seller_id,
                COALESCE(sp.store_name, seller.name) AS seller_name
            FROM order_items oi
            INNER JOIN products p
                ON p.id = oi.product_id
            INNER JOIN users seller
                ON seller.id = p.seller_id
            LEFT JOIN seller_profiles sp
                ON sp.user_id = seller.id
            WHERE oi.order_id = :order_id
              AND p.seller_id = :seller_id
            ORDER BY oi.id ASC
            SQL
        );
        $statement->execute([
            'order_id' => $orderId,
            'seller_id' => $sellerId,
        ]);

        return array_map(
            static fn (array $row): array => [
                'product_id' => (int) $row['product_id'],
                'product_name' => (string) $row['product_name'],
                'slug' => (string) ($row['slug'] ?? ''),
                'image_url' => (string) ($row['image_url'] ?: '/assets/images/products/product-fallback.svg'),
                'seller_id' => (int) ($row['seller_id'] ?? 0),
                'seller_name' => trim((string) ($row['seller_name'] ?? '')) ?: 'Marketplace seller',
                'quantity' => (int) $row['quantity'],
                'unit_price' => (float) $row['unit_price'],
                'unit_price_formatted' => money((float) $row['unit_price']),
                'line_total' => (float) $row['line_total'],
                'line_total_formatted' => money((float) $row['line_total']),
            ],
            $statement->fetchAll()
        );
    }

    private function refreshOrderStatus(int $orderId): void
    {
        if (!$this->fulfillmentsTableAvailable()) {
            return;
        }

        $statement = $this->connection->prepare(
            'SELECT status FROM order_fulfillments WHERE order_id = :order_id'
        );
        $statement->execute(['order_id' => $orderId]);
        $statuses = array_map(
            static fn (array $row): string => (string) $row['status'],
            $statement->fetchAll()
        );

        if ($statuses === []) {
            return;
        }

        $orderStatus = $this->aggregateOrderStatus($statuses);
        $update = $this->connection->prepare(
            'UPDATE orders SET status = :status, updated_at = CURRENT_TIMESTAMP WHERE id = :id'
        );
        $update->execute([
            'id' => $orderId,
            'status' => $orderStatus,
        ]);
    }

    private function aggregateOrderStatus(array $statuses): string
    {
        $statuses = array_values(array_filter(array_map('strval', $statuses)));

        if ($statuses === []) {
            return 'pending';
        }

        $unique = array_values(array_unique($statuses));

        if (count(array_diff($unique, ['cancelled'])) === 0) {
            return 'cancelled';
        }

        if (count(array_diff($unique, ['delivered', 'cancelled'])) === 0 && in_array('delivered', $unique, true)) {
            return 'delivered';
        }

        if (count(array_intersect($unique, ['shipped', 'out_for_delivery', 'delivered'])) > 0) {
            return 'shipped';
        }

        if (count(array_intersect($unique, ['paid', 'processing', 'packed'])) > 0) {
            return 'paid';
        }

        return 'pending';
    }

    private function fulfillmentSelectSql(): string
    {
        return <<<SQL
            SELECT
                f.id,
                f.order_id,
                f.seller_id,
                f.status,
                f.seller_subtotal,
                f.item_count,
                f.item_quantity,
                f.courier_name,
                f.tracking_number,
                f.status_note,
                f.estimated_delivery_date,
                f.shipped_at,
                f.out_for_delivery_at,
                f.delivered_at,
                f.created_at,
                f.updated_at,
                o.order_number,
                o.status AS order_status,
                o.customer_id,
                o.shipping_recipient,
                o.shipping_line_1,
                o.shipping_line_2,
                o.shipping_city,
                o.shipping_state,
                o.shipping_postal_code,
                o.shipping_country,
                o.shipping_phone,
                o.created_at AS order_created_at,
                customer.name AS customer_name,
                customer.email AS customer_email,
                COALESCE(sp.store_name, seller.name) AS seller_name
            FROM order_fulfillments f
            INNER JOIN orders o
                ON o.id = f.order_id
            INNER JOIN users customer
                ON customer.id = o.customer_id
            INNER JOIN users seller
                ON seller.id = f.seller_id
            LEFT JOIN seller_profiles sp
                ON sp.user_id = seller.id
            SQL;
    }

    private function fulfillmentsTableAvailable(): bool
    {
        if ($this->fulfillmentsTableAvailable !== null) {
            return $this->fulfillmentsTableAvailable;
        }

        try {
            $statement = $this->connection->query(
                <<<SQL
                SELECT COUNT(*)
                FROM information_schema.tables
                WHERE table_schema = DATABASE()
                  AND table_name = 'order_fulfillments'
                SQL
            );

            $this->fulfillmentsTableAvailable = (int) $statement->fetchColumn() > 0;
        } catch (PDOException) {
            $this->fulfillmentsTableAvailable = false;
        }

        return $this->fulfillmentsTableAvailable;
    }

    private function fulfillmentStatusFromOrderStatus(string $orderStatus): string
    {
        return match ($orderStatus) {
            'pending' => 'pending',
            'paid' => 'paid',
            'shipped' => 'shipped',
            'delivered' => 'delivered',
            'cancelled' => 'cancelled',
            default => 'pending',
        };
    }

    private function normalizeFulfillment(array $row): array
    {
        $status = (string) $row['status'];
        $sellerSubtotal = (float) $row['seller_subtotal'];

        return [
            'id' => (int) $row['id'],
            'order_id' => (int) $row['order_id'],
            'order_number' => (string) ($row['order_number'] ?? ''),
            'order_status' => (string) ($row['order_status'] ?? ''),
            'customer_id' => (int) ($row['customer_id'] ?? 0),
            'customer_name' => trim((string) ($row['customer_name'] ?? '')),
            'customer_email' => trim((string) ($row['customer_email'] ?? '')),
            'seller_id' => (int) $row['seller_id'],
            'seller_name' => trim((string) ($row['seller_name'] ?? '')) ?: 'Marketplace seller',
            'status' => $status,
            'status_label' => delivery_status_label($status),
            'seller_subtotal' => $sellerSubtotal,
            'seller_subtotal_formatted' => money($sellerSubtotal),
            'item_count' => (int) $row['item_count'],
            'item_quantity' => (int) $row['item_quantity'],
            'courier_name' => $row['courier_name'] !== null ? (string) $row['courier_name'] : null,
            'tracking_number' => $row['tracking_number'] !== null ? (string) $row['tracking_number'] : null,
            'status_note' => $row['status_note'] !== null ? (string) $row['status_note'] : null,
            'estimated_delivery_date' => $row['estimated_delivery_date'] !== null ? (string) $row['estimated_delivery_date'] : null,
            'estimated_delivery_date_formatted' => !empty($row['estimated_delivery_date']) ? date('d M Y', strtotime((string) $row['estimated_delivery_date'])) : null,
            'shipped_at' => $row['shipped_at'] !== null ? (string) $row['shipped_at'] : null,
            'out_for_delivery_at' => $row['out_for_delivery_at'] !== null ? (string) $row['out_for_delivery_at'] : null,
            'delivered_at' => $row['delivered_at'] !== null ? (string) $row['delivered_at'] : null,
            'shipping_recipient' => (string) ($row['shipping_recipient'] ?? ''),
            'shipping_line_1' => (string) ($row['shipping_line_1'] ?? ''),
            'shipping_line_2' => $row['shipping_line_2'] ?? null,
            'shipping_city' => (string) ($row['shipping_city'] ?? ''),
            'shipping_state' => (string) ($row['shipping_state'] ?? ''),
            'shipping_postal_code' => (string) ($row['shipping_postal_code'] ?? ''),
            'shipping_country' => (string) ($row['shipping_country'] ?? ''),
            'shipping_phone' => $row['shipping_phone'] ?? null,
            'created_at' => (string) ($row['created_at'] ?? ''),
            'updated_at' => (string) ($row['updated_at'] ?? ''),
            'order_created_at' => (string) ($row['order_created_at'] ?? ($row['created_at'] ?? '')),
            'is_editable' => (int) $row['id'] > 0,
        ];
    }

    private function normalizeOrder(array $row): array
    {
        $subtotal = (float) $row['subtotal'];
        $shippingFee = (float) $row['shipping_fee'];
        $total = (float) $row['total'];
        $discountAmount = max(0, round(($subtotal + $shippingFee) - $total, 2));

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
            'subtotal' => $subtotal,
            'subtotal_formatted' => money($subtotal),
            'shipping_fee' => $shippingFee,
            'shipping_fee_formatted' => $shippingFee > 0 ? money($shippingFee) : 'FREE',
            'discount_amount' => $discountAmount,
            'discount_amount_formatted' => money($discountAmount),
            'total' => $total,
            'total_formatted' => money($total),
            'created_at' => (string) $row['created_at'],
            'updated_at' => (string) $row['updated_at'],
        ];
    }
}
