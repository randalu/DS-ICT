<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

if (admin_check()) {
    redirect('admin/dashboard.php');
}

redirect('admin/login.php');