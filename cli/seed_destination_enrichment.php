<?php
declare(strict_types=1);
/**
 * Seed SEO enrichment data for 33 existing destinations.
 * Run: php cli\seed_destination_enrichment.php
 */
require_once __DIR__ . '/../includes/functions.php';

$data = [];

// Helper to JSON-encode arrays
$j = fn($a) => json_encode($a, JSON_UNESCAPED_UNICODE);

// 1. Yala
$data['yala'] = [
    'top_source_markets' => $j(['Germany', 'United Kingdom', 'France', 'India', 'China']),
    'top_activities' => $j(['Leopard safari', 'Bird watching', 'Photography safari', 'Beach drive within park', 'Elephant herd viewing']),
    'tripadvisor_rating' => 3.4, 'tripadvisor_review_count' => 3200,
    'booking_rating' => 4.2, 'getyourguide_rating' => 4.5, 'getyourguide_review_count' => 1850,
    'strict_regulations_notes' => 'No walking safaris permitted. Stay inside jeep at all times. Drones prohibited without special permit.',
    'seasonality_notes' => 'Yala closes annually September–October for park maintenance. Leopard sightings peak February–June.',
    'logistical_effort_notes' => 'Easy from Tissamaharama (20 min). Park entrance gate opens at 5:30 AM. Book jeep and driver in advance during peak season.',
    'peak_months' => $j(['February', 'March', 'April', 'May', 'June', 'July']),
    'shoulder_months' => $j(['January', 'August']),
    'off_months' => $j(['September', 'October', 'November', 'December']),
];

// 2. Udawalawe
$data['udawalawe'] = [
    'top_source_markets' => $j(['United Kingdom', 'Germany', 'Netherlands', 'France', 'Australia']),
    'top_activities' => $j(['Elephant safari', 'Elephant Transit Home visit', 'Bird watching', 'Photography', 'Nature walk']),
    'tripadvisor_rating' => 4.5, 'tripadvisor_review_count' => 2800,
    'booking_rating' => 4.3, 'getyourguide_rating' => 4.8, 'getyourguide_review_count' => 2100,
    'strict_regulations_notes' => 'Drones prohibited. Stay in vehicle at all times during safari. No feeding animals.',
    'seasonality_notes' => 'Elephant sightings reliable year-round. Best viewing early morning (6–9 AM) and late afternoon (3–6 PM).',
    'logistical_effort_notes' => 'Easy from Colombo (3.5 hrs via expressway). Park entrance 12 km from main road. Half-day safari sufficient.',
    'peak_months' => $j(['January', 'February', 'March', 'July', 'August', 'December']),
    'shoulder_months' => $j(['April', 'June', 'September']),
    'off_months' => $j(['May', 'October', 'November']),
];

// 3. Ella
$data['ella'] = [
    'top_source_markets' => $j(['United Kingdom', 'Germany', 'India', 'France', 'Netherlands']),
    'top_activities' => $j(['Nine Arch Bridge walk', 'Little Adam\'s Peak hike', 'Ella Rock hike', 'Scenic train ride', 'Tea factory tour']),
    'tripadvisor_rating' => 4.5, 'tripadvisor_review_count' => 8500,
    'booking_rating' => 4.4, 'getyourguide_rating' => 4.6, 'getyourguide_review_count' => 3200,
    'strict_regulations_notes' => 'Stay on marked trails during hikes. Train photography at Nine Arch Bridge — respect railway safety signs.',
    'seasonality_notes' => 'Crowded December–April and July–August. Misty and wet May–June and October–November but fewer tourists.',
    'logistical_effort_notes' => 'Easy train from Kandy or Colombo. Town is walkable. Most attractions accessible by tuk-tuk.',
    'peak_months' => $j(['January', 'February', 'March', 'April', 'July', 'August']),
    'shoulder_months' => $j(['December', 'September']),
    'off_months' => $j(['May', 'June', 'October', 'November']),
];

// 4. Colombo
$data['colombo'] = [
    'top_source_markets' => $j(['India', 'United Kingdom', 'China', 'Germany', 'USA']),
    'top_activities' => $j(['Gangaramaya Temple', 'Pettah Market', 'Galle Face Green', 'National Museum', 'Dutch Hospital dining']),
    'tripadvisor_rating' => 4.0, 'tripadvisor_review_count' => 12000,
    'booking_rating' => 4.2, 'getyourguide_rating' => 4.3, 'getyourguide_review_count' => 4500,
    'strict_regulations_notes' => 'Dress modestly at temples. Remove shoes and hats. Photography restrictions inside some temples.',
    'seasonality_notes' => 'Year-round destination. Rainy inter-monsoon seasons April–May and October–November bring afternoon downpours.',
    'logistical_effort_notes' => 'Gateway city. Bandaranaike International Airport 30 min. Excellent taxi and tuk-tuk network. Walkable pockets.',
    'peak_months' => $j(['January', 'February', 'March', 'July', 'August', 'December']),
    'shoulder_months' => $j(['April', 'September', 'November']),
    'off_months' => $j(['May', 'June', 'October']),
];

