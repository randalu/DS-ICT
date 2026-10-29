<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../includes/ai_search.php';

echo "Seeding AI search index...\n";

// Ensure schema
db()->exec("
    CREATE TABLE IF NOT EXISTS search_index (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        content_type VARCHAR(50) NOT NULL,
        entity_id INT UNSIGNED NOT NULL,
        title VARCHAR(255) NOT NULL,
        url VARCHAR(255) NOT NULL,
        excerpt TEXT,
        embedding_json JSON,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX (content_type, entity_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

// Destinations — schema uses `name`/`excerpt`/`is_published` (see database/schema.sql:35)
$destinations = fetch_all("SELECT id, name, slug, excerpt FROM destinations WHERE is_published = 1");
foreach ($destinations as $dest) {
    echo "Processing Destination: {$dest['name']}\n";
    $text = "Destination: {$dest['name']}. {$dest['excerpt']}";
    $embedding = ai_get_embedding($text);
    
    if ($embedding) {
        execute_query("
            INSERT INTO search_index (content_type, entity_id, title, url, excerpt, embedding_json)
            VALUES ('destination', :id, :title, :url, :excerpt, :embedding)
        ", [
            'id' => $dest['id'],
            'title' => $dest['name'],
            'url' => "/destinations/{$dest['slug']}",
            'excerpt' => $dest['excerpt'],
            'embedding' => json_encode($embedding)
        ]);
    }
    sleep(1); // crude rate limit
}

// Itineraries — schema uses `name`/`excerpt`
$itineraries = fetch_all("SELECT id, name, slug, excerpt FROM itineraries WHERE is_published = 1");
foreach ($itineraries as $itin) {
    echo "Processing Itinerary: {$itin['name']}\n";
    $text = "Itinerary: {$itin['name']}. {$itin['excerpt']}";
    $embedding = ai_get_embedding($text);
    
    if ($embedding) {
        execute_query("
            INSERT INTO search_index (content_type, entity_id, title, url, excerpt, embedding_json)
            VALUES ('itinerary', :id, :title, :url, :excerpt, :embedding)
        ", [
            'id' => $itin['id'],
            'title' => $itin['name'],
            'url' => "/itineraries/{$itin['slug']}",
            'excerpt' => $itin['excerpt'],
            'embedding' => json_encode($embedding)
        ]);
    }
    sleep(1);
}

echo "Seeding complete.\n";
