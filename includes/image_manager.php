<?php
declare(strict_types=1);

require_once __DIR__ . '/functions.php';
require_once __DIR__.'/page_media.php';

/** This service never creates schema or calls the full content save handlers. */
function image_manager_types(): array
{
    $types = ['destination' => ['table' => 'destinations', 'label' => 'Destinations', 'form' => 'destination_form.php'],
        'itinerary' => ['table' => 'itineraries', 'label' => 'Itineraries', 'form' => 'itinerary_form.php'],
        'tour' => ['table' => 'tours', 'label' => 'Tour Packages', 'form' => 'tour_form.php'],
        'day_tour' => ['table' => 'day_tours', 'label' => 'Day Tour Packages', 'form' => 'day_tour_form.php']];
    if (db_table_exists('activity_pages')) {
        $types['activity'] = ['table' => 'activity_pages', 'label' => 'Activities', 'form' => 'activity_form.php'];
    }
    foreach(['experience','blog','page','blog_landing'] as $key){$def=page_media_types()[$key];$types[$key]=['table'=>$def[0],'label'=>match($key){'experience'=>'Experiences','blog'=>'Blog posts','page'=>'Static pages',default=>'Blog landing'},'form'=>$def[2]];}
    return $types;
}

function image_manager_type(string $type): array
{
    return image_manager_types()[$type] ?? throw new InvalidArgumentException('Choose a supported content type.');
}

function image_manager_return(mixed $value): string
{
    return match ($value) {
        'image_manager' => 'admin/image_manager.php',
        'seo_manager' => 'admin/seo_manager.php',
        'quotations' => 'admin/quotations.php',
        default => 'admin/dashboard.php',
    };
}

function image_manager_ini_bytes(string $value): int
{
    $value = trim($value);
    return (int) ((float) $value * match (strtolower(substr($value, -1))) {
        'g' => 1073741824, 'm' => 1048576, 'k' => 1024, default => 1,
    });
}

function image_manager_limit(bool $video): int
{
    $limits = [$video ? 100 * 1048576 : 20 * 1048576];
    foreach (['upload_max_filesize', 'post_max_size'] as $key) {
        $bytes = image_manager_ini_bytes((string) ini_get($key));
        if ($bytes > 0) { $limits[] = $key === 'post_max_size' ? max(0, $bytes - 65536) : $bytes; }
    }
    return min($limits);
}

function image_manager_record(string $type, int $id, bool $lock = false): array
{
    $table = image_manager_type($type)['table'];
    if (!db_table_exists($table)) { throw new InvalidArgumentException('This content is unavailable in this database.'); }
    $suffix = $lock && db()->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql' ? ' FOR UPDATE' : '';
    return fetch_one("SELECT * FROM {$table} WHERE id = :id" . $suffix, ['id' => $id])
        ?? throw new InvalidArgumentException('Content record not found.');
}

function image_manager_children(string $type, int $id): array
{
    if ($type === 'itinerary' && db_table_exists('itinerary_days') && db_column_exists('itinerary_days', 'image_path')) {
        return fetch_all('SELECT * FROM itinerary_days WHERE itinerary_id = :id ORDER BY day_number, id', ['id' => $id]);
    }
    if ($type === 'day_tour' && db_table_exists('day_tour_gallery') && db_column_exists('day_tour_gallery', 'image_path') && db_column_exists('day_tour_gallery', 'alt_text') && db_column_exists('day_tour_gallery', 'sort_order')) {
        return fetch_all('SELECT * FROM day_tour_gallery WHERE day_tour_id = :id ORDER BY sort_order, id', ['id' => $id]);
    }
    if ($type === 'destination' && db_table_exists('destination_photos') && db_column_exists('destination_photos', 'image_path')) {
        return fetch_all('SELECT * FROM destination_photos WHERE destination_id = :id ORDER BY sort_order, id', ['id' => $id]);
    }
    if ($type === 'tour' && db_column_exists('tours', 'coverage_json')) {
        require_once __DIR__ . '/tour_coverage.php';
        $parent = image_manager_record($type, $id);
        return array_map(static fn($row, $index) => $row + ['id' => $index + 1], tour_coverage_decode($parent['coverage_json'] ?? null), array_keys(tour_coverage_decode($parent['coverage_json'] ?? null)));
    }
    return [];
}

