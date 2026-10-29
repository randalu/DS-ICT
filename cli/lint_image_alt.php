<?php
declare(strict_types=1);

/**
 * Lint missing/generic alt text across DB image fields and blog content.
 *
 * Reports CSV rows with: source_table | id | slug | image_path | reason
 * "reason" is one of: missing | generic | empty
 *
 * Usage:
 *   php cli/lint_image_alt.php                    # all sources, summary
 *   php cli/lint_image_alt.php --csv              # write CSV to stdout
 *   php cli/lint_image_alt.php --only=destination_photos
 *   php cli/lint_image_alt.php --only=blog_content
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "Run from CLI.\n");
    exit(1);
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

$args = array_slice($argv, 1);
$csv = false;
$only = '';
foreach ($args as $a) {
    if ($a === '--csv') { $csv = true; continue; }
    if (str_starts_with($a, '--only=')) { $only = substr($a, 7); }
}

$findings = [];

// Generic / lazy alt strings worth catching.
$genericPatterns = [
    '/^image$/i',
    '/^photo$/i',
    '/^untitled/i',
    '/^IMG[_\- ]?\d+$/i',
    '/^DSC[_\- ]?\d+$/i',
    '/^Screenshot/i',
    '/^pexels[_\- ]/i',
    '/^unsplash[_\- ]/i',
];

$isGeneric = static function (string $alt) use ($genericPatterns): bool {
    foreach ($genericPatterns as $p) {
        if (preg_match($p, $alt)) {
            return true;
        }
    }
    return false;
};

if ($only === '' || $only === 'destination_photos') {
    $rows = fetch_all("SELECT dp.id, dp.image_path, dp.caption, d.slug AS destination_slug, d.name AS destination_name
        FROM destination_photos dp
        LEFT JOIN destinations d ON d.id = dp.destination_id");
    foreach ($rows as $r) {
        $caption = trim((string) $r['caption']);
        if ($caption === '') {
            $findings[] = ['destination_photos', $r['id'], $r['destination_slug'], $r['image_path'], 'missing'];
        } elseif ($isGeneric($caption)) {
            $findings[] = ['destination_photos', $r['id'], $r['destination_slug'], $r['image_path'], 'generic'];
        }
    }
}

if ($only === '' || $only === 'itinerary_days') {
    if (db_column_exists('itinerary_days', 'image_path')) {
        $rows = fetch_all("SELECT d.id, d.image_path, d.title, i.slug AS itinerary_slug
            FROM itinerary_days d
            LEFT JOIN itineraries i ON i.id = d.itinerary_id
            WHERE d.image_path IS NOT NULL AND d.image_path != ''");
        foreach ($rows as $r) {
            $title = trim((string) $r['title']);
            if ($title === '') {
                $findings[] = ['itinerary_days', $r['id'], $r['itinerary_slug'], $r['image_path'], 'missing'];
            } elseif ($isGeneric($title)) {
                $findings[] = ['itinerary_days', $r['id'], $r['itinerary_slug'], $r['image_path'], 'generic'];
            }
        }
    }
}

if ($only === '' || $only === 'review_photos') {
    if (db_table_exists('review_photos') && db_column_exists('review_photos', 'alt_text')) {
        $rows = fetch_all("SELECT rp.id, rp.image_path, rp.alt_text, r.id AS review_id
            FROM review_photos rp
            LEFT JOIN reviews r ON r.id = rp.review_id");
        foreach ($rows as $r) {
            $alt = trim((string) ($r['alt_text'] ?? ''));
            if ($alt === '') {
                $findings[] = ['review_photos', $r['id'], (string) ($r['review_id'] ?? ''), $r['image_path'], 'missing'];
            } elseif ($isGeneric($alt)) {
                $findings[] = ['review_photos', $r['id'], (string) ($r['review_id'] ?? ''), $r['image_path'], 'generic'];
            }
        }
    }
}

if ($only === '' || $only === 'experience_gallery') {
    if (db_table_exists('experience_gallery') && db_column_exists('experience_gallery', 'alt_text')) {
        $rows = fetch_all("SELECT eg.id, eg.image_path, eg.alt_text, e.slug AS experience_slug
            FROM experience_gallery eg
            LEFT JOIN experiences e ON e.id = eg.experience_id");
        foreach ($rows as $r) {
            $alt = trim((string) ($r['alt_text'] ?? ''));
            if ($alt === '') {
                $findings[] = ['experience_gallery', $r['id'], $r['experience_slug'], $r['image_path'], 'missing'];
            } elseif ($isGeneric($alt)) {
                $findings[] = ['experience_gallery', $r['id'], $r['experience_slug'], $r['image_path'], 'generic'];
            }
        }
    }
}

if ($only === '' || $only === 'business_partner_photos') {
    if (db_table_exists('business_partner_photos') && db_column_exists('business_partner_photos', 'alt_text')) {
        $rows = fetch_all("SELECT bpp.id, bpp.image_path, bpp.alt_text, bp.slug AS partner_slug
            FROM business_partner_photos bpp
            LEFT JOIN business_partners bp ON bp.id = bpp.partner_id");
        foreach ($rows as $r) {
            $alt = trim((string) ($r['alt_text'] ?? ''));
            if ($alt === '') {
                $findings[] = ['business_partner_photos', $r['id'], $r['partner_slug'], $r['image_path'], 'missing'];
            } elseif ($isGeneric($alt)) {
                $findings[] = ['business_partner_photos', $r['id'], $r['partner_slug'], $r['image_path'], 'generic'];
            }
        }
    }
}

if ($only === '' || $only === 'media_assets') {
    if (db_table_exists('media_assets') && db_column_exists('media_assets', 'alt_text')) {
        $rows = fetch_all("SELECT id, file_path, file_name, alt_text
            FROM media_assets
            WHERE mime_type LIKE 'image/%'");
        foreach ($rows as $r) {
            $alt = trim((string) ($r['alt_text'] ?? ''));
            if ($alt === '') {
                $findings[] = ['media_assets', $r['id'], $r['file_name'], $r['file_path'], 'missing'];
            } elseif ($isGeneric($alt)) {
                $findings[] = ['media_assets', $r['id'], $r['file_name'], $r['file_path'], 'generic'];
            }
        }
    }
}

if ($only === '' || $only === 'blog_content') {
    $rows = fetch_all("SELECT id, slug, content FROM blog_posts WHERE content IS NOT NULL AND content != ''");
    foreach ($rows as $r) {
        $html = (string) $r['content'];
        // Each <img> tag — capture src + alt
        if (!preg_match_all('~<img\s+[^>]*>~i', $html, $tags)) {
            continue;
        }
        foreach ($tags[0] as $tag) {
            $src = '';
            $alt = null;
            if (preg_match('~\bsrc\s*=\s*"([^"]*)"~i', $tag, $m)) { $src = $m[1]; }
            if (preg_match('~\balt\s*=\s*"([^"]*)"~i', $tag, $m)) { $alt = $m[1]; }

            if ($alt === null) {
                $findings[] = ['blog_content', $r['id'], $r['slug'], $src, 'missing'];
            } elseif (trim($alt) === '') {
                $findings[] = ['blog_content', $r['id'], $r['slug'], $src, 'empty'];
            } elseif ($isGeneric(trim($alt))) {
                $findings[] = ['blog_content', $r['id'], $r['slug'], $src, 'generic'];
            }
        }
    }
}

if ($only === '' || $only === 'package_covers') {
    foreach (package_tables() as $type => $table) {
        if (!db_table_exists($table) || !db_column_exists($table, 'cover_image') || !db_column_exists($table, 'cover_alt')) { continue; }
        foreach (fetch_all("SELECT id,slug,cover_image,cover_alt FROM $table WHERE cover_image IS NOT NULL AND cover_image<>''") as $row) {
            $alt = trim((string) $row['cover_alt']);
            if ($alt === '' || $isGeneric($alt)) { $findings[] = [$table . '.cover', $row['id'], $row['slug'], $row['cover_image'], $alt === '' ? 'missing' : 'generic']; }
        }
    }
}
if (($only === '' || $only === 'tour_coverage') && db_column_exists('tours', 'coverage_json')) {
    require_once __DIR__ . '/../includes/tour_coverage.php';
    foreach (fetch_all('SELECT id,slug,coverage_json FROM tours') as $row) {
        foreach (tour_coverage_decode($row['coverage_json']) as $index => $card) {
            if (empty($card['image_asset_id'])) { continue; }
            $alt = trim((string) ($card['image_alt'] ?? ''));
            if ($alt === '' || $isGeneric($alt)) { $findings[] = ['tour_coverage', $row['id'], $row['slug'], 'card:' . ($index + 1), $alt === '' ? 'missing' : 'generic']; }
        }
    }
}
if (($only === '' || $only === 'day_tour_gallery') && db_table_exists('day_tour_gallery') && db_column_exists('day_tour_gallery', 'alt_text')) {
    foreach (fetch_all('SELECT p.id,p.image_path,p.alt_text,d.slug FROM day_tour_gallery p JOIN day_tours d ON d.id=p.day_tour_id') as $row) {
        $alt = trim((string) $row['alt_text']);
        if ($alt === '' || $isGeneric($alt)) { $findings[] = ['day_tour_gallery', $row['id'], $row['slug'], $row['image_path'], $alt === '' ? 'missing' : 'generic']; }
    }
}

if (($only === '' || $only === 'tour_program') && db_column_exists('tours','program_json')) {
    require_once __DIR__ . '/../includes/tour_program.php';
    foreach (fetch_all('SELECT id,slug,program_json FROM tours') as $row) {
        $program=tour_program_decode($row['program_json']);
        foreach (['days','hotels'] as $group) { foreach ($program[$group] as $index=>$item) {
            if (!empty($item['image_asset_id']) && (trim((string)($item['image_alt']??''))==='' || $isGeneric((string)$item['image_alt']))) { $findings[]=['tour_program',$row['id'],$row['slug'],$group.':'.($index+1),'missing or generic']; }
        } }
    }
}
if (($only === '' || $only === 'activity_covers')
    && db_table_exists('activity_pages')
    && db_column_exists('activity_pages', 'cover_image')
    && db_column_exists('activity_pages', 'cover_alt')) {
    foreach (fetch_all("SELECT id,slug,cover_image,cover_alt FROM activity_pages WHERE cover_image IS NOT NULL AND cover_image<>''") as $row) {
        $alt = trim((string) $row['cover_alt']);
        if ($alt === '' || $isGeneric($alt)) {
            $findings[] = ['activity_pages.cover', $row['id'], $row['slug'], $row['cover_image'], $alt === '' ? 'missing' : 'generic'];
        }
    }
}
if (($only==='' || $only==='activity_entry_images') && db_table_exists('activity_examples') && db_table_exists('activity_pages')) {
    require_once __DIR__.'/../includes/activity_gallery.php';
    if(activity_gallery_available()){
        foreach(fetch_all('SELECT e.id,p.slug FROM activity_examples e JOIN activity_pages p ON p.id=e.activity_page_id WHERE p.is_published=1 AND NOT EXISTS(SELECT 1 FROM activity_example_images g WHERE g.activity_example_id=e.id)') as $row){
            $findings[]=['activity_entry_images',$row['id'],$row['slug'],'','missing gallery'];
        }
        foreach(fetch_all('SELECT g.id,g.alt_text,m.file_path,p.slug FROM activity_example_images g JOIN activity_examples e ON e.id=g.activity_example_id JOIN activity_pages p ON p.id=e.activity_page_id LEFT JOIN media_assets m ON m.id=g.media_asset_id') as $row){
            $alt=trim((string)$row['alt_text']);
            if($alt===''||$isGeneric($alt)){$findings[]=['activity_entry_images',$row['id'],$row['slug'],$row['file_path'],$alt===''?'missing':'generic'];}
            if(!activity_gallery_file_exists((string)$row['file_path'])){$findings[]=['activity_entry_images',$row['id'],$row['slug'],$row['file_path'],'missing image file'];}
        }
    }
}
if($only==='' || $only==='page_galleries'){
    require_once __DIR__.'/../includes/page_media.php';
    foreach(page_media_types() as $mediaType=>$definition)foreach(page_media_published($mediaType) as $record){
        $rows=page_gallery_rows($mediaType,(int)$record['id']);$slug=$record['slug']?:'blog';
        if(!$rows&&!in_array($mediaType,['page','blog_landing'],true))$findings[]=['page_galleries',$record['id'],$slug,'','missing gallery'];
        foreach($rows as $photo){$description=trim((string)$photo['alt_text']);if($description===''||$isGeneric($description))$findings[]=['page_galleries',$photo['id'],$slug,$photo['image_path'],$description===''?'missing':'generic'];if(!page_media_file_exists((string)$photo['image_path']))$findings[]=['page_galleries',$photo['id'],$slug,$photo['image_path'],'missing image file'];}
        foreach(['hero_image','cover_image','og_image'] as $slot)if(!empty($record[$slot])){$description=trim((string)($record[page_media_alt_field($slot)]??''));if($description===''||$isGeneric($description))$findings[]=['page_media',$record['id'],$slug,$record[$slot],$description===''?'missing':'generic'];if(!page_media_file_exists((string)$record[$slot]))$findings[]=['page_media',$record['id'],$slug,$record[$slot],'missing image file'];}
    }
}
if ($csv) {
    $out = fopen('php://stdout', 'w');
    fputcsv($out, ['source', 'id', 'slug', 'image_path', 'reason']);
    foreach ($findings as $f) {
        fputcsv($out, $f);
    }
    fclose($out);
    exit(0);
}

if (!$findings) {
    echo "No alt-text issues found.\n";
    exit(0);
}

$bySource = [];
foreach ($findings as $f) {
    $bySource[$f[0]][$f[4]] = ($bySource[$f[0]][$f[4]] ?? 0) + 1;
}

echo "Alt-text lint summary:\n\n";
foreach ($bySource as $src => $reasons) {
    echo "  {$src}\n";
    foreach ($reasons as $reason => $count) {
        echo "    {$reason}: {$count}\n";
    }
}
echo "\nTotal issues: " . count($findings) . "\n";
echo "Run with --csv to dump every row.\n";
exit(1);
