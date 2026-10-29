<?php
declare(strict_types=1);
require_once __DIR__ . '/route_map.php';

/** All writes use transactional storage, including when called inside an existing save. */
function route_map_transaction(array $tables, callable $operation): mixed
{
    foreach ($tables as $table) {
        $engine = fetch_one('SELECT ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :table', ['table' => $table]);
        if (strcasecmp((string) ($engine['ENGINE'] ?? ''), 'InnoDB') !== 0) { throw new RuntimeException('Route Map saves require the migration and transactional InnoDB tables.'); }
    }
    $pdo = db();
    $nested = $pdo->inTransaction();
    $nested ? $pdo->exec('SAVEPOINT route_map_save') : $pdo->beginTransaction();
    try {
        $result = $operation();
        $nested ? $pdo->exec('RELEASE SAVEPOINT route_map_save') : $pdo->commit();
        return $result;
    } catch (Throwable $error) {
        if ($pdo->inTransaction()) { $nested ? $pdo->exec('ROLLBACK TO SAVEPOINT route_map_save') : $pdo->rollBack(); }
        if ($error instanceof PDOException && in_array((string) $error->getCode(), ['23000', '40001'], true)) {
            throw new DomainException('A map or location was changed concurrently, or this destination is already linked. Reload and compare before saving.', 0, $error);
        }
        throw $error;
    }
}

function route_map_library_revision(array $locations): string
{
    return route_map_hash(array_map(static fn(array $r): int => (int) $r['revision'], $locations));
}

function route_map_save(string $type, int $id, array $input): void
{
    $parentTable = route_map_type($type)['table'];
    if (!route_map_available()) { throw new RuntimeException('Apply the Route Maps migration before saving maps.'); }
    route_map_transaction([$parentTable, 'map_locations', 'package_route_maps'], static function () use ($type, $id, $input): void {
        $record = route_map_record($type, $id, true);
        $schedule = route_map_schedule($type, $record, true);
        $locations = route_map_locations(true);
        $row = fetch_one('SELECT route_json, revision FROM package_route_maps WHERE package_type = :type AND package_id = :id FOR UPDATE', ['type' => $type, 'id' => $id]);
        foreach (['revision', 'source_revision', 'library_revision'] as $field) {
            if (!is_string($input[$field] ?? null)) { throw new InvalidArgumentException('Reload the complete map form before saving.'); }
        }
        if (!hash_equals($row ? (string) $row['revision'] : 'missing', $input['revision'])
            || !hash_equals($schedule['revision'], $input['source_revision'])
            || !hash_equals(route_map_library_revision($locations), $input['library_revision'])) {
            throw new DomainException('The map, daily plan or location library changed. Your entries are retained; reload saved values and compare before saving.');
        }
        $status = $input['status'] ?? '';
        if (!in_array($status, ['draft', 'ready'], true)) { throw new InvalidArgumentException('Choose Draft or Display route.'); }
        if ($row && $status === 'ready') {
            try {
                $previous = route_map_decode($row['route_json']);
                $review = !hash_equals($previous['source_revision'], $schedule['revision'])
                    || !hash_equals($previous['locations_revision'], route_map_locations_revision($previous['days'], $locations));
            } catch (Throwable) { $review = true; }
            if ($review && ($input['confirm_review'] ?? '') !== '1') { throw new InvalidArgumentException('Confirm that you reviewed the changed daily plan and map locations.'); }
        }
        $days = route_map_normalize_days($input['map_days'] ?? null, $schedule, $locations, $status === 'ready');
        $doc = ['version' => 1, 'status' => $status, 'source_revision' => $schedule['revision'],
            'locations_revision' => route_map_locations_revision($days, $locations), 'days' => $days];
        $params = ['type' => $type, 'id' => $id, 'json' => json_encode($doc, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR)];
        if ($row) {
            execute_query('UPDATE package_route_maps SET route_json = :json, revision = revision + 1 WHERE package_type = :type AND package_id = :id', $params);
        } else {
            execute_query('INSERT INTO package_route_maps(package_type, package_id, route_json) VALUES(:type, :id, :json)', $params);
        }
    });
}

