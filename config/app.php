<?php

declare(strict_types=1);

return [
    'name' => (string) env('APP_NAME', 'Meridian Mart'),
    'timezone' => (string) env('APP_TIMEZONE', 'Asia/Singapore'),
    'free_shipping_threshold' => (float) env('FREE_SHIPPING_THRESHOLD', 120),
    'flat_shipping_rate' => (float) env('FLAT_SHIPPING_RATE', 8.90),
];

