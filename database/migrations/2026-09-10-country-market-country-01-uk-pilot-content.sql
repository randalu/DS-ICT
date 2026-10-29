-- COUNTRY-01: publish the reviewed United Kingdom pilot copy
--
-- Approval boundary: review this migration and the UK pilot tab before
-- committing/applying it. It copies only the stored UK draft into the public
-- country_pages row. It intentionally does not replace hero/OG media, change
-- publication state, or update any of the remaining 19 country pages.

SET NAMES utf8mb4;

UPDATE country_pages AS page
INNER JOIN country_market_editorial_drafts AS draft
    ON draft.country_slug = page.slug
   AND draft.country_slug = 'united-kingdom'
SET
    page.meta_title = draft.meta_title,
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
WHERE page.slug = 'united-kingdom';

UPDATE country_market_editorial_drafts
SET
    status = 'pilot_published_review',
    reviewer_notes = CONCAT(
        COALESCE(reviewer_notes, ''),
        ' Pilot content migration prepared/applied for review; media remains blocked and remaining country pages are unchanged.'
    ),
    updated_at = CURRENT_TIMESTAMP
WHERE country_slug = 'united-kingdom';

UPDATE country_market_audits
SET
    editorial_status = 'pilot_published_review',
    metadata_status = 'review_pending',
    factual_claims_status = 'source_backed_draft',
    updated_at = CURRENT_TIMESTAMP
WHERE country_slug = 'united-kingdom';
