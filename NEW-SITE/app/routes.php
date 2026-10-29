<?php
declare(strict_types=1);

namespace NewSite;

/** Register only exact public records delivered by the optional verified adapter. */
function catalog_route_manifest(?array $records = null): array
{
    static $routes = [];
    if ($records === null) {
        return $routes;
    }
    $routes = [];
    foreach (['destination' => 'destinations', 'itinerary' => 'itineraries', 'tour' => 'tours',
        'day-tour' => 'day-tours', 'experience' => 'experiences', 'blog' => 'blog'] as $family => $prefix) {
        foreach ($records[$family] ?? [] as $record) {
            $slug = $record['slug'] ?? '';
            if (!is_string($slug) || strlen($slug) > 180 || !preg_match('/\A[a-z0-9]+(?:-[a-z0-9]+)*\z/D', $slug)
                || !is_string($record['title'] ?? null) || $record['title'] === '') {
                continue;
            }
            $path = '/' . $prefix . '/' . $slug;
            if (($record['url'] ?? '') !== $path) {
                continue;
            }
            $routes[$path] = ['title' => $record['title'], 'family' => $family,
                'kind' => $family === 'blog' ? 'article' : 'detail', 'slug' => $slug];
            $duration = (int) ($record['duration_days'] ?? $record['duration'] ?? 0);
            if ($family === 'itinerary' && $duration >= 1 && $duration <= 60) {
                $routes['/' . $duration . '-day-itinerary'] = ['title' => $duration . '-day journeys',
                    'family' => 'itinerary', 'kind' => 'collection', 'duration' => $duration];
            }
        }
    }
    return $routes;
}

