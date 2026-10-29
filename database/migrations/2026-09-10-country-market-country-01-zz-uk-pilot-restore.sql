-- COUNTRY-01 correction: restore the approved United Kingdom pilot after the
-- remaining-page standard migration. Media remains published locally; this
-- migration only restores UK-specific pilot copy and status.

SET NAMES utf8mb4;

INSERT INTO country_market_editorial_drafts (
    country_slug, country_name, guide_key, version, status, meta_title,
    meta_description, excerpt, intro, why_sri_lanka, recommended_itinerary,
    travel_info, best_time, faq_json, cta_title, cta_text, source_keys_json,
    owner, review_date, reviewer_notes
) VALUES (
    'united-kingdom',
    'United Kingdom',
    'country-market-editorial-v1',
    '1.0-pilot',
    'pilot_published_review',
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
    'Pilot approval received and restored after the remaining-page standard pass. Media is published locally; country-specific links, schema/SEO, and responsive review remain open.'
)
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

UPDATE country_pages page
JOIN country_market_editorial_drafts draft ON draft.country_slug = page.slug
SET page.meta_title = draft.meta_title,
    page.meta_description = draft.meta_description,
    page.excerpt = draft.excerpt,
    page.intro = draft.intro,
    page.why_sri_lanka = draft.why_sri_lanka,
    page.recommended_itinerary = draft.recommended_itinerary,
    page.travel_info = draft.travel_info,
    page.best_time = draft.best_time,
    page.faq_json = draft.faq_json,
    page.cta_title = draft.cta_title,
    page.cta_text = draft.cta_text,
    page.updated_at = CURRENT_TIMESTAMP
WHERE page.slug = 'united-kingdom'
  AND draft.status = 'pilot_published_review';

UPDATE country_market_audits
SET editorial_status = 'pilot_published_review',
    metadata_status = 'review_pending',
    factual_claims_status = 'source_backed_draft',
    blocked_reason = 'Approved UK pilot copy and rights-cleared media are applied locally; country-specific links, schema/SEO, and responsive review remain open.',
    updated_at = CURRENT_TIMESTAMP
WHERE country_slug = 'united-kingdom';
