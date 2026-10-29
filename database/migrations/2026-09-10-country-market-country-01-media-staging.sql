-- COUNTRY-01: stage rights-cleared photography for the 19 blocked pages.
--
-- This migration records the asset files and their source/license evidence, and
-- prepares page assignments. It intentionally does not update country_pages.
-- Public hero/OG replacement remains gated on UK pilot approval and final
-- deployment of these files.

SET NAMES utf8mb4;

INSERT INTO media_assets (
    file_path,
    file_name,
    mime_type,
    file_size,
    alt_text,
    seo_title,
    tags,
    source_type,
    source_url,
    created_at,
    updated_at
)
SELECT
    'assets/images/country-market/nine-arch-bridge.jpg',
    'nine-arch-bridge.jpg',
    'image/jpeg',
    1914332,
    'Nine Arch Bridge in the green hill country of Sri Lanka',
    'Nine Arch Bridge Sri Lanka',
    'Sri Lanka, Ella, Nine Arch Bridge, hill country',
    'Unsplash License',
    'https://unsplash.com/photos/nine-arch-bridge-surrounded-by-lush-green-forest-w5jDXdaRvO0',
    CURRENT_TIMESTAMP,
    CURRENT_TIMESTAMP
WHERE NOT EXISTS (
    SELECT 1 FROM media_assets
    WHERE file_path = 'assets/images/country-market/nine-arch-bridge.jpg'
);

INSERT INTO media_assets (
    file_path, file_name, mime_type, file_size, alt_text, seo_title, tags,
    source_type, source_url, created_at, updated_at
)
SELECT
    'assets/images/country-market/nuwara-eliya-tea.jpg',
    'nuwara-eliya-tea.jpg',
    'image/jpeg',
    1109937,
    'Tea plantation landscape near Nuwara Eliya in Sri Lanka',
    'Nuwara Eliya tea country Sri Lanka',
    'Sri Lanka, Nuwara Eliya, tea country, hill country',
    'Unsplash License',
    'https://unsplash.com/photos/lush-green-tea-plantation-on-a-misty-hillside-ZWdrM9IBBIE',
    CURRENT_TIMESTAMP,
    CURRENT_TIMESTAMP
WHERE NOT EXISTS (
    SELECT 1 FROM media_assets
    WHERE file_path = 'assets/images/country-market/nuwara-eliya-tea.jpg'
);

INSERT INTO media_assets (
    file_path, file_name, mime_type, file_size, alt_text, seo_title, tags,
    source_type, source_url, created_at, updated_at
)
SELECT
    'assets/images/country-market/mirissa-coastline.jpg',
    'mirissa-coastline.jpg',
    'image/jpeg',
    1077892,
    'Tropical coastline near Mirissa in southern Sri Lanka',
    'Mirissa coastline Sri Lanka',
    'Sri Lanka, Mirissa, south coast, coastline',
    'Unsplash License',
    'https://unsplash.com/photos/tropical-coastline-with-lush-trees-and-ocean-waves-iscpI9MWDxg',
    CURRENT_TIMESTAMP,
    CURRENT_TIMESTAMP
WHERE NOT EXISTS (
    SELECT 1 FROM media_assets
    WHERE file_path = 'assets/images/country-market/mirissa-coastline.jpg'
);

INSERT INTO media_assets (
    file_path, file_name, mime_type, file_size, alt_text, seo_title, tags,
    source_type, source_url, created_at, updated_at
)
SELECT
    'assets/images/country-market/unawatuna-beach.jpg',
    'unawatuna-beach.jpg',
    'image/jpeg',
    174073,
    'Unawatuna beach and sea in southern Sri Lanka',
    'Unawatuna Beach Sri Lanka',
    'Sri Lanka, Unawatuna, south coast, beach',
    'Unsplash License',
    'https://unsplash.com/photos/landscape-photo-of-island-iz8MNOJWW1c',
    CURRENT_TIMESTAMP,
    CURRENT_TIMESTAMP
WHERE NOT EXISTS (
    SELECT 1 FROM media_assets
    WHERE file_path = 'assets/images/country-market/unawatuna-beach.jpg'
);

