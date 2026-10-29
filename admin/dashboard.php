<?php
declare(strict_types=1);

$adminTitle = 'Dashboard';
$adminPage = 'dashboard';

require_once __DIR__ . '/../includes/auth.php';

admin_require_auth();
header('Cache-Control: private, no-store, max-age=0');

$stats = [
    'destinations'    => fetch_one("SELECT COUNT(*) AS total FROM destinations")['total'] ?? 0,
    'tours'           => fetch_one("SELECT COUNT(*) AS total FROM tours")['total'] ?? 0,
    'itineraries'     => fetch_one("SELECT COUNT(*) AS total FROM itineraries")['total'] ?? 0,
    'countries'       => fetch_one("SELECT COUNT(*) AS total FROM country_pages")['total'] ?? 0,
    'pages'           => fetch_one("SELECT COUNT(*) AS total FROM static_pages")['total'] ?? 0,
    'faq_items'       => db_table_exists('faq_entries') ? (fetch_one("SELECT COUNT(*) AS total FROM faq_entries")['total'] ?? 0) : 0,
    'reviews'         => fetch_one("SELECT COUNT(*) AS total FROM reviews")['total'] ?? 0,
    'inquiries'       => fetch_one("SELECT COUNT(*) AS total FROM contact_inquiries")['total'] ?? 0,
    'inquiries_week'  => fetch_one("SELECT COUNT(*) AS total FROM contact_inquiries WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")['total'] ?? 0,
    'inquiries_month' => fetch_one("SELECT COUNT(*) AS total FROM contact_inquiries WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")['total'] ?? 0,
    'blog_posts'      => fetch_one("SELECT COUNT(*) AS total FROM blog_posts WHERE is_published = 1 AND (published_at IS NULL OR published_at <= NOW())")['total'] ?? 0,
    'views_today'     => fetch_one("SELECT COUNT(*) AS total FROM page_views WHERE DATE(viewed_at) = CURDATE()")['total'] ?? 0,
    'views_week'      => fetch_one("SELECT COUNT(*) AS total FROM page_views WHERE viewed_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")['total'] ?? 0,
];

