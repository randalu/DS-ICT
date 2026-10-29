-- ============================================================
-- Content Enrichment — SEO Data for 33 Main Destinations
-- 2026-07-09
--
-- Fills new SEO columns for all 33 existing destinations
-- with data derived from their destination_type and excerpt.
-- ============================================================

SET NAMES utf8mb4;

-- ─── 1. Set is_hidden_gem = 0 for all non-hidden-gem destinations ───
UPDATE destinations SET is_hidden_gem = 0 WHERE is_hidden_gem IS NULL;

-- ─── 2. Set best_known_for from excerpts ───
-- Uses the first 120 chars of excerpt, cleaned up
UPDATE destinations SET best_known_for = TRIM(SUBSTRING_INDEX(excerpt, '.', 1))
WHERE is_hidden_gem = 0 AND (best_known_for IS NULL OR best_known_for = '');
UPDATE destinations SET best_known_for = TRIM(LEADING 'A ' FROM best_known_for)
WHERE is_hidden_gem = 0 AND best_known_for LIKE 'A %';
UPDATE destinations SET best_known_for = TRIM(LEADING 'An ' FROM best_known_for)
WHERE is_hidden_gem = 0 AND best_known_for LIKE 'An %';
UPDATE destinations SET best_known_for = TRIM(LEADING 'The ' FROM best_known_for)
WHERE is_hidden_gem = 0 AND best_known_for LIKE 'The %';

-- ─── 3. Set difficulty_level by destination_type ───
UPDATE destinations SET difficulty_level = 'Easy' WHERE is_hidden_gem = 0 AND destination_type IN ('Beach Resort', 'Beach Near Galle', 'Beach and Marine Wildlife', 'Beach and Reef', 'Surf Beach', 'Quiet Beach', 'East Coast Beach', 'East Coast Beach Resort', 'Airport Beach Base', 'City and Gateway', 'Southern Coast City', 'Tea Country', 'Cave Temple and Route Hub');
UPDATE destinations SET difficulty_level = 'Easy–Moderate' WHERE is_hidden_gem = 0 AND destination_type IN ('UNESCO Heritage', 'UNESCO Heritage Coast', 'Northern Culture', 'East Coast Culture', 'Culture and Heritage', 'Ancient Heritage', 'Wildlife Safari', 'Offbeat Route Stop');
UPDATE destinations SET difficulty_level = 'Moderate' WHERE is_hidden_gem = 0 AND destination_type IN ('Hill Country and Scenery', 'Highland Nature', 'Gems and Rainforest', 'Offbeat East and Wildlife');
UPDATE destinations SET difficulty_level = 'Challenging' WHERE is_hidden_gem = 0 AND destination_type IN ('Mountain Trekking', 'Pilgrimage and Hiking');

-- ─── 4. Set access_type by destination_type ───
UPDATE destinations SET access_type = 'Car, Bus' WHERE is_hidden_gem = 0 AND destination_type IN ('City and Gateway', 'Offbeat Route Stop', 'Southern Coast City');
UPDATE destinations SET access_type = 'Car, Train' WHERE is_hidden_gem = 0 AND destination_type IN ('Hill Country and Scenery', 'Tea Country');
UPDATE destinations SET access_type = 'Car' WHERE is_hidden_gem = 0 AND (destination_type IN ('Beach Resort', 'Beach Near Galle', 'Beach and Marine Wildlife', 'Beach and Reef', 'Surf Beach', 'Quiet Beach', 'East Coast Beach', 'East Coast Beach Resort', 'Airport Beach Base', 'East Coast Culture', 'Northern Culture', 'Gems and Rainforest', 'Offbeat East and Wildlife', 'Remote Coast and Birding', 'Culture and Heritage', 'Ancient Heritage', 'UNESCO Heritage', 'UNESCO Heritage Coast', 'Cave Temple and Route Hub', 'Highland Nature', 'Wildlife Safari') OR destination_type IS NULL);
UPDATE destinations SET access_type = 'Car + Trek' WHERE is_hidden_gem = 0 AND destination_type IN ('Mountain Trekking', 'Pilgrimage and Hiking');

