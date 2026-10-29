-- COUNTRY-01: preserve the original trust-audit snapshot after the public
-- media replacements have been applied. This keeps the initial 19-page block
-- directly queryable instead of relying only on migration history.

SET NAMES utf8mb4;

ALTER TABLE country_market_audits
    ADD COLUMN initial_hero_image VARCHAR(255) NULL,
    ADD COLUMN initial_og_image VARCHAR(255) NULL,
    ADD COLUMN initial_media_gate VARCHAR(40) NULL,
    ADD COLUMN initial_blocked_reason TEXT NULL;

UPDATE country_market_audits
SET initial_hero_image = CASE
        WHEN country_slug = 'india' THEN 'countries/20260415161048_4ff65ef0d7122ca6.jpg'
        ELSE 'assets/images/placeholders/placeholder-country.svg'
    END,
    initial_og_image = CASE
        WHEN country_slug = 'india' THEN 'countries/20260415161048_67fdaf1d4a71fc0e.jpg'
        ELSE 'assets/images/og-default.jpg'
    END,
    initial_media_gate = CASE
        WHEN country_slug = 'india' THEN 'pass'
        ELSE 'blocked'
    END,
    initial_blocked_reason = CASE
        WHEN country_slug = 'india' THEN 'India was the only current media pass at the initial COUNTRY-01 audit.'
        ELSE 'Placeholder hero and default OG image required rights-cleared replacement before later content, SEO, linking, and responsive gates.'
    END,
    updated_at = CURRENT_TIMESTAMP
WHERE workstream = 'COUNTRY-01';
