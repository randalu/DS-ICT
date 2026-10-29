-- Customer account area with Google login and inquiry messaging.
-- Safe to run more than once.

DROP PROCEDURE IF EXISTS ict_add_column_if_missing;
DROP PROCEDURE IF EXISTS ict_add_index_if_missing;

DELIMITER //

CREATE PROCEDURE ict_add_column_if_missing(
    IN p_table VARCHAR(64),
    IN p_column VARCHAR(64),
    IN p_definition TEXT
)
BEGIN
    IF NOT EXISTS (
        SELECT 1
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = p_table
          AND COLUMN_NAME = p_column
    ) THEN
        SET @sql = CONCAT('ALTER TABLE `', p_table, '` ADD COLUMN `', p_column, '` ', p_definition);
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END//

CREATE PROCEDURE ict_add_index_if_missing(
    IN p_table VARCHAR(64),
    IN p_index VARCHAR(64),
    IN p_definition TEXT
)
BEGIN
    IF NOT EXISTS (
        SELECT 1
        FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = p_table
          AND INDEX_NAME = p_index
    ) THEN
        SET @sql = CONCAT('ALTER TABLE `', p_table, '` ADD INDEX `', p_index, '` ', p_definition);
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END//

DELIMITER ;

CREATE TABLE IF NOT EXISTS customer_users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    google_sub VARCHAR(191) NOT NULL,
    email VARCHAR(190) NOT NULL,
    email_verified TINYINT(1) NOT NULL DEFAULT 0,
    name VARCHAR(190) NULL,
    avatar_url VARCHAR(500) NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'active',
    last_login_at DATETIME NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_customer_users_google_sub (google_sub),
    KEY idx_customer_users_email (email),
    KEY idx_customer_users_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CALL ict_add_column_if_missing('contact_inquiries', 'customer_user_id', 'BIGINT UNSIGNED NULL AFTER `id`');
CALL ict_add_index_if_missing('contact_inquiries', 'idx_contact_inquiries_customer_user', '(`customer_user_id`)');

CREATE TABLE IF NOT EXISTS contact_inquiry_messages (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    inquiry_id BIGINT UNSIGNED NOT NULL,
    customer_user_id BIGINT UNSIGNED NULL,
    admin_user_id BIGINT UNSIGNED NULL,
    sender_type VARCHAR(20) NOT NULL,
    message_body TEXT NOT NULL,
    is_visible_to_customer TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_contact_inquiry_messages_inquiry (inquiry_id, created_at),
    KEY idx_contact_inquiry_messages_customer (customer_user_id, created_at),
    KEY idx_contact_inquiry_messages_admin (admin_user_id, created_at),
    KEY idx_contact_inquiry_messages_visible (is_visible_to_customer)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS analytics_settings (
    setting_key VARCHAR(120) PRIMARY KEY,
    setting_value LONGTEXT NULL,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO analytics_settings (setting_key, setting_value)
SELECT 'customer_google_login_enabled', '0'
WHERE NOT EXISTS (SELECT 1 FROM analytics_settings WHERE setting_key = 'customer_google_login_enabled');

INSERT INTO analytics_settings (setting_key, setting_value)
SELECT 'customer_google_client_id', ''
WHERE NOT EXISTS (SELECT 1 FROM analytics_settings WHERE setting_key = 'customer_google_client_id');

INSERT INTO analytics_settings (setting_key, setting_value)
SELECT 'customer_google_client_secret', ''
WHERE NOT EXISTS (SELECT 1 FROM analytics_settings WHERE setting_key = 'customer_google_client_secret');

DROP PROCEDURE IF EXISTS ict_add_column_if_missing;
DROP PROCEDURE IF EXISTS ict_add_index_if_missing;
