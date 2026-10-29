<?php
declare(strict_types=1);
/**
 * Phase 6: Enrich seasonal guides, tours, itineraries, and experiences.
 * Run: php cli/seed_phase6_enrichment.php
 */
require_once __DIR__ . '/../includes/functions.php';

$j = fn($a) => json_encode($a, JSON_UNESCAPED_UNICODE);
$updated = 0; $errors = 0;

// ============================================================
// 6.1 Seasonal Guides — meta_title & meta_description (12)
// ============================================================
$guides = [
    'january' => [
        'meta_title' => 'Sri Lanka in January | Weather, Events & Travel Guide | Island Culture Travels',
        'meta_description' => 'Visit Sri Lanka in January for peak south-coast sun, Duruthu Perahera in Colombo, whale watching off Mirissa, and cool Hill Country treks. Private chauffeur-guided tours available.',
    ],
    'february' => [
        'meta_title' => 'Sri Lanka in February | Dry Season Travel & Events Guide | Island Culture Travels',
        'meta_description' => 'February is Sri Lanka\'s driest month — ideal for beach holidays, Yala & Wilpattu wildlife safaris, and Mirissa whale watching. Plan your private guided tour with Island Culture Travels.',
    ],
    'march' => [
        'meta_title' => 'Sri Lanka in March | Last Dry Month & Pilgrim Season | Island Culture Travels',
        'meta_description' => 'March offers Sri Lanka\'s last reliably dry weather before the monsoon. Perfect for Adam\'s Peak pilgrimages, Ella tea country treks, and south-coast beach stays with fewer crowds.',
    ],
    'april' => [
        'meta_title' => 'Sri Lanka in April | Sinhala & Tamil New Year Guide | Island Culture Travels',
        'meta_description' => 'April brings Sinhala & Tamil New Year festivities across Sri Lanka. The south coast sees last good beach days while the east coast season begins in Trincomalee and Pasikudah.',
    ],
    'may' => [
        'meta_title' => 'Sri Lanka in May | Vesak Festival & East Coast Season | Island Culture Travels',
        'meta_description' => 'May in Sri Lanka shifts the best weather east — surf at Arugam Bay, snorkel Pigeon Island, and celebrate Vesak lantern festivals. Quiet month with great value travel.',
    ],
    'june' => [
        'meta_title' => 'Sri Lanka in June | East Coast Surf & Poson Festival | Island Culture Travels',
        'meta_description' => 'June offers uncrowded east-coast beaches, excellent Arugam Bay surf, Poson Poya pilgrimages to Mihintale, and lush green Hill Country between showers.',
    ],
    'july' => [
        'meta_title' => 'Sri Lanka in July | Esala Perahera & Peak East Coast | Island Culture Travels',
        'meta_description' => 'July is festival season in Sri Lanka — Kandy\'s Esala Perahera, the Minneriya elephant gathering, and prime east-coast beach weather. Book private transport ahead.',
    ],
    'august' => [
        'meta_title' => 'Sri Lanka in August | Perahera Finale & Elephant Gathering | Island Culture Travels',
        'meta_description' => 'August continues Sri Lanka\'s festival season with Esala Perahera final nights, peak elephant viewing at Minneriya-Kaudulla, and the Nallur Festival in Jaffna.',
    ],
    'september' => [
        'meta_title' => 'Sri Lanka in September | Yala Reopens & Shoulder Season | Island Culture Travels',
        'meta_description' => 'September is a transition month — Yala reopens after rest, south-coast pricing drops, and the Nallur Festival continues in Jaffna. Good value and quieter crowds.',
    ],
    'october' => [
        'meta_title' => 'Sri Lanka in October | Inter-Monsoon & Diwali Celebrations | Island Culture Travels',
        'meta_description' => 'October is Sri Lanka\'s wettest month island-wide but rewards with spectacular waterfalls, Diwali celebrations, and deep-green landscapes. Best for flexible itineraries.',
    ],
    'november' => [
        'meta_title' => 'Sri Lanka in November | South Coast Season Returns | Island Culture Travels',
        'meta_description' => 'November marks the south coast\'s return — whale watching restarts in Mirissa, beach resorts reopen, and shoulder-season pricing makes luxury stays excellent value.',
    ],
    'december' => [
        'meta_title' => 'Sri Lanka in December | Peak Season Sun & Holiday Travel | Island Culture Travels',
        'meta_description' => 'December brings peak-season sun to Sri Lanka\'s south and west coasts. Christmas and New Year surcharges apply; book early for best rates on private tours and luxury stays.',
    ],
];

foreach ($guides as $month => $data) {
    try {
        $check = fetch_one("SELECT month_key FROM seasonal_guides WHERE month_key = :mk", ['mk' => $month]);
        if (!$check) { echo "SKIP {$month} — not found\n"; continue; }
        execute_query("UPDATE seasonal_guides SET meta_title = :mt, meta_description = :md WHERE month_key = :mk", [
            'mt' => $data['meta_title'], 'md' => $data['meta_description'], 'mk' => $month,
        ]);
        echo "SG {$month}\n"; ++$updated;
    } catch (\Throwable $e) { echo "FAIL SG {$month}: {$e->getMessage()}\n"; ++$errors; }
}

