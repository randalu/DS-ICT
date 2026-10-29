<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$root=dirname(__DIR__,2);
require $root.'/cli/seed_destination_map_locations.php';
require $root.'/config/database.php';
// Only an explicitly selected synthetic map fixture can be the source.
if (!preg_match('/\Aict_route_maps_20261025_[a-f0-9]{6}\z/',DB_NAME)) { throw new RuntimeException('Select the disposable map-fixture DB_NAME.'); }
$options=getopt('',['backup-dir:']);$backup=realpath($options['backup-dir']??'');
if (!$backup) { throw new RuntimeException('Provide a private backup directory outside the website.'); }
$source=DB_NAME;$name='ict_map_seed_20261025_'.bin2hex(random_bytes(3));$pdo=pdo();
$pdo->exec('CREATE DATABASE `'.$name.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
foreach (['tours','itineraries','itinerary_days','day_tours','destinations','media_assets','day_tour_gallery','site_settings','schema_migrations','map_locations','package_route_maps'] as $table) {
    if (!rm_release_columns($pdo,$table)) { continue; }
    $pdo->exec('CREATE TABLE `'.$name.'`.`'.$table.'` LIKE `'.$source.'`.`'.$table.'`');
    if (!in_array($table,['destinations','map_locations'],true)) { $pdo->exec('INSERT INTO `'.$name.'`.`'.$table.'` SELECT * FROM `'.$source.'`.`'.$table.'`'); }
}
$pdo->exec('USE `'.$name.'`');
$catalog=route_map_seed_catalog($root.'/assets/maps/destination-locations.json');
$insert=$pdo->prepare('INSERT INTO destinations(id,name,slug) VALUES(?,?,?)');
foreach($catalog['data']['places'] as $i=>$place) { $insert->execute([$i+1,$place['names'][0],$place['slug']]); }
$insert->execute([901,'Ussangoda','ussangoda']);$insert->execute([902,'Minneriya / Kaudulla','minneriya-kaudulla']);$insert->execute([903,'Unresearched place','unresearched-place']);
$checks=[];$expect=static function(string $label,bool $passed)use(&$checks):void { $checks[]=['check'=>$label,'passed'=>$passed];if(!$passed)throw new RuntimeException('Rehearsal failed: '.$label); };
$run=static function(array $args)use($root,$name,$backup):array {
    $environment=getenv();$environment['DB_NAME']=$name;
    $process=proc_open(array_merge([PHP_BINARY,'-d','session.save_path='.$backup,$root.'/cli/seed_destination_map_locations.php'],$args),[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,$root,$environment,['bypass_shell'=>true]);
    if(!is_resource($process))throw new RuntimeException('Cannot start rehearsal child.');
    fclose($pipes[0]);$stdout=stream_get_contents($pipes[1]);$stderr=stream_get_contents($pipes[2]);fclose($pipes[1]);fclose($pipes[2]);$code=proc_close($process);
    return ['code'=>$code,'data'=>json_decode($stdout,true),'stderr'=>$stderr];
};
$protected=rm_release_protected($pdo);$routes=rm_release_fingerprint($pdo,'package_route_maps','id');$history=rm_release_fingerprint($pdo,'schema_migrations',isset(rm_release_columns($pdo,'schema_migrations')['id'])?'id':'migration');
$preview=$run(['--dry-run']);
$expect('dry run reports every sourced destination and unmatched place',$preview['code']===0 && count($preview['data']['create'])===41 && count($preview['data']['skipped'])===3);
$expect('dry run creates no map rows',(int)$pdo->query('SELECT COUNT(*) FROM map_locations')->fetchColumn()===0);
$noPlan=$run(['--apply','--backup-dir='.$backup]);$expect('apply without reviewed plan is rejected',$noPlan['code']!==0);
$badBackup=$run(['--apply','--backup-dir='.$root,'--expected-plan='.$preview['data']['plan_sha256']]);$expect('backup inside public root is rejected',$badBackup['code']!==0);
$apply=$run(['--apply','--backup-dir='.$backup,'--expected-plan='.$preview['data']['plan_sha256']]);
if($apply['code']!==0)fwrite(STDERR,$apply['stderr']);
$expect('first apply creates all 41 sourced linked locations',$apply['code']===0 && $apply['data']['inserted']===41 && count($apply['data']['skipped'])===3);
$expect('created locations are verified active and unique by destination',(int)$pdo->query('SELECT COUNT(*) FROM map_locations WHERE is_verified=1 AND is_active=1 AND destination_id IS NOT NULL')->fetchColumn()===41 && (int)$pdo->query('SELECT COUNT(DISTINCT destination_id) FROM map_locations')->fetchColumn()===41);
$rows=$pdo->query('SELECT * FROM map_locations ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
$expect('coordinates and source attribution match the pinned catalog',array_reduce($rows,static function(bool $ok,array $row)use($preview):bool { $entry=array_values(array_filter($preview['data']['create'],static fn(array $e):bool=>$e['destination_id']===(int)$row['destination_id']))[0];return $ok && $row['latitude']===$entry['latitude'] && $row['longitude']===$entry['longitude'] && $row['coordinate_source']===$entry['coordinate_source']; },true));
$preview2=$run(['--dry-run']);$again=$run(['--apply','--backup-dir='.$backup,'--expected-plan='.$preview2['data']['plan_sha256']]);
$expect('rerun inserts no duplicates and preserves every existing location',$again['code']===0 && $again['data']['inserted']===0 && $again['data']['preserved']===41 && $rows===$pdo->query('SELECT * FROM map_locations ORDER BY id')->fetchAll(PDO::FETCH_ASSOC));
$expect('destinations packages settings routes and migration history preserved',rm_release_protected($pdo)===$protected && rm_release_fingerprint($pdo,'package_route_maps','id')===$routes && rm_release_fingerprint($pdo,'schema_migrations',isset(rm_release_columns($pdo,'schema_migrations')['id'])?'id':'migration')===$history);
$pdo->exec("UPDATE map_locations SET name='Owner location',is_verified=0,is_active=0,revision=9 WHERE destination_id=1");
$stale=$run(['--apply','--backup-dir='.$backup,'--expected-plan='.$preview2['data']['plan_sha256']]);
$expect('concurrent location edit rejects stale preview',$stale['code']!==0 && str_contains($stale['stderr'],'changed since the preview'));
$ownerBefore=$pdo->query('SELECT * FROM map_locations WHERE destination_id=1')->fetch(PDO::FETCH_ASSOC);$preview3=$run(['--dry-run']);$ownerRun=$run(['--apply','--backup-dir='.$backup,'--expected-plan='.$preview3['data']['plan_sha256']]);
$expect('owner custom inactive unverified location remains byte exact',$ownerRun['code']===0 && $ownerBefore===$pdo->query('SELECT * FROM map_locations WHERE destination_id=1')->fetch(PDO::FETCH_ASSOC));
$pdo->exec('DELETE FROM map_locations');
$pdo->exec("INSERT INTO map_locations(name,latitude,longitude,coordinate_source,is_verified,is_active) VALUES('Colombo',6.9,79.8,'Owner unlinked place',0,0)");
$unlinked=$run(['--dry-run']);$expect('unlinked owner location is reported without duplication',count($unlinked['data']['create'])===40 && count($unlinked['data']['skipped'])===4);
$unlinkedRows=$pdo->query('SELECT * FROM map_locations ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
$pdo->exec("CREATE TRIGGER route_seed_force_failure BEFORE INSERT ON map_locations FOR EACH ROW BEGIN IF NEW.name='Kandy' THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='Synthetic insertion failure'; END IF; END");
$failure=$run(['--apply','--backup-dir='.$backup,'--expected-plan='.$unlinked['data']['plan_sha256']]);
$expect('insertion failure rolls back all earlier inserted rows',$failure['code']!==0 && $unlinkedRows===$pdo->query('SELECT * FROM map_locations ORDER BY id')->fetchAll(PDO::FETCH_ASSOC));
$pdo->exec('DROP TRIGGER route_seed_force_failure');
$pdo->exec("UPDATE destinations SET name='Owner renamed destination' WHERE id=2");
$staleDestination=$run(['--apply','--backup-dir='.$backup,'--expected-plan='.$unlinked['data']['plan_sha256']]);
$expect('concurrent destination edit rejects stale preview',$staleDestination['code']!==0);
$changed=$run(['--dry-run']);$expect('renamed known slug requires review instead of guessing',count($changed['data']['create'])===39 && count($changed['data']['skipped'])===5);
$pdo->exec('ALTER TABLE map_locations DROP INDEX uq_map_location_destination');$badSchema=$run(['--dry-run']);
$expect('incompatible map schema is rejected',$badSchema['code']!==0);
$files=glob($backup.'/destination-map-before-*.json');
$expect('successful and failed applies have private verified preimages',count($files)===4);
foreach($files as$file){$snapshot=json_decode(file_get_contents($file),true,512,JSON_THROW_ON_ERROR);$expect('private backup readback '.basename($file),isset($snapshot['plan']['plan_sha256'],$snapshot['locations'],$snapshot['protected'],$snapshot['package_routes']));}
echo json_encode(['result'=>'DESTINATION_MAP_SEED_REHEARSAL_OK','database'=>$name,'count'=>count($checks),'checks'=>$checks],JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES).PHP_EOL;