function image_manager_slot(string $type, array $parent, string $slot, int $childId = 0): array
{
    $table = image_manager_type($type)['table'];
    $field = $slot;
    $row = $parent;
    $where = ['id' => (int) $parent['id']];
    $video = false;
    if (in_array($slot, ['program_day','program_hotel'], true)) {
        require_once __DIR__ . '/tour_program.php';
        if ($type !== 'tour' || !array_key_exists('program_json', $parent)) { throw new InvalidArgumentException('Tour programme is unavailable.'); }
        $program = tour_program_decode($parent['program_json']);
        $group = $slot === 'program_day' ? 'days' : 'hotels';
        $card = $program[$group][$childId - 1] ?? throw new InvalidArgumentException('Programme row no longer exists. Reload this Tour.');
        $asset = !empty($card['image_asset_id']) ? fetch_one('SELECT file_path FROM media_assets WHERE id=:id', ['id'=>(int)$card['image_asset_id']]) : null;
        $field = 'program_json'; $row = $parent + ['image_alt'=>$card['image_alt'] ?? '', '_image_asset_id'=>(int)($card['image_asset_id']??0)];
        $coveragePath = (string)($asset['file_path'] ?? '');
    } elseif ($slot === 'coverage') {
        require_once __DIR__ . '/tour_coverage.php';
        if ($type !== 'tour' || !array_key_exists('coverage_json', $parent)) { throw new InvalidArgumentException('Tour coverage is unavailable.'); }
        $cards = tour_coverage_decode($parent['coverage_json']);
        $card = $cards[$childId - 1] ?? throw new InvalidArgumentException('Coverage card no longer exists. Reload this tour.');
        $asset = !empty($card['image_asset_id']) && db_table_exists('media_assets') ? fetch_one('SELECT file_path FROM media_assets WHERE id=:id', ['id' => (int) $card['image_asset_id']]) : null;
        $field = 'coverage_json';
        $row = $parent + ['image_alt' => $card['image_alt'] ?? '', '_image_asset_id'=>(int)($card['image_asset_id']??0)];
        $coveragePath = (string) ($asset['file_path'] ?? '');
    } elseif (in_array($slot, ['day', 'gallery', 'gallery_add', 'day_tour_gallery'], true)) {
        if (($slot === 'day' && $type !== 'itinerary') || (in_array($slot, ['gallery', 'gallery_add'], true) && $type !== 'destination') || ($slot === 'day_tour_gallery' && $type !== 'day_tour')) {
            throw new InvalidArgumentException('This image slot does not belong to this content type.');
        }
        $table = match ($slot) { 'day' => 'itinerary_days', 'day_tour_gallery' => 'day_tour_gallery', default => 'destination_photos' };
        $owner = match ($slot) { 'day' => 'itinerary_id', 'day_tour_gallery' => 'day_tour_id', default => 'destination_id' };
        if (!db_table_exists($table) || !db_column_exists($table, 'image_path')) {
            throw new InvalidArgumentException('These images are unavailable in this database.');
        }
        if ($slot === 'day_tour_gallery' && !db_column_exists($table, 'alt_text')) { throw new InvalidArgumentException('Day tour gallery alt text is unavailable.'); }
        $field = 'image_path';
        if ($slot === 'gallery_add') {
            $row = ['gallery' => image_manager_children($type, (int) $parent['id'])];
            $where = [$owner => (int) $parent['id']];
        } else {
            $where = ['id' => $childId, $owner => (int) $parent['id']];
            $row = fetch_one("SELECT * FROM {$table} WHERE id = :id AND {$owner} = :{$owner}", $where)
                ?? throw new InvalidArgumentException('Image entry not found for this content.');
        }
    } else {
        $allowed = ['hero_image', 'og_image'];
        $allowed[] = 'cover_image';
        if (in_array($type, ['destination', 'itinerary'], true) && db_column_exists($table, 'hero_media_type')) { $allowed[] = 'hero_video_poster'; }
        if (!in_array($slot, $allowed, true) || !db_column_exists($table, $field)) {
            throw new InvalidArgumentException('This media field is unavailable.');
        }
        $video = $slot === 'hero_image' && in_array($type, ['destination', 'itinerary'], true) && db_column_exists($table, 'hero_media_type');
        if ($slot === 'hero_video_poster' && ($row['hero_media_type'] ?? '') !== 'video') {
            throw new InvalidArgumentException('Choose a video hero before changing its poster.');
        }
    }
    $state = [$type, (int) $parent['id'], $slot, $childId, $row[$field] ?? null];
    require_once __DIR__.'/media_alt.php';
    $state[]=media_alt_placement($type,(int)$parent['id'],$slot,$childId,(string)($row[$field]??''));
    if ($slot === 'hero_image' || $slot === 'hero_video_poster') {
        $state[] = [$parent['hero_image'] ?? null, $parent['hero_media_type'] ?? null,
            $parent['hero_media_asset_id'] ?? null, $parent['hero_video_poster'] ?? null];
    }
    if ($slot === 'cover_image') { $state[] = [$parent['cover_alt'] ?? null, $parent['cover_media_asset_id'] ?? null]; }
    if ($slot === 'gallery') { $state[] = $row['caption'] ?? ''; }
    if ($slot === 'day_tour_gallery') { $state[] = $row['alt_text'] ?? ''; }
    if ($slot === 'gallery_add') { $state[] = $row['gallery']; }
    return ['table' => $table, 'field' => $field, 'row' => $row, 'where' => $where, 'video' => $video,
        'path' => in_array($slot, ['coverage','program_day','program_hotel'], true) ? $coveragePath : (string) ($row[$field] ?? ''),
        'revision' => hash_hmac('sha256', json_encode($state, JSON_THROW_ON_ERROR), csrf_token())];
}

