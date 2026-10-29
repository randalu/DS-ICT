<?php
declare(strict_types=1);

namespace ICT\Content;

use ICT\Support\ContentNormalizer;
use PDO;
use Throwable;

/**
 * Read-only queries for the public home page.
 *
 * Rules for this layer:
 *  - prepared statements only, no string interpolation of user input
 *  - LIMIT values are integers clamped locally before interpolation
 *  - a failing query logs and returns an empty list; the page decides what to
 *    do with missing content instead of throwing a 500
 *  - results are memoised per request, so a page can ask twice for free
 */
final class HomeRepository
{
    /** @var array<string,list<array<string,mixed>>> */
    private array $memo = [];

    public function __construct(private ?PDO $pdo = null)
    {
    }

    private function db(): PDO
    {
        return $this->pdo ??= pdo();
    }

    /**
     * Homepage CMS settings and hero, delegated to the existing manager
     * helpers so editors keep the same admin screen during the migration.
     *
     * @return array{settings:array<string,string>,hero:array<string,string>,sections:list<string>}
     */
    public function homepage(): array
    {
        $defaults = [
            'settings' => [],
            'hero' => [],
            'sections' => [],
        ];

        try {
            if (function_exists('ensure_homepage_manager_schema')) {
                ensure_homepage_manager_schema();
            }

            return [
                'settings' => function_exists('homepage_new_settings') ? (array) homepage_new_settings() : [],
                'hero' => function_exists('homepage_new_active_hero') ? (array) homepage_new_active_hero() : [],
                'sections' => function_exists('homepage_new_active_sections') ? (array) homepage_new_active_sections() : [],
            ];
        } catch (Throwable $e) {
            error_log('[ICT home] homepage settings: ' . $e->getMessage());

            return $defaults;
        }
    }

    /**
     * Featured destinations first, then the rest of the published catalogue.
     *
     * @return list<array<string,mixed>>
     */
    public function destinations(int $limit = 4): array
    {
        $limit = max(1, min(24, $limit));

        $rows = $this->rows('destinations', sprintf(
            'SELECT id, name, slug, excerpt, best_known_for, region_label, province,
                    stay_duration, destination_type, hero_image, cover_image, og_image,
                    hero_alt, cover_alt, og_alt
             FROM destinations
             WHERE is_published = 1
             ORDER BY is_featured DESC, sort_order ASC, name ASC
             LIMIT %d',
            $limit
        ));

        return array_map(static fn(array $row): array => ContentNormalizer::destination($row), $rows);
    }

    /**
     * @return list<array<string,mixed>>
     */
    public function journeys(int $limit = 3): array
    {
        $limit = max(1, min(24, $limit));

        $rows = $this->rows('journeys', sprintf(
            'SELECT id, name, slug, excerpt, days, traveller_types,
                    price_from, price_currency, price_unit,
                    hero_image, cover_image, og_image, hero_alt, cover_alt, og_alt
             FROM itineraries
             WHERE is_published = 1
             ORDER BY is_featured DESC, sort_order ASC, days ASC
             LIMIT %d',
            $limit
        ));

        return array_map(static fn(array $row): array => ContentNormalizer::journey($row), $rows);
    }

    /**
     * @return list<array<string,mixed>>
     */
    public function experiences(int $limit = 6): array
    {
        $limit = max(1, min(24, $limit));

        $rows = $this->rows('experiences', sprintf(
            'SELECT id, title, slug, excerpt, best_time,
                    hero_image, cover_image, og_image, hero_alt, cover_alt, og_alt
             FROM experiences
             WHERE is_published = 1
             ORDER BY is_featured DESC, sort_order ASC, title ASC
             LIMIT %d',
            $limit
        ));

        return array_map(static fn(array $row): array => ContentNormalizer::experience($row), $rows);
    }

    /**
     * @return list<array<string,mixed>>
     */
    public function dayTours(int $limit = 3): array
    {
        $limit = max(1, min(24, $limit));

        $rows = $this->rows('day_tours', sprintf(
            'SELECT id, name, slug, excerpt, duration_hours, max_group_size,
                    price_from, price_currency,
                    hero_image, cover_image, og_image, hero_alt, cover_alt, og_alt
             FROM day_tours
             WHERE is_published = 1
             ORDER BY is_featured DESC, sort_order ASC, name ASC
             LIMIT %d',
            $limit
        ));

        return array_map(static fn(array $row): array => ContentNormalizer::dayTour($row), $rows);
    }

    /**
     * @return list<array<string,mixed>>
     */
    public function articles(int $limit = 3): array
    {
        $limit = max(1, min(24, $limit));

        $rows = $this->rows('articles', sprintf(
            'SELECT id, title, slug, excerpt, published_at, created_at,
                    hero_image, cover_image, og_image, hero_alt, cover_alt, og_alt
             FROM blog_posts
             WHERE is_published = 1
             ORDER BY is_featured DESC, COALESCE(published_at, created_at) DESC
             LIMIT %d',
            $limit
        ));

        return array_map(static fn(array $row): array => ContentNormalizer::article($row), $rows);
    }

    /**
     * Verified, published reviews only. Placeholder/test rows are excluded at
     * the query level as well as by the Phase 0 data fix.
     *
     * @return list<array<string,mixed>>
     */
    public function reviews(int $limit = 3): array
    {
        $limit = max(1, min(12, $limit));

        $rows = $this->rows('reviews', sprintf(
            "SELECT id, customer_name, country, rating, review_title, review_text,
                    trip_type, tour_done_date, tour_done_month, review_source, review_source_url
             FROM reviews
             WHERE is_published = 1
               AND customer_name NOT LIKE 'Test Guest%%'
             ORDER BY is_featured DESC, sort_order ASC, COALESCE(tour_done_date, created_at) DESC
             LIMIT %d",
            $limit
        ));

        return array_map(static fn(array $row): array => ContentNormalizer::review($row), $rows);
    }

    /**
     * Region chips for the destination grid, derived from published rows so the
     * filter can never advertise a region with nothing behind it.
     *
     * @return list<string>
     */
    public function regionLabels(): array
    {
        $rows = $this->rows('regions',
            "SELECT DISTINCT region_label
             FROM destinations
             WHERE is_published = 1 AND COALESCE(region_label, '') <> ''
             ORDER BY region_label ASC"
        );

        $labels = [];
        foreach ($rows as $row) {
            $label = trim((string) ($row['region_label'] ?? ''));
            if ($label !== '') {
                $labels[] = $label;
            }
        }

        return array_values(array_unique($labels));
    }

    /**
     * @param list<mixed> $params
     * @return list<array<string,mixed>>
     */
    private function rows(string $key, string $sql, array $params = []): array
    {
        if (isset($this->memo[$key])) {
            return $this->memo[$key];
        }

        try {
            $stmt = $this->db()->prepare($sql);
            $stmt->execute($params);
            /** @var list<array<string,mixed>> $rows */
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) {
            error_log(sprintf('[ICT home] query "%s" failed: %s', $key, $e->getMessage()));
            $rows = [];
        }

        return $this->memo[$key] = $rows;
    }
}
