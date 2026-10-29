<?php
declare(strict_types=1);

$adminPage = 'webstats';
$adminTitle = 'Web Stats';

require_once __DIR__ . '/../includes/auth.php';

admin_require_auth();

ensure_analytics_events_table();

$allowedRanges = [7, 14, 30, 60, 90, 180];
$days = (int) get_input('days', 30);
if (!in_array($days, $allowedRanges, true)) {
    $days = 30;
}

$today = new DateTimeImmutable('today');
$rangeStart = $today->sub(new DateInterval('P' . max(0, $days - 1) . 'D'));
$rangeStartSql = $rangeStart->format('Y-m-d 00:00:00');
$rangeEndSql = $today->format('Y-m-d 23:59:59');

$params = ['start' => $rangeStartSql];
$visitorExpr = ict_webstats_visitor_sql();
$visitExpr = ict_webstats_visit_sql();
$collectionEnabled = true;
$identityReady = ict_webstats_has_identity_columns();
header('Cache-Control: private, no-store, max-age=0');
$latestView = fetch_one('SELECT MAX(viewed_at) AS latest FROM page_views')['latest'] ?? null;
$targetDimensionExpr = db_column_exists('analytics_events', 'target_group')
    ? "CASE WHEN TRIM(target_group) <> '' THEN target_group ELSE target_url END"
    : 'target_url';

// ⚡ Bolt: Combine multiple page_views aggregations into a single query to prevent redundant full table scans.
$combinedStats = fetch_one(
    "SELECT
        COUNT(*) AS total_views,
        COUNT(DISTINCT {$visitorExpr}) AS unique_visitors,
        COUNT(DISTINCT NULLIF(TRIM(ip_address), '')) AS unique_ips,
        COUNT(DISTINCT {$visitExpr}) AS total_visits,
        COUNT(DISTINCT NULLIF(TRIM(country), '')) AS unique_countries
     FROM page_views
     WHERE viewed_at >= :start",
    $params
);

$totalViews = (int) ($combinedStats['total_views'] ?? 0);
$uniqueVisitors = (int) ($combinedStats['unique_visitors'] ?? 0);
$uniqueIps = (int) ($combinedStats['unique_ips'] ?? 0);
$totalVisits = (int) ($combinedStats['total_visits'] ?? 0);
$uniqueCountries = (int) ($combinedStats['unique_countries'] ?? 0);

$totalInquiries = (int) (fetch_one(
    "SELECT COUNT(*) AS total
     FROM contact_inquiries
     WHERE created_at >= :start",
    $params
)['total'] ?? 0);

$totalTrackedClicks = (int) (fetch_one(
    "SELECT COUNT(*) AS total
     FROM analytics_events
     WHERE created_at >= :start",
    $params
)['total'] ?? 0);

$previousRangeStartSql = $rangeStart->sub(new DateInterval('P' . $days . 'D'))->format('Y-m-d 00:00:00');
$previousRangeEndSql = $rangeStart->sub(new DateInterval('P1D'))->format('Y-m-d 23:59:59');
$previousParams = [
    'start' => $previousRangeStartSql,
    'end' => $previousRangeEndSql,
];

// ⚡ Bolt: Combine multiple page_views aggregations into a single query to prevent redundant full table scans for previous period.
$previousCombinedStats = fetch_one(
    "SELECT
        COUNT(*) AS total_views,
        COUNT(DISTINCT {$visitorExpr}) AS unique_visitors
     FROM page_views
     WHERE viewed_at >= :start
       AND viewed_at <= :end",
    $previousParams
);

$previousTotalViews = (int) ($previousCombinedStats['total_views'] ?? 0);
$previousUniqueVisitors = (int) ($previousCombinedStats['unique_visitors'] ?? 0);

$previousTotalInquiries = (int) (fetch_one(
    "SELECT COUNT(*) AS total
     FROM contact_inquiries
     WHERE created_at >= :start
       AND created_at <= :end",
    $previousParams
)['total'] ?? 0);

$previousTotalTrackedClicks = (int) (fetch_one(
    "SELECT COUNT(*) AS total
     FROM analytics_events
     WHERE created_at >= :start
       AND created_at <= :end",
    $previousParams
)['total'] ?? 0);

$avgViewsPerDay = $days > 0 ? round($totalViews / $days, 1) : 0.0;
$pagesPerVisitor = $uniqueVisitors > 0 ? round($totalViews / $uniqueVisitors, 2) : 0.0;
$inquiryConversionRate = $uniqueVisitors > 0 ? round(($totalInquiries / $uniqueVisitors) * 100, 2) : 0.0;

$deltaPercent = static function (int $current, int $previous): float {
    if ($previous <= 0) {
        return $current > 0 ? 100.0 : 0.0;
    }
    return round((($current - $previous) / $previous) * 100, 2);
};

$deltaViews = $deltaPercent($totalViews, $previousTotalViews);
$deltaVisitors = $deltaPercent($uniqueVisitors, $previousUniqueVisitors);
$deltaInquiries = $deltaPercent($totalInquiries, $previousTotalInquiries);
$deltaClicks = $deltaPercent($totalTrackedClicks, $previousTotalTrackedClicks);

$dailyViews = fetch_all(
    "SELECT
        DATE(viewed_at) AS day,
        COUNT(*) AS views,
        COUNT(DISTINCT {$visitorExpr}) AS visitors,
        COUNT(DISTINCT {$visitExpr}) AS visits
     FROM page_views
     WHERE viewed_at >= :start
     GROUP BY DATE(viewed_at)
     ORDER BY day ASC",
    $params
);

$dailyInquiries = fetch_all(
    "SELECT DATE(created_at) AS day, COUNT(*) AS inquiries
     FROM contact_inquiries
     WHERE created_at >= :start
     GROUP BY DATE(created_at)
     ORDER BY day ASC",
    $params
);

