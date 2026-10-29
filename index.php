<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/schema.php';
require_once __DIR__ . '/includes/responsive_image.php';

$iconMarkup = static function (string $type): string {
    $icons = [
        'vehicle' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 16h14M7 16l-1-4 2-5h8l2 5-1 4M8 7V5h8v2M7 19a1.5 1.5 0 1 0 0 .01M17 19a1.5 1.5 0 1 0 0 .01" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7"/></svg>',
        'flex' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 8c0-2.2 1.8-4 4-4h4M17 16c0 2.2-1.8 4-4 4H9M7 8l2-2M7 8l2 2M17 16l-2-2M17 16l-2 2" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7"/></svg>',
        'location' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 21s6-5.3 6-11a6 6 0 1 0-12 0c0 5.7 6 11 6 11Z" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7"/><circle cx="12" cy="10" r="2.2" fill="none" stroke="currentColor" stroke-width="1.7"/></svg>',
        'support' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 12a8 8 0 0 1 16 0M6 15v2a2 2 0 0 0 2 2h1v-6H8a2 2 0 0 0-2 2Zm12 0v2a2 2 0 0 1-2 2h-1v-6h1a2 2 0 0 1 2 2Zm-8 4h4" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7"/></svg>',
        'shield' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 3 6 5v5c0 4 2.6 7.6 6 9 3.4-1.4 6-5 6-9V5l-6-2Z" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7"/><path d="m9.5 11.5 1.7 1.7 3.3-3.7" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7"/></svg>',
        'expert' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 12a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7ZM5 20a7 7 0 0 1 14 0M4 11h3M17 11h3" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7"/></svg>',
        'route' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M7 18a2 2 0 1 0 0-4 2 2 0 0 0 0 4Zm10-8a2 2 0 1 0 0-4 2 2 0 0 0 0 4ZM9 16h4a4 4 0 0 0 4-4V8" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7"/></svg>',
        'thumb' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 11V6.8c0-1.4 1-2.7 2.4-3l.6-.1.9 4.5c.1.7 0 1.4-.4 2l-.5.8H18a2 2 0 0 1 2 2l-1 5a2.5 2.5 0 0 1-2.4 2H9m0-9H5v9h4v-9Z" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.7"/></svg>',
        'sunrise' => '<svg viewBox="0 0 48 48" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M5 32h38M9 38h30M15 32a9 9 0 0 1 18 0M24 9v5M8 20l4 3M40 20l-4 3M14 12l3 4M34 12l-3 4"/><path d="M5 32h38" stroke-width="3"/></svg>',
        'island' => '<svg viewBox="0 0 48 48" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M5 38c8-7 30-7 38 0M25 33c4-8 5-15 4-22M29 13c-6-7-13-3-15 1 6-1 10-1 15-1Zm0 0c5-7 12-3 14 1-6-1-10-1-14-1Zm0 0c-5 0-8 5-8 10 3-4 5-7 8-10ZM5 42c4-2 8 2 12 0s8 2 12 0 8 2 14 0"/><circle cx="10" cy="23" r="4"/></svg>',
        'heart' => '<svg viewBox="0 0 48 48" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M24 38 9 23C-1 12 15 2 24 14 33 2 49 12 39 23L24 38Z"/><path d="M7 42h34M31 8l2-4M38 10l4-1"/></svg>',
        'family' => '<svg viewBox="0 0 48 48" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><circle cx="13" cy="13" r="5"/><circle cx="35" cy="13" r="5"/><circle cx="24" cy="25" r="4"/><path d="M4 35v-7a9 9 0 0 1 15-7M44 35v-7a9 9 0 0 0-15-7M16 42v-4a8 8 0 0 1 16 0v4M9 35v7M39 35v7"/></svg>',
        'mountain' => '<svg viewBox="0 0 48 48" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="m3 39 16-27 16 27H3Zm25-12 6-12 11 24H35M13 22l6 3 5-4M19 12V5h10l-3 3 3 3H19"/><path d="M9 43h30"/></svg>',
        'lotus' => '<svg viewBox="0 0 48 48" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M24 36c-10-9-9-19 0-29 9 10 10 20 0 29Zm0 0C10 36 5 27 6 17c10 1 16 7 18 19Zm0 0c14 0 19-9 18-19-10 1-16 7-18 19ZM9 41h30"/></svg>',
        'gem' => '<svg viewBox="0 0 48 48" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="m5 18 9-10h20l9 10-19 24L5 18Zm0 0h38M14 8l4 10 6 24 6-24 4-10M18 18l6-10 6 10"/></svg>',
        'wildlife' => '<svg viewBox="0 0 48 48" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M9 38V23C9 9 32 8 34 22l1 12c1 6 8 6 8 0v-7M10 24l-5-3v10M13 38v5h7v-8h6v8h7v-8M25 18c-9-6-14 8-5 12 4 2 7-1 7-5V14"/><circle cx="31" cy="22" r=".8" fill="currentColor"/></svg>',
    ];

    return $icons[$type] ?? $icons['vehicle'];
};

$truncate = static function (?string $text, int $length = 140): string {
    $value = trim((string) $text);
    if ($value === '') {
        return '';
    }
    if (function_exists('mb_strimwidth')) {
        return rtrim((string) mb_strimwidth($value, 0, $length, '...'));
    }
    return strlen($value) > $length ? rtrim(substr($value, 0, $length - 3)) . '...' : $value;
};

$resolveImage = static function (?string $path) : string {
    $value = trim((string) $path);
    if ($value === '') {
        return '';
    }
    if (preg_match('~^https?://~i', $value)) {
        return $value;
    }
    if (str_starts_with($value, 'assets/')) {
        return base_url($value);
    }
    return stored_media_url($value);
};

$heroImage = base_url('assets/images/galle-lighthouse.jpg');
$heroFallbackImages = [
    base_url('assets/images/home-hero/home-4.jpg'),
    base_url('assets/images/home-hero/home-6.jpg'),
    base_url('assets/images/home-hero/home-3.jpg'),
    base_url('assets/images/home-hero/home-2.jpg'),
];

