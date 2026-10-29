<?php
declare(strict_types=1);

namespace ICT\Pages;

use ICT\Content\DestinationRepository;
use ICT\Support\MediaResolver;
use ICT\View\Page;
use ICT\View\Renderer;
use Throwable;

/**
 * Controller for /destinations/{slug} on the Daylight (v3) shell.
 */
final class DestinationPageV3
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
        $repository = new DestinationRepository();
        $destination = $repository->find($this->slug);

        if ($destination === null) {
            return null;
        }

        $name = trim((string) ($destination['name'] ?? 'Destination'));
        $url = base_url('destinations/' . $this->slug);

        $heroPath = trim((string) ($destination['hero_image'] ?? '')) !== ''
            ? (string) $destination['hero_image']
            : (string) ($destination['cover_image'] ?? $destination['og_image'] ?? '');
        $hero = $this->resolveMedia($heroPath);

        $metaTitle = trim((string) ($destination['meta_title'] ?? '')) ?: $name . ' | ' . APP_NAME;
        $metaDescription = trim((string) ($destination['meta_description'] ?? ''))
            ?: trim((string) ($destination['excerpt'] ?? ''));

        $faq = [];
        try {
            $faqItems = schema_faq_from_json((string) ($destination['faq_json'] ?? ''));
            if (is_array($faqItems) && isset($faqItems['mainEntity']) && is_array($faqItems['mainEntity'])) {
                foreach ($faqItems['mainEntity'] as $item) {
                    $q = trim((string) ($item['name'] ?? ''));
                    $a = trim((string) ($item['acceptedAnswer']['text'] ?? ''));
                    if ($q !== '' && $a !== '') {
                        $faq[] = ['q' => $q, 'a' => $a];
                    }
                }
            }
        } catch (Throwable $e) {
            error_log('[ICT destination] faq: ' . $e->getMessage());
        }

        // Some rows store the raw editorial shape ([{q,a}]) rather than the
        // schema shape, so fall back to a direct decode.
        if ($faq === []) {
            $raw = trim((string) ($destination['faq_json'] ?? ''));
            if ($raw !== '' && str_starts_with($raw, '[')) {
                $decoded = json_decode($raw, true);
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
        }

        $schema = [];
        try {
            $schema = [schema_breadcrumbs([
                ['name' => 'Home', 'url' => base_url()],
                ['name' => 'Destinations', 'url' => base_url('destinations')],
                ['name' => $name, 'url' => $url],
            ])];

            $schema[] = schema_destination($destination, $url);

            if ($faq !== []) {
                $faqSchema = schema_faq_from_items(array_map(
                    static fn(array $item): array => ['q' => $item['q'], 'a' => $item['a']],
                    $faq
                ));
                if (is_array($faqSchema) && $faqSchema !== []) {
                    $schema[] = $faqSchema;
                }
            }
        } catch (Throwable $e) {
            error_log('[ICT destination] schema: ' . $e->getMessage());
        }

        return new Page(
            template: 'v3/pages/destinations/show',
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
                'excerpt' => trim((string) ($destination['excerpt'] ?? '')),
                'intro' => (string) ($destination['intro'] ?? ''),
                'why_visit' => (string) ($destination['why_visit'] ?? ''),
                'things_to_do' => (string) ($destination['things_to_do'] ?? ''),
                'best_time' => (string) ($destination['best_time'] ?? ''),
                'getting_there' => (string) ($destination['how_to_get_there'] ?? ''),
                'where_to_stay' => (string) ($destination['where_to_stay'] ?? ''),
                'travel_tips' => (string) ($destination['travel_tips'] ?? ''),
                'region' => trim((string) ($destination['region_label'] ?? $destination['province'] ?? '')),
                'type' => trim((string) ($destination['destination_type'] ?? '')),
                'stay' => trim((string) ($destination['stay_duration'] ?? '')),
                'best_known_for' => trim((string) ($destination['best_known_for'] ?? '')),
                'faq' => $faq,
                'related' => $repository->related($destination, 3),
                'cta_url' => base_url('contact'),
                'itineraries_url' => base_url('itineraries'),
            ],
            schema: $schema,
            bodyClass: 'ix-page-destination',
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