$dailyTrackedClicks = fetch_all(
    "SELECT DATE(created_at) AS day, COUNT(*) AS clicks
     FROM analytics_events
     WHERE created_at >= :start
     GROUP BY DATE(created_at)
     ORDER BY day ASC",
    $params
);

$topPages = fetch_all(
    "SELECT path, COUNT(*) AS views
     FROM page_views
     WHERE viewed_at >= :start
     GROUP BY path
     ORDER BY views DESC
     LIMIT 15",
    $params
);

$topLandingPages = fetch_all(
    "SELECT pv.path, COUNT(*) AS sessions
     FROM page_views pv
     INNER JOIN (
        SELECT MIN(id) AS first_view_id
        FROM page_views
        WHERE viewed_at >= :start
        AND ({$visitExpr}) IS NOT NULL
        GROUP BY {$visitExpr}
     ) s ON s.first_view_id = pv.id
     GROUP BY pv.path
     ORDER BY sessions DESC
     LIMIT 10",
    $params
);

$topExitPages = fetch_all(
    "SELECT pv.path, COUNT(*) AS sessions
     FROM page_views pv
     INNER JOIN (
        SELECT MAX(id) AS last_view_id
        FROM page_views
        WHERE viewed_at >= :start
        AND ({$visitExpr}) IS NOT NULL
        GROUP BY {$visitExpr}
     ) s ON s.last_view_id = pv.id
     GROUP BY pv.path
     ORDER BY sessions DESC
     LIMIT 10",
    $params
);

$topReferrers = fetch_all(
    "SELECT
        CASE
            WHEN TRIM(referrer) = '' AND TRIM(ip_address) = '' THEN '(Direct / unavailable)'
            WHEN TRIM(referrer) = '' THEN '(Direct)'
            ELSE LOWER(
                SUBSTRING_INDEX(
                    SUBSTRING_INDEX(
                        REPLACE(REPLACE(referrer, 'https://', ''), 'http://', ''),
                        '/',
                        1
                    ),
                    '?',
                    1
                )
            )
        END AS domain,
        COUNT(*) AS visits
     FROM page_views
     WHERE viewed_at >= :start
     GROUP BY domain
     ORDER BY visits DESC
     LIMIT 12",
    $params
);

$countryBreakdown = fetch_all(
    "SELECT
        CASE
            WHEN TRIM(country) = '' THEN 'Unknown'
            WHEN LENGTH(TRIM(country)) = 2 THEN UPPER(TRIM(country))
            ELSE TRIM(country)
        END AS country_label,
        COUNT(*) AS visits,
        COUNT(DISTINCT {$visitorExpr}) AS visitors
     FROM page_views
     WHERE viewed_at >= :start
     GROUP BY country_label
     ORDER BY visits DESC
     LIMIT 20",
    $params
);

$device_typeExpr = db_column_exists('page_views', 'device_type')
    ? "COALESCE(NULLIF(device_type, ''), CASE
            WHEN TRIM(user_agent) = '' THEN 'Not collected'
            WHEN LOWER(user_agent) LIKE '%bot%' OR LOWER(user_agent) LIKE '%spider%' OR LOWER(user_agent) LIKE '%crawl%' THEN 'Bots'
            WHEN LOWER(user_agent) LIKE '%ipad%' OR LOWER(user_agent) LIKE '%tablet%' OR LOWER(user_agent) LIKE '%kindle%' THEN 'Tablet'
            WHEN LOWER(user_agent) LIKE '%mobi%' OR LOWER(user_agent) LIKE '%iphone%' OR LOWER(user_agent) LIKE '%android%' THEN 'Mobile'
            ELSE 'Desktop'
        END)"
    : "CASE
            WHEN TRIM(user_agent) = '' THEN 'Not collected'
            WHEN LOWER(user_agent) LIKE '%bot%' OR LOWER(user_agent) LIKE '%spider%' OR LOWER(user_agent) LIKE '%crawl%' THEN 'Bots'
            WHEN LOWER(user_agent) LIKE '%ipad%' OR LOWER(user_agent) LIKE '%tablet%' OR LOWER(user_agent) LIKE '%kindle%' THEN 'Tablet'
            WHEN LOWER(user_agent) LIKE '%mobi%' OR LOWER(user_agent) LIKE '%iphone%' OR LOWER(user_agent) LIKE '%android%' THEN 'Mobile'
            ELSE 'Desktop'
        END";

$deviceBreakdown = fetch_all(
    "SELECT
        {$device_typeExpr} AS device_type,
        COUNT(*) AS visits
     FROM page_views
     WHERE viewed_at >= :start
     GROUP BY {$device_typeExpr}
     ORDER BY visits DESC",
    $params
);

