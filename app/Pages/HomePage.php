<?php
declare(strict_types=1);

namespace ICT\Pages;

use ICT\Content\HomeRepository;
use ICT\Support\MediaResolver;
use ICT\View\Page;
use ICT\View\Renderer;
use Throwable;

/**
 * Controller for the public home page ("/").
 *
 * It assembles a Page model from the repository and hands it to the renderer.
 * Everything the old index.php did inside its template — hero copy, CTA
 * destinations, section ordering, schema — is decided here, so the view stays
 * declarative.
 */
final class HomePage
{
    public function __construct(private ?HomeRepository $repository = null)
    {
    }

    public function run(): void
    {
        Renderer::render($this->page());
    }

    public function page(): Page
    {
        $repository = $this->repository ??= new HomeRepository();
        $homepage = $repository->homepage();
        $settings = $homepage['settings'];
        $hero = $homepage['hero'];

        $heroImage = $this->resolveMediaPath(
            (string) ($hero['file_path'] ?? 'assets/images/galle-lighthouse.jpg')
        );

        $heroIsVideo = strtolower((string) ($hero['media_type'] ?? 'image')) === 'video';
        $posterPath = (string) ($hero['poster_path'] ?? '');
        $poster = $posterPath !== ''
            ? $this->resolveMediaPath($posterPath)
            : base_url('assets/images/galle-lighthouse.jpg');

        $seoTitle = trim((string) ($settings['homepage_new_seo_title'] ?? ''));
        if ($seoTitle === '') {
            $seoTitle = 'Sri Lanka Private Tours & Private Driver | ' . APP_NAME;
        }

        $seoDescription = trim((string) ($settings['homepage_new_seo_description'] ?? ''));
        if ($seoDescription === '') {
            $seoDescription = 'Plan a private Sri Lanka tour with your own driver-guide, flexible routes, cultural sites, wildlife, beaches, and practical support from arrival to departure.';
        }

        $ogImage = trim((string) ($settings['homepage_new_og_image'] ?? ''));

        $journeys = $repository->journeys(3);
        $destinations = $repository->destinations(4);
        $experiences = $repository->experiences(6);
        $dayTours = $repository->dayTours(3);
        $articles = $repository->articles(3);
        $reviews = $repository->reviews(3);

        $data = [
            'hero' => [
                'eyebrow' => trim((string) ($hero['headline_script'] ?? '')) ?: 'The Beauty of Sri Lanka',
                'title' => trim((string) ($hero['headline_main'] ?? '')) ?: 'Private Sri Lanka Tours with Your Own Driver-Guide',
                'supporting' => trim((string) ($hero['supporting_text'] ?? '')) ?: 'Explore private Sri Lanka tours and chauffeur-guided travel for couples, families, wildlife, beaches, and culture — with a flexible itinerary shaped around your dates and pace.',
                'image' => $heroIsVideo ? $poster : $heroImage,
                'video' => $heroIsVideo ? $heroImage : '',
                'primary_label' => trim((string) ($hero['primary_cta_label'] ?? '')) ?: 'Explore Itineraries',
                'primary_url' => $this->toUrl((string) ($hero['primary_cta_url'] ?? 'itineraries')),
                'secondary_label' => trim((string) ($hero['secondary_cta_label'] ?? '')) ?: 'Plan Your Trip',
                'secondary_url' => $this->toUrl((string) ($hero['secondary_cta_url'] ?? 'contact')),
            ],
            'trust' => [
                ['title' => 'Private driver & vehicle', 'text' => 'Your comfort, your pace'],
                ['title' => 'Flexible & customisable', 'text' => 'Shaped around your dates'],
                ['title' => 'Local expertise', 'text' => 'Route and timing know-how'],
            ],
            'finder' => [
                'destinations' => array_slice(array_column($destinations, 'title'), 0, 6),
                'interests' => array_values(array_unique(array_filter(array_column($experiences, 'title')))) ?: ['Wildlife', 'Beaches', 'Culture', 'Tea country'],
            ],
            'intro' => [
                'eyebrow' => 'Why Island Culture',
                'title' => 'A private route through Sri Lanka, planned the way you would plan it yourself.',
                'body' => 'We are a small Sri Lankan team building private, driver-guided journeys rather than selling fixed departures. You keep the itinerary, the pace and the detours — and someone local answers when the plan needs to change.',
            ],
            'journeys' => $journeys,
            'destinations' => $destinations,
            'regions' => $repository->regionLabels(),
            'experiences' => $experiences,
            'dayTours' => $dayTours,
            'articles' => $articles,
            'reviews' => $reviews,
            'steps' => [
                ['number' => '01', 'title' => 'Tell us the shape of the trip', 'text' => 'Dates, travellers, and the two or three things you will not skip.'],
                ['number' => '02', 'title' => 'Get a route, not a catalogue', 'text' => 'A day-by-day plan with driving times that actually work.'],
                ['number' => '03', 'title' => 'Travel with one guide throughout', 'text' => 'The same driver-guide from arrival to departure, reachable on WhatsApp.'],
            ],
            'cta' => [
                'title' => trim((string) ($settings['homepage_new_cta_heading'] ?? '')) ?: 'Ready to Plan Your Sri Lanka Journey?',
                'body' => trim((string) ($settings['homepage_new_cta_body'] ?? '')) ?: 'Tell us your dates and interests. We will come back with a route, a price and an honest view of what fits.',
                'label' => trim((string) ($settings['homepage_new_cta_button_label'] ?? '')) ?: 'Plan My Trip',
                'url' => $this->toUrl((string) ($settings['homepage_new_cta_button_url'] ?? 'contact')),
            ],
        ];

        $schema = [];
        try {
            $schema = [schema_website(), schema_travel_agency()];
            foreach ($reviews as $review) {
                $schema[] = schema_review([
                    'author' => $review['name'],
                    'rating' => (float) $review['rating'],
                    'text' => $review['text'],
                    'date_iso' => $review['date'],
                ]);
            }
        } catch (Throwable $e) {
            error_log('[ICT home] schema: ' . $e->getMessage());
            $schema = [];
        }

        return new Page(
            template: 'pages/home',
            meta: [
                'title' => $seoTitle,
                'description' => $seoDescription,
                'canonical' => base_url(),
                'image' => $ogImage !== '' ? $this->resolveMediaPath($ogImage) : $data['hero']['image'],
                'image_alt' => $data['hero']['title'],
                'type' => 'website',
                'robots' => 'index,follow,max-image-preview:large',
            ],
            data: $data,
            schema: $schema,
            bodyClass: 'ic-v2 ic-page-home',
        );
    }

    /** Accept assets/ paths, upload paths and absolute URLs alike. */
    private function resolveMediaPath(string $path): string
    {
        return MediaResolver::url($path);
    }

    /** Turn a stored CTA target ("contact", "/contact", full URL) into a URL. */
    private function toUrl(string $target): string
    {
        $target = trim($target);

        if ($target === '') {
            return base_url();
        }

        if (preg_match('~^https?://~i', $target) === 1) {
            return $target;
        }

        return base_url(ltrim($target, '/'));
    }
}
