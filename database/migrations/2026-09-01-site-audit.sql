CREATE TABLE IF NOT EXISTS site_audit_runs (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    started_at    DATETIME NOT NULL,
    finished_at   DATETIME NULL,
    status        ENUM('running','done','failed') NOT NULL DEFAULT 'running',
    pages_crawled INT UNSIGNED NOT NULL DEFAULT 0,
    issue_count   INT UNSIGNED NOT NULL DEFAULT 0,
    triggered_by  VARCHAR(100) NOT NULL DEFAULT 'admin'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS site_audit_issues (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    run_id      INT UNSIGNED NOT NULL,
    severity    ENUM('critical','high','medium','low') NOT NULL,
    type        VARCHAR(100) NOT NULL,
    page_url    VARCHAR(2048) NOT NULL,
    detail      TEXT NULL,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_run_id   (run_id),
    INDEX idx_severity (severity),
    INDEX idx_type     (type(40))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
