<?php
declare(strict_types=1);

// Only this reviewed additive migration is executable through this entry point.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
const RM_RELEASE_MIGRATION = '2026-10-25-route-maps.sql';

function rm_release_columns(PDO $pdo, string $table): array
{
    $statement = $pdo->prepare('SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT, EXTRA FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? ORDER BY ORDINAL_POSITION');
    $statement->execute([$table]);
    return array_column($statement->fetchAll(PDO::FETCH_ASSOC), null, 'COLUMN_NAME');
}

function rm_release_expected(): array
{
    return [
        'map_locations' => ['id','name','latitude','longitude','destination_id','coordinate_source','is_verified','is_active','revision','created_at','updated_at'],
        'package_route_maps' => ['id','package_type','package_id','route_json','revision','created_at','updated_at'],
    ];
}

function rm_release_validate_table(PDO $pdo, string $table, bool $required): void
{
    $columns = rm_release_columns($pdo, $table);
    if (!$columns) {
        if ($required) { throw new RuntimeException('Expected map table is missing: ' . $table); }
        return;
    }
    $statement = $pdo->prepare('SELECT ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=?');
    $statement->execute([$table]);
    if (strcasecmp((string) $statement->fetchColumn(), 'InnoDB') !== 0 || array_diff(rm_release_expected()[$table], array_keys($columns))) {
        throw new RuntimeException('Existing map schema needs review before activation: ' . $table);
    }
    $indexes = $pdo->query('SHOW INDEX FROM `' . $table . '`')->fetchAll(PDO::FETCH_ASSOC);
    $unique = [];
    foreach ($indexes as $index) {
        if ((int) $index['Non_unique'] === 0) { $unique[$index['Key_name']][(int) $index['Seq_in_index']] = $index['Column_name']; }
    }
    $wanted = $table === 'map_locations' ? ['destination_id'] : ['package_type','package_id'];
    $found = false;
    foreach ($unique as $index) { ksort($index); if (array_values($index) === $wanted) { $found = true; } }
    if (!$found || !isset($unique['PRIMARY']) || array_values($unique['PRIMARY']) !== ['id']) { throw new RuntimeException('Map uniqueness/primary-key definitions need review: ' . $table); }
    $types = $table === 'map_locations' ? ['latitude'=>'decimal(9,6)','longitude'=>'decimal(9,6)','name'=>'varchar(150)','coordinate_source'=>'varchar(500)'] : ['package_type'=>'varchar(20)','route_json'=>'longtext'];
    foreach ($types as $column => $expected) {
        if (strtolower($columns[$column]['COLUMN_TYPE']) !== $expected) { throw new RuntimeException('Map column type needs review: ' . $table . '.' . $column); }
    }
    foreach (['id','revision'] as $column) {
        if (!str_starts_with(strtolower($columns[$column]['COLUMN_TYPE']), 'bigint') || !str_contains(strtolower($columns[$column]['COLUMN_TYPE']), 'unsigned')) {
            throw new RuntimeException('Map identifier type needs review: ' . $table . '.' . $column);
        }
    }
    foreach ($columns as $column=>$definition) {
        if (!in_array($column,rm_release_expected()[$table],true) && $definition['IS_NULLABLE']==='NO' && $definition['COLUMN_DEFAULT']===null && !str_contains($definition['EXTRA'],'auto_increment')) { throw new RuntimeException('Unexpected required map column needs review: '.$table.'.'.$column); }
        if (in_array($column,rm_release_expected()[$table],true) && $definition['IS_NULLABLE'] !== ($column==='destination_id' ? 'YES' : 'NO')) { throw new RuntimeException('Map column nullability needs review: '.$table.'.'.$column); }
    }
    if (!str_contains($columns['id']['EXTRA'], 'auto_increment')) { throw new RuntimeException('Map identifier must auto-increment: ' . $table); }
}

