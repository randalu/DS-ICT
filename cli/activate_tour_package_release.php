<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../includes/tour_consolidation.php';

/** Explicit release activation; the default mode is read-only. No historical migration replay. */
function tour_release_photo(array $photo, bool $create): int
{
    $path = $photo['path'];
    if (!is_string($path) || !str_starts_with($path, 'assets/images/') || str_contains($path, '..')) {
        throw new RuntimeException('Invalid release photograph path.');
    }
    $file = BASE_PATH . '/' . $path;
    if (!is_file($file) || !hash_equals($photo['sha256'], hash_file('sha256', $file)) || !getimagesize($file)) {
        throw new RuntimeException('Missing or changed release photograph: ' . $path);
    }
    $asset = fetch_one('SELECT id,mime_type FROM media_assets WHERE file_path=:path ORDER BY id LIMIT 1', ['path'=>$path]);
    if ($asset && !str_starts_with((string)$asset['mime_type'], 'image/')) { throw new RuntimeException('Photograph is registered with an incompatible media type.'); }
    if (!$asset && $create) {
        execute_query('INSERT INTO media_assets(file_path,file_name,mime_type,file_size,alt_text) VALUES(:path,:name,:mime,:size,:alt)',
            ['path'=>$path,'name'=>basename($path),'mime'=>(new finfo(FILEINFO_MIME_TYPE))->file($file),'size'=>filesize($file),'alt'=>$photo['alt']]);
        return (int)db()->lastInsertId();
    }
    return (int)($asset['id'] ?? 0);
}
function tour_release_record(string $table, string $slug): array
{
    if (!in_array($table,['tours','itineraries','day_tours','destinations','experiences'],true)) { throw new RuntimeException('Invalid collection.'); }
    $rows=fetch_all('SELECT * FROM `'.$table.'` WHERE slug=:slug',['slug'=>$slug]);
    if(count($rows)!==1){throw new RuntimeException('Expected one '.$table.' record for '.$slug);}
    return $rows[0];
}
function tour_release_expect(mixed $current, mixed $before, mixed $after, string $label): void
{
    $normalize=static fn($v)=>str_replace("\r\n","\n",(string)$v);
    if($normalize($current)!==$normalize($before)&&$normalize($current)!==$normalize($after)) {
        throw new RuntimeException('Live content differs from the reviewed preimage: '.$label.'. No content overwrite performed.');
    }
}
function tour_release_cards(array $cards, bool $create): array
{
    foreach($cards as &$card){
        foreach(['destination','experience'] as $kind){$slug=$card[$kind.'_slug'];unset($card[$kind.'_slug']);$card[$kind.'_id']=$slug?(int)tour_release_record($kind==='destination'?'destinations':'experiences',$slug)['id']:0;}
        $photo=$card['photo']??null;unset($card['photo']);$card['image_asset_id']=$photo?tour_release_photo($photo,$create):0;$card['image_alt']=$photo['alt']??'';
    }unset($card);
    return $cards;
}
function tour_release_preflight(array $plan): void
{
    foreach($plan['covers'] as $cover){
        $r=tour_release_record($cover['table'],$cover['slug']);tour_release_photo($cover['photo'],false);
        // Newly added columns are blank on live installations, even when the local
        // review fixture previously had a cover. Never replace a different live cover.
        if(trim((string)($r['cover_image']??''))!==''){
            tour_release_expect($r['cover_image'],$cover['before']['cover_image'],$cover['photo']['path'],$cover['slug'].' cover');
        }
        if(trim((string)($r['cover_alt']??''))!==''){
            tour_release_expect($r['cover_alt'],$cover['before']['cover_alt'],$cover['photo']['alt'],$cover['slug'].' cover description');
        }
    }
    foreach($plan['tours'] as $tour){
        $r=tour_release_record('tours',$tour['slug']);
        foreach($tour['fields'] as $field=>$v){
            if(!in_array($field,['excerpt','intro','highlights','itinerary','sample_itinerary','cta_title','cta_text'],true)){throw new RuntimeException('Protected Tour field.');}
            tour_release_expect($r[$field]??null,$v['before'],tour_consolidation_wording($v['after']),$tour['slug'].' '.$field);
        }
        $cards=tour_release_cards($tour['coverage'],false);
        $current=tour_coverage_decode($r['coverage_json']??null);
        if($current!=tour_coverage_decode($tour['coverage_before'])&&$current!=$cards){throw new RuntimeException('Live coverage differs from reviewed content: '.$tour['slug']);}
        if($tour['hero']){tour_release_photo($tour['hero'],false);tour_release_expect($r['hero_image']??null,$tour['hero_before'],$tour['hero']['path'],$tour['slug'].' hero');}
    }
    foreach(($plan['preserve_live_fields']??[]) as $slug=>$fields){
        tour_release_record('tours',$slug);
        foreach($fields as $field){if(!in_array($field,['itinerary','sample_itinerary','cta_text','hero_image'],true)){throw new RuntimeException('Invalid live-preservation field.');}}
        foreach($plan['tours'] as $tour){if($tour['slug']!==$slug){continue;}foreach($fields as $field){if(isset($tour['fields'][$field])||($field==='hero_image'&&!empty($tour['hero']))){throw new RuntimeException('A preserved live field must not also be replaced.');}}}
    }

    $old=tour_release_record('tours',$plan['retire_slug']);tour_release_expect($old['is_published'],$plan['retire_published_before'],0,'duplicate Safari publication');
    tour_release_record('tours',$plan['retained_slug']);
    foreach(['tours','itineraries','day_tours','destination_tour','media_assets','n_day_itinerary_pages','itinerary_package_links','itinerary_tour','static_pages','seo_intent_map'] as $table){
        if(db_table_exists($table)){ $status=fetch_one('SELECT ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=:name',['name'=>$table]);if(strcasecmp((string)$status['ENGINE'],'InnoDB')!==0){throw new RuntimeException('Transactional table required: '.$table);} }
    }
}
function tour_release_record_migration(string $name,string $sql): void
{
    if(!db_table_exists('schema_migrations')){throw new RuntimeException('Existing migration history required.');}
    $key=db_column_exists('schema_migrations','migration')?'migration':'version';
    if(fetch_one('SELECT `'.$key.'` FROM schema_migrations WHERE `'.$key.'`=:name',['name'=>$name])){return;}
    $row=[$key=>$name];
    if(db_column_exists('schema_migrations','checksum')){$row['checksum']=hash('sha256',$sql);}
    if(db_column_exists('schema_migrations','status')){$row['status']='applied';}
    if(db_column_exists('schema_migrations','batch')){$row['batch']=(int)(fetch_one('SELECT COALESCE(MAX(batch),0)+1 AS n FROM schema_migrations')['n']);}
    $keys=array_keys($row);db()->prepare('INSERT INTO schema_migrations (`'.implode('`,`',$keys).'`) VALUES ('.implode(',',array_fill(0,count($keys),'?')).')')->execute(array_values($row));
}

try{
    $args=getopt('',['check','apply','backup-dir:']);
    $plan=json_decode(file_get_contents(BASE_PATH.'/database/releases/2026-10-24-tour-packages.json'),true,512,JSON_THROW_ON_ERROR);
    tour_release_preflight($plan);
    if(!isset($args['apply'])){echo "CHECK PASSED: ".count($plan['covers'])." covers, two curated Tours, live edits preserved; source details will be transferred from this database. No writes.\n";exit;}
    $directory=(string)($args['backup-dir']??'');
    if($directory===''||!is_dir($directory)||!is_writable($directory)){throw new RuntimeException('Provide an existing private writable --backup-dir outside the website.');}
    $directory=realpath($directory);$website=realpath(BASE_PATH);
    $normalized=static fn($v)=>strtolower(str_replace('\\','/',rtrim($v,'/\\'))).'/';
    if(str_starts_with($normalized($directory),$normalized($website))){throw new RuntimeException('The backup directory must be outside the website.');}
    umask(0077);$backup=$directory.'/tour-release-before-'.date('Ymd-His').'-'.bin2hex(random_bytes(4)).'.json';
    $tables=['tours','itineraries','day_tours','destination_tour','media_assets','n_day_itinerary_pages','itinerary_package_links','itinerary_tour','static_pages','seo_intent_map','schema_migrations'];$snapshot=[];
    foreach($tables as $table){if(db_table_exists($table)){$snapshot[$table]=fetch_all('SELECT * FROM `'.$table.'` ORDER BY 1');}}
    $handle=fopen($backup,'x');if(!$handle){throw new RuntimeException('Cannot create private preimage.');}
    $json=json_encode($snapshot,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);$written=fwrite($handle,$json);fclose($handle);
    if($written!==strlen($json)){throw new RuntimeException('Incomplete preimage; activation stopped.');}
    foreach(['2026-10-24-tour-coverage.sql','2026-10-24-package-editor-media.sql','2026-10-24-tour-program.sql'] as $migration){
        $sql=file_get_contents(BASE_PATH.'/database/migrations/'.$migration);db()->exec($sql);tour_release_record_migration($migration,$sql);
    }
    db()->beginTransaction();
    try{
        foreach(['tours','itineraries','day_tours','destination_tour','media_assets'] as $table){fetch_all('SELECT * FROM `'.$table.'` FOR UPDATE');}
        tour_release_preflight($plan);
        foreach($plan['covers'] as $cover){$r=tour_release_record($cover['table'],$cover['slug']);$photo=$cover['photo'];$id=tour_release_photo($photo,true);execute_query('UPDATE `'.$cover['table'].'` SET cover_image=:path,cover_alt=:alt,cover_media_asset_id=:asset WHERE id=:id',['path'=>$photo['path'],'alt'=>$photo['alt'],'asset'=>$id,'id'=>$r['id']]);}
        foreach($plan['tours'] as $tour){
            $r=tour_release_record('tours',$tour['slug']);$fields=[];foreach($tour['fields'] as $key=>$v){$fields[$key]=tour_consolidation_wording($v['after']);}
            $cards=tour_coverage_normalize(tour_release_cards($tour['coverage'],true),'tour_coverage_resolve');$fields['coverage_json']=json_encode($cards,JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
            if($tour['hero']){tour_release_photo($tour['hero'],true);$fields['hero_image']=$tour['hero']['path'];}
            foreach($fields as $field=>$value){execute_query('UPDATE tours SET `'.$field.'`=:value WHERE id=:id',['value'=>$value,'id'=>$r['id']]);}
            foreach($cards as $card){if($card['destination_id']&&!fetch_one('SELECT id FROM destination_tour WHERE destination_id=:d AND tour_id=:t',['d'=>$card['destination_id'],'t'=>$r['id']])){execute_query('INSERT INTO destination_tour(destination_id,tour_id) VALUES(:d,:t)',['d'=>$card['destination_id'],'t'=>$r['id']]);}}
        }
        $old=tour_release_record('tours',$plan['retire_slug']);$retained=tour_release_record('tours',$plan['retained_slug']);
        foreach(fetch_all('SELECT destination_id FROM destination_tour WHERE tour_id=:id',['id'=>$old['id']]) as $link){if(!fetch_one('SELECT id FROM destination_tour WHERE destination_id=:d AND tour_id=:t',['d'=>$link['destination_id'],'t'=>$retained['id']])){execute_query('INSERT INTO destination_tour(destination_id,tour_id) VALUES(:d,:t)',['d'=>$link['destination_id'],'t'=>$retained['id']]);}}
        execute_query('UPDATE tours SET is_published=0 WHERE id=:id',['id'=>$old['id']]);db()->commit();
    }catch(Throwable $e){if(db()->inTransaction()){db()->rollBack();}throw $e;}
    $converted=tour_consolidation_activate($directory,$plan['preserve_live_fields']??[]);
    echo 'ACTIVATED: '.count($converted).' Tour programmes transferred. Private preimage: '.$backup.PHP_EOL;
    echo "Code, additive schema, curated content and reference retirement have separate recovery boundaries. Preserve this directory.\n";
}catch(Throwable $e){fwrite(STDERR,$e->getMessage().PHP_EOL);exit(1);}
