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
}
