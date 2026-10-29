<?php
declare(strict_types=1);

namespace ICT\Content;

use ICT\Support\ContentNormalizer;
use PDO;
use Throwable;

/** Read queries for an experience detail page. */
final class ExperienceRepository
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
                'SELECT * FROM experiences WHERE slug = :slug AND is_published = 1 LIMIT 1'
            );
            $stmt->execute(['slug' => $slug]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            return is_array($row) ? $row : null;
        } catch (Throwable $e) {
            error_log('[ICT experience] find: ' . $e->getMessage());

            return null;
        }
    }

    /**
     * @param array<string,mixed> $current
     * @return list<array<string,mixed>>
     */
    public function related(array $current, int $limit = 3): array
    {
        $limit = max(1, min(6, $limit));

        try {
            $sql = sprintf(
                'SELECT id, title, slug, excerpt, hero_image, cover_image, og_image, hero_alt, cover_alt, og_alt
                 FROM experiences
                 WHERE is_published = 1 AND id <> :id
                 ORDER BY (category_id = :category) DESC, is_featured DESC, sort_order ASC
                 LIMIT %d',
                $limit
            );
            $stmt = $this->db()->prepare($sql);
            $stmt->execute([
                'id' => (int) ($current['id'] ?? 0),
                'category' => (int) ($current['category_id'] ?? 0),
            ]);

            /** @var list<array<string,mixed>> $rows */
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            return array_map(static fn(array $row): array => ContentNormalizer::experience($row), $rows);
        } catch (Throwable $e) {
            error_log('[ICT experience] related: ' . $e->getMessage());

            return [];
        }
    }

    private function db(): PDO
    {
        return $this->pdo ??= pdo();
    }
}