function rm_release_fingerprint(PDO $pdo, string $table, string $order): ?array
{
    if (!rm_release_columns($pdo, $table)) { return null; }
    $hash = hash_init('sha256'); $count = 0;
    $statement = $pdo->query('SELECT * FROM `' . $table . '` ORDER BY `' . $order . '`');
    while ($row = $statement->fetch(PDO::FETCH_ASSOC)) { hash_update($hash, json_encode($row, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . "\n"); $count++; }
    return ['count'=>$count,'sha256'=>hash_final($hash)];
}

function rm_release_protected(PDO $pdo): array
{
    $state = [];
    foreach (['tours','itineraries','itinerary_days','day_tours','destinations','media_assets','day_tour_gallery'] as $table) { $state[$table] = rm_release_fingerprint($pdo, $table, 'id'); }
    $state['site_settings'] = rm_release_fingerprint($pdo, 'site_settings', 'setting_key');
    return $state;
}

function rm_release_history(PDO $pdo, string $checksum): array
{
    $columns = rm_release_columns($pdo, 'schema_migrations');
    $key = isset($columns['migration']) ? 'migration' : (isset($columns['version']) ? 'version' : null);
    if ($key === null) { throw new RuntimeException('The existing migration-history table is required; this command does not create or repair unrelated schema.'); }
    $statement = $pdo->prepare('SELECT * FROM schema_migrations WHERE `' . $key . '`=?');
    $statement->execute([RM_RELEASE_MIGRATION]); $row = $statement->fetch(PDO::FETCH_ASSOC) ?: null;
    if ($row && isset($columns['checksum']) && !hash_equals((string) $row['checksum'], $checksum)) { throw new RuntimeException('Recorded map migration checksum differs; stop for review.'); }
    if ($row && isset($columns['status']) && ($row['status'] ?? '') !== 'applied') { throw new RuntimeException('Recorded map migration status needs review.'); }
    $supported = [$key,'checksum','status','batch','execution_time_ms','applied_at'];
    foreach ($columns as $name=>$definition) {
        if (!in_array($name,$supported,true) && $definition['IS_NULLABLE']==='NO' && $definition['COLUMN_DEFAULT']===null && !str_contains($definition['EXTRA'],'auto_increment')) { throw new RuntimeException('Unsupported migration-history schema; no changes made.'); }
    }
    return ['key'=>$key,'columns'=>$columns,'row'=>$row];
}

function rm_release_write_json(string $path, array $value): void
{
    $json = json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . "\n";
    $handle = fopen($path, 'x');
    if (!$handle) { throw new RuntimeException('Could not create a new private activation record.'); }
    try {
        if (fwrite($handle,$json) !== strlen($json) || !fflush($handle)) { throw new RuntimeException('Private activation backup could not be written completely.'); }
        if (function_exists('fsync')) { fsync($handle); }
    } finally { fclose($handle); }
    if (PHP_OS_FAMILY !== 'Windows') { chmod($path,0600); }
    $read = file_get_contents($path);
    if ($read !== $json || json_decode($read,true,512,JSON_THROW_ON_ERROR) !== $value) { throw new RuntimeException('Private activation backup verification failed.'); }
}

function rm_release_main(): void
{
    $options = getopt('', ['root:','check','apply','backup-dir:']);
    if (isset($options['check']) === isset($options['apply'])) { throw new RuntimeException('Use exactly one of --check or --apply.'); }
    $root = realpath($options['root'] ?? dirname(__DIR__));
    if (!$root || !is_file($root.'/config/database.php')) { throw new RuntimeException('Choose the reviewed application root.'); }
    require_once $root.'/config/database.php';
    $pdo = pdo();
    $file = $root.'/database/migrations/'.RM_RELEASE_MIGRATION;
    // Before checkout, the staged CLI preflight may run against the previous release.
    $migration = is_file($file) ? $file : __DIR__.'/../database/migrations/'.RM_RELEASE_MIGRATION;
    if (!is_file($migration)) { throw new RuntimeException('The reviewed map migration file is missing.'); }
    $checksum = hash_file('sha256',$migration);
    foreach (array_keys(rm_release_expected()) as $table) { rm_release_validate_table($pdo,$table,false); }
    $history = rm_release_history($pdo,$checksum);
    if (isset($options['check'])) {
        echo json_encode(['result'=>'ROUTE_MAP_PREFLIGHT_OK','migration'=>RM_RELEASE_MIGRATION,'tables_present'=>array_map(static fn($t)=> (bool) rm_release_columns($pdo,$t),array_keys(rm_release_expected())),'database_changes'=>false],JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR).PHP_EOL;
        return;
    }
    $directory = realpath($options['backup-dir'] ?? '');
    $normalize = static fn(string $p):string => rtrim(strtolower(str_replace('\\','/',$p)),'/');
    if (!$directory || !is_dir($directory) || !is_writable($directory) || $normalize($directory)===$normalize($root) || str_starts_with($normalize($directory).'/', $normalize($root).'/')) {
        throw new RuntimeException('Supply an existing private --backup-dir outside the website root.');
    }
    require_once $root.'/includes/records_workbook.php';
    if (!ict_records_spool_private($directory)) { throw new RuntimeException('The backup directory must be restricted to its owner/service identity and administrators.'); }
    $lock = $pdo->query("SELECT GET_LOCK('ict_route_maps_20261025', 10)")->fetchColumn();
    if ((int) $lock !== 1) { throw new RuntimeException('Another map activation is in progress; no changes made.'); }
    try {
        $history = rm_release_history($pdo,$checksum);
        $protected = rm_release_protected($pdo);
        $backup = ['version'=>1,'migration'=>RM_RELEASE_MIGRATION,'checksum'=>$checksum,'created_utc'=>gmdate('c'),'history'=>$history['row'],'protected'=>$protected,'tables'=>[]];
        foreach (array_keys(rm_release_expected()) as $table) {
            $exists = (bool) rm_release_columns($pdo,$table);
            $backup['tables'][$table] = $exists ? ['create'=>$pdo->query('SHOW CREATE TABLE `'.$table.'`')->fetch(PDO::FETCH_NUM)[1],'rows'=>$pdo->query('SELECT * FROM `'.$table.'` ORDER BY id')->fetchAll(PDO::FETCH_ASSOC)] : null;
        }
        $tag = gmdate('Ymd-His').'-'.bin2hex(random_bytes(4));
        $backupFile = $directory.'/route-maps-before-'.$tag.'.json';
        rm_release_write_json($backupFile,$backup);
        echo 'PRIVATE_MAP_BACKUP_VERIFIED '.$backupFile.PHP_EOL;
        $start = microtime(true);
        $sql = file_get_contents($migration);
        if ($sql === false || !hash_equals($checksum,hash('sha256',$sql))) { throw new RuntimeException('Migration changed after preflight.'); }
        $pdo->exec($sql);
        foreach (array_keys(rm_release_expected()) as $table) {
            rm_release_validate_table($pdo,$table,true);
            $current = $pdo->query('SELECT * FROM `'.$table.'` ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
            if ($current !== ($backup['tables'][$table]['rows'] ?? [])) { throw new RuntimeException('Map data changed during activation; inspect the private backup before proceeding.'); }
        }
        if (rm_release_protected($pdo) !== $protected) { throw new RuntimeException('Package/settings/media state changed during activation; review concurrent edits. No automatic data restore was attempted.'); }
        $history = rm_release_history($pdo,$checksum);
        if (!$history['row']) {
            $values = [$history['key']=>RM_RELEASE_MIGRATION];
            foreach (['checksum'=>$checksum,'status'=>'applied','batch'=>(int) $pdo->query('SELECT COALESCE(MAX('.(isset($history['columns']['batch'])?'batch':'1').'),0) FROM schema_migrations')->fetchColumn()+1,'execution_time_ms'=>(int) round((microtime(true)-$start)*1000)] as $key=>$value) {
                if (isset($history['columns'][$key])) { $values[$key]=$value; }
            }
            $keys=array_keys($values);
            $pdo->prepare('INSERT INTO schema_migrations (`'.implode('`,`',$keys).'`) VALUES ('.implode(',',array_fill(0,count($keys),'?')).')')->execute(array_values($values));
        }
        $result = ['result'=>'ROUTE_MAP_MIGRATION_OK','migration'=>RM_RELEASE_MIGRATION,'checksum'=>$checksum,'schema_ready'=>true,'private_backup_verified'=>true,'existing_map_data_preserved'=>true,'package_settings_media_preserved'=>true,'already_recorded'=>(bool) $history['row'],'application_records_created'=>0];
        rm_release_write_json($directory.'/route-maps-result-'.$tag.'.json',$result);
        echo json_encode($result,JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR).PHP_EOL;
    } finally { $pdo->query("SELECT RELEASE_LOCK('ict_route_maps_20261025')"); }
}

if (realpath($_SERVER['SCRIPT_FILENAME'] ?? '') === __FILE__) {
    try { rm_release_main(); }
    catch (Throwable $error) {
        $message = $error instanceof PDOException ? 'Database operation failed (SQLSTATE '.$error->getCode().'); inspect the private activation packet.' : $error->getMessage();
        if (str_contains($message,'Database connection failed')) { $message = 'Application database connection failed; existing credentials were not changed.'; }
        fwrite(STDERR,'ROUTE_MAP_ACTIVATION_FAILED: '.$message.PHP_EOL);
        exit(1);
    }
}
