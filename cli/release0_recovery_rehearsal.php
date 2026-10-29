<?php
declare(strict_types=1);
// Synthetic, offline recovery proof. Never connects to the operational database.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
if (count($argv) !== 1) { fwrite(STDERR, "CLI only; no production or apply mode.\n"); exit(2); }
require_once __DIR__ . '/../includes/records_workbook.php';
$root = ict_records_temp_directory() . '/ict-r0-recovery-' . bin2hex(random_bytes(8));
$check = static function (bool $ok, string $message): void { if (!$ok) { throw new RuntimeException($message); } };
$files = ['application.php', 'synthetic.sqlite', 'config.json', 'uploads/synthetic.txt', 'server-rules.txt', 'records.xlsx'];
$report = ['scope'=>'synthetic_offline_recovery_only', 'production_backup_verified'=>false, 'external_sharing_verified'=>false, 'checks'=>[]];
$pdo = null;
$workbook = null;
try {
    $check(mkdir($root, 0700), 'Could not create isolated rehearsal directory.');
    foreach (['source', 'source/uploads', 'restored', 'restored/uploads'] as $directory) { $check(mkdir($root.'/'.$directory, 0700), 'Could not prepare rehearsal directories.'); }
    $pdo = new PDO('sqlite:'.$root.'/source/synthetic.sqlite', null, null, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
    $pdo->exec('CREATE TABLE settings (key TEXT PRIMARY KEY, value TEXT NOT NULL); CREATE TABLE records (reference TEXT PRIMARY KEY, description TEXT NOT NULL); CREATE TABLE pages (slug TEXT PRIMARY KEY, content TEXT NOT NULL)');
    $stmt = $pdo->prepare('INSERT INTO settings VALUES (?, ?)');
    $stmt->execute(['retention', json_encode(ict_retention_defaults(), JSON_THROW_ON_ERROR)]);
    $stmt = null;
    $pdo->exec("INSERT INTO records VALUES ('SYNTHETIC-ONLY', 'Fictional record; no customer data'); INSERT INTO pages VALUES ('synthetic-policy', 'Unpublished synthetic policy')");
    $pdo = null;
    file_put_contents($root.'/source/config.json', json_encode(['environment'=>'synthetic', 'mail_transport'=>'disabled', 'tracking'=>false, 'ai'=>false, 'notifications'=>false, 'booking_integrations'=>false], JSON_THROW_ON_ERROR));
    file_put_contents($root.'/source/uploads/synthetic.txt', 'SYNTHETIC UPLOAD ONLY');
    file_put_contents($root.'/source/server-rules.txt', 'Loopback only; noindex; deny unknown routes; no outbound integrations');
    file_put_contents($root.'/source/application.php', '<?php $db=new PDO("sqlite:".__DIR__."/synthetic.sqlite"); $rules=json_decode($db->query("SELECT value FROM settings WHERE key=\'retention\'")->fetchColumn(),true,16,JSON_THROW_ON_ERROR); $config=json_decode(file_get_contents(__DIR__."/config.json"),true); if(count($rules["rules"])!==10 || $config["mail_transport"]!=="disabled" || $config["tracking"] || $config["notifications"] || $config["ai"]) { exit(1); } echo "isolated-restore-ok";');
    $workbook = ict_records_workbook_snapshot(ict_retention_defaults());
    $check(copy($workbook, $root.'/source/records.xlsx'), 'Workbook copy failed.');
    unlink($workbook); $workbook = null;
    $manifest = [];
    $zip = new ZipArchive();
    $check($zip->open($root.'/backup.zip', ZipArchive::CREATE | ZipArchive::EXCL) === true, 'Archive creation failed.');
    foreach ($files as $file) {
        $manifest[$file] = hash_file('sha256', $root.'/source/'.$file);
        $check($zip->addFile($root.'/source/'.$file, $file), 'Archive entry failed.');
    }
    $check($zip->close(), 'Archive finalization failed.');
    $archiveHash = hash_file('sha256', $root.'/backup.zip');
    // Change source after backup: recovery must use the backed-up bytes.
    file_put_contents($root.'/source/uploads/synthetic.txt', 'Changed after backup');
    $check($zip->open($root.'/backup.zip') === true, 'Archive reopening failed.');
    $check($zip->numFiles === count($files), 'Unexpected archive entries.');
    foreach ($files as $file) {
        $bytes = $zip->getFromName($file);
        $check(is_string($bytes) && hash('sha256', $bytes) === $manifest[$file], 'Backup checksum mismatch.');
        $check(file_put_contents($root.'/restored/'.$file, $bytes) !== false, 'Restore write failed.');
        $check(hash_file('sha256', $root.'/restored/'.$file) === $manifest[$file], 'Restore checksum mismatch.');
    }
    $zip->close();
    $report['checks']['six_components_checksum_verified'] = true;
    $report['checks']['restored_original_before_source_change'] = file_get_contents($root.'/restored/uploads/synthetic.txt') === 'SYNTHETIC UPLOAD ONLY';
    $pdo = new PDO('sqlite:'.$root.'/restored/synthetic.sqlite');
    $check($pdo->query('PRAGMA integrity_check')->fetchColumn() === 'ok', 'Restored database integrity failed.');
    $check((int)$pdo->query('SELECT COUNT(*) FROM records')->fetchColumn() === 1, 'Restored record count failed.');
    $check($pdo->query('SELECT content FROM pages')->fetchColumn() === 'Unpublished synthetic policy', 'Restored policy changed.');
    $rules = json_decode($pdo->query("SELECT value FROM settings WHERE key='retention'")->fetchColumn(), true, 16, JSON_THROW_ON_ERROR);
    $check(ict_retention_validate($rules) === ict_retention_defaults(), 'Restored rule document failed.');
    $pdo = null;
    $report['checks']['database_integrity_counts_rules_and_policy'] = true;
    $process = proc_open([PHP_BINARY, $root.'/restored/application.php'], [1=>['pipe','w'], 2=>['pipe','w']], $pipes, null, null, ['bypass_shell'=>true]);
    $check(is_resource($process), 'Restored synthetic application could not start.');
    $result = stream_get_contents($pipes[1]).stream_get_contents($pipes[2]); fclose($pipes[1]); fclose($pipes[2]);
    $check(proc_close($process) === 0 && $result === 'isolated-restore-ok', 'Restored synthetic application smoke failed.');
    $report['checks']['restored_synthetic_application_and_integrations_disabled'] = true;
    $report['archive_sha256'] = $archiveHash;
    $report['components'] = $files;
} finally {
    $stmt = null;
    $pdo = null;
    if (isset($zip)) { try { @$zip->close(); } catch (Throwable) {} $zip = null; }
    if (is_string($workbook) && is_file($workbook)) { unlink($workbook); }
    // Delete only explicitly named synthetic files under this newly created directory.
    foreach (['source', 'restored'] as $part) { foreach ($files as $file) { if (is_file($root.'/'.$part.'/'.$file)) { unlink($root.'/'.$part.'/'.$file); } } }
    if (is_file($root.'/backup.zip')) { unlink($root.'/backup.zip'); }
    foreach (['source/uploads','restored/uploads','source','restored'] as $directory) { if (is_dir($root.'/'.$directory)) { rmdir($root.'/'.$directory); } }
    if (is_dir($root)) { rmdir($root); }
}
$report['checks']['temporary_artifacts_removed'] = !file_exists($root);
$check(!in_array(false, $report['checks'], true), 'Rehearsal verification failed.');
echo json_encode($report, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR), PHP_EOL;
