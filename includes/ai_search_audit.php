<?php
declare(strict_types=1);

require_once __DIR__ . '/functions.php';

function ai_search_content_targets(): array
{
    return [
        ['key' => 'country_pages', 'label' => 'Top source-market country pages', 'target' => 20, 'href' => base_url('admin/countries.php'), 'query' => "SELECT COUNT(*) AS total FROM country_pages WHERE is_published = 1"],
        ['key' => 'destinations', 'label' => 'Destination guides', 'target' => 30, 'href' => base_url('admin/destinations.php'), 'query' => "SELECT COUNT(*) AS total FROM destinations WHERE is_published = 1"],
        ['key' => 'experiences', 'label' => 'Experience pages', 'target' => 16, 'href' => base_url('admin/experiences.php'), 'query' => "SELECT COUNT(*) AS total FROM experiences WHERE is_published = 1"],
        ['key' => 'itineraries', 'label' => 'Itinerary pages', 'target' => 12, 'href' => base_url('admin/itineraries.php'), 'query' => "SELECT COUNT(*) AS total FROM itineraries WHERE is_published = 1"],
        ['key' => 'tours', 'label' => 'Tour packages', 'target' => 7, 'href' => base_url('admin/tours.php'), 'query' => "SELECT COUNT(*) AS total FROM tours WHERE is_published = 1"],
        ['key' => 'scheduled_blog', 'label' => 'Scheduled blog posts', 'target' => 20, 'href' => base_url('admin/blog_posts.php'), 'query' => "SELECT COUNT(*) AS total FROM blog_posts WHERE is_published = 1 AND published_at > NOW()"],
    ];
}

function ai_search_content_target_status(): array
{
    $status = [];

    foreach (ai_search_content_targets() as $target) {
        $table = $target['key'] === 'scheduled_blog' ? 'blog_posts' : $target['key'];
        $value = db_table_exists((string) $table) ? (int) (fetch_one((string) $target['query'])['total'] ?? 0) : 0;
        $goal = max(1, (int) $target['target']);

        $target['value'] = $value;
        $target['percent'] = min(100, (int) round(($value / $goal) * 100));
        $target['complete'] = $value >= $goal;
        $status[] = $target;
    }

    return $status;
}

function ai_search_audit_specs(): array
{
    return [
        [
            'label' => 'Destinations',
            'table' => 'destinations',
            'title_column' => 'name',
            'edit_path' => 'admin/destination_form.php',
            'body_columns' => ['excerpt', 'intro', 'why_visit', 'things_to_do', 'how_to_get_there', 'suggested_itinerary', 'travel_tips'],
            'min_body_chars' => 900,
        ],
        [
            'label' => 'Experiences',
            'table' => 'experiences',
            'title_column' => 'title',
            'edit_path' => 'admin/experience_form.php',
            'body_columns' => ['excerpt', 'intro', 'where_to_experience', 'best_time', 'cta_text'],
            'min_body_chars' => 650,
        ],
        [
            'label' => 'Itineraries',
            'table' => 'itineraries',
            'title_column' => 'name',
            'edit_path' => 'admin/itinerary_form.php',
            'body_columns' => ['excerpt', 'intro', 'highlights', 'route_summary', 'best_for', 'travel_notes', 'pricing_notes'],
            'min_body_chars' => 850,
        ],
        [
            'label' => 'Tours',
            'table' => 'tours',
            'title_column' => 'name',
            'edit_path' => 'admin/tour_form.php',
            'body_columns' => ['excerpt', 'intro', 'highlights', 'sample_itinerary', 'pricing_notes', 'vehicle_options'],
            'min_body_chars' => 750,
        ],
        [
            'label' => 'Country pages',
            'table' => 'country_pages',
            'title_column' => 'country_name',
            'edit_path' => 'admin/country_form.php',
            'body_columns' => ['excerpt', 'intro', 'why_sri_lanka', 'recommended_itinerary', 'travel_info', 'best_time'],
            'min_body_chars' => 750,
        ],
        [
            'label' => 'Blog posts',
            'table' => 'blog_posts',
            'title_column' => 'title',
            'edit_path' => 'admin/blog_post_form.php',
            'body_columns' => ['excerpt', 'content'],
            'min_body_chars' => 1300,
            'scheduled_allowed' => true,
        ],
    ];
}

function ai_search_sql_text_expr(string $table, array $columns): string
{
    $parts = [];
    foreach ($columns as $column) {
        if (db_column_exists($table, (string) $column)) {
            $parts[] = 'COALESCE(' . (string) $column . ", '')";
        }
    }

    return $parts ? "CONCAT_WS(' ', " . implode(', ', $parts) . ')' : "''";
}

