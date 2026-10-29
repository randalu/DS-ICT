<?php
declare(strict_types=1);

/**
 * Related-content fetchers.
 *
 * Consolidates the inline closures in blog_post.php and the per-page SQL in
 * destination.php / itinerary.php / country.php. Manual id pinning always wins;
 * remaining slots fill from category/featured fallbacks.
 */

/**
 * Related blog posts.
 *  - $manualIds first (in supplied order)
 *  - then same-category newest, excluding $excludePostId
 *  - then global newest fallback
 */
function fetch_related_posts(int $excludePostId, ?int $categoryId, int $limit = 4, array $manualIds = []): array
{
    $limit = max(1, min(20, $limit));
    $manual = related_unique_positive_ids($manualIds);
    $picked = [$excludePostId];
    $results = [];

    if ($manual) {
        [$ph, $params] = related_in_params($manual, 'mp');
        $rows = fetch_all(
            "SELECT id, title, slug, published_at, hero_image
             FROM blog_posts
             WHERE is_published = 1
               AND id IN (" . implode(',', $ph) . ")",
            $params
        );
        $byId = [];
        foreach ($rows as $row) { $byId[(int) $row['id']] = $row; }
        foreach ($manual as $id) {
            if (isset($byId[$id])) {
                $results[] = $byId[$id];
                $picked[] = $id;
                if (count($results) >= $limit) {
                    return $results;
                }
            }
        }
    }

    if ($categoryId !== null && count($results) < $limit) {
        [$ph, $params] = related_in_params($picked, 'pp');
        $params['cat'] = $categoryId;
        $params['rem'] = $limit - count($results);
        $rows = fetch_all(
            "SELECT id, title, slug, published_at, hero_image
             FROM blog_posts
             WHERE is_published = 1
               AND category_id = :cat
               AND id NOT IN (" . implode(',', $ph) . ")
             ORDER BY COALESCE(published_at, created_at) DESC
             LIMIT " . (int) $params['rem'],
            ['cat' => $categoryId] + array_diff_key($params, ['rem' => 1])
        );
        foreach ($rows as $row) {
            $results[] = $row;
            $picked[] = (int) $row['id'];
        }
    }

    if (count($results) < $limit) {
        [$ph, $params] = related_in_params($picked, 'pp');
        $rem = $limit - count($results);
        $rows = fetch_all(
            "SELECT id, title, slug, published_at, hero_image
             FROM blog_posts
             WHERE is_published = 1
               AND id NOT IN (" . implode(',', $ph) . ")
             ORDER BY COALESCE(published_at, created_at) DESC
             LIMIT " . (int) $rem,
            $params
        );
        foreach ($rows as $row) {
            $results[] = $row;
        }
    }

    return $results;
}

/**
 * Related itineraries.
 *  - $manualIds first
 *  - then optional category match
 *  - then featured / sort_order fallback
 */
function fetch_related_itineraries(int $excludeId = 0, ?int $categoryId = null, int $limit = 6, array $manualIds = []): array
{
    $limit = max(1, min(20, $limit));
    $manual = related_unique_positive_ids($manualIds);
    $picked = $excludeId > 0 ? [$excludeId] : [];
    $results = [];

    if ($manual) {
        [$ph, $params] = related_in_params($manual, 'm');
        $rows = fetch_all(
            "SELECT id, name, slug, days, excerpt, hero_image, og_image, price_from, price_to, price_currency
             FROM itineraries
             WHERE is_published = 1
               AND id IN (" . implode(',', $ph) . ")",
            $params
        );
        $byId = [];
        foreach ($rows as $row) { $byId[(int) $row['id']] = $row; }
        foreach ($manual as $id) {
            if (isset($byId[$id])) {
                $results[] = $byId[$id];
                $picked[] = $id;
                if (count($results) >= $limit) {
                    return $results;
                }
            }
        }
    }

    if ($categoryId !== null && db_column_exists('itineraries', 'category_id') && count($results) < $limit) {
        [$ph, $params] = related_in_params($picked ?: [0], 'c');
        $params['cat'] = $categoryId;
        $rem = $limit - count($results);
        $rows = fetch_all(
            "SELECT id, name, slug, days, excerpt, hero_image, og_image, price_from, price_to, price_currency
             FROM itineraries
             WHERE is_published = 1
               AND category_id = :cat
               AND id NOT IN (" . implode(',', $ph) . ")
             ORDER BY is_featured DESC, sort_order ASC, days ASC
             LIMIT " . (int) $rem,
            $params
        );
        foreach ($rows as $row) {
            $results[] = $row;
            $picked[] = (int) $row['id'];
        }
    }

    if (count($results) < $limit) {
        [$ph, $params] = related_in_params($picked ?: [0], 'f');
        $rem = $limit - count($results);
        $rows = fetch_all(
            "SELECT id, name, slug, days, excerpt, hero_image, og_image, price_from, price_to, price_currency
             FROM itineraries
             WHERE is_published = 1
               AND id NOT IN (" . implode(',', $ph) . ")
             ORDER BY is_featured DESC, sort_order ASC, days ASC
             LIMIT " . (int) $rem,
            $params
        );
        foreach ($rows as $row) {
            $results[] = $row;
        }
    }

    return $results;
}

