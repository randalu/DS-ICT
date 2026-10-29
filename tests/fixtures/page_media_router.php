<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli-server'||!in_array($_SERVER['REMOTE_ADDR']??'',['127.0.0.1','::1'],true)){http_response_code(404);exit;}
$root=dirname(__DIR__,2);$path=parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH);
if(str_starts_with((string)$path,'/assets/')&&!str_contains((string)$path,'..')){
    $base=realpath($root.'/assets');$file=realpath($root.$path);if($file&&str_starts_with($file,$base.DIRECTORY_SEPARATOR)&&is_file($file)&&!preg_match('/\.(php|phtml|phar)$/i',$file))return false;
    http_response_code(404);exit;
}
require __DIR__.'/page_media_bootstrap.php';
session_name('ict_page_media_fixture');require_once $root.'/includes/auth.php';require_once $root.'/includes/page_gallery_view.php';
header('X-Robots-Tag: noindex,nofollow');$recordPageView=false;
if($path==='/__fixture-admin'){
    $admin=fetch_one('SELECT id,password_hash FROM admins WHERE id=1');$_SESSION[ADMIN_SESSION_KEY]=1;$_SESSION[ADMIN_CREDENTIAL_STAMP_KEY]=admin_credential_stamp(1,$admin['password_hash']);
    header('Location: /admin/image_manager.php?type=blog&id=101');exit;
}
if(($_GET['guest']??'')==='1'){unset($_SESSION[ADMIN_SESSION_KEY],$_SESSION[ADMIN_CREDENTIAL_STAMP_KEY]);}
if($path==='/__gallery-demo'){
    $count=max(0,min(8,(int)($_GET['count']??5)));$photos=page_gallery_rows('blog',101);$rows=[];for($i=0;$i<$count;$i++)$rows[]=$photos[$i%count($photos)];
    $bodyClass='page-has-gallery';$seoTitle='Synthetic gallery layout';$extraStylesheets=['assets/css/page-gallery.css'];require $root.'/includes/header.php';
    echo '<section class="page-compact-title"><div class="container"><p>Local gallery verification</p><h1>Sri Lanka private travel gallery</h1></div></section>';page_gallery_assets();page_gallery_public($rows,'Sri Lanka private travel');require $root.'/includes/footer.php';exit;
}
$admin=['/admin/image_alt_api.php','/admin/image_manager.php','/admin/image_manager_api.php','/admin/page_media_api.php','/admin/seo_manager.php','/admin/seo_manager_api.php','/admin/blog_settings.php','/admin/blog_landing_save.php','/admin/page_form.php','/admin/page_save.php','/admin/blog_post_form.php','/admin/experience_form.php','/admin/itinerary_form.php','/admin/tour_form.php','/admin/day_tour_form.php','/admin/activity_form.php','/admin/destination_form.php','/admin/media_asset_delete.php'];
if(in_array($path,$admin,true)){require $root.$path;exit;}
if($path==='/blog'){require $root.'/blog.php';exit;}
if($path==='/sitemap-images.xml'){$_GET['type']='images';require $root.'/sitemap_partial.php';exit;}
foreach(page_media_types() as $type=>[$table,,$editor,$prefix]){if($type==='blog_landing')continue;$record=page_media_record($type,101);if($path!==parse_url(page_media_public_url($type,$record),PHP_URL_PATH))continue;$_GET['slug']=$record['slug'];$_GET['id']=101;$file=match($type){'page'=>'static_page.php','blog'=>'blog_post.php','day_tour'=>'day-tour.php',default=>$type.'.php'};require $root.'/'.$file;exit;}
http_response_code(404);echo 'Fixture route not found.';
