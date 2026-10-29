<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

admin_require_auth();

if (!is_post()) {
    redirect('admin/social_posts.php');
}

guard_post_size();
require_csrf();
ensure_social_marketing_schema();

$id = (int) post_input('id', 0);

$data = [
    'title' => trim((string) post_input('title', '')),
    'description' => trim((string) post_input('description', '')),
    'reference_destination_id' => (int) post_input('reference_destination_id', 0),
    'reference_itinerary_id' => (int) post_input('reference_itinerary_id', 0),
    'reference_tour_id' => (int) post_input('reference_tour_id', 0),
    'experience_name' => trim((string) post_input('experience_name', '')),
    'hashtags' => trim((string) post_input('hashtags', '')),
    'status' => strtolower(trim((string) post_input('status', 'draft'))),
    'scheduled_at' => trim((string) post_input('scheduled_at', '')),
];

$platformOptions = social_media_platform_options();
$selectedPlatformsRaw = post_input('platform_targets', []);
$selectedPlatforms = [];
if (is_array($selectedPlatformsRaw)) {
    foreach ($selectedPlatformsRaw as $platform) {
        $platform = strtolower(trim((string) $platform));
        if (isset($platformOptions[$platform])) {
            $selectedPlatforms[] = $platform;
        }
    }
}
$selectedPlatforms = array_values(array_unique($selectedPlatforms));

$assetIdsRaw = post_input('asset_ids', []);
$assetIds = [];
if (is_array($assetIdsRaw)) {
    foreach ($assetIdsRaw as $assetIdRaw) {
        $assetId = (int) $assetIdRaw;
        if ($assetId > 0) {
            $assetIds[] = $assetId;
        }
    }
}
$assetIds = array_values(array_unique($assetIds));

old_input_set($_POST);

$errors = [];
if ($data['title'] === '') {
    $errors[] = 'Title is required.';
}
if (mb_strlen($data['title']) > 255) {
    $errors[] = 'Title must be 255 characters or fewer.';
}
if (mb_strlen($data['experience_name']) > 180) {
    $errors[] = 'Custom experience name must be 180 characters or fewer.';
}
if (mb_strlen($data['hashtags']) > 255) {
    $errors[] = 'Hashtags must be 255 characters or fewer.';
}

$statusOptions = ['draft', 'ready', 'scheduled', 'published', 'archived'];
if (!in_array($data['status'], $statusOptions, true)) {
    $errors[] = 'Invalid draft status selected.';
    $data['status'] = 'draft';
}

$scheduledAt = null;
if ($data['scheduled_at'] !== '') {
    $ts = strtotime($data['scheduled_at']);
    if ($ts === false) {
        $errors[] = 'Scheduled date/time is invalid.';
    } else {
        $scheduledAt = date('Y-m-d H:i:s', $ts);
    }
}

if ($data['status'] === 'scheduled' && $scheduledAt === null) {
    $errors[] = 'Scheduled posts require a scheduled date/time.';
}

if ($data['reference_destination_id'] > 0) {
    if (!db_table_exists('destinations')) {
        $errors[] = 'Destination references are not available in this database.';
    } else {
        $publishedFilter = db_column_exists('destinations', 'is_published') ? ' AND is_published = 1' : '';
        $exists = fetch_one('SELECT id FROM destinations WHERE id = :id' . $publishedFilter . ' LIMIT 1', ['id' => $data['reference_destination_id']]);
        if (!$exists) {
            $errors[] = 'Selected destination reference is not available.';
        }
    }
}

if ($data['reference_itinerary_id'] > 0) {
    if (!db_table_exists('itineraries')) {
        $errors[] = 'Itinerary references are not available in this database.';
    } else {
        $publishedFilter = db_column_exists('itineraries', 'is_published') ? ' AND is_published = 1' : '';
        $exists = fetch_one('SELECT id FROM itineraries WHERE id = :id' . $publishedFilter . ' LIMIT 1', ['id' => $data['reference_itinerary_id']]);
        if (!$exists) {
            $errors[] = 'Selected itinerary reference is not available.';
        }
    }
}

if ($data['reference_tour_id'] > 0) {
    if (!db_table_exists('tours')) {
        $errors[] = 'Tour references are not available in this database.';
    } else {
        $publishedFilter = db_column_exists('tours', 'is_published') ? ' AND is_published = 1' : '';
        $exists = fetch_one('SELECT id FROM tours WHERE id = :id' . $publishedFilter . ' LIMIT 1', ['id' => $data['reference_tour_id']]);
        if (!$exists) {
            $errors[] = 'Selected tour reference is not available.';
        }
    }
}

