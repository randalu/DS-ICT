<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/blog_widgets.php';

admin_require_auth();

if (!is_post()) {
    redirect('admin/blog_settings.php');
}

guard_post_size();
require_csrf();

if (!db_table_exists('blog_widget_defaults')) {
    flash_set('error', 'Run the migration database/migrations/2026-04-27-blog-widgets-v2.sql before saving global defaults.');
    redirect('admin/blog_settings.php');
}

$rawJson = trim((string) post_input('widgets_v2_json', ''));
$decoded = $rawJson !== '' ? json_decode($rawJson, true) : null;
if (!is_array($decoded)) {
    $decoded = ['version' => 2, 'widgets' => []];
}

$config = validate_widget_config($decoded);
$json = json_encode($config, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
if ($json === false) {
    flash_set('error', 'Failed to encode widget config.');
    redirect('admin/blog_settings.php');
}

try {
    execute_query(
        "INSERT INTO blog_widget_defaults (id, config_json) VALUES (1, :j)
         ON DUPLICATE KEY UPDATE config_json = :j",
        ['j' => $json]
    );
    flash_set('success', 'Global widget defaults saved.');
} catch (Throwable $e) {
    flash_set('error', 'Failed to save defaults.');
}

redirect('admin/blog_settings.php');
