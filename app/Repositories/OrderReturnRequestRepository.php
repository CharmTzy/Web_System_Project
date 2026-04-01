<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;
use PDOException;
use RuntimeException;

final class OrderReturnRequestRepository
{
    private ?bool $tableAvailable = null;

    public function __construct(private readonly PDO $connection)
    {
    }

    public function isAvailable(): bool
    {
        if ($this->tableAvailable !== null) {
            return $this->tableAvailable;
        }

        try {
            $statement = $this->connection->query(
                <<<SQL
                SELECT COUNT(*)
                FROM information_schema.tables
                WHERE table_schema = DATABASE()
                  AND table_name = 'order_return_requests'
                SQL
            );

            $this->tableAvailable = (int) $statement->fetchColumn() > 0;
        } catch (PDOException) {
            $this->tableAvailable = false;
        }

        return $this->tableAvailable;
    }

    public function listForCustomer(int $customerId): array
    {
        if (!$this->isAvailable()) {
            return [];
        }

        return $this->listBy(
            'WHERE r.customer_id = :customer_id',
            ['customer_id' => $customerId]
        );
    }

    public function listForSeller(int $sellerId): array
    {
        if (!$this->isAvailable()) {
            return [];
        }

        return $this->listBy(
            'WHERE r.seller_id = :seller_id',
            ['seller_id' => $sellerId]
        );
    }

    public function listForAdmin(): array
    {
        if (!$this->isAvailable()) {
            return [];
        }

        return $this->listBy();
    }

    public function findForCustomer(int $requestId, int $customerId): ?array
    {
        if (!$this->isAvailable()) {
            return null;
        }

        return $this->findBy(
            'WHERE r.id = :id AND r.customer_id = :customer_id LIMIT 1',
            ['id' => $requestId, 'customer_id' => $customerId]
        );
    }

    public function findForSeller(int $requestId, int $sellerId): ?array
    {
        if (!$this->isAvailable()) {
            return null;
        }

        return $this->findBy(
            'WHERE r.id = :id AND r.seller_id = :seller_id LIMIT 1',
            ['id' => $requestId, 'seller_id' => $sellerId]
        );
    }

    public function findForAdmin(int $requestId): ?array
    {
        if (!$this->isAvailable()) {
            return null;
        }

        return $this->findBy(
            'WHERE r.id = :id LIMIT 1',
            ['id' => $requestId]
        );
    }

    public function findLatestForCustomerPackage(int $customerId, int $orderId, int $sellerId): ?array
    {
        if (!$this->isAvailable()) {
            return null;
        }

        return $this->findBy(
            <<<SQL
            WHERE r.customer_id = :customer_id
              AND r.order_id = :order_id
              AND r.seller_id = :seller_id
            ORDER BY r.created_at DESC, r.id DESC
            LIMIT 1
            SQL,
            [
                'customer_id' => $customerId,
                'order_id' => $orderId,
                'seller_id' => $sellerId,
            ]
        );
    }

    public function findActiveForCustomerPackage(int $customerId, int $orderId, int $sellerId): ?array
    {
        if (!$this->isAvailable()) {
            return null;
        }

        return $this->findBy(
            <<<SQL
            WHERE r.customer_id = :customer_id
              AND r.order_id = :order_id
              AND r.seller_id = :seller_id
              AND r.status IN ('pending', 'approved', 'received')
            ORDER BY r.created_at DESC, r.id DESC
            LIMIT 1
            SQL,
            [
                'customer_id' => $customerId,
                'order_id' => $orderId,
                'seller_id' => $sellerId,
            ]
        );
    }

    public function create(array $attributes): array
    {
        if (!$this->isAvailable()) {
            throw new RuntimeException('Return requests are not available until the latest database migration is applied.');
        }

        $statement = $this->connection->prepare(
            <<<SQL
            INSERT INTO order_return_requests (
                order_id,
                seller_id,
                customer_id,
                request_type,
                status,
                reason_code,
                reason_details,
                seller_response,
                reviewed_at,
                resolved_at
            ) VALUES (
                :order_id,
                :seller_id,
                :customer_id,
                :request_type,
                :status,
                :reason_code,
                :reason_details,
                :seller_response,
                :reviewed_at,
                :resolved_at
            )
            SQL
        );
        $statement->execute([
            'order_id' => $attributes['order_id'],
            'seller_id' => $attributes['seller_id'],
            'customer_id' => $attributes['customer_id'],
            'request_type' => $attributes['request_type'],
            'status' => $attributes['status'] ?? 'pending',
            'reason_code' => $attributes['reason_code'],
            'reason_details' => $attributes['reason_details'] ?? null,
            'seller_response' => $attributes['seller_response'] ?? null,
            'reviewed_at' => $attributes['reviewed_at'] ?? null,
            'resolved_at' => $attributes['resolved_at'] ?? null,
        ]);

        return $this->findForAdmin((int) $this->connection->lastInsertId()) ?? [];
    }

