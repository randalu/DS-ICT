-- COUNTRY-01: apply the approved editorial standard and publish staged media
-- for the remaining 19 country-market pages. Country-specific itinerary links,
-- schema/SEO checks, and desktop/mobile review remain later gates.

SET NAMES utf8mb4;

INSERT INTO country_market_editorial_drafts (
    workstream, country_slug, country_name, guide_key, version, status,
    meta_title, meta_description, excerpt, intro, why_sri_lanka,
    recommended_itinerary, travel_info, best_time, faq_json, cta_title,
    cta_text, source_keys_json, owner, review_date, reviewer_notes
)
SELECT
    'COUNTRY-01',
    cp.slug,
    cp.country_name,
    'country-market-editorial-v1',
    '1.0',
    'standard_applied_review',
    CONCAT('Sri Lanka Tours from ', cp.country_name, ' | Island Culture Travels'),
    CONCAT('Plan a private Sri Lanka journey from ', cp.country_name, ' with practical guidance on entry checks, routes, travel seasons, and pace. Check current official guidance before booking.'),
    CONCAT('Plan a private Sri Lanka journey from ', cp.country_name, ' with practical guidance on entry checks, routes, travel seasons, and pace.'),
    CONCAT('Travelers from ', cp.country_name, ' can plan Sri Lanka around the time available, the regions they want to visit, and the conditions they prefer. A route can combine heritage sites, hill country, wildlife, and a coast chosen for the travel month, but travel times between regions are longer than the map may suggest. Check current flight schedules and entry guidance before booking.'),
    'Sri Lanka offers a varied route within one trip. A first visit might combine the Cultural Triangle, Kandy or the central hills, a wildlife area, and a coast chosen for the travel month. The right balance depends on arrival time, interests, mobility, and how often you want to change accommodation.

Use the route as a planning starting point rather than a fixed itinerary. Temple visits and other religious sites require respectful dress and behaviour; leave time for visits, rest, and changing road conditions.

A chauffeur-guided itinerary can reduce the need to navigate unfamiliar roads and can be adjusted around your preferred pace. Confirm the provider, vehicle, inclusions, and cancellation terms before booking.',
    'If you have around 10 to 14 days, a practical starting point is a route that combines the Cultural Triangle, the central hills, one wildlife area, and a south, west, or east-coast stay selected for the travel month. The exact sequence should account for arrival time, road conditions, accommodation changes, and the activities you want to include.',
    'Flights and connections: schedules and routes change. Check current arrivals and departures at Bandaranaike International Airport and confirm the itinerary with the airline before booking. Allow time for connections and the transfer from the airport to the first stay.

Entry requirements: check the official Sri Lanka ETA portal for whether your passport is eligible, current fees, required documents, and conditions before booking. Eligibility, fees, and conditions can change; an ETA does not by itself guarantee entry.

Passport: check that your travel document meets the current validity and condition requirements, and confirm any passport-specific rule with the relevant authority or travel provider.

Health: review the CDC and NaTHNaC Sri Lanka guidance and arrange a travel-health discussion with a qualified clinician. Mosquito-bite prevention may be relevant, and yellow-fever certificate rules can depend on where you have travelled or transited.

Money and payments: the local currency is the Sri Lankan rupee. ATMs and card acceptance vary by location and provider, so carry a suitable payment backup and check current advice. Do not rely on a fixed exchange rate or assume every small business accepts cards.',
    'Travel conditions vary by coast and month. Routes focused on the south and west are often considered from November to April, while an east-coast focus can be considered at other points of the year. Conditions vary, including during transition months, so check a current forecast and choose the coast that fits the travel month rather than treating one season as a guarantee.',
    '[{"q":"What should I check before booking a Sri Lanka trip?","a":"Check the official Sri Lanka ETA portal, current airline and airport schedules, travel-health guidance, safety advice, and payment conditions for your dates and passport."},{"q":"How should I choose a route?","a":"Start with the time available, regions of interest, travel month, mobility needs, and tolerance for changing accommodation. Treat a suggested route as a planning starting point rather than a fixed promise."},{"q":"What health planning should I do?","a":"Review the CDC and NaTHNaC Sri Lanka guidance and speak with a qualified clinician about your itinerary and medical history."},{"q":"Should I carry cash as well as cards?","a":"Sri Lanka uses the Sri Lankan rupee. ATM access and card acceptance vary, so carry a practical backup and check current payment advice."}]',
    CONCAT('Plan your Sri Lanka journey from ', cp.country_name),
    'Share your approximate dates, trip length, preferred regions, and travel pace. We can suggest a route for review, then confirm the practical details before you book.',
    '["sri-lanka-eta-rules","sri-lanka-eta-fees","bandaranaike-flight-schedule","srilankan-airlines-timetable","cdc-sri-lanka-health","nathnac-sri-lanka-health","fcdo-sri-lanka-safety","fcdo-sri-lanka-entry-money","cbsl-exchange-rates","cbsl-currency-faq"]',
    'Editorial lead',
    '2026-10-10',
    'Approved UK pilot standard applied as neutral planning copy. Country-specific itinerary links and schema/SEO review remain deferred. Recheck volatile guidance before publication review.'