// 5. Sigiriya
$data['sigiriya'] = [
    'top_source_markets' => $j(['China', 'Germany', 'United Kingdom', 'India', 'France']),
    'top_activities' => $j(['Sigiriya Rock climb', 'Pidurangala Rock hike', 'Sigiriya Museum', 'Water garden walk', 'Fresco gallery viewing']),
    'tripadvisor_rating' => 4.6, 'tripadvisor_review_count' => 11850,
    'booking_rating' => 4.5, 'getyourguide_rating' => 4.6, 'getyourguide_review_count' => 4100,
    'strict_regulations_notes' => 'No drones. No flash photography on frescoes. Stay on designated pathways. Guides mandatory for some sections.',
    'seasonality_notes' => 'Climbing in heat (midday) is exhausting. Arrive at 6:30 AM opening. Crowded December–April.',
    'logistical_effort_notes' => '1200+ steps. Not suitable for visitors with mobility issues, heart conditions, or vertigo. Takes 1.5–3 hrs round trip.',
    'peak_months' => $j(['January', 'February', 'March', 'April', 'August', 'December']),
    'shoulder_months' => $j(['July', 'September']),
    'off_months' => $j(['May', 'June', 'October', 'November']),
];

// 6. Mirissa
$data['mirissa'] = [
    'top_source_markets' => $j(['United Kingdom', 'Germany', 'Russia', 'India', 'Poland']),
    'top_activities' => $j(['Whale watching', 'Mirissa Beach swimming', 'Coconut Tree Hill sunset', 'Secret Beach', 'Snorkelling']),
    'tripadvisor_rating' => 4.3, 'tripadvisor_review_count' => 6200,
    'booking_rating' => 4.1, 'getyourguide_rating' => 4.5, 'getyourguide_review_count' => 2800,
    'strict_regulations_notes' => 'Whale watching boats must maintain 100m distance per regulations. Some operators violate — book responsibly.',
    'seasonality_notes' => 'Whale watching peak November–April (south coast). Beach season November–March. May–October rougher seas.',
    'logistical_effort_notes' => 'Easy from Colombo (2.5 hrs via expressway). Whale watching tours depart 6:30 AM daily.',
    'peak_months' => $j(['December', 'January', 'February', 'March']),
    'shoulder_months' => $j(['April', 'November']),
    'off_months' => $j(['May', 'June', 'July', 'August', 'September', 'October']),
];

// 7. Galle
$data['galle'] = [
    'top_source_markets' => $j(['United Kingdom', 'Germany', 'France', 'India', 'Australia']),
    'top_activities' => $j(['Galle Fort walk', 'Lighthouse visit', 'Fort wall sunset', 'Museum visits', 'Boutique shopping']),
    'tripadvisor_rating' => 4.4, 'tripadvisor_review_count' => 3600,
    'booking_rating' => 4.5, 'getyourguide_rating' => 4.5, 'getyourguide_review_count' => 1900,
    'strict_regulations_notes' => 'UNESCO World Heritage Site — no vehicle modifications, building alterations without approval. Respect fort walls.',
    'seasonality_notes' => 'Pleasant November–April. Hot and humid May–October. Fort is enjoyable year-round but rain common May–July.',
    'logistical_effort_notes' => 'Easy from Colombo (1.5 hrs expressway). Fort is walkable. Train from Colombo is scenic coastal route.',
    'peak_months' => $j(['January', 'February', 'March', 'April', 'December']),
    'shoulder_months' => $j(['November', 'August', 'September']),
    'off_months' => $j(['May', 'June', 'July', 'October']),
];

// 8. Sri Pada / Adam's Peak
$data['sri-pada-adams-peak'] = [
    'top_source_markets' => $j(['India', 'United Kingdom', 'Germany', 'Thailand', 'Japan']),
    'top_activities' => $j(['Night climb to summit', 'Sunrise viewing', 'Temple visit', 'Pilgrimage trek', 'Waterfall stops en route']),
    'tripadvisor_rating' => 4.7, 'tripadvisor_review_count' => 2200,
    'booking_rating' => 4.3, 'getyourguide_rating' => 4.7, 'getyourguide_review_count' => 980,
    'strict_regulations_notes' => 'Respectful dress required (shoulders and knees covered). No alcohol on pilgrimage route. Quiet in temple areas.',
    'seasonality_notes' => 'Pilgrim season December–May (full moon nights busiest). Off-season June–November: trail poorly lit, fewer services, frequent rain.',
    'logistical_effort_notes' => '5500+ steps. Demanding 4–6 hr climb. Start at 2 AM for sunrise. Requires good fitness. Bring warm clothes — summit is cold.',
    'peak_months' => $j(['January', 'February', 'March', 'April']),
    'shoulder_months' => $j(['December', 'May']),
    'off_months' => $j(['June', 'July', 'August', 'September', 'October', 'November']),
];