$destinationRows = fetch_all(sprintf(
    "SELECT %s, " . content_image_select_fragment('destinations') . "
     FROM destinations
     WHERE is_published = 1 AND is_featured = 1
     ORDER BY sort_order ASC, name ASC",
    destination_card_select_fields()
));

$destinationCount = count($destinationRows);
if ($destinationCount > 0) {
    // Advance one position per homepage load so every featured stop gets the lead card.
    $destinationOffset = ((int) ($_SESSION['homepage_destination_offset'] ?? -1) + 1) % $destinationCount;
    $_SESSION['homepage_destination_offset'] = $destinationOffset;
    $destinationRows = array_slice(array_merge(
        array_slice($destinationRows, $destinationOffset),
        array_slice($destinationRows, 0, $destinationOffset)
    ), 0, 4);
}

$destinationCards = [];
foreach ($destinationRows as $destination) {
    $image = content_image_path($destination, ['hero_image','og_image']);
    $summary = trim((string) ($destination['excerpt'] ?? ''));
    if ($summary === '') {
        $summary = trim((string) ($destination['meta_description'] ?? ''));
    }
    $slug = trim((string) ($destination['slug'] ?? ''));
    $destinationCards[] = [
        'name' => (string) ($destination['name'] ?? 'Destination'),
        'label' => $truncate($summary !== '' ? $summary : 'Discover a private Sri Lanka stop worth adding to your route.', 100),
        'url' => $slug !== '' ? base_url('destinations/' . $slug) : base_url('destinations'),
        'image' => $image !== '' ? $resolveImage($image) : DEFAULT_OG_IMAGE,
    ];
}

$useCategories = db_table_exists('itinerary_categories') && db_column_exists('itineraries', 'category_id');
$categoryJoin = $useCategories ? 'LEFT JOIN itinerary_categories c ON c.id = i.category_id' : '';
$categorySelect = $useCategories ? 'c.name AS category_name,' : 'NULL AS category_name,';
$itineraryRows = fetch_all(
    "SELECT
        i.id,
        i.name,
        i.slug,
        i.excerpt,
        i.days,
        i.hero_image,
        i.og_image,
        {$categorySelect}
        " . pricing_select_fragment('itineraries') . "
     FROM itineraries i
     {$categoryJoin}
     WHERE i.is_published = 1
     ORDER BY i.is_featured DESC, i.sort_order ASC, i.days ASC
     LIMIT 8"
);

$itineraryCards = [];
foreach ($itineraryRows as $index => $itinerary) {
    $image = package_card_image($itinerary, 'itinerary');
    $slug = trim((string) ($itinerary['slug'] ?? ''));
    $itineraryCards[] = [
        'name' => (string) ($itinerary['name'] ?? 'Sri Lanka itinerary'),
        'days' => (int) ($itinerary['days'] ?? 0),
        'image_alt' => package_card_alt($itinerary, 'itinerary'),
        'nights' => max(1, (int) ($itinerary['days'] ?? 1) - 1),
        'summary' => $truncate((string) ($itinerary['excerpt'] ?? 'Private route with chauffeur-guide support.'), 88),
        'price' => format_price_range($itinerary) ?: 'Custom quote',
        'url' => $slug !== '' ? base_url('itineraries/' . $slug) : base_url('itineraries'),
        'image' => $image !== '' ? $resolveImage($image) : ($heroFallbackImages[$index % count($heroFallbackImages)]),
    ];
}

$itineraryFallbacks = [
    ['name' => 'Culture Triangle Explorer', 'days' => 6, 'nights' => 5, 'summary' => 'Explore ancient cities, UNESCO sites, and cultural heritage.', 'price' => 'From $620 per person', 'url' => base_url('itineraries'), 'image' => base_url('assets/images/home-hero/home-2.jpg')],
    ['name' => 'Wild Sri Lanka Safari', 'days' => 7, 'nights' => 6, 'summary' => 'Discover national parks, wildlife, and scenic highlands.', 'price' => 'From $780 per person', 'url' => base_url('itineraries'), 'image' => base_url('assets/images/home-hero/home-6.jpg')],
    ['name' => 'Hill Country Escape', 'days' => 5, 'nights' => 4, 'summary' => 'Tea plantations, charming towns, and breathtaking views.', 'price' => 'From $540 per person', 'url' => base_url('itineraries'), 'image' => base_url('assets/images/home-hero/home-3.jpg')],
    ['name' => 'Beach & Beyond', 'days' => 8, 'nights' => 7, 'summary' => 'Relax on the beaches and explore the southern coast.', 'price' => 'From $890 per person', 'url' => base_url('itineraries'), 'image' => base_url('assets/images/home-hero/home-4.jpg')],
];
foreach ($itineraryFallbacks as $fallback) {
    if (count($itineraryCards) >= 4) {
        break;
    }
    $itineraryCards[] = $fallback;
}
$itineraryCards = array_slice($itineraryCards, 0, 4);

$categoryRows = fetch_published_itinerary_categories_with_counts();
$categoryPresentation = [
    'short-tours' => ['icon' => 'sunrise', 'description' => 'A few days away, filled with culture, coast and time to explore.'],
    'classic-tours' => ['icon' => 'island', 'description' => 'Ancient cities, tea country and the coast in one island journey.'],
    'day-tours' => ['icon' => 'sunrise', 'description' => 'Make a day of it: culture, wildlife or the coast from your base.'],
    'honeymoon' => ['icon' => 'heart', 'description' => 'Slow mornings, scenic stays and time together, at your own pace.'],
    'family' => ['icon' => 'family', 'description' => 'Curious little explorers, shared discoveries and room to slow down.'],
    'adventure' => ['icon' => 'mountain', 'description' => 'Take the scenic trail through peaks, forests and open landscapes.'],
    'wellness' => ['icon' => 'lotus', 'description' => 'Gentler days, peaceful surroundings and space to unwind.'],
    'luxury' => ['icon' => 'gem', 'description' => 'Thoughtful stays and a private journey shaped around your comfort.'],
    'wildlife' => ['icon' => 'wildlife', 'description' => 'Explore national parks and the wild side of Sri Lanka.'],
];
$categoryCards = [];
foreach ($categoryRows as $category) {
    $categorySlug = trim((string) ($category['slug'] ?? ''));
    if ($categorySlug === '') {
        continue;
    }
    $categoryName = trim((string) ($category['name'] ?? '')) ?: 'Tour category';
    $categoryCards[] = [
        'name' => $categoryName,
        'icon' => $categoryPresentation[$categorySlug]['icon'] ?? 'route',
        'description' => $categoryPresentation[$categorySlug]['description'] ?? ($truncate((string) ($category['description'] ?? ''), 120) ?: 'Shape a private Sri Lanka journey around the places and experiences you love.'),
        'cta' => $categorySlug === 'day-tours' ? 'Explore day tours' : 'Explore journeys',
        'url' => itinerary_category_url($category),
    ];
}
$categoryCards = array_slice($categoryCards, 0, 8);