function image_manager_present(string $type, array $parent, string $slot, int $childId = 0): array
{
    $target = image_manager_slot($type, $parent, $slot, $childId);
    $video = $slot === 'hero_image' && ($parent['hero_media_type'] ?? '') === 'video';
    require_once __DIR__.'/media_alt.php';
    $altAsset=media_alt_target_asset($parent,$target,$slot);
    $placementAlt=media_alt_placement($type,(int)$parent['id'],$slot,$childId,$target['path']);
    return ['asset_id'=>(int)($altAsset['id']??0), 'asset_alt_revision'=>$altAsset?media_alt_revision($altAsset):'', 'revision' => $target['revision'], 'path' => $target['path'],
        'url' => $target['path'] !== '' ? stored_media_url($target['path']) : '',
        'media_type' => $video ? 'video' : 'image',
        'poster_url' => $video && !empty($parent['hero_video_poster']) ? stored_media_url($parent['hero_video_poster']) : '',
        'caption' => (string) ($target['row']['caption'] ?? $target['row']['alt_text'] ?? $target['row']['image_alt'] ?? ($slot === 'cover_image' ? ($parent['cover_alt'] ?? '') : ($placementAlt['alt_text'] ?? $altAsset['alt_text'] ?? '')))];
}

function image_manager_validate_mime(string $mime, bool $allowVideo): array
{
    $allowed = media_allowed_mime_types($allowVideo);
    return $allowed[$mime] ?? throw new InvalidArgumentException('Unsupported format. Use JPG, PNG, WebP, AVIF or GIF; video heroes also accept MP4, WebM or MOV. Convert HEIC photos to JPG first.');
}

/** Register a genuine HTTP upload without invoking schema-creating media helpers. */
function image_manager_upload(array $file, bool $allowVideo, ?string &$createdPath): array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new InvalidArgumentException('Upload failed or exceeded the server limit. Choose the file again.');
    }
    $tmp = (string) ($file['tmp_name'] ?? '');
    if (!is_uploaded_file($tmp)) { throw new InvalidArgumentException('Invalid uploaded file.'); }
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($tmp);
    $format = image_manager_validate_mime((string) $mime, $allowVideo);
    $bytes = filesize($tmp);
    if (!$bytes || $bytes > image_manager_limit($format['media_type'] === 'video')) {
        throw new InvalidArgumentException('File exceeds the displayed upload limit or is empty.');
    }
    if ($format['media_type'] === 'image') {
        $dimensions = @getimagesize($tmp);
        if (!$dimensions || $dimensions[0] * $dimensions[1] > 50000000) {
            throw new InvalidArgumentException('Choose a valid image no larger than 50 megapixels.');
        }
        $memoryLimit = image_manager_ini_bytes((string) ini_get('memory_limit'));
        if ($memoryLimit > 0 && memory_get_usage(true) + $dimensions[0] * $dimensions[1] * 12 + 16777216 > $memoryLimit) {
            throw new InvalidArgumentException('This image resolution exceeds the server processing limit. Resize the photo before uploading.');
        }
    }
    if (!db_table_exists('media_assets')) { throw new InvalidArgumentException('Media library schema is unavailable.'); }
    ensure_directory(UPLOAD_BASE_PATH . '/image-manager');
    $createdPath = 'image-manager/' . bin2hex(random_bytes(16)) . '.' . $format['extension'];
    $absolute = UPLOAD_BASE_PATH . '/' . $createdPath;
    if (!move_uploaded_file($tmp, $absolute)) { throw new RuntimeException('Could not store upload.'); }
    if ($format['media_type'] === 'image') {
        optimize_uploaded_image_file($absolute, $format['extension']);
        generate_image_variants_for_uploaded_file($absolute, $format['extension']);
    }
    execute_query('INSERT INTO media_assets (file_path, file_name, mime_type, file_size, uploaded_by)
        VALUES (:path, :name, :mime, :size, :admin)', ['path' => $createdPath,
        'name' => mb_substr(basename((string) ($file['name'] ?? 'Upload')), 0, 255),
        'mime' => $mime, 'size' => filesize($absolute), 'admin' => admin_id()]);
    return ['path' => $createdPath, 'asset_id' => (int) db()->lastInsertId(), 'media_type' => $format['media_type']];
}

