<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/indexnow.php';

admin_require_auth();
ensure_business_partners_schema();

if (!is_post()) {
    redirect('admin/business_partners.php');
}

guard_post_size();
require_csrf();

$id = (int) post_input('id', 0);
$statuses = business_partner_statuses();
$businessTypes = business_partner_types();

$data = [
    'category_id' => max(1, (int) post_input('category_id', 1)),
    'business_name' => trim((string) post_input('business_name', '')),
    'slug' => trim((string) post_input('slug', '')),
    'business_type' => trim((string) post_input('business_type', 'hotel')),
    'location' => trim((string) post_input('location', '')),
    'destination_id' => (int) post_input('destination_id', 0),
    'contact_person' => trim((string) post_input('contact_person', '')),
    'contact_role' => trim((string) post_input('contact_role', '')),
    'email' => trim((string) post_input('email', '')),
    'phone' => trim((string) post_input('phone', '')),
    'whatsapp' => trim((string) post_input('whatsapp', '')),
    'website_url' => trim((string) post_input('website_url', '')),
    'google_maps_url' => trim((string) post_input('google_maps_url', '')),
    'short_description' => trim((string) post_input('short_description', '')),
    'partner_reason' => trim((string) post_input('partner_reason', '')),
    'accommodation_details' => trim((string) post_input('accommodation_details', '')),
    'price_range' => trim((string) post_input('price_range', '')),
    'admin_notes' => trim((string) post_input('admin_notes', '')),
    'status' => trim((string) post_input('status', 'applied')),
    'is_featured' => post_input('is_featured') ? 1 : 0,
    'sort_order' => (int) post_input('sort_order', 0),
    'meta_title' => trim((string) post_input('meta_title', '')),
    'meta_description' => trim((string) post_input('meta_description', '')),
    'canonical_url' => trim((string) post_input('canonical_url', '')),
];

$data['slug'] = $data['slug'] !== '' ? slugify($data['slug']) : slugify($data['business_name']);
$data['canonical_url'] = $data['canonical_url'] !== '' ? $data['canonical_url'] : base_url('business-partners/partner/' . $data['slug']);
$data['destination_id'] = $data['destination_id'] > 0 ? $data['destination_id'] : null;
$destinationIds = array_values(array_filter(
    array_unique(array_map('intval', (array) post_input('destinations', []))),
    static fn(int $destinationId): bool => $destinationId > 0
));
if ($data['destination_id'] !== null) {
    $destinationIds[] = (int) $data['destination_id'];
    $destinationIds = array_values(array_unique($destinationIds));
}
$itineraryIds = array_values(array_filter(
    array_unique(array_map('intval', (array) post_input('itineraries', []))),
    static fn(int $itineraryId): bool => $itineraryId > 0
));

foreach (['contact_person', 'contact_role', 'email', 'phone', 'whatsapp', 'website_url', 'google_maps_url', 'partner_reason', 'accommodation_details', 'price_range', 'admin_notes', 'meta_title', 'meta_description'] as $nullable) {
    if ($data[$nullable] === '') {
        $data[$nullable] = null;
    }
}

old_input_set($_POST);

$errors = [];
if ($data['business_name'] === '') {
    $errors[] = 'Business name is required.';
}
if ($data['slug'] === '') {
    $errors[] = 'Slug could not be generated.';
}
$canonicalScheme = strtolower((string) parse_url($data['canonical_url'], PHP_URL_SCHEME));
if (!filter_var($data['canonical_url'], FILTER_VALIDATE_URL) || !in_array($canonicalScheme, ['http', 'https'], true)) {
    $errors[] = 'Canonical URL must be a valid http(s) URL.';
}
if (!isset($businessTypes[$data['business_type']])) {
    $errors[] = 'Business type is invalid.';
}
if (!isset($statuses[$data['status']])) {
    $errors[] = 'Status is invalid.';
}
if ($data['email'] !== null && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Email must be valid.';
}
if ($data['website_url'] !== null && !filter_var($data['website_url'], FILTER_VALIDATE_URL)) {
    $errors[] = 'Website URL must be valid.';
}
if ($data['google_maps_url'] !== null && !filter_var($data['google_maps_url'], FILTER_VALIDATE_URL)) {
    $errors[] = 'Google Maps URL must be valid.';
}
foreach (['website_url' => 'Website URL', 'google_maps_url' => 'Google Maps URL'] as $urlField => $urlLabel) {
    if ($data[$urlField] === null) {
        continue;
    }
    $scheme = strtolower((string) parse_url((string) $data[$urlField], PHP_URL_SCHEME));
    if (!in_array($scheme, ['http', 'https'], true)) {
        $errors[] = $urlLabel . ' must use http or https.';
    }
}