function route_map_location_normalize(array $input): array
{
    $latitude = $input['latitude'] ?? null;
    $longitude = $input['longitude'] ?? null;
    foreach ([$latitude, $longitude] as $value) {
        if ((!is_string($value) && !is_int($value) && !is_float($value)) || !is_numeric($value) || !is_finite((float) $value)) {
            throw new InvalidArgumentException('Enter valid latitude and longitude.');
        }
    }
    if ((float) $latitude < 5.8 || (float) $latitude > 10 || (float) $longitude < 79.4 || (float) $longitude > 82) {
        throw new InvalidArgumentException('Choose coordinates within the Sri Lanka map area (latitude 5.8–10, longitude 79.4–82).');
    }
    foreach (['is_verified', 'is_active'] as $field) {
        if (!in_array($input[$field] ?? '0', ['0', '1'], true)) { throw new InvalidArgumentException('Invalid location status.'); }
    }
    $destination = $input['destination_id'] ?? '';
    return ['name' => route_map_text($input['name'] ?? '', 150), 'latitude' => number_format((float) $latitude, 6, '.', ''),
        'longitude' => number_format((float) $longitude, 6, '.', ''), 'coordinate_source' => route_map_text($input['coordinate_source'] ?? '', 500),
        'destination_id' => $destination === '' ? null : route_map_integer($destination, 1, PHP_INT_MAX),
        'is_verified' => (int) (($input['is_verified'] ?? '0') === '1'), 'is_active' => (int) (($input['is_active'] ?? '0') === '1')];
}

function route_map_location_save(int $id, array $input): int
{
    if ($id < 0 || !route_map_available()) { throw new InvalidArgumentException('Location storage is unavailable.'); }
    $values = route_map_location_normalize($input);
    if (!is_string($input['revision'] ?? null)) { throw new InvalidArgumentException('Reload the location before saving.'); }
    return route_map_transaction(['map_locations'], static function () use ($id, $input, $values): int {
        $row = $id ? fetch_one('SELECT revision FROM map_locations WHERE id = :id FOR UPDATE', ['id' => $id]) : null;
        if (($id && !$row) || !hash_equals($row ? (string) $row['revision'] : 'missing', $input['revision'])) {
            throw new DomainException('This saved location changed. Reload and compare before saving.');
        }
        if ($values['destination_id'] !== null && (!db_table_exists('destinations')
            || !fetch_one('SELECT id FROM destinations WHERE id = :id', ['id' => $values['destination_id']]))) {
            throw new InvalidArgumentException('Choose an existing destination record.');
        }
        if ($id) {
            execute_query('UPDATE map_locations SET name=:name, latitude=:latitude, longitude=:longitude, destination_id=:destination_id, coordinate_source=:coordinate_source, is_verified=:is_verified, is_active=:is_active, revision=revision+1 WHERE id=:id', $values + ['id' => $id]);
            return $id;
        }
        execute_query('INSERT INTO map_locations(name,latitude,longitude,destination_id,coordinate_source,is_verified,is_active) VALUES(:name,:latitude,:longitude,:destination_id,:coordinate_source,:is_verified,:is_active)', $values);
        return (int) db()->lastInsertId();
    });
}

function route_map_design_save(array $input): void
{
    $design = route_map_design_normalize($input);
    if (!is_string($input['revision'] ?? null)) { throw new InvalidArgumentException('Reload the default design before saving.'); }
    route_map_transaction(['site_settings'], static function () use ($design, $input): void {
        $row = fetch_one('SELECT setting_value FROM site_settings WHERE setting_key=:key FOR UPDATE', ['key' => ROUTE_MAP_DESIGN_KEY]);
        if (!hash_equals($row ? hash('sha256', (string) $row['setting_value']) : 'missing', $input['revision'])) {
            throw new DomainException('The default design changed. Reload and compare before saving.');
        }
        $values = ['key' => ROUTE_MAP_DESIGN_KEY, 'value' => json_encode($design, JSON_THROW_ON_ERROR)];
        if ($row) { execute_query('UPDATE site_settings SET setting_value=:value WHERE setting_key=:key', $values); }
        else { execute_query('INSERT INTO site_settings(setting_key,setting_value) VALUES(:key,:value)', $values); }
    });
}
