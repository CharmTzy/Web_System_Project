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

            $reviews = array_map(fn (array $row): array => $this->normalize($row), $statement->fetchAll());

            foreach ($reviews as &$review) {
                $review['media'] = $this->listMediaByReviewId($review['id']);
            }

            return $reviews;
        } catch (PDOException) {
            return [];
        }
    }

    public function listAllByProduct(int $productId): array
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
                ORDER BY pr.updated_at DESC, pr.id DESC
                SQL
            );
            $statement->execute(['product_id' => $productId]);

            $reviews = array_map(fn (array $row): array => $this->normalize($row), $statement->fetchAll());

            foreach ($reviews as &$review) {
                $review['media'] = $this->listMediaByReviewId($review['id']);
            }

            return $reviews;
        } catch (PDOException) {
            return [];
        }
    }

    public function listAllForAdmin(): array
    {
        try {
            $statement = $this->connection->query(
                <<<SQL
                SELECT
                    pr.*,
                    u.name AS user_name,
                    p.name AS product_name,
                    p.slug AS product_slug,
                    p.seller_id
                FROM product_reviews pr
                INNER JOIN users u
                    ON u.id = pr.user_id
                INNER JOIN products p
                    ON p.id = pr.product_id
                ORDER BY pr.updated_at DESC, pr.id DESC
                SQL
            );

            $reviews = array_map(fn (array $row): array => $this->normalize($row), $statement->fetchAll());

            $reviewIds = array_map(
                static fn(array $review): int => (int) $review['id'],
                $reviews
            );

            $mediaByReviewId = $this->listMediaByReviewIds($reviewIds);

            foreach ($reviews as &$review) {
                $review['media'] = $mediaByReviewId[$review['id']] ?? [];
            }
            unset($review);

            return $reviews;
        } catch (PDOException) {
            return [];
        }
    }



    public function findById(int $reviewId): ?array
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
                WHERE pr.id = :review_id
                LIMIT 1
                SQL
            );
            $statement->execute(['review_id' => $reviewId]);
            $row = $statement->fetch();

            if (!is_array($row)) {
                return null;
            }

            $review = $this->normalize($row);
            $review['media'] = $this->listMediaByReviewId($review['id']);

            return $review;
        } catch (PDOException) {
            return null;
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

            if (!is_array($row)) {
                return null;
            }

            $review = $this->normalize($row);
            $review['media'] = $this->listMediaByReviewId($review['id']);

            return $review;
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

    public function reviewsByUserForProducts(int $userId, array $productIds): array
    {
        $productIds = array_values(array_filter(array_map('intval', $productIds)));

        if ($productIds === []) {
            return [];
        }

        try {
            $placeholders = implode(', ', array_fill(0, count($productIds), '?'));
            $statement = $this->connection->prepare(
                <<<SQL
                SELECT
                    pr.*,
                    u.name AS user_name
                FROM product_reviews pr
                INNER JOIN users u
                    ON u.id = pr.user_id
                WHERE pr.user_id = ?
                  AND pr.product_id IN ($placeholders)
                ORDER BY pr.updated_at DESC, pr.id DESC
                SQL
            );
            $statement->execute([$userId, ...$productIds]);

            $reviews = [];

            foreach ($statement->fetchAll() as $row) {
                if (!is_array($row)) {
                    continue;
                }

                $review = $this->normalize($row);
                $review['media'] = $this->listMediaByReviewId($review['id']);
                $reviews[(int) $review['product_id']] = $review;
            }

            return $reviews;
        } catch (PDOException) {
            return [];
        }
    }

    public function save(int $productId, int $userId, array $data): int
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
                    seller_reply,
                    is_visible,
                    is_flagged,
                    flagged_reason,
                    moderated_by
                ) VALUES (
                    :product_id,
                    :user_id,
                    :rating,
                    :title,
                    :comment,
                    NULL,
                    1,
                    0,
                    NULL,
                    NULL
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

            $reviewId = (int) $this->connection->lastInsertId();
        } else {
            $statement = $this->connection->prepare(
                <<<SQL
                UPDATE product_reviews
                SET rating = :rating,
                    title = :title,
                    comment = :comment,
                    is_visible = 1,
                    is_flagged = 0,
                    flagged_reason = NULL,
                    moderated_by = NULL
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

            $reviewId = (int) $existing['id'];
        }

        $this->refreshProductStats($productId);

        return $reviewId;
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

    public function deleteById(int $reviewId): ?int
    {
        $review = $this->findById($reviewId);

        if ($review === null) {
            return null;
        }

        $statement = $this->connection->prepare(
            'DELETE FROM product_reviews WHERE id = :id'
        );
        $statement->execute(['id' => $reviewId]);

        $this->refreshProductStats($review['product_id']);

        return (int) $review['product_id'];
    }

    public function replyAsSeller(int $reviewId, string $reply): bool
    {
        try {
            $statement = $this->connection->prepare(
                <<<SQL
                UPDATE product_reviews
                SET seller_reply = :seller_reply
                WHERE id = :review_id
                SQL
            );
            $statement->execute([
                'seller_reply' => $reply,
                'review_id' => $reviewId,
            ]);

            return $statement->rowCount() > 0;
        } catch (PDOException) {
            return false;
        }
    }

    public function flag(int $reviewId, int $flaggedBy, string $reason): bool
    {
        try {
            $this->connection->beginTransaction();

            $flagStatement = $this->connection->prepare(
                <<<SQL
                INSERT INTO product_review_flags (
                    review_id,
                    flagged_by,
                    reason
                ) VALUES (
                    :review_id,
                    :flagged_by,
                    :reason
                )
                SQL
            );
            $flagStatement->execute([
                'review_id' => $reviewId,
                'flagged_by' => $flaggedBy,
                'reason' => $reason,
            ]);

            $reviewStatement = $this->connection->prepare(
                <<<SQL
                UPDATE product_reviews
                SET is_flagged = 1,
                    flagged_reason = :flagged_reason
                WHERE id = :review_id
                SQL
            );
            $reviewStatement->execute([
                'flagged_reason' => $reason,
                'review_id' => $reviewId,
            ]);

            $this->connection->commit();

            return true;
        } catch (PDOException) {
            if ($this->connection->inTransaction()) {
                $this->connection->rollBack();
            }

            return false;
        }
    }

    public function hide(int $reviewId, ?int $moderatedBy = null, ?string $reason = null): bool
    {
        try {
            $review = $this->findById($reviewId);

            if ($review === null) {
                return false;
            }

            $statement = $this->connection->prepare(
                <<<SQL
                UPDATE product_reviews
                SET is_visible = 0,
                    is_flagged = 1,
                    hide_reason = :hide_reason,
                    moderated_by = :moderated_by
                WHERE id = :review_id
                SQL
            );
            $statement->execute([
                'hide_reason' => $reason,
                'moderated_by' => $moderatedBy,
                'review_id' => $reviewId,
            ]);

            $this->refreshProductStats((int) $review['product_id']);

            return true;
        } catch (PDOException) {
            return false;
        }
    }

    public function restore(int $reviewId): bool
    {
        try {
            $review = $this->findById($reviewId);

            if ($review === null) {
                return false;
            }

            $statement = $this->connection->prepare(
                <<<SQL
                UPDATE product_reviews
                SET is_visible = 1,
                    is_flagged = 0,
                    flagged_reason = NULL,
                    hide_reason = NULL,
                    moderated_by = NULL
                WHERE id = :review_id
                SQL
            );
            $statement->execute([
                'review_id' => $reviewId,
            ]);

            $this->refreshProductStats((int) $review['product_id']);

            return true;
        } catch (PDOException) {
            return false;
        }
    }

    public function addMedia(int $reviewId, string $mediaType, string $filePath): ?int
    {
        try {
            $statement = $this->connection->prepare(
                <<<SQL
                INSERT INTO product_review_media (
                    review_id,
                    media_type,
                    file_path
                ) VALUES (
                    :review_id,
                    :media_type,
                    :file_path
                )
                ON DUPLICATE KEY UPDATE
                    file_path = VALUES(file_path),
                    created_at = CURRENT_TIMESTAMP
                SQL
            );
            $statement->execute([
                'review_id' => $reviewId,
                'media_type' => $mediaType,
                'file_path' => $filePath,
            ]);

            return (int) ($this->connection->lastInsertId() ?: $reviewId);
        } catch (PDOException) {
            return null;
        }
    }

    public function deleteMediaByType(int $reviewId, string $mediaType): void
    {
        $statement = $this->connection->prepare(
            'DELETE FROM product_review_media WHERE review_id = :review_id AND media_type = :media_type'
        );
        $statement->execute([
            'review_id' => $reviewId,
            'media_type' => $mediaType,
        ]);
    }

    public function listMediaByReviewId(int $reviewId): array
    {
        try {
            $statement = $this->connection->prepare(
                <<<SQL
                SELECT
                    id,
                    review_id,
                    media_type,
                    file_path,
                    created_at
                FROM product_review_media
                WHERE review_id = :review_id
                ORDER BY id ASC
                SQL
            );
            $statement->execute(['review_id' => $reviewId]);

            return array_map(
                static fn(array $row): array => [
                    'id' => (int) $row['id'],
                    'review_id' => (int) $row['review_id'],
                    'media_type' => (string) $row['media_type'],
                    'file_path' => (string) $row['file_path'],
                    'created_at' => (string) $row['created_at'],
                ],
                $statement->fetchAll()
            );
        } catch (PDOException) {
            return [];
        }
    }

    private function listMediaByReviewIds(array $reviewIds): array
    {
        $reviewIds = array_values(array_filter(array_map('intval', $reviewIds)));

        if ($reviewIds === []) {
            return [];
        }

        try {
            $placeholders = implode(', ', array_fill(0, count($reviewIds), '?'));
            $statement = $this->connection->prepare(
                "SELECT id, review_id, media_type, file_path, created_at
                FROM product_review_media
                WHERE review_id IN ($placeholders)
                ORDER BY id ASC"
            );
            $statement->execute($reviewIds);

            $grouped = [];

            foreach ($statement->fetchAll() as $row) {
                $reviewId = (int) $row['review_id'];

                $grouped[$reviewId][] = [
                    'id' => (int) $row['id'],
                    'review_id' => $reviewId,
                    'media_type' => (string) $row['media_type'],
                    'file_path' => (string) $row['file_path'],
                    'created_at' => (string) $row['created_at'],
                ];
            }

            return $grouped;
        } catch (PDOException) {
            return [];
        }
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
            'product_name' => isset($row['product_name']) && $row['product_name'] !== null
                ? (string) $row['product_name']
                : null,
            'product_slug' => isset($row['product_slug']) && $row['product_slug'] !== null
                ? (string) $row['product_slug']
                : null,
            'seller_id' => isset($row['seller_id']) && $row['seller_id'] !== null
                ? (int) $row['seller_id']
                : null,
            'user_id' => (int) $row['user_id'],
            'user_name' => (string) ($row['user_name'] ?? ''),
            'rating' => (int) $row['rating'],
            'title' => $row['title'] !== null ? (string) $row['title'] : null,
            'comment' => (string) $row['comment'],
            'seller_reply' => isset($row['seller_reply']) && $row['seller_reply'] !== null
                ? (string) $row['seller_reply']
                : null,
            'is_visible' => (bool) $row['is_visible'],
            'is_flagged' => isset($row['is_flagged']) ? (bool) $row['is_flagged'] : false,
            'flagged_reason' => isset($row['flagged_reason']) && $row['flagged_reason'] !== null
                ? (string) $row['flagged_reason']
                : null,
            'hide_reason' => isset($row['hide_reason']) && $row['hide_reason'] !== null
                ? (string) $row['hide_reason']
                : null,
            'moderated_by' => isset($row['moderated_by']) && $row['moderated_by'] !== null
                ? (int) $row['moderated_by']
                : null,
            'created_at' => (string) $row['created_at'],
            'updated_at' => (string) $row['updated_at'],
        ];
    }
}
