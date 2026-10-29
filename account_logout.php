<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/customer_auth.php';

customer_logout();
flash_set('success', 'You have been signed out.');
redirect('account/login');
