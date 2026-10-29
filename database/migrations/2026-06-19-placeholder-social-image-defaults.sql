-- Keeps temporary SVG placeholders visible on pages while giving social/structured
-- data a JPG fallback image that external parsers handle more reliably.

SET NAMES utf8mb4;

UPDATE destinations
SET og_image = 'assets/images/og-default.jpg'
WHERE og_image LIKE 'assets/images/placeholders/%';

UPDATE experiences
SET og_image = 'assets/images/og-default.jpg'
WHERE og_image LIKE 'assets/images/placeholders/%';

UPDATE itineraries
SET og_image = 'assets/images/og-default.jpg'
WHERE og_image LIKE 'assets/images/placeholders/%';

UPDATE tours
SET og_image = 'assets/images/og-default.jpg'
WHERE og_image LIKE 'assets/images/placeholders/%';

UPDATE country_pages
SET og_image = 'assets/images/og-default.jpg'
WHERE og_image LIKE 'assets/images/placeholders/%';

UPDATE blog_posts
SET og_image = 'assets/images/og-default.jpg'
WHERE og_image LIKE 'assets/images/placeholders/%';
