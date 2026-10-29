-- Additive and rerunnable. Apply explicitly after reviewing the release.
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
