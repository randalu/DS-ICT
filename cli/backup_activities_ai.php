<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require_once __DIR__.'/../includes/functions.php';require_once __DIR__.'/../includes/schema.php';
$file=$argv[1]??'';$directory=realpath(dirname($file));$web=realpath(BASE_PATH);
if(!$directory||!$web||str_starts_with(strtolower($directory.DIRECTORY_SEPARATOR),strtolower($web.DIRECTORY_SEPARATOR))||is_file($file)){throw new RuntimeException('Choose a new private backup file outside the webroot.');}
$tables=['activity_pages','activity_examples','activity_example_images','media_assets','media_asset_alt_revisions','media_placement_alts','admin_ai_requests','seo_requests','schema_migrations'];
$backup=['format'=>'island-activities-ai-v1','created_at'=>gmdate(DATE_ATOM),'tables'=>[]];
db()->exec('SET TRANSACTION ISOLATION LEVEL REPEATABLE READ');db()->beginTransaction();
try{foreach($tables as$table){if(!db_table_exists($table))continue;$create=fetch_one('SHOW CREATE TABLE `'.$table.'`');$backup['tables'][$table]=['schema'=>array_values($create)[1],'rows'=>fetch_all('SELECT * FROM `'.$table.'`')];}db()->commit();}catch(Throwable$e){if(db()->inTransaction())db()->rollBack();throw$e;}
$old=umask(0077);$handle=fopen($file,'x');if(!$handle)throw new RuntimeException('Could not create private backup.');
try{$json=json_encode($backup,JSON_THROW_ON_ERROR|JSON_INVALID_UTF8_SUBSTITUTE);if(fwrite($handle,$json)!==strlen($json))throw new RuntimeException('Incomplete backup.');}finally{fclose($handle);umask($old);}
if(!is_array(json_decode(file_get_contents($file),true,512,JSON_THROW_ON_ERROR)))throw new RuntimeException('Backup verification failed.');
echo 'PRIVATE_BACKUP_OK '.basename($file).' SHA256 '.hash_file('sha256',$file)."\n";
