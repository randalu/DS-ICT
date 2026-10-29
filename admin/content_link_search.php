<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

admin_require_auth();

header('Content-Type: application/json; charset=utf-8');

$q = mb_substr(trim((string) get_input('q', '')), 0, 120);
$like = '%' . $q . '%';
$items = [];

if ($q !== '') {
    $selectColumn = static function (string $table, string $column, string $alias, string $fallback = 'NULL'): string {
        return db_column_exists($table, $column)
            ? $column . ' AS ' . $alias
            : $fallback . ' AS ' . $alias;
    };

    $queryParts = [];
    $params = [];
    $addContentQuery = static function (string $table, string $titleColumn, string $type) use ($like, $selectColumn, &$queryParts, &$params): void {
        if (
            !db_table_exists($table)
            || !db_column_exists($table, 'id')
            || !db_column_exists($table, $titleColumn)
            || !db_column_exists($table, 'slug')
        ) {
            return;
        }

        $titleParam = $type . '_title';
        $params[$titleParam] = $like;
        $searchParts = [$titleColumn . ' LIKE :' . $titleParam];
        if (db_column_exists($table, 'excerpt')) {
            $excerptParam = $type . '_excerpt';
            $params[$excerptParam] = $like;
            $searchParts[] = 'excerpt LIKE :' . $excerptParam;
        }

        $whereParts = ['(' . implode(' OR ', $searchParts) . ')'];
        if (db_column_exists($table, 'is_published')) {
            array_unshift($whereParts, 'is_published = 1');
        }

        $typeLiteral = str_replace("'", "''", $type);
        $queryParts[] = '(SELECT id, ' . $titleColumn . ' AS title, slug, '
            . $selectColumn($table, 'excerpt', 'excerpt', "''") . ', '
            . $selectColumn($table, 'hero_image', 'hero_image') . ', '
            . $selectColumn($table, 'cover_image', 'cover_image') . ', '
            . $selectColumn($table, 'og_image', 'og_image') . ", '"
            . $typeLiteral . "' AS type FROM " . $table
            . ' WHERE ' . implode(' AND ', $whereParts)
            . ' ORDER BY ' . $titleColumn . ' ASC LIMIT 8)';
    };

    foreach ([
        ['destinations', 'name', 'destination'],
        ['itineraries', 'name', 'itinerary'],
        ['experiences', 'title', 'experience'],
        ['blog_posts', 'title', 'blog'],
    ] as [$table, $titleColumn, $type]) {
        $addContentQuery($table, $titleColumn, $type);
    }

    if (!empty($queryParts)) {
        foreach (fetch_all(implode(' UNION ALL ', $queryParts), $params) as $row) {
            $items[] = $row;
        }
    }
}

$items = array_slice(array_map(static function (array $row): array {
    $type = (string) $row['type'];
    $slug = (string) ($row['slug'] ?? '');
    $prefix = ['destination' => 'destinations', 'itinerary' => 'itineraries', 'experience' => 'experiences', 'blog' => 'blog'][$type] ?? '';
    $image = trim((string) ($row['cover_image'] ?? '')) ?: trim((string) ($row['hero_image'] ?? '')) ?: trim((string) ($row['og_image'] ?? ''));

    return [
        'id' => (int) ($row['id'] ?? 0),
        'type' => $type,
        'label' => ucfirst($type),
        'title' => (string) ($row['title'] ?? ''),
        'url' => base_url($prefix . '/' . $slug),
        'image' => $image !== '' ? stored_media_url($image) : DEFAULT_OG_IMAGE,
        'excerpt' => mb_substr(trim(strip_tags((string) ($row['excerpt'] ?? ''))), 0, 120),
    ];
}, $items), 0, 20);

echo json_encode(['items' => $items], JSON_UNESCAPED_SLASHES);
