-- Blog sidebar widgets support
-- MySQL 8.x compatible (no ADD COLUMN IF NOT EXISTS). Run once per database.

ALTER TABLE blog_posts
    ADD COLUMN sidebar_widgets_json LONGTEXT NULL AFTER canonical_url;
