<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
$root=dirname(__DIR__,2);ini_set('session.save_path',$root.'/output/session');
require_once $root.'/includes/activity_pages.php';
require_once $root.'/includes/image_manager.php';
$out=getenv('ACTIVITY_GALLERY_REHEARSAL_DIR')?:$root.'/output/activity-gallery-smoke';$manifest=$out.'/fixture.json';
$mode=$argv[1]??'setup';
function ag_rehearsal_hashes():array {
    $hashes=[];foreach(['activity_pages','activity_examples','activity_example_images','media_assets'] as $table){$hashes[$table]=hash('sha256',json_encode(fetch_all('SELECT * FROM '.$table.' ORDER BY id'),JSON_THROW_ON_ERROR));}return $hashes;
}
function ag_rehearsal_foreign_hashes(array $fixture,array $owned):array {
    $entryIds=implode(',',array_map('intval',$fixture['entries']));$assetIds=implode(',',array_map('intval',$owned));
    $filters=['activity_pages'=>'id<>'.(int)$fixture['page'],'activity_examples'=>'activity_page_id<>'.(int)$fixture['page'],'activity_example_images'=>'activity_example_id NOT IN ('.$entryIds.')','media_assets'=>'id NOT IN ('.$assetIds.')'];
    $hashes=[];foreach($filters as $table=>$where){$hashes[$table]=hash('sha256',json_encode(fetch_all('SELECT * FROM '.$table.' WHERE '.$where.' ORDER BY id'),JSON_THROW_ON_ERROR));}return $hashes;
}
function ag_rehearsal_asset_in_use(int $asset):bool {
    if(activity_gallery_reference_count($asset)>0)return true;
    foreach([['itineraries','cover_media_asset_id'],['tours','cover_media_asset_id'],['day_tours','cover_media_asset_id'],['destinations','hero_media_asset_id'],['itineraries','hero_media_asset_id'],['experiences','hero_media_asset_id'],['blog_posts','hero_media_asset_id'],['static_pages','hero_media_asset_id'],['homepage_hero_media','media_asset_id'],['social_media_post_assets','asset_id']] as [$table,$column]){
        if(db_table_exists($table)&&db_column_exists($table,$column)&&(int)(fetch_one('SELECT COUNT(*) AS total FROM '.$table.' WHERE '.$column.'=:id',['id'=>$asset])['total']??0)>0)return true;
    }
    require_once __DIR__.'/../../includes/tour_coverage.php';require_once __DIR__.'/../../includes/tour_program.php';
    return tour_coverage_media_reference_count($asset)>0||tour_program_media_reference_count($asset)>0;
}
if($mode==='setup'){
    if(is_file($manifest))throw new RuntimeException('A fixture manifest already exists; inspect or clean it first.');
    if(!activity_gallery_available())throw new RuntimeException('Apply the gallery migration first.');
    if(!is_dir($out))mkdir($out,0775,true);
    $baseline=ag_rehearsal_hashes();$originalAssets=array_map(static fn($r)=>(int)$r['id'],fetch_all('SELECT id FROM media_assets'));
    $slug='gallery-smoke-'.bin2hex(random_bytes(5));db()->beginTransaction();
    try{
        execute_query("INSERT INTO activity_pages(name,slug,excerpt,meta_title,meta_description,hero_image,cover_image,cover_alt,og_image,research_sources_json,research_verified_at) VALUES('Entry gallery layout rehearsal',:slug,'Temporary synthetic content for testing gallery layouts. Existing guest activity content is preserved.','Entry gallery local rehearsal','Synthetic gallery fixtures for local browser verification.','assets/images/home-hero/home-5.jpg','assets/images/home-hero/home-5.jpg','Landscape for local verification','assets/images/home-hero/home-5.jpg','[\"https://example.test\"]','2026-10-25')",['slug'=>$slug]);$page=(int)db()->lastInsertId();
        $paths=['assets/images/country-market/matara-beach.jpg'];for($i=1;$i<=6;$i++)$paths[]='assets/images/home-hero/home-'.$i.'.jpg';
        $assets=[];foreach($paths as $index=>$path){execute_query("INSERT INTO media_assets(file_path,file_name,mime_type,file_size,alt_text) VALUES(:path,:name,'image/jpeg',:size,:alt)",['path'=>$path,'name'=>'Activity gallery rehearsal image '.$index,'size'=>filesize($root.'/'.$path),'alt'=>'Landscape photograph for local gallery verification']);$assets[]=(int)db()->lastInsertId();}
        $entries=[];
        foreach([0,1,2,5,4] as $index=>$count){
            execute_query("INSERT INTO activity_examples(activity_page_id,title,destinations_json,source_urls_json,last_verified_at,sort_order) VALUES(:page,:title,'[\"Kandy\",\"Galle\"]','[\"https://example.test\"]','2026-10-25',:sort)",['page'=>$page,'title'=>($count===0?'Existing text-only entry':$count.' image entry').($count===5?' with a long descriptive title for responsive layout validation':''),'sort'=>$index]);$entry=(int)db()->lastInsertId();$entries[]=$entry;
            for($photo=0;$photo<$count;$photo++){activity_gallery_save(['type'=>'activity','id'=>$page,'example_id'=>$entry,'action'=>'activity_gallery_add','source'=>'library','asset_id'=>$assets[$photo],'alt_text'=>$photo===0?'Portrait beach photograph for gallery verification':'Landscape photograph number '.($photo+1),'revision'=>activity_gallery_revision($entry)]);}
        }
        db()->commit();
        file_put_contents($manifest,json_encode(['page'=>$page,'slug'=>$slug,'entries'=>$entries,'assets'=>$assets,'original_assets'=>$originalAssets,'baseline'=>$baseline],JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR));
        echo json_encode(['page'=>$page,'slug'=>$slug,'entries'=>$entries,'counts'=>[0,1,2,5,4]],JSON_PRETTY_PRINT).PHP_EOL;
    }catch(Throwable $error){if(db()->inTransaction())db()->rollBack();throw $error;}
    exit;
}
if(!is_file($manifest))throw new RuntimeException('Fixture manifest not found.');
$fixture=json_decode(file_get_contents($manifest),true,32,JSON_THROW_ON_ERROR);
$page=fetch_one('SELECT id,slug,is_published FROM activity_pages WHERE id=:id',['id'=>$fixture['page']]);
if(!$page||$page['slug']!==$fixture['slug']||!str_starts_with($page['slug'],'gallery-smoke-'))throw new RuntimeException('Fixture identity differs; refusing.');
if($mode==='race-prepare'){
    $entry=$fixture['entries'][4];if(count(activity_gallery_images($entry))!==4)throw new RuntimeException('Race fixture must start with four images.');
    $input=['type'=>'activity','id'=>$fixture['page'],'example_id'=>$entry,'action'=>'activity_gallery_add','source'=>'library','alt_text'=>'Concurrent gallery assignment validation','revision'=>activity_gallery_revision($entry)];
    foreach([1=>5,2=>6] as $worker=>$assetIndex){file_put_contents($out.'/race-'.$worker.'.json',json_encode($input+['asset_id'=>$fixture['assets'][$assetIndex]],JSON_THROW_ON_ERROR));}
    echo "Two requests prepared with the same gallery revision.\n";exit;
}
if($mode==='race-worker'){
    $worker=(int)($argv[2]??0);if(!in_array($worker,[1,2],true))throw new InvalidArgumentException('Worker must be one or two.');
    $input=json_decode(file_get_contents($out.'/race-'.$worker.'.json'),true,16,JSON_THROW_ON_ERROR);
    try{activity_gallery_save($input);echo json_encode(['worker'=>$worker,'result'=>'saved']);}catch(DomainException){echo json_encode(['worker'=>$worker,'result'=>'stale_rejected']);}exit;
}
if($mode==='cleanup'){
    $linked=activity_category_images((int)$fixture['page']);$owned=$fixture['assets'];$uploaded=[];
    foreach($linked as $photo){$asset=(int)$photo['media_asset_id'];if(!in_array($asset,$fixture['original_assets'],true)&&!in_array($asset,$owned,true)){$owned[]=$asset;$uploaded[$asset]=$photo['file_path'];}}
    $foreignBefore=ag_rehearsal_foreign_hashes($fixture,$owned);$ownedBefore=[];
    foreach($owned as $asset){$ownedBefore[$asset]=fetch_one('SELECT * FROM media_assets WHERE id=:id',['id'=>$asset]);}
    $retained=[];db()->beginTransaction();
    try{
        execute_query('DELETE FROM activity_pages WHERE id=:id AND slug=:slug',['id'=>$fixture['page'],'slug'=>$fixture['slug']]);
        foreach($owned as $asset){if(ag_rehearsal_asset_in_use($asset)){$retained[]=$asset;continue;}execute_query('DELETE FROM media_assets WHERE id=:id',['id'=>$asset]);}
        db()->commit();
    }catch(Throwable $error){if(db()->inTransaction())db()->rollBack();throw $error;}
    foreach($uploaded as $asset=>$path){if(!in_array($asset,$retained,true)&&str_starts_with($path,'image-manager/'))delete_uploaded_media($path);}
    $retainedUnchanged=true;foreach($retained as $asset){if($ownedBefore[$asset]!==fetch_one('SELECT * FROM media_assets WHERE id=:id',['id'=>$asset])){$retainedUnchanged=false;}}
    $after=ag_rehearsal_hashes();$result=['fixture_removed'=>true,'original_rows_unchanged'=>$after===$fixture['baseline'],'current_non_fixture_rows_preserved'=>$foreignBefore===ag_rehearsal_foreign_hashes($fixture,$owned),'retained_asset_ids'=>$retained,'retained_assets_unchanged'=>$retainedUnchanged,'after_hashes'=>$after];
    file_put_contents($out.'/cleanup.json',json_encode($result,JSON_PRETTY_PRINT));rename($manifest,$out.'/completed-fixture.json');echo json_encode($result,JSON_PRETTY_PRINT).PHP_EOL;exit;
}
throw new InvalidArgumentException('Unknown rehearsal action.');
