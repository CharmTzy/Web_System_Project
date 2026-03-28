<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

require_any_role(['admin', 'seller']);

$redirect = ($_SESSION['user_role'] ?? '') === 'admin'
    ? '/admin/products.php'
    : '/seller/products.php';

header('Location: ' . $redirect, true, 302);
exit;
