-- COUNTRY-01: final local link/schema/responsive review markers and public
-- country-name grammar corrections. These are local review results; production
-- publication still requires the normal commit/deploy workflow.

SET NAMES utf8mb4;

ALTER TABLE country_market_audits
    ADD COLUMN schema_seo_status VARCHAR(40) NOT NULL DEFAULT 'pending',
    ADD COLUMN responsive_status VARCHAR(40) NOT NULL DEFAULT 'pending';

UPDATE country_market_editorial_drafts
SET country_name = CASE country_slug
    WHEN 'russian-federation' THEN 'Russia'
    WHEN 'united-states' THEN 'the United States'
    WHEN 'netherlands' THEN 'the Netherlands'
    WHEN 'maldives' THEN 'the Maldives'
    ELSE country_name
END,
    meta_title = CASE country_slug
        WHEN 'russian-federation' THEN REPLACE(meta_title, 'from Russian Federation', 'from Russia')
        WHEN 'united-states' THEN REPLACE(meta_title, 'from United States', 'from the United States')
        WHEN 'netherlands' THEN REPLACE(meta_title, 'from Netherlands', 'from the Netherlands')
        WHEN 'maldives' THEN REPLACE(meta_title, 'from Maldives', 'from the Maldives')
        ELSE meta_title
    END,
    meta_description = CASE country_slug
        WHEN 'russian-federation' THEN REPLACE(meta_description, 'from Russian Federation', 'from Russia')
        WHEN 'united-states' THEN REPLACE(meta_description, 'from United States', 'from the United States')
        WHEN 'netherlands' THEN REPLACE(meta_description, 'from Netherlands', 'from the Netherlands')
        WHEN 'maldives' THEN REPLACE(meta_description, 'from Maldives', 'from the Maldives')
        ELSE meta_description
    END,
    excerpt = CASE country_slug
        WHEN 'russian-federation' THEN REPLACE(excerpt, 'from Russian Federation', 'from Russia')
        WHEN 'united-states' THEN REPLACE(excerpt, 'from United States', 'from the United States')
        WHEN 'netherlands' THEN REPLACE(excerpt, 'from Netherlands', 'from the Netherlands')
        WHEN 'maldives' THEN REPLACE(excerpt, 'from Maldives', 'from the Maldives')
        ELSE excerpt
    END,
    intro = CASE country_slug
        WHEN 'russian-federation' THEN REPLACE(intro, 'from Russian Federation', 'from Russia')
        WHEN 'united-states' THEN REPLACE(intro, 'from United States', 'from the United States')
        WHEN 'netherlands' THEN REPLACE(intro, 'from Netherlands', 'from the Netherlands')
        WHEN 'maldives' THEN REPLACE(intro, 'from Maldives', 'from the Maldives')
        ELSE intro
    END,
    cta_title = CASE country_slug
        WHEN 'russian-federation' THEN REPLACE(cta_title, 'from Russian Federation', 'from Russia')
        WHEN 'united-states' THEN REPLACE(cta_title, 'from United States', 'from the United States')
        WHEN 'netherlands' THEN REPLACE(cta_title, 'from Netherlands', 'from the Netherlands')
        WHEN 'maldives' THEN REPLACE(cta_title, 'from Maldives', 'from the Maldives')
        ELSE cta_title
    END,
    updated_at = CURRENT_TIMESTAMP
WHERE country_slug IN ('russian-federation', 'united-states', 'netherlands', 'maldives');

UPDATE country_pages page
INNER JOIN country_market_editorial_drafts draft ON draft.country_slug = page.slug
SET page.meta_title = draft.meta_title,
    page.meta_description = draft.meta_description,
    page.excerpt = draft.excerpt,
    page.intro = draft.intro,
    page.cta_title = draft.cta_title,
    page.updated_at = CURRENT_TIMESTAMP
WHERE page.slug IN ('russian-federation', 'united-states', 'netherlands', 'maldives')
  AND draft.country_slug = page.slug;

UPDATE country_market_audits
SET internal_links_status = 'pass',
    internal_links_notes = 'Three curated country-market itinerary links plus destination links render in the local route smoke.',
    schema_seo_status = 'pass_local_route_smoke',
    responsive_status = 'pass_local_viewport_smoke',
    metadata_status = 'pass_local_route',
    updated_at = CURRENT_TIMESTAMP
WHERE workstream = 'COUNTRY-01';
