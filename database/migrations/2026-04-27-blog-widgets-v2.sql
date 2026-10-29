-- Blog widget v2 — global defaults table.
-- Per-post widgets remain in blog_posts.sidebar_widgets_json (now in v2 shape).
-- See decode_blog_sidebar_widgets() for the v1->v2 upcast logic.

CREATE TABLE blog_widget_defaults (
    id TINYINT UNSIGNED PRIMARY KEY,
    config_json JSON NOT NULL,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO blog_widget_defaults (id, config_json) VALUES
    (1, JSON_OBJECT('version', 2, 'widgets', JSON_ARRAY()));