FROM country_pages cp
WHERE cp.is_published = 1
  AND cp.slug <> 'india'
ON DUPLICATE KEY UPDATE
    country_name = VALUES(country_name),
    guide_key = VALUES(guide_key),
    version = VALUES(version),
    status = VALUES(status),
    meta_title = VALUES(meta_title),
    meta_description = VALUES(meta_description),
    excerpt = VALUES(excerpt),
    intro = VALUES(intro),
    why_sri_lanka = VALUES(why_sri_lanka),
    recommended_itinerary = VALUES(recommended_itinerary),
    travel_info = VALUES(travel_info),
    best_time = VALUES(best_time),
    faq_json = VALUES(faq_json),
    cta_title = VALUES(cta_title),
    cta_text = VALUES(cta_text),
    source_keys_json = VALUES(source_keys_json),
    owner = VALUES(owner),
    review_date = VALUES(review_date),
    reviewer_notes = VALUES(reviewer_notes),
    updated_at = CURRENT_TIMESTAMP;

UPDATE country_pages cp
JOIN country_market_editorial_drafts d ON d.country_slug = cp.slug
SET cp.meta_title = d.meta_title,
    cp.meta_description = d.meta_description,
    cp.excerpt = d.excerpt,
    cp.intro = d.intro,
    cp.why_sri_lanka = d.why_sri_lanka,
    cp.recommended_itinerary = d.recommended_itinerary,
    cp.travel_info = d.travel_info,
    cp.best_time = d.best_time,
    cp.faq_json = d.faq_json,
    cp.cta_title = d.cta_title,
    cp.cta_text = d.cta_text,
    cp.updated_at = CURRENT_TIMESTAMP
WHERE cp.is_published = 1
  AND cp.slug <> 'india'
  AND d.status = 'standard_applied_review';

UPDATE country_pages cp
JOIN country_market_media_assignments ma ON ma.country_slug = cp.slug
SET cp.hero_image = ma.candidate_asset_path,
    cp.og_image = ma.candidate_asset_path,
    cp.updated_at = CURRENT_TIMESTAMP
WHERE cp.is_published = 1
  AND cp.slug <> 'india'
  AND ma.replacement_status = 'ready_for_publish_after_pilot'
  AND ma.rights_status = 'rights_verified'
  AND ma.candidate_asset_path IS NOT NULL;

UPDATE country_market_media_assignments
SET replacement_status = 'published_locally',
    notes = 'Rights-verified asset assigned to public hero and OG fields by COUNTRY-01 standard/media migration. Production commit/deploy remains separate.'
WHERE country_slug <> 'india'
  AND replacement_status = 'ready_for_publish_after_pilot'
  AND rights_status = 'rights_verified';

UPDATE country_market_audits
SET media_gate = 'pass',
    hero_media_status = 'pass',
    og_media_status = 'pass',
    metadata_status = 'review_pending',
    factual_claims_status = 'source_backed_draft',
    internal_links_status = 'deferred',
    internal_links_notes = 'Country-specific itinerary links remain a later COUNTRY-01 gate after editorial/media application.',
    editorial_status = 'standard_applied_review',
    blocked_reason = 'Editorial standard and rights-cleared media applied locally; country-specific links, schema/SEO, and responsive review remain open.',
    updated_at = CURRENT_TIMESTAMP
WHERE country_slug <> 'india';
