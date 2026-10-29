<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';

ensure_experiences_schema();

$experiences = [
    [
        'title' => 'Wildlife Safaris',
        'slug' => 'wildlife-safaris',
        'excerpt' => 'Track elephants, leopards, crocodiles, birds, and open-country wildlife with private vehicle routing that keeps safari days practical.',
        'intro' => '<p>Sri Lanka is one of Asia\'s strongest wildlife destinations for private travelers. The best safari experience is not only about entering a park; it is about choosing the right park, timing the game drives well, and building enough breathing room into the route.</p><p>We use wildlife experiences as route anchors for families, photographers, first-time visitors, and nature-focused travelers who want the excitement of safari without turning the whole trip into a rush.</p>',
        'where_to_experience' => '<p>Yala is best known for leopard-focused safaris and dry-zone landscapes, while Udawalawe is one of the most reliable places to see elephants. Both work well as part of a south-coast or wildlife-focused itinerary.</p>',
        'best_time' => 'Morning and late-afternoon drives are usually best for wildlife activity. Dry-season months can improve visibility around water sources, while shoulder months may feel quieter and greener.',
        'tips' => ['Start early and avoid overpacking the safari day', 'Carry sun protection and a light layer for morning drives', 'Bring binoculars if birdlife matters to you', 'Do not plan a long road transfer immediately after every safari'],
        'destinations' => ['yala', 'udawalawe'],
        'itineraries' => ['sri-lanka-wildlife-safari-itinerary-7-days'],
        'hero_image' => 'assets/images/yala-national-park-leopard-safari-large.webp',
        'og_image' => 'assets/images/yala-national-park-lk.avif',
        'featured' => 1,
        'sort' => 10,
    ],
    [
        'title' => 'Ancient Culture and Temples',
        'slug' => 'ancient-culture-and-temples',
        'excerpt' => 'Explore rock fortresses, sacred sites, old kingdoms, cave temples, and living Buddhist culture with enough time to understand the setting.',
        'intro' => '<p>Sri Lanka\'s cultural sites are most rewarding when they are paced properly. Instead of treating every temple or ruin as a quick photo stop, we plan cultural days around light, heat, crowd flow, and the natural rhythm of the route.</p><p>This experience is ideal for travelers who want history, architecture, religion, local stories, and scenic cultural landscapes woven into a private itinerary.</p>',
        'where_to_experience' => '<p>Sigiriya is one of the strongest cultural anchors for a first Sri Lanka trip. It pairs well with Dambulla, Polonnaruwa, village routes, and hill country transfers depending on the number of travel days.</p>',
        'best_time' => 'Early morning is usually the most comfortable time for climbs and exposed archaeological sites. Modest clothing is useful for temple visits.',
        'tips' => ['Start major climbs early', 'Carry socks for hot temple floors', 'Respect dress codes at sacred sites', 'Leave time for smaller cultural stops between headline attractions'],
        'destinations' => ['sigiriya'],
        'itineraries' => [],
        'hero_image' => 'assets/images/kandy-temple-lk.jpg',
        'og_image' => 'assets/images/kandy-temple-lk.jpg',
        'featured' => 1,
        'sort' => 20,
    ],
    [
        'title' => 'Scenic Train Journeys',
        'slug' => 'scenic-train-journeys',
        'excerpt' => 'Add Sri Lanka\'s hill-country rail scenery to a private route without losing the comfort and flexibility of chauffeur-guided travel.',
        'intro' => '<p>The train through Sri Lanka\'s tea country is famous for misty hills, valleys, small stations, and slow travel atmosphere. The trick is fitting it into the route without making luggage, timing, or onward transfers stressful.</p><p>We usually combine private transport with selected train sectors, so you enjoy the scenic part while your wider itinerary still moves smoothly.</p>',
        'where_to_experience' => '<p>The most popular rail sectors are in the central hills, especially around Kandy, Nanu Oya, Haputale, Ella, and Badulla. Availability and comfort depend on the season and seat class.</p>',
        'best_time' => 'Train views are beautiful year-round, but mist and rain can change visibility in the hills. Booking ahead is strongly recommended for reserved seats.',
        'tips' => ['Book reserved seats where possible', 'Keep valuables and documents with you', 'Use private transport before and after the rail sector', 'Avoid planning tight same-day connections around train arrival times'],
        'destinations' => [],
        'itineraries' => [],
        'hero_image' => 'assets/images/nine-arch-bridge-lk.jpg',
        'og_image' => 'assets/images/nine-arch-bridge-lk.jpg',
        'featured' => 1,
        'sort' => 30,
    ],
    [
        'title' => 'Tea Country and Plantations',
        'slug' => 'tea-country-and-plantations',
        'excerpt' => 'Slow down in Sri Lanka\'s hill country with tea estates, viewpoints, waterfalls, cool weather, and colonial-era mountain towns.',
        'intro' => '<p>Tea country gives a Sri Lanka itinerary a completely different mood: cooler air, green slopes, winding roads, waterfalls, and quiet viewpoints. It is the part of the trip where pacing matters most.</p><p>This experience works well for couples, families, photographers, and travelers who want a gentler break between cultural touring, safari, and the coast.</p>',
        'where_to_experience' => '<p>Nuwara Eliya, Ella, Haputale, Hatton, and the surrounding tea estates all offer different versions of the hill-country experience. The right choice depends on route direction and travel style.</p>',
        'best_time' => 'Carry a light jacket for evenings. Driving distances in the hills can feel longer than map times suggest because roads are winding.',
        'tips' => ['Plan fewer stops on hill-country transfer days', 'Choose viewpoints based on weather and cloud cover', 'Keep a flexible plan for waterfalls and hikes', 'Expect cooler evenings than the coast'],
        'destinations' => [],
        'itineraries' => [],
        'hero_image' => 'assets/images/adams-peak-lk.jpg',
        'og_image' => 'assets/images/adams-peak-lk.jpg',
        'featured' => 0,
        'sort' => 40,
    ],
    [
        'title' => 'Village Food and Cooking',
        'slug' => 'village-food-and-cooking',
        'excerpt' => 'Taste Sri Lanka through rice and curry, village kitchens, spice gardens, markets, home-style meals, and cooking demonstrations.',
        'intro' => '<p>Food is one of the easiest ways to feel Sri Lanka beyond sightseeing. A good food experience can be a village lunch, a market stop, a cooking demonstration, a seafood meal near the coast, or a simple roadside tea break.</p><p>We add food experiences carefully, choosing moments that fit naturally into the day rather than forcing long detours.</p>',
        'where_to_experience' => '<p>Food experiences can be added across the island: cultural triangle villages, coastal seafood towns, hill-country tea stops, spice-growing areas, and family-run restaurants.</p>',
        'best_time' => 'Lunch experiences work best on lighter sightseeing days. Tell us about spice tolerance, allergies, and dietary preferences before the route is finalized.',
        'tips' => ['Share food restrictions early', 'Try local meals when the day is not too rushed', 'Ask before photographing home kitchens or people', 'Balance street food with comfortable restaurant stops'],
        'destinations' => [],
        'itineraries' => [],
        'hero_image' => 'assets/images/home-hero/home-5.jpg',
        'og_image' => 'assets/images/home-hero/home-5.jpg',
        'featured' => 0,
        'sort' => 50,
    ],
    [
        'title' => 'Beaches and Coastal Life',
        'slug' => 'beaches-and-coastal-life',
        'excerpt' => 'Add beach time, fishing villages, sunset stops, seafood meals, and relaxed coastal days to balance a private Sri Lanka route.',
        'intro' => '<p>Sri Lanka\'s coastline is not one single beach experience. The south coast, west coast, east coast, and northwest each have different seasons, moods, and travel logic.</p><p>We use coastal time to balance busier cultural, safari, or hill-country days with space to rest, swim, eat well, and enjoy slower travel.</p>',
        'where_to_experience' => '<p>South-coast routes often pair well with wildlife and Galle, while east-coast beaches can suit different seasons. The best beach choice depends heavily on month of travel.</p>',
        'best_time' => 'The south and west coasts are usually strongest from November to April, while the east coast is often better from April to September.',
        'tips' => ['Match the coast to your travel month', 'Keep at least two nights if beach rest matters', 'Avoid overloading beach days with inland sightseeing', 'Check sea conditions before swimming'],
        'destinations' => [],
        'itineraries' => [],
        'hero_image' => 'assets/images/mirissa-beach-lk.jpg',
        'og_image' => 'assets/images/galle-lighthouse.jpg',
        'featured' => 0,
        'sort' => 60,
    ],
    [
        'title' => 'Waterfalls and Hill Country Views',
        'slug' => 'waterfalls-and-hill-country-views',
        'excerpt' => 'Build scenic viewpoints, waterfalls, misty roads, short walks, and photography stops into a comfortable private route.',
        'intro' => '<p>The hill country is full of small scenic rewards: waterfalls near the road, valley viewpoints, tea-covered bends, railway scenes, and short walks. These moments are often more memorable when the day is not overplanned.</p><p>This experience suits travelers who enjoy photography, soft adventure, cooler weather, and flexible stopovers.</p>',
        'where_to_experience' => '<p>Ella, Haputale, Nuwara Eliya, Ramboda, and the wider central highlands offer many waterfall and viewpoint options that can be shaped around weather and route direction.</p>',
        'best_time' => 'Waterfalls can be stronger after rain, but heavy rain may affect road comfort and visibility. Flexibility helps.',
        'tips' => ['Wear shoes with grip for short walks', 'Keep rain protection handy in the hills', 'Avoid packing too many viewpoints into one transfer', 'Let the driver adjust stops based on weather'],
        'destinations' => [],
        'itineraries' => [],
        'hero_image' => 'assets/images/random_waterfall.webp',
        'og_image' => 'assets/images/random_waterfall.webp',
        'featured' => 0,
        'sort' => 70,
    ],
    [
        'title' => 'Soft Adventure',
        'slug' => 'soft-adventure',
        'excerpt' => 'Add active but manageable experiences like short hikes, river scenery, cycling, village walks, surfing lessons, or nature trails.',
        'intro' => '<p>Soft adventure is for travelers who want movement and nature without making the trip feel extreme. The best options depend on fitness level, travel month, and how much comfort you want around the activity.</p><p>We can add active experiences as half-day moments within a private itinerary, leaving enough time for recovery and onward travel.</p>',
        'where_to_experience' => '<p>Soft adventure can work in the hills, cultural triangle, rainforest areas, riverside villages, and selected coastal towns depending on the season.</p>',
        'best_time' => 'Morning activity slots are usually more comfortable. Weather can change plans quickly in rainforest and hill-country areas.',
        'tips' => ['Tell us your fitness level honestly', 'Keep activity days lighter on driving', 'Use proper shoes for trails and wet ground', 'Plan rest time after early starts'],
        'destinations' => [],
        'itineraries' => [],
        'hero_image' => 'assets/images/home-hero/home-2.jpg',
        'og_image' => 'assets/images/adams-peak-lk.jpg',
        'featured' => 0,
        'sort' => 80,
    ],
    [
        'title' => 'Ayurveda and Wellness',
        'slug' => 'ayurveda-and-wellness',
        'excerpt' => 'Include spa time, Ayurveda treatments, yoga, meditation, and slower stays where wellness is part of the travel rhythm.',
        'intro' => '<p>Wellness in Sri Lanka can be simple or serious: a relaxing spa treatment, a yoga morning, a few quiet nights near the coast, or a structured Ayurveda stay. The route should reflect how deep you want the wellness focus to be.</p><p>We help balance wellness time with sightseeing so the trip feels restorative instead of packed.</p>',
        'where_to_experience' => '<p>Wellness can be added around beach stays, hill-country retreats, boutique hotels, and selected Ayurveda-focused properties.</p>',
        'best_time' => 'A wellness-focused stay works best when you avoid changing hotels every night. Leave space between long drives and treatments.',
        'tips' => ['Decide whether you want light spa time or serious Ayurveda', 'Avoid heavy sightseeing on treatment days', 'Tell us about medical concerns before planning', 'Choose fewer hotel changes for a calmer rhythm'],
        'destinations' => [],
        'itineraries' => [],
        'hero_image' => 'assets/images/home-hero/home-4.jpg',
        'og_image' => 'assets/images/home-hero/home-4.jpg',
        'featured' => 0,
        'sort' => 90,
    ],
    [
        'title' => 'Hidden Gems by Private Driver',
        'slug' => 'hidden-gems-by-private-driver',
        'excerpt' => 'Use private transport to add quiet temples, local viewpoints, village roads, small markets, and flexible stops between major sights.',
        'intro' => '<p>The advantage of a private driver is not only comfort; it is the ability to stop well. Some of the best Sri Lanka moments happen between the famous places: a quiet viewpoint, a fruit stall, a small temple, a local bakery, or a backroad through fields.</p><p>This experience is about designing a route with room for those moments instead of racing from attraction to attraction.</p>',
        'where_to_experience' => '<p>Hidden-gem stops can be built into almost every route, especially between the cultural triangle, hill country, wildlife parks, and the southern coast.</p>',
        'best_time' => 'This style works best when the itinerary is not too crowded. A flexible day plan gives the driver room to suggest useful stops.',
        'tips' => ['Avoid overfilling every day', 'Tell us what kind of local moments interest you', 'Keep some cash for small purchases', 'Trust route adjustments when weather or crowds change'],
        'destinations' => ['sigiriya', 'udawalawe', 'yala'],
        'itineraries' => ['sri-lanka-wildlife-safari-itinerary-7-days'],
        'hero_image' => 'assets/images/galle-lighthouse.jpg',
        'og_image' => 'assets/images/home-hero/home-1.jpg',
        'featured' => 1,
        'sort' => 100,
    ],
];

