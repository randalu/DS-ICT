<?php
declare(strict_types=1);

// Explicit, additive release runner. Does not invoke tour-content consolidation.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }

function release_column(string $table, string $column): bool
{
    return (bool) fetch_one('SELECT 1 FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name=:t AND column_name=:c', ['t'=>$table,'c'=>$column]);
}
function release_table(string $table): bool
{
    return (bool) fetch_one('SELECT 1 FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name=:t', ['t'=>$table]);
}
/** Read-only checks shared by the plan and the backup, before pulling live code. */
function release_backup_preflight(): array
{
    if (!extension_loaded('pdo_mysql') || !extension_loaded('zlib')) {
        throw new RuntimeException('The release requires pdo_mysql and zlib.');
    }
    $tables = fetch_all("SHOW FULL TABLES WHERE Table_type='BASE TABLE'");
    foreach (['VIEWS'=>'TABLE_SCHEMA','ROUTINES'=>'ROUTINE_SCHEMA','EVENTS'=>'EVENT_SCHEMA'] as $kind=>$field) {
        if ((int)(fetch_one("SELECT COUNT(*) AS n FROM information_schema.$kind WHERE $field=DATABASE()")['n']??0) > 0) {
            throw new RuntimeException('Database has '.$kind.'; use an owner-reviewed complete backup before this release.');
        }
    }
    foreach ($tables as $row) {
        $table = (string)array_values($row)[0];
        $engine = fetch_one('SELECT ENGINE FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name=:t', ['t'=>$table]);
        if (strtoupper((string)$engine['ENGINE']) !== 'INNODB') { throw new RuntimeException('Backup requires InnoDB tables.'); }
    }
    foreach(fetch_all('SHOW TRIGGERS') as $trigger){
        release_backup_trigger(fetch_one('SHOW CREATE TRIGGER `'.str_replace('`','``',(string)$trigger['Trigger']).'`')?:[]);
    }
    return $tables;
}

function release_backup_literal(mixed $value, bool $json = false): string
{
    if ($value === null) { return 'NULL'; }
    $hex = bin2hex((string)$value);
    if ($json) { return 'CONVERT(0x'.$hex.' USING utf8mb4)'; }
    return $hex === '' ? "''" : 'CAST(0x'.$hex.' AS BINARY)';
}

function release_backup_trigger(array $definition): string
{
    $ddl = (string)($definition['SQL Original Statement']??'');
    if ($ddl === '') { throw new RuntimeException('Cannot read trigger definition.'); }
    $settings = '';
    foreach (['sql_mode','character_set_client','collation_connection'] as $setting) {
        if (!isset($definition[$setting])) { throw new RuntimeException('Cannot read trigger creation settings.'); }
        $value = (string)$definition[$setting];
        $literal = $value === '' ? "''" : 'CONVERT(0x'.bin2hex($value).' USING utf8mb4)';
        $settings .= 'SET SESSION '.$setting.'='.$literal.";\n";
    }
    do { $delimiter = 'ict_end_'.bin2hex(random_bytes(12)); } while (str_contains($ddl,$delimiter));
    return $settings."DELIMITER ".$delimiter."\n".$ddl.$delimiter."\nDELIMITER ;\n";
}

