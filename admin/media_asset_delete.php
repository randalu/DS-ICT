<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

admin_require_auth();

if (!is_post()) {
    redirect('admin/media_library.php');
}

guard_post_size();
require_csrf();
ensure_social_marketing_schema();

$id = (int) post_input('id', 0);
if ($id <= 0) {
    flash_set('error', 'Invalid media asset selected.');
    redirect('admin/media_library.php');
}

$record = fetch_one('SELECT id, file_path FROM media_assets WHERE id = :id LIMIT 1', ['id' => $id]);
if (!$record) {
    flash_set('error', 'Media asset not found.');
    redirect('admin/media_library.php');
}

$referenceChecks = [
    ['itineraries', 'cover_media_asset_id', 'itinerary cover'],
    ['tours', 'cover_media_asset_id', 'tour cover'],
    ['day_tours', 'cover_media_asset_id', 'day tour cover'],
    ['destinations', 'hero_media_asset_id', 'destination hero'],
    ['itineraries', 'hero_media_asset_id', 'itinerary hero'],
    ['experiences', 'hero_media_asset_id', 'experience hero'],
    ['blog_posts', 'hero_media_asset_id', 'blog hero'],
    ['static_pages', 'hero_media_asset_id', 'page hero'],
    ['homepage_hero_media', 'media_asset_id', 'homepage hero'],
];
$references = [];
require_once __DIR__.'/../includes/page_media.php';
foreach(page_media_types() as $mediaType=>[$mediaTable]){
    if(!db_table_exists($mediaTable))continue;
    foreach(['hero_image','cover_image','og_image','hero_video_poster'] as $field)if(db_column_exists($mediaTable,$field)){
        $count=(int)(fetch_one("SELECT COUNT(*) AS n FROM $mediaTable WHERE $field=:path",['path'=>$record['file_path']])['n']??0);if($count)$references[]=$mediaType.' '.$field.' ('.$count.')';
    }
}
if(db_table_exists('page_gallery_images') && fetch_one('SELECT id FROM page_gallery_images WHERE media_asset_id=:id LIMIT 1',['id'=>$id]))$references[]='page gallery';
foreach(['destination','experience','day_tour'] as $mediaType){[$galleryTable,,$imageField]=page_gallery_storage($mediaType);if(db_column_exists($galleryTable,$imageField) && fetch_one("SELECT id FROM $galleryTable WHERE $imageField=:path LIMIT 1",['path'=>$record['file_path']]))$references[]=$mediaType.' gallery';}

require_once __DIR__.'/../includes/activity_gallery.php';
$activityReferences=activity_gallery_reference_count($id,(string)$record['file_path']);
if($activityReferences){$references[]='Activity page or entry images ('.$activityReferences.')';}
foreach ($referenceChecks as [$table, $column, $label]) {
    if (!db_table_exists($table) || !db_column_exists($table, $column)) {
        continue;
    }
    $count = (int) (fetch_one(
        "SELECT COUNT(*) AS total FROM {$table} WHERE {$column} = :asset_id",
        ['asset_id' => $id]
    )['total'] ?? 0);
    if ($count > 0) {
        $references[] = $label . ' (' . $count . ')';
    }
}

require_once __DIR__ . '/../includes/tour_coverage.php';
require_once __DIR__ . '/../includes/tour_program.php';
$programReferences = tour_program_media_reference_count($id);
if ($programReferences > 0) { $references[] = 'Tour days or stays (' . $programReferences . ')'; }
$coverageReferences = tour_coverage_media_reference_count($id);
if ($coverageReferences > 0) {
    $references[] = 'tour coverage (' . $coverageReferences . ')';
}

if ($references) {
    flash_set('error', 'Media asset is still used by ' . implode(', ', $references) . '. Remove those references before deleting it.');
    redirect('admin/media_library.php');
}

try {
    db()->beginTransaction();

    if (db_table_exists('social_media_post_assets')) {
        execute_query('DELETE FROM social_media_post_assets WHERE asset_id = :asset_id', ['asset_id' => $id]);
    }
    execute_query('DELETE FROM media_assets WHERE id = :id', ['id' => $id]);

    db()->commit();
} catch (Throwable $e) {
    if (db()->inTransaction()) {
        db()->rollBack();
    }
    flash_set('error', 'Failed to delete media asset.');
    redirect('admin/media_library.php');
}

$filePath = trim((string) ($record['file_path'] ?? ''));
if ($filePath !== '') {
    delete_uploaded_media($filePath);
}

flash_set('success', 'Media asset deleted.');
redirect('admin/media_library.php');
