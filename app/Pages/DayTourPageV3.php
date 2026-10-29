<?php
declare(strict_types=1);

namespace ICT\Pages;

use ICT\Content\DayTourRepository;
use ICT\Support\MediaResolver;
use ICT\Support\PriceFormatter;
use ICT\View\Page;
use ICT\View\Renderer;
use Throwable;

/** Controller for /day-tours/{slug} on the Daylight (v3) shell. */
final class DayTourPageV3
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
        $repository = new DayTourRepository();
        $tour = $repository->find($this->slug);

        if ($tour === null) {
            return null;
        }

        $name = trim((string) ($tour['name'] ?? 'Day tour'));
        $url = base_url('day-tours/' . $this->slug);

        $heroPath = trim((string) ($tour['hero_image'] ?? '')) !== ''
            ? (string) $tour['hero_image']
            : (string) ($tour['cover_image'] ?? $tour['og_image'] ?? '');
        $hero = $this->resolveMedia($heroPath);

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
                ['name' => 'Day tours', 'url' => base_url('day-tours')],
                ['name' => $name, 'url' => $url],
            ])];

            $service = schema_service($tour, $url);
            if (is_array($service) && $service !== []) {
                $schema[] = $service;
            }
        } catch (Throwable $e) {
            error_log('[ICT day tour] schema: ' . $e->getMessage());
        }

        return new Page(
            template: 'v3/pages/day-tours/show',
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
                'highlights' => (string) ($tour['highlights'] ?? ''),
                'itinerary' => (string) ($tour['itinerary'] ?? ''),
                'includes' => (string) ($tour['includes'] ?? ''),
                'excludes' => (string) ($tour['excludes'] ?? ''),
                'meeting_point' => trim((string) ($tour['meeting_point'] ?? '')),
                'start_time' => trim((string) ($tour['start_time'] ?? '')),
                'duration' => PriceFormatter::duration($tour['duration_hours'] ?? null),
                'vehicle' => trim((string) ($tour['vehicle_type'] ?? '')),
                'group' => (int) ($tour['max_group_size'] ?? 0),
                'difficulty' => trim((string) ($tour['difficulty'] ?? '')),
                'price' => PriceFormatter::forRow($tour),
                'price_unit' => trim((string) ($tour['price_unit'] ?? '')),
                'faq' => $faq,
                'related' => $repository->related((int) ($tour['id'] ?? 0), 3),
                'cta_url' => base_url('contact'),
            ],
            schema: $schema,
            bodyClass: 'ix-page-day-tour',
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
