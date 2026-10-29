<?php
declare(strict_types=1);

namespace ICT\Content;

use ICT\Support\ContentNormalizer;
use PDO;
use Throwable;

/** Read queries for a blog article page. */
final class ArticleRepository
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
            $stmt = $this->db()->prepare('SELECT * FROM blog_posts WHERE slug = :slug AND is_published = 1 LIMIT 1');
            $stmt->execute(['slug' => $slug]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            return is_array($row) ? $row : null;
        } catch (Throwable $e) {
            error_log('[ICT article] find: ' . $e->getMessage());

            return null;
        }
    }

    /**
     * One page of published articles, newest first.
     *
     * @return array{items:list<array<string,mixed>>,total:int}
     */
    public function paginate(int $page, int $perPage = 9): array
    {
        $perPage = max(1, min(24, $perPage));
        $page = max(1, $page);
        $offset = ($page - 1) * $perPage;

        try {
            $total = (int) $this->db()->query('SELECT COUNT(*) FROM blog_posts WHERE is_published = 1')->fetchColumn();

            $sql = sprintf(
                'SELECT id, title, slug, excerpt, published_at, created_at,
                        hero_image, cover_image, og_image, hero_alt, cover_alt, og_alt
                 FROM blog_posts
                 WHERE is_published = 1
                 ORDER BY COALESCE(published_at, created_at) DESC
                 LIMIT %d OFFSET %d',
                $perPage,
                $offset
            );
            $stmt = $this->db()->prepare($sql);
            $stmt->execute();

            /** @var list<array<string,mixed>> $rows */
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            return [
                'items' => array_map(static fn(array $row): array => ContentNormalizer::article($row), $rows),
                'total' => $total,
            ];
        } catch (Throwable $e) {
            error_log('[ICT article] paginate: ' . $e->getMessage());

            return ['items' => [], 'total' => 0];
        }
    }

    /** @return list<array<string,mixed>> */
    public function related(int $excludeId, int $limit = 3): array
    {
        $limit = max(1, min(6, $limit));

        try {
            $sql = sprintf(
                'SELECT id, title, slug, excerpt, published_at, created_at,
                        hero_image, cover_image, og_image, hero_alt, cover_alt, og_alt
                 FROM blog_posts
                 WHERE is_published = 1 AND id <> :id
                 ORDER BY COALESCE(published_at, created_at) DESC
                 LIMIT %d',
                $limit
            );
            $stmt = $this->db()->prepare($sql);
            $stmt->execute(['id' => $excludeId]);

            /** @var list<array<string,mixed>> $rows */
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            return array_map(static fn(array $row): array => ContentNormalizer::article($row), $rows);
        } catch (Throwable $e) {
            error_log('[ICT article] related: ' . $e->getMessage());

            return [];
        }
    }

    private function db(): PDO
    {
        return $this->pdo ??= pdo();
    }
}
