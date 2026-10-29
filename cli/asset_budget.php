<?php
declare(strict_types=1);

/**
 * Asset budget check for the public site.
 *
 * Guards the numbers the v3 rebuild committed to:
 *   - public CSS (v3 sheets + fonts CSS)          <= 90 KB uncompressed
 *   - home page JavaScript                          <= 85 KB gzip
 *   - detail page JavaScript (no Vue)               <= 40 KB gzip
 *
 * The home budget includes the Vue 3.5 global build (~61 KB gzip), which carries
 * the runtime template compiler because the project has no bundler. Pages
 * without islands never load it, which is why their budget is 40 KB.
 *
 * Usage:  php cli\asset_budget.php [--json]
 * Exit:   0 when every budget passes, 1 when any budget is exceeded.
 */

$root = dirname(__DIR__);

$cssFiles = array_merge(
    glob($root . '/assets/css/v3/*.css') ?: [],
    [$root . '/assets/fonts/brand.css']
);

$homeJs = [
    'assets/vendor/alpinejs-3.14.8/dist/cdn.min.js',
    'lib/vue-3.5.44/dist/vue.global.prod.js',
    'assets/js/site/islands.js',
    'assets/js/site/motion.js',
];

$detailJs = [
    'assets/vendor/alpinejs-3.14.8/dist/cdn.min.js',
    'assets/js/site/motion.js',
];

$cssBytes = 0;
foreach ($cssFiles as $file) {
    if (is_file($file)) {
        $cssBytes += (int) filesize($file);
    }
}

$gzip = static function (array $relativeFiles) use ($root): int {
    $total = 0;
    foreach ($relativeFiles as $relative) {
        $path = $root . '/' . ltrim($relative, '/');
        if (!is_file($path)) {
            continue;
        }
        $compressed = gzcompress((string) file_get_contents($path), 9);
        $total += strlen((string) $compressed);
    }

    return $total;
};

$homeJsBytes = $gzip($homeJs);
$detailJsBytes = $gzip($detailJs);

$budgets = [
    'public_css' => ['value' => $cssBytes, 'limit' => 90 * 1024, 'label' => 'Public CSS (uncompressed)', 'unit' => 'KB'],
    'home_js' => ['value' => $homeJsBytes, 'limit' => 85 * 1024, 'label' => 'Home JS (gzip)', 'unit' => 'KB'],
    'detail_js' => ['value' => $detailJsBytes, 'limit' => 40 * 1024, 'label' => 'Detail JS (gzip)', 'unit' => 'KB'],
];

$failed = false;
$report = [];

foreach ($budgets as $key => $budget) {
    $ok = $budget['value'] <= $budget['limit'];
    $failed = $failed || !$ok;
    $report[] = [
        'key' => $key,
        'label' => $budget['label'],
        'value_kb' => round($budget['value'] / 1024, 1),
        'limit_kb' => round($budget['limit'] / 1024, 1),
        'ok' => $ok,
    ];
}

if (in_array('--json', $argv ?? [], true)) {
    echo json_encode(['pass' => !$failed, 'budgets' => $report], JSON_PRETTY_PRINT) . PHP_EOL;
    exit($failed ? 1 : 0);
}

echo "Asset budget\n";
echo str_repeat('-', 52) . PHP_EOL;

foreach ($report as $row) {
    printf(
        "%-28s %6.1f / %5.1f KB  %s\n",
        $row['label'],
        $row['value_kb'],
        $row['limit_kb'],
        $row['ok'] ? 'PASS' : 'FAIL'
    );
}

echo str_repeat('-', 52) . PHP_EOL;
echo $failed ? "Budget exceeded.\n" : "All budgets pass.\n";

exit($failed ? 1 : 0);
