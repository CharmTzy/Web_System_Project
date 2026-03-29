-- Migration: User Management & Address Management
-- Run this AFTER schema.sql and seed.sql

-- Add profile and status columns to users
ALTER TABLE users
    ADD COLUMN IF NOT EXISTS phone       VARCHAR(30)  DEFAULT NULL AFTER email,
    ADD COLUMN IF NOT EXISTS avatar_url  VARCHAR(255) DEFAULT NULL AFTER phone,
    ADD COLUMN IF NOT EXISTS is_active   TINYINT(1)   NOT NULL DEFAULT 1 AFTER role;

-- Customer addresses
CREATE TABLE IF NOT EXISTS addresses (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     BIGINT UNSIGNED NOT NULL,
    label       VARCHAR(50)  NOT NULL DEFAULT 'Home',
    recipient   VARCHAR(120) NOT NULL,
    line_1      VARCHAR(255) NOT NULL,
    line_2      VARCHAR(255) DEFAULT NULL,
    city        VARCHAR(100) NOT NULL,
    state       VARCHAR(100) NOT NULL,
    postal_code VARCHAR(20)  NOT NULL,
    country     VARCHAR(80)  NOT NULL DEFAULT 'Singapore',
    phone       VARCHAR(30)  DEFAULT NULL,
    is_default  TINYINT(1)   NOT NULL DEFAULT 0,
    created_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_addresses_user (user_id),
    CONSTRAINT fk_addresses_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed: set all existing users as active
UPDATE users SET is_active = 1 WHERE id > 0;

-- Seed: sample address for the customer
INSERT INTO addresses (user_id, label, recipient, line_1, city, state, postal_code, country, phone, is_default)
SELECT 6, 'Home', 'Sample Customer', '123 Orchard Road, #04-56', 'Singapore', 'Central', '238888', 'Singapore', '+65 9123 4567', 1
WHERE NOT EXISTS (
    SELECT 1
    FROM addresses
    WHERE user_id = 6
      AND label = 'Home'
      AND line_1 = '123 Orchard Road, #04-56'
);
