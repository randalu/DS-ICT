-- Growth suite migration: inquiry CRM, analytics config, social media assets/content manager
-- MySQL 8.x compatible (no ADD COLUMN IF NOT EXISTS). Run once per database.

SET FOREIGN_KEY_CHECKS = 0;

ALTER TABLE contact_inquiries
    ADD COLUMN preferred_itinerary_id BIGINT UNSIGNED NULL AFTER interests;
ALTER TABLE contact_inquiries
    ADD COLUMN preferred_itinerary_name VARCHAR(180) NULL AFTER preferred_itinerary_id;
ALTER TABLE contact_inquiries
    ADD COLUMN whatsapp VARCHAR(50) NULL AFTER phone;
ALTER TABLE contact_inquiries
    ADD COLUMN adults_count INT UNSIGNED NULL AFTER group_size;
ALTER TABLE contact_inquiries
    ADD COLUMN children_count INT UNSIGNED NULL AFTER adults_count;
ALTER TABLE contact_inquiries
    ADD COLUMN budget_min DECIMAL(10,2) NULL AFTER children_count;
ALTER TABLE contact_inquiries
    ADD COLUMN budget_max DECIMAL(10,2) NULL AFTER budget_min;
ALTER TABLE contact_inquiries
    ADD COLUMN budget_currency CHAR(3) NULL AFTER budget_max;
ALTER TABLE contact_inquiries
    ADD COLUMN accommodation_preference VARCHAR(120) NULL AFTER budget_currency;
ALTER TABLE contact_inquiries
    ADD COLUMN inquiry_status VARCHAR(30) NOT NULL DEFAULT 'new' AFTER source_page;
ALTER TABLE contact_inquiries
    ADD COLUMN admin_notes LONGTEXT NULL AFTER inquiry_status;
ALTER TABLE contact_inquiries
    ADD COLUMN is_spam TINYINT(1) NOT NULL DEFAULT 0 AFTER admin_notes;
ALTER TABLE contact_inquiries
    ADD COLUMN spam_reason VARCHAR(255) NULL AFTER is_spam;
ALTER TABLE contact_inquiries
    ADD COLUMN follow_up_at DATETIME NULL AFTER spam_reason;
ALTER TABLE contact_inquiries
    ADD COLUMN last_contacted_at DATETIME NULL AFTER follow_up_at;
ALTER TABLE contact_inquiries
    ADD COLUMN admin_updated_at DATETIME NULL AFTER last_contacted_at;
ALTER TABLE contact_inquiries
    ADD COLUMN email_sent_to_customer TINYINT(1) NOT NULL DEFAULT 0 AFTER admin_updated_at;
ALTER TABLE contact_inquiries
    ADD COLUMN email_sent_to_admin TINYINT(1) NOT NULL DEFAULT 0 AFTER email_sent_to_customer;
ALTER TABLE contact_inquiries
    ADD COLUMN tracking_meta_json LONGTEXT NULL AFTER email_sent_to_admin;

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

INSERT IGNORE INTO analytics_event_types (event_key, label, description, is_active, sort_order) VALUES
('whatsapp_click', 'WhatsApp Click', 'User clicked a WhatsApp contact link.', 1, 1),
('phone_click', 'Phone Click', 'User clicked a tel: phone link.', 1, 2),
('email_click', 'Email Click', 'User clicked a mailto: link.', 1, 3),
('contact_cta_click', 'Contact CTA Click', 'User clicked a primary contact/quote CTA.', 1, 4),
('sticky_cta_click', 'Sticky CTA Click', 'User clicked a sticky mobile CTA action (WhatsApp/Call/Quote).', 1, 5),
('external_link_click', 'External Link Click', 'User clicked a non-site external URL.', 1, 6);

INSERT IGNORE INTO analytics_settings (setting_key, setting_value) VALUES
('events_retention_days', '365');

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
