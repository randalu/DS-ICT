<?php
declare(strict_types=1);

// HEAD only, on the new folder at the local default Laragon mount. No old pages.
$base = 'http://127.0.0.1/island/NEW-SITE';
$results = [];
foreach (['/public/', '/app/inventory.json', '/app/bootstrap.php', '/public/assets/site.css'] as $path) {
    $context = stream_context_create(['http' => ['method' => 'HEAD', 'ignore_errors' => true, 'follow_location' => 0, 'timeout' => 5]]);
    unset($http_response_header);
    @file_get_contents($base . $path, false, $context);
    $status = $http_response_header[0] ?? 'unavailable';
    $results[] = ['url' => $base . $path, 'method' => 'HEAD', 'status' => $status];
    if (!preg_match('/\s403\s/', $status)) {
        fwrite(STDERR, 'Apache denial unverified: ' . $path . ' (' . $status . ")\n");
        exit(1);
    }
}
$output = dirname(__DIR__) . '/output';
if (!is_dir($output)) {
    mkdir($output, 0770, true);
}
file_put_contents($output . '/apache-denial.json', json_encode(['result' => 'pass', 'checks' => $results], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
echo "PASS: 4 Apache HEAD requests deny NEW-SITE public and source paths with 403.\n";
