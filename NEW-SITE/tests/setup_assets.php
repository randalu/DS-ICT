<?php
declare(strict_types=1);

// Explicit one-time build assets only; the preview never fetches fonts at runtime.
$directory = dirname(__DIR__) . '/public/assets/fonts';
if (!is_dir($directory)) {
    mkdir($directory, 0770, true);
}
$sources = [
    'fraunces-latin.woff2' => 'https://fonts.gstatic.com/s/fraunces/v38/6NUu8FyLNQOQZAnv9bYEvDiIdE9Ea92uemAk_WBq8U_9v0c2Wa0K7iN7hzFUPJH58nib14c7qv8.woff2',
    'manrope-latin.woff2' => 'https://fonts.gstatic.com/s/manrope/v20/xn7gYHE41ni1AdIRggexSg.woff2',
    'Fraunces-OFL.txt' => 'https://raw.githubusercontent.com/google/fonts/main/ofl/fraunces/OFL.txt',
    'Manrope-OFL.txt' => 'https://raw.githubusercontent.com/google/fonts/main/ofl/manrope/OFL.txt',
];
$manifest = [];
foreach ($sources as $filename => $url) {
    $target = $directory . '/' . $filename;
    if (!is_file($target)) {
        $context = stream_context_create(['http' => ['timeout' => 25, 'follow_location' => 0], 'ssl' => ['verify_peer' => true, 'verify_peer_name' => true]]);
        $data = file_get_contents($url, false, $context);
        if ($data === false || (str_ends_with($filename, '.woff2') ? substr($data, 0, 4) !== 'wOF2' : !str_contains($data, 'SIL OPEN FONT LICENSE'))) {
            throw new RuntimeException('Invalid or unavailable font asset: ' . $filename);
        }
        file_put_contents($target, $data);
    }
    $manifest[$filename] = ['source' => $url, 'bytes' => filesize($target), 'sha256' => hash_file('sha256', $target)];
    echo $filename . ': ' . filesize($target) . " bytes\n";
}
file_put_contents($directory . '/sources.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
