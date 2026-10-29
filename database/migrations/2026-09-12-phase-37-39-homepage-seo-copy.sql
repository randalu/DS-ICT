-- PHASE 37-39: homepage commercial SEO title, H1, and opening copy.
-- This migration changes only DB-managed homepage copy; HSTS remains separate.

SET NAMES utf8mb4;

INSERT INTO site_settings (setting_key, setting_value)
VALUES
    ('homepage_new_seo_title', 'Sri Lanka Private Tours & Private Driver | Island Culture Travels'),
    ('homepage_new_seo_description', 'Plan a private Sri Lanka tour with your own driver-guide, flexible routes, cultural sites, wildlife, beaches, and practical support from arrival to departure.'),
    ('homepage_new_og_title', 'Sri Lanka Private Tours & Private Driver | Island Culture Travels'),
    ('homepage_new_og_description', 'Explore private Sri Lanka tours, chauffeur-guided travel, custom itineraries, and practical route ideas for couples, families, wildlife, beaches, and culture.')
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value);

UPDATE homepage_hero_media
SET headline_main = 'Private Sri Lanka Tours with Your Own Driver-Guide',
    supporting_text = 'Explore private Sri Lanka tours and chauffeur-guided travel for couples, families, wildlife, beaches, and culture—with a flexible itinerary shaped around your dates and pace.'
WHERE is_active = 1;
