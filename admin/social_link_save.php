<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

admin_require_auth();

if (!is_post()) {
    redirect('admin/social_links.php');
}

guard_post_size();
require_csrf();

$id = (int) post_input('id', 0);
if ($id <= 0) {
    flash_set('error', 'Invalid social link slot.');
    redirect('admin/social_links.php');
}

$record = fetch_one("SELECT * FROM social_links WHERE id = :id LIMIT 1", ['id' => $id]) ?: null;
if (!$record) {
    flash_set('error', 'Social link slot not found.');
    redirect('admin/social_links.php');
}

$data = [
    'label'      => trim((string) post_input('label', '')),
    'url'        => trim((string) post_input('url', '')),
    'icon_text'  => trim((string) post_input('icon_text', '')),
    'sort_order' => (int) post_input('sort_order', 0),
    'is_active'  => post_input('is_active') ? 1 : 0,
];

old_input_set($_POST);

$errors = [];

if ($data['label'] === '') {
    $errors[] = 'Label is required.';
}
if ($data['url'] !== '') {
    $scheme = strtolower((string) parse_url($data['url'], PHP_URL_SCHEME));
    if (!filter_var($data['url'], FILTER_VALIDATE_URL) || !in_array($scheme, ['http', 'https'], true)) {
        $errors[] = 'URL must be a valid http(s) address.';
    }
}

if ($errors) {
    flash_set('error', implode(' ', $errors));
    redirect('admin/social_link_form.php?id=' . $id);
}

$iconImage = (string) ($record['icon_image'] ?? '');
if (post_input('remove_icon_image')) {
    $iconImage = '';
}

try {
    if (!empty($_FILES['icon_image']['name'])) {
        $uploaded = upload_image($_FILES['icon_image'], 'social');
        if ($uploaded !== null) {
            $iconImage = $uploaded;
        }
    }

    execute_query(
        "UPDATE social_links
         SET label = :label,
             url = :url,
             icon_text = :icon_text,
             icon_image = :icon_image,
             sort_order = :sort_order,
             is_active = :is_active
         WHERE id = :id",
        array_merge($data, [
            'icon_image' => $iconImage !== '' ? $iconImage : null,
            'id' => $id,
        ])
    );

    old_input_clear();
    flash_set('success', 'Social link saved.');
    redirect('admin/social_links.php');
} catch (Throwable $e) {
    flash_set('error', 'Failed to save social link.');
    redirect('admin/social_link_form.php?id=' . $id);
}
