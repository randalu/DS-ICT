<?php
declare(strict_types=1);

/** Separate card photography from a package's detail hero and social image. */
function package_tables(): array
{
    return ['itinerary' => 'itineraries', 'tour' => 'tours', 'day_tour' => 'day_tours'];
}

function package_card_record(array $record, string $type = ''): array
{
    $table = package_tables()[$type] ?? (in_array($type, package_tables(), true) ? $type : '');
    if ($table === '' || array_key_exists('cover_image', $record) || !db_table_exists($table) || !db_column_exists($table, 'cover_image')) {
        return $record;
    }
    // One small public-image projection per collection per request also supports older query callers.
    static $cache = [];
    if (!isset($cache[$table])) {
        $cache[$table] = ['id' => [], 'slug' => []];
        $published = db_column_exists($table, 'is_published') ? ' WHERE is_published = 1' : '';
        foreach (fetch_all('SELECT id, slug, hero_image, og_image, ' . package_card_select_fragment($table) . ' FROM ' . $table . $published) as $row) {
            $cache[$table]['id'][(int) $row['id']] = $row;
            $cache[$table]['slug'][(string) $row['slug']] = $row;
        }
    }
    $images = $cache[$table]['slug'][(string) ($record['slug'] ?? '')] ?? $cache[$table]['id'][(int) ($record['id'] ?? 0)] ?? [];
    return array_replace($images, $record);
}

function package_card_image(array $record, string $type = ''): string
{
    $record = package_card_record($record, $type);
    $path = content_image_path($record, ['cover_image', 'hero_image', 'og_image']);
    return $path !== '' ? stored_media_url($path) : '';
}

/** Images already assigned to a public package, including its independent card photograph. */
function package_sitemap_images(array $record, string $type): array
{
    if (!isset(package_tables()[$type])) { throw new InvalidArgumentException('Unsupported package.'); }
    $record = package_card_record($record, $type); $images = [];
    foreach (['cover_image', 'hero_image', 'og_image'] as $field) {
        $path = content_image_path($record, [$field]);
        if ($path !== '') { $images[] = stored_media_url($path); }
    }
    if ($type === 'tour' && db_column_exists('tours', 'coverage_json')) {
        require_once __DIR__ . '/tour_coverage.php';
        $json = $record['coverage_json'] ?? null;
        if (!array_key_exists('coverage_json', $record) && !empty($record['slug'])) {
            $json = fetch_one('SELECT coverage_json FROM tours WHERE slug=:slug', ['slug' => $record['slug']])['coverage_json'] ?? null;
        }
        foreach (tour_coverage_public_items($json) as $card) { if ($card['image_url'] !== '') { $images[] = $card['image_url']; } }
    }
    if ($type === 'tour' && db_column_exists('tours','program_json')) {
        require_once __DIR__ . '/tour_program.php';
        $json = $record['program_json'] ?? null;
        if (!array_key_exists('program_json',$record) && !empty($record['slug'])) { $json = fetch_one('SELECT program_json FROM tours WHERE slug=:slug',['slug'=>$record['slug']])['program_json'] ?? null; }
        $program = tour_program_decode($json);
        foreach (array_merge($program['days'],$program['hotels']) as $row) { $photo = tour_program_image($row); if ($photo!=='') { $images[]=$photo; } }
    }
    return array_values(array_unique(array_filter($images, static fn(string $url): bool => !is_placeholder_media_url($url))));
}

function package_edit_revision(array $record): string
{
    return hash_hmac('sha256', json_encode($record, JSON_THROW_ON_ERROR), csrf_token());
}

function package_traveller_types(array $input): ?string
{
    if (($input['traveller_types_present'] ?? '') !== '1') { return null; }
    $selected = $input['traveller_types_raw'] ?? [];
    if (!is_array($selected) || count($selected) > 30) { throw new InvalidArgumentException('Invalid traveller types.'); }
    $types = [];
    foreach ($selected as $value) {
        if (!is_string($value) || trim($value) === '' || strip_tags($value) !== $value || str_contains($value, ',') || str_contains($value, "\0")) { throw new InvalidArgumentException('Traveller types must contain plain text.'); }
        $types[] = trim($value);
    }
    $csv = implode(',', array_unique($types));
    if (mb_strlen($csv) > 120) { throw new InvalidArgumentException('Traveller types must be 120 characters or fewer.'); }
    return $csv;
}

/** Preserve metadata and IDs on unchanged relationships during ordinary editor saves. */
function package_sync_links(string $table, string $owner, string $foreign, int $id, array $selected): void
{
    $allowed = ['itinerary_destination' => ['itinerary_id','destination_id'], 'destination_itinerary' => ['itinerary_id','destination_id'], 'destination_tour' => ['tour_id','destination_id'], 'itinerary_tour' => ['tour_id','itinerary_id']];
    if (($allowed[$table] ?? []) !== [$owner, $foreign]) { throw new InvalidArgumentException('Invalid package relationship.'); }
    if (!db_table_exists($table) || !db_column_exists($table, $owner) || !db_column_exists($table, $foreign)) { return; }
    $existing = array_map('intval', array_column(fetch_all("SELECT $foreign FROM $table WHERE $owner=:id", ['id' => $id]), $foreign));
    foreach (array_diff($existing, $selected) as $related) { execute_query("DELETE FROM $table WHERE $owner=:id AND $foreign=:related", ['id' => $id, 'related' => $related]); }
    foreach (array_diff($selected, $existing) as $related) { execute_query("INSERT INTO $table($owner,$foreign) VALUES(:id,:related)", ['id' => $id, 'related' => $related]); }
}

