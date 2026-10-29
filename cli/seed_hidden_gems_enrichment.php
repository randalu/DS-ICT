<?php
declare(strict_types=1);
/**
 * Enrich hidden gem destinations with SEO data.
 * Run: php cli\seed_hidden_gems_enrichment.php
 */
require_once __DIR__ . '/../includes/functions.php';

$j = fn($a) => json_encode($a, JSON_UNESCAPED_UNICODE);

$data = [];

// sinharaja
$data['sinharaja-forest-reserve'] = [
    'top_source_markets' => $j(['United Kingdom', 'Germany', 'France', 'Netherlands', 'Australia']),
    'top_activities' => $j(['Guided rainforest trek', 'Bird watching (endemics)', 'Waterfall walk', 'Butterfly spotting', 'Night walk']),
    'tripadvisor_rating' => 4.6, 'tripadvisor_review_count' => 980,
    'booking_rating' => 4.1, 'getyourguide_rating' => 4.6, 'getyourguide_review_count' => 420,
    'strict_regulations_notes' => 'UNESCO reserve: guide mandatory. No littering. No feeding wildlife. Stay on trails. Permits required for research.',
    'seasonality_notes' => 'Rainforest — expect rain any day. Driest January–March and August–September. Birding best in dry season.',
    'logistical_effort_notes' => 'Moderate from Colombo (3.5 hrs). Last 20 km winding. Leeches in wet conditions. Moderate fitness for trails.',
    'peak_months' => $j(['January', 'February', 'March', 'August']),
    'shoulder_months' => $j(['April', 'July', 'September']),
    'off_months' => $j(['May', 'June', 'October', 'November', 'December']),
];

// haputale
$data['haputale'] = [
    'top_source_markets' => $j(['United Kingdom', 'Germany', 'India', 'France', 'Netherlands']),
    'top_activities' => $j(["Lipton's Seat hike", 'Tea factory tour', 'Adisham Bungalow', 'Train ride', 'Thangamale Sanctuary']),
    'tripadvisor_rating' => 4.4, 'tripadvisor_review_count' => 1200,
    'booking_rating' => 4.2, 'getyourguide_rating' => 4.5, 'getyourguide_review_count' => 380,
    'strict_regulations_notes' => 'Dress modestly at Adisham Bungalow (monastery). No photography inside tea factory processing areas.',
    'seasonality_notes' => 'Best January–April (clear views). Misty May–July. Second clear window August–September.',
    'logistical_effort_notes' => 'Easy from Colombo (4 hrs by road or scenic train). Lipton\'s Seat road steep — 4WD recommended.',
    'peak_months' => $j(['January', 'February', 'March', 'April']),
    'shoulder_months' => $j(['August', 'September', 'December']),
    'off_months' => $j(['May', 'June', 'July', 'October', 'November']),
];

// wilpattu
$data['wilpattu-national-park'] = [
    'top_source_markets' => $j(['Germany', 'United Kingdom', 'France', 'India', 'Italy']),
    'top_activities' => $j(['Leopard safari', 'Sloth bear watching', 'Birding at willus', 'Photography safari', 'Picnic safari']),
    'tripadvisor_rating' => 4.3, 'tripadvisor_review_count' => 850,
    'booking_rating' => 4.0, 'getyourguide_rating' => 4.4, 'getyourguide_review_count' => 310,
    'strict_regulations_notes' => 'Park closes annually November–January (monsoon). Stay in vehicle. No off-road driving. Guide mandatory.',
    'seasonality_notes' => 'Open February–October. Peak leopard sightings May–June. Dry season (May–Sept) best for willu viewing.',
    'logistical_effort_notes' => 'Moderate from Colombo (4 hrs). Rough park roads. Dusty — bring mask. Book jeep in advance.',
    'peak_months' => $j(['May', 'June', 'July', 'August', 'September']),
    'shoulder_months' => $j(['February', 'March', 'April', 'October']),
    'off_months' => $j(['November', 'December', 'January']),
];