ensure_experiences_schema();
$experienceRows = fetch_all(
    "SELECT title, slug, excerpt, intro, hero_image, og_image, " . content_image_select_fragment('experiences') . "
     FROM experiences
     WHERE is_published = 1
     ORDER BY is_featured DESC, sort_order ASC, title ASC
     LIMIT 8"
);

$experienceCards = [];
foreach ($experienceRows as $index => $experience) {
    $image = content_image_path($experience, ['hero_image','og_image']);
    $slug = trim((string) ($experience['slug'] ?? ''));
    $summary = trim((string) ($experience['excerpt'] ?? ''));
    if ($summary === '') {
        $summary = trim((string) ($experience['intro'] ?? ''));
    }
    $experienceCards[] = [
        'name' => (string) ($experience['title'] ?? 'Sri Lanka experience'),
        'summary' => $truncate($summary !== '' ? $summary : 'Add memorable local experiences to a private Sri Lanka route.', 82),
        'url' => $slug !== '' ? base_url('experiences/' . $slug) : base_url('experiences'),
        'image' => $image !== '' ? $resolveImage($image) : ($heroFallbackImages[$index % count($heroFallbackImages)]),
    ];
}

$experienceFallbacks = [
    ['name' => 'Scenic Train Journeys', 'summary' => 'Slow travel through tea country, mountain passes, and iconic bridges.', 'url' => base_url('experiences'), 'image' => base_url('assets/images/home-hero/home-3.jpg')],
    ['name' => 'Wildlife Safaris', 'summary' => 'National parks, elephants, leopards, and sunrise jeep drives.', 'url' => base_url('experiences'), 'image' => base_url('assets/images/home-hero/home-6.jpg')],
    ['name' => 'Hidden Gems', 'summary' => 'Less-crowded stops, local villages, and route detours worth taking.', 'url' => base_url('experiences'), 'image' => base_url('assets/images/home-hero/home-2.jpg')],
];
foreach ($experienceFallbacks as $fallback) {
    if (count($experienceCards) >= 4) {
        break;
    }
    $experienceCards[] = $fallback;
}
$experienceCards = array_slice($experienceCards, 0, 4);

$dayTourRows = db_table_exists('day_tours') ? fetch_all(
    "SELECT name, slug, excerpt, price_from, price_to, price_currency, price_unit, duration_hours, difficulty, hero_image, og_image
     FROM day_tours
     WHERE is_published = 1
     ORDER BY is_featured DESC, sort_order ASC, name ASC
     LIMIT 8"
) : [];

$dayTourCards = [];
$dayTourImageFallbacks = [
    'sigiriya-dambulla-day-tour' => 'assets/images/home-hero/home-2.jpg',
    'kandy-city-day-tour' => 'assets/images/kandy-temple-lk.jpg',
    'yala-safari-day-tour' => 'assets/images/yala-national-park-leopard-safari-large.webp',
    'galle-fort-south-coast-day-tour' => 'assets/images/galle-lighthouse.jpg',
    'nuwara-eliya-tea-country-day-tour' => 'assets/images/country-market/nuwara-eliya-tea.jpg',
    'ella-kandy-adams-peak-day-tour' => 'assets/images/nine-arch-bridge-lk.jpg',
    'mirissa-whale-watching-day-tour' => 'assets/images/mirissa-beach-lk.jpg',
];
foreach ($dayTourRows as $dt) {
    $dt = package_card_record($dt, 'day_tour');
    $slug = trim((string) ($dt['slug'] ?? ''));
    $image = $dayTourImageFallbacks[$slug] ?? 'assets/images/placeholders/placeholder-tour.svg';
    foreach (['cover_image', 'hero_image', 'og_image'] as $imageKey) {
        $candidateImage = trim((string) ($dt[$imageKey] ?? ''));
        if ($candidateImage === '' || media_path_is_video($candidateImage)) {
            continue;
        }
        $storagePath = image_normalize_storage_path($candidateImage);
        $candidateFile = $storagePath !== null && str_starts_with($storagePath, 'assets/')
            ? BASE_PATH . '/' . $storagePath
            : UPLOAD_BASE_PATH . '/' . ($storagePath ?? '');
        if ($storagePath === null || (!str_contains($storagePath, '..') && is_file($candidateFile))) {
            $image = $candidateImage;
            break;
        }
    }
    $priceLabel = format_price_range($dt, false);
    $hours = (float) ($dt['duration_hours'] ?? 0);
    $durationLabel = $hours > 0 ? ($hours >= 8 ? 'Full day' : $hours . ' hrs') : '';
    $dayTourCards[] = [
        'name' => (string) ($dt['name'] ?? 'Day Tour'),
        'summary' => trim((string) ($dt['excerpt'] ?? '')),
        'url' => $slug !== '' ? base_url('day-tours/' . $slug) : base_url('day-tours'),
        'image' => $resolveImage($image),
        'image_alt' => str_contains($image, '/placeholders/') ? 'Day tour image awaiting selection' : package_card_alt($dt, 'day_tour'),
        'price' => $priceLabel,
        'duration' => $durationLabel,
    ];
}

ensure_reviews_schema();
$reviewRows = fetch_all(
    "SELECT id, customer_name, country, rating, review_title, review_text, trip_type,
            tour_done_date, tour_done_month, review_source, review_source_url, created_at
     FROM reviews
     WHERE is_published = 1
       AND customer_name NOT LIKE '%test%'
       AND customer_name NOT LIKE '%remove%'
     ORDER BY is_featured DESC, sort_order ASC, id DESC
     LIMIT 6"
);

