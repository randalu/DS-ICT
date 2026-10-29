<?php
declare(strict_types=1);

/**
 * Read-only route and SEO audit for the Phase 44-47 commercial pages.
 */

require_once __DIR__ . '/../config/bootstrap.php';

$pages = [
    'private-driver-sri-lanka' => '/private-driver-sri-lanka',
    'private-driver-sri-lanka-cost' => '/private-driver-sri-lanka/cost',
    'sri-lanka-private-tours' => '/sri-lanka-private-tours',
    'sri-lanka-tour-cost' => '/sri-lanka-tour-cost',
    'sri-lanka-airport-transfers' => '/sri-lanka-airport-transfers',
    'colombo-airport-to-sigiriya' => '/colombo-airport-to-sigiriya',
    'sigiriya-to-kandy-private-transfer' => '/sigiriya-to-kandy-private-transfer',
    'kandy-to-ella-private-transfer' => '/kandy-to-ella-private-transfer',
    'ella-to-yala-private-transfer' => '/ella-to-yala-private-transfer',
    'ella-to-udawalawe-private-transfer' => '/ella-to-udawalawe-private-transfer',
    'yala-to-mirissa-private-transfer' => '/yala-to-mirissa-private-transfer',
    'galle-to-colombo-airport-private-transfer' => '/galle-to-colombo-airport-private-transfer',
];

$render = static function (string $slug, string $requestPath): string {
    $_GET = ['slug' => $slug];
    $_REQUEST = $_GET;
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_SERVER['REQUEST_URI'] = $requestPath;
    ob_start();
    try {
        require __DIR__ . '/../static_page.php';
        return (string) ob_get_clean();
    } catch (Throwable $exception) {
        ob_end_clean();
        return '<!-- route exception: ' . e($exception->getMessage()) . ' -->';
    }
};

$failures = 0;
$passed = 0;
foreach ($pages as $slug => $requestPath) {
    $issues = [];
    $page = find_published_static_page_by_slug($slug);
    $html = $render($slug, $requestPath);

    if (!$page) {
        $issues[] = 'missing published CMS record';
    }
    if (!preg_match('~<title[^>]*>\s*.+?\s*</title>~is', $html)) {
        $issues[] = 'missing title';
    }
    $expectedCanonical = static_page_public_url($slug, (string) ($page['canonical_url'] ?? ''));
    if (!preg_match('~<link[^>]+rel=["\']canonical["\'][^>]+href=["\']([^"\']+)["\']~i', $html, $canonicalMatch)
        || trim(html_entity_decode($canonicalMatch[1], ENT_QUOTES | ENT_HTML5, 'UTF-8')) !== $expectedCanonical) {
        $issues[] = 'canonical does not match public route';
    }
    if (preg_match_all('~<h1\b[^>]*>~i', $html) !== 1) {
        $issues[] = 'expected exactly one H1';
    }
    if (!preg_match('~<script[^>]+type=["\']application/ld\+json["\'][^>]*>.*?Service.*?</script>~is', $html)) {
        $issues[] = 'missing Service JSON-LD';
    }
    if (!preg_match('~<script[^>]+type=["\']application/ld\+json["\'][^>]*>.*?FAQPage.*?</script>~is', $html)) {
        $issues[] = 'missing FAQPage JSON-LD';
    }
    if (!preg_match('~(?:href|content)=["\'][^"\']*(?:/contact|wa\.me)[^"\']*["\']~i', $html)) {
        $issues[] = 'missing contact or WhatsApp CTA';
    }
    if (preg_match('~(?:href|src)=["\']/(?!/)[^"\']*["\']~i', $html)) {
        $issues[] = 'root-relative content link is not deployment-subdirectory safe';
    }

    $hero = trim((string) ($page['hero_image'] ?? ''));
    if ($hero === '' || is_placeholder_media_url($hero)) {
        $issues[] = 'missing or placeholder hero media';
    }

    if ($issues) {
        $failures++;
        echo '[FAIL] ' . $slug . ': ' . implode('; ', $issues) . PHP_EOL;
    } else {
        $passed++;
        echo '[PASS] ' . $slug . PHP_EOL;
    }
}

echo sprintf("Commercial pages summary: %d/%d passed; %d failed.\n", $passed, count($pages), $failures);
exit($failures > 0 ? 1 : 0);
