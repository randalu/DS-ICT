<?php
declare(strict_types=1);

/**
 * Read-only release audit for homepage Phases 37-43.
 */

require_once __DIR__ . '/../config/bootstrap.php';

$render = static function (): string {
    $_GET = [];
    $_REQUEST = [];
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_SERVER['REQUEST_URI'] = '/';
    $_SERVER['SCRIPT_NAME'] = '/index.php';
    ob_start();
    try {
        require __DIR__ . '/../index.php';
        return (string) ob_get_clean();
    } catch (Throwable $exception) {
        ob_end_clean();
        return '<!-- route exception: ' . e($exception->getMessage()) . ' -->';
    }
};

$html = $render();
$issues = [];
$homepageSourcePath = __DIR__ . '/../index.php';
$homepageSource = is_file($homepageSourcePath) ? (string) file_get_contents($homepageSourcePath) : '';
if ($homepageSource === '') {
    $issues[] = 'homepage source could not be read for fallback-review audit';
}
$expectedTitle = 'Sri Lanka Private Tours & Private Driver | Island Culture Travels';
$expectedH1 = 'Private Sri Lanka Tours with Your Own Driver-Guide';

$titleText = '';
if (preg_match('~<title[^>]*>(.*?)</title>~is', $html, $titleMatch)) {
    $titleText = trim(html_entity_decode(strip_tags($titleMatch[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
}
if ($titleText !== $expectedTitle) {
    $issues[] = 'homepage SEO title is not the Phase 37 title';
}
if (!preg_match('~<link[^>]+rel=["\']canonical["\'][^>]+href=["\']([^"\']+)["\']~i', $html, $canonicalMatch)
    || trim(html_entity_decode($canonicalMatch[1], ENT_QUOTES | ENT_HTML5, 'UTF-8')) !== base_url()) {
    $issues[] = 'homepage canonical does not match base URL';
}
if (preg_match_all('~<h1\b[^>]*>~i', $html) !== 1) {
    $issues[] = 'homepage does not have exactly one H1';
}
if (!preg_match('~' . preg_quote($expectedH1, '~') . '~i', $html)) {
    $issues[] = 'homepage H1 is not the Phase 38 commercial H1';
}
foreach (['chauffeur-guided', 'couples', 'families', 'wildlife', 'beaches', 'culture'] as $term) {
    if (stripos($html, $term) === false) {
        $issues[] = 'Phase 39 opening copy is missing ' . $term;
    }
}
foreach (['Private driver &amp; vehicle', 'Why Travel With Island Culture Travels?', 'Find a Tour Category'] as $required) {
    if (stripos($html, $required) === false) {
        $issues[] = 'homepage section missing: ' . html_entity_decode($required, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}
foreach (['Tobia T', 'Isabel P', 'Priya M.'] as $forbiddenReviewName) {
    if ($homepageSource !== '' && stripos($homepageSource, $forbiddenReviewName) !== false) {
        $issues[] = 'fallback review literal remains in index.php: ' . $forbiddenReviewName;
    }
}

if ($issues) {
    echo '[FAIL] Homepage: ' . implode('; ', $issues) . PHP_EOL;
    exit(1);
}

echo '[PASS] Homepage Phases 37-43' . PHP_EOL;
echo "Homepage summary: 1/1 passed.\n";