function release_backup(string $directory): string
{
    $directory = realpath($directory) ?: '';
    $public = realpath(BASE_PATH);
    $normalize = static fn(string $p): string => strtolower(str_replace('\\', '/', $p));
    if ($directory === '' || $normalize($directory) === $normalize($public)
        || str_starts_with($normalize($directory).'/', $normalize($public).'/')) {
        throw new RuntimeException('Backup directory must already exist outside the web checkout.');
    }
    if (PHP_OS_FAMILY !== 'Windows' && ((fileperms($directory) & 0077) !== 0 || !is_writable($directory))) {
        throw new RuntimeException('Backup directory must be writable and private (chmod 700).');
    }
    $tables = release_backup_preflight();
    $file = $directory.'/page-media-'.gmdate('Ymd-His').'-'.bin2hex(random_bytes(4)).'.sql.gz';
    $out = gzopen($file, 'wb9');
    if (!$out) { throw new RuntimeException('Cannot create backup.'); }
    chmod($file, 0600);
    $write = static function(string $sql) use ($out): void {
        if (gzwrite($out, $sql) !== strlen($sql)) { throw new RuntimeException('Backup write failed.'); }
    };
    $counts = [];
    try {
        db()->exec('SET TRANSACTION ISOLATION LEVEL REPEATABLE READ');
        db()->exec('START TRANSACTION WITH CONSISTENT SNAPSHOT');
        $write("-- Island page-media pre-migration backup; keep private.\nSET @ict_backup_sql_mode=@@sql_mode, @ict_backup_character_set_client=@@character_set_client, @ict_backup_collation_connection=@@collation_connection;\nSET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\nSET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';\n");
        foreach ($tables as $row) {
            $table = (string)array_values($row)[0];
            $quoted = '`'.str_replace('`','``',$table).'`';
            $ddl = array_values(fetch_one('SHOW CREATE TABLE '.$quoted))[1];
            $write('DROP TABLE IF EXISTS '.$quoted.";\n".$ddl.";\n");
            $statement = db()->query('SELECT * FROM '.$quoted);
            $jsonColumns = array_fill_keys(array_column(array_filter(fetch_all('SHOW COLUMNS FROM '.$quoted), static fn($c)=>strtolower($c['Type'])==='json'),'Field'),true);
            $counts[$table] = 0;
            while ($record = $statement->fetch(PDO::FETCH_ASSOC)) {
                $columns = implode(',', array_map(static fn($c)=>'`'.str_replace('`','``',$c).'`', array_keys($record)));
                // Hex literals preserve text and binary bytes without SQL-mode escaping assumptions.
                $values = [];
                foreach($record as $column=>$value){
                    $values[]=release_backup_literal($value,isset($jsonColumns[$column]));
                }
                $values=implode(',',$values);
                $write('INSERT INTO '.$quoted.' ('.$columns.') VALUES ('.$values.");\n");
                $counts[$table]++;
            }
            $statement->closeCursor();
        }
        foreach (fetch_all('SHOW TRIGGERS') as $trigger) {
            $name = (string)$trigger['Trigger'];
            $definition = fetch_one('SHOW CREATE TRIGGER `'.str_replace('`','``',$name).'`');
            $write(release_backup_trigger($definition));
        }
        $write("SET SESSION sql_mode=@ict_backup_sql_mode, character_set_client=@ict_backup_character_set_client, collation_connection=@ict_backup_collation_connection;\nSET FOREIGN_KEY_CHECKS=1;\n-- backup complete\n");
        db()->commit();
    } catch (Throwable $error) {
        if (db()->inTransaction()) { db()->rollBack(); }
        throw $error;
    } finally { gzclose($out); }
    $verify = gzopen($file, 'rb'); $tail = '';
    while (!gzeof($verify)) { $tail = substr($tail.gzread($verify, 1048576), -100); }
    gzclose($verify);
    if (!str_ends_with($tail, "-- backup complete\n")) { throw new RuntimeException('Backup verification failed.'); }
    $checksum=hash_file('sha256',$file);
    if($checksum===false){throw new RuntimeException('Cannot checksum backup.');}
    $manifest=json_encode(['sha256'=>$checksum,'tables'=>count($counts),'rows'=>array_sum($counts)],JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR).PHP_EOL;
    if(file_put_contents($file.'.json',$manifest)!==strlen($manifest)||!chmod($file.'.json',0600)){
        throw new RuntimeException('Cannot write private backup checksum manifest.');
    }
    echo 'Backup: '.$file.PHP_EOL;
    return $file;
}

