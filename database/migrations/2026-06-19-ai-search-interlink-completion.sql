-- Completes destination and itinerary interlinks for the AI/search content pack.
-- Idempotent: pair tables have unique keys and these INSERT IGNORE statements
-- only add missing relationships between already seeded slugs.

SET NAMES utf8mb4;

INSERT IGNORE INTO itinerary_destination (itinerary_id, destination_id)
SELECT i.id, d.id FROM itineraries i JOIN destinations d ON d.slug IN ('colombo', 'sigiriya', 'kandy', 'ella', 'negombo')
WHERE i.slug = 'sri-lanka-itinerary-5-days';

INSERT IGNORE INTO itinerary_destination (itinerary_id, destination_id)
SELECT i.id, d.id FROM itineraries i JOIN destinations d ON d.slug IN ('colombo', 'sigiriya', 'kandy', 'ella', 'yala', 'galle', 'mirissa')
WHERE i.slug = 'sri-lanka-itinerary-7-days';

INSERT IGNORE INTO itinerary_destination (itinerary_id, destination_id)
SELECT i.id, d.id FROM itineraries i JOIN destinations d ON d.slug IN ('colombo', 'sigiriya', 'anuradhapura', 'polonnaruwa', 'kandy', 'nuwara-eliya', 'ella', 'yala', 'galle', 'mirissa', 'bentota', 'negombo')
WHERE i.slug = 'sri-lanka-itinerary-12-days';

INSERT IGNORE INTO itinerary_destination (itinerary_id, destination_id)
SELECT i.id, d.id FROM itineraries i JOIN destinations d ON d.slug IN ('negombo', 'sigiriya', 'kandy', 'nuwara-eliya', 'ella', 'mirissa', 'galle', 'bentota', 'tangalle')
WHERE i.slug = 'sri-lanka-honeymoon-itinerary-12-days';

INSERT IGNORE INTO itinerary_destination (itinerary_id, destination_id)
SELECT i.id, d.id FROM itineraries i JOIN destinations d ON d.slug IN ('yala', 'udawalawe', 'sigiriya', 'dambulla', 'anuradhapura', 'ella', 'tangalle')
WHERE i.slug = 'sri-lanka-wildlife-itinerary-9-days';

INSERT IGNORE INTO itinerary_destination (itinerary_id, destination_id)
SELECT i.id, d.id FROM itineraries i JOIN destinations d ON d.slug IN ('galle', 'unawatuna', 'hikkaduwa', 'bentota', 'mirissa', 'weligama', 'tangalle', 'matara')
WHERE i.slug = 'sri-lanka-south-coast-itinerary-8-days';

INSERT IGNORE INTO itinerary_destination (itinerary_id, destination_id)
SELECT i.id, d.id FROM itineraries i JOIN destinations d ON d.slug IN ('colombo', 'sigiriya', 'kandy', 'nuwara-eliya', 'ella', 'yala', 'galle', 'bentota', 'negombo')
WHERE i.slug = 'sri-lanka-luxury-itinerary-11-days';

INSERT IGNORE INTO itinerary_destination (itinerary_id, destination_id)
SELECT i.id, d.id FROM itineraries i JOIN destinations d ON d.slug IN ('horton-plains', 'knuckles-mountain-range', 'kandy', 'nuwara-eliya', 'udawalawe', 'yala', 'ratnapura')
WHERE i.slug = 'sri-lanka-birding-nature-itinerary-12-days';

INSERT IGNORE INTO experience_destination (experience_id, destination_id)
SELECT e.id, d.id FROM experiences e JOIN destinations d ON d.slug IN ('kandy', 'ella', 'nuwara-eliya')
WHERE e.slug = 'scenic-train-journeys';

INSERT IGNORE INTO experience_destination (experience_id, destination_id)
SELECT e.id, d.id FROM experiences e JOIN destinations d ON d.slug IN ('kandy', 'nuwara-eliya', 'ella')
WHERE e.slug IN ('tea-country-and-plantations', 'tea-country-nuwara-eliya');

INSERT IGNORE INTO experience_destination (experience_id, destination_id)
SELECT e.id, d.id FROM experiences e JOIN destinations d ON d.slug IN ('sigiriya', 'dambulla', 'kandy')
WHERE e.slug = 'village-food-and-cooking';

INSERT IGNORE INTO experience_destination (experience_id, destination_id)
SELECT e.id, d.id FROM experiences e JOIN destinations d ON d.slug IN ('sigiriya', 'dambulla', 'polonnaruwa')
WHERE e.slug = 'minneriya-elephant-gathering';

INSERT IGNORE INTO experience_destination (experience_id, destination_id)
SELECT e.id, d.id FROM experiences e JOIN destinations d ON d.slug IN ('bentota', 'hikkaduwa', 'galle', 'unawatuna', 'mirissa', 'trincomalee', 'passikudah', 'arugam-bay')
WHERE e.slug = 'beaches-and-coastal-life';

INSERT IGNORE INTO experience_destination (experience_id, destination_id)
SELECT e.id, d.id FROM experiences e JOIN destinations d ON d.slug IN ('ella', 'nuwara-eliya', 'horton-plains', 'knuckles-mountain-range')
WHERE e.slug = 'waterfalls-and-hill-country-views';

