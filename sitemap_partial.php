<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';

header('Content-Type: application/xml; charset=utf-8');

$type = strtolower(trim((string) get_input('type', '')));
$allowed = [
    'core',
    'destinations',
    'itineraries',
    'tours',
    'experiences',
    'activities',
    'partners',
    'countries',
    'blog',
    'themes',
    'seasonal',
    'images',
    'day-tours',
];

if ($type === '' || !in_array($type, $allowed, true)) {
    http_response_code(404);
    echo 'Not found';
    exit;
}

require_once __DIR__ . '/includes/retired_package_pages.php';
$urls = [];
$withImages = $type === 'images';
$businessPartnersPublicEnabled = business_partners_public_enabled();

$addUrl = static function (string $loc, ?string $lastmod = null, array $images = []) use (&$urls): void {
    if (package_reference_path_retired($loc)) { return; }
    $images = array_values(array_filter(array_map('trim', $images), static fn(string $v): bool => $v !== '' && !is_placeholder_media_url($v)));
    $urls[] = [
        'loc' => $loc,
        'lastmod' => $lastmod,
        'images' => $images,
    ];
};

$fmtLastmod = static function (?string $value): ?string {
    $value = trim((string) $value);
    if ($value === '') {
        return null;
    }
    $ts = strtotime($value);
    return $ts ? date('c', $ts) : null;
};

$columnSelect = static function (string $table, string $column, ?string $alias = null, string $fallback = 'NULL'): string {
    $alias ??= $column;

    return db_column_exists($table, $column)
        ? ($column . ($alias !== $column ? ' AS ' . $alias : ''))
        : ($fallback . ' AS ' . $alias);
};

$orderBy = static function (string $table, string $fallbackColumn = 'slug'): string {
    if (db_column_exists($table, 'id')) {
        return 'id ASC';
    }

    return db_column_exists($table, $fallbackColumn) ? $fallbackColumn . ' ASC' : '1 ASC';
};

