<?php
declare(strict_types=1);

const SITE_AUDIT_STALE_AFTER_MINUTES = 30;
const SITE_AUDIT_MAX_DETAIL_LENGTH = 2000;

/**
 * Return whether the audit tables are available on this installation.
 */
function site_audit_tables_available(): bool
{
    return db_table_exists('site_audit_runs') && db_table_exists('site_audit_issues');
}

/**
 * Validate and normalize an audit target URL.
 *
 * The admin launcher uses APP_URL. The optional argument exists for the CLI
 * and for tests, but query strings, fragments, credentials, and non-HTTP
 * schemes are intentionally rejected.
 */
function site_audit_base_url(?string $candidate = null): string
{
    $raw = trim((string) ($candidate ?? (defined('APP_URL') ? APP_URL : '')));
    if ($raw === '' || preg_match('~\Ahttps?://~i', $raw) !== 1) {
        return '';
    }

    $parts = parse_url($raw);
    if (!is_array($parts) || empty($parts['host'])
        || isset($parts['user']) || isset($parts['pass'])
        || isset($parts['query']) || isset($parts['fragment'])) {
        return '';
    }

    $host = (string) $parts['host'];
    if (filter_var($host, FILTER_VALIDATE_IP) === false && preg_match('/\A[a-z0-9.-]+\z/i', $host) !== 1) {
        return '';
    }

    return rtrim($raw, '/');
}

/**
 * Accept only absolute HTTP(S) URLs in persisted worker issue records.
 */
function site_audit_issue_url(mixed $candidate): ?string
{
    $url = trim((string) $candidate);
    if ($url === '' || preg_match('/[\x00-\x20]/', $url) === 1) {
        return null;
    }

    $parts = parse_url($url);
    if (!is_array($parts)
        || !in_array(strtolower((string) ($parts['scheme'] ?? '')), ['http', 'https'], true)
        || trim((string) ($parts['host'] ?? '')) === ''
        || isset($parts['user'])
        || isset($parts['pass'])) {
        return null;
    }

    return substr($url, 0, 2048);
}

/**
 * Build the private worker log path for a run.
 */
function site_audit_log_path(int $runId): string
{
    if ($runId <= 0) {
        throw new InvalidArgumentException('A positive audit run ID is required.');
    }

    // Separate applications and disposable schemas; never overwrite another
    // installation's worker artifacts when both have run #1.
    return site_audit_private_directory() . DIRECTORY_SEPARATOR . 'run_' . $runId . '.jsonl';
}