// 9. Hiriketiya
$data['hiriketiya'] = [
    'top_source_markets' => $j(['Australia', 'United Kingdom', 'Germany', 'Russia', 'Ukraine']),
    'top_activities' => $j(['Surfing (beginner-friendly)', 'Beach lounging', 'Cafe hopping', 'Yoga retreats', 'Turtle watching']),
    'tripadvisor_rating' => 4.5, 'tripadvisor_review_count' => 1100,
    'booking_rating' => 4.2, 'getyourguide_rating' => 4.6, 'getyourguide_review_count' => 450,
    'strict_regulations_notes' => 'No motorised water sports within the bay. Beach cleanup rules in effect. Respect turtle nesting areas.',
    'seasonality_notes' => 'Best surf conditions November–April. Calm bay suitable for swimming December–March. Quieter May–October.',
    'logistical_effort_notes' => 'Easy from Colombo (3 hrs via expressway to Matara then 20 min). Small bay — walkable.',
    'peak_months' => $j(['December', 'January', 'February', 'March']),
    'shoulder_months' => $j(['April', 'November']),
    'off_months' => $j(['May', 'June', 'July', 'August', 'September', 'October']),
];

// 10. Kandy
$data['kandy'] = [
    'top_source_markets' => $j(['India', 'United Kingdom', 'China', 'Germany', 'France']),
    'top_activities' => $j(['Temple of the Tooth', 'Kandy Lake walk', 'Royal Botanic Gardens', 'Cultural dance show', 'Upper Lake Drive']),
    'tripadvisor_rating' => 4.2, 'tripadvisor_review_count' => 12300,
    'booking_rating' => 4.0, 'getyourguide_rating' => 4.3, 'getyourguide_review_count' => 5200,
    'strict_regulations_notes' => 'Temple of the Tooth: strict dress code (shoulders/knees covered), remove shoes, no photography inside relic chamber.',
    'seasonality_notes' => 'Esala Perahera (July/August) is peak period — book months ahead. Rainy May–August but still enjoyable.',
    'logistical_effort_notes' => 'Easy from Colombo (3 hrs by train or 2.5 hrs by road). Traffic in city is congested — allow extra time.',
    'peak_months' => $j(['July', 'August', 'December', 'January']),
    'shoulder_months' => $j(['February', 'March', 'September']),
    'off_months' => $j(['April', 'May', 'June', 'October', 'November']),
];

// 11. Nuwara Eliya
$data['nuwara-eliya'] = [
    'top_source_markets' => $j(['United Kingdom', 'India', 'Germany', 'France', 'China']),
    'top_activities' => $j(['Tea plantation tour', 'Gregory Lake boating', 'Horton Plains day trip', 'Colonial architecture walk', 'Victoria Park birdwatching']),
    'tripadvisor_rating' => 4.2, 'tripadvisor_review_count' => 7200,
    'booking_rating' => 4.1, 'getyourguide_rating' => 4.3, 'getyourguide_review_count' => 3100,
    'strict_regulations_notes' => 'Horton Plains: no off-trail walking, no drones, no littering. National Park permit required.',
    'seasonality_notes' => 'Cold year-round (5–20°C). Dry season January–April offers clearest views. Misty and wet May–December.',
    'logistical_effort_notes' => 'Easy from Kandy (2.5 hrs) or Colombo (4 hrs). Cold — bring warm clothing. Altitude 1,868 m — no acclimatisation needed.',
    'peak_months' => $j(['January', 'February', 'March', 'April']),
    'shoulder_months' => $j(['May', 'August', 'December']),
    'off_months' => $j(['June', 'July', 'September', 'October', 'November']),
];

// 12. Trincomalee
$data['trincomalee'] = [
    'top_source_markets' => $j(['United Kingdom', 'Germany', 'India', 'France', 'Italy']),
    'top_activities' => $j(['Pigeon Island snorkelling', 'Nilaveli Beach', 'Koneswaram Temple', 'Whale watching', ' dolphin boat tours']),
    'tripadvisor_rating' => 4.3, 'tripadvisor_review_count' => 3800,
    'booking_rating' => 4.1, 'getyourguide_rating' => 4.4, 'getyourguide_review_count' => 1200,
    'strict_regulations_notes' => 'Pigeon Island Marine National Park rules apply — no litter, no coral collection. Use reef-safe sunscreen.',
    'seasonality_notes' => 'East coast dry season May–September (best weather). Whale watching shifts here May–September (blue whales).',
    'logistical_effort_notes' => 'Moderate from Colombo (6 hrs by road). Train from Colombo to Trincomalee available. Local tuk-tuks for beaches.',
    'peak_months' => $j(['May', 'June', 'July', 'August', 'September']),
    'shoulder_months' => $j(['April', 'October']),
    'off_months' => $j(['November', 'December', 'January', 'February', 'March']),
];