INSERT INTO media_assets (
    file_path, file_name, mime_type, file_size, alt_text, seo_title, tags,
    source_type, source_url, created_at, updated_at
)
SELECT
    'assets/images/country-market/minneriya-elephant.jpg',
    'minneriya-elephant.jpg',
    'image/jpeg',
    1682853,
    'Asian elephant in Minneriya National Park, Sri Lanka',
    'Minneriya National Park Sri Lanka',
    'Sri Lanka, Minneriya, wildlife, elephant, national park',
    'Unsplash License',
    'https://unsplash.com/photos/a-young-elephant-walks-beside-a-vehicle-in-tall-grass-GFM00I6Wkok',
    CURRENT_TIMESTAMP,
    CURRENT_TIMESTAMP
WHERE NOT EXISTS (
    SELECT 1 FROM media_assets
    WHERE file_path = 'assets/images/country-market/minneriya-elephant.jpg'
);

INSERT INTO media_assets (
    file_path, file_name, mime_type, file_size, alt_text, seo_title, tags,
    source_type, source_url, created_at, updated_at
)
SELECT
    'assets/images/country-market/matara-beach.jpg',
    'matara-beach.jpg',
    'image/jpeg',
    933117,
    'Palm-fringed beach near Matara in southern Sri Lanka',
    'Matara beach Sri Lanka',
    'Sri Lanka, Matara, south coast, beach',
    'Unsplash License',
    'https://unsplash.com/photos/a-beach-with-palm-trees-and-the-ocean-yTOLWxLFYP4',
    CURRENT_TIMESTAMP,
    CURRENT_TIMESTAMP
WHERE NOT EXISTS (
    SELECT 1 FROM media_assets
    WHERE file_path = 'assets/images/country-market/matara-beach.jpg'
);

INSERT INTO country_market_media_rights (
    asset_path,
    asset_role,
    rights_status,
    source_type,
    source_url,
    license,
    credit,
    proof_reference,
    owner,
    review_date,
    notes
)
VALUES
    ('assets/images/country-market/nine-arch-bridge.jpg', 'hero_og_candidate', 'rights_verified', 'Unsplash', 'https://unsplash.com/photos/nine-arch-bridge-surrounded-by-lush-green-forest-w5jDXdaRvO0', 'Unsplash License', 'Robin Grevers / Unsplash', 'https://unsplash.com/license', 'Media editor', '2026-10-10', 'Landscape source verified and downloaded locally; no attribution required by the license, but source is retained for audit. Avoid implying route, season, or availability claims from the image.'),
    ('assets/images/country-market/nuwara-eliya-tea.jpg', 'hero_og_candidate', 'rights_verified', 'Unsplash', 'https://unsplash.com/photos/lush-green-tea-plantation-on-a-misty-hillside-ZWdrM9IBBIE', 'Unsplash License', 'Erik Esly / Unsplash', 'https://unsplash.com/license', 'Media editor', '2026-10-10', 'Landscape source verified and downloaded locally; no attribution required by the license, but source is retained for audit.'),
    ('assets/images/country-market/mirissa-coastline.jpg', 'hero_og_candidate', 'rights_verified', 'Unsplash', 'https://unsplash.com/photos/tropical-coastline-with-lush-trees-and-ocean-waves-iscpI9MWDxg', 'Unsplash License', 'Zoshua Colah / Unsplash', 'https://unsplash.com/license', 'Media editor', '2026-10-10', 'Landscape source verified and downloaded locally; no attribution required by the license, but source is retained for audit.'),
    ('assets/images/country-market/unawatuna-beach.jpg', 'hero_og_candidate', 'rights_verified', 'Unsplash', 'https://unsplash.com/photos/landscape-photo-of-island-iz8MNOJWW1c', 'Unsplash License', 'Rowan Heuvel / Unsplash', 'https://unsplash.com/license', 'Media editor', '2026-10-10', 'Landscape source verified and downloaded locally; no attribution required by the license, but source is retained for audit.'),
    ('assets/images/country-market/minneriya-elephant.jpg', 'hero_og_candidate', 'rights_verified', 'Unsplash', 'https://unsplash.com/photos/a-young-elephant-walks-beside-a-vehicle-in-tall-grass-GFM00I6Wkok', 'Unsplash License', 'Alehandra / Unsplash', 'https://unsplash.com/license', 'Media editor', '2026-10-10', 'Wildlife source verified and downloaded locally; retain responsible wildlife wording and do not imply sightings or guaranteed access.'),
    ('assets/images/country-market/matara-beach.jpg', 'hero_og_candidate', 'rights_verified', 'Unsplash', 'https://unsplash.com/photos/a-beach-with-palm-trees-and-the-ocean-yTOLWxLFYP4', 'Unsplash License', 'Dulana Kodithuwakku / Unsplash', 'https://unsplash.com/license', 'Media editor', '2026-10-10', 'Landscape source verified and downloaded locally; no attribution required by the license, but source is retained for audit.')
