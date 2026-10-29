CREATE TABLE IF NOT EXISTS analytics_settings (
    setting_key VARCHAR(120) PRIMARY KEY,
    setting_value LONGTEXT NULL,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO analytics_settings (setting_key, setting_value)
VALUES
    ('google_tag_manager_id', 'GTM-56SFZG95'),
    ('google_tag_manager_enabled', '1')
ON DUPLICATE KEY UPDATE
    setting_value = VALUES(setting_value);
