-- PHASE 44-47 compatibility follow-up: normalize the remaining CMS links.

UPDATE static_pages
SET content = REPLACE(content, '<a href="/destinations">destinations</a>', 'destinations')
WHERE slug = 'sri-lanka-private-tours';

UPDATE static_pages
SET content = REPLACE(content, '<a href="/itineraries">sample itineraries</a>', 'sample itineraries')
WHERE slug = 'sri-lanka-tour-cost';
