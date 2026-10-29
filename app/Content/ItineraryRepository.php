<?php
declare(strict_types=1);

namespace ICT\Content;

use ICT\Support\ContentNormalizer;
use PDO;
use Throwable;

/**
 * Read queries for a single itinerary detail page.
 *
 * Prepared statements only; a missing or unpublished slug returns null so the
 * controller can answer 404 instead of rendering an empty page.
 */
final class ItineraryRepository
{
    public function __construct(private ?PDO $pdo = null)
    {
    }

    /** @return array<string,mixed>|null */
    public function find(string $slug): ?array
    {
        $slug = trim($slug);

        if ($slug === '') {
            return null;
        }

        try {
            $stmt = $this->db()->prepare(
                'SELECT * FROM itineraries WHERE slug = :slug AND is_published = 1 LIMIT 1'
            );
            $stmt->execute(['slug' => $slug]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            return is_array($row) ? $row : null;
        } catch (Throwable $e) {
            error_log('[ICT itinerary] find: ' . $e->getMessage());

            return null;
        }
    }

    /**
     * @return list<array<string,mixed>>
     */
    public function days(int $itineraryId): array
    {
        try {
            $stmt = $this->db()->prepare(
                'SELECT day_number, day_end, title, summary, overnight_location, travel_time, image_path, highlights_json
                 FROM itinerary_days
                 WHERE itinerary_id = :id
                 ORDER BY day_number ASC'
            );
            $stmt->execute(['id' => $itineraryId]);

            /** @var list<array<string,mixed>> $rows */
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            return $rows;
        } catch (Throwable $e) {
            error_log('[ICT itinerary] days: ' . $e->getMessage());

            return [];
        }
    }

    /**
     * Other published itineraries, same category first.
     *
     * @return list<array<string,mixed>>
     */
    public function related(int $excludeId, int $categoryId, int $limit = 3): array
    {
        $limit = max(1, min(6, $limit));

        try {
            $sql = sprintf(
                'SELECT id, name, slug, excerpt, days, price_from, price_currency,
                        hero_image, cover_image, og_image, hero_alt, cover_alt, og_alt
                 FROM itineraries
                 WHERE is_published = 1 AND id <> :id
                 ORDER BY (category_id = :category) DESC, is_featured DESC, sort_order ASC
                 LIMIT %d',
                $limit
            );
            $stmt = $this->db()->prepare($sql);
            $stmt->execute(['id' => $excludeId, 'category' => $categoryId]);

            /** @var list<array<string,mixed>> $rows */
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            return array_map(static fn(array $row): array => ContentNormalizer::journey($row), $rows);
        } catch (Throwable $e) {
            error_log('[ICT itinerary] related: ' . $e->getMessage());

            return [];
        }
    }

    private function db(): PDO
    {
        return $this->pdo ??= pdo();
    }
}
