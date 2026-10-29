-- Public Business Partners feature toggle.
-- Default enabled. Disabling this setting hides public partner pages/links without changing partner records.

INSERT INTO site_settings (setting_key, setting_value)
VALUES ('business_partners_public_enabled', '1')
ON DUPLICATE KEY UPDATE setting_value = setting_value;
