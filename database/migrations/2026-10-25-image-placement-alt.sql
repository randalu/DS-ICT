-- Independent descriptions for legacy image slots without a native alt column.
CREATE TABLE IF NOT EXISTS media_placement_alts (
 content_type VARCHAR(24) NOT NULL,
 content_id BIGINT UNSIGNED NOT NULL,
 slot VARCHAR(40) NOT NULL,
 child_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
 image_path VARCHAR(255) NOT NULL,
 alt_text VARCHAR(255) NOT NULL,
 updated_at DATETIME NOT NULL,
 PRIMARY KEY(content_type,content_id,slot,child_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
