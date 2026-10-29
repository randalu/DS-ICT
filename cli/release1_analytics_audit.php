<?php
declare(strict_types=1);

/** Read-only Release 1 analytics event baseline and duplicate-fire audit. */

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../includes/analytics_audit.php';

$days = 30;
$outputPath = '';
$format = 'json';
foreach (array_slice($argv, 1) as $argument) {
    if (str_starts_with($argument, '--days=')) {
        $parsedDays = filter_var(substr($argument, 7), FILTER_VALIDATE_INT);
        if ($parsedDays === false || $parsedDays < 1 || $parsedDays > 365) {
            fwrite(STDERR, "Usage: php cli/release1_analytics_audit.php [--days=1..365] [--format=json|text] [--output=PATH]\n");
            exit(2);
        }
        $days = $parsedDays;
        continue;
    }
    if (str_starts_with($argument, '--format=')) {
        $format = strtolower(trim(substr($argument, 9)));
        if (!in_array($format, ['json', 'text'], true)) {
            fwrite(STDERR, "Usage: php cli/release1_analytics_audit.php [--days=1..365] [--format=json|text] [--output=PATH]\n");
            exit(2);
        }
        continue;
    }
    if (str_starts_with($argument, '--output=')) {
        $outputPath = trim(substr($argument, 9));
        if ($outputPath === '') {
            fwrite(STDERR, "Output path cannot be empty.\n");
            exit(2);
        }
        continue;
    }

    fwrite(STDERR, "Usage: php cli/release1_analytics_audit.php [--days=1..365] [--format=json|text] [--output=PATH]\n");
    exit(2);
}

$relativeFiles = [
    'includes/header.php',
    'includes/footer.php',
    'includes/schema_helpers.php',
    'includes/sticky_cta.php',
    'includes/cta.php',
    'contact.php',
    'account_customize_itinerary.php',
    'account_plan_request_save.php',
    'account_inquiry_message_save.php',
    'track-event.php',
];
$sources = [];
foreach ($relativeFiles as $relativePath) {
    $absolutePath = __DIR__ . '/../' . $relativePath;
    $content = is_file($absolutePath) ? file_get_contents($absolutePath) : false;
    if (!is_string($content)) {
        fwrite(STDERR, 'Could not read analytics source: ' . $relativePath . PHP_EOL);
        exit(2);
    }
    $sources[$relativePath] = $content;
}

$static = release1_analytics_static_audit($sources);
$runtime = [
    'analytics_settings_table_available' => db_table_exists('analytics_settings'),
    'analytics_event_types_table_available' => db_table_exists('analytics_event_types'),
    'analytics_events_table_available' => db_table_exists('analytics_events'),
    'retention_days' => null,
    'active_internal_click_event_keys' => [],
    'event_counts_last_' . $days . '_days' => [],
    'event_row_count_last_' . $days . '_days' => null,
    'same_second_duplicate_summary_by_event' => [],
    'same_second_duplicate_candidate_group_count' => null,
    'same_second_duplicate_event_row_count' => null,
];

$settings = [];
if ($runtime['analytics_settings_table_available']
    && db_column_exists('analytics_settings', 'setting_key')
    && db_column_exists('analytics_settings', 'setting_value')) {
    foreach (fetch_all(
        "SELECT setting_key, setting_value
         FROM analytics_settings
         WHERE setting_key IN ('google_tag_manager_enabled', 'google_tag_manager_id', 'events_retention_days')"
    ) as $row) {
        $settings[(string) ($row['setting_key'] ?? '')] = (string) ($row['setting_value'] ?? '');
    }
}

$environmentGtmId = trim((string) (getenv('GTM_CONTAINER_ID') ?: getenv('GOOGLE_TAG_MANAGER_ID') ?: ''));
$runtime = array_merge($runtime, release1_analytics_resolve_gtm_settings($settings, $environmentGtmId));
$runtime['retention_days'] = isset($settings['events_retention_days'])
    ? max(30, min(3650, (int) $settings['events_retention_days']))
    : null;

if ($runtime['analytics_event_types_table_available']
    && db_column_exists('analytics_event_types', 'event_key')
    && db_column_exists('analytics_event_types', 'is_active')) {
    $runtime['active_internal_click_event_keys'] = array_values(array_map(
        static fn(array $row): string => (string) ($row['event_key'] ?? ''),
        fetch_all('SELECT event_key FROM analytics_event_types WHERE is_active = 1 ORDER BY sort_order, id')
    ));
}

$aggregateAvailable = $runtime['analytics_events_table_available']
    && db_column_exists('analytics_events', 'event_type')
    && db_column_exists('analytics_events', 'created_at');