$browser_nameExpr = db_column_exists('page_views', 'browser_name')
    ? "COALESCE(NULLIF(browser_name, ''), CASE
            WHEN TRIM(user_agent) = '' THEN 'Not collected'
            WHEN LOWER(user_agent) LIKE '%edg/%' OR LOWER(user_agent) LIKE '%edge/%' THEN 'Edge'
            WHEN LOWER(user_agent) LIKE '%opr/%' OR LOWER(user_agent) LIKE '%opera%' THEN 'Opera'
            WHEN LOWER(user_agent) LIKE '%chrome/%' AND LOWER(user_agent) NOT LIKE '%edg/%' AND LOWER(user_agent) NOT LIKE '%opr/%' THEN 'Chrome'
            WHEN LOWER(user_agent) LIKE '%safari/%' AND LOWER(user_agent) NOT LIKE '%chrome/%' THEN 'Safari'
            WHEN LOWER(user_agent) LIKE '%firefox/%' THEN 'Firefox'
            WHEN LOWER(user_agent) LIKE '%msie%' OR LOWER(user_agent) LIKE '%trident/%' THEN 'Internet Explorer'
            ELSE 'Other'
        END)"
    : "CASE
            WHEN TRIM(user_agent) = '' THEN 'Not collected'
            WHEN LOWER(user_agent) LIKE '%edg/%' OR LOWER(user_agent) LIKE '%edge/%' THEN 'Edge'
            WHEN LOWER(user_agent) LIKE '%opr/%' OR LOWER(user_agent) LIKE '%opera%' THEN 'Opera'
            WHEN LOWER(user_agent) LIKE '%chrome/%' AND LOWER(user_agent) NOT LIKE '%edg/%' AND LOWER(user_agent) NOT LIKE '%opr/%' THEN 'Chrome'
            WHEN LOWER(user_agent) LIKE '%safari/%' AND LOWER(user_agent) NOT LIKE '%chrome/%' THEN 'Safari'
            WHEN LOWER(user_agent) LIKE '%firefox/%' THEN 'Firefox'
            WHEN LOWER(user_agent) LIKE '%msie%' OR LOWER(user_agent) LIKE '%trident/%' THEN 'Internet Explorer'
            ELSE 'Other'
        END";

$browserBreakdown = fetch_all(
    "SELECT
        {$browser_nameExpr} AS browser_name,
        COUNT(*) AS visits
     FROM page_views
     WHERE viewed_at >= :start
     GROUP BY {$browser_nameExpr}
     ORDER BY visits DESC",
    $params
);

$os_nameExpr = db_column_exists('page_views', 'os_name')
    ? "COALESCE(NULLIF(os_name, ''), CASE
            WHEN TRIM(user_agent) = '' THEN 'Not collected'
            WHEN LOWER(user_agent) LIKE '%windows%' THEN 'Windows'
            WHEN LOWER(user_agent) LIKE '%android%' THEN 'Android'
            WHEN LOWER(user_agent) LIKE '%iphone%' OR LOWER(user_agent) LIKE '%ipad%' OR LOWER(user_agent) LIKE '%ios%' THEN 'iOS'
            WHEN LOWER(user_agent) LIKE '%mac os%' OR LOWER(user_agent) LIKE '%macintosh%' THEN 'macOS'
            WHEN LOWER(user_agent) LIKE '%linux%' THEN 'Linux'
            ELSE 'Other'
        END)"
    : "CASE
            WHEN TRIM(user_agent) = '' THEN 'Not collected'
            WHEN LOWER(user_agent) LIKE '%windows%' THEN 'Windows'
            WHEN LOWER(user_agent) LIKE '%android%' THEN 'Android'
            WHEN LOWER(user_agent) LIKE '%iphone%' OR LOWER(user_agent) LIKE '%ipad%' OR LOWER(user_agent) LIKE '%ios%' THEN 'iOS'
            WHEN LOWER(user_agent) LIKE '%mac os%' OR LOWER(user_agent) LIKE '%macintosh%' THEN 'macOS'
            WHEN LOWER(user_agent) LIKE '%linux%' THEN 'Linux'
            ELSE 'Other'
        END";

$osBreakdown = fetch_all(
    "SELECT
        {$os_nameExpr} AS os_name,
        COUNT(*) AS visits
     FROM page_views
     WHERE viewed_at >= :start
     GROUP BY {$os_nameExpr}
     ORDER BY visits DESC",
    $params
);

$hourlyTraffic = fetch_all(
    "SELECT HOUR(viewed_at) AS hour_no, COUNT(*) AS visits
     FROM page_views
     WHERE viewed_at >= :start
     GROUP BY HOUR(viewed_at)
     ORDER BY hour_no ASC",
    $params
);

$weekdayTraffic = fetch_all(
    "SELECT DAYOFWEEK(viewed_at) AS weekday_no, COUNT(*) AS visits
     FROM page_views
     WHERE viewed_at >= :start
     GROUP BY DAYOFWEEK(viewed_at)
     ORDER BY weekday_no ASC",
    $params
);

$eventBreakdown = fetch_all(
    "SELECT event_type, COUNT(*) AS total
     FROM analytics_events
     WHERE created_at >= :start
     GROUP BY event_type
     ORDER BY total DESC",
    $params
);

$topEventTargets = fetch_all(
    "SELECT {$targetDimensionExpr} AS target_label, COUNT(*) AS total
     FROM analytics_events
     WHERE created_at >= :start
       AND TRIM({$targetDimensionExpr}) <> ''
     GROUP BY target_label
     ORDER BY total DESC
     LIMIT 12",
    $params
);

$topVisitors = fetch_all(
    "SELECT {$visitorExpr} AS visitor_label, COUNT(*) AS visits, MAX(viewed_at) AS last_seen
     FROM page_views
     WHERE viewed_at >= :start
       AND ({$visitorExpr}) IS NOT NULL
     GROUP BY {$visitorExpr}
     ORDER BY visits DESC
     LIMIT 15",
    $params
);

$recentVisits = fetch_all(
    "SELECT viewed_at, path, country, referrer, {$device_typeExpr} AS device_label, {$browser_nameExpr} AS browser_label, {$os_nameExpr} AS os_label
     FROM page_views
     WHERE viewed_at >= :start
     ORDER BY viewed_at DESC
     LIMIT 40",
    $params
);

$recentEvents = fetch_all(
    "SELECT created_at, event_type, path, {$targetDimensionExpr} AS target_url
     FROM analytics_events
     WHERE created_at >= :start
     ORDER BY created_at DESC
     LIMIT 40",
    $params
);

