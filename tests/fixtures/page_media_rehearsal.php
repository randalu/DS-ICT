<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
$root=dirname(__DIR__,2);
foreach([$root.'/output/session',$root.'/output/page-gallery-20261028'] as $directory){if(!is_dir($directory)&&!mkdir($directory,0700,true))throw new RuntimeException('Cannot prepare fixture output.');}
ini_set('session.save_path',$root.'/output/session');require_once $root.'/includes/page_media.php';
$manifest=$root.'/output/page-gallery-20261028/fixture.json';$mode=$argv[1]??'setup';
if($mode==='drop'){
    $fixture=json_decode(file_get_contents($manifest),true,32,JSON_THROW_ON_ERROR);$name=$fixture['database'];
    if(!preg_match('/^ict_page_media_test_[a-f0-9]{12}$/D',$name)||$name===DB_NAME)throw new RuntimeException('Refusing unexpected database.');
    db()->exec('DROP DATABASE '.$name);unlink($manifest);echo "Disposable page-media schema removed.\n";exit;
}
if(is_file($manifest))throw new RuntimeException('Inspect the existing synthetic schema manifest first.');
if(!preg_match('/^[a-zA-Z0-9_]+$/D',DB_NAME))throw new RuntimeException('Unexpected source schema name.');
$resume=$argv[2]??'';if($resume!==''&&!preg_match('/^ict_page_media_test_[a-f0-9]{12}$/D',$resume))throw new RuntimeException('Unexpected resume schema.');
$name=$resume?:'ict_page_media_test_'.bin2hex(random_bytes(6));$tables=db()->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
$port=(int)(getenv('ICT_PAGE_MEDIA_FIXTURE_PORT')?:57943);
if($port<1024||$port>65535)throw new RuntimeException('Invalid local fixture port.');
$baseline=[];foreach(['destinations','itineraries','tours','day_tours','experiences','activity_pages','blog_posts','static_pages','media_assets','destination_photos','experience_gallery','day_tour_gallery'] as $table)if(in_array($table,$tables,true))$baseline[$table]=hash('sha256',json_encode(fetch_all('SELECT * FROM '.$table.' ORDER BY id'),JSON_THROW_ON_ERROR));
db()->exec('CREATE DATABASE IF NOT EXISTS '.$name.' CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
try{
    foreach($tables as $table){if($table==='page_gallery_images')continue;if(!preg_match('/^[a-zA-Z0-9_]+$/D',$table))throw new RuntimeException('Unexpected table.');db()->exec('CREATE TABLE IF NOT EXISTS '.$name.'.'.$table.' LIKE '.DB_NAME.'.'.$table);}
    db()->exec('USE '.$name);
    $migration=file_get_contents($root.'/database/migrations/2026-10-28-page-media-galleries.sql');db()->exec($migration);db()->exec($migration);
    foreach(fetch_all('SELECT TABLE_NAME,COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE()') as $column){db_table_exists($column['TABLE_NAME'],true);db_column_exists($column['TABLE_NAME'],$column['COLUMN_NAME'],true);}
    execute_query("INSERT INTO admins(id,name,email,username,password_hash,is_active) VALUES(1,'Page media fixture','fixture@example.test','page-media-fixture',:hash,1)",['hash'=>password_hash(bin2hex(random_bytes(32)),PASSWORD_DEFAULT)]);
    $records=[];
    foreach(page_media_types() as $type=>[$table,$title]){
        $id=$type==='blog_landing'?1:101;
        if($type!=='blog_landing'){
            $values=['id'=>$id,$title=>'Synthetic '.str_replace('_',' ',$type),'slug'=>'page-media-fixture-'.$type,'is_published'=>1];
            foreach(fetch_all("SELECT COLUMN_NAME,DATA_TYPE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=:table AND IS_NULLABLE='NO' AND COLUMN_DEFAULT IS NULL AND EXTRA NOT LIKE '%auto_increment%'",['table'=>$table]) as $column){$key=$column['COLUMN_NAME'];if(!array_key_exists($key,$values))$values[$key]=in_array($column['DATA_TYPE'],['int','bigint','tinyint','smallint','decimal','float','double'],true)?0:'Fixture';}
            $cols=array_keys($values);execute_query("INSERT INTO $table (".implode(',',$cols).') VALUES (:'.implode(',:',$cols).')',$values);
        }
        $values=[];foreach(['intro'=>'<p>A synthetic introduction for local layout verification.</p>','content'=>'<h2>Travel information</h2><p>Fixture content for testing; original site content remains unchanged.</p>','excerpt'=>'Private Sri Lanka travel, arranged around your interests.','meta_title'=>'Synthetic '.$type.' | Island Culture Travels','meta_description'=>'Synthetic local page used to verify the approved gallery implementation.','hero_image'=>'assets/images/home-hero/home-1.jpg','hero_alt'=>'Sri Lanka landscape','cover_image'=>'assets/images/home-hero/home-2.jpg','cover_alt'=>'Tea-covered Sri Lanka hills','og_image'=>'assets/images/home-hero/home-3.jpg','og_alt'=>'Sri Lanka coast','faq_json'=>'[]'] as $field=>$value)if(db_column_exists($table,$field))$values[$field]=$value;
        $sets=[];foreach($values as $key=>$value)$sets[]="$key=:$key";execute_query("UPDATE $table SET ".implode(',',$sets).' WHERE id=:id',$values+['id'=>$id]);$records[$type]=$id;
    }
    $assets=[];for($i=1;$i<=6;$i++){execute_query("INSERT INTO media_assets(file_path,file_name,mime_type,file_size,alt_text) VALUES(:path,:name,'image/jpeg',100,:alt)",['path'=>'assets/images/home-hero/home-'.$i.'.jpg','name'=>'Fixture landscape '.$i,'alt'=>'Sri Lanka landscape photograph '.$i]);$assets[]=(int)db()->lastInsertId();}
    execute_query("INSERT INTO media_assets(file_path,file_name,mime_type,file_size,alt_text) VALUES('assets/images/country-market/matara-beach.jpg','Fixture portrait','image/jpeg',100,'Portrait beach photograph')");$assets[]=(int)db()->lastInsertId();
    foreach($records as $type=>$id){for($i=0;$i<5;$i++)page_media_save(['type'=>$type,'id'=>$id,'action'=>'add','source'=>'library','asset_id'=>$assets[$i],'description'=>'Sri Lanka landscape photograph '.($i+1),'revision'=>page_media_revision($type,page_media_record($type,$id))]);}
    if(file_put_contents($manifest,json_encode(['database'=>$name,'port'=>$port,'records'=>$records,'assets'=>$assets,'original_hashes'=>$baseline],JSON_PRETTY_PRINT|JSON_THROW_ON_ERROR))===false)throw new RuntimeException('Could not record fixture ownership.');
    echo "Synthetic schema prepared; migration ran twice successfully. No source rows copied.\n";
}catch(Throwable $e){db()->exec('USE '.DB_NAME);db()->exec('DROP DATABASE '.$name);throw $e;}
finally{db()->exec('USE '.DB_NAME);}