$findDestinationId = static function (string $slug): ?int {
    $row = fetch_one('SELECT id FROM destinations WHERE slug = :slug LIMIT 1', ['slug' => $slug]);
    return $row ? (int) $row['id'] : null;
};

$findItineraryId = static function (string $slug): ?int {
    $row = fetch_one('SELECT id FROM itineraries WHERE slug = :slug LIMIT 1', ['slug' => $slug]);
    return $row ? (int) $row['id'] : null;
};

$created = 0;
$updated = 0;

foreach ($experiences as $item) {
    $existing = fetch_one('SELECT id, hero_image, og_image FROM experiences WHERE slug = :slug LIMIT 1', ['slug' => $item['slug']]);
    $payload = [
        'title' => $item['title'],
        'slug' => $item['slug'],
        'excerpt' => $item['excerpt'],
        'intro' => $item['intro'],
        'where_to_experience' => $item['where_to_experience'],
        'tips_json' => json_encode($item['tips'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        'best_time' => $item['best_time'],
        'meta_title' => $item['title'] . ' in Sri Lanka | Island Culture Travels',
        'meta_description' => excerpt_words($item['excerpt'], 26),
        'canonical_url' => base_url('experiences/' . $item['slug']),
        'cta_title' => 'Add ' . $item['title'] . ' to Your Sri Lanka Trip',
        'cta_text' => 'Tell us your dates, interests, and travel pace. We will shape a private route around the experiences that matter most to you.',
        'hero_image' => (string) ($item['hero_image'] ?? ''),
        'og_image' => (string) ($item['og_image'] ?? ''),
        'sort_order' => $item['sort'],
        'is_featured' => $item['featured'],
        'is_published' => 1,
    ];

    if ($existing) {
        if (!empty($existing['hero_image'])) {
            $payload['hero_image'] = (string) $existing['hero_image'];
        }
        if (!empty($existing['og_image'])) {
            $payload['og_image'] = (string) $existing['og_image'];
        }

        $setParts = [];
        foreach (array_keys($payload) as $column) {
            $setParts[] = "{$column} = :{$column}";
        }
        execute_query(
            'UPDATE experiences SET ' . implode(', ', $setParts) . ' WHERE id = :id',
            array_merge($payload, ['id' => (int) $existing['id']])
        );
        $experienceId = (int) $existing['id'];
        $updated++;
    } else {
        $columns = array_keys($payload);
        $placeholders = array_map(static fn(string $column): string => ':' . $column, $columns);
        execute_query(
            'INSERT INTO experiences (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $placeholders) . ')',
            $payload
        );
        $experienceId = (int) last_insert_id();
        $created++;
    }

    execute_query('DELETE FROM experience_destination WHERE experience_id = :id', ['id' => $experienceId]);
    foreach ($item['destinations'] as $destinationSlug) {
        $destinationId = $findDestinationId($destinationSlug);
        if ($destinationId) {
            execute_query(
                'INSERT IGNORE INTO experience_destination (experience_id, destination_id) VALUES (:experience_id, :destination_id)',
                ['experience_id' => $experienceId, 'destination_id' => $destinationId]
            );
        }
    }

    execute_query('DELETE FROM experience_itinerary WHERE experience_id = :id', ['id' => $experienceId]);
    foreach ($item['itineraries'] as $itinerarySlug) {
        $itineraryId = $findItineraryId($itinerarySlug);
        if ($itineraryId) {
            execute_query(
                'INSERT IGNORE INTO experience_itinerary (experience_id, itinerary_id) VALUES (:experience_id, :itinerary_id)',
                ['experience_id' => $experienceId, 'itinerary_id' => $itineraryId]
            );
        }
    }
}

$relatedPairs = [
    'wildlife-safaris' => ['hidden-gems-by-private-driver', 'soft-adventure'],
    'ancient-culture-and-temples' => ['hidden-gems-by-private-driver', 'village-food-and-cooking'],
    'scenic-train-journeys' => ['tea-country-and-plantations', 'waterfalls-and-hill-country-views'],
    'tea-country-and-plantations' => ['scenic-train-journeys', 'waterfalls-and-hill-country-views'],
    'village-food-and-cooking' => ['ancient-culture-and-temples', 'hidden-gems-by-private-driver'],
    'beaches-and-coastal-life' => ['ayurveda-and-wellness', 'village-food-and-cooking'],
    'waterfalls-and-hill-country-views' => ['tea-country-and-plantations', 'scenic-train-journeys'],
    'soft-adventure' => ['wildlife-safaris', 'waterfalls-and-hill-country-views'],
    'ayurveda-and-wellness' => ['beaches-and-coastal-life', 'tea-country-and-plantations'],
    'hidden-gems-by-private-driver' => ['wildlife-safaris', 'ancient-culture-and-temples'],
];

foreach ($relatedPairs as $slug => $relatedSlugs) {
    $source = fetch_one('SELECT id FROM experiences WHERE slug = :slug LIMIT 1', ['slug' => $slug]);
    if (!$source) {
        continue;
    }
    execute_query('DELETE FROM experience_related WHERE experience_id = :id', ['id' => (int) $source['id']]);
    foreach ($relatedSlugs as $relatedSlug) {
        $target = fetch_one('SELECT id FROM experiences WHERE slug = :slug LIMIT 1', ['slug' => $relatedSlug]);
        if ($target) {
            execute_query(
                'INSERT IGNORE INTO experience_related (experience_id, related_experience_id) VALUES (:experience_id, :related_id)',
                ['experience_id' => (int) $source['id'], 'related_id' => (int) $target['id']]
            );
        }
    }
}

echo "Experiences seeded. Created: {$created}; Updated: {$updated}." . PHP_EOL;
