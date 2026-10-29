-- Persist crawler totals and the imported JSONL position between polling requests.
-- IF NOT EXISTS keeps each column independently idempotent when an older
-- installation already has only part of this progress schema.
ALTER TABLE site_audit_runs
    ADD COLUMN IF NOT EXISTS total_pages INT UNSIGNED NOT NULL DEFAULT 0 AFTER pages_crawled,
    ADD COLUMN IF NOT EXISTS log_offset BIGINT UNSIGNED NOT NULL DEFAULT 0 AFTER issue_count,
    ADD COLUMN IF NOT EXISTS triggered_by VARCHAR(100) NOT NULL DEFAULT 'admin' AFTER issue_count;