/** Exact inspected prerequisite SQL, not permission to run arbitrary historical migrations. */
function release_reviewed_prerequisites(): array
{
    return [
        '2026-09-01-site-audit.sql'=>'cb1ead01d17944fbc332920c381fdd7c3c771635a5a68eaf331695172c3102e0',
        '2026-09-12-site-audit-progress.sql'=>'031b7a01244302ceb66ac51cd9015cbcec242476c7b62db37f17c808f585ac05',
        '2026-10-15-seo-manager.sql'=>'3c0565d25f8f5df8db6462debe1bd0ef7deae6822f5357ad16657a900c058a26',
        '2026-10-16-site-audit-portal.sql'=>'87f3452046a8497fd5d347879dac3c2629d73cd5086c7b5b6ef702b2359a999d',
        '2026-10-25-activities.sql'=>'61745f0c5c36598d1e347519d833c44c026a7a6951b30d340e80175b2b181829',
        '2026-10-25-activity-entry-galleries.sql'=>'8dbc2d4e85db6ab788b1c7334ad6408f6fabbe134455531a19bab4eaaae81f87',
        '2026-10-25-shared-admin-openai.sql'=>'f9b77571e76914ed65f78a1f0de08529b0afa9932543ef45ee8c2d77cc745375',
        '2026-10-25-image-placement-alt.sql'=>'15ed308f89a02894937969fb821cf2799b31132328c0813db470614194f0c5da',
    ];
}

function release_migration_names(): array
{
    return array_merge(array_keys(release_reviewed_prerequisites()),[
        '2026-09-26-release0-policy-acceptance.sql',
        '2026-10-22-consented-visitor-counting.sql',
        '2026-10-23-first-party-stats.sql',
        '2026-10-23-quotation-builder.sql',
        '2026-10-24-package-editor-media.sql',
        '2026-10-24-tour-coverage.sql',
        '2026-10-24-tour-program.sql',
        '2026-10-25-route-maps.sql',
        '2026-10-28-page-media-galleries.sql',
    ]);
}

/** A deliberately narrow public diagnostic containing filenames/reasons, never PDO details. */
final class PageMediaPlanBlocked extends RuntimeException
{
    public function __construct(public readonly array $reasons)
    {
        parent::__construct(implode("\n",$reasons));
    }
}

/** Validate only this release's ledger rows; unrelated history is diagnostic and never replayed. */
function release_migration_plan(array $ledger,array $files,array $names,?callable $inspectProgressColumns=null): array
{
    $pending=[];$historicalDrift=0;$historicalStatuses=[];$verifiedProgressHistory=[];$blocked=[];$seen=[];
    $reviewed=release_reviewed_prerequisites();
    sort($files,SORT_STRING);
    foreach($files as $file){
        $name=basename($file);$release=in_array($name,$names,true);
        if(!preg_match('/\A[a-z0-9][a-z0-9._-]*\.sql\z/D',$name)){throw new RuntimeException('Invalid migration filename.');}
        $seen[]=$name;
        $sql=file_get_contents($file);if($sql===false)throw new RuntimeException('Cannot read migration: '.$name);
        $checksum=hash('sha256',str_replace("\r\n","\n",$sql));
        $pinned=$release&&isset($reviewed[$name])&&hash_equals($reviewed[$name],$checksum);
        if($release&&isset($reviewed[$name])&&!$pinned){
            $blocked[]='Reviewed prerequisite SQL changed: '.$name;
        }
        if(isset($ledger[$name])){
            $row=$ledger[$name];
            if(array_key_exists('status',$row)&&$row['status']!=='applied'){
                if($release){$blocked[]='Non-applied release ledger status: '.$name;}
                else{$historicalStatuses[]=$name;}
            }
            if(!empty($row['checksum'])&&!hash_equals($row['checksum'],$checksum)&&!hash_equals($row['checksum'],hash('sha256',$sql))){
                // Only this inspected, column-only historical migration has a compatibility path.
                // An existing applied row is preserved verbatim, never relabelled or replayed.
                if($pinned&&$name==='2026-09-12-site-audit-progress.sql'&&($row['status']??null)==='applied'
                    &&release_progress_history_matches($inspectProgressColumns)){
                    $verifiedProgressHistory[]=$name;$historicalDrift++;
                }elseif($release){$blocked[]='Release checksum mismatch: '.$name;}
                else{$historicalDrift++;}
            }
        }elseif(!$release){$blocked[]='Unreviewed pending migration: '.$name;}
        else{$pending[$name]=$file;}
    }
    foreach(array_diff($names,$seen) as $name){$blocked[]='Missing release migration file: '.$name;}
    if($blocked){throw new PageMediaPlanBlocked($blocked);}
    return compact('pending','historicalDrift','historicalStatuses','verifiedProgressHistory');
}

