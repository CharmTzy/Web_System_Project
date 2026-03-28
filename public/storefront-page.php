<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

redirect_if_role_disallowed(['admin', 'seller']);

$page = trim((string) ($_GET['page'] ?? ''));
$allowedPages = [
    'cart.html' => __DIR__ . '/cart.html',
    'product.html' => __DIR__ . '/product.html',
];

$target = $allowedPages[$page] ?? null;

if ($target === null || !is_file($target)) {
    http_response_code(404);
    exit('Not Found');
}

header('Content-Type: text/html; charset=UTF-8');
readfile($target);
exit;
