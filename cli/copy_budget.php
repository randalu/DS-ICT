<?php
declare(strict_types=1);

/**
 * Copy budget check for marketing pages.
 *
 * The v3.1 brief asks for less text, so the home page has a hard ceiling. This
 * counts *visible* words: head, scripts, styles and HTML entities are removed
 * before counting, and punctuation-only tokens are ignored.
 *
 * Usage:  php cli\copy_budget.php [--json]
 * Exit:   0 when every budget passes, 1 when a page exceeds its ceiling.
 */

$url = getenv('ICT_QA_URL') ?: 'http://localhost/ict/';

$budgets = [
    ['label' => 'Home', 'url' => $url, 'limit' => 450],
];

$fetch = static function (string $target): string {
    $context = stream_context_create([
        'http' => ['timeout' => 20, 'ignore_errors' => true, 'header' => "User-Agent: ICT-copy-budget\r\n"],
    ]);
    $html = @file_get_contents($target, false, $context);

    return is_string($html) ? $html : '';
};

$visibleText = static function (string $html): string {
    // Drop everything that is not body copy.
    $html = preg_replace('~<head\b.*?</head>~is', ' ', $html) ?? $html;
    $html = preg_replace('~<script\b.*?</script>~is', ' ', $html) ?? $html;
    $html = preg_replace('~<style\b.*?</style>~is', ' ', $html) ?? $html;
    $html = preg_replace('~<svg\b.*?</svg>~is', ' ', $html) ?? $html;
    $text = strip_tags($html);
    $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $text = preg_replace('~[0-9]+\s*(?=\s)~', ' ', $text) ?? $text;

    return trim(preg_replace('~\s+~u', ' ', $text) ?? $text);
};

$countWords = static function (string $text): int {
    $tokens = preg_split('~\s+~u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];
    $words = array_filter($tokens, static fn(string $token): bool => preg_match('~[\p{L}\p{N}]~u', $token) === 1);

    return count($words);
};

$rows = [];
$failed = false;

foreach ($budgets as $budget) {
    $html = $fetch($budget['url']);
    $words = $html === '' ? 0 : $countWords($visibleText($html));
    $ok = $html !== '' && $words <= $budget['limit'];
    $failed = $failed || !$ok;
    $rows[] = [
        'label' => $budget['label'],
        'words' => $words,
        'limit' => $budget['limit'],
        'ok' => $ok,
        'fetched' => $html !== '',
    ];
}

if (in_array('--json', $argv ?? [], true)) {
    echo json_encode(['pass' => !$failed, 'pages' => $rows], JSON_PRETTY_PRINT) . PHP_EOL;
    exit($failed ? 1 : 0);
}

echo "Copy budget\n";
echo str_repeat('-', 48) . PHP_EOL;
foreach ($rows as $row) {
    printf("%-24s %4d / %4d words  %s\n", $row['label'], $row['words'], $row['limit'], $row['ok'] ? 'PASS' : 'FAIL');
    if (!$row['fetched']) {
        echo "  (could not fetch " . $url . " — is the local site running?)\n";
    }
}
echo str_repeat('-', 48) . PHP_EOL;
echo $failed ? "Copy budget exceeded.\n" : "All copy budgets pass.\n";

exit($failed ? 1 : 0);
