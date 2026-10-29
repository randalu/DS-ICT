<?php
declare(strict_types=1);
require_once __DIR__ . '/image_manager.php';

function package_gallery_revision(int $id): string
{
    return hash_hmac('sha256', json_encode(image_manager_children('day_tour', $id), JSON_THROW_ON_ERROR), csrf_token());
}

function package_gallery_save(int $id, array $input, array $files): void
{
    if (!db_table_exists('day_tour_gallery') || !db_column_exists('day_tour_gallery', 'alt_text')) { throw new InvalidArgumentException('Gallery schema is unavailable.'); }
    $created = null; $pdo = db(); $nested = $pdo->inTransaction();
    foreach (['day_tours','day_tour_gallery','media_assets'] as $table) {
        $engine = fetch_one('SHOW TABLE STATUS WHERE Name=:name', ['name' => $table]);
        if (strtolower((string) ($engine['Engine'] ?? '')) !== 'innodb') { throw new InvalidArgumentException('Gallery saves require transactional tables.'); }
    }
    $nested ? $pdo->exec('SAVEPOINT package_gallery_save') : $pdo->beginTransaction();
    try {
        image_manager_record('day_tour', $id, true);
        if (!is_string($input['revision'] ?? null) || !hash_equals(package_gallery_revision($id), $input['revision'])) { throw new DomainException('The gallery changed. Reload and compare before saving.'); }
        $child = filter_var($input['photo_id'] ?? 0, FILTER_VALIDATE_INT);
        if ($child === false || $child < 0) { throw new InvalidArgumentException('Invalid gallery entry.'); }
        $row = $child ? fetch_one('SELECT * FROM day_tour_gallery WHERE id=:id AND day_tour_id=:owner', ['id' => $child, 'owner' => $id]) : null;
        if ($child && !$row) { throw new InvalidArgumentException('Gallery entry does not belong to this day tour.'); }
        if (($input['action'] ?? '') === 'remove') {
            if (!$row) { throw new InvalidArgumentException('Choose a gallery entry.'); }
            execute_query('DELETE FROM day_tour_gallery WHERE id=:id AND day_tour_id=:owner', ['id' => $child, 'owner' => $id]);
        } else {
            $alt = $input['alt_text'] ?? '';
            if (!is_string($alt) || trim($alt) === '' || mb_strlen($alt) > 255 || strip_tags($alt) !== $alt || str_contains($alt, "\0")) { throw new InvalidArgumentException('Use a photo description of up to 255 plain text characters.'); }
            $sort = filter_var($input['sort_order'] ?? 0, FILTER_VALIDATE_INT);
            if ($sort === false || $sort < -2147483648 || $sort > 2147483647) { throw new InvalidArgumentException('Invalid order.'); }
            $file = $files['gallery_image'] ?? null;
            $hasFile = is_array($file) && ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
            $asset = $input['gallery_image_media_asset_id'] ?? '';
            if (!is_string($asset) || ($asset !== '' && !ctype_digit($asset)) || ($hasFile && (int) $asset > 0)) { throw new InvalidArgumentException('Choose one upload or library photo.'); }
            $media = $hasFile ? image_manager_upload($file, false, $created) : ((int) $asset > 0 ? image_manager_library_asset((int) $asset, false) : null);
            if ($hasFile && db_column_exists('media_assets', 'alt_text')) { execute_query('UPDATE media_assets SET alt_text=:alt WHERE id=:id', ['alt' => trim($alt), 'id' => $media['asset_id']]); }
            $path = $media['path'] ?? $row['image_path'] ?? '';
            if ($path === '') { throw new InvalidArgumentException('Select a photo.'); }
            $params = ['owner' => $id, 'path' => $path, 'alt' => trim($alt), 'sort' => $sort];
            if ($row) { execute_query('UPDATE day_tour_gallery SET image_path=:path,alt_text=:alt,sort_order=:sort WHERE id=:id AND day_tour_id=:owner', $params + ['id' => $child]); }
            else { execute_query('INSERT INTO day_tour_gallery(day_tour_id,image_path,alt_text,sort_order) VALUES(:owner,:path,:alt,:sort)', $params); }
        }
        $nested ? $pdo->exec('RELEASE SAVEPOINT package_gallery_save') : $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) { $nested ? $pdo->exec('ROLLBACK TO SAVEPOINT package_gallery_save') : $pdo->rollBack(); }
        if ($created !== null) { delete_uploaded_media($created); }
        throw $e;
    }
}
