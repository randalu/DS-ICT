<?php
/**
 * Re-seed the AI search index with OpenRouter embeddings.
 *
 * Use this after changing the embedding model (e.g. switching from Gemini 768-dim
 * to OpenAI 1536-dim) — it clears the old vectors so the index never contains
 * mixed-dimension embeddings, which would corrupt cosine similarity scores.
 *
 * Usage:
 *   php cli/reseed_ai_search.php              # full re-seed
 *   php cli/reseed_ai_search.php --dry-run   # show what would be indexed
 *   php cli/reseed_ai_search.php --force     # re-generate even if key missing
 *
 * Requires:
 *   - OPENROUTER_API_KEY set in site_settings.ai_api_key
 *   - An embedding model configured in site_settings.ai_embedding_model
 *     (defaults to openai/text-embedding-3-small)
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../includes/ai_search.php';

$isDryRun = in_array('--dry-run', $argv, true);
$isForce  = in_array('--force',  $argv, true);

echo "=== AI Search Re-seed (OpenRouter) ===\n\n";

$apiKey = trim((string) site_setting_get('ai_api_key', ''));
$model  = trim((string) site_setting_get('ai_embedding_model', 'openai/text-embedding-3-small'));

// In dry-run mode, skip the API key check entirely (no DB needed)
if (!$isDryRun && $apiKey === '') {
    if ($isForce) {
        echo "[!] No API key found (ai_api_key site setting is empty). Running in force mode.\n";
        echo "    The seeder will fail on every embedding call — expected in test environments.\n\n";
    } else {
        echo "[!] No OpenRouter API key configured.\n";
        echo "    Set ai_api_key in Admin > AI Settings, or run with --force to skip this check.\n";
        exit(1);
    }
}

echo "API key: " . ($apiKey !== '' ? substr($apiKey, 0, 8) . '...' : '(none / force mode)') . "\n";
echo "Embedding model: {$model}\n\n";

// In dry-run mode, skip DB-dependent setup entirely
if ($isDryRun) {
    echo "=== DRY RUN ===\n";
    echo "Content discovery and index operations are skipped (no DB required).\n";
    echo "Run without --dry-run to seed the live index.\n";
    exit(0);
}

// Clear the index (only after confirming we can run)
$cleared = db()->exec("TRUNCATE TABLE search_index");
echo "Cleared search_index table. ({$cleared} rows removed)\n\n";

// Collect all content to index
$content = [];

// Destinations — schema uses `name`/`excerpt`/`is_published`
$destinations = fetch_all("SELECT id, name, slug, excerpt FROM destinations WHERE is_published = 1");
foreach ($destinations as $dest) {
    $text = "Destination: {$dest['name']}. {$dest['excerpt']}";
    $content[] = [
        'type'    => 'destination',
        'entity_id' => (int) $dest['id'],
        'title'   => $dest['name'],
        'url'     => "/destinations/{$dest['slug']}",
        'excerpt' => $dest['excerpt'],
        'text'    => $text,
    ];
}

// Itineraries — schema uses `name`/`excerpt`
$itineraries = fetch_all("SELECT id, name, slug, excerpt FROM itineraries WHERE is_published = 1");
foreach ($itineraries as $itin) {
    $text = "Itinerary: {$itin['name']}. {$itin['excerpt']}";
    $content[] = [
        'type'    => 'itinerary',
        'entity_id' => (int) $itin['id'],
        'title'   => $itin['name'],
        'url'     => "/itineraries/{$itin['slug']}",
        'excerpt' => $itin['excerpt'],
        'text'    => $text,
    ];
}

// Experiences — schema uses `excerpt` (not short_description)
if (db_table_exists('experiences')) {
    $publishedWhere = db_column_exists('experiences', 'is_published') ? ' AND is_published = 1' : '';
    $experiences = fetch_all(
        "SELECT id, title, slug, excerpt FROM experiences WHERE 1=1 {$publishedWhere}"
    );
    foreach ($experiences as $exp) {
        $text = "Experience: {$exp['title']}. {$exp['excerpt']}";
        $content[] = [
            'type'    => 'experience',
            'entity_id' => (int) $exp['id'],
            'title'   => $exp['title'],
            'url'     => "/experiences/{$exp['slug']}",
            'excerpt' => $exp['excerpt'],
            'text'    => $text,
        ];
    }
}

// Tours
if (db_table_exists('tours')) {
    $publishedWhere = db_column_exists('tours', 'is_published') ? ' AND is_published = 1' : '';
    $tours = fetch_all(
        "SELECT id, name, slug, excerpt FROM tours WHERE 1=1 {$publishedWhere}"
    );
    foreach ($tours as $tour) {
        $text = "Tour: {$tour['name']}. {$tour['excerpt']}";
        $content[] = [
            'type'    => 'tour',
            'entity_id' => (int) $tour['id'],
            'title'   => $tour['name'],
            'url'     => "/tours/{$tour['slug']}",
            'excerpt' => $tour['excerpt'],
            'text'    => $text,
        ];
    }
}

// Static pages
if (db_table_exists('static_pages')) {
    $pages = fetch_all(
        "SELECT id, title, slug, content FROM static_pages WHERE is_published = 1 LIMIT 20"
    );
    foreach ($pages as $page) {
        $text = "Page: {$page['title']}. " . mb_substr(strip_tags((string) $page['content']), 0, 300);
        $content[] = [
            'type'    => 'page',
            'entity_id' => (int) $page['id'],
            'title'   => $page['title'],
            'url'     => "/{$page['slug']}",
            'excerpt' => mb_substr(strip_tags((string) $page['content']), 0, 160),
            'text'    => $text,
        ];
    }
}

echo "Found " . count($content) . " content items to index.\n";
echo "Embedding model: {$model}\n\n";

$indexed  = 0;
$skipped  = 0;
$failed   = 0;
$totalDim = 0;

echo "Indexing...\n\n";

foreach ($content as $item) {
    $embedding = ai_get_embedding($item['text']);

    if ($embedding === null) {
        echo "  [FAIL] {$item['type']}: {$item['title']} — no embedding returned\n";
        $failed++;
        continue;
    }

    $dim = count($embedding);
    if ($totalDim === 0) {
        $totalDim = $dim;
    } elseif ($dim !== $totalDim) {
        echo "  [WARN] {$item['type']}: {$item['title']} — "
           . "dimension mismatch: got {$dim}, expected {$totalDim}\n";
    }

    execute_query("
        INSERT INTO search_index (content_type, entity_id, title, url, excerpt, embedding_json)
        VALUES (:type, :id, :title, :url, :excerpt, :embedding)
    ", [
        'type'      => $item['type'],
        'id'        => $item['entity_id'],
        'title'     => $item['title'],
        'url'       => $item['url'],
        'excerpt'   => $item['excerpt'] ?? '',
        'embedding' => json_encode($embedding),
    ]);

    echo "  [OK] {$item['type']}: {$item['title']} (dim={$dim})\n";
    $indexed++;

    // Brief rate-limit pause
    usleep(200_000); // 200ms
}

echo "\n";
echo "=== Done ===\n";
echo "Indexed : {$indexed}\n";
echo "Failed  : {$failed}\n";
echo "Total   : " . count($content) . "\n";

if ($indexed > 0) {
    $avg = db()->query("SELECT AVG(JSON_LENGTH(embedding_json)) AS avg FROM search_index")->fetch(PDO::FETCH_ASSOC);
    $avg = (int) ($avg['avg'] ?? 0);
    echo "Avg dim : {$avg} (expected {$totalDim})\n";
}

echo "\nNote: After re-seeding, visit /ai-search to verify search quality.\n";
