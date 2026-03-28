-- Migration: Product Reviews
-- Run this AFTER the orders migration so purchase checks can read orders and order_items

CREATE TABLE IF NOT EXISTS product_reviews (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    rating TINYINT UNSIGNED NOT NULL,
    title VARCHAR(120) DEFAULT NULL,
    comment TEXT NOT NULL,
    seller_reply TEXT DEFAULT NULL,
    is_visible TINYINT(1) NOT NULL DEFAULT 1,
    is_flagged TINYINT(1) NOT NULL DEFAULT 0,
    flagged_reason VARCHAR(255) DEFAULT NULL,
    moderated_by BIGINT UNSIGNED DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_product_reviews_product_user (product_id, user_id),
    INDEX idx_product_reviews_visible (product_id, is_visible, created_at),
    CONSTRAINT fk_product_reviews_product
        FOREIGN KEY (product_id) REFERENCES products(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_product_reviews_user
        FOREIGN KEY (user_id) REFERENCES users(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_product_reviews_moderated_by
        FOREIGN KEY (moderated_by) REFERENCES users(id)
        ON DELETE SET NULL,
    CONSTRAINT chk_product_reviews_rating
        CHECK (rating BETWEEN 1 AND 5)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS product_review_media (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    review_id BIGINT UNSIGNED NOT NULL,
    media_type ENUM('photo', 'video') NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_product_review_media_once (review_id, media_type),
    CONSTRAINT fk_product_review_media_review
        FOREIGN KEY (review_id) REFERENCES product_reviews(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS product_review_flags (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    review_id BIGINT UNSIGNED NOT NULL,
    flagged_by BIGINT UNSIGNED NOT NULL,
    reason VARCHAR(255) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_product_review_flags_review
        FOREIGN KEY (review_id) REFERENCES product_reviews(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_product_review_flags_user
        FOREIGN KEY (flagged_by) REFERENCES users(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
