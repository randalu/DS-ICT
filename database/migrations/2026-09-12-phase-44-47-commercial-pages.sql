-- PHASE 44-47: commercial planning pages
--
-- These pages use the existing static-page CMS and public route. They explain
-- planning variables without inventing fixed prices, guarantees, or reviews.
-- The nested cost route is mapped by static_page_public_path() in the app.

SET NAMES utf8mb4;

SET @ict_column_exists = (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'static_pages'
      AND COLUMN_NAME = 'faq_json'
);
SET @ict_sql = IF(
    @ict_column_exists = 0,
    'ALTER TABLE static_pages ADD COLUMN faq_json LONGTEXT NULL AFTER content',
    'SELECT 1'
);
PREPARE ict_stmt FROM @ict_sql;
EXECUTE ict_stmt;
DEALLOCATE PREPARE ict_stmt;

SET @ict_column_exists = (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'static_pages'
      AND COLUMN_NAME = 'cta_title'
);
SET @ict_sql = IF(
    @ict_column_exists = 0,
    'ALTER TABLE static_pages ADD COLUMN cta_title VARCHAR(255) NULL AFTER faq_json',
    'SELECT 1'
);
PREPARE ict_stmt FROM @ict_sql;
EXECUTE ict_stmt;
DEALLOCATE PREPARE ict_stmt;

SET @ict_column_exists = (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'static_pages'
      AND COLUMN_NAME = 'cta_text'
);
SET @ict_sql = IF(
    @ict_column_exists = 0,
    'ALTER TABLE static_pages ADD COLUMN cta_text TEXT NULL AFTER cta_title',
    'SELECT 1'
);
PREPARE ict_stmt FROM @ict_sql;
EXECUTE ict_stmt;
DEALLOCATE PREPARE ict_stmt;

INSERT INTO static_pages
    (title, slug, content, faq_json, cta_title, cta_text, hero_image,
     meta_title, meta_description, canonical_url, sort_order, is_published)