function image_manager_library_asset(int $id, bool $allowVideo): array
{
    if (!db_table_exists('media_assets')) { throw new InvalidArgumentException('Media library is unavailable.'); }
    $asset = fetch_one('SELECT * FROM media_assets WHERE id = :id', ['id' => $id])
        ?? throw new InvalidArgumentException('Selected media no longer exists.');
    $format = image_manager_validate_mime((string) $asset['mime_type'], $allowVideo);
    $path = (string) $asset['file_path'];
    $absolute = uploaded_media_filesystem_path($path);
    // Legacy static assets are selectable, but remote paths are not fetched by this portal.
    if ($absolute === null && str_starts_with($path, 'assets/')) {
        $root = realpath(__DIR__ . '/../assets');
        $candidate = realpath(__DIR__ . '/../' . $path);
        if ($root && $candidate && str_starts_with($candidate, $root . DIRECTORY_SEPARATOR)) { $absolute = $candidate; }
    }
    if (!$absolute || !is_file($absolute)) { throw new InvalidArgumentException('Selected media file is missing or not stored locally.'); }
    $actual = (new finfo(FILEINFO_MIME_TYPE))->file($absolute);
    $actualFormat = image_manager_validate_mime((string) $actual, $allowVideo);
    if ($actualFormat['media_type'] !== $format['media_type']) { throw new InvalidArgumentException('Selected file does not match its media type.'); }
    return ['path' => $path, 'asset_id' => $id, 'media_type' => $actualFormat['media_type']];
}

