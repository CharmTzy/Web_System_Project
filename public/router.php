<?php

declare(strict_types=1);

$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$publicDir = __DIR__;
$filePath = realpath($publicDir . $uri);

if ($uri !== '/' && $filePath !== false && str_starts_with($filePath, $publicDir . DIRECTORY_SEPARATOR) && is_file($filePath)) {
    return false;
}

if ($uri === '/') {
    header('Location: /index.html', true, 302);
    return true;
}

if ($uri === '/index.php') {
    header('Location: /index.html', true, 302);
    return true;
}

if ($uri === '/cart.php') {
    header('Location: /cart.html', true, 302);
    return true;
}

if ($uri === '/index.html') {
    require $publicDir . '/index.php';
    return true;
}

if ($uri === '/cart.html') {
    require $publicDir . '/cart.php';
    return true;
}

return false;
