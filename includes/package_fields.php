<?php
declare(strict_types=1);

require_once __DIR__ . '/functions.php';
require_once __DIR__.'/page_media.php';
require_once __DIR__ . '/tour_coverage.php';
require_once __DIR__ . '/tour_program.php';

/** Complete public-package context, including children and relationship metadata. No customer data. */
function package_field_inventory(string $type, int $id, bool $lock = false): array
{
    $table = package_tables()[$type] ?? throw new InvalidArgumentException('Unsupported package.');
    $suffix = $lock && db()->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql' ? ' FOR UPDATE' : '';
    $record = fetch_one("SELECT * FROM $table WHERE id=:id" . $suffix, ['id' => $id])
        ?? throw new InvalidArgumentException('Package not found.');
    $children = [];
    $childTable = match ($type) { 'itinerary' => 'itinerary_days', 'day_tour' => 'day_tour_gallery', default => '' };
    if ($childTable !== '' && db_table_exists($childTable)) {
        $owner = $type . '_id';
        $children[$childTable] = fetch_all("SELECT * FROM $childTable WHERE $owner=:id ORDER BY id" . $suffix, ['id' => $id]);
    }
    if ($type === 'tour') { $children['coverage'] = tour_coverage_decode($record['coverage_json'] ?? null); $program = tour_program_decode($record['program_json'] ?? null); foreach (['days','hotels','sections'] as $group) { $children['programme_' . $group] = $program[$group]; } }
    $relations = [];
    $pivots = match ($type) {
        'itinerary' => ['itinerary_destination', 'destination_itinerary', 'experience_itinerary', 'itinerary_tour', 'country_market_itinerary_links'],
        'tour' => ['destination_tour', 'experience_tour'],
        default => ['day_tour_destination', 'day_tour_itinerary', 'day_tour_experience'],
    };
    foreach ($pivots as $pivot) {
        $owner = $type . '_id';
        if (db_table_exists($pivot) && db_column_exists($pivot, $owner)) {
            $relations[$pivot] = fetch_all("SELECT * FROM $pivot WHERE $owner=:id", ['id' => $id]);
        }
    }
    return ['record' => $record, 'children' => $children, 'relationships' => $relations];
}

function package_editor_links(string $type, int $id): array
{
    $form = match ($type) { 'itinerary' => 'itinerary_form.php', 'tour' => 'tour_form.php', 'day_tour' => 'day_tour_form.php', default => throw new InvalidArgumentException('Unsupported package.') };
    $links = ['Full editor' => base_url('admin/' . $form . '?id=' . $id),
        'Images' => base_url('admin/image_manager.php?type=' . $type . '&id=' . $id),
        'SEO and wording' => base_url('admin/seo_manager.php?type=' . $type . '&id=' . $id),
        'All fields' => base_url('admin/package_fields.php?type=' . $type . '&id=' . $id),
        'Site Audit' => base_url('admin/site_audit.php')];
    if ($type === 'itinerary') { $links['Day plan'] = base_url('admin/itinerary_days.php?itinerary_id=' . $id); }
    if ($type === 'day_tour') { $links['Gallery'] = base_url('admin/day_tour_gallery.php?id=' . $id); }
    if ($type === 'tour') { $links['Route, days and stays'] = base_url('admin/tour_program.php?id=' . $id); }
    return $links;
}

function package_seo_attach(string $type, array $record, bool $lock = false): array
{
    if (!isset(package_tables()[$type])) { return $record; }
    if ($type === 'tour') {
        foreach (tour_coverage_decode($record['coverage_json'] ?? null) as $index => $card) {
            foreach (['title', 'description', 'image_alt'] as $key) { $record['coverage_' . $index . '_' . $key] = (string) ($card[$key] ?? ''); }
        }
    }
    if ($type === 'itinerary' && db_table_exists('itinerary_days')) {
        $suffix = $lock && db()->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql' ? ' FOR UPDATE' : '';
        $record['_days'] = fetch_all('SELECT * FROM itinerary_days WHERE itinerary_id=:id ORDER BY id' . $suffix, ['id' => $record['id']]);
        foreach ($record['_days'] as $day) {
            foreach (['title', 'summary', 'highlights_json'] as $key) {
                if (array_key_exists($key, $day)) { $record['day_' . $day['id'] . '_' . $key] = (string) ($day[$key] ?? ''); }
            }
        }
    }
    if ($type === 'day_tour' && db_table_exists('day_tour_gallery')) {
        $suffix = $lock && db()->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql' ? ' FOR UPDATE' : '';
        $record['_gallery'] = fetch_all('SELECT * FROM day_tour_gallery WHERE day_tour_id=:id ORDER BY id' . $suffix, ['id' => $record['id']]);
        foreach ($record['_gallery'] as $photo) { $record['gallery_' . $photo['id'] . '_alt_text'] = (string) ($photo['alt_text'] ?? ''); }
    }
    return $record;
}

