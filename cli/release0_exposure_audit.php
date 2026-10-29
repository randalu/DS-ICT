<?php
declare(strict_types=1);

/**
 * Verify that development, installer, and source-note files are not public.
 *
 * The origin is explicit by design. Run this against a controlled local or
 * staging vhost; never let a release check silently probe production.
 */

$baseUrl = '';
$format = 'text';
$strict = false;
foreach (array_slice($argv, 1) as $argument) {
    if (str_starts_with($argument, '--base-url=')) {
        $baseUrl = rtrim(trim(substr($argument, 11)), '/');
        continue;
    }
    if (str_starts_with($argument, '--format=')) {
        $format = strtolower(trim(substr($argument, 9)));
        if (!in_array($format, ['text', 'json'], true)) {
            fwrite(STDERR, "Usage: php cli/release0_exposure_audit.php --base-url=URL [--format=text|json] [--strict]\n");
            exit(2);
        }
        continue;
    }
    if ($argument === '--strict') {
        $strict = true;
        continue;
    }

    fwrite(STDERR, "Usage: php cli/release0_exposure_audit.php --base-url=URL [--format=text|json] [--strict]\n");
    exit(2);
}

$baseParts = parse_url($baseUrl);
if ($baseUrl === ''
    || !is_array($baseParts)
    || !in_array(strtolower((string) ($baseParts['scheme'] ?? '')), ['http', 'https'], true)
    || !empty($baseParts['user'])
    || !empty($baseParts['pass'])
    || !empty($baseParts['query'])
    || !empty($baseParts['fragment'])) {
    fwrite(STDERR, "A credential-free HTTP(S) --base-url without query or fragment is required.\n");
    exit(2);
}

$protectedPaths = [
    'install.php',
    'hash.php',
    'test.php',
    'map_preview.php',
    'map_demo.html',
    'ict_mockups.html',
    'tripadvisor_widgets_preview.php',
    'README.md',
    'AGENTS.md',
    'DEPLOY.md',
    'SKILL.md',
    'task_plan.md',
    'progress.md',
    'sql-import.sql',
    'config/config.php',
    'includes/functions.php',
    'database/schema.sql',
    'references/seo-trust-master-plan.md',
];

$fetchStatus = static function (string $url): array {
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'ignore_errors' => true,
            'follow_location' => false,
            'timeout' => 10,
            'header' => "User-Agent: ICT-Release0ExposureAudit/1.0\r\n",
        ],
        'ssl' => [
            'verify_peer' => true,
            'verify_peer_name' => true,
        ],
    ]);
    $body = @file_get_contents($url, false, $context);
    $headers = $http_response_header ?? [];
    $status = 0;
    if (isset($headers[0]) && preg_match('/\s(\d{3})\s/', $headers[0], $match)) {
        $status = (int) $match[1];
    }

    return [
        'status' => $status,
        'bytes' => is_string($body) ? strlen($body) : 0,
    ];
};

$results = [];
$failures = 0;
foreach ($protectedPaths as $path) {
    $url = $baseUrl . '/' . $path;
    $response = $fetchStatus($url);
    $blocked = in_array($response['status'], [403, 404, 410], true);
    if (!$blocked) {
        $failures++;
    }
    $results[] = [
        'path' => $path,
        'url' => $url,
        'status' => $response['status'],
        'bytes' => $response['bytes'],
        'blocked' => $blocked,
    ];
}

$report = [
    'gate' => 'release0-development-exposure',
    'base_url' => $baseUrl,
    'protected_count' => count($protectedPaths),
    'failure_count' => $failures,
    'results' => $results,
];

if ($format === 'json') {
    echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
} else {
    foreach ($results as $result) {
        echo ($result['blocked'] ? '[PASS] ' : '[FAIL] ')
            . $result['path'] . ': HTTP ' . $result['status'] . PHP_EOL;
    }
    echo 'Exposure summary: ' . (string) (count($protectedPaths) - $failures)
        . '/' . (string) count($protectedPaths) . ' protected.' . PHP_EOL;
}

exit($strict && $failures > 0 ? 1 : 0);
