<?php
declare(strict_types=1);

namespace ICT\Support;

/**
 * Pagination maths and canonical rules for listing pages.
 *
 * Canonical contract (same as blog.php, guarded by BlogPaginationCanonicalTest):
 *  - no page, or page 1            -> the bare base URL
 *  - page N where 1 < N <= pages   -> base?page=N  (self-canonical)
 *  - page N out of range           -> the bare base URL
 *  - tracking parameters (utm_*)   -> never part of the canonical
 */
final class Pagination
{
    public static function canonical(string $baseUrl, int $page, int $pages): string
    {
        $baseUrl = rtrim($baseUrl, '/');

        if ($page <= 1 || $pages < 1 || $page > $pages) {
            return $baseUrl;
        }

        return $baseUrl . '?page=' . $page;
    }

    /**
     * @return array{page:int,pages:int,total:int,per_page:int,has_prev:bool,has_next:bool,prev_url:string,next_url:string}
     */
    public static function window(string $baseUrl, int $page, int $total, int $perPage): array
    {
        $perPage = max(1, $perPage);
        $pages = max(1, (int) ceil($total / $perPage));
        $page = max(1, min($page, $pages));

        return [
            'page' => $page,
            'pages' => $pages,
            'total' => $total,
            'per_page' => $perPage,
            'has_prev' => $page > 1,
            'has_next' => $page < $pages,
            'prev_url' => self::pageUrl($baseUrl, $page - 1),
            'next_url' => self::pageUrl($baseUrl, $page + 1),
        ];
    }

    /** Page URL that keeps an existing filter query string intact. */
    public static function pageUrl(string $baseUrl, int $page, array $query = []): string
    {
        $query = array_filter(
            $query,
            static fn($value): bool => !is_array($value) && trim((string) $value) !== ''
        );

        if ($page > 1) {
            $query['page'] = $page;
        } else {
            unset($query['page']);
        }

        $baseUrl = rtrim($baseUrl, '/');

        return $query === [] ? $baseUrl : $baseUrl . '?' . http_build_query($query);
    }

    /** Current page from the query string, clamped to a sane range. */
    public static function currentPage(array $query): int
    {
        $raw = $query['page'] ?? null;

        if (!is_scalar($raw)) {
            return 1;
        }

        $page = (int) $raw;

        return $page > 0 ? min($page, 1000) : 1;
    }
}