$reviewCards = [];
$formatReviewDate = static function (array $review): string {
    $date = trim((string) ($review['tour_done_date'] ?? ''));
    if ($date !== '') {
        $timestamp = strtotime($date);
        return $timestamp ? date('F Y', $timestamp) : $date;
    }
    $month = trim((string) ($review['tour_done_month'] ?? ''));
    if ($month !== '') {
        $dateObject = DateTime::createFromFormat('Y-m', $month);
        return $dateObject ? $dateObject->format('F Y') : $month;
    }
    return '';
};
foreach ($reviewRows as $review) {
    $reviewCards[] = [
        'name' => (string) ($review['customer_name'] ?? 'Guest'),
        'type' => trim((string) (($review['trip_type'] ?? '') !== '' ? $review['trip_type'] : 'Private Tour')),
        'country' => trim((string) ($review['country'] ?? '')),
        'title' => trim((string) ($review['review_title'] ?? '')),
        'text' => $truncate((string) ($review['review_text'] ?? ''), 320),
        'rating' => max(1, min(5, (int) ($review['rating'] ?? 5))),
        'date' => $formatReviewDate($review),
        'source' => trim((string) ($review['review_source'] ?? '')) !== '' ? review_source_label((string) $review['review_source']) : '',
        'source_url' => preg_match('~\Ahttps?://~i', trim((string) ($review['review_source_url'] ?? ''))) ? trim((string) $review['review_source_url']) : '',
    ];
}
$reviewCards = array_slice($reviewCards, 0, 3);

$trustItems = [
    ['icon' => 'vehicle', 'title' => 'Private driver & vehicle', 'text' => 'Your comfort, your pace'],
    ['icon' => 'flex', 'title' => 'Flexible & customizable', 'text' => 'Tailor-made for you'],
    ['icon' => 'location', 'title' => 'Local experts', 'text' => 'Deep local knowledge'],
    ['icon' => 'support', 'title' => 'Responsive trip support', 'text' => 'Help before and during your route'],
];

$approachItems = [
    ['icon' => 'shield', 'title' => 'Private & Flexible', 'text' => 'Travel with your own private driver and vehicle. Your itinerary, your pace.'],
    ['icon' => 'expert', 'title' => 'Local Expertise', 'text' => 'Our team knows Sri Lanka deeply and creates authentic experiences.'],
    ['icon' => 'route', 'title' => 'Tailor-Made Itineraries', 'text' => 'Every journey is customized to match your interests, style, and budget.'],
    ['icon' => 'support', 'title' => 'Planning support', 'text' => 'Clear help with route choices, timing, and practical trip questions.'],
    ['icon' => 'thumb', 'title' => 'Evidence-led planning', 'text' => 'We keep published trip details, route ideas, and guest feedback clear and useful.'],
];

ensure_homepage_manager_schema();
$homepageSettings = homepage_new_settings();
$homepageHero = homepage_new_active_hero();
$homepageActiveSections = homepage_new_active_sections();
// Keep the two discovery widgets together without changing saved CMS settings.
if (in_array('destinations', $homepageActiveSections, true) && in_array('day-tours', $homepageActiveSections, true)) {
    $homepageActiveSections = array_values(array_filter(
        $homepageActiveSections,
        static fn(string $section): bool => $section !== 'day-tours'
    ));
    $destinationSectionIndex = array_search('destinations', $homepageActiveSections, true);
    array_splice($homepageActiveSections, $destinationSectionIndex + 1, 0, ['day-tours']);
}

$heroMediaType = (string) ($homepageHero['media_type'] ?? 'image');
$heroMediaPath = (string) ($homepageHero['file_path'] ?? 'assets/images/galle-lighthouse.jpg');
$heroPosterPath = (string) ($homepageHero['poster_path'] ?? '');
$heroFallbackImagePath = 'assets/images/galle-lighthouse.jpg';
$heroImagePath = $heroMediaType === 'video' ? ($heroPosterPath !== '' ? $heroPosterPath : $heroFallbackImagePath) : $heroMediaPath;
$heroImage = $resolveImage($heroImagePath);
$heroVideoUrl = $heroMediaType === 'video' ? $resolveImage($heroMediaPath) : '';
$heroScript = trim((string) ($homepageHero['headline_script'] ?? 'The Beauty of Sri Lanka'));
$heroTitle = trim((string) ($homepageHero['headline_main'] ?? 'Private Sri Lanka Tours with Your Own Driver-Guide'));
$heroSupportingText = trim((string) ($homepageHero['supporting_text'] ?? 'Explore private Sri Lanka tours and chauffeur-guided travel for couples, families, wildlife, beaches, and culture—with a flexible itinerary shaped around your dates and pace.'));
$heroPrimaryLabel = trim((string) ($homepageHero['primary_cta_label'] ?? 'Explore Itineraries'));
$heroPrimaryUrl = trim((string) ($homepageHero['primary_cta_url'] ?? 'itineraries'));
$heroSecondaryLabel = trim((string) ($homepageHero['secondary_cta_label'] ?? 'Plan Your Trip'));
$heroSecondaryUrl = trim((string) ($homepageHero['secondary_cta_url'] ?? 'contact'));

$ctaHeading = trim((string) ($homepageSettings['homepage_new_cta_heading'] ?? 'Ready to Plan Your Sri Lanka Journey?'));
$ctaBody = trim((string) ($homepageSettings['homepage_new_cta_body'] ?? 'Let our travel experts create the perfect itinerary for your unforgettable trip.'));
$ctaButtonLabel = trim((string) ($homepageSettings['homepage_new_cta_button_label'] ?? 'Plan My Trip'));
$ctaButtonUrl = trim((string) ($homepageSettings['homepage_new_cta_button_url'] ?? 'contact'));
$ctaImage = trim((string) ($homepageSettings['homepage_new_cta_image'] ?? 'assets/images/home-hero/home-5.jpg'));

