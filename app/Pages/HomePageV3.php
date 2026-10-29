<?php
declare(strict_types=1);

namespace ICT\Pages;

use ICT\Content\HomeRepository;
use ICT\Support\MediaResolver;
use ICT\View\Page;
use ICT\View\Renderer;
use Throwable;

/**
 * Controller for the Daylight (v3.1) home page.
 *
 * Copy is intentionally minimal: the template budgets roughly 450 words of body
 * text, so this controller only passes what the layout renders — headline, one
 * supporting line, four counts, three journeys, the matcher set, four
 * destinations, three experiences, three steps, three journal rows and one CTA.
 */
final class HomePageV3
{
    public function __construct(private ?HomeRepository $repository = null)
    {
    }

    public function run(): void
    {
        Renderer::render($this->page(), 'views/v3/layout.php');
    }

    public function page(): Page
    {
        $repository = $this->repository ??= new HomeRepository();
        $homepage = $repository->homepage();
        $settings = $homepage['settings'];
        $hero = $homepage['hero'];

        $heroUrl = $this->resolveMedia((string) ($hero['file_path'] ?? 'assets/images/galle-lighthouse.jpg'));
        $posterPath = (string) ($hero['poster_path'] ?? '');
        $poster = $posterPath !== '' ? $this->resolveMedia($posterPath) : base_url('assets/images/galle-lighthouse.jpg');

        $isVideo = strtolower((string) ($hero['media_type'] ?? 'image')) === 'video'
            && MediaResolver::isVideo($heroUrl);

        $counts = $repository->counts();
        $journeys = $repository->journeys(3);
        $allJourneys = $repository->journeys(13);
        $destinations = $repository->destinations(4);
        $experiences = $repository->experiences(3);
        $articles = $repository->articles(3);

        $seoTitle = trim((string) ($settings['homepage_new_seo_title'] ?? ''));
        if ($seoTitle === '') {
            $seoTitle = 'Sri Lanka Private Tours & Private Driver | ' . APP_NAME;
        }

        $seoDescription = trim((string) ($settings['homepage_new_seo_description'] ?? ''));
        if ($seoDescription === '') {
            $seoDescription = 'Plan a private Sri Lanka tour with your own driver-guide, flexible routes, cultural sites, wildlife, beaches, and practical support from arrival to departure.';
        }

        $cmsHeading = trim((string) ($hero['headline_main'] ?? ''));
        $heading = $cmsHeading !== '' ? $cmsHeading : 'Sri Lanka, without the tour bus';
        $headingEm = $cmsHeading !== '' ? '' : 'at your own pace';

        $data = [
            'islands' => $allJourneys !== [],
            'hero' => [
                'eyebrow' => 'Private driver-guides · Sri Lanka',
                'title' => $heading,
                'title_em' => $headingEm,
                'sub' => 'One guide, one vehicle, your dates.',
                'image' => $poster,
                'video' => $isVideo ? $heroUrl : '',
                'primary_label' => 'Plan my trip',
                'primary_url' => base_url('contact'),
                'secondary_label' => 'Browse journeys',
                'secondary_url' => base_url('itineraries'),
            ],
            'stats' => [
                ['count' => $counts['destinations'], 'label' => 'Destinations'],
                ['count' => $counts['itineraries'], 'label' => 'Itineraries'],
                ['count' => $counts['experiences'], 'label' => 'Experiences'],
                ['count' => $counts['day_tours'], 'label' => 'Day tours'],
            ],
            'journeys' => array_map(static fn(array $card): array => [
                'title' => $card['title'],
                'url' => $card['url'],
                'image' => $card['image'],
                'alt' => $card['alt'],
                'days' => (int) ($card['days'] ?? 0),
                'price' => $card['price'],
                'kicker' => 'Itinerary',
            ], $journeys),
            'matcher' => array_map(static fn(array $card): array => [
                'title' => $card['title'],
                'url' => $card['url'],
                'image' => $card['image'],
                'alt' => $card['alt'],
                'days' => (int) ($card['days'] ?? 0),
                'price' => $card['price'],
            ], $allJourneys),
            // Server-rendered fallback is intentionally short: the island expands
            // it to the full set, and a no-JS visitor gets a link instead of 13
            // card titles of marketing copy.
            'matcherFallback' => array_map(static fn(array $card): array => [
                'title' => $card['title'],
                'url' => $card['url'],
                'image' => $card['image'],
                'alt' => $card['alt'],
                'days' => (int) ($card['days'] ?? 0),
                'price' => $card['price'],
            ], array_slice($allJourneys, 0, 3)),
            'destinations' => $destinations,
            'experiences' => array_map(static fn(array $card): array => [
                'title' => $card['title'],
                'url' => $card['url'],
                'image' => $card['image'],
                'alt' => $card['alt'],
                'kicker' => '',
            ], $experiences),
            'steps' => [
                ['num' => '01', 'title' => 'Send your dates', 'text' => 'Dates and must-see stops.'],
                ['num' => '02', 'title' => 'Get a real route', 'text' => 'Day by day, real drive times.'],
                ['num' => '03', 'title' => 'Travel with one guide', 'text' => 'Arrival to departure.'],
            ],
            'articles' => array_map(static fn(array $card): array => [
                'title' => $card['title'],
                'url' => $card['url'],
                'date' => (string) ($card['meta'][0] ?? ''),
            ], $articles),
            'cta' => [
                'title' => 'Tell us your dates.',
                'body' => 'A route, a price and an honest view of what fits.',
                'label' => 'Plan my trip',
                'url' => base_url('contact'),
            ],
            'whatsapp' => function_exists('site_whatsapp_url') ? site_whatsapp_url() : '',
        ];

        $schema = [];
        try {
            $schema = [schema_website(), schema_travel_agency()];
        } catch (Throwable $e) {
            error_log('[ICT home v3] schema: ' . $e->getMessage());
        }

        $ogImage = trim((string) ($settings['homepage_new_og_image'] ?? ''));

        return new Page(
            template: 'v3/pages/home',
            meta: [
                'title' => $seoTitle,
                'description' => $seoDescription,
                'canonical' => base_url(),
                'image' => $ogImage !== '' ? $this->resolveMedia($ogImage) : $poster,
                'image_alt' => $heading,
                'type' => 'website',
                'robots' => 'index,follow,max-image-preview:large',
            ],
            data: $data,
            schema: $schema,
            bodyClass: 'ix-page-home',
        );
    }

    /**
     * Resolve a stored media value, falling back to bundled art when the file is
     * not present in this checkout (uploads are empty locally).
     */
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
