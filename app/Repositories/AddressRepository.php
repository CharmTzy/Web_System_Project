<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class AddressRepository
{
    public function __construct(private readonly PDO $connection)
    {
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->connection->prepare(
            'SELECT * FROM addresses WHERE id = :id LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return is_array($row) ? $this->normalize($row) : null;
    }

    public function listByUser(int $userId): array
    {
        $stmt = $this->connection->prepare(
            'SELECT * FROM addresses WHERE user_id = :user_id ORDER BY is_default DESC, created_at DESC'
        );
        $stmt->execute(['user_id' => $userId]);

        return array_map(fn (array $row) => $this->normalize($row), $stmt->fetchAll());
    }

    public function findDetailedById(int $id): ?array
    {
        $stmt = $this->connection->prepare(
            <<<SQL
            SELECT
                a.*,
                u.name AS user_name,
                u.email AS user_email
            FROM addresses a
            INNER JOIN users u
                ON u.id = a.user_id
            WHERE a.id = :id
            LIMIT 1
            SQL
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return is_array($row) ? $this->normalizeDetailed($row) : null;
    }

    public function listAll(array $filters = []): array
    {
        $conditions = [];
        $params = [];

        if (($filters['user_id'] ?? 0) > 0) {
            $conditions[] = 'a.user_id = :user_id';
            $params['user_id'] = (int) $filters['user_id'];
        }

        if (($filters['search'] ?? '') !== '') {
            $conditions[] = '(u.name LIKE :search_name OR u.email LIKE :search_email OR a.recipient LIKE :search_recipient OR a.line_1 LIKE :search_line)';
            $searchPattern = '%' . $filters['search'] . '%';
            $params['search_name'] = $searchPattern;
            $params['search_email'] = $searchPattern;
            $params['search_recipient'] = $searchPattern;
            $params['search_line'] = $searchPattern;
        }

        $sql = <<<SQL
            SELECT
                a.*,
                u.name AS user_name,
                u.email AS user_email
            FROM addresses a
            INNER JOIN users u
                ON u.id = a.user_id
            SQL;

        if ($conditions !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }

        $sql .= ' ORDER BY a.is_default DESC, a.created_at DESC';

        $stmt = $this->connection->prepare($sql);
        $stmt->execute($params);

        return array_map(fn (array $row) => $this->normalizeDetailed($row), $stmt->fetchAll());
    }

    public function countByUser(int $userId): int
    {
        $stmt = $this->connection->prepare(
            'SELECT COUNT(*) FROM addresses WHERE user_id = :user_id'
        );
        $stmt->execute(['user_id' => $userId]);

        return (int) $stmt->fetchColumn();
    }

    public function create(array $data): int
    {
        $stmt = $this->connection->prepare(
            'INSERT INTO addresses (user_id, label, recipient, line_1, line_2, city, state, postal_code, country, phone, is_default)
             VALUES (:user_id, :label, :recipient, :line_1, :line_2, :city, :state, :postal_code, :country, :phone, :is_default)'
        );

        $stmt->execute([
            'user_id' => $data['user_id'],
            'label' => $data['label'] ?? 'Home',
            'recipient' => $data['recipient'],
            'line_1' => $data['line_1'],
            'line_2' => $data['line_2'] ?? null,
            'city' => $data['city'],
            'state' => $data['state'],
            'postal_code' => $data['postal_code'],
            'country' => $data['country'] ?? 'Singapore',
            'phone' => $data['phone'] ?? null,
            'is_default' => (int) ($data['is_default'] ?? 0),
        ]);

        return (int) $this->connection->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $fields = [];
        $params = ['id' => $id];

        foreach (['label', 'recipient', 'line_1', 'line_2', 'city', 'state', 'postal_code', 'country', 'phone', 'is_default'] as $col) {
            if (array_key_exists($col, $data)) {
                $fields[] = "$col = :$col";
                $params[$col] = $data[$col];
            }
        }

        if ($fields === []) {
            return false;
        }

        $stmt = $this->connection->prepare(
            'UPDATE addresses SET ' . implode(', ', $fields) . ' WHERE id = :id'
        );

        return $stmt->execute($params);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->connection->prepare('DELETE FROM addresses WHERE id = :id');

        return $stmt->execute(['id' => $id]);
    }

    public function clearDefault(int $userId): bool
    {
        $stmt = $this->connection->prepare(
            'UPDATE addresses SET is_default = 0 WHERE user_id = :user_id'
        );

        return $stmt->execute(['user_id' => $userId]);
    }

    public function setDefault(int $id, int $userId): bool
    {
        $this->clearDefault($userId);

        return $this->update($id, ['is_default' => 1]);
    }

    private function normalize(array $row): array
    {
        return [
            'id' => (int) $row['id'],
            'user_id' => (int) $row['user_id'],
            'label' => (string) $row['label'],
            'recipient' => (string) $row['recipient'],
            'line_1' => (string) $row['line_1'],
            'line_2' => $row['line_2'],
            'city' => (string) $row['city'],
            'state' => (string) $row['state'],
            'postal_code' => (string) $row['postal_code'],
            'country' => (string) $row['country'],
            'phone' => $row['phone'],
            'is_default' => (bool) $row['is_default'],
            'created_at' => (string) $row['created_at'],
            'updated_at' => (string) $row['updated_at'],
        ];
    }

    private function normalizeDetailed(array $row): array
    {
        return $this->normalize($row) + [
            'user_name' => (string) ($row['user_name'] ?? ''),
            'user_email' => (string) ($row['user_email'] ?? ''),
        ];
    }
}
