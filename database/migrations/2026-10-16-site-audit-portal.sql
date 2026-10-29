-- Additive only: retain legacy runs/issues and SEO request/revision history.
-- MySQL and MariaDB compatible guards for older installations. The historical
-- progress migration uses MariaDB ADD COLUMN IF NOT EXISTS syntax.
SET @ict_audit_ddl = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='site_audit_runs' AND COLUMN_NAME='total_pages')=0,
 'ALTER TABLE site_audit_runs ADD COLUMN total_pages INT UNSIGNED NOT NULL DEFAULT 0', 'SELECT 1');
PREPARE ict_audit_stmt FROM @ict_audit_ddl;
EXECUTE ict_audit_stmt;
DEALLOCATE PREPARE ict_audit_stmt;
SET @ict_audit_ddl = IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='site_audit_runs' AND COLUMN_NAME='log_offset')=0,
 'ALTER TABLE site_audit_runs ADD COLUMN log_offset BIGINT UNSIGNED NOT NULL DEFAULT 0', 'SELECT 1');
PREPARE ict_audit_stmt FROM @ict_audit_ddl;
EXECUTE ict_audit_stmt;
DEALLOCATE PREPARE ict_audit_stmt;
CREATE TABLE IF NOT EXISTS site_audit_snapshots (
 run_id INT UNSIGNED NOT NULL PRIMARY KEY,
 profile VARCHAR(100) NOT NULL,
 base_url VARCHAR(2048) NOT NULL,
 inventory_json LONGTEXT NOT NULL,
 coverage_json LONGTEXT NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS site_audit_evidence (
 issue_id INT UNSIGNED NOT NULL PRIMARY KEY,
 run_id INT UNSIGNED NOT NULL,
 fingerprint CHAR(64) NOT NULL,
 certainty VARCHAR(20) NOT NULL,
 target_url VARCHAR(2048) NULL,
 source_location VARCHAR(300) NOT NULL DEFAULT '',
 evidence_json LONGTEXT NOT NULL,
 observed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 KEY idx_run (run_id), KEY idx_fingerprint (fingerprint)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS site_audit_observations (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 run_id INT UNSIGNED NOT NULL,
 observation_key CHAR(64) NOT NULL,
 kind VARCHAR(20) NOT NULL,
 page_url VARCHAR(2048) NOT NULL,
 data_json LONGTEXT NOT NULL,
 observed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 UNIQUE KEY idx_observation (run_id, observation_key), KEY idx_run_kind (run_id,kind)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS site_audit_triage (
 fingerprint CHAR(64) NOT NULL PRIMARY KEY,
 state VARCHAR(20) NOT NULL,
 reason VARCHAR(1000) NOT NULL DEFAULT '',
 admin_id INT UNSIGNED NOT NULL,
 updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS site_audit_reviews (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 run_id INT UNSIGNED NOT NULL,
 admin_id INT UNSIGNED NOT NULL,
 model VARCHAR(100) NOT NULL,
 context_hash CHAR(64) NOT NULL,
 result_json LONGTEXT NOT NULL,
 created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 KEY idx_run (run_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
