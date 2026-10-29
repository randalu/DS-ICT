<?php
declare(strict_types=1);

namespace ICT\Pages;

use ICT\Content\TourRepository;
use ICT\Support\MediaResolver;
use ICT\Support\PriceFormatter;
use ICT\View\Page;
use ICT\View\Renderer;
use Throwable;

/** Controller for /tours/{slug} on the Daylight (v3) shell. */
final class TourPageV3
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
        $repository = new TourRepository();
        $tour = $repository->find($this->slug);

        if ($tour === null) {
            return null;
        }

        $name = trim((string) ($tour['name'] ?? 'Tour'));
        $id = (int) ($tour['id'] ?? 0);
        $url = base_url('tours/' . $this->slug);

        $heroPath = trim((string) ($tour['hero_image'] ?? '')) !== ''
            ? (string) $tour['hero_image']
            : (string) ($tour['cover_image'] ?? $tour['og_image'] ?? '');
        $hero = $this->resolveMedia($heroPath);

        $programme = [];
        $rawProgram = trim((string) ($tour['program_json'] ?? ''));
        if ($rawProgram !== '') {
            $decoded = json_decode($rawProgram, true);
            if (is_array($decoded)) {
                foreach ($decoded as $index => $day) {
                    if (!is_array($day)) {
                        continue;
                    }
                    $title = trim((string) ($day['title'] ?? $day['day_title'] ?? ''));
                    $body = trim((string) ($day['summary'] ?? $day['text'] ?? $day['description'] ?? ''));
                    if ($title === '' && $body === '') {
                        continue;
                    }
                    $programme[] = [
                        'label' => trim((string) ($day['label'] ?? '')) ?: 'Day ' . ((int) ($day['day'] ?? $index + 1)),
                        'title' => $title,
                        'body' => $body,
                    ];
                }
            }
        }

        $faq = [];
        $rawFaq = trim((string) ($tour['faq_json'] ?? ''));
        if ($rawFaq !== '') {
            $decoded = json_decode($rawFaq, true);
            if (is_array($decoded)) {
                foreach ($decoded as $item) {
                    if (!is_array($item)) {
                        continue;
                    }
                    $q = trim((string) ($item['q'] ?? $item['question'] ?? ''));
                    $a = trim((string) ($item['a'] ?? $item['answer'] ?? ''));
                    if ($q !== '' && $a !== '') {
                        $faq[] = ['q' => $q, 'a' => $a];
                    }
                }
            }
        }

        $metaTitle = trim((string) ($tour['meta_title'] ?? '')) ?: $name . ' | ' . APP_NAME;
        $metaDescription = trim((string) ($tour['meta_description'] ?? '')) ?: trim((string) ($tour['excerpt'] ?? ''));

        $schema = [];
        try {
            $schema = [schema_breadcrumbs([
                ['name' => 'Home', 'url' => base_url()],
                ['name' => 'Tours', 'url' => base_url('tours')],
                ['name' => $name, 'url' => $url],
            ])];

            $service = schema_service($tour, $url);
            if (is_array($service) && $service !== []) {
                $schema[] = $service;
            }
        } catch (Throwable $e) {
            error_log('[ICT tour] schema: ' . $e->getMessage());
        }

        return new Page(
            template: 'v3/pages/tours/show',
            meta: [
                'title' => $metaTitle,
                'description' => $metaDescription,
                'canonical' => $url,
                'image' => $hero,
                'image_alt' => $name,
                'type' => 'article',
                'robots' => 'index,follow,max-image-preview:large',
            ],
            data: [
                'islands' => false,
                'name' => $name,
                'url' => $url,
                'hero' => $hero,
                'excerpt' => trim((string) ($tour['excerpt'] ?? '')),
                'intro' => (string) ($tour['intro'] ?? ''),
                'includes' => (string) ($tour['includes'] ?? ''),
                'excludes' => (string) ($tour['excludes'] ?? ''),
                'pricing_notes' => (string) ($tour['pricing_notes'] ?? ''),
                'price' => PriceFormatter::forRow($tour),
                'price_unit' => trim((string) ($tour['price_unit'] ?? '')),
                'programme' => $programme,
                'faq' => $faq,
                'related' => $repository->related($id, 3),
                'cta_url' => base_url('contact'),
            ],
            schema: $schema,
            bodyClass: 'ix-page-tour',
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
