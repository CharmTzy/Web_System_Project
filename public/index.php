<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

if (($_SESSION['user_role'] ?? '') === 'admin') {
    header('Location: /admin/', true, 302);
    exit;
}

header('Content-Type: text/html; charset=UTF-8');
readfile(__DIR__ . '/index.html');
exit;
