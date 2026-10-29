-- Seasonal guides admin table (month-by-month content for /sri-lanka-in-{month})
-- Compatible with MySQL 8.x and MariaDB 10.x

CREATE TABLE IF NOT EXISTS seasonal_guides (
    month_key VARCHAR(20) PRIMARY KEY,
    title VARCHAR(180) NULL,
    intro TEXT NULL,
    highlights_json LONGTEXT NULL,
    best_regions_json LONGTEXT NULL,
    avoid_regions_json LONGTEXT NULL,
    meta_title VARCHAR(180) NULL,
    meta_description VARCHAR(300) NULL,
    hero_image VARCHAR(255) NULL,
    is_published TINYINT(1) NOT NULL DEFAULT 1,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