$seoTitle = trim((string) ($homepageSettings['homepage_new_seo_title'] ?? '')) ?: 'Sri Lanka Private Tours & Private Driver | Island Culture Travels';
$seoDescription = trim((string) ($homepageSettings['homepage_new_seo_description'] ?? '')) ?: 'Plan a private Sri Lanka tour with your own driver-guide, flexible routes, cultural sites, wildlife, beaches, and practical support from arrival to departure.';
$seoCanonical = base_url();
$seoImage = trim((string) ($homepageSettings['homepage_new_og_image'] ?? '')) !== ''
    ? $resolveImage((string) $homepageSettings['homepage_new_og_image'])
    : $heroImage;
$seoOgTitle = trim((string) ($homepageSettings['homepage_new_og_title'] ?? '')) ?: $seoTitle;
$seoOgDescription = trim((string) ($homepageSettings['homepage_new_og_description'] ?? '')) ?: $seoDescription;
$seoType = 'website';
$schemaGraph = [schema_website(), schema_travel_agency()];
foreach ($reviewRows as $review) {
    $schemaGraph[] = schema_review([
        'author' => (string) ($review['customer_name'] ?? 'Guest'),
        'rating' => (float) ($review['rating'] ?? 0),
        'text' => (string) ($review['review_text'] ?? ''),
        'date_iso' => (string) (($review['tour_done_date'] ?? '') ?: ($review['created_at'] ?? '')),
    ]);
}