// kalpitiya
$data['kalpitiya'] = [
    'top_source_markets' => $j(['Germany', 'United Kingdom', 'Netherlands', 'Italy', 'Russia']),
    'top_activities' => $j(['Kitesurfing', 'Dolphin watching', 'Bar Reef snorkelling', 'Sandboarding', 'Lagoon sunset cruise']),
    'tripadvisor_rating' => 4.2, 'tripadvisor_review_count' => 420,
    'booking_rating' => 3.8, 'getyourguide_rating' => 4.2, 'getyourguide_review_count' => 120,
    'strict_regulations_notes' => 'Bar Reef marine sanctuary: no fishing, no coral collection. Dolphin watching boats must maintain distance.',
    'seasonality_notes' => 'Wind season May–September (kitesurfing). Dolphin season October–February. Transitional March–April and October.',
    'logistical_effort_notes' => 'Easy from Colombo (3 hrs). Narrow peninsula road. Limited accommodation — book ahead in wind season.',
    'peak_months' => $j(['June', 'July', 'August', 'January', 'February']),
    'shoulder_months' => $j(['May', 'September', 'December']),
    'off_months' => $j(['March', 'April', 'October', 'November']),
];

// gal-oya
$data['gal-oya-national-park'] = [
    'top_source_markets' => $j(['United Kingdom', 'Germany', 'France', 'Netherlands', 'Australia']),
    'top_activities' => $j(['Walking safari', 'Boat safari', 'Vedda village visit', 'Elephant watching', 'Birding']),
    'tripadvisor_rating' => 4.6, 'tripadvisor_review_count' => 580,
    'booking_rating' => 4.3, 'getyourguide_rating' => 4.7, 'getyourguide_review_count' => 210,
    'strict_regulations_notes' => 'Walking safari: accompanied by armed tracker at all times. No unauthorised walking. Park permit required.',
    'seasonality_notes' => 'Best March–July (dry). Animals concentrate near water. Walking trails best in dry conditions.',
    'logistical_effort_notes' => 'Remote — 7 hrs from Colombo. Gal Oya Lodge only premium option. Private transport essential.',
    'peak_months' => $j(['March', 'April', 'May', 'June', 'July']),
    'shoulder_months' => $j(['February', 'August']),
    'off_months' => $j(['September', 'October', 'November', 'December', 'January']),
];

// ritigala
$data['ritigala'] = [
    'top_source_markets' => $j(['Germany', 'United Kingdom', 'France', 'Netherlands', 'Japan']),
    'top_activities' => $j(['Guided ruins hike', 'Stone bridge walk', 'Meditation platform visit', 'Birdwatching', 'Butterfly spotting']),
    'tripadvisor_rating' => 4.5, 'tripadvisor_review_count' => 340,
    'booking_rating' => 4.2, 'getyourguide_rating' => 4.6, 'getyourguide_review_count' => 110,
    'strict_regulations_notes' => 'Strict nature reserve: no removing anything (not even leaves). Guide mandatory. No food or smoking inside.',
    'seasonality_notes' => 'Best February–September (dry). Trails slippery November–December. Go early morning for bird activity.',
    'logistical_effort_notes' => 'Moderate from Sigiriya (1 hr). Guide mandatory (2000 LKR). Moderate hike on uneven ground. Sturdy shoes essential.',
    'peak_months' => $j(['February', 'March', 'April', 'August', 'September']),
    'shoulder_months' => $j(['January', 'May', 'June', 'July']),
    'off_months' => $j(['October', 'November', 'December']),
];

