<?php

declare(strict_types=1);

return [
    'name' => (string) env('APP_NAME', 'NovaMarket'),
    'url' => rtrim((string) env('APP_URL', 'http://localhost:8000'), '/'),
    'timezone' => (string) env('APP_TIMEZONE', 'Asia/Singapore'),
    'currency' => (string) env('APP_CURRENCY', 'sgd'),
    'support_email' => (string) env('SUPPORT_EMAIL', ''),
    'abuse_report_email' => (string) env('ABUSE_REPORT_EMAIL', ''),
    'support_phone' => (string) env('SUPPORT_PHONE', ''),
    'support_location' => (string) env('SUPPORT_LOCATION', ''),
    'free_shipping_threshold' => (float) env('FREE_SHIPPING_THRESHOLD', 120),
    'flat_shipping_rate' => (float) env('FLAT_SHIPPING_RATE', 8.9),
    'stripe_secret_key' => (string) env('STRIPE_SECRET_KEY', ''),
    'stripe_publishable_key' => (string) env('STRIPE_PUBLISHABLE_KEY', ''),
    'stripe_webhook_secret' => (string) env('STRIPE_WEBHOOK_SECRET', ''),
    'stripe_webhook_tolerance' => (int) env('STRIPE_WEBHOOK_TOLERANCE', 300),
    'google_cloud_storage_bucket' => (string) env('GOOGLE_CLOUD_STORAGE_BUCKET', ''),
    'google_cloud_storage_product_prefix' => trim((string) env('GOOGLE_CLOUD_STORAGE_PRODUCT_PREFIX', 'product-images'), '/'),
    'google_cloud_storage_review_prefix' => trim((string) env('GOOGLE_CLOUD_STORAGE_REVIEW_PREFIX', 'review-images'), '/'),
    'chat_websocket_url' => (string) env('CHAT_WEBSOCKET_URL', ''),
    'chat_websocket_path' => (string) env('CHAT_WEBSOCKET_PATH', '/ws/chat'),
    'chat_websocket_port' => (int) env('CHAT_WEBSOCKET_PORT', 8080),
    'chat_server_host' => (string) env('CHAT_SERVER_HOST', '0.0.0.0'),
    'chat_server_port' => (int) env('CHAT_SERVER_PORT', 8080),
];
