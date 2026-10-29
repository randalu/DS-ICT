-- Additive metadata only. No secrets, image bytes or provider payloads.
CREATE TABLE IF NOT EXISTS admin_ai_requests (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 admin_id BIGINT UNSIGNED NOT NULL,
 request_token VARCHAR(64) NOT NULL,
 feature VARCHAR(16) NOT NULL,
 model VARCHAR(100) NOT NULL,
 status VARCHAR(24) NOT NULL,
 input_tokens INT UNSIGNED NOT NULL DEFAULT 0,
 output_tokens INT UNSIGNED NOT NULL DEFAULT 0,
 created_at DATETIME NOT NULL,
 finished_at DATETIME NULL,
 UNIQUE KEY admin_ai_once(admin_id,request_token),
 KEY admin_ai_usage(feature,created_at,admin_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS media_asset_alt_revisions (
 media_asset_id BIGINT UNSIGNED NOT NULL PRIMARY KEY,
 version BIGINT UNSIGNED NOT NULL DEFAULT 0,
 updated_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
