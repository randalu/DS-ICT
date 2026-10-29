-- Theme pages: extend itinerary_categories with optional theme metadata.
-- MySQL 8.x — no IF NOT EXISTS; check column existence before re-applying.

ALTER TABLE itinerary_categories
    ADD COLUMN is_theme TINYINT(1) NOT NULL DEFAULT 0,
    ADD COLUMN theme_slug VARCHAR(80) NULL,
    ADD COLUMN theme_intro_html MEDIUMTEXT NULL,
    ADD UNIQUE KEY uq_itinerary_categories_theme_slug (theme_slug);

-- Seed the five whitelisted themes. Match these to existing categories by name
-- where possible; admin can swap mappings via the itinerary_categories editor.
INSERT INTO itinerary_categories (name, slug, is_theme, theme_slug, sort_order)
VALUES
    ('Honeymoon', 'honeymoon', 1, 'honeymoon-tours', 100),
    ('Family',    'family',    1, 'family-tours',    101),
    ('Adventure', 'adventure', 1, 'adventure-tours', 102),
    ('Wellness',  'wellness',  1, 'wellness-tours',  103),
    ('Luxury',    'luxury',    1, 'luxury-tours',    104)
ON DUPLICATE KEY UPDATE
    is_theme   = VALUES(is_theme),
    theme_slug = VALUES(theme_slug);
