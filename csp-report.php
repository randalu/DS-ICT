<?php
/**
 * CSP violation report endpoint.
 *
 * Receives POSTed JSON reports from browsers and logs them to data/csp.log.
 * This lets us catch violations before they affect users, and is the
 * foundation for flipping to report-only again if enforcement is too strict.
 *
 * Add to the CSP header (already done in .htaccess):
 *   report-uri /csp-report.php
 *
 * Apache >2.19 + mod_reporting (or nginx) can use report-to, but for simplicity
 * we rely on the legacy report-uri fallback which all browsers support.
 */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('405 Method Not Allowed');
}

$raw = file_get_contents('php://input');
$report = json_decode($raw, true);

if (!is_array($report)) {
    http_response_code(400);
    exit('400 Bad Request');
}

// Log to data/csp.log — date, violated-directive, blocked-uri, source-file, line
$blockedUri  = $report['csp-report']['blocked-uri']  ?? 'unknown';
$directive   = $report['csp-report']['violated-directive'] ?? 'unknown';
$sourceFile  = $report['csp-report']['source-file'] ?? '-';
$line        = $report['csp-report']['line-number'] ?? '-';
$referrer    = $_SERVER['HTTP_REFERER'] ?? '-';
$userAgent   = substr($_SERVER['HTTP_USER_AGENT'] ?? 'unknown', 0, 120);
$timestamp   = date('Y-m-d H:i:s');

$entry = "[{$timestamp}] [{$_SERVER['REMOTE_ADDR']}] "
       . "directive={$directive} blocked={$blockedUri} "
       . "source={$sourceFile}:{$line} ref={$referrer} ua={$userAgent}\n";

$logDir = dirname(__DIR__) . '/data';
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}

$logFile = $logDir . '/csp.log';

// Keep the log manageable: rotate at 5 MB
if (is_file($logFile) && filesize($logFile) > 5 * 1024 * 1024) {
    copy($logFile, $logDir . '/csp.log.' . date('Y-m-d-His') . '.bak');
}

file_put_contents($logFile, $entry, FILE_APPEND | LOCK_EX);

// 204 No Content — tell the browser not to retry
http_response_code(204);
