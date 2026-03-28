<?php

declare(strict_types=1);

$config = require dirname(__DIR__) . '/bootstrap.php';

redirect_if_role_disallowed(['admin', 'seller']);

$pageTitle = 'Contact';
$appName = $config['app']['name'];
$pageSkeletonVariant = 'legal';

require dirname(__DIR__) . '/resources/views/layouts/header.php';
echo render('legal/contact-page');
require dirname(__DIR__) . '/resources/views/layouts/footer.php';
