<?php
declare(strict_types=1);

namespace ICT\Pages;

use ICT\Content\CollectionRepository;
use ICT\Support\Pagination;
use ICT\View\Page;
use ICT\View\Renderer;
use Throwable;

/**
 * Controller for the six discovery indexes:
 * /destinations, /experiences, /tours, /itineraries, /day-tours, /activities.
 *
 * The families differ only in their copy, filter set and query definition, so
 * one controller drives all six from the registry below. The view is shared for
 * the same reason (views/pages/collections/index.php).
 *
 * SEO behaviour is inherited, not reinvented:
 *  - a filtered URL canonicalises to the bare base and is noindex,follow
 *    (seo_filter_canonical, same helper the legacy listings used)
 *  - page N > 1 keeps a self-canonical base?page=N, page 1 and out-of-range
 *    pages canonicalise to the base
 */
final class CollectionIndexPage
{
    private ?CollectionRepository $repository = null;

    /** @var array<string,array<string,mixed>> */
    private const FAMILIES = [
        'destinations' => [
            'base' => 'destinations',
            'eyebrow' => 'Where you can go',
            'heading' => 'Sri Lanka destinations',
            'intro' => 'Every stop in the catalogue, from the Cultural Triangle to the south coast. Filter by region or search by name, then open a destination for the full guide.',
            'title' => 'Sri Lanka Destinations | Private Travel Guide | ' . APP_NAME,
            'description' => "Explore Sri Lanka's top destinations — cultural landmarks, hill country, wildlife parks, and coastal beaches. All reachable with a private driver and comfortable vehicle.",
            'per_page' => 12,
            'filters' => ['q', 'region'],
            'list_name' => 'Sri Lanka Destinations',
            'empty' => 'No destinations match that filter yet. Clear the filter to see all of them.',
        ],
        'experiences' => [
            'base' => 'experiences',
            'eyebrow' => 'What you can do',
            'heading' => 'Sri Lanka experiences',
            'intro' => 'Cultural moments, wildlife encounters, food, tea country and the off-route places worth a detour. Every experience can be woven into a private itinerary.',
            'title' => 'Sri Lanka Experiences & Hidden Gems | Private Travel | ' . APP_NAME,
            'description' => "Discover Sri Lanka's cultural moments, wildlife encounters, local food, and off-route hidden gems that can be woven into any private itinerary.",
            'per_page' => 12,
            'filters' => ['q', 'category'],
            'list_name' => 'Sri Lanka Experiences',
            'empty' => 'No experiences match that filter yet. Clear the filter to see all of them.',
        ],
        'tours' => [
            'base' => 'tours',
            'eyebrow' => 'Private tours',
            'heading' => 'Sri Lanka tours',
            'intro' => 'Chauffeur-guided private tours with a fixed shape and a flexible middle: the route, the pace and the stops stay yours to adjust.',
            'title' => 'Sri Lanka Tours | Private Chauffeur-Guided Trips | ' . APP_NAME,
            'description' => 'Browse private Sri Lanka tours, wildlife safaris, cultural routes, beach escapes, and flexible trip ideas arranged by Island Culture Travels.',
            'per_page' => 12,
            'filters' => ['q'],
            'list_name' => 'Sri Lanka Tours',
            'empty' => 'No tours match that search yet.',
        ],
        'itineraries' => [
            'base' => 'itineraries',
            'eyebrow' => 'Signature journeys',
            'heading' => 'Private Sri Lanka itineraries',
            'intro' => 'Ready-to-customise routes from 3 to 14 days. Each one lists its days, driving times and the places it pairs well with.',
            'title' => 'Private Sri Lanka Itineraries | Chauffeur-Guided Tours | ' . APP_NAME,
            'description' => 'Browse ready-to-customise Sri Lanka itineraries — short breaks, family journeys, honeymoons, and multi-region private tours from 3 to 14 days.',
            'per_page' => 12,
            'filters' => ['q', 'days'],
            'list_name' => 'Sri Lanka Itineraries',
            'empty' => 'No itineraries match that filter yet. Clear it to see the full range.',
        ],
        'day-tours' => [
            'base' => 'day-tours',
            'eyebrow' => 'Short and close',
            'heading' => 'Sri Lanka day tours',
            'intro' => 'Single-day private trips from Colombo, Kandy, Galle and the coast — hotel pickup, a driver-guide and a route that fits the day.',
            'title' => 'Sri Lanka Day Tours | Private Chauffeur-Guided Day Trips | ' . APP_NAME,
            'description' => 'Book private Sri Lanka day tours: Sigiriya, Kandy, Yala Safari, Galle Fort, Nuwara Eliya tea country, and more. Chauffeur-guide, flexible pace, hotel pickup.',
            'per_page' => 12,
            'filters' => ['q', 'difficulty', 'price'],
            'list_name' => 'Sri Lanka Day Tours',
            'empty' => 'No day tours match that combination yet. Try a wider price range.',
        ],
        'activities' => [
            'base' => 'activities',
            'eyebrow' => 'Things to do',
            'heading' => 'Activities in Sri Lanka',
            'intro' => 'Landscapes, wildlife, water, culture and crafts — browse the activity types that can anchor a day or a whole route.',
            'title' => 'Activities in Sri Lanka | ' . APP_NAME,
            'description' => 'Browse Sri Lanka activity highlights and discover the experiences that fit a private itinerary.',
            'per_page' => 18,
            'filters' => ['q'],
            'list_name' => 'Sri Lanka Activities',
            'empty' => 'No activities match that search yet.',
        ],
    ];