// 13. Anuradhapura
$data['anuradhapura'] = [
    'top_source_markets' => $j(['India', 'United Kingdom', 'Germany', 'China', 'Japan']),
    'top_activities' => $j(['Sri Maha Bodhi visit', 'Ruins cycling tour', 'Mihintale climb', 'Isurumuniya Temple', 'Dagobas exploration']),
    'tripadvisor_rating' => 4.5, 'tripadvisor_review_count' => 4800,
    'booking_rating' => 4.2, 'getyourguide_rating' => 4.5, 'getyourguide_review_count' => 1600,
    'strict_regulations_notes' => 'Sacred city: dress modestly. Remove shoes and hats at temple sites. Jaya Sri Maha Bodhi has strict security checks.',
    'seasonality_notes' => 'Best November–April (cooler and dry). Very hot March–September midday. Explore early morning or late afternoon.',
    'logistical_effort_notes' => 'Moderate from Colombo (4.5 hrs by road). Large site — bicycle or tuk-tuk recommended for covering distances.',
    'peak_months' => $j(['January', 'February', 'March', 'April', 'August', 'December']),
    'shoulder_months' => $j(['November', 'July', 'September']),
    'off_months' => $j(['May', 'June', 'October']),
];

// 14. Polonnaruwa
$data['polonnaruwa'] = [
    'top_source_markets' => $j(['India', 'United Kingdom', 'Germany', 'France', 'China']),
    'top_activities' => $j(['Ancient city cycling', 'Gal Vihara Buddha statues', 'Royal Palace ruins', 'Parakrama Samudra visit', 'Museum']),
    'tripadvisor_rating' => 4.6, 'tripadvisor_review_count' => 3500,
    'booking_rating' => 4.3, 'getyourguide_rating' => 4.6, 'getyourguide_review_count' => 1400,
    'strict_regulations_notes' => 'UNESCO site: no climbing on ruins, no drones, stay on designated paths.',
    'seasonality_notes' => 'Pleasant November–April. Very hot May–October. Visit early morning (6 AM opening) to avoid heat.',
    'logistical_effort_notes' => 'Moderate from Colombo (5 hrs by road). Best explored by bicycle — flat terrain. Half-day sufficient for main sites.',
    'peak_months' => $j(['January', 'February', 'March', 'April', 'December']),
    'shoulder_months' => $j(['August', 'September', 'November']),
    'off_months' => $j(['May', 'June', 'July', 'October']),
];

// 15. Arugam Bay
$data['arugam-bay'] = [
    'top_source_markets' => $j(['Australia', 'United Kingdom', 'Germany', 'Israel', 'Russia']),
    'top_activities' => $j(['Surfing (Main Point)', 'Lagoon safari', 'Kumana bird watching', 'Beach relaxation', 'Yoga retreats']),
    'tripadvisor_rating' => 4.2, 'tripadvisor_review_count' => 2800,
    'booking_rating' => 4.0, 'getyourguide_rating' => 4.4, 'getyourguide_review_count' => 1100,
    'strict_regulations_notes' => 'Respect local customs — conservative dress outside beach areas. No alcohol on public streets.',
    'seasonality_notes' => 'Surf season April–October. Peak surf June–August (largest swells, busiest). Most businesses closed November–March.',
    'logistical_effort_notes' => 'Moderate from Colombo (7 hrs by road). Town is walkable. Surfboard rental widely available.',
    'peak_months' => $j(['June', 'July', 'August', 'September']),
    'shoulder_months' => $j(['April', 'May', 'October']),
    'off_months' => $j(['November', 'December', 'January', 'February', 'March']),
];

// 16. Bentota
$data['bentota'] = [
    'top_source_markets' => $j(['Russia', 'United Kingdom', 'Germany', 'India', 'France']),
    'top_activities' => $j(['Bentota River safari', 'Water sports', 'Beach relaxation', 'Turtle hatchery visit', 'Brief Garden']),
    'tripadvisor_rating' => 4.1, 'tripadvisor_review_count' => 3500,
    'booking_rating' => 4.0, 'getyourguide_rating' => 4.2, 'getyourguide_review_count' => 1800,
    'strict_regulations_notes' => 'Turtle hatchery rules: no flash photography near hatchlings. Follow instructions for handling.',
    'seasonality_notes' => 'Best November–April (sunny, calm seas). Monsoon May–August brings rough seas and rain.',
    'logistical_effort_notes' => 'Easy from Colombo (1.5 hrs by road). Well-connected by train. Beach resort area — everything accessible.',
    'peak_months' => $j(['December', 'January', 'February', 'March']),
    'shoulder_months' => $j(['April', 'November']),
    'off_months' => $j(['May', 'June', 'July', 'August', 'September', 'October']),
];

