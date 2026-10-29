-- Additive SEO workspace only. Run explicitly; never installed by a web request.
CREATE TABLE IF NOT EXISTS seo_plans (
 content_type VARCHAR(24) NOT NULL, content_id BIGINT UNSIGNED NOT NULL,
 plan_json LONGTEXT NOT NULL, version INT UNSIGNED NOT NULL DEFAULT 1,
 reviewed_at DATETIME NULL, updated_at DATETIME NOT NULL,
 PRIMARY KEY(content_type, content_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS seo_guidance (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, admin_id BIGINT UNSIGNED NOT NULL,
 guidance_json LONGTEXT NOT NULL, created_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS seo_briefs (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, admin_id BIGINT UNSIGNED NOT NULL,
 kind VARCHAR(20) NOT NULL, content_type VARCHAR(24) NOT NULL, content_id BIGINT UNSIGNED NOT NULL,
 market VARCHAR(100) NOT NULL, brief_json LONGTEXT NOT NULL, created_at DATETIME NOT NULL,
 KEY seo_brief_lookup(kind,content_type,content_id,created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS seo_suggestions (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, admin_id BIGINT UNSIGNED NOT NULL,
 content_type VARCHAR(24) NOT NULL, content_id BIGINT UNSIGNED NOT NULL, field_name VARCHAR(40) NOT NULL,
 source_revision CHAR(64) NOT NULL, policy_version VARCHAR(40) NOT NULL, guidance_id BIGINT UNSIGNED NULL,
 model VARCHAR(60) NOT NULL, suggestion_json LONGTEXT NOT NULL, created_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS seo_revisions (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, admin_id BIGINT UNSIGNED NOT NULL,
 content_type VARCHAR(24) NOT NULL, content_id BIGINT UNSIGNED NOT NULL,
 before_json LONGTEXT NOT NULL, after_json LONGTEXT NOT NULL, policy_version VARCHAR(40) NOT NULL,
 provenance_json LONGTEXT NOT NULL, created_at DATETIME NOT NULL,
 KEY seo_revision_lookup(content_type,content_id,id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
CREATE TABLE IF NOT EXISTS seo_requests (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, admin_id BIGINT UNSIGNED NOT NULL,
 request_token VARCHAR(64) NOT NULL, kind VARCHAR(20) NOT NULL, status VARCHAR(24) NOT NULL,
 input_tokens INT UNSIGNED NOT NULL DEFAULT 0, output_tokens INT UNSIGNED NOT NULL DEFAULT 0,
 created_at DATETIME NOT NULL, finished_at DATETIME NULL,
 UNIQUE KEY seo_request_once(admin_id,request_token), KEY seo_usage(admin_id,created_at,kind)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
