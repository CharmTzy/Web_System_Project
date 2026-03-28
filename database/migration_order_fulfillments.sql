CREATE TABLE IF NOT EXISTS order_fulfillments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id BIGINT UNSIGNED NOT NULL,
    seller_id BIGINT UNSIGNED NOT NULL,
    status ENUM('pending', 'paid', 'processing', 'packed', 'shipped', 'out_for_delivery', 'delivered', 'cancelled') NOT NULL DEFAULT 'pending',
    seller_subtotal DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
    item_count INT UNSIGNED NOT NULL DEFAULT 0,
    item_quantity INT UNSIGNED NOT NULL DEFAULT 0,
    courier_name VARCHAR(120) DEFAULT NULL,
    tracking_number VARCHAR(120) DEFAULT NULL,
    status_note VARCHAR(500) DEFAULT NULL,
    estimated_delivery_date DATE DEFAULT NULL,
    shipped_at DATETIME DEFAULT NULL,
    out_for_delivery_at DATETIME DEFAULT NULL,
    delivered_at DATETIME DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_order_fulfillments_order_seller (order_id, seller_id),
    INDEX idx_order_fulfillments_seller_status (seller_id, status),
    INDEX idx_order_fulfillments_order_status (order_id, status),
    CONSTRAINT fk_order_fulfillments_order
        FOREIGN KEY (order_id) REFERENCES orders(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_order_fulfillments_seller
        FOREIGN KEY (seller_id) REFERENCES users(id)
        ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO order_fulfillments (
    order_id,
    seller_id,
    status,
    seller_subtotal,
    item_count,
    item_quantity,
    created_at,
    updated_at
)
SELECT
    o.id AS order_id,
    p.seller_id,
    CASE o.status
        WHEN 'pending' THEN 'pending'
        WHEN 'paid' THEN 'paid'
        WHEN 'shipped' THEN 'shipped'
        WHEN 'delivered' THEN 'delivered'
        WHEN 'cancelled' THEN 'cancelled'
        ELSE 'pending'
    END AS status,
    ROUND(COALESCE(SUM(oi.line_total), 0), 2) AS seller_subtotal,
    COUNT(oi.id) AS item_count,
    COALESCE(SUM(oi.quantity), 0) AS item_quantity,
    MIN(o.created_at) AS created_at,
    MAX(o.updated_at) AS updated_at
FROM orders o
INNER JOIN order_items oi
    ON oi.order_id = o.id
INNER JOIN products p
    ON p.id = oi.product_id
GROUP BY o.id, p.seller_id
ON DUPLICATE KEY UPDATE
    status = VALUES(status),
    seller_subtotal = VALUES(seller_subtotal),
    item_count = VALUES(item_count),
    item_quantity = VALUES(item_quantity),
    updated_at = VALUES(updated_at);
