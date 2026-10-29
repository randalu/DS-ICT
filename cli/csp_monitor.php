<?php
/**
 * CSP violation log monitor.
 *
 * Usage:
 *   php cli/csp_monitor.php            # show last 24h summary
 *   php cli/csp_monitor.php --hours=48 # look back 48 hours
 *   php cli/csp_monitor.php --tail=20  # last 20 lines verbatim
 *   php cli/csp_monitor.php --json     # emit JSON for log pipelines
 *
 * Reads data/csp.log (written by csp-report.php) and reports:
 *   - Total violations in the window
 *   - Top blocked-uri patterns
 *   - Top violated directives
 *   - Top source files
 *
 * Designed to be run via cron / a hosted scheduler after deploy.
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "Run from CLI.\n");
    exit(1);
}

$logFile = dirname(__DIR__) . '/data/csp.log';

$args = array_slice($argv, 1);
$hours  = 24;
$tailN  = 0;
$json   = false;

foreach ($args as $arg) {
    if (preg_match('/^--hours=(\d+)$/', $arg, $m)) {
        $hours = (int) $m[1];
    } elseif (preg_match('/^--tail=(\d+)$/', $arg, $m)) {
        $tailN = (int) $m[1];
    } elseif ($arg === '--json') {
        $json = true;
    }
}

if (!is_file($logFile)) {
    if ($json) {
        echo json_encode(['error' => 'csp.log not found', 'path' => $logFile]) . "\n";
    } else {
        echo "No CSP log found at {$logFile}\n";
        echo "(The file is created on first violation. If you've just deployed, this is normal.)\n";
    }
    exit(0);
}

$lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
$cutoff = time() - ($hours * 3600);
$recent = [];

foreach ($lines as $line) {
    // Format: [YYYY-MM-DD HH:MM:SS] [ip] directive=X blocked=Y source=Z:N ref=R ua=U
    if (!preg_match('/^\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\] \[([^\]]+)\] directive=([^ ]+) blocked=([^ ]+) source=([^:]+):(\d+|-) ref=([^ ]+) ua=(.*)$/', $line, $m)) {
        continue;
    }
    $ts = strtotime($m[1]);
    if ($ts === false || $ts < $cutoff) {
        continue;
    }
    $recent[] = [
        'ts'        => $m[1],
        'ip'        => $m[2],
        'directive' => $m[3],
        'blocked'   => $m[4],
        'source'    => $m[5] . ':' . $m[6],
        'ref'       => $m[7],
        'ua'        => $m[8],
    ];
}

if ($json) {
    echo json_encode([
        'window_hours' => $hours,
        'total'        => count($recent),
        'violations'   => $recent,
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
    exit(0);
}

echo "CSP violations in last {$hours}h: " . count($recent) . "\n";
echo str_repeat('─', 60) . "\n";

if ($recent === []) {
    echo "No violations.  ✓\n";
    exit(0);
}

// Tail mode: print last N lines verbatim
if ($tailN > 0) {
    $tail = array_slice($recent, -$tailN);
    foreach ($tail as $r) {
        echo "[{$r['ts']}] {$r['directive']} blocked={$r['blocked']} src={$r['source']}\n";
    }
    exit(0);
}

// Aggregate mode
$byDirective = [];
$byBlocked   = [];
$bySource    = [];
foreach ($recent as $r) {
    $byDirective[$r['directive']] = ($byDirective[$r['directive']] ?? 0) + 1;
    $byBlocked[$r['blocked']]     = ($byBlocked[$r['blocked']]     ?? 0) + 1;
    $bySource[$r['source']]       = ($bySource[$r['source']]       ?? 0) + 1;
}

arsort($byDirective);
arsort($byBlocked);
arsort($bySource);

echo "\nTop violated directives:\n";
foreach (array_slice($byDirective, 0, 5, true) as $k => $v) {
    echo "  " . str_pad((string) $v, 5) . " {$k}\n";
}

echo "\nTop blocked URIs:\n";
foreach (array_slice($byBlocked, 0, 5, true) as $k => $v) {
    echo "  " . str_pad((string) $v, 5) . " {$k}\n";
}

echo "\nTop source files:\n";
foreach (array_slice($bySource, 0, 5, true) as $k => $v) {
    echo "  " . str_pad((string) $v, 5) . " {$k}\n";
}

echo "\n";
echo "Use --tail=N to dump the last N violations verbatim.\n";
echo "Use --json to pipe into a log aggregator.\n";