if ($assetIds) {
    $assetParams = [];
    $assetPlaceholders = [];
    foreach ($assetIds as $index => $assetId) {
        $param = 'asset_' . $index;
        $assetPlaceholders[] = ':' . $param;
        $assetParams[$param] = $assetId;
    }
    $foundAssetRows = fetch_all('SELECT id FROM media_assets WHERE id IN (' . implode(',', $assetPlaceholders) . ')', $assetParams);
    $foundAssetIds = array_map(static fn(array $row): int => (int) ($row['id'] ?? 0), $foundAssetRows);
    if (count(array_diff($assetIds, $foundAssetIds)) > 0) {
        $errors[] = 'One or more selected media assets are not available.';
    }
}

if ($errors) {
    flash_set('error', implode(' ', $errors));
    redirect($id > 0 ? 'admin/social_post_form.php?id=' . $id : 'admin/social_post_form.php');
}

$formRedirect = $id > 0 ? 'admin/social_post_form.php?id=' . $id : 'admin/social_post_form.php';

$existing = null;
if ($id > 0) {
    $existing = fetch_one('SELECT id FROM social_media_posts WHERE id = :id LIMIT 1', ['id' => $id]);
    if (!$existing) {
        flash_set('error', 'Social draft not found.');
        redirect('admin/social_posts.php');
    }
}

$adminUser = admin_user();
$createdBy = (int) ($adminUser['id'] ?? 0);

$dbData = [
    'title' => $data['title'],
    'description' => $data['description'] !== '' ? $data['description'] : null,
    'reference_destination_id' => $data['reference_destination_id'] > 0 ? $data['reference_destination_id'] : null,
    'reference_itinerary_id' => $data['reference_itinerary_id'] > 0 ? $data['reference_itinerary_id'] : null,
    'reference_tour_id' => $data['reference_tour_id'] > 0 ? $data['reference_tour_id'] : null,
    'experience_name' => $data['experience_name'] !== '' ? $data['experience_name'] : null,
    'platform_targets_json' => $selectedPlatforms ? json_encode($selectedPlatforms, JSON_UNESCAPED_SLASHES) : json_encode([], JSON_UNESCAPED_SLASHES),
    'hashtags' => $data['hashtags'] !== '' ? $data['hashtags'] : null,
    'status' => $data['status'],
    'scheduled_at' => $scheduledAt,
];

try {
    db()->beginTransaction();

    if ($id > 0) {
        $setParts = [];
        foreach (array_keys($dbData) as $column) {
            $setParts[] = $column . ' = :' . $column;
        }
        execute_query('UPDATE social_media_posts SET ' . implode(', ', $setParts) . ' WHERE id = :id', array_merge($dbData, ['id' => $id]));
    } else {
        execute_query(
            "INSERT INTO social_media_posts
            (title, description, reference_destination_id, reference_itinerary_id, reference_tour_id, experience_name, platform_targets_json, hashtags, status, scheduled_at, created_by)
            VALUES
            (:title, :description, :reference_destination_id, :reference_itinerary_id, :reference_tour_id, :experience_name, :platform_targets_json, :hashtags, :status, :scheduled_at, :created_by)",
            array_merge($dbData, ['created_by' => $createdBy > 0 ? $createdBy : null])
        );
        $id = (int) last_insert_id();
    }

    execute_query('DELETE FROM social_media_post_assets WHERE post_id = :post_id', ['post_id' => $id]);
    $sortOrder = 1;
    foreach ($assetIds as $assetId) {
        execute_query(
            'INSERT IGNORE INTO social_media_post_assets (post_id, asset_id, sort_order) VALUES (:post_id, :asset_id, :sort_order)',
            [
                'post_id' => $id,
                'asset_id' => $assetId,
                'sort_order' => $sortOrder,
            ]
        );
        $sortOrder++;
    }

    db()->commit();
} catch (Throwable $e) {
    if (db()->inTransaction()) {
        db()->rollBack();
    }
    flash_set('error', 'Unable to save social draft. Please review the inputs and try again.');
    redirect($formRedirect);
}

old_input_clear();
flash_set('success', 'Social draft saved successfully.');
redirect('admin/social_posts.php');
