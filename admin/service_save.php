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
$redirectTarget = $id > 0 ? 'admin/service_form.php?id=' . $id : 'admin/service_form.php';

$data = [
    'icon'        => trim((string) post_input('icon', '')),
    'title'       => trim((string) post_input('title', '')),
    'description' => trim((string) post_input('description', '')),
    'sort_order'  => (int) post_input('sort_order', 0),
    'is_active'   => post_input('is_active') ? 1 : 0,
];

old_input_set($_POST);

$errors = [];
if ($data['title'] === '') {
    $errors[] = 'Title is required.';
}
if (mb_strlen($data['title']) > 200) {
    $errors[] = 'Title must be 200 characters or fewer.';
}
if (mb_strlen($data['icon']) > 20) {
    $errors[] = 'Icon must be 20 characters or fewer.';
}

if ($id > 0) {
    $record = fetch_one('SELECT id FROM services WHERE id = :id LIMIT 1', ['id' => $id]);
    if (!$record) {
        old_input_clear();
        flash_set('error', 'Service not found.');
        redirect('admin/services.php');
    }
}

if ($errors) {
    flash_set('error', implode(' ', $errors));
    redirect($redirectTarget);
}

try {
    if ($id > 0) {
        execute_query(
            "UPDATE services SET icon=:icon, title=:title, description=:description,
             sort_order=:sort_order, is_active=:is_active WHERE id=:id",
            array_merge($data, ['id' => $id])
        );
    } else {
        execute_query(
            "INSERT INTO services (icon, title, description, sort_order, is_active)
             VALUES (:icon, :title, :description, :sort_order, :is_active)",
            $data
        );
        $id = (int) last_insert_id();
    }

    old_input_clear();
    flash_set('success', 'Service saved.');
    redirect('admin/services.php');
} catch (Throwable $e) {
    flash_set('error', 'Failed to save service.');
    redirect($redirectTarget);
}