INSERT IGNORE INTO experience_destination (experience_id, destination_id)
SELECT e.id, d.id FROM experiences e JOIN destinations d ON d.slug IN ('mirissa', 'weligama', 'matara')
WHERE e.slug = 'mirissa-whale-watching';

INSERT IGNORE INTO experience_destination (experience_id, destination_id)
SELECT e.id, d.id FROM experiences e JOIN destinations d ON d.slug IN ('ella', 'sigiriya', 'knuckles-mountain-range', 'arugam-bay')
WHERE e.slug = 'soft-adventure';

INSERT IGNORE INTO experience_destination (experience_id, destination_id)
SELECT e.id, d.id FROM experiences e JOIN destinations d ON d.slug IN ('bentota', 'galle', 'kandy', 'tangalle')
WHERE e.slug IN ('ayurveda-and-wellness', 'ayurveda-wellness-retreat');

INSERT IGNORE INTO experience_destination (experience_id, destination_id)
SELECT e.id, d.id FROM experiences e JOIN destinations d ON d.slug IN ('weligama', 'mirissa', 'arugam-bay', 'hikkaduwa')
WHERE e.slug = 'south-coast-surf-lessons';

INSERT IGNORE INTO experience_destination (experience_id, destination_id)
SELECT e.id, d.id FROM experiences e JOIN destinations d ON d.slug IN ('colombo', 'kandy', 'galle', 'negombo')
WHERE e.slug = 'sri-lankan-food-market-tour';

INSERT IGNORE INTO experience_destination (experience_id, destination_id)
SELECT e.id, d.id FROM experiences e JOIN destinations d ON d.slug IN ('anuradhapura', 'polonnaruwa', 'kandy', 'dambulla', 'sigiriya')
WHERE e.slug = 'buddhist-heritage-pilgrimage';

INSERT IGNORE INTO experience_destination (experience_id, destination_id)
SELECT e.id, d.id FROM experiences e JOIN destinations d ON d.slug IN ('galle', 'mirissa', 'tangalle', 'bentota', 'ella', 'kandy')
WHERE e.slug = 'honeymoon-beach-boutique-stays';

INSERT IGNORE INTO experience_destination (experience_id, destination_id)
SELECT e.id, d.id FROM experiences e JOIN destinations d ON d.slug IN ('sigiriya', 'kandy', 'udawalawe', 'galle', 'bentota', 'negombo')
WHERE e.slug = 'family-wildlife-beach-route';

INSERT IGNORE INTO experience_destination (experience_id, destination_id)
SELECT e.id, d.id FROM experiences e JOIN destinations d ON d.slug IN ('colombo', 'sigiriya', 'kandy', 'nuwara-eliya', 'ella', 'yala', 'galle')
WHERE e.slug = 'luxury-slow-travel-sri-lanka';

INSERT IGNORE INTO experience_destination (experience_id, destination_id)
SELECT e.id, d.id FROM experiences e JOIN destinations d ON d.slug IN ('horton-plains', 'knuckles-mountain-range', 'ratnapura', 'yala', 'udawalawe')
WHERE e.slug = 'sinharaja-birding-nature';

INSERT IGNORE INTO experience_itinerary (experience_id, itinerary_id)
SELECT e.id, i.id FROM experiences e JOIN itineraries i ON i.slug IN ('sri-lanka-itinerary-7-days', 'sri-lanka-itinerary-10-days', 'sri-lanka-itinerary-14-days')
WHERE e.slug IN ('scenic-train-journeys', 'tea-country-and-plantations', 'waterfalls-and-hill-country-views', 'soft-adventure');

INSERT IGNORE INTO experience_itinerary (experience_id, itinerary_id)
SELECT e.id, i.id FROM experiences e JOIN itineraries i ON i.slug IN ('sri-lanka-wildlife-itinerary-9-days', 'sri-lanka-family-itinerary-10-days', 'sri-lanka-itinerary-14-days')
WHERE e.slug IN ('minneriya-elephant-gathering', 'family-wildlife-beach-route');

INSERT IGNORE INTO experience_itinerary (experience_id, itinerary_id)
SELECT e.id, i.id FROM experiences e JOIN itineraries i ON i.slug IN ('sri-lanka-south-coast-itinerary-8-days', 'sri-lanka-honeymoon-itinerary-12-days', 'sri-lanka-itinerary-14-days')
WHERE e.slug IN ('beaches-and-coastal-life', 'mirissa-whale-watching', 'south-coast-surf-lessons', 'honeymoon-beach-boutique-stays');

INSERT IGNORE INTO experience_itinerary (experience_id, itinerary_id)
SELECT e.id, i.id FROM experiences e JOIN itineraries i ON i.slug IN ('sri-lanka-itinerary-12-days', 'sri-lanka-itinerary-14-days', 'sri-lanka-luxury-itinerary-11-days')
WHERE e.slug IN ('sri-lankan-food-market-tour', 'buddhist-heritage-pilgrimage', 'ayurveda-and-wellness', 'ayurveda-wellness-retreat', 'luxury-slow-travel-sri-lanka');

INSERT IGNORE INTO experience_itinerary (experience_id, itinerary_id)
SELECT e.id, i.id FROM experiences e JOIN itineraries i ON i.slug IN ('sri-lanka-birding-nature-itinerary-12-days', 'sri-lanka-wildlife-itinerary-9-days')
WHERE e.slug = 'sinharaja-birding-nature';
