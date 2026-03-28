<?php

declare(strict_types=1);

$config = require dirname(__DIR__, 2) . '/bootstrap.php';

require_role('customer');

flash('orders_notice', 'Payment settings are not available on this page right now. Review your orders instead.');
header('Location: /customer/orders.php');
exit;
