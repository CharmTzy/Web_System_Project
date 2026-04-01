<?php

declare(strict_types=1);

$config = require dirname(__DIR__, 2) . '/bootstrap.php';

(new \App\Controllers\Admin\ProductEditController($config))->handle();
