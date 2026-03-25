-- Migration: Product Media Gallery
-- Run this after schema.sql and seed.sql for an existing database.

CREATE TABLE IF NOT EXISTS product_media (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id BIGINT UNSIGNED NOT NULL,
    media_type ENUM('image', 'video') NOT NULL DEFAULT 'image',
    media_url VARCHAR(500) NOT NULL,
    thumbnail_url VARCHAR(500) DEFAULT NULL,
    alt_text VARCHAR(255) DEFAULT NULL,
    sort_order INT UNSIGNED NOT NULL DEFAULT 1,
    is_primary TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_product_media_product (product_id),
    UNIQUE KEY uq_product_media_product_sort (product_id, sort_order),
    CONSTRAINT fk_product_media_product
        FOREIGN KEY (product_id) REFERENCES products(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO product_media (
    product_id,
    media_type,
    media_url,
    thumbnail_url,
    alt_text,
    sort_order,
    is_primary
)
SELECT
    p.id,
    'image',
    p.image_url,
    p.image_url,
    p.name,
    1,
    1
FROM products p
WHERE p.image_url IS NOT NULL
  AND p.image_url <> ''
  AND NOT EXISTS (
      SELECT 1
      FROM product_media pm
      WHERE pm.product_id = p.id
  );
