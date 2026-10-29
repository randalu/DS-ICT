-- Island Culture Travels
-- Phase 1 database schema
-- Compatible with MySQL 8+ / MariaDB 10.5+

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS admins (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL,
    username VARCHAR(80) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    last_login_at DATETIME NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_admins_email (email),
    UNIQUE KEY uq_admins_username (username),
    KEY idx_admins_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- WARNING: The default admin seed below is for local development only.
-- The password is a bcrypt hash of "imran123". Remove or replace before production use.
-- The installer (install.php) always creates/overrides the primary admin with user-chosen credentials.
INSERT IGNORE INTO admins (name, email, username, password_hash, is_active)
VALUES (
    'Imran',
    'imran.admin@islandculturetravels.com',
    'imran',
    '$2y$10$rdX/3jowvni7NGH5pihAse7g/BfEA25/.WKkP7fvQNhkXDLCB1Tuu',
    1
);

CREATE TABLE IF NOT EXISTS destinations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    slug VARCHAR(180) NOT NULL,
    excerpt VARCHAR(500) NULL,
    intro LONGTEXT NULL,
    why_visit LONGTEXT NULL,
    things_to_do LONGTEXT NULL,
    best_time LONGTEXT NULL,
    how_to_get_there LONGTEXT NULL,
    suggested_itinerary LONGTEXT NULL,
    where_to_stay LONGTEXT NULL,
    travel_tips LONGTEXT NULL,
    nearby_travel_times LONGTEXT NULL,
    works_well_with LONGTEXT NULL,
    faq_json LONGTEXT NULL,
    cta_title VARCHAR(255) NULL,
    cta_text TEXT NULL,
    province VARCHAR(200) NULL,
    temperature VARCHAR(60) NULL,
    destination_type VARCHAR(120) NULL,
    recommended_stay VARCHAR(80) NULL,
    hero_image VARCHAR(255) NULL,
    cover_image VARCHAR(255) NULL,
    og_image VARCHAR(255) NULL,
    meta_title VARCHAR(255) NULL,
    meta_description VARCHAR(320) NULL,
    canonical_url VARCHAR(255) NULL,
    is_featured TINYINT(1) NOT NULL DEFAULT 0,
    sort_order INT NOT NULL DEFAULT 0,
    is_published TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_destinations_slug (slug),
    KEY idx_destinations_published (is_published),
    KEY idx_destinations_featured (is_featured),
    KEY idx_destinations_sort (sort_order, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS tours (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    slug VARCHAR(180) NOT NULL,
    excerpt VARCHAR(500) NULL,
    intro LONGTEXT NULL,
    highlights LONGTEXT NULL,
    sample_itinerary LONGTEXT NULL,
    coverage_json LONGTEXT NULL,
    program_json LONGTEXT NULL,
    pricing_notes LONGTEXT NULL,
    price_from DECIMAL(10,2) NULL,
    price_to DECIMAL(10,2) NULL,
    price_currency CHAR(3) NOT NULL DEFAULT 'USD',
    price_unit VARCHAR(80) NULL,
    vehicle_options LONGTEXT NULL,
    faq_json LONGTEXT NULL,
    cta_title VARCHAR(255) NULL,
    cta_text TEXT NULL,
    hero_image VARCHAR(255) NULL,
    og_image VARCHAR(255) NULL,
    meta_title VARCHAR(255) NULL,
    meta_description VARCHAR(320) NULL,
    canonical_url VARCHAR(255) NULL,
    is_featured TINYINT(1) NOT NULL DEFAULT 0,
    sort_order INT NOT NULL DEFAULT 0,
    is_published TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_tours_slug (slug),
    KEY idx_tours_published (is_published),
    KEY idx_tours_featured (is_featured),
    KEY idx_tours_sort (sort_order, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS itineraries (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    slug VARCHAR(180) NOT NULL,
    days INT UNSIGNED NOT NULL DEFAULT 1,
    category_id BIGINT UNSIGNED NULL,
    excerpt VARCHAR(500) NULL,
    intro LONGTEXT NULL,
    highlights LONGTEXT NULL,
    route_summary LONGTEXT NULL,
    nearby_travel_times LONGTEXT NULL,
    works_well_with LONGTEXT NULL,
    best_for LONGTEXT NULL,
    travel_notes LONGTEXT NULL,
    pricing_notes LONGTEXT NULL,
    vehicle_options LONGTEXT NULL,
    price_from DECIMAL(10,2) NULL,
    price_to DECIMAL(10,2) NULL,
    price_currency CHAR(3) NOT NULL DEFAULT 'USD',
    price_unit VARCHAR(80) NULL,
    faq_json LONGTEXT NULL,
    cta_title VARCHAR(255) NULL,
    cta_text TEXT NULL,
    hero_image VARCHAR(255) NULL,
    og_image VARCHAR(255) NULL,
    meta_title VARCHAR(255) NULL,
    meta_description VARCHAR(320) NULL,
    canonical_url VARCHAR(255) NULL,
    is_featured TINYINT(1) NOT NULL DEFAULT 0,
    sort_order INT NOT NULL DEFAULT 0,
    is_published TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_itineraries_slug (slug),
    KEY idx_itineraries_category (category_id),
    KEY idx_itineraries_days (days),
    KEY idx_itineraries_published (is_published),
    KEY idx_itineraries_featured (is_featured),
    KEY idx_itineraries_sort (sort_order, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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

CREATE TABLE IF NOT EXISTS itinerary_days (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    itinerary_id BIGINT UNSIGNED NOT NULL,
    day_number INT UNSIGNED NOT NULL,
    day_end INT UNSIGNED NULL,
    title VARCHAR(255) NOT NULL,
    summary LONGTEXT NULL,
    overnight_location VARCHAR(150) NULL,
    travel_time VARCHAR(100) NULL,
    image_path VARCHAR(255) NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_itinerary_days_unique_day (itinerary_id, day_number),
    KEY idx_itinerary_days_itinerary (itinerary_id),
    CONSTRAINT fk_itinerary_days_itinerary
        FOREIGN KEY (itinerary_id) REFERENCES itineraries(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS country_pages (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    country_name VARCHAR(120) NOT NULL,
    slug VARCHAR(180) NOT NULL,
    excerpt VARCHAR(500) NULL,
    intro LONGTEXT NULL,
    why_sri_lanka LONGTEXT NULL,
    recommended_itinerary LONGTEXT NULL,
    travel_info LONGTEXT NULL,
    best_time LONGTEXT NULL,
    faq_json LONGTEXT NULL,
    cta_title VARCHAR(255) NULL,
    cta_text TEXT NULL,
    hero_image VARCHAR(255) NULL,
    og_image VARCHAR(255) NULL,
    meta_title VARCHAR(255) NULL,
    meta_description VARCHAR(320) NULL,
    canonical_url VARCHAR(255) NULL,
    sort_order INT NOT NULL DEFAULT 0,
    is_published TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_country_pages_slug (slug),
    KEY idx_country_pages_published (is_published),
    KEY idx_country_pages_sort (sort_order, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS static_pages (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(150) NOT NULL,
    slug VARCHAR(180) NOT NULL,
    content LONGTEXT NULL,
    hero_image VARCHAR(255) NULL,
    meta_title VARCHAR(255) NULL,
    meta_description VARCHAR(320) NULL,
    canonical_url VARCHAR(255) NULL,
    sort_order INT NOT NULL DEFAULT 0,
    is_published TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_static_pages_slug (slug),
    KEY idx_static_pages_published (is_published),
    KEY idx_static_pages_sort (sort_order, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS reviews (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    customer_name VARCHAR(150) NOT NULL,
    country VARCHAR(120) NULL,
    rating TINYINT UNSIGNED NOT NULL DEFAULT 5,
    review_text TEXT NOT NULL,
    trip_type VARCHAR(120) NULL,
    tour_done_month CHAR(7) NULL,
    review_source VARCHAR(120) NULL,
    review_source_url VARCHAR(255) NULL,
    is_featured TINYINT(1) NOT NULL DEFAULT 0,
    is_published TINYINT(1) NOT NULL DEFAULT 1,
    sort_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_reviews_published (is_published),
    KEY idx_reviews_featured (is_featured),
    KEY idx_reviews_sort (sort_order, id),
    CONSTRAINT chk_reviews_rating CHECK (rating BETWEEN 1 AND 5)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS contact_inquiries (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(190) NULL,
    phone VARCHAR(50) NULL,
    country VARCHAR(120) NULL,
    travel_dates VARCHAR(120) NULL,
    group_size VARCHAR(50) NULL,
    interests TEXT NULL,
    message TEXT NOT NULL,
    source_page VARCHAR(255) NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_contact_inquiries_created (created_at),
    KEY idx_contact_inquiries_email (email),
    KEY idx_contact_inquiries_phone (phone)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS destination_related (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    destination_id BIGINT UNSIGNED NOT NULL,
    related_destination_id BIGINT UNSIGNED NOT NULL,
    UNIQUE KEY uq_destination_related_pair (destination_id, related_destination_id),
    KEY idx_destination_related_destination (destination_id),
    KEY idx_destination_related_related (related_destination_id),
    CONSTRAINT fk_destination_related_destination
        FOREIGN KEY (destination_id) REFERENCES destinations(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_destination_related_related
        FOREIGN KEY (related_destination_id) REFERENCES destinations(id)
        ON DELETE CASCADE,
    CONSTRAINT chk_destination_related_not_same CHECK (destination_id <> related_destination_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS destination_tour (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    destination_id BIGINT UNSIGNED NOT NULL,
    tour_id BIGINT UNSIGNED NOT NULL,
    UNIQUE KEY uq_destination_tour_pair (destination_id, tour_id),
    KEY idx_destination_tour_destination (destination_id),
    KEY idx_destination_tour_tour (tour_id),
    CONSTRAINT fk_destination_tour_destination
        FOREIGN KEY (destination_id) REFERENCES destinations(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_destination_tour_tour
        FOREIGN KEY (tour_id) REFERENCES tours(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS destination_itinerary (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    destination_id BIGINT UNSIGNED NOT NULL,
    itinerary_id BIGINT UNSIGNED NOT NULL,
    UNIQUE KEY uq_destination_itinerary_pair (destination_id, itinerary_id),
    KEY idx_destination_itinerary_destination (destination_id),
    KEY idx_destination_itinerary_itinerary (itinerary_id),
    CONSTRAINT fk_destination_itinerary_destination
        FOREIGN KEY (destination_id) REFERENCES destinations(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_destination_itinerary_itinerary
        FOREIGN KEY (itinerary_id) REFERENCES itineraries(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS itinerary_destination (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    itinerary_id BIGINT UNSIGNED NOT NULL,
    destination_id BIGINT UNSIGNED NOT NULL,
    UNIQUE KEY uq_itinerary_destination_pair (itinerary_id, destination_id),
    KEY idx_itinerary_destination_itinerary (itinerary_id),
    KEY idx_itinerary_destination_destination (destination_id),
    CONSTRAINT fk_itinerary_destination_itinerary
        FOREIGN KEY (itinerary_id) REFERENCES itineraries(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_itinerary_destination_destination
        FOREIGN KEY (destination_id) REFERENCES destinations(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS itinerary_tour (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    itinerary_id BIGINT UNSIGNED NOT NULL,
    tour_id BIGINT UNSIGNED NOT NULL,
    UNIQUE KEY uq_itinerary_tour_pair (itinerary_id, tour_id),
    KEY idx_itinerary_tour_itinerary (itinerary_id),
    KEY idx_itinerary_tour_tour (tour_id),
    CONSTRAINT fk_itinerary_tour_itinerary
        FOREIGN KEY (itinerary_id) REFERENCES itineraries(id)
        ON DELETE CASCADE,
    CONSTRAINT fk_itinerary_tour_tour
        FOREIGN KEY (tour_id) REFERENCES tours(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── Services ────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS services (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    icon VARCHAR(20) NOT NULL DEFAULT '',
    title VARCHAR(200) NOT NULL DEFAULT '',
    description TEXT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_services_sort (sort_order, id),
    KEY idx_services_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO services (id, icon, title, description, sort_order, is_active) VALUES
(1, '🚗', 'Private Tours & Transfers', '100% private travel in comfortable, air-conditioned vehicles with professional, friendly English-speaking drivers.', 1, 1),
(2, '🏨', 'Hotel Reservations', 'We assist with hotel suggestions from boutique guesthouses to luxury resorts — personally vetted for quality and location.', 2, 1),
(3, '🧭', 'Expert Local Guidance', 'Driver-guides with deep local knowledge bringing landscapes, history, and culture to life throughout your journey.', 3, 1),
(4, '📅', 'Custom Itinerary Planning', 'Free, no-obligation itinerary design built around your interests, pace, group size, and travel dates.', 4, 1),
(5, '✈️', 'Airport Transfers', 'Meet-and-greet at Bandaranaike International Airport, departure transfers, and inter-city transfers — always on time.', 5, 1),
(6, '💬', 'WhatsApp Support', 'Our team is reachable on WhatsApp before and throughout your journey. Real people, real help — any time, any day.', 6, 1);

-- ─── Destination Photos ───────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS social_links (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slot_key VARCHAR(80) NOT NULL,
    label VARCHAR(120) NOT NULL DEFAULT '',
    url VARCHAR(255) NULL,
    icon_text VARCHAR(20) NULL,
    icon_image VARCHAR(255) NULL,
    sort_order INT NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    is_custom TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_social_links_slot (slot_key),
    KEY idx_social_links_sort (sort_order, id),
    KEY idx_social_links_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO social_links (slot_key, label, url, icon_text, icon_image, sort_order, is_active, is_custom) VALUES
('facebook', 'Facebook', NULL, 'f', NULL, 1, 1, 0),
('instagram', 'Instagram', NULL, 'ig', NULL, 2, 1, 0),
('youtube', 'YouTube', NULL, 'yt', NULL, 3, 1, 0),
('pinterest', 'Pinterest', NULL, 'p', NULL, 4, 1, 0),
('linkedin', 'LinkedIn', NULL, 'in', NULL, 5, 1, 0),
('tripadvisor', 'Tripadvisor', NULL, '*', NULL, 6, 1, 0),
('custom_1', 'Custom 1', NULL, '', NULL, 101, 0, 1),
('custom_2', 'Custom 2', NULL, '', NULL, 102, 0, 1),
('custom_3', 'Custom 3', NULL, '', NULL, 103, 0, 1),
('custom_4', 'Custom 4', NULL, '', NULL, 104, 0, 1),
('custom_5', 'Custom 5', NULL, '', NULL, 105, 0, 1);

CREATE TABLE IF NOT EXISTS destination_photos (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    destination_id BIGINT UNSIGNED NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    caption VARCHAR(300) NULL,
    sort_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_dest_photos_destination (destination_id),
    KEY idx_dest_photos_sort (destination_id, sort_order),
    CONSTRAINT fk_dest_photos_destination
        FOREIGN KEY (destination_id) REFERENCES destinations(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── Blog Posts ───────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS blog_categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    slug VARCHAR(140) NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    UNIQUE KEY uq_blog_categories_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS blog_posts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_id INT UNSIGNED NULL,
    title VARCHAR(250) NOT NULL,
    slug VARCHAR(280) NOT NULL,
    excerpt VARCHAR(500) NULL,
    content LONGTEXT NULL,
    hero_image VARCHAR(255) NULL,
    og_image VARCHAR(255) NULL,
    author VARCHAR(120) NULL DEFAULT 'Island Culture Travels',
    meta_title VARCHAR(255) NULL,
    meta_description VARCHAR(320) NULL,
    canonical_url VARCHAR(255) NULL,
    sidebar_widgets_json LONGTEXT NULL,
    is_featured TINYINT(1) NOT NULL DEFAULT 0,
    is_published TINYINT(1) NOT NULL DEFAULT 0,
    published_at DATETIME NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_blog_posts_slug (slug),
    KEY idx_blog_posts_published (is_published),
    KEY idx_blog_posts_category (category_id),
    KEY idx_blog_posts_published_at (published_at),
    CONSTRAINT fk_blog_posts_category
        FOREIGN KEY (category_id) REFERENCES blog_categories(id)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── Seasonal Availability ────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS seasonal_notes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    entity_type ENUM('destination','tour') NOT NULL,
    entity_id BIGINT UNSIGNED NOT NULL,
    season_label VARCHAR(80) NOT NULL DEFAULT '',
    season_note TEXT NULL,
    KEY idx_seasonal_entity (entity_type, entity_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─── Page Views (Web Stats) ───────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS page_views (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    path VARCHAR(500) NOT NULL,
    ip_address VARCHAR(45) NOT NULL DEFAULT '',
    country VARCHAR(80) NOT NULL DEFAULT '',
    referrer VARCHAR(500) NOT NULL DEFAULT '',
    user_agent VARCHAR(300) NOT NULL DEFAULT '',
    visitor_key CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL DEFAULT '',
    visit_key CHAR(32) CHARACTER SET ascii COLLATE ascii_bin NOT NULL DEFAULT '',
    device_type VARCHAR(16) NOT NULL DEFAULT '',
    browser_name VARCHAR(24) NOT NULL DEFAULT '',
    os_name VARCHAR(24) NOT NULL DEFAULT '',
    viewed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_pv_path (path(200)),
    KEY idx_pv_viewed_at (viewed_at),
    KEY idx_pv_ip (ip_address)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Analytics events + configurable tracking settings
CREATE TABLE IF NOT EXISTS analytics_events (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_type VARCHAR(80) NOT NULL,
    path VARCHAR(500) NOT NULL DEFAULT '',
    target_url VARCHAR(700) NOT NULL DEFAULT '',
    target_group VARCHAR(120) NOT NULL DEFAULT '',
    ip_address VARCHAR(45) NOT NULL DEFAULT '',
    user_agent VARCHAR(300) NOT NULL DEFAULT '',
    referrer VARCHAR(500) NOT NULL DEFAULT '',
    meta_json LONGTEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_analytics_events_type (event_type),
    KEY idx_analytics_events_created (created_at),
    KEY idx_analytics_events_path (path(200)),
    KEY idx_analytics_events_target_group (target_group)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS analytics_event_types (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    event_key VARCHAR(80) NOT NULL,
    label VARCHAR(120) NOT NULL,
    description VARCHAR(255) NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    sort_order INT NOT NULL DEFAULT 0,
    retention_days INT UNSIGNED NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_analytics_event_types_key (event_key),
    KEY idx_analytics_event_types_active (is_active),
    KEY idx_analytics_event_types_sort (sort_order, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS analytics_target_rules (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    rule_name VARCHAR(120) NOT NULL,
    match_type VARCHAR(20) NOT NULL DEFAULT 'contains',
    match_value VARCHAR(255) NOT NULL,
    group_label VARCHAR(120) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    sort_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_analytics_target_rules_active (is_active),
    KEY idx_analytics_target_rules_sort (sort_order, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS analytics_settings (
    setting_key VARCHAR(120) PRIMARY KEY,
    setting_value LONGTEXT NULL,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS site_settings (
    setting_key VARCHAR(120) PRIMARY KEY,
    setting_value LONGTEXT NULL,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO analytics_event_types (event_key, label, description, is_active, sort_order) VALUES
('whatsapp_click', 'WhatsApp Click', 'User clicked a WhatsApp contact link.', 1, 1),
('phone_click', 'Phone Click', 'User clicked a tel: phone link.', 1, 2),
('email_click', 'Email Click', 'User clicked a mailto: link.', 1, 3),
('contact_cta_click', 'Contact CTA Click', 'User clicked a primary contact/quote CTA.', 1, 4),
('external_link_click', 'External Link Click', 'User clicked a non-site external URL.', 1, 5);

INSERT IGNORE INTO analytics_settings (setting_key, setting_value) VALUES
('events_retention_days', '365');

-- Extended inquiry CRM + anti-spam fields
ALTER TABLE contact_inquiries
    ADD COLUMN preferred_itinerary_id BIGINT UNSIGNED NULL AFTER interests,
    ADD COLUMN preferred_itinerary_name VARCHAR(180) NULL AFTER preferred_itinerary_id,
    ADD COLUMN whatsapp VARCHAR(50) NULL AFTER phone,
    ADD COLUMN adults_count INT UNSIGNED NULL AFTER group_size,
    ADD COLUMN children_count INT UNSIGNED NULL AFTER adults_count,
    ADD COLUMN budget_min DECIMAL(10,2) NULL AFTER children_count,
    ADD COLUMN budget_max DECIMAL(10,2) NULL AFTER budget_min,
    ADD COLUMN budget_currency CHAR(3) NULL AFTER budget_max,
    ADD COLUMN accommodation_preference VARCHAR(120) NULL AFTER budget_currency,
    ADD COLUMN inquiry_status VARCHAR(30) NOT NULL DEFAULT 'new' AFTER source_page,
    ADD COLUMN admin_notes LONGTEXT NULL AFTER inquiry_status,
    ADD COLUMN is_spam TINYINT(1) NOT NULL DEFAULT 0 AFTER admin_notes,
    ADD COLUMN spam_reason VARCHAR(255) NULL AFTER is_spam,
    ADD COLUMN follow_up_at DATETIME NULL AFTER spam_reason,
    ADD COLUMN last_contacted_at DATETIME NULL AFTER follow_up_at,
    ADD COLUMN admin_updated_at DATETIME NULL AFTER last_contacted_at,
    ADD COLUMN email_sent_to_customer TINYINT(1) NOT NULL DEFAULT 0 AFTER admin_updated_at,
    ADD COLUMN email_sent_to_admin TINYINT(1) NOT NULL DEFAULT 0 AFTER email_sent_to_customer,
    ADD COLUMN tracking_meta_json LONGTEXT NULL AFTER email_sent_to_admin,
    ADD KEY idx_contact_inquiries_itinerary (preferred_itinerary_id),
    ADD KEY idx_contact_inquiries_status (inquiry_status),
    ADD KEY idx_contact_inquiries_spam (is_spam),
    ADD KEY idx_contact_inquiries_follow_up (follow_up_at);

CREATE TABLE IF NOT EXISTS contact_inquiry_activity (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    inquiry_id BIGINT UNSIGNED NOT NULL,
    action_type VARCHAR(80) NOT NULL,
    old_value VARCHAR(190) NULL,
    new_value VARCHAR(190) NULL,
    note TEXT NULL,
    admin_user_id BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_contact_inquiry_activity_inquiry (inquiry_id, created_at),
    KEY idx_contact_inquiry_activity_action (action_type),
    KEY idx_contact_inquiry_activity_admin (admin_user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS form_submission_limits (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    form_key VARCHAR(80) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_form_submission_limits_form_time (form_key, created_at),
    KEY idx_form_submission_limits_ip_time (ip_address, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Social media marketing / assets
CREATE TABLE IF NOT EXISTS media_assets (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    file_path VARCHAR(255) NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    mime_type VARCHAR(120) NOT NULL,
    file_size BIGINT UNSIGNED NOT NULL DEFAULT 0,
    alt_text VARCHAR(255) NULL,
    seo_title VARCHAR(255) NULL,
    seo_description VARCHAR(320) NULL,
    tags VARCHAR(255) NULL,
    uploaded_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_media_assets_created (created_at),
    KEY idx_media_assets_uploaded_by (uploaded_by)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS social_media_posts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description LONGTEXT NULL,
    reference_destination_id BIGINT UNSIGNED NULL,
    reference_itinerary_id BIGINT UNSIGNED NULL,
    reference_tour_id BIGINT UNSIGNED NULL,
    experience_name VARCHAR(180) NULL,
    platform_targets_json LONGTEXT NULL,
    hashtags VARCHAR(255) NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'draft',
    scheduled_at DATETIME NULL,
    published_at DATETIME NULL,
    blog_post_id BIGINT UNSIGNED NULL,
    created_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_social_media_posts_status (status),
    KEY idx_social_media_posts_scheduled (scheduled_at),
    KEY idx_social_media_posts_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS social_media_post_assets (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    post_id BIGINT UNSIGNED NOT NULL,
    asset_id BIGINT UNSIGNED NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_social_media_post_asset (post_id, asset_id),
    KEY idx_social_media_post_assets_post (post_id, sort_order),
    KEY idx_social_media_post_assets_asset (asset_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS social_publish_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    post_id BIGINT UNSIGNED NOT NULL,
    platform VARCHAR(40) NOT NULL,
    publish_status VARCHAR(40) NOT NULL,
    response_json LONGTEXT NULL,
    error_message TEXT NULL,
    retry_count INT UNSIGNED NOT NULL DEFAULT 0,
    attempted_by BIGINT UNSIGNED NULL,
    attempted_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_social_publish_logs_post (post_id, attempted_at),
    KEY idx_social_publish_logs_platform (platform, attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS social_platform_connections (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    platform VARCHAR(40) NOT NULL,
    auth_mode VARCHAR(30) NOT NULL DEFAULT 'manual',
    account_label VARCHAR(150) NULL,
    access_token TEXT NULL,
    refresh_token TEXT NULL,
    token_expires_at DATETIME NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_social_platform_connections_platform (platform)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- Additive only. Apply explicitly; public/admin reads never install this schema.
CREATE TABLE IF NOT EXISTS map_locations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    latitude DECIMAL(9,6) NOT NULL,
    longitude DECIMAL(9,6) NOT NULL,
    destination_id BIGINT UNSIGNED NULL,
    coordinate_source VARCHAR(500) NOT NULL,
    is_verified TINYINT(1) NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    revision BIGINT UNSIGNED NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_map_location_destination (destination_id),
    KEY idx_map_location_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS package_route_maps (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    package_type VARCHAR(20) NOT NULL,
    package_id BIGINT UNSIGNED NOT NULL,
    route_json LONGTEXT NOT NULL,
    revision BIGINT UNSIGNED NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_package_route_map (package_type, package_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS activity_pages (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(180) NOT NULL,
    slug VARCHAR(180) NOT NULL,
    is_hub TINYINT(1) NOT NULL DEFAULT 0,
    excerpt TEXT NOT NULL,
    meta_title VARCHAR(255) NULL,
    meta_description VARCHAR(320) NULL,
    hero_image VARCHAR(255) NULL,
    cover_image VARCHAR(255) NULL,
    cover_alt VARCHAR(255) NULL,
    cover_media_asset_id BIGINT UNSIGNED NULL,
    og_image VARCHAR(255) NULL,
    research_sources_json LONGTEXT NULL,
    research_verified_at DATE NULL,
    is_published TINYINT(1) NOT NULL DEFAULT 0,
    sort_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_activity_pages_slug (slug),
    KEY idx_activity_pages_published_order (is_published, sort_order, id),
    KEY idx_activity_pages_hub (is_hub)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS activity_examples (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    activity_page_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(255) NOT NULL,
    destinations_json LONGTEXT NOT NULL,
    source_urls_json LONGTEXT NULL,
    last_verified_at DATE NULL,
    sort_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_activity_example_title (activity_page_id, title),
    KEY idx_activity_examples_order (activity_page_id, sort_order, id),
    CONSTRAINT fk_activity_examples_page FOREIGN KEY (activity_page_id)
        REFERENCES activity_pages(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Additive, rerunnable: no existing activity or media records are changed.
CREATE TABLE IF NOT EXISTS activity_example_images (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    activity_example_id BIGINT UNSIGNED NOT NULL,
    media_asset_id BIGINT UNSIGNED NOT NULL,
    alt_text VARCHAR(255) NOT NULL,
    sort_order INT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_activity_example_asset (activity_example_id, media_asset_id),
    KEY idx_activity_example_image_order (activity_example_id, sort_order, id),
    KEY idx_activity_example_image_asset (media_asset_id),
    CONSTRAINT fk_activity_example_image_entry FOREIGN KEY (activity_example_id)
        REFERENCES activity_examples(id) ON DELETE CASCADE,
    CONSTRAINT fk_activity_example_image_asset FOREIGN KEY (media_asset_id)
        REFERENCES media_assets(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
