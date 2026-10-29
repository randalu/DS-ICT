<?php
declare(strict_types=1);
require_once __DIR__.'/../includes/auth.php';
require_once __DIR__.'/../includes/tour_program.php';
admin_require_auth();
if(!is_post()){redirect('admin/tours.php');}
guard_post_size();require_csrf();
$id=(int)post_input('program_tour_id',0);old_input_set($_POST);
try{
    if(post_input('program_ready','')!=='1'){throw new InvalidArgumentException('Programme controls did not initialise. No changes were saved.');}
    $revision=post_input('program_revision','');if(!is_string($revision)){throw new InvalidArgumentException('Invalid programme revision.');}
    tour_program_save($id,post_input('program',[]),$revision);
    old_input_clear();flash_set('success','Tour programme saved.');
}catch(Throwable $e){flash_set('error',$e->getMessage());}
redirect('admin/tour_program.php?id='.$id);
