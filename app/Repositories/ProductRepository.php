<?php

declare(strict_types=1);

namespace App\Repositories;

use PDO;
use PDOException;

final class ProductRepository implements CatalogRepositoryInterface
{
    public function __construct(private readonly PDO $connection)
    {
    }

    public function categories(): array
    {
        $statement = $this->connection->query(
            <<<SQL
            SELECT
                c.id,
                c.name,
                c.slug,
                c.description,
                COUNT(p.id) AS product_count
            FROM categories c
            LEFT JOIN products p
                ON p.category_id = c.id
               AND p.is_active = 1
            GROUP BY c.id, c.name, c.slug, c.description
            ORDER BY c.name ASC
            SQL
        );

        return array_map(
            static fn (array $row): array => [
                'id' => (int) $row['id'],
                'name' => $row['name'],
                'slug' => $row['slug'],
                'description' => $row['description'],
                'product_count' => (int) $row['product_count'],
            ],
            $statement->fetchAll()
        );
    }

    public function listProducts(array $filters): array
    {
        [$sql, $params] = $this->baseProductQuery($filters);
        $statement = $this->connection->prepare($sql);
        $statement->execute($params);

        return array_map([$this, 'normalizeProduct'], $statement->fetchAll());
    }

    public function findById(int $id): ?array
    {
        $statement = $this->connection->prepare(
            $this->baseSelect() . ' WHERE p.is_active = 1 AND p.id = :id LIMIT 1'
        );
        $statement->execute(['id' => $id]);
        $row = $statement->fetch();

        return is_array($row) ? $this->enrichProductWithMedia($this->normalizeProduct($row)) : null;
    }

    public function findBySlug(string $slug): ?array
    {
        $statement = $this->connection->prepare(
            $this->baseSelect() . ' WHERE p.is_active = 1 AND p.slug = :slug LIMIT 1'
        );
        $statement->execute(['slug' => $slug]);
        $row = $statement->fetch();

        return is_array($row) ? $this->enrichProductWithMedia($this->normalizeProduct($row)) : null;
    }

    public function findByIds(array $ids): array
    {
        $ids = array_values(array_filter(array_map('intval', $ids)));

        if ($ids === []) {
            return [];
        }

        $placeholders = implode(', ', array_fill(0, count($ids), '?'));
        $statement = $this->connection->prepare(
            $this->baseSelect() . " WHERE p.is_active = 1 AND p.id IN ($placeholders)"
        );
        $statement->execute($ids);

        $products = [];

        foreach ($statement->fetchAll() as $row) {
            $product = $this->normalizeProduct($row);
            $products[$product['id']] = $product;
        }

        return $products;
    }

    public function listManagedProducts(?int $sellerId = null): array
    {
        $sql = $this->baseSelect();
        $params = [];
        $conditions = [];

        if ($sellerId !== null) {
            $conditions[] = 'p.seller_id = :seller_id';
            $params['seller_id'] = $sellerId;
        }

        if ($conditions !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }

        $sql .= ' ORDER BY p.created_at DESC, p.id DESC';

        $statement = $this->connection->prepare($sql);
        $statement->execute($params);

        return array_map([$this, 'normalizeProduct'], $statement->fetchAll());
    }

    public function findManagedById(int $id): ?array
    {
        $statement = $this->connection->prepare(
            $this->baseSelect() . ' WHERE p.id = :id LIMIT 1'
        );
        $statement->execute(['id' => $id]);
        $row = $statement->fetch();

        return is_array($row) ? $this->normalizeProduct($row) : null;
    }