if ($aggregateAvailable) {
    $counts = fetch_all(
        'SELECT event_type, COUNT(*) AS total
         FROM analytics_events
         WHERE created_at >= DATE_SUB(NOW(), INTERVAL ' . $days . ' DAY)
         GROUP BY event_type
         ORDER BY total DESC, event_type ASC'
    );
    $runtime['event_counts_last_' . $days . '_days'] = array_map(
        static fn(array $row): array => [
            'event_type' => (string) ($row['event_type'] ?? ''),
            'count' => (int) ($row['total'] ?? 0),
        ],
        $counts
    );
    $runtime['event_row_count_last_' . $days . '_days'] = array_sum(
        array_column($runtime['event_counts_last_' . $days . '_days'], 'count')
    );

    $hasDuplicateDimensions = db_column_exists('analytics_events', 'path')
        && db_column_exists('analytics_events', 'target_url')
        && db_column_exists('analytics_events', 'ip_address')
        && db_column_exists('analytics_events', 'user_agent');
    if ($hasDuplicateDimensions) {
        $duplicateSummary = fetch_all(
            'SELECT event_type,
                    COUNT(*) AS duplicate_group_count,
                    SUM(duplicate_count) AS duplicate_event_row_count
             FROM (
                 SELECT event_type, COUNT(*) AS duplicate_count
                 FROM analytics_events
                 WHERE created_at >= DATE_SUB(NOW(), INTERVAL ' . $days . ' DAY)
                 GROUP BY event_type, path, target_url, ip_address, user_agent,
                          FLOOR(UNIX_TIMESTAMP(created_at))
                 HAVING COUNT(*) > 1
             ) AS duplicate_groups
             GROUP BY event_type
             ORDER BY duplicate_group_count DESC, event_type ASC'
        );
        $runtime['same_second_duplicate_summary_by_event'] = array_map(
            static fn(array $row): array => [
                'event_type' => (string) ($row['event_type'] ?? ''),
                'candidate_group_count' => (int) ($row['duplicate_group_count'] ?? 0),
                'candidate_event_row_count' => (int) ($row['duplicate_event_row_count'] ?? 0),
            ],
            $duplicateSummary
        );
        $runtime['same_second_duplicate_candidate_group_count'] = array_sum(
            array_column($runtime['same_second_duplicate_summary_by_event'], 'candidate_group_count')
        );
        $runtime['same_second_duplicate_event_row_count'] = array_sum(
            array_column($runtime['same_second_duplicate_summary_by_event'], 'candidate_event_row_count')
        );
    }
}

$report = [
    'generated_at' => date(DATE_ATOM),
    'audit' => 'release1-analytics-event-baseline',
    'window_days' => $days,
    'static_code_baseline' => $static,
    'runtime_baseline' => $runtime,
    'duplicate_fire_assessment' => [
        'first_party_click_transport' => 'One first-party transport attempt is made per qualifying click; a rejected sendBeacon falls back to fetch.',
        'gtm_transport' => 'The same click also pushes one custom event to dataLayer; this is separate from first-party event storage.',
        'queued_conversion_events' => 'Server-side success branches queue GTM events, flushed once from the session and then unset.',
        'same_second_rows' => 'Heuristic candidates only; rapid repeated clicks or different users behind the same IP/user-agent can collide. Raw target, IP, and user-agent values are not written to this report.',
        'external_gtm_tag_rules' => 'Not observable from repository source or local analytics tables; GTM Preview/Tag Assistant is required to rule out duplicate GA4 tags.',
        'internal_duplicate_candidate_groups_observed' => $runtime['same_second_duplicate_candidate_group_count'],
        'internal_duplicate_candidate_event_rows_observed' => $runtime['same_second_duplicate_event_row_count'],
        'web_server_gtm_environment_verified' => false,
    ],
    'external_validation_required' => [
        'Inspect the configured GTM container in Preview mode and verify exactly one GA4 event tag per custom click/funnel event.',
        'Verify consent behavior, GA4 property routing, and key-event designation in the live analytics accounts.',
    ],
];

$json = json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
if (!is_string($json)) {
    fwrite(STDERR, "Could not serialize analytics audit.\n");
    exit(2);
}
$json .= PHP_EOL;

if ($outputPath !== '') {
    $absoluteOutput = str_starts_with($outputPath, '/') || preg_match('/^[A-Za-z]:[\\\\\/]/', $outputPath)
        ? $outputPath
        : dirname(__DIR__) . DIRECTORY_SEPARATOR . $outputPath;
    $parentDirectory = dirname($absoluteOutput);
    if (!is_dir($parentDirectory)) {
        fwrite(STDERR, "Output directory does not exist.\n");
        exit(2);
    }
    if (file_put_contents($absoluteOutput, $json, LOCK_EX) === false) {
        fwrite(STDERR, "Could not write analytics audit output.\n");
        exit(2);
    }
    fwrite(STDERR, 'Analytics baseline written to ' . $outputPath . PHP_EOL);
}

if ($format === 'json') {
    echo $json;
} else {
    echo 'Release 1 analytics baseline (' . $days . ' days)' . PHP_EOL;
    echo 'CLI-resolved GTM: ' . ($runtime['cli_resolved_enabled'] ? $runtime['cli_resolved_container_id'] : 'disabled or invalid') . PHP_EOL;
    echo 'Web-server GTM environment: not verified by CLI.' . PHP_EOL;
    echo 'First-party events: ' . ($runtime['event_row_count_last_' . $days . '_days'] === null
        ? 'analytics table unavailable'
        : (string) $runtime['event_row_count_last_' . $days . '_days']) . PHP_EOL;
    echo 'Same-second duplicate candidates: '
        . ($runtime['same_second_duplicate_candidate_group_count'] === null
            ? 'unavailable'
            : (string) $runtime['same_second_duplicate_candidate_group_count']) . PHP_EOL;
    echo 'External GTM tag duplication: not verifiable locally; GTM Preview required.' . PHP_EOL;
}