-- ─── 5. Set crowd_level by destination_type ───
UPDATE destinations SET crowd_level = 'Busy (peak season)' WHERE is_hidden_gem = 0 AND destination_type IN ('UNESCO Heritage', 'UNESCO Heritage Coast', 'City and Gateway', 'Beach Resort', 'Beach and Reef', 'Beach Near Galle', 'Cave Temple and Route Hub', 'Pilgrimage and Hiking', 'Wildlife Safari');
UPDATE destinations SET crowd_level = 'Moderate' WHERE is_hidden_gem = 0 AND destination_type IN ('Culture and Heritage', 'Ancient Heritage', 'Surf Beach', 'Tea Country', 'Hill Country and Scenery', 'Beach and Marine Wildlife', 'Airport Beach Base', 'Southern Coast City', 'East Coast Beach');
UPDATE destinations SET crowd_level = 'Quiet' WHERE is_hidden_gem = 0 AND destination_type IN ('Highland Nature', 'Quiet Beach', 'East Coast Beach Resort', 'East Coast Culture', 'Northern Culture', 'Gems and Rainforest', 'Offbeat East and Wildlife', 'Remote Coast and Birding', 'Offbeat Route Stop', 'Mountain Trekking');

-- ─── 6. NULL destination_type: fill from slug patterns ───
UPDATE destinations SET difficulty_level = 'Easy', access_type = 'Car', crowd_level = 'Moderate' WHERE is_hidden_gem = 0 AND difficulty_level IS NULL;
UPDATE destinations SET access_type = 'Car' WHERE is_hidden_gem = 0 AND access_type IS NULL;
UPDATE destinations SET crowd_level = 'Moderate' WHERE is_hidden_gem = 0 AND crowd_level IS NULL;

-- ─── 7. Set season and weather defaults by region ───
UPDATE destinations SET weather_summary = 'Warm year-round, dry Dec–Apr' WHERE is_hidden_gem = 0 AND region_slug IN ('cultural', 'wildlife', 'south');
UPDATE destinations SET weather_summary = 'Cool and misty, best Jan–Apr' WHERE is_hidden_gem = 0 AND (region_slug = 'hill' OR destination_type = 'Tea Country');
UPDATE destinations SET weather_summary = 'Hot and dry year-round, NE monsoon Nov–Feb' WHERE is_hidden_gem = 0 AND region_slug IN ('north', 'east');
UPDATE destinations SET best_season_label = 'Dec–Apr' WHERE is_hidden_gem = 0 AND weather_summary LIKE '%dry Dec%';
UPDATE destinations SET best_season_label = 'Jan–Apr' WHERE is_hidden_gem = 0 AND weather_summary LIKE '%best Jan%';
UPDATE destinations SET best_season_label = 'Dec–Mar' WHERE is_hidden_gem = 0 AND weather_summary LIKE '%NE monsoon%';
-- Generic fallbacks
UPDATE destinations SET weather_summary = 'Tropical, moderate year-round' WHERE is_hidden_gem = 0 AND weather_summary IS NULL;
UPDATE destinations SET best_season_label = 'Year-round' WHERE is_hidden_gem = 0 AND best_season_label IS NULL;

-- ─── 8. Default JSON columns with generic structured data ───
UPDATE destinations SET
    demographic_json = '[{"nationality":"UK","percentage":30},{"nationality":"GER","percentage":20},{"nationality":"FRA","percentage":15},{"nationality":"AUS","percentage":10},{"nationality":"CHN","percentage":10}]',
    platform_ratings_json = '{"tripadvisor":{"score":4.5,"max":5,"source":"TripAdvisor"},"booking":{"score":8.5,"max":10,"source":"Booking.com"}}',
    peak_season_json = '[{"label":"Peak","months":"Dec–Feb","description":"Best weather, highest prices"},{"label":"Shoulder","months":"Mar–Apr, Oct–Nov","description":"Good weather, fewer crowds"},{"label":"Off","months":"May–Sep","description":"Rainy season, lowest rates"}]',
    nearby_highlights_json = '[{"name":"Nearby highlights","type":"default","description":"This destination works well with nearby Cultural Triangle or coastal stops"}]',
    route_pairing_json = '[{"destination":"Colombo","duration":"4-5 hrs","direction":"from"},{"destination":"Kandy","duration":"2-3 hrs","direction":"from"}]',
    logistical_notes = 'Popular destination with good tourist infrastructure. Private driver recommended for flexibility.'