function release_progress_history_matches(?callable $inspect): bool
{
    if($inspect===null)return false;
    $columns=$inspect();
    if(!is_array($columns))return false;
    try{return release_audit_progress_plan($columns)===[];}
    catch(RuntimeException){return false;}
}

function release_audit_progress_columns(): array
{
    return [
        'total_pages'=>['COLUMN_TYPE'=>'int unsigned','IS_NULLABLE'=>'NO','COLUMN_DEFAULT'=>'0','EXTRA'=>''],
        'log_offset'=>['COLUMN_TYPE'=>'bigint unsigned','IS_NULLABLE'=>'NO','COLUMN_DEFAULT'=>'0','EXTRA'=>''],
        'triggered_by'=>['COLUMN_TYPE'=>'varchar(100)','IS_NULLABLE'=>'NO','COLUMN_DEFAULT'=>'admin','EXTRA'=>''],
    ];
}

/** Ignore obsolete integer display widths, not signedness, width of strings, or nullability. */
function release_assert_column(string $table,string $column,array $expected,array $actual): void
{
    $type=static fn(string $value): string => preg_replace('/\b(tinyint|smallint|mediumint|int|bigint)\(\d+\)/i','$1',strtolower($value));
    if($type((string)($actual['COLUMN_TYPE']??''))!==$type($expected['COLUMN_TYPE'])
        ||($actual['IS_NULLABLE']??'')!==$expected['IS_NULLABLE']){
        throw new RuntimeException('Incompatible prerequisite column: '.$table.'.'.$column);
    }
    if(array_key_exists('COLUMN_DEFAULT',$expected)){
        // MariaDB may include SQL quotes in INFORMATION_SCHEMA string defaults.
        $default=$actual['COLUMN_DEFAULT']??null;
        if(is_string($default)&&strlen($default)>=2&&$default[0]==="'"&&str_ends_with($default,"'")){$default=str_replace("''","'",substr($default,1,-1));}
        if($default===null||(string)$default!==$expected['COLUMN_DEFAULT']){throw new RuntimeException('Incompatible prerequisite default: '.$table.'.'.$column);}
    }
    if(array_key_exists('EXTRA',$expected)&&(!array_key_exists('EXTRA',$actual)||strtolower(trim((string)$actual['EXTRA']))!==$expected['EXTRA'])){
        throw new RuntimeException('Incompatible prerequisite attributes: '.$table.'.'.$column);
    }
}

function release_columns(string $table): array
{
    return array_column(fetch_all('SELECT COLUMN_NAME,COLUMN_TYPE,IS_NULLABLE,COLUMN_DEFAULT,EXTRA FROM information_schema.columns WHERE table_schema=DATABASE() AND table_name=:t',['t'=>$table]),null,'COLUMN_NAME');
}

/** Validate every existing column before returning any additive ALTER; never MODIFY existing data. */
function release_audit_progress_plan(array $actual): array
{
    $sql=[];
    foreach(release_audit_progress_columns() as $column=>$expected){
        if(isset($actual[$column])){release_assert_column('site_audit_runs',$column,$expected,$actual[$column]);}
        else{$sql[]='ALTER TABLE site_audit_runs ADD COLUMN '.$column.' '.strtoupper($expected['COLUMN_TYPE'])." NOT NULL DEFAULT ".($column==='triggered_by'?"'admin'":'0');}
    }
    return $sql;
}

function release_apply_audit_progress(callable $inspect,callable $execute): void
{
    foreach(release_audit_progress_plan($inspect()) as $sql){$execute($sql);}
    if(release_audit_progress_plan($inspect())){throw new RuntimeException('Audit progress columns missing after ALTER.');}
}

