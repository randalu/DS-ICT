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
if ($id <= 0) {
    flash_set('error', 'Invalid business partner ID.');
    redirect('admin/business_partners.php');
}

$record = fetch_one('SELECT slug, status, hero_image, og_image FROM business_partners WHERE id = :id LIMIT 1', ['id' => $id]);
if (!$record) {
    flash_set('error', 'Business partner not found.');
    redirect('admin/business_partners.php');
}

$photos = fetch_business_partner_photos($id);
$pathsToDelete = [];
foreach ([$record['hero_image'] ?? '', $record['og_image'] ?? ''] as $path) {
    if (trim((string) $path) !== '') {
        $pathsToDelete[] = (string) $path;
    }
}
foreach ($photos as $photo) {
    if (!empty($photo['image_path'])) {
        $pathsToDelete[] = (string) $photo['image_path'];
    }
}

try {
    db()->beginTransaction();

    if (db_table_exists('business_partner_photos')) {
        execute_query('DELETE FROM business_partner_photos WHERE partner_id = :id', ['id' => $id]);
    }
    if (db_table_exists('business_partner_destination')) {
        execute_query('DELETE FROM business_partner_destination WHERE partner_id = :id', ['id' => $id]);
    }
    if (db_table_exists('business_partner_itinerary')) {
        execute_query('DELETE FROM business_partner_itinerary WHERE partner_id = :id', ['id' => $id]);
    }
    execute_query('DELETE FROM business_partners WHERE id = :id', ['id' => $id]);

    db()->commit();

    foreach (array_unique($pathsToDelete) as $path) {
        delete_uploaded_media($path);
    }
} catch (Throwable $e) {
    if (db()->inTransaction()) {
        db()->rollBack();
    }
    flash_set('error', 'Failed to delete business partner.');
    redirect('admin/business_partners.php');
}

$indexNowUrls = [base_url('business-partners'), base_url('business-partners/hotels-accommodations'), base_url('sitemap.xml')];
if (($record['status'] ?? '') === 'published' && !empty($record['slug'])) {
    $indexNowUrls[] = base_url('business-partners/partner/' . $record['slug']);
}
indexnow_submit_urls($indexNowUrls);

flash_set('success', 'Business partner deleted.');
redirect('admin/business_partners.php');