/** Concrete preview registry, never a generic slug-to-200 catchall. */
function route_manifest(?array $environment = null): array
{
    $routes = [];
    $add = static function (string $path, string $title, string $family, string $kind, array $extra = []) use (&$routes): void {
        $routes[$path] = ['title' => $title, 'family' => $family, 'kind' => $kind] + $extra;
    };
    $add('/', 'Sri Lanka, at your pace', 'static', 'home');
    foreach (['destinations' => ['Destinations', 'destination'], 'itineraries' => ['Journey inspiration', 'itinerary'],
        'tours' => ['Private tours', 'tour'], 'day-tours' => ['Day tours', 'day-tour'],
        'experiences' => ['Experiences', 'experience'], 'blog' => ['Travel journal', 'blog']] as $path => [$title, $family]) {
        $add('/' . $path, $title, $family, 'collection');
    }
    $details = [
        'destination' => ['destinations', ['galle' => 'Galle', 'ella' => 'Ella hills', 'kandy' => 'Kandy', 'mirissa' => 'Mirissa', 'yala' => 'Yala']],
        'itinerary' => ['itineraries', ['demo-hills-coast' => 'From green hills to the coast', 'demo-southern-perspectives' => 'Southern perspectives', 'demo-island-contrasts' => 'An island of contrasts']],
        'tour' => ['tours', ['demo-cultural' => 'Cultural perspectives', 'demo-hills-coast' => 'Hills and coast private tour']],
        'day-tour' => ['day-tours', ['demo-galle' => 'Galle coastal day', 'demo-kandy' => 'Kandy cultural day', 'demo-ella' => 'Ella hillside day']],
        'experience' => ['experiences', ['demo-coastal-streets' => 'Coastal perspectives', 'demo-hill-country' => 'Into the hill country', 'demo-wildlife-landscapes' => 'Wild landscapes']],
        'blog' => ['blog', ['demo-coastal-chapter' => 'A coastal chapter', 'demo-change-of-scenery' => 'A change of scenery', 'demo-planning-space' => 'Leaving room in a journey']],
    ];
    foreach ($details as $family => [$prefix, $records]) {
        foreach ($records as $slug => $title) {
            $add('/' . $prefix . '/' . $slug, $title, $family, $family === 'blog' ? 'article' : 'detail', ['slug' => $slug]);
        }
    }
    // These are explicit category records. A tour with the same slug wins in page resolution.
    $add('/tours/cultural', 'Cultural journey samples', 'tour', 'collection', ['category' => 'cultural']);
    $add('/from', 'Travel planning by departure country', 'guide', 'collection', ['guide_group' => 'country']);
    foreach (['united-kingdom' => 'United Kingdom', 'australia' => 'Australia', 'india' => 'India'] as $slug => $title) {
        $add('/from/' . $slug, 'Sri Lanka from ' . $title, 'guide', 'article', ['slug' => 'from-' . $slug, 'guide_group' => 'country']);
    }
    $add('/seasonal', 'When to visit', 'guide', 'collection', ['guide_group' => 'month']);
    foreach (['january', 'february', 'march', 'april', 'may', 'june', 'july', 'august', 'september', 'october', 'november', 'december'] as $month) {
        $add('/sri-lanka-in-' . $month, 'Sri Lanka in ' . ucfirst($month), 'guide', 'article', ['slug' => 'month-' . $month, 'guide_group' => 'month']);
    }
    foreach (['honeymoon', 'family', 'adventure', 'wellness', 'luxury', 'wildlife'] as $theme) {
        $add('/' . $theme . '-tours', ucfirst($theme) . ' journey inspiration', 'guide', 'article', ['slug' => 'theme-' . $theme, 'theme' => $theme]);
    }
    // Only durations backed by explicitly labelled sample records are registered.
    foreach ([7, 10, 14] as $days) {
        $add('/' . $days . '-day-itinerary', $days . '-day illustrative journeys', 'itinerary', 'collection', ['duration' => $days]);
    }
    foreach (['about-us' => ['About Island Culture Travels', 'about'], 'why-choose-us' => ['Why travel with us', 'why'],
        'reviews' => ['Traveller reviews', 'reviews'], 'faq' => ['Travel questions', 'faq'],
        'contact' => ['Plan your trip', 'contact'], 'plan' => ['Trip planner', 'plan'],
        'compare' => ['Compare journeys', 'compare'], 'ai-search' => ['Explore the sample catalog', 'ai'],
        'privacy_choices.php' => ['Privacy choices preview', 'privacy']] as $path => [$title, $type]) {
        $add('/' . $path, $title, 'support', 'support', ['support_type' => $type]);
    }
    foreach (['private-driver-sri-lanka' => ['Private chauffeur travel', 'private-driver-sri-lanka'],
        'private-driver-sri-lanka/cost' => ['Private driver cost information', 'private-driver-sri-lanka-cost'],
        'airport-transfers' => ['Airport transfers', 'airport-transfers'],
        'sri-lanka-private-tours' => ['Sri Lanka private tours', 'sri-lanka-private-tours'],
        'sri-lanka-tour-cost' => ['Sri Lanka tour cost information', 'sri-lanka-tour-cost'],
        'sri-lanka-airport-transfers' => ['Sri Lanka airport transfers', 'sri-lanka-airport-transfers'],
        'colombo-airport-to-sigiriya' => ['Colombo Airport to Sigiriya', 'colombo-airport-to-sigiriya'],
        'sigiriya-to-kandy-private-transfer' => ['Sigiriya to Kandy private transfer', 'sigiriya-to-kandy-private-transfer'],
        'kandy-to-ella-private-transfer' => ['Kandy to Ella private transfer', 'kandy-to-ella-private-transfer'],
        'ella-to-yala-private-transfer' => ['Ella to Yala private transfer', 'ella-to-yala-private-transfer'],
        'ella-to-udawalawe-private-transfer' => ['Ella to Udawalawe private transfer', 'ella-to-udawalawe-private-transfer'],
        'yala-to-mirissa-private-transfer' => ['Yala to Mirissa private transfer', 'yala-to-mirissa-private-transfer'],
        'galle-to-colombo-airport-private-transfer' => ['Galle to Colombo Airport private transfer', 'galle-to-colombo-airport-private-transfer']] as $path => [$title, $slug]) {
        $add('/' . $path, $title, 'static', 'support', ['slug' => $slug, 'support_type' => 'service']);
    }
    foreach (['privacy-policy', 'cookie-policy', 'travel-insurance', 'accessibility', 'complaints',
        'responsible-travel', 'company-credentials', 'traveller-support', 'booking-terms-and-conditions',
        'cancellation-refund-policy', 'payment-deposit-policy', 'book-with-confidence', 'trust-safety',
        'drivers-vehicles', 'how-booking-works', 'tour-inclusions-exclusions'] as $slug) {
        $add('/' . $slug, ucwords(str_replace('-', ' ', $slug)), 'support', 'support', ['support_type' => 'policy', 'publication_status' => 'unavailable']);
    }
    foreach (['' => 'Your travel space', '/login' => 'Account preview', '/customize-itinerary' => 'Customize a sample journey',
        '/plans/1' => 'Synthetic plan preview', '/inquiries/1' => 'Synthetic inquiry preview'] as $suffix => $title) {
        $add('/account' . $suffix, $title, 'account', 'account', ['support_type' => 'account' . ($suffix === '' ? '' : '-' . str_replace('/', '-', trim($suffix, '/')))]);
    }
    $partners = $environment === null ? getenv('NEW_SITE_DEMO_PARTNERS') : ($environment['NEW_SITE_DEMO_PARTNERS'] ?? false);
    if ($partners === '1') {
        $add('/business-partners', 'Synthetic partner directory', 'partner', 'collection');
        $add('/business-partners/apply', 'Partner application preview', 'partner', 'support', ['support_type' => 'partners-apply']);
        $add('/business-partners/stays', 'Synthetic stays category', 'partner', 'collection', ['category' => 'stays']);
        $add('/business-partners/partner/demo-coastal-stay', 'Synthetic coastal stay', 'partner', 'detail', ['slug' => 'demo-coastal-stay']);
    }
    return array_replace($routes, catalog_route_manifest());
}

function resolve_route(array $server, array $query = [], ?array $environment = null): array
{
    $path = request_path($server);
    if ($path === '/index.php') {
        $candidate = $query['route'] ?? '/';
        $path = is_string($candidate) ? normalized_path($candidate) : null;
    }
    if ($path === null) {
        return ['route' => '/', 'status' => 400, 'title' => 'Invalid preview route', 'kind' => 'not-found', 'family' => 'support', 'query' => []];
    }
    $entry = route_manifest($environment)[$path] ?? null;
    return ['route' => $path, 'status' => $entry === null ? 404 : 200,
        'title' => $entry['title'] ?? 'Page not found', 'kind' => $entry['kind'] ?? 'not-found',
        'family' => $entry['family'] ?? 'support', 'query' => $query] + ($entry ?? []);
}
