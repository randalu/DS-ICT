<?php
declare(strict_types=1);

/** Seed unpublished policy/trust drafts; never overwrite existing records. */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../includes/policy_acceptance.php';

$format = 'text';
$syncSafeDisclosures = false;
foreach (array_slice($argv, 1) as $argument) {
    if ($argument === '--sync-safe-disclosures') { $syncSafeDisclosures = true; continue; }
    if (str_starts_with($argument, '--format=')) {
        $format = strtolower(trim(substr($argument, 9)));
        if (in_array($format, ['text', 'json'], true)) { continue; }
    }
    fwrite(STDERR, "Usage: php cli/seed_release0_policy_drafts.php [--format=text|json] [--sync-safe-disclosures]\n");
    exit(2);
}
if (!db_table_exists('static_pages')) { fwrite(STDERR, "static_pages is unavailable.\n"); exit(2); }
ensure_static_pages_extended_schema();
ensure_customer_policy_acceptance_schema();
$results = [];
foreach (ict_policy_documents() as $key => $document) {
    if ($syncSafeDisclosures && $key === 'privacy') {
        $oldDisclosure = '<p>Depending on the feature used, this may include name, email, phone or WhatsApp details, country, travel dates, group size, itinerary interests, accommodation preferences, messages, account identifiers, plan changes, payment references, technical logs, and consent records.</p>';
        $newDisclosure = '<p>Depending on the feature used, this may include name, email, phone or WhatsApp details, country, travel dates, group size, itinerary interests, accommodation preferences, messages, account identifiers, plan changes, payment references, technical logs, and consent records. When booking policies are accepted, the acceptance record includes the policy versions and snapshot, time, IP address, and browser user-agent for evidence and dispute handling.</p>';
        execute_query('UPDATE static_pages SET content = REPLACE(content, :old_text, :new_text) WHERE slug = :slug AND is_published = 0 AND LOCATE(:old_check, content) > 0 AND LOCATE(:new_check, content) = 0', [
            'old_text' => $oldDisclosure, 'new_text' => $newDisclosure, 'slug' => $document['slug'], 'old_check' => $oldDisclosure, 'new_check' => $newDisclosure,
        ]);
    }
    $existing = fetch_one('SELECT id, is_published, title, content, meta_title, meta_description FROM static_pages WHERE slug = :slug LIMIT 1', ['slug' => $document['slug']]);
    if ($existing) {
        $differences = [];
        foreach (['title', 'content', 'meta_title', 'meta_description'] as $field) {
            if ((string) ($existing[$field] ?? '') !== (string) ($document[$field] ?? '')) { $differences[] = $field; }
        }
        $results[] = ['key' => $key, 'slug' => $document['slug'], 'action' => 'skipped_existing',
            'id' => (int) ($existing['id'] ?? 0), 'is_published' => (int) ($existing['is_published'] ?? 0), 'source_differences' => $differences];
        continue;
    }
    $columns = ['title' => $document['title'], 'slug' => $document['slug'], 'content' => $document['content'],
        'meta_title' => $document['meta_title'], 'meta_description' => $document['meta_description'],
        'canonical_url' => null, 'sort_order' => 90, 'is_published' => 0];
    $writeColumns = [];
    $writeValues = [];
    foreach ($columns as $column => $value) {
        if (!db_column_exists('static_pages', $column)) { continue; }
        $writeColumns[] = $column;
        $writeValues[$column] = $value;
    }
    $placeholders = array_map(static fn(string $column): string => ':' . $column, $writeColumns);
    execute_query('INSERT INTO static_pages (' . implode(', ', $writeColumns) . ') VALUES (' . implode(', ', $placeholders) . ')', $writeValues);
    $results[] = ['key' => $key, 'slug' => $document['slug'], 'action' => 'created_unpublished', 'id' => (int) last_insert_id(), 'is_published' => 0];
}
$report = ['draft_count' => count($results), 'published_by_this_command' => 0, 'results' => $results];
if ($format === 'json') {
    echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
} else {
    foreach ($results as $result) { echo '[' . $result['action'] . '] ' . $result['slug'] . ' (#' . $result['id'] . ')' . PHP_EOL; }
    echo 'Policy drafts created or already present: ' . count($results) . '; published by command: 0.' . PHP_EOL;
}
