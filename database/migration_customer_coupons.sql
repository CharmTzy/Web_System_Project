CREATE TABLE IF NOT EXISTS coupons (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(40) NOT NULL UNIQUE,
    title VARCHAR(160) NOT NULL,
    description VARCHAR(255) NOT NULL,
    coupon_type ENUM('limited_time', 'free_shipping', 'shop') NOT NULL,
    discount_type ENUM('percentage', 'fixed_amount', 'shipping') NOT NULL,
    discount_value DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    minimum_spend DECIMAL(10, 2) DEFAULT NULL,
    seller_id BIGINT UNSIGNED DEFAULT NULL,
    starts_at DATETIME NOT NULL,
    ends_at DATETIME NOT NULL,
    is_featured TINYINT(1) NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_coupons_type (coupon_type),
    INDEX idx_coupons_active_window (is_active, starts_at, ends_at),
    CONSTRAINT fk_coupons_seller
        FOREIGN KEY (seller_id) REFERENCES users(id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO coupons (
    id,
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
) VALUES
    (1, 'FLASH15', 'Weekend flash markdown', 'Save on selected favorites before the campaign closes this week.', 'limited_time', 'percentage', 15.00, 80.00, NULL, '2026-03-20 00:00:00', '2026-03-28 23:59:59', 1, 1),
    (2, 'SAVE12NOW', 'Today-only order bonus', 'Use this fixed discount on a qualifying order request during the current promo window.', 'limited_time', 'fixed_amount', 12.00, 100.00, NULL, '2026-03-19 00:00:00', '2026-03-31 23:59:59', 0, 1),
    (3, 'SHIPFREE60', 'Free shipping for basket top-ups', 'Unlock delivery savings once your basket reaches the minimum spend.', 'free_shipping', 'shipping', 0.00, 60.00, NULL, '2026-03-15 00:00:00', '2026-04-12 23:59:59', 1, 1),
    (4, 'HARBORSHIP', 'Harbor Home delivery perk', 'Get free standard shipping on eligible home living picks from Harbor Home.', 'free_shipping', 'shipping', 0.00, 35.00, 4, '2026-03-18 00:00:00', '2026-04-18 23:59:59', 0, 1),
    (5, 'HARBOR10', 'Harbor Home shop voucher', 'Take 10% off comfort-first essentials from the Harbor Home shop.', 'shop', 'percentage', 10.00, 50.00, 4, '2026-03-17 00:00:00', '2026-04-15 23:59:59', 1, 1),
    (6, 'SUMMIT18', 'Summit Office workspace deal', 'Get a fixed cart discount on workday gear from Summit Office.', 'shop', 'fixed_amount', 18.00, 120.00, 3, '2026-03-16 00:00:00', '2026-04-08 23:59:59', 0, 1)
ON DUPLICATE KEY UPDATE
    title = VALUES(title),
    description = VALUES(description),
    coupon_type = VALUES(coupon_type),
    discount_type = VALUES(discount_type),
    discount_value = VALUES(discount_value),
    minimum_spend = VALUES(minimum_spend),
    seller_id = VALUES(seller_id),
    starts_at = VALUES(starts_at),
    ends_at = VALUES(ends_at),
    is_featured = VALUES(is_featured),
    is_active = VALUES(is_active);

DELETE FROM coupons
WHERE id IN (
    SELECT id FROM (
        SELECT id
        FROM coupons
        WHERE coupon_type NOT IN ('limited_time', 'free_shipping', 'shop')
    ) AS removable_coupons
);

SET @drop_brand_name_column := IF(
    EXISTS (
        SELECT 1
        FROM information_schema.columns
        WHERE table_schema = DATABASE()
          AND table_name = 'coupons'
          AND column_name = 'brand_name'
    ),
    'ALTER TABLE coupons DROP COLUMN brand_name',
    'SELECT 1'
);
PREPARE drop_brand_name_column_stmt FROM @drop_brand_name_column;
EXECUTE drop_brand_name_column_stmt;
DEALLOCATE PREPARE drop_brand_name_column_stmt;

ALTER TABLE coupons
    MODIFY coupon_type ENUM('limited_time', 'free_shipping', 'shop') NOT NULL;
