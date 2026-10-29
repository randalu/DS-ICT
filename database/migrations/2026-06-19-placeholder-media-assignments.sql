-- Assigns explicit temporary placeholder images to published content records
-- that still have no hero image, social image, or selected media asset.
-- These placeholders are intentionally tracked under assets/images/placeholders/
-- so the admin AI/Search Audit can list them for final replacement.

SET NAMES utf8mb4;

UPDATE destinations
SET hero_image = 'assets/images/placeholders/placeholder-destination.svg',
    og_image = 'assets/images/placeholders/placeholder-destination.svg',
    hero_media_type = 'image'
WHERE is_published = 1
  AND (hero_image IS NULL OR TRIM(hero_image) = '')
  AND (og_image IS NULL OR TRIM(og_image) = '')
  AND (hero_media_asset_id IS NULL OR hero_media_asset_id = 0);

UPDATE experiences
SET hero_image = 'assets/images/placeholders/placeholder-experience.svg',
    og_image = 'assets/images/placeholders/placeholder-experience.svg',
    hero_media_type = 'image'
WHERE is_published = 1
  AND (hero_image IS NULL OR TRIM(hero_image) = '')
  AND (og_image IS NULL OR TRIM(og_image) = '')
  AND (hero_media_asset_id IS NULL OR hero_media_asset_id = 0);

UPDATE itineraries
SET hero_image = 'assets/images/placeholders/placeholder-itinerary.svg',
    og_image = 'assets/images/placeholders/placeholder-itinerary.svg',
    hero_media_type = 'image'
WHERE is_published = 1
  AND (hero_image IS NULL OR TRIM(hero_image) = '')
  AND (og_image IS NULL OR TRIM(og_image) = '')
  AND (hero_media_asset_id IS NULL OR hero_media_asset_id = 0);

UPDATE tours
SET hero_image = 'assets/images/placeholders/placeholder-tour.svg',
    og_image = 'assets/images/placeholders/placeholder-tour.svg'
WHERE is_published = 1
  AND (hero_image IS NULL OR TRIM(hero_image) = '')
  AND (og_image IS NULL OR TRIM(og_image) = '');

UPDATE country_pages
SET hero_image = 'assets/images/placeholders/placeholder-country.svg',
    og_image = 'assets/images/placeholders/placeholder-country.svg'
WHERE is_published = 1
  AND (hero_image IS NULL OR TRIM(hero_image) = '')
  AND (og_image IS NULL OR TRIM(og_image) = '');

UPDATE blog_posts
SET hero_image = 'assets/images/placeholders/placeholder-blog.svg',
    og_image = 'assets/images/placeholders/placeholder-blog.svg',
    hero_media_type = 'image'
WHERE is_published = 1
  AND (hero_image IS NULL OR TRIM(hero_image) = '')
  AND (og_image IS NULL OR TRIM(og_image) = '')
  AND (hero_media_asset_id IS NULL OR hero_media_asset_id = 0);
