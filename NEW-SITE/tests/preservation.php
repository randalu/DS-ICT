<?php
declare(strict_types=1);

$root = dirname(__DIR__, 2);
$output = dirname(__DIR__) . '/output';
$baseline = $output . '/original-source-baseline.json';
$excluded = ['NEW-SITE', '.git', '.kilo', '.claude', '.hermes', '.agents', '.opencode', '.playwright-cli', 'node_modules', 'vendor', 'uploads', 'logs', 'storage', 'cache', 'data', 'references', 'output'];
$extensions = ['php', 'css', 'js', 'json', 'html', 'xml', 'lock'];
$hashes = [];
$scan = function (string $directory) use (&$scan, &$hashes, $root, $excluded, $extensions): void {
    foreach (new DirectoryIterator($directory) as $entry) {
        if ($entry->isDot() || $entry->isLink()) {
            continue;
        }
        $name = $entry->getFilename();
        if ($entry->isDir()) {
            if (!in_array($name, $excluded, true) && !str_starts_with($name, '.')) {
                $scan($entry->getPathname());
            }
            continue;
        }
        if ($name !== '.htaccess' && $name !== '.env.example' && (str_starts_with($name, '.') || !in_array(strtolower($entry->getExtension()), $extensions, true))) {
            continue;
        }
        $path = str_replace('\\', '/', substr($entry->getPathname(), strlen($root) + 1));
        $hashes[$path] = hash_file('sha256', $entry->getPathname());
    }
};
$scan($root);
ksort($hashes);
if (($argv[1] ?? '') === 'capture') {
    if (is_file($baseline)) {
        fwrite(STDERR, "Baseline already exists; refusing to replace it.\n");
        exit(1);
    }
    if (!is_dir($output)) {
        mkdir($output, 0770, true);
    }
    file_put_contents($baseline, json_encode(['scope' => 'Existing application source only; environment, uploads, private data, dependencies and references excluded.', 'hashes' => $hashes], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
    echo 'Captured ' . count($hashes) . " original source fingerprints.\n";
    exit;
}
if (!is_file($baseline)) {
    fwrite(STDERR, "Run capture before implementation.\n");
    exit(1);
}
$saved = json_decode(file_get_contents($baseline), true, flags: JSON_THROW_ON_ERROR)['hashes'];
// Older captured baselines can include generated artifact directories; never
// treat those as original source or revert concurrent work inside them.
$saved = array_filter($saved, static fn (string $path): bool => array_intersect(explode('/', $path), $excluded) === [], ARRAY_FILTER_USE_KEY);
$changes = [];
foreach ($saved as $path => $hash) {
    if (($hashes[$path] ?? null) !== $hash) {
        $changes[] = $path;
    }
}
foreach (array_diff_key($hashes, $saved) as $path => $hash) {
    $changes[] = $path;
}
if ($changes !== []) {
    $visible = array_slice($changes, 0, 15);
    echo 'Original source changed (' . count($changes) . " paths); inspect concurrent edits, do not revert them:\n" . implode("\n", $visible) . (count($changes) > count($visible) ? "\n..." : '') . "\n";
    exit(1);
}
echo count($saved) . " original source files unchanged. No environment or uploaded files were inspected.\n";