    public function createManagedProduct(array $data): int
    {
        $statement = $this->connection->prepare(
            <<<SQL
            INSERT INTO products (
                seller_id,
                category_id,
                sku,
                name,
                slug,
                short_description,
                description,
                price,
                compare_price,
                stock_quantity,
                image_url,
                average_rating,
                review_count,
                is_active,
                is_featured
            ) VALUES (
                :seller_id,
                :category_id,
                :sku,
                :name,
                :slug,
                :short_description,
                :description,
                :price,
                :compare_price,
                :stock_quantity,
                :image_url,
                :average_rating,
                :review_count,
                :is_active,
                :is_featured
            )
            SQL
        );
        $statement->execute([
            'seller_id' => $data['seller_id'],
            'category_id' => $data['category_id'],
            'sku' => $data['sku'],
            'name' => $data['name'],
            'slug' => $data['slug'],
            'short_description' => $data['short_description'],
            'description' => $data['description'],
            'price' => $data['price'],
            'compare_price' => $data['compare_price'],
            'stock_quantity' => $data['stock_quantity'],
            'image_url' => $data['image_url'],
            'average_rating' => $data['average_rating'] ?? 0,
            'review_count' => $data['review_count'] ?? 0,
            'is_active' => $data['is_active'] ?? 1,
            'is_featured' => $data['is_featured'] ?? 0,
        ]);

        return (int) $this->connection->lastInsertId();
    }

    public function updateManagedProduct(int $id, array $data): bool
    {
        $fields = [];
        $params = ['id' => $id];

        foreach ([
            'seller_id',
            'category_id',
            'sku',
            'name',
            'slug',
            'short_description',
            'description',
            'price',
            'compare_price',
            'stock_quantity',
            'image_url',
            'is_active',
            'is_featured',
        ] as $column) {
            if (array_key_exists($column, $data)) {
                $fields[] = $column . ' = :' . $column;
                $params[$column] = $data[$column];
            }
        }

        if ($fields === []) {
            return false;
        }

        $statement = $this->connection->prepare(
            'UPDATE products SET ' . implode(', ', $fields) . ' WHERE id = :id'
        );

        return $statement->execute($params);
    }

    public function deleteManagedProduct(int $id): bool
    {
        $statement = $this->connection->prepare('DELETE FROM products WHERE id = :id');

        return $statement->execute(['id' => $id]);
    }

    private function baseProductQuery(array $filters): array
    {
        $conditions = ['p.is_active = 1'];
        $params = [];

        if ($filters['search'] !== '') {
            $conditions[] = '(p.name LIKE :search_name OR p.short_description LIKE :search_description OR c.name LIKE :search_category OR COALESCE(sp.store_name, u.name) LIKE :search_seller)';
            $searchPattern = '%' . $filters['search'] . '%';
            $params['search_name'] = $searchPattern;
            $params['search_description'] = $searchPattern;
            $params['search_category'] = $searchPattern;
            $params['search_seller'] = $searchPattern;
        }

        if ($filters['category'] !== '') {
            $conditions[] = 'c.slug = :category';
            $params['category'] = $filters['category'];
        }

        if ($filters['min_price'] !== null) {
            $conditions[] = 'p.price >= :min_price';
            $params['min_price'] = $filters['min_price'];
        }

        if ($filters['max_price'] !== null) {
            $conditions[] = 'p.price <= :max_price';
            $params['max_price'] = $filters['max_price'];
        }

        if ($filters['in_stock']) {
            $conditions[] = 'p.stock_quantity > 0';
        }

        $orderBy = match ($filters['sort']) {
            'price_asc' => 'p.price ASC, p.name ASC',
            'price_desc' => 'p.price DESC, p.name ASC',
            'rating_desc' => 'p.average_rating DESC, p.review_count DESC, p.name ASC',
            'newest' => 'p.created_at DESC, p.name ASC',
            default => 'p.is_featured DESC, p.created_at DESC, p.name ASC',
        };

        $sql = $this->baseSelect()
            . ' WHERE ' . implode(' AND ', $conditions)
            . ' ORDER BY ' . $orderBy;

        return [$sql, $params];
    }

