<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
$root=dirname(__DIR__,2);ini_set('session.save_path',$root.'/output/session');require_once $root.'/includes/auth.php';
$mode=$argv[1]??'';
if(!in_array($mode,['guest','method','csrf','oversized','foreign'],true))throw new InvalidArgumentException('Unknown probe.');
$fixture=json_decode(file_get_contents($root.'/output/activity-gallery-smoke/fixture.json'),true,32,JSON_THROW_ON_ERROR);
$_SESSION=[];
if($mode!=='guest'){
    // A new CLI-only test session; no browser session, credential, or key is returned.
    $admin=fetch_one('SELECT id,password_hash FROM admins WHERE is_active=1 ORDER BY id LIMIT 1');
    if(!$admin)throw new RuntimeException('An active local admin is required for authenticated guard probes.');
    $_SESSION[ADMIN_SESSION_KEY]=(int)$admin['id'];$_SESSION[ADMIN_CREDENTIAL_STAMP_KEY]=admin_credential_stamp((int)$admin['id'],$admin['password_hash']);unset($admin);
}
$_SERVER['REQUEST_METHOD']=$mode==='method'?'GET':'POST';$_SERVER['CONTENT_LENGTH']=$mode==='oversized'?(string)PHP_INT_MAX:'100';$_GET=[];
$_POST=['type'=>'activity','action'=>'activity_gallery_add','id'=>(string)$fixture['page'],'example_id'=>'-1','revision'=>'invalid','asset_id'=>(string)$fixture['assets'][0],'source'=>'library','alt_text'=>'Guard probe that must never save','_token'=>$mode==='csrf'?'invalid':csrf_token()];
register_shutdown_function(static function(){echo "\nHTTP:".http_response_code()."\n";});
require $root.'/admin/image_manager_api.php';