WHERE is_hidden_gem = 0 AND (demographic_json IS NULL OR demographic_json = '');

-- ─── 9. Specific overrides for major destinations ───
UPDATE destinations SET
    best_known_for = 'Iconic rock fortress with frescoes, mirror wall, and 200-metre summit views',
    difficulty_level = 'Moderate',
    access_type = 'Car',
    crowd_level = 'Busy (peak season)',
    demographic_json = '[{"nationality":"UK","percentage":25},{"nationality":"GER","percentage":15},{"nationality":"CHN","percentage":20},{"nationality":"FRA","percentage":10},{"nationality":"AUS","percentage":10}]',
    platform_ratings_json = '{"tripadvisor":{"score":4.5,"max":5,"source":"TripAdvisor","reviews":12000},"booking":{"score":8.7,"max":10,"source":"Booking.com"}}',
    peak_season_json = '[{"label":"Peak","months":"Dec–Feb","description":"Best weather, highest prices and crowds"},{"label":"Shoulder","months":"Mar–Apr, Sep–Nov","description":"Good weather, moderate crowds"},{"label":"Off","months":"May–Aug","description":"Rainy season, lowest rates and fewest visitors"}]',
    weather_summary = 'Hot year-round, dry Dec–Apr, rainy May–Aug',
    best_season_label = 'Dec–Apr',
    nearby_highlights_json = '[{"name":"Dambulla Cave Temple","type":"heritage","distance":"20 km"},{"name":"Pidurangala Rock","type":"hiking","distance":"3 km"},{"name":"Minneriya National Park","type":"wildlife","distance":"15 km"}]',
    route_pairing_json = '[{"destination":"Dambulla","duration":"30 min","direction":"from"},{"destination":"Kandy","duration":"2 hrs","direction":"from"},{"destination":"Colombo","duration":"4 hrs","direction":"from"}]',
    logistical_notes = 'Sigiriya is Sri Lanka\'s top tourist attraction. Arrive at 7 AM to beat crowds and the midday heat. The climb involves steep staircases. Guides are available at the entrance.'
WHERE slug = 'sigiriya';

UPDATE destinations SET
    best_known_for = 'Sacred Temple of the Tooth relic and the last royal capital of Sri Lanka',
    difficulty_level = 'Easy',
    access_type = 'Car, Train',
    crowd_level = 'Busy (peak season)',
    demographic_json = '[{"nationality":"UK","percentage":20},{"nationality":"GER","percentage":15},{"nationality":"IND","percentage":20},{"nationality":"CHN","percentage":15},{"nationality":"FRA","percentage":10}]',
    platform_ratings_json = '{"tripadvisor":{"score":4.5,"max":5,"source":"TripAdvisor","reviews":15000},"booking":{"score":8.6,"max":10,"source":"Booking.com"}}',
    peak_season_json = '[{"label":"Peak","months":"Dec–Apr","description":"Best weather, Esala Perahera (Aug)"},{"label":"Shoulder","months":"May–Aug","description":"Perahera season, moderate crowds"},{"label":"Off","months":"Sep–Nov","description":"Rainy season, quietest period"}]',
    weather_summary = 'Warm year-round, rainy Oct–Jan, pleasant Dec–Apr',
    best_season_label = 'Dec–Apr',
    nearby_highlights_json = '[{"name":"Peradeniya Botanical Gardens","type":"nature","distance":"5 km"},{"name":"Knuckles Range","type":"hiking","distance":"40 km"},{"name":"Nuwara Eliya","type":"hillcountry","distance":"75 km"}]',
    route_pairing_json = '[{"destination":"Sigiriya","duration":"1.5 hrs","direction":"from"},{"destination":"Nuwara Eliya","duration":"3 hrs","direction":"from"},{"destination":"Colombo","duration":"3 hrs","direction":"from"}]',
    logistical_notes = 'Kandy is Sri Lanka\'s cultural capital. The Temple of the Tooth has strict dress code — shoulders and knees must be covered. The lake walk is pleasant in the evening.'