$latestInquiries = fetch_all("
    SELECT id, name, email, country, created_at
    FROM contact_inquiries
    ORDER BY id DESC
    LIMIT 8
");

$topDestinations = fetch_all("
    SELECT pv.path, COUNT(*) AS views
    FROM page_views pv
    WHERE pv.path LIKE '/destinations/%'
      AND pv.viewed_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY pv.path
    ORDER BY views DESC
    LIMIT 5
");

$recentReviews = fetch_all("
    SELECT customer_name, country, rating, review_text, created_at
    FROM reviews
    ORDER BY id DESC
    LIMIT 3
");

$partnerStatusColumn = null;
if (db_table_exists('business_partners')) {
    if (db_column_exists('business_partners', 'application_status')) {
        $partnerStatusColumn = 'application_status';
    } elseif (db_column_exists('business_partners', 'status')) {
        $partnerStatusColumn = 'status';
    }
}

$partnerStatusRows = $partnerStatusColumn ? fetch_all("
    SELECT {$partnerStatusColumn} AS partner_status, COUNT(*) AS total
    FROM business_partners
    GROUP BY {$partnerStatusColumn}
    ORDER BY total DESC
") : [];

$partnerStatusSummary = [];
foreach ($partnerStatusRows as $row) {
    $key = trim((string) ($row['partner_status'] ?? ''));
    if ($key !== '') {
        $partnerStatusSummary[$key] = (int) ($row['total'] ?? 0);
    }
}

$scheduledBlogPosts = db_table_exists('blog_posts') && db_column_exists('blog_posts', 'published_at')
    ? (int) (fetch_one("SELECT COUNT(*) AS total FROM blog_posts WHERE is_published = 1 AND published_at > NOW()")['total'] ?? 0)
    : 0;
$publishedDestinations = (int) (fetch_one("SELECT COUNT(*) AS total FROM destinations WHERE is_published = 1")['total'] ?? 0);
$publishedExperiences = db_table_exists('experiences')
    ? (int) (fetch_one("SELECT COUNT(*) AS total FROM experiences WHERE is_published = 1")['total'] ?? 0)
    : 0;
$publishedItineraries = (int) (fetch_one("SELECT COUNT(*) AS total FROM itineraries WHERE is_published = 1")['total'] ?? 0);
$publishedCountries = (int) (fetch_one("SELECT COUNT(*) AS total FROM country_pages WHERE is_published = 1")['total'] ?? 0);

$aiSearchReadiness = [
    ['label' => 'Top country pages', 'value' => $publishedCountries, 'target' => 20, 'href' => base_url('admin/countries.php')],
    ['label' => 'Destination guides', 'value' => $publishedDestinations, 'target' => 30, 'href' => base_url('admin/destinations.php')],
    ['label' => 'Experience pages', 'value' => $publishedExperiences, 'target' => 16, 'href' => base_url('admin/experiences.php')],
    ['label' => 'Itinerary pages', 'value' => $publishedItineraries, 'target' => 12, 'href' => base_url('admin/itineraries.php')],
    ['label' => 'Scheduled blog posts', 'value' => $scheduledBlogPosts, 'target' => 20, 'href' => base_url('admin/blog_posts.php')],
];

$contentStats = [
    ['label' => 'Destinations', 'value' => (int) $stats['destinations'], 'href' => base_url('admin/destinations.php')],
    ['label' => 'Tours', 'value' => (int) $stats['tours'], 'href' => base_url('admin/tours.php')],
    ['label' => 'Itineraries', 'value' => (int) $stats['itineraries'], 'href' => base_url('admin/itineraries.php')],
    ['label' => 'Blog Posts', 'value' => (int) $stats['blog_posts'], 'href' => base_url('admin/blog_posts.php')],
    ['label' => 'FAQ Items', 'value' => (int) $stats['faq_items'], 'href' => base_url('admin/faqs.php')],
    ['label' => 'Reviews', 'value' => (int) $stats['reviews'], 'href' => base_url('admin/reviews.php')],
    ['label' => 'Inquiries', 'value' => (int) $stats['inquiries'], 'href' => base_url('admin/inquiries.php')],
];

$quickActions = [
    ['label' => 'Homepage Manager', 'href' => base_url('admin/homepage_manager.php'), 'class' => 'btn btn-primary btn-sm'],
    ['label' => 'Add Destination', 'href' => base_url('admin/destination_form.php'), 'class' => 'btn btn-primary btn-sm'],
    ['label' => 'New Blog Post', 'href' => base_url('admin/blog_post_form.php'), 'class' => 'btn btn-outline-primary btn-sm'],
    ['label' => 'Add Tour', 'href' => base_url('admin/tour_form.php'), 'class' => 'btn btn-outline-secondary btn-sm'],
    ['label' => 'Add Itinerary', 'href' => base_url('admin/itinerary_form.php'), 'class' => 'btn btn-outline-secondary btn-sm'],
    ['label' => 'Add Review', 'href' => base_url('admin/review_form.php'), 'class' => 'btn btn-outline-secondary btn-sm'],
    ['label' => 'Media Library', 'href' => base_url('admin/media_library.php'), 'class' => 'btn btn-outline-dark btn-sm'],
];

$trendDates = [];
for ($i = 13; $i >= 0; $i--) {
    $trendDates[] = date('Y-m-d', strtotime("-{$i} days"));
}

$inquiryTrendRows = fetch_all("
    SELECT DATE(created_at) AS trend_day, COUNT(*) AS total
    FROM contact_inquiries
    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 13 DAY)
    GROUP BY DATE(created_at)
");

$viewTrendRows = fetch_all("
    SELECT DATE(viewed_at) AS trend_day, COUNT(*) AS total
    FROM page_views
    WHERE viewed_at >= DATE_SUB(CURDATE(), INTERVAL 13 DAY)
    GROUP BY DATE(viewed_at)
");

$mapTrend = static function (array $rows): array {
    $mapped = [];
    foreach ($rows as $row) {
        $mapped[(string) $row['trend_day']] = (int) $row['total'];
    }
    return $mapped;
};

$inquiryTrendMap = $mapTrend($inquiryTrendRows);
$viewTrendMap = $mapTrend($viewTrendRows);
$dashboardChartPayload = [
    'labels' => array_map(static fn(string $date): string => date('M j', strtotime($date)), $trendDates),
    'inquiries' => array_map(static fn(string $date): int => $inquiryTrendMap[$date] ?? 0, $trendDates),
    'views' => array_map(static fn(string $date): int => $viewTrendMap[$date] ?? 0, $trendDates),
];

require __DIR__ . '/_top.php';
?>

<div class="row">
    <div class="col-sm-12">
        <div class="home-tab">
            <div class="d-sm-flex align-items-center justify-content-between border-bottom pb-3 mb-4">
                <div>
                    <h4 class="card-title card-title-dash mb-1">Operations snapshot</h4>
                    <p class="text-muted mb-0">Lead activity, content health, and quick admin actions in one view.</p>
                </div>
                <div class="btn-wrapper">
                    <a href="<?= e(base_url('admin/inquiries.php')) ?>" class="btn btn-outline-dark btn-sm"><i class="mdi mdi-email-fast-outline"></i> Open Inquiries</a>
                    <a href="<?= e(base_url('admin/webstats.php')) ?>" class="btn btn-primary btn-sm text-white"><i class="mdi mdi-chart-line"></i> View Web Stats</a>
                </div>
            </div>

            <div class="row">
                <div class="col-sm-12">
                    <div class="statistics-details d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4">
                        <div>
                            <p class="statistics-title">Inquiries (7d)</p>
                            <h3 class="rate-percentage"><?= (int) $stats['inquiries_week'] ?></h3>
                            <p class="text-muted d-flex mb-0"><span>New lead activity</span></p>
                        </div>
                        <div>
                            <p class="statistics-title">Inquiries (30d)</p>
                            <h3 class="rate-percentage"><?= (int) $stats['inquiries_month'] ?></h3>
                            <p class="text-muted d-flex mb-0"><span>Rolling month total</span></p>
                        </div>
                        <div>
                            <p class="statistics-title">Page Views Today</p>
                            <h3 class="rate-percentage"><?= (int) $stats['views_today'] ?></h3>
                            <p class="text-muted d-flex mb-0"><span>Traffic since midnight</span></p>
                        </div>
                        <div>
                            <p class="statistics-title">Page Views (7d)</p>
                            <h3 class="rate-percentage"><?= (int) $stats['views_week'] ?></h3>
                            <p class="text-muted d-flex mb-0"><span>Weekly visibility</span></p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card card-soft mb-4">
                <div class="card-body">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                        <div>
                            <h4 class="card-title card-title-dash mb-1">AI/search content readiness</h4>
                            <p class="text-muted mb-0">Coverage targets for entity-rich pages, internal linking, and scheduled publishing.</p>
                        </div>
                        <div class="d-flex flex-wrap gap-2">
                            <a href="<?= e(base_url('admin/ai_search_audit.php')) ?>" class="btn btn-primary btn-sm text-white">Open AI/Search Audit</a>
                            <a href="<?= e(base_url('admin/blog_posts.php')) ?>" class="btn btn-outline-primary btn-sm">Review content queue</a>
                        </div>
                    </div>
                    <div class="row g-3">
                        <?php foreach ($aiSearchReadiness as $item): ?>
                            <?php
                            $target = max(1, (int) $item['target']);
                            $value = max(0, (int) $item['value']);
                            $percent = min(100, (int) round(($value / $target) * 100));
                            ?>
                            <div class="col-md-6 col-xl">
                                <a href="<?= e($item['href']) ?>" class="text-decoration-none">
                                    <div class="border rounded-4 p-3 h-100 bg-white">
                                        <div class="d-flex justify-content-between align-items-start gap-2">
                                            <span class="small fw-bold text-dark"><?= e($item['label']) ?></span>
                                            <span class="badge <?= $value >= $target ? 'bg-success' : 'bg-warning text-dark' ?>"><?= $value ?>/<?= $target ?></span>
                                        </div>
                                        <div class="progress mt-3" style="height:6px;">
                                            <div class="progress-bar <?= $value >= $target ? 'bg-success' : 'bg-primary' ?>" role="progressbar" style="width: <?= $percent ?>%;" aria-valuenow="<?= $percent ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="row g-4 mb-4 dashboard-two-col">
                <div class="col-xl-8">
                    <div class="card card-soft h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div>
                                    <h4 class="card-title card-title-dash mb-1">Lead and traffic trend</h4>
                                    <p class="text-muted mb-0">Last 14 days, using real inquiry and page-view data.</p>
                                </div>
                            </div>
                            <canvas id="dashboardTrendChart" height="118" aria-label="Inquiries and page views trend"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-xl-4">
                    <div class="card card-soft h-100">
                        <div class="card-body">
                            <h4 class="card-title card-title-dash mb-3">Review/content status</h4>
                            <canvas id="dashboardContentChart" height="178" aria-label="Content status summary"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-xl-8">
                    <div class="card card-soft">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h4 class="card-title card-title-dash mb-0">Content overview</h4>
                                <a href="<?= e(base_url('admin/pages.php')) ?>" class="text-primary small fw-semibold">Manage content</a>
                            </div>
                            <div class="row g-3">
                                <?php foreach ($contentStats as $item): ?>
                                    <div class="col-6 col-md-4 col-xl-3">
                                        <a href="<?= e($item['href']) ?>" class="text-decoration-none">
                                            <div class="border rounded-4 p-3 h-100 bg-white">
                                                <p class="text-muted small mb-1"><?= e($item['label']) ?></p>
                                                <h3 class="mb-0 text-dark fw-bold"><?= (int) $item['value'] ?></h3>
                                            </div>
                                        </a>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-4">
                    <div class="card card-soft h-100">
                        <div class="card-body">
                            <h4 class="card-title card-title-dash mb-3">Quick actions</h4>
                            <div class="d-grid gap-2">
                                <?php foreach ($quickActions as $action): ?>
                                    <a href="<?= e($action['href']) ?>" class="<?= e($action['class']) ?>"><?= e($action['label']) ?></a>
                                <?php endforeach; ?>
                            </div>
                            <?php if ($partnerStatusSummary): ?>
                                <div class="border-top mt-4 pt-3">
                                    <h5 class="mb-3">Partner pipeline</h5>
                                    <div class="d-grid gap-2">
                                        <?php foreach ($partnerStatusSummary as $status => $count): ?>
                                            <div class="d-flex justify-content-between align-items-center">
                                                <span class="text-capitalize"><?= e(str_replace('_', ' ', $status)) ?></span>
                                                <span class="badge bg-light text-dark"><?= (int) $count ?></span>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-lg-4">
                    <div class="card card-soft h-100">
                        <div class="card-body">
                            <h4 class="card-title card-title-dash mb-3">Top destination pages (30d)</h4>
                            <?php if ($topDestinations): ?>
                                <div class="table-responsive">
                                    <table class="table table-borderless">
                                        <tbody>
                                        <?php foreach ($topDestinations as $td): ?>
                                            <tr>
                                                <td class="text-truncate" style="max-width: 220px;"><?= e((string) $td['path']) ?></td>
                                                <td class="text-end fw-bold"><?= (int) $td['views'] ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <p class="text-muted mb-0">No destination page view data yet.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card card-soft h-100">
                        <div class="card-body">
                            <h4 class="card-title card-title-dash mb-3">Recent reviews</h4>
                            <?php if ($recentReviews): ?>
                                <?php foreach ($recentReviews as $rev): ?>
                                    <div class="border-bottom pb-3 mb-3">
                                        <div class="d-flex justify-content-between gap-2 mb-1">
                                            <strong><?= e((string) $rev['customer_name']) ?></strong>
                                            <span class="text-muted small"><?= e((string) ($rev['country'] ?? '')) ?></span>
                                        </div>
                                        <div class="text-warning small mb-1"><?= str_repeat('★', max(0, (int) $rev['rating'])) ?></div>
                                        <p class="text-muted small mb-0"><?= e(mb_substr((string) $rev['review_text'], 0, 120)) ?>...</p>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p class="text-muted mb-0">No reviews yet.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card card-soft h-100">
                        <div class="card-body">
                            <h4 class="card-title card-title-dash mb-3">Admin shortcuts</h4>
                            <div class="d-grid gap-2">
                                <a href="<?= e(base_url('admin/analytics_settings.php')) ?>" class="btn btn-outline-secondary btn-sm">Tracking settings</a>
                                <a href="<?= e(base_url('admin/site_settings.php')) ?>" class="btn btn-outline-secondary btn-sm">Site settings</a>
                                <a href="<?= e(base_url('admin/weather_api.php')) ?>" class="btn btn-outline-secondary btn-sm">Weather API / Data</a>
                                <a href="<?= e(base_url('admin/business_partners.php')) ?>" class="btn btn-outline-secondary btn-sm">Business partners</a>
                                <a href="<?= e(base_url('admin/social_posts.php')) ?>" class="btn btn-outline-secondary btn-sm">Social drafts</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-sm-12">
                    <div class="card card-soft">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h4 class="card-title card-title-dash mb-0">Latest inquiries</h4>
                                <a href="<?= e(base_url('admin/inquiries.php')) ?>" class="text-primary small fw-semibold">View all</a>
                            </div>
                            <?php if ($latestInquiries): ?>
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Country</th>
                                            <th>Date</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        <?php foreach ($latestInquiries as $item): ?>
                                            <tr>
                                                <td><?= e((string) $item['name']) ?></td>
                                                <td><?= e((string) ($item['email'] ?? '')) ?></td>
                                                <td><?= e((string) ($item['country'] ?? '')) ?></td>
                                                <td><?= e(date('d M Y', strtotime((string) $item['created_at']))) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <p class="text-muted mb-0">No inquiries yet.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.8/dist/chart.umd.min.js" defer></script>
<script>
window.addEventListener('DOMContentLoaded', function () {
    if (!window.Chart) return;
    var trendPayload = <?= json_encode($dashboardChartPayload, JSON_UNESCAPED_SLASHES) ?>;
    var trendCanvas = document.getElementById('dashboardTrendChart');
    if (trendCanvas) {
        new Chart(trendCanvas, {
            type: 'line',
            data: {
                labels: trendPayload.labels,
                datasets: [
                    {
                        label: 'Inquiries',
                        data: trendPayload.inquiries,
                        borderColor: '#0d6efd',
                        backgroundColor: 'rgba(13, 110, 253, 0.12)',
                        tension: 0.35,
                        fill: true
                    },
                    {
                        label: 'Page views',
                        data: trendPayload.views,
                        borderColor: '#198754',
                        backgroundColor: 'rgba(25, 135, 84, 0.1)',
                        tension: 0.35,
                        fill: true
                    }
                ]
            },
            options: {
                responsive: true,
                plugins: { legend: { position: 'bottom' } },
                scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
            }
        });
    }

    var contentCanvas = document.getElementById('dashboardContentChart');
    if (contentCanvas) {
        new Chart(contentCanvas, {
            type: 'doughnut',
            data: {
                labels: ['Destinations', 'Itineraries', 'Reviews', 'Inquiries'],
                datasets: [{
                    data: [
                        <?= (int) $stats['destinations'] ?>,
                        <?= (int) $stats['itineraries'] ?>,
                        <?= (int) $stats['reviews'] ?>,
                        <?= (int) $stats['inquiries'] ?>
                    ],
                    backgroundColor: ['#0d6efd', '#20c997', '#ffc107', '#dc3545'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { position: 'bottom' } },
                cutout: '68%'
            }
        });
    }
});
</script>
<?php require __DIR__ . '/_bottom.php'; ?>
