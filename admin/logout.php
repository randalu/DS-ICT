<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

admin_logout();
flash_set('success', 'You have been logged out.');
redirect('admin/login.php');