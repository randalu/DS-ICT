<?php
declare(strict_types=1);
require_once __DIR__.'/../includes/auth.php';require_once __DIR__.'/../includes/page_media.php';
admin_require_auth();if(!is_post())redirect('admin/blog_settings.php');guard_post_size();require_csrf();old_input_set($_POST);
try{page_media_transaction(function(){
    $record=page_media_record('blog_landing',1,true);if(!is_string($_POST['revision']??null)||!hash_equals(page_media_revision('blog_landing',$record),$_POST['revision']))throw new DomainException('Landing page changed. Reload and compare before saving.');
    $values=[];foreach(['title'=>250,'intro'=>30000,'cta_title'=>255,'cta_text'=>30000,'meta_title'=>255,'meta_description'=>320] as $key=>$limit){$value=$_POST[$key]??null;if(!is_string($value)||!mb_check_encoding($value,'UTF-8')||mb_strlen($value)>$limit||str_contains($value,"\0")||strip_tags($value)!==$value)throw new InvalidArgumentException('Use plain text within the field length limits.');$values[$key]=trim($value);}
    if($values['title']==='')throw new InvalidArgumentException('Heading is required.');$sets=[];foreach($values as $key=>$value)$sets[]="$key=:$key";execute_query('UPDATE blog_landing SET '.implode(',',$sets).' WHERE id=1',$values);
});old_input_clear();flash_set('success','Blog landing content saved.');}catch(InvalidArgumentException|DomainException $e){flash_set('error',$e->getMessage());}catch(Throwable $e){error_log('Blog landing: '.$e->getMessage());flash_set('error','Could not save Blog landing content.');}redirect('admin/blog_settings.php');