ON DUPLICATE KEY UPDATE
    rights_status = VALUES(rights_status),
    source_type = VALUES(source_type),
    source_url = VALUES(source_url),
    license = VALUES(license),
    credit = VALUES(credit),
    proof_reference = VALUES(proof_reference),
    owner = VALUES(owner),
    review_date = VALUES(review_date),
    notes = VALUES(notes),
    updated_at = CURRENT_TIMESTAMP;

UPDATE country_market_media_assignments
SET
    replacement_status = 'ready_for_publish_after_pilot',
    rights_status = 'rights_verified',
    candidate_license = 'Unsplash License',
    candidate_asset_path = CASE country_slug
        WHEN 'united-kingdom' THEN 'assets/images/country-market/nuwara-eliya-tea.jpg'
        WHEN 'russian-federation' THEN 'assets/images/country-market/mirissa-coastline.jpg'
        WHEN 'china' THEN 'assets/images/country-market/nine-arch-bridge.jpg'
        WHEN 'germany' THEN 'assets/images/country-market/unawatuna-beach.jpg'
        WHEN 'france' THEN 'assets/images/country-market/minneriya-elephant.jpg'
        WHEN 'australia' THEN 'assets/images/country-market/matara-beach.jpg'
        WHEN 'united-states' THEN 'assets/images/country-market/mirissa-coastline.jpg'
        WHEN 'poland' THEN 'assets/images/country-market/nine-arch-bridge.jpg'
        WHEN 'netherlands' THEN 'assets/images/country-market/nuwara-eliya-tea.jpg'
        WHEN 'canada' THEN 'assets/images/country-market/unawatuna-beach.jpg'
        WHEN 'bangladesh' THEN 'assets/images/country-market/minneriya-elephant.jpg'
        WHEN 'japan' THEN 'assets/images/country-market/matara-beach.jpg'
        WHEN 'italy' THEN 'assets/images/country-market/mirissa-coastline.jpg'
        WHEN 'maldives' THEN 'assets/images/country-market/unawatuna-beach.jpg'
        WHEN 'switzerland' THEN 'assets/images/country-market/nuwara-eliya-tea.jpg'
        WHEN 'malaysia' THEN 'assets/images/country-market/nine-arch-bridge.jpg'
        WHEN 'spain' THEN 'assets/images/country-market/matara-beach.jpg'
        WHEN 'israel' THEN 'assets/images/country-market/minneriya-elephant.jpg'
        WHEN 'denmark' THEN 'assets/images/country-market/mirissa-coastline.jpg'
        ELSE candidate_asset_path
    END,
    candidate_source_url = CASE country_slug
        WHEN 'united-kingdom' THEN 'https://unsplash.com/photos/lush-green-tea-plantation-on-a-misty-hillside-ZWdrM9IBBIE'
        WHEN 'russian-federation' THEN 'https://unsplash.com/photos/tropical-coastline-with-lush-trees-and-ocean-waves-iscpI9MWDxg'
        WHEN 'china' THEN 'https://unsplash.com/photos/nine-arch-bridge-surrounded-by-lush-green-forest-w5jDXdaRvO0'
        WHEN 'germany' THEN 'https://unsplash.com/photos/landscape-photo-of-island-iz8MNOJWW1c'
        WHEN 'france' THEN 'https://unsplash.com/photos/a-young-elephant-walks-beside-a-vehicle-in-tall-grass-GFM00I6Wkok'
        WHEN 'australia' THEN 'https://unsplash.com/photos/a-beach-with-palm-trees-and-the-ocean-yTOLWxLFYP4'
        WHEN 'united-states' THEN 'https://unsplash.com/photos/tropical-coastline-with-lush-trees-and-ocean-waves-iscpI9MWDxg'
        WHEN 'poland' THEN 'https://unsplash.com/photos/nine-arch-bridge-surrounded-by-lush-green-forest-w5jDXdaRvO0'
        WHEN 'netherlands' THEN 'https://unsplash.com/photos/lush-green-tea-plantation-on-a-misty-hillside-ZWdrM9IBBIE'
        WHEN 'canada' THEN 'https://unsplash.com/photos/landscape-photo-of-island-iz8MNOJWW1c'
        WHEN 'bangladesh' THEN 'https://unsplash.com/photos/a-young-elephant-walks-beside-a-vehicle-in-tall-grass-GFM00I6Wkok'
        WHEN 'japan' THEN 'https://unsplash.com/photos/a-beach-with-palm-trees-and-the-ocean-yTOLWxLFYP4'
        WHEN 'italy' THEN 'https://unsplash.com/photos/tropical-coastline-with-lush-trees-and-ocean-waves-iscpI9MWDxg'
        WHEN 'maldives' THEN 'https://unsplash.com/photos/landscape-photo-of-island-iz8MNOJWW1c'
        WHEN 'switzerland' THEN 'https://unsplash.com/photos/lush-green-tea-plantation-on-a-misty-hillside-ZWdrM9IBBIE'
        WHEN 'malaysia' THEN 'https://unsplash.com/photos/nine-arch-bridge-surrounded-by-lush-green-forest-w5jDXdaRvO0'
        WHEN 'spain' THEN 'https://unsplash.com/photos/a-beach-with-palm-trees-and-the-ocean-yTOLWxLFYP4'
        WHEN 'israel' THEN 'https://unsplash.com/photos/a-young-elephant-walks-beside-a-vehicle-in-tall-grass-GFM00I6Wkok'
        WHEN 'denmark' THEN 'https://unsplash.com/photos/tropical-coastline-with-lush-trees-and-ocean-waves-iscpI9MWDxg'
        ELSE candidate_source_url
    END,
    review_date = '2026-10-10',
    notes = 'Rights verified and asset imported locally. Do not publish until the United Kingdom pilot is approved and the media publish migration is reviewed.'
WHERE country_slug <> 'india';

UPDATE country_market_media_assignments
SET
    candidate_credit = CASE candidate_asset_path
        WHEN 'assets/images/country-market/nuwara-eliya-tea.jpg' THEN 'Erik Esly / Unsplash'
        WHEN 'assets/images/country-market/mirissa-coastline.jpg' THEN 'Zoshua Colah / Unsplash'
        WHEN 'assets/images/country-market/nine-arch-bridge.jpg' THEN 'Robin Grevers / Unsplash'
        WHEN 'assets/images/country-market/unawatuna-beach.jpg' THEN 'Rowan Heuvel / Unsplash'
        WHEN 'assets/images/country-market/minneriya-elephant.jpg' THEN 'Alehandra / Unsplash'
        WHEN 'assets/images/country-market/matara-beach.jpg' THEN 'Dulana Kodithuwakku / Unsplash'
        ELSE candidate_credit
    END,
    updated_at = CURRENT_TIMESTAMP
WHERE country_slug <> 'india';

UPDATE country_market_audits
SET
    blocked_reason = 'Editorial pilot approval and media publish migration still pending; rights-cleared candidates are staged.',
    updated_at = CURRENT_TIMESTAMP
WHERE country_slug <> 'india';
