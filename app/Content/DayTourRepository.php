<?php
declare(strict_types=1);

namespace ICT\Content;

use ICT\Support\ContentNormalizer;
use PDO;
use Throwable;

/** Read queries for a day tour detail page. */
final class DayTourRepository
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
            $stmt = $this->db()->prepare('SELECT * FROM day_tours WHERE slug = :slug AND is_published = 1 LIMIT 1');
            $stmt->execute(['slug' => $slug]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            return is_array($row) ? $row : null;
        } catch (Throwable $e) {
            error_log('[ICT day tour] find: ' . $e->getMessage());

            return null;
        }
    }

    /** @return list<array<string,mixed>> */
    public function related(int $excludeId, int $limit = 3): array
    {
        $limit = max(1, min(6, $limit));

        try {
            $sql = sprintf(
                'SELECT id, name, slug, excerpt, duration_hours, max_group_size,
                        price_from, price_currency, hero_image, cover_image, og_image,
                        hero_alt, cover_alt, og_alt
                 FROM day_tours
                 WHERE is_published = 1 AND id <> :id
                 ORDER BY is_featured DESC, sort_order ASC, name ASC
                 LIMIT %d',
                $limit
            );
            $stmt = $this->db()->prepare($sql);
            $stmt->execute(['id' => $excludeId]);

            /** @var list<array<string,mixed>> $rows */
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            return array_map(static fn(array $row): array => ContentNormalizer::dayTour($row), $rows);
        } catch (Throwable $e) {
            error_log('[ICT day tour] related: ' . $e->getMessage());

            return [];
        }
    }

    private function db(): PDO
    {
        return $this->pdo ??= pdo();
    }
}
