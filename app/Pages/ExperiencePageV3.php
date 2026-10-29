<?php
declare(strict_types=1);

namespace ICT\Pages;

use ICT\Content\ExperienceRepository;
use ICT\Support\MediaResolver;
use ICT\View\Page;
use ICT\View\Renderer;
use Throwable;

/** Controller for /experiences/{slug} on the Daylight (v3) shell. */
final class ExperiencePageV3
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
        $repository = new ExperienceRepository();
        $experience = $repository->find($this->slug);

        if ($experience === null) {
            return null;
        }

        $title = trim((string) ($experience['title'] ?? 'Experience'));
        $url = base_url('experiences/' . $this->slug);

        $heroPath = trim((string) ($experience['hero_image'] ?? '')) !== ''
            ? (string) $experience['hero_image']
            : (string) ($experience['cover_image'] ?? $experience['og_image'] ?? '');
        $hero = $this->resolveMedia($heroPath);

        $tips = [];
        $rawTips = trim((string) ($experience['tips_json'] ?? ''));
        if ($rawTips !== '') {
            $decoded = json_decode($rawTips, true);
            if (is_array($decoded)) {
                foreach ($decoded as $tip) {
                    if (is_string($tip)) {
                        $tip = trim($tip);
                        if ($tip !== '') {
                            $tips[] = $tip;
                        }
                    } elseif (is_array($tip)) {
                        $text = trim((string) ($tip['tip'] ?? $tip['text'] ?? $tip['title'] ?? ''));
                        if ($text !== '') {
                            $tips[] = $text;
                        }
                    }
                }
            }
        }

        $metaTitle = trim((string) ($experience['meta_title'] ?? '')) ?: $title . ' | ' . APP_NAME;
        $metaDescription = trim((string) ($experience['meta_description'] ?? ''))
            ?: trim((string) ($experience['excerpt'] ?? ''));

        $schema = [];
        try {
            $schema = [schema_breadcrumbs([
                ['name' => 'Home', 'url' => base_url()],
                ['name' => 'Experiences', 'url' => base_url('experiences')],
                ['name' => $title, 'url' => $url],
            ])];

            $item = schema_experience($experience, $url);
            if (is_array($item) && $item !== []) {
                $schema[] = $item;
            }
        } catch (Throwable $e) {
            error_log('[ICT experience] schema: ' . $e->getMessage());
        }

        return new Page(
            template: 'v3/pages/experiences/show',
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
                'excerpt' => trim((string) ($experience['excerpt'] ?? '')),
                'intro' => (string) ($experience['intro'] ?? ''),
                'where' => (string) ($experience['where_to_experience'] ?? ''),
                'best_time' => trim((string) ($experience['best_time'] ?? '')),
                'tips' => array_slice($tips, 0, 6),
                'related' => $repository->related($experience, 3),
                'cta_url' => base_url('contact'),
                'itineraries_url' => base_url('itineraries'),
            ],
            schema: $schema,
            bodyClass: 'ix-page-experience',
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
