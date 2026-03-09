<?php

declare(strict_types=1);

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