/** Derive column and uniqueness postconditions only from the exact hash-pinned CREATE statements. */
function release_created_contracts(string $sql): array
{
    $contracts=[];
    preg_match_all('/CREATE TABLE IF NOT EXISTS ([a-z_]+)\s*\((.*?)\)\s*ENGINE=InnoDB\b/is',$sql,$tables,PREG_SET_ORDER);
    foreach($tables as $table){
        $columns=[];$unique=[];
        foreach(preg_split('/,(?![^(]*\))/',$table[2]) as $part){
            $part=trim($part);
            if(preg_match('/\A([a-z_]+)\s+((?:enum\([^)]*\)|(?:bigint|int|tinyint|varchar|char|longtext|text|datetime|timestamp|date)(?:\(\d+\))?)(?:\s+unsigned)?)(?:\s|$)/i',$part,$column)){
                $columns[$column[1]]=['COLUMN_TYPE'=>strtolower($column[2]),'IS_NULLABLE'=>preg_match('/\b(?:NOT NULL|PRIMARY KEY|AUTO_INCREMENT)\b/i',$part)?'NO':'YES'];
                if(stripos($part,'PRIMARY KEY')!==false){$unique['PRIMARY']=[$column[1]];}
            }elseif(preg_match('/\APRIMARY KEY\s*\(([^)]+)\)/i',$part,$index)){$unique['PRIMARY']=array_map('trim',explode(',',$index[1]));}
            elseif(preg_match('/\AUNIQUE KEY ([a-z_]+)\s*\(([^)]+)\)/i',$part,$index)){$unique[$index[1]]=array_map('trim',explode(',',$index[2]));}
            elseif(!preg_match('/\A(?:KEY|INDEX|CONSTRAINT)\b/i',$part)){throw new RuntimeException('Unsupported reviewed table definition.');}
        }
        if(!$columns){throw new RuntimeException('Empty reviewed table definition.');}
        $contracts[$table[1]]=compact('columns','unique');
    }
    return $contracts;
}

function release_check_created_tables(string $sql,bool $allowMissing): void
{
    foreach(release_created_contracts($sql) as $table=>$contract){
        if(!release_table($table)){
            if($allowMissing){continue;}
            throw new RuntimeException('Missing prerequisite table: '.$table);
        }
        $actual=release_columns($table);
        foreach($contract['columns'] as $column=>$expected){
            if(!isset($actual[$column])){throw new RuntimeException('Missing prerequisite column: '.$table.'.'.$column);}
            release_assert_column($table,$column,$expected,$actual[$column]);
        }
        $indexes=[];
        foreach(fetch_all('SHOW INDEX FROM `'.$table.'`') as $index){
            if((int)$index['Non_unique']===0){$indexes[$index['Key_name']][(int)$index['Seq_in_index']]=$index['Column_name'];}
        }
        foreach($contract['unique'] as $name=>$columns){
            $found=$indexes[$name]??[];ksort($found);
            if(array_values($found)!==$columns){throw new RuntimeException('Missing or incompatible unique index: '.$table.'.'.$name);}
        }
    }
}

function release_schema_preflight(array $pending): void
{
    foreach(['media_assets','destinations','itineraries','tours','day_tours','experiences','blog_posts','static_pages'] as $table){
        if(!release_table($table)){throw new RuntimeException('Missing prerequisite table: '.$table);}
    }
    $media=release_columns('media_assets');
    release_assert_column('media_assets','id',['COLUMN_TYPE'=>'bigint unsigned','IS_NULLABLE'=>'NO'],$media['id']??[]);
    foreach(release_reviewed_prerequisites() as $name=>$checksum){
        release_check_created_tables(file_get_contents(BASE_PATH.'/database/migrations/'.$name),isset($pending[$name]));
    }
    if(release_table('site_audit_runs')){
        $missing=release_audit_progress_plan(release_columns('site_audit_runs'));
        if($missing&&!isset($pending['2026-09-12-site-audit-progress.sql'])){throw new RuntimeException('Applied audit progress schema is incomplete; review baseline without rewriting history.');}
    }elseif(!isset($pending['2026-09-01-site-audit.sql'])){throw new RuntimeException('Missing site audit baseline table.');}
    release_backup_preflight();
}

