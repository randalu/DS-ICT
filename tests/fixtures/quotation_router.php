<?php
declare(strict_types=1);
// Explicit synthetic-only loopback router. Not usable on a deployed server.
if (PHP_SAPI !== 'cli-server' || getenv('DB_NAME') !== 'ict_quotation_rehearsal_20261023' || !in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1','::1'], true)) { http_response_code(404); exit; }
$root=dirname(__DIR__,2);$path=parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH);
header('X-Robots-Tag: noindex, nofollow');
if ($path === '/favicon.ico') { http_response_code(204); return; }
if ($path === '/__quotation_customer_fixture') {
    require_once $root.'/includes/customer_auth.php';$ids=json_decode((string)file_get_contents($root.'/output/quotation/customer-demo.json'),true);$role=($_GET['role']??'owner')==='other'?'other':'owner';$_SESSION[CUSTOMER_SESSION_KEY]=$ids[$role];
    header('Location: /account/plans/'.$ids['plan']);return;
}
if(preg_match('~^/account/plans/([0-9]+)/?$~',$path,$match)){$_GET['id']=$match[1];require $root.'/account_plan.php';return;}
if(preg_match('~^/account/plans/([0-9]+)/policy-accept$~',$path,$match)){$_GET['plan_id']=$match[1];require $root.'/account_plan_policy_accept.php';return;}
if($path==='/account_quotation_pdf.php'){return false;}
if($path==='/__quotation_fixture'){
    require_once $root.'/includes/auth.php';$admin=fetch_one('SELECT id,password_hash FROM admins WHERE id=101');session_regenerate_id(true);$_SESSION[ADMIN_SESSION_KEY]=101;$_SESSION[ADMIN_CREDENTIAL_STAMP_KEY]=admin_credential_stamp(101,$admin['password_hash']);
    header('Location: /admin/quotations.php');return;
}
if (preg_match('~^/admin/(?:quotation[a-z_]*|inquiries|customer_plan_booking_status_save|login|logout|media_picker_api)\.php$~',$path) || preg_match('~^/assets/(?:css|js|vendor|fonts|images)/[a-zA-Z0-9/_.-]+$~',$path)) { return false; }
http_response_code(404);echo 'Not available in the isolated quotation fixture.';
