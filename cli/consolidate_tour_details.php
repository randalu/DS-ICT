<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require_once __DIR__.'/../config/bootstrap.php';
require_once __DIR__.'/../includes/tour_consolidation.php';
$options=getopt('',['apply','backup:']);
try{
    if(!isset($options['apply'])){$plan=tour_consolidation_plan();echo json_encode(array_map(static fn($r)=>['id'=>$r['id'],'slug'=>$r['slug'],'days'=>count($r['program']['days']),'stays'=>count($r['program']['hotels']),'source_reference_days'=>$r['source_reference_days']],$plan),JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES).PHP_EOL;exit;}
    $backup=$options['backup']??'';if(!is_string($backup)||$backup===''||!is_dir(dirname($backup))){throw new InvalidArgumentException('Provide --backup with a new content-backup JSON path in an existing private directory.');}
    echo json_encode(tour_consolidation_apply($backup),JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES).PHP_EOL;
}catch(Throwable $e){fwrite(STDERR,$e->getMessage().PHP_EOL);exit(1);}
