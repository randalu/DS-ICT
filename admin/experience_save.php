<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/indexnow.php';

admin_require_auth();
ensure_experiences_schema();

if (!is_post()) {
    redirect('admin/experiences.php');
}

guard_post_size();
require_csrf();

$id = (int) post_input('id', 0);
$categoryId = (int) post_input('category_id', 0);
$categoryId = $categoryId > 0 ? $categoryId : null;

$data = [
    'title' => trim((string) post_input('title', '')),
    'slug' => trim((string) post_input('slug', '')),
    'category_id' => $categoryId,
    'excerpt' => trim((string) post_input('excerpt', '')),
    'intro' => trim((string) post_input('intro', '')),
    'where_to_experience' => trim((string) post_input('where_to_experience', '')),
    'tips_json' => trim((string) post_input('tips_json', '')),
    'day_arc_json' => trim((string) post_input('day_arc_json', '')),
    'best_time' => trim((string) post_input('best_time', '')),
    'meta_title' => trim((string) post_input('meta_title', '')),
    'meta_description' => trim((string) post_input('meta_description', '')),
    'canonical_url' => trim((string) post_input('canonical_url', '')),
    'cta_title' => trim((string) post_input('cta_title', '')),
    'cta_text' => trim((string) post_input('cta_text', '')),
    'sort_order' => (int) post_input('sort_order', 0),
    'is_featured' => post_input('is_featured') ? 1 : 0,
    'is_published' => post_input('is_published') ? 1 : 0,
];

$data['slug'] = $data['slug'] !== '' ? slugify($data['slug']) : slugify($data['title']);
$data['canonical_url'] = $data['canonical_url'] !== '' ? $data['canonical_url'] : base_url('experiences/' . $data['slug']);

old_input_set($_POST);

$destinationIds = array_values(array_filter(
    array_unique(array_map('intval', (array) post_input('destinations', []))),
    static fn(int $destinationId): bool => $destinationId > 0
));
$itineraryIds = array_values(array_filter(
    array_unique(array_map('intval', (array) post_input('itineraries', []))),
    static fn(int $itineraryId): bool => $itineraryId > 0
));
$relatedExperienceIds = array_values(array_filter(
    array_unique(array_map('intval', (array) post_input('related_experiences', []))),
    static fn(int $relatedId): bool => $relatedId > 0
));

