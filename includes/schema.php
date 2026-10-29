<?php
declare(strict_types=1);

/**
 * Schema helpers
 */

function schema_output(array $graph): void
{
    if (!$graph) {
        return;
    }

    echo '<script type="application/ld+json">' . json_encode([
        '@context' => 'https://schema.org',
        '@graph'   => array_values($graph),
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>';
}

function schema_website(): array
{
    return [
        '@type' => 'WebSite',
        '@id'   => base_url() . '#website',
        'name'  => APP_NAME,
        'url'   => base_url(),
        'inLanguage' => 'en',
        'publisher' => [
            '@id' => base_url() . '#business',
        ],
    ];
}

function schema_travel_agency(): array
{
    $sameAs = [];
    foreach (fetch_active_social_links() as $social) {
        $url = trim((string) ($social['url'] ?? ''));
        if ($url !== '' && filter_var($url, FILTER_VALIDATE_URL)) {
            $sameAs[] = $url;
        }
    }

    $schema = [
        '@type' => 'TravelAgency',
        '@id'   => base_url() . '#business',
        'name'  => APP_NAME,
        'url'   => base_url(),
        'logo'  => base_url('island_culture_travels_logo_banner.png'),
        'image' => DEFAULT_OG_IMAGE,
        'description' => 'Private chauffeur-guided Sri Lanka tours with comfortable vehicles, custom itineraries, family tours, safari tours, airport transfers, and local travel planning.',
        'telephone' => site_primary_phone(),
        'email' => site_contact_email(),
        'address' => [
            '@type' => 'PostalAddress',
            'streetAddress' => '163/3 Niwasa Udyana Mawatha',
            'addressLocality' => 'Raddolugama (Katunayake)',
            'postalCode' => '11390',
            'addressCountry' => 'LK',
        ],
        'areaServed' => [
            '@type' => 'Country',
            'name' => 'Sri Lanka',
        ],
        'knowsAbout' => [
            'Sri Lanka private tours',
            'Sri Lanka tour operator',
            'Chauffeur-guided tours',
            'Custom Sri Lanka itineraries',
            'Sri Lanka airport transfers',
        ],
    ];

    if ($sameAs) {
        $schema['sameAs'] = array_values(array_unique($sameAs));
    }

    return $schema;
}

function schema_breadcrumbs(array $items): array
{
    $list = [];
    $position = 1;

    foreach ($items as $item) {
        $entry = [
            '@type'    => 'ListItem',
            'position' => $position,
            'name'     => $item['name'] ?? '',
        ];

        if (!empty($item['url'])) {
            $entry['item'] = $item['url'];
        }

        $list[] = $entry;
        $position++;
    }

    return [
        '@type' => 'BreadcrumbList',
        'itemListElement' => $list,
    ];
}

function schema_faq_from_json(?string $faqJson): ?array
{
    if (!$faqJson) {
        return null;
    }

    $items = json_decode($faqJson, true);
    if (!is_array($items) || !$items) {
        return null;
    }

    return schema_faq_from_items($items);
}

function schema_faq_from_items(array $items): ?array
{
    $entities = [];
    foreach ($items as $item) {
        if (!is_array($item)) {
            continue;
        }

        $question = trim((string) ($item['question'] ?? ''));
        $answer = trim((string) ($item['answer'] ?? ''));

        if ($question === '' || $answer === '') {
            continue;
        }

        $entities[] = [
            '@type' => 'Question',
            'name' => $question,
            'acceptedAnswer' => [
                '@type' => 'Answer',
                'text' => $answer,
            ],
        ];
    }

    if (!$entities) {
        return null;
    }

    return [
        '@type' => 'FAQPage',
        'mainEntity' => $entities,
    ];
}

function schema_destination(array $destination, string $url): array
{
    $schema = [
        '@type' => 'TouristDestination',
        'name'  => $destination['name'] ?? '',
        'url'   => $url,
        'description' => $destination['meta_description'] ?: ($destination['excerpt'] ?: ''),
        'image' => !empty($destination['og_image'])
            ? upload_url($destination['og_image'])
            : (!empty($destination['cover_image']) ? upload_url($destination['cover_image']) : DEFAULT_OG_IMAGE),
    ];

    $province = trim((string) ($destination['province'] ?? ''));
    if ($province !== '') {
        $schema['containedInPlace'] = [
            '@type' => 'AdministrativeArea',
            'name'  => $province,
        ];
    }

    $lat = isset($destination['latitude']) ? (float) $destination['latitude'] : 0.0;
    $lng = isset($destination['longitude']) ? (float) $destination['longitude'] : 0.0;
    if ($lat !== 0.0 && $lng !== 0.0) {
        $schema['geo'] = [
            '@type'     => 'GeoCoordinates',
            'latitude'  => $lat,
            'longitude' => $lng,
        ];
    }

    if (!empty($destination['best_time_json'])) {
        $decoded = json_decode((string) $destination['best_time_json'], true);
        $months = [];
        if (is_array($decoded)) {
            $months = isset($decoded['months']) && is_array($decoded['months'])
                ? $decoded['months']
                : (array_is_list($decoded) ? $decoded : []);
        }
        if ($months) {
            $schema['additionalProperty'] = [
                '@type' => 'PropertyValue',
                'name'  => 'bestTimeToVisit',
                'value' => implode(', ', array_map('ucfirst', array_map('strval', $months))),
            ];
        }
    }

    return $schema;
}

function schema_service(array $tour, string $url): array
{
    $schema = [
        '@type' => 'Service',
        'name'  => $tour['name'] ?? '',
        'url'   => $url,
        'description' => $tour['meta_description'] ?: ($tour['excerpt'] ?: ''),
        'provider' => [
            '@type' => 'TravelAgency',
            '@id'   => base_url() . '#business',
            'name'  => APP_NAME,
            'url'   => base_url(),
        ],
        'image' => !empty($tour['og_image']) ? upload_url($tour['og_image']) : DEFAULT_OG_IMAGE,
    ];

    return $schema;
}

function schema_trip(array $itinerary, string $url): array
{
    $images = [];
    if (!empty($itinerary['og_image'])) {
        $images[] = upload_url((string) $itinerary['og_image']);
    }
    if (!empty($itinerary['day_images']) && is_array($itinerary['day_images'])) {
        foreach ($itinerary['day_images'] as $dayImage) {
            $dayImage = trim((string) $dayImage);
            if ($dayImage !== '') {
                $images[] = $dayImage;
            }
        }
    }
    $images = array_values(array_unique($images));

    $schema = [
        '@type' => 'TouristTrip',
        'name'  => $itinerary['name'] ?? '',
        'url'   => $url,
        'description' => $itinerary['meta_description'] ?: ($itinerary['excerpt'] ?: ''),
        'image' => $images ?: [DEFAULT_OG_IMAGE],
        'provider' => [
            '@type' => 'TravelAgency',
            '@id'   => base_url() . '#business',
            'name'  => APP_NAME,
            'url'   => base_url(),
        ],
    ];

    // Add Offer (price) if pricing data is available
    $priceFrom = (float) ($itinerary['price_from'] ?? $itinerary['base_price'] ?? 0);
    $priceTo   = (float) ($itinerary['price_to']   ?? $itinerary['price_upto'] ?? 0);
    $currency  = (string) ($itinerary['price_currency'] ?? $itinerary['currency'] ?? 'USD');
    if ($priceFrom > 0) {
        $offer = [
            '@type'         => 'Offer',
            'url'           => $url,
            'priceCurrency' => $currency !== '' ? $currency : 'USD',
            'price'         => number_format($priceFrom, 2, '.', ''),
            'availability'  => 'https://schema.org/InStock',
        ];
        if ($priceTo > $priceFrom) {
            $offer['@type']    = 'AggregateOffer';
            $offer['lowPrice'] = number_format($priceFrom, 2, '.', '');
            $offer['highPrice'] = number_format($priceTo, 2, '.', '');
            unset($offer['price']);
        }
        $schema['offers'] = $offer;
    }

    $rating = fetch_review_aggregate_for_trip((string) ($itinerary['name'] ?? ''));
    if ($rating) {
        $schema['aggregateRating'] = $rating;
    }

    return $schema;
}

function schema_trip_from_tour(array $tour, string $url): array
{
    $node = [
        '@type' => 'TouristTrip',
        'name' => $tour['name'] ?? '',
        'url' => $url,
        'description' => $tour['meta_description'] ?: ($tour['excerpt'] ?: ''),
        'image' => !empty($tour['og_image']) ? upload_url($tour['og_image']) : DEFAULT_OG_IMAGE,
        'provider' => [
            '@type' => 'TravelAgency',
            '@id'   => base_url() . '#business',
            'name'  => APP_NAME,
            'url'   => base_url(),
        ],
    ];

    $rating = fetch_review_aggregate_for_trip((string) ($tour['name'] ?? ''));
    if ($rating) {
        $node['aggregateRating'] = $rating;
    }

    return $node;
}

function schema_business_partner(array $partner, string $url, array $photos = []): array
{
    $type = strtolower(trim((string) ($partner['business_type'] ?? 'hotel')));
    $schemaType = match ($type) {
        'hotel', 'resort' => 'Hotel',
        'villa', 'guesthouse', 'boutique-stay', 'homestay' => 'LodgingBusiness',
        default => 'LocalBusiness',
    };

    $images = [];
    if (!empty($partner['og_image'])) {
        $images[] = stored_media_url((string) $partner['og_image']);
    }
    if (!empty($partner['hero_image'])) {
        $images[] = stored_media_url((string) $partner['hero_image']);
    }
    foreach ($photos as $photo) {
        if (!empty($photo['image_path'])) {
            $images[] = stored_media_url((string) $photo['image_path']);
        }
    }
    $images = array_values(array_unique(array_filter($images)));

    $schema = [
        '@type' => $schemaType,
        'name' => (string) ($partner['business_name'] ?? ''),
        'url' => $url,
        'description' => trim((string) ($partner['meta_description'] ?? $partner['short_description'] ?? '')),
        'image' => $images ?: [DEFAULT_OG_IMAGE],
    ];

    $location = trim((string) ($partner['location'] ?? ''));
    if ($location !== '') {
        $schema['address'] = [
            '@type' => 'PostalAddress',
            'addressLocality' => $location,
            'addressCountry' => 'LK',
        ];
    }

    $phone = trim((string) ($partner['phone'] ?? $partner['whatsapp'] ?? ''));
    if ($phone !== '') {
        $schema['telephone'] = $phone;
    }

    $website = trim((string) ($partner['website_url'] ?? ''));
    if ($website !== '' && filter_var($website, FILTER_VALIDATE_URL)) {
        $schema['sameAs'] = [$website];
    }

    return $schema;
}

/**
 * ItemList schema for index/listing pages.
 * Each $item: ['url' => '...', 'name' => '...', 'image' => '...' (optional)].
 */
function schema_item_list(string $name, array $items): array
{
    $list = [];
    $position = 1;
    foreach ($items as $item) {
        if (!is_array($item)) {
            continue;
        }
        $entry = [
            '@type'    => 'ListItem',
            'position' => $position,
            'url'      => (string) ($item['url'] ?? ''),
            'name'     => (string) ($item['name'] ?? ''),
        ];
        if (!empty($item['image'])) {
            $entry['image'] = (string) $item['image'];
        }
        $list[] = $entry;
        $position++;
    }

    return [
        '@type' => 'ItemList',
        'name'  => $name,
        'itemListElement' => $list,
    ];
}

function schema_article(array $article, string $url, string $type = 'Article'): array
{
    $headline = trim((string) ($article['title'] ?? $article['name'] ?? ''));
    $description = trim((string) ($article['meta_description'] ?? $article['excerpt'] ?? ''));
    $image = trim((string) ($article['image'] ?? $article['og_image'] ?? $article['hero_image'] ?? ''));
    $datePublished = trim((string) ($article['published_at'] ?? $article['created_at'] ?? ''));
    $dateModified = trim((string) ($article['updated_at'] ?? $article['published_at'] ?? $article['created_at'] ?? ''));
    $author = trim((string) ($article['author'] ?? APP_NAME));

    $schema = [
        '@type' => $type,
        'headline' => $headline,
        'url' => $url,
        'mainEntityOfPage' => [
            '@type' => 'WebPage',
            '@id' => $url,
        ],
        'publisher' => [
            '@id' => base_url() . '#business',
        ],
        'author' => [
            '@type' => $author === APP_NAME ? 'Organization' : 'Person',
            'name' => $author,
        ],
        'inLanguage' => 'en',
    ];

    if ($description !== '') {
        $schema['description'] = $description;
    }

    if ($image !== '') {
        $schema['image'] = preg_match('~^https?://~i', $image) ? $image : upload_url($image);
    } else {
        $schema['image'] = DEFAULT_OG_IMAGE;
    }

    $publishedTs = $datePublished !== '' ? strtotime($datePublished) : false;
    if ($publishedTs) {
        $schema['datePublished'] = date('c', $publishedTs);
    }

    $modifiedTs = $dateModified !== '' ? strtotime($dateModified) : false;
    if ($modifiedTs) {
        $schema['dateModified'] = date('c', $modifiedTs);
    }

    if (!empty($article['about']) && is_array($article['about'])) {
        $schema['about'] = array_values(array_filter(array_map('strval', $article['about'])));
    }

    if (!empty($article['mentions']) && is_array($article['mentions'])) {
        $schema['mentions'] = array_values(array_filter(array_map('strval', $article['mentions'])));
    }

    return $schema;
}

/**
 * Single Review schema node.
 * $review: ['author','rating','text','date_iso','tour_name'(optional)].
 */
function schema_review(array $review): array
{
    $node = [
        '@type'  => 'Review',
        'author' => [
            '@type' => 'Person',
            'name'  => trim((string) ($review['author'] ?? 'Anonymous')),
        ],
        'reviewBody' => trim((string) ($review['text'] ?? $review['review_text'] ?? '')),
    ];
    $rating = (float) ($review['rating'] ?? 0);
    if ($rating > 0) {
        $node['reviewRating'] = [
            '@type'       => 'Rating',
            'ratingValue' => number_format($rating, 1, '.', ''),
            'bestRating'  => 5,
            'worstRating' => 1,
        ];
    }
    if (!empty($review['date_iso'])) {
        $node['datePublished'] = (string) $review['date_iso'];
    } elseif (!empty($review['created_at'])) {
        $ts = strtotime((string) $review['created_at']);
        if ($ts) {
            $node['datePublished'] = date('c', $ts);
        }
    }
    if (!empty($review['tour_name'])) {
        $node['itemReviewed'] = [
            '@type' => 'TouristTrip',
            'name'  => (string) $review['tour_name'],
        ];
    }
    return $node;
}

/**
 * Place schema for destinations.
 * Adds geo (when latitude/longitude present), touristType, and a
 * touristSeason / Specialty rendering of the bestTimeToVisit when supplied.
 */
function schema_place(array $destination, string $url): array
{
    $place = [
        '@type' => 'Place',
        'name'  => (string) ($destination['name'] ?? ''),
        'url'   => $url,
        'description' => trim((string) ($destination['meta_description'] ?? $destination['excerpt'] ?? '')),
        'image' => !empty($destination['og_image'])
            ? upload_url($destination['og_image'])
            : (!empty($destination['cover_image']) ? upload_url($destination['cover_image']) : DEFAULT_OG_IMAGE),
    ];

    $province = trim((string) ($destination['province'] ?? ''));
    if ($province !== '') {
        $place['containedInPlace'] = [
            '@type' => 'AdministrativeArea',
            'name'  => $province,
        ];
    }

    $lat = isset($destination['latitude']) ? (float) $destination['latitude'] : 0.0;
    $lng = isset($destination['longitude']) ? (float) $destination['longitude'] : 0.0;
    if ($lat !== 0.0 && $lng !== 0.0) {
        $place['geo'] = [
            '@type'     => 'GeoCoordinates',
            'latitude'  => $lat,
            'longitude' => $lng,
        ];
    }

    if (!empty($destination['best_time_json'])) {
        $decoded = json_decode((string) $destination['best_time_json'], true);
        $months = [];
        if (is_array($decoded)) {
            $months = isset($decoded['months']) && is_array($decoded['months'])
                ? $decoded['months']
                : (array_is_list($decoded) ? $decoded : []);
        }
        if ($months) {
            $place['additionalProperty'] = [
                '@type' => 'PropertyValue',
                'name'  => 'bestTimeToVisit',
                'value' => implode(', ', array_map('ucfirst', array_map('strval', $months))),
            ];
        }
    }

    return $place;
}

function schema_experience(array $experience, string $url): array
{
    $images = [];
    $image = content_image_path($experience, ['hero_image','og_image']);
    if ($image !== '') {
        $images[] = stored_media_url($image);
    }

    $node = [
        '@type' => 'TouristAttraction',
        'name'  => $experience['title'] ?? '',
        'url'   => $url,
        'description' => $experience['meta_description'] ?: ($experience['short_description'] ?? ''),
        'image' => $images ?: [DEFAULT_OG_IMAGE],
        'provider' => [
            '@id'   => base_url() . '#business',
        ],
    ];

    $rating = fetch_review_aggregate_for_trip((string) ($experience['title'] ?? ''));
    if ($rating) {
        $node['aggregateRating'] = $rating;
    }

    return $node;
}

/**
 * Fetch aggregate review data (avg rating + count) for a tour/experience
 * by its name. Returns null if no published reviews exist, so callers can
 * safely use it in conditional schema nodes.
 *
 * Returns: ['@type' => 'AggregateRating', 'ratingValue' => 4.8, 'reviewCount' => 24]
 */
function fetch_review_aggregate_for_trip(string $tripName): ?array
{
    $tripName = trim($tripName);
    if ($tripName === '' || !function_exists('ensure_reviews_schema')) {
        return null;
    }
    if (!db_table_exists('reviews')) {
        return null;
    }

    try {
        ensure_reviews_schema();
        $row = fetch_one(
            "SELECT AVG(rating) AS avg_rating, COUNT(*) AS review_count
             FROM reviews
             WHERE is_published = 1 AND trip_type = :trip",
            ['trip' => $tripName]
        );
    } catch (Throwable) {
        return null;
    }

    if (!$row || (int) ($row['review_count'] ?? 0) === 0) {
        return null;
    }

    return [
        '@type'       => 'AggregateRating',
        'ratingValue' => round((float) $row['avg_rating'], 1),
        'reviewCount' => (int) $row['review_count'],
        'bestRating'  => 5,
        'worstRating' => 1,
    ];
}

