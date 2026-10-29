<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
$root=dirname(__DIR__,2);require_once $root.'/includes/page_media.php';$manifest=$root.'/output/page-gallery-20261028/fixture.json';$fixture=json_decode(file_get_contents($manifest),true,32,JSON_THROW_ON_ERROR);$name=$fixture['database'];if(!preg_match('/^ict_page_media_test_[a-f0-9]{12}$/D',$name)||$name===DB_NAME)throw new RuntimeException('Unexpected synthetic schema.');
$original=DB_NAME;db()->exec('USE '.$name);$uploads=fetch_all("SELECT id,file_path FROM media_assets WHERE file_name IN ('fixture-landscape.jpg','fixture-logo.png') AND file_path LIKE 'image-manager/%'");
$removed=[];
foreach($uploads as $upload){
    db()->exec('USE '.$original);$shared=fetch_one('SELECT id FROM media_assets WHERE file_path=:path LIMIT 1',['path'=>$upload['file_path']])!==null;
    foreach(page_media_types() as [$table])foreach(['hero_image','cover_image','og_image','hero_video_poster'] as $field)if(db_column_exists($table,$field,true)&&fetch_one("SELECT id FROM $table WHERE $field=:path LIMIT 1",['path'=>$upload['file_path']]))$shared=true;
    if($shared)throw new RuntimeException('A synthetic upload is referenced outside the test schema; retained.');
    db()->exec('USE '.$name);execute_query('DELETE FROM page_gallery_images WHERE media_asset_id=:id',['id'=>$upload['id']]);execute_query('DELETE FROM media_assets WHERE id=:id',['id'=>$upload['id']]);delete_uploaded_media($upload['file_path']);$removed[]=$upload['file_path'];
}
db()->exec('USE '.$original);$preserved=[];foreach($fixture['original_hashes'] as $table=>$before){$after=hash('sha256',json_encode(fetch_all('SELECT * FROM '.$table.' ORDER BY id'),JSON_THROW_ON_ERROR));$preserved[$table]=hash_equals($before,$after);}
file_put_contents($root.'/output/page-gallery-20261028/cleanup.json',json_encode(['removed_synthetic_uploads'=>$removed,'source_rows_match_baseline'=>$preserved,'migration_present_in_original'=>db_table_exists('page_gallery_images',true)],JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR));
// Only the exact task-created database identified above is removed.
db()->exec('DROP DATABASE '.$name);unlink($manifest);echo "Synthetic database and owned upload files removed. Source row preservation recorded.\n";
