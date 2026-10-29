-- Adds CTA + FAQ fields across page types and review tour month/year metadata.
-- MySQL 8.x compatible (no ADD COLUMN IF NOT EXISTS). Run once per database.

ALTER TABLE tours
    ADD COLUMN cta_title VARCHAR(255) NULL AFTER faq_json;
ALTER TABLE tours
    ADD COLUMN cta_text TEXT NULL AFTER cta_title;

ALTER TABLE itineraries
    ADD COLUMN faq_json LONGTEXT NULL AFTER travel_notes;
ALTER TABLE itineraries
    ADD COLUMN cta_title VARCHAR(255) NULL AFTER faq_json;
ALTER TABLE itineraries
    ADD COLUMN cta_text TEXT NULL AFTER cta_title;

ALTER TABLE country_pages
    ADD COLUMN cta_title VARCHAR(255) NULL AFTER faq_json;
ALTER TABLE country_pages
    ADD COLUMN cta_text TEXT NULL AFTER cta_title;

ALTER TABLE reviews
    ADD COLUMN tour_done_month CHAR(7) NULL AFTER trip_type;
