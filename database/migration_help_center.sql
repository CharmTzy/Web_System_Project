CREATE TABLE IF NOT EXISTS help_categories (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    slug VARCHAR(120) NOT NULL UNIQUE,
    description VARCHAR(255) DEFAULT NULL,
    icon_key VARCHAR(40) NOT NULL DEFAULT 'general',
    sort_order INT UNSIGNED NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS help_questions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_id BIGINT UNSIGNED NOT NULL,
    question VARCHAR(255) NOT NULL,
    answer TEXT NOT NULL,
    is_hot TINYINT(1) NOT NULL DEFAULT 0,
    sort_order INT UNSIGNED NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_help_questions_category (category_id),
    INDEX idx_help_questions_hot (is_hot),
    CONSTRAINT fk_help_questions_category
        FOREIGN KEY (category_id) REFERENCES help_categories(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO help_categories (id, name, slug, description, icon_key, sort_order) VALUES
    (1, 'Shop with NovaMarket', 'shop-with-novamarket', 'Buying basics, gift options, and everyday marketplace support.', 'shop', 1),
    (2, 'Deals & Rewards', 'deals-rewards', 'Promo codes, vouchers, reward points, and campaign deals.', 'tag', 2),
    (3, 'Order Requests', 'order-requests', 'Cart, address checks, and order-request issues.', 'document', 3),
    (4, 'Orders & Shipping', 'orders-shipping', 'Order tracking, delivery timing, address edits, and shipping issues.', 'truck', 4),
    (5, 'Returns & Refunds', 'returns-refunds', 'Return requests, refund timing, and damaged-item support.', 'refresh', 5),
    (6, 'Account & Security', 'account-security', 'Passwords, phone numbers, sign-up issues, and login safety.', 'shield', 6),
    (7, 'Policies', 'policies', 'Store rules, return eligibility, and marketplace policies.', 'document', 7),
    (8, 'Regulations & Notices', 'regulations-notices', 'Compliance notes, restricted items, and legal notices.', 'notice', 8)
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    slug = VALUES(slug),
    description = VALUES(description),
    icon_key = VALUES(icon_key),
    sort_order = VALUES(sort_order);

INSERT INTO help_questions (id, category_id, question, answer, is_hot, sort_order) VALUES
    (1, 6, 'How do I reset my account password if I forgot it?', 'Open the sign in page, choose "Forgot password", and follow the reset link sent to your email address. If the email does not arrive, check your spam folder and confirm that you entered the same address used on your NovaMarket account.', 1, 1),
    (2, 6, 'Why can''t I change my mobile number?', 'For security, some number changes require a recent login, account verification, or a cooling-off period after password updates. Make sure your account email is verified first, then retry from your profile settings.', 1, 2),
    (3, 1, 'Why can''t I create a new account?', 'Account creation may be blocked if the email is already registered, the password does not meet the minimum rules, or repeated sign-up attempts triggered a temporary security check. Try again with a different email or wait a few minutes before retrying.', 1, 3),
    (4, 4, 'How do I track my order request?', 'Go to your profile or orders page, open the request, and review the shipping status shown by the seller or courier. Tracking updates usually appear shortly after the request is confirmed, packed, and handed over for delivery.', 1, 4),
    (5, 5, 'How do I request a return or refund?', 'Open the order, choose the return or refund option, describe the issue clearly, and attach supporting photos if needed. Requests are reviewed based on the seller''s return window and NovaMarket policy.', 1, 5),
    (6, 3, 'Why can''t I save my order request?', 'Order requests can fail if your cart is empty, the selected shipping address is incomplete, stock changed before submission, or your session expired. Refresh the page, review your cart, and try again with a valid delivery address.', 1, 6),
    (7, 7, 'What items are not eligible for return?', 'Used personal-care goods, customized products, digital items, and products marked as non-returnable are usually excluded unless they arrive damaged or incorrect. Always check the product listing and policy notes before purchase.', 1, 7),
    (8, 2, 'How do I use a promo code or reward voucher?', 'Apply the promo code from your coupon wallet while reviewing your cart or order request. Some rewards require a minimum spend, a valid campaign period, or specific product eligibility, so check the promotion terms if the discount is not applied.', 1, 8)
ON DUPLICATE KEY UPDATE
    category_id = VALUES(category_id),
    question = VALUES(question),
    answer = VALUES(answer),
    is_hot = VALUES(is_hot),
    sort_order = VALUES(sort_order);
