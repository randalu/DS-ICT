<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

admin_require_auth();

if (!is_post()) {
    redirect('admin/weather_api.php');
}

guard_post_size();
require_csrf();
ensure_destination_weather_schema();

$action = trim((string) post_input('action', ''));

function admin_weather_redirect(string $suffix = ''): void
{
    redirect('admin/weather_api.php' . $suffix);
}

function admin_weather_decimal_or_null(mixed $value, float $min, float $max): ?string
{
    $value = trim((string) $value);
    if ($value === '') {
        return null;
    }
    if (!is_numeric($value)) {
        throw new InvalidArgumentException('Latitude and longitude must be valid decimal numbers.');
    }
    $number = (float) $value;
    if ($number < $min || $number > $max) {
        throw new InvalidArgumentException('Latitude or longitude is outside the valid range.');
    }

    return number_format($number, 7, '.', '');
}

function admin_weather_destination_order_clause(): string
{
    $orderParts = [];
    if (db_column_exists('destinations', 'sort_order')) {
        $orderParts[] = 'sort_order ASC';
    }
    if (db_column_exists('destinations', 'name')) {
        $orderParts[] = 'name ASC';
    }
    $orderParts[] = 'id ASC';

    return implode(', ', $orderParts);
}

function admin_weather_configured_destinations(?int $limit = null): array
{
    if (!db_table_exists('destinations')) {
        return [];
    }

    $limitSql = $limit !== null ? ' LIMIT ' . max(1, $limit) : '';
    return fetch_all("
        SELECT * FROM destinations
        WHERE weather_enabled = 1
          AND weather_latitude IS NOT NULL
          AND weather_longitude IS NOT NULL
        ORDER BY " . admin_weather_destination_order_clause() . $limitSql
    );
}

function admin_weather_refresh_destination(int $destinationId): array
{
    if (!db_table_exists('destinations')) {
        return ['ok' => false, 'message' => 'Destinations table is not available in this database.'];
    }

    $destination = fetch_one("SELECT * FROM destinations WHERE id = :id LIMIT 1", ['id' => $destinationId]) ?: [];
    if (!$destination) {
        return ['ok' => false, 'message' => 'Destination not found.'];
    }
    if (!destination_weather_is_configured($destination)) {
        return ['ok' => false, 'message' => 'Destination weather location is not configured.'];
    }

    $current = destination_weather_fetch_payload($destination, 'current', true);
    $forecast = destination_weather_fetch_payload($destination, 'forecast_7_day', true);
    $errors = array_filter([(string) ($current['error'] ?? ''), (string) ($forecast['error'] ?? '')]);

    if ($errors) {
        return ['ok' => false, 'message' => implode(' ', $errors)];
    }

    return ['ok' => true, 'message' => 'Weather refreshed for ' . (string) ($destination['name'] ?? 'destination') . '.'];
}

if ($action === 'settings') {
    $enabled = post_input('weather_api_enabled') ? '1' : '0';
    $newApiKey = trim((string) post_input('weather_api_key', ''));
    $existingApiKey = trim((string) destination_weather_setting_get('weather_api_key', ''));
    $units = strtoupper(trim((string) post_input('weather_units_system', 'METRIC')));
    $language = preg_replace('/[^a-zA-Z-]/', '', trim((string) post_input('weather_language_code', 'en'))) ?: 'en';
    $currentCache = max(10, min(1440, (int) post_input('weather_current_cache_minutes', 60)));
    $forecastCache = max(60, min(2880, (int) post_input('weather_forecast_cache_minutes', 360)));
    $timeout = max(3, min(20, (int) post_input('weather_timeout_seconds', 8)));

    if (!in_array($units, ['METRIC', 'IMPERIAL'], true)) {
        $units = 'METRIC';
    }

    if ($enabled === '1' && $newApiKey === '' && $existingApiKey === '') {
        flash_set('error', 'Add a Google Weather API key before enabling live weather.');
        admin_weather_redirect();
    }

    destination_weather_setting_set('weather_api_enabled', $enabled);
    if ($newApiKey !== '') {
        destination_weather_setting_set('weather_api_key', $newApiKey);
    }
    destination_weather_setting_set('weather_units_system', $units);
    destination_weather_setting_set('weather_language_code', $language);
    destination_weather_setting_set('weather_current_cache_minutes', (string) $currentCache);
    destination_weather_setting_set('weather_forecast_cache_minutes', (string) $forecastCache);
    destination_weather_setting_set('weather_timeout_seconds', (string) $timeout);

    if (post_input('test_api')) {
        $testRows = admin_weather_configured_destinations(1);
        $testDestination = $testRows[0] ?? null;

        if (!$testDestination) {
            flash_set('error', 'Settings saved, but no destination is configured for weather testing.');
            admin_weather_redirect();
        }

        $result = admin_weather_refresh_destination((int) $testDestination['id']);
        flash_set($result['ok'] ? 'success' : 'error', 'Settings saved. ' . $result['message']);
        admin_weather_redirect();
    }

    flash_set('success', $enabled === '1' ? 'Weather API settings saved and live weather enabled.' : 'Weather API settings saved.');
    admin_weather_redirect();
}

