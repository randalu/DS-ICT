-- Follow-up quality fixes found during live deployment validation.
-- Idempotent: only expands short records and fills blank image alt/caption text.

SET NAMES utf8mb4;

UPDATE destinations
SET why_visit = CONCAT(why_visit, ' Yala also helps travelers decide between leopard-focused safari, family wildlife timing, and south-coast routing. It is useful when a private itinerary needs one strong wildlife anchor without adding unnecessary distance.'),
    things_to_do = CONCAT(things_to_do, ' Add a practical safari plan around Block 1 or suitable nearby blocks, early jeep pickup, lodge timing, birdlife stops, and a backup nature stop if weather or park conditions change.'),
    travel_tips = CONCAT(travel_tips, ' Keep the night before safari close to the park, avoid late arrivals before a dawn drive, and discuss realistic leopard expectations before booking. Private transfers from Ella, Tangalle, Galle, or Udawalawe should include rest time so the safari does not feel rushed.')
WHERE slug = 'yala'
  AND CHAR_LENGTH(CONCAT_WS(' ', excerpt, intro, why_visit, things_to_do, how_to_get_there, suggested_itinerary, travel_tips)) < 900;

UPDATE destinations
SET why_visit = CONCAT(why_visit, ' Udawalawe is especially strong for travelers who want an easier safari day, more reliable elephant viewing, and less pressure than a leopard-focused park. It fits family routes, first-time safari plans, and efficient transfers between the hills and the coast.'),
    things_to_do = CONCAT(things_to_do, ' Build the day around a morning or afternoon jeep safari, the Elephant Transit Home when timing works, reservoir scenery, birdlife, and a relaxed onward transfer toward Tangalle, Galle, Ella, or Colombo.'),
    travel_tips = CONCAT(travel_tips, ' Choose Udawalawe when the group values comfort and consistent wildlife over chasing rare sightings. Keep luggage, snacks, sun protection, and children''s rest needs in the private vehicle, and avoid pairing safari with an overloaded driving day.')
WHERE slug = 'udawalawe'
  AND CHAR_LENGTH(CONCAT_WS(' ', excerpt, intro, why_visit, things_to_do, how_to_get_there, suggested_itinerary, travel_tips)) < 900;

UPDATE blog_posts
SET excerpt = 'How a private driver makes the Colombo, Kandy, Nuwara Eliya, and Ella route smoother, safer, and easier to pace.',
    content = '<p>Ella is one of Sri Lanka''s most requested hill-country stops, but the journey there is where many itineraries either become smooth or become tiring. A private driver helps because the route is not only about reaching Ella. It is about timing viewpoints, tea country, waterfalls, train sections, luggage, and hotel check-ins in a way that still feels like a holiday.</p><h2>Why the route needs planning</h2><p>Travelers often search for the Kandy to Ella train first, then later realize that tickets, station transfers, luggage, delays, and hotel locations all need coordination. A private driver gives you a flexible base. You can take the scenic train for the section that matters, while the vehicle handles luggage and continues to the next hotel or viewpoint.</p><h2>Best stops on the way</h2><p>Depending on your route, the drive can include Peradeniya, tea estates, Ramboda, Nuwara Eliya, Gregory Lake, Nanu Oya, Haputale viewpoints, Ravana Falls, and Nine Arch Bridge timing. Not every stop belongs in one day. The best private plan chooses the right two or three stops and protects time for weather, traffic, and slow mountain roads.</p><h2>Who benefits most</h2><p>Families, couples, photographers, older travelers, and anyone with limited days benefit from private-driver support. It reduces friction around station timing and gives you more control if rain, road work, or a late train changes the day. It also makes the route easier when Ella is combined with Yala, Udawalawe, Tangalle, or Galle afterward.</p><h2>Practical advice</h2><p>Plan Ella as a two-night stay when possible. Start key viewpoints early, avoid adding too many waterfalls in one transfer day, and use the driver to balance the train experience with comfort. The goal is not to tick off every stop between Kandy and Ella. The goal is to arrive with enough energy to enjoy the hills.</p>',
    meta_title = 'Travel to Ella with a Private Driver',
    meta_description = 'Plan travel to Ella with a private driver, scenic train sections, tea country stops, luggage support, waterfalls, and realistic Sri Lanka hill-country timing.'
WHERE slug = 'travel-to-ella-with'
  AND CHAR_LENGTH(COALESCE(content, '')) < 1300;

UPDATE destination_photos dp
LEFT JOIN destinations d ON d.id = dp.destination_id
SET dp.caption = CONCAT(COALESCE(d.name, 'Sri Lanka destination'), ' travel photo for private Sri Lanka tours')
WHERE dp.caption IS NULL OR TRIM(dp.caption) = '';

UPDATE media_assets ma
LEFT JOIN destinations d ON d.id = ma.destination_id
SET ma.alt_text = CASE
    WHEN d.name IS NOT NULL AND TRIM(d.name) != '' THEN CONCAT(d.name, ' travel image for Sri Lanka private tours')
    ELSE 'Sri Lanka private travel image for Island Culture Travels'
END
WHERE ma.mime_type LIKE 'image/%'
  AND (ma.alt_text IS NULL OR TRIM(ma.alt_text) = '');
