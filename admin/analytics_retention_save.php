<?php
declare(strict_types=1);
require_once __DIR__.'/../includes/auth.php';
admin_require_auth();
if(!is_post()){redirect('admin/analytics_retention.php');}
guard_post_size();require_csrf();
$input=post_input('analytics_hold',[]);old_input_set(['analytics_hold'=>$input]);
try{
    if(!is_array($input)){throw new InvalidArgumentException('Use the listed hold fields.');}
    ict_analytics_hold_save($input,(int)admin_id());old_input_clear();
    flash_set('success','Hold review recorded. Disposal remains paused; no records were deleted.');
}catch(InvalidArgumentException $exception){flash_set('error',$exception->getMessage());}
catch(Throwable){flash_set('error','Could not record the hold. Disposal remains paused.');}
redirect('admin/analytics_retention.php');
