<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

admin_require_auth();
ensure_reviews_schema();

if (!is_post()) {
    redirect('admin/reviews.php');
}

guard_post_size();
require_csrf();

$id = (int) post_input('id', 0);

$tourDoneDate = trim((string) post_input('tour_done_date', ''));
$tourDoneMonth = $tourDoneDate !== '' ? substr($tourDoneDate, 0, 7) : trim((string) post_input('tour_done_month', ''));

$data = [
    'customer_name' => trim((string) post_input('customer_name', '')),
    'country' => trim((string) post_input('country', '')),
    'country_code' => strtoupper(trim((string) post_input('country_code', ''))),
    'rating' => max(1, min(5, (int) post_input('rating', 5))),
    'review_title' => trim((string) post_input('review_title', '')),
    'review_text' => trim((string) post_input('review_text', '')),
    'trip_type' => trim((string) post_input('trip_type', '')),
    'tour_done_date' => $tourDoneDate !== '' ? $tourDoneDate : null,
    'tour_done_month' => $tourDoneMonth !== '' ? $tourDoneMonth : null,
    'review_source' => trim((string) post_input('review_source', '')),
    'review_source_url' => trim((string) post_input('review_source_url', '')),
    'sort_order' => (int) post_input('sort_order', 0),
    'is_featured' => post_input('is_featured') ? 1 : 0,
    'is_published' => post_input('is_published') ? 1 : 0,
];

old_input_set($_POST);

$errors = [];

if ($data['customer_name'] === '') {
    $errors[] = 'Customer name is required.';
}
if ($data['review_text'] === '') {
    $errors[] = 'Review text is required.';
}
if ($data['country_code'] === '') {
    $data['country_code'] = null;
}
if ($data['country_code'] !== null && preg_match('/^[A-Z]{2}$/', $data['country_code']) !== 1) {
    $errors[] = 'Country code must be a 2-letter ISO code, for example AU or GB.';
}
if ($data['tour_done_date'] !== null && preg_match('/^\d{4}-\d{2}-\d{2}$/', $data['tour_done_date']) !== 1) {
    $errors[] = 'Tour done date must be a valid date.';
}
if ($data['tour_done_date'] !== null && preg_match('/^\d{4}-\d{2}-\d{2}$/', $data['tour_done_date']) === 1) {
    [$year, $month, $day] = array_map('intval', explode('-', $data['tour_done_date']));
    if (!checkdate($month, $day, $year)) {
        $errors[] = 'Tour done date must be a valid calendar date.';
    }
}
if ($data['review_source_url'] !== '' && !filter_var($data['review_source_url'], FILTER_VALIDATE_URL)) {
    $errors[] = 'Original review link must be a valid URL.';
}
if ($data['review_source_url'] !== '') {
    $sourceScheme = strtolower((string) parse_url($data['review_source_url'], PHP_URL_SCHEME));
    if (!in_array($sourceScheme, ['http', 'https'], true)) {
        $errors[] = 'Original review link must use http or https.';
    }
}

$lengthLimits = [
    'customer_name' => ['Customer name', 180],
    'country' => ['Country', 120],
    'review_title' => ['Review title', 255],
    'trip_type' => ['Trip type', 120],
    'review_source' => ['Review source', 120],
    'review_source_url' => ['Original review link', 255],
];
foreach ($lengthLimits as $field => [$label, $limit]) {
    if ($data[$field] !== null && mb_strlen((string) $data[$field]) > $limit) {
        $errors[] = $label . ' must be ' . $limit . ' characters or fewer.';
    }
}

$photoAlt = (array) post_input('photo_alt', []);
$photoSort = (array) post_input('photo_sort', []);
foreach ($photoAlt as $altText) {
    $altText = trim((string) $altText);
    if ($altText === '') {
        $errors[] = 'Photo alt text is required.';
        break;
    }
    if (mb_strlen($altText) > 255) {
        $errors[] = 'Photo alt text must be 255 characters or fewer.';
        break;
    }
}

$record = [];
if ($id > 0) {
    $record = fetch_one("SELECT * FROM reviews WHERE id = :id LIMIT 1", ['id' => $id]) ?: [];
    if (!$record) {
        flash_set('error', 'Review not found.');
        redirect('admin/reviews.php');
    }
}

if ($errors) {
    flash_set('error', implode(' ', $errors));
    redirect($id > 0 ? 'admin/review_form.php?id=' . $id : 'admin/review_form.php');
}

