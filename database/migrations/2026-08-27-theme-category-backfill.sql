-- Backfill themed itinerary categories so theme pages can render from data.
-- This keeps theme pages data-driven instead of relying on empty-state copy.

SET NAMES utf8mb4;

UPDATE itineraries i
JOIN itinerary_categories c ON c.theme_slug = 'honeymoon-tours'
SET i.category_id = c.id
WHERE i.slug = 'sri-lanka-honeymoon-itinerary-12-days'
  AND COALESCE(i.category_id, 0) <> c.id;

UPDATE itineraries i
JOIN itinerary_categories c ON c.theme_slug = 'family-tours'
SET i.category_id = c.id
WHERE i.slug = 'sri-lanka-family-itinerary-10-days'
  AND COALESCE(i.category_id, 0) <> c.id;

UPDATE itineraries i
JOIN itinerary_categories c ON c.theme_slug = 'luxury-tours'
SET i.category_id = c.id
WHERE i.slug = 'sri-lanka-luxury-itinerary-11-days'
  AND COALESCE(i.category_id, 0) <> c.id;
