INSERT INTO users (id, name, email, password_hash, role) VALUES
    (1, 'Admin User', 'admin@meridianmart.test', '$2y$12$eBzUWJoDoB55n7mjjzpaquK/l.EfPgXG7xL/0dEKaSsxhK7o8T2yq', 'admin'),
    (2, 'Northwind Audio', 'seller1@meridianmart.test', '$2y$12$eBzUWJoDoB55n7mjjzpaquK/l.EfPgXG7xL/0dEKaSsxhK7o8T2yq', 'seller'),
    (3, 'Summit Office', 'seller2@meridianmart.test', '$2y$12$eBzUWJoDoB55n7mjjzpaquK/l.EfPgXG7xL/0dEKaSsxhK7o8T2yq', 'seller'),
    (4, 'Harbor Home', 'seller3@meridianmart.test', '$2y$12$eBzUWJoDoB55n7mjjzpaquK/l.EfPgXG7xL/0dEKaSsxhK7o8T2yq', 'seller'),
    (5, 'Tide Carry Co.', 'seller4@meridianmart.test', '$2y$12$eBzUWJoDoB55n7mjjzpaquK/l.EfPgXG7xL/0dEKaSsxhK7o8T2yq', 'seller'),
    (6, 'Sample Customer', 'customer@meridianmart.test', '$2y$12$eBzUWJoDoB55n7mjjzpaquK/l.EfPgXG7xL/0dEKaSsxhK7o8T2yq', 'customer');

INSERT INTO seller_profiles (user_id, store_name, store_slug, support_email) VALUES
    (2, 'Northwind Audio', 'northwind-audio', 'support@northwind-audio.test'),
    (3, 'Summit Office', 'summit-office', 'support@summit-office.test'),
    (4, 'Harbor Home', 'harbor-home', 'support@harbor-home.test'),
    (5, 'Tide Carry Co.', 'tide-carry-co', 'support@tide-carry.test');

INSERT INTO categories (id, name, slug, description) VALUES
    (1, 'Tech', 'tech', 'Everyday devices and desktop upgrades.'),
    (2, 'Home Living', 'home-living', 'Comfort-focused essentials for work and rest.'),
    (3, 'Kitchen', 'kitchen', 'Countertop helpers for daily routines.'),
    (4, 'Wellness', 'wellness', 'Products centered on hydration and calm.'),
    (5, 'Lifestyle', 'lifestyle', 'Practical carry goods and everyday accessories.');

INSERT INTO help_categories (id, name, slug, description, icon_key, sort_order) VALUES
    (1, 'Shop with NovaMarket', 'shop-with-novamarket', 'Buying basics, gift options, and everyday marketplace support.', 'shop', 1),
    (2, 'Deals & Rewards', 'deals-rewards', 'Promo codes, vouchers, reward points, and campaign deals.', 'tag', 2),
    (3, 'Order Requests', 'order-requests', 'Cart, address checks, and order-request issues.', 'document', 3),
    (4, 'Orders & Shipping', 'orders-shipping', 'Order tracking, delivery timing, address edits, and shipping issues.', 'truck', 4),
    (5, 'Returns & Refunds', 'returns-refunds', 'Return requests, refund timing, and damaged-item support.', 'refresh', 5),
    (6, 'Account & Security', 'account-security', 'Passwords, phone numbers, sign-up issues, and login safety.', 'shield', 6),
    (7, 'Policies', 'policies', 'Store rules, return eligibility, and marketplace policies.', 'document', 7),
    (8, 'Regulations & Notices', 'regulations-notices', 'Compliance notes, restricted items, and legal notices.', 'notice', 8);

