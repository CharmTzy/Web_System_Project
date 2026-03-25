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

function request_is_secure(): bool
{
    $https = strtolower((string) ($_SERVER['HTTPS'] ?? ''));
    $forwardedProto = strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''));
    $forwardedSsl = strtolower((string) ($_SERVER['HTTP_X_FORWARDED_SSL'] ?? ''));
    $serverPort = (string) ($_SERVER['SERVER_PORT'] ?? '');

    return ($https !== '' && $https !== 'off')
        || $forwardedProto === 'https'
        || $forwardedSsl === 'on'
        || $serverPort === '443';
}

function bootstrap_session_security(): void
{
    if (session_status() !== PHP_SESSION_NONE) {
        return;
    }

    $isSecure = request_is_secure();

    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_samesite', 'Lax');
    ini_set('session.cookie_secure', $isSecure ? '1' : '0');

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => $isSecure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}

function send_security_headers(): void
{
    if (headers_sent()) {
        return;
    }

    $contentSecurityPolicy = implode('; ', [
        "default-src 'self'",
        "base-uri 'self'",
        "form-action 'self'",
        "frame-ancestors 'none'",
        "object-src 'none'",
        "img-src 'self' data: https://storage.googleapis.com",
        "font-src 'self' data: https://fonts.gstatic.com",
        "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdn.jsdelivr.net",
        "script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net",
        "connect-src 'self' ws: wss: https://cdn.jsdelivr.net",
        "media-src 'self' https://storage.googleapis.com",
    ]);

    header('X-Frame-Options: DENY');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: accelerometer=(), camera=(), geolocation=(), gyroscope=(), microphone=(), payment=(), usb=()');
    header('Cross-Origin-Opener-Policy: same-origin');
    header('Content-Security-Policy: ' . $contentSecurityPolicy);

    if (request_is_secure()) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
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

function product_url(array $product): string
{
    $params = http_build_query([
        'id' => (string) ($product['id'] ?? ''),
        'slug' => (string) ($product['slug'] ?? ''),
    ]);

    return '/product.html' . ($params !== '' ? '?' . $params : '');
}

function chat_websocket_url(array $appConfig): string
{
    $configured = trim((string) ($appConfig['chat_websocket_url'] ?? ''));

    if ($configured !== '') {
        return $configured;
    }

    $host = trim((string) ($_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost'));
    $host = preg_replace('/:\d+$/', '', $host) ?: 'localhost';
    $scheme = request_is_secure() ? 'wss' : 'ws';
    $port = (int) ($appConfig['chat_websocket_port'] ?? 8080);
    $path = '/' . ltrim((string) ($appConfig['chat_websocket_path'] ?? '/ws/chat'), '/');

    return sprintf('%s://%s:%d%s', $scheme, $host, $port, $path);
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

function report_exception(Throwable $exception, string $context = ''): void
{
    $prefix = $context !== '' ? '[' . $context . '] ' : '';

    error_log(sprintf(
        '%s%s in %s:%d',
        $prefix,
        $exception->getMessage(),
        $exception->getFile(),
        $exception->getLine()
    ));
}

function service_unavailable_message(): string
{
    return 'This service is temporarily unavailable. Please try again shortly.';
}

function render_error_page(int $status, string $title, string $message): never
{
    if (!headers_sent()) {
        http_response_code($status);
        header('Content-Type: text/html; charset=UTF-8');
    }

    echo '<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>'
        . e($title)
        . ' | NovaMarket</title><style>body{margin:0;font-family:Manrope,Arial,sans-serif;background:#f4f7fb;color:#17324d}main{min-height:100vh;display:grid;place-items:center;padding:2rem}.panel{max-width:36rem;background:#fff;border:1px solid #d7e3f6;border-radius:24px;padding:2rem 2.25rem;box-shadow:0 24px 60px rgba(15,23,42,.08)}h1{margin:0 0 .75rem;font-size:2rem}p{margin:0;color:#506b8a;line-height:1.6}a{display:inline-block;margin-top:1.25rem;color:#0d67d5;font-weight:700;text-decoration:none}</style></head><body><main><section class="panel"><h1>'
        . e($title)
        . '</h1><p>'
        . e($message)
        . '</p><a href="/">Back to home</a></section></main></body></html>';
    exit;
}

function client_ip(): string
{
    $ip = trim((string) ($_SERVER['REMOTE_ADDR'] ?? ''));

    return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : 'unknown';
}

function rate_limit_storage_path(string $key): string
{
    return rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'novamarket-rate-limit-' . hash('sha256', $key) . '.json';
}

function read_rate_limit_state(string $key, int $windowSeconds): array
{
    $path = rate_limit_storage_path($key);
    $defaultState = [
        'attempts' => 0,
        'first_attempt_at' => time(),
    ];

    if (!is_file($path)) {
        return $defaultState;
    }

    $contents = file_get_contents($path);
    $state = is_string($contents) ? json_decode($contents, true) : null;

    if (!is_array($state)) {
        return $defaultState;
    }

    $attempts = max(0, (int) ($state['attempts'] ?? 0));
    $firstAttemptAt = (int) ($state['first_attempt_at'] ?? time());

    if (time() - $firstAttemptAt >= $windowSeconds) {
        return $defaultState;
    }

    return [
        'attempts' => $attempts,
        'first_attempt_at' => $firstAttemptAt,
    ];
}

function write_rate_limit_state(string $key, array $state): void
{
    $path = rate_limit_storage_path($key);

    file_put_contents($path, json_encode($state, JSON_THROW_ON_ERROR), LOCK_EX);
}

function rate_limit_status(string $key, int $limit, int $windowSeconds): array
{
    $state = read_rate_limit_state($key, $windowSeconds);
    $retryAfter = max(0, $windowSeconds - (time() - (int) $state['first_attempt_at']));
    $isLimited = (int) $state['attempts'] >= $limit;

    return [
        'attempts' => (int) $state['attempts'],
        'remaining' => max(0, $limit - (int) $state['attempts']),
        'retry_after' => $isLimited ? max(1, $retryAfter) : 0,
        'is_limited' => $isLimited,
    ];
}

function rate_limit_record_failure(string $key, int $windowSeconds): array
{
    $state = read_rate_limit_state($key, $windowSeconds);

    if ((int) $state['attempts'] === 0) {
        $state['first_attempt_at'] = time();
    }

    $state['attempts'] = (int) $state['attempts'] + 1;

    write_rate_limit_state($key, $state);

    return $state;
}

function clear_rate_limit(string $key): void
{
    $path = rate_limit_storage_path($key);

    if (is_file($path)) {
        @unlink($path);
    }
}

function is_safe_user_error(Throwable $exception): bool
{
    return $exception instanceof InvalidArgumentException
        || $exception instanceof RuntimeException;
}

function safe_exception_message(Throwable $exception, string $fallback = 'Something went wrong. Please try again.'): string
{
    return is_safe_user_error($exception)
        ? $exception->getMessage()
        : $fallback;
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

function flash(string $key, mixed $value = null): mixed
{
    if (!isset($_SESSION['_flash']) || !is_array($_SESSION['_flash'])) {
        $_SESSION['_flash'] = [];
    }

    if (func_num_args() > 1) {
        $_SESSION['_flash'][$key] = $value;
        return null;
    }

    if (!array_key_exists($key, $_SESSION['_flash'])) {
        return null;
    }

    $stored = $_SESSION['_flash'][$key];
    unset($_SESSION['_flash'][$key]);

    return $stored;
}
