<?php
declare(strict_types=1);

namespace ICT\Content;

use ICT\Support\ContentNormalizer;
use ICT\Support\MediaResolver;
use PDO;
use Throwable;

/**
 * Read queries for the six discovery indexes.
 *
 * All families share the same query shape (published rows, optional equality
 * filter, optional LIKE search, fixed ordering, LIMIT/OFFSET), so they live in
 * one repository with a per-family definition instead of six near-identical
 * classes. Splitting a family out is trivial once it needs its own logic.
 *
 * Guarantees:
 *  - prepared statements for every user-supplied value
 *  - LIMIT/OFFSET are integers clamped in code, never interpolated from input
 *  - a failing query logs and reports zero rows rather than throwing a 500
 */
final class CollectionRepository
{
    public const FAMILIES = ['destinations', 'experiences', 'tours', 'itineraries', 'day-tours', 'activities'];

    public function __construct(private ?PDO $pdo = null)
    {
    }

    /**
     * One page of a collection.
     *
     * @param array<string,string> $filters
     * @return array{items:list<array<string,mixed>>,total:int}
     */
    public function page(string $family, array $filters, int $page, int $perPage = 12): array
    {
        $perPage = max(1, min(48, $perPage));
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;

        [$select, $from, $where, $params, $order] = $this->build($family, $filters);

        $total = $this->scalar("SELECT COUNT(*) FROM {$from} WHERE {$where}", $params);

        $sql = "SELECT {$select} FROM {$from} WHERE {$where} ORDER BY {$order} LIMIT {$perPage} OFFSET {$offset}";

        $rows = $this->rows($sql, $params);

        return [
            'items' => array_map(fn(array $row): array => $this->toCard($family, $row), $rows),
            'total' => $total,
        ];
    }

    /**
     * Filter options for the toolbar. Only values that actually occur in
     * published rows are offered, so a chip can never return nothing.
     *
     * @return array<string,list<array{value:string,label:string}>>
     */
    public function facets(string $family): array
    {
        return match ($family) {
            'destinations' => ['region' => $this->destinationsRegions()],
            'experiences' => ['category' => $this->experienceCategories()],
            'day-tours' => ['difficulty' => $this->dayTourDifficulties()],
            default => [],
        };
    }

    public function supports(string $family): bool
    {
        return in_array($family, self::FAMILIES, true);
    }

    /**
     * Theme landing pages (honeymoon-tours, wildlife-tours, …) that the tours
     * index links to as an alternative browse path.
     *
     * Delegates to the legacy helpers because they already own the canonical
     * rule for a theme category (and the sitemap decision that goes with it).
     *
     * @return list<array{label:string,url:string,count:int}>
     */
    public function themes(): array
    {
        if (!function_exists('fetch_published_itinerary_categories_with_counts')) {
            return [];
        }

        try {
            $rows = fetch_published_itinerary_categories_with_counts();
        } catch (Throwable $e) {
            error_log('[ICT collections] themes: ' . $e->getMessage());

            return [];
        }

        $themes = [];

        foreach ($rows as $row) {
            if ((int) ($row['is_theme'] ?? 0) !== 1) {
                continue;
            }

            $label = trim((string) ($row['name'] ?? ''));
            $url = function_exists('itinerary_category_public_canonical_url')
                ? (string) itinerary_category_public_canonical_url($row)
                : '';

            if ($label === '' || $url === '') {
                continue;
            }

            $themes[] = [
                'label' => $label,
                'url' => $url,
                'count' => (int) ($row['itinerary_count'] ?? 0),
            ];
        }

        return $themes;
    }

    /**
     * Published slugs for a session-save endpoint, used to sanitise posted
     * selections against real content. Guarded for older installs.
     *
     * @return list<string>
     */
    public function publishedSlugs(string $family): array
    {
        $table = match ($family) {
            'destinations' => 'destinations',
            'experiences' => 'experiences',
            default => null,
        };

        if ($table === null || !db_table_exists($table) || !db_column_exists($table, 'slug')) {
            return [];
        }

        $filter = db_column_exists($table, 'is_published') ? ' WHERE is_published = 1' : '';
        $rows = $this->rows('SELECT slug FROM ' . $table . $filter);

        return array_values(array_filter(array_map(
            static fn(array $row): string => (string) ($row['slug'] ?? ''),
            $rows
        )));
    }

    /**
     * The /activities hub record, which supplies that page's copy and image.
     *
     * @return array<string,mixed>|null
     */
    public function hub(): ?array
    {
        $rows = $this->rows(
            'SELECT id, name, slug, excerpt, meta_title, meta_description, hero_image, cover_image, og_image
             FROM activity_pages
             WHERE is_hub = 1 AND is_published = 1
             ORDER BY sort_order ASC, id ASC
             LIMIT 1'
        );

        return $rows[0] ?? null;
    }

