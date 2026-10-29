<?php
declare(strict_types=1);

// Standalone, side-effect-free assertions. No Composer/root test bootstrap.
require_once dirname(__DIR__) . '/app/bootstrap.php';

use function NewSite\{build_page, contained_file, content_state, e, icon, media,
    media_allowlist, normalized_path, public_asset, request_guard, resolve_route,
    route_manifest, source_image, url};

$checks = 0;
$check = static function (bool $condition, string $message) use (&$checks): void {
    $checks++;
    if (!$condition) {
        fwrite(STDERR, 'FAIL: ' . $message . PHP_EOL);
        exit(1);
    }
};
$server = ['REMOTE_ADDR' => '127.0.0.1', 'HTTP_HOST' => '127.0.0.1:8099',
    'SERVER_PORT' => '8099', 'REQUEST_METHOD' => 'GET', 'REQUEST_URI' => '/'];
$check(request_guard($server) === 200, 'loopback request accepted');
foreach (['192.168.1.1', '8.8.8.8', '', '127.0.0.2', '::ffff:127.0.0.1'] as $ip) {
    $check(request_guard(array_replace($server, ['REMOTE_ADDR' => $ip])) === 403, 'non-approved remote denied');
}
foreach (['evil.example:8099', 'localhost.evil:8099', '127.0.0.1:80', '127.0.0.1:65536', '127.0.0.1:8099@evil', "localhost\r\nX: a"] as $host) {
    $check(request_guard(array_replace($server, ['HTTP_HOST' => $host])) === 403, 'invalid host denied');
}
$check(request_guard(array_replace($server, ['HTTP_HOST' => 'localhost:8099'])) === 200, 'localhost host accepted');
$check(request_guard(array_replace($server, ['REMOTE_ADDR' => '::1', 'HTTP_HOST' => '[::1]:8099'])) === 200, 'IPv6 loopback accepted');
foreach (['POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS', 'TRACE', 'CONNECT'] as $method) {
    $check(request_guard(array_replace($server, ['REQUEST_METHOD' => $method])) === 405, 'unsafe method denied');
}
$check(request_guard(array_replace($server, ['REQUEST_METHOD' => 'HEAD'])) === 200, 'HEAD accepted');
foreach (['HTTP_FORWARDED', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED_HOST'] as $header) {
    $check(request_guard($server + [$header => '127.0.0.1']) === 403, 'forwarding header denied');
}
foreach (['//evil', '/a/../b', '/%2e%2e/.env', '/%252e%252e/file', '/foo%2f..%2fbar', '/foo\\bar', '/foo%00bar', '/foo//bar', '/.env', 'https://example.com', '/foo?bar', '/foo#bar'] as $path) {
    $check(normalized_path($path) === null, 'unsafe path rejected: ' . $path);
}
$check(normalized_path('/destinations/') === '/destinations', 'trailing slash normalized');
$check(normalized_path('/DESTINATIONS/Galle/') === '/destinations/galle', 'original case-insensitive path behavior preserved');
$check(normalized_path('/destinations/%67alle') === '/destinations/galle', 'single decode normalized');
$home = resolve_route($server);
$check($home['status'] === 200 && $home['kind'] === 'home', 'homepage resolves');
$entry = resolve_route(array_replace($server, ['REQUEST_URI' => '/index.php?route=%2Fcontact']), ['route' => '/contact']);
$check($entry['route'] === '/contact' && $entry['status'] === 200 && $entry['kind'] === 'support', 'query entrypoint resolves known route');
$bad = resolve_route(array_replace($server, ['REQUEST_URI' => '/index.php']), ['route' => ['contact']]);
$check($bad['status'] === 400, 'array route denied');
$bad = resolve_route(array_replace($server, ['REQUEST_URI' => '/index.php']), ['route' => '/../../config']);
$check($bad['status'] === 400, 'query traversal denied');
$unknown = resolve_route(array_replace($server, ['REQUEST_URI' => '/not-a-published-place']));
$check($unknown['status'] === 404 && $unknown['kind'] === 'not-found', 'unknown routes are real 404s');
foreach (['/account/logout', '/subscribe.php', '/track-event.php', '/admin', '/install.php', '/account/plans/99'] as $path) {
    $check(resolve_route(array_replace($server, ['REQUEST_URI' => $path]))['status'] === 404, 'stateful/private route not registered');
}
$check(url('https://example.com') === '/', 'external navigation denied');
$check(url('//evil.test') === '/', 'scheme-relative navigation denied');
$check(url('/contact', ['q' => '<demo>']) === '/contact?q=%3Cdemo%3E', 'local query encoding');
$check(e('<script>"&') === '&lt;script&gt;&quot;&amp;', 'HTML escaping');
$check(icon('unknown') === '', 'unknown icons do not accept markup');
$check(str_contains(icon('menu', '"><script>'), '&quot;&gt;&lt;script&gt;'), 'icon class escaped');
$check(content_state([])['mode'] === 'fixture', 'absent config uses honest fixture mode');
foreach ([['NEW_SITE_DB_DSN' => ''], ['NEW_SITE_DB_USER' => 'root'], ['NEW_SITE_DB_PASSWORD' => ''],
    ['NEW_SITE_DB_DSN' => 'mysql:host=not-contacted', 'NEW_SITE_DB_USER' => 'demo', 'NEW_SITE_DB_PASSWORD' => 'not-used']] as $config) {
    $check(content_state($config)['mode'] === 'unavailable', 'supplied/partial config fails closed without connection');
}
$page = build_page($home, []);
foreach (['route', 'title', 'description', 'canonical', 'status', 'mode', 'source_message', 'page_kind',
    'hero', 'destinations', 'journeys', 'experiences', 'articles', 'reviews', 'active_journey', 'milestones', 'route_manifest'] as $key) {
    $check(array_key_exists($key, $page), 'page contract key ' . $key);
}
$check($page['mode'] === 'fixture' && $page['reviews'] === [] && is_int($page['active_journey']), 'honest fixture contract');
$check($page['canonical'] === 'https://srilankaprivatetravels.com/', 'canonical production identity preserved');
foreach (['destinations', 'journeys', 'experiences', 'articles'] as $collection) {
    foreach ($page[$collection] as $card) {
        foreach (['id', 'title', 'summary', 'image', 'image_alt', 'url', 'label'] as $key) {
            $check(isset($card[$key]) && $card[$key] !== '', $collection . ' card key ' . $key);
        }
        $check(str_starts_with($card['id'], 'demo-'), 'synthetic ID explicitly demo-prefixed');
        $check(str_contains($card['label'], 'Design sample') && str_contains($card['label'], 'not a published offer'), 'sample commercial label explicit');
        $check(!isset($card['price']) && !isset($card['days']) && !isset($card['rating']), 'no invented commercial facts');
        $check(isset(route_manifest()[$card['url']]), 'card stays within known local registry');
        $check(source_image($card['image']) !== null, 'fixture image exists and MIME verified');
    }
}
foreach ($page['journeys'] as $journey) {
    $check(str_contains($journey['route_label'], 'Illustrative') && str_contains($journey['route_label'], 'not a published itinerary'), 'route sequence explicitly illustrative');
    foreach ($journey['route'] as $stop) {
        $check(isset($stop['title'], route_manifest()[$stop['url']]), 'ordered stop contract');
    }
}
$unavailable = build_page($home, ['NEW_SITE_DB_DSN' => 'invalid']);
$check($unavailable['mode'] === 'unavailable' && $unavailable['journeys'] === [] && $unavailable['destinations'] === [], 'unavailable never silently falls back to fixture catalogue');
foreach (media_allowlist() as $path) {
    $check(source_image($path) !== null, 'allowlisted image is present and valid: ' . $path);
    $check(str_starts_with(media($path), '/media.php?path=assets%2Fimages%2F'), 'media helper stays local');
}
foreach (['.env', '../.env', 'assets/images/../../.env', 'assets/uploads/private.jpg',
    'https://example.com/photo.jpg', 'assets/images/logo.svg', 'assets/images/%2e%2e/test.png',
    'assets/images/does-not-exist.jpg', 'assets/images/../images/galle-lighthouse.w1200.webp'] as $path) {
    $check(source_image($path) === null && media($path) === '', 'non-allowlisted media rejected');
}
$check(contained_file(dirname(__DIR__) . '/public', '../app/bootstrap.php') === null, 'realpath traversal denied');
$check(public_asset('/assets/../../app/bootstrap.php') === null, 'public asset traversal denied');
$check(public_asset('/assets/config.json') === null && public_asset('/assets/source.php') === null, 'JSON and PHP source denied');
$check(session_status() === PHP_SESSION_NONE, 'no session started');
foreach (get_included_files() as $file) {
    $check(str_starts_with(realpath($file), realpath(dirname(__DIR__)) . DIRECTORY_SEPARATOR), 'all imported files inside NEW-SITE');
}
$check(!array_filter(headers_list(), static fn (string $header): bool => stripos($header, 'Set-Cookie:') === 0), 'no cookies emitted');

$render = static function (array $page): string {
    ob_start();
    try {
        require dirname(__DIR__) . '/templates/layout.php';
        return (string) ob_get_contents();
    } finally {
        ob_end_clean();
    }
};
$html = $render($unavailable);
$check(str_contains($html, 'data-content-mode="unavailable"') && str_contains($html, 'Content unavailable'), 'unavailable source is visible at top of rendered page');
$check(!str_contains($html, 'class="travel-card '), 'configured unavailable source does not render fixture commercial cards');
$html = $render($page);
$check(str_contains($html, 'Sample content') && str_contains($html, 'Design sample - not a published offer'), 'rendered fixture labels stay explicit');
$check(!preg_match('/(?:src|href)=""/', $html), 'rendered media and links have nonempty safe targets');

// Optional integration run, against an already-running isolated loopback server only.
if (isset($argv[1])) {
    $base = $argv[1];
    $check((bool) preg_match('~\Ahttp://127\.0\.0\.1:([0-9]{1,5})\z~D', $base), 'HTTP tests restricted to explicit loopback URL');
    $http = static function (string $path, string $method = 'GET', string $extra = '') use ($base): array {
        $context = stream_context_create(['http' => ['method' => $method, 'ignore_errors' => true,
            'follow_location' => 0, 'timeout' => 5, 'header' => $extra]]);
        $body = @file_get_contents($base . $path, false, $context);
        return ['body' => $body, 'headers' => $http_response_header ?? []];
    };
    foreach (['/contact', '/account', '/index.php?route=%2Fdestinations'] as $path) {
        $response = $http($path);
        $check(str_contains($response['headers'][0] ?? '', '200'), 'known HTTP route is 200');
        $headers = implode("\n", $response['headers']);
        $check(stripos($headers, 'X-Robots-Tag: noindex') !== false && stripos($headers, 'Set-Cookie:') === false, 'noindex and cookie-free HTTP response');
    }
    foreach (['/router.php', '/app/bootstrap.php', '/fixtures/catalog.php', '/tests/runtime.php', '/composer.json', '/assets/secret.php', '/account/logout', '/unknown-place'] as $path) {
        $response = $http($path);
        $check(str_contains($response['headers'][0] ?? '', '404'), 'internal/unknown HTTP resource denied: ' . $path);
        $check(!str_contains((string) $response['body'], '<?php'), 'PHP source never exposed');
    }
    $check(str_contains($http('/.env')['headers'][0] ?? '', '400'), 'hidden file path denied before dispatch');
    foreach (['/', '/index.php', '/media.php', '/assets/style.css', '/unknown-place'] as $path) {
        $check(str_contains($http($path, 'POST')['headers'][0] ?? '', '405'), 'POST denied on every resource');
        $check(str_contains($http($path, 'GET', "Host: attacker.example\r\n")['headers'][0] ?? '', '403'), 'host guard covers every resource');
    }
    $check(str_contains($http('/media.php?path=' . rawurlencode(media_allowlist()[0]))['headers'][0] ?? '', '200'), 'approved image served');
    $check(str_contains($http('/media.php?path=..%2F.env')['headers'][0] ?? '', '404'), 'HTTP media traversal denied');
    $check(str_contains($http('/', 'GET', "Host: attacker.example\r\n")['headers'][0] ?? '', '403'), 'host attack denied');
    $check(str_contains($http('/', 'HEAD')['headers'][0] ?? '', '200'), 'HEAD supported');
    $check(str_contains($http('/', 'GET', "X-Forwarded-For: 127.0.0.1\r\n")['headers'][0] ?? '', '403'), 'proxy-forwarded requests denied');
    $check(stripos(implode("\n", $http('/')['headers']), 'X-Powered-By:') === false, 'runtime identity header suppressed');
}
echo 'PASS: ' . $checks . ' isolated runtime assertions' . PHP_EOL;