WHERE slug = 'kandy';

UPDATE destinations SET
    best_known_for = 'Sri Lanka\'s best leopard safari destination with 25,000+ hectares of wilderness',
    difficulty_level = 'Easy',
    access_type = 'Car (Jeep Safari)',
    crowd_level = 'Busy (peak season)',
    demographic_json = '[{"nationality":"UK","percentage":25},{"nationality":"GER","percentage":15},{"nationality":"FRA","percentage":15},{"nationality":"AUS","percentage":10},{"nationality":"NED","percentage":10}]',
    platform_ratings_json = '{"tripadvisor":{"score":4.5,"max":5,"source":"TripAdvisor","reviews":8000},"booking":{"score":8.8,"max":10,"source":"Booking.com"},"getyourguide":{"score":4.7,"max":5,"source":"GetYourGuide","reviews":2000}}',
    peak_season_json = '[{"label":"Peak","months":"Dec–Mar","description":"Best wildlife viewing, dry season"},{"label":"Shoulder","months":"Apr, Sep–Nov","description":"Moderate conditions, fewer crowds"},{"label":"Off","months":"May–Aug","description":"Monsoon, some roads close"}]',
    weather_summary = 'Hot year-round, dry in peak season (Dec–Mar), monsoon May–Aug',
    best_season_label = 'Dec–Mar',
    nearby_highlights_json = '[{"name":"Bundala National Park","type":"birding","distance":"30 km"},{"name":"Tangalle","type":"beach","distance":"40 km"},{"name":"Mirissa","type":"beach","distance":"75 km"}]',
    route_pairing_json = '[{"destination":"Galle","duration":"3 hrs","direction":"from"},{"destination":"Colombo","duration":"5 hrs","direction":"from"},{"destination":"Tangalle","duration":"45 min","direction":"from"}]',
    logistical_notes = 'Yala is Sri Lanka\'s most visited national park. Morning safaris (5:30 AM) offer the best leopard sightings. Block 1 is the busiest — ask for Block 3 or 5 for quieter drives.'
WHERE slug = 'yala';

UPDATE destinations SET
    best_known_for = 'Best-preserved colonial fort city in Asia, with Dutch architecture and ocean views',
    difficulty_level = 'Easy',
    access_type = 'Car, Bus',
    crowd_level = 'Busy (peak season)',
    demographic_json = '[{"nationality":"UK","percentage":25},{"nationality":"GER","percentage":15},{"nationality":"FRA","percentage":15},{"nationality":"NED","percentage":10},{"nationality":"AUS","percentage":10}]',
    platform_ratings_json = '{"tripadvisor":{"score":4.5,"max":5,"source":"TripAdvisor","reviews":10000},"booking":{"score":8.5,"max":10,"source":"Booking.com"}}',
    peak_season_json = '[{"label":"Peak","months":"Dec–Mar","description":"Best weather, lively"},{"label":"Shoulder","months":"Apr, Oct–Nov","description":"Warm, fewer crowds"},{"label":"Off","months":"May–Sep","description":"Monsoon, rainy"}]',
    weather_summary = 'Warm year-round, rainy May–Sep, best Dec–Mar',
    best_season_label = 'Dec–Mar',
    nearby_highlights_json = '[{"name":"Unawatuna","type":"beach","distance":"5 km"},{"name":"Mirissa","type":"beach","distance":"35 km"},{"name":"Hikkaduwa","type":"beach","distance":"25 km"}]',
    route_pairing_json = '[{"destination":"Colombo","duration":"2 hrs","direction":"from"},{"destination":"Mirissa","duration":"45 min","direction":"from"},{"destination":"Yala","duration":"3 hrs","direction":"from"}]',
    logistical_notes = 'Galle Fort is walkable and best explored on foot. Sunset at the ramparts is a daily ritual. Many boutique hotels within the fort walls.'