function ai_search_published_where(string $table, bool $includeScheduled = false): string
{
    $where = [];

    if (db_column_exists($table, 'is_published')) {
        $where[] = 'is_published = 1';
    }
    if ($table === 'blog_posts' && !$includeScheduled && db_column_exists('blog_posts', 'published_at')) {
        $where[] = '(published_at IS NULL OR published_at <= NOW())';
    }

    return $where ? implode(' AND ', $where) : '1=1';
}

function ai_search_issue_samples(array $spec, string $where, int $limit = 5): array
{
    $table = (string) $spec['table'];
    $titleColumn = (string) $spec['title_column'];
    $limit = max(1, min(20, $limit));

    return fetch_all(
        "SELECT id, slug, {$titleColumn} AS title
         FROM {$table}
         WHERE {$where}
         ORDER BY updated_at DESC, id DESC
         LIMIT {$limit}"
    );
}

function ai_search_add_issue(array &$issues, array $spec, string $severity, string $issue, int $count, string $where, int $sampleLimit): void
{
    if ($count <= 0) {
        return;
    }

    $issues[] = [
        'severity' => $severity,
        'area' => (string) $spec['label'],
        'issue' => $issue,
        'count' => $count,
        'edit_path' => (string) $spec['edit_path'],
        'samples' => ai_search_issue_samples($spec, $where, $sampleLimit),
    ];
}

function ai_search_quality_issues(int $sampleLimit = 5): array
{
    $issues = [];

    foreach (ai_search_audit_specs() as $spec) {
        $table = (string) $spec['table'];
        if (!db_table_exists($table)) {
            $issues[] = [
                'severity' => 'High',
                'area' => (string) $spec['label'],
                'issue' => 'Required content table is missing.',
                'count' => 1,
                'edit_path' => '',
                'samples' => [],
            ];
            continue;
        }

        $publishedWhere = ai_search_published_where($table, !empty($spec['scheduled_allowed']));
        $metaParts = [];
        foreach (['meta_title', 'meta_description'] as $column) {
            if (db_column_exists($table, $column)) {
                $metaParts[] = "{$column} IS NULL OR TRIM({$column}) = ''";
            } else {
                $metaParts[] = '1=1';
            }
        }
        $metaWhere = $publishedWhere . ' AND (' . implode(' OR ', $metaParts) . ')';
        $metaCount = (int) (fetch_one("SELECT COUNT(*) AS total FROM {$table} WHERE {$metaWhere}")['total'] ?? 0);
        ai_search_add_issue($issues, $spec, 'Medium', 'Published records missing SEO title or meta description.', $metaCount, $metaWhere, $sampleLimit);

        $textExpr = ai_search_sql_text_expr($table, $spec['body_columns']);
        $minChars = (int) $spec['min_body_chars'];
        $thinWhere = "{$publishedWhere} AND CHAR_LENGTH(TRIM({$textExpr})) < {$minChars}";
        $thinCount = (int) (fetch_one("SELECT COUNT(*) AS total FROM {$table} WHERE {$thinWhere}")['total'] ?? 0);
        ai_search_add_issue($issues, $spec, 'High', "Published records below {$minChars} core content characters.", $thinCount, $thinWhere, $sampleLimit);

        $imageParts = [];
        foreach (['hero_image', 'og_image', 'hero_media_asset_id'] as $column) {
            if (db_column_exists($table, $column)) {
                $imageParts[] = "({$column} IS NOT NULL AND TRIM(CAST({$column} AS CHAR)) <> '')";
            }
        }
        if ($imageParts) {
            $imageWhere = "{$publishedWhere} AND NOT (" . implode(' OR ', $imageParts) . ')';
            $imageCount = (int) (fetch_one("SELECT COUNT(*) AS total FROM {$table} WHERE {$imageWhere}")['total'] ?? 0);
            ai_search_add_issue($issues, $spec, 'Low', 'Published records missing hero or social image.', $imageCount, $imageWhere, $sampleLimit);
        }
    }

    if (db_table_exists('itineraries') && db_table_exists('itinerary_destination')) {
        $spec = [
            'label' => 'Internal links',
            'table' => 'itineraries',
            'title_column' => 'name',
            'edit_path' => 'admin/itinerary_form.php',
        ];
        $where = ai_search_published_where('itineraries') . ' AND NOT EXISTS (SELECT 1 FROM itinerary_destination idl WHERE idl.itinerary_id = itineraries.id)';
        $count = (int) (fetch_one("SELECT COUNT(*) AS total FROM itineraries WHERE {$where}")['total'] ?? 0);
        ai_search_add_issue($issues, $spec, 'High', 'Published itineraries without linked destinations.', $count, $where, $sampleLimit);
    }

    if (db_table_exists('experiences') && db_table_exists('experience_destination')) {
        $spec = [
            'label' => 'Internal links',
            'table' => 'experiences',
            'title_column' => 'title',
            'edit_path' => 'admin/experience_form.php',
        ];
        $where = ai_search_published_where('experiences') . ' AND NOT EXISTS (SELECT 1 FROM experience_destination ed WHERE ed.experience_id = experiences.id)';
        $count = (int) (fetch_one("SELECT COUNT(*) AS total FROM experiences WHERE {$where}")['total'] ?? 0);
        ai_search_add_issue($issues, $spec, 'Medium', 'Published experiences without linked destinations.', $count, $where, $sampleLimit);
    }

    return $issues;
}