    public function __construct(private readonly string $family)
    {
    }

    /**
     * POST endpoints that the legacy index pages hosted.
     *
     * /destinations saved the visitor's route builder selection and
     * /experiences saved the wishlist. Both are session-backed, token-checked
     * and answer 204; dropping them would silently break the JS that calls
     * them, so they live here rather than being lost with the old templates.
     *
     * @var array<string,array{action:string,field:string,limit:int}>
     */
    private const SAVES = [
        'destinations' => ['action' => 'save_route', 'field' => 'dest_slugs', 'limit' => 20],
        'experiences' => ['action' => 'save_wishlist', 'field' => 'exp_slugs', 'limit' => 30],
    ];

    public function run(): void
    {
        if (!isset(self::FAMILIES[$this->family])) {
            Renderer::notFound();

            return;
        }

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $this->handleSave();

            return;
        }

        Renderer::render($this->page());
    }

    /**
     * Session save endpoint, preserving the legacy guard order exactly:
     * action -> size guard -> CSRF -> token match -> sanitise -> 204.
     */
    private function handleSave(): void
    {
        $config = self::SAVES[$this->family] ?? null;

        if ($config === null) {
            http_response_code(400);
            exit;
        }

        $action = trim((string) post_input('action', ''));

        if ($action !== $config['action']) {
            http_response_code(400);
            exit;
        }

        guard_post_size();
        require_csrf();

        $postedToken = trim((string) post_input('token', ''));
        $cookieToken = route_session_token(false);

        if ($cookieToken === '' || !hash_equals($cookieToken, $postedToken)) {
            http_response_code(204);
            exit;
        }

        $repository = $this->repository ??= new CollectionRepository();
        $slugs = route_session_sanitize_slugs(
            post_input($config['field'], ''),
            $repository->publishedSlugs($this->family),
            $config['limit']
        );

        route_session_save_slugs($postedToken, $config['field'], $slugs);

        http_response_code(204);
        exit;
    }

    public function page(): Page
    {
        $config = self::FAMILIES[$this->family];
        $repository = $this->repository ??= new CollectionRepository();

        $filters = $this->readFilters($config['filters']);
        $requestedPage = Pagination::currentPage($_GET);
        $page = $requestedPage;
        $base = base_url($config['base']);

        $result = $repository->page($this->family, $filters, $page, (int) $config['per_page']);
        $window = Pagination::window($base, $page, (int) $result['total'], (int) $config['per_page']);

        // Filtered listings canonicalise to the base and drop out of the index,
        // exactly as seo_filter_canonical() does everywhere else on the site.
        $filtered = seo_filter_canonical($base, array_filter($filters, static fn(string $v): bool => $v !== ''));

        if ($filtered['robots'] !== null) {
            $canonical = $filtered['canonical'];
            $robots = $filtered['robots'];
        } else {
            // Canonical uses the *requested* page, not the clamped one, so a
            // page beyond the last page canonicalises to the base instead of
            // aliasing the final page (BlogPaginationCanonicalTest rule).
            $canonical = Pagination::canonical($base, $requestedPage, $window['pages']);
            $robots = 'index,follow,max-image-preview:large';
        }

        $heading = (string) $config['heading'];
        $title = (string) $config['title'];
        $description = (string) $config['description'];
        $heroImage = '';

        if ($this->family === 'activities') {
            $hub = $repository->hub();
            if ($hub !== null) {
                $heading = trim((string) ($hub['name'] ?? '')) ?: $heading;
                $title = trim((string) ($hub['meta_title'] ?? '')) ?: $title;
                $description = trim((string) ($hub['meta_description'] ?? '')) ?: $description;
                $heroImage = trim((string) ($hub['og_image'] ?? $hub['hero_image'] ?? ''));
            }
        }

        $schema = [];
        try {
            $schema = [schema_breadcrumbs([
                ['name' => 'Home', 'url' => base_url()],
                ['name' => $heading, 'url' => $base],
            ])];

            if ($result['items'] !== []) {
                $schema[] = schema_item_list(
                    (string) $config['list_name'],
                    array_map(
                        static fn(array $card): array => [
                            'name' => (string) $card['title'],
                            'url' => (string) $card['url'],
                            'image' => (string) $card['image'],
                        ],
                        $result['items']
                    )
                );
            }
        } catch (Throwable $e) {
            error_log('[ICT collections] schema: ' . $e->getMessage());
            $schema = [];
        }

        return new Page(
            template: 'pages/collections/index',
            meta: [
                'title' => $title,
                'description' => $description,
                'canonical' => $canonical,
                'image' => $heroImage !== '' ? \ICT\Support\MediaResolver::url($heroImage) : \DEFAULT_OG_IMAGE,
                'type' => 'website',
                'robots' => $robots,
            ],
            data: [
                'family' => $this->family,
                'eyebrow' => $config['eyebrow'],
                'heading' => $heading,
                'intro' => $config['intro'],
                'empty' => $config['empty'],
                'items' => $result['items'],
                'total' => $result['total'],
                'window' => $window,
                'filters' => $filters,
                'facets' => $repository->facets($this->family),
                'themes' => $this->family === 'tours' ? $repository->themes() : [],
                'base_url' => $base,
                'query' => $this->cleanQuery(),
            ],
            schema: $schema,
            bodyClass: 'ic-v2 ic-page-collection ic-page-' . $this->family,
        );
    }

    /**
     * Only the filters this family advertises are read; everything else in the
     * query string is ignored, so a made-up parameter cannot reach the SQL.
     *
     * @param list<string> $allowed
     * @return array<string,string>
     */
    private function readFilters(array $allowed): array
    {
        $filters = [];

        foreach ($allowed as $key) {
            $value = $_GET[$key] ?? '';
            $filters[$key] = is_scalar($value) ? trim((string) $value) : '';
        }

        return $filters;
    }

    /** Query string for links, without the page key or empty values. */
    private function cleanQuery(): array
    {
        $query = [];

        foreach (['q', 'region', 'category', 'difficulty', 'price', 'days'] as $key) {
            $value = $_GET[$key] ?? '';
            if (is_scalar($value) && trim((string) $value) !== '') {
                $query[$key] = trim((string) $value);
            }
        }

        return $query;
    }
}