$renderHomepageSection = static function (string $sectionKey) use (
    $trustItems,
    $itineraryCards,
    $categoryCards,
    $experienceCards,
    $destinationCards,
    $dayTourCards,
    $approachItems,
    $reviewCards,
    $iconMarkup,
    $ctaHeading,
    $ctaBody,
    $ctaButtonLabel,
    $ctaButtonUrl,
    $ctaImage
): void {
    switch ($sectionKey) {
        case 'trust':
            ?>
            <section class="new-home-floating-trust">
                <div class="container">
                    <div class="new-home-trust-card">
                        <?php foreach ($trustItems as $item): ?>
                            <article class="new-home-trust-item trust-item">
                                <span class="new-home-trust-icon trust-icon"><?= $iconMarkup((string) $item['icon']) ?></span>
                                <div>
                                    <strong class="trust-item-title"><?= e($item['title']) ?></strong>
                                    <span class="trust-item-text"><?= e($item['text']) ?></span>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>
            <?php
            break;

        case 'itineraries':
            ?>
            <section class="new-home-block">
                <div class="container">
                    <div class="section-head new-home-section-head">
                        <div>
                            <span class="section-eyebrow new-home-section-label">Tours</span>
                            <h2 class="section-title">Featured Tour Itineraries</h2>
                            <p class="section-lead">Handpicked private tours across Sri Lanka, designed to show you the very best of our island - at your pace.</p>
                        </div>
                        <a href="<?= e(base_url('itineraries')) ?>" class="section-link new-home-inline-link">View all itineraries &rarr;</a>
                    </div>
                    <div class="new-home-rail-wrap">
                        <button class="new-home-carousel-arrow new-home-carousel-prev" type="button" data-rail-prev="itineraries" aria-label="Previous itineraries">&#8249;</button>
                        <div class="new-home-rail new-home-rail-itineraries" data-rail-track="itineraries">
                        <?php foreach ($itineraryCards as $card): ?>
                            <a href="<?= e($card['url']) ?>" class="new-home-itinerary-card">
                                <span class="new-home-itinerary-thumb">
                                    <?= responsive_image([
                                        'src' => $card['image'],
                                        'alt' => $card['image_alt'] ?? $card['name'],
                                        'widths' => [600, 1200],
                                        'sizes' => '(min-width: 1220px) 32vw, (min-width: 768px) 45vw, 100vw',
                                        'width' => 1200,
                                        'height' => 900,
                                        'class' => 'new-home-cover',
                                    ]) ?>
                                    <span class="new-home-itinerary-shade"></span>
                                    <span class="card-badge">&#9716; <?= e((string) $card['days']) ?> Days</span>
                                    <span class="new-home-itinerary-copy">
                                        <strong><?= e($card['name']) ?></strong>
                                        <span class="new-home-itinerary-summary"><?= e($card['summary']) ?></span>
                                        <span class="new-home-itinerary-price">Starting from <?= e($card['price']) ?></span>
                                    </span>
                                </span>
                            </a>
                        <?php endforeach; ?>
                            <article class="new-home-collection-card">
                                <a href="<?= e(base_url('itineraries')) ?>" class="new-home-collection-tile">
                                    <span class="new-home-collection-kicker">All tours</span>
                                    <strong>See All Itineraries</strong>
                                    <span>Browse every published private route.</span>
                                    <em>Explore now &rarr;</em>
                                </a>
                            </article>
                        </div>
                        <button class="new-home-carousel-arrow new-home-carousel-next" type="button" data-rail-next="itineraries" aria-label="Next itineraries">&#8250;</button>
                    </div>
                    <div class="new-home-carousel-dots" data-rail-dots="itineraries"></div>
                </div>
            </section>
            <?php
            break;

        case 'experiences':
            ?>
            <section class="new-home-block new-home-block-dark">
                <div class="container">
                    <div class="section-head new-home-section-head">
                        <div>
                            <span class="section-eyebrow new-home-section-label">Experiences</span>
                            <h2 class="section-title">Signature Experiences</h2>
                            <p class="section-lead">Wildlife, trains, hidden gems, food, culture, and the kind of moments that make a route feel personal.</p>
                        </div>
                        <a href="<?= e(base_url('experiences')) ?>" class="section-link new-home-inline-link">View all experiences &rarr;</a>
                    </div>
                    <div class="new-home-rail-wrap">
                        <button class="new-home-carousel-arrow new-home-carousel-prev" type="button" data-rail-prev="experiences" aria-label="Previous experiences">&#8249;</button>
                        <div class="new-home-rail new-home-rail-experiences" data-rail-track="experiences">
                            <?php foreach ($experienceCards as $card): ?>
                                <article class="new-home-experience-card">
                                    <a href="<?= e($card['url']) ?>" class="card-exp">
                                        <?= responsive_image([
                                            'src' => $card['image'],
                                            'alt' => $card['name'],
                                            'widths' => [600, 1200],
                                            'sizes' => '(min-width: 1220px) 32vw, (min-width: 768px) 42vw, 86vw',
                                            'width' => 1200,
                                            'height' => 1500,
                                            'class' => 'new-home-cover',
                                        ]) ?>
                                        <span class="card-exp-overlay"></span>
                                        <span class="card-exp-body">
                                            <strong class="card-exp-title"><?= e($card['name']) ?></strong>
                                            <span class="card-exp-excerpt"><?= e($card['summary']) ?></span>
                                        </span>
                                    </a>
                                </article>
                            <?php endforeach; ?>
                            <article class="new-home-collection-card">
                                <a href="<?= e(base_url('experiences')) ?>" class="new-home-collection-tile">
                                    <span class="new-home-collection-kicker">All experiences</span>
                                    <strong>See All Experiences</strong>
                                    <span>Hidden gems, activities, and route ideas across Sri Lanka.</span>
                                    <em>Explore now &rarr;</em>
                                </a>
                            </article>
                        </div>
                        <button class="new-home-carousel-arrow new-home-carousel-next" type="button" data-rail-next="experiences" aria-label="Next experiences">&#8250;</button>
                    </div>
                    <div class="new-home-carousel-dots" data-rail-dots="experiences"></div>
                </div>
            </section>
            <?php
            break;

        case 'day-tours':
            ?>
            <section class="new-home-block new-home-day-tours" aria-labelledby="home-day-tours-title">
                <div class="container">
                    <div class="new-home-section-head">
                        <div>
                            <span class="new-home-section-label">Day Tours</span>
                            <h2 id="home-day-tours-title">One-Day Adventures</h2>
                            <p>Flexible day tours from your base — each designed to fit one day with a private driver.</p>
                        </div>
                        <a href="<?= e(base_url('day-tours')) ?>" class="new-home-inline-link">View all day tours &rarr;</a>
                    </div>
                    <div class="new-home-rail-wrap">
                        <button class="new-home-carousel-arrow new-home-carousel-prev" type="button" data-rail-prev="day-tours" aria-label="Previous day tours">&#8249;</button>
                        <div class="new-home-rail new-home-rail-day-tours" data-rail-track="day-tours">
                        <?php foreach ($dayTourCards as $card): ?>
                            <article class="new-home-day-tour-card">
                                <a href="<?= e($card['url']) ?>" class="new-home-daytour-thumb">
                                    <?= responsive_image([
                                        'src' => $card['image'],
                                        'alt' => $card['image_alt'],
                                        'widths' => [400, 800, 1200],
                                        'sizes' => '(min-width: 1200px) 380px, (min-width: 768px) 45vw, 94vw',
                                        'width' => 1200,
                                        'height' => 1350,
                                        'class' => 'new-home-cover',
                                    ]) ?>
                                    <span class="new-home-destination-shade"></span>
                                    <div class="new-home-daytour-copy">
                                        <h3><?= e($card['name']) ?></h3>
                                        <span class="new-home-daytour-meta">
                                            <?php if ($card['duration'] !== ''): ?>
                                                <span><?= e($card['duration']) ?></span>
                                            <?php endif; ?>
                                            <?php if ($card['price'] !== ''): ?>
                                                <span><?= e($card['price']) ?></span>
                                            <?php endif; ?>
                                        </span>
                                    </div>
                                </a>
                            </article>
                        <?php endforeach; ?>
                            <article class="new-home-collection-card">
                                <a href="<?= e(base_url('day-tours')) ?>" class="new-home-collection-tile">
                                    <span class="new-home-collection-kicker">All day tours</span>
                                    <strong>See All Day Tours</strong>
                                    <span>Browse every available day tour and add-on.</span>
                                    <em>Explore now &rarr;</em>
                                </a>
                            </article>
                        </div>
                        <button class="new-home-carousel-arrow new-home-carousel-next" type="button" data-rail-next="day-tours" aria-label="Next day tours">&#8250;</button>
                    </div>
                    <div class="new-home-carousel-dots" data-rail-dots="day-tours"></div>
                </div>
            </section>
            <?php
            break;

        case 'destinations':
            if ($destinationCards === []) {
                break;
            }
            ?>
            <section class="new-home-block new-home-destinations" aria-labelledby="home-destinations-title">
                <div class="container">
                    <div class="new-home-destinations-heading">
                        <h2 id="home-destinations-title">Where will you<br><span>find yourself?</span></h2>
                        <a href="<?= e(base_url('destinations')) ?>" class="new-home-destinations-link">
                            Discover the island
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 18 18 6M6 6h12v12"/></svg>
                        </a>
                    </div>
                    <div class="new-home-destinations-grid">
                        <?php foreach (array_slice($destinationCards, 0, 4) as $cardIndex => $card): ?>
                            <article class="new-home-destinations-card new-home-destinations-card--<?= (int) $cardIndex ?>">
                                <a href="<?= e($card['url']) ?>">
                                    <div class="new-home-destinations-image">
                                        <?= responsive_image([
                                            'src' => $card['image'],
                                            'alt' => $card['name'],
                                            'widths' => [400, 800, 1200],
                                            'sizes' => $cardIndex === 0 || $cardIndex === 3
                                                ? '(max-width: 640px) 90vw, (max-width: 1024px) 45vw, ' . ($cardIndex === 0 ? '40vw' : '60vw')
                                                : '(max-width: 1024px) 45vw, 30vw',
                                            'width' => 1200,
                                            'height' => $cardIndex === 0 ? 1400 : 700,
                                        ]) ?>
                                        <span class="new-home-destinations-action" aria-hidden="true">
                                            <svg viewBox="0 0 24 24"><path d="M6 18 18 6M6 6h12v12"/></svg>
                                        </span>
                                    </div>
                                    <div class="new-home-destinations-copy">
                                        <p><?= e($card['label']) ?></p>
                                        <h3><?= e($card['name']) ?></h3>
                                    </div>
                                </a>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>
            <?php
            break;

        case 'categories':
            ?>
            <section class="new-home-block new-home-categories-block">
                <div class="container">
                    <div class="new-home-section-head">
                        <div>
                            <span class="new-home-section-label">Choose your style</span>
                            <h2>A journey for every kind of traveler</h2>
                            <p>Start with the kind of journey you want, then shape the route around your dates, pace, and interests.</p>
                        </div>
                        <a href="<?= e(base_url('itineraries')) ?>" class="new-home-inline-link">Browse all itineraries &rarr;</a>
                    </div>
                    <?php if ($categoryCards): ?>
                        <div class="new-home-category-grid">
                            <?php foreach ($categoryCards as $category): ?>
                                <a class="new-home-category-card" href="<?= e($category['url']) ?>">
                                    <span class="new-home-category-icon"><?= $iconMarkup($category['icon']) ?></span>
                                    <span class="new-home-category-copy">
                                        <strong><?= e($category['name']) ?></strong>
                                        <span class="new-home-category-description"><?= e($category['description']) ?></span>
                                        <span class="new-home-category-meta"><?= e($category['cta']) ?> <span aria-hidden="true">&nearr;</span></span>
                                    </span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="new-home-empty-state">Tour categories are being refreshed. Browse the full itinerary collection while we update the menu.</div>
                    <?php endif; ?>
                </div>
            </section>
            <?php
            break;

        case 'approach':
            ?>
            <section class="new-home-block new-home-approach-block">
                <div class="container">
                    <div class="new-home-section-head new-home-section-head-stack">
                        <div>
                            <span class="new-home-section-label">Our approach</span>
                            <h2>Why Travel With Island Culture Travels?</h2>
                        </div>
                    </div>
                    <div class="new-home-approach-grid">
                        <?php foreach ($approachItems as $item): ?>
                            <article class="new-home-approach-item">
                                <span class="new-home-approach-icon"><?= $iconMarkup((string) $item['icon']) ?></span>
                                <h3><?= e($item['title']) ?></h3>
                                <p><?= e($item['text']) ?></p>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>
            <?php
            break;

        case 'reviews':
            ?>
            <section class="new-home-block new-home-reviews-block" aria-labelledby="home-reviews-title">
                <div class="container">
                    <div class="section-head new-home-section-head">
                        <div>
                            <span class="section-eyebrow new-home-section-label">What guests say</span>
                            <h2 class="section-title" id="home-reviews-title">Journeys remembered.<br>Stories shared.</h2>
                            <p>A little of Sri Lanka, through the eyes of the people who traveled with us.</p>
                        </div>
                        <a href="<?= e(base_url('reviews')) ?>" class="section-link new-home-inline-link">View all reviews &rarr;</a>
                    </div>
                    <?php if ($reviewCards): ?>
                    <div class="new-home-testimonial-shell">
                        <div class="new-home-testimonial-rail" data-review-carousel id="home-review-cards" tabindex="0" aria-label="Guest reviews">
                        <?php foreach ($reviewCards as $card): ?>
                            <article class="review-card new-home-testimonial-card">
                                <div class="new-home-review-topline">
                                    <span class="review-stars new-home-review-stars" aria-label="<?= (int) $card['rating'] ?> out of 5 stars"><?= str_repeat('&#9733;', (int) $card['rating']) ?></span>
                                    <span class="new-home-review-quote" aria-hidden="true">&ldquo;</span>
                                </div>
                                <?php if ($card['title'] !== ''): ?><h3 class="new-home-review-title"><?= e($card['title']) ?></h3><?php endif; ?>
                                <blockquote><p class="review-text"><?= e($card['text']) ?></p></blockquote>
                                <div class="review-author new-home-testimonial-author">
                                    <div class="review-avatar new-home-testimonial-avatar"><?= e(strtoupper(substr((string) $card['name'], 0, 1))) ?></div>
                                    <div>
                                        <strong class="review-name"><?= e($card['name']) ?></strong>
                                        <span class="review-type"><?= e(trim((string) ($card['type'] . ($card['country'] !== '' ? ' | ' . $card['country'] : '')))) ?></span>
                                        <?php if ($card['date'] !== ''): ?><span class="new-home-review-meta"><?= e($card['date']) ?></span><?php endif; ?>
                                    </div>
                                </div>
                                <?php if ($card['source'] !== ''): ?>
                                    <div class="new-home-review-source">
                                        <?php if ($card['source_url'] !== ''): ?><a href="<?= e($card['source_url']) ?>" target="_blank" rel="noopener">Read on <?= e($card['source']) ?> <span aria-hidden="true">&nearr;</span></a>
                                        <?php else: ?><span><?= e($card['source']) ?></span><?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </article>
                        <?php endforeach; ?>
                        </div>
                        <div class="new-home-review-controls">
                            <span>More guest stories</span>
                            <button class="new-home-testimonial-arrow" type="button" data-review-prev aria-controls="home-review-cards" aria-label="Previous reviews">&#8592;</button>
                            <button class="new-home-testimonial-arrow" type="button" data-review-next aria-controls="home-review-cards" aria-label="Next reviews">&#8594;</button>
                        </div>
                    </div>
                    <?php else: ?>
                        <div class="new-home-empty-state">Discover guest stories and feedback from journeys around Sri Lanka. <a href="<?= e(base_url('reviews')) ?>">Explore traveler reviews &rarr;</a></div>
                    <?php endif; ?>
                </div>
            </section>
            <?php
            break;

        case 'cta':
            ?>
            <section class="new-home-block new-home-cta-band-wrap">
                <div class="container">
                    <h2><?= e($ctaHeading) ?></h2>
                    <p><?= e($ctaBody) ?></p>
                    <div class="new-home-cta-actions">
                        <a href="<?= e(base_url($ctaButtonUrl)) ?>" class="btn btn-accent"><?= e($ctaButtonLabel) ?> &rarr;</a>
                        <a href="<?= e(site_whatsapp_url(site_default_whatsapp_text())) ?>" target="_blank" rel="noopener" class="btn btn-whatsapp">WhatsApp Us</a>
                    </div>
                </div>
            </section>
            <?php
            break;
    }
};
$bodyClass = 'page-home-new page-home-new-exact';
$extraStylesheets = ['assets/css/new-home.css'];
require __DIR__ . '/includes/header.php';
?>

