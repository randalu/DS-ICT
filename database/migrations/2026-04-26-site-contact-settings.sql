-- Site contact settings store (admin-editable phones/email/whatsapp)
-- Safe to run multiple times on MySQL 8.0+ / MariaDB 10.5+

CREATE TABLE IF NOT EXISTS site_settings (
    setting_key VARCHAR(120) PRIMARY KEY,
    setting_value LONGTEXT NULL,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO site_settings (setting_key, setting_value)
SELECT 'contact_phone_primary', '+94 76 919 2427'
WHERE NOT EXISTS (
    SELECT 1 FROM site_settings WHERE setting_key = 'contact_phone_primary'
);

INSERT INTO site_settings (setting_key, setting_value)
SELECT 'contact_phone_secondary', '+94 77 949 2427'
WHERE NOT EXISTS (
    SELECT 1 FROM site_settings WHERE setting_key = 'contact_phone_secondary'
);

INSERT INTO site_settings (setting_key, setting_value)
SELECT 'contact_email', 'hello@islandculturetravels.com'
WHERE NOT EXISTS (
    SELECT 1 FROM site_settings WHERE setting_key = 'contact_email'
);

INSERT INTO site_settings (setting_key, setting_value)
SELECT 'contact_whatsapp_number', '+94 76 919 2427'
WHERE NOT EXISTS (
    SELECT 1 FROM site_settings WHERE setting_key = 'contact_whatsapp_number'
);

INSERT INTO site_settings (setting_key, setting_value)
SELECT 'contact_whatsapp_default_text', 'Hello Island Culture Travels, I would like to plan a Sri Lanka tour.'
WHERE NOT EXISTS (
    SELECT 1 FROM site_settings WHERE setting_key = 'contact_whatsapp_default_text'
);