function site_audit_private_directory(): string
{
    static $verified = null;
    if (is_string($verified)) { return $verified; }
    $identity = function_exists('posix_geteuid') ? (string) posix_geteuid() : '';
    if (PHP_OS_FAMILY==='Windows') {
        $identityPipes=[];
        $identityProcess=@proc_open(['powershell.exe','-NoProfile','-NonInteractive','-Command','[Security.Principal.WindowsIdentity]::GetCurrent().User.Value'],[1=>['pipe','w'],2=>['pipe','w']],$identityPipes,null,null,['bypass_shell'=>true]);
        if (!is_resource($identityProcess)) { throw new RuntimeException('Cannot identify the audit service account.'); }
        $identity=trim((string)stream_get_contents($identityPipes[1]));stream_get_contents($identityPipes[2]);fclose($identityPipes[1]);fclose($identityPipes[2]);
        if(proc_close($identityProcess)!==0 || !preg_match('/^S-[0-9-]+$/D',$identity)){throw new RuntimeException('Cannot identify the audit service account.');}
    }
    if($identity===''){throw new RuntimeException('Cannot identify the audit service account.');}
    $scope = substr(hash('sha256', BASE_PATH . ':' . DB_NAME . ':' . $identity), 0, 20);
    $directory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'ict-site-audit-' . $scope;
    if (is_link($directory)) { throw new RuntimeException('Private audit storage cannot be a symbolic link.'); }
    $new = !file_exists($directory);
    if ($new && !@mkdir($directory,0700) && !is_dir($directory)) { throw new RuntimeException('Private audit storage cannot be prepared.'); }
    $resolved=realpath($directory);$public=realpath(BASE_PATH);
    if (!$resolved || !$public || str_starts_with(strtolower(str_replace('\\','/',$resolved)).'/',strtolower(str_replace('\\','/',$public)).'/') || !is_writable($resolved)) { throw new RuntimeException('Audit storage must be writable and outside the public application.'); }
    $documentRoot=realpath($_SERVER['DOCUMENT_ROOT'] ?? BASE_PATH);
    if ($documentRoot && str_starts_with(strtolower(str_replace('\\','/',$resolved)).'/',strtolower(str_replace('\\','/',$documentRoot)).'/')) { throw new RuntimeException('Audit storage must stay outside the web document root.'); }
    if (PHP_OS_FAMILY==='Windows' && $new) {
        $encoded=base64_encode($resolved);
        $script='$ErrorActionPreference="Stop"; $path=[Text.Encoding]::UTF8.GetString([Convert]::FromBase64String("'.$encoded.'")); $acl=[IO.Directory]::GetAccessControl($path); $user=[Security.Principal.WindowsIdentity]::GetCurrent().User; $acl.SetAccessRuleProtection($true,$false); foreach($sid in @($user.Value,"S-1-5-18","S-1-5-32-544")){ $rule=New-Object Security.AccessControl.FileSystemAccessRule([Security.Principal.SecurityIdentifier]::new($sid),"FullControl","ContainerInherit,ObjectInherit","None","Allow"); $acl.AddAccessRule($rule) }; [IO.Directory]::SetAccessControl($path,$acl)';
        $pipes=[];$process=@proc_open(['powershell.exe','-NoProfile','-NonInteractive','-EncodedCommand',base64_encode(mb_convert_encoding($script,'UTF-16LE','UTF-8'))],[1=>['pipe','w'],2=>['pipe','w']],$pipes,null,null,['bypass_shell'=>true]);
        if (!is_resource($process)) { throw new RuntimeException('Cannot restrict private audit storage.'); }
        stream_get_contents($pipes[1]);stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);
        if(proc_close($process)!==0){throw new RuntimeException('Cannot restrict private audit storage.');}
    }
    require_once __DIR__ . '/records_workbook.php';
    if (!ict_records_spool_private($resolved)) { throw new RuntimeException('Audit storage permissions are not private to the service identity.'); }
    return $verified=$resolved;
}

function site_audit_offset_path(int $runId): string
{
    return site_audit_log_path($runId) . '.offset';
}

/**
 * Mark abandoned workers as failed so they cannot block a new run forever.
 */
function site_audit_recover_stale_runs(): int
{
    if (!db_table_exists('site_audit_runs')) {
        return 0;
    }

    $minutes = SITE_AUDIT_STALE_AFTER_MINUTES;
    $cutoff = time() - ($minutes * 60);
    $runningRuns = fetch_all('SELECT id, started_at FROM site_audit_runs WHERE status = \'running\'');
    $recovered = 0;

    foreach ($runningRuns as $run) {
        $runId = (int) ($run['id'] ?? 0);
        if ($runId <= 0 || site_audit_run_last_activity_at($run, $runId) >= $cutoff) {
            continue;
        }

        $stmt = db()->prepare(
            "UPDATE site_audit_runs
             SET status = 'failed', finished_at = NOW()
             WHERE id = :id AND status = 'running'"
        );
        $stmt->execute(['id' => $runId]);
        $recovered += $stmt->rowCount();
    }

    return $recovered;
}

/**
 * Use worker-log activity as the freshness signal when a crawl runs longer
 * than the stale threshold. The start time remains the fallback before the
 * worker creates its log.
 */
function site_audit_run_last_activity_at(array $run, int $runId): int
{
    $startedAt = strtotime((string) ($run['started_at'] ?? '')) ?: 0;
    $lastActivity = $startedAt;
    $logPath = site_audit_log_path($runId);
    if (is_file($logPath)) {
        $logModifiedAt = @filemtime($logPath);
        if (is_int($logModifiedAt)) {
            $lastActivity = max($lastActivity, $logModifiedAt);
        }
    }

    return $lastActivity > 0 ? $lastActivity : time();
}

/**
 * Convert a worker issue into a bounded, database-safe payload.
 */
