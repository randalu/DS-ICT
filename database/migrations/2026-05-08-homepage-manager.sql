-- Homepage manager for /new.php: hero media pool plus section/SEO/CTA settings.
-- Safe to run once on VPS; does not modify index.php or existing homepage content.

CREATE TABLE IF NOT EXISTS homepage_hero_media (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  media_asset_id BIGINT UNSIGNED NULL,
  media_type VARCHAR(20) NOT NULL DEFAULT 'image',
  file_path VARCHAR(255) NOT NULL,
  poster_path VARCHAR(255) NULL,
  headline_script VARCHAR(180) NULL,
  headline_main VARCHAR(255) NULL,
  supporting_text TEXT NULL,
  primary_cta_label VARCHAR(120) NULL,
  primary_cta_url VARCHAR(255) NULL,
  secondary_cta_label VARCHAR(120) NULL,
  secondary_cta_url VARCHAR(255) NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  sort_order INT NOT NULL DEFAULT 0,
  created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_homepage_hero_active (is_active, sort_order, id),
  KEY idx_homepage_hero_asset (media_asset_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS site_settings (
  setting_key VARCHAR(120) PRIMARY KEY,
  setting_value LONGTEXT NULL,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO site_settings (setting_key, setting_value)
SELECT 'homepage_new_layout_json', '{"trust":{"label":"Trust Strip","enabled":true,"sort_order":10},"itineraries":{"label":"Featured Itineraries","enabled":true,"sort_order":20},"experiences":{"label":"Experiences","enabled":true,"sort_order":30},"destinations":{"label":"Destinations","enabled":true,"sort_order":40},"approach":{"label":"Why Travel With Us","enabled":true,"sort_order":50},"reviews":{"label":"Reviews","enabled":true,"sort_order":60},"cta":{"label":"CTA Banner","enabled":true,"sort_order":70}}'
WHERE NOT EXISTS (SELECT 1 FROM site_settings WHERE setting_key = 'homepage_new_layout_json');

INSERT INTO site_settings (setting_key, setting_value)
SELECT 'homepage_new_seo_title', 'Private Journeys, Authentic Experiences | Island Culture Travels'
WHERE NOT EXISTS (SELECT 1 FROM site_settings WHERE setting_key = 'homepage_new_seo_title');

INSERT INTO site_settings (setting_key, setting_value)
SELECT 'homepage_new_seo_description', 'Private Sri Lanka tours with driver-guides, flexible itineraries, destination highlights, local experiences, and traveler reviews.'
WHERE NOT EXISTS (SELECT 1 FROM site_settings WHERE setting_key = 'homepage_new_seo_description');

INSERT INTO site_settings (setting_key, setting_value)
SELECT 'homepage_new_og_title', 'Private Journeys, Authentic Experiences | Island Culture Travels'
WHERE NOT EXISTS (SELECT 1 FROM site_settings WHERE setting_key = 'homepage_new_og_title');

INSERT INTO site_settings (setting_key, setting_value)
SELECT 'homepage_new_og_description', 'Explore private Sri Lanka tours, signature experiences, popular destinations, and traveler reviews with Island Culture Travels.'
WHERE NOT EXISTS (SELECT 1 FROM site_settings WHERE setting_key = 'homepage_new_og_description');

INSERT INTO site_settings (setting_key, setting_value)
SELECT 'homepage_new_og_image', ''
WHERE NOT EXISTS (SELECT 1 FROM site_settings WHERE setting_key = 'homepage_new_og_image');

INSERT INTO site_settings (setting_key, setting_value)
SELECT 'homepage_new_cta_heading', 'Ready to Plan Your Sri Lanka Journey?'
WHERE NOT EXISTS (SELECT 1 FROM site_settings WHERE setting_key = 'homepage_new_cta_heading');

INSERT INTO site_settings (setting_key, setting_value)
SELECT 'homepage_new_cta_body', 'Let our travel experts create the perfect itinerary for your unforgettable trip.'
WHERE NOT EXISTS (SELECT 1 FROM site_settings WHERE setting_key = 'homepage_new_cta_body');

INSERT INTO site_settings (setting_key, setting_value)
SELECT 'homepage_new_cta_button_label', 'Plan My Trip'
WHERE NOT EXISTS (SELECT 1 FROM site_settings WHERE setting_key = 'homepage_new_cta_button_label');

INSERT INTO site_settings (setting_key, setting_value)
SELECT 'homepage_new_cta_button_url', 'contact'
WHERE NOT EXISTS (SELECT 1 FROM site_settings WHERE setting_key = 'homepage_new_cta_button_url');

INSERT INTO site_settings (setting_key, setting_value)
SELECT 'homepage_new_cta_image', 'assets/images/home-hero/home-5.jpg'
WHERE NOT EXISTS (SELECT 1 FROM site_settings WHERE setting_key = 'homepage_new_cta_image');