VALUES
(
    'Private Driver in Sri Lanka',
    'private-driver-sri-lanka',
    '<p>A private driver is a practical way to connect Sri Lanka''s airports, cultural sites, hill country, wildlife areas, and coast without turning every travel day into a transfer problem. You travel in your own vehicle and can shape the pace around your arrival time, interests, and hotel plan.</p><h2>What to plan</h2><p>Tell us your dates, group size, preferred vehicle comfort, and the places you are considering. We can then suggest a route with realistic driving days, rest stops, and time for experiences rather than only listing every possible attraction.</p><h2>Driver, vehicle, and route support</h2><p>Private travel works best when the route, vehicle, and daily rhythm are considered together. Your driver can help with practical route timing and local stops, while the itinerary remains yours to adjust as the trip develops.</p><p>Use the planning links below to review the private itinerary collection, Sri Lanka destinations, and contact options.</p>',
    '[{"question":"What information should I send for a private driver quote?","answer":"Share your travel dates, number of travelers, luggage needs, arrival and departure details, preferred vehicle comfort, and the places or experiences you want to include."},{"question":"Can a private driver route be changed during the trip?","answer":"Route changes depend on timing, distance, accommodation, and availability. Share the change with the team early so the practical options can be checked."},{"question":"Is a private driver suitable for a family or small group?","answer":"It can be a useful fit for families and small groups because the route and daily pace can be planned around the group rather than a fixed shared departure."}]',
    'Plan a private driver route',
    'Send your dates and route ideas. We will help you compare a practical private-driver setup.',
    'assets/images/home-hero/home-5.jpg',
    'Private Driver in Sri Lanka | Island Culture Travels',
    'Plan a private driver and vehicle route in Sri Lanka around your dates, pace, luggage, destinations, and travel interests.',
    NULL, 440, 1
),
(
    'Sri Lanka Private Driver Cost',
    'private-driver-sri-lanka-cost',
    '<p>The cost of a private driver in Sri Lanka depends on the route you choose, the number of travel days, vehicle size and comfort, season, hotel pattern, and any extra services requested. A useful quote should show what is included so that the lowest headline number is not the only comparison.</p><h2>What changes the quote</h2><ul><li>Travel dates and total number of days.</li><li>Vehicle type and space for passengers and luggage.</li><li>Airport transfers, long-distance driving, and route changes.</li><li>Whether hotels, activities, entrance fees, meals, or specialist guides are included.</li><li>Pickup and drop-off locations and the pace of the itinerary.</li></ul><h2>How to compare options</h2><p>Ask for the route, inclusions, exclusions, cancellation terms, and payment schedule alongside the total. Once we know your actual plan, we can prepare a current quote rather than presenting a price that may not fit your trip.</p><p>Use the planning links below to review the private driver guide and request a current quote.</p>',
    '[{"question":"Why is there no single private driver price for every Sri Lanka trip?","answer":"Trips differ in length, route, vehicle, season, airport transfers, hotel plan, and inclusions. A current quote needs those details to be meaningful."},{"question":"Should I compare a driver quote with a car-only rental?","answer":"Compare the full arrangement: vehicle, driver support, fuel and route logistics, insurance or commercial terms, transfers, and what happens if the route changes."},{"question":"Can I request a quote before choosing every hotel?","answer":"Yes. Send the dates, group details, approximate route, and hotel comfort level first. The itinerary and quote can then be refined as your choices become clearer."}]',
    'Request a current cost estimate',
    'Share the route you are considering and we will outline the main cost variables before preparing a quote.',
    'assets/images/home-hero/home-3.jpg',
    'Sri Lanka Private Driver Cost | Island Culture Travels',
    'Understand what affects private driver and vehicle costs in Sri Lanka before requesting a current route-based quote.',
    NULL, 441, 1
),
(
    'Sri Lanka Private Tours',
    'sri-lanka-private-tours',
    '<p>Private Sri Lanka tours give you room to combine culture, wildlife, tea country, coast, food, and slower days around one route. The right plan is less about collecting every highlight and more about choosing a sequence that suits your dates, interests, and comfort.</p><h2>Build the route around your priorities</h2><p>Start with the experience you care about most: ancient cities, wildlife, scenic rail and hill-country travel, beaches, wellness, food, or a relaxed family pace. We can then connect the main stops with sensible travel days and time to settle in.</p><h2>Flexible itinerary ideas</h2><p>Use the planning links below to browse itinerary ideas, experiences, destinations, and route options. Then tell us which parts should be longer, shorter, quieter, or more active.</p><p>Private tours can be planned for couples, families, and small groups. The final route should reflect your actual dates and the time available on the ground.</p>',
    '[{"question":"How far in advance should I plan a private Sri Lanka tour?","answer":"Earlier planning gives you more choice of dates, vehicles, and accommodation, especially around busy travel periods. We can still review shorter lead times when availability allows."},{"question":"Can I combine beaches, wildlife, and hill country?","answer":"Yes, but the best sequence depends on your travel month, arrival point, trip length, and tolerance for longer drives. We will help balance the route instead of adding every stop automatically."},{"question":"Can you tailor an existing itinerary?","answer":"Yes. Use a published itinerary as a starting point and tell us which destinations, experiences, hotel style, and travel pace you want changed."}]',
    'Shape your private Sri Lanka tour',
    'Tell us what you want the journey to feel like, and we will help turn the ideas into a workable route.',
    'assets/images/home-hero/home-2.jpg',
    'Sri Lanka Private Tours | Island Culture Travels',
    'Explore private Sri Lanka tours built around your dates, interests, pace, destinations, and driver-guided travel preferences.',
    NULL, 442, 1
),
(
    'Sri Lanka Tour Cost',
    'sri-lanka-tour-cost',
    '<p>There is no useful single price for a Sri Lanka private tour before the route and inclusions are known. The total can include a vehicle and driver, accommodation, airport transfers, activities, entrance fees, meals, specialist guides, and the time of year.</p><h2>Ask for an itemised plan</h2><p>A clear request should separate transport from accommodation and activities. It should also state the number of travelers, room setup, vehicle comfort, arrival and departure details, and whether you want a relaxed or full itinerary.</p><h2>Keep the route realistic</h2><p>A lower total is not helpful if it relies on long daily transfers or leaves out costs you expected to be included. We will help compare route options and explain where a change in pace, hotel level, or activity mix affects the estimate.</p><p>Use the planning links below to browse private tour ideas, sample itineraries, and the contact form for a current trip estimate.</p>',
    '[{"question":"What should be included in a Sri Lanka tour cost comparison?","answer":"Compare the route, vehicle and driver arrangement, accommodation, airport transfers, activities, entrance fees, meals, taxes or service charges, exclusions, and payment terms."},{"question":"Does a longer tour always cost more per day?","answer":"Not necessarily. The daily average depends on the route, hotel pattern, vehicle, activities, and how transfers are arranged. Ask for the total and the inclusions together."},{"question":"Can you provide a cost range before I decide on the itinerary?","answer":"We can discuss planning options, but a useful current range still needs approximate dates, group size, route, vehicle comfort, and accommodation expectations."}]',
    'Get a route-based tour estimate',
    'Send the details you already know. We will identify the open cost decisions and prepare the next practical option.',
    'assets/images/home-hero/home-4.jpg',
    'Sri Lanka Tour Cost | Island Culture Travels',
    'Learn what shapes the cost of a private Sri Lanka tour and request a current route-based estimate with clear inclusions.',
    NULL, 443, 1
)
ON DUPLICATE KEY UPDATE
    title = VALUES(title),
    content = VALUES(content),
    faq_json = VALUES(faq_json),
    cta_title = VALUES(cta_title),
    cta_text = VALUES(cta_text),
    hero_image = VALUES(hero_image),
    meta_title = VALUES(meta_title),
    meta_description = VALUES(meta_description),
    canonical_url = VALUES(canonical_url),
    sort_order = VALUES(sort_order),
    is_published = VALUES(is_published);