function site_audit_sanitize_issue(mixed $issue): ?array
{
    if (!is_array($issue)) {
        return null;
    }

    $severity = strtolower(trim((string) ($issue['severity'] ?? 'low')));
    if (!in_array($severity, ['critical', 'high', 'medium', 'low'], true)) {
        $severity = 'low';
    }

    $type = strtolower(trim((string) ($issue['type'] ?? 'unknown')));
    $type = preg_replace('/[^a-z0-9_]+/i', '_', $type) ?: 'unknown';
    $type = substr($type, 0, 100);

    $pageUrl = site_audit_issue_url($issue['page_url'] ?? null);
    if ($pageUrl === null) {
        return null;
    }

    $detail = $issue['detail'] ?? null;
    $detail = $detail === null ? null : mb_strcut(trim((string) $detail), 0, SITE_AUDIT_MAX_DETAIL_LENGTH, 'UTF-8');

    return [
        'severity' => $severity,
        'type' => $type,
        'page_url' => $pageUrl,
        'detail' => $detail === '' ? null : $detail,
    ];
}

/**
 * Read only complete JSONL records. A worker may be writing the final line
 * while a browser poll arrives; an unterminated line must remain unread until
 * the next poll instead of being lost by an advanced file offset.
 */
function site_audit_read_complete_log(string $path, int $offset): array
{
    if (!is_file($path)) {
        return ['events' => [], 'next_offset' => max(0, $offset)];
    }

    $handle = @fopen($path, 'rb');
    if (!is_resource($handle)) {
        return ['events' => [], 'next_offset' => max(0, $offset)];
    }

    try {
        $size = (int) (@fstat($handle)['size'] ?? 0);
        if ($offset < 0 || $offset > $size) {
            $offset = 0;
        }

        if (@fseek($handle, $offset) !== 0) {
            return ['events' => [], 'next_offset' => $offset];
        }

        $contents = stream_get_contents($handle);
        if (!is_string($contents) || $contents === '') {
            return ['events' => [], 'next_offset' => $offset];
        }

        $lastNewline = strrpos($contents, "\n");
        if ($lastNewline === false) {
            return ['events' => [], 'next_offset' => $offset];
        }

        $complete = substr($contents, 0, $lastNewline + 1);
        $events = [];
        foreach (preg_split('/\r?\n/', $complete, -1, PREG_SPLIT_NO_EMPTY) ?: [] as $line) {
            $event = json_decode((string) $line, true);
            if (is_array($event)) {
                $events[] = $event;
            }
        }

        return [
            'events' => $events,
            'next_offset' => $offset + strlen($complete),
        ];
    } finally {
        fclose($handle);
    }
}

function site_audit_run_offset(array $run, int $runId): int
{
    if (array_key_exists('log_offset', $run)) {
        return max(0, (int) $run['log_offset']);
    }

    $offsetPath = site_audit_offset_path($runId);
    if (!is_file($offsetPath)) {
        return 0;
    }

    return max(0, (int) @file_get_contents($offsetPath));
}

function site_audit_run_total_pages(array $run): int
{
    return max(0, (int) ($run['total_pages'] ?? 0));
}

function site_audit_summary(array $run, int $newIssues = 0, ?string $error = null): array
{
    $status = (string) ($run['status'] ?? 'failed');

    return [
        'run_id' => (int) ($run['id'] ?? 0),
        'status' => $status,
        'progress' => max(0, (int) ($run['pages_crawled'] ?? 0)),
        'total_pages' => site_audit_run_total_pages($run),
        'issue_count' => max(0, (int) ($run['issue_count'] ?? 0)),
        'new_issues' => max(0, $newIssues),
        'new_issue_rows' => [],
        'done' => $status !== 'running',
        'error' => $error,
    ];
}

/**
 * Import complete worker events and return the current progress snapshot.
 */
