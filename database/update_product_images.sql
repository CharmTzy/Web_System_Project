SET @old_sql_safe_updates := @@SQL_SAFE_UPDATES;
SET SQL_SAFE_UPDATES = 0;

START TRANSACTION;

SET @media_base_url := 'https://storage.googleapis.com/novamarket-product-images/product-images/';

UPDATE products p
INNER JOIN (
    SELECT 'nova-wireless-earbuds' AS slug, 'nova-wireless-earbuds-1.jpg' AS primary_image
    UNION ALL SELECT 'echo-mechanical-keyboard' AS slug, 'echo-mechanical-keyboard-1.jpg' AS primary_image
    UNION ALL SELECT 'halo-standing-desk-lamp' AS slug, 'halo-standing-desk-lamp-1.jpg' AS primary_image
    UNION ALL SELECT 'ember-mug-warmer' AS slug, 'ember-mug-warmer-1.jpg' AS primary_image
    UNION ALL SELECT 'solace-aroma-diffuser' AS slug, 'solace-aroma-diffuser-1.jpg' AS primary_image
    UNION ALL SELECT 'tidefold-weekender-bag' AS slug, 'tidefold-weekender-bag-1.jpg' AS primary_image
    UNION ALL SELECT 'pulse-smart-bottle' AS slug, 'pulse-smart-bottle-1.jpg' AS primary_image
    UNION ALL SELECT 'hearth-throw-blanket' AS slug, 'hearth-throw-blanket-1.jpg' AS primary_image
    UNION ALL SELECT 'aeroblend-portable-blender' AS slug, 'aeroblend-portable-blender-1.jpg' AS primary_image
    UNION ALL SELECT 'terra-recycled-tote' AS slug, 'terra-recycled-tote-1.jpg' AS primary_image
) media
    ON media.slug = p.slug
SET p.image_url = CONCAT(@media_base_url, media.primary_image)
WHERE p.id IN (
    SELECT target.id
    FROM (
        SELECT p2.id
        FROM products p2
        WHERE p2.slug IN (
            'nova-wireless-earbuds',
            'echo-mechanical-keyboard',
            'halo-standing-desk-lamp',
            'ember-mug-warmer',
            'solace-aroma-diffuser',
            'tidefold-weekender-bag',
            'pulse-smart-bottle',
            'hearth-throw-blanket',
            'aeroblend-portable-blender',
            'terra-recycled-tote'
        )
    ) AS target
);

DELETE pm
FROM product_media pm
WHERE pm.id IN (
    SELECT target.id
    FROM (
        SELECT pm2.id
        FROM product_media pm2
        INNER JOIN products p2
            ON p2.id = pm2.product_id
        WHERE p2.slug IN (
            'nova-wireless-earbuds',
            'echo-mechanical-keyboard',
            'halo-standing-desk-lamp',
            'ember-mug-warmer',
            'solace-aroma-diffuser',
            'tidefold-weekender-bag',
            'pulse-smart-bottle',
            'hearth-throw-blanket',
            'aeroblend-portable-blender',
            'terra-recycled-tote'
        )
    ) AS target
);

ALTER TABLE product_media AUTO_INCREMENT = 1;

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
    CONCAT(@media_base_url, media.file_name),
    CONCAT(@media_base_url, media.thumbnail_file),
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
WHERE p.slug IN (
    'nova-wireless-earbuds',
    'echo-mechanical-keyboard',
    'halo-standing-desk-lamp',
    'ember-mug-warmer',
    'solace-aroma-diffuser',
    'tidefold-weekender-bag',
    'pulse-smart-bottle',
    'hearth-throw-blanket',
    'aeroblend-portable-blender',
    'terra-recycled-tote'
)
ORDER BY p.id, media.sort_order;

COMMIT;

SET SQL_SAFE_UPDATES = @old_sql_safe_updates;
