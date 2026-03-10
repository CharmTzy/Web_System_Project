<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class UserRepository
{
    public function __construct(private readonly PDO $connection)
    {
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->connection->prepare(
            'SELECT id, name, email, phone, avatar_url, role, is_active, created_at, updated_at FROM users WHERE id = :id LIMIT 1'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return is_array($row) ? $this->normalize($row) : null;
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->connection->prepare(
            'SELECT id, name, email, phone, avatar_url, password_hash, role, is_active, created_at, updated_at FROM users WHERE email = :email LIMIT 1'
        );
        $stmt->execute(['email' => $email]);
        $row = $stmt->fetch();

        return is_array($row) ? $this->normalize($row, includePassword: true) : null;
    }

    public function emailExists(string $email, ?int $excludeId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM users WHERE email = :email';
        $params = ['email' => $email];

        if ($excludeId !== null) {
            $sql .= ' AND id != :exclude_id';
            $params['exclude_id'] = $excludeId;
        }

        $stmt = $this->connection->prepare($sql);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn() > 0;
    }

    public function create(array $data): int
    {
        $stmt = $this->connection->prepare(
            'INSERT INTO users (name, email, phone, password_hash, role, is_active) VALUES (:name, :email, :phone, :password_hash, :role, :is_active)'
        );

        $stmt->execute([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'password_hash' => $data['password_hash'],
            'role' => $data['role'],
            'is_active' => $data['is_active'] ?? 1,
        ]);

        return (int) $this->connection->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $fields = [];
        $params = ['id' => $id];

        foreach (['name', 'email', 'phone', 'avatar_url', 'password_hash', 'role', 'is_active'] as $col) {
            if (array_key_exists($col, $data)) {
                $fields[] = "$col = :$col";
                $params[$col] = $data[$col];
            }
        }

        if ($fields === []) {
            return false;
        }

        $stmt = $this->connection->prepare(
            'UPDATE users SET ' . implode(', ', $fields) . ' WHERE id = :id'
        );

        return $stmt->execute($params);
    }

    public function listAll(array $filters = []): array
    {
        $conditions = [];
        $params = [];

        if (!empty($filters['role'])) {
            $conditions[] = 'role = :role';
            $params['role'] = $filters['role'];
        }

        if (!empty($filters['search'])) {
            $conditions[] = '(name LIKE :search OR email LIKE :search)';
            $params['search'] = '%' . $filters['search'] . '%';
        }

        if (isset($filters['is_active'])) {
            $conditions[] = 'is_active = :is_active';
            $params['is_active'] = (int) $filters['is_active'];
        }

        $sql = 'SELECT id, name, email, phone, avatar_url, role, is_active, created_at, updated_at FROM users';

        if ($conditions !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }

        $sql .= ' ORDER BY created_at DESC';

        $stmt = $this->connection->prepare($sql);
        $stmt->execute($params);

        return array_map(fn (array $row) => $this->normalize($row), $stmt->fetchAll());
    }

    public function findSellerProfile(int $userId): ?array
    {
        $stmt = $this->connection->prepare(
            'SELECT user_id, store_name, store_slug, support_email, created_at, updated_at FROM seller_profiles WHERE user_id = :user_id LIMIT 1'
        );
        $stmt->execute(['user_id' => $userId]);
        $row = $stmt->fetch();

        return is_array($row) ? $row : null;
    }

    public function upsertSellerProfile(int $userId, array $data): bool
    {
        $stmt = $this->connection->prepare(
            'INSERT INTO seller_profiles (user_id, store_name, store_slug, support_email)
             VALUES (:user_id, :store_name, :store_slug, :support_email)
             ON DUPLICATE KEY UPDATE store_name = VALUES(store_name), store_slug = VALUES(store_slug), support_email = VALUES(support_email)'
        );

        return $stmt->execute([
            'user_id' => $userId,
            'store_name' => $data['store_name'],
            'store_slug' => $data['store_slug'],
            'support_email' => $data['support_email'] ?? null,
        ]);
    }

    private function normalize(array $row, bool $includePassword = false): array
    {
        $user = [
            'id' => (int) $row['id'],
            'name' => (string) $row['name'],
            'email' => (string) $row['email'],
            'phone' => $row['phone'] ?? null,
            'avatar_url' => $row['avatar_url'] ?? null,
            'role' => (string) $row['role'],
            'is_active' => (bool) ($row['is_active'] ?? true),
            'created_at' => (string) $row['created_at'],
            'updated_at' => (string) $row['updated_at'],
        ];

        if ($includePassword && isset($row['password_hash'])) {
            $user['password_hash'] = (string) $row['password_hash'];
        }

        return $user;
    }
}
