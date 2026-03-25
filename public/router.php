<?php

declare(strict_types=1);

$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$publicDir = __DIR__;
$filePath = realpath($publicDir . $uri);

if ($uri !== '/' && $filePath !== false && str_starts_with($filePath, $publicDir . DIRECTORY_SEPARATOR) && is_file($filePath)) {
    return false;
}

if ($uri === '/' || $uri === '/index.php') {
    header('Location: /index.html', true, 302);
    return true;
}

if ($uri === '/cart.php') {
    if (empty($_SESSION['user_id'])) {
        header('Location: /login.php?redirect=%2Fcart.html&cart_notice=full-cart', true, 302);
        return true;
    }

    header('Location: /cart.html', true, 302);
    return true;
}

return false;
