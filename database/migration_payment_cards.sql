-- Migration: Payment Cards & Orders
-- Run this AFTER migration_users_addresses.sql

-- Stored payment cards for customers
CREATE TABLE IF NOT EXISTS payment_cards (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id         BIGINT UNSIGNED NOT NULL,
    label           VARCHAR(50)  NOT NULL DEFAULT 'My Card',
    cardholder_name VARCHAR(120) NOT NULL,
    card_last_four  CHAR(4)      NOT NULL,
    card_brand      ENUM('visa', 'mastercard', 'amex', 'discover', 'other') NOT NULL DEFAULT 'visa',
    expiry_month    TINYINT UNSIGNED NOT NULL,
    expiry_year     SMALLINT UNSIGNED NOT NULL,
    is_default      TINYINT(1)   NOT NULL DEFAULT 0,
    created_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_payment_cards_user (user_id),
    INDEX idx_payment_cards_user_default (user_id, is_default),
    CONSTRAINT fk_payment_cards_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Orders table: captures the shipping address and payment card used at checkout
CREATE TABLE IF NOT EXISTS orders (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    customer_id     BIGINT UNSIGNED NOT NULL,
    order_number    VARCHAR(30)  NOT NULL UNIQUE,
    status          ENUM('pending', 'paid', 'shipped', 'delivered', 'cancelled') NOT NULL DEFAULT 'pending',

    -- Shipping address snapshot (copied at checkout so edits don't affect past orders)
    shipping_recipient   VARCHAR(120) NOT NULL,
    shipping_line_1      VARCHAR(255) NOT NULL,
    shipping_line_2      VARCHAR(255) DEFAULT NULL,
    shipping_city        VARCHAR(100) NOT NULL,
    shipping_state       VARCHAR(100) NOT NULL,
    shipping_postal_code VARCHAR(20)  NOT NULL,
    shipping_country     VARCHAR(80)  NOT NULL DEFAULT 'Singapore',
    shipping_phone       VARCHAR(30)  DEFAULT NULL,

    -- Payment snapshot (last four digits only - never store full card numbers)
    payment_card_brand     ENUM('visa', 'mastercard', 'amex', 'discover', 'other') DEFAULT NULL,
    payment_card_last_four CHAR(4) DEFAULT NULL,

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

-- Order line items (snapshot of product at time of purchase)
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

-- Sample payment card for the seeded test customer.
-- Uses a guarded INSERT so the migration can be rerun safely.
INSERT INTO payment_cards (
    user_id,
    label,
    cardholder_name,
    card_last_four,
    card_brand,
    expiry_month,
    expiry_year,
    is_default
)
SELECT
    u.id,
    'Personal Visa',
    u.name,
    '4242',
    'visa',
    12,
    2028,
    1
FROM users u
WHERE u.role = 'customer'
  AND u.email = 'customer@meridianmart.test'
  AND NOT EXISTS (
      SELECT 1
      FROM payment_cards pc
      WHERE pc.user_id = u.id
        AND pc.card_last_four = '4242'
        AND pc.card_brand = 'visa'
  )
LIMIT 1;