// CLI rehearsals may include the backup helper without running the migration entry point.
if (realpath($_SERVER['SCRIPT_FILENAME']??'') !== __FILE__) { return; }
try {
    require_once __DIR__ . '/../config/bootstrap.php';
    ini_set('display_errors','0');
    $options = getopt('', ['plan','apply','backup-dir:']);
    if (isset($options['plan']) && isset($options['apply'])) { throw new RuntimeException('Choose --plan or --apply.'); }
    $apply = isset($options['apply']);
    if (DB_DRIVER !== 'mysql') { throw new RuntimeException('MySQL/MariaDB is required.'); }
    if (!release_table('schema_migrations')) { throw new RuntimeException('Existing migration ledger is required.'); }
    $key = release_column('schema_migrations','migration') ? 'migration' : 'version';
    $ledger = array_column(fetch_all('SELECT * FROM schema_migrations'), null, $key);
    $names = release_migration_names();
    $inspectProgress=static fn():array=>release_columns('site_audit_runs');
    $plan=release_migration_plan($ledger,glob(BASE_PATH.'/database/migrations/*.sql'),$names,$inspectProgress);
    ['pending'=>$pending,'historicalDrift'=>$historicalDrift,'historicalStatuses'=>$historicalStatuses]=$plan;
    echo 'Historical checksum differences preserved: '.$historicalDrift.PHP_EOL;
    echo 'Historical non-applied ledger rows preserved (outside this release): '.count($historicalStatuses).PHP_EOL;
    foreach($plan['verifiedProgressHistory'] as $name){echo 'Preserved historical checksum; audit progress schema verified: '.$name.PHP_EOL;}
    echo 'Pending release migrations: '.count($pending).PHP_EOL;
    foreach ($pending as $name=>$file) { echo '  '.$name.PHP_EOL; }
    release_schema_preflight($pending);
    if (!$apply) { echo "Plan only; no writes. Use --apply --backup-dir=/private/existing/directory\n"; exit; }
    if (!$pending) { echo "Nothing to migrate.\n"; exit; }
    $lock = 'ict-page-media-'.substr(hash('sha256',DB_NAME),0,24);
    if ((int)(fetch_one('SELECT GET_LOCK(:lock,0) AS acquired',['lock'=>$lock])['acquired']??0)!==1) { throw new RuntimeException('Another release migration is running.'); }
    try {
        // Recheck under the lock; never use a stale plan.
        $current = array_column(fetch_all('SELECT * FROM schema_migrations'),null,$key);
        if(release_migration_plan($current,glob(BASE_PATH.'/database/migrations/*.sql'),$names,$inspectProgress)!==$plan){throw new RuntimeException('Migration ledger changed; rerun the plan.');}
        release_schema_preflight($pending);
        umask(0077);
        release_backup((string)($options['backup-dir']??''));
        $batch = release_column('schema_migrations','batch') ? 1+(int)(fetch_one('SELECT MAX(batch) AS b FROM schema_migrations')['b']??0) : 1;
        foreach ($pending as $name=>$file) {
            $sql = file_get_contents($file); $started = microtime(true);
            if (preg_match('/^\s*DELIMITER\b/im',$sql)) { throw new RuntimeException('Unsupported DELIMITER migration.'); }
            if(isset(release_reviewed_prerequisites()[$name])){
                if(!hash_equals(release_reviewed_prerequisites()[$name],hash('sha256',str_replace("\r\n","\n",$sql)))){throw new RuntimeException('Reviewed prerequisite SQL changed.');}
                if(in_array($name,['2026-09-12-site-audit-progress.sql','2026-10-16-site-audit-portal.sql'],true)){
                    if(!release_table('site_audit_runs')){throw new RuntimeException('Missing site_audit_runs prerequisite.');}
                    release_apply_audit_progress(static fn():array=>release_columns('site_audit_runs'),static fn(string $ddl)=>db()->exec($ddl));
                    // The portal repeats two columns; the reviewed PHP guards cover all three.
                    $sql=$name==='2026-09-12-site-audit-progress.sql'?'':substr($sql,strpos($sql,'CREATE TABLE IF NOT EXISTS site_audit_snapshots'));
                }
            }
            if ($name === '2026-09-26-release0-policy-acceptance.sql') {
                // MySQL lacks ADD COLUMN IF NOT EXISTS. Execute equivalent per-column guards.
                preg_match("/'ALTER TABLE customer_itinerary_plans (.*?)',\\s*'SELECT 1'/s",$sql,$match);
                if (empty($match[1])) { throw new RuntimeException('Policy migration shape changed.'); }
                if (release_table('customer_itinerary_plans')) {
                    foreach (explode(', ADD COLUMN IF NOT EXISTS ',str_replace("''","'",$match[1])) as $definition) {
                        $definition = preg_replace('/^ADD COLUMN IF NOT EXISTS /','',$definition);
                        if (!preg_match('/^([a-z_]+) /',$definition,$column)) { throw new RuntimeException('Invalid policy column.'); }
                        if (!release_column('customer_itinerary_plans',$column[1])) { db()->exec('ALTER TABLE customer_itinerary_plans ADD COLUMN '.$definition); }
                        if (!release_column('customer_itinerary_plans',$column[1])) { throw new RuntimeException('Policy column missing after ALTER.'); }
                    }
                }
                $sql = substr($sql,strpos($sql,'CREATE TABLE IF NOT EXISTS customer_policy_acceptances'));
            }
            if(trim($sql)!==''){db()->exec($sql);} // DDL commits implicitly; never pretend a failed DDL was applied.
            if(isset(release_reviewed_prerequisites()[$name])){release_check_created_tables($sql,false);}
            if ($name === '2026-10-28-page-media-galleries.sql') {
                foreach (['blog_landing','page_gallery_images'] as $table) { if (!release_table($table)) { throw new RuntimeException('Missing '.$table); } }
                foreach (['destinations','itineraries','tours','day_tours','experiences','activity_pages','blog_posts','static_pages','blog_landing'] as $table) {
                    if (!release_table($table)) { throw new RuntimeException('Missing prerequisite table '.$table); }
                    foreach (['hero_image','hero_alt','cover_image','cover_alt','cover_media_asset_id','og_image','og_alt','hero_poster_alt'] as $column) {
                        if (!release_column($table,$column)) { throw new RuntimeException('Missing '.$table.'.'.$column); }
                    }
                }
                foreach (['intro','layout_style'] as $column) { if (!release_column('static_pages',$column)) { throw new RuntimeException('Missing static page field.'); } }
                if (!fetch_one('SELECT id FROM blog_landing WHERE id=1')) { throw new RuntimeException('Missing Blog landing singleton.'); }
            }
            $data = [$key=>$name];
            foreach (['checksum'=>hash('sha256',str_replace("\r\n","\n",file_get_contents($file))),'status'=>'applied','batch'=>$batch,'execution_time_ms'=>(int)((microtime(true)-$started)*1000)] as $column=>$value) {
                if (release_column('schema_migrations',$column)) { $data[$column]=$value; }
            }
            $columns = array_keys($data);
            execute_query('INSERT INTO schema_migrations (`'.implode('`,`',$columns).'`) VALUES (:'.implode(',:',$columns).')',$data);
            echo 'Applied: '.$name.PHP_EOL;
        }
        echo 'Release migration complete. No content consolidation performed.'.PHP_EOL;
    } finally { execute_query('SELECT RELEASE_LOCK(:lock)',['lock'=>$lock]); }
} catch (Throwable $error) {
    // Database errors can contain row values: keep details in owner-only server logs.
    error_log('Page-media migration: '.$error->getMessage());
    if($error instanceof PageMediaPlanBlocked){fwrite(STDERR,"Release plan blockers (no writes):\n".implode("\n",$error->reasons)."\n");}
    fwrite(STDERR,"Migration stopped; no failed migration was marked applied. Review the private PHP error log before retrying.\n");
    exit(1);
}
