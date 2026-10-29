<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

admin_require_auth();

if (!is_post()) {
    redirect('admin/trip_planner_settings.php');
}

guard_post_size();
require_csrf();
ensure_site_settings_schema();

$intro = trim((string) post_input('trip_planner_intro', ''));
$daysCsv = trim((string) post_input('trip_planner_days_csv', ''));
$themesText = (string) post_input('trip_planner_themes_text', '');
$metaTitle = trim((string) post_input('trip_planner_meta_title', ''));
$metaDescription = trim((string) post_input('trip_planner_meta_description', ''));

old_input_set($_POST);

// Normalize day options
$days = [];
foreach (preg_split('/\\s*,\\s*/', $daysCsv) ?: [] as $part) {
    $n = (int) trim((string) $part);
    if ($n >= 1 && $n <= 30) {
        $days[$n] = $n;
    }
}
$days = array_values($days);
sort($days);
if (!$days) {
    $days = [3, 4, 5, 6, 7, 8, 9, 10, 12, 14];
}
$daysCsv = implode(',', $days);

// Parse theme lines: slug | Label
$themes = [];
foreach (preg_split('/\\r?\\n+/', trim($themesText)) ?: [] as $line) {
    $line = trim((string) $line);
    if ($line === '') {
        continue;
    }
    $parts = array_map('trim', explode('|', $line, 2));
    $slug = strtolower(trim((string) ($parts[0] ?? '')));
    $slug = preg_replace('/[^a-z0-9-]+/', '', $slug) ?: '';
    $label = trim((string) ($parts[1] ?? ''));
    if ($slug === '' || $label === '') {
        continue;
    }
    $themes[] = ['slug' => $slug, 'label' => $label];
    if (count($themes) >= 30) {
        break;
    }
}
if (!$themes) {
    $themes = trip_planner_theme_options();
}

site_setting_set('trip_planner_intro', $intro !== '' ? $intro : trip_planner_intro_text());
site_setting_set('trip_planner_days_csv', $daysCsv);
site_setting_set('trip_planner_themes_json', json_encode($themes, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '[]');
site_setting_set('trip_planner_meta_title', $metaTitle);
site_setting_set('trip_planner_meta_description', $metaDescription);

old_input_clear();
flash_set('success', 'Trip planner settings updated.');
redirect('admin/trip_planner_settings.php');