if ($action === 'destinations') {
    if (!db_table_exists('destinations')) {
        flash_set('error', 'Destination weather locations are unavailable because the destinations table is missing.');
        admin_weather_redirect();
    }

    $rows = post_input('destinations', []);
    if (!is_array($rows)) {
        flash_set('error', 'No destination weather rows were submitted.');
        admin_weather_redirect();
    }

    try {
        foreach ($rows as $id => $row) {
            if (!is_array($row)) {
                continue;
            }
            $destinationId = (int) $id;
            if ($destinationId <= 0) {
                continue;
            }

            $enabled = isset($row['weather_enabled']) ? 1 : 0;
            $latitude = admin_weather_decimal_or_null($row['weather_latitude'] ?? '', -90, 90);
            $longitude = admin_weather_decimal_or_null($row['weather_longitude'] ?? '', -180, 180);
            $label = substr(trim((string) ($row['weather_location_label'] ?? '')), 0, 180);
            $note = trim((string) ($row['weather_admin_note'] ?? ''));

            execute_query(
                "UPDATE destinations
                 SET weather_enabled = :weather_enabled,
                     weather_latitude = :weather_latitude,
                     weather_longitude = :weather_longitude,
                     weather_location_label = :weather_location_label,
                     weather_admin_note = :weather_admin_note
                 WHERE id = :id",
                [
                    'weather_enabled' => $enabled,
                    'weather_latitude' => $latitude,
                    'weather_longitude' => $longitude,
                    'weather_location_label' => $label !== '' ? $label : null,
                    'weather_admin_note' => $note !== '' ? $note : null,
                    'id' => $destinationId,
                ]
            );
        }
    } catch (InvalidArgumentException $e) {
        $message = $e->getMessage();
        $allowedMessages = [
            'Latitude and longitude must be valid decimal numbers.',
            'Latitude or longitude is outside the valid range.',
        ];
        flash_set('error', in_array($message, $allowedMessages, true) ? $message : 'Weather location values are invalid.');
        admin_weather_redirect();
    }

    flash_set('success', 'Destination weather locations saved.');
    admin_weather_redirect();
}

if ($action === 'monthly_notes') {
    $destinationId = (int) post_input('destination_id', 0);
    if ($destinationId <= 0) {
        flash_set('error', 'Choose a destination first.');
        admin_weather_redirect();
    }
    if (!db_table_exists('destinations')) {
        flash_set('error', 'Monthly weather guidance is unavailable because the destinations table is missing.');
        admin_weather_redirect();
    }
    $destinationExists = fetch_one('SELECT id FROM destinations WHERE id = :id LIMIT 1', ['id' => $destinationId]);
    if (!$destinationExists) {
        flash_set('error', 'Choose an existing destination first.');
        admin_weather_redirect();
    }

    $months = post_input('months', []);
    if (!is_array($months)) {
        flash_set('error', 'No monthly weather notes were submitted.');
        admin_weather_redirect('?destination_id=' . $destinationId);
    }

    foreach (destination_weather_month_labels() as $monthKey => $monthLabel) {
        $row = isset($months[$monthKey]) && is_array($months[$monthKey]) ? $months[$monthKey] : [];
        $summary = trim((string) ($row['climate_summary'] ?? ''));
        $temperature = substr(trim((string) ($row['temperature_note'] ?? '')), 0, 255);
        $rain = substr(trim((string) ($row['rain_note'] ?? '')), 0, 255);
        $sea = substr(trim((string) ($row['sea_note'] ?? '')), 0, 255);
        $bestFor = substr(trim((string) ($row['best_for'] ?? '')), 0, 255);

        if ($summary === '' && $temperature === '' && $rain === '' && $sea === '' && $bestFor === '') {
            execute_query(
                "DELETE FROM destination_weather_monthly_notes WHERE destination_id = :destination_id AND month_key = :month_key",
                ['destination_id' => $destinationId, 'month_key' => $monthKey]
            );
            continue;
        }

        execute_query(
            "INSERT INTO destination_weather_monthly_notes
                (destination_id, month_key, climate_summary, temperature_note, rain_note, sea_note, best_for)
             VALUES
                (:destination_id, :month_key, :climate_summary, :temperature_note, :rain_note, :sea_note, :best_for)
             ON DUPLICATE KEY UPDATE
                climate_summary = VALUES(climate_summary),
                temperature_note = VALUES(temperature_note),
                rain_note = VALUES(rain_note),
                sea_note = VALUES(sea_note),
                best_for = VALUES(best_for)",
            [
                'destination_id' => $destinationId,
                'month_key' => $monthKey,
                'climate_summary' => $summary !== '' ? $summary : null,
                'temperature_note' => $temperature !== '' ? $temperature : null,
                'rain_note' => $rain !== '' ? $rain : null,
                'sea_note' => $sea !== '' ? $sea : null,
                'best_for' => $bestFor !== '' ? $bestFor : null,
            ]
        );
    }

    flash_set('success', 'Monthly weather guidance saved.');
    admin_weather_redirect('?destination_id=' . $destinationId);
}

if ($action === 'refresh_destination') {
    $destinationId = (int) post_input('destination_id', 0);
    $result = admin_weather_refresh_destination($destinationId);
    flash_set($result['ok'] ? 'success' : 'error', $result['message']);
    admin_weather_redirect();
}

if ($action === 'refresh_all') {
    $rows = admin_weather_configured_destinations();

    $ok = 0;
    $errors = [];
    foreach ($rows as $row) {
        $result = admin_weather_refresh_destination((int) $row['id']);
        if ($result['ok']) {
            $ok++;
        } else {
            $errors[] = (string) ($row['name'] ?? 'Destination') . ': ' . $result['message'];
        }
    }

    if ($errors) {
        flash_set('error', 'Refreshed ' . $ok . ' destination(s). Issues: ' . implode(' ', array_slice($errors, 0, 3)));
    } else {
        flash_set('success', 'Weather refreshed for ' . $ok . ' configured destination(s).');
    }
    admin_weather_redirect();
}

flash_set('error', 'Unknown weather action.');
admin_weather_redirect();