    private function baseSelect(): string
    {
        return <<<SQL
            SELECT
                p.id,
                p.seller_id,
                p.category_id,
                p.sku,
                p.name,
                p.slug,
                p.short_description,
                p.description,
                p.price,
                p.compare_price,
                p.stock_quantity,
                p.image_url,
                p.average_rating AS rating,
                p.review_count,
                p.is_active,
                p.is_featured,
                p.created_at,
                c.name AS category_name,
                c.slug AS category_slug,
                COALESCE(sp.store_name, u.name) AS seller_name
            FROM products p
            INNER JOIN categories c
                ON c.id = p.category_id
            INNER JOIN users u
                ON u.id = p.seller_id
            LEFT JOIN seller_profiles sp
                ON sp.user_id = u.id
            SQL;
    }

    private function normalizeProduct(array $row): array
    {
        return [
            'id' => (int) $row['id'],
            'seller_id' => (int) $row['seller_id'],
            'seller_name' => (string) $row['seller_name'],
            'category_id' => (int) $row['category_id'],
            'category_name' => (string) $row['category_name'],
            'category_slug' => (string) $row['category_slug'],
            'sku' => (string) $row['sku'],
            'name' => (string) $row['name'],
            'slug' => (string) $row['slug'],
            'short_description' => (string) $row['short_description'],
            'description' => (string) $row['description'],
            'price' => (float) $row['price'],
            'compare_price' => $row['compare_price'] === null ? null : (float) $row['compare_price'],
            'stock_quantity' => (int) $row['stock_quantity'],
            'image_url' => (string) ($row['image_url'] ?: '/assets/images/products/product-fallback.svg'),
            'rating' => (float) $row['rating'],
            'review_count' => (int) $row['review_count'],
            'is_active' => (bool) $row['is_active'],
            'is_featured' => (bool) $row['is_featured'],
            'created_at' => (string) $row['created_at'],
        ];
    }

    private function enrichProductWithMedia(array $product): array
    {
        $product['media'] = $this->fetchMediaForProduct(
            $product['id'],
            $product['image_url'],
            $product['name']
        );

        foreach ($product['media'] as $media) {
            if (($media['type'] ?? 'image') !== 'image') {
                continue;
            }

            $product['image_url'] = $media['url'];
            break;
        }

        return $product;
    }

    private function fetchMediaForProduct(int $productId, string $fallbackUrl, string $fallbackAlt): array
    {
        try {
            $statement = $this->connection->prepare(
                <<<SQL
                SELECT
                    id,
                    media_type,
                    media_url,
                    thumbnail_url,
                    alt_text,
                    sort_order,
                    is_primary
                FROM product_media
                WHERE product_id = :product_id
                ORDER BY is_primary DESC, sort_order ASC, id ASC
                SQL
            );
            $statement->execute(['product_id' => $productId]);
            $rows = $statement->fetchAll();
        } catch (PDOException) {
            return [$this->fallbackMediaItem($fallbackUrl, $fallbackAlt)];
        }

        if ($rows === []) {
            return [$this->fallbackMediaItem($fallbackUrl, $fallbackAlt)];
        }

        $media = array_map(function (array $row) use ($fallbackUrl, $fallbackAlt): array {
            $type = ($row['media_type'] ?? 'image') === 'video' ? 'video' : 'image';
            $url = (string) ($row['media_url'] ?: $fallbackUrl);
            $thumbnail = (string) ($row['thumbnail_url'] ?: ($type === 'image' ? $url : $fallbackUrl));

            return [
                'id' => (int) $row['id'],
                'type' => $type,
                'url' => $url,
                'thumbnail_url' => $thumbnail,
                'alt_text' => (string) ($row['alt_text'] ?: $fallbackAlt),
                'sort_order' => (int) $row['sort_order'],
                'is_primary' => (bool) $row['is_primary'],
            ];
        }, $rows);

        if (!array_filter($media, static fn (array $item): bool => $item['is_primary'])) {
            $media[0]['is_primary'] = true;
        }

        return $media;
    }

    private function fallbackMediaItem(string $fallbackUrl, string $fallbackAlt): array
    {
        return [
            'id' => 0,
            'type' => 'image',
            'url' => $fallbackUrl,
            'thumbnail_url' => $fallbackUrl,
            'alt_text' => $fallbackAlt,
            'sort_order' => 1,
            'is_primary' => true,
        ];
    }
}