<div class="new-home-main">
    <section class="new-home-hero-exact">
        <?php if ($heroMediaType === 'video' && $heroVideoUrl !== ''): ?>
            <video class="new-home-hero-bg" autoplay muted loop playsinline preload="metadata" poster="<?= e($heroImage) ?>">
                <source src="<?= e($heroVideoUrl) ?>" type="<?= str_ends_with(strtolower($heroVideoUrl), '.webm') ? 'video/webm' : 'video/mp4' ?>">
            </video>
        <?php else: ?>
            <?= responsive_image([
                'src' => $heroImage,
                'alt' => 'Sri Lanka coastline hero scene',
                'widths' => [800, 1200, 1600],
                'sizes' => '100vw',
                'width' => 1600,
                'height' => 900,
                'class' => 'new-home-hero-bg',
                'loading' => 'eager',
                'fetchpriority' => 'high',
            ]) ?>
        <?php endif; ?>
        <div class="new-home-hero-overlay"></div>
        <div class="container new-home-hero-content">
            <div class="new-home-hero-copy">
                <?php if ($heroScript !== ''): ?>
                    <span class="new-home-script"><?= e($heroScript) ?></span>
                <?php endif; ?>
                <h1><?= nl2br(e($heroTitle)) ?></h1>
                <p><?= e($heroSupportingText) ?></p>
                <div class="new-home-hero-actions">
                    <?php if ($heroPrimaryLabel !== ''): ?>
                        <a href="<?= e(base_url($heroPrimaryUrl)) ?>" class="btn btn-primary new-home-hero-btn new-home-hero-btn-primary"><?= e($heroPrimaryLabel) ?></a>
                    <?php endif; ?>
                    <?php if ($heroSecondaryLabel !== ''): ?>
                        <a href="<?= e(base_url($heroSecondaryUrl)) ?>" class="btn btn-ghost new-home-hero-btn new-home-hero-btn-secondary"><?= e($heroSecondaryLabel) ?></a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>
    <?php foreach ($homepageActiveSections as $sectionKey): ?>
        <?php $renderHomepageSection($sectionKey); ?>
    <?php endforeach; ?>
