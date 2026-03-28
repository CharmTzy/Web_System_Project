-- Migration: Orders (removes legacy payment-card storage)
-- Run this AFTER migration_users_addresses.sql

DROP TABLE IF EXISTS payment_cards;

-- Orders table: captures the shipping address snapshot used when a request is submitted
CREATE TABLE IF NOT EXISTS orders (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    customer_id     BIGINT UNSIGNED NOT NULL,
    order_number    VARCHAR(30)  NOT NULL UNIQUE,
    status          ENUM('pending', 'paid', 'shipped', 'delivered', 'cancelled') NOT NULL DEFAULT 'pending',

    -- Shipping address snapshot (copied when a request is submitted so edits don't affect past orders)
    shipping_recipient   VARCHAR(120) NOT NULL,
    shipping_line_1      VARCHAR(255) NOT NULL,
    shipping_line_2      VARCHAR(255) DEFAULT NULL,
    shipping_city        VARCHAR(100) NOT NULL,
    shipping_state       VARCHAR(100) NOT NULL,
    shipping_postal_code VARCHAR(20)  NOT NULL,
    shipping_country     VARCHAR(80)  NOT NULL DEFAULT 'Singapore',
    shipping_phone       VARCHAR(30)  DEFAULT NULL,

    subtotal        DECIMAL(10, 2) NOT NULL,
    shipping_fee    DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    total           DECIMAL(10, 2) NOT NULL,

    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_orders_customer (customer_id),
    INDEX idx_orders_status (status),
    INDEX idx_orders_created_at (created_at),
    CONSTRAINT fk_orders_customer
        FOREIGN KEY (customer_id) REFERENCES users(id)
        ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Order line items (snapshot of product details when an order request is created)
CREATE TABLE IF NOT EXISTS order_items (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id    BIGINT UNSIGNED NOT NULL,
    product_id  BIGINT UNSIGNED NOT NULL,
    product_name VARCHAR(150) NOT NULL,
    quantity    INT UNSIGNED NOT NULL,
    unit_price  DECIMAL(10, 2) NOT NULL,
    line_total  DECIMAL(10, 2) NOT NULL,
    created_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_order_items_order (order_id),
    UNIQUE KEY uq_order_items_order_product (order_id, product_id),
    CONSTRAINT fk_order_items_order
        FOREIGN KEY (order_id) REFERENCES orders(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_order_items_product
        FOREIGN KEY (product_id) REFERENCES products(id)
        ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET @has_payment_card_brand := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'orders'
      AND COLUMN_NAME = 'payment_card_brand'
);
SET @drop_payment_card_brand_sql := IF(
    @has_payment_card_brand > 0,
    'ALTER TABLE orders DROP COLUMN payment_card_brand',
    'SELECT 1'
);
PREPARE drop_payment_card_brand_stmt FROM @drop_payment_card_brand_sql;
EXECUTE drop_payment_card_brand_stmt;
DEALLOCATE PREPARE drop_payment_card_brand_stmt;

SET @has_payment_card_last_four := (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'orders'
      AND COLUMN_NAME = 'payment_card_last_four'
);
SET @drop_payment_card_last_four_sql := IF(
    @has_payment_card_last_four > 0,
    'ALTER TABLE orders DROP COLUMN payment_card_last_four',
    'SELECT 1'
);
PREPARE drop_payment_card_last_four_stmt FROM @drop_payment_card_last_four_sql;
EXECUTE drop_payment_card_last_four_stmt;
DEALLOCATE PREPARE drop_payment_card_last_four_stmt;
