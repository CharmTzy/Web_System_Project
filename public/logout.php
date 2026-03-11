<?php

declare(strict_types=1);

$config = require dirname(__DIR__) . '/bootstrap.php';

$database = new \App\Support\Database($config['database']);
$connection = $database->connection();

if ($connection) {
    $authService = new \App\Services\AuthService(
        new \App\Repositories\UserRepository($connection)
    );
    $authService->logout();
} else {
    $_SESSION = [];
    session_destroy();
}

header('Location: /login.php');
exit;