// ============================================================
// 6.2 Tours — Fill missing content per tour type
// ============================================================

// Multi-day tours (need includes, excludes, best_time_json, itinerary)
$multiDay = [
    'sri-lanka-wildlife-safari-tour' => [
        'includes' => "Private air-conditioned vehicle throughout\nEnglish-speaking chauffeur-guide\nPark entrance fees for Yala, Udawalawe & Wilpattu\nJeep safaris with experienced driver-trackers\nAccommodation as per itinerary\nDaily breakfast at hotels\nAll government taxes & service charges\n24/7 support from our travel team",
        'excludes' => "International flights\nVisa fees\nMeals not specified in itinerary\nPersonal expenses (tips, souvenirs, drinks)\nCamera/video permits at national parks\nTravel insurance (recommended)",
        'best_time_json' => $j(['February', 'March', 'April', 'May', 'June', 'July', 'August', 'September']),
        'itinerary' => "Day 1: Airport pickup — transfer to Negombo\nDay 2: Drive to Wilpattu — afternoon safari\nDay 3: Morning safari — drive to Sigiriya\nDay 4: Sigiriya Rock & Dambulla Cave Temple — drive to Kandy\nDay 5: Kandy — Temple of the Tooth, tea plantation\nDay 6: Drive to Nuwara Eliya via tea country\nDay 7: Horton Plains trek — drive to Udawalawe\nDay 8: Udawalawe safari — drive to Yala\nDay 9: Yala full-day safari\nDay 10: Departure",
    ],
    'classic-sri-lanka-private-tour-package' => [
        'includes' => "Private vehicle with driver for entire tour\nProfessional English-speaking chauffeur-guide\nAll accommodation as per itinerary\nDaily breakfast at hotels\nAirport transfers on arrival & departure\nAll government taxes & service charges\nComplimentary SIM card with data\nFlexible itinerary adjustments en route",
        'excludes' => "International airfare\nVisa on arrival fee\nLunch & dinner unless specified\nEntrance fees to sites & parks\nPersonal expenses & tips\nTravel insurance",
        'best_time_json' => $j(['January', 'February', 'March', 'April', 'August', 'December']),
        'itinerary' => "Day 1: Airport — Negombo\nDay 2: Negombo — Sigiriya (Pidurangala sunset)\nDay 3: Sigiriya Rock — Dambulla — Kandy\nDay 4: Kandy — Temple of the Tooth — cultural show\nDay 5: Kandy — Nuwara Eliya (tea plantations)\nDay 6: Nuwara Eliya — Ella (train ride)\nDay 7: Ella hikes — Little Adam's Peak, Nine Arch\nDay 8: Ella — Yala (evening safari)\nDay 9: Yala safari — Mirissa\nDay 10: Mirissa whale watching\nDay 11: Mirissa — Galle Fort\nDay 12: Galle — Colombo\nDay 13: Colombo city tour\nDay 14: Departure",
    ],
    'sri-lanka-10-day-itinerary-package' => [
        'includes' => "Private vehicle & driver for 10 days\nEnglish-speaking chauffeur-guide\nAccommodation with breakfast\nAirport transfers\nAll taxes & service charges\n24/7 in-trip support",
        'excludes' => "International flights & visa\nMeals not listed\nEntrance fees\nPersonal expenses\nTravel insurance",
        'best_time_json' => $j(['January', 'February', 'March', 'April', 'August', 'December']),
        'itinerary' => "Day 1: Airport — Negombo\nDay 2: Negombo — Sigiriya — Dambulla\nDay 3: Sigiriya — Kandy\nDay 4: Kandy — Nuwara Eliya\nDay 5: Nuwara Eliya — Ella\nDay 6: Ella — Yala\nDay 7: Yala morning safari — Mirissa\nDay 8: Mirissa whale watching\nDay 9: Mirissa — Galle — Colombo\nDay 10: Departure",
    ],
    'sri-lanka-14-day-grand-tour-package' => [
        'includes' => "Private luxury vehicle with A/C\nExpert English-speaking chauffeur-guide\nPremium accommodation with breakfast\nAll airport & port transfers\nAll government taxes\nFlexible day-by-day customization\nEmergency contact 24/7",
        'excludes' => "International flights & visa\nMeals not specified\nPark & site entrance fees\nCamera permits\nTips & personal expenses\nTravel insurance",
        'best_time_json' => $j(['January', 'February', 'March', 'April', 'December']),
        'itinerary' => "Day 1: Airport — Negombo\nDay 2: Negombo — Anuradhapura\nDay 3: Anuradhapura — Sigiriya\nDay 4: Sigiriya — Dambulla — Kandy\nDay 5: Kandy — Temple of the Tooth\nDay 6: Kandy — Nuwara Eliya tea country\nDay 7: Nuwara Eliya — Horton Plains — Ella\nDay 8: Ella hiking & scenery\nDay 9: Ella — Yala\nDay 10: Yala — Udawalawe\nDay 11: Udawalawe — Mirissa\nDay 12: Mirissa whale watching\nDay 13: Mirissa — Galle — Colombo\nDay 14: Departure",
    ],
    'sri-lanka-honeymoon-package' => [
        'includes' => "Private luxury vehicle\nEnglish-speaking chauffeur-guide\nHandpicked boutique accommodation\nBreakfast daily\nAirport transfers\nWelcome hamper on arrival\nRomantic sunset dinner arrangement\nAll taxes",
        'excludes' => "International flights & visa\nLunch & dinners (except sunset dinner)\nEntrance fees\nPersonal expenses\nTravel insurance",
        'best_time_json' => $j(['January', 'February', 'March', 'April', 'December']),
        'itinerary' => "Day 1: Airport — Negombo (romantic beach dinner)\nDay 2: Negombo — Sigiriya (sunset at Pidurangala)\nDay 3: Sigiriya — Kandy (Temple of the Tooth)\nDay 4: Kandy — Nuwara Eliya (tea country)\nDay 5: Nuwara Eliya — Ella (scenic train)\nDay 6: Ella hiking & couple's time\nDay 7: Ella — Mirissa (beach relaxation)\nDay 8: Mirissa whale watching & sunset beach\nDay 9: Mirissa — Galle Fort walk\nDay 10: Galle — Colombo departure",
    ],
    'sri-lanka-family-tour-package' => [
        'includes' => "Private spacious vehicle with child seats available\nEnglish-speaking chauffeur-guide\nFamily-friendly accommodation\nBreakfast daily\nAirport transfers\nAll taxes & service charges\nFlexible kid-friendly pacing",
        'excludes' => "International flights & visa\nMeals not specified\nEntrance fees\nPersonal expenses\nTravel insurance",
        'best_time_json' => $j(['January', 'February', 'March', 'April', 'August', 'December']),
        'itinerary' => "Day 1: Airport — Negombo\nDay 2: Negombo — Sigiriya (Pidurangala easy hike)\nDay 3: Sigiriya — Dambulla — Kandy\nDay 4: Kandy (Temple of the Tooth, lake walk)\nDay 5: Kandy — Nuwara Eliya (tea plantation)\nDay 6: Nuwara Eliya — Ella (easy hikes)\nDay 7: Ella — Udawalawe (elephant safari)\nDay 8: Udawalawe — Mirissa (beach day)\nDay 9: Mirissa beach & relaxation\nDay 10: Departure",
    ],
    'sri-lanka-wildlife-safari-package' => [
        'includes' => "Private vehicle with driver-guide\nJeep safaris at Yala, Udawalawe & Minneriya\nPark entrance fees & permits\nAccommodation with breakfast\nExperienced naturalist tracker\nAirport transfers\nAll taxes",
        'excludes' => "International flights & visa\nMeals not specified\nCamera permits at parks\nPersonal expenses & tips\nTravel insurance",
        'best_time_json' => $j(['March', 'April', 'May', 'June', 'July', 'August', 'September']),
        'itinerary' => "Day 1: Airport — Negombo\nDay 2: Negombo — Wilpattu (afternoon safari)\nDay 3: Wilpattu morning safari — drive to Habarana\nDay 4: Minneriya elephant gathering (afternoon)\nDay 5: Habarana — Kandy (Temple of the Tooth)\nDay 6: Kandy — Nuwara Eliya — Udawalawe\nDay 7: Udawalawe full-day safari\nDay 8: Udawalawe — Yala (evening safari)\nDay 9: Yala full-day leopard safari\nDay 10: Departure from Colombo",
    ],
    'luxury-sri-lanka-slow-travel-package' => [
        'includes' => "Premium luxury vehicle with A/C\nPrivate English-speaking guide\n5-star & boutique accommodation\nDaily breakfast & selected fine dining\nAirport VIP transfers\nAll government taxes\nConcierge support 24/7\nCurated experiences & private guides",
        'excludes' => "International flights & visa\nMeals not specified\nPersonal expenses\nTravel insurance\nOptional activity upgrades",
        'best_time_json' => $j(['January', 'February', 'March', 'December']),
        'itinerary' => "Day 1: Airport VIP — Colombo (luxury hotel)\nDay 2: Colombo private tour & dining\nDay 3: Colombo — Galle Fort (boutique stay)\nDay 4: Galle Fort exploration\nDay 5: Galle — Mirissa (luxury beach resort)\nDay 6: Mirissa private whale watching\nDay 7: Mirissa — Kandy (hill country luxury)\nDay 8: Kandy private temple tour\nDay 9: Kandy — Sigiriya (luxury lodge)\nDay 10: Sigiriya sunrise climb\nDay 11: Sigiriya — Colombo departure",
    ],
];

