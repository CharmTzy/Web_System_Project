<?php

declare(strict_types=1);

header('Content-Type: application/json');

$config = require dirname(__DIR__, 2) . '/bootstrap.php';

$loggedIn = !empty($_SESSION['user_id']);

echo json_encode([
    'ok' => true,
    'logged_in' => $loggedIn,
    'user' => $loggedIn ? [
        'id' => $_SESSION['user_id'],
        'name' => $_SESSION['user_name'] ?? '',
        'role' => $_SESSION['user_role'] ?? '',
    ] : null,
]);
