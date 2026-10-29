<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

admin_require_auth();
ensure_social_marketing_schema();

header('Content-Type: application/json; charset=utf-8');

$q = mb_substr(trim((string) get_input('q', '')), 0, 120);
$allowVideo = (int) get_input('allow_video', 0) === 1;

$where = ["(mime_type LIKE 'image/%'" . ($allowVideo ? " OR mime_type LIKE 'video/%'" : '') . ')'];
$params = [];
if ($q !== '') {
    $where[] = '(file_name LIKE :q_file OR alt_text LIKE :q_alt OR tags LIKE :q_tags OR seo_title LIKE :q_seo)';
    $like = '%' . $q . '%';
    $params = [
        'q_file' => $like,
        'q_alt' => $like,
        'q_tags' => $like,
        'q_seo' => $like,
    ];
}

$rows = fetch_all(
    "SELECT id, file_path, file_name, mime_type, alt_text
     FROM media_assets
     WHERE " . implode(' AND ', $where) . "
     ORDER BY id DESC
     LIMIT 36",
    $params
);

$items = array_map(static function (array $row): array {
    $mime = (string) ($row['mime_type'] ?? '');
    return [
        'id' => (int) ($row['id'] ?? 0),
        'url' => stored_media_url((string) ($row['file_path'] ?? '')),
        'path' => (string) ($row['file_path'] ?? ''),
        'name' => (string) ($row['file_name'] ?? ''),
        'mime_type' => $mime,
        'media_type' => str_starts_with(strtolower($mime), 'video/') ? 'video' : 'image',
        'alt' => (string) ($row['alt_text'] ?? ''),
    ];
}, $rows);

echo json_encode(['items' => $items], JSON_UNESCAPED_SLASHES);
