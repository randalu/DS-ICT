-- PHASE 58-67: definitive duration guides, package intent, and tour-type pages.
-- Route windows are planning baselines. Confirm dates, traffic, weather,
-- park conditions, train availability, hotel location, and supplier terms.

SET NAMES utf8mb4;

-- Add the package content columns without failing when an older deployment
-- already contains one of them. Dynamic SQL keeps this compatible with the
-- supported MySQL/MariaDB versions.
SET @ict_tours_includes_exists = (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'tours'
      AND COLUMN_NAME = 'includes'
);
SET @ict_tours_includes_sql = IF(
    @ict_tours_includes_exists = 0,
    'ALTER TABLE tours ADD COLUMN includes LONGTEXT NULL AFTER highlights',
    'SELECT 1'
);
PREPARE ict_tours_includes_stmt FROM @ict_tours_includes_sql;
EXECUTE ict_tours_includes_stmt;
DEALLOCATE PREPARE ict_tours_includes_stmt;

SET @ict_tours_excludes_exists = (
    SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'tours'
      AND COLUMN_NAME = 'excludes'
);
SET @ict_tours_excludes_sql = IF(
    @ict_tours_excludes_exists = 0,
    'ALTER TABLE tours ADD COLUMN excludes LONGTEXT NULL AFTER includes',
    'SELECT 1'
);
PREPARE ict_tours_excludes_stmt FROM @ict_tours_excludes_sql;
EXECUTE ict_tours_excludes_stmt;
DEALLOCATE PREPARE ict_tours_excludes_stmt;