INSERT INTO help_questions (id, category_id, question, answer, is_hot, sort_order) VALUES
    (1, 6, 'How do I reset my account password if I forgot it?', 'Open the sign in page, choose "Forgot password", and follow the reset link sent to your email address. If the email does not arrive, check your spam folder and confirm that you entered the same address used on your NovaMarket account.', 1, 1),
    (2, 6, 'Why can''t I change my mobile number?', 'For security, some number changes require a recent login, account verification, or a cooling-off period after password updates. Make sure your account email is verified first, then retry from your profile settings.', 1, 2),
    (3, 1, 'Why can''t I create a new account?', 'Account creation may be blocked if the email is already registered, the password does not meet the minimum rules, or repeated sign-up attempts triggered a temporary security check. Try again with a different email or wait a few minutes before retrying.', 1, 3),
    (4, 4, 'How do I track my order request?', 'Go to your profile or orders page, open the request, and review the shipping status shown by the seller or courier. Tracking updates usually appear shortly after the request is confirmed, packed, and handed over for delivery.', 1, 4),
    (5, 5, 'How do I request a return or refund?', 'Open the order, choose the return or refund option, describe the issue clearly, and attach supporting photos if needed. Requests are reviewed based on the seller''s return window and NovaMarket policy.', 1, 5),
    (6, 3, 'Why can''t I save my order request?', 'Order requests can fail if your cart is empty, the selected shipping address is incomplete, stock changed before submission, or your session expired. Refresh the page, review your cart, and try again with a valid delivery address.', 1, 6),
    (7, 7, 'What items are not eligible for return?', 'Used personal-care goods, customized products, digital items, and products marked as non-returnable are usually excluded unless they arrive damaged or incorrect. Always check the product listing and policy notes before purchase.', 1, 7),
    (8, 2, 'How do I use a promo code or reward voucher?', 'Apply the promo code from your coupon wallet while reviewing your cart or order request. Some rewards require a minimum spend, a valid campaign period, or specific product eligibility, so check the promotion terms if the discount is not applied.', 1, 8);

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
    (6, 'SUMMIT18', 'Summit Office workspace deal', 'Get a fixed cart discount on workday gear from Summit Office.', 'shop', 'fixed_amount', 18.00, 120.00, 3, '2026-03-16 00:00:00', '2026-04-08 23:59:59', 0, 1);