$exportType = trim((string) get_input('export', ''));
if ($exportType !== '') {
    $filename = 'webstats-' . $exportType . '-' . date('Ymd-His') . '.csv';
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    $out = fopen('php://output', 'w');
    if ($out === false) {
        exit;
    }

    if ($exportType === 'daily') {
        fputcsv($out, ['date', 'views', 'visitors', 'visits', 'inquiries', 'clicks']);
        $dailyInquiriesMapExport = [];
        foreach ($dailyInquiries as $row) {
            $dailyInquiriesMapExport[(string) ($row['day'] ?? '')] = (int) ($row['inquiries'] ?? 0);
        }
        $dailyClicksMapExport = [];
        foreach ($dailyTrackedClicks as $row) {
            $dailyClicksMapExport[(string) ($row['day'] ?? '')] = (int) ($row['clicks'] ?? 0);
        }
        foreach ($dailyViews as $row) {
            $day = (string) ($row['day'] ?? '');
            if ($day === '') {
                continue;
            }
            fputcsv($out, [
                $day,
                (int) ($row['views'] ?? 0),
                (int) ($row['visitors'] ?? 0),
                (int) ($row['visits'] ?? 0),
                (int) ($dailyInquiriesMapExport[$day] ?? 0),
                (int) ($dailyClicksMapExport[$day] ?? 0),
            ]);
        }
    } elseif ($exportType === 'countries') {
        fputcsv($out, ['country', 'visits', 'visitors']);
        foreach ($countryBreakdown as $row) {
            fputcsv($out, [
                (string) ($row['country_label'] ?? 'Unknown'),
                (int) ($row['visits'] ?? 0),
                (int) ($row['visitors'] ?? 0),
            ]);
        }
    } elseif ($exportType === 'targets') {
        fputcsv($out, ['target_group_or_url', 'clicks']);
        foreach ($topEventTargets as $row) {
            fputcsv($out, [
                (string) ($row['target_label'] ?? ''),
                (int) ($row['total'] ?? 0),
            ]);
        }
    } elseif ($exportType === 'pages') {
        fputcsv($out, ['path', 'views']);
        foreach ($topPages as $row) {
            fputcsv($out, [
                (string) ($row['path'] ?? '/'),
                (int) ($row['views'] ?? 0),
            ]);
        }
    }

    fclose($out);
    exit;
}

$dailyViewsMap = [];
foreach ($dailyViews as $row) {
    $day = (string) ($row['day'] ?? '');
    if ($day === '') {
        continue;
    }
    $dailyViewsMap[$day] = [
        'views' => (int) ($row['views'] ?? 0),
        'visitors' => (int) ($row['visitors'] ?? 0),
        'visits' => (int) ($row['visits'] ?? 0),
    ];
}

$dailyInquiriesMap = [];
foreach ($dailyInquiries as $row) {
    $day = (string) ($row['day'] ?? '');
    if ($day === '') {
        continue;
    }
    $dailyInquiriesMap[$day] = (int) ($row['inquiries'] ?? 0);
}

$dailyTrackedClicksMap = [];
foreach ($dailyTrackedClicks as $row) {
    $day = (string) ($row['day'] ?? '');
    if ($day === '') {
        continue;
    }
    $dailyTrackedClicksMap[$day] = (int) ($row['clicks'] ?? 0);
}

$chartLabels = [];
$viewsSeries = [];
$visitorsSeries = [];
$visitsSeries = [];
$inquiriesSeries = [];
$clicksSeries = [];
$cursor = $rangeStart;
for ($i = 0; $i < $days; $i++) {
    $key = $cursor->format('Y-m-d');
    $chartLabels[] = $cursor->format('M d');
    $viewsSeries[] = (int) (($dailyViewsMap[$key]['views'] ?? 0));
    $visitorsSeries[] = (int) (($dailyViewsMap[$key]['visitors'] ?? 0));
    $visitsSeries[] = (int) (($dailyViewsMap[$key]['visits'] ?? 0));
    $inquiriesSeries[] = (int) ($dailyInquiriesMap[$key] ?? 0);
    $clicksSeries[] = (int) ($dailyTrackedClicksMap[$key] ?? 0);
    $cursor = $cursor->add(new DateInterval('P1D'));
}

$hourlySeries = array_fill(0, 24, 0);
foreach ($hourlyTraffic as $row) {
    $hour = (int) ($row['hour_no'] ?? -1);
    if ($hour >= 0 && $hour <= 23) {
        $hourlySeries[$hour] = (int) ($row['visits'] ?? 0);
    }
}

$weekdayLabels = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
$weekdaySeries = array_fill(0, 7, 0);
foreach ($weekdayTraffic as $row) {
    $weekdayNo = (int) ($row['weekday_no'] ?? 0);
    if ($weekdayNo >= 1 && $weekdayNo <= 7) {
        $weekdaySeries[$weekdayNo - 1] = (int) ($row['visits'] ?? 0);
    }
}

$deviceLabels = array_map(static fn(array $r): string => (string) ($r['device_type'] ?? 'Unknown'), $deviceBreakdown);
$deviceSeries = array_map(static fn(array $r): int => (int) ($r['visits'] ?? 0), $deviceBreakdown);

$browserLabels = array_map(static fn(array $r): string => (string) ($r['browser_name'] ?? 'Other'), $browserBreakdown);
$browserSeries = array_map(static fn(array $r): int => (int) ($r['visits'] ?? 0), $browserBreakdown);

$osLabels = array_map(static fn(array $r): string => (string) ($r['os_name'] ?? 'Other'), $osBreakdown);
$osSeries = array_map(static fn(array $r): int => (int) ($r['visits'] ?? 0), $osBreakdown);

$referrerLabels = array_map(static fn(array $r): string => (string) ($r['domain'] ?? '(Unknown)'), $topReferrers);
$referrerSeries = array_map(static fn(array $r): int => (int) ($r['visits'] ?? 0), $topReferrers);

