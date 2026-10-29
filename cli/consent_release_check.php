<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
if(array_diff(array_slice($argv,1),['--json'])){fwrite(STDERR,"Only --json is supported; this checker never configures, migrates or disposes data.\n");exit(2);}
require_once __DIR__.'/../includes/functions.php';
$pdo=db();$pdo->exec('SET TRANSACTION READ ONLY');$pdo->beginTransaction();
try{
    $settings=ict_privacy_settings();$report=ict_analytics_retention_report();
    $assets=json_decode(file_get_contents(__DIR__.'/../assets/vendor/manifest.json'),true,32,JSON_THROW_ON_ERROR);$assetErrors=[];
    foreach($assets['assets'] as $asset){$path=__DIR__.'/../'.$asset['path'];if(!is_file($path)||hash_file('sha256',$path)!==$asset['sha256']){$assetErrors[]=$asset['path'];}}
    $safe=!$settings['public_ai_search_enabled']&&!$settings['gtm_allowed']&&!$settings['chat_allowed']&&!$settings['review_invitations_allowed']&&ict_analytics_disposal_paused()&&!$report['apply_available'];
    $result=['mode'=>'read-only','safe_switches'=>$safe,'privacy_configured'=>$settings['configured'],'privacy_schema'=>$settings['schema_version'],'purpose_revision'=>$settings['purpose_revision'],'preference_days'=>$settings['preference_days'],
        'gtm_allowed'=>$settings['gtm_allowed'],'chat_allowed'=>$settings['chat_allowed'],'review_invitations_allowed'=>$settings['review_invitations_allowed'],'public_ai_search_enabled'=>$settings['public_ai_search_enabled'],
        'assets_checked'=>count($assets['assets']),'asset_errors'=>$assetErrors,'retention_dry_run'=>$report,'database_writes'=>false,'migration_run'=>false,'actual_disposal'=>false];
    echo json_encode($result,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR).PHP_EOL;
}finally{if($pdo->inTransaction()){$pdo->rollBack();}}
exit($safe&&!$assetErrors&&$report['candidate_count']!==null?0:1);
