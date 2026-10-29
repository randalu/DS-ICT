<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/site_audit.php';

admin_require_auth();

header('Content-Type: application/json; charset=utf-8');

if (!is_post()) {
    http_response_code(405);
    echo json_encode(['error' => 'POST required']);
    exit;
}

guard_post_size();
require_csrf();

if (!site_audit_tables_available()) {
    http_response_code(503);
    echo json_encode(['error' => 'Site audit is not installed. Run the site-audit migration first.']);
    exit;
}

if (!audit_portal_ready()) {
    http_response_code(503); echo json_encode(['error' => 'Apply the reviewed site-audit portal and progress migrations before starting a new run.']); exit;
}
$preflight = audit_preflight();
if (!$preflight['ok']) { http_response_code(503); echo json_encode(['error' => $preflight['error']]); exit; }

$baseUrl = site_audit_base_url();
if ($baseUrl === '') {
    http_response_code(500);
    echo json_encode(['error' => 'The configured application URL is not a valid HTTP(S) audit target.']);
    exit;
}

site_audit_recover_stale_runs();

// GET_LOCK closes the small race where two admin tabs submit at the same
// time. If a legacy/mock driver does not expose MySQL advisory locks, the
// active-run query below remains the compatibility fallback.
$lockAcquired = false;
try {
    $lockResult = fetch_one("SELECT GET_LOCK('ict_site_audit_run', 2) AS acquired");
    if (is_array($lockResult) && array_key_exists('acquired', $lockResult)) {
        if ((int) $lockResult['acquired'] !== 1) {
            http_response_code(409);
            echo json_encode(['error' => 'An audit is already being started.']);
            exit;
        }
        $lockAcquired = true;
    }

    $activeRun = fetch_one("SELECT id FROM site_audit_runs WHERE status = 'running' ORDER BY id DESC LIMIT 1");
    if ($activeRun) {
        http_response_code(409);
        echo json_encode(['error' => 'An audit is already running.']);
        exit;
    }

    $admin = admin_user() ?? [];
    $triggeredBy = trim((string) ($admin['username'] ?? $admin['email'] ?? 'admin'));
    $triggeredBy = substr($triggeredBy !== '' ? $triggeredBy : 'admin', 0, 100);

    $stmt = db()->prepare(
        'INSERT INTO site_audit_runs (started_at, status, triggered_by)
         VALUES (NOW(), \'running\', :triggered_by)'
    );
    $stmt->execute(['triggered_by' => $triggeredBy]);
    $runId = (int) db()->lastInsertId();
    try {
        $inventory = audit_inventory();
        execute_query('INSERT INTO site_audit_snapshots(run_id,profile,base_url,inventory_json) VALUES(:run,:profile,:base,:inventory)',
            ['run' => $runId, 'profile' => SITE_AUDIT_PROFILE . '-' . substr($inventory['schema_signature'],0,16), 'base' => $baseUrl, 'inventory' => audit_json($inventory)]);
        $manifest = site_audit_log_path($runId) . '.inventory.json';
        $handle = @fopen($manifest,'xb');
        if (!is_resource($handle)) { throw new RuntimeException('Inventory could not be prepared.'); }
        try { $json=audit_json($inventory); if (fwrite($handle,$json)!==strlen($json)) { throw new RuntimeException('Inventory could not be written.'); } }
        finally { fclose($handle); }
        @chmod($manifest, 0600);
    } catch (Throwable $e) {
        site_audit_mark_failed($runId, $baseUrl, 'Public inventory preparation failed.'); throw $e;
    }
} catch (Throwable) {
    http_response_code(500);
    echo json_encode(['error' => 'The audit run could not be created.']);
    exit;
} finally {
    if ($lockAcquired) {
        try {
            // DO produces no result set; exec(SELECT ...) leaves this PDO connection unread.
            db()->exec("DO RELEASE_LOCK('ict_site_audit_run')");
        } catch (Throwable) {
        }
    }
}

$logFile = site_audit_log_path($runId);
$worker = site_audit_start_worker($runId, $baseUrl, $logFile);
if (!$worker['ok']) {
    site_audit_mark_failed($runId, $baseUrl, (string) ($worker['error'] ?? 'The audit worker could not be started.'));
    http_response_code(500);
    echo json_encode([
        'run_id' => $runId,
        'started' => false,
        'error' => (string) ($worker['error'] ?? 'The audit worker could not be started.'),
    ]);
    exit;
}

echo json_encode([
    'run_id' => $runId,
    'started' => true,
]);