function ai_search_issue_edit_url(array $issue, array $sample): string
{
    $editPath = trim((string) ($issue['edit_path'] ?? ''));
    if ($editPath === '') {
        return '#';
    }

    return base_url($editPath . '?id=' . (int) $sample['id']);
}

function ai_search_missing_image_queue(int $limitPerArea = 100): array
{
    $queue = [];
    $limitPerArea = max(1, min(250, $limitPerArea));

    foreach (ai_search_audit_specs() as $spec) {
        $table = (string) $spec['table'];
        if (!db_table_exists($table)) {
            continue;
        }

        $imageParts = [];
        foreach (['hero_image', 'og_image', 'hero_media_asset_id'] as $column) {
            if (db_column_exists($table, $column)) {
                $imageParts[] = "({$column} IS NOT NULL AND TRIM(CAST({$column} AS CHAR)) <> '')";
            }
        }
        if (!$imageParts) {
            continue;
        }

        $publishedWhere = ai_search_published_where($table, !empty($spec['scheduled_allowed']));
        $imageWhere = "{$publishedWhere} AND NOT (" . implode(' OR ', $imageParts) . ')';
        $count = (int) (fetch_one("SELECT COUNT(*) AS total FROM {$table} WHERE {$imageWhere}")['total'] ?? 0);
        if ($count <= 0) {
            continue;
        }

        $samples = ai_search_issue_samples($spec, $imageWhere, $limitPerArea);
        $queue[] = [
            'area' => (string) $spec['label'],
            'count' => $count,
            'edit_path' => (string) $spec['edit_path'],
            'records' => $samples,
        ];
    }

    return $queue;
}

function ai_search_placeholder_image_paths(): array
{
    return [
        'assets/images/placeholders/placeholder-destination.svg',
        'assets/images/placeholders/placeholder-experience.svg',
        'assets/images/placeholders/placeholder-itinerary.svg',
        'assets/images/placeholders/placeholder-tour.svg',
        'assets/images/placeholders/placeholder-country.svg',
        'assets/images/placeholders/placeholder-blog.svg',
    ];
}

function ai_search_placeholder_image_queue(int $limitPerArea = 100): array
{
    $queue = [];
    $limitPerArea = max(1, min(250, $limitPerArea));
    $quotedPaths = implode(', ', array_map(static fn(string $path): string => db()->quote($path), ai_search_placeholder_image_paths()));

    foreach (ai_search_audit_specs() as $spec) {
        $table = (string) $spec['table'];
        if (!db_table_exists($table)) {
            continue;
        }

        $placeholderParts = [];
        foreach (['hero_image', 'og_image'] as $column) {
            if (db_column_exists($table, $column)) {
                $placeholderParts[] = "{$column} IN ({$quotedPaths})";
            }
        }
        if (!$placeholderParts) {
            continue;
        }

        $publishedWhere = ai_search_published_where($table, !empty($spec['scheduled_allowed']));
        $placeholderWhere = "{$publishedWhere} AND (" . implode(' OR ', $placeholderParts) . ')';
        $count = (int) (fetch_one("SELECT COUNT(*) AS total FROM {$table} WHERE {$placeholderWhere}")['total'] ?? 0);
        if ($count <= 0) {
            continue;
        }

        $queue[] = [
            'area' => (string) $spec['label'],
            'count' => $count,
            'edit_path' => (string) $spec['edit_path'],
            'records' => ai_search_issue_samples($spec, $placeholderWhere, $limitPerArea),
        ];
    }

    return $queue;
}

function ai_search_next_blog_posts(int $limit = 20): array
{
    if (!db_table_exists('blog_posts')) {
        return [];
    }

    $limit = max(1, min(50, $limit));

    return fetch_all(
        "SELECT id, title, slug, published_at, is_published
         FROM blog_posts
         WHERE is_published = 1
           AND published_at > NOW()
         ORDER BY published_at ASC, id ASC
         LIMIT {$limit}"
    );
}