$lengthLimits = [
    'business_name' => ['Business name', 255],
    'slug' => ['Slug', 255],
    'business_type' => ['Business type', 80],
    'location' => ['Location', 180],
    'contact_person' => ['Contact person', 180],
    'contact_role' => ['Contact role', 180],
    'email' => ['Email', 190],
    'phone' => ['Phone', 80],
    'whatsapp' => ['WhatsApp', 80],
    'website_url' => ['Website URL', 255],
    'google_maps_url' => ['Google Maps URL', 255],
    'price_range' => ['Price range', 80],
    'meta_title' => ['Meta title', 255],
    'meta_description' => ['Meta description', 320],
    'canonical_url' => ['Canonical URL', 255],
];
foreach ($lengthLimits as $field => [$label, $limit]) {
    if ($data[$field] !== null && mb_strlen((string) $data[$field]) > $limit) {
        $errors[] = $label . ' must be ' . $limit . ' characters or fewer.';
    }
}

if (db_table_exists('business_partner_categories')) {
    $categoryExists = fetch_one(
        'SELECT id FROM business_partner_categories WHERE id = :id LIMIT 1',
        ['id' => $data['category_id']]
    );
    if (!$categoryExists) {
        $errors[] = 'Category is invalid.';
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

$photoAlt = (array) post_input('photo_alt', []);
$photoSort = (array) post_input('photo_sort', []);
foreach ($photoAlt as $altText) {
    if (mb_strlen(trim((string) $altText)) > 255) {
        $errors[] = 'Photo alt text must be 255 characters or fewer.';
        break;
    }
}

$existing = fetch_one(
    'SELECT id FROM business_partners WHERE slug = :slug AND id != :id LIMIT 1',
    ['slug' => $data['slug'], 'id' => $id]
);
if ($existing) {
    $errors[] = 'Slug already exists.';
}

$record = [];
if ($id > 0) {
    $record = fetch_one('SELECT * FROM business_partners WHERE id = :id LIMIT 1', ['id' => $id]) ?: [];
    if (!$record) {
        flash_set('error', 'Business partner not found.');
        redirect('admin/business_partners.php');
    }
}

if ($errors) {
    flash_set('error', implode(' ', $errors));
    redirect($id > 0 ? 'admin/business_partner_form.php?id=' . $id : 'admin/business_partner_form.php');
}

try {
    db()->beginTransaction();

    $photoPathsToDelete = [];
    $heroImage = $record['hero_image'] ?? null;
    $ogImage = $record['og_image'] ?? null;

    if (!empty($_FILES['hero_image']['name'])) {
        $heroImage = upload_image($_FILES['hero_image'], 'business-partners');
    }
    if (!empty($_FILES['og_image']['name'])) {
        $ogImage = upload_image($_FILES['og_image'], 'business-partners');
    }

    $wasPublished = (string) ($record['status'] ?? '') === 'published';
    $isPublished = $data['status'] === 'published';
    $publishedAtSql = $isPublished && (!$wasPublished || empty($record['published_at'])) ? ', published_at = CURRENT_TIMESTAMP' : '';

    $writeData = array_merge($data, [
        'hero_image' => $heroImage,
        'og_image' => $ogImage,
    ]);

    if ($id > 0) {
        $setParts = [];
        foreach (array_keys($writeData) as $column) {
            $setParts[] = "{$column} = :{$column}";
        }
        execute_query(
            'UPDATE business_partners SET ' . implode(', ', $setParts) . $publishedAtSql . ' WHERE id = :id',
            array_merge($writeData, ['id' => $id])
        );
    } else {
        $columns = array_keys($writeData);
        $placeholders = array_map(static fn(string $column): string => ':' . $column, $columns);
        $extraColumns = $isPublished ? ', published_at' : '';
        $extraValues = $isPublished ? ', CURRENT_TIMESTAMP' : '';
        execute_query(
            'INSERT INTO business_partners (' . implode(', ', $columns) . $extraColumns . ') VALUES (' . implode(', ', $placeholders) . $extraValues . ')',
            $writeData
        );
        $id = (int) last_insert_id();
    }

    $hasPartnerDestination = db_table_exists('business_partner_destination');
    $hasPartnerItinerary = db_table_exists('business_partner_itinerary');
    $hasPartnerPhotos = db_table_exists('business_partner_photos');

    if ($hasPartnerDestination) {
        execute_query('DELETE FROM business_partner_destination WHERE partner_id = :id', ['id' => $id]);
    }
    if ($hasPartnerItinerary) {
        execute_query('DELETE FROM business_partner_itinerary WHERE partner_id = :id', ['id' => $id]);
    }

    if ($hasPartnerDestination) {
        $validDestinationIds = array_values(array_filter($destinationIds, static fn(int $destinationId): bool => $destinationId > 0));
        if (!empty($validDestinationIds)) {
            $values = [];
            $params = ['partner_id' => $id];
            foreach ($validDestinationIds as $index => $destinationId) {
                $values[] = "(:partner_id, :destination_id_{$index})";
                $params["destination_id_{$index}"] = $destinationId;
            }
            execute_query(
                'INSERT IGNORE INTO business_partner_destination (partner_id, destination_id) VALUES ' . implode(', ', $values),
                $params
            );
        }
    }

    if ($hasPartnerItinerary) {
        $validItineraryIds = array_values(array_filter($itineraryIds, static fn(int $itineraryId): bool => $itineraryId > 0));
        if (!empty($validItineraryIds)) {
            $values = [];
            $params = ['partner_id' => $id];
            foreach ($validItineraryIds as $index => $itineraryId) {
                $values[] = "(:partner_id, :itinerary_id_{$index})";
                $params["itinerary_id_{$index}"] = $itineraryId;
            }
            execute_query(
                'INSERT IGNORE INTO business_partner_itinerary (partner_id, itinerary_id) VALUES ' . implode(', ', $values),
                $params
            );
        }
    }

    if ($hasPartnerPhotos) {
        $deletePhotoIds = array_values(array_filter(
            array_unique(array_map('intval', (array) post_input('delete_photo_ids', []))),
            static fn(int $photoId): bool => $photoId > 0
        ));
        if (!empty($deletePhotoIds)) {
            $placeholders = implode(',', array_map(static fn(int $index): string => ':id_' . $index, array_keys($deletePhotoIds)));
            $params = ['partner_id' => $id];
            foreach ($deletePhotoIds as $index => $photoId) {
                $params['id_' . $index] = $photoId;
            }

            $photosToDelete = fetch_all(
                "SELECT image_path FROM business_partner_photos WHERE partner_id = :partner_id AND id IN ({$placeholders})",
                $params
            );

            foreach ($photosToDelete as $photo) {
                if (!empty($photo['image_path'])) {
                    $photoPathsToDelete[] = (string) $photo['image_path'];
                }
            }

            execute_query(
                "DELETE FROM business_partner_photos WHERE partner_id = :partner_id AND id IN ({$placeholders})",
                $params
            );
        }
    }

    foreach ($photoAlt as $photoId => $altText) {
        $photoId = (int) $photoId;
        if ($photoId <= 0) {
            continue;
        }
        if ($hasPartnerPhotos) {
            execute_query(
                'UPDATE business_partner_photos
                 SET alt_text = :alt_text, sort_order = :sort_order
                 WHERE id = :photo_id AND partner_id = :partner_id',
                [
                    'alt_text' => trim((string) $altText),
                    'sort_order' => (int) ($photoSort[$photoId] ?? 0),
                    'photo_id' => $photoId,
                    'partner_id' => $id,
                ]
            );
        }
    }

    $existingPhotoCount = $hasPartnerPhotos
        ? (int) (fetch_one(
            'SELECT COUNT(*) AS total FROM business_partner_photos WHERE partner_id = :partner_id',
            ['partner_id' => $id]
        )['total'] ?? 0)
        : 0;

    if ($hasPartnerPhotos && !empty($_FILES['partner_photos']['name']) && is_array($_FILES['partner_photos']['name'])) {
        $uploaded = 0;
        foreach ($_FILES['partner_photos']['name'] as $index => $name) {
            if (trim((string) $name) === '') {
                continue;
            }
            $file = [
                'name' => $_FILES['partner_photos']['name'][$index] ?? '',
                'type' => $_FILES['partner_photos']['type'][$index] ?? '',
                'tmp_name' => $_FILES['partner_photos']['tmp_name'][$index] ?? '',
                'error' => $_FILES['partner_photos']['error'][$index] ?? UPLOAD_ERR_NO_FILE,
                'size' => $_FILES['partner_photos']['size'][$index] ?? 0,
            ];
            $path = upload_image($file, 'business-partners');
            if ($path === null) {
                continue;
            }
            execute_query(
                'INSERT INTO business_partner_photos (partner_id, image_path, alt_text, sort_order)
                 VALUES (:partner_id, :image_path, :alt_text, :sort_order)',
                [
                    'partner_id' => $id,
                    'image_path' => $path,
                    'alt_text' => mb_substr($data['business_name'] . ' accommodation partner', 0, 255),
                    'sort_order' => ($existingPhotoCount + $uploaded + 1) * 10,
                ]
            );
            $uploaded++;
        }
    }

    db()->commit();

    foreach (array_unique($photoPathsToDelete) as $photoPath) {
        delete_uploaded_media($photoPath);
    }

    $oldSlug = trim((string) ($record['slug'] ?? ''));
    $indexNowUrls = [
        base_url('business-partners'),
        base_url('business-partners/hotels-accommodations'),
        base_url('sitemap.xml'),
    ];
    if ($isPublished) {
        $indexNowUrls[] = base_url('business-partners/partner/' . $data['slug']);
    }
    if ($oldSlug !== '' && ($oldSlug !== $data['slug'] || !$isPublished) && $wasPublished) {
        $indexNowUrls[] = base_url('business-partners/partner/' . $oldSlug);
    }
    indexnow_submit_urls($indexNowUrls);

    old_input_clear();
    flash_set('success', 'Business partner saved successfully.');
    redirect('admin/business_partners.php');
} catch (Throwable $e) {
    if (db()->inTransaction()) {
        db()->rollBack();
    }

    flash_set('error', 'Could not save business partner.');
    redirect($id > 0 ? 'admin/business_partner_form.php?id=' . $id : 'admin/business_partner_form.php');
}