/** One parent lock serializes portal saves; conditional writes also protect against the full admin. */
function image_manager_save(array $input, ?array $file = null): array
{
    $type = (string) ($input['type'] ?? '');
    $id = (int) ($input['id'] ?? 0);
    $slot = (string) ($input['slot'] ?? '');
    $child = (int) ($input['child_id'] ?? 0);
    $source = (string) ($input['source'] ?? '');
    if ($slot === 'gallery_add' && $source !== 'library') {
        throw new InvalidArgumentException('Upload photos to the destination Media Library first, then choose which ones to add to the public gallery.');
    }
    $hasFile = $file !== null && ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
    $assetId = (int) ($input['asset_id'] ?? 0);
    $captionOnly = $source === 'caption' && !$hasFile && $assetId === 0 && in_array($slot, ['day','cover_image', 'coverage', 'program_day', 'program_hotel', 'day_tour_gallery', 'gallery','hero_image','og_image','hero_video_poster'], true);
    if (!$captionOnly && !(($source === 'upload' && $hasFile && $assetId === 0) || ($source === 'library' && !$hasFile && $assetId > 0))) {
        throw new InvalidArgumentException('Choose exactly one upload or library image.');
    }
    $connection = db();
    // Older installations must not silently lose rollback guarantees.
    if ($connection->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql') {
        $parentTable = image_manager_type($type)['table'];
        $childTable = match ($slot) { 'day' => 'itinerary_days', 'gallery', 'gallery_add' => 'destination_photos', 'day_tour_gallery' => 'day_tour_gallery', default => $parentTable };
        foreach (array_unique([$parentTable, $childTable, 'media_assets']) as $table) {
            $metadata = fetch_one('SHOW TABLE STATUS WHERE Name = :name', ['name' => $table]);
            if (strtolower((string) ($metadata['Engine'] ?? '')) !== 'innodb') {
                throw new InvalidArgumentException('Image saving requires the existing transactional media and content tables. This portal does not install or alter them.');
            }
        }
    }
    $nested = $connection->inTransaction();
    $nested ? $connection->exec('SAVEPOINT image_manager_save') : $connection->beginTransaction();
    $createdPath = null;
    try {
        $parent = image_manager_record($type, $id, true);
        if (in_array($slot, ['gallery', 'gallery_add'], true)) {
            image_manager_gallery_require();
            image_manager_gallery_lock($id);
        }
        $target = image_manager_slot($type, $parent, $slot, $child);
        if (!hash_equals($target['revision'], (string) ($input['revision'] ?? ''))) {
            throw new DomainException('This image changed in another editor. Review the latest image and select your replacement again.');
        }
        require_once __DIR__.'/media_alt.php';
        $caption = trim((string) ($input['caption'] ?? ''));
        if($caption!=='') { $caption=media_alt_validate($caption, in_array($slot, ['gallery', 'gallery_add'], true) ? 300 : 255); }
        if (in_array($slot, ['gallery', 'gallery_add'], true) && ($caption === '' || mb_strlen($caption) > 300)) {
            throw new InvalidArgumentException('Gallery caption / alt text is required and must be 300 characters or fewer.');
        }
        if ($slot === 'day_tour_gallery' && ($caption === '' || mb_strlen($caption) > 255)) { throw new InvalidArgumentException('Day tour gallery alt text is required, maximum 255 characters.'); }
        if ((in_array($slot, ['coverage','program_day','program_hotel'], true) || ($slot === 'cover_image' && array_key_exists('cover_alt', $parent))) && ($caption === '' || mb_strlen($caption) > 255 || strip_tags($caption) !== $caption || str_contains($caption, "\0"))) { throw new InvalidArgumentException('Image alt text is required, maximum 255 plain text characters.'); }
        if ($captionOnly && $target['path'] === '') { throw new InvalidArgumentException('Select an image before saving its description.'); }
        $media = $captionOnly ? ['path' => $target['path'], 'asset_id' => $parent['cover_media_asset_id'] ?? 0] : ($source === 'upload' ? image_manager_upload($file, $target['video'], $createdPath)
            : image_manager_library_asset($assetId, $target['video']));
        $altAsset=$captionOnly?media_alt_target_asset($parent,$target,$slot):fetch_one('SELECT id,file_path,alt_text,mime_type FROM media_assets WHERE id=:id',['id'=>(int)$media['asset_id']]);
        $altAssetId=(int)($altAsset['id']??0);
        // Legacy destination captions may be longer than the reusable library alt.
        if (mb_strlen($caption) <= 255 || ($input['update_asset_alt'] ?? '') === '1') {
            if($caption!=='' && !$altAssetId) { $altAssetId=media_alt_ensure_asset((string)$media['path']); }
            media_alt_assign($altAssetId,$caption,$input);
        }
        if (!$captionOnly && in_array($slot, ['gallery', 'gallery_add'], true)) {
            $asset = fetch_one('SELECT destination_id FROM media_assets WHERE id = :id' . ($connection->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql' ? ' FOR UPDATE' : ''), ['id' => $media['asset_id']]);
            if (!empty($asset['destination_id']) && (int) $asset['destination_id'] !== $id) {
                throw new InvalidArgumentException('Choose an image assigned to this destination or an unassigned image.');
            }
            execute_query('UPDATE media_assets SET destination_id = :destination WHERE id = :id', ['destination' => $id, 'id' => $media['asset_id']]);
            if ($source === 'upload' && mb_strlen($caption) <= 255 && db_column_exists('media_assets', 'alt_text')) {
                execute_query('UPDATE media_assets SET alt_text = :caption WHERE id = :id', ['caption' => $caption, 'id' => $media['asset_id']]);
            }
        }
        if ($slot === 'gallery_add') {
            $order = fetch_one('SELECT COALESCE(MAX(sort_order), -1) + 1 AS next_order FROM destination_photos WHERE destination_id = :id', ['id' => $id]);
            execute_query('INSERT INTO destination_photos (destination_id, image_path, caption, sort_order) VALUES (:id, :path, :caption, :sort)',
                ['id' => $id, 'path' => $media['path'], 'caption' => $caption, 'sort' => (int) $order['next_order']]);
            $child = (int) $connection->lastInsertId();
        } else {
            $updates = [$target['field'] => $media['path']];
            if (in_array($slot, ['program_day','program_hotel'], true)) {
                $program = tour_program_decode($parent['program_json']); $group = $slot === 'program_day' ? 'days' : 'hotels';
                if (!$captionOnly) { $program[$group][$child - 1]['image_asset_id'] = $media['asset_id']; }
                $program[$group][$child - 1]['image_alt'] = $caption;
                $updates = ['program_json'=>json_encode($program, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)];
            } elseif ($slot === 'coverage') {
                $cards = tour_coverage_decode($parent['coverage_json']);
                if (!$captionOnly) { $cards[$child - 1]['image_asset_id'] = $media['asset_id']; }
                $cards[$child - 1]['image_alt'] = $caption;
                $updates = ['coverage_json' => json_encode($cards, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)];
            }
            if ($slot === 'cover_image' && array_key_exists('cover_alt', $parent)) {
                $updates['cover_alt'] = $caption;
                if (!$captionOnly) { $updates['cover_media_asset_id'] = $media['asset_id']; }
            }
            if ($slot === 'gallery') { $updates['caption'] = $caption; }
            if ($slot === 'day_tour_gallery') { $updates['alt_text'] = $caption; }
            if (!$captionOnly && $slot === 'hero_image' && in_array($type, ['destination', 'itinerary'], true)) {
                foreach (['hero_media_type' => $media['media_type'], 'hero_media_asset_id' => $media['asset_id'], 'hero_video_poster' => null] as $key => $value) {
                    if (db_column_exists($target['table'], $key)) { $updates[$key] = $value; }
                }
            }
            $sets = []; $conditions = []; $params = [];
            foreach ($updates as $key => $value) { $sets[] = "{$key} = :set_{$key}"; $params['set_' . $key] = $value; }
            foreach ($target['where'] as $key => $value) { $conditions[] = "{$key} = :where_{$key}"; $params['where_' . $key] = $value; }
            $expected = [$target['field'] => $target['row'][$target['field']] ?? null];
            if ($slot === 'cover_image') {
                foreach (['cover_alt', 'cover_media_asset_id'] as $key) { if (array_key_exists($key, $parent)) { $expected[$key] = $parent[$key]; } }
            }
            if ($slot === 'gallery') { $expected['caption'] = $target['row']['caption']; }
            if ($slot === 'day_tour_gallery') { $expected['alt_text'] = $target['row']['alt_text'] ?? null; }
            if (in_array($slot, ['hero_image', 'hero_video_poster'], true)) {
                foreach (['hero_image', 'hero_media_type', 'hero_media_asset_id', 'hero_video_poster'] as $key) {
                    if (db_column_exists($target['table'], $key)) { $expected[$key] = $parent[$key] ?? null; }
                }
            }
            foreach ($expected as $key => $value) {
                if ($value === null) { $conditions[] = "{$key} IS NULL"; }
                else { $conditions[] = "{$key} = :old_{$key}"; $params['old_' . $key] = $value; }
            }
            $statement = $connection->prepare('UPDATE ' . $target['table'] . ' SET ' . implode(', ', $sets) . ' WHERE ' . implode(' AND ', $conditions));
            $statement->execute($params);
            // MySQL reports zero for unchanged values as well as conflicts.
            if ($statement->rowCount() === 0) {
                $latest = image_manager_record($type, $id);
                $now = image_manager_slot($type, $latest, $slot, $child);
                if ($now['revision'] !== $target['revision']) { throw new DomainException('This image changed in another editor. Review it and try again.'); }
            }
        }
        media_alt_save_placement($type,$id,$slot,$child,(string)$media['path'],$caption);
        $parent = image_manager_record($type, $id);
        $result = image_manager_present($type, $parent, $slot === 'gallery_add' ? 'gallery' : $slot, $child);
        $result['child_id'] = $child;
        $result['add_revision'] = $type === 'destination' && db_table_exists('destination_photos') && db_column_exists('destination_photos', 'image_path')
            ? image_manager_slot($type, $parent, 'gallery_add')['revision'] : null;
        if ($slot === 'hero_image' && in_array($type, ['destination', 'itinerary'], true) && db_column_exists(image_manager_type($type)['table'], 'hero_video_poster')) {
            $result['poster'] = ($parent['hero_media_type'] ?? '') === 'video' ? image_manager_present($type, $parent, 'hero_video_poster') : null;
        }
        if ($slot === 'hero_video_poster') { $result['hero'] = image_manager_present($type, $parent, 'hero_image'); }
        $nested ? $connection->exec('RELEASE SAVEPOINT image_manager_save') : $connection->commit();
        return $result;
    } catch (Throwable $error) {
        if ($connection->inTransaction()) {
            $nested ? $connection->exec('ROLLBACK TO SAVEPOINT image_manager_save') : $connection->rollBack();
        }
        if ($createdPath !== null) { delete_uploaded_media($createdPath); }
        throw $error;
    }
}

/** Gallery schema is optional; reads never install or alter it. */
function image_manager_gallery_available(): bool
{
    foreach (['destination_photos' => ['id', 'destination_id', 'image_path', 'caption', 'sort_order'],
        'media_assets' => ['id', 'destination_id']] as $table => $columns) {
        if (!db_table_exists($table)) { return false; }
        foreach ($columns as $column) { if (!db_column_exists($table, $column)) { return false; } }
    }
    return true;
}

function image_manager_gallery_require(): void
{
    if (!image_manager_gallery_available()) { throw new InvalidArgumentException('Destination gallery management is unavailable until the existing gallery and Media Library destination schema is installed.'); }
}

function image_manager_gallery_lock(int $id): void
{
    if (db()->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql') {
        fetch_all('SELECT id FROM destination_photos WHERE destination_id = :id ORDER BY sort_order, id FOR UPDATE', ['id' => $id]);
    }
}

function image_manager_gallery_revision(int $id): string
{
    return image_manager_slot('destination', image_manager_record('destination', $id), 'gallery_add')['revision'];
}

/** These actions change gallery metadata only and never delete a file or asset. */
function image_manager_gallery_manage(array $input): void
{
    image_manager_gallery_require();
    $action = (string) ($input['action'] ?? '');
    if (!in_array($action, ['gallery_caption', 'gallery_remove', 'gallery_order'], true)) { throw new InvalidArgumentException('Unknown gallery action.'); }
    $id = (int) ($input['id'] ?? 0);
    $connection = db();
    if ($connection->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql') {
        foreach (['destinations', 'destination_photos'] as $table) {
            $metadata = fetch_one('SHOW TABLE STATUS WHERE Name = :name', ['name' => $table]);
            if (strtolower((string) ($metadata['Engine'] ?? '')) !== 'innodb') { throw new InvalidArgumentException('Gallery changes require transactional content tables.'); }
        }
    }
    $nested = $connection->inTransaction();
    $nested ? $connection->exec('SAVEPOINT image_manager_gallery') : $connection->beginTransaction();
    try {
        image_manager_record('destination', $id, true);
        image_manager_gallery_lock($id);
        if (!hash_equals(image_manager_gallery_revision($id), (string) ($input['revision'] ?? ''))) {
            throw new DomainException('The gallery changed in another editor. Review the refreshed gallery before saving again.');
        }
        if ($action === 'gallery_order') {
            try { $order = json_decode((string) ($input['order'] ?? ''), true, 512, JSON_THROW_ON_ERROR); }
            catch (JsonException) { throw new InvalidArgumentException('Invalid gallery order.'); }
            $ids = array_column(image_manager_children('destination', $id), 'id');
            $ids = array_map('intval', $ids); sort($ids);
            if (!is_array($order) || !array_is_list($order) || count(array_filter($order, 'is_int')) !== count($order)) { throw new InvalidArgumentException('Invalid gallery order.'); }
            $sorted = $order; sort($sorted);
            if ($sorted !== $ids) { throw new InvalidArgumentException('Submit every photo from this destination exactly once.'); }
            foreach ($order as $position => $photo) { execute_query('UPDATE destination_photos SET sort_order=:position WHERE id=:photo AND destination_id=:id', ['position' => $position, 'photo' => $photo, 'id' => $id]); }
        } else {
            $child = (int) ($input['child_id'] ?? 0);
            image_manager_slot('destination', image_manager_record('destination', $id), 'gallery', $child);
            if ($action === 'gallery_remove') {
                execute_query('DELETE FROM destination_photos WHERE id=:child AND destination_id=:id', ['child' => $child, 'id' => $id]);
            } else {
                require_once __DIR__.'/media_alt.php';
                $caption = media_alt_validate(trim((string) ($input['caption'] ?? '')), 300);
                if ($caption === '' || mb_strlen($caption) > 300) { throw new InvalidArgumentException('Caption / alt text is required, maximum 300 characters.'); }
                if (mb_strlen($caption) <= 255 || ($input['update_asset_alt'] ?? '') === '1') {
                    $photo=fetch_one('SELECT image_path FROM destination_photos WHERE id=:id AND destination_id=:parent',['id'=>$child,'parent'=>$id]);
                    $asset=media_alt_asset((string)($photo['image_path']??''));
                    media_alt_assign((int)($asset['id']??0),$caption,$input);
                }
                execute_query('UPDATE destination_photos SET caption=:caption WHERE id=:child AND destination_id=:id', ['caption' => $caption, 'child' => $child, 'id' => $id]);
            }
        }
        $nested ? $connection->exec('RELEASE SAVEPOINT image_manager_gallery') : $connection->commit();
    } catch (Throwable $error) {
        if ($connection->inTransaction()) { $nested ? $connection->exec('ROLLBACK TO SAVEPOINT image_manager_gallery') : $connection->rollBack(); }
        throw $error;
    }
}

/** Destination uploads are library-only. Public gallery selection is a separate action. */
function image_manager_destination_library_available(): bool
{
    return db_table_exists('destinations') && db_table_exists('media_assets') && db_column_exists('media_assets', 'destination_id');
}

function image_manager_destination_upload(array $input, ?array $file): array
{
    if (!image_manager_destination_library_available()) {
        throw new InvalidArgumentException('Destination categories are unavailable in this Media Library schema.');
    }
    $id = (int) ($input['id'] ?? 0);
    $caption = trim((string) ($input['caption'] ?? ''));
    $requestId = (string) ($input['request_id'] ?? '');
    if (!preg_match('/\A[a-f0-9]{32}\z/', $requestId)) { throw new InvalidArgumentException('Invalid upload request identifier.'); }
    if ($caption === '' || mb_strlen($caption) > 300) { throw new InvalidArgumentException('Caption / alt text is required, maximum 300 characters.'); }
    if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file((string) ($file['tmp_name'] ?? ''))) {
        throw new InvalidArgumentException('Choose one image within the displayed upload limit.');
    }
    image_manager_record('destination', $id);
    $fingerprint = hash('sha256', $id . ':' . $caption . ':' . hash_file('sha256', $file['tmp_name']));
    $previous = $_SESSION['_ict_destination_uploads'][$requestId] ?? null;
    if ($previous) {
        if (!hash_equals($previous['fingerprint'], $fingerprint)) { throw new InvalidArgumentException('This request identifier was already used for another photo.'); }
        return image_manager_library_asset((int) $previous['asset_id'], false);
    }
    $connection = db();
    if ($connection->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql') {
        foreach (['destinations', 'media_assets'] as $table) {
            $metadata = fetch_one('SHOW TABLE STATUS WHERE Name = :name', ['name' => $table]);
            if (strtolower((string) ($metadata['Engine'] ?? '')) !== 'innodb') { throw new InvalidArgumentException('Uploads require transactional content and media tables.'); }
        }
    }
    $connection->beginTransaction(); $created = null;
    try {
        image_manager_record('destination', $id, true);
        $media = image_manager_upload($file, false, $created);
        execute_query('UPDATE media_assets SET destination_id=:destination WHERE id=:id', ['destination' => $id, 'id' => $media['asset_id']]);
        if (db_column_exists('media_assets', 'alt_text')) { execute_query('UPDATE media_assets SET alt_text=:caption WHERE id=:id', ['caption' => $caption, 'id' => $media['asset_id']]); }
        $connection->commit();
    } catch (Throwable $error) {
        if ($connection->inTransaction()) { $connection->rollBack(); }
        if ($created !== null) { delete_uploaded_media($created); }
        throw $error;
    }
    $_SESSION['_ict_destination_uploads'][$requestId] = ['fingerprint' => $fingerprint, 'asset_id' => $media['asset_id']];
    while (count($_SESSION['_ict_destination_uploads']) > 100) { array_shift($_SESSION['_ict_destination_uploads']); }
    return $media;
}
