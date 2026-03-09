<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require __DIR__ . '/app/Support/helpers.php';

load_env(__DIR__ . '/.env');

spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';

    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $path = __DIR__ . '/app/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';

    if (is_file($path)) {
        require $path;
    }
});

date_default_timezone_set((string) env('APP_TIMEZONE', 'Asia/Singapore'));

return [
    'app' => require __DIR__ . '/config/app.php',
    'database' => require __DIR__ . '/config/database.php',
];