// ussangoda
$data['ussangoda'] = [
    'top_source_markets' => $j(['United Kingdom', 'Germany', 'India', 'France', 'Russia']),
    'top_activities' => $j(['Photography', 'Scenic viewing', 'Ramayana trail visit', 'Sunset watching', 'Geology exploration']),
    'tripadvisor_rating' => 4.0, 'tripadvisor_review_count' => 180,
    'booking_rating' => 3.9, 'getyourguide_rating' => 4.1, 'getyourguide_review_count' => 45,
    'strict_regulations_notes' => 'No climbing on the soil formations. Stay on designated paths. No littering — fragile ecosystem.',
    'seasonality_notes' => 'Best December–March (dry season). Extremely hot midday — visit late afternoon for best light.',
    'logistical_effort_notes' => 'Easy — 30 min from Tangalle. Quick photo stop (30–60 min). No shade — bring sun protection.',
    'peak_months' => $j(['January', 'February', 'March']),
    'shoulder_months' => $j(['December', 'April']),
    'off_months' => $j(['May', 'June', 'July', 'August', 'September', 'October', 'November']),
];

// yapahuwa
$data['yapahuwa'] = [
    'top_source_markets' => $j(['Germany', 'United Kingdom', 'France', 'India', 'Japan']),
    'top_activities' => $j(['Ornamental staircase climb', 'Summit ruins explore', 'Cave temple visit', 'Photography', 'Sunset viewing']),
    'tripadvisor_rating' => 4.3, 'tripadvisor_review_count' => 420,
    'booking_rating' => 4.0, 'getyourguide_rating' => 4.4, 'getyourguide_review_count' => 95,
    'strict_regulations_notes' => 'Archaeological site: no climbing on ruins. Remove shoes at cave temple. Drones prohibited.',
    'seasonality_notes' => 'Best November–April (cooler). Avoid midday May–October. Visit early morning (8 AM) or late afternoon (4 PM).',
    'logistical_effort_notes' => 'Easy from Kurunegala (45 min). Steep but short climb (10–15 min). Not wheelchair accessible.',
    'peak_months' => $j(['January', 'February', 'March', 'April']),
    'shoulder_months' => $j(['November', 'December']),
    'off_months' => $j(['May', 'June', 'July', 'August', 'September', 'October']),
];

// minneriya
$data['minneriya-kaudulla'] = [
    'top_source_markets' => $j(['United Kingdom', 'Germany', 'China', 'France', 'India']),
    'top_activities' => $j(['Elephant Gathering safari', 'Bird watching', 'Photography safari', 'Minneriya tank visit', 'Combined Kaudulla safari']),
    'tripadvisor_rating' => 4.5, 'tripadvisor_review_count' => 1600,
    'booking_rating' => 4.2, 'getyourguide_rating' => 4.5, 'getyourguide_review_count' => 680,
    'strict_regulations_notes' => 'National Park rules: stay in vehicle. No feeding animals. Drones prohibited. No off-road driving.',
    'seasonality_notes' => 'Peak Gathering July–October (up to 300 elephants). Fewer elephants November–June. Afternoon safari (3 PM) best.',
    'logistical_effort_notes' => 'Easy from Habarana (15 min). Dusty — bring mask. Jeep safari essential. Combines well with Sigiriya.',
    'peak_months' => $j(['July', 'August', 'September', 'October']),
    'shoulder_months' => $j(['June', 'November']),
    'off_months' => $j(['December', 'January', 'February', 'March', 'April', 'May']),
];

// delft-island
$data['delft-island'] = [
    'top_source_markets' => $j(['United Kingdom', 'Germany', 'India', 'France', 'Italy']),
    'top_activities' => $j(['Wild horse viewing', 'Baobab tree visit', 'Dutch Fort ruins', 'Coral fence walk', 'Cycling']),
    'tripadvisor_rating' => 4.1, 'tripadvisor_review_count' => 250,
    'booking_rating' => 3.7, 'getyourguide_rating' => 4.2, 'getyourguide_review_count' => 60,
    'strict_regulations_notes' => 'Ferry schedule unpredictable in rainy season. No accommodation infrastructure. Bring own food and water.',
    'seasonality_notes' => 'Only reliable December–March (dry season). Ferry cancellations common April–November.',
    'logistical_effort_notes' => 'Moderate — 1 hr drive from Jaffna + 1.5 hr ferry. Day trip only from Jaffna. Very basic amenities.',
    'peak_months' => $j(['January', 'February', 'March']),
    'shoulder_months' => $j(['December', 'April']),
    'off_months' => $j(['May', 'June', 'July', 'August', 'September', 'October', 'November']),
];

