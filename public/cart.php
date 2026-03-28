<?php

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

redirect_if_role_disallowed(['admin']);

header('Location: /cart.html', true, 302);
exit;
