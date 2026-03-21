<?php

declare(strict_types=1);

namespace App\Support;

final class SampleCatalog
{
    private const MEDIA_BASE_URL = 'https://storage.googleapis.com/novamarket-product-images/product-images/';

    public static function categories(): array
    {
        return [
            [
                'id' => 1,
                'name' => 'Tech',
                'slug' => 'tech',
                'description' => 'Everyday devices and desktop upgrades.',
            ],
            [
                'id' => 2,
                'name' => 'Home Living',
                'slug' => 'home-living',
                'description' => 'Comfort-focused essentials for work and rest.',
            ],
            [
                'id' => 3,
                'name' => 'Kitchen',
                'slug' => 'kitchen',
                'description' => 'Countertop helpers for daily routines.',
            ],
            [
                'id' => 4,
                'name' => 'Wellness',
                'slug' => 'wellness',
                'description' => 'Products centered on hydration and calm.',
            ],
            [
                'id' => 5,
                'name' => 'Lifestyle',
                'slug' => 'lifestyle',
                'description' => 'Practical carry goods and everyday accessories.',
            ],
        ];
    }

    public static function products(): array
    {
        return array_map(
            static fn (array $product): array => self::withCloudMedia($product),
            [
                [
                'id' => 1,
                'seller_id' => 2,
                'seller_name' => 'Northwind Audio',
                'category_id' => 1,
                'category_name' => 'Tech',
                'category_slug' => 'tech',
                'sku' => 'MM-TECH-001',
                'name' => 'Nova Wireless Earbuds',
                'slug' => 'nova-wireless-earbuds',
                'short_description' => 'Noise-controlled earbuds with a pocket-size charging case.',
                'description' => 'Nova Wireless Earbuds are tuned for commuting and study sessions, with balanced sound, touch controls, and a secure in-ear fit.',
                'price' => 79.90,
                'compare_price' => 99.90,
                'stock_quantity' => 24,
                'image_url' => '/assets/images/products/nova-wireless-earbuds.svg',
                'rating' => 4.8,
                'review_count' => 128,
                'is_active' => true,
                'is_featured' => true,
                'created_at' => '2026-02-02 10:15:00',
            ],
            [
                'id' => 2,
                'seller_id' => 2,
                'seller_name' => 'Summit Office',
                'category_id' => 1,
                'category_name' => 'Tech',
                'category_slug' => 'tech',
                'sku' => 'MM-TECH-002',
                'name' => 'Echo Mechanical Keyboard',
                'slug' => 'echo-mechanical-keyboard',
                'short_description' => 'Compact tactile keyboard with hot-swappable switches.',
                'description' => 'Echo Mechanical Keyboard is built for long typing sessions, with sound-dampened keys, USB-C connectivity, and adjustable tilt.',
                'price' => 119.00,
                'compare_price' => null,
                'stock_quantity' => 12,
                'image_url' => '/assets/images/products/echo-mechanical-keyboard.svg',
                'rating' => 4.7,
                'review_count' => 74,
                'is_active' => true,
                'is_featured' => true,
                'created_at' => '2026-01-20 09:45:00',
            ],
            [
                'id' => 3,
                'seller_id' => 3,
                'seller_name' => 'Summit Office',
                'category_id' => 2,
                'category_name' => 'Home Living',
                'category_slug' => 'home-living',
                'sku' => 'MM-HOME-001',
                'name' => 'Halo Standing Desk Lamp',
                'slug' => 'halo-standing-desk-lamp',
                'short_description' => 'Adjustable LED lamp with warm-to-cool light presets.',
                'description' => 'Halo Standing Desk Lamp offers glare-free lighting, a compact footprint, and four brightness zones for home office setups.',
                'price' => 64.50,
                'compare_price' => 84.50,
                'stock_quantity' => 16,
                'image_url' => '/assets/images/products/halo-standing-desk-lamp.svg',
                'rating' => 4.6,
                'review_count' => 59,
                'is_active' => true,
                'is_featured' => false,
                'created_at' => '2026-01-15 16:30:00',
            ],
            [
                'id' => 4,
                'seller_id' => 4,
                'seller_name' => 'Canvas and Clay',
                'category_id' => 3,
                'category_name' => 'Kitchen',
                'category_slug' => 'kitchen',
                'sku' => 'MM-KITCHEN-001',
                'name' => 'Ember Mug Warmer',
                'slug' => 'ember-mug-warmer',
                'short_description' => 'Desk-friendly warmer that keeps coffee and tea at serving temperature.',
                'description' => 'Ember Mug Warmer uses low-profile heating with a splash-safe surface and one-button controls for shared workspaces.',
                'price' => 42.90,
                'compare_price' => null,
                'stock_quantity' => 31,
                'image_url' => '/assets/images/products/ember-mug-warmer.svg',
                'rating' => 4.5,
                'review_count' => 113,
                'is_active' => true,
                'is_featured' => false,
                'created_at' => '2026-02-10 08:10:00',
            ],
            [
                'id' => 5,
                'seller_id' => 4,
                'seller_name' => 'Harbor Home',
                'category_id' => 4,
                'category_name' => 'Wellness',
                'category_slug' => 'wellness',
                'sku' => 'MM-WELL-001',
                'name' => 'Solace Aroma Diffuser',
                'slug' => 'solace-aroma-diffuser',
                'short_description' => 'Ceramic diffuser with quiet mist modes and timer presets.',
                'description' => 'Solace Aroma Diffuser brings a subtle mist output, soft ambient light, and auto shutoff for evening routines.',
                'price' => 58.00,
                'compare_price' => 72.00,
                'stock_quantity' => 9,
                'image_url' => '/assets/images/products/solace-aroma-diffuser.svg',
                'rating' => 4.9,
                'review_count' => 98,
                'is_active' => true,
                'is_featured' => true,
                'created_at' => '2026-02-24 12:00:00',
            ],
            [
                'id' => 6,
                'seller_id' => 5,
                'seller_name' => 'Tide Carry Co.',
                'category_id' => 5,
                'category_name' => 'Lifestyle',
                'category_slug' => 'lifestyle',
                'sku' => 'MM-LIFE-001',
                'name' => 'TideFold Weekender Bag',
                'slug' => 'tidefold-weekender-bag',
                'short_description' => 'Water-resistant carry bag sized for short trips and day use.',
                'description' => 'TideFold Weekender Bag includes a padded laptop sleeve, trolley strap, and separate shoe compartment.',
                'price' => 89.00,
                'compare_price' => 110.00,
                'stock_quantity' => 7,
                'image_url' => '/assets/images/products/tidefold-weekender-bag.svg',
                'rating' => 4.7,
                'review_count' => 45,
                'is_active' => true,
                'is_featured' => false,
                'created_at' => '2026-01-08 14:20:00',
            ],
            [
                'id' => 7,
                'seller_id' => 3,
                'seller_name' => 'Harbor Home',
                'category_id' => 4,
                'category_name' => 'Wellness',
                'category_slug' => 'wellness',
                'sku' => 'MM-WELL-002',
                'name' => 'Pulse Smart Bottle',
                'slug' => 'pulse-smart-bottle',
                'short_description' => 'Insulated bottle with hydration reminders and a carry loop.',
                'description' => 'Pulse Smart Bottle tracks your refill routine, keeps drinks cool, and charges with a hidden USB-C port.',
                'price' => 54.00,
                'compare_price' => null,
                'stock_quantity' => 18,
                'image_url' => '/assets/images/products/pulse-smart-bottle.svg',
                'rating' => 4.4,
                'review_count' => 67,
                'is_active' => true,
                'is_featured' => false,
                'created_at' => '2026-02-12 11:15:00',
            ],
            [
                'id' => 8,
                'seller_id' => 4,
                'seller_name' => 'Harbor Home',
                'category_id' => 2,
                'category_name' => 'Home Living',
                'category_slug' => 'home-living',
                'sku' => 'MM-HOME-002',
                'name' => 'Hearth Throw Blanket',
                'slug' => 'hearth-throw-blanket',
                'short_description' => 'Soft woven blanket for lounge corners and reading nooks.',
                'description' => 'Hearth Throw Blanket uses a textured weave and machine-washable fibers for everyday living spaces.',
                'price' => 49.00,
                'compare_price' => 65.00,
                'stock_quantity' => 20,
                'image_url' => '/assets/images/products/hearth-throw-blanket.svg',
                'rating' => 4.8,
                'review_count' => 39,
                'is_active' => true,
                'is_featured' => false,
                'created_at' => '2026-02-18 15:10:00',
            ],
            [
                'id' => 9,
                'seller_id' => 4,
                'seller_name' => 'Canvas and Clay',
                'category_id' => 3,
                'category_name' => 'Kitchen',
                'category_slug' => 'kitchen',
                'sku' => 'MM-KITCHEN-002',
                'name' => 'AeroBlend Portable Blender',
                'slug' => 'aeroblend-portable-blender',
                'short_description' => 'Rechargeable smoothie blender for desks, dorms, and travel.',
                'description' => 'AeroBlend Portable Blender crushes soft fruit and ice, detaches for washing, and stores neatly in a drawer.',
                'price' => 68.00,
                'compare_price' => 82.00,
                'stock_quantity' => 14,
                'image_url' => '/assets/images/products/aeroblend-portable-blender.svg',
                'rating' => 4.3,
                'review_count' => 53,
                'is_active' => true,
                'is_featured' => true,
                'created_at' => '2026-02-05 13:35:00',
            ],
            [
                'id' => 10,
                'seller_id' => 5,
                'seller_name' => 'Tide Carry Co.',
                'category_id' => 5,
                'category_name' => 'Lifestyle',
                'category_slug' => 'lifestyle',
                'sku' => 'MM-LIFE-002',
                'name' => 'Terra Recycled Tote',
                'slug' => 'terra-recycled-tote',
                'short_description' => 'Structured everyday tote made from recycled canvas.',
                'description' => 'Terra Recycled Tote includes an interior bottle pocket, magnetic top closure, and reinforced handles.',
                'price' => 36.00,
                'compare_price' => null,
                'stock_quantity' => 26,
                'image_url' => '/assets/images/products/terra-recycled-tote.svg',
                'rating' => 4.6,
                'review_count' => 61,
                'is_active' => true,
                'is_featured' => false,
                'created_at' => '2026-01-29 17:05:00',
            ],
            ]
        );
    }

