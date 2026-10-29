<?php
declare(strict_types=1);

namespace ICT\Pages;

use ICT\Content\ArticleRepository;
use ICT\Support\Pagination;
use ICT\View\Page;
use ICT\View\Renderer;
use Throwable;

/**
 * Controller for /blog on the Daylight (v3) shell.
 *
 * Pagination keeps the legacy contract (9 per page), which
 * BlogPaginationCanonicalTest pins: page 1 canonicalises to /blog, page N to
 * /blog?page=N, and an out-of-range page falls back to /blog.
 */
final class BlogIndexPageV3
{
    private const PER_PAGE = 9;

    public function run(): void
    {
        Renderer::render($this->page(), 'views/v3/layout.php');
    }

    public function page(): Page
    {
        $repository = new ArticleRepository();
        $requested = Pagination::currentPage($_GET);
        $base = base_url('blog');

        $result = $repository->paginate($requested, self::PER_PAGE);
        $window = Pagination::window($base, $requested, (int) $result['total'], self::PER_PAGE);

        $schema = [];
        try {
            $schema = [schema_breadcrumbs([
                ['name' => 'Home', 'url' => base_url()],
                ['name' => 'Blog', 'url' => $base],
            ])];

            if ($result['items'] !== []) {
                $schema[] = schema_item_list('Sri Lanka Travel Journal', array_map(
                    static fn(array $card): array => [
                        'name' => (string) $card['title'],
                        'url' => (string) $card['url'],
                        'image' => (string) $card['image'],
                    ],
                    $result['items']
                ));
            }
        } catch (Throwable $e) {
            error_log('[ICT blog] schema: ' . $e->getMessage());
        }

        return new Page(
            template: 'v3/pages/blog/index',
            meta: [
                'title' => 'Sri Lanka Travel Journal | ' . APP_NAME,
                'description' => 'Practical Sri Lanka travel writing: itineraries, timing, routes and the places worth the detour.',
                'canonical' => Pagination::canonical($base, $requested, (int) $window['pages']),
                'image' => $result['items'][0]['image'] ?? DEFAULT_OG_IMAGE,
                'image_alt' => 'Sri Lanka travel journal',
                'type' => 'website',
                'robots' => 'index,follow,max-image-preview:large',
            ],
            data: [
                'islands' => false,
                'items' => $result['items'],
                'total' => (int) $result['total'],
                'window' => $window,
                'base_url' => $base,
            ],
            schema: $schema,
            bodyClass: 'ix-page-blog',
        );
    }
}