// Day tours (need cta_text, sample_itinerary, pricing_notes)
$dayTours = [
    'sigiriya-dambulla-day-tour' => [
        'cta_text' => 'Book this full-day Cultural Triangle tour with a private chauffeur — climb Sigiriya at sunrise, explore the Dambulla Cave Temple, and return with memories that last a lifetime.',
        'sample_itinerary' => "5:00 AM — Pickup from your hotel\n6:00 AM — Sigiriya Rock climb (avoid crowds & heat)\n9:00 AM — Breakfast at a local restaurant\n10:00 AM — Dambulla Cave Temple visit\n12:00 PM — Spice garden visit\n1:00 PM — Lunch in Dambulla\n2:00 PM — Optional: village bullock-cart ride\n4:00 PM — Return to hotel",
        'pricing_notes' => 'Pricing depends on group size, pickup location, and vehicle type. Includes private A/C vehicle, English-speaking driver, and fuel. Entrance fees paid separately (Sigiriya ~$35 USD).',
    ],
    'kandy-city-day-tour' => [
        'cta_text' => 'Explore Kandy\'s sacred heritage with a private chauffeur — Temple of the Tooth, botanical gardens, and a cultural dance show in one seamless day.',
        'sample_itinerary' => "8:00 AM — Pickup from your Kandy hotel\n8:30 AM — Temple of the Tooth Relic\n10:00 AM — Kandy Lake walk\n11:00 AM — Royal Botanic Gardens, Peradeniya\n1:00 PM — Lunch at a Kandyan restaurant\n2:30 PM — Gem museum & workshop tour\n4:00 PM — Cultural dance show\n5:30 PM — Return to hotel",
        'pricing_notes' => 'Pricing varies by group size and pickup location. Includes private vehicle, driver-guide, and fuel. Temple entrance ($10 USD) and garden entry ($8 USD) not included.',
    ],
    'yala-safari-day-tour' => [
        'cta_text' => 'Experience Yala\'s leopard safari with a private jeep and expert tracker — we handle the permits, picks, and packed breakfast so you focus on wildlife.',
        'sample_itinerary' => "4:30 AM — Pickup from your hotel\n5:00 AM — Arrive at Yala park gate\n5:30 AM — Morning safari begins\n8:00 AM — Breakfast stop at beach\n9:00 AM — Continue safari\n11:00 AM — Return to hotel\n12:00 PM — Rest & lunch\n2:00 PM — Optional: afternoon safari or leisure",
        'pricing_notes' => 'Includes private jeep safari with tracker, park entrance fees, and packed breakfast. Morning half-day is standard. Full-day option available. Camera permit extra ($25 USD).',
    ],
    'galle-fort-south-coast-day-tour' => [
        'cta_text' => 'Walk the ramparts of UNESCO-listed Galle Fort with a private driver who knows every hidden lane, café, and boutique worth stopping at.',
        'sample_itinerary' => "8:00 AM — Pickup from your south-coast hotel\n9:00 AM — Galle Fort guided walk (walls, lighthouse)\n10:30 AM — Dutch Reformed Church & museum\n12:00 PM — Lunch at a fort restaurant\n1:30 PM — Shopping: boutiques, tea, gemstones\n3:00 PM — Optional: stilt fisherman photo stop\n4:00 PM — Beach stop en route back\n5:00 PM — Return to hotel",
        'pricing_notes' => 'Pricing by group size and pickup location. A comfortable, low-mileage day. Fort entrance is free; lunch and shopping at own expense.',
    ],
    'nuwara-eliya-tea-country-day-tour' => [
        'cta_text' => 'Let us take you through Sri Lanka\'s tea country — visit a working tea factory, taste single-origin brews, and photograph misty valleys at your own pace.',
        'sample_itinerary' => "7:00 AM — Pickup from Nuwara Eliya hotel\n7:30 AM — Visit to Pedro or Damro tea factory\n9:00 AM — Tea tasting session\n10:00 AM — Gregory Lake walk & boating\n12:00 PM — Lunch at hill-club restaurant\n1:30 PM — Visit to Ambewela farm (Little England)\n3:00 PM — Photography stop at Lovers Leap\n4:00 PM — Return to hotel",
        'pricing_notes' => 'Pricing by group size. Tea factory entry is free or minimal. Lake boating extra. Comfortable half-day or full-day option available.',
    ],
    'ella-kandy-adams-peak-day-tour' => [
        'cta_text' => 'Travel from Ella to Kandy via the most scenic route — Little Adam\'s Peak, Nine Arch Bridge, and a stop at a waterfall for swimming.',
        'sample_itinerary' => "6:00 AM — Little Adam's Peak sunrise hike\n8:00 AM — Breakfast at hotel\n9:00 AM — Nine Arch Bridge visit\n10:00 AM — Depart Ella toward Kandy\n11:00 AM — Ravana Falls photo stop\n12:00 PM — Lunch in Haputale\n1:00 PM — Tea plantation visit en route\n3:00 PM — Arrive Kandy & hotel check-in",
        'pricing_notes' => 'One-way transfer with sightseeing included. Pricing by group size and vehicle type. A full day of scenic driving and short walks. Entrance fees minimal.',
    ],
    'anuradhapura-ancient-city-day-tour' => [
        'cta_text' => 'Discover Sri Lanka\'s first ancient capital with a private driver-guide — Sri Maha Bodhi, Ruwanwelisaya Dagoba, and Mihintale all in one day.',
        'sample_itinerary' => "7:00 AM — Pickup from Anuradhapura hotel\n7:30 AM — Sri Maha Bodhi (sacred fig tree)\n8:30 AM — Ruwanwelisaya & Jetavanaramaya\n10:00 AM — Isurumuniya Temple\n11:00 AM — Mihintale climb\n1:00 PM — Lunch at local restaurant\n2:00 PM — Samadhi Buddha statue\n3:00 PM — Museum visit\n4:00 PM — Return to hotel",
        'pricing_notes' => 'Pricing by group size. Sacred city ticket ($25 USD) covers all main sites. Mihintale entry separate. Bicycle or tuk-tuk hire optional for moving between sites.',
    ],
    'mirissa-whale-watching-day-tour' => [
        'cta_text' => 'We\'ll arrange your Mirissa whale-watching trip with a responsible operator, early-morning pickup, and a smooth return — blue whales guaranteed? Nature doesn\'t work that way, but we try hard.',
        'sample_itinerary' => "5:00 AM — Pickup from Mirissa hotel\n5:30 AM — Check-in at harbour\n6:00 AM — Depart for whale watching\n8:00 AM — Breakfast on board\n9:00 AM — Prime whale sighting time\n11:00 AM — Return to harbour\n11:30 AM — Optional: Coconut Tree Hill\n12:00 PM — Return to hotel",
        'pricing_notes' => 'Includes boat tour with breakfast, hotel transfers, and experienced crew. Morning trip only (5:30-11:30 AM). Blue whale sighting rate 80-90% in peak season (Nov-Apr).',
    ],
    'udawalawe-safari-day-tour' => [
        'cta_text' => 'Book a private Udawalawe safari with a driver who knows where the elephants feed at different times of day — plus a visit to the Elephant Transit Home.',
        'sample_itinerary' => "5:30 AM — Pickup from hotel\n6:00 AM — Park entrance & safari begins\n7:00 AM — Elephant herds at reservoir edge\n8:00 AM — Breakfast stop inside park\n9:00 AM — Continue wildlife drive\n11:00 AM — Elephant Transit Home visit\n12:00 PM — Return to hotel\n1:00 PM — Lunch & rest",
        'pricing_notes' => 'Half-day safari standard (5:30 AM-12 PM). Full-day option available. Includes jeep, driver-tracker, park entry, and water. Elephant Transit Home donation separate.',
    ],
    'trincomalee-nilaveli-day-tour' => [
        'cta_text' => 'Explore Trincomalee\'s east-coast highlights — Koneswaram Temple, Nilaveli Beach, and Pigeon Island snorkelling — with a private chauffeur.',
        'sample_itinerary' => "7:00 AM — Pickup from Trincomalee hotel\n7:30 AM — Koneswaram Temple & Fort Frederick\n9:00 AM — Drive to Nilaveli Beach\n9:30 AM — Boat to Pigeon Island\n10:00 AM — Snorkelling (reef & fish)\n12:00 PM — Lunch at Nilaveli\n2:00 PM — Marble Beach visit\n3:00 PM — Return to Trincomalee",
        'pricing_notes' => 'Pricing by group size. Pigeon Island boat & snorkel gear extra (~$15-20 USD per person). Best season May-September.',
    ],
];

