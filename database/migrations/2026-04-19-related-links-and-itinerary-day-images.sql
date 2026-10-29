-- Adds explicit itinerary<->tour relations and day-level itinerary images.
-- MySQL 8.x compatible (no ADD COLUMN IF NOT EXISTS). Run once per database.

ALTER TABLE itinerary_days
    ADD COLUMN image_path VARCHAR(255) NULL AFTER travel_time;

CREATE TABLE IF NOT EXISTS itinerary_tour (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    itinerary_id BIGINT UNSIGNED NOT NULL,
    tour_id BIGINT UNSIGNED NOT NULL,
    UNIQUE KEY uq_itinerary_tour_pair (itinerary_id, tour_id),
    KEY idx_itinerary_tour_itinerary (itinerary_id),
    KEY idx_itinerary_tour_tour (tour_id),
    CONSTRAINT fk_itinerary_tour_itinerary
        FOREIGN KEY (itinerary_id) REFERENCES itineraries(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_itinerary_tour_tour
        FOREIGN KEY (tour_id) REFERENCES tours(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
