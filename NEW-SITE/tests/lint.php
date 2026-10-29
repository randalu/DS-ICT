<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$files = [];
$scan = function (string $directory) use (&$scan, &$files): void {
    foreach (new DirectoryIterator($directory) as $entry) {
        if ($entry->isDot() || $entry->isLink() || in_array($entry->getFilename(), ['node_modules', 'output'], true)) {
            continue;
        }
        if ($entry->isDir()) {
            $scan($entry->getPathname());
        } elseif ($entry->getExtension() === 'php') {
            $files[] = $entry->getPathname();
        }
    }
};
$scan($root);
foreach ($files as $file) {
    $pipes = [];
    $process = proc_open([PHP_BINARY, '-l', $file], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, $root);
    if (!is_resource($process)) {
        throw new RuntimeException('Unable to start isolated syntax check.');
    }
    $stdout = stream_get_contents($pipes[1]);
    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    if (proc_close($process) !== 0) {
        fwrite(STDERR, $stdout . $stderr);
        exit(1);
    }
}
echo 'PASS: ' . count($files) . " NEW-SITE PHP syntax checks.\n";
