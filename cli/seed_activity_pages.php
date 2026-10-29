<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/activity_seed_data.php';

if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
foreach (['activity_pages','activity_examples'] as $table) {
    if (!db_table_exists($table)) {
        fwrite(STDERR, "Missing {$table}; apply database/migrations/2026-10-25-activities.sql first.\n");
        exit(1);
    }
}

$connection = db();
$connection->beginTransaction();
$pageCount = 0;
$exampleCount = 0;
try {
    foreach (activity_seed_data() as $page) {
        $sourcesJson = json_encode($page['sources'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        execute_query('INSERT IGNORE INTO activity_pages
            (name,slug,is_hub,excerpt,meta_title,meta_description,research_sources_json,research_verified_at,is_published,sort_order)
            VALUES(:name,:slug,:hub,:excerpt,:title,:description,:sources,:verified,0,:sort)',
            ['name'=>$page['name'],'slug'=>$page['slug'],'hub'=>$page['is_hub'] ?? 0,'excerpt'=>$page['excerpt'],
                'title'=>$page['meta_title'],'description'=>$page['meta_description'],'sources'=>$sourcesJson,
                'verified'=>null,'sort'=>$page['sort_order']]);
        $inserted = $connection->query('SELECT ROW_COUNT()')->fetchColumn() > 0;
        if (!$inserted) { continue; } // Existing categories and their examples stay untouched.
        $pageCount++;
        $stored = fetch_one('SELECT id FROM activity_pages WHERE slug=:slug', ['slug'=>$page['slug']]);
        if (!$stored) { throw new RuntimeException('Could not locate seeded activity page ' . $page['slug']); }
        foreach ($page['examples'] as $index => $example) {
            execute_query('INSERT IGNORE INTO activity_examples
                (activity_page_id,title,destinations_json,source_urls_json,last_verified_at,sort_order)
                VALUES(:id,:title,:destinations,:sources,:verified,:sort)',
                ['id'=>(int)$stored['id'],'title'=>$example['title'],
                    'destinations'=>json_encode($example['destinations'],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR),
                    'sources'=>json_encode($example['sources'],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR),
                    'verified'=>$example['verified'] ?? null,'sort'=>$index]);
            $exampleCount += $connection->query('SELECT ROW_COUNT()')->fetchColumn() > 0 ? 1 : 0;
        }
    }
    $connection->commit();
    fwrite(STDOUT, "Activity seed complete: {$pageCount} new pages and {$exampleCount} new examples. Seeded pages remain drafts.\n");
} catch (Throwable $error) {
    if ($connection->inTransaction()) { $connection->rollBack(); }
    fwrite(STDERR, "Activity seed failed; rolled back: " . $error->getMessage() . "\n");
    exit(1);
}