CREATE TABLE IF NOT EXISTS n_day_itinerary_pages (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    days SMALLINT UNSIGNED NOT NULL,
    title VARCHAR(255) NOT NULL,
    meta_title VARCHAR(255) NULL,
    meta_description VARCHAR(320) NULL,
    intro_html MEDIUMTEXT NULL,
    route_html MEDIUMTEXT NULL,
    nights_json LONGTEXT NULL,
    transfers_html MEDIUMTEXT NULL,
    alternatives_html MEDIUMTEXT NULL,
    pace_html MEDIUMTEXT NULL,
    seasonal_html MEDIUMTEXT NULL,
    best_for_html MEDIUMTEXT NULL,
    package_path VARCHAR(255) NULL,
    package_label VARCHAR(255) NULL,
    faq_json LONGTEXT NULL,
    is_published TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_n_day_itinerary_pages_days (days),
    KEY idx_n_day_itinerary_pages_published (is_published)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS itinerary_package_links (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    informational_path VARCHAR(255) NOT NULL,
    package_slug VARCHAR(180) NOT NULL,
    package_path VARCHAR(255) NOT NULL,
    informational_label VARCHAR(255) NOT NULL,
    package_label VARCHAR(255) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_itinerary_package_links_info_package (informational_path, package_slug),
    KEY idx_itinerary_package_links_package (package_slug),
    KEY idx_itinerary_package_links_info (informational_path),
    KEY idx_itinerary_package_links_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS theme_page_editorial (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    theme_slug VARCHAR(80) NOT NULL,
    page_title VARCHAR(255) NOT NULL,
    meta_title VARCHAR(255) NULL,
    meta_description VARCHAR(320) NULL,
    hero_image VARCHAR(255) NULL,
    intro_html MEDIUMTEXT NULL,
    route_html MEDIUMTEXT NULL,
    planning_html MEDIUMTEXT NULL,
    seasonal_html MEDIUMTEXT NULL,
    reassurance_html MEDIUMTEXT NULL,
    faq_json LONGTEXT NULL,
    cta_title VARCHAR(255) NULL,
    cta_text TEXT NULL,
    is_published TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_theme_page_editorial_slug (theme_slug),
    KEY idx_theme_page_editorial_published (is_published)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS theme_tour_links (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    theme_slug VARCHAR(80) NOT NULL,
    tour_slug VARCHAR(180) NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_theme_tour_links (theme_slug, tour_slug),
    KEY idx_theme_tour_links_active (theme_slug, is_active, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO itinerary_categories
    (name, slug, is_theme, theme_slug, sort_order, is_published)
VALUES
    ('Wildlife', 'wildlife', 1, 'wildlife-tours', 105, 1)
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    is_theme = VALUES(is_theme),
    theme_slug = VALUES(theme_slug),
    sort_order = VALUES(sort_order),
    is_published = VALUES(is_published);

UPDATE itineraries
SET category_id = (SELECT id FROM itinerary_categories WHERE theme_slug = 'wildlife-tours' LIMIT 1)
WHERE is_published = 1
  AND slug IN (
      'sri-lanka-wildlife-safari-itinerary-7-days',
      'sri-lanka-wildlife-itinerary-9-days',
      'sri-lanka-birding-nature-itinerary-12-days'
  );

INSERT INTO n_day_itinerary_pages
    (days, title, meta_title, meta_description, intro_html, route_html,
     nights_json, transfers_html, alternatives_html, pace_html, seasonal_html,
     best_for_html, package_path, package_label, faq_json, is_published)
VALUES
(
    5,
    '5-Day Sri Lanka Itinerary: Choose a Region and Do It Well',
    '5-Day Sri Lanka Itinerary | Focused Private Route',
    'Plan a realistic 5-day Sri Lanka itinerary with a focused regional route, private driver, transfer guidance, seasonal choices, and practical tradeoffs.',
    '<p>Five days is enough for a memorable Sri Lanka visit when the route is focused. It is not enough to cover the entire island comfortably. Choose culture and hill country, or choose the south coast with one wildlife or heritage day, rather than spending the trip in the vehicle.</p><p>This guide is an information page for route planning. A final private itinerary should follow your arrival time, departure airport, mobility, interests, and tolerance for hotel changes.</p>',
    '<h2>Suggested 5-day route</h2><ol><li><strong>Day 1:</strong> Arrive at Bandaranaike International Airport and travel to Sigiriya or Dambulla. A late arrival may justify an airport or Negombo night.</li><li><strong>Day 2:</strong> Sigiriya, Pidurangala, Dambulla, or a nearby cultural site. Choose one main climb and leave recovery time.</li><li><strong>Day 3:</strong> Travel to Kandy and plan the Temple of the Tooth, lake area, or a cultural visit around confirmed opening times.</li><li><strong>Day 4:</strong> Continue to Nuwara Eliya or another hill-country base for tea landscapes and a cooler overnight.</li><li><strong>Day 5:</strong> Return toward Colombo or the airport. Book an evening departure when travelling from the hills by road.</li></ol>',
    '[{"place":"Sigiriya or Dambulla","nights":2,"note":"Use this as the cultural anchor and avoid adding a far-south detour."},{"place":"Kandy","nights":1,"note":"A practical transition into the hills with one carefully chosen cultural visit."},{"place":"Nuwara Eliya or a nearby hill base","nights":1,"note":"Keep the final road movement realistic and protect the flight buffer."}]',
    '<p>Use roughly 4-5 hours from the airport to Sigiriya, 2.5-3.5 hours from Sigiriya to Kandy, 2.5-3.5 hours from Kandy to Nuwara Eliya, and about 5-6 hours from the hills back toward the airport by road. These are planning windows, not guarantees.</p>',
    '<p><strong>Culture and hills:</strong> the strongest first-timer choice. <strong>South coast:</strong> replace the Cultural Triangle and hills with Galle, a beach base, and shorter coastal movements. <strong>Wildlife:</strong> choose one park and accept that another region must be removed.</p>',
    '<p>This is an active short route with an early start and several hotel changes. It works best when the group accepts one major experience per day rather than a checklist of every attraction.</p>',
    '<p>For south and west coast time, November to April is often the easier planning season. Hill-country weather changes quickly. Wildlife, sea conditions, attraction hours, and road conditions must be checked for the travel dates.</p>',
    '<p>Best for stopovers, short annual leave, repeat visitors, and travellers who already know which region matters most. It is not the right format for people who want several beach days and a complete island circuit.</p>',
    'tours/classic-sri-lanka-private-tour-package',
    'Ask for a custom 5-day private version',
    '[{"question":"Can five days cover the whole island?","answer":"No. A focused regional route is more comfortable and more useful than trying to connect every major stop."},{"question":"Should I choose culture, hills, beach, or safari?","answer":"Choose the experience you would most regret missing, then build the route around that region and your flight times."},{"question":"Is a private driver useful for five days?","answer":"A private vehicle can make a focused route practical, but it does not remove the need for realistic travel time and rest."}]',
    1
),
(
    7,
    '7-Day Sri Lanka Itinerary: Culture, Hills and One Strong Finish',
    '7-Day Sri Lanka Itinerary | Culture, Hills and Safari or Coast',
    'Build a realistic 7-day Sri Lanka itinerary with culture, hill country, one safari or coast finish, private transport, pacing, and seasonal alternatives.',
    '<p>A week in Sri Lanka works when you make one deliberate choice at the end of the route. Culture, hills, and a safari can fit, or culture, hills, and the coast can fit. Trying to do both wildlife and a full beach circuit usually turns the holiday into a sequence of long transfers.</p><p>Use the route below as a planning framework. Your arrival time, hotel locations, train availability, and departure flight determine the final order.</p>',
    '<h2>Suggested 7-day route</h2><ol><li><strong>Day 1:</strong> Airport to Sigiriya or Habarana. Keep the arrival day light after an international flight.</li><li><strong>Day 2:</strong> Sigiriya, Dambulla, or Polonnaruwa. Choose one main cultural priority and one optional stop.</li><li><strong>Day 3:</strong> Travel to Kandy and settle into the hill-country transition.</li><li><strong>Day 4:</strong> Kandy to Nuwara Eliya or Ella. The road or scenic train choice depends on seats, luggage, and arrival timing.</li><li><strong>Day 5:</strong> A slower hill-country day with a walk, tea experience, or local viewpoint.</li><li><strong>Day 6:</strong> Choose Udawalawe for an elephant-focused wildlife finish or Galle for a south-coast and heritage finish.</li><li><strong>Day 7:</strong> Return toward the airport with a departure buffer. A late flight is safer after a southern route.</li></ol>',
    '[{"place":"Sigiriya or Habarana","nights":2,"note":"Cultural Triangle base with an arrival recovery option."},{"place":"Kandy","nights":1,"note":"Keep the city visit focused and allow time for hotel access."},{"place":"Nuwara Eliya or Ella","nights":2,"note":"Two nights protect the value of the hill-country detour."},{"place":"Udawalawe or Galle","nights":1,"note":"Select one finish rather than forcing safari and beach together."}]',
    '<p>Allow about 4-5 hours from the airport to Sigiriya, 2.5-3.5 hours from Sigiriya to Kandy, 2.5-4 hours from Kandy into the hills, 2.5-3.5 hours from Ella toward Udawalawe, and roughly 2-3 hours from Galle to the airport by expressway. Traffic, stops, weather, and the exact hotel can change the day.</p>',
    '<p><strong>Safari finish:</strong> choose Udawalawe for a practical route and elephant-focused experience. <strong>Coast finish:</strong> choose Galle or a nearby base for heritage and a more relaxed final night. <strong>Train option:</strong> use a short scenic section only when tickets and onward transfers are confirmed.</p>',
    '<p>The pace is active but manageable when there is one main move per day. Families should protect pool and rest time. Couples can trade a major attraction for a slower hill or coast day.</p>',
    '<p>South and west coast planning is commonly easier from November to April, while east-coast alternatives can be more suitable at other times. Safari sightings and train operations vary by date and conditions.</p>',
    '<p>Best for first-time visitors with one week, couples, small families, and travellers who want a representative route without pretending every region can receive equal time.</p>',
    'tours/classic-sri-lanka-private-tour-package',
    'Ask for a private 7-day route',
    '[{"question":"Should I choose safari or beach on a 7-day trip?","answer":"Choose based on the experience you value most and the season. Both can work, but fitting both at full strength usually creates unnecessary travel."},{"question":"Can I take the Kandy to Ella train?","answer":"A scenic train section can work when tickets, station transfers, luggage, and the next hotel are confirmed for the travel date."},{"question":"Is seven days suitable for a family?","answer":"Yes, when the route limits long drives, includes rest breaks, and chooses one main activity each day."}]',
    1
),
(
    9,
    '9-Day Sri Lanka Wildlife Itinerary: Three Habitats, One Focus',
    '9-Day Sri Lanka Wildlife Itinerary | Yala, Udawalawe and More',
    'Plan a wildlife-focused 9-day Sri Lanka itinerary with Yala, Udawalawe, dry-zone or wetland options, realistic safari timing, and private transfers.',
    '<p>Nine days gives wildlife travellers room to choose parks for different habitats instead of treating one safari as a guaranteed highlight. A practical route can combine the Cultural Triangle or dry-zone elephants, Udawalawe, Yala, and a short south-coast finish without promising sightings.</p><p>Wild animals, park access, weather, crowding, and safari operator standards change. Build the route around habitat and season, then keep expectations honest.</p>',
    '<h2>Suggested 9-day route</h2><ol><li><strong>Day 1:</strong> Airport to Habarana or Sigiriya.</li><li><strong>Day 2:</strong> Cultural Triangle base with a Minneriya or Kaudulla option when current conditions support it.</li><li><strong>Day 3:</strong> Travel south to Udawalawe and settle before the next safari.</li><li><strong>Day 4:</strong> Morning or afternoon Udawalawe safari with a recovery window.</li><li><strong>Day 5:</strong> Continue toward Yala or Tissamaharama and protect the park timing.</li><li><strong>Day 6:</strong> Yala safari with a second option only if the group has the energy and current access permits it.</li><li><strong>Day 7:</strong> Transfer toward Bundala, the south coast, or a quieter wildlife base.</li><li><strong>Day 8:</strong> Birding, coast, or a second safari chosen around the season.</li><li><strong>Day 9:</strong> Return toward the airport with an appropriate departure buffer.</li></ol>',
    '[{"place":"Habarana or Sigiriya","nights":2,"note":"Dry-zone culture and wildlife options without starting with a long southern drive."},{"place":"Udawalawe","nights":2,"note":"Allow one recovery period around the safari rather than stacking every activity."},{"place":"Yala or Tissamaharama","nights":2,"note":"Position the stay around the relevant park gate and safari start time."},{"place":"South coast or wetland base","nights":2,"note":"Use the final nights for another habitat or a gentler coastal finish."}]',
    '<p>Typical planning windows include 4-5 hours from the airport to the Cultural Triangle, 4-6 hours from Habarana toward Udawalawe, 2-3 hours from Udawalawe to Yala, and 2-4 hours from Yala toward the south coast. Safari start times make hotel positioning more important than a short distance on a map.</p>',
    '<p><strong>Elephant priority:</strong> give Udawalawe more space. <strong>Leopard and mixed wildlife priority:</strong> consider Yala while accepting that sightings are never guaranteed. <strong>Birding:</strong> add Bundala or another wetland when seasonal conditions and the route support it. <strong>More culture:</strong> remove one park rather than adding more driving.</p>',
    '<p>This is an early-start itinerary. Plan recovery after dawn safaris, carry practical clothing and sun protection, and avoid a long transfer immediately after a late game drive.</p>',
    '<p>Park conditions and seasonal wildlife patterns vary. Minneriya and Kaudulla are often considered around dry-season water levels, while Yala, Udawalawe, and Bundala need current park and operator checks. Keep a coast alternative for poor sea or park conditions.</p>',
    '<p>Best for wildlife photographers, birders, families with older children, and travellers who prefer patient observation to a checklist of monuments. Choose a shorter route if early mornings are not a good fit.</p>',
    'tours/sri-lanka-wildlife-safari-package',
    'Compare the wildlife private package',
    '[{"question":"Will I definitely see a leopard or elephant herd?","answer":"No wildlife sighting is guaranteed. Park, season, weather, habitat, and animal behaviour all affect what a safari finds."},{"question":"Is Yala or Udawalawe better?","answer":"Udawalawe often suits elephant-focused planning while Yala offers a different habitat and leopard interest. Choose based on priorities and current conditions."},{"question":"How many safaris should I plan?","answer":"One or two well-timed safaris can be more enjoyable than stacking several dawn starts. Leave recovery time and protect the route between parks."}]',
    1
),
(
    10,
    '10-Day Sri Lanka Itinerary: The Flagship Private Route',
    '10-Day Sri Lanka Itinerary | Definitive Private Route Guide',
    'Plan a definitive 10-day Sri Lanka itinerary with daily route logic, realistic drives, hotel-location advice, safari alternatives, train considerations, seasonal coast choices, and package planning.',
    '<p>Ten days is a strong first-trip length because it can connect culture, tea country, one wildlife area, and the coast without requiring every day to be a race. The best version still makes choices: Yala or Udawalawe, road or train through the hills, and which coast fits the travel month.</p><p>This guide explains the planning logic. The linked package page is for a quote, inclusions, vehicle, accommodation basis, and booking conversation. A published route is a starting point, not a promise that every attraction will fit every date.</p>',
    '<h2>Day-by-day 10-day route</h2><ol><li><strong>Day 1:</strong> Airport arrival and transfer to Sigiriya or Habarana. Use a recovery night when the flight arrives late.</li><li><strong>Day 2:</strong> Sigiriya, Pidurangala, Dambulla, or Polonnaruwa depending on the priority and heat.</li><li><strong>Day 3:</strong> Travel to Kandy with one purposeful stop rather than a crowded list.</li><li><strong>Day 4:</strong> Kandy and the transition toward tea country. Confirm hotel access and cultural timings.</li><li><strong>Day 5:</strong> Nuwara Eliya or another hill base. A scenic train section is optional when tickets and transfers work.</li><li><strong>Day 6:</strong> Continue to Ella or keep a slower tea-country day when the group needs it.</li><li><strong>Day 7:</strong> Transfer toward Udawalawe or Yala. Position the overnight around the next safari start.</li><li><strong>Day 8:</strong> Safari or wildlife alternative, with a recovery period after an early start.</li><li><strong>Day 9:</strong> Continue to Galle, Mirissa, or another coast selected for the season and departure plan.</li><li><strong>Day 10:</strong> Coast or heritage morning, then travel to the airport with a generous flight buffer.</li></ol>',
    '[{"place":"Sigiriya or Habarana","nights":2,"note":"Cultural Triangle anchor with time for one major site and a flexible second day."},{"place":"Kandy","nights":1,"note":"A practical bridge between the Cultural Triangle and the hills."},{"place":"Nuwara Eliya and/or Ella","nights":2,"note":"Choose road or train based on the travel date, luggage, and arrival timing."},{"place":"Yala or Udawalawe","nights":2,"note":"Select the park and lodge position around the safari objective."},{"place":"Galle, Mirissa, or another coast","nights":2,"note":"Use the final base for recovery, beach, heritage, or a departure-friendly finish."}]',
    '<p>Useful planning baselines include 4-5 hours from the airport to Sigiriya, 2.5-3.5 hours from Sigiriya to Kandy, 2.5-3.5 hours from Kandy to Nuwara Eliya, 2-3 hours from Nuwara Eliya to Ella by road, 2.5-4 hours from Ella to a safari base, and 2-3 hours from Galle to the airport by expressway. Stops, weather, traffic, road conditions, and hotel location can change the actual day.</p>',
    '<p><strong>Yala:</strong> consider it for leopard-focused interest and a park experience that needs careful timing. <strong>Udawalawe:</strong> consider it for a more direct southbound route and elephant interest. <strong>Train:</strong> use only when seats, luggage support, station transfers, and a backup road plan are confirmed. <strong>Coast:</strong> select south or west for one seasonal pattern and east for another instead of treating one beach as year-round.</p>',
    '<p>The route is moderately active with several bases. Two-night stays protect the experience, but the hill and wildlife sections still involve winding roads and early starts. Families and slower travellers can remove a stop rather than compressing every day.</p>',
    '<p>South and west coast conditions are often easier from November to April, while east-coast alternatives can be useful at other times. Tea-country weather is variable. Safari, whale watching, train seats, and attraction access require current confirmation.</p>',
    '<p>Best for first-time visitors, couples, families with a flexible pace, and long-haul travellers who want a balanced route. It suits travellers who prefer a private plan with room for adjustments rather than a fixed group departure.</p>',
    'tours/sri-lanka-10-day-itinerary-package',
    'Review the 10-day private tour package',
    '[{"question":"Is ten days enough for the main Sri Lanka route?","answer":"Ten days can cover the major culture, hill, wildlife, and coast choices when the sequence is realistic. A 12 or 14-day route gives more breathing room."},{"question":"Should I choose Yala or Udawalawe?","answer":"Choose around wildlife priorities, route position, lodge location, and current conditions. Neither park guarantees a particular sighting."},{"question":"Can I include the Kandy to Ella train?","answer":"Yes when the timetable, tickets, station transfers, luggage, and next accommodation are confirmed. A private road plan is the more predictable fallback."},{"question":"What should the quoted cost include?","answer":"Ask for a clear price basis covering vehicle and driver, accommodation basis, activities, park or train costs, meals, taxes, and exclusions. Published ranges are planning estimates, not a final quote."}]',
    1
),
(
    12,
    '12-Day Sri Lanka Itinerary: More Space, Fewer Compromises',
    '12-Day Sri Lanka Itinerary | Comfortable Private Route',
    'Plan a 12-day Sri Lanka itinerary with longer stays, culture, tea country, wildlife, beaches, seasonal alternatives, and practical private-driver pacing.',
    '<p>Twelve days is distinct from both the compact ten-day circuit and the complete fourteen-day loop. It gives the main route more breathing room: two-night anchors, a calmer hill-country section, a wildlife choice, and enough coast time to recover before departure.</p><p>Use the extra days to reduce hotel changes or to add one carefully chosen experience. Do not fill every spare day with another distant region.</p>',
    '<h2>Day-by-day 12-day route</h2><ol><li><strong>Days 1-2:</strong> Arrive and settle in Sigiriya or Habarana with one cultural highlight and one flexible day.</li><li><strong>Day 3:</strong> Travel to Kandy and keep the arrival evening light.</li><li><strong>Day 4:</strong> Kandy or a nearby cultural and garden day.</li><li><strong>Day 5:</strong> Continue to Nuwara Eliya or another tea-country base.</li><li><strong>Days 6-7:</strong> Ella or the central hills with time for a walk, train section, or slower scenic day.</li><li><strong>Day 8:</strong> Transfer to Yala or Udawalawe and prepare for the safari schedule.</li><li><strong>Day 9:</strong> Safari or wildlife alternative with recovery time.</li><li><strong>Days 10-11:</strong> Galle, Mirissa, or another coast selected for the season.</li><li><strong>Day 12:</strong> Final coast morning and airport transfer with a safe departure buffer.</li></ol>',
    '[{"place":"Sigiriya or Habarana","nights":2,"note":"A settled Cultural Triangle base rather than a one-night stop."},{"place":"Kandy","nights":2,"note":"Use the second day for culture, gardens, or rest instead of another long transfer."},{"place":"Nuwara Eliya and Ella","nights":2,"note":"Keep the hills scenic and unhurried while confirming train logistics."},{"place":"Yala or Udawalawe","nights":2,"note":"One wildlife base with enough time for the park plan and recovery."},{"place":"Galle, Mirissa, or another coast","nights":3,"note":"Finish with a genuine rest and a departure-friendly final base."}]',
    '<p>Most transfers can be planned below 4.5 hours when bases are chosen carefully. Use about 2.5-3.5 hours from Kandy to Nuwara Eliya, 2-3 hours from Nuwara Eliya to Ella by road, 2.5-4 hours from Ella to the safari belt, and 2-3 hours from Galle to the airport by expressway. The best route is often the one that avoids an unnecessary cross-island detour.</p>',
    '<p><strong>More coast:</strong> trade one hill or wildlife night for beach recovery. <strong>More wildlife:</strong> use the extra time for a second park only when the group accepts early starts. <strong>East coast:</strong> choose Trincomalee or Pasikuda when the season and transfers support it. <strong>Culture:</strong> add Anuradhapura or Polonnaruwa only if the group values ancient-city depth.</p>',
    '<p>This route is moderate rather than slow. Two-night stays reduce packing, but several regional transfers remain. It suits groups that want comfort without giving up the main Sri Lanka experiences.</p>',
    '<p>Choose the coast by travel month, not by a universal best-beach claim. Hill weather, safari conditions, whale watching, and train schedules need current checks. Keep an indoor or rest alternative for weather changes.</p>',
    '<p>Best for long-haul travellers, couples, families with enough time, and anyone who wants the ten-day highlights without changing hotels every night.</p>',
    'tours/classic-sri-lanka-private-tour-package',
    'Ask for a comfortable 12-day private route',
    '[{"question":"What does twelve days add over ten days?","answer":"The main benefit is space for two-night stays, a slower hill section, or an extra coast or wildlife choice. It should not simply add another long detour."},{"question":"Can I include the east coast?","answer":"Possibly, when the travel month and route allow it. Adding the east coast to a full south-and-hill circuit can create long transfers, so one coast may need to be removed."},{"question":"Is twelve days suitable for families?","answer":"It can be a good balance when the route protects rest, child-friendly activities, vehicle space, and shorter daily plans."}]',
    1
),
(
    14,
    '14-Day Sri Lanka Itinerary: The Complete Island Balance',
    '14-Day Sri Lanka Itinerary | Complete Private Route Guide',
    'Plan a 14-day Sri Lanka itinerary with ancient cities, the Cultural Triangle, hills, tea country, wildlife, beaches, seasonal alternatives, and breathing room.',
    '<p>Fourteen days gives a first visit enough space for a broad Sri Lanka circuit without making every morning a checkout. You can include deeper heritage, the central hills, one wildlife area, and a genuine coast finish. The route still needs discipline: a complete island visit is a balanced selection, not a promise to visit every province.</p>',
    '<h2>Day-by-day 14-day route</h2><ol><li><strong>Day 1:</strong> Arrive and recover near the airport or Negombo.</li><li><strong>Days 2-3:</strong> Anuradhapura or another northern heritage choice, then continue toward Sigiriya.</li><li><strong>Days 4-5:</strong> Sigiriya and the Cultural Triangle with one flexible cultural or wildlife option.</li><li><strong>Day 6:</strong> Travel to Kandy and plan a focused city visit.</li><li><strong>Day 7:</strong> Continue through tea country and settle in Nuwara Eliya.</li><li><strong>Days 8-9:</strong> Ella or another hill base with a walk, train section, or slower scenic day.</li><li><strong>Days 10-11:</strong> Yala or Udawalawe with a properly timed safari and recovery period.</li><li><strong>Days 12-13:</strong> Tangalle, Mirissa, Galle, or a seasonal coast with two nights for rest.</li><li><strong>Day 14:</strong> Final coast or heritage morning and airport transfer with a generous buffer.</li></ol>',
    '[{"place":"Negombo or airport area","nights":1,"note":"Protect the first morning when the international arrival is late."},{"place":"Anuradhapura and/or Sigiriya","nights":4,"note":"Give ancient sites and the Cultural Triangle more than a rushed pass."},{"place":"Kandy and tea country","nights":2,"note":"Build a sensible hill transition and consider luggage before choosing the train."},{"place":"Ella or another hill base","nights":2,"note":"Keep time for scenery, walks, and weather flexibility."},{"place":"Yala or Udawalawe","nights":2,"note":"Choose one wildlife zone and plan around the park gate and safari time."},{"place":"South, west, or east coast","nights":3,"note":"Select the coast that matches the season and departure route."}]',
    '<p>Longer drives should be buffered with two-night stays. The airport to Anuradhapura or the Cultural Triangle can take much of a day depending on the start point. Kandy to Nuwara Eliya is often around 2.5-3.5 hours, Nuwara Eliya to Ella around 2-3 hours by road, Ella to a safari base around 2.5-4 hours, and Galle to the airport around 2-3 hours by expressway.</p>',
    '<p><strong>Ancient-city depth:</strong> keep Anuradhapura and remove a second coast. <strong>More beach:</strong> skip the far-north extension and use two or three coast nights. <strong>East coast:</strong> choose it when seasonal conditions justify the longer connection. <strong>More rest:</strong> turn one single-night stop into a two-night base.</p>',
    '<p>Fourteen days can be comfortable when hotel changes are controlled. Use the extra time for recovery, not only for more attractions. A premium vehicle or larger van can improve comfort on longer winding roads.</p>',
    '<p>South and west coast choices often suit November to April, while east-coast choices can be considered in other months. Rain, sea conditions, wildlife access, and mountain weather can change the best sequence.</p>',
    '<p>Best for first-time visitors who want broad coverage, long-haul travellers, families with time for rest, and repeat visitors who want to add heritage or wildlife depth.</p>',
    'tours/sri-lanka-14-day-grand-tour-package',
    'Review the 14-day private tour package',
    '[{"question":"Is fourteen days enough for a complete Sri Lanka route?","answer":"It is enough for a balanced first visit with culture, hills, wildlife, and coast. It is still better to choose a few regions well than to add every distant stop."},{"question":"Should I include Anuradhapura?","answer":"Include it when ancient-city depth matters and the group accepts the longer northern movement. Otherwise use those nights for the Cultural Triangle or coast."},{"question":"Which coast is best for a fourteen-day itinerary?","answer":"Choose the coast around the travel month, sea conditions, and departure route. There is no single year-round best coast for every trip."}]',
    1
)
ON DUPLICATE KEY UPDATE
    title = VALUES(title),
    meta_title = VALUES(meta_title),
    meta_description = VALUES(meta_description),
    intro_html = VALUES(intro_html),
    route_html = VALUES(route_html),
    nights_json = VALUES(nights_json),
    transfers_html = VALUES(transfers_html),
    alternatives_html = VALUES(alternatives_html),
    pace_html = VALUES(pace_html),
    seasonal_html = VALUES(seasonal_html),
    best_for_html = VALUES(best_for_html),
    package_path = VALUES(package_path),
    package_label = VALUES(package_label),
    faq_json = VALUES(faq_json),
    is_published = VALUES(is_published);

INSERT INTO itinerary_package_links
    (informational_path, package_slug, package_path, informational_label, package_label, is_active)
VALUES
    ('/5-day-itinerary', 'classic-sri-lanka-private-tour-package', '/tours/classic-sri-lanka-private-tour-package', 'Read the 5-day planning guide', 'Ask for a custom 5-day private route', 1),
    ('/7-day-itinerary', 'classic-sri-lanka-private-tour-package', '/tours/classic-sri-lanka-private-tour-package', 'Read the 7-day planning guide', 'Ask for a private 7-day version', 1),
    ('/9-day-itinerary', 'sri-lanka-wildlife-safari-package', '/tours/sri-lanka-wildlife-safari-package', 'Read the 9-day wildlife guide', 'Compare the wildlife private package', 1),
    ('/10-day-itinerary', 'sri-lanka-10-day-itinerary-package', '/tours/sri-lanka-10-day-itinerary-package', 'Read the 10-day planning guide', 'Review the 10-day private tour package', 1),
    ('/12-day-itinerary', 'classic-sri-lanka-private-tour-package', '/tours/classic-sri-lanka-private-tour-package', 'Read the 12-day planning guide', 'Ask for a custom 12-day private route', 1),
    ('/14-day-itinerary', 'sri-lanka-14-day-grand-tour-package', '/tours/sri-lanka-14-day-grand-tour-package', 'Read the 14-day planning guide', 'Review the 14-day private tour package', 1)
ON DUPLICATE KEY UPDATE
    informational_path = VALUES(informational_path),
    package_path = VALUES(package_path),
    informational_label = VALUES(informational_label),
    package_label = VALUES(package_label),
    is_active = VALUES(is_active);

INSERT INTO theme_page_editorial
    (theme_slug, page_title, meta_title, meta_description, hero_image, intro_html,
     route_html, planning_html, seasonal_html, reassurance_html, faq_json,
     cta_title, cta_text, is_published)
VALUES
(
    'honeymoon-tours',
    'Sri Lanka Honeymoon Tours',
    'Sri Lanka Honeymoon Tours | Private Romantic Itineraries',
    'Plan a private Sri Lanka honeymoon with romantic stays, scenic hills, wildlife, beaches, privacy, and a flexible chauffeur-guided route.',
    'assets/images/home-hero/home-4.jpg',
    '<p>A Sri Lanka honeymoon works best when the route leaves room for the two of you. Choose a few memorable bases, protect private time, and use the driver for practical movement rather than filling every hour with sightseeing.</p><p>Romance can mean a boutique stay, a quiet beach, a scenic train section, a private safari, a sunset walk, or simply two nights without packing again. The right plan depends on your budget, travel month, privacy needs, and the experiences you genuinely enjoy.</p>',
    '<h2>Choose a honeymoon shape</h2><ul><li><strong>7 days:</strong> culture, hills, and one coast or wildlife finish with careful pacing.</li><li><strong>10 days:</strong> a balanced first trip with Sigiriya, tea country, a safari option, and a beach ending.</li><li><strong>12-14 days:</strong> more two-night stays, slower beaches, private experiences, and room for upgrades.</li></ul>',
    '<p>Ask for room types, privacy expectations, honeymoon amenities, transfer comfort, dietary needs, and any celebration plans before confirming a hotel. A clean private vehicle, realistic check-in timing, and a driver who understands when to give the couple space matter as much as the attraction list.</p>',
    '<p>South and west coast honeymoons are often easier to plan from November to April. East-coast alternatives may suit other months. Whale watching, train seats, safari access, and hotel availability are date-sensitive and should be confirmed rather than promised.</p>',
    '<p>Private travel does not automatically mean every activity is private or every hotel is included. Confirm the accommodation basis, vehicle standard, meals, guides, safari jeep, train tickets, cancellation terms, and upgrade costs in the quote.</p>',
    '[{"question":"How many days are best for a Sri Lanka honeymoon?","answer":"Seven days can work for a focused route. Ten to fourteen days usually allow more privacy, two-night stays, and a calmer beach or hill-country finish."},{"question":"Can we include a scenic train?","answer":"Yes, when seats and transfers are confirmed. A private road plan should remain available if the timetable or luggage arrangements do not fit."},{"question":"Can the honeymoon route be private?","answer":"The vehicle and route can be planned privately. Confirm which guides, safaris, excursions, meals, and hotel services are private or shared."}]',
    'Plan a private Sri Lanka honeymoon',
    'Share your travel month, preferred hotel style, privacy priorities, and the moments you want the trip to be remembered for.',
    1
),
(
    'family-tours',
    'Sri Lanka Family Tours',
    'Sri Lanka Family Tours | Private Family-Friendly Routes',
    'Plan a private Sri Lanka family tour with child-seat options, manageable drives, bathroom breaks, wildlife, beaches, flexible pacing, and practical support.',
    'assets/images/home-hero/home-2.jpg',
    '<p>Family travel in Sri Lanka is most comfortable when the route respects children as travellers, not as small adults. Build in shorter movement blocks, bathroom and snack stops, pool or rest time, and one main activity before the heat of the day.</p><p>A private vehicle makes it easier to adjust the day, but it cannot make a five-hour road movement feel like a short transfer. The best family plan chooses fewer bases and keeps the vehicle, luggage, rooms, food, and activities aligned.</p>',
    '<h2>Family route ideas</h2><ul><li><strong>5-7 days:</strong> choose the Cultural Triangle and hills, or a wildlife and coast route, rather than all regions.</li><li><strong>10 days:</strong> combine Sigiriya, Kandy or the hills, one wildlife area, and a beach with rest time.</li><li><strong>12-14 days:</strong> use two-night stays and add a slower coast or extra recovery day.</li></ul>',
    '<p>Tell the planner the children ages, car-seat or booster needs, luggage volume, food preferences, nap patterns, medical or accessibility needs, and whether connecting rooms or family suites are required. Confirm vehicle capacity for people, luggage, and child equipment before booking.</p><p>Good family stops include places with shade, toilets, flexible visit length, and a simple return to the hotel. Wildlife and beach activities still depend on weather, park rules, sea conditions, and the age of the children.</p>',
    '<p>Choose the coast around the travel month and sea conditions. Hill-country weather can be cool and wet. Safari timing means early starts, so a rest day or a lodge near the relevant gate may be worth more than another attraction.</p>',
    '<p>Ask for current safety and supplier information. Private travel is not a guarantee of medical access, road comfort, child-seat availability, or food suitability. Those needs should be confirmed in the booking conversation.</p>',
    '[{"question":"Are Sri Lanka tours suitable for children?","answer":"They can be, especially with shorter drives, flexible mornings, suitable accommodation, and activities matched to the childrens ages and energy."},{"question":"Can you arrange child seats?","answer":"Request child seats or boosters early and confirm availability, fit, and vehicle space before the booking is accepted."},{"question":"Which wildlife park works best for families?","answer":"The choice depends on the childrens ages, safari length, current park conditions, and the rest of the route. Udawalawe is often considered for an accessible elephant-focused day, but no sighting is guaranteed."}]',
    'Plan a practical family route',
    'Send the childrens ages, travel dates, room needs, food requirements, and the experiences they will enjoy most.',
    1
),
(
    'wildlife-tours',
    'Sri Lanka Wildlife Tours',
    'Sri Lanka Wildlife Tours | Yala, Udawalawe and Ethical Safari Planning',
    'Plan a private Sri Lanka wildlife tour around Yala, Udawalawe, Minneriya, Kaudulla, Wilpattu, wetlands, seasonality, route timing, and responsible standards.',
    'assets/images/home-hero/home-6.jpg',
    '<p>Sri Lanka wildlife travel is stronger when the park, habitat, season, vehicle, lodge, and route are chosen together. Yala, Udawalawe, Minneriya, Kaudulla, Wilpattu, Bundala, and whale-watching areas offer different experiences rather than one universal best park.</p><p>Wildlife is unpredictable. A responsible plan sets expectations clearly, leaves room for weather and access changes, avoids promising sightings, and chooses operators who respect animals, park rules, and other visitors.</p>',
    '<h2>Match the park to the priority</h2><ul><li><strong>Yala:</strong> a varied park option with leopard interest, subject to access, crowding, weather, and animal behaviour.</li><li><strong>Udawalawe:</strong> often considered for an elephant-focused route and a practical southbound connection.</li><li><strong>Minneriya or Kaudulla:</strong> dry-zone choices that depend on water levels, season, and where herds are currently gathering.</li><li><strong>Wilpattu:</strong> a different northern route that needs extra time and a clear reason to make the longer connection.</li><li><strong>Bundala and wetlands:</strong> useful for birding and habitat variety when the season and route fit.</li></ul>',
    '<p>Plan the safari around the park gate, lodge position, start time, jeep arrangement, park fees, and the next route day. A private road vehicle does not replace the park jeep or guarantee a private safari. Confirm whether the jeep, tracker, entrance fee, meals, guide, and transfers are included.</p><p>Choose fewer high-quality safari starts when the group needs rest. Ethical viewing means keeping distance, following park instructions, avoiding harassment or blocking, and accepting that the animals decide what is seen.</p>',
    '<p>Wildlife patterns and coast conditions change by season. Yala, Udawalawe, Minneriya, Kaudulla, Wilpattu, Bundala, and whale-watching plans should be checked close to travel. Keep a route alternative for rain, sea conditions, park closures, or low sightings.</p>',
    '<p>Responsible wildlife travel avoids guaranteed-sighting language, crowding claims, and feeding or touching wild animals. Ask for current operator, park, access, and cancellation details before paying deposits.</p>',
    '[{"question":"Which wildlife park should I choose?","answer":"Choose around the species and habitat interest, travel month, lodge position, route length, and tolerance for early starts. Current conditions matter more than a permanent ranking."},{"question":"Will I see a leopard or elephants?","answer":"Sightings are never guaranteed. A skilled operator and sensible timing can improve the experience, but wildlife remains unpredictable."},{"question":"Can wildlife fit into a family or honeymoon route?","answer":"Yes, when safari timing, vehicle comfort, lodge location, age needs, privacy, and recovery time are planned together."},{"question":"What does responsible safari behaviour mean?","answer":"Follow park rules, keep distance, avoid chasing or blocking animals, do not feed wildlife, and choose operators who put animal welfare and visitor safety first."}]',
    'Plan a responsible Sri Lanka wildlife route',
    'Tell us the wildlife priorities, travel month, group needs, and route length. We will compare parks and practical transfers without promising a sighting.',
    1
)
ON DUPLICATE KEY UPDATE
    page_title = VALUES(page_title),
    meta_title = VALUES(meta_title),
    meta_description = VALUES(meta_description),
    hero_image = VALUES(hero_image),
    intro_html = VALUES(intro_html),
    route_html = VALUES(route_html),
    planning_html = VALUES(planning_html),
    seasonal_html = VALUES(seasonal_html),
    reassurance_html = VALUES(reassurance_html),
    faq_json = VALUES(faq_json),
    cta_title = VALUES(cta_title),
    cta_text = VALUES(cta_text),
    is_published = VALUES(is_published);

INSERT INTO theme_tour_links (theme_slug, tour_slug, sort_order, is_active)
VALUES
    ('honeymoon-tours', 'sri-lanka-honeymoon-package', 1, 1),
    ('family-tours', 'sri-lanka-family-tour-package', 1, 1),
    ('wildlife-tours', 'sri-lanka-wildlife-safari-tour', 1, 1),
    ('wildlife-tours', 'sri-lanka-wildlife-safari-package', 2, 1),
    ('luxury-tours', 'luxury-sri-lanka-slow-travel-package', 1, 1)
ON DUPLICATE KEY UPDATE
    sort_order = VALUES(sort_order),
    is_active = VALUES(is_active);

UPDATE tours
SET
    excerpt = 'A clear 10-day private Sri Lanka package covering culture, hill country, one wildlife choice, and a seasonal coast finish with quoted inclusions and a private vehicle.',
    intro = '<p>This transactional package is the quote-ready version of the 10-day planning guide. It is designed for travellers who want a private vehicle and driver-guide, a practical route, and a clear conversation about hotels, activities, and upgrades.</p><p>The route normally connects the Cultural Triangle, Kandy or the hills, Ella or tea country, one wildlife area, and a south or west coast finish. The exact order changes around the travel month, arrival time, hotel availability, and the group pace.</p>',
    highlights = '<ul><li>Private airport pickup and route planning around the arrival flight</li><li>Sigiriya or Dambulla cultural time with a realistic daily pace</li><li>Kandy and hill-country travel with road or train-support options</li><li>Yala or Udawalawe comparison rather than a fixed wildlife promise</li><li>Galle, Mirissa, or another seasonal coast finish</li><li>One planning conversation covering vehicle, hotels, activities, and changes</li></ul>',
    includes = '<ul><li>Private air-conditioned vehicle and driver-guide for the agreed route</li><li>Airport pickup and departure transfer when included in the confirmed itinerary</li><li>Route planning, practical stop suggestions, and day-by-day coordination</li><li>Accommodation and activities only where listed in the written quote</li></ul>',
    excludes = '<ul><li>International flights, visas, insurance, and personal spending</li><li>Park entrance fees, safari jeeps, train tickets, guides, and attraction fees unless listed</li><li>Meals and hotel upgrades unless included in the confirmed quote</li><li>Changes, extra nights, or supplier cancellation charges outside the agreed terms</li></ul>',
    sample_itinerary = '<ol><li>Arrival and transfer to Sigiriya or Habarana</li><li>Cultural Triangle day</li><li>Transfer to Kandy</li><li>Kandy and transition to tea country</li><li>Nuwara Eliya or hill-country day</li><li>Ella or scenic train-support option</li><li>Transfer to Yala or Udawalawe</li><li>Wildlife or recovery day</li><li>Transfer to the seasonal coast</li><li>Coast morning and airport transfer</li></ol>',
    pricing_notes = 'The published price range is an indicative land-services planning basis, not a final quote. Confirm the number of travellers, vehicle class, accommodation tier, room arrangement, meal basis, safari or train costs, taxes, season, and supplier cancellation terms before payment. A clear quote should separate included services from optional upgrades.',
    vehicle_options = 'Choose a sedan for one or two travellers when luggage is light, or a larger vehicle for families, groups, and longer routes. Confirm luggage volume, child-seat needs, air-conditioning, comfort expectations, and vehicle availability in the written quote.',
    cta_title = 'Request a Clear 10-Day Private Tour Quote',
    cta_text = 'Send your dates, group size, arrival details, hotel style, wildlife priority, and vehicle needs. We will compare the route with the detailed 10-day guide and return a practical quote.',
    meta_title = '10-Day Sri Lanka Private Tour Package | Clear Route and Quote',
    meta_description = 'Request a clear 10-day private Sri Lanka tour package covering culture, hills, wildlife, coast, vehicle, inclusions, exclusions, and flexible planning.',
    canonical_url = NULL,
    faq_json = '[{"question":"What is included in the 10-day package?","answer":"The final quote should list the private vehicle and driver-guide, airport transfers, accommodation basis, activities, fees, meals, taxes, and exclusions separately. Do not rely on an unitemised range."},{"question":"Can I choose Yala or Udawalawe?","answer":"Yes, when the route and travel month support the choice. Compare park interest, lodge position, travel time, and current conditions before confirming."},{"question":"Can I change the route after requesting a quote?","answer":"Usually the route can be adjusted before confirmation subject to availability, distance, supplier terms, and the resulting price. Ask for the revised details in writing."},{"question":"Where is the detailed planning guide?","answer":"Use the 10-day itinerary guide to understand the route logic, alternatives, transfer windows, and planning tradeoffs before choosing package inclusions."}]'
WHERE slug = 'sri-lanka-10-day-itinerary-package';

UPDATE tours
SET
    includes = '<ul><li>Private vehicle and driver-guide for the agreed itinerary</li><li>Airport transfers and route coordination when listed in the quote</li><li>Accommodation and activities only where written in the confirmed package</li></ul>',
    excludes = '<ul><li>Flights, visas, insurance, personal spending, and unlisted meals</li><li>Park fees, safari jeeps, train tickets, and optional guides unless listed</li><li>Supplier cancellation charges and route changes outside the agreed terms</li></ul>'
WHERE slug IN ('classic-sri-lanka-private-tour-package', 'sri-lanka-14-day-grand-tour-package', 'sri-lanka-honeymoon-package', 'sri-lanka-family-tour-package', 'sri-lanka-wildlife-safari-tour', 'sri-lanka-wildlife-safari-package', 'luxury-sri-lanka-slow-travel-package');

UPDATE tours
SET
    excerpt = 'A wildlife-focused private Sri Lanka route comparing Yala, Udawalawe, dry-zone parks, wetlands, ethical safari standards, and realistic expectations.',
    intro = '<p>This wildlife package is built around habitat and route logic rather than a guaranteed sighting. Compare Yala, Udawalawe, Minneriya, Kaudulla, Wilpattu, and Bundala around the travel month, the species interest, the lodge position, and the time available.</p><p>Safari access, park conditions, animal behaviour, crowding, and weather change. We will confirm the current practical options and keep an alternative route available when conditions move.</p>',
    highlights = '<ul><li>Yala option for varied habitats and leopard interest</li><li>Udawalawe option for an elephant-focused southbound route</li><li>Minneriya or Kaudulla option when current dry-zone conditions support it</li><li>Bundala or other wetland option for birding and habitat variety</li><li>Private road transfers coordinated around safari start times</li><li>Responsible viewing expectations with no guaranteed sightings</li></ul>',
    sample_itinerary = '<ol><li>Airport to Habarana or Sigiriya</li><li>Dry-zone cultural or wildlife option</li><li>Transfer to Udawalawe</li><li>Udawalawe safari and recovery</li><li>Transfer toward Yala or another wildlife base</li><li>Yala or alternative park plan</li><li>Wetland, coast, or second wildlife option</li><li>South-coast recovery</li><li>Airport transfer or extension</li></ol>',
    pricing_notes = 'Confirm park fees, safari jeep type, guide or tracker arrangements, accommodation, meals, vehicle, taxes, and cancellation terms separately. Wildlife costs vary by park, group size, season, and whether the jeep is private or shared.',
    vehicle_options = 'Private road vehicle for airport and intercity movements. Safari jeeps are separate park or operator arrangements unless the written quote states otherwise. Confirm vehicle space, luggage storage, child-seat needs, and early-start comfort.',
    cta_title = 'Plan a Responsible Wildlife Route',
    cta_text = 'Tell us the species, habitats, travel month, group needs, and preferred pace. We will compare parks without promising a sighting.',
    meta_title = 'Sri Lanka Wildlife Safari Package | Responsible Private Route',
    meta_description = 'Compare a private Sri Lanka wildlife safari package across Yala, Udawalawe, Minneriya, Kaudulla, Wilpattu, and Bundala with responsible planning.',
    faq_json = '[{"question":"Will this package guarantee a leopard or elephant sighting?","answer":"No. Wildlife is unpredictable. Good timing and responsible operators can improve the experience, but no sighting should be guaranteed."},{"question":"Are safari jeeps included?","answer":"They should be listed separately in the written quote with park fees, guide or tracker arrangements, and whether the jeep is private or shared."},{"question":"Can the route include a beach or family-friendly recovery day?","answer":"Yes, when the park timing and available days allow it. Recovery time can make a wildlife route more enjoyable and practical."}]'
WHERE slug IN ('sri-lanka-wildlife-safari-tour', 'sri-lanka-wildlife-safari-package');

UPDATE tours
SET
    excerpt = 'A private Sri Lanka honeymoon route with slower stays, romantic accommodation choices, scenic hills, wildlife, beaches, and privacy planning.',
    intro = '<p>This honeymoon package is designed around time together rather than a checklist of attractions. Choose a few memorable bases, use private transfers for practical movement, and keep enough unplanned space for the couple.</p><p>Confirm room category, privacy, meals, celebration services, vehicle standard, guides, safari arrangements, train seats, and cancellation terms before payment. Romantic extras vary by property and season.</p>',
    highlights = '<ul><li>Two-night stays where the route and availability allow</li><li>Tea-country scenery, a scenic train option, or a private road alternative</li><li>Private safari and beach or heritage finish options</li><li>Vehicle comfort and privacy preferences discussed before quoting</li><li>Flexible 7, 10, 12, or 14-day route versions</li></ul>',
    pricing_notes = 'Honeymoon pricing depends heavily on room category, hotel tier, private experiences, safari arrangements, meals, and season. The written quote should separate land transport, accommodation, activities, upgrades, and cancellation terms.',
    vehicle_options = 'A comfortable private sedan may suit two travellers with light luggage. A larger or newer vehicle can be selected for longer routes, additional luggage, privacy, or comfort preferences.',
    cta_title = 'Plan a Private Sri Lanka Honeymoon',
    cta_text = 'Share your travel month, hotel style, privacy priorities, and the moments you want the trip to be remembered for.',
    meta_title = 'Sri Lanka Honeymoon Package | Private Romantic Tour',
    meta_description = 'Plan a private Sri Lanka honeymoon with romantic stays, scenic hills, wildlife, beaches, privacy, and a flexible chauffeur-guided route.'
WHERE slug = 'sri-lanka-honeymoon-package';

UPDATE tours
SET
    excerpt = 'A practical private Sri Lanka family route with child-seat planning, shorter movement blocks, rest time, wildlife, beaches, and flexible pacing.',
    intro = '<p>Family travel works best when the route is planned around children ages, energy, food, sleep, vehicle space, and realistic transfer times. The private vehicle helps adjust the day, but it cannot remove long roads or change park and attraction rules.</p><p>Confirm child seats or boosters, room configuration, bathroom and snack stops, food needs, medical or accessibility requirements, and the age suitability of activities before booking.</p>',
    highlights = '<ul><li>Shorter movement blocks and fewer unnecessary hotel changes</li><li>Child-seat or booster requests checked before confirmation</li><li>Pool, rest, shade, and flexible activity windows</li><li>Wildlife and beach options matched to childrens ages</li><li>Vehicle and luggage capacity reviewed as part of the quote</li></ul>',
    pricing_notes = 'Family pricing depends on room configuration, vehicle size, child seats, meal basis, activity fees, safari arrangements, and travel season. Ask for a clear per-group quote with child policies and supplier terms.',
    vehicle_options = 'A minivan or larger vehicle may be more comfortable for families, luggage, child equipment, and car-seat installation. Confirm the exact vehicle and seating arrangement before payment.',
    cta_title = 'Plan a Practical Family Tour',
    cta_text = 'Send the childrens ages, room needs, food or medical requirements, and the experiences they will enjoy most.',
    meta_title = 'Sri Lanka Family Tour Package | Private Family-Friendly Route',
    meta_description = 'Plan a private Sri Lanka family tour with child-seat planning, manageable drives, wildlife, beaches, flexible pacing, and practical support.'
WHERE slug = 'sri-lanka-family-tour-package';
