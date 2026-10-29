-- B2B business partner applications and published partner directory.
-- MySQL 8.x / MariaDB compatible. Safe to run more than once after PHP schema guards.

CREATE TABLE IF NOT EXISTS business_partner_categories (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(180) NOT NULL,
    slug VARCHAR(180) NOT NULL UNIQUE,
    description TEXT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    is_published TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_business_partner_categories_public (is_published, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS business_partners (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_id BIGINT UNSIGNED NULL,
    business_name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    business_type VARCHAR(80) NOT NULL DEFAULT 'hotel',
    location VARCHAR(180) NULL,
    destination_id BIGINT UNSIGNED NULL,
    contact_person VARCHAR(180) NULL,
    contact_role VARCHAR(180) NULL,
    email VARCHAR(190) NULL,
    phone VARCHAR(80) NULL,
    whatsapp VARCHAR(80) NULL,
    website_url VARCHAR(255) NULL,
    google_maps_url VARCHAR(255) NULL,
    short_description TEXT NULL,
    partner_reason TEXT NULL,
    accommodation_details TEXT NULL,
    price_range VARCHAR(80) NULL,
    admin_notes LONGTEXT NULL,
    status VARCHAR(40) NOT NULL DEFAULT 'applied',
    is_featured TINYINT(1) NOT NULL DEFAULT 0,
    sort_order INT NOT NULL DEFAULT 0,
    hero_image VARCHAR(255) NULL,
    og_image VARCHAR(255) NULL,
    meta_title VARCHAR(255) NULL,
    meta_description VARCHAR(320) NULL,
    canonical_url VARCHAR(255) NULL,
    published_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_business_partners_public (status, sort_order),
    KEY idx_business_partners_category (category_id, status),
    KEY idx_business_partners_destination (destination_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS business_partner_photos (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    partner_id BIGINT UNSIGNED NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    alt_text VARCHAR(255) NULL,
    sort_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_business_partner_photos_partner (partner_id, sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS business_partner_destination (
    partner_id BIGINT UNSIGNED NOT NULL,
    destination_id BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (partner_id, destination_id),
    KEY idx_business_partner_destination_destination (destination_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS business_partner_itinerary (
    partner_id BIGINT UNSIGNED NOT NULL,
    itinerary_id BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (partner_id, itinerary_id),
    KEY idx_business_partner_itinerary_itinerary (itinerary_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO business_partner_categories (id, name, slug, description, sort_order, is_published)
VALUES (
    1,
    'Hotels & Accommodations',
    'hotels-accommodations',
    'Official hotel, villa, guesthouse, boutique stay, resort, and homestay partners for private Sri Lanka tours.',
    10,
    1
);
