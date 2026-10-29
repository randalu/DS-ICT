-- Add day-by-day coverage for the live-only Sri Lanka Short Escape itinerary.
-- This slug may not exist in every local install, so the migration is idempotent
-- and only inserts/updates rows when the itinerary is present.

SET NAMES utf8mb4;

INSERT INTO itinerary_days
    (itinerary_id, day_number, day_end, title, summary, overnight_location, travel_time)
SELECT i.id, 1, 1, 'Arrival and Negombo recovery', 'Meet your private chauffeur at the airport and settle near Negombo for a light first day. Keep the schedule simple for flight recovery, check-in, and a relaxed coastal or lagoon-side evening.', 'Negombo', 'Airport to Negombo 20-40 minutes'
FROM itineraries i WHERE i.slug = 'sri-lanka-short-escape-5-days'
ON DUPLICATE KEY UPDATE
    day_end = VALUES(day_end),
    title = VALUES(title),
    summary = VALUES(summary),
    overnight_location = VALUES(overnight_location),
    travel_time = VALUES(travel_time),
    updated_at = CURRENT_TIMESTAMP;

INSERT INTO itinerary_days
    (itinerary_id, day_number, day_end, title, summary, overnight_location, travel_time)
SELECT i.id, 2, 2, 'Sigiriya and Dambulla culture', 'Travel into the Cultural Triangle for Sigiriya or Dambulla Cave Temple, choosing the main visit around heat, arrival timing, and guest energy. Keep the day focused because this is a short route.', 'Sigiriya, Dambulla, or Habarana', 'Negombo to Sigiriya/Dambulla 4-5 hours'
FROM itineraries i WHERE i.slug = 'sri-lanka-short-escape-5-days'
ON DUPLICATE KEY UPDATE
    day_end = VALUES(day_end),
    title = VALUES(title),
    summary = VALUES(summary),
    overnight_location = VALUES(overnight_location),
    travel_time = VALUES(travel_time),
    updated_at = CURRENT_TIMESTAMP;

INSERT INTO itinerary_days
    (itinerary_id, day_number, day_end, title, summary, overnight_location, travel_time)
SELECT i.id, 3, 3, 'Kandy via Matale', 'Continue to Kandy with a practical Matale, spice, craft, garden, or temple-area stop based on timing. Use the evening for the lake, food, or the Temple of the Sacred Tooth Relic area.', 'Kandy', 'Sigiriya/Dambulla to Kandy 2.5-3.5 hours'
FROM itineraries i WHERE i.slug = 'sri-lanka-short-escape-5-days'
ON DUPLICATE KEY UPDATE
    day_end = VALUES(day_end),
    title = VALUES(title),
    summary = VALUES(summary),
    overnight_location = VALUES(overnight_location),
    travel_time = VALUES(travel_time),
    updated_at = CURRENT_TIMESTAMP;

INSERT INTO itinerary_days
    (itinerary_id, day_number, day_end, title, summary, overnight_location, travel_time)
SELECT i.id, 4, 4, 'Nuwara Eliya or Bentota choice', 'Choose the final experience based on the traveller: tea-country scenery around Nuwara Eliya for hills and waterfalls, or Bentota and the west coast for a softer beach finish before departure.', 'Nuwara Eliya, Bentota, or west coast', 'Kandy to Nuwara Eliya 2.5-3.5 hours; Kandy to Bentota 4-5 hours'
FROM itineraries i WHERE i.slug = 'sri-lanka-short-escape-5-days'
ON DUPLICATE KEY UPDATE
    day_end = VALUES(day_end),
    title = VALUES(title),
    summary = VALUES(summary),
    overnight_location = VALUES(overnight_location),
    travel_time = VALUES(travel_time),
    updated_at = CURRENT_TIMESTAMP;

INSERT INTO itinerary_days
    (itinerary_id, day_number, day_end, title, summary, overnight_location, travel_time)
SELECT i.id, 5, 5, 'Colombo shopping and airport drop-off', 'Return toward Colombo or the airport with time for shopping, a final meal, or a short city stop only if the flight schedule leaves a safe buffer.', 'Departure, Colombo, or Negombo', 'Final transfer varies by overnight base'
FROM itineraries i WHERE i.slug = 'sri-lanka-short-escape-5-days'
ON DUPLICATE KEY UPDATE
    day_end = VALUES(day_end),
    title = VALUES(title),
    summary = VALUES(summary),
    overnight_location = VALUES(overnight_location),
    travel_time = VALUES(travel_time),
    updated_at = CURRENT_TIMESTAMP;