$tourTypes = array_merge($multiDay, $dayTours);
foreach ($tourTypes as $slug => $data) {
    try {
        $check = fetch_one("SELECT id FROM tours WHERE slug = :slug", ['slug' => $slug]);
        if (!$check) { echo "SKIP tour {$slug} — not found\n"; continue; }
        $set = []; $params = ['slug' => $slug];
        foreach ($data as $col => $val) {
            if (in_array($col, ['includes','excludes','best_time_json','itinerary','cta_text','sample_itinerary','pricing_notes'])) {
                $set[] = "`{$col}` = :{$col}";
                $params[$col] = $val;
            }
        }
        if (!empty($set)) {
            execute_query("UPDATE tours SET " . implode(', ', $set) . " WHERE slug = :slug", $params);
            echo "TR {$slug}\n"; ++$updated;
        }
    } catch (\Throwable $e) { echo "FAIL TR {$slug}: {$e->getMessage()}\n"; ++$errors; }
}

// ============================================================
// 6.3 Itineraries — best_months_json (15)
// ============================================================
$itineraries = [
    'sri-lanka-itinerary-5-days' => ['Jan', 'Feb', 'Mar', 'Apr', 'Dec'],
    'sri-lanka-short-escape-5-days' => ['Jan', 'Feb', 'Mar', 'Dec'],
    'sri-lanka-itinerary-7-days' => ['Jan', 'Feb', 'Mar', 'Apr', 'Aug', 'Dec'],
    'sri-lanka-south-coast-itinerary-8-days' => ['Dec', 'Jan', 'Feb', 'Mar'],
    'sri-lanka-short-classic-itinerary-9-days' => ['Jan', 'Feb', 'Mar', 'Apr', 'Aug', 'Dec'],
    'sri-lanka-wildlife-itinerary-9-days' => ['Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep'],
    'sri-lanka-itinerary-10-days' => ['Jan', 'Feb', 'Mar', 'Apr', 'Aug', 'Dec'],
    'sri-lanka-family-itinerary-10-days' => ['Jan', 'Feb', 'Mar', 'Apr', 'Aug', 'Dec'],
    'sri-lanka-east-coast-itinerary-10-days' => ['May', 'Jun', 'Jul', 'Aug', 'Sep'],
    'sri-lanka-luxury-itinerary-11-days' => ['Jan', 'Feb', 'Mar', 'Dec'],
    'sri-lanka-itinerary-12-days' => ['Jan', 'Feb', 'Mar', 'Apr', 'Aug', 'Dec'],
    'sri-lanka-honeymoon-itinerary-12-days' => ['Jan', 'Feb', 'Mar', 'Apr', 'Dec'],
    'sri-lanka-culture-hills-safari-itinerary-12-days' => ['Jan', 'Feb', 'Mar', 'Apr', 'Aug'],
    'sri-lanka-birding-nature-itinerary-12-days' => ['Feb', 'Mar', 'Apr', 'Sep', 'Oct', 'Nov'],
    'sri-lanka-itinerary-14-days' => ['Jan', 'Feb', 'Mar', 'Apr', 'Dec'],
];

