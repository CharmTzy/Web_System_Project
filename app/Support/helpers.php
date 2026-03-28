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

function request_origin(): ?string
{
    $host = trim((string) ($_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? ''));

    if ($host === '') {
        return null;
    }

    return (request_is_secure() ? 'https' : 'http') . '://' . $host;
}

function url_origin(string $url): ?string
{
    $parts = parse_url($url);

    if (!is_array($parts) || empty($parts['scheme']) || empty($parts['host'])) {
        return null;
    }

    $origin = strtolower((string) $parts['scheme']) . '://' . strtolower((string) $parts['host']);
    $port = isset($parts['port']) ? (int) $parts['port'] : null;

    if ($port !== null) {
        $isDefaultPort = ($parts['scheme'] === 'http' && $port === 80)
            || ($parts['scheme'] === 'https' && $port === 443);

        if (!$isDefaultPort) {
            $origin .= ':' . $port;
        }
    }

    return $origin;
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

    $formActionSources = ["'self'"];

    foreach ([
        request_origin(),
        url_origin((string) env('APP_URL', '')),
        'https://checkout.stripe.com',
    ] as $origin) {
        if ($origin !== null && !in_array($origin, $formActionSources, true)) {
            $formActionSources[] = $origin;
        }
    }

    $contentSecurityPolicy = implode('; ', [
        "default-src 'self'",
        "base-uri 'self'",
        'form-action ' . implode(' ', $formActionSources),
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

function stripe_mode(?array $appConfig = null): string
{
    $keys = [
        trim((string) ($appConfig['stripe_secret_key'] ?? env('STRIPE_SECRET_KEY', ''))),
        trim((string) ($appConfig['stripe_publishable_key'] ?? env('STRIPE_PUBLISHABLE_KEY', ''))),
    ];

    foreach ($keys as $key) {
        if ($key === '') {
            continue;
        }

        if (
            str_starts_with($key, 'sk_test_')
            || str_starts_with($key, 'pk_test_')
            || str_starts_with($key, 'rk_test_')
        ) {
            return 'test';
        }

        if (
            str_starts_with($key, 'sk_live_')
            || str_starts_with($key, 'pk_live_')
            || str_starts_with($key, 'rk_live_')
        ) {
            return 'live';
        }
    }

    return 'unconfigured';
}

function payments_use_test_mode(?array $appConfig = null): bool
{
    return stripe_mode($appConfig) === 'test';
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

function delivery_status_label(string $status): string
{
    return ucwords(str_replace('_', ' ', trim($status)));
}

function delivery_status_badge_class(string $status): string
{
    return match ($status) {
        'pending' => 'pill-badge pill-badge--dark',
        'paid', 'processing' => 'pill-badge pill-badge--soft',
        'packed', 'out_for_delivery' => 'pill-badge pill-badge--accent',
        'shipped' => 'pill-badge pill-badge--info',
        'delivered' => 'pill-badge pill-badge--success',
        'cancelled' => 'pill-badge pill-badge--danger',
        default => 'pill-badge pill-badge--dark',
    };
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

function stripe_api_request(string $method, string $path, string $secretKey, array $params = []): array
{
    if ($secretKey === '') {
        throw new RuntimeException('Stripe secret key is not configured.');
    }

    $url = 'https://api.stripe.com/v1/' . ltrim($path, '/');
    $curl = curl_init();

    if ($curl === false) {
        throw new RuntimeException('Failed to initialize Stripe request.');
    }

    $upperMethod = strtoupper($method);
    $headers = [
        'Authorization: Bearer ' . $secretKey,
    ];

    if ($upperMethod === 'GET' && $params !== []) {
        $url .= '?' . http_build_query($params);
    }

    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_TIMEOUT, 25);
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $upperMethod);
    curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

    if (in_array($upperMethod, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($params));
    }

    $responseBody = curl_exec($curl);
    $httpCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $curlError = curl_error($curl);
    curl_close($curl);

    if (!is_string($responseBody)) {
        throw new RuntimeException($curlError !== '' ? $curlError : 'No response from Stripe API.');
    }

    $decoded = json_decode($responseBody, true);

    if (!is_array($decoded)) {
        throw new RuntimeException('Invalid response from Stripe API.');
    }

    if ($httpCode >= 400 || !empty($decoded['error'])) {
        $message = (string) ($decoded['error']['message'] ?? 'Stripe API request failed.');
        throw new RuntimeException($message);
    }

    return $decoded;
}

function stripe_verify_webhook_signature(string $payload, string $signatureHeader, string $webhookSecret, int $toleranceSeconds = 300): bool
{
    if ($payload === '' || $signatureHeader === '' || $webhookSecret === '') {
        return false;
    }

    $parts = [];

    foreach (explode(',', $signatureHeader) as $component) {
        [$key, $value] = array_pad(explode('=', trim($component), 2), 2, '');

        if ($key !== '') {
            $parts[$key][] = $value;
        }
    }

    $timestamp = isset($parts['t'][0]) ? (int) $parts['t'][0] : 0;
    $signatures = $parts['v1'] ?? [];

    if ($timestamp <= 0 || $signatures === []) {
        return false;
    }

    if (abs(time() - $timestamp) > $toleranceSeconds) {
        return false;
    }

    $signedPayload = $timestamp . '.' . $payload;
    $expected = hash_hmac('sha256', $signedPayload, $webhookSecret);

    foreach ($signatures as $signature) {
        if (is_string($signature) && hash_equals($expected, $signature)) {
            return true;
        }
    }

    return false;
}

function role_home_path(?string $role): string
{
    return match ($role) {
        'admin' => '/admin/',
        'seller' => '/seller/',
        'customer' => '/index.html',
        default => '/login.php',
    };
}

function require_role(string $requiredRole): void
{
    if (empty($_SESSION['user_id'])) {
        header('Location: /login.php');
        exit;
    }

    $currentRole = (string) ($_SESSION['user_role'] ?? '');

    if ($currentRole !== $requiredRole) {
        header('Location: ' . role_home_path($currentRole));
        exit;
    }
}

function require_any_role(array $allowedRoles): void
{
    if (empty($_SESSION['user_id'])) {
        header('Location: /login.php');
        exit;
    }

    $currentRole = (string) ($_SESSION['user_role'] ?? '');

    if (!in_array($currentRole, $allowedRoles, true)) {
        header('Location: ' . role_home_path($currentRole));
        exit;
    }
}

function redirect_if_role_disallowed(array $disallowedRoles): void
{
    if (empty($_SESSION['user_id'])) {
        return;
    }

    $currentRole = (string) ($_SESSION['user_role'] ?? '');

    if (in_array($currentRole, $disallowedRoles, true)) {
        header('Location: ' . role_home_path($currentRole));
        exit;
    }
}

function paginate_items(array $items, int $page = 1, int $perPage = 10): array
{
    $perPage = max(1, $perPage);
    $totalItems = count($items);
    $totalPages = max(1, (int) ceil($totalItems / $perPage));
    $page = max(1, min($page, $totalPages));
    $offset = ($page - 1) * $perPage;

    return [
        'items' => array_values(array_slice($items, $offset, $perPage)),
        'page' => $page,
        'per_page' => $perPage,
        'total_items' => $totalItems,
        'total_pages' => $totalPages,
        'from' => $totalItems === 0 ? 0 : $offset + 1,
        'to' => $totalItems === 0 ? 0 : min($totalItems, $offset + $perPage),
        'has_prev' => $page > 1,
        'has_next' => $page < $totalPages,
        'prev_page' => max(1, $page - 1),
        'next_page' => min($totalPages, $page + 1),
    ];
}
