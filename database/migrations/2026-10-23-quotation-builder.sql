-- Explicit operator-run migration; request handlers never run DDL.
CREATE TABLE IF NOT EXISTS quotations (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 reference VARCHAR(40) NULL UNIQUE,
 inquiry_id BIGINT UNSIGNED NULL,
 created_by BIGINT UNSIGNED NOT NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS quotation_revisions (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 quotation_id BIGINT UNSIGNED NOT NULL,
 revision INT UNSIGNED NOT NULL,
 state VARCHAR(20) NOT NULL DEFAULT 'draft',
 lock_version INT UNSIGNED NOT NULL DEFAULT 1,
 step TINYINT UNSIGNED NOT NULL DEFAULT 1,
 document_json LONGTEXT NOT NULL,
 assets_json LONGTEXT NULL,
 pdf_bytes LONGBLOB NULL,
 pdf_sha256 CHAR(64) NULL,
 issued_at DATETIME NULL,
 withdrawn_at DATETIME NULL,
 updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 UNIQUE KEY uq_quote_revision (quotation_id, revision),
 KEY idx_quote_state (state, updated_at),
 CHECK (JSON_VALID(document_json))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS quotation_library (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 kind VARCHAR(20) NOT NULL,
 name VARCHAR(190) NOT NULL,
 payload_json LONGTEXT NOT NULL,
 lock_version INT UNSIGNED NOT NULL DEFAULT 1,
 updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 UNIQUE KEY uq_quote_library (kind, name),
 CHECK (JSON_VALID(payload_json))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS quotation_events (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 revision_id BIGINT UNSIGNED NOT NULL,
 admin_id BIGINT UNSIGNED NOT NULL,
 action VARCHAR(40) NOT NULL,
 details_json LONGTEXT NOT NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 KEY idx_quote_events (revision_id, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS quotation_bookings (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 quotation_id BIGINT UNSIGNED NOT NULL UNIQUE,
 revision_id BIGINT UNSIGNED NOT NULL,
 option_key CHAR(1) NOT NULL,
 plan_id BIGINT UNSIGNED NOT NULL UNIQUE,
 services_sha256 CHAR(64) NOT NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS quotation_guest_acceptances (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 plan_id BIGINT UNSIGNED NOT NULL,
 revision_id BIGINT UNSIGNED NOT NULL,
 option_key CHAR(1) NOT NULL,
 customer_name VARCHAR(190) NOT NULL,
 channel VARCHAR(20) NOT NULL,
 evidence_reference VARCHAR(190) NOT NULL,
 accepted_at DATETIME NOT NULL,
 policy_snapshot_json LONGTEXT NOT NULL,
 policy_snapshot_hash CHAR(64) NOT NULL,
 services_sha256 CHAR(64) NOT NULL,
 admin_id BIGINT UNSIGNED NOT NULL,
 recorded_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 KEY idx_quote_guest_plan (plan_id, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS quotation_customer_acceptances (
 acceptance_id BIGINT UNSIGNED PRIMARY KEY,
 plan_id BIGINT UNSIGNED NOT NULL,
 revision_id BIGINT UNSIGNED NOT NULL,
 services_sha256 CHAR(64) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
ALTER TABLE customer_itinerary_plans MODIFY customer_user_id BIGINT UNSIGNED NULL;
SET @ict_quote_guest_column = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='contact_inquiries' AND COLUMN_NAME='admin_guest_only');
SET @ict_quote_sql = IF(@ict_quote_guest_column=0, 'ALTER TABLE contact_inquiries ADD COLUMN admin_guest_only TINYINT(1) NOT NULL DEFAULT 0', 'SELECT 1');
PREPARE ict_quote_stmt FROM @ict_quote_sql;
EXECUTE ict_quote_stmt;
DEALLOCATE PREPARE ict_quote_stmt;
-- Unsigned INT already supports Day 0; no renumbering or destructive day migration.
