<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$root=dirname(__DIR__,2);
require $root.'/cli/activate_route_maps.php';
require $root.'/config/database.php';
if (!preg_match('/\Aict_route_maps_20261025_[a-f0-9]{6}\z/',DB_NAME)) { throw new RuntimeException('Run only with the disposable map-fixture DB_NAME.'); }
$options=getopt('',['backup-dir:']);
$backup=realpath($options['backup-dir']??'');
if (!$backup) { throw new RuntimeException('Provide a private backup directory outside the website.'); }
$source=DB_NAME;$name='ict_map_activation_20261025_'.bin2hex(random_bytes(3));
$pdo=pdo();
$pdo->exec('CREATE DATABASE `'.$name.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
foreach (['tours','itineraries','itinerary_days','day_tours','destinations','media_assets','day_tour_gallery','site_settings','schema_migrations'] as $table) {
    if (!rm_release_columns($pdo,$table)) { continue; }
    $pdo->exec('CREATE TABLE `'.$name.'`.`'.$table.'` LIKE `'.$source.'`.`'.$table.'`');
    if ($table!=='schema_migrations') { $pdo->exec('INSERT INTO `'.$name.'`.`'.$table.'` SELECT * FROM `'.$source.'`.`'.$table.'`'); }
}
$pdo->exec('USE `'.$name.'`');
$checks=[];
$expect=static function(string $label,bool $value)use(&$checks):void { $checks[]=['check'=>$label,'passed'=>$value];if(!$value)throw new RuntimeException('Rehearsal failed: '.$label); };
$run=static function(array $args)use($root,$name,$backup):array {
    $environment=getenv();$environment['DB_NAME']=$name;
    $command=array_merge([PHP_BINARY,'-d','session.save_path='.$backup,$root.'/cli/activate_route_maps.php'],$args);
    $process=proc_open($command,[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,$root,$environment,['bypass_shell'=>true]);
    if(!is_resource($process))throw new RuntimeException('Could not run activation rehearsal child.');
    fclose($pipes[0]);$stdout=stream_get_contents($pipes[1]);$stderr=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);
    return ['code'=>proc_close($process),'stdout'=>$stdout,'stderr'=>$stderr];
};
$protected=rm_release_protected($pdo);
$first=$run(['--check']);
$expect('read-only preflight succeeds',$first['code']===0 && str_contains($first['stdout'],'ROUTE_MAP_PREFLIGHT_OK'));
$expect('preflight creates no map schema',!rm_release_columns($pdo,'map_locations') && !rm_release_columns($pdo,'package_route_maps'));
$apply=$run(['--apply','--backup-dir='.$backup]);
if($apply['code']!==0)fwrite(STDERR,$apply['stderr']);
$expect('first activation succeeds',$apply['code']===0 && str_contains($apply['stdout'],'ROUTE_MAP_MIGRATION_OK'));
foreach(array_keys(rm_release_expected())as$table)rm_release_validate_table($pdo,$table,true);
$expect('first activation creates no application rows',(int)$pdo->query('SELECT COUNT(*) FROM map_locations')->fetchColumn()===0 && (int)$pdo->query('SELECT COUNT(*) FROM package_route_maps')->fetchColumn()===0);
$checksum=hash_file('sha256',$root.'/database/migrations/'.RM_RELEASE_MIGRATION);
$expect('migration recorded with exact checksum',rm_release_history($pdo,$checksum)['row']!==null);
$expect('existing package/settings/media data preserved',rm_release_protected($pdo)===$protected);
$pdo->exec("INSERT INTO map_locations(name,latitude,longitude,coordinate_source,is_verified) VALUES('Sentinel Colombo',6.93548,79.84868,'Synthetic verified fixture',1)");
$pdo->exec("INSERT INTO package_route_maps(package_type,package_id,route_json) VALUES('itinerary',901,'{\"sentinel\":true}')");
$before=[$pdo->query('SELECT * FROM map_locations')->fetchAll(PDO::FETCH_ASSOC),$pdo->query('SELECT * FROM package_route_maps')->fetchAll(PDO::FETCH_ASSOC)];
$again=$run(['--apply','--backup-dir='.$backup]);
$expect('rerun succeeds without duplicate migration history',$again['code']===0 && str_contains($again['stdout'],'"already_recorded":true'));
$after=[$pdo->query('SELECT * FROM map_locations')->fetchAll(PDO::FETCH_ASSOC),$pdo->query('SELECT * FROM package_route_maps')->fetchAll(PDO::FETCH_ASSOC)];
$expect('rerun preserves existing map rows exactly',$before===$after);
$expect('rerun preserves other package/settings/media data',rm_release_protected($pdo)===$protected);
$inside=$run(['--apply','--backup-dir='.$root]);
$expect('public-root backup directory rejected',$inside['code']!==0 && str_contains($inside['stderr'],'outside the website root'));
$pdo->prepare('UPDATE schema_migrations SET checksum=? WHERE migration=?')->execute([str_repeat('0',64),RM_RELEASE_MIGRATION]);
$wrong=$run(['--check']);
$expect('mismatched migration checksum rejected',$wrong['code']!==0 && str_contains($wrong['stderr'],'checksum differs'));
$pdo->prepare('UPDATE schema_migrations SET checksum=? WHERE migration=?')->execute([$checksum,RM_RELEASE_MIGRATION]);
$pdo->exec('ALTER TABLE package_route_maps DROP INDEX uq_package_route_map');
$bad=$run(['--check']);
$expect('incompatible existing map schema rejected',$bad['code']!==0 && str_contains($bad['stderr'],'definitions need review'));
$pdo->exec('ALTER TABLE package_route_maps ADD UNIQUE KEY uq_package_route_map(package_type,package_id)');
$pdo->exec('RENAME TABLE schema_migrations TO route_map_rehearsal_history');
$pdo->exec('CREATE TABLE schema_migrations(version VARCHAR(255) PRIMARY KEY) ENGINE=InnoDB');
$legacy=$run(['--apply','--backup-dir='.$backup]);
$expect('legacy version-only migration history supported',$legacy['code']===0 && (int)$pdo->query('SELECT COUNT(*) FROM schema_migrations')->fetchColumn()===1);
$expect('legacy activation preserves sentinel map data',$before===[$pdo->query('SELECT * FROM map_locations')->fetchAll(PDO::FETCH_ASSOC),$pdo->query('SELECT * FROM package_route_maps')->fetchAll(PDO::FETCH_ASSOC)]);
$files=glob($backup.'/route-maps-before-*.json');
$expect('private backups and results exist',count($files)===3 && count(glob($backup.'/route-maps-result-*.json'))===3);
foreach($files as$file){$snapshot=json_decode(file_get_contents($file),true,512,JSON_THROW_ON_ERROR);$expect('backup readback '.basename($file),$snapshot['checksum']===$checksum && $snapshot['protected']===$protected);}
echo json_encode(['database'=>$name,'checks'=>$checks,'count'=>count($checks),'result'=>'ACTIVATION_REHEARSAL_OK'],JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES).PHP_EOL;