$topPageLabels = array_map(static fn(array $r): string => (string) ($r['path'] ?? '/'), $topPages);
$topPageSeries = array_map(static fn(array $r): int => (int) ($r['views'] ?? 0), $topPages);

$landingLabels = array_map(static fn(array $r): string => (string) ($r['path'] ?? '/'), $topLandingPages);
$landingSeries = array_map(static fn(array $r): int => (int) ($r['sessions'] ?? 0), $topLandingPages);

$exitLabels = array_map(static fn(array $r): string => (string) ($r['path'] ?? '/'), $topExitPages);
$exitSeries = array_map(static fn(array $r): int => (int) ($r['sessions'] ?? 0), $topExitPages);

$eventLabels = array_map(static fn(array $r): string => ucwords(str_replace('_', ' ', (string) ($r['event_type'] ?? 'unknown'))), $eventBreakdown);
$eventSeries = array_map(static fn(array $r): int => (int) ($r['total'] ?? 0), $eventBreakdown);

$countryLabels = array_map(static fn(array $r): string => (string) ($r['country_label'] ?? 'Unknown'), $countryBreakdown);
$countrySeries = array_map(static fn(array $r): int => (int) ($r['visits'] ?? 0), $countryBreakdown);

require __DIR__ . '/_top.php';
?>

<style>
    .stats-chart-wrap {
        position: relative;
        width: 100%;
        height: 260px;
    }

    .stats-chart-wrap.stats-chart-tall {
        height: 340px;
    }

    @media (max-width: 991.98px) {
        .stats-chart-wrap {
            height: 230px;
        }

        .stats-chart-wrap.stats-chart-tall {
            height: 300px;
        }
    }
</style>

<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
    <div>
        <div class="small text-secondary">
            Detailed analytics for the last <?= (int) $days ?> day<?= $days === 1 ? '' : 's' ?>.
            Previous comparison window: <?= e(date('d M', strtotime($previousRangeStartSql))) ?> - <?= e(date('d M', strtotime($previousRangeEndSql))) ?>.
        </div>
    </div>
    <div class="d-flex flex-wrap gap-2">
        <?php foreach ($allowedRanges as $range): ?>
            <a href="?days=<?= (int) $range ?>" class="btn btn-sm <?= $days === $range ? 'btn-success' : 'btn-outline-dark' ?>"><?= (int) $range ?>d</a>
        <?php endforeach; ?>
        <a href="?days=<?= (int) $days ?>&export=daily" class="btn btn-sm btn-outline-secondary">Daily CSV</a>
        <a href="?days=<?= (int) $days ?>&export=countries" class="btn btn-sm btn-outline-secondary">Countries CSV</a>
        <a href="?days=<?= (int) $days ?>&export=targets" class="btn btn-sm btn-outline-secondary">Targets CSV</a>
        <a href="<?= e(base_url('admin/privacy_settings.php')) ?>" class="btn btn-sm btn-outline-dark">Configure Tracking</a>
    </div>
</div>

