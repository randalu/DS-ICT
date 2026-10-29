<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

$type = trim((string) get_input('type', ''));
$id = (int) get_input('id', 0);
$row = null;

if ($id > 0) {
    if ($type === 'destination') {
        $row = fetch_one("SELECT id, name AS title, slug, excerpt, hero_image, cover_image FROM destinations WHERE id = :id AND is_published = 1 LIMIT 1", ['id' => $id]);
        $prefix = 'destinations';
        $label = 'Destination';
    } elseif ($type === 'itinerary') {
        $row = fetch_one("SELECT id, name AS title, slug, excerpt, hero_image, og_image FROM itineraries WHERE id = :id AND is_published = 1 LIMIT 1", ['id' => $id]);
        $prefix = 'itineraries';
        $label = 'Itinerary';
    } elseif ($type === 'experience' && db_table_exists('experiences')) {
        $row = fetch_one("SELECT id, title, slug, excerpt, hero_image, og_image FROM experiences WHERE id = :id AND is_published = 1 LIMIT 1", ['id' => $id]);
        $prefix = 'experiences';
        $label = 'Experience';
    } elseif ($type === 'blog') {
        $row = fetch_one("SELECT id, title, slug, excerpt, hero_image, og_image FROM blog_posts WHERE id = :id AND is_published = 1 LIMIT 1", ['id' => $id]);
        $prefix = 'blog';
        $label = 'Travel Guide';
    }
}

if (!$row) {
    http_response_code(404);
    echo json_encode(['error' => 'Not found']);
    exit;
}

$image = trim((string) ($row['cover_image'] ?? '')) ?: trim((string) ($row['hero_image'] ?? '')) ?: trim((string) ($row['og_image'] ?? ''));

echo json_encode([
    'label' => $label ?? ucfirst($type),
    'title' => (string) ($row['title'] ?? ''),
    'url' => base_url(($prefix ?? '') . '/' . (string) ($row['slug'] ?? '')),
    'image' => $image !== '' ? stored_media_url($image) : DEFAULT_OG_IMAGE,
    'excerpt' => mb_substr(trim(strip_tags((string) ($row['excerpt'] ?? ''))), 0, 140),
], JSON_UNESCAPED_SLASHES);