function package_season_json(string $previous, array $input, string $field, string $months, string $note): ?string
{
    if (($input[$field . '_override'] ?? '') === '1') {
        $value = $input[$field . '_raw'] ?? '';
        if (!is_string($value) || mb_strlen($value) > 30000) { throw new InvalidArgumentException('Season details are too long.'); }
        if (trim($value) === '') { return null; }
        try { $decoded = json_decode($value, true, 32, JSON_THROW_ON_ERROR); }
        catch (JsonException) { throw new InvalidArgumentException('Season details must be valid JSON.'); }
        if (!is_array($decoded)) { throw new InvalidArgumentException('Season details must be a JSON object or list.'); }
        return $value;
    }
    if (!array_key_exists($note, $input)) { return $previous !== '' ? $previous : null; }
    $decoded = json_decode($previous, true);
    if (!is_array($decoded)) { if ($previous !== '') { return $previous; } $decoded = []; }
    // Rich per-month ratings are edited explicitly in the structured field below the selector.
    if (array_is_list($decoded) && $decoded !== []) { return $previous; }
    if (isset($decoded['months']) && !is_array($decoded['months'])) { return $previous; }
    foreach ($decoded['months'] ?? [] as $month) { if (!is_string($month)) { return $previous; } }
    $selected = $input[$months] ?? [];
    if (!is_array($selected)) { throw new InvalidArgumentException('Invalid month selection.'); }
    foreach ($selected as $month) { if (!is_string($month) || !in_array($month, ['january','february','march','april','may','june','july','august','september','october','november','december'], true)) { throw new InvalidArgumentException('Invalid month selection.'); } }
    $decoded['months'] = array_values(array_unique($selected));
    if (!is_string($input[$note]) || mb_strlen($input[$note]) > 6000) { throw new InvalidArgumentException('Season note is too long.'); }
    $decoded['note'] = $input[$note];
    return json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
}

function package_card_alt(array $record, string $type = ''): string
{
    $record = package_card_record($record, $type);
    $name = trim((string) ($record['name'] ?? $record['title'] ?? 'Private Sri Lanka journey'));
    return !empty($record['cover_image']) && !media_path_is_video((string) $record['cover_image'])
        ? (trim((string) ($record['cover_alt'] ?? '')) ?: $name) : $name;
}

function package_card_select_fragment(string $table, string $alias = ''): string
{
    if (!in_array($table, package_tables(), true) || ($alias !== '' && !preg_match('/\A[a-z_]+\z/', $alias))) {
        throw new InvalidArgumentException('Invalid package projection.');
    }
    $prefix = $alias !== '' ? $alias . '.' : '';
    $parts = [];
    foreach (['cover_image', 'cover_alt'] as $field) {
        $parts[] = (db_column_exists($table, $field) ? $prefix . $field : 'NULL') . ' AS ' . $field;
    }
    return implode(', ', $parts) . ', ' . content_image_select_fragment($table, $alias);
}

/** Only explicitly submitted cover fields are changed. Never replace or backfill a hero. */
function package_cover_updates(string $type, array $record, array $input, array $files, ?string &$createdPath): array
{
    $table = package_tables()[$type] ?? throw new InvalidArgumentException('Unsupported package type.');
    if (($input['cover_present'] ?? '') !== '1') { return []; }
    if (!db_column_exists($table, 'cover_image') || !db_column_exists($table, 'cover_alt') || !db_column_exists($table, 'cover_media_asset_id')) {
        throw new InvalidArgumentException('Apply the package media migration before editing a cover.');
    }
    $alt = $input['cover_alt'] ?? '';
    if (!is_string($alt) || mb_strlen($alt) > 255 || $alt !== strip_tags($alt) || str_contains($alt, "\0")) {
        throw new InvalidArgumentException('Cover alt text must be plain text, up to 255 characters.');
    }
    $assetId = $input['cover_image_media_asset_id'] ?? '';
    if (!is_string($assetId) || ($assetId !== '' && !ctype_digit($assetId))) { throw new InvalidArgumentException('Invalid cover image selection.'); }
    $file = $files['cover_image'] ?? null;
    $hasFile = is_array($file) && ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
    $clear = ($input['cover_clear'] ?? '') === '1';
    if ((int) $hasFile + (int) ((int) $assetId > 0) + (int) $clear > 1) {
        throw new InvalidArgumentException('Choose one cover source, or clear the cover.');
    }
    $updates = ['cover_alt' => trim($alt)];
    if ($clear) { return ['cover_image' => null, 'cover_media_asset_id' => null, 'cover_alt' => null]; }
    if (!$hasFile && (int) $assetId === 0 && !empty($record['cover_image']) && trim($alt) === '') { throw new InvalidArgumentException('Describe the assigned cover image, or explicitly clear the cover.'); }
    if ($hasFile || (int) $assetId > 0) {
        if (trim($alt) === '') { throw new InvalidArgumentException('Describe the new cover image in the alt text field.'); }
        require_once __DIR__ . '/image_manager.php';
        $media = $hasFile ? image_manager_upload($file, false, $createdPath) : image_manager_library_asset((int) $assetId, false);
        if ($hasFile && db_column_exists('media_assets', 'alt_text')) { execute_query('UPDATE media_assets SET alt_text=:alt WHERE id=:id', ['alt' => trim($alt), 'id' => $media['asset_id']]); }
        $updates += ['cover_image' => $media['path'], 'cover_media_asset_id' => $media['asset_id']];
    }
    return $updates;
}
