<?php
declare(strict_types=1);

/** Coverage is deliberately independent of day plans and price inclusions. Reads never install schema. */
function tour_coverage_decode(?string $json): array
{
    $rows = json_decode((string) $json, true);
    if (!is_array($rows) || !array_is_list($rows)) {
        return [];
    }
    return array_values(array_filter($rows, static fn($row): bool => is_array($row)
        && is_string($row['title'] ?? null) && trim($row['title']) !== ''
        && is_string($row['location'] ?? null) && is_string($row['description'] ?? null)));
}

function tour_coverage_revision(?string $json): string
{
    return hash('sha256', (string) $json);
}

/** The resolver returns an existing destination, experience or verified local library image. */
function tour_coverage_normalize(mixed $rows, callable $resolve): array
{
    if (!is_array($rows) || count($rows) > 40) {
        throw new InvalidArgumentException('Coverage must contain no more than 40 cards.');
    }
    $result = [];
    foreach (array_values($rows) as $index => $row) {
        if (!is_array($row)) {
            throw new InvalidArgumentException('Invalid coverage card.');
        }
        $card = [];
        foreach (['title' => 180, 'location' => 255, 'description' => 6000, 'image_alt' => 255] as $key => $limit) {
            if (isset($row[$key]) && !is_string($row[$key])) {
                throw new InvalidArgumentException('Coverage text must be plain text.');
            }
            $value = trim((string) ($row[$key] ?? ''));
            if (mb_strlen($value) > $limit || str_contains($value, "\0") || $value !== strip_tags($value)) {
                throw new InvalidArgumentException('Coverage ' . $key . ' must be plain text within ' . $limit . ' characters.');
            }
            $card[$key] = $value;
        }
        if ($card['title'] === '' || $card['location'] === '' || $card['description'] === '') {
            throw new InvalidArgumentException('Coverage card ' . ($index + 1) . ' needs a title, exact location and description.');
        }
        $status = $row['status'] ?? 'proposed';
        if (!is_string($status) || !in_array($status, ['proposed', 'optional'], true)) {
            throw new InvalidArgumentException('Choose a valid coverage status.');
        }
        $card['status'] = $status;
        foreach (['destination_id' => 'destination', 'experience_id' => 'experience', 'image_asset_id' => 'image'] as $key => $type) {
            $value = $row[$key] ?? '';
            if (!is_int($value) && !is_string($value)) {
                throw new InvalidArgumentException('Invalid coverage reference.');
            }
            if ($value !== '' && !preg_match('/\A[0-9]{1,10}\z/', (string) $value)) {
                throw new InvalidArgumentException('Invalid coverage reference.');
            }
            $id = (int) $value;
            if ($id > 0 && !$resolve($type, $id)) {
                throw new InvalidArgumentException('The selected coverage ' . $type . ' is unavailable. Choose it again.');
            }
            $card[$key] = $id;
        }
        if ($card['image_asset_id'] > 0 && $card['image_alt'] === '') {
            throw new InvalidArgumentException('Add meaningful alt text for each coverage image.');
        }
        $result[] = $card;
    }
    return $result;
}

function tour_coverage_resolve(string $type, int $id): ?array
{
    if ($type === 'image') {
        require_once __DIR__ . '/image_manager.php';
        return image_manager_library_asset($id, false);
    }
    $table = match ($type) { 'destination' => 'destinations', 'experience' => 'experiences', default => '' };
    if ($table === '' || !db_table_exists($table)) {
        return null;
    }
    return fetch_one("SELECT id FROM {$table} WHERE id = :id", ['id' => $id]);
}

function tour_coverage_public_items(?string $json): array
{
    $rows = tour_coverage_decode($json);
    $relatedRecords = [];
    $images = [];
    foreach (['destination_id' => ['destinations', 'name'], 'experience_id' => ['experiences', 'title'], 'image_asset_id' => ['media_assets', 'file_path']] as $key => [$table, $label]) {
        $ids = array_values(array_unique(array_filter(array_map(static fn(array $row): int => (int) ($row[$key] ?? 0), $rows), static fn(int $id): bool => $id > 0)));
        if (!$ids || !db_table_exists($table)) {
            continue;
        }
        $marks = implode(',', array_fill(0, count($ids), '?'));
        if ($table === 'media_assets') {
            foreach (fetch_all("SELECT id, file_path, mime_type FROM media_assets WHERE id IN ({$marks})", $ids) as $asset) {
                $images[(int) $asset['id']] = $asset;
            }
        } elseif (db_column_exists($table, 'is_published')) {
            foreach (fetch_all("SELECT id, {$label} AS name, slug FROM {$table} WHERE id IN ({$marks}) AND is_published = 1", $ids) as $related) {
                $relatedRecords[$table][(int) $related['id']] = $related;
            }
        }
    }
    foreach ($rows as &$row) {
        $row['image_alt'] = is_string($row['image_alt'] ?? null) ? $row['image_alt'] : '';
        $row['links'] = [];
        foreach (['destination_id' => 'destinations', 'experience_id' => 'experiences'] as $key => $table) {
            $related = $relatedRecords[$table][(int) ($row[$key] ?? 0)] ?? null;
            if ($related) {
                $row['links'][] = ['label' => $related['name'], 'url' => base_url($table . '/' . $related['slug'])];
            }
        }
        $row['image_url'] = '';
        $asset = $images[(int) ($row['image_asset_id'] ?? 0)] ?? null;
        if ($asset && str_starts_with((string) $asset['mime_type'], 'image/') && !media_path_is_video((string) $asset['file_path'])) {
            $row['image_url'] = stored_media_url((string) $asset['file_path']);
        }
    }
    unset($row);
    return $rows;
}

function tour_coverage_media_reference_count(int $assetId): int
{
    if (!db_table_exists('tours') || !db_column_exists('tours', 'coverage_json')) {
        return 0;
    }
    $count = 0;
    foreach (fetch_all('SELECT coverage_json FROM tours WHERE coverage_json IS NOT NULL') as $tour) {
        foreach (tour_coverage_decode($tour['coverage_json']) as $card) {
            if ((int) ($card['image_asset_id'] ?? 0) === $assetId) {
                $count++;
            }
        }
    }
    return $count;
}