function package_seo_definition(string $field): ?array
{
    if(preg_match('/\Apage_gallery_\d+_alt_text\z/',$field))return ['label'=>'Gallery photo description','limit'=>255,'format'=>'text','ai'=>false];
    if (preg_match('/\Acoverage_\d+_(title|description|image_alt)\z/', $field, $m)) {
        return ['label' => ucwords(str_replace('_', ' ', $field)), 'limit' => ['title' => 180, 'description' => 6000, 'image_alt' => 255][$m[1]], 'format' => 'text', 'ai' => $m[1] !== 'image_alt'];
    }
    if (preg_match('/\Agallery_\d+_alt_text\z/', $field)) { return ['label' => ucwords(str_replace('_', ' ', $field)), 'limit' => 255, 'format' => 'text', 'ai' => false]; }
    if (preg_match('/\Aday_\d+_(title|summary|highlights_json)\z/', $field, $m)) {
        return ['label' => ucwords(str_replace('_', ' ', $field)), 'limit' => $m[1] === 'title' ? 255 : 30000, 'format' => $m[1] === 'summary' ? 'html' : ($m[1] === 'highlights_json' ? 'list' : 'text')];
    }
    return null;
}

/** History may restore words only while the same card/day structure still exists. */
function package_seo_identity(array $record): string
{
    $cards = tour_coverage_decode($record['coverage_json'] ?? null);
    foreach ($cards as &$card) { unset($card['title'], $card['description'], $card['image_alt']); } unset($card);
    $days = $record['_days'] ?? [];
    foreach ($days as &$day) { unset($day['title'], $day['summary'], $day['highlights_json'], $day['updated_at'], $day['created_at']); } unset($day);
    $photos = $record['_gallery'] ?? [];
    foreach ($photos as &$photo) { unset($photo['alt_text'], $photo['updated_at'], $photo['created_at']); } unset($photo);
    $guides = $record['_guides'] ?? [];
    foreach ($guides as &$guide) { unset($guide['informational_label'], $guide['package_label'], $guide['updated_at'], $guide['created_at']); } unset($guide);
    $pagePhotos=$record['_page_gallery']??[];foreach($pagePhotos as &$photo)unset($photo['alt_text']);unset($photo);
    return hash('sha256', json_encode([$cards, $days, $photos, $guides,$pagePhotos], JSON_THROW_ON_ERROR));
}

function package_seo_write(string $type, array $record, array $values): void
{
    $table = package_tables()[$type] ?? seo_type($type)['table'];
    $cards = tour_coverage_decode($record['coverage_json'] ?? null); $coverageChanged = false;
    foreach ($values as $field => $value) {
        if (str_starts_with($field, '_')) { continue; }
        if(preg_match('/\Apage_gallery_(\d+)_alt_text\z/',$field,$match)){
            $description=page_media_description($value);[$galleryTable,$owner,,$alt]=page_gallery_storage($type);
            $where="$owner=:owner";$params=['alt'=>$description,'photo'=>(int)$match[1],'owner'=>$record['id']];if($galleryTable==='page_gallery_images'){$where.=' AND content_type=:type';$params['type']=$type;}
            execute_query("UPDATE $galleryTable SET $alt=:alt WHERE id=:photo AND $where",$params);continue;
        }
        if (str_starts_with($field, 'guide_')) { throw new DomainException('Separate Tour reference links are retired.'); }
        if (preg_match('/\Acoverage_(\d+)_(title|description|image_alt)\z/', $field, $m)) {
            $cards[(int) $m[1]][$m[2]] = $value; $coverageChanged = true;
        } elseif (preg_match('/\Aday_(\d+)_(title|summary|highlights_json)\z/', $field, $m)) {
            execute_query('UPDATE itinerary_days SET ' . $m[2] . '=:value WHERE id=:day AND itinerary_id=:owner', ['value' => $value, 'day' => (int) $m[1], 'owner' => $record['id']]);
        } elseif (preg_match('/\Agallery_(\d+)_alt_text\z/', $field, $m)) {
            execute_query('UPDATE day_tour_gallery SET alt_text=:value WHERE id=:photo AND day_tour_id=:owner', ['value' => $value, 'photo' => (int) $m[1], 'owner' => $record['id']]);
        } else { execute_query("UPDATE $table SET $field=:value WHERE id=:id", ['value' => $value, 'id' => $record['id']]); }
    }
    if ($coverageChanged) { execute_query('UPDATE tours SET coverage_json=:value WHERE id=:id', ['value' => json_encode($cards, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR), 'id' => $record['id']]); }
}
