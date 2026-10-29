-- Release 0: versioned policy acceptance for the customer itinerary boundary.
-- This migration adds storage only. Acceptance is collected only after the
-- admin review sets booking_status=awaiting_acceptance in application code.

SET NAMES utf8mb4;

SET @ict_table_exists = (
    SELECT COUNT(*)
    FROM information_schema.tables
    WHERE table_schema = DATABASE()
      AND table_name = 'customer_itinerary_plans'
);

SET @ict_sql = IF(
    @ict_table_exists = 1,
    'ALTER TABLE customer_itinerary_plans ADD COLUMN IF NOT EXISTS booking_status VARCHAR(30) NOT NULL DEFAULT ''planning'', ADD COLUMN IF NOT EXISTS booking_terms_version VARCHAR(40) NULL, ADD COLUMN IF NOT EXISTS booking_terms_accepted_at DATETIME NULL, ADD COLUMN IF NOT EXISTS cancellation_policy_version VARCHAR(40) NULL, ADD COLUMN IF NOT EXISTS cancellation_policy_accepted_at DATETIME NULL, ADD COLUMN IF NOT EXISTS payment_policy_version VARCHAR(40) NULL, ADD COLUMN IF NOT EXISTS privacy_policy_version VARCHAR(40) NULL, ADD COLUMN IF NOT EXISTS marketing_consent TINYINT(1) NOT NULL DEFAULT 0, ADD COLUMN IF NOT EXISTS marketing_consent_at DATETIME NULL, ADD COLUMN IF NOT EXISTS customer_ip VARCHAR(45) NULL, ADD COLUMN IF NOT EXISTS customer_user_agent VARCHAR(512) NULL, ADD COLUMN IF NOT EXISTS policy_snapshot_hash CHAR(64) NULL, ADD COLUMN IF NOT EXISTS policy_snapshot_json LONGTEXT NULL, ADD COLUMN IF NOT EXISTS policy_accepted_at DATETIME NULL, ADD COLUMN IF NOT EXISTS booking_confirmed_at DATETIME NULL, ADD COLUMN IF NOT EXISTS booking_confirmed_by_admin_user_id BIGINT UNSIGNED NULL',
    'SELECT 1'
);
PREPARE ict_stmt FROM @ict_sql;
EXECUTE ict_stmt;
DEALLOCATE PREPARE ict_stmt;

CREATE TABLE IF NOT EXISTS customer_policy_acceptances (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    plan_id BIGINT UNSIGNED NOT NULL,
    customer_user_id BIGINT UNSIGNED NOT NULL,
    booking_terms_version VARCHAR(40) NOT NULL,
    booking_terms_accepted_at DATETIME NOT NULL,
    cancellation_policy_version VARCHAR(40) NOT NULL,
    cancellation_policy_accepted_at DATETIME NOT NULL,
    payment_policy_version VARCHAR(40) NOT NULL,
    privacy_policy_version VARCHAR(40) NOT NULL,
    marketing_consent TINYINT(1) NOT NULL DEFAULT 0,
    marketing_consent_at DATETIME NULL,
    customer_ip VARCHAR(45) NULL,
    customer_user_agent VARCHAR(512) NULL,
    policy_snapshot_hash CHAR(64) NOT NULL,
    policy_snapshot_json LONGTEXT NOT NULL,
    accepted_at DATETIME NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_policy_acceptances_plan (plan_id, accepted_at),
    KEY idx_policy_acceptances_customer (customer_user_id, accepted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