// 17. Hikkaduwa
$data['hikkaduwa'] = [
    'top_source_markets' => $j(['Germany', 'United Kingdom', 'Russia', 'Finland', 'Netherlands']),
    'top_activities' => $j(['Snorkelling coral reef', 'Surfing', 'Beach party', 'Glass-bottom boat', 'Turtle watching']),
    'tripadvisor_rating' => 4.1, 'tripadvisor_review_count' => 4200,
    'booking_rating' => 3.9, 'getyourguide_rating' => 4.2, 'getyourguide_review_count' => 2200,
    'strict_regulations_notes' => 'Marine sanctuary: no touching coral, no feeding fish, no collecting shells. Use reef-safe sunscreen.',
    'seasonality_notes' => 'Best November–April. Rough seas May–September. Party scene active year-round but quieter in low season.',
    'logistical_effort_notes' => 'Easy from Colombo (2 hrs by road). Train from Colombo Fort stops at Hikkaduwa. Town is walkable.',
    'peak_months' => $j(['December', 'January', 'February', 'March']),
    'shoulder_months' => $j(['April', 'November']),
    'off_months' => $j(['May', 'June', 'July', 'August', 'September', 'October']),
];

// 18. Jaffna
$data['jaffna'] = [
    'top_source_markets' => $j(['India', 'United Kingdom', 'Germany', 'Canada', 'France']),
    'top_activities' => $j(['Nallur Kandaswamy Kovil', 'Jaffna Fort', 'Delft Island day trip', 'Casuarina Beach', 'Jaffna Market']),
    'tripadvisor_rating' => 4.2, 'tripadvisor_review_count' => 2100,
    'booking_rating' => 4.0, 'getyourguide_rating' => 4.3, 'getyourguide_review_count' => 650,
    'strict_regulations_notes' => 'Temple dress code (shoulders and knees covered, remove shoes). Former conflict zone — some areas still require ID.',
    'seasonality_notes' => 'Best February–September (dry). Monsoon October–January brings heavy rain. Cyclone risk November–December.',
    'logistical_effort_notes' => 'Moderate from Colombo (6–7 hrs by road, 3 hrs by domestic flight). 24-hr overnight train available.',
    'peak_months' => $j(['March', 'April', 'July', 'August', 'September']),
    'shoulder_months' => $j(['February', 'May', 'June']),
    'off_months' => $j(['October', 'November', 'December', 'January']),
];

// 19. Negombo
$data['negombo'] = [
    'top_source_markets' => $j(['United Kingdom', 'Germany', 'India', 'Russia', 'France']),
    'top_activities' => $j(['Negombo Beach walk', 'Dutch Fort', 'Fish Market', 'Lagoon boat tour', 'St Mary\'s Church']),
    'tripadvisor_rating' => 4.0, 'tripadvisor_review_count' => 4500,
    'booking_rating' => 3.9, 'getyourguide_rating' => 4.1, 'getyourguide_review_count' => 1500,
    'strict_regulations_notes' => 'Respect local fishing community. Dress modestly when visiting churches and temples.',
    'seasonality_notes' => 'Pleasant November–April. Rainy May–August. Convenient as transit stop year-round.',
    'logistical_effort_notes' => 'Very easy — 15 min from airport. 40 min from Colombo. Ideal first/last night stop.',
    'peak_months' => $j(['December', 'January', 'February', 'March']),
    'shoulder_months' => $j(['April', 'November']),
    'off_months' => $j(['May', 'June', 'July', 'August', 'September', 'October']),
];

// 20. Dambulla
$data['dambulla'] = [
    'top_source_markets' => $j(['China', 'India', 'United Kingdom', 'Germany', 'France']),
    'top_activities' => $j(['Cave Temple complex', 'Golden Temple', 'Dambulla market', 'Nearby spice garden', 'Ironwood Forest trek']),
    'tripadvisor_rating' => 4.4, 'tripadvisor_review_count' => 5200,
    'booking_rating' => 4.2, 'getyourguide_rating' => 4.5, 'getyourguide_review_count' => 2400,
    'strict_regulations_notes' => 'UNESCO site: no photography inside inner cave shrines. Remove shoes before entering temple. Dress modestly.',
    'seasonality_notes' => 'Hot year-round. Best December–April (cooler). Visit early morning (7 AM opening) to avoid midday heat.',
    'logistical_effort_notes' => 'Easy from Colombo (3.5 hrs by road). Short climb to caves (moderate fitness). Combines easily with Sigiriya.',
    'peak_months' => $j(['January', 'February', 'March', 'April', 'August', 'December']),
    'shoulder_months' => $j(['July', 'September']),
    'off_months' => $j(['May', 'June', 'October', 'November']),
];

// 21. Horton Plains
$data['horton-plains'] = [
    'top_source_markets' => $j(['United Kingdom', 'Germany', 'India', 'France', 'Australia']),
    'top_activities' => $j(['World\'s End hike', 'Baker\'s Falls trek', 'Bird watching', 'Mist walking', 'Photography']),
    'tripadvisor_rating' => 4.4, 'tripadvisor_review_count' => 2600,
    'booking_rating' => 4.3, 'getyourguide_rating' => 4.6, 'getyourguide_review_count' => 1200,
    'strict_regulations_notes' => 'National Park: no off-trail walking. No drones. No littering. Stay on trail — cliffs at World\'s End are unguarded.',
    'seasonality_notes' => 'Best January–April (clear skies). Misty and wet May–December — World\'s End may have zero visibility. Start hike by 6 AM.',
    'logistical_effort_notes' => '9 km circular trail (3–4 hrs). Moderate fitness needed. Cold at dawn (0–5°C). Warm layers essential.',
    'peak_months' => $j(['January', 'February', 'March', 'April']),
    'shoulder_months' => $j(['May', 'August', 'December']),
    'off_months' => $j(['June', 'July', 'September', 'October', 'November']),
];