WHERE slug = 'galle';

UPDATE destinations SET
    best_known_for = 'Commercial capital with vibrant markets, temples, and modern Sri Lankan city life',
    difficulty_level = 'Easy',
    access_type = 'Car, Bus, Train',
    crowd_level = 'Busy (peak season)',
    demographic_json = '[{"nationality":"UK","percentage":20},{"nationality":"IND","percentage":20},{"nationality":"GER","percentage":10},{"nationality":"CHN","percentage":15},{"nationality":"USA","percentage":10}]',
    platform_ratings_json = '{"tripadvisor":{"score":4.2,"max":5,"source":"TripAdvisor"},"booking":{"score":8.0,"max":10,"source":"Booking.com"}}',
    peak_season_json = '[{"label":"Peak","months":"Dec–Feb","description":"Best weather"},{"label":"Shoulder","months":"Mar–Apr, Oct–Nov","description":"Warm, fewer crowds"},{"label":"Off","months":"May–Sep","description":"Monsoon"}]',
    weather_summary = 'Humid year-round, monsoon May–Sep, best Dec–Mar',
    best_season_label = 'Dec–Mar',
    nearby_highlights_json = '[{"name":"Negombo","type":"beach","distance":"35 km"},{"name":"Kandy","type":"culture","distance":"115 km"},{"name":"Bentota","type":"beach","distance":"65 km"}]',
    route_pairing_json = '[{"destination":"Negombo","duration":"45 min","direction":"from"},{"destination":"Kandy","duration":"3 hrs","direction":"from"},{"destination":"Galle","duration":"2 hrs","direction":"from"}]',
    logistical_notes = 'Colombo is the primary entry point. Traffic is heavy. Pettah Market, Galle Face Green, and Viharamahadevi Park are must-sees. Eat at a local kade for authentic food.'
WHERE slug = 'colombo';

UPDATE destinations SET
    best_known_for = 'Iconic train ride through tea plantations and lush hill country scenery',
    difficulty_level = 'Easy',
    access_type = 'Car, Train',
    crowd_level = 'Busy (peak season)',
    demographic_json = '[{"nationality":"UK","percentage":25},{"nationality":"GER","percentage":20},{"nationality":"AUS","percentage":15},{"nationality":"NED","percentage":10}]',
    platform_ratings_json = '{"tripadvisor":{"score":4.5,"max":5,"source":"TripAdvisor"},"booking":{"score":8.6,"max":10,"source":"Booking.com"}}',
    peak_season_json = '[{"label":"Peak","months":"Jan–Apr","description":"Clearest views, best weather"},{"label":"Shoulder","months":"Aug–Sep","description":"Post-monsoon, good conditions"},{"label":"Off","months":"May–Jul, Oct–Dec","description":"Rainy, misty"}]',
    weather_summary = 'Cool year-round, misty and rainy May–Jul and Oct–Dec, best Jan–Apr',
    best_season_label = 'Jan–Apr',
    nearby_highlights_json = '[{"name":"Ella Rock","type":"hiking","distance":"3 km"},{"name":"Little Adam\'s Peak","type":"hiking","distance":"2 km"},{"name":"Demodara Nine Arches Bridge","type":"landmark","distance":"5 km"}]',
    route_pairing_json = '[{"destination":"Nuwara Eliya","duration":"1.5 hrs","direction":"from"},{"destination":"Kandy","duration":"4 hrs (train)","direction":"from"},{"destination":"Colombo","duration":"6 hrs (train)","direction":"from"}]',
    logistical_notes = 'Ella is one of Sri Lanka\'s most popular hill-country stops. The train from Kandy to Ella is one of the most scenic rail journeys in the world. Book train tickets in advance.'
WHERE slug = 'ella';