try {
    db()->beginTransaction();
    $photoPathsToDelete = [];

    if ($id > 0) {
        $setParts = [];
        foreach (array_keys($data) as $column) {
            $setParts[] = "{$column} = :{$column}";
        }
        execute_query(
            "UPDATE reviews SET " . implode(', ', $setParts) . " WHERE id = :id",
            array_merge($data, ['id' => $id])
        );
    } else {
        $columns = array_keys($data);
        $placeholders = array_map(static fn(string $column): string => ':' . $column, $columns);
        execute_query(
            "INSERT INTO reviews (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")",
            $data
        );
        $id = (int) last_insert_id();
    }

    $hasReviewPhotos = db_table_exists('review_photos');
    $deletePhotoIds = array_unique(array_map('intval', (array) post_input('delete_photo_ids', [])));
    if ($hasReviewPhotos) {
        foreach ($deletePhotoIds as $photoId) {
            if ($photoId > 0) {
                $photo = fetch_one(
                    "SELECT image_path FROM review_photos WHERE id = :photo_id AND review_id = :review_id LIMIT 1",
                    ['photo_id' => $photoId, 'review_id' => $id]
                );
                if ($photo && !empty($photo['image_path'])) {
                    $photoPathsToDelete[] = (string) $photo['image_path'];
                }
                execute_query(
                    "DELETE FROM review_photos WHERE id = :photo_id AND review_id = :review_id",
                    ['photo_id' => $photoId, 'review_id' => $id]
                );
            }
        }
    }

    if ($hasReviewPhotos) {
        $updateParams = ['review_id' => $id];
        $altCases = [];
        $sortCases = [];
        $idPlaceholders = [];

        foreach ($photoAlt as $photoId => $altText) {
            $photoId = (int) $photoId;
            if ($photoId <= 0) {
                continue;
            }

            $altCases[] = "WHEN id = :id_alt_{$photoId} THEN :alt_{$photoId}";
            $sortCases[] = "WHEN id = :id_sort_{$photoId} THEN :sort_{$photoId}";
            $idPlaceholders[] = ":id_in_{$photoId}";

            $updateParams["id_alt_{$photoId}"] = $photoId;
            $updateParams["id_sort_{$photoId}"] = $photoId;
            $updateParams["id_in_{$photoId}"] = $photoId;
            $updateParams["alt_{$photoId}"] = trim((string) $altText);
            $updateParams["sort_{$photoId}"] = (int) ($photoSort[$photoId] ?? 0);
        }

        if (!empty($idPlaceholders)) {
            $idList = implode(', ', $idPlaceholders);
            $altCaseSql = implode(' ', $altCases);
            $sortCaseSql = implode(' ', $sortCases);

            execute_query(
                "UPDATE review_photos
                 SET alt_text = CASE {$altCaseSql} ELSE alt_text END,
                     sort_order = CASE {$sortCaseSql} ELSE sort_order END
                 WHERE id IN ({$idList}) AND review_id = :review_id",
                $updateParams
            );
        }
    }

    $existingPhotoCount = $hasReviewPhotos
        ? (int) (fetch_one(
            "SELECT COUNT(*) AS total FROM review_photos WHERE review_id = :review_id",
            ['review_id' => $id]
        )['total'] ?? 0)
        : 0;
    $remainingSlots = max(0, 5 - $existingPhotoCount);

    if ($hasReviewPhotos && $remainingSlots > 0 && !empty($_FILES['review_photos']['name']) && is_array($_FILES['review_photos']['name'])) {
        $uploaded = 0;
        $insertData = [];
        $valuesParts = [];

        foreach ($_FILES['review_photos']['name'] as $index => $name) {
            if ($uploaded >= $remainingSlots) {
                break;
            }
            if (trim((string) $name) === '') {
                continue;
            }
            $file = [
                'name' => $_FILES['review_photos']['name'][$index] ?? '',
                'type' => $_FILES['review_photos']['type'][$index] ?? '',
                'tmp_name' => $_FILES['review_photos']['tmp_name'][$index] ?? '',
                'error' => $_FILES['review_photos']['error'][$index] ?? UPLOAD_ERR_NO_FILE,
                'size' => $_FILES['review_photos']['size'][$index] ?? 0,
            ];
            $path = upload_image($file, 'reviews');
            if ($path === null) {
                continue;
            }
            $valuesParts[] = "(:review_id_{$uploaded}, :image_path_{$uploaded}, :alt_text_{$uploaded}, :sort_order_{$uploaded})";
            $insertData["review_id_{$uploaded}"] = $id;
            $insertData["image_path_{$uploaded}"] = $path;
            $insertData["alt_text_{$uploaded}"] = mb_substr($data['customer_name'] . ' Sri Lanka tour photo', 0, 255);
            $insertData["sort_order_{$uploaded}"] = ($existingPhotoCount + $uploaded + 1) * 10;

            $uploaded++;
        }

        if (!empty($insertData)) {
            execute_query(
                "INSERT INTO review_photos (review_id, image_path, alt_text, sort_order) VALUES " . implode(', ', $valuesParts),
                $insertData
            );
        }
    }

    db()->commit();

    foreach (array_unique($photoPathsToDelete) as $photoPath) {
        delete_uploaded_media($photoPath);
    }

    old_input_clear();
    flash_set('success', 'Review saved successfully.');
    redirect('admin/reviews.php');
} catch (Throwable $e) {
    if (db()->inTransaction()) {
        db()->rollBack();
    }

    flash_set('error', 'Failed to save review.');
    redirect($id > 0 ? 'admin/review_form.php?id=' . $id : 'admin/review_form.php');
}