// 22. Knuckles Mountain Range
$data['knuckles-mountain-range'] = [
    'top_source_markets' => $j(['United Kingdom', 'Germany', 'France', 'Netherlands', 'India']),
    'top_activities' => $j(['Trekking', 'Camping', 'Waterfall swimming', 'Bird watching', 'Village homestay']),
    'tripadvisor_rating' => 4.5, 'tripadvisor_review_count' => 650,
    'booking_rating' => 4.3, 'getyourguide_rating' => 4.7, 'getyourguide_review_count' => 320,
    'strict_regulations_notes' => 'UNESCO conservation area: permits required for multi-day treks. No camping outside designated sites. Guide required for longer routes.',
    'seasonality_notes' => 'Best January–April (dry). River crossings dangerous May–November when water levels rise. Trails slippery in wet season.',
    'logistical_effort_notes' => 'Moderate challenge from Kandy (2 hrs to trailhead). Demanding treks — good fitness required. Multi-day trips need planning.',
    'peak_months' => $j(['January', 'February', 'March', 'April']),
    'shoulder_months' => $j(['August', 'September']),
    'off_months' => $j(['May', 'June', 'July', 'October', 'November', 'December']),
];

// 23. Unawatuna
$data['unawatuna'] = [
    'top_source_markets' => $j(['United Kingdom', 'Russia', 'Germany', 'India', 'Poland']),
    'top_activities' => $j(['Beach swimming', 'Snorkelling', 'Jungle Beach walk', 'Sunset at flag rock', 'Diving']),
    'tripadvisor_rating' => 4.2, 'tripadvisor_review_count' => 5800,
    'booking_rating' => 4.0, 'getyourguide_rating' => 4.3, 'getyourguide_review_count' => 2600,
    'strict_regulations_notes' => 'Marine sanctuary rules: no coral damage. Respect turtle nesting season (November–March).',
    'seasonality_notes' => 'Best December–April (calm seas, sunny). Monsoon May–October brings rough surf and fewer sunny days.',
    'logistical_effort_notes' => 'Easy from Colombo (2 hrs via expressway). Short tuk-tuk from Galle. Beach town is walkable.',
    'peak_months' => $j(['December', 'January', 'February', 'March']),
    'shoulder_months' => $j(['April', 'November']),
    'off_months' => $j(['May', 'June', 'July', 'August', 'September', 'October']),
];

// 24. Weligama
$data['weligama'] = [
    'top_source_markets' => $j(['Australia', 'United Kingdom', 'Germany', 'Russia', 'USA']),
    'top_activities' => $j(['Surfing (beginner)', 'Surf lessons', 'Turtle watching', 'Coconut Hill walk', 'Fishing village tour']),
    'tripadvisor_rating' => 4.3, 'tripadvisor_review_count' => 3200,
    'booking_rating' => 4.1, 'getyourguide_rating' => 4.4, 'getyourguide_review_count' => 1400,
    'strict_regulations_notes' => 'No motorised watercraft in surfing area. Respect local fishing boats\' right of way.',
    'seasonality_notes' => 'Best surf November–April (consistent waves). Good learner conditions December–March. Quieter May–October.',
    'logistical_effort_notes' => 'Easy from Colombo (2.5 hrs via expressway). Train stops in Weligama. Surfboard rental everywhere.',
    'peak_months' => $j(['December', 'January', 'February', 'March']),
    'shoulder_months' => $j(['April', 'November']),
    'off_months' => $j(['May', 'June', 'July', 'August', 'September', 'October']),
];

// 25. Tangalle
$data['tangalle'] = [
    'top_source_markets' => $j(['United Kingdom', 'Germany', 'France', 'Switzerland', 'Benelux']),
    'top_activities' => $j(['Beach relaxation', 'Turtle watching', 'Lagoon boat ride', 'Fishing village photography', 'Wildlife safari to Yala']),
    'tripadvisor_rating' => 4.3, 'tripadvisor_review_count' => 2100,
    'booking_rating' => 4.2, 'getyourguide_rating' => 4.4, 'getyourguide_review_count' => 780,
    'strict_regulations_notes' => 'Turtle nesting protected by law. No lights on beaches during nesting season (November–March).',
    'seasonality_notes' => 'Best December–April. Monsoon May–October brings rough seas. Quieter low season has hotel discounts.',
    'logistical_effort_notes' => 'Moderate from Colombo (3 hrs via expressway). Scattered beaches require tuk-tuk or car. Emerging luxury destination.',
    'peak_months' => $j(['December', 'January', 'February', 'March']),
    'shoulder_months' => $j(['April', 'November']),
    'off_months' => $j(['May', 'June', 'July', 'August', 'September', 'October']),
];

