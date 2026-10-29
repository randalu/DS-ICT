-- COUNTRY-04: refresh canonical source endpoints and reconcile media tracking
-- with the rights-verified assets already served by country_pages.
--
-- This migration does not rewrite country copy, change public URLs, or alter
-- HSTS. It only removes stale tracking metadata after a live public/DB check.

SET NAMES utf8mb4;

UPDATE country_market_sources
SET
    source_url = 'https://www.airport.lk/flight_info/departure.php',
    verified_on = '2026-09-12',
    review_date = '2026-09-19',
    notes = 'Use for current arrival/departure availability only. Schedules change; do not present a route or flight time as fixed. Canonical live departures endpoint checked 2026-09-12.'
WHERE source_key = 'bandaranaike-flight-schedule';

UPDATE country_market_sources
SET
    source_url = 'https://travelhealthpro.org.uk/countries/sri-lanka',
    verified_on = '2026-09-12',
    review_date = '2026-12-12',
    notes = 'Use for UK-traveller health planning, yellow-fever certificate conditions, mosquito-borne illness, and clinician signposting. Canonical country endpoint checked 2026-09-12.'
WHERE source_key = 'nathnac-sri-lanka-health';

UPDATE country_market_media_assignments AS assignment
INNER JOIN country_pages AS page
    ON page.slug = assignment.country_slug
SET
    assignment.current_hero_path = page.hero_image,
    assignment.current_og_path = page.og_image,
    assignment.replacement_status = 'published_locally',
    assignment.rights_status = 'rights_verified',
    assignment.notes = 'Live country_pages currently serve the rights-verified candidate asset as hero and OG. Tracking reconciled by COUNTRY-04 after public media verification; country-specific link, schema, and responsive checks remain separate gates.',
    assignment.updated_at = CURRENT_TIMESTAMP
WHERE assignment.country_slug <> 'india'
  AND page.is_published = 1
  AND page.hero_image IS NOT NULL
  AND page.og_image IS NOT NULL
  AND page.hero_image NOT LIKE '%placeholder%'
  AND page.og_image NOT LIKE '%og-default%'
  AND assignment.candidate_asset_path IS NOT NULL
  AND assignment.candidate_asset_path = page.hero_image
  AND assignment.candidate_asset_path = page.og_image
  AND assignment.rights_status = 'rights_verified';
