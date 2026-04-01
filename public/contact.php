<?php

declare(strict_types=1);

$config = require dirname(__DIR__) . '/bootstrap.php';

$pageTitle = 'Contact';
$appName = $config['app']['name'];
$pageSkeletonVariant = 'legal';
$appUrl = (string) ($config['app']['url'] ?? '');
$siteHost = (string) (parse_url($appUrl, PHP_URL_HOST) ?: $appUrl ?: 'NovaMarket');
$supportProfile = [
    'app_name' => $appName,
    'site_url' => $appUrl,
    'site_host' => $siteHost,
    'support_email' => trim((string) ($config['app']['support_email'] ?? '')),
    'support_phone' => trim((string) ($config['app']['support_phone'] ?? '')),
    'support_location' => trim((string) ($config['app']['support_location'] ?? '')),
    'payments_in_test_mode' => payments_use_test_mode($config['app']),
];

require dirname(__DIR__) . '/resources/views/layouts/header.php';
echo render('legal/contact-page', ['supportProfile' => $supportProfile]);
require dirname(__DIR__) . '/resources/views/layouts/footer.php';