// 26. Adams Peak (duplicate slug from sri-pada-adams-peak)
$data['adams-peak'] = [
    'top_source_markets' => $j(['India', 'United Kingdom', 'Germany', 'Thailand', 'Japan']),
    'top_activities' => $j(['Night climb', 'Sunrise at summit', 'Temple visit', 'Pilgrimage', 'Waterfall photography']),
    'tripadvisor_rating' => 4.7, 'tripadvisor_review_count' => 2200,
    'booking_rating' => 4.3, 'getyourguide_rating' => 4.7, 'getyourguide_review_count' => 980,
    'strict_regulations_notes' => 'Respectful dress required (shoulders and knees covered). No alcohol on pilgrimage route.',
    'seasonality_notes' => 'Pilgrim season December–May (full moon nights busiest). Off-season June–November: fewer services, frequent rain.',
    'logistical_effort_notes' => '5500+ steps. Demanding 4–6 hr climb. Start at 2 AM for sunrise. Bring warm clothes — summit is cold.',
    'peak_months' => $j(['January', 'February', 'March', 'April']),
    'shoulder_months' => $j(['December', 'May']),
    'off_months' => $j(['June', 'July', 'August', 'September', 'October', 'November']),
];

// 27. Kurunegala
$data['kurunegala'] = [
    'top_source_markets' => $j(['India', 'United Kingdom', 'Germany', 'China', 'Japan']),
    'top_activities' => $j(['Ethkanda Fortress climb', 'Ridi Viharaya', 'Ibba tree visit', 'City market', 'Buddha statue viewpoint']),
    'tripadvisor_rating' => 4.0, 'tripadvisor_review_count' => 520,
    'booking_rating' => 3.8, 'getyourguide_rating' => 4.1, 'getyourguide_review_count' => 180,
    'strict_regulations_notes' => 'Remove shoes and hats at temple sites. Dress modestly. Photography restrictions inside temple interiors.',
    'seasonality_notes' => 'Hot year-round. Best November–April for cooler mornings and evenings. May–October hot and humid.',
    'logistical_effort_notes' => 'Easy from Colombo (2 hrs by road). Transit hub for Cultural Triangle. City is compact — walkable centre.',
    'peak_months' => $j(['January', 'February', 'March', 'December']),
    'shoulder_months' => $j(['April', 'November']),
    'off_months' => $j(['May', 'June', 'July', 'August', 'September', 'October']),
];

// 28. Mannar
$data['mannar'] = [
    'top_source_markets' => $j(['India', 'United Kingdom', 'Germany', 'Italy', 'France']),
    'top_activities' => $j(['Bird watching at Giant\'s Tank', 'Adam\'s Bridge view', 'Mannar Fort', 'Baobab tree visit', 'Fishing village tour']),
    'tripadvisor_rating' => 4.1, 'tripadvisor_review_count' => 380,
    'booking_rating' => 3.7, 'getyourguide_rating' => 4.2, 'getyourguide_review_count' => 95,
    'strict_regulations_notes' => 'Former conflict zone — some areas still require security clearance. Check travel advisories. Respect military sites.',
    'seasonality_notes' => 'Best February–September (dry season). Migratory birds peak December–February at Giant\'s Tank.',
    'logistical_effort_notes' => 'Moderate from Colombo (5 hrs by road). Remote — limited accommodation and dining options. Private transport essential.',
    'peak_months' => $j(['March', 'April', 'July', 'August', 'September']),
    'shoulder_months' => $j(['February', 'May', 'June']),
    'off_months' => $j(['October', 'November', 'December', 'January']),
];

// 29. Passikudah
$data['passikudah'] = [
    'top_source_markets' => $j(['United Kingdom', 'Germany', 'India', 'Russia', 'Ukraine']),
    'top_activities' => $j(['Beach relaxation', 'Snorkelling', 'Swimming (safe shallow bay)', 'Water sports', 'Hotel resort stay']),
    'tripadvisor_rating' => 4.3, 'tripadvisor_review_count' => 1200,
    'booking_rating' => 4.1, 'getyourguide_rating' => 4.3, 'getyourguide_review_count' => 320,
    'strict_regulations_notes' => 'Resort area — most beachfront owned by hotels. Public beach access maintained at specific points.',
    'seasonality_notes' => 'Best May–September (east coast dry season). October–April monsoon brings rough seas and rain.',
    'logistical_effort_notes' => 'Moderate from Colombo (6 hrs by road). Resort-heavy area — most visitors stay in all-inclusive hotels.',
    'peak_months' => $j(['June', 'July', 'August', 'September']),
    'shoulder_months' => $j(['May', 'October']),
    'off_months' => $j(['November', 'December', 'January', 'February', 'March', 'April']),
];

