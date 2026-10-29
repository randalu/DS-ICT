<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require_once __DIR__.'/../includes/functions.php';
require_once __DIR__.'/../includes/schema.php';
$apply=in_array('--apply',$argv,true);
if(!$apply&&!in_array('--check',$argv,true)){fwrite(STDERR,"Use --check or --apply. Only release migrations are allowed.\n");exit(2);}
$files=['2026-10-25-activities.sql','2026-10-25-activity-entry-galleries.sql','2026-10-25-shared-admin-openai.sql','2026-10-25-image-placement-alt.sql'];
$schemas=[];$migrationTables=[];$requiredIndexes=[];$requiredForeign=[];
foreach($files as$name){
    $sql=file_get_contents(__DIR__.'/../database/migrations/'.$name);
    preg_match_all('/CREATE TABLE IF NOT EXISTS\s+(\w+)\s*\((.*?)\)\s*ENGINE/is', $sql,$tables,PREG_SET_ORDER);
    foreach($tables as$table){
        $migrationTables[$name][]=$table[1];
        preg_match_all('/(?:UNIQUE\s+KEY|(?<!FOREIGN )KEY)\s+(\w+)\s*\(/i',$table[2],$indexes);
        $requiredIndexes[$table[1]]=array_merge(['PRIMARY'],$indexes[1]);
        preg_match_all('/CONSTRAINT\s+(\w+)\s+FOREIGN KEY\s*\((\w+)\)\s*REFERENCES\s+(\w+)\s*\((\w+)\)\s*ON DELETE\s+(CASCADE|RESTRICT)/is',$table[2],$foreign,PREG_SET_ORDER);
        $requiredForeign[$table[1]]=$foreign;
        preg_match_all('/^\s*(\w+)\s+((?:BIGINT|INT|TINYINT|VARCHAR|CHAR|TEXT|LONGTEXT|DATETIME|TIMESTAMP|DATE)(?:\([\d,]+\))?(?:\s+UNSIGNED)?)/im',$table[2],$columns,PREG_SET_ORDER);
        $schemas[$table[1]]=[];foreach($columns as$column){$schemas[$table[1]][$column[1]]=strtolower(preg_replace('/\s+/',' ',$column[2]));}
    }
}
// Fail before any DDL if existing tables are incomplete or incompatible.
foreach($schemas as$table=>$columns){
    if(!db_table_exists($table))continue;
    $status=fetch_one('SHOW TABLE STATUS WHERE Name=:name',['name'=>$table]);
    if(strtolower((string)($status['Engine']??''))!=='innodb'){throw new RuntimeException('Nontransactional release table: '.$table);}
    $actual=array_column(fetch_all('SHOW COLUMNS FROM `'.$table.'`'),'Type','Field');
    foreach($columns as$column=>$type){
        $current=strtolower((string)($actual[$column]??''));
        $normalize=static fn(string$v)=>preg_replace('/\b(bigint|int|tinyint)\(\d+\)/','$1',$v);
        if($normalize($current)!==$normalize($type)){throw new RuntimeException('Incompatible schema: '.$table.'.'.$column);}
    }
    $indexRows=fetch_all('SHOW INDEX FROM `'.$table.'`');$indexes=array_unique(array_column($indexRows,'Key_name'));
    foreach($requiredIndexes[$table]as$index){if(!in_array($index,$indexes,true))throw new RuntimeException('Missing release index: '.$table.'.'.$index);}
    foreach($requiredForeign[$table]as$foreign){
        $found=fetch_one('SELECT k.COLUMN_NAME,k.REFERENCED_TABLE_NAME,k.REFERENCED_COLUMN_NAME,r.DELETE_RULE FROM information_schema.KEY_COLUMN_USAGE k JOIN information_schema.REFERENTIAL_CONSTRAINTS r ON r.CONSTRAINT_SCHEMA=k.CONSTRAINT_SCHEMA AND r.CONSTRAINT_NAME=k.CONSTRAINT_NAME AND r.TABLE_NAME=k.TABLE_NAME WHERE k.TABLE_SCHEMA=DATABASE() AND k.TABLE_NAME=:table AND k.CONSTRAINT_NAME=:name',['table'=>$table,'name'=>$foreign[1]]);
        if(!$found || $found['COLUMN_NAME']!==$foreign[2] || $found['REFERENCED_TABLE_NAME']!==$foreign[3] || $found['REFERENCED_COLUMN_NAME']!==$foreign[4] || strtoupper($found['DELETE_RULE'])!==strtoupper($foreign[5]))throw new RuntimeException('Incompatible release foreign key: '.$table.'.'.$foreign[1]);
    }
}
if(!db_table_exists('media_assets')||!db_column_exists('media_assets','alt_text')){throw new RuntimeException('Existing media library schema is required.');}
$mediaId=fetch_one("SHOW COLUMNS FROM media_assets LIKE 'id'");
if(preg_replace('/bigint\(\d+\)/','bigint',strtolower((string)($mediaId['Type']??'')))!=='bigint unsigned'){throw new RuntimeException('Review media asset ID compatibility before applying the Activity gallery foreign key.');}
$ledger=db_table_exists('schema_migrations');
$version=$ledger?(db_column_exists('schema_migrations','migration')?'migration':'version'):'migration';
if($ledger&&!db_column_exists('schema_migrations',$version)){throw new RuntimeException('Unknown migration ledger layout.');}
foreach($files as$name){
    $sql=file_get_contents(__DIR__.'/../database/migrations/'.$name);$hash=hash('sha256',$sql);
    $row=$ledger?fetch_one('SELECT * FROM schema_migrations WHERE `'.$version.'`=:name',['name'=>$name]):null;
    if($row && isset($row['checksum'])&&!hash_equals((string)$row['checksum'],$hash)){throw new RuntimeException('Migration checksum differs: '.$name);}
    if($row){foreach($migrationTables[$name]as$table){if(!db_table_exists($table))throw new RuntimeException('Recorded migration has a missing table: '.$table);}}
    echo ($row?'ALREADY_RECORDED ':($apply?'APPLY ':'PENDING ')).$name."\n";
    if(!$apply||$row)continue;
    if(!$ledger){db()->exec("CREATE TABLE schema_migrations(migration VARCHAR(255) PRIMARY KEY,checksum CHAR(64) NOT NULL,status VARCHAR(20) NOT NULL DEFAULT 'applied',batch INT UNSIGNED NOT NULL DEFAULT 1,applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");$ledger=true;}
    db()->exec($sql);
    $values=[$version=>$name];
    foreach(['checksum'=>$hash,'status'=>'applied','batch'=>1]as$column=>$value){if(db_column_exists('schema_migrations',$column))$values[$column]=$value;}
    $columns=array_keys($values);
    execute_query('INSERT INTO schema_migrations (`'.implode('`,`',$columns).'`) VALUES (:'.implode(',:',$columns).')',$values);
}
echo $apply?"RELEASE_SCHEMA_APPLIED\n":"RELEASE_SCHEMA_PREFLIGHT_OK\n";
