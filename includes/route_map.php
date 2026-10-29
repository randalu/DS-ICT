<?php
declare(strict_types=1);

require_once __DIR__ . '/functions.php';

const ROUTE_MAP_DESIGN_KEY = 'route_map_design_json';

function route_map_types(): array
{
    return [
        'itinerary' => ['table' => 'itineraries', 'label' => 'Itineraries', 'editor' => 'itinerary_days.php', 'id_key' => 'itinerary_id'],
        'tour' => ['table' => 'tours', 'label' => 'Tour Packages', 'editor' => 'tour_program.php', 'id_key' => 'id'],
        'day_tour' => ['table' => 'day_tours', 'label' => 'Day Tour Packages', 'editor' => 'day_tour_form.php', 'id_key' => 'id'],
    ];
}

function route_map_type(string $type): array
{
    return route_map_types()[$type] ?? throw new InvalidArgumentException('Choose an itinerary, tour package or day tour.');
}

function route_map_available(): bool
{
    foreach (['map_locations' => ['id', 'name', 'latitude', 'longitude', 'destination_id', 'coordinate_source', 'is_verified', 'is_active', 'revision'],
        'package_route_maps' => ['package_type', 'package_id', 'route_json', 'revision']] as $table => $columns) {
        if (!db_table_exists($table)) { return false; }
        foreach ($columns as $column) {
            if (!db_column_exists($table, $column)) { return false; }
        }
    }
    return true;
}

function route_map_text(mixed $value, int $limit, bool $required = true): string
{
    if (!is_string($value) || !mb_check_encoding($value, 'UTF-8') || mb_strlen($value) > $limit
        || $value !== strip_tags($value) || preg_match('/[\x00-\x1f\x7f]/', $value)) {
        throw new InvalidArgumentException('Use plain text within the stated field limits.');
    }
    $value = trim($value);
    if ($required && $value === '') { throw new InvalidArgumentException('Complete all required map fields.'); }
    return $value;
}

function route_map_integer(mixed $value, int $min, int $max): int
{
    if ((!is_int($value) && !is_string($value)) || !preg_match('/\A(?:0|[1-9][0-9]*)\z/', (string) $value)) {
        throw new InvalidArgumentException('Use valid whole-number map references.');
    }
    $number = filter_var($value, FILTER_VALIDATE_INT);
    if ($number === false || $number < $min || $number > $max) { throw new InvalidArgumentException('Map reference is out of range.'); }
    return $number;
}

