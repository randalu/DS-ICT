-- COUNTRY-01: country-market inventory and editorial gates
--
-- This migration records the audit and approval workflow requested for the
-- country-market pages. It intentionally does not rewrite country_pages,
-- replace media, or change publication state. Those actions require reviewed
-- copy, rights-cleared assets, and approval of the United Kingdom pilot.

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS country_market_audits (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    workstream VARCHAR(40) NOT NULL DEFAULT 'COUNTRY-01',
    country_slug VARCHAR(180) NOT NULL,
    country_name VARCHAR(120) NOT NULL,
    page_path VARCHAR(255) NOT NULL,
    http_status SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    status_source VARCHAR(80) NOT NULL DEFAULT 'not_checked',
    publication_status VARCHAR(40) NOT NULL DEFAULT 'unknown',
    media_gate VARCHAR(40) NOT NULL DEFAULT 'pending',
    hero_image VARCHAR(255) NULL,
    hero_media_status VARCHAR(40) NOT NULL DEFAULT 'pending',
    og_image VARCHAR(255) NULL,
    og_media_status VARCHAR(40) NOT NULL DEFAULT 'pending',
    metadata_status VARCHAR(40) NOT NULL DEFAULT 'pending',
    metadata_notes TEXT NULL,
    factual_claims_status VARCHAR(40) NOT NULL DEFAULT 'needs_verification',
    factual_claims_notes TEXT NULL,
    internal_links_status VARCHAR(40) NOT NULL DEFAULT 'pending',
    internal_links_notes TEXT NULL,
    cta_status VARCHAR(40) NOT NULL DEFAULT 'pending',
    cta_notes TEXT NULL,
    editorial_status VARCHAR(60) NOT NULL DEFAULT 'not_started',
    blocked_reason TEXT NULL,
    audit_owner VARCHAR(120) NOT NULL DEFAULT 'Editorial lead',
    audit_review_date DATE NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_country_market_audits_slug (country_slug),
    KEY idx_country_market_audits_gate (media_gate),
    KEY idx_country_market_audits_editorial (editorial_status),
    KEY idx_country_market_audits_review_date (audit_review_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS country_market_sources (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    workstream VARCHAR(40) NOT NULL DEFAULT 'COUNTRY-01',
    source_key VARCHAR(100) NOT NULL,
    topic VARCHAR(60) NOT NULL,
    source_name VARCHAR(255) NOT NULL,
    authority VARCHAR(255) NOT NULL,
    source_url VARCHAR(768) NOT NULL,
    owner VARCHAR(120) NOT NULL,
    verified_on DATE NULL,
    review_date DATE NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'active',
    notes TEXT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_country_market_sources_key (source_key),
    KEY idx_country_market_sources_topic (topic),
    KEY idx_country_market_sources_review_date (review_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS country_market_editorial_guides (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    workstream VARCHAR(40) NOT NULL DEFAULT 'COUNTRY-01',
    guide_key VARCHAR(100) NOT NULL,
    version VARCHAR(30) NOT NULL,
    title VARCHAR(255) NOT NULL,
    status VARCHAR(40) NOT NULL DEFAULT 'draft',
    content LONGTEXT NOT NULL,
    owner VARCHAR(120) NOT NULL,
    review_date DATE NULL,
    pilot_country_slug VARCHAR(180) NULL,
    approved_at DATETIME NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_country_market_guides_key (guide_key),
    KEY idx_country_market_guides_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS country_market_editorial_drafts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    workstream VARCHAR(40) NOT NULL DEFAULT 'COUNTRY-01',
    country_slug VARCHAR(180) NOT NULL,
    country_name VARCHAR(120) NOT NULL,
    guide_key VARCHAR(100) NOT NULL,
    version VARCHAR(30) NOT NULL,
    status VARCHAR(40) NOT NULL DEFAULT 'draft_pending_approval',
    meta_title VARCHAR(255) NULL,
    meta_description VARCHAR(320) NULL,
    excerpt VARCHAR(500) NULL,
    intro LONGTEXT NULL,
    why_sri_lanka LONGTEXT NULL,
    recommended_itinerary LONGTEXT NULL,
    travel_info LONGTEXT NULL,
    best_time LONGTEXT NULL,
    faq_json LONGTEXT NULL,
    cta_title VARCHAR(255) NULL,
    cta_text TEXT NULL,
    source_keys_json LONGTEXT NULL,
    owner VARCHAR(120) NOT NULL,
    review_date DATE NULL,
    approved_by VARCHAR(120) NULL,
    approved_at DATETIME NULL,
    reviewer_notes TEXT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_country_market_drafts_slug (country_slug),
    KEY idx_country_market_drafts_status (status),
    KEY idx_country_market_drafts_review_date (review_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS country_market_media_assignments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    workstream VARCHAR(40) NOT NULL DEFAULT 'COUNTRY-01',
    country_slug VARCHAR(180) NOT NULL,
    country_name VARCHAR(120) NOT NULL,
    current_hero_path VARCHAR(255) NULL,
    current_og_path VARCHAR(255) NULL,
    replacement_status VARCHAR(40) NOT NULL DEFAULT 'not_started',
    rights_status VARCHAR(40) NOT NULL DEFAULT 'not_started',
    candidate_asset_path VARCHAR(255) NULL,
    candidate_license VARCHAR(255) NULL,
    candidate_source_url VARCHAR(768) NULL,
    candidate_credit VARCHAR(255) NULL,
    owner VARCHAR(120) NOT NULL,
    review_date DATE NULL,
    notes TEXT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_country_market_media_assignments_slug (country_slug),
    KEY idx_country_market_media_assignments_replacement (replacement_status),
    KEY idx_country_market_media_assignments_rights (rights_status),
    KEY idx_country_market_media_assignments_review_date (review_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS country_market_media_rights (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    workstream VARCHAR(40) NOT NULL DEFAULT 'COUNTRY-01',
    asset_path VARCHAR(255) NOT NULL,
    asset_role VARCHAR(40) NOT NULL DEFAULT 'candidate',
    rights_status VARCHAR(40) NOT NULL DEFAULT 'unverified',
    source_type VARCHAR(80) NULL,
    source_url VARCHAR(768) NULL,
    license VARCHAR(255) NULL,
    credit VARCHAR(255) NULL,
    proof_reference VARCHAR(768) NULL,
    owner VARCHAR(120) NOT NULL,
    review_date DATE NULL,
    notes TEXT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_country_market_media_rights_asset (asset_path),
    KEY idx_country_market_media_rights_status (rights_status),
    KEY idx_country_market_media_rights_review_date (review_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO country_market_audits (
    workstream, country_slug, country_name, page_path, http_status, status_source,
    publication_status, media_gate, hero_image, hero_media_status, og_image,
    og_media_status, metadata_status, metadata_notes, factual_claims_status,
    factual_claims_notes, internal_links_status, internal_links_notes, cta_status,
    cta_notes, editorial_status, blocked_reason, audit_owner, audit_review_date
) VALUES
('COUNTRY-01','india','India','/from/india',200,'local-http-smoke','published','pass','countries/20260415161048_4ff65ef0d7122ca6.jpg','pass','countries/20260415161048_67fdaf1d4a71fc0e.jpg','pass','generic','Title and description are present; review for country-specific usefulness and evidence before rewriting.','needs_verification','Review visa, flight, fees, health, safety, currency, and payment claims against the source register before copy changes.','generic','The route currently renders a generic recommended itinerary and the same top-destination set; country-specific links are not yet complete.','present','Contact and WhatsApp CTAs render; country-specific CTA wording and claims still need editorial review.','media_pass',NULL,'Editorial lead','2026-10-10'),
('COUNTRY-01','united-kingdom','United Kingdom','/from/united-kingdom',200,'local-http-smoke','published','blocked','assets/images/placeholders/placeholder-country.svg','blocked','assets/images/og-default.jpg','blocked','generic','Title and description are present but templated; canonical_url is null and the runtime fallback is used.','needs_verification','Existing copy includes unsupported or time-sensitive claims about visa fees, flight times, weather, health, safety, cash, and payment; rewrite only after source review.','generic','The route currently renders a generic recommended itinerary and the same top-destination set; country-specific links are not yet complete.','present','Contact and WhatsApp CTAs render; country-specific CTA wording and claims still need editorial review.','pilot_pending_approval','Placeholder hero and default OG image block trust review until rights-cleared Sri Lanka travel photography is supplied.','Editorial lead','2026-10-10'),
('COUNTRY-01','russian-federation','Russian Federation','/from/russian-federation',200,'local-http-smoke','published','blocked','assets/images/placeholders/placeholder-country.svg','blocked','assets/images/og-default.jpg','blocked','generic','Title and description are present but templated; review country naming and metadata usefulness.','needs_verification','Review all factual and audience claims against the source register before copy changes.','generic','The route currently renders generic itinerary and destination links; country-specific links are not yet complete.','present','Contact and WhatsApp CTAs render; country-specific CTA wording and claims still need editorial review.','blocked_by_media_gate','Placeholder hero and default OG image require rights-cleared replacement before copy, SEO, and link completion.','Editorial lead','2026-10-10'),
('COUNTRY-01','china','China','/from/china',200,'local-http-smoke','published','blocked','assets/images/placeholders/placeholder-country.svg','blocked','assets/images/og-default.jpg','blocked','generic','Title and description are present but templated; review country naming and metadata usefulness.','needs_verification','Review all factual and audience claims against the source register before copy changes.','generic','The route currently renders generic itinerary and destination links; country-specific links are not yet complete.','present','Contact and WhatsApp CTAs render; country-specific CTA wording and claims still need editorial review.','blocked_by_media_gate','Placeholder hero and default OG image require rights-cleared replacement before copy, SEO, and link completion.','Editorial lead','2026-10-10'),
('COUNTRY-01','germany','Germany','/from/germany',200,'local-http-smoke','published','blocked','assets/images/placeholders/placeholder-country.svg','blocked','assets/images/og-default.jpg','blocked','generic','Title and description are present but templated; review country naming and metadata usefulness.','needs_verification','Review all factual and audience claims against the source register before copy changes.','generic','The route currently renders generic itinerary and destination links; country-specific links are not yet complete.','present','Contact and WhatsApp CTAs render; country-specific CTA wording and claims still need editorial review.','blocked_by_media_gate','Placeholder hero and default OG image require rights-cleared replacement before copy, SEO, and link completion.','Editorial lead','2026-10-10'),
('COUNTRY-01','france','France','/from/france',200,'local-http-smoke','published','blocked','assets/images/placeholders/placeholder-country.svg','blocked','assets/images/og-default.jpg','blocked','generic','Title and description are present but templated; review country naming and metadata usefulness.','needs_verification','Review all factual and audience claims against the source register before copy changes.','generic','The route currently renders generic itinerary and destination links; country-specific links are not yet complete.','present','Contact and WhatsApp CTAs render; country-specific CTA wording and claims still need editorial review.','blocked_by_media_gate','Placeholder hero and default OG image require rights-cleared replacement before copy, SEO, and link completion.','Editorial lead','2026-10-10'),
('COUNTRY-01','australia','Australia','/from/australia',200,'local-http-smoke','published','blocked','assets/images/placeholders/placeholder-country.svg','blocked','assets/images/og-default.jpg','blocked','generic','Title and description are present but templated; review country naming and metadata usefulness.','needs_verification','Review all factual and audience claims against the source register before copy changes.','generic','The route currently renders generic itinerary and destination links; country-specific links are not yet complete.','present','Contact and WhatsApp CTAs render; country-specific CTA wording and claims still need editorial review.','blocked_by_media_gate','Placeholder hero and default OG image require rights-cleared replacement before copy, SEO, and link completion.','Editorial lead','2026-10-10'),
('COUNTRY-01','united-states','United States','/from/united-states',200,'local-http-smoke','published','blocked','assets/images/placeholders/placeholder-country.svg','blocked','assets/images/og-default.jpg','blocked','generic','Title and description are present but templated; review country naming and metadata usefulness.','needs_verification','Review all factual and audience claims against the source register before copy changes.','generic','The route currently renders generic itinerary and destination links; country-specific links are not yet complete.','present','Contact and WhatsApp CTAs render; country-specific CTA wording and claims still need editorial review.','blocked_by_media_gate','Placeholder hero and default OG image require rights-cleared replacement before copy, SEO, and link completion.','Editorial lead','2026-10-10'),
('COUNTRY-01','poland','Poland','/from/poland',200,'local-http-smoke','published','blocked','assets/images/placeholders/placeholder-country.svg','blocked','assets/images/og-default.jpg','blocked','generic','Title and description are present but templated; review country naming and metadata usefulness.','needs_verification','Review all factual and audience claims against the source register before copy changes.','generic','The route currently renders generic itinerary and destination links; country-specific links are not yet complete.','present','Contact and WhatsApp CTAs render; country-specific CTA wording and claims still need editorial review.','blocked_by_media_gate','Placeholder hero and default OG image require rights-cleared replacement before copy, SEO, and link completion.','Editorial lead','2026-10-10'),
('COUNTRY-01','netherlands','Netherlands','/from/netherlands',200,'local-http-smoke','published','blocked','assets/images/placeholders/placeholder-country.svg','blocked','assets/images/og-default.jpg','blocked','generic','Title and description are present but templated; review country naming and metadata usefulness.','needs_verification','Review all factual and audience claims against the source register before copy changes.','generic','The route currently renders generic itinerary and destination links; country-specific links are not yet complete.','present','Contact and WhatsApp CTAs render; country-specific CTA wording and claims still need editorial review.','blocked_by_media_gate','Placeholder hero and default OG image require rights-cleared replacement before copy, SEO, and link completion.','Editorial lead','2026-10-10'),
('COUNTRY-01','canada','Canada','/from/canada',200,'local-http-smoke','published','blocked','assets/images/placeholders/placeholder-country.svg','blocked','assets/images/og-default.jpg','blocked','generic','Title and description are present but templated; review country naming and metadata usefulness.','needs_verification','Review all factual and audience claims against the source register before copy changes.','generic','The route currently renders generic itinerary and destination links; country-specific links are not yet complete.','present','Contact and WhatsApp CTAs render; country-specific CTA wording and claims still need editorial review.','blocked_by_media_gate','Placeholder hero and default OG image require rights-cleared replacement before copy, SEO, and link completion.','Editorial lead','2026-10-10'),
('COUNTRY-01','bangladesh','Bangladesh','/from/bangladesh',200,'local-http-smoke','published','blocked','assets/images/placeholders/placeholder-country.svg','blocked','assets/images/og-default.jpg','blocked','generic','Title and description are present but templated; review country naming and metadata usefulness.','needs_verification','Review all factual and audience claims against the source register before copy changes.','generic','The route currently renders generic itinerary and destination links; country-specific links are not yet complete.','present','Contact and WhatsApp CTAs render; country-specific CTA wording and claims still need editorial review.','blocked_by_media_gate','Placeholder hero and default OG image require rights-cleared replacement before copy, SEO, and link completion.','Editorial lead','2026-10-10'),
('COUNTRY-01','japan','Japan','/from/japan',200,'local-http-smoke','published','blocked','assets/images/placeholders/placeholder-country.svg','blocked','assets/images/og-default.jpg','blocked','generic','Title and description are present but templated; review country naming and metadata usefulness.','needs_verification','Review all factual and audience claims against the source register before copy changes.','generic','The route currently renders generic itinerary and destination links; country-specific links are not yet complete.','present','Contact and WhatsApp CTAs render; country-specific CTA wording and claims still need editorial review.','blocked_by_media_gate','Placeholder hero and default OG image require rights-cleared replacement before copy, SEO, and link completion.','Editorial lead','2026-10-10'),
('COUNTRY-01','italy','Italy','/from/italy',200,'local-http-smoke','published','blocked','assets/images/placeholders/placeholder-country.svg','blocked','assets/images/og-default.jpg','blocked','generic','Title and description are present but templated; review country naming and metadata usefulness.','needs_verification','Review all factual and audience claims against the source register before copy changes.','generic','The route currently renders generic itinerary and destination links; country-specific links are not yet complete.','present','Contact and WhatsApp CTAs render; country-specific CTA wording and claims still need editorial review.','blocked_by_media_gate','Placeholder hero and default OG image require rights-cleared replacement before copy, SEO, and link completion.','Editorial lead','2026-10-10'),
('COUNTRY-01','maldives','Maldives','/from/maldives',200,'local-http-smoke','published','blocked','assets/images/placeholders/placeholder-country.svg','blocked','assets/images/og-default.jpg','blocked','generic','Title and description are present but templated; review country naming and metadata usefulness.','needs_verification','Review all factual and audience claims against the source register before copy changes.','generic','The route currently renders generic itinerary and destination links; country-specific links are not yet complete.','present','Contact and WhatsApp CTAs render; country-specific CTA wording and claims still need editorial review.','blocked_by_media_gate','Placeholder hero and default OG image require rights-cleared replacement before copy, SEO, and link completion.','Editorial lead','2026-10-10'),
('COUNTRY-01','switzerland','Switzerland','/from/switzerland',200,'local-http-smoke','published','blocked','assets/images/placeholders/placeholder-country.svg','blocked','assets/images/og-default.jpg','blocked','generic','Title and description are present but templated; review country naming and metadata usefulness.','needs_verification','Review all factual and audience claims against the source register before copy changes.','generic','The route currently renders generic itinerary and destination links; country-specific links are not yet complete.','present','Contact and WhatsApp CTAs render; country-specific CTA wording and claims still need editorial review.','blocked_by_media_gate','Placeholder hero and default OG image require rights-cleared replacement before copy, SEO, and link completion.','Editorial lead','2026-10-10'),
('COUNTRY-01','malaysia','Malaysia','/from/malaysia',200,'local-http-smoke','published','blocked','assets/images/placeholders/placeholder-country.svg','blocked','assets/images/og-default.jpg','blocked','generic','Title and description are present but templated; review country naming and metadata usefulness.','needs_verification','Review all factual and audience claims against the source register before copy changes.','generic','The route currently renders generic itinerary and destination links; country-specific links are not yet complete.','present','Contact and WhatsApp CTAs render; country-specific CTA wording and claims still need editorial review.','blocked_by_media_gate','Placeholder hero and default OG image require rights-cleared replacement before copy, SEO, and link completion.','Editorial lead','2026-10-10'),
('COUNTRY-01','spain','Spain','/from/spain',200,'local-http-smoke','published','blocked','assets/images/placeholders/placeholder-country.svg','blocked','assets/images/og-default.jpg','blocked','generic','Title and description are present but templated; review country naming and metadata usefulness.','needs_verification','Review all factual and audience claims against the source register before copy changes.','generic','The route currently renders generic itinerary and destination links; country-specific links are not yet complete.','present','Contact and WhatsApp CTAs render; country-specific CTA wording and claims still need editorial review.','blocked_by_media_gate','Placeholder hero and default OG image require rights-cleared replacement before copy, SEO, and link completion.','Editorial lead','2026-10-10'),
('COUNTRY-01','israel','Israel','/from/israel',200,'local-http-smoke','published','blocked','assets/images/placeholders/placeholder-country.svg','blocked','assets/images/og-default.jpg','blocked','generic','Title and description are present but templated; review country naming and metadata usefulness.','needs_verification','Review all factual and audience claims against the source register before copy changes.','generic','The route currently renders generic itinerary and destination links; country-specific links are not yet complete.','present','Contact and WhatsApp CTAs render; country-specific CTA wording and claims still need editorial review.','blocked_by_media_gate','Placeholder hero and default OG image require rights-cleared replacement before copy, SEO, and link completion.','Editorial lead','2026-10-10'),
('COUNTRY-01','denmark','Denmark','/from/denmark',200,'local-http-smoke','published','blocked','assets/images/placeholders/placeholder-country.svg','blocked','assets/images/og-default.jpg','blocked','generic','Title and description are present but templated; review country naming and metadata usefulness.','needs_verification','Review all factual and audience claims against the source register before copy changes.','generic','The route currently renders generic itinerary and destination links; country-specific links are not yet complete.','present','Contact and WhatsApp CTAs render; country-specific CTA wording and claims still need editorial review.','blocked_by_media_gate','Placeholder hero and default OG image require rights-cleared replacement before copy, SEO, and link completion.','Editorial lead','2026-10-10')
ON DUPLICATE KEY UPDATE
    country_name = VALUES(country_name), page_path = VALUES(page_path), http_status = VALUES(http_status),
    status_source = VALUES(status_source), publication_status = VALUES(publication_status), media_gate = VALUES(media_gate),
    hero_image = VALUES(hero_image), hero_media_status = VALUES(hero_media_status), og_image = VALUES(og_image),
    og_media_status = VALUES(og_media_status), metadata_status = VALUES(metadata_status), metadata_notes = VALUES(metadata_notes),
    factual_claims_status = VALUES(factual_claims_status), factual_claims_notes = VALUES(factual_claims_notes),
    internal_links_status = VALUES(internal_links_status), internal_links_notes = VALUES(internal_links_notes),
    cta_status = VALUES(cta_status), cta_notes = VALUES(cta_notes), editorial_status = VALUES(editorial_status),
    blocked_reason = VALUES(blocked_reason), audit_owner = VALUES(audit_owner), audit_review_date = VALUES(audit_review_date);

INSERT INTO country_market_sources (
    source_key, topic, source_name, authority, source_url, owner, verified_on, review_date, status, notes
) VALUES
('sri-lanka-eta-rules','visa','Sri Lanka ETA official application and terms','Department of Immigration & Emigration, Sri Lanka','https://eta.gov.lk/slvisa/etaslvisa/etaNavServ','Editorial lead','2026-09-10','2026-10-10','active','Use for entry requirements, ETA validity, required documents, and current eligibility. Recheck before publishing any nationality-specific guidance.'),
('sri-lanka-eta-fees','fees','Sri Lanka ETA official fee table','Department of Immigration & Emigration, Sri Lanka','https://www.eta.gov.lk/slvisa/visainfo/fees.jsp?locale=en_US','Editorial lead','2026-09-10','2026-10-10','active','Use for current visa and ETA fees. Do not hard-code a fee without an as-of date and nationality check.'),
('sri-lanka-immigration-fees','fees','Sri Lanka Immigration visa fees and extensions','Department of Immigration & Emigration, Sri Lanka','https://www.immigration.gov.lk/pages_e.php?id=45','Editorial lead','2026-09-10','2026-10-10','active','Use to cross-check visa extension and fee information against the ETA portal.'),
('bandaranaike-flight-schedule','flights','Bandaranaike International Airport live flight information','Airport & Aviation Services (Sri Lanka) (Private) Limited','https://www.airport.lk/flight_info/departure','Editorial lead','2026-09-10','2026-09-17','active','Use for current arrival/departure availability only. Schedules change; do not present a route or flight time as fixed.'),
('srilankan-airlines-timetable','flights','SriLankan Airlines flight timetable','SriLankan Airlines','https://www.srilankan.com/en_uk/plan-and-book/timetable','Editorial lead','2026-09-10','2026-09-17','active','Use as an airline-specific cross-check. Confirm every route with the airline before publication.'),
('cdc-sri-lanka-health','health','Sri Lanka traveller health guidance','U.S. Centers for Disease Control and Prevention','https://wwwnc.cdc.gov/travel/destinations/traveler/none/sri-lanka','Health editor','2026-09-10','2026-12-10','active','Use for health notices, vaccines, mosquito-bite prevention, food and water, and medical planning. Include a clinician/official-health disclaimer where appropriate.'),
('nathnac-sri-lanka-health','health','Sri Lanka country health advice','UK Health Security Agency / NaTHNaC','https://travelhealthpro.org.uk/country/204/sri-lanka','Health editor','2026-09-10','2026-12-10','active','Use for UK-traveller health planning, yellow-fever certificate conditions, mosquito-borne illness, and clinician signposting.'),
('fcdo-sri-lanka-safety','safety','Sri Lanka safety and security advice','UK Foreign, Commonwealth & Development Office','https://www.gov.uk/foreign-travel-advice/sri-lanka/safety-and-security','Editorial lead','2026-09-10','2026-10-10','active','Use for current safety, crime, transport, protest, and card-fraud guidance. Do not turn advisory language into a safety guarantee.'),
('fcdo-sri-lanka-entry-money','payment','Sri Lanka entry, ATM, and payment guidance','UK Foreign, Commonwealth & Development Office','https://www.gov.uk/foreign-travel-advice/sri-lanka/entry-requirements','Editorial lead','2026-09-10','2026-10-10','active','Use for ATM/card-acceptance cautions and entry requirements. State that availability varies by location and provider.'),
('cbsl-exchange-rates','currency','Sri Lanka Central Bank exchange rates','Central Bank of Sri Lanka','https://www.cbsl.gov.lk/en/rates-and-indicators/exchange-rates','Finance editor','2026-09-10','2026-09-17','active','Use for the current Sri Lankan Rupee reference rate. Rates are indicative and change; never promise a fixed conversion.'),
('cbsl-currency-faq','currency','Sri Lanka currency FAQ','Central Bank of Sri Lanka','https://www.cbsl.gov.lk/en/faq/currency','Finance editor','2026-09-10','2026-10-10','active','Use to identify the local currency and current note/coin information. Avoid unsupported claims about foreign-currency acceptance.')
ON DUPLICATE KEY UPDATE
    topic = VALUES(topic), source_name = VALUES(source_name), authority = VALUES(authority), source_url = VALUES(source_url),
    owner = VALUES(owner), verified_on = VALUES(verified_on), review_date = VALUES(review_date), status = VALUES(status), notes = VALUES(notes);

INSERT INTO country_market_editorial_guides (
    guide_key, version, title, status, content, owner, review_date, pilot_country_slug
) VALUES (
    'country-market-editorial-v1',
    '1.0-draft',
    'Country-market page editorial style guide',
    'draft_pending_uk_approval',
    'Purpose: make country-market pages useful, neutral, evidence-led planning pages for travelers considering Sri Lanka.\n\nNaming and grammar: use the official country name in headings and metadata, such as United Kingdom and United States. Use UK passport holders only when a visa or entry rule is specifically about passports. Avoid Russian Federation in consumer-facing copy when Russia is the correct country reference for the audience, but verify legal or official wording before changing it. Use consistent Sri Lanka, Sri Lankan, traveler, itinerary, and chauffeur-guided grammar.\n\nTone: remove stereotypes, nationality-based personality claims, slang, jokes about weather or culture, and comparisons that imply a group behaves in a particular way. Replace them with neutral planning advice about routes, seasons, transport, accessibility, interests, and trip length. The UK pilot must remove slang and stereotypes such as rubbish, tropical cousin, good genes, and Instagram guarantees.\n\nEvidence: verify visa rules, fees, flights, health, safety, currency, and payment guidance against the source register before publication. Add an as-of date for volatile information. Do not promise fixed flight times, weather, availability, safety, card acceptance, visa outcomes, or guaranteed wildlife sightings. Avoid absolute wording such as always, never, safest, perfect, zero chance, or no exceptions unless an authoritative source supports the exact statement.\n\nHealth and safety: provide practical signposting, not medical advice or safety guarantees. Link travelers to current official guidance and advise checking a clinician or relevant authority where appropriate.\n\nInternal linking and CTAs: add only verified country-relevant itinerary and destination links. Keep a clear contact CTA and WhatsApp CTA, but do not imply that a route, price, hotel, vehicle, visa outcome, or travel time is guaranteed.\n\nSEO and media: give each page a useful, non-duplicative title and meta description, preserve the /from/{slug} URL and canonical behavior, and use schema only for claims present on the page. Do not mark a page media-complete while hero or OG assets are placeholders/defaults. Use rights-cleared Sri Lanka travel photography with meaningful alt text and a recorded provenance/license note.\n\nWorkflow: inventory all 20 pages; keep the 19 placeholder-image pages blocked; complete and approve the UK pilot; then apply the guide to the remaining 19 pages; only after that complete country-specific links, schema/SEO checks, and desktop/mobile review.',
    'Editorial lead',
    '2026-10-10',
    'united-kingdom'
)
ON DUPLICATE KEY UPDATE
    version = VALUES(version), title = VALUES(title), status = VALUES(status), content = VALUES(content),
    owner = VALUES(owner), review_date = VALUES(review_date), pilot_country_slug = VALUES(pilot_country_slug);

INSERT INTO country_market_editorial_drafts (
    country_slug, country_name, guide_key, version, status, meta_title, meta_description,
    excerpt, intro, why_sri_lanka, recommended_itinerary, travel_info, best_time, faq_json,
    cta_title, cta_text, source_keys_json, owner, review_date, reviewer_notes
) VALUES (
    'united-kingdom',
    'United Kingdom',
    'country-market-editorial-v1',
    '1.0-pilot',
    'draft_pending_approval',
    'Sri Lanka Tours from the United Kingdom | Island Culture Travels',
    'Plan a private Sri Lanka journey from the United Kingdom with practical guidance on entry requirements, routes, seasons, and travel pace. Check current official guidance before booking.',
    'Plan a private Sri Lanka journey from the United Kingdom with practical guidance on entry requirements, routes, seasons, and travel pace.',
    'Travelers from the United Kingdom can plan Sri Lanka around the time available, the regions they want to visit, and the conditions they prefer. A route can combine heritage sites, hill country, wildlife, and a coast chosen for the travel month, but travel times between regions are longer than the map may suggest. Check current flight schedules and entry guidance before booking.',
    'Sri Lanka offers a varied route within one trip. A first visit might combine the Cultural Triangle, Kandy or the central hills, a wildlife area, and a coast chosen for the travel month. The right balance depends on your arrival time, interests, mobility, and how often you want to change accommodation.\n\nUse the route as a planning starting point rather than a fixed itinerary. Temple visits and other religious sites require respectful dress and behaviour; leave time for visits, rest, and changing road conditions.\n\nA chauffeur-guided itinerary can reduce the need to navigate unfamiliar roads and can be adjusted around your preferred pace. Confirm the provider, vehicle, inclusions, and cancellation terms before booking.',
    'For a trip of around 10 to 14 days, a practical starting point is a route that combines the Cultural Triangle, the central hills, one wildlife area, and a south, west, or east-coast stay selected for the travel month. The exact sequence should account for arrival time, road conditions, accommodation changes, and the activities you want to include.',
    'Flights and connections: schedules and routes change. Check current arrivals and departures at Bandaranaike International Airport and confirm the itinerary with the airline before booking. Allow time for connections and the transfer from the airport to the first stay.\n\nEntry requirements: UK passport holders should check the official Sri Lanka ETA portal and current UK travel advice before travel. At the 2026-09-10 source review, official guidance listed UK nationals among the countries eligible for a 30-day tourist ETA without an ETA fee, but eligibility, fees, and conditions can change.\n\nPassport: current UK travel advice says a full British citizen passport should be valid for at least six months after the day of arrival. Other passport types and dual-national situations should be checked with the relevant authority or travel provider.\n\nHealth: review the CDC and NaTHNaC Sri Lanka guidance and arrange a travel-health discussion with a qualified clinician. Mosquito-bite prevention may be relevant, and yellow-fever certificate rules can depend on where you have travelled or transited.\n\nMoney and payments: the local currency is the Sri Lankan rupee. ATMs and card acceptance vary by location and provider, so carry a suitable payment backup and check current advice. Do not rely on a fixed exchange rate or assume every small business accepts cards.',
    'Travel conditions vary by coast and month. Routes focused on the south and west are often considered from November to April, while an east-coast focus can be considered at other points in the year. Conditions vary, including during transition months, so check a current forecast and choose the coast that fits the travel month rather than treating one season as a guarantee.',
    '[{"q":"Do UK passport holders need an ETA for Sri Lanka?","a":"Check the official Sri Lanka ETA portal and current UK travel advice before booking. At the 2026-09-10 source review, the official guidance listed UK nationals among the countries eligible for a 30-day tourist ETA without an ETA fee, but requirements and fees can change."},{"q":"How should I check flights from the United Kingdom?","a":"Flight routes, schedules, and connection times change. Check the current Bandaranaike International Airport and airline schedules for your dates, then confirm the itinerary directly with the airline."},{"q":"What health planning should I do before Sri Lanka?","a":"Review the CDC and NaTHNaC Sri Lanka guidance and speak with a qualified clinician about your itinerary and medical history. Follow current advice on mosquito-bite prevention and any certificate requirements that apply to your route."},{"q":"Should I carry cash as well as cards?","a":"Sri Lanka uses the Sri Lankan rupee. ATM access and card acceptance vary by location and provider, so carry a practical backup and check current payment advice before travelling."}]',
    'Plan your Sri Lanka journey from the United Kingdom',
    'Share your approximate dates, trip length, preferred regions, and travel pace. We can suggest a route for review, then confirm the practical details before you book.',
    '["sri-lanka-eta-rules","sri-lanka-eta-fees","bandaranaike-flight-schedule","srilankan-airlines-timetable","cdc-sri-lanka-health","nathnac-sri-lanka-health","fcdo-sri-lanka-safety","fcdo-sri-lanka-entry-money","cbsl-exchange-rates","cbsl-currency-faq"]',
    'Editorial lead',
    '2026-10-10',
    'Draft only. Do not copy into country_pages until a named reviewer approves tone, factual claims, source links, country naming, and media readiness.'
)
ON DUPLICATE KEY UPDATE
    country_name = VALUES(country_name), guide_key = VALUES(guide_key), version = VALUES(version), status = VALUES(status),
    meta_title = VALUES(meta_title), meta_description = VALUES(meta_description), excerpt = VALUES(excerpt), intro = VALUES(intro),
    why_sri_lanka = VALUES(why_sri_lanka), recommended_itinerary = VALUES(recommended_itinerary), travel_info = VALUES(travel_info),
    best_time = VALUES(best_time), faq_json = VALUES(faq_json), cta_title = VALUES(cta_title), cta_text = VALUES(cta_text),
    source_keys_json = VALUES(source_keys_json), owner = VALUES(owner), review_date = VALUES(review_date), reviewer_notes = VALUES(reviewer_notes);

INSERT INTO country_market_media_assignments (
    country_slug, country_name, current_hero_path, current_og_path, replacement_status, rights_status,
    owner, review_date, notes
) VALUES
('india','India','countries/20260415161048_4ff65ef0d7122ca6.jpg','countries/20260415161048_67fdaf1d4a71fc0e.jpg','not_required_current_pass','not_in_scope_current_pass','Media editor','2026-10-10','India is the current media pass. Preserve it while keeping provenance improvements separate from the 19-page replacement gate.'),
('united-kingdom','United Kingdom','assets/images/placeholders/placeholder-country.svg','assets/images/og-default.jpg','required','not_started','Media editor','2026-10-10','Replace both placeholder/default assets only after a rights-cleared candidate and provenance record are approved.'),
('russian-federation','Russian Federation','assets/images/placeholders/placeholder-country.svg','assets/images/og-default.jpg','required','not_started','Media editor','2026-10-10','Replace both placeholder/default assets only after a rights-cleared candidate and provenance record are approved.'),
('china','China','assets/images/placeholders/placeholder-country.svg','assets/images/og-default.jpg','required','not_started','Media editor','2026-10-10','Replace both placeholder/default assets only after a rights-cleared candidate and provenance record are approved.'),
('germany','Germany','assets/images/placeholders/placeholder-country.svg','assets/images/og-default.jpg','required','not_started','Media editor','2026-10-10','Replace both placeholder/default assets only after a rights-cleared candidate and provenance record are approved.'),
('france','France','assets/images/placeholders/placeholder-country.svg','assets/images/og-default.jpg','required','not_started','Media editor','2026-10-10','Replace both placeholder/default assets only after a rights-cleared candidate and provenance record are approved.'),
('australia','Australia','assets/images/placeholders/placeholder-country.svg','assets/images/og-default.jpg','required','not_started','Media editor','2026-10-10','Replace both placeholder/default assets only after a rights-cleared candidate and provenance record are approved.'),
('united-states','United States','assets/images/placeholders/placeholder-country.svg','assets/images/og-default.jpg','required','not_started','Media editor','2026-10-10','Replace both placeholder/default assets only after a rights-cleared candidate and provenance record are approved.'),
('poland','Poland','assets/images/placeholders/placeholder-country.svg','assets/images/og-default.jpg','required','not_started','Media editor','2026-10-10','Replace both placeholder/default assets only after a rights-cleared candidate and provenance record are approved.'),
('netherlands','Netherlands','assets/images/placeholders/placeholder-country.svg','assets/images/og-default.jpg','required','not_started','Media editor','2026-10-10','Replace both placeholder/default assets only after a rights-cleared candidate and provenance record are approved.'),
('canada','Canada','assets/images/placeholders/placeholder-country.svg','assets/images/og-default.jpg','required','not_started','Media editor','2026-10-10','Replace both placeholder/default assets only after a rights-cleared candidate and provenance record are approved.'),
('bangladesh','Bangladesh','assets/images/placeholders/placeholder-country.svg','assets/images/og-default.jpg','required','not_started','Media editor','2026-10-10','Replace both placeholder/default assets only after a rights-cleared candidate and provenance record are approved.'),
('japan','Japan','assets/images/placeholders/placeholder-country.svg','assets/images/og-default.jpg','required','not_started','Media editor','2026-10-10','Replace both placeholder/default assets only after a rights-cleared candidate and provenance record are approved.'),
('italy','Italy','assets/images/placeholders/placeholder-country.svg','assets/images/og-default.jpg','required','not_started','Media editor','2026-10-10','Replace both placeholder/default assets only after a rights-cleared candidate and provenance record are approved.'),
('maldives','Maldives','assets/images/placeholders/placeholder-country.svg','assets/images/og-default.jpg','required','not_started','Media editor','2026-10-10','Replace both placeholder/default assets only after a rights-cleared candidate and provenance record are approved.'),
('switzerland','Switzerland','assets/images/placeholders/placeholder-country.svg','assets/images/og-default.jpg','required','not_started','Media editor','2026-10-10','Replace both placeholder/default assets only after a rights-cleared candidate and provenance record are approved.'),
('malaysia','Malaysia','assets/images/placeholders/placeholder-country.svg','assets/images/og-default.jpg','required','not_started','Media editor','2026-10-10','Replace both placeholder/default assets only after a rights-cleared candidate and provenance record are approved.'),
('spain','Spain','assets/images/placeholders/placeholder-country.svg','assets/images/og-default.jpg','required','not_started','Media editor','2026-10-10','Replace both placeholder/default assets only after a rights-cleared candidate and provenance record are approved.'),
('israel','Israel','assets/images/placeholders/placeholder-country.svg','assets/images/og-default.jpg','required','not_started','Media editor','2026-10-10','Replace both placeholder/default assets only after a rights-cleared candidate and provenance record are approved.'),
('denmark','Denmark','assets/images/placeholders/placeholder-country.svg','assets/images/og-default.jpg','required','not_started','Media editor','2026-10-10','Replace both placeholder/default assets only after a rights-cleared candidate and provenance record are approved.')
ON DUPLICATE KEY UPDATE
    country_name = VALUES(country_name), current_hero_path = VALUES(current_hero_path), current_og_path = VALUES(current_og_path),
    replacement_status = VALUES(replacement_status), rights_status = VALUES(rights_status), owner = VALUES(owner),
    review_date = VALUES(review_date), notes = VALUES(notes);

INSERT INTO country_market_media_rights (
    asset_path, asset_role, rights_status, owner, review_date, notes
) VALUES
('media/20260427001026_f3089bbe7294.jpg','candidate','unverified','Media editor','2026-10-10','Existing Adam''s Peak candidate has meaningful alt text but media_assets.source_type and media_assets.source_url are blank. Do not assign until provenance and license proof are recorded.'),
('media/20260427001026_baa3fbe2f161.jpg','candidate','unverified','Media editor','2026-10-10','Existing Galle Lighthouse candidate has meaningful alt text but media_assets.source_type and media_assets.source_url are blank. Do not assign until provenance and license proof are recorded.'),
('media/20260427001026_b6730a050359.jpg','candidate','unverified','Media editor','2026-10-10','Existing Kandy Temple candidate has meaningful alt text but media_assets.source_type and media_assets.source_url are blank. Do not assign until provenance and license proof are recorded.'),
('media/20260427001026_6eb193038144.jpg','candidate','unverified','Media editor','2026-10-10','Existing Mirissa candidate has meaningful alt text but media_assets.source_type and media_assets.source_url are blank. Do not assign until provenance and license proof are recorded.'),
('media/20260427001026_93b1e9e7c8c8.jpg','candidate','unverified','Media editor','2026-10-10','Existing Nine Arch Bridge candidate has meaningful alt text but media_assets.source_type and media_assets.source_url are blank. Do not assign until provenance and license proof are recorded.'),
('media/20260427001026_9b7798e834d6.jpg','candidate','unverified','Media editor','2026-10-10','Existing Tissamaharama candidate has meaningful alt text but media_assets.source_type and media_assets.source_url are blank. Do not assign until provenance and license proof are recorded.')
ON DUPLICATE KEY UPDATE
    asset_role = VALUES(asset_role), rights_status = VALUES(rights_status), owner = VALUES(owner),
    review_date = VALUES(review_date), notes = VALUES(notes);
