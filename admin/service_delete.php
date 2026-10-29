<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

admin_require_auth();

if (!is_post()) {
    redirect('admin/services.php');
}

guard_post_size();
require_csrf();

$id = (int) post_input('id', 0);

if ($id <= 0) {
    flash_set('error', 'Invalid service ID.');
    redirect('admin/services.php');
}

$record = fetch_one('SELECT id FROM services WHERE id = :id LIMIT 1', ['id' => $id]);
if (!$record) {
    flash_set('error', 'Service not found.');
    redirect('admin/services.php');
}

try {
    execute_query("DELETE FROM services WHERE id = :id", ['id' => $id]);
    flash_set('success', 'Service deleted.');
} catch (Throwable $e) {
    flash_set('error', 'Failed to delete service.');
}

redirect('admin/services.php');
