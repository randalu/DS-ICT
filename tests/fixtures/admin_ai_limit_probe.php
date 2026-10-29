<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require_once __DIR__.'/../../includes/admin_openai.php';
define('DB_NAME','synthetic_quota_probe');$hour=0;$day=0;$inserted=0;
function db_table_exists(string $table):bool{return true;}
function fetch_one(string $sql,array $params=[]):?array{global $hour,$day;if(str_contains($sql,'GET_LOCK'))return ['ok'=>1];if(str_contains($sql,'COUNT(*)'))return ['n'=>str_contains($sql,'INTERVAL 1 HOUR')?$hour:$day];return null;}
function execute_query(string $sql,array $params=[]):bool{global $inserted;if(str_starts_with($sql,'INSERT'))$inserted++;return true;}
function db():object{return new class{function lastInsertId():string{return '1';}};}
$checks=0;
foreach([[20,100,true],[199,999,true],[200,0,false],[0,1000,false]] as [$hour,$day,$allowed]){
    $accepted=true;try{admin_openai_begin('alt',bin2hex(random_bytes(16)),1);}catch(InvalidArgumentException){$accepted=false;}
    if($accepted!==$allowed)throw new RuntimeException('Quota boundary failed.');$checks++;
}
if($inserted!==2)throw new RuntimeException('Rejected requests were inserted.');
echo json_encode(['checks'=>$checks,'accepted_reservations'=>$inserted]).PHP_EOL;
