<?php

declare(strict_types=1);

if (!function_exists('mb_strlen')) {
    function mb_strlen(string $string, ?string $encoding = null): int
    {
        return strlen($string);
    }
}

if (!function_exists('mb_substr')) {
    function mb_substr(string $string, int $start, ?int $length = null, ?string $encoding = null): string
    {
        return $length === null
            ? substr($string, $start)
            : substr($string, $start, $length);
    }
}

if (!function_exists('mb_strtolower')) {
    function mb_strtolower(string $string, ?string $encoding = null): string
    {
        return strtolower($string);
    }
}

function load_env(string $path): void
{
    if (!is_file($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    if ($lines === false) {
        return;
    }

    foreach ($lines as $line) {
        $trimmed = trim($line);

        if ($trimmed === '' || str_starts_with($trimmed, '#')) {
            continue;
        }

        [$name, $value] = array_pad(explode('=', $trimmed, 2), 2, '');
        $name = trim($name);

        if ($name === '') {
            continue;
        }

        $value = trim(trim($value), "\"'");

        if (array_key_exists($name, $_ENV)) {
            continue;
        }

        $_ENV[$name] = $value;
        $_SERVER[$name] = $value;
        putenv($name . '=' . $value);
    }
}

function env(string $key, mixed $default = null): mixed
{
    $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);

    if ($value === false || $value === null || $value === '') {
        return $default;
    }

    return $value;
}

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function money(float|int|string $value): string
{
    return '$' . number_format((float) $value, 2);
}

function asset(string $path): string
{
    return '/assets/' . ltrim($path, '/');
}

function render(string $view, array $data = []): string
{
    $file = dirname(__DIR__, 2) . '/resources/views/' . $view . '.php';

    if (!is_file($file)) {
        throw new RuntimeException('View not found: ' . $view);
    }

    extract($data, EXTR_SKIP);

    ob_start();
    require $file;

    return (string) ob_get_clean();
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function verify_csrf(?string $token): bool
{
    return is_string($token)
        && isset($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

function bool_from_input(mixed $value): bool
{
    if (is_bool($value)) {
        return $value;
    }

    $normalized = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

    return $normalized ?? false;
}

function product_manager_seed_products(?string $sellerName = null): array
{
    $products = \App\Support\SampleCatalog::products();

    if ($sellerName !== null && $sellerName !== '') {
        $products = array_values(array_filter(
            $products,
            static fn (array $product): bool => $product['seller_name'] === $sellerName
        ));
    }

    return array_map(static function (array $product): array {
        return [
            'id' => 'catalog-' . (string) $product['id'],
            'name' => $product['name'],
            'category' => $product['category_name'],
            'price' => (float) $product['price'],
            'stock' => (int) $product['stock_quantity'],
            'sku' => $product['sku'],
            'status' => !$product['is_active']
                ? 'Draft'
                : ($product['stock_quantity'] > 0 ? 'Active' : 'Out of Stock'),
            'featured' => (bool) $product['is_featured'],
            'image' => $product['image_url'],
            'description' => $product['short_description'] ?: $product['description'],
            'updated_at' => $product['created_at'],
            'seller_name' => $product['seller_name'],
        ];
    }, $products);
}

function product_manager_categories(?string $sellerName = null): array
{
    $products = product_manager_seed_products($sellerName);

    if ($products === []) {
        $categories = array_map(
            static fn (array $category): string => $category['name'],
            \App\Support\SampleCatalog::categories()
        );
    } else {
        $categories = array_values(array_unique(array_map(
            static fn (array $product): string => $product['category'],
            $products
        )));
    }

    sort($categories);

    return $categories;
}
