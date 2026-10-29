<?php
declare(strict_types=1);
require_once __DIR__.'/../includes/auth.php';
require_once __DIR__.'/../includes/day_tour_admin.php';
admin_require_auth();
if(!is_post()){header('Allow: POST');http_response_code(405);exit('Use POST to save a day tour.');}
guard_post_size();require_csrf();
$id=max(0,(int)post_input('id',0));
$old=[];foreach($_POST as $key=>$value){if(is_string($value)||(in_array($key,['destinations','itineraries','experiences','relations_present'],true)&&is_array($value))){$old[$key]=$value;}}old_input_set($old);
try {
    foreach(array_keys($_FILES) as $key){if(!in_array($key,['hero_image','og_image','cover_image'],true))throw new InvalidArgumentException('Unsupported upload field.');foreach(['name','tmp_name','error','size'] as $part){if(!is_scalar($_FILES[$key][$part]??null))throw new InvalidArgumentException('Choose one file per image.');}}
    $id=day_tour_admin_save($_POST,$_FILES);old_input_clear();flash_set('success','Day Tour Package saved successfully.');redirect('admin/day_tour_form.php?id='.$id);
} catch(InvalidArgumentException|DomainException $error){flash_set('error',$error->getMessage());}
catch(Throwable $error){error_log('Day tour save: '.$error->getMessage());flash_set('error','The day tour could not be saved. Existing content and images are unchanged.');}
redirect($id?'admin/day_tour_form.php?id='.$id:'admin/day_tour_form.php');
