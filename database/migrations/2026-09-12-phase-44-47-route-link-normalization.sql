-- PHASE 44-47 compatibility follow-up.
-- Keep CMS content independent of a deployment subdirectory; the rendered
-- static-page related-link block supplies base_url-aware navigation.

UPDATE static_pages
SET content = REPLACE(
    REPLACE(
        REPLACE(
            REPLACE(content, '<a href="/itineraries">private itinerary collection</a>', 'private itinerary collection'),
            '<a href="/destinations">Sri Lanka destinations</a>', 'Sri Lanka destinations'
        ),
        '<a href="/contact">ask for a route recommendation</a>', 'ask for a route recommendation'
    ),
    '<a href="/private-driver-sri-lanka">private driver planning guide</a>', 'private driver planning guide'
)
WHERE slug IN ('private-driver-sri-lanka', 'private-driver-sri-lanka-cost', 'sri-lanka-private-tours', 'sri-lanka-tour-cost');

UPDATE static_pages
SET content = REPLACE(
    REPLACE(
        REPLACE(content, '<a href="/contact">request a current quote</a>', 'request a current quote'),
        '<a href="/itineraries">itinerary collection</a>', 'itinerary collection'
    ),
    '<a href="/experiences">experiences</a>', 'experiences'
)
WHERE slug IN ('private-driver-sri-lanka-cost', 'sri-lanka-private-tours');

UPDATE static_pages
SET content = REPLACE(
    REPLACE(
        REPLACE(content, '<a href="/destinations">destinations</a>', 'destinations'),
        '<a href="/tours">private tour ideas</a>', 'private tour ideas'
    ),
    '<a href="/contact">request a current trip estimate</a>', 'request a current trip estimate'
)
WHERE slug = 'sri-lanka-tour-cost';
