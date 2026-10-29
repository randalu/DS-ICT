<?php
declare(strict_types=1);

/**
 * Read-only self-contained Tour and retained theme release audit.
 */

require_once __DIR__ . '/../config/bootstrap.php';

ob_start();
register_shutdown_function(static function (): void {
    if (ob_get_level() > 0) {
        ob_end_flush();
    }
});

$failures = [];
$passed = 0;
$total = 0;

$render = static function (string $file, array $get, string $requestPath): string {
    $_GET = $get;
    $_REQUEST = $get;
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_SERVER['REQUEST_URI'] = $requestPath;

    $startedTransaction = false;
    if (!db()->inTransaction()) {
        db()->beginTransaction();
        $startedTransaction = true;
    }

    ob_start();
    try {
        require $file;
        $html = (string) ob_get_clean();
        if ($startedTransaction && db()->inTransaction()) {
            db()->rollBack();
        }
        return $html;
    } catch (Throwable $exception) {
        if (ob_get_level() > 0) {
            ob_end_clean();
        }
        if ($startedTransaction && db()->inTransaction()) {
            db()->rollBack();
        }
        return '<!-- route exception: ' . e($exception->getMessage()) . ' -->';
    }
};

$assert = static function (string $label, array $issues) use (&$failures, &$passed, &$total): void {
    $total++;
    if ($issues) {
        $failures[$label] = $issues;
        echo '[FAIL] ' . $label . ': ' . implode('; ', $issues) . PHP_EOL;
        return;
    }

    $passed++;
    echo '[PASS] ' . $label . PHP_EOL;
};

if (!db_column_exists('tours','program_json')) {
    fwrite(STDERR,"Tour programme schema is missing. Apply the current migration first.\n"); exit(2);
}
$retirementIssues=[];
foreach (['n_day_itinerary_pages','itinerary_package_links','itinerary_tour'] as $table) {
    if (db_table_exists($table) && (int)(fetch_one('SELECT COUNT(*) AS total FROM '.$table)['total'] ?? 0)>0) { $retirementIssues[]='retired content or connections remain in '.$table; }
}
$assert('Tour reference pages and connections retired',$retirementIssues);

if (!db_table_exists('theme_page_editorial') || !db_table_exists('theme_tour_links')) {
    fwrite(STDERR, "Phase 58-67 audit: required theme editorial tables are missing.\n");
    exit(2);
}

$themeSlugs = ['honeymoon-tours', 'family-tours', 'wildlife-tours'];
foreach ($themeSlugs as $slug) {
    $editorial = fetch_one('SELECT * FROM theme_page_editorial WHERE theme_slug = :slug AND is_published = 1 LIMIT 1', ['slug' => $slug]);
    $tourCount = (int) (fetch_one(
        'SELECT COUNT(*) AS total FROM theme_tour_links l INNER JOIN tours t ON t.slug = l.tour_slug WHERE l.theme_slug = :slug AND l.is_active = 1 AND t.is_published = 1',
        ['slug' => $slug]
    )['total'] ?? 0);
    $html = $render(__DIR__ . '/../theme.php', ['slug' => $slug], '/' . $slug);
    $issues = [];
    if (!$editorial) {
        $issues[] = 'missing published theme editorial';
    } else {
        foreach (['intro_html', 'route_html', 'planning_html', 'seasonal_html', 'reassurance_html', 'faq_json'] as $field) {
            if (trim((string) ($editorial[$field] ?? '')) === '') {
                $issues[] = 'theme field missing: ' . $field;
            }
        }
    }
    if ($tourCount < 1) {
        $issues[] = 'no linked published tour package';
    }
    if (!preg_match('~<title[^>]*>\s*.+?\s*</title>~is', $html)) {
        $issues[] = 'missing title';
    }
    if (preg_match_all('~<h1\b[^>]*>~i', $html) !== 1) {
        $issues[] = 'expected exactly one H1';
    }
    if (!preg_match('~<script[^>]+type=["\']application/ld\+json["\'][^>]*>.*?Service.*?</script>~is', $html)) {
        $issues[] = 'missing Service JSON-LD';
    }
    if (!preg_match('~<script[^>]+type=["\']application/ld\+json["\'][^>]*>.*?FAQPage.*?</script>~is', $html)) {
        $issues[] = 'missing FAQPage JSON-LD';
    }
    if (!preg_match('~(?:href|content)=["\'][^"\']*(?:/contact|wa\.me)[^"\']*["\']~i', $html)) {
        $issues[] = 'missing conversion CTA';
    }
    if (!preg_match('~href=["\'][^"\']*/tours/[a-z0-9-]+[^"\']*["\']~i', $html)) {
        $issues[] = 'missing tour package link';
    }

    $assert('/' . $slug, $issues);
}

$themeSitemap = $render(__DIR__ . '/../sitemap_partial.php', ['type' => 'themes'], '/sitemap-themes.xml');
$sitemapIssues = [];
if (!str_contains($themeSitemap, '<urlset')) {
    $sitemapIssues[] = 'missing XML urlset';
}
if (!str_contains($themeSitemap, htmlspecialchars(base_url('wildlife-tours'), ENT_XML1 | ENT_QUOTES, 'UTF-8'))) {
    $sitemapIssues[] = 'wildlife theme missing from sitemap';
}
$assert('/sitemap-themes.xml', $sitemapIssues);

$packageRows=fetch_all('SELECT slug FROM tours WHERE is_published=1 ORDER BY slug');
foreach ($packageRows as $packageRow) {
    $packageSlug=(string)$packageRow['slug'];
    $html=$render(__DIR__.'/../tour.php',['slug'=>$packageSlug],'/tours/'.$packageSlug);
    $issues=[];
    if (preg_match_all('~<h1\b[^>]*>~i',$html)!==1) { $issues[]='expected exactly one H1'; }
    foreach (['tour-route','tour-days','tour-hotels','tour-coverage'] as $section) { if (!str_contains($html,'id="'.$section.'"')) { $issues[]='missing owned section '.$section; } }
    if (str_contains($html,'Detailed planning guides')) { $issues[]='removed reference area returned'; }
    foreach ([5,7,9,10,12,14] as $days) { if (str_contains($html,'href="'.e(base_url($days.'-day-itinerary')))) { $issues[]='retired reference link returned'; } }
    if (!str_contains($html,'Customise This Tour')) { $issues[]='missing conversion CTA'; }
    $assert('/tours/'.$packageSlug,$issues);
}

echo sprintf("Tour and theme summary: %d/%d checks passed; %d failed.\n", $passed, $total, count($failures));
exit($failures ? 1 : 0);
