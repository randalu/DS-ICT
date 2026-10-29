<?php
declare(strict_types=1);

namespace ICT\Pages;

use ICT\Content\ArticleRepository;
use ICT\Support\MediaResolver;
use ICT\View\Page;
use ICT\View\Renderer;
use Throwable;

/** Controller for /blog/{slug} on the Daylight (v3) shell. */
final class ArticlePageV3
{
    public function __construct(private readonly string $slug)
    {
    }

    public function run(): void
    {
        $page = $this->page();

        if ($page === null) {
            Renderer::notFound();

            return;
        }

        Renderer::render($page, 'views/v3/layout.php');
    }

    public function page(): ?Page
    {
        $repository = new ArticleRepository();
        $article = $repository->find($this->slug);

        if ($article === null) {
            return null;
        }

        $title = trim((string) ($article['title'] ?? 'Article'));
        $url = base_url('blog/' . $this->slug);

        $heroPath = trim((string) ($article['hero_image'] ?? '')) !== ''
            ? (string) $article['hero_image']
            : (string) ($article['cover_image'] ?? $article['og_image'] ?? '');
        $hero = $this->resolveMedia($heroPath);

        $published = trim((string) ($article['published_at'] ?? $article['created_at'] ?? ''));
        $publishedLabel = '';
        if ($published !== '') {
            $timestamp = strtotime($published);
            if ($timestamp !== false) {
                $publishedLabel = date('j F Y', $timestamp);
            }
        }

        $metaTitle = trim((string) ($article['meta_title'] ?? '')) ?: $title . ' | ' . APP_NAME;
        $metaDescription = trim((string) ($article['meta_description'] ?? ''))
            ?: trim((string) ($article['excerpt'] ?? ''));

        $schema = [];
        try {
            $schema = [schema_breadcrumbs([
                ['name' => 'Home', 'url' => base_url()],
                ['name' => 'Blog', 'url' => base_url('blog')],
                ['name' => $title, 'url' => $url],
            ])];

            $entry = schema_article($article, $url);
            if (is_array($entry) && $entry !== []) {
                $schema[] = $entry;
            }
        } catch (Throwable $e) {
            error_log('[ICT article] schema: ' . $e->getMessage());
        }

        return new Page(
            template: 'v3/pages/blog/show',
            meta: [
                'title' => $metaTitle,
                'description' => $metaDescription,
                'canonical' => $url,
                'image' => $hero,
                'image_alt' => $title,
                'type' => 'article',
                'robots' => 'index,follow,max-image-preview:large',
            ],
            data: [
                'islands' => false,
                'title' => $title,
                'url' => $url,
                'hero' => $hero,
                'excerpt' => trim((string) ($article['excerpt'] ?? '')),
                'content' => (string) ($article['content'] ?? ''),
                'author' => trim((string) ($article['author'] ?? '')) ?: APP_NAME,
                'published' => $publishedLabel,
                'related' => $repository->related((int) ($article['id'] ?? 0), 3),
                'cta_url' => base_url('contact'),
                'blog_url' => base_url('blog'),
            ],
            schema: $schema,
            bodyClass: 'ix-page-article',
        );
    }

    private function resolveMedia(string $path): string
    {
        $url = MediaResolver::url($path);

        return $this->isMissingLocalFile($url) ? base_url('assets/images/galle-lighthouse.jpg') : $url;
    }

    private function isMissingLocalFile(string $url): bool
    {
        $base = rtrim(base_url(), '/');

        if (!str_starts_with($url, $base . '/')) {
            return false;
        }

        $relative = ltrim(substr($url, strlen($base)), '/');
        $relative = strtok($relative, '?') ?: $relative;

        if ($relative === '' || str_contains($relative, '..')) {
            return false;
        }

        return !is_file(APP_ROOT . '/' . $relative);
    }
}