if ($type === 'core') {
    $coreUrls = [
        base_url(),
        base_url('destinations'),
        base_url('experiences'),
        base_url('itineraries'),
        base_url('tours'),
        base_url('seasonal'),
        base_url('from'),
        base_url('about-us'),
        base_url('why-choose-us'),
        base_url('reviews'),
        base_url('faq'),
        base_url('contact'),
        base_url('blog'),
    ];

    if(db_table_exists('activity_pages') && fetch_one('SELECT id FROM activity_pages WHERE is_hub=1 AND is_published=1 LIMIT 1')){$coreUrls[]=base_url('activities');}
    foreach ($coreUrls as $loc) {
        $addUrl($loc);
    }

    if ($businessPartnersPublicEnabled) {
        $addUrl(base_url('business-partners'));
        $addUrl(base_url('business-partners/hotels-accommodations'));
    }

    $staticPages = [];
    if (db_table_exists('static_pages') && db_column_exists('static_pages', 'slug')) {
        $staticWhere = ["slug NOT IN ('about-us', 'why-choose-us', 'reviews', 'faq', 'contact')"];
        if (db_column_exists('static_pages', 'is_published')) {
            $staticWhere[] = 'is_published = 1';
        }
        $staticPages = fetch_all("
            SELECT slug, " . $columnSelect('static_pages', 'canonical_url') . ", " . $columnSelect('static_pages', 'updated_at') . "
            FROM static_pages
            WHERE " . implode(' AND ', $staticWhere) . "
            ORDER BY " . $orderBy('static_pages') . "
        ");
    }
    foreach ($staticPages as $row) {
        $addUrl(static_page_public_url((string) $row['slug'], (string) ($row['canonical_url'] ?? '')), $fmtLastmod($row['updated_at'] ?? null));
    }
}

if ($type === 'destinations' || $withImages) {
    $rows = [];
    if (db_table_exists('destinations') && db_column_exists('destinations', 'slug')) {
        $destinationWhere = [];
        if (db_column_exists('destinations', 'is_published')) {
            $destinationWhere[] = 'is_published = 1';
        }
        $destinationWhereSql = $destinationWhere ? ('WHERE ' . implode(' AND ', $destinationWhere)) : '';
        $rows = fetch_all("
            SELECT slug,
                   " . $columnSelect('destinations', 'updated_at') . ",
                   " . $columnSelect('destinations', 'og_image') . ",
                   " . $columnSelect('destinations', 'cover_image') . ",
                   " . $columnSelect('destinations', 'hero_image') . "
            FROM destinations
            {$destinationWhereSql}
            ORDER BY " . $orderBy('destinations') . "
        ");
    }
    foreach ($rows as $row) {
        $images = [];
        if ($withImages) {
            $images[] = !empty($row['og_image'])
                ? upload_url((string) $row['og_image'])
                : (!empty($row['cover_image'])
                    ? upload_url((string) $row['cover_image'])
                    : (!empty($row['hero_image']) ? upload_url((string) $row['hero_image']) : ''));
        }
        if ($type === 'destinations') {
            $addUrl(base_url('destinations/' . (string) $row['slug']), $fmtLastmod($row['updated_at'] ?? null), $images);
        } elseif ($withImages) {
            $addUrl(base_url('destinations/' . (string) $row['slug']), $fmtLastmod($row['updated_at'] ?? null), $images);
        }
    }
}

if ($type === 'itineraries' || $withImages) {
    $rows = [];
    if (db_table_exists('itineraries') && db_column_exists('itineraries', 'slug')) {
        $itineraryWhere = [];
        if (db_column_exists('itineraries', 'is_published')) {
            $itineraryWhere[] = 'is_published = 1';
        }
        $itineraryWhereSql = $itineraryWhere ? ('WHERE ' . implode(' AND ', $itineraryWhere)) : '';
        $rows = fetch_all("
            SELECT slug,
                   " . $columnSelect('itineraries', 'updated_at') . ",
                   " . $columnSelect('itineraries', 'og_image') . ",
                   " . $columnSelect('itineraries', 'hero_image') . "
            FROM itineraries
            {$itineraryWhereSql}
            ORDER BY " . $orderBy('itineraries') . "
        ");
    }
    foreach ($rows as $row) {
        $images = [];
        if ($withImages) {
            $images = package_sitemap_images($row, 'itinerary');
        }
        $addUrl(base_url('itineraries/' . (string) $row['slug']), $fmtLastmod($row['updated_at'] ?? null), $images);
    }
}

if (($type === 'tours' || $withImages) && db_table_exists('tours') && db_column_exists('tours', 'slug')) {
    $tourWhere = [];
    if (db_column_exists('tours', 'is_published')) {
        $tourWhere[] = 'is_published = 1';
    }
    $tourWhereSql = $tourWhere ? ('WHERE ' . implode(' AND ', $tourWhere)) : '';
    $tourRows = fetch_all(
        "SELECT slug,
                " . $columnSelect('tours', 'updated_at') . ",
                " . $columnSelect('tours', 'og_image') . ",
                " . $columnSelect('tours', 'hero_image') . "
         FROM tours
         {$tourWhereSql}
         ORDER BY " . $orderBy('tours')
    );

    foreach ($tourRows as $row) {
        $images = [];
        if ($withImages) {
            $images = package_sitemap_images($row, 'tour');
        }
        $addUrl(base_url('tours/' . (string) $row['slug']), $fmtLastmod($row['updated_at'] ?? null), $images);
    }
}

if ($type === 'experiences' || $withImages) {
    $experienceRows = fetch_experiences(true);
    foreach ($experienceRows as $row) {
        $images = [];
        if ($withImages) {
            $images[] = !empty($row['og_image'])
                ? upload_url((string) $row['og_image'])
                : (!empty($row['hero_image']) ? upload_url((string) $row['hero_image']) : '');
        }

        if ($type === 'experiences') {
            $addUrl(base_url('experiences/' . (string) $row['slug']), $fmtLastmod($row['updated_at'] ?? null), $images);
        } elseif ($withImages) {
            $addUrl(base_url('experiences/' . (string) $row['slug']), $fmtLastmod($row['updated_at'] ?? null), $images);
        }
    }
}

if ($type === 'activities' || $withImages) {
    require_once __DIR__.'/includes/activity_gallery.php';
    if (db_table_exists('activity_pages') && db_column_exists('activity_pages', 'slug')) {
        $activityWhere = db_column_exists('activity_pages', 'is_published') ? 'WHERE is_published = 1' : '';
        $activityRows = fetch_all("SELECT id,slug,is_hub,updated_at,og_image,hero_image,cover_image
            FROM activity_pages {$activityWhere} ORDER BY sort_order,id");
        foreach ($activityRows as $row) {
            $loc = !empty($row['is_hub'])
                ? base_url('activities')
                : base_url('activities/' . (string) $row['slug']);
            $images = [];
            if ($withImages) {
                foreach (['og_image','cover_image','hero_image'] as $field) {
                    if (!empty($row[$field])) { $images[] = upload_url((string) $row[$field]); }
                }
                foreach(activity_category_images((int)$row['id']) as $photo){if(!empty($photo['file_path'])){$images[]=stored_media_url($photo['file_path']);}}
                $images=array_values(array_unique($images));
            }
            $addUrl($loc, $fmtLastmod($row['updated_at'] ?? null), $images);
        }
    }
}

if ($type === 'day-tours' || $withImages) {
    $dayTourRows = [];
    if (db_table_exists('day_tours') && db_column_exists('day_tours', 'slug')) {
        $dtWhere = [];
        if (db_column_exists('day_tours', 'is_published')) {
            $dtWhere[] = 'is_published = 1';
        }
        $dtWhereSql = $dtWhere ? ('WHERE ' . implode(' AND ', $dtWhere)) : '';
        $dayTourRows = fetch_all("
            SELECT slug,
                   " . $columnSelect('day_tours', 'updated_at') . ",
                   " . $columnSelect('day_tours', 'og_image') . ",
                   " . $columnSelect('day_tours', 'hero_image') . "
            FROM day_tours
            {$dtWhereSql}
            ORDER BY " . $orderBy('day_tours') . "
        ");
    }
    foreach ($dayTourRows as $row) {
        $images = [];
        if ($withImages) {
            $images = package_sitemap_images($row, 'day_tour');
        }
        $addUrl(base_url('day-tours/' . (string) $row['slug']), $fmtLastmod($row['updated_at'] ?? null), $images);
    }
}

if ($type === 'tours' || $type === 'themes' || ($type === 'experiences' && empty($experienceRows)) || ($withImages && empty($experienceRows))) {
    if (db_table_exists('itinerary_categories')) {
        $hasThemeSlug = db_column_exists('itinerary_categories', 'theme_slug');
        $hasCategorySlug = db_column_exists('itinerary_categories', 'slug');
        $where = [];
        $params = [];

        if ($type === 'themes' && $hasThemeSlug) {
            if (db_column_exists('itinerary_categories', 'is_theme')) {
                $where[] = 'is_theme = 1';
            }
            $where[] = "COALESCE(TRIM(theme_slug), '') <> ''";
        }

        if (db_column_exists('itinerary_categories', 'is_published')) {
            $where[] = 'is_published = 1';
        }

        $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
        $themeSlugSelect = $hasThemeSlug ? 'theme_slug' : 'NULL AS theme_slug';
        $slugSelect = $hasCategorySlug ? 'slug' : 'NULL AS slug';
        $itineraryCountSelect = 'NULL AS itinerary_count';
        if ($type === 'tours'
            && db_table_exists('itineraries')
            && db_column_exists('itineraries', 'category_id')) {
            $itineraryPublishedFilter = db_column_exists('itineraries', 'is_published')
                ? ' AND category_itinerary.is_published = 1'
                : '';
            $itineraryCountSelect = '(SELECT COUNT(*) FROM itineraries category_itinerary
                                      WHERE category_itinerary.category_id = itinerary_categories.id'
                . $itineraryPublishedFilter . ') AS itinerary_count';
        } elseif ($type === 'tours') {
            $itineraryCountSelect = '0 AS itinerary_count';
        }

        $rows = (!$hasCategorySlug && !$hasThemeSlug) || ($type === 'themes' && !$hasThemeSlug)
            ? []
            : fetch_all(
                "SELECT id, {$slugSelect}, {$themeSlugSelect}, {$itineraryCountSelect},
                        " . $columnSelect('itinerary_categories', 'updated_at') . ",
                        " . $columnSelect('itinerary_categories', 'og_image') . ",
                        " . $columnSelect('itinerary_categories', 'hero_image') . "
                 FROM itinerary_categories
                 {$whereSql}
                 ORDER BY " . $orderBy('itinerary_categories'),
                $params
            );

        foreach ($rows as $row) {
            if ($type === 'themes') {
                $themeSlug = trim((string) ($row['theme_slug'] ?? ''));
                if ($themeSlug === '') {
                    continue;
                }
                $loc = base_url($themeSlug);
            } elseif ($type === 'tours') {
                if (empty($row['slug'])) {
                    continue;
                }
                if (!itinerary_category_sitemap_indexable($row)) {
                    continue;
                }
                $loc = base_url('tours/' . (string) $row['slug']);
            } else {
                if (empty($row['slug'])) {
                    continue;
                }
                $loc = base_url('experiences/' . (string) $row['slug']);
            }

            $images = [];
            if ($withImages) {
                $images[] = !empty($row['og_image'])
                    ? upload_url((string) $row['og_image'])
                    : (!empty($row['hero_image']) ? upload_url((string) $row['hero_image']) : '');
            }

            if ($type === 'tours' || $type === 'experiences' || $type === 'themes') {
                $addUrl($loc, $fmtLastmod($row['updated_at'] ?? null), $images);
            } elseif ($withImages) {
                $addUrl($loc, $fmtLastmod($row['updated_at'] ?? null), $images);
            }
        }
    }
}

if (($type === 'partners' || $withImages) && $businessPartnersPublicEnabled) {
    if (db_table_exists('business_partners') && db_column_exists('business_partners', 'slug')) {
        $partnerWhere = [];
        if (db_column_exists('business_partners', 'status')) {
            $partnerWhere[] = "status = 'published'";
        }
        $partnerWhereSql = $partnerWhere ? ('WHERE ' . implode(' AND ', $partnerWhere)) : '';
        $partnerOrderParts = array_values(array_filter([
            db_column_exists('business_partners', 'is_featured') ? 'is_featured DESC' : null,
            db_column_exists('business_partners', 'sort_order') ? 'sort_order ASC' : null,
            db_column_exists('business_partners', 'business_name') ? 'business_name ASC' : null,
            $orderBy('business_partners'),
        ]));
        $rows = fetch_all("
            SELECT slug,
                   " . $columnSelect('business_partners', 'updated_at') . ",
                   " . $columnSelect('business_partners', 'published_at') . ",
                   " . $columnSelect('business_partners', 'og_image') . ",
                   " . $columnSelect('business_partners', 'hero_image') . "
            FROM business_partners
            {$partnerWhereSql}
            ORDER BY " . implode(', ', array_unique($partnerOrderParts)) . "
        ");
        foreach ($rows as $row) {
            $images = [];
            if ($withImages) {
                $images[] = !empty($row['og_image'])
                    ? upload_url((string) $row['og_image'])
                    : (!empty($row['hero_image']) ? upload_url((string) $row['hero_image']) : '');
            }
            $lastmod = $fmtLastmod($row['updated_at'] ?? null) ?: $fmtLastmod($row['published_at'] ?? null);
            if ($type === 'partners') {
                $addUrl(base_url('business-partners/partner/' . (string) $row['slug']), $lastmod, $images);
            } elseif ($withImages) {
                $addUrl(base_url('business-partners/partner/' . (string) $row['slug']), $lastmod, $images);
            }
        }
    }
}

if (($type === 'countries' || $withImages) && db_table_exists('country_pages') && db_column_exists('country_pages', 'slug')) {
    $countryWhere = [];
    if (db_column_exists('country_pages', 'is_published')) {
        $countryWhere[] = 'is_published = 1';
    }
    $countryWhereSql = $countryWhere ? ('WHERE ' . implode(' AND ', $countryWhere)) : '';
    $rows = fetch_all("
        SELECT slug,
               " . $columnSelect('country_pages', 'updated_at') . ",
               " . $columnSelect('country_pages', 'og_image') . ",
               " . $columnSelect('country_pages', 'hero_image') . "
        FROM country_pages
        {$countryWhereSql}
        ORDER BY " . $orderBy('country_pages') . "
    ");
    foreach ($rows as $row) {
        $images = [];
        if ($withImages) {
            $images[] = !empty($row['og_image'])
                ? upload_url((string) $row['og_image'])
                : (!empty($row['hero_image']) ? upload_url((string) $row['hero_image']) : '');
        }
        $addUrl(base_url('from/' . (string) $row['slug']), $fmtLastmod($row['updated_at'] ?? null), $images);
    }
}

if ($type === 'blog' || $withImages) {
    if (db_table_exists('blog_posts') && db_column_exists('blog_posts', 'slug')) {
        $blogWhere = [];
        if (db_column_exists('blog_posts', 'is_published')) {
            $blogWhere[] = 'is_published = 1';
        }
        if (db_column_exists('blog_posts', 'published_at')) {
            $blogWhere[] = '(published_at IS NULL OR published_at <= NOW())';
        }
        $blogWhereSql = $blogWhere ? ('WHERE ' . implode(' AND ', $blogWhere)) : '';
        $rows = fetch_all("
            SELECT slug,
                   " . $columnSelect('blog_posts', 'canonical_url') . ",
                   " . $columnSelect('blog_posts', 'updated_at') . ",
                   " . $columnSelect('blog_posts', 'published_at') . ",
                   " . $columnSelect('blog_posts', 'hero_image') . ",
                   " . $columnSelect('blog_posts', 'og_image') . "
            FROM blog_posts
            {$blogWhereSql}
            ORDER BY " . $orderBy('blog_posts') . "
        ");
        foreach ($rows as $row) {
            $loc = trim((string) ($row['canonical_url'] ?? ''));
            if ($loc === '') {
                $loc = base_url('blog/' . (string) $row['slug']);
            }

            $images = [];
            if ($withImages) {
                $images[] = !empty($row['og_image'])
                    ? upload_url((string) $row['og_image'])
                    : (!empty($row['hero_image']) ? upload_url((string) $row['hero_image']) : '');
            }

            $lastmod = $fmtLastmod($row['updated_at'] ?? null);
            if (!$lastmod) {
                $lastmod = $fmtLastmod($row['published_at'] ?? null);
            }
            $addUrl($loc, $lastmod, $images);
        }
    }
}

if ($type === 'seasonal') {
    $months = [
        'january','february','march','april','may','june',
        'july','august','september','october','november','december',
    ];
    foreach ($months as $m) {
        $addUrl(base_url('sri-lanka-in-' . $m));
    }
}

// Duplicate root duration pages are retired and never enter the sitemap.

if($withImages){
    require_once __DIR__.'/includes/page_media.php';$pageImages=[];$pageDates=[];
    foreach(page_media_types() as $mediaType=>$definition)foreach(page_media_published($mediaType) as $record){
        $loc=page_media_public_url($mediaType,$record);$images=[];
        foreach(['hero_image','cover_image','og_image','hero_video_poster'] as $field)if(!empty($record[$field]) && !media_path_is_video((string)$record[$field]))$images[]=stored_media_url($record[$field]);
        foreach(page_gallery_rows($mediaType,(int)$record['id']) as $photo)if(!empty($photo['image_path']))$images[]=stored_media_url($photo['image_path']);
        $pageImages[$loc]=array_values(array_unique(array_filter($images,static fn($image)=>!is_placeholder_media_url($image))));$pageDates[$loc]=$fmtLastmod($record['updated_at']??null);
    }
    $seen=[];foreach($urls as &$url){if(isset($pageImages[$url['loc']]))$url['images']=array_values(array_unique(array_merge($url['images'],$pageImages[$url['loc']])));$seen[$url['loc']]=true;}unset($url);
    foreach($pageImages as $loc=>$images)if(!isset($seen[$loc])&&$images)$addUrl($loc,$pageDates[$loc],$images);
}
echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"<?= $withImages ? ' xmlns:image="http://www.google.com/schemas/sitemap-image/1.1"' : '' ?>>
<?php foreach ($urls as $url): ?>
    <url>
        <loc><?= e($url['loc']) ?></loc>
        <?php if (!empty($url['lastmod'])): ?>
            <lastmod><?= e((string) $url['lastmod']) ?></lastmod>
        <?php endif; ?>
        <?php if ($withImages && !empty($url['images'])): ?>
            <?php foreach ($url['images'] as $img): ?>
                <?php if ($img === '') continue; ?>
                <image:image>
                    <image:loc><?= e($img) ?></image:loc>
                </image:image>
            <?php endforeach; ?>
        <?php endif; ?>
    </url>
<?php endforeach; ?>
</urlset>
