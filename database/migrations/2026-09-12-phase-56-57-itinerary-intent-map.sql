-- PHASE 56-57: duration inventory and informational/package intent separation.

CREATE TABLE IF NOT EXISTS seo_intent_map (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    target_path VARCHAR(255) NOT NULL,
    intent_role VARCHAR(60) NOT NULL,
    keyword_family VARCHAR(255) NOT NULL,
    canonical_target VARCHAR(255) NOT NULL,
    notes TEXT NULL,
    review_date DATE NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_seo_intent_map_path (target_path),
    KEY idx_seo_intent_map_role (intent_role),
    KEY idx_seo_intent_map_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO seo_intent_map
    (target_path, intent_role, keyword_family, canonical_target, notes, review_date, is_active)
VALUES
    ('/5-day-itinerary', 'informational_itinerary', '5 day Sri Lanka itinerary', '/5-day-itinerary', 'Planning route: explain time tradeoffs and keep package pricing on transactional tour pages.', '2026-09-12', 1),
    ('/7-day-itinerary', 'informational_itinerary', '7 day Sri Lanka itinerary', '/7-day-itinerary', 'Planning route: focused regional choices and realistic transfer pacing.', '2026-09-12', 1),
    ('/9-day-itinerary', 'informational_itinerary', '9 day Sri Lanka itinerary', '/9-day-itinerary', 'Planning route: wildlife or south-coast emphasis without duplicating package copy.', '2026-09-12', 1),
    ('/10-day-itinerary', 'informational_itinerary', '10 day Sri Lanka itinerary', '/10-day-itinerary', 'Flagship planning route: route logic, alternatives, timings, and practical travel decisions.', '2026-09-12', 1),
    ('/12-day-itinerary', 'informational_itinerary', '12 day Sri Lanka itinerary', '/12-day-itinerary', 'Planning route: deeper regional combinations and slower pacing.', '2026-09-12', 1),
    ('/14-day-itinerary', 'informational_itinerary', '14 day Sri Lanka itinerary', '/14-day-itinerary', 'Planning route: complete island balance with extra nights and seasonal alternatives.', '2026-09-12', 1),
    ('/itineraries/sri-lanka-itinerary-10-days', 'itinerary_detail', 'Sri Lanka 10 day itinerary route', '/itineraries/sri-lanka-itinerary-10-days', 'Existing itinerary detail supports route discovery; link to the informational duration guide instead of duplicating it.', '2026-09-12', 1),
    ('/tours/sri-lanka-10-day-itinerary-package', 'transactional_package', '10 day Sri Lanka private tour package', '/tours/sri-lanka-10-day-itinerary-package', 'Transactional package page: keep price basis, inclusions, vehicle, accommodation, and enquiry CTA here.', '2026-09-12', 1),
    ('/sri-lanka-private-tours', 'commercial_hub', 'Sri Lanka private tours', '/sri-lanka-private-tours', 'Commercial hub: link to duration guides and package intent without becoming a duplicate itinerary page.', '2026-09-12', 1)
ON DUPLICATE KEY UPDATE
    intent_role = VALUES(intent_role),
    keyword_family = VALUES(keyword_family),
    canonical_target = VALUES(canonical_target),
    notes = VALUES(notes),
    review_date = VALUES(review_date),
    is_active = VALUES(is_active);
