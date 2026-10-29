<?php
declare(strict_types=1);

/** Operational first-party statistics. No DDL, raw IP, or full browser strings. */
function ict_webstats_identity(?int $now = null): array
{
    if (ict_analytics_request_group() === '') { return ['visitor_key' => '', 'visit_key' => '']; }
    $now ??= time();
    // Separate from optional-service consent; existing website session only, no new cookie.
    $visitor = $_SESSION['ict_analytics_browser'] ?? '';
    if (!is_string($visitor) || preg_match('/\A[a-f0-9]{64}\z/', $visitor) !== 1) {
        $visitor = bin2hex(random_bytes(32));
        $_SESSION['ict_analytics_browser'] = $visitor;
    }
    $visit = $_SESSION['ict_analytics_visit'] ?? null;
    if (!is_array($visit) || ($visit['visitor'] ?? '') !== $visitor
        || !is_int($visit['last_seen'] ?? null) || $now < $visit['last_seen']
        || $now - $visit['last_seen'] >= 1800
        || preg_match('/\A[a-f0-9]{32}\z/', (string) ($visit['key'] ?? '')) !== 1) {
        $visit = ['visitor' => $visitor, 'key' => bin2hex(random_bytes(16))];
    }
    $visit['last_seen'] = $now;
    $_SESSION['ict_analytics_visit'] = $visit;
    return ['visitor_key' => $visitor, 'visit_key' => $visit['key']];
}

function ict_webstats_has_identity_columns(): bool
{
    return db_column_exists('page_views', 'visitor_key') && db_column_exists('page_views', 'visit_key');
}

function ict_webstats_has_metadata_columns(): bool
{
    return ict_webstats_has_identity_columns() && db_column_exists('page_views', 'device_type')
        && db_column_exists('page_views', 'browser_name') && db_column_exists('page_views', 'os_name');
}

function ict_webstats_page_path(): string
{
    $path = request_path();
    if (basename($path) === 'track-event.php' || basename((string) ($_SERVER['SCRIPT_NAME'] ?? '')) === 'track-event.php') {
        $path = (string) (parse_url((string) ($_SERVER['HTTP_REFERER'] ?? ''), PHP_URL_PATH) ?: '/');
    }
    $base = rtrim(ict_privacy_cookie_path(), '/');
    if ($base !== '' && str_starts_with($path, $base . '/')) { $path = substr($path, strlen($base)); }
    return preg_match('~\A/[a-zA-Z0-9/_%.\-]*\z~', $path) === 1 ? substr($path, 0, 450) : '/' . ict_analytics_request_group();
}

function ict_webstats_referrer(): string
{
    $ref = parse_url((string) ($_SERVER['HTTP_REFERER'] ?? ''));
    if (!is_array($ref) || !in_array($ref['scheme'] ?? '', ['http', 'https'], true)) { return ''; }
    $host = strtolower((string) ($ref['host'] ?? ''));
    // Domain only: no referrer path, credentials, fragments, queries or local IPs.
    if (strlen($host) > 253 || filter_var($host, FILTER_VALIDATE_IP)
        || preg_match('/\A[a-z0-9](?:[a-z0-9.-]*[a-z0-9])?\z/', $host) !== 1) { return ''; }
    return $ref['scheme'] . '://' . $host;
}

function ict_webstats_metadata(string $agent): array
{
    $ua = strtolower(substr($agent, 0, 1000));
    $bot = preg_match('/bot|spider|crawl|headless|scanner/', $ua) === 1;
    $device = $ua === '' ? 'Unknown' : ($bot ? 'Bots' : (preg_match('/ipad|tablet|kindle|silk/', $ua) ? 'Tablet'
        : (preg_match('/mobi|iphone|android/', $ua) ? 'Mobile' : 'Desktop')));
    $browser = match (true) {
        $bot => 'Bot', preg_match('/edg[aei]?\/|edge\//', $ua) === 1 => 'Edge',
        preg_match('/opr\/|opera/', $ua) === 1 => 'Opera',
        preg_match('/firefox\/|fxios\//', $ua) === 1 => 'Firefox',
        preg_match('/chrome\/|crios\//', $ua) === 1 => 'Chrome',
        str_contains($ua, 'safari/') => 'Safari', preg_match('/msie|trident/', $ua) === 1 => 'Internet Explorer',
        $ua === '' => 'Unknown', default => 'Other',
    };
    $os = match (true) {
        str_contains($ua, 'windows') => 'Windows', str_contains($ua, 'android') => 'Android',
        preg_match('/iphone|ipad|ipod/', $ua) === 1 => 'iOS', preg_match('/mac os|macintosh/', $ua) === 1 => 'macOS',
        str_contains($ua, 'linux') => 'Linux', $ua === '' => 'Unknown', default => 'Other',
    };
    return ['device_type' => $device, 'browser_name' => $browser, 'os_name' => $os];
}

/** SQL fragments use fixed column names only; blank aggregate records have no identity. */
function ict_webstats_visitor_sql(): string
{
    $legacy = "CASE WHEN TRIM(ip_address) <> '' THEN CONCAT('legacy:', ip_address, '|', LEFT(user_agent, 80)) END";
    return ict_webstats_has_identity_columns()
        ? "COALESCE(CONCAT('browser:', NULLIF(visitor_key, '')), {$legacy})" : $legacy;
}

function ict_webstats_visit_sql(): string
{
    $legacy = "CASE WHEN TRIM(ip_address) <> '' THEN CONCAT('legacy:', ip_address, '|', DATE(viewed_at)) END";
    return ict_webstats_has_identity_columns()
        ? "COALESCE(CONCAT('browser:', NULLIF(visit_key, '')), {$legacy})" : $legacy;
}