    public function update(int $requestId, array $attributes): ?array
    {
        if (!$this->isAvailable()) {
            throw new RuntimeException('Return requests are not available until the latest database migration is applied.');
        }

        $fields = [];
        $params = ['id' => $requestId];

        foreach ([
            'status',
            'reason_code',
            'reason_details',
            'seller_response',
            'reviewed_at',
            'resolved_at',
        ] as $column) {
            if (array_key_exists($column, $attributes)) {
                $fields[] = $column . ' = :' . $column;
                $params[$column] = $attributes[$column];
            }
        }

        if ($fields === []) {
            return $this->findForAdmin($requestId);
        }

        $fields[] = 'updated_at = CURRENT_TIMESTAMP';

        $statement = $this->connection->prepare(
            'UPDATE order_return_requests SET ' . implode(', ', $fields) . ' WHERE id = :id'
        );
        $statement->execute($params);

        return $this->findForAdmin($requestId);
    }

    private function listBy(string $whereClause = '', array $params = []): array
    {
        $statement = $this->connection->prepare(
            $this->selectSql()
            . ($whereClause !== '' ? "\n" . $whereClause : '')
            . "\nORDER BY r.created_at DESC, r.id DESC"
        );
        $statement->execute($params);

        return array_map([$this, 'normalizeRequest'], $statement->fetchAll());
    }

    private function findBy(string $whereClause, array $params): ?array
    {
        $statement = $this->connection->prepare($this->selectSql() . "\n" . $whereClause);
        $statement->execute($params);
        $row = $statement->fetch();

        return is_array($row) ? $this->normalizeRequest($row) : null;
    }

    private function selectSql(): string
    {
        return <<<SQL
            SELECT
                r.id,
                r.order_id,
                r.seller_id,
                r.customer_id,
                r.request_type,
                r.status,
                r.reason_code,
                r.reason_details,
                r.seller_response,
                r.reviewed_at,
                r.resolved_at,
                r.created_at,
                r.updated_at,
                o.order_number,
                o.status AS order_status,
                customer.name AS customer_name,
                customer.email AS customer_email,
                COALESCE(sp.store_name, seller.name) AS seller_name
            FROM order_return_requests r
            INNER JOIN orders o
                ON o.id = r.order_id
            INNER JOIN users customer
                ON customer.id = r.customer_id
            INNER JOIN users seller
                ON seller.id = r.seller_id
            LEFT JOIN seller_profiles sp
                ON sp.user_id = seller.id
            SQL;
    }

    private function normalizeRequest(array $row): array
    {
        return [
            'id' => (int) $row['id'],
            'order_id' => (int) $row['order_id'],
            'order_number' => (string) ($row['order_number'] ?? ''),
            'order_status' => (string) ($row['order_status'] ?? ''),
            'seller_id' => (int) $row['seller_id'],
            'seller_name' => trim((string) ($row['seller_name'] ?? '')) ?: 'Marketplace seller',
            'customer_id' => (int) $row['customer_id'],
            'customer_name' => trim((string) ($row['customer_name'] ?? '')),
            'customer_email' => trim((string) ($row['customer_email'] ?? '')),
            'request_type' => (string) $row['request_type'],
            'status' => (string) $row['status'],
            'reason_code' => (string) ($row['reason_code'] ?? ''),
            'reason_details' => $row['reason_details'] !== null ? trim((string) $row['reason_details']) : null,
            'seller_response' => $row['seller_response'] !== null ? trim((string) $row['seller_response']) : null,
            'reviewed_at' => $row['reviewed_at'] !== null ? (string) $row['reviewed_at'] : null,
            'resolved_at' => $row['resolved_at'] !== null ? (string) $row['resolved_at'] : null,
            'created_at' => (string) ($row['created_at'] ?? ''),
            'updated_at' => (string) ($row['updated_at'] ?? ''),
        ];
    }
}
