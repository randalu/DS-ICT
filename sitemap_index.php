<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';

header('Content-Type: application/xml; charset=utf-8');

$types = [
    'core',
    'destinations',
    'itineraries',
    'tours',
    'experiences',
    'activities',
    'day-tours',
    'countries',
    'blog',
    'themes',
    'seasonal',
    'images',
];

$includePartnersSitemap = false;
if (business_partners_public_enabled()
    && db_table_exists('business_partners')
    && db_column_exists('business_partners', 'slug')) {
    $partnerWhere = db_column_exists('business_partners', 'status')
        ? "WHERE status = 'published'"
        : '';
    $partnerCount = fetch_one("SELECT COUNT(*) AS total FROM business_partners {$partnerWhere}");
    $includePartnersSitemap = (int) ($partnerCount['total'] ?? 0) > 0;
}

if ($includePartnersSitemap) {
    array_splice($types, 4, 0, ['partners']);
}

echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
<?php foreach ($types as $type): ?>
    <sitemap>
        <loc><?= e(base_url('sitemap-' . $type . '.xml')) ?></loc>
    </sitemap>
<?php endforeach; ?>
</sitemapindex>