<div class="alert alert-info" role="status">
    <strong>First-party website statistics are active for all public page visits, regardless of optional-service choices.</strong>
    Last recorded pageview: <?= $latestView ? e((string) $latestView) : 'none' ?>.
    <?php if (!$identityReady || !ict_webstats_has_metadata_columns()): ?>Apply the statistics migration to enable all breakdowns.<?php endif; ?>
    <div class="small mt-1">Visitors estimate browser sessions, not people. Visits restart after 30 minutes of inactivity. New records include public URL paths without queries, country when available, coarse device/browser/OS categories, and referrer domains. IP addresses and full browser strings are not stored. Cleared or blocked session cookies affect visitor estimates. Legacy estimates use older methods. Missing historical traffic cannot be reconstructed.</div>
    <div class="small mt-1">Reports refresh automatically every 60 seconds while visible. <a href="?days=<?= (int) $days ?>">Refresh now</a></div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-3">
        <div class="card card-soft h-100">
            <div class="card-body">
                <div class="small text-secondary">Views vs Previous</div>
                <div class="h5 fw-bold mb-0"><?= e(number_format($deltaViews, 2)) ?>%</div>
                <div class="small text-secondary"><?= number_format($previousTotalViews) ?> previous</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-soft h-100">
            <div class="card-body">
                <div class="small text-secondary">Visitors vs Previous</div>
                <div class="h5 fw-bold mb-0"><?= e(number_format($deltaVisitors, 2)) ?>%</div>
                <div class="small text-secondary"><?= number_format($previousUniqueVisitors) ?> previous</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-soft h-100">
            <div class="card-body">
                <div class="small text-secondary">Inquiries vs Previous</div>
                <div class="h5 fw-bold mb-0"><?= e(number_format($deltaInquiries, 2)) ?>%</div>
                <div class="small text-secondary"><?= number_format($previousTotalInquiries) ?> previous</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-soft h-100">
            <div class="card-body">
                <div class="small text-secondary">Clicks vs Previous</div>
                <div class="h5 fw-bold mb-0"><?= e(number_format($deltaClicks, 2)) ?>%</div>
                <div class="small text-secondary"><?= number_format($previousTotalTrackedClicks) ?> previous</div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-6 col-xl-2">
        <div class="card card-soft h-100">
            <div class="card-body">
                <div class="small text-secondary">Page Views</div>
                <div class="h4 fw-bold mb-0"><?= number_format($totalViews) ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-2">
        <div class="card card-soft h-100">
            <div class="card-body">
                <div class="small text-secondary">Measured Visitors</div>
                <div class="h4 fw-bold mb-0"><?= number_format($uniqueVisitors) ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-2">
        <div class="card card-soft h-100">
            <div class="card-body">
                <div class="small text-secondary">Visits</div>
                <div class="h4 fw-bold mb-0"><?= number_format($totalVisits) ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-2">
        <div class="card card-soft h-100">
            <div class="card-body">
                <div class="small text-secondary">Inquiries</div>
                <div class="h4 fw-bold mb-0"><?= number_format($totalInquiries) ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-2">
        <div class="card card-soft h-100">
            <div class="card-body">
                <div class="small text-secondary">Tracked Clicks</div>
                <div class="h4 fw-bold mb-0"><?= number_format($totalTrackedClicks) ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-2">
        <div class="card card-soft h-100">
            <div class="card-body">
                <div class="small text-secondary">Inquiry Rate</div>
                <div class="h4 fw-bold mb-0"><?= e(number_format($inquiryConversionRate, 2)) ?>%</div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-3">
        <div class="card card-soft h-100">
            <div class="card-body">
                <div class="small text-secondary">Avg Views / Day</div>
                <div class="h5 fw-bold mb-0"><?= e(number_format($avgViewsPerDay, 1)) ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-soft h-100">
            <div class="card-body">
                <div class="small text-secondary">Pages / Visitor</div>
                <div class="h5 fw-bold mb-0"><?= e(number_format($pagesPerVisitor, 2)) ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-soft h-100">
            <div class="card-body">
                <div class="small text-secondary">Range Start</div>
                <div class="h5 fw-bold mb-0"><?= e($rangeStart->format('d M Y')) ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-soft h-100">
            <div class="card-body">
                <div class="small text-secondary">Countries (Active)</div>
                <div class="h5 fw-bold mb-0"><?= number_format($uniqueCountries) ?></div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-12">
        <div class="card card-soft">
            <div class="card-body">
                <h2 class="h6 mb-3">Traffic, Visitors, Visits, Inquiries, and Tracked Clicks Trend</h2>
                <div class="stats-chart-wrap stats-chart-tall">
                    <canvas id="trafficTrendChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-lg-4">
        <div class="card card-soft h-100">
            <div class="card-body">
                <h2 class="h6 mb-3">Device Split</h2>
                <div class="stats-chart-wrap">
                    <canvas id="deviceChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card card-soft h-100">
            <div class="card-body">
                <h2 class="h6 mb-3">Browser Split</h2>
                <div class="stats-chart-wrap">
                    <canvas id="browserChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card card-soft h-100">
            <div class="card-body">
                <h2 class="h6 mb-3">Operating Systems</h2>
                <div class="stats-chart-wrap">
                    <canvas id="osChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-lg-6">
        <div class="card card-soft h-100">
            <div class="card-body">
                <h2 class="h6 mb-3">Top Pages</h2>
                <div class="stats-chart-wrap">
                    <canvas id="topPagesChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card card-soft h-100">
            <div class="card-body">
                <h2 class="h6 mb-3">Traffic Sources (Referrer Domains)</h2>
                <div class="stats-chart-wrap">
                    <canvas id="referrersChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-lg-7">
        <div class="card card-soft h-100">
            <div class="card-body">
                <h2 class="h6 mb-3">Traffic by Country</h2>
                <div class="stats-chart-wrap">
                    <canvas id="countryTrafficChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card card-soft h-100">
            <div class="card-body">
                <h2 class="h6 mb-3">Country Breakdown Table</h2>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Country</th>
                                <th class="text-end">Pageviews</th>
                                <th class="text-end">Visitors</th>
                                <th class="text-end">Share</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if ($countryBreakdown): ?>
                            <?php foreach ($countryBreakdown as $row): ?>
                                <?php
                                $visits = (int) ($row['visits'] ?? 0);
                                $visitors = (int) ($row['visitors'] ?? 0);
                                $share = $totalViews > 0 ? ($visits / $totalViews) * 100 : 0;
                                ?>
                                <tr>
                                    <td><?= e((string) ($row['country_label'] ?? 'Unknown')) ?></td>
                                    <td class="text-end"><?= number_format($visits) ?></td>
                                    <td class="text-end"><?= number_format($visitors) ?></td>
                                    <td class="text-end"><?= e(number_format($share, 1)) ?>%</td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="text-center text-secondary">No country data available yet.</td>
                            </tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-lg-6">
        <div class="card card-soft h-100">
            <div class="card-body">
                <h2 class="h6 mb-3">Top Landing vs Exit Pages</h2>
                <div class="stats-chart-wrap">
                    <canvas id="landingExitChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-3">
        <div class="card card-soft h-100">
            <div class="card-body">
                <h2 class="h6 mb-3">Hourly Traffic Pattern</h2>
                <div class="stats-chart-wrap">
                    <canvas id="hourlyChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-3">
        <div class="card card-soft h-100">
            <div class="card-body">
                <h2 class="h6 mb-3">Weekday Pattern</h2>
                <div class="stats-chart-wrap">
                    <canvas id="weekdayChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-lg-4">
        <div class="card card-soft h-100">
            <div class="card-body">
                <h2 class="h6 mb-3">Tracked Click Event Types</h2>
                <div class="stats-chart-wrap">
                    <canvas id="eventTypeChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card card-soft h-100">
            <div class="card-body">
                <h2 class="h6 mb-3">Top Click Targets</h2>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Target Group / URL</th>
                                <th class="text-end">Clicks</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if ($topEventTargets): ?>
                            <?php foreach ($topEventTargets as $row): ?>
                                <tr>
                                    <td class="text-break"><?= e((string) ($row['target_label'] ?? '')) ?></td>
                                    <td class="text-end"><?= number_format((int) ($row['total'] ?? 0)) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="2" class="text-center text-secondary">No tracked click targets in this range.</td>
                            </tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-xl-6">
        <div class="card card-soft h-100">
            <div class="card-body">
                <h2 class="h6 mb-3">Top Visitors</h2>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Visitor</th>
                                <th class="text-end">Pageviews</th>
                                <th>Last Seen</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if ($topVisitors): ?>
                            <?php foreach ($topVisitors as $row): ?>
                                <tr>
                                    <td><?= e('Visitor ' . substr(hash('sha256', (string) ($row['visitor_label'] ?? '')), 0, 10)) ?></td>
                                    <td class="text-end"><?= number_format((int) ($row['visits'] ?? 0)) ?></td>
                                    <td class="text-secondary"><?= e(date('d M Y H:i', strtotime((string) ($row['last_seen'] ?? 'now')))) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3" class="text-center text-secondary">No visitor data found in this range.</td>
                            </tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-6">
        <div class="card card-soft h-100">
            <div class="card-body">
                <h2 class="h6 mb-3">Top Pages Table</h2>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Path</th>
                                <th class="text-end">Views</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if ($topPages): ?>
                            <?php foreach ($topPages as $row): ?>
                                <tr>
                                    <td class="text-break"><?= e((string) ($row['path'] ?? '/')) ?></td>
                                    <td class="text-end"><?= number_format((int) ($row['views'] ?? 0)) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="2" class="text-center text-secondary">No page view records found in this range.</td>
                            </tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-12">
        <div class="card card-soft">
            <div class="card-body">
                <h2 class="h6 mb-3">Recent Pageviews (Latest 40)</h2>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Time</th>
                                <th>Path</th>
                                <th>Country</th>
                                <th>Device</th>
                                <th>Browser</th>
                                <th>OS</th>
                                <th>Referrer</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($recentVisits as $row): ?>
                            <tr>
                                <td class="text-nowrap text-secondary"><?= e(date('d M H:i', strtotime((string) ($row['viewed_at'] ?? 'now')))) ?></td>
                                <td class="text-break"><?= e((string) ($row['path'] ?? '/')) ?></td>
                                <td><?= e(trim((string) ($row['country'] ?? '')) ?: 'Unknown') ?></td>
                                <td><?= e((string) ($row['device_label'] ?? 'Unknown')) ?></td>
                                <td><?= e((string) ($row['browser_label'] ?? 'Unknown')) ?></td>
                                <td><?= e((string) ($row['os_label'] ?? 'Unknown')) ?></td>
                                <td class="text-break text-secondary"><?= e((string) mb_substr((string) ($row['referrer'] ?? ''), 0, 80)) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12">
        <div class="card card-soft">
            <div class="card-body">
                <h2 class="h6 mb-3">Recent Tracked Events (Latest 40)</h2>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Time</th>
                                <th>Event Type</th>
                                <th>Page</th>
                                <th>Target</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if ($recentEvents): ?>
                            <?php foreach ($recentEvents as $row): ?>
                                <tr>
                                    <td class="text-nowrap text-secondary"><?= e(date('d M H:i', strtotime((string) ($row['created_at'] ?? 'now')))) ?></td>
                                    <td><?= e(ucwords(str_replace('_', ' ', (string) ($row['event_type'] ?? '')))) ?></td>
                                    <td class="text-break"><?= e((string) ($row['path'] ?? '/')) ?></td>
                                    <td class="text-break text-secondary"><?= e((string) mb_substr((string) ($row['target_url'] ?? ''), 0, 110)) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="text-center text-secondary">No tracked events found yet.</td>
                            </tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="<?= e(base_url('assets/vendor/chart.js-4.4.4/dist/chart.umd.js')) ?>"></script>
