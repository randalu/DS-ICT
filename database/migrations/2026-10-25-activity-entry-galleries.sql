-- Additive, rerunnable: no existing activity or media records are changed.
CREATE TABLE IF NOT EXISTS activity_example_images (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    activity_example_id BIGINT UNSIGNED NOT NULL,
    media_asset_id BIGINT UNSIGNED NOT NULL,
    alt_text VARCHAR(255) NOT NULL,
    sort_order INT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_activity_example_asset (activity_example_id, media_asset_id),
    KEY idx_activity_example_image_order (activity_example_id, sort_order, id),
    KEY idx_activity_example_image_asset (media_asset_id),
    CONSTRAINT fk_activity_example_image_entry FOREIGN KEY (activity_example_id)
        REFERENCES activity_examples(id) ON DELETE CASCADE,
    CONSTRAINT fk_activity_example_image_asset FOREIGN KEY (media_asset_id)
        REFERENCES media_assets(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
