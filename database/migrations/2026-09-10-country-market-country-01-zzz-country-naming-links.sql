-- COUNTRY-01: country-market display names and curated itinerary links.
-- Keep the source country_name for admin/audit identity; public display names
-- correct consumer grammar without changing the /from/{slug} URL contract.

SET NAMES utf8mb4;

ALTER TABLE country_pages
    ADD COLUMN public_country_name VARCHAR(120) NULL AFTER country_name;

UPDATE country_pages
SET public_country_name = CASE slug
    WHEN 'united-kingdom' THEN 'the United Kingdom'
    WHEN 'russian-federation' THEN 'Russia'
    WHEN 'united-states' THEN 'the United States'
    WHEN 'netherlands' THEN 'the Netherlands'
    WHEN 'maldives' THEN 'the Maldives'
    ELSE country_name
END
WHERE is_published = 1;

CREATE TABLE IF NOT EXISTS country_market_itinerary_links (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    workstream VARCHAR(40) NOT NULL DEFAULT 'COUNTRY-01',
    country_slug VARCHAR(180) NOT NULL,
    itinerary_slug VARCHAR(180) NOT NULL,
    link_type VARCHAR(40) NOT NULL DEFAULT 'market_curated',
    sort_order INT NOT NULL DEFAULT 0,
    owner VARCHAR(120) NOT NULL DEFAULT 'Editorial lead',
    review_date DATE NULL,
    notes TEXT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_country_market_itinerary_link (country_slug, itinerary_slug),
    KEY idx_country_market_itinerary_country (country_slug, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO country_market_itinerary_links (
    country_slug, itinerary_slug, link_type, sort_order, owner, review_date, notes
) VALUES
('india','sri-lanka-itinerary-7-days','market_curated',1,'Editorial lead','2026-10-10','General first-visit planning option; confirm dates, interests, and current availability.'),
('india','sri-lanka-family-itinerary-10-days','market_curated',2,'Editorial lead','2026-10-10','General family planning option; confirm suitability and inclusions before booking.'),
('india','sri-lanka-itinerary-12-days','market_curated',3,'Editorial lead','2026-10-10','General longer-route planning option; route order and travel times require review.'),
('united-kingdom','sri-lanka-itinerary-10-days','market_curated',1,'Editorial lead','2026-10-10','General first-visit planning option; confirm dates, interests, and current availability.'),
('united-kingdom','sri-lanka-itinerary-14-days','market_curated',2,'Editorial lead','2026-10-10','General longer-route planning option; route order and travel times require review.'),
('united-kingdom','sri-lanka-wildlife-itinerary-9-days','market_curated',3,'Editorial lead','2026-10-10','Wildlife planning option; do not imply guaranteed sightings or access.'),
('russian-federation','sri-lanka-itinerary-7-days','market_curated',1,'Editorial lead','2026-10-10','General first-visit planning option; confirm dates, interests, and current availability.'),
('russian-federation','sri-lanka-itinerary-10-days','market_curated',2,'Editorial lead','2026-10-10','General route planning option; route order and travel times require review.'),
('russian-federation','sri-lanka-wildlife-itinerary-9-days','market_curated',3,'Editorial lead','2026-10-10','Wildlife planning option; do not imply guaranteed sightings or access.'),
('china','sri-lanka-itinerary-7-days','market_curated',1,'Editorial lead','2026-10-10','General first-visit planning option; confirm dates, interests, and current availability.'),
('china','sri-lanka-itinerary-10-days','market_curated',2,'Editorial lead','2026-10-10','General route planning option; route order and travel times require review.'),
('china','sri-lanka-itinerary-14-days','market_curated',3,'Editorial lead','2026-10-10','General longer-route planning option; route order and travel times require review.'),
('germany','sri-lanka-itinerary-10-days','market_curated',1,'Editorial lead','2026-10-10','General first-visit planning option; confirm dates, interests, and current availability.'),
('germany','sri-lanka-itinerary-12-days','market_curated',2,'Editorial lead','2026-10-10','General route planning option; route order and travel times require review.'),
('germany','sri-lanka-birding-nature-itinerary-12-days','market_curated',3,'Editorial lead','2026-10-10','Nature planning option; activities and conditions vary by season and location.'),
('france','sri-lanka-itinerary-10-days','market_curated',1,'Editorial lead','2026-10-10','General first-visit planning option; confirm dates, interests, and current availability.'),
('france','sri-lanka-itinerary-14-days','market_curated',2,'Editorial lead','2026-10-10','General longer-route planning option; route order and travel times require review.'),
('france','sri-lanka-honeymoon-itinerary-12-days','market_curated',3,'Editorial lead','2026-10-10','Honeymoon planning option; confirm inclusions and preferences before booking.'),
('australia','sri-lanka-itinerary-10-days','market_curated',1,'Editorial lead','2026-10-10','General first-visit planning option; confirm dates, interests, and current availability.'),
('australia','sri-lanka-itinerary-12-days','market_curated',2,'Editorial lead','2026-10-10','General route planning option; route order and travel times require review.'),
('australia','sri-lanka-itinerary-14-days','market_curated',3,'Editorial lead','2026-10-10','General longer-route planning option; route order and travel times require review.'),
('united-states','sri-lanka-itinerary-10-days','market_curated',1,'Editorial lead','2026-10-10','General first-visit planning option; confirm dates, interests, and current availability.'),
('united-states','sri-lanka-itinerary-14-days','market_curated',2,'Editorial lead','2026-10-10','General longer-route planning option; route order and travel times require review.'),
('united-states','sri-lanka-luxury-itinerary-11-days','market_curated',3,'Editorial lead','2026-10-10','Luxury planning option; confirm accommodation, transport, and inclusions before booking.'),
('poland','sri-lanka-itinerary-7-days','market_curated',1,'Editorial lead','2026-10-10','General first-visit planning option; confirm dates, interests, and current availability.'),
('poland','sri-lanka-itinerary-10-days','market_curated',2,'Editorial lead','2026-10-10','General route planning option; route order and travel times require review.'),
('poland','sri-lanka-wildlife-itinerary-9-days','market_curated',3,'Editorial lead','2026-10-10','Wildlife planning option; do not imply guaranteed sightings or access.'),
('netherlands','sri-lanka-itinerary-10-days','market_curated',1,'Editorial lead','2026-10-10','General first-visit planning option; confirm dates, interests, and current availability.'),
('netherlands','sri-lanka-itinerary-12-days','market_curated',2,'Editorial lead','2026-10-10','General route planning option; route order and travel times require review.'),
('netherlands','sri-lanka-birding-nature-itinerary-12-days','market_curated',3,'Editorial lead','2026-10-10','Nature planning option; activities and conditions vary by season and location.'),
('canada','sri-lanka-itinerary-10-days','market_curated',1,'Editorial lead','2026-10-10','General first-visit planning option; confirm dates, interests, and current availability.'),
('canada','sri-lanka-itinerary-14-days','market_curated',2,'Editorial lead','2026-10-10','General longer-route planning option; route order and travel times require review.'),
('canada','sri-lanka-luxury-itinerary-11-days','market_curated',3,'Editorial lead','2026-10-10','Luxury planning option; confirm accommodation, transport, and inclusions before booking.'),
('bangladesh','sri-lanka-itinerary-5-days','market_curated',1,'Editorial lead','2026-10-10','Short-route planning option; confirm arrival times and travel pace.'),
('bangladesh','sri-lanka-itinerary-7-days','market_curated',2,'Editorial lead','2026-10-10','General first-visit planning option; confirm dates, interests, and current availability.'),
('bangladesh','sri-lanka-family-itinerary-10-days','market_curated',3,'Editorial lead','2026-10-10','General family planning option; confirm suitability and inclusions before booking.'),
('japan','sri-lanka-itinerary-7-days','market_curated',1,'Editorial lead','2026-10-10','General first-visit planning option; confirm dates, interests, and current availability.'),
('japan','sri-lanka-itinerary-10-days','market_curated',2,'Editorial lead','2026-10-10','General route planning option; route order and travel times require review.'),
('japan','sri-lanka-itinerary-14-days','market_curated',3,'Editorial lead','2026-10-10','General longer-route planning option; route order and travel times require review.'),
('italy','sri-lanka-itinerary-10-days','market_curated',1,'Editorial lead','2026-10-10','General first-visit planning option; confirm dates, interests, and current availability.'),
('italy','sri-lanka-itinerary-12-days','market_curated',2,'Editorial lead','2026-10-10','General route planning option; route order and travel times require review.'),
('italy','sri-lanka-south-coast-itinerary-8-days','market_curated',3,'Editorial lead','2026-10-10','Coastal planning option; coast conditions vary by month and location.'),
('maldives','sri-lanka-itinerary-5-days','market_curated',1,'Editorial lead','2026-10-10','Short-route planning option; confirm arrival times and travel pace.'),
('maldives','sri-lanka-itinerary-7-days','market_curated',2,'Editorial lead','2026-10-10','General first-visit planning option; confirm dates, interests, and current availability.'),
('maldives','sri-lanka-south-coast-itinerary-8-days','market_curated',3,'Editorial lead','2026-10-10','Coastal planning option; coast conditions vary by month and location.'),
('switzerland','sri-lanka-itinerary-10-days','market_curated',1,'Editorial lead','2026-10-10','General first-visit planning option; confirm dates, interests, and current availability.'),
('switzerland','sri-lanka-itinerary-12-days','market_curated',2,'Editorial lead','2026-10-10','General route planning option; route order and travel times require review.'),
('switzerland','sri-lanka-luxury-itinerary-11-days','market_curated',3,'Editorial lead','2026-10-10','Luxury planning option; confirm accommodation, transport, and inclusions before booking.'),
('malaysia','sri-lanka-itinerary-5-days','market_curated',1,'Editorial lead','2026-10-10','Short-route planning option; confirm arrival times and travel pace.'),
('malaysia','sri-lanka-itinerary-7-days','market_curated',2,'Editorial lead','2026-10-10','General first-visit planning option; confirm dates, interests, and current availability.'),
('malaysia','sri-lanka-itinerary-10-days','market_curated',3,'Editorial lead','2026-10-10','General route planning option; route order and travel times require review.'),
('spain','sri-lanka-itinerary-10-days','market_curated',1,'Editorial lead','2026-10-10','General first-visit planning option; confirm dates, interests, and current availability.'),
('spain','sri-lanka-itinerary-12-days','market_curated',2,'Editorial lead','2026-10-10','General route planning option; route order and travel times require review.'),
('spain','sri-lanka-south-coast-itinerary-8-days','market_curated',3,'Editorial lead','2026-10-10','Coastal planning option; coast conditions vary by month and location.'),
('israel','sri-lanka-itinerary-7-days','market_curated',1,'Editorial lead','2026-10-10','General first-visit planning option; confirm dates, interests, and current availability.'),
('israel','sri-lanka-itinerary-10-days','market_curated',2,'Editorial lead','2026-10-10','General route planning option; route order and travel times require review.'),
('israel','sri-lanka-wildlife-itinerary-9-days','market_curated',3,'Editorial lead','2026-10-10','Wildlife planning option; do not imply guaranteed sightings or access.'),
('denmark','sri-lanka-itinerary-10-days','market_curated',1,'Editorial lead','2026-10-10','General first-visit planning option; confirm dates, interests, and current availability.'),
('denmark','sri-lanka-itinerary-12-days','market_curated',2,'Editorial lead','2026-10-10','General route planning option; route order and travel times require review.'),
('denmark','sri-lanka-birding-nature-itinerary-12-days','market_curated',3,'Editorial lead','2026-10-10','Nature planning option; activities and conditions vary by season and location.')
ON DUPLICATE KEY UPDATE
    link_type = VALUES(link_type),
    sort_order = VALUES(sort_order),
    owner = VALUES(owner),
    review_date = VALUES(review_date),
    notes = VALUES(notes),
    updated_at = CURRENT_TIMESTAMP;
