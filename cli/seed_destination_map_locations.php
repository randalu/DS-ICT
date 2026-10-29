<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__.'/../includes/route_map_destination_seed.php';
require_once __DIR__.'/activate_route_maps.php';

function route_map_seed_main(): void
{
    $options = getopt('', ['dry-run','apply','backup-dir:','expected-plan:']);
    if (isset($options['dry-run']) === isset($options['apply'])) { throw new RuntimeException('Choose exactly one of --dry-run or --apply.'); }
    $apply = isset($options['apply']); $root = dirname(__DIR__);
    require_once $root.'/config/database.php';
    $pdo = pdo(); $catalog = route_map_seed_catalog($root.'/assets/maps/destination-locations.json');
    foreach (array_keys(rm_release_expected()) as $table) { rm_release_validate_table($pdo, $table, true); }
    $engine = $pdo->query("SELECT ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='destinations'")->fetchColumn();
    if (strcasecmp((string) $engine, 'InnoDB') !== 0) { throw new RuntimeException('Destination imports require transactional InnoDB storage.'); }
    $directory = null;
    if ($apply) {
        if (!preg_match('/\A[a-f0-9]{64}\z/', (string) ($options['expected-plan'] ?? ''))) { throw new RuntimeException('Apply requires the SHA256 plan from a fresh --dry-run.'); }
        $directory = realpath($options['backup-dir'] ?? '');
        $normalize = static fn(string $path):string => rtrim(strtolower(str_replace('\\','/',$path)), '/');
        if (!$directory || !is_dir($directory) || !is_writable($directory) || str_starts_with($normalize($directory).'/', $normalize($root).'/')) { throw new RuntimeException('Provide a private backup directory outside the website root.'); }
        require_once $root.'/includes/records_workbook.php';
        if (!ict_records_spool_private($directory)) { throw new RuntimeException('Backup directory must be private to its owner and administrators.'); }
        if ((int) $pdo->query("SELECT GET_LOCK('ict_route_maps_20261025',10)")->fetchColumn() !== 1) { throw new RuntimeException('Another map activation/import is running.'); }
    }
    try {
        $pdo->exec($apply ? 'SET TRANSACTION ISOLATION LEVEL REPEATABLE READ' : 'SET TRANSACTION READ ONLY');
        $pdo->beginTransaction();
        $suffix = $apply ? ' FOR UPDATE' : '';
        $destinations = $pdo->query('SELECT id,name,slug FROM destinations ORDER BY id'.$suffix)->fetchAll(PDO::FETCH_ASSOC);
        $locations = $pdo->query('SELECT * FROM map_locations ORDER BY id'.$suffix)->fetchAll(PDO::FETCH_ASSOC);
        $plan = route_map_seed_plan($destinations, $locations, $catalog);
        if (!$apply) { $pdo->rollBack(); echo json_encode(['result'=>'DESTINATION_MAP_SEED_PLAN','database_changes'=>false]+$plan, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR).PHP_EOL; return; }
        if (!hash_equals($plan['plan_sha256'], $options['expected-plan'])) { throw new RuntimeException('Destinations, locations or catalog changed since the preview. Run --dry-run again; no changes made.'); }
        $protected = rm_release_protected($pdo);
        $routes = rm_release_fingerprint($pdo, 'package_route_maps', 'id');
        $tag = gmdate('Ymd-His').'-'.bin2hex(random_bytes(4));
        $backup = $directory.'/destination-map-before-'.$tag.'.json';
        rm_release_write_json($backup, ['created_utc'=>gmdate('c'), 'plan'=>$plan, 'destinations'=>$destinations, 'locations'=>$locations, 'protected'=>$protected, 'package_routes'=>$routes]);
        fwrite(STDERR, 'PRIVATE_DESTINATION_MAP_BACKUP_VERIFIED '.$backup.PHP_EOL);
        $statement = $pdo->prepare('INSERT INTO map_locations(name,latitude,longitude,destination_id,coordinate_source,is_verified,is_active) VALUES(?,?,?,?,?,1,1)');
        $inserted = [];
        foreach ($plan['create'] as $entry) {
            $statement->execute([$entry['name'],$entry['latitude'],$entry['longitude'],$entry['destination_id'],$entry['coordinate_source']]);
            $inserted[] = ['location_id'=>(int) $pdo->lastInsertId(), 'destination_id'=>$entry['destination_id'], 'name'=>$entry['name']];
        }
        $after = $pdo->query('SELECT * FROM map_locations ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
        $newIds = array_column($inserted, 'location_id');
        $oldRows = array_values(array_filter($after, static fn(array $row):bool => !in_array((int) $row['id'], $newIds, true)));
        if ($oldRows !== $locations || count($after) !== count($locations)+count($inserted)
            || rm_release_protected($pdo) !== $protected || rm_release_fingerprint($pdo, 'package_route_maps', 'id') !== $routes) {
            throw new RuntimeException('Import preservation check failed; transaction rolled back.');
        }
        $result = ['result'=>'DESTINATION_MAP_SEED_OK', 'inserted'=>count($inserted), 'preserved'=>count($plan['preserve']), 'skipped'=>$plan['skipped'],
            'created'=>$inserted, 'plan_sha256'=>$plan['plan_sha256'], 'backup'=>$backup, 'existing_locations_preserved'=>true, 'destinations_packages_settings_routes_preserved'=>true];
        // A prepared record is diagnostic only; commit success is confirmed on stdout afterward.
        rm_release_write_json($directory.'/destination-map-prepared-'.$tag.'.json', ['state'=>'prepared_not_committed','result'=>$result]);
        $pdo->commit();
        echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR).PHP_EOL;
    } finally {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        if ($apply) { $pdo->query("SELECT RELEASE_LOCK('ict_route_maps_20261025')"); }
    }
}

if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    try { route_map_seed_main(); }
    catch (Throwable $error) {
        $message = $error instanceof PDOException ? 'Database operation failed (SQLSTATE '.$error->getCode().').' : $error->getMessage();
        if (str_contains($message,'Database connection failed')) { $message='Application database connection failed; existing credentials were not changed.'; }
        fwrite(STDERR, 'DESTINATION_MAP_SEED_FAILED: '.$message.PHP_EOL); exit(1);
    }
}
