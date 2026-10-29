<?php
declare(strict_types=1);

namespace ICT\Pages;

use ICT\Content\ItineraryRepository;
use ICT\Support\MediaResolver;
use ICT\Support\PriceFormatter;
use ICT\View\Page;
use ICT\View\Renderer;
use Throwable;

/**
 * Controller for /itineraries/{slug} on the Daylight (v3) shell.
 *
 * Keeps SEO behaviour identical: the canonical is derived from the route (the
 * stored canonical_url is frequently a legacy cross-origin value, and
 * seo_normalize_canonical() would reject it anyway), and the JSON-LD Trip graph
 * still comes from schema_trip().
 */
final class ItineraryPageV3
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
        $repository = new ItineraryRepository();
        $itinerary = $repository->find($this->slug);

        if ($itinerary === null) {
            return null;
        }

        $name = trim((string) ($itinerary['name'] ?? 'Itinerary'));
        $id = (int) ($itinerary['id'] ?? 0);
        $days = (int) ($itinerary['days'] ?? 0);
        $url = base_url('itineraries/' . $this->slug);

        $heroPath = trim((string) ($itinerary['hero_image'] ?? '')) !== ''
            ? (string) $itinerary['hero_image']
            : (string) ($itinerary['cover_image'] ?? $itinerary['og_image'] ?? '');
        $hero = $this->resolveMedia($heroPath);

        $price = PriceFormatter::forRow($itinerary);
        $priceUnit = trim((string) ($itinerary['price_unit'] ?? ''));

        $dayRows = $repository->days($id);
        $timeline = [];

        foreach ($dayRows as $row) {
            $start = (int) ($row['day_number'] ?? 0);
            $end = (int) ($row['day_end'] ?? $start);
            $label = $start > 0
                ? ($end > $start ? sprintf('Days %d–%d', $start, $end) : sprintf('Day %d', $start))
                : 'Day';

            $timeline[] = [
                'label' => $label,
                'title' => trim((string) ($row['title'] ?? '')),
                'summary' => trim((string) ($row['summary'] ?? '')),
                'stay' => trim((string) ($row['overnight_location'] ?? '')),
                'travel' => trim((string) ($row['travel_time'] ?? '')),
                'image' => trim((string) ($row['image_path'] ?? '')) !== ''
                    ? $this->resolveMedia((string) $row['image_path'])
                    : '',
            ];
        }

        $related = $repository->related($id, (int) ($itinerary['category_id'] ?? 0), 3);

        $metaTitle = trim((string) ($itinerary['meta_title'] ?? ''));
        if ($metaTitle === '') {
            $metaTitle = $name . ' | ' . APP_NAME;
        }

        $metaDescription = trim((string) ($itinerary['meta_description'] ?? ''));
        if ($metaDescription === '') {
            $metaDescription = trim((string) ($itinerary['excerpt'] ?? ''));
        }

        $schema = [];
        try {
            $schema = [schema_breadcrumbs([
                ['name' => 'Home', 'url' => base_url()],
                ['name' => 'Itineraries', 'url' => base_url('itineraries')],
                ['name' => $name, 'url' => $url],
            ])];

            $trip = schema_trip($itinerary, $url);
            if (is_array($trip) && $trip !== []) {
                $schema[] = $trip;
            }
        } catch (Throwable $e) {
            error_log('[ICT itinerary] schema: ' . $e->getMessage());
        }

        return new Page(
            template: 'v3/pages/itineraries/show',
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
                'days' => $days,
                'price' => $price,
                'price_unit' => $priceUnit,
                'hero' => $hero,
                'excerpt' => trim((string) ($itinerary['excerpt'] ?? '')),
                'intro' => (string) ($itinerary['intro'] ?? ''),
                'route_summary' => (string) ($itinerary['route_summary'] ?? ''),
                'best_for' => (string) ($itinerary['best_for'] ?? ''),
                'travel_notes' => (string) ($itinerary['travel_notes'] ?? ''),
                'timeline' => $timeline,
                'related' => $related,
                'cta_url' => base_url('contact'),
            ],
            schema: $schema,
            bodyClass: 'ix-page-itinerary',
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
