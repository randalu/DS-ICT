<?php
declare(strict_types=1);

// Standalone: no original controllers, root bootstrap, database or external requests.
require_once dirname(__DIR__) . '/app/bootstrap.php';
set_error_handler(static function (int $severity, string $message, string $file, int $line): never {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

use function NewSite\{build_page, catalog_records, catalog_route_manifest, content_state,
    filtered_items, media_allowlist, page_model, paginate_items, query_text, resolve_route, route_manifest};

$checks = 0;
$check = static function (bool $condition, string $message) use (&$checks): void {
    $checks++;
    if (!$condition) {
        fwrite(STDERR, 'FAIL: ' . $message . PHP_EOL);
        exit(1);
    }
};
$server = ['REQUEST_URI' => '/', 'REQUEST_METHOD' => 'GET'];
$resolve = static fn (string $path, array $query = [], array $env = []): array => resolve_route(array_replace($server, ['REQUEST_URI' => $path]), $query, $env);
$page = static fn (string $path, array $query = [], array $env = []): array => build_page($resolve($path, $query, $env), $env);
$manifest = route_manifest([]);
$inventory = json_decode((string) file_get_contents(dirname(__DIR__) . '/app/inventory.json'), true, 512, JSON_THROW_ON_ERROR);
$check($inventory['defaultConcreteRouteCount'] === count($manifest), 'inventory route count agrees with registry');
$families = [];
foreach ($manifest as $path => $entry) {
    $resolved = $resolve($path);
    $check($resolved['status'] === 200, 'registered route resolves: ' . $path);
    $model = $page($path);
    $check($model['status'] === 200, 'registered route has backed model: ' . $path);
    $check($model['page_kind'] !== 'milestone', 'no deferred milestone pages: ' . $path);
    foreach (['family', 'record', 'items', 'filters', 'filter_options', 'pagination', 'breadcrumbs', 'related', 'support_type'] as $key) {
        $check(array_key_exists($key, $model), 'page contract ' . $path . ':' . $key);
    }
    foreach ($model['items'] as $item) {
        $check(isset($manifest[$item['url']]), 'collection card has explicit detail route');
        $check(!isset($item['days']) || is_int($item['days']), 'card never contains timeline array');
    }
    $families[$entry['family']] = true;
    if (in_array($entry['kind'], ['detail', 'article'], true)) {
        $record = $model['record'];
        $check(is_array($record), 'detail backed by actual fixture');
        foreach (['id', 'slug', 'title', 'summary', 'image', 'image_alt', 'url', 'label', 'demo'] as $key) {
            $check(isset($record[$key]), 'detail base key ' . $key);
        }
        $check(str_starts_with($record['id'], 'demo-') && $record['demo'] === true, 'synthetic identity');
        $check($record['label'] === 'Design sample - not a published offer', 'explicit sample label');
        $check(in_array($record['image'], media_allowlist(), true), 'approved image allowlist only');
        $check(!isset($record['price']) && !isset($record['rating']) && !isset($record['reviews']), 'no invented commercial proof');
    }
    $closed = $page($path, [], ['NEW_SITE_DB_DSN' => '']);
    $check($closed['mode'] === 'unavailable', 'partial configuration fails closed');
    $check($closed['record'] === null && $closed['items'] === [] && $closed['related'] === [], 'unavailable never leaks fixture records');
}
foreach (['destination', 'itinerary', 'tour', 'day-tour', 'experience', 'blog', 'guide', 'static', 'support', 'account'] as $family) {
    $check(isset($families[$family]), 'family coverage: ' . $family);
}
foreach (['/itineraries/unknown', '/destinations/not-a-place', '/tours/not-a-category', '/tours/cultural/extra', '/blog/not-a-post',
    '/from/unknown', '/sri-lanka-in-smarch', '/account/logout', '/account/plans/2', '/account/inquiries/2',
    '/account/save', '/account_plan_policy_accept.php', '/privacy_choices_save.php', '/track-event.php',
    '/business-partners', '/business-partners/apply', '/business-partners/partner/demo-coastal-stay'] as $path) {
    $check($page($path)['status'] === 404, 'unknown, disabled or stateful route is 404: ' . $path);
}
foreach (range(1, 60) as $days) {
    $model = $page('/' . $days . '-day-itinerary');
    $check($model['status'] === (in_array($days, [7, 10, 14], true) ? 200 : 404), 'exact-day routes require matching records: ' . $days);
}
foreach ([0, 61, 99, 100] as $days) {
    $check($page('/' . $days . '-day-itinerary')['status'] === 404, 'out-of-bounds exact days rejected');
}
$check($page('/07-day-itinerary')['status'] === 404, 'zero-padded duration is not invented route');
$check(count($page('/itineraries')['items']) === 3, 'three illustrative itinerary records');
$check(count($page('/destinations')['items']) === 5, 'five approved destination records');
$check(count($page('/destinations', ['region' => 'south', 'q' => 'galle'])['items']) === 1, 'destination region and search filters');
$check(count($page('/day-tours')['items']) === 3, 'three day tour records');
$check(count($page('/experiences')['items']) === 3, 'three experience records');
$check(count($page('/blog')['items']) === 3, 'three article samples');
$check(count($page('/from')['items']) === 3 && count($page('/seasonal')['items']) === 12, 'country and month guide records');
$check(count($page('/tours')['items']) === 2, 'proper tours separate from categories');
$category = $page('/tours/cultural');
$check($category['page_kind'] === 'collection' && count($category['items']) === 2, 'category fallback backed by category record');
$cost = $page('/private-driver-sri-lanka/cost');
$check($cost['record']['slug'] === 'private-driver-sri-lanka-cost' && $cost['support_type'] === 'service', 'special private-driver cost mapping');
foreach (['/booking-terms-and-conditions', '/privacy-policy', '/cancellation-refund-policy', '/payment-deposit-policy', '/trust-safety'] as $path) {
    $policy = $page($path);
    $check($policy['publication_status'] === 'unavailable' && $policy['record'] === null, 'policy approved publication status unavailable; no invented text');
}
$check($page('/booking-terms')['status'] === 404 && $page('/airport-transfer-galle')['status'] === 404, 'unverified guessed legacy routes are not registered');
$check($page('/reviews')['reviews'] === [] && $page('/reviews')['items'] === [], 'real review empty state only');
foreach (['/account', '/account/login', '/account/customize-itinerary', '/account/plans/1', '/account/inquiries/1'] as $path) {
    $check($page($path)['page_kind'] === 'account', 'explicit synthetic account screen');
}
$check(count($page('/itineraries', ['days' => '7'])['items']) === 1, 'days filter matches real sample duration');
$check(count($page('/itineraries', ['theme' => 'wildlife', 'traveller' => 'friends', 'start' => 'galle'])['items']) === 1, 'combined itinerary filters');
$check($page('/itineraries', ['days' => '7', 'theme' => 'wildlife'])['items'] === [], 'server-rendered empty results');
$check(count($page('/day-tours', ['region' => 'south', 'difficulty' => 'easy', 'price' => 'unquoted', 'q' => 'galle'])['items']) === 1, 'all day tour filter names');
$check(count($page('/experiences', ['mood' => 'nature'])['items']) === 1, 'mood filter');
$check(count($page('/blog', ['q' => 'scenery', 'category' => 'hills'])['items']) === 1, 'article search and category');
$check($page('/blog', ['q' => '<script>'])['items'] === [], 'search markup is inert text, not a match');
foreach (['q', 'page', 'days', 'theme', 'traveller', 'start', 'region', 'difficulty', 'price', 'mood', 'category', 'ids'] as $field) {
    $check(query_text([$field => ['nested' => 'x']], $field) === '', 'array GET ignored: ' . $field);
}
$check(query_text(['q' => str_repeat('x', 121)], 'q', 120) === '', 'oversized search ignored');
$check(query_text(['q' => "a\0b"], 'q') === '', 'control characters ignored');
$check(query_text(['q' => "\xFF"], 'q') === '', 'invalid UTF-8 ignored');
$check($page('/blog', ['page' => '999999'])['pagination']['page'] === 1, 'over-range page clamps to last available');
$check($page('/blog', ['page' => '-1'])['pagination']['page'] === 1, 'invalid page normalizes to 1');
$check($page('/blog')['pagination']['per_page'] === 9, 'blog nine-per-page contract');
$rows = array_fill(0, 20, ['title' => 'Synthetic pager test']);
$pager = paginate_items($rows, ['page' => '2'], '/blog', ['q' => 'coast', 'category' => 'planning']);
$check(count($pager['items']) === 9 && $pager['pagination']['total'] === 20 && $pager['pagination']['pages'] === 3, 'consistent nine-row pagination');
$check($pager['pagination']['next_url'] === '/blog?q=coast&category=planning&page=3', 'pagination preserves bounded filter states');
$check(paginate_items($rows, ['page' => '99'], '/blog', [])['pagination']['page'] === 3, 'valid out-of-range clamps to last');
$check(paginate_items($rows, ['page' => ['99']], '/blog', [])['pagination']['page'] === 1, 'array page normalizes safely');
$plan = $page('/plan', ['d' => '10', 'theme' => 'wildlife']);
$check(count($plan['items']) === 1 && $plan['filters']['d'] === '10', 'planner GET design transition');
$journeys = $page('/itineraries')['items'];
$ids = array_column($journeys, 'id');
$comparison = $page('/compare', ['ids' => implode(',', [$ids[0], 'unknown', $ids[1], $ids[0]])]);
$check(count($comparison['items']) === 2 && count($comparison['comparison_options']) === 3, 'compare fixture ID allowlist and deduplication');
$check($page('/compare', ['ids' => ['1']])['items'] === [], 'compare arrays cannot become IDs');
$check(count($page('/ai-search', ['q' => 'galle'])['items']) > 0, 'server-rendered local catalog search');
$detail = $page('/itineraries/demo-hills-coast')['record'];
$check(is_array($detail['days']) && count($detail['days']) === 7 && $detail['duration_days'] === 7, 'detail timeline and numeric duration distinct');
$check(str_contains($detail['duration_label'], 'illustrative sample'), 'duration labelled illustrative');
$home = $page('/');
foreach ($home['journeys'] as $card) {
    $check(!isset($card['days']) && isset($manifest[$card['url']]), 'approved homepage data shape preserved; real demo detail link');
}
$partnerEnv = ['NEW_SITE_DEMO_PARTNERS' => '1'];
$check(count(route_manifest($partnerEnv)) === count($manifest) + 4, 'exactly four opt-in partner routes');
$check($inventory['optInPartnerConcreteRouteCount'] === count(route_manifest($partnerEnv)), 'inventory partner count agrees with registry');
$check($page('/business-partners', [], $partnerEnv)['items'][0]['demo'] === true, 'partner fixture explicitly synthetic');
$check($page('/business-partners/partner/demo-coastal-stay', [], $partnerEnv)['record']['demo'] === true, 'partner detail synthetic');
$check($page('/business-partners/nope', [], $partnerEnv)['status'] === 404, 'unknown partner category still404');
$fixture = require dirname(__DIR__) . '/fixtures/pages.php';
$missingService = page_model(array_replace($cost, ['mode' => 'database', 'record' => null]), $resolve('/private-driver-sri-lanka/cost'), ['records' => []]);
$check($missingService['status'] === 200 && $missingService['record'] === null && $missingService['publication_status'] === 'unavailable', 'known source-unavailable service keeps explicit support layout200');
$compareCatalog = ['records' => ['itinerary' => []]];
for ($index = 1; $index <= 6; $index++) {
    $compareCatalog['records']['itinerary']['limit-' . $index] = ['id' => 'demo-limit-' . $index, 'title' => 'Synthetic limit check'];
}
$compareResolved = $resolve('/compare', ['ids' => 'demo-limit-1,demo-limit-2,demo-limit-3,demo-limit-4,demo-limit-5,demo-limit-6']);
$compareLimit = page_model($page('/compare'), $compareResolved, $compareCatalog);
$check(count($compareLimit['items']) === 4, 'comparison max four allowlisted records');
$fixture['records']['tour']['cultural'] = array_replace($fixture['records']['tour']['demo-cultural'], ['slug' => 'cultural', 'url' => '/tours/cultural', 'title' => 'Collision test tour']);
$collision = page_model($category, $resolve('/tours/cultural'), $fixture);
$check($collision['page_kind'] === 'detail' && $collision['record']['title'] === 'Collision test tour', 'tour-first precedence over category fallback');
$syntheticSource = ['itinerary' => ['source-sample' => ['id' => '42', 'slug' => 'source-sample', 'url' => '/itineraries/source-sample', 'title' => 'Pure sourced-route test', 'duration_days' => 8]]];
$syntheticSource['itinerary']['invalid'] = ['id' => '43', 'slug' => '../private', 'url' => '/itineraries/../private', 'title' => 'Must not register'];
$syntheticSource['tour']['invalid-url'] = ['id' => '44', 'slug' => 'invalid-url', 'url' => 'https://external.test', 'title' => 'Must not register'];
catalog_route_manifest($syntheticSource);
$check(count(catalog_route_manifest()) === 2, 'source registry accepts only valid exact family/slug/url records');
$check($resolve('/itineraries/source-sample')['status'] === 200, 'exact adapter records can register new slugs without a generic catchall');
$check($resolve('/8-day-itinerary')['status'] === 200, 'adapter duration route requires an actual record');
$check($resolve('/itineraries/another-unknown')['status'] === 404, 'source registry does not make unknown slugs200');
catalog_route_manifest([]);
$check($resolve('/itineraries/source-sample')['status'] === 404, 'source registry resets when unavailable or fixture mode');
$check(content_state([])['mode'] === 'fixture', 'standalone tests never require database');
$check(session_status() === PHP_SESSION_NONE, 'page tests never start sessions');
foreach (get_included_files() as $file) {
    $check(str_starts_with(realpath($file), realpath(dirname(__DIR__)) . DIRECTORY_SEPARATOR), 'only NEW-SITE imports');
}
echo 'PASS: ' . $checks . ' page assertions; ' . count($manifest) . ' default concrete routes; ' . count(route_manifest($partnerEnv)) . ' with synthetic partners.' . PHP_EOL;