    /**
     * @param array<string,string> $filters
     * @return array{0:string,1:string,2:string,3:array<string,string>,4:string}
     */
    private function build(string $family, array $filters): array
    {
        $q = trim((string) ($filters['q'] ?? ''));
        $params = [];
        $where = [];

        switch ($family) {
            case 'destinations':
                $select = 'd.id, d.name, d.slug, d.excerpt, d.best_known_for, d.region_label, d.province,'
                    . ' d.stay_duration, d.destination_type, d.hero_image, d.cover_image, d.og_image,'
                    . ' d.hero_alt, d.cover_alt, d.og_alt';
                $from = 'destinations d';
                $order = 'd.is_featured DESC, d.sort_order ASC, d.name ASC';
                $where[] = 'd.is_published = 1';
                if (($region = trim((string) ($filters['region'] ?? ''))) !== '') {
                    $where[] = 'd.region_label = :region';
                    $params['region'] = $region;
                }
                if ($q !== '') {
                    $where[] = $this->likeAll(['d.name', 'd.excerpt', 'd.best_known_for'], $params, $q);
                }
                break;

            case 'experiences':
                $select = 'e.id, e.title, e.slug, e.excerpt, e.hero_image, e.cover_image, e.og_image,'
                    . ' e.hero_alt, e.cover_alt, e.og_alt';
                $from = 'experiences e';
                $order = 'e.is_featured DESC, e.sort_order ASC, e.title ASC';
                $where[] = 'e.is_published = 1';
                if (($category = trim((string) ($filters['category'] ?? ''))) !== '') {
                    $where[] = 'EXISTS (SELECT 1 FROM experience_category_pivot p'
                        . ' JOIN experience_categories c ON c.id = p.category_id'
                        . ' WHERE p.experience_id = e.id AND c.slug = :category)';
                    $params['category'] = $category;
                }
                if ($q !== '') {
                    $where[] = $this->likeAll(['e.title', 'e.excerpt'], $params, $q);
                }
                break;

            case 'tours':
                $select = 't.id, t.name, t.slug, t.excerpt, t.price_from, t.price_currency,'
                    . ' t.hero_image, t.cover_image, t.og_image, t.hero_alt, t.cover_alt, t.og_alt';
                $from = 'tours t';
                $order = 't.is_featured DESC, t.sort_order ASC, t.name ASC';
                $where[] = 't.is_published = 1';
                if ($q !== '') {
                    $where[] = $this->likeAll(['t.name', 't.excerpt'], $params, $q);
                }
                break;

            case 'itineraries':
                $select = 'i.id, i.name, i.slug, i.excerpt, i.days, i.price_from, i.price_currency,'
                    . ' i.hero_image, i.cover_image, i.og_image, i.hero_alt, i.cover_alt, i.og_alt';
                $from = 'itineraries i';
                $order = 'i.is_featured DESC, i.sort_order ASC, i.days ASC';
                $where[] = 'i.is_published = 1';
                $days = trim((string) ($filters['days'] ?? ''));
                if ($days === '1-7') {
                    $where[] = 'i.days BETWEEN 1 AND 7';
                } elseif ($days === '8-12') {
                    $where[] = 'i.days BETWEEN 8 AND 12';
                } elseif ($days === '13+') {
                    $where[] = 'i.days >= 13';
                }
                if ($q !== '') {
                    $where[] = $this->likeAll(['i.name', 'i.excerpt'], $params, $q);
                }
                break;

            case 'day-tours':
                $select = 'dt.id, dt.name, dt.slug, dt.excerpt, dt.duration_hours, dt.max_group_size,'
                    . ' dt.difficulty, dt.price_from, dt.price_currency,'
                    . ' dt.hero_image, dt.cover_image, dt.og_image, dt.hero_alt, dt.cover_alt, dt.og_alt';
                $from = 'day_tours dt';
                $order = 'dt.is_featured DESC, dt.sort_order ASC, dt.name ASC';
                $where[] = 'dt.is_published = 1';
                if (($difficulty = trim((string) ($filters['difficulty'] ?? ''))) !== '') {
                    $where[] = 'dt.difficulty = :difficulty';
                    $params['difficulty'] = $difficulty;
                }
                $price = trim((string) ($filters['price'] ?? ''));
                if ($price === 'under-100') {
                    $where[] = 'dt.price_from < 100';
                } elseif ($price === '100-200') {
                    $where[] = 'dt.price_from BETWEEN 100 AND 200';
                } elseif ($price === '200+') {
                    $where[] = 'dt.price_from >= 200';
                }
                if ($q !== '') {
                    $where[] = $this->likeAll(['dt.name', 'dt.excerpt', 'dt.meeting_point'], $params, $q);
                }
                break;

            case 'activities':
                $select = 'a.id, a.name, a.slug, a.excerpt, a.hero_image, a.cover_image, a.og_image,'
                    . ' a.hero_alt, a.cover_alt, a.og_alt';
                $from = 'activity_pages a';
                $order = 'a.sort_order ASC, a.name ASC';
                $where[] = 'a.is_published = 1';
                $where[] = 'a.is_hub = 0';
                if ($q !== '') {
                    $where[] = $this->likeAll(['a.name', 'a.excerpt'], $params, $q);
                }
                break;

            default:
                // Unknown family: return an impossible condition rather than
                // leaking a wider query than the caller asked for.
                return ['1 AS id', 'destinations', '1 = 0', [], 'id ASC'];
        }

        return [$select, $from, implode(' AND ', $where), $params, $order];
    }

