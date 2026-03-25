<?php

declare(strict_types=1);

return [
    'name' => (string) env('APP_NAME', 'NovaMarket'),
    'timezone' => (string) env('APP_TIMEZONE', 'Asia/Singapore'),
    'free_shipping_threshold' => (float) env('FREE_SHIPPING_THRESHOLD', 120),
    'flat_shipping_rate' => (float) env('FLAT_SHIPPING_RATE', 8.90),
    'chat_websocket_url' => (string) env('CHAT_WEBSOCKET_URL', ''),
    'chat_websocket_path' => (string) env('CHAT_WEBSOCKET_PATH', '/ws/chat'),
    'chat_websocket_port' => (int) env('CHAT_WEBSOCKET_PORT', 8080),
    'chat_server_host' => (string) env('CHAT_SERVER_HOST', '0.0.0.0'),
    'chat_server_port' => (int) env('CHAT_SERVER_PORT', 8080),
];
