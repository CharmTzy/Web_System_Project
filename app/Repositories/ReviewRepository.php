<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;
use PDOException;

final class ReviewRepository
{
    public function __construct(private readonly PDO $connection)
    {
    }

    public function listVisibleByProduct(int $productId): array
    {
        try {
            $statement = $this->connection->prepare(
                <<<SQL
                SELECT
                    pr.*,
                    u.name AS user_name
                FROM product_reviews pr
                INNER JOIN users u
                    ON u.id = pr.user_id
                WHERE pr.product_id = :product_id
                  AND pr.is_visible = 1
                ORDER BY pr.updated_at DESC, pr.id DESC
                SQL
            );
            $statement->execute(['product_id' => $productId]);

            return array_map(fn (array $row): array => $this->normalize($row), $statement->fetchAll());
        } catch (PDOException) {
            return [];
        }
    }

    public function findByProductAndUser(int $productId, int $userId): ?array
    {
        try {
            $statement = $this->connection->prepare(
                <<<SQL
                SELECT
                    pr.*,
                    u.name AS user_name
                FROM product_reviews pr
                INNER JOIN users u
                    ON u.id = pr.user_id
                WHERE pr.product_id = :product_id
                  AND pr.user_id = :user_id
                LIMIT 1
                SQL
            );
            $statement->execute([
                'product_id' => $productId,
                'user_id' => $userId,
            ]);
            $row = $statement->fetch();

            return is_array($row) ? $this->normalize($row) : null;
        } catch (PDOException) {
            return null;
        }
    }

    public function reviewedProductIdsForUser(int $userId, array $productIds): array
    {
        $productIds = array_values(array_filter(array_map('intval', $productIds)));

        if ($productIds === []) {
            return [];
        }

        try {
            $placeholders = implode(', ', array_fill(0, count($productIds), '?'));
            $statement = $this->connection->prepare(
                "SELECT product_id FROM product_reviews WHERE user_id = ? AND product_id IN ($placeholders)"
            );
            $statement->execute([$userId, ...$productIds]);

            return array_map('intval', $statement->fetchAll(PDO::FETCH_COLUMN));
        } catch (PDOException) {
            return [];
        }
    }

    public function save(int $productId, int $userId, array $data): void
    {
        $existing = $this->findByProductAndUser($productId, $userId);

        if ($existing === null) {
            $statement = $this->connection->prepare(
                <<<SQL
                INSERT INTO product_reviews (
                    product_id,
                    user_id,
                    rating,
                    title,
                    comment,
                    is_visible
                ) VALUES (
                    :product_id,
                    :user_id,
                    :rating,
                    :title,
                    :comment,
                    1
                )
                SQL
            );
            $statement->execute([
                'product_id' => $productId,
                'user_id' => $userId,
                'rating' => $data['rating'],
                'title' => $data['title'],
                'comment' => $data['comment'],
            ]);
        } else {
            $statement = $this->connection->prepare(
                <<<SQL
                UPDATE product_reviews
                SET rating = :rating,
                    title = :title,
                    comment = :comment,
                    is_visible = 1
                WHERE product_id = :product_id
                  AND user_id = :user_id
                SQL
            );
            $statement->execute([
                'product_id' => $productId,
                'user_id' => $userId,
                'rating' => $data['rating'],
                'title' => $data['title'],
                'comment' => $data['comment'],
            ]);
        }

        $this->refreshProductStats($productId);
    }

    public function delete(int $productId, int $userId): void
    {
        $statement = $this->connection->prepare(
            'DELETE FROM product_reviews WHERE product_id = :product_id AND user_id = :user_id'
        );
        $statement->execute([
            'product_id' => $productId,
            'user_id' => $userId,
        ]);

        $this->refreshProductStats($productId);
    }

    private function refreshProductStats(int $productId): void
    {
        $statement = $this->connection->prepare(
            <<<SQL
            UPDATE products p
            LEFT JOIN (
                SELECT
                    product_id,
                    ROUND(AVG(rating), 2) AS average_rating,
                    COUNT(*) AS review_count
                FROM product_reviews
                WHERE product_id = :stats_product_id
                  AND is_visible = 1
                GROUP BY product_id
            ) review_stats
                ON review_stats.product_id = p.id
            SET p.average_rating = COALESCE(review_stats.average_rating, 0),
                p.review_count = COALESCE(review_stats.review_count, 0)
            WHERE p.id = :product_id
            SQL
        );
        $statement->execute([
            'stats_product_id' => $productId,
            'product_id' => $productId,
        ]);
    }

    private function normalize(array $row): array
    {
        return [
            'id' => (int) $row['id'],
            'product_id' => (int) $row['product_id'],
            'user_id' => (int) $row['user_id'],
            'user_name' => (string) ($row['user_name'] ?? ''),
            'rating' => (int) $row['rating'],
            'title' => $row['title'] !== null ? (string) $row['title'] : null,
            'comment' => (string) $row['comment'],
            'is_visible' => (bool) $row['is_visible'],
            'created_at' => (string) $row['created_at'],
            'updated_at' => (string) $row['updated_at'],
        ];
    }
}