$errors = [];
if ($data['title'] === '') {
    $errors[] = 'Title is required.';
}
if ($data['slug'] === '') {
    $errors[] = 'Slug could not be generated.';
}
$canonicalScheme = strtolower((string) parse_url($data['canonical_url'], PHP_URL_SCHEME));
if (!filter_var($data['canonical_url'], FILTER_VALIDATE_URL) || !in_array($canonicalScheme, ['http', 'https'], true)) {
    $errors[] = 'Canonical URL must be a valid http(s) URL.';
}
if ($categoryId !== null) {
    $categoryExists = db_table_exists('experience_categories')
        && (bool) fetch_one(
            'SELECT id FROM experience_categories WHERE id = :id AND is_active = 1 LIMIT 1',
            ['id' => $categoryId]
        );
    if (!$categoryExists) {
        $errors[] = 'Selected mood/category is not available.';
    }
}
if ($data['tips_json'] !== '') {
    $decodedTips = json_decode($data['tips_json'], true);
    if ($decodedTips === null && json_last_error() !== JSON_ERROR_NONE) {
        $errors[] = 'Tips JSON is invalid.';
    } elseif (!is_array($decodedTips)) {
        $errors[] = 'Tips JSON must be an array of tip strings.';
    } else {
        $tips = [];
        foreach ($decodedTips as $tip) {
            $tip = trim((string) $tip);
            if ($tip !== '') {
                $tips[] = $tip;
            }
        }
        $data['tips_json'] = $tips
            ? json_encode($tips, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            : '';
    }
}

if ($data['day_arc_json'] !== '') {
    $decodedDayArc = json_decode($data['day_arc_json'], true);
    if ($decodedDayArc === null && json_last_error() !== JSON_ERROR_NONE) {
        $errors[] = 'Day Arc JSON is invalid.';
    } elseif (!is_array($decodedDayArc)) {
        $errors[] = 'Day Arc JSON must be an array of day segment objects.';
    } else {
        $data['day_arc_json'] = json_encode($decodedDayArc, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}

$lengthLimits = [
    'title' => ['Title', 255],
    'slug' => ['Slug', 255],
    'meta_title' => ['Meta title', 255],
    'meta_description' => ['Meta description', 320],
    'canonical_url' => ['Canonical URL', 255],
    'cta_title' => ['CTA title', 255],
];
foreach ($lengthLimits as $field => [$label, $limit]) {
    if ($data[$field] !== '' && mb_strlen((string) $data[$field]) > $limit) {
        $errors[] = $label . ' must be ' . $limit . ' characters or fewer.';
    }
}

$galleryAlt = (array) post_input('gallery_alt', []);
$gallerySort = (array) post_input('gallery_sort', []);
foreach ($galleryAlt as $altText) {
    $altText = trim((string) $altText);
    if ($altText === '') {
        $errors[] = 'Gallery alt text is required.';
        break;
    }
    if (mb_strlen($altText) > 255) {
        $errors[] = 'Gallery alt text must be 255 characters or fewer.';
        break;
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

if ($destinationIds) {
    $existingDestinationIds = $fetchExistingIds('destinations', 'id', $destinationIds);
    if (array_diff($destinationIds, $existingDestinationIds)) {
        $errors[] = 'Selected destinations are invalid.';
    }
}
if ($itineraryIds) {
    $existingItineraryIds = $fetchExistingIds('itineraries', 'id', $itineraryIds);
    if (array_diff($itineraryIds, $existingItineraryIds)) {
        $errors[] = 'Selected itineraries are invalid.';
    }
}
if ($relatedExperienceIds) {
    $existingRelatedExperienceIds = $fetchExistingIds('experiences', 'id', $relatedExperienceIds);
    if (array_diff($relatedExperienceIds, $existingRelatedExperienceIds)) {
        $errors[] = 'Selected related experiences are invalid.';
    }
}

$existing = fetch_one(
    "SELECT id FROM experiences WHERE slug = :slug AND id != :id LIMIT 1",
    ['slug' => $data['slug'], 'id' => $id]
);
if ($existing) {
    $errors[] = 'Slug already exists.';
}

if ($errors) {
    flash_set('error', implode(' ', $errors));
    redirect($id > 0 ? 'admin/experience_form.php?id=' . $id : 'admin/experience_form.php');
}

$record = [];
if ($id > 0) {
    $record = fetch_one("SELECT * FROM experiences WHERE id = :id LIMIT 1", ['id' => $id]) ?: [];
    if (!$record) {
        flash_set('error', 'Experience not found.');
        redirect('admin/experiences.php');
    }
}

try {
    db()->beginTransaction();
    $galleryPathsToDelete = [];

    $heroMedia = admin_resolve_media_field('hero_image', 'experiences', [
        'path' => $record['hero_image'] ?? null,
        'asset_id' => $record['hero_media_asset_id'] ?? null,
        'media_type' => $record['hero_media_type'] ?? 'image',
    ], true);
    $ogMedia = admin_resolve_media_field('og_image', 'experiences', ['path' => $record['og_image'] ?? null]);
    $heroPoster = $record['hero_video_poster'] ?? null;
    if (!empty($_FILES['hero_image_poster']['name'])) {
        $posterMedia = save_media_asset_from_upload($_FILES['hero_image_poster'], 'experiences', false);
        $heroPoster = $posterMedia['file_path'];
    }

    $writeData = array_merge($data, [
        'hero_image' => $heroMedia['path'],
        'hero_media_type' => $heroMedia['media_type'],
        'hero_media_asset_id' => $heroMedia['asset_id'],
        'hero_video_poster' => $heroPoster,
        'og_image' => $ogMedia['path'],
    ]);

    $writeData = array_filter($writeData, static fn($value, string $column): bool => db_column_exists('experiences', $column), ARRAY_FILTER_USE_BOTH);

    if ($id > 0) {
        $setParts = [];
        foreach (array_keys($writeData) as $column) {
            $setParts[] = "{$column} = :{$column}";
        }

        execute_query(
            'UPDATE experiences SET ' . implode(', ', $setParts) . ' WHERE id = :id',
            array_merge($writeData, ['id' => $id])
        );
    } else {
        $columns = array_keys($writeData);
        $placeholders = array_map(static fn(string $column): string => ':' . $column, $columns);

        execute_query(
            'INSERT INTO experiences (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $placeholders) . ')',
            $writeData
        );

        $id = (int) last_insert_id();
    }

    $hasExperienceDestination = db_table_exists('experience_destination');
    $hasExperienceItinerary = db_table_exists('experience_itinerary');
    $hasExperienceRelated = db_table_exists('experience_related');
    $hasExperienceCategoryPivot = db_table_exists('experience_category_pivot');
    $hasExperienceGallery = db_table_exists('experience_gallery');

    if ($hasExperienceDestination) {
        execute_query('DELETE FROM experience_destination WHERE experience_id = :id', ['id' => $id]);
    }
    if ($hasExperienceItinerary) {
        execute_query('DELETE FROM experience_itinerary WHERE experience_id = :id', ['id' => $id]);
    }
    if ($hasExperienceRelated) {
        execute_query('DELETE FROM experience_related WHERE experience_id = :id', ['id' => $id]);
    }
    if ($hasExperienceCategoryPivot) {
        execute_query('DELETE FROM experience_category_pivot WHERE experience_id = :id', ['id' => $id]);
    }

    if ($hasExperienceCategoryPivot && $categoryId !== null) {
        execute_query(
            'INSERT IGNORE INTO experience_category_pivot (experience_id, category_id) VALUES (:experience_id, :category_id)',
            ['experience_id' => $id, 'category_id' => $categoryId]
        );
    }

    if ($hasExperienceDestination) {
        foreach ($destinationIds as $destinationId) {
            if ($destinationId > 0) {
                execute_query(
                    'INSERT IGNORE INTO experience_destination (experience_id, destination_id) VALUES (:experience_id, :destination_id)',
                    ['experience_id' => $id, 'destination_id' => $destinationId]
                );
            }
        }
    }

    if ($hasExperienceItinerary) {
        foreach ($itineraryIds as $itineraryId) {
            if ($itineraryId > 0) {
                execute_query(
                    'INSERT IGNORE INTO experience_itinerary (experience_id, itinerary_id) VALUES (:experience_id, :itinerary_id)',
                    ['experience_id' => $id, 'itinerary_id' => $itineraryId]
                );
            }
        }
    }

    if ($hasExperienceDestination && db_column_exists('experiences', 'destination_count')) {
        execute_query(
            'UPDATE experiences
             SET destination_count = (SELECT COUNT(*) FROM experience_destination WHERE experience_id = :experience_id)
             WHERE id = :id',
            ['experience_id' => $id, 'id' => $id]
        );
    }

    if ($hasExperienceItinerary && db_column_exists('experiences', 'itinerary_count')) {
        execute_query(
            'UPDATE experiences
             SET itinerary_count = (SELECT COUNT(*) FROM experience_itinerary WHERE experience_id = :experience_id)
             WHERE id = :id',
            ['experience_id' => $id, 'id' => $id]
        );
    }

    if ($hasExperienceRelated) {
        foreach ($relatedExperienceIds as $relatedId) {
            if ($relatedId > 0 && $relatedId !== $id) {
                execute_query(
                    'INSERT IGNORE INTO experience_related (experience_id, related_experience_id) VALUES (:experience_id, :related_id)',
                    ['experience_id' => $id, 'related_id' => $relatedId]
                );
            }
        }
    }

    if ($hasExperienceGallery) {
        foreach (array_unique(array_map('intval', (array) post_input('delete_gallery_ids', []))) as $photoId) {
            if ($photoId > 0) {
                $photo = fetch_one(
                    'SELECT image_path FROM experience_gallery WHERE id = :photo_id AND experience_id = :experience_id LIMIT 1',
                    ['photo_id' => $photoId, 'experience_id' => $id]
                );
                if ($photo && !empty($photo['image_path'])) {
                    $galleryPathsToDelete[] = (string) $photo['image_path'];
                }
                execute_query(
                    'DELETE FROM experience_gallery WHERE id = :photo_id AND experience_id = :experience_id',
                    ['photo_id' => $photoId, 'experience_id' => $id]
                );
            }
        }
    }

    foreach ($galleryAlt as $photoId => $altText) {
        $photoId = (int) $photoId;
        if ($photoId <= 0) {
            continue;
        }
        if ($hasExperienceGallery) {
            execute_query(
                'UPDATE experience_gallery
                 SET alt_text = :alt_text, sort_order = :sort_order
                 WHERE id = :photo_id AND experience_id = :experience_id',
                [
                    'alt_text' => trim((string) $altText),
                    'sort_order' => (int) ($gallerySort[$photoId] ?? 0),
                    'photo_id' => $photoId,
                    'experience_id' => $id,
                ]
            );
        }
    }

    $existingGalleryCount = $hasExperienceGallery
        ? (int) (fetch_one(
            'SELECT COUNT(*) AS total FROM experience_gallery WHERE experience_id = :experience_id',
            ['experience_id' => $id]
        )['total'] ?? 0)
        : 0;

    if ($hasExperienceGallery && !empty($_FILES['gallery_images']['name']) && is_array($_FILES['gallery_images']['name'])) {
        $uploaded = 0;
        foreach ($_FILES['gallery_images']['name'] as $index => $name) {
            if (trim((string) $name) === '') {
                continue;
            }
            $file = [
                'name' => $_FILES['gallery_images']['name'][$index] ?? '',
                'type' => $_FILES['gallery_images']['type'][$index] ?? '',
                'tmp_name' => $_FILES['gallery_images']['tmp_name'][$index] ?? '',
                'error' => $_FILES['gallery_images']['error'][$index] ?? UPLOAD_ERR_NO_FILE,
                'size' => $_FILES['gallery_images']['size'][$index] ?? 0,
            ];
            $path = upload_image($file, 'experiences');
            if ($path === null) {
                continue;
            }
            execute_query(
                'INSERT INTO experience_gallery (experience_id, image_path, alt_text, sort_order)
                 VALUES (:experience_id, :image_path, :alt_text, :sort_order)',
                [
                    'experience_id' => $id,
                    'image_path' => $path,
                    'alt_text' => mb_substr($data['title'] . ' in Sri Lanka', 0, 255),
                    'sort_order' => ($existingGalleryCount + $uploaded + 1) * 10,
                ]
            );
            $uploaded++;
        }
    }

    db()->commit();

    foreach (array_unique($galleryPathsToDelete) as $galleryPath) {
        delete_uploaded_media($galleryPath);
    }

    $oldSlug = trim((string) ($record['slug'] ?? ''));
    $wasPublished = (int) ($record['is_published'] ?? 0) === 1;
    $isPublishedNow = (int) $data['is_published'] === 1;

    $indexNowUrls = [
        base_url('experiences'),
        base_url('sitemap.xml'),
    ];
    if ($isPublishedNow) {
        $indexNowUrls[] = base_url('experiences/' . $data['slug']);
    }
    if ($oldSlug !== '' && ($oldSlug !== $data['slug'] || !$isPublishedNow) && $wasPublished) {
        $indexNowUrls[] = base_url('experiences/' . $oldSlug);
    }
    indexnow_submit_urls($indexNowUrls);

    old_input_clear();
    flash_set('success', 'Experience saved successfully.');
    redirect('admin/experiences.php');
} catch (Throwable $e) {
    if (db()->inTransaction()) {
        db()->rollBack();
    }

    flash_set('error', 'Could not save experience.');
    redirect($id > 0 ? 'admin/experience_form.php?id=' . $id : 'admin/experience_form.php');
}
