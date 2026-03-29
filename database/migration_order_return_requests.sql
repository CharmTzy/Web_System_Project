CREATE TABLE IF NOT EXISTS order_return_requests (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id BIGINT UNSIGNED NOT NULL,
    seller_id BIGINT UNSIGNED NOT NULL,
    customer_id BIGINT UNSIGNED NOT NULL,
    request_type ENUM('refund', 'return', 'return_refund') NOT NULL,
    status ENUM('pending', 'approved', 'rejected', 'received', 'refunded', 'cancelled') NOT NULL DEFAULT 'pending',
    reason_code VARCHAR(40) NOT NULL,
    reason_details TEXT DEFAULT NULL,
    seller_response TEXT DEFAULT NULL,
    reviewed_at DATETIME DEFAULT NULL,
    resolved_at DATETIME DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_order_return_requests_customer (customer_id, status),
    INDEX idx_order_return_requests_seller (seller_id, status),
    INDEX idx_order_return_requests_order (order_id, status),
    CONSTRAINT fk_order_return_requests_order
        FOREIGN KEY (order_id) REFERENCES orders(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_order_return_requests_seller
        FOREIGN KEY (seller_id) REFERENCES users(id)
        ON DELETE RESTRICT,
    CONSTRAINT fk_order_return_requests_customer
        FOREIGN KEY (customer_id) REFERENCES users(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
