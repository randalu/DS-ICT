-- COUNTRY-02: restore the India page's broken hero/OG media assignment.
-- The asset is uploaded separately to assets/uploads/india-galle-fort-wikimedia.jpg.

UPDATE country_pages
SET hero_image = 'india-galle-fort-wikimedia.jpg',
    og_image = 'india-galle-fort-wikimedia.jpg'
WHERE slug = 'india';

INSERT INTO country_market_media_rights (
    asset_path, asset_role, rights_status, source_type, source_url, license, credit,
    proof_reference, owner, review_date, notes
) VALUES (
    'india-galle-fort-wikimedia.jpg',
    'hero_and_og',
    'verified',
    'Wikimedia Commons photograph',
    'https://commons.wikimedia.org/wiki/File:Galle_Fort_lighthouse_Leuchtturm_(35113214702).jpg',
    'CC BY-SA 2.0',
    'FLASHPACKER TRAVELGUIDE',
    'Wikimedia Commons file page and license record',
    'Media editor',
    '2026-10-10',
    'Daylight Galle Fort coastal photograph for the India country-market page. Attribution and share-alike terms apply.'
)
ON DUPLICATE KEY UPDATE
    asset_role = VALUES(asset_role),
    rights_status = VALUES(rights_status),
    source_type = VALUES(source_type),
    source_url = VALUES(source_url),
    license = VALUES(license),
    credit = VALUES(credit),
    proof_reference = VALUES(proof_reference),
    owner = VALUES(owner),
    review_date = VALUES(review_date),
    notes = VALUES(notes);

UPDATE country_market_media_assignments
SET current_hero_path = 'india-galle-fort-wikimedia.jpg',
    current_og_path = 'india-galle-fort-wikimedia.jpg',
    replacement_status = 'remediated',
    rights_status = 'verified',
    candidate_asset_path = 'india-galle-fort-wikimedia.jpg',
    candidate_license = 'CC BY-SA 2.0',
    candidate_source_url = 'https://commons.wikimedia.org/wiki/File:Galle_Fort_lighthouse_Leuchtturm_(35113214702).jpg',
    candidate_credit = 'FLASHPACKER TRAVELGUIDE',
    owner = 'Media editor',
    review_date = '2026-10-10',
    notes = 'Remediated broken legacy India hero/OG references with a verified daylight Galle Fort photograph; attribution required.'
WHERE country_slug = 'india';

UPDATE country_market_audits
SET media_gate = 'pass',
    hero_image = 'india-galle-fort-wikimedia.jpg',
    hero_media_status = 'pass',
    og_image = 'india-galle-fort-wikimedia.jpg',
    og_media_status = 'pass',
    blocked_reason = NULL,
    audit_owner = 'Media editor',
    audit_review_date = '2026-10-10'
WHERE workstream = 'COUNTRY-01'
  AND country_slug = 'india';