<script>
(function () {
    if (typeof Chart === 'undefined') return;

    var palette = {
        blue: '#0ea5e9',
        indigo: '#6366f1',
        green: '#16a34a',
        amber: '#f59e0b',
        rose: '#f43f5e',
        slate: '#334155',
        cyan: '#06b6d4',
        violet: '#8b5cf6',
        teal: '#14b8a6'
    };

    var sharedOptions = {
        responsive: true,
        maintainAspectRatio: false,
        interaction: { mode: 'index', intersect: false },
        plugins: {
            legend: { labels: { boxWidth: 10, boxHeight: 10, usePointStyle: true, pointStyle: 'circle' } }
        },
        scales: {
            x: { ticks: { maxRotation: 0, autoSkip: true, maxTicksLimit: 12 }, grid: { display: false } },
            y: { beginAtZero: true, ticks: { precision: 0 } }
        }
    };

    var labels = <?= json_encode($chartLabels, JSON_UNESCAPED_SLASHES) ?>;
    var viewsSeries = <?= json_encode($viewsSeries, JSON_UNESCAPED_SLASHES) ?>;
    var visitorsSeries = <?= json_encode($visitorsSeries, JSON_UNESCAPED_SLASHES) ?>;
    var visitsSeries = <?= json_encode($visitsSeries, JSON_UNESCAPED_SLASHES) ?>;
    var inquiriesSeries = <?= json_encode($inquiriesSeries, JSON_UNESCAPED_SLASHES) ?>;
    var clicksSeries = <?= json_encode($clicksSeries, JSON_UNESCAPED_SLASHES) ?>;
    var landingSeries = <?= json_encode($landingSeries, JSON_UNESCAPED_SLASHES) ?>;
    var exitSeries = <?= json_encode($exitSeries, JSON_UNESCAPED_SLASHES) ?>;
    var countryLabels = <?= json_encode($countryLabels, JSON_UNESCAPED_SLASHES) ?>;
    var countrySeries = <?= json_encode($countrySeries, JSON_UNESCAPED_SLASHES) ?>;

    new Chart(document.getElementById('trafficTrendChart'), {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                { label: 'Views', data: viewsSeries, borderColor: palette.blue, backgroundColor: 'rgba(14,165,233,.12)', borderWidth: 2, tension: .35, fill: true },
                { label: 'Visitors', data: visitorsSeries, borderColor: palette.indigo, backgroundColor: 'transparent', borderWidth: 2, tension: .35, fill: false },
                { label: 'Visits', data: visitsSeries, borderColor: '#9333ea', backgroundColor: 'transparent', borderWidth: 2, tension: .35, fill: false },
                { label: 'Inquiries', data: inquiriesSeries, borderColor: palette.green, backgroundColor: 'transparent', borderWidth: 2, tension: .35, fill: false },
                { label: 'Tracked Clicks', data: clicksSeries, borderColor: palette.amber, backgroundColor: 'transparent', borderWidth: 2, tension: .35, fill: false }
            ]
        },
        options: sharedOptions
    });

    var pieOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { position: 'bottom' } }
    };

    new Chart(document.getElementById('deviceChart'), {
        type: 'doughnut',
        data: {
            labels: <?= json_encode($deviceLabels, JSON_UNESCAPED_SLASHES) ?>,
            datasets: [{ data: <?= json_encode($deviceSeries, JSON_UNESCAPED_SLASHES) ?>, backgroundColor: ['#0ea5e9', '#6366f1', '#16a34a', '#f59e0b', '#f43f5e'] }]
        },
        options: pieOptions
    });

    new Chart(document.getElementById('browserChart'), {
        type: 'bar',
        data: {
            labels: <?= json_encode($browserLabels, JSON_UNESCAPED_SLASHES) ?>,
            datasets: [{ label: 'Pageviews', data: <?= json_encode($browserSeries, JSON_UNESCAPED_SLASHES) ?>, backgroundColor: '#6366f1', borderRadius: 6 }]
        },
        options: sharedOptions
    });

    new Chart(document.getElementById('osChart'), {
        type: 'bar',
        data: {
            labels: <?= json_encode($osLabels, JSON_UNESCAPED_SLASHES) ?>,
            datasets: [{ label: 'Pageviews', data: <?= json_encode($osSeries, JSON_UNESCAPED_SLASHES) ?>, backgroundColor: '#14b8a6', borderRadius: 6 }]
        },
        options: sharedOptions
    });

    new Chart(document.getElementById('topPagesChart'), {
        type: 'bar',
        data: {
            labels: <?= json_encode($topPageLabels, JSON_UNESCAPED_SLASHES) ?>,
            datasets: [{ label: 'Views', data: <?= json_encode($topPageSeries, JSON_UNESCAPED_SLASHES) ?>, backgroundColor: '#0ea5e9', borderRadius: 6 }]
        },
        options: sharedOptions
    });

    new Chart(document.getElementById('referrersChart'), {
        type: 'bar',
        data: {
            labels: <?= json_encode($referrerLabels, JSON_UNESCAPED_SLASHES) ?>,
            datasets: [{ label: 'Pageviews', data: <?= json_encode($referrerSeries, JSON_UNESCAPED_SLASHES) ?>, backgroundColor: '#8b5cf6', borderRadius: 6 }]
        },
        options: sharedOptions
    });

    new Chart(document.getElementById('countryTrafficChart'), {
        type: 'bar',
        data: {
            labels: countryLabels,
            datasets: [{ label: 'Pageviews', data: countrySeries, backgroundColor: '#06b6d4', borderRadius: 6 }]
        },
        options: sharedOptions
    });

    var landing = <?= json_encode($landingLabels, JSON_UNESCAPED_SLASHES) ?>;
    var exit = <?= json_encode($exitLabels, JSON_UNESCAPED_SLASHES) ?>;
    var mergedPaths = Array.from(new Set([].concat(landing, exit)));
    var landingData = mergedPaths.map(function (path) {
        var i = landing.indexOf(path);
        return i >= 0 ? landingSeries[i] : 0;
    });
    var exitData = mergedPaths.map(function (path) {
        var i = exit.indexOf(path);
        return i >= 0 ? exitSeries[i] : 0;
    });

    new Chart(document.getElementById('landingExitChart'), {
        type: 'bar',
        data: {
            labels: mergedPaths,
            datasets: [
                { label: 'Landing Sessions', data: landingData, backgroundColor: '#16a34a', borderRadius: 6 },
                { label: 'Exit Sessions', data: exitData, backgroundColor: '#f97316', borderRadius: 6 }
            ]
        },
        options: sharedOptions
    });

    new Chart(document.getElementById('hourlyChart'), {
        type: 'line',
        data: {
            labels: <?= json_encode(array_map(static fn(int $h): string => sprintf('%02d:00', $h), range(0, 23)), JSON_UNESCAPED_SLASHES) ?>,
            datasets: [{ label: 'Pageviews', data: <?= json_encode($hourlySeries, JSON_UNESCAPED_SLASHES) ?>, borderColor: '#06b6d4', backgroundColor: 'rgba(6,182,212,.12)', fill: true, tension: .3 }]
        },
        options: sharedOptions
    });

    new Chart(document.getElementById('weekdayChart'), {
        type: 'bar',
        data: {
            labels: <?= json_encode($weekdayLabels, JSON_UNESCAPED_SLASHES) ?>,
            datasets: [{ label: 'Pageviews', data: <?= json_encode($weekdaySeries, JSON_UNESCAPED_SLASHES) ?>, backgroundColor: '#334155', borderRadius: 6 }]
        },
        options: sharedOptions
    });

    new Chart(document.getElementById('eventTypeChart'), {
        type: 'doughnut',
        data: {
            labels: <?= json_encode($eventLabels, JSON_UNESCAPED_SLASHES) ?>,
            datasets: [{ data: <?= json_encode($eventSeries, JSON_UNESCAPED_SLASHES) ?>, backgroundColor: ['#0ea5e9', '#16a34a', '#f59e0b', '#8b5cf6', '#ef4444'] }]
        },
        options: pieOptions
    });
})();
</script>

<?php require __DIR__ . '/_bottom.php'; ?>