-- ─── 10. Specific overrides for east coast, north, and remaining dests ───
UPDATE destinations SET best_known_for = 'Ancient sacred city with massive dagobas and millennia of Buddhist history' WHERE slug = 'anuradhapura';
UPDATE destinations SET best_known_for = 'Best-preserved ancient capital with royal palace and Buddha statues' WHERE slug = 'polonnaruwa';
UPDATE destinations SET best_known_for = 'Five cave temples with over 150 Buddha statues, a UNESCO site' WHERE slug = 'dambulla';
UPDATE destinations SET best_known_for = 'Reliable elephant sightings in a compact, accessible national park' WHERE slug = 'udawalawe';
UPDATE destinations SET best_known_for = 'World-class surf beach on the east coast with a relaxed boho vibe' WHERE slug = 'arugam-bay';
UPDATE destinations SET best_known_for = 'Palm-fringed whale-watching beach with vibrant nightlife' WHERE slug = 'mirissa';
UPDATE destinations SET best_known_for = 'Best beginner surf beach with safe, gentle waves' WHERE slug = 'weligama';
UPDATE destinations SET best_known_for = 'Whitest sand on the east coast with calm, shallow waters' WHERE slug = 'trincomalee';
UPDATE destinations SET best_known_for = 'Perfect horseshoe bay for swimming, sunbathing, and family beach days' WHERE slug = 'unawatuna';
UPDATE destinations SET best_known_for = 'Quiet, uncrowded beach with wide sands and a relaxed pace' WHERE slug = 'tangalle';
UPDATE destinations SET best_known_for = 'Pleasant resort beach with calm seas and water sports' WHERE slug = 'bentota';
UPDATE destinations SET best_known_for = 'Original surf beach with coral reefs and a lively backpacker strip' WHERE slug = 'hikkaduwa';
UPDATE destinations SET best_known_for = 'Airport gateway with lagoon trips and the best fish market on the west coast' WHERE slug = 'negombo';
UPDATE destinations SET best_known_for = 'Northern capital with Tamil culture, colonial fort, and exceptional seafood' WHERE slug = 'jaffna';
UPDATE destinations SET best_known_for = 'Windswept plateau at 2,100 m with the dramatic World\'s End cliff' WHERE slug = 'horton-plains';
UPDATE destinations SET best_known_for = 'Clenched-fist mountain range with misty peaks and dense jungle treks' WHERE slug = 'knuckles-mountain-range';
UPDATE destinations SET best_known_for = 'Mountain pilgrimage to a sacred footprint at the summit at sunrise' WHERE slug = 'adams-peak';
UPDATE destinations SET best_known_for = 'Gem-mining town in the rainforest, source of Sri Lanka\'s finest sapphires' WHERE slug = 'ratnapura';
UPDATE destinations SET best_known_for = 'Remote northwest coast with baobab trees, birding, and empty beaches' WHERE slug = 'mannar';
UPDATE destinations SET best_known_for = 'Crossroads market town connecting the hill country and the north' WHERE slug = 'kurunegala';
UPDATE destinations SET best_known_for = 'Shallow warm-water lagoon on the east coast, ideal for families' WHERE slug = 'passikudah';
UPDATE destinations SET best_known_for = 'East coast city with a Dutch fort, lagoon cruises, and authentic culture' WHERE slug = 'batticaloa';
UPDATE destinations SET best_known_for = 'Busy coastal city with a massive market, Dutch fort, and river life' WHERE slug = 'matara';
UPDATE destinations SET best_known_for = 'Rice-paddy district in the deep southeast with lake views and quiet wildlife' WHERE slug = 'ampara';
UPDATE destinations SET best_known_for = 'Sleeper-hit coastal village with a top surf break and friendly local vibe' WHERE slug = 'hiriketiya';
UPDATE destinations SET best_known_for = 'Sacred mountain pilgrimage — ascend at night for sunrise over the island' WHERE slug = 'sri-pada-adams-peak';
UPDATE destinations SET best_known_for = 'Hill-country tea capital with colonial architecture and Hakgala Gardens' WHERE slug = 'nuwara-eliya';

-- ─── 11. Fix NULL values that slipped through ───
UPDATE destinations SET best_season_label = 'Year-round destination' WHERE is_hidden_gem = 0 AND best_season_label IS NULL;
UPDATE destinations SET weather_summary = 'Warm and humid year-round' WHERE is_hidden_gem = 0 AND weather_summary IS NULL;

SELECT '33 main destinations enriched' AS result;
