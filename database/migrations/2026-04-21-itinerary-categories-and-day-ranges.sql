ALTER TABLE itinerary_days
    ADD COLUMN day_end INT UNSIGNED NULL AFTER day_number;

UPDATE itinerary_days
SET day_end = day_number
WHERE day_end IS NULL;

CREATE TABLE IF NOT EXISTS itinerary_categories (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    slug VARCHAR(180) NOT NULL,
    description TEXT NULL,
    hero_image VARCHAR(255) NULL,
    og_image VARCHAR(255) NULL,
    meta_title VARCHAR(255) NULL,
    meta_description VARCHAR(320) NULL,
    canonical_url VARCHAR(255) NULL,
    sort_order INT NOT NULL DEFAULT 0,
    is_published TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_itinerary_categories_slug (slug),
    KEY idx_itinerary_categories_published (is_published),
    KEY idx_itinerary_categories_sort (sort_order, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE itineraries
    ADD COLUMN category_id BIGINT UNSIGNED NULL AFTER days,
    ADD COLUMN highlights LONGTEXT NULL AFTER intro,
    ADD COLUMN pricing_notes LONGTEXT NULL AFTER travel_notes,
    ADD COLUMN vehicle_options LONGTEXT NULL AFTER pricing_notes;

ALTER TABLE itineraries
    ADD KEY idx_itineraries_category (category_id);

ALTER TABLE itineraries
    ADD CONSTRAINT fk_itineraries_category
        FOREIGN KEY (category_id) REFERENCES itinerary_categories(id)
        ON DELETE SET NULL;

INSERT IGNORE INTO itinerary_categories (name, slug, description, meta_title, meta_description, canonical_url, sort_order, is_published)
VALUES
    ('Classic Tours', 'classic-tours', 'Balanced private Sri Lanka routes covering culture, nature, and beaches.', 'Classic Sri Lanka Tours | Island Culture Travels', 'Browse classic Sri Lanka itinerary styles with private transport and flexible planning.', NULL, 1, 1),
    ('Day Tours', 'day-tours', 'Short one-day and two-day itinerary options with private driver support.', 'Sri Lanka Day Tours | Island Culture Travels', 'Explore Sri Lanka day tour itineraries with private vehicles and easy route planning.', NULL, 2, 1);

UPDATE itineraries i
LEFT JOIN itinerary_categories c ON c.slug = 'classic-tours'
SET i.category_id = c.id
WHERE i.category_id IS NULL;
