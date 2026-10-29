<?php
declare(strict_types=1);

namespace ICT\Content;

use ICT\Support\ContentNormalizer;
use PDO;
use Throwable;

/**
 * Read queries for a destination detail page.
 */
final class DestinationRepository
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
                'SELECT * FROM destinations WHERE slug = :slug AND is_published = 1 LIMIT 1'
            );
            $stmt->execute(['slug' => $slug]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            return is_array($row) ? $row : null;
        } catch (Throwable $e) {
            error_log('[ICT destination] find: ' . $e->getMessage());

            return null;
        }
    }

    /**
     * Same-region destinations first, then featured ones.
     *
     * @param array<string,mixed> $current
     * @return list<array<string,mixed>>
     */
    public function related(array $current, int $limit = 3): array
    {
        $limit = max(1, min(6, $limit));
        $region = trim((string) ($current['region_label'] ?? ''));
        $id = (int) ($current['id'] ?? 0);

        try {
            $sql = sprintf(
                'SELECT id, name, slug, excerpt, best_known_for, region_label, province,
                        stay_duration, destination_type, hero_image, cover_image, og_image,
                        hero_alt, cover_alt, og_alt
                 FROM destinations
                 WHERE is_published = 1 AND id <> :id
                 ORDER BY (region_label = :region) DESC, is_featured DESC, sort_order ASC
                 LIMIT %d',
                $limit
            );
            $stmt = $this->db()->prepare($sql);
            $stmt->execute(['id' => $id, 'region' => $region]);

            /** @var list<array<string,mixed>> $rows */
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            return array_map(static fn(array $row): array => ContentNormalizer::destination($row), $rows);
        } catch (Throwable $e) {
            error_log('[ICT destination] related: ' . $e->getMessage());

            return [];
        }
    }

    private function db(): PDO
    {
        return $this->pdo ??= pdo();
    }
}