/**
 * Related destinations.
 *  - $manualIds first
 *  - then featured / sort_order fallback
 */
function fetch_related_destinations(int $excludeId = 0, int $limit = 6, array $manualIds = []): array
{
    $limit = max(1, min(20, $limit));
    $manual = related_unique_positive_ids($manualIds);
    $picked = $excludeId > 0 ? [$excludeId] : [];
    $results = [];

    if ($manual) {
        [$ph, $params] = related_in_params($manual, 'm');
        $rows = fetch_all(
            "SELECT id, name, slug, excerpt, cover_image, hero_image, og_image
             FROM destinations
             WHERE is_published = 1
               AND id IN (" . implode(',', $ph) . ")",
            $params
        );
        $byId = [];
        foreach ($rows as $row) { $byId[(int) $row['id']] = $row; }
        foreach ($manual as $id) {
            if (isset($byId[$id])) {
                $results[] = $byId[$id];
                $picked[] = $id;
                if (count($results) >= $limit) {
                    return $results;
                }
            }
        }
    }

    if (count($results) < $limit) {
        [$ph, $params] = related_in_params($picked ?: [0], 'f');
        $rem = $limit - count($results);
        $rows = fetch_all(
            "SELECT id, name, slug, excerpt, cover_image, hero_image, og_image
             FROM destinations
             WHERE is_published = 1
               AND id NOT IN (" . implode(',', $ph) . ")
             ORDER BY is_featured DESC, sort_order ASC, name ASC
             LIMIT " . (int) $rem,
            $params
        );
        foreach ($rows as $row) {
            $results[] = $row;
        }
    }

    return $results;
}

/**
 * Related tour categories.
 */
function fetch_related_tours(int $excludeId = 0, int $limit = 6): array
{
    if (!db_table_exists('itinerary_categories')) {
        return [];
    }
    $limit = max(1, min(20, $limit));
    $picked = $excludeId > 0 ? [$excludeId] : [0];
    [$ph, $params] = related_in_params($picked, 't');
    return fetch_all(
        "SELECT id, name, slug, hero_image, og_image
         FROM itinerary_categories
         WHERE id NOT IN (" . implode(',', $ph) . ")"
         . (db_column_exists('itinerary_categories', 'is_published') ? ' AND is_published = 1' : '') .
         " ORDER BY sort_order ASC, name ASC
         LIMIT " . (int) $limit,
        $params
    );
}

// ─── helpers ──────────────────────────────────────────────────────────────

/**
 * Seasonal helpers: destinations/itineraries that are "best in" a given month.
 * Month value must be lowercase English month name, e.g. "april".
 */
function fetch_destinations_for_month(string $month, int $limit = 12): array
{
    $month = strtolower(trim($month));
    if ($month === '') {
        return [];
    }

    $limit = max(1, min(48, $limit));

    if (!db_column_exists('destinations', 'best_time_json')) {
        return [];
    }

    return fetch_all(
        "SELECT id, name, slug, excerpt, cover_image, hero_image, og_image, best_time_json, " . content_image_select_fragment('destinations') . "
         FROM destinations
         WHERE is_published = 1
           AND best_time_json IS NOT NULL
           AND JSON_CONTAINS(best_time_json, JSON_QUOTE(:month), '$.months')
         ORDER BY is_featured DESC, sort_order ASC, name ASC
         LIMIT " . (int) $limit,
        ['month' => $month]
    );
}

function fetch_itineraries_for_month(string $month, int $limit = 12): array
{
    $month = strtolower(trim($month));
    if ($month === '') {
        return [];
    }

    $limit = max(1, min(48, $limit));

    if (!db_column_exists('itineraries', 'best_months_json')) {
        return [];
    }

    return fetch_all(
        "SELECT id, name, slug, days, excerpt, hero_image, og_image, price_from, price_to, price_currency, best_months_json
         FROM itineraries
         WHERE is_published = 1
           AND best_months_json IS NOT NULL
           AND JSON_CONTAINS(best_months_json, JSON_QUOTE(:month), '$.months')
         ORDER BY is_featured DESC, sort_order ASC, days ASC
         LIMIT " . (int) $limit,
        ['month' => $month]
    );
}

function related_unique_positive_ids(array $values): array
{
    return array_values(array_unique(array_filter(
        array_map('intval', $values),
        static fn(int $id): bool => $id > 0
    )));
}

function related_in_params(array $ids, string $prefix): array
{
    $placeholders = [];
    $params = [];
    if (!$ids) {
        $ids = [0];
    }
    foreach ($ids as $i => $id) {
        $key = $prefix . $i;
        $placeholders[] = ':' . $key;
        $params[$key] = $id;
    }
    return [$placeholders, $params];
}