<script>
window.addEventListener('DOMContentLoaded', function () {
    var initRail = function (name, desktopPerView) {
        var track = document.querySelector('[data-rail-track="' + name + '"]');
        var prev = document.querySelector('[data-rail-prev="' + name + '"]');
        var next = document.querySelector('[data-rail-next="' + name + '"]');
        var dotsWrap = document.querySelector('[data-rail-dots="' + name + '"]');
        if (!track || !prev || !next || !dotsWrap) return;

        var cards = Array.prototype.slice.call(track.children);
        if (!cards.length) return;

        var getStep = function () {
            var first = cards[0];
            var style = window.getComputedStyle(track);
            var gap = parseFloat(style.columnGap || style.gap || '0') || 0;
            return first.getBoundingClientRect().width + gap;
        };

        var perView = function () {
            if (window.innerWidth >= 1200) return desktopPerView;
            if (window.innerWidth >= 768) return 2;
            return 1;
        };

        var pageCount = function () {
            return Math.max(1, Math.ceil(cards.length / perView()));
        };

        var currentPage = function () {
            var step = getStep() * perView();
            return step > 0 ? Math.round(track.scrollLeft / step) : 0;
        };

        var renderDots = function () {
            dotsWrap.innerHTML = '';
            for (var i = 0; i < pageCount(); i += 1) {
                var dot = document.createElement('button');
                dot.type = 'button';
                dot.className = 'new-home-dot' + (i === currentPage() ? ' is-active' : '');
                dot.setAttribute('aria-label', 'Go to slide ' + (i + 1));
                dot.addEventListener('click', function (index) {
                    return function () {
                        track.scrollTo({ left: getStep() * perView() * index, behavior: 'smooth' });
                    };
                }(i));
                dotsWrap.appendChild(dot);
            }
        };

        var updateDots = function () {
            var dots = dotsWrap.querySelectorAll('.new-home-dot');
            dots.forEach(function (dot, index) {
                dot.classList.toggle('is-active', index === currentPage());
            });
        };

        prev.addEventListener('click', function () {
            track.scrollBy({ left: -(getStep() * perView()), behavior: 'smooth' });
        });

        next.addEventListener('click', function () {
            track.scrollBy({ left: getStep() * perView(), behavior: 'smooth' });
        });

        track.addEventListener('scroll', function () {
            window.requestAnimationFrame(updateDots);
        });

        window.addEventListener('resize', renderDots);
        renderDots();
    };

    initRail('itineraries', 4);
    initRail('experiences', 4);
    initRail('day-tours', 3);

    var reviewTrack = document.querySelector('[data-review-carousel]');
    var reviewPrev = document.querySelector('[data-review-prev]');
    var reviewNext = document.querySelector('[data-review-next]');
    if (reviewTrack && reviewPrev && reviewNext) {
        var reviewStep = function () {
            var card = reviewTrack.querySelector('.new-home-testimonial-card');
            if (!card) return 0;
            var style = window.getComputedStyle(reviewTrack);
            var gap = parseFloat(style.columnGap || style.gap || '0') || 0;
            return card.getBoundingClientRect().width + gap;
        };
        reviewPrev.addEventListener('click', function () {
            reviewTrack.scrollBy({ left: -reviewStep(), behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth' });
        });
        reviewNext.addEventListener('click', function () {
            reviewTrack.scrollBy({ left: reviewStep(), behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth' });
        });
        var updateReviewControls = function () {
            var maxScroll = reviewTrack.scrollWidth - reviewTrack.clientWidth;
            reviewNext.parentElement.hidden = maxScroll <= 2;
            reviewPrev.disabled = reviewTrack.scrollLeft <= 2;
            reviewNext.disabled = reviewTrack.scrollLeft >= maxScroll - 2;
        };
        reviewTrack.addEventListener('scroll', updateReviewControls, { passive: true });
        window.addEventListener('resize', updateReviewControls);
        updateReviewControls();
    }
});
</script>
</div>
<?php
schema_output($schemaGraph);
require __DIR__ . '/includes/footer.php';