    /**
     * Build "(col LIKE :q1 OR col LIKE :q2 ...)".
     *
     * PDO runs with ATTR_EMULATE_PREPARES disabled, so a named placeholder can
     * only appear once in a statement. Each column therefore gets its own
     * parameter bound to the same pattern.
     *
     * @param list<string>        $columns
     * @param array<string,mixed> $params
     */
    private function likeAll(array $columns, array &$params, string $search): string
    {
        $pattern = '%' . $search . '%';
        $parts = [];

        foreach ($columns as $index => $column) {
            $key = 'q' . ($index + 1);
            $params[$key] = $pattern;
            $parts[] = $column . ' LIKE :' . $key;
        }

        return '(' . implode(' OR ', $parts) . ')';
    }

    /**
     * @param array<string,mixed> $row
     * @return array<string,mixed>
     */
    private function toCard(string $family, array $row): array
    {
        return match ($family) {
            'destinations' => ContentNormalizer::destination($row),
            'experiences' => ContentNormalizer::experience($row),
            'tours' => $this->journeyCard($row, 'tour', 'Tours'),
            'itineraries' => ContentNormalizer::journey($row),
            'day-tours' => ContentNormalizer::dayTour($row),
            'activities' => $this->activityCard($row),
            default => ContentNormalizer::destination($row),
        };
    }

    /**
     * Tours reuse the journey card shape but live under /tours.
     *
     * @param array<string,mixed> $row
     */
    private function journeyCard(array $row, string $segment, string $kicker): array
    {
        $card = ContentNormalizer::journey($row);
        $slug = trim((string) ($row['slug'] ?? ''));
        $card['url'] = $slug !== '' ? base_url($segment . '/' . $slug) : base_url($segment);
        $card['kicker'] = $kicker;

        return $card;
    }

    /**
     * @param array<string,mixed> $row
     */
    private function activityCard(array $row): array
    {
        $name = trim((string) ($row['name'] ?? 'Activity'));
        $slug = trim((string) ($row['slug'] ?? ''));
        $media = MediaResolver::forRow($row, $name);

        return [
            'title' => $name,
            'url' => $slug !== '' ? base_url('activities/' . $slug) : base_url('activities'),
            'image' => $media['url'],
            'alt' => $media['alt'],
            'excerpt' => trim((string) ($row['excerpt'] ?? '')),
            'meta' => [],
            'price' => '',
            'kicker' => 'Activity',
        ];
    }

    /** @return list<array{value:string,label:string}> */
    private function destinationsRegions(): array
    {
        $rows = $this->rows(
            "SELECT DISTINCT region_label FROM destinations
             WHERE is_published = 1 AND COALESCE(region_label, '') <> ''
             ORDER BY region_label ASC"
        );

        return array_map(
            static fn(array $row): array => ['value' => (string) $row['region_label'], 'label' => (string) $row['region_label']],
            $rows
        );
    }

    /** @return list<array{value:string,label:string}> */
    private function experienceCategories(): array
    {
        try {
            $rows = $this->rows(
                'SELECT c.slug, c.name FROM experience_categories c
                 WHERE EXISTS (
                     SELECT 1 FROM experience_category_pivot p
                     JOIN experiences e ON e.id = p.experience_id
                     WHERE p.category_id = c.id AND e.is_published = 1
                 )
                 ORDER BY c.name ASC'
            );
        } catch (Throwable $e) {
            error_log('[ICT collections] categories: ' . $e->getMessage());
            $rows = [];
        }

        return array_map(
            static fn(array $row): array => ['value' => (string) $row['slug'], 'label' => (string) $row['name']],
            $rows
        );
    }

    /** @return list<array{value:string,label:string}> */
    private function dayTourDifficulties(): array
    {
        $rows = $this->rows(
            "SELECT DISTINCT difficulty FROM day_tours
             WHERE is_published = 1 AND COALESCE(difficulty, '') <> ''
             ORDER BY difficulty ASC"
        );

        return array_map(
            static fn(array $row): array => [
                'value' => (string) $row['difficulty'],
                'label' => ucfirst((string) $row['difficulty']),
            ],
            $rows
        );
    }

    /**
     * @param array<string,mixed> $params
     */
    private function scalar(string $sql, array $params = []): int
    {
        try {
            $stmt = $this->db()->prepare($sql);
            $stmt->execute($params);

            return (int) $stmt->fetchColumn();
        } catch (Throwable $e) {
            error_log('[ICT collections] count failed: ' . $e->getMessage());

            return 0;
        }
    }

    /**
     * @param array<string,mixed> $params
     * @return list<array<string,mixed>>
     */
    private function rows(string $sql, array $params = []): array
    {
        try {
            $stmt = $this->db()->prepare($sql);
            $stmt->execute($params);

            /** @var list<array<string,mixed>> $rows */
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            return $rows;
        } catch (Throwable $e) {
            error_log('[ICT collections] query failed: ' . $e->getMessage());

            return [];
        }
    }

    private function db(): PDO
    {
        return $this->pdo ??= pdo();
    }
}
