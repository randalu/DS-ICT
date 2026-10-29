-- Enables the public under-construction notice for the image-replacement review window.
-- Admin can disable it later from Site Settings. Re-running this migration preserves admin changes.

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS site_settings (
    setting_key VARCHAR(120) PRIMARY KEY,
    setting_value LONGTEXT NULL,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO site_settings (setting_key, setting_value)
VALUES
    ('site_under_construction_notice_enabled', '1'),
    ('site_under_construction_notice_message', 'We are updating this website with final Sri Lanka travel photos. All pages remain available for review, and trip inquiries are still open.')
ON DUPLICATE KEY UPDATE setting_value = setting_value;