foreach ($itineraries as $slug => $months) {
    try {
        $check = fetch_one("SELECT id FROM itineraries WHERE slug = :slug", ['slug' => $slug]);
        if (!$check) { echo "SKIP IT {$slug} — not found\n"; continue; }
        execute_query("UPDATE itineraries SET best_months_json = :bmj WHERE slug = :slug", [
            'bmj' => $j($months), 'slug' => $slug,
        ]);
        echo "IT {$slug}\n"; ++$updated;
    } catch (\Throwable $e) { echo "FAIL IT {$slug}: {$e->getMessage()}\n"; ++$errors; }
}

// ============================================================
// 6.4 Experiences — day_arc_json (26)
// ============================================================
$experiences = [
    'wildlife-safaris' => ['early' => '6:00 AM: Morning safari departures — best for leopard and elephant activity before the heat builds.', 'mid' => '12:00 PM: Return to lodge for lunch & rest. Pool time or wildlife documentaries in the common area.', 'late' => '3:00 PM: Afternoon safari begins. Focus on waterhole activity and sunset photography.', 'evening' => '7:00 PM: Dinner with safari stories. Night sky viewing at lodges with no light pollution.'],
    'ancient-culture-and-temples' => ['early' => '6:00 AM: Sunrise visit to the main temple or dagoba — cooler temperatures and soft light for photography.', 'mid' => '12:00 PM: Lunch at a local restaurant. Midday heat is ideal for a museum visit or rest.', 'late' => '3:00 PM: Continue temple exploration or cycle between ruins. Late afternoon offers golden light.', 'evening' => '6:00 PM: Evening temple visit for ceremony (puja) — a peaceful end to a cultural day.'],
    'scenic-train-journeys' => ['early' => '5:30 AM: Arrive at station early for the best window seats on the observation carriage.', 'mid' => '12:00 PM: Train passes through tea plantations and hill-country tunnels. Buy snacks from vendors at stations.', 'late' => '3:00 PM: Arrive at destination station. Transfer to hotel with time for a shower and walk.', 'evening' => '7:00 PM: Dinner overlooking the hills. The train ride remains the highlight of the day.'],
    'tea-country-and-plantations' => ['early' => '7:00 AM: Morning walk through a working tea estate — watch pickers at work and learn about the flush cycle.', 'mid' => '12:00 PM: Factory tour & tasting session. Single-origin Ceylon tea straight from the source.', 'late' => '2:00 PM: Drive through rolling tea-covered hills with photo stops at viewpoints.', 'evening' => '5:00 PM: Sunset at a tea estate viewpoint. Cool hill-country air and fresh tea.'],
    'village-food-and-cooking' => ['early' => '8:00 AM: Visit a local market to source fresh ingredients — spices, vegetables, and tropical fruit.', 'mid' => '11:00 AM: Hands-on cooking class in a village home. Learn to make curry powder from scratch.', 'late' => '1:00 PM: Lunch — the meal you helped prepare. Traditional rice and curry eaten by hand.', 'evening' => '4:00 PM: Village walk to meet craftspeople or visit a local farm.'],
    'beaches-and-coastal-life-sri-lanka' => ['early' => '7:00 AM: Early morning beach walk — calm seas, soft sand, and turtle tracks in the sand.', 'mid' => '11:00 AM: Snorkelling or swimming. Return to hotel for lunch and shade during peak heat.', 'late' => '3:00 PM: Second beach session. Try kayaking, paddleboarding, or simply read under a palm.', 'evening' => '5:30 PM: Sunset drinks at a beach bar or Coconut Tree Hill. Dinner at a seaside restaurant.'],
    'waterfalls-and-hill-country-views' => ['early' => '6:00 AM: Depart early for the waterfall trail — best light and fewer crowds at popular falls.', 'mid' => '11:00 AM: Swim in natural pools at the base of the waterfall. Picnic lunch by the water.', 'late' => '2:00 PM: Continue to a viewpoint or second waterfall. Hill-country roads wind through tea estates.', 'evening' => '5:00 PM: Return to accommodation. Dinner with panoramic hill-country views.'],
    'soft-adventure' => ['early' => '6:00 AM: Kayaking on a calm river or lagoon — birdlife is active and the water is glassy.', 'mid' => '12:00 PM: Lunch at a riverside café. Rest during the hottest part of the day.', 'late' => '3:00 PM: Cycling through rural villages or hiking a short trail with a local guide.', 'evening' => '6:00 PM: Sunset from a hilltop or riverbank. Light activity level, big scenery.'],
    'ayurveda-and-wellness' => ['early' => '6:00 AM: Morning yoga or meditation session. Herbal tea before the first treatment.', 'mid' => '10:00 AM: Ayurvedic consultation with a practitioner — customize your treatment plan.', 'late' => '3:00 PM: Full-body Abhyanga massage (warm oil treatment) followed by herbal steam bath.', 'evening' => '6:00 PM: Light dinner of Ayurvedic cuisine. Early night recommended for maximum benefit.'],
    'hidden-gems-by-private-driver' => ['early' => '7:00 AM: Depart for the first hidden gem — a local market, village temple, or viewpoint not in guidebooks.', 'mid' => '12:00 PM: Lunch at a family-run restaurant off the tourist trail. Home-style cooking.', 'late' => '2:00 PM: Second hidden location — an old colonial building, secluded beach, or waterfall.', 'evening' => '5:00 PM: Return to your hotel. The beauty of this day is the flexibility to follow your curiosity.'],
    'sri-lanka-surf-tour' => ['early' => '6:00 AM: Morning surf session — glassy conditions, uncrowded lineups, and the best waves of the day.', 'mid' => '11:00 AM: Brunch and rest. Watch other surfers or take a theory session on reading waves.', 'late' => '3:00 PM: Second surf session at a different break. Focus on technique or new skills.', 'evening' => '6:00 PM: Sunset ritual with the surf community. Dinner at a beachfront café.'],
    'sigiriya-pidurangala-sunrise' => ['early' => '5:00 AM: Leave hotel for Pidurangala Rock — the 20-minute climb rewards with a front-row seat to sunrise over Sigiriya.', 'mid' => '8:00 AM: Breakfast at your hotel. Rest after the early start.', 'late' => '2:00 PM: Optional Sigiriya Rock climb (separate day) or visit Dambulla Cave Temple instead.', 'evening' => '5:00 PM: Village safari or bullock-cart ride through the paddy fields around Sigiriya.'],
    'kandy-to-ella-train' => ['early' => '6:00 AM: Transfer to Kandy railway station. Board the scenic train — reserve observation car seats in advance.', 'mid' => '12:00 PM: Train weaves through tea estates and misty mountains. Passengers hang out of open doorways for photos.', 'late' => '3:00 PM: Arrive at Ella station. Brief walk to your hotel with views of the valley.', 'evening' => '6:00 PM: Sunset at a viewpoint café. Dinner — Ella has excellent Sri Lankan and international options.'],
    'yala-leopard-safari' => ['early' => '5:00 AM: Pre-dawn departure for Yala. First in line at the park gate — early birds spot the most leopards.', 'mid' => '10:00 AM: Park breakfast stop at Yala\'s beach. Return to hotel by noon for rest.', 'late' => '3:00 PM: Afternoon safari targets waterholes where animals gather as temperatures drop.', 'evening' => '6:30 PM: Golden-hour sightings. Return after sunset — your guide will recap the day\'s sightings.'],
    'udawalawe-elephant-safari' => ['early' => '5:30 AM: Morning safari — elephants are most active at dawn. Herds of 20+ are common near the reservoir.', 'mid' => '11:00 AM: Visit the Elephant Transit Home to watch orphaned elephants being fed.', 'late' => '2:00 PM: Rest at your hotel or by the pool. Afternoon heat sends elephants to shade.', 'evening' => '4:00 PM: Short afternoon drive or preparation for departure.'],
    'minneriya-elephant-gathering' => ['early' => '6:00 AM: Morning game drive in Kaudulla National Park — fewer vehicles and active herds grazing.', 'mid' => '11:00 AM: Return to hotel for breakfast and rest. The elephants rest midday too.', 'late' => '2:00 PM: Afternoon Minneriya safari (peak Gathering time: 3-6 PM). Up to 300 elephants visible.', 'evening' => '6:30 PM: Return as sunset colours the reservoir. One of Sri Lanka\'s most unforgettable wildlife experiences.'],
    'galle-fort-southern-coast' => ['early' => '7:00 AM: Galle Fort walk along the ramparts — lighthouse to Flag Rock. The morning light is beautiful.', 'mid' => '12:00 PM: Lunch at a fort restaurant. Explore boutiques, galleries, and the Old Dutch Hospital.', 'late' => '3:00 PM: Optional: stilt fisherman photo stop or beach time at Unawatuna (10 min drive).', 'evening' => '5:30 PM: Sunset on the fort walls. Dinner at a rooftop restaurant overlooking the Indian Ocean.'],
    'mirissa-whale-watching' => ['early' => '5:00 AM: Check-in at Mirissa harbour. Board the boat for a 6 AM departure — calmest seas are at dawn.', 'mid' => '9:00 AM: Blue whale sightings peak mid-morning. Breakfast served on board between sightings.', 'late' => '11:00 AM: Return to harbour. Rest at your hotel or visit Coconut Tree Hill for photos.', 'evening' => '4:00 PM: Relax on Mirissa Beach. Early dinner recommended after the early start.'],
    'tea-country-nuwara-eliya' => ['early' => '6:00 AM: Visit a tea factory before the tour buses arrive — see the withering, rolling, and firing process.', 'mid' => '12:00 PM: Lunch at the Hill Club or Grand Hotel — colonial-era ambiance and good food.', 'late' => '2:00 PM: Gregory Lake walk or boating. Visit Victoria Park for birdwatching.', 'evening' => '5:00 PM: Sunset at Single Tree Hill. Cool evening air — jacket recommended. Dinner with a fireplace.'],
    'south-coast-surf-lessons' => ['early' => '7:00 AM: Beginner surf lesson — calm morning conditions and gentle waves at Weligama Bay.', 'mid' => '11:00 AM: Beach break after the session. Lunch at a beachfront café.', 'late' => '3:00 PM: Practice session with instructor feedback. Video analysis of your technique.', 'evening' => '5:00 PM: Free time for swimming or beach walks. The best part of surf lessons is the relaxed pace.'],
    'sri-lankan-food-market-tour' => ['early' => '6:00 AM: Market tour at its liveliest — vendors arrive, fresh produce glistens, and the spice aisle is fragrant.', 'mid' => '10:00 AM: Cooking demo with market ingredients. Learn to make kottu roti or hoppers.', 'late' => '12:00 PM: Enjoy the meal you helped create. Fresh, authentic, and rewarding.', 'evening' => '4:00 PM: Free time to explore or rest. Food tours usually wrap up by early afternoon.'],
    'ayurveda-wellness-retreat' => ['early' => '6:00 AM: Morning herbal tea and yoga. Set intentions for the day with your wellness practitioner.', 'mid' => '10:00 AM: Ayurvedic treatment — Shirodhara (warm oil on forehead) or full-body massage.', 'late' => '2:00 PM: Rest period. Light lunch of Ayurvedic dishes. Herbal steam bath.', 'evening' => '5:00 PM: Guided meditation or nature walk. Early dinner and early bed — the Ayurvedic way.'],
    'buddhist-heritage-pilgrimage' => ['early' => '5:00 AM: Participate in morning offerings at the main temple — almsgiving to monks is a profound experience.', 'mid' => '11:00 AM: Temple circuit — visit 3-4 significant stupas and shrines with a local guide.', 'late' => '2:00 PM: Monastery visit to understand monastic life. Discussion with a resident monk if available.', 'evening' => '5:00 PM: Evening puja (prayer) session. The sound of chanting in an ancient temple is meditative.'],
    'honeymoon-beach-boutique-stays' => ['early' => '7:00 AM: Sunrise swim in calm, warm seas. The beach is quiet and intimate in the early hours.', 'mid' => '11:00 AM: Couple\'s spa treatment — side-by-side massages in an open-air pavilion.', 'late' => '3:00 PM: Relaxation by the pool or a walk to a secluded cove. No schedule, no rush.', 'evening' => '6:00 PM: Private sunset dinner on the beach. Candles, fresh seafood, and the sound of waves.'],
    'luxury-slow-travel-sri-lanka' => ['early' => '7:00 AM: Leisurely breakfast in your villa or suite. No early starts — this is slow travel.', 'mid' => '11:00 AM: Curated experience — private art tour, cigar tasting, or chef-led market visit.', 'late' => '3:00 PM: Pool or spa time. Your chauffeur is on standby for any spontaneous outing.', 'evening' => '6:00 PM: Sundowners at a private viewpoint. Dinner at the island\'s best restaurant — reservation arranged.'],
    'sinharaja-rainforest' => ['early' => '5:00 AM: Pre-dawn start for birdwatching — endemic species are most vocal at first light.', 'mid' => '9:00 AM: Full rainforest trek with a mandatory guide. Waterfall stop and natural pool swim.', 'late' => '12:00 PM: Packed lunch in the forest. Listen for the Sri Lanka Blue Magpie\'s distinctive call.', 'evening' => '3:00 PM: Return to the entrance. Rest at your eco-lodge. Evening wildlife walk if energy permits.'],
];

foreach ($experiences as $slug => $arc) {
    try {
        $check = fetch_one("SELECT id FROM experiences WHERE slug = :slug", ['slug' => $slug]);
        if (!$check) { echo "SKIP EX {$slug} — not found\n"; continue; }
        execute_query("UPDATE experiences SET day_arc_json = :daj WHERE slug = :slug", [
            'daj' => $j($arc), 'slug' => $slug,
        ]);
        echo "EX {$slug}\n"; ++$updated;
    } catch (\Throwable $e) { echo "FAIL EX {$slug}: {$e->getMessage()}\n"; ++$errors; }
}

echo "\nDone. Updated: {$updated}, Errors: {$errors}\n";
exit($errors > 0 ? 1 : 0);