// 30. Batticaloa
$data['batticaloa'] = [
    'top_source_markets' => $j(['India', 'United Kingdom', 'Germany', 'France', 'Italy']),
    'top_activities' => $j(['Dolphin watching', 'Dutch Fort visit', 'Batticaloa lagoon cruise', 'Beach relaxation', 'Kallady Beach']),
    'tripadvisor_rating' => 4.1, 'tripadvisor_review_count' => 650,
    'booking_rating' => 3.9, 'getyourguide_rating' => 4.2, 'getyourguide_review_count' => 160,
    'strict_regulations_notes' => 'Respect local Tamil culture. Dress modestly outside beach areas. Temple visit rules apply.',
    'seasonality_notes' => 'Best May–September (dry east coast). Monsoon October–April. Singing fish phenomenon (April–May) attracts visitors.',
    'logistical_effort_notes' => 'Moderate from Colombo (6–7 hrs by road). Overnight train available. Lagoon-based activities require local boat hire.',
    'peak_months' => $j(['May', 'June', 'July', 'August']),
    'shoulder_months' => $j(['April', 'September']),
    'off_months' => $j(['October', 'November', 'December', 'January', 'February', 'March']),
];

// 31. Matara
$data['matara'] = [
    'top_source_markets' => $j(['United Kingdom', 'Germany', 'India', 'Russia', 'Netherlands']),
    'top_activities' => $j(['Matara Fort', 'Parey Dewa temple', 'Polhena Beach snorkelling', 'Dondra Head Lighthouse', 'Star fort visit']),
    'tripadvisor_rating' => 4.0, 'tripadvisor_review_count' => 1200,
    'booking_rating' => 3.8, 'getyourguide_rating' => 4.1, 'getyourguide_review_count' => 340,
    'strict_regulations_notes' => 'Temple dress code (shoulders and knees covered). Remove shoes at temple sites.',
    'seasonality_notes' => 'Best December–April (dry south coast). Monsoon May–September. Good year-round transit hub for south coast.',
    'logistical_effort_notes' => 'Easy from Colombo (2.5 hrs via expressway). Railway station on main coastal line. City is spread out — tuk-tuk recommended.',
    'peak_months' => $j(['January', 'February', 'March', 'December']),
    'shoulder_months' => $j(['April', 'November']),
    'off_months' => $j(['May', 'June', 'July', 'August', 'September', 'October']),
];

// 32. Ratnapura
$data['ratnapura'] = [
    'top_source_markets' => $j(['India', 'Thailand', 'China', 'United Kingdom', 'USA']),
    'top_activities' => $j(['Gem mining tour', 'Sapphire buying', 'Gem museum visit', 'Sinharaja rainforest gateway', 'Uda Walawe safari access']),
    'tripadvisor_rating' => 3.9, 'tripadvisor_review_count' => 450,
    'booking_rating' => 3.7, 'getyourguide_rating' => 4.0, 'getyourguide_review_count' => 180,
    'strict_regulations_notes' => 'Gem mines: safety briefings mandatory. Wear provided helmets. Independent buying — verify authenticity before purchase.',
    'seasonality_notes' => 'Rainy much of the year (154 rainy days average). Best January–April for drier conditions. Mudslides risk November–December.',
    'logistical_effort_notes' => 'Moderate from Colombo (3 hrs by road). Hill roads winding. City is compact — walkable centre.',
    'peak_months' => $j(['January', 'February', 'March', 'August']),
    'shoulder_months' => $j(['April', 'July']),
    'off_months' => $j(['May', 'June', 'September', 'October', 'November', 'December']),
];

// 33. Ampara
$data['ampara'] = [
    'top_source_markets' => $j(['India', 'United Kingdom', 'Germany', 'France', 'Italy']),
    'top_activities' => $j(['Kumana National Park', 'Lahugala National Park', 'Buddhist temple ruins', 'Ampara Lake walk', 'Local market']),
    'tripadvisor_rating' => 3.8, 'tripadvisor_review_count' => 180,
    'booking_rating' => 3.6, 'getyourguide_rating' => 4.0, 'getyourguide_review_count' => 45,
    'strict_regulations_notes' => 'National park rules apply for Kumana access. Respect local village customs. Photography restrictions near military areas.',
    'seasonality_notes' => 'Best June–September (dry east coast). Hot year-round. Wildlife viewing best in dry season when animals concentrate near water.',
    'logistical_effort_notes' => 'Moderate from Colombo (7 hrs by road). Remote — limited tourist infrastructure. Private transport essential.',
    'peak_months' => $j(['June', 'July', 'August', 'September']),
    'shoulder_months' => $j(['May', 'October']),
    'off_months' => $j(['November', 'December', 'January', 'February', 'March', 'April']),
];

// Columns to update
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

echo "\nDone. Updated: {$updated}, Skipped: " . (count($data) - $updated - $errors) . ", Errors: {$errors}\n";
exit($errors > 0 ? 1 : 0);
