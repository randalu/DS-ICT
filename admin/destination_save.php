<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/indexnow.php';

admin_require_auth();

if (!is_post()) {
    redirect('admin/destinations.php');
}

guard_post_size();
require_csrf();

$id = (int) post_input('id', 0);

$data = [
    'name' => trim((string) post_input('name', '')),
    'slug' => trim((string) post_input('slug', '')),
    'excerpt' => trim((string) post_input('excerpt', '')),
    'intro' => trim((string) post_input('intro', '')),
    'why_visit' => trim((string) post_input('why_visit', '')),
    'things_to_do' => trim((string) post_input('things_to_do', '')),
    'best_time' => trim((string) post_input('best_time', '')),
    'how_to_get_there' => trim((string) post_input('how_to_get_there', '')),
    'nearby_travel_times' => trim((string) post_input('nearby_travel_times', '')),
    'works_well_with' => trim((string) post_input('works_well_with', '')),
    'suggested_itinerary' => trim((string) post_input('suggested_itinerary', '')),
    'where_to_stay' => trim((string) post_input('where_to_stay', '')),
    'travel_tips' => trim((string) post_input('travel_tips', '')),
    'faq_json' => trim((string) post_input('faq_json', '')),
    'cta_title' => trim((string) post_input('cta_title', '')),
    'cta_text' => trim((string) post_input('cta_text', '')),
    'best_time_json' => '',
    'route_notes_json' => '',
    'region_slug' => trim((string) post_input('region_slug', '')),
    'activity_categories_json' => trim((string) post_input('activity_categories_json', '')),
    'source_markets_json' => trim((string) post_input('source_markets_json', '')),
    'avg_rating' => (float) post_input('avg_rating', 0),
    'avg_rating_count' => (int) post_input('avg_rating_count', 0),
    'meta_title' => trim((string) post_input('meta_title', '')),
    'meta_description' => trim((string) post_input('meta_description', '')),
    'canonical_url' => trim((string) post_input('canonical_url', '')),
    'sort_order' => (int) post_input('sort_order', 0),
    'is_featured' => post_input('is_featured') ? 1 : 0,
    'is_published' => post_input('is_published') ? 1 : 0,
];

$data['slug'] = $data['slug'] !== '' ? slugify($data['slug']) : slugify($data['name']);
$data['canonical_url'] = $data['canonical_url'] !== '' ? $data['canonical_url'] : base_url('destinations/' . $data['slug']);

old_input_set($_POST);

