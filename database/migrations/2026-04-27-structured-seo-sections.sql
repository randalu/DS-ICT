-- Structured SEO section columns: best months, route notes, route stops.
-- MySQL 8.x compatible (no IF NOT EXISTS — re-running this migration is unsafe;
-- check whether the columns already exist before re-applying).

ALTER TABLE destinations
    ADD COLUMN best_time_json   JSON NULL AFTER best_time,
    ADD COLUMN route_notes_json JSON NULL AFTER best_time_json;

ALTER TABLE itineraries
    ADD COLUMN best_months_json JSON NULL AFTER travel_notes;