INSERT INTO products (
    id,
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
    is_featured,
    created_at
) VALUES
    (1, 2, 1, 'MM-TECH-001', 'Nova Wireless Earbuds', 'nova-wireless-earbuds', 'Noise-controlled earbuds with a pocket-size charging case.', 'Nova Wireless Earbuds are tuned for commuting and study sessions, with balanced sound, touch controls, and a secure in-ear fit.', 79.90, 99.90, 24, 'https://storage.googleapis.com/novamarket-product-images/product-images/nova-wireless-earbuds-1.jpg', 4.80, 128, 1, 1, '2026-02-02 10:15:00'),
    (2, 3, 1, 'MM-TECH-002', 'Echo Mechanical Keyboard', 'echo-mechanical-keyboard', 'Compact tactile keyboard with hot-swappable switches.', 'Echo Mechanical Keyboard is built for long typing sessions, with sound-dampened keys, USB-C connectivity, and adjustable tilt.', 119.00, NULL, 12, 'https://storage.googleapis.com/novamarket-product-images/product-images/echo-mechanical-keyboard-1.jpg', 4.70, 74, 1, 1, '2026-01-20 09:45:00'),
    (3, 3, 2, 'MM-HOME-001', 'Halo Standing Desk Lamp', 'halo-standing-desk-lamp', 'Adjustable LED lamp with warm-to-cool light presets.', 'Halo Standing Desk Lamp offers glare-free lighting, a compact footprint, and four brightness zones for home office setups.', 64.50, 84.50, 16, 'https://storage.googleapis.com/novamarket-product-images/product-images/halo-standing-desk-lamp-1.jpg', 4.60, 59, 1, 0, '2026-01-15 16:30:00'),
    (4, 4, 3, 'MM-KITCHEN-001', 'Ember Mug Warmer', 'ember-mug-warmer', 'Desk-friendly warmer that keeps coffee and tea at serving temperature.', 'Ember Mug Warmer uses low-profile heating with a splash-safe surface and one-button controls for shared workspaces.', 42.90, NULL, 31, 'https://storage.googleapis.com/novamarket-product-images/product-images/ember-mug-warmer-1.jpg', 4.50, 113, 1, 0, '2026-02-10 08:10:00'),
    (5, 4, 4, 'MM-WELL-001', 'Solace Aroma Diffuser', 'solace-aroma-diffuser', 'Ceramic diffuser with quiet mist modes and timer presets.', 'Solace Aroma Diffuser brings a subtle mist output, soft ambient light, and auto shutoff for evening routines.', 58.00, 72.00, 9, 'https://storage.googleapis.com/novamarket-product-images/product-images/solace-aroma-diffuser-1.jpg', 4.90, 98, 1, 1, '2026-02-24 12:00:00'),
    (6, 5, 5, 'MM-LIFE-001', 'TideFold Weekender Bag', 'tidefold-weekender-bag', 'Water-resistant carry bag sized for short trips and day use.', 'TideFold Weekender Bag includes a padded laptop sleeve, trolley strap, and separate shoe compartment.', 89.00, 110.00, 7, 'https://storage.googleapis.com/novamarket-product-images/product-images/tidefold-weekender-bag-1.jpg', 4.70, 45, 1, 0, '2026-01-08 14:20:00'),
    (7, 4, 4, 'MM-WELL-002', 'Pulse Smart Bottle', 'pulse-smart-bottle', 'Insulated bottle with hydration reminders and a carry loop.', 'Pulse Smart Bottle tracks your refill routine, keeps drinks cool, and charges with a hidden USB-C port.', 54.00, NULL, 18, 'https://storage.googleapis.com/novamarket-product-images/product-images/pulse-smart-bottle-1.jpg', 4.40, 67, 1, 0, '2026-02-12 11:15:00'),
    (8, 4, 2, 'MM-HOME-002', 'Hearth Throw Blanket', 'hearth-throw-blanket', 'Soft woven blanket for lounge corners and reading nooks.', 'Hearth Throw Blanket uses a textured weave and machine-washable fibers for everyday living spaces.', 49.00, 65.00, 20, 'https://storage.googleapis.com/novamarket-product-images/product-images/hearth-throw-blanket-1.jpg', 4.80, 39, 1, 0, '2026-02-18 15:10:00'),
    (9, 4, 3, 'MM-KITCHEN-002', 'AeroBlend Portable Blender', 'aeroblend-portable-blender', 'Rechargeable smoothie blender for desks, dorms, and travel.', 'AeroBlend Portable Blender crushes soft fruit and ice, detaches for washing, and stores neatly in a drawer.', 68.00, 82.00, 14, 'https://storage.googleapis.com/novamarket-product-images/product-images/aeroblend-portable-blender-1.jpg', 4.30, 53, 1, 1, '2026-02-05 13:35:00'),
    (10, 5, 5, 'MM-LIFE-002', 'Terra Recycled Tote', 'terra-recycled-tote', 'Structured everyday tote made from recycled canvas.', 'Terra Recycled Tote includes an interior bottle pocket, magnetic top closure, and reinforced handles.', 36.00, NULL, 26, 'https://storage.googleapis.com/novamarket-product-images/product-images/terra-recycled-tote-1.jpg', 4.60, 61, 1, 0, '2026-01-29 17:05:00');

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
    media.media_type,
    CONCAT('https://storage.googleapis.com/novamarket-product-images/product-images/', media.file_name),
    CONCAT('https://storage.googleapis.com/novamarket-product-images/product-images/', media.thumbnail_file),
    CONCAT(p.name, ' ', media.media_type, ' ', media.media_index),
    media.sort_order,
    media.is_primary