// Compose best_time_json from month checkboxes + free-text note
$bestTimeMonths = array_values(array_filter(array_map(
    static fn($m) => strtolower(trim((string) $m)),
    (array) post_input('best_time_months', [])
)));
$bestTimeNote = trim((string) post_input('best_time_note', ''));
if ($bestTimeMonths || $bestTimeNote !== '') {
    $data['best_time_json'] = json_encode([
        'months' => $bestTimeMonths,
        'note'   => $bestTimeNote,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

$errors = [];

// route_notes_json: validate it's well-formed JSON or blank
$routeNotesRaw = trim((string) post_input('route_notes_json', ''));
if ($routeNotesRaw !== '') {
    $decodedRoute = json_decode($routeNotesRaw, true);
    if (!is_array($decodedRoute) || json_last_error() !== JSON_ERROR_NONE) {
        $errors[] = 'Route Notes JSON is invalid.';
    } else {
        $data['route_notes_json'] = json_encode($decodedRoute, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}

$relatedDestinationIds = array_values(array_filter(array_unique(array_map(
    'intval',
    (array) post_input('related_destinations', [])
)), static fn(int $value): bool => $value > 0 && $value !== $id));
$tourIds = array_values(array_filter(array_unique(array_map(
    'intval',
    (array) post_input('tours', [])
)), static fn(int $value): bool => $value > 0));
$itineraryIds = array_values(array_filter(array_unique(array_map(
    'intval',
    (array) post_input('itineraries', [])
)), static fn(int $value): bool => $value > 0));

if ($data['name'] === '') {
    $errors[] = 'Name is required.';
}

if ($data['slug'] === '') {
    $errors[] = 'Slug could not be generated.';
}

$lengthLimits = [
    'name' => 150,
    'slug' => 180,
    'excerpt' => 500,
    'cta_title' => 255,
    'meta_title' => 255,
    'meta_description' => 320,
    'canonical_url' => 255,
];
foreach ($lengthLimits as $field => $limit) {
    if (mb_strlen((string) $data[$field]) > $limit) {
        $label = ucwords(str_replace('_', ' ', $field));
        $errors[] = "{$label} must be {$limit} characters or fewer.";
    }
}

$canonicalScheme = strtolower((string) parse_url($data['canonical_url'], PHP_URL_SCHEME));
if (!filter_var($data['canonical_url'], FILTER_VALIDATE_URL) || !in_array($canonicalScheme, ['http', 'https'], true)) {
    $errors[] = 'Canonical URL must be a valid http(s) URL.';
}

if ($data['faq_json'] !== '') {
    $decoded = json_decode($data['faq_json'], true);
    if ($decoded === null && json_last_error() !== JSON_ERROR_NONE) {
        $errors[] = 'FAQ JSON is invalid.';
    }
}

foreach (['activity_categories_json', 'source_markets_json'] as $jsonField) {
    if ($data[$jsonField] !== '') {
        $decoded = json_decode($data[$jsonField], true);
        if ($decoded === null && json_last_error() !== JSON_ERROR_NONE) {
            $labels = ['activity_categories_json' => 'Activity Categories', 'source_markets_json' => 'Source Markets'];
            $errors[] = $labels[$jsonField] . ' JSON is invalid.';
        }
    }
}

$fetchExistingIds = static function (string $table, string $column, array $ids): array {
    if (!$ids || !db_table_exists($table) || !db_column_exists($table, $column)) {
        return [];
    }

    $placeholders = [];
    $params = [];
    foreach (array_values($ids) as $index => $candidateId) {
        $key = 'id' . $index;
        $placeholders[] = ':' . $key;
        $params[$key] = (int) $candidateId;
    }

    $rows = fetch_all(
        'SELECT ' . $column . ' FROM ' . $table . ' WHERE ' . $column . ' IN (' . implode(', ', $placeholders) . ')',
        $params
    );

    return array_map(static fn(array $row): int => (int) ($row[$column] ?? 0), $rows);
};

if ($relatedDestinationIds) {
    $existingRelatedDestinationIds = $fetchExistingIds('destinations', 'id', $relatedDestinationIds);
    if (array_diff($relatedDestinationIds, $existingRelatedDestinationIds)) {
        $errors[] = 'Selected related destinations are invalid.';
    }
}
if ($tourIds) {
    $existingTourIds = $fetchExistingIds('tours', 'id', $tourIds);
    if (array_diff($tourIds, $existingTourIds)) {
        $errors[] = 'Selected tours are invalid.';
    }
}
if ($itineraryIds) {
    $existingItineraryIds = $fetchExistingIds('itineraries', 'id', $itineraryIds);
    if (array_diff($itineraryIds, $existingItineraryIds)) {
        $errors[] = 'Selected itineraries are invalid.';
    }
}

$existing = fetch_one(
    "SELECT id FROM destinations WHERE slug = :slug AND id != :id LIMIT 1",
    ['slug' => $data['slug'], 'id' => $id]
);

if ($existing) {
    $errors[] = 'Slug already exists.';
}

if ($errors) {
    flash_set('error', implode(' ', $errors));
    redirect($id > 0 ? 'admin/destination_form.php?id=' . $id : 'admin/destination_form.php');
}

$record = [];
if ($id > 0) {
    $record = fetch_one("SELECT * FROM destinations WHERE id = :id LIMIT 1", ['id' => $id]) ?: [];
    if (!$record) {
        flash_set('error', 'Destination not found.');
        redirect('admin/destinations.php');
    }
}

try {
    $heroMedia = admin_resolve_media_field('hero_image', 'destinations', [
        'path' => $record['hero_image'] ?? null,
        'asset_id' => $record['hero_media_asset_id'] ?? null,
        'media_type' => $record['hero_media_type'] ?? 'image',
    ], true);
    $coverMedia = admin_resolve_media_field('cover_image', 'destinations', ['path' => $record['cover_image'] ?? null]);
    $ogMedia = admin_resolve_media_field('og_image', 'destinations', ['path' => $record['og_image'] ?? null]);
    $heroPoster = $record['hero_video_poster'] ?? null;
    if (!empty($_FILES['hero_image_poster']['name'])) {
        $posterMedia = save_media_asset_from_upload($_FILES['hero_image_poster'], 'destinations', false);
        $heroPoster = $posterMedia['file_path'];
    }

    // Build a write payload using only columns that exist in this DB.
    // This keeps saves working on older installs that may not yet have all optional columns.
    $columnValueMap = [
        'name' => $data['name'],
        'slug' => $data['slug'],
        'excerpt' => $data['excerpt'],
        'intro' => $data['intro'],
        'why_visit' => $data['why_visit'],
        'things_to_do' => $data['things_to_do'],
        'best_time' => $data['best_time'],
        'how_to_get_there' => $data['how_to_get_there'],
        'nearby_travel_times' => $data['nearby_travel_times'],
        'works_well_with' => $data['works_well_with'],
        'suggested_itinerary' => $data['suggested_itinerary'],
        'where_to_stay' => $data['where_to_stay'],
        'travel_tips' => $data['travel_tips'],
        'faq_json' => $data['faq_json'],
        'cta_title' => $data['cta_title'],
        'cta_text' => $data['cta_text'],
        'best_time_json' => $data['best_time_json'],
        'route_notes_json' => $data['route_notes_json'],
        'region_slug' => $data['region_slug'],
        'activity_categories_json' => $data['activity_categories_json'],
        'source_markets_json' => $data['source_markets_json'],
        'avg_rating' => $data['avg_rating'],
        'avg_rating_count' => $data['avg_rating_count'],
        'hero_image' => $heroMedia['path'],
        'hero_media_type' => $heroMedia['media_type'],
        'hero_media_asset_id' => $heroMedia['asset_id'],
        'hero_video_poster' => $heroPoster,
        'cover_image' => $coverMedia['path'],
        'og_image' => $ogMedia['path'],
        'meta_title' => $data['meta_title'],
        'meta_description' => $data['meta_description'],
        'canonical_url' => $data['canonical_url'],
        'is_featured' => $data['is_featured'],
        'sort_order' => $data['sort_order'],
        'is_published' => $data['is_published'],
    ];

    $writeData = [];
    foreach ($columnValueMap as $column => $value) {
        if (db_column_exists('destinations', $column)) {
            // JSON columns: convert empty string to NULL so MySQL doesn't reject the write.
            if (in_array($column, ['best_time_json', 'route_notes_json', 'faq_json', 'activity_categories_json', 'source_markets_json'], true) && $value === '') {
                $writeData[$column] = null;
            } else {
                $writeData[$column] = $value;
            }
        }
    }

    if (!isset($writeData['name'], $writeData['slug'])) {
        throw new RuntimeException('Required destination columns are missing in database schema.');
    }

    if ($id > 0) {
        $setParts = [];
        foreach (array_keys($writeData) as $column) {
            $setParts[] = "{$column} = :{$column}";
        }
        execute_query(
            "UPDATE destinations SET " . implode(', ', $setParts) . " WHERE id = :id",
            array_merge($writeData, ['id' => $id])
        );
    } else {
        $columns = array_keys($writeData);
        $placeholders = array_map(static fn(string $column): string => ':' . $column, $columns);

        execute_query(
            "INSERT INTO destinations (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")",
            $writeData
        );

        $id = (int) last_insert_id();
    }

    $hasDestinationRelated = db_table_exists('destination_related')
        && db_column_exists('destination_related', 'destination_id')
        && db_column_exists('destination_related', 'related_destination_id');
    $hasDestinationTour = db_table_exists('destination_tour')
        && db_column_exists('destination_tour', 'destination_id')
        && db_column_exists('destination_tour', 'tour_id');
    $hasDestinationItinerary = db_table_exists('destination_itinerary')
        && db_column_exists('destination_itinerary', 'destination_id')
        && db_column_exists('destination_itinerary', 'itinerary_id');
    $hasItineraryDestination = db_table_exists('itinerary_destination')
        && db_column_exists('itinerary_destination', 'itinerary_id')
        && db_column_exists('itinerary_destination', 'destination_id');
    $hasSeasonalNotes = db_table_exists('seasonal_notes')
        && db_column_exists('seasonal_notes', 'entity_type')
        && db_column_exists('seasonal_notes', 'entity_id')
        && db_column_exists('seasonal_notes', 'season_label')
        && db_column_exists('seasonal_notes', 'season_note');

    if ($hasDestinationRelated) {
        execute_query("DELETE FROM destination_related WHERE destination_id = :id", ['id' => $id]);
    }
    if ($hasDestinationTour) {
        execute_query("DELETE FROM destination_tour WHERE destination_id = :id", ['id' => $id]);
    }
    if ($hasDestinationItinerary) {
        execute_query("DELETE FROM destination_itinerary WHERE destination_id = :id", ['id' => $id]);
    }

    if ($hasDestinationRelated) {
        foreach ($relatedDestinationIds as $relatedId) {
            if ($relatedId > 0 && $relatedId !== $id) {
                execute_query(
                    "INSERT IGNORE INTO destination_related (destination_id, related_destination_id) VALUES (:destination_id, :related_id)",
                    ['destination_id' => $id, 'related_id' => $relatedId]
                );
            }
        }
    }

    if ($hasDestinationTour) {
        foreach ($tourIds as $tourId) {
            if ($tourId > 0) {
                execute_query(
                    "INSERT IGNORE INTO destination_tour (destination_id, tour_id) VALUES (:destination_id, :tour_id)",
                    ['destination_id' => $id, 'tour_id' => $tourId]
                );
            }
        }
    }

    foreach ($itineraryIds as $itineraryId) {
        if ($itineraryId > 0) {
            if ($hasDestinationItinerary) {
                execute_query(
                    "INSERT IGNORE INTO destination_itinerary (destination_id, itinerary_id) VALUES (:destination_id, :itinerary_id)",
                    ['destination_id' => $id, 'itinerary_id' => $itineraryId]
                );
            }

            if ($hasItineraryDestination) {
                execute_query(
                    "INSERT IGNORE INTO itinerary_destination (itinerary_id, destination_id) VALUES (:itinerary_id, :destination_id)",
                    ['itinerary_id' => $itineraryId, 'destination_id' => $id]
                );
            }
        }
    }

    // Seasonal availability
    $seasonLabel = trim((string) post_input('season_label', ''));
    $seasonNote  = trim((string) post_input('season_note', ''));
    if ($hasSeasonalNotes) {
        execute_query("DELETE FROM seasonal_notes WHERE entity_type='destination' AND entity_id=:id", ['id' => $id]);
    }
    if ($hasSeasonalNotes && $seasonLabel !== '') {
        execute_query(
            "INSERT INTO seasonal_notes (entity_type, entity_id, season_label, season_note) VALUES ('destination', :id, :label, :note)",
            ['id' => $id, 'label' => $seasonLabel, 'note' => $seasonNote]
        );
    }

    $oldSlug = trim((string) ($record['slug'] ?? ''));
    $wasPublished = (int) ($record['is_published'] ?? 0) === 1;
    $isPublishedNow = (int) $data['is_published'] === 1;

    $indexNowUrls = [
        base_url('destinations'),
        base_url('sitemap.xml'),
    ];

    if ($isPublishedNow) {
        $indexNowUrls[] = base_url('destinations/' . $data['slug']);
    }

    if ($id > 0 && $wasPublished && $oldSlug !== '' && ($oldSlug !== $data['slug'] || !$isPublishedNow)) {
        $indexNowUrls[] = base_url('destinations/' . $oldSlug);
    }

    indexnow_submit_urls($indexNowUrls);

    old_input_clear();
    flash_set('success', 'Destination saved successfully.');
    redirect('admin/destinations.php');
} catch (Throwable $e) {
    error_log('[destination_save] ' . $e->getMessage());
    flash_set('error', 'Failed to save destination.');
    redirect($id > 0 ? 'admin/destination_form.php?id=' . $id : 'admin/destination_form.php');
}
