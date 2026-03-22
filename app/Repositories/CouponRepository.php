<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;

final class CouponRepository implements CouponRepositoryInterface
{
    public function __construct(private readonly PDO $connection)
    {
    }

    public function activeCoupons(): array
    {
        $statement = $this->connection->query(
            <<<SQL
            SELECT
                c.id,
                c.code,
                c.title,
                c.description,
                c.coupon_type,
                c.discount_type,
                c.discount_value,
                c.minimum_spend,
                c.starts_at,
                c.ends_at,
                c.is_featured,
                sp.store_name AS seller_name
            FROM coupons c
            LEFT JOIN seller_profiles sp
                ON sp.user_id = c.seller_id
            WHERE c.is_active = 1
                AND c.starts_at <= NOW()
                AND c.ends_at >= NOW()
            ORDER BY c.is_featured DESC, c.ends_at ASC, c.id ASC
            SQL
        );

        return array_map(
            static fn (array $row): array => [
                'id' => (int) $row['id'],
                'code' => (string) $row['code'],
                'title' => (string) $row['title'],
                'description' => (string) $row['description'],
                'coupon_type' => (string) $row['coupon_type'],
                'discount_type' => (string) $row['discount_type'],
                'discount_value' => (float) $row['discount_value'],
                'minimum_spend' => $row['minimum_spend'] !== null ? (float) $row['minimum_spend'] : null,
                'seller_name' => $row['seller_name'] !== null ? (string) $row['seller_name'] : null,
                'starts_at' => (string) $row['starts_at'],
                'ends_at' => (string) $row['ends_at'],
                'is_featured' => (bool) $row['is_featured'],
            ],
            $statement->fetchAll()
        );
    }

    public function listAll(array $filters = []): array
    {
        $conditions = [];
        $params = [];

        if (($filters['coupon_type'] ?? '') !== '') {
            $conditions[] = 'c.coupon_type = :coupon_type';
            $params['coupon_type'] = $filters['coupon_type'];
        }

        if (($filters['search'] ?? '') !== '') {
            $conditions[] = '(c.code LIKE :search_code OR c.title LIKE :search_title OR c.description LIKE :search_description OR sp.store_name LIKE :search_store)';
            $searchPattern = '%' . $filters['search'] . '%';
            $params['search_code'] = $searchPattern;
            $params['search_title'] = $searchPattern;
            $params['search_description'] = $searchPattern;
            $params['search_store'] = $searchPattern;
        }

        $sql = <<<SQL
            SELECT
                c.id,
                c.code,
                c.title,
                c.description,
                c.coupon_type,
                c.discount_type,
                c.discount_value,
                c.minimum_spend,
                c.seller_id,
                c.starts_at,
                c.ends_at,
                c.is_featured,
                c.is_active,
                c.created_at,
                sp.store_name AS seller_name
            FROM coupons c
            LEFT JOIN seller_profiles sp
                ON sp.user_id = c.seller_id
            SQL;

        if ($conditions !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }

        $sql .= ' ORDER BY c.is_featured DESC, c.ends_at ASC, c.id DESC';

        $statement = $this->connection->prepare($sql);
        $statement->execute($params);

        return array_map([$this, 'normalizeCoupon'], $statement->fetchAll());
    }

    public function findById(int $id): ?array
    {
        $statement = $this->connection->prepare(
            <<<SQL
            SELECT
                c.id,
                c.code,
                c.title,
                c.description,
                c.coupon_type,
                c.discount_type,
                c.discount_value,
                c.minimum_spend,
                c.seller_id,
                c.starts_at,
                c.ends_at,
                c.is_featured,
                c.is_active,
                c.created_at,
                sp.store_name AS seller_name
            FROM coupons c
            LEFT JOIN seller_profiles sp
                ON sp.user_id = c.seller_id
            WHERE c.id = :id
            LIMIT 1
            SQL
        );
        $statement->execute(['id' => $id]);
        $row = $statement->fetch();

        return is_array($row) ? $this->normalizeCoupon($row) : null;
    }

    public function create(array $data): int
    {
        $statement = $this->connection->prepare(
            <<<SQL
            INSERT INTO coupons (
                code,
                title,
                description,
                coupon_type,
                discount_type,
                discount_value,
                minimum_spend,
                seller_id,
                starts_at,
                ends_at,
                is_featured,
                is_active
            ) VALUES (
                :code,
                :title,
                :description,
                :coupon_type,
                :discount_type,
                :discount_value,
                :minimum_spend,
                :seller_id,
                :starts_at,
                :ends_at,
                :is_featured,
                :is_active
            )
            SQL
        );
        $statement->execute([
            'code' => $data['code'],
            'title' => $data['title'],
            'description' => $data['description'],
            'coupon_type' => $data['coupon_type'],
            'discount_type' => $data['discount_type'],
            'discount_value' => $data['discount_value'],
            'minimum_spend' => $data['minimum_spend'],
            'seller_id' => $data['seller_id'],
            'starts_at' => $data['starts_at'],
            'ends_at' => $data['ends_at'],
            'is_featured' => $data['is_featured'],
            'is_active' => $data['is_active'],
        ]);

        return (int) $this->connection->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $statement = $this->connection->prepare(
            <<<SQL
            UPDATE coupons
            SET
                code = :code,
                title = :title,
                description = :description,
                coupon_type = :coupon_type,
                discount_type = :discount_type,
                discount_value = :discount_value,
                minimum_spend = :minimum_spend,
                seller_id = :seller_id,
                starts_at = :starts_at,
                ends_at = :ends_at,
                is_featured = :is_featured,
                is_active = :is_active
            WHERE id = :id
            SQL
        );

        return $statement->execute([
            'id' => $id,
            'code' => $data['code'],
            'title' => $data['title'],
            'description' => $data['description'],
            'coupon_type' => $data['coupon_type'],
            'discount_type' => $data['discount_type'],
            'discount_value' => $data['discount_value'],
            'minimum_spend' => $data['minimum_spend'],
            'seller_id' => $data['seller_id'],
            'starts_at' => $data['starts_at'],
            'ends_at' => $data['ends_at'],
            'is_featured' => $data['is_featured'],
            'is_active' => $data['is_active'],
        ]);
    }

    public function delete(int $id): bool
    {
        $statement = $this->connection->prepare('DELETE FROM coupons WHERE id = :id');

        return $statement->execute(['id' => $id]);
    }

    private function normalizeCoupon(array $row): array
    {
        return [
            'id' => (int) $row['id'],
            'code' => (string) $row['code'],
            'title' => (string) $row['title'],
            'description' => (string) $row['description'],
            'coupon_type' => (string) $row['coupon_type'],
            'discount_type' => (string) $row['discount_type'],
            'discount_value' => (float) $row['discount_value'],
            'minimum_spend' => $row['minimum_spend'] !== null ? (float) $row['minimum_spend'] : null,
            'seller_id' => $row['seller_id'] !== null ? (int) $row['seller_id'] : null,
            'seller_name' => $row['seller_name'] !== null ? (string) $row['seller_name'] : null,
            'starts_at' => (string) $row['starts_at'],
            'ends_at' => (string) $row['ends_at'],
            'is_featured' => (bool) $row['is_featured'],
            'is_active' => (bool) ($row['is_active'] ?? true),
            'created_at' => (string) ($row['created_at'] ?? ''),
        ];
    }
}
