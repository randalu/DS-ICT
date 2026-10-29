<?php
declare(strict_types=1);
require_once __DIR__.'/../includes/auth.php';
admin_require_auth();
http_response_code(410);
$adminTitle='Reference links removed';$adminPage='tours';require __DIR__.'/_top.php';
echo '<p>Separate planning-guide links have been removed. Edit the route, days and stays inside the Tour programme.</p><a href="'.e(base_url('admin/tours.php')).'">Manage Tours</a>';
require __DIR__.'/_bottom.php';