FROM products p
INNER JOIN (
    SELECT 'nova-wireless-earbuds' AS slug, 'image' AS media_type, 'nova-wireless-earbuds-1.jpg' AS file_name, 'nova-wireless-earbuds-1.jpg' AS thumbnail_file, 1 AS media_index, 1 AS sort_order, 1 AS is_primary
    UNION ALL SELECT 'nova-wireless-earbuds' AS slug, 'video' AS media_type, 'nova-wireless-earbuds-1.mp4' AS file_name, 'nova-wireless-earbuds-1.jpg' AS thumbnail_file, 1 AS media_index, 2 AS sort_order, 0 AS is_primary
    UNION ALL SELECT 'nova-wireless-earbuds' AS slug, 'image' AS media_type, 'nova-wireless-earbuds-2.jpg' AS file_name, 'nova-wireless-earbuds-2.jpg' AS thumbnail_file, 2 AS media_index, 3 AS sort_order, 0 AS is_primary
    UNION ALL SELECT 'nova-wireless-earbuds' AS slug, 'video' AS media_type, 'nova-wireless-earbuds-2.mp4' AS file_name, 'nova-wireless-earbuds-2.jpg' AS thumbnail_file, 2 AS media_index, 4 AS sort_order, 0 AS is_primary
    UNION ALL SELECT 'nova-wireless-earbuds' AS slug, 'image' AS media_type, 'nova-wireless-earbuds-3.jpg' AS file_name, 'nova-wireless-earbuds-3.jpg' AS thumbnail_file, 3 AS media_index, 5 AS sort_order, 0 AS is_primary
    UNION ALL SELECT 'nova-wireless-earbuds' AS slug, 'image' AS media_type, 'nova-wireless-earbuds.jpg' AS file_name, 'nova-wireless-earbuds.jpg' AS thumbnail_file, 4 AS media_index, 6 AS sort_order, 0 AS is_primary
    UNION ALL SELECT 'echo-mechanical-keyboard' AS slug, 'image' AS media_type, 'echo-mechanical-keyboard-1.jpg' AS file_name, 'echo-mechanical-keyboard-1.jpg' AS thumbnail_file, 1 AS media_index, 1 AS sort_order, 1 AS is_primary
    UNION ALL SELECT 'echo-mechanical-keyboard' AS slug, 'video' AS media_type, 'echo-mechanical-keyboard-1.mp4' AS file_name, 'echo-mechanical-keyboard-1.jpg' AS thumbnail_file, 1 AS media_index, 2 AS sort_order, 0 AS is_primary
    UNION ALL SELECT 'echo-mechanical-keyboard' AS slug, 'image' AS media_type, 'echo-mechanical-keyboard-2.jpg' AS file_name, 'echo-mechanical-keyboard-2.jpg' AS thumbnail_file, 2 AS media_index, 3 AS sort_order, 0 AS is_primary
    UNION ALL SELECT 'echo-mechanical-keyboard' AS slug, 'video' AS media_type, 'echo-mechanical-keyboard-2.mp4' AS file_name, 'echo-mechanical-keyboard-2.jpg' AS thumbnail_file, 2 AS media_index, 4 AS sort_order, 0 AS is_primary
    UNION ALL SELECT 'echo-mechanical-keyboard' AS slug, 'image' AS media_type, 'echo-mechanical-keyboard-3.jpg' AS file_name, 'echo-mechanical-keyboard-3.jpg' AS thumbnail_file, 3 AS media_index, 5 AS sort_order, 0 AS is_primary
    UNION ALL SELECT 'echo-mechanical-keyboard' AS slug, 'image' AS media_type, 'echo-mechanical-keyboard.jpg' AS file_name, 'echo-mechanical-keyboard.jpg' AS thumbnail_file, 4 AS media_index, 6 AS sort_order, 0 AS is_primary
    UNION ALL SELECT 'halo-standing-desk-lamp' AS slug, 'image' AS media_type, 'halo-standing-desk-lamp-1.jpg' AS file_name, 'halo-standing-desk-lamp-1.jpg' AS thumbnail_file, 1 AS media_index, 1 AS sort_order, 1 AS is_primary
    UNION ALL SELECT 'halo-standing-desk-lamp' AS slug, 'video' AS media_type, 'halo-standing-desk-lamp-1.mp4' AS file_name, 'halo-standing-desk-lamp-1.jpg' AS thumbnail_file, 1 AS media_index, 2 AS sort_order, 0 AS is_primary
    UNION ALL SELECT 'halo-standing-desk-lamp' AS slug, 'image' AS media_type, 'halo-standing-desk-lamp-2.jpg' AS file_name, 'halo-standing-desk-lamp-2.jpg' AS thumbnail_file, 2 AS media_index, 3 AS sort_order, 0 AS is_primary
    UNION ALL SELECT 'halo-standing-desk-lamp' AS slug, 'image' AS media_type, 'halo-standing-desk-lamp-3.jpg' AS file_name, 'halo-standing-desk-lamp-3.jpg' AS thumbnail_file, 3 AS media_index, 4 AS sort_order, 0 AS is_primary
    UNION ALL SELECT 'halo-standing-desk-lamp' AS slug, 'image' AS media_type, 'halo-standing-desk-lamp.jpg' AS file_name, 'halo-standing-desk-lamp.jpg' AS thumbnail_file, 4 AS media_index, 5 AS sort_order, 0 AS is_primary
    UNION ALL SELECT 'ember-mug-warmer' AS slug, 'image' AS media_type, 'ember-mug-warmer-1.jpg' AS file_name, 'ember-mug-warmer-1.jpg' AS thumbnail_file, 1 AS media_index, 1 AS sort_order, 1 AS is_primary
    UNION ALL SELECT 'ember-mug-warmer' AS slug, 'video' AS media_type, 'ember-mug-warmer-1.mp4' AS file_name, 'ember-mug-warmer-1.jpg' AS thumbnail_file, 1 AS media_index, 2 AS sort_order, 0 AS is_primary
    UNION ALL SELECT 'ember-mug-warmer' AS slug, 'image' AS media_type, 'ember-mug-warmer-2.jpg' AS file_name, 'ember-mug-warmer-2.jpg' AS thumbnail_file, 2 AS media_index, 3 AS sort_order, 0 AS is_primary
    UNION ALL SELECT 'ember-mug-warmer' AS slug, 'image' AS media_type, 'ember-mug-warmer-3.jpg' AS file_name, 'ember-mug-warmer-3.jpg' AS thumbnail_file, 3 AS media_index, 4 AS sort_order, 0 AS is_primary
    UNION ALL SELECT 'ember-mug-warmer' AS slug, 'image' AS media_type, 'ember-mug-warmer.jpg' AS file_name, 'ember-mug-warmer.jpg' AS thumbnail_file, 4 AS media_index, 5 AS sort_order, 0 AS is_primary
    UNION ALL SELECT 'solace-aroma-diffuser' AS slug, 'image' AS media_type, 'solace-aroma-diffuser-1.jpg' AS file_name, 'solace-aroma-diffuser-1.jpg' AS thumbnail_file, 1 AS media_index, 1 AS sort_order, 1 AS is_primary
    UNION ALL SELECT 'solace-aroma-diffuser' AS slug, 'video' AS media_type, 'solace-aroma-diffuser-1.mp4' AS file_name, 'solace-aroma-diffuser-1.jpg' AS thumbnail_file, 1 AS media_index, 2 AS sort_order, 0 AS is_primary
    UNION ALL SELECT 'solace-aroma-diffuser' AS slug, 'image' AS media_type, 'solace-aroma-diffuser-2.jpg' AS file_name, 'solace-aroma-diffuser-2.jpg' AS thumbnail_file, 2 AS media_index, 3 AS sort_order, 0 AS is_primary
    UNION ALL SELECT 'solace-aroma-diffuser' AS slug, 'image' AS media_type, 'solace-aroma-diffuser-3.jpg' AS file_name, 'solace-aroma-diffuser-3.jpg' AS thumbnail_file, 3 AS media_index, 4 AS sort_order, 0 AS is_primary
    UNION ALL SELECT 'solace-aroma-diffuser' AS slug, 'image' AS media_type, 'solace-aroma-diffuser.jpg' AS file_name, 'solace-aroma-diffuser.jpg' AS thumbnail_file, 4 AS media_index, 5 AS sort_order, 0 AS is_primary
    UNION ALL SELECT 'tidefold-weekender-bag' AS slug, 'image' AS media_type, 'tidefold-weekender-bag-1.jpg' AS file_name, 'tidefold-weekender-bag-1.jpg' AS thumbnail_file, 1 AS media_index, 1 AS sort_order, 1 AS is_primary
    UNION ALL SELECT 'tidefold-weekender-bag' AS slug, 'video' AS media_type, 'tidefold-weekender-bag-1.mp4' AS file_name, 'tidefold-weekender-bag-1.jpg' AS thumbnail_file, 1 AS media_index, 2 AS sort_order, 0 AS is_primary
    UNION ALL SELECT 'tidefold-weekender-bag' AS slug, 'image' AS media_type, 'tidefold-weekender-bag-2.jpg' AS file_name, 'tidefold-weekender-bag-2.jpg' AS thumbnail_file, 2 AS media_index, 3 AS sort_order, 0 AS is_primary
    UNION ALL SELECT 'tidefold-weekender-bag' AS slug, 'image' AS media_type, 'tidefold-weekender-bag-3.jpg' AS file_name, 'tidefold-weekender-bag-3.jpg' AS thumbnail_file, 3 AS media_index, 4 AS sort_order, 0 AS is_primary
    UNION ALL SELECT 'tidefold-weekender-bag' AS slug, 'image' AS media_type, 'tidefold-weekender-bag.jpg' AS file_name, 'tidefold-weekender-bag.jpg' AS thumbnail_file, 4 AS media_index, 5 AS sort_order, 0 AS is_primary
    UNION ALL SELECT 'pulse-smart-bottle' AS slug, 'image' AS media_type, 'pulse-smart-bottle-1.jpg' AS file_name, 'pulse-smart-bottle-1.jpg' AS thumbnail_file, 1 AS media_index, 1 AS sort_order, 1 AS is_primary
    UNION ALL SELECT 'pulse-smart-bottle' AS slug, 'video' AS media_type, 'pulse-smart-bottle-1.mp4' AS file_name, 'pulse-smart-bottle-1.jpg' AS thumbnail_file, 1 AS media_index, 2 AS sort_order, 0 AS is_primary
    UNION ALL SELECT 'pulse-smart-bottle' AS slug, 'image' AS media_type, 'pulse-smart-bottle-2.jpg' AS file_name, 'pulse-smart-bottle-2.jpg' AS thumbnail_file, 2 AS media_index, 3 AS sort_order, 0 AS is_primary
    UNION ALL SELECT 'pulse-smart-bottle' AS slug, 'image' AS media_type, 'pulse-smart-bottle-3.jpg' AS file_name, 'pulse-smart-bottle-3.jpg' AS thumbnail_file, 3 AS media_index, 4 AS sort_order, 0 AS is_primary
    UNION ALL SELECT 'pulse-smart-bottle' AS slug, 'image' AS media_type, 'pulse-smart-bottle.jpg' AS file_name, 'pulse-smart-bottle.jpg' AS thumbnail_file, 4 AS media_index, 5 AS sort_order, 0 AS is_primary
    UNION ALL SELECT 'hearth-throw-blanket' AS slug, 'image' AS media_type, 'hearth-throw-blanket-1.jpg' AS file_name, 'hearth-throw-blanket-1.jpg' AS thumbnail_file, 1 AS media_index, 1 AS sort_order, 1 AS is_primary
    UNION ALL SELECT 'hearth-throw-blanket' AS slug, 'video' AS media_type, 'hearth-throw-blanket-1.mp4' AS file_name, 'hearth-throw-blanket-1.jpg' AS thumbnail_file, 1 AS media_index, 2 AS sort_order, 0 AS is_primary
    UNION ALL SELECT 'hearth-throw-blanket' AS slug, 'image' AS media_type, 'hearth-throw-blanket-2.jpg' AS file_name, 'hearth-throw-blanket-2.jpg' AS thumbnail_file, 2 AS media_index, 3 AS sort_order, 0 AS is_primary
    UNION ALL SELECT 'hearth-throw-blanket' AS slug, 'image' AS media_type, 'hearth-throw-blanket-3.jpg' AS file_name, 'hearth-throw-blanket-3.jpg' AS thumbnail_file, 3 AS media_index, 4 AS sort_order, 0 AS is_primary
    UNION ALL SELECT 'hearth-throw-blanket' AS slug, 'image' AS media_type, 'hearth-throw-blanket.jpg' AS file_name, 'hearth-throw-blanket.jpg' AS thumbnail_file, 4 AS media_index, 5 AS sort_order, 0 AS is_primary
    UNION ALL SELECT 'aeroblend-portable-blender' AS slug, 'image' AS media_type, 'aeroblend-portable-blender-1.jpg' AS file_name, 'aeroblend-portable-blender-1.jpg' AS thumbnail_file, 1 AS media_index, 1 AS sort_order, 1 AS is_primary
    UNION ALL SELECT 'aeroblend-portable-blender' AS slug, 'video' AS media_type, 'aeroblend-portable-blender-1.mp4' AS file_name, 'aeroblend-portable-blender-1.jpg' AS thumbnail_file, 1 AS media_index, 2 AS sort_order, 0 AS is_primary
    UNION ALL SELECT 'aeroblend-portable-blender' AS slug, 'image' AS media_type, 'aeroblend-portable-blender-2.jpg' AS file_name, 'aeroblend-portable-blender-2.jpg' AS thumbnail_file, 2 AS media_index, 3 AS sort_order, 0 AS is_primary
    UNION ALL SELECT 'aeroblend-portable-blender' AS slug, 'image' AS media_type, 'aeroblend-portable-blender-3.jpg' AS file_name, 'aeroblend-portable-blender-3.jpg' AS thumbnail_file, 3 AS media_index, 4 AS sort_order, 0 AS is_primary
    UNION ALL SELECT 'aeroblend-portable-blender' AS slug, 'image' AS media_type, 'aeroblend-portable-blender.jpg' AS file_name, 'aeroblend-portable-blender.jpg' AS thumbnail_file, 4 AS media_index, 5 AS sort_order, 0 AS is_primary
    UNION ALL SELECT 'terra-recycled-tote' AS slug, 'image' AS media_type, 'terra-recycled-tote-1.jpg' AS file_name, 'terra-recycled-tote-1.jpg' AS thumbnail_file, 1 AS media_index, 1 AS sort_order, 1 AS is_primary
    UNION ALL SELECT 'terra-recycled-tote' AS slug, 'video' AS media_type, 'terra-recycled-tote-1.mp4' AS file_name, 'terra-recycled-tote-1.jpg' AS thumbnail_file, 1 AS media_index, 2 AS sort_order, 0 AS is_primary
    UNION ALL SELECT 'terra-recycled-tote' AS slug, 'image' AS media_type, 'terra-recycled-tote-2.jpg' AS file_name, 'terra-recycled-tote-2.jpg' AS thumbnail_file, 2 AS media_index, 3 AS sort_order, 0 AS is_primary
    UNION ALL SELECT 'terra-recycled-tote' AS slug, 'image' AS media_type, 'terra-recycled-tote-3.jpg' AS file_name, 'terra-recycled-tote-3.jpg' AS thumbnail_file, 3 AS media_index, 4 AS sort_order, 0 AS is_primary
    UNION ALL SELECT 'terra-recycled-tote' AS slug, 'image' AS media_type, 'terra-recycled-tote.jpg' AS file_name, 'terra-recycled-tote.jpg' AS thumbnail_file, 4 AS media_index, 5 AS sort_order, 0 AS is_primary
) media
    ON media.slug = p.slug
ORDER BY p.id, media.sort_order;