// bundala
$data['bundala-national-park'] = [
    'top_source_markets' => $j(['United Kingdom', 'Germany', 'Netherlands', 'France', 'USA']),
    'top_activities' => $j(['Flamingo watching', 'Bird photography', 'Lagoon safari', 'Sand dune walk', 'Cave temple visit']),
    'tripadvisor_rating' => 4.3, 'tripadvisor_review_count' => 380,
    'booking_rating' => 4.0, 'getyourguide_rating' => 4.4, 'getyourguide_review_count' => 120,
    'strict_regulations_notes' => 'Ramsar wetland: no littering. No disturbing birds. Stay with guide. Drones require special permit.',
    'seasonality_notes' => 'Peak bird season November–March (migratory birds). Flamingos peak December–February.',
    'logistical_effort_notes' => 'Easy from Tangalle (30 min). Half-day trip sufficient. Early morning best for bird activity.',
    'peak_months' => $j(['December', 'January', 'February', 'March']),
    'shoulder_months' => $j(['November', 'April']),
    'off_months' => $j(['May', 'June', 'July', 'August', 'September', 'October']),
];

// meemure
$data['meemure-village'] = [
    'top_source_markets' => $j(['United Kingdom', 'Germany', 'France', 'Netherlands', 'India']),
    'top_activities' => $j(['Village homestay', 'Waterfall hike', 'Rice terrace walk', 'Traditional cooking', 'Stargazing']),
    'tripadvisor_rating' => 4.6, 'tripadvisor_review_count' => 210,
    'booking_rating' => 4.1, 'getyourguide_rating' => 4.7, 'getyourguide_review_count' => 85,
    'strict_regulations_notes' => 'Traditional village: respect local customs. No alcohol in public. Minimal facilities — be self-sufficient.',
    'seasonality_notes' => 'Best January–April (dry season). River crossings difficult May–July (heavy rain).',
    'logistical_effort_notes' => 'Moderate — 2.5 hrs from Kandy + 1 km riverbed walk. No road access. Basic homestay only.',
    'peak_months' => $j(['January', 'February', 'March', 'April']),
    'shoulder_months' => $j(['August', 'September']),
    'off_months' => $j(['May', 'June', 'July', 'October', 'November', 'December']),
];

$updateCols = [
    'top_source_markets', 'top_activities', 'tripadvisor_rating', 'tripadvisor_review_count',
    'booking_rating', 'getyourguide_rating', 'getyourguide_review_count',
    'strict_regulations_notes', 'seasonality_notes', 'logistical_effort_notes',
    'peak_months', 'shoulder_months', 'off_months',
];

// Only attempt columns that actually exist in the table
$existingCols = array_flip(array_map(fn($c) => $c['Field'], fetch_all("SHOW COLUMNS FROM destinations")));
$updateCols = array_values(array_filter($updateCols, fn($c) => isset($existingCols[$c])));

$updated = 0;
$errors = 0;

foreach ($data as $slug => $row) {
    $existing = fetch_one("SELECT id FROM destinations WHERE slug = :slug", ['slug' => $slug]);
    if (!$existing) {
        echo "SKIP {$slug} — not found\n";
        continue;
    }

    try {
        $setParts = [];
        $params = ['slug' => $slug];
        foreach ($updateCols as $col) {
            if (array_key_exists($col, $row)) {
                $setParts[] = "`{$col}` = :{$col}";
                $params[$col] = $row[$col];
            }
        }
        $setClause = implode(', ', $setParts);
        $sql = "UPDATE destinations SET {$setClause} WHERE slug = :slug";
        execute_query($sql, $params);
        echo "OK   {$slug}\n";
        ++$updated;
    } catch (\Throwable $e) {
        echo "FAIL {$slug}: {$e->getMessage()}\n";
        ++$errors;
    }
}

echo "\nDone. Updated: {$updated}, Errors: {$errors}\n";
exit($errors > 0 ? 1 : 0);