    private static function withCloudMedia(array $product): array
    {
        $slug = (string) $product['slug'];
        $name = (string) $product['name'];

        $product['image_url'] = self::mediaUrl($slug . '-1.jpg');
        $product['media'] = self::buildMediaGallery((int) $product['id'], $slug, $name);

        return $product;
    }

    private static function buildMediaGallery(int $productId, string $slug, string $name): array
    {
        $items = [
            [
                'type' => 'image',
                'file' => $slug . '-1.jpg',
                'thumbnail' => $slug . '-1.jpg',
                'alt_text' => $name . ' image 1',
                'is_primary' => true,
            ],
            [
                'type' => 'video',
                'file' => $slug . '-1.mp4',
                'thumbnail' => $slug . '-1.jpg',
                'alt_text' => $name . ' video 1',
                'is_primary' => false,
            ],
            [
                'type' => 'image',
                'file' => $slug . '-2.jpg',
                'thumbnail' => $slug . '-2.jpg',
                'alt_text' => $name . ' image 2',
                'is_primary' => false,
            ],
        ];

        if (in_array($slug, ['nova-wireless-earbuds', 'echo-mechanical-keyboard'], true)) {
            $items[] = [
                'type' => 'video',
                'file' => $slug . '-2.mp4',
                'thumbnail' => $slug . '-2.jpg',
                'alt_text' => $name . ' video 2',
                'is_primary' => false,
            ];
        }

        $items[] = [
            'type' => 'image',
            'file' => $slug . '-3.jpg',
            'thumbnail' => $slug . '-3.jpg',
            'alt_text' => $name . ' image 3',
            'is_primary' => false,
        ];

        $items[] = [
            'type' => 'image',
            'file' => $slug . '.jpg',
            'thumbnail' => $slug . '.jpg',
            'alt_text' => $name . ' image 4',
            'is_primary' => false,
        ];

        return array_map(
            static fn (array $item, int $index): array => [
                'id' => 'sample-media-' . $productId . '-' . ($index + 1),
                'type' => $item['type'],
                'url' => self::mediaUrl($item['file']),
                'thumbnail_url' => self::mediaUrl($item['thumbnail']),
                'alt_text' => $item['alt_text'],
                'sort_order' => $index + 1,
                'is_primary' => $item['is_primary'],
            ],
            $items,
            array_keys($items)
        );
    }

    private static function mediaUrl(string $fileName): string
    {
        return self::MEDIA_BASE_URL . $fileName;
    }
}