function route_map_hash(mixed $value): string
{
    return hash('sha256', json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
}

function route_map_record(string $type, int $id, bool $lock = false): array
{
    $table = route_map_type($type)['table'];
    if ($id < 1 || !db_table_exists($table)) { throw new InvalidArgumentException('Package not found.'); }
    return fetch_one("SELECT * FROM {$table} WHERE id = :id" . ($lock ? ' FOR UPDATE' : ''), ['id' => $id])
        ?? throw new InvalidArgumentException('Package not found.');
}

/** Expand written ranges without changing the programme. Unwritten itinerary days stay visible as pending. */
function route_map_expand_days(array $rows, int $duration = 0): array
{
    if ($duration < 0 || $duration > 366 || count($rows) > 366) { throw new InvalidArgumentException('Map programmes support up to 366 days.'); }
    $days = [];
    foreach ($rows as $row) {
        if (!is_array($row)) { throw new InvalidArgumentException('Invalid saved daily plan.'); }
        $start = route_map_integer($row['start'] ?? null, 1, 366);
        $end = route_map_integer($row['end'] ?? $start, $start, 366);
        for ($day = $start; $day <= $end; $day++) {
            if (isset($days[$day])) { throw new InvalidArgumentException('Resolve overlapping programme days before mapping them.'); }
            $days[$day] = ['day' => $day, 'title' => (string) ($row['title'] ?? ''), 'written' => true];
        }
    }
    for ($day = 1; $day <= $duration; $day++) {
        $days[$day] ??= ['day' => $day, 'title' => 'Daily plan pending', 'written' => false];
    }
    ksort($days);
    return array_values($days);
}

function route_map_schedule(string $type, array $record, bool $lock = false): array
{
    route_map_type($type);
    $rows = [];
    $duration = 0;
    $routeContext = '';
    if ($type === 'itinerary') {
        $duration = (int) ($record['days'] ?? 0);
        $routeContext = (string) ($record['route_summary'] ?? '');
        if (db_table_exists('itinerary_days')) {
            $end = db_column_exists('itinerary_days', 'day_end') ? 'COALESCE(day_end, day_number)' : 'day_number';
            $source = fetch_all("SELECT id, day_number AS start, {$end} AS end, title, overnight_location FROM itinerary_days WHERE itinerary_id = :id ORDER BY day_number, id" . ($lock ? ' FOR UPDATE' : ''), ['id' => (int) $record['id']]);
            $rows = $source;
        }
    } elseif ($type === 'tour') {
        $raw = $record['program_json'] ?? null;
        $program = $raw === null || $raw === '' ? ['days' => []] : json_decode((string) $raw, true, 32, JSON_THROW_ON_ERROR);
        if (!is_array($program) || !is_array($program['days'] ?? null)) { throw new InvalidArgumentException('Repair the saved Tour daily plan before mapping it.'); }
        $routeContext = (string) ($program['route'] ?? '');
        foreach ($program['days'] as $index => $day) {
            if (!is_array($day)) { throw new InvalidArgumentException('Invalid Tour day.'); }
            $rows[] = ['start' => $day['day_start'] ?? $index + 1, 'end' => $day['day_end'] ?? $day['day_start'] ?? $index + 1,
                'title' => $day['title'] ?? '', 'location' => $day['location'] ?? '', 'overnight' => $day['overnight'] ?? ''];
        }
    } else {
        $rows = [['start' => 1, 'end' => 1, 'title' => (string) $record['name'], 'meeting_point' => $record['meeting_point'] ?? '', 'itinerary' => $record['itinerary'] ?? '']];
    }
    return ['days' => route_map_expand_days($rows, $duration), 'revision' => route_map_hash([$type, (int) $record['id'], $duration, $routeContext, $rows])];
}

function route_map_locations(bool $lock = false): array
{
    if (!route_map_available()) { return []; }
    $rows = fetch_all('SELECT * FROM map_locations ORDER BY id' . ($lock ? ' FOR UPDATE' : ''));
    $locations = [];
    foreach ($rows as $row) { $locations[(int) $row['id']] = $row; }
    return $locations;
}

function route_map_locations_revision(array $days, array $locations): string
{
    $versions = [];
    foreach ($days as $day) {
        foreach ($day['stops'] as $id) { $versions[$id] = $locations[$id]['revision'] ?? 'missing'; }
    }
    ksort($versions);
    return route_map_hash($versions);
}

function route_map_normalize_days(mixed $input, array $schedule, array $locations, bool $ready): array
{
    if (!is_array($input) || count($input) > 367) { throw new InvalidArgumentException('Invalid map days.'); }
    $expected = [0 => ['written' => true]];
    foreach ($schedule['days'] as $day) { $expected[$day['day']] = $day; }
    $out = [];
    $total = 0;
    foreach ($input as $number => $stops) {
        $day = route_map_integer($number, 0, 366);
        if (!isset($expected[$day]) || !is_array($stops) || !array_is_list($stops) || count($stops) > 8) {
            throw new InvalidArgumentException('Use existing programme days and up to eight ordered stops per day.');
        }
        $ids = [];
        foreach ($stops as $value) {
            if ($value === '') { continue; }
            $id = route_map_integer($value, 1, PHP_INT_MAX);
            if (!isset($locations[$id])) { throw new InvalidArgumentException('A selected map location no longer exists.'); }
            if ($ready && (!(int) $locations[$id]['is_verified'] || !(int) $locations[$id]['is_active'])) {
                throw new InvalidArgumentException('Verify and activate every selected location before displaying this route.');
            }
            $ids[] = $id;
        }
        $total += count($ids);
        $out[$day] = ['day' => $day, 'stops' => $ids];
    }
    if ($total > 180) { throw new InvalidArgumentException('Use at most 180 route stops.'); }
    foreach ($schedule['days'] as $day) {
        if (!isset($out[$day['day']])) { throw new InvalidArgumentException('Complete the map controls for every programme day.'); }
        if ($ready && (!$day['written'] || !$out[$day['day']]['stops'])) { throw new InvalidArgumentException('Add a written daily plan and at least one destination for every day before displaying this route.'); }
    }
    if ($ready && !$schedule['days']) { throw new InvalidArgumentException('Save the package daily plan before displaying a route.'); }
    ksort($out);
    return array_values($out);
}

function route_map_decode(string $json): array
{
    if (strlen($json) > 100000) { throw new InvalidArgumentException('Map document is too large.'); }
    $doc = json_decode($json, true, 16, JSON_THROW_ON_ERROR);
    if (!is_array($doc) || ($doc['version'] ?? null) !== 1 || !in_array($doc['status'] ?? null, ['draft', 'ready'], true)
        || !is_string($doc['source_revision'] ?? null) || !preg_match('/\A[a-f0-9]{64}\z/', $doc['source_revision'])
        || !is_string($doc['locations_revision'] ?? null) || !preg_match('/\A[a-f0-9]{64}\z/', $doc['locations_revision'])
        || !is_array($doc['days'] ?? null) || !array_is_list($doc['days']) || count($doc['days']) > 367) {
        throw new InvalidArgumentException('Invalid saved route map.');
    }
    $last = -1;
    $total = 0;
    foreach ($doc['days'] as $row) {
        if (!is_array($row) || !is_int($row['day'] ?? null) || $row['day'] <= $last || $row['day'] > 366
            || !is_array($row['stops'] ?? null) || !array_is_list($row['stops']) || count($row['stops']) > 8) {
            throw new InvalidArgumentException('Invalid saved map order.');
        }
        foreach ($row['stops'] as $id) { if (!is_int($id) || $id < 1) { throw new InvalidArgumentException('Invalid saved map location.'); } }
        $last = $row['day'];
        $total += count($row['stops']);
    }
    if ($total > 180) { throw new InvalidArgumentException('Too many saved map stops.'); }
    return $doc;
}

/** Read only; old installations render an overview without creating any tables/settings. */
function route_map_state(string $type, array $record): array
{
    $state = ['available' => false, 'revision' => 'missing', 'document' => null, 'ready' => false, 'review' => false,
        'message' => '', 'locations' => [], 'schedule' => ['days' => [], 'revision' => '']];
    try {
        $record = route_map_record($type, (int) ($record['id'] ?? 0));
        $state['schedule'] = route_map_schedule($type, $record);
        $state['available'] = route_map_available();
        if (!$state['available']) { $state['message'] = 'Apply the Route Maps migration to enable editing. The default overview remains available.'; return $state; }
        $state['locations'] = route_map_locations();
        $row = fetch_one('SELECT route_json, revision FROM package_route_maps WHERE package_type = :type AND package_id = :id', ['type' => $type, 'id' => (int) $record['id']]);
        if (!$row) { return $state; }
        $state['revision'] = (string) $row['revision'];
        $state['document'] = route_map_decode($row['route_json']);
        $doc = $state['document'];
        $state['review'] = !hash_equals($doc['source_revision'], $state['schedule']['revision'])
            || !hash_equals($doc['locations_revision'], route_map_locations_revision($doc['days'], $state['locations']));
        if ($state['review']) { $state['message'] = 'The daily plan or a saved location changed. Review the map destinations before displaying the route again.'; return $state; }
        $input = [];
        foreach ($doc['days'] as $day) { $input[$day['day']] = $day['stops']; }
        route_map_normalize_days($input, $state['schedule'], $state['locations'], $doc['status'] === 'ready');
        $state['ready'] = $doc['status'] === 'ready';
    } catch (Throwable) {
        $state['message'] = 'This route needs review. Check its saved locations and daily plan; the public page displays the Sri Lanka overview.';
        $state['review'] = true;
    }
    return $state;
}

function route_map_design_defaults(): array
{
    return ['version' => 1, 'title' => 'Your journey at a glance', 'brand' => 'srilankaprivatetravels.com', 'route_color' => '#158d94'];
}

function route_map_design_normalize(array $input): array
{
    $color = $input['route_color'] ?? '';
    if (!is_string($color) || !preg_match('/\A#[a-fA-F0-9]{6}\z/', $color)) { throw new InvalidArgumentException('Choose a valid six-digit route colour.'); }
    // Keep lines readable on the fixed pale map background.
    $channels = sscanf($color, '#%02x%02x%02x');
    $linear = array_map(static function ($c): float { $v = $c / 255; return $v <= .04045 ? $v / 12.92 : (($v + .055) / 1.055) ** 2.4; }, $channels);
    $luminance = .2126 * $linear[0] + .7152 * $linear[1] + .0722 * $linear[2];
    if ($luminance > .27) { throw new InvalidArgumentException('Choose a darker route colour so the arrows remain visible.'); }
    return ['version' => 1, 'title' => route_map_text($input['title'] ?? '', 90), 'brand' => route_map_text($input['brand'] ?? '', 100), 'route_color' => strtolower($color)];
}

function route_map_design_state(): array
{
    $state = ['design' => route_map_design_defaults(), 'revision' => 'missing', 'saved' => false, 'available' => db_table_exists('site_settings')];
    if ($state['available']) {
        $row = fetch_one('SELECT setting_value FROM site_settings WHERE setting_key = :key', ['key' => ROUTE_MAP_DESIGN_KEY]);
        if ($row) {
            $state['revision'] = hash('sha256', (string) $row['setting_value']);
            try {
                $value = json_decode((string) $row['setting_value'], true, 8, JSON_THROW_ON_ERROR);
                if (!is_array($value) || ($value['version'] ?? null) !== 1) { throw new InvalidArgumentException('Unknown map style.'); }
                $state['design'] = route_map_design_normalize($value);
                $state['saved'] = true;
            } catch (Throwable) { /* The stored, versioned built-in design remains usable. */ }
        }
    }
    return $state;
}

function route_map_editor_url(string $type, int $id): string
{
    $definition = route_map_type($type);
    return base_url('admin/' . $definition['editor'] . '?' . $definition['id_key'] . '=' . $id . '#route-map-editor');
}