function site_audit_import_log(int $runId): array
{
    if (!site_audit_tables_available()) {
        throw new RuntimeException('Site audit tables are not installed.');
    }

    site_audit_recover_stale_runs();

    $run = fetch_one('SELECT * FROM site_audit_runs WHERE id = :id LIMIT 1', ['id' => $runId]);
    if (!$run) {
        throw new OutOfBoundsException('Audit run not found.');
    }

    if ((string) ($run['status'] ?? '') !== 'running') {
        return site_audit_summary($run);
    }

    $lockHandle = null;
    $lockPath = site_audit_log_path($runId) . '.lock';
    try {
        $lockHandle = @fopen($lockPath, 'c+');
        if (is_resource($lockHandle)) {
            @flock($lockHandle, LOCK_EX);
        }

        // Refresh the row after taking the per-run lock so two admin tabs do
        // not process the same file range concurrently.
        $run = fetch_one('SELECT * FROM site_audit_runs WHERE id = :id LIMIT 1', ['id' => $runId]);
        if (!$run) {
            throw new OutOfBoundsException('Audit run not found.');
        }
        if ((string) ($run['status'] ?? '') !== 'running') {
            return site_audit_summary($run);
        }

        $hasTotalPages = db_column_exists('site_audit_runs', 'total_pages');
        $hasLogOffset = db_column_exists('site_audit_runs', 'log_offset');
        $offset = site_audit_run_offset($run, $runId);
        $log = site_audit_read_complete_log(site_audit_log_path($runId), $offset);
        $events = $log['events'];

        if (!$events) {
            return site_audit_summary($run);
        }

        $progress = max(0, (int) ($run['pages_crawled'] ?? 0));
        $totalPages = site_audit_run_total_pages($run);
        $done = false;
        $fatal = null;
        $issues = [];

        foreach ($events as $event) {
            if (isset($event['total_pages'])) {
                $totalPages = max(0, (int) $event['total_pages']);
            }
            if (isset($event['progress'])) {
                $progress = max(0, (int) $event['progress']);
            }
            if (!empty($event['done'])) {
                $done = true;
            }
            if (isset($event['fatal'])) {
                $fatal = substr(trim((string) $event['fatal']), 0, SITE_AUDIT_MAX_DETAIL_LENGTH);
            }
            if (isset($event['error']) && $fatal === null) {
                $fatal = substr(trim((string) ($event['detail'] ?? $event['error'])), 0, SITE_AUDIT_MAX_DETAIL_LENGTH);
            }
            if (isset($event['issue'])) {
                $issue = site_audit_sanitize_issue($event['issue']);
                if ($issue !== null) {
                    $issue['_audit_raw'] = $event['issue'];
                    $issues[] = $issue;
                }
            }
        }

        $status = $fatal !== null ? 'failed' : ($done ? 'done' : 'running');
        $pdo = db();
        $startedTransaction = false;

        try {
            $pdo->beginTransaction();
            $startedTransaction = true;

            if ($issues) {
                $insert = $pdo->prepare(
                    'INSERT INTO site_audit_issues (run_id, severity, type, page_url, detail)
                     VALUES (:run_id, :severity, :type, :page_url, :detail)'
                );
                foreach ($issues as $issue) {
                    $insert->execute([
                        'run_id' => $runId,
                        'severity' => $issue['severity'],
                        'type' => $issue['type'],
                        'page_url' => $issue['page_url'],
                        'detail' => $issue['detail'],
                    ]);
                    $insertedIssueId = (int) $pdo->lastInsertId();
                    if (audit_portal_ready()) {
                        $evidence = audit_evidence($issue['_audit_raw']);
                        execute_query('INSERT INTO site_audit_evidence(issue_id,run_id,fingerprint,certainty,target_url,source_location,evidence_json) VALUES(:issue,:run,:fingerprint,:certainty,:target_url,:source_location,:evidence_json)',
                            ['issue' => $insertedIssueId, 'run' => $runId] + $evidence);
                    }
                }
            }

            audit_import_events($runId, $events);

            $countRow = fetch_one(
                'SELECT COUNT(*) AS c FROM site_audit_issues WHERE run_id = :id',
                ['id' => $runId]
            );
            $issueCount = max(0, (int) ($countRow['c'] ?? 0));
            $set = 'pages_crawled = :pages, issue_count = :issues, status = :status';
            $params = [
                'pages' => $progress,
                'issues' => $issueCount,
                'status' => $status,
                'id' => $runId,
            ];
            if ($hasTotalPages) {
                $set .= ', total_pages = :total_pages';
                $params['total_pages'] = $totalPages;
            }
            if ($hasLogOffset) {
                $set .= ', log_offset = :log_offset';
                $params['log_offset'] = max(0, (int) $log['next_offset']);
            }
            if ($status !== 'running') {
                $set .= ', finished_at = NOW()';
            }

            $pdo->prepare("UPDATE site_audit_runs SET {$set} WHERE id = :id")->execute($params);
            $pdo->commit();
            $startedTransaction = false;
        } catch (Throwable $exception) {
            if ($startedTransaction && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $exception;
        }

        if (!$hasLogOffset) {
            @file_put_contents(site_audit_offset_path($runId), (string) $log['next_offset'], LOCK_EX);
        }

        return [
            'run_id' => $runId,
            'status' => $status,
            'progress' => $progress,
            'total_pages' => $totalPages,
            'issue_count' => $issueCount,
            'new_issues' => count($issues),
            'new_issue_rows' => $issues,
            'done' => $status !== 'running',
            'error' => $fatal,
        ];
    } finally {
        if (is_resource($lockHandle)) {
            @flock($lockHandle, LOCK_UN);
            fclose($lockHandle);
        }
    }
}

function site_audit_poll_result(int $runId): array
{
    if ($runId <= 0) {
        throw new InvalidArgumentException('run_id required');
    }
    if (!site_audit_tables_available()) {
        throw new RuntimeException('Site audit tables are not installed.');
    }

    site_audit_recover_stale_runs();
    $run = fetch_one('SELECT * FROM site_audit_runs WHERE id = :id LIMIT 1', ['id' => $runId]);
    if (!$run) {
        throw new OutOfBoundsException('Audit run not found.');
    }

    return (string) ($run['status'] ?? '') === 'running'
        ? site_audit_import_log($runId)
        : site_audit_summary($run);
}

function site_audit_mark_failed(int $runId, string $baseUrl, string $detail): void
{
    if (!site_audit_tables_available()) {
        return;
    }

    $detail = substr(trim($detail), 0, SITE_AUDIT_MAX_DETAIL_LENGTH);
    execute_query(
        "UPDATE site_audit_runs SET status = 'failed', finished_at = NOW() WHERE id = :id AND status = 'running'",
        ['id' => $runId]
    );
    execute_query(
        "INSERT INTO site_audit_issues (run_id, severity, type, page_url, detail)
         VALUES (:run_id, 'critical', 'worker_start_failed', :page_url, :detail)",
        [
            'run_id' => $runId,
            'page_url' => substr($baseUrl !== '' ? $baseUrl : 'about:blank', 0, 2048),
            'detail' => $detail === '' ? 'The audit worker could not be started.' : $detail,
        ]
    );
    execute_query(
        'UPDATE site_audit_runs
         SET issue_count = (SELECT COUNT(*) FROM site_audit_issues WHERE run_id = :run_id)
         WHERE id = :id',
        ['run_id' => $runId, 'id' => $runId]
    );
}

function site_audit_node_available(): bool
{
    if (!function_exists('proc_open')) {
        return false;
    }

    $command = PHP_OS_FAMILY === 'Windows' ? 'where.exe node' : 'command -v node';
    $pipes = [];
    $process = @proc_open(
        $command,
        [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
        $pipes,
        BASE_PATH
    );
    if (!is_resource($process)) {
        return false;
    }

    $output = trim((string) stream_get_contents($pipes[1]));
    fclose($pipes[1]);
    if (isset($pipes[2]) && is_resource($pipes[2])) {
        fclose($pipes[2]);
    }
    $exitCode = proc_close($process);

    return $exitCode === 0 && $output !== '';
}

/** Optional service-account module path; ignored local config is loopback-only. */
function site_audit_prepare_node_runtime(): void
{
    $modules = trim((string) (getenv('SITE_AUDIT_NODE_MODULES') ?: ''));
    $browsers = trim((string) (getenv('PLAYWRIGHT_BROWSERS_PATH') ?: ''));
    if (($modules === '' || $browsers === '') && db_table_exists('site_settings')) {
        // Read only these non-secret paths; the general settings getter can install defaults.
        $runtimeSettings = array_column(fetch_all("SELECT setting_key,setting_value FROM site_settings WHERE setting_key IN ('site_audit_node_modules','site_audit_browsers_path')"), 'setting_value', 'setting_key');
        if ($modules === '') { $modules = trim((string) ($runtimeSettings['site_audit_node_modules'] ?? '')); }
        if ($browsers === '') { $browsers = trim((string) ($runtimeSettings['site_audit_browsers_path'] ?? '')); }
    }
    $host = strtolower((string) parse_url(defined('APP_URL') ? APP_URL : '', PHP_URL_HOST));
    if ($modules === '' && defined('APP_ENV') && APP_ENV !== 'production' && in_array($host,['localhost','127.0.0.1','::1'],true)) {
        $configPath = BASE_PATH . '/output/site-audit-runtime.json';
        if (is_file($configPath) && filesize($configPath) <= 8192) {
            $config = json_decode((string) file_get_contents($configPath),true);
            if (!is_array($config) || !is_string($config['node_modules'] ?? null)) { throw new RuntimeException('The local audit runtime configuration is invalid.'); }
            $modules = $config['node_modules'];
            if (isset($config['max_external_links'])) {
                if (!is_int($config['max_external_links']) || $config['max_external_links'] < 0 || $config['max_external_links'] > 200) { throw new RuntimeException('The local external-link limit is invalid.'); }
                putenv('SITE_AUDIT_MAX_EXTERNAL_LINKS=' . $config['max_external_links']);
            }
        }
    }
    if ($modules === '') { return; }
    $resolved = realpath($modules);
    if (!$resolved || !is_file($resolved . '/playwright/package.json') || !is_file($resolved . '/playwright-core/package.json')) { throw new RuntimeException('The configured audit Playwright packages are unavailable.'); }
    if ($browsers !== '') {
        $browserRoot = realpath($browsers);
        if (!$browserRoot || !is_dir($browserRoot)) { throw new RuntimeException('The configured audit Chromium storage is unavailable.'); }
        putenv('PLAYWRIGHT_BROWSERS_PATH=' . $browserRoot);
    }
    putenv('NODE_PATH=' . $resolved);
}

/**
 * Start the Playwright worker without exposing the command or temp path to
 * the browser. The caller records a failed run when the host cannot execute
 * Node.js or write the private log.
 */
function site_audit_start_worker(int $runId, string $baseUrl, string $logFile): array
{
    site_audit_prepare_node_runtime();
    $preflight = audit_preflight();
    if (!$preflight['ok']) { return ['ok' => false, 'error' => $preflight['error']]; }
    $script = BASE_PATH . DIRECTORY_SEPARATOR . 'cli' . DIRECTORY_SEPARATOR . 'site_audit.js';
    if (!is_file($script)) {
        return ['ok' => false, 'error' => 'The site audit worker script is missing.'];
    }
    if (!site_audit_node_available()) {
        return ['ok' => false, 'error' => 'Node.js is not available to the web worker.'];
    }

    if (is_link($logFile)) { return ['ok' => false, 'error' => 'The private audit log path is unsafe.']; }
    $logHandle = @fopen($logFile, 'xb');
    if (!is_resource($logHandle)) {
        return ['ok' => false, 'error' => 'The private audit log directory is not writable.'];
    }
    fclose($logHandle);
    @chmod($logFile, 0600);

    if (!function_exists('exec') && !function_exists('popen')) {
        return ['ok' => false, 'error' => 'Background process execution is disabled on this host.'];
    }

    $scriptArg = escapeshellarg($script);
    $baseArg = escapeshellarg($baseUrl);
    $logArg = escapeshellarg($logFile);
    $command = 'node ' . $scriptArg
        . ' --run-id=' . $runId
        . ' --base-url=' . $baseArg
        . ' --inventory=' . escapeshellarg(site_audit_log_path($runId) . '.inventory.json')
        . ' --max-external-links=' . ((ctype_digit((string) getenv('SITE_AUDIT_MAX_EXTERNAL_LINKS'))) ? min(200,(int) getenv('SITE_AUDIT_MAX_EXTERNAL_LINKS')) : 25)
        . ' > ' . $logArg . ' 2>&1';

    if (PHP_OS_FAMILY === 'Windows') {
        if (!function_exists('popen')) {
            return ['ok' => false, 'error' => 'Background process execution is disabled on this host.'];
        }
        $process = @popen('start /B "" ' . $command, 'r');
        if (!is_resource($process)) {
            return ['ok' => false, 'error' => 'The Node.js audit worker could not be started.'];
        }
        @pclose($process);
        return ['ok' => true, 'error' => null];
    }

    if (!function_exists('exec')) {
        return ['ok' => false, 'error' => 'Background process execution is disabled on this host.'];
    }
    $exitCode = 0;
    @exec($command . ' < /dev/null &', $unusedOutput, $exitCode);
    if ($exitCode !== 0) {
        return ['ok' => false, 'error' => 'The Node.js audit worker could not be started.'];
    }

    return ['ok' => true, 'error' => null];
}

require_once __DIR__ . '/site_audit_portal.php';
