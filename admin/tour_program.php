<?php
declare(strict_types=1);
require_once __DIR__.'/../includes/auth.php';
require_once __DIR__.'/../includes/tour_program.php';
admin_require_auth();
$id=(int)get_input('id',0);$record=fetch_one('SELECT * FROM tours WHERE id=:id',['id'=>$id]);
if(!$record){http_response_code(404);exit('Tour not found.');}
$adminPage='tours';$adminTitle='Route, days and stays: '.$record['name'];
$available=db_column_exists('tours','program_json');
$program=tour_program_decode($record['program_json']??null);
$revision=tour_coverage_revision($record['program_json']??null);
if((int)old_input('program_tour_id',0)===$id&&get_input('saved','')!=='1'){
    $entered=old_input('program',null);if(is_array($entered)){$program=$entered;$revision=(string)old_input('program_revision','');}
}
require __DIR__.'/_top.php';
?>
<p><a href="<?= e(base_url('admin/tour_form.php?id='.$id)) ?>">Tour overview and experiences</a> · <a href="<?= e(base_url('admin/image_manager.php?type=tour&id='.$id)) ?>">Manage all Tour photographs</a></p>
<p>These details belong to this Tour and appear on its own public page. Accommodation is proposed or optional until confirmed in the traveller’s quote. Add actual hotel names only when verified.</p>
<?php if(!$available): ?><div class="alert alert-info">Apply the Tour programme migration before editing these details.</div><?php else: ?>
<form method="post" action="<?= e(base_url('admin/tour_program_save.php')) ?>" id="tour-program-editor" data-library-url="<?= e(base_url('admin/image_manager_api.php?action=library')) ?>">
<?= csrf_field() ?><input type="hidden" name="program_tour_id" value="<?= $id ?>"><input type="hidden" name="program_revision" value="<?= e($revision) ?>">
<div class="card card-soft mb-4"><div class="card-body"><label class="form-label" for="program-route">Route plan</label><textarea class="form-control" id="program-route" name="program[route]" rows="5" maxlength="12000"><?= e((string)($program['route']??'')) ?></textarea><div class="form-text">Describe the ordered locations, route alternatives and travel flow.</div></div></div>
<?php foreach(['days'=>'Daily plan and highlights','hotels'=>'Hotels and accommodation','sections'=>'Detailed travel information'] as $group=>$heading): ?>
<section class="card card-soft mb-4" data-program-group="<?= $group ?>"><div class="card-body"><h2 class="h4"><?= e($heading) ?></h2><div data-program-rows></div><button type="button" class="btn btn-outline-dark" data-program-add hidden>Add <?= $group==='days'?'day':($group==='hotels'?'stay':'section') ?></button></div></section>
<?php endforeach; ?>
<script type="application/json" data-program-data><?= json_encode($program,JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_INVALID_UTF8_SUBSTITUTE) ?></script>
<p class="text-secondary" role="status" data-program-status></p>
<noscript><p class="alert alert-warning">Enable JavaScript to edit programme rows. Existing content is preserved if row controls do not initialise.</p></noscript>
<button class="btn btn-primary" type="submit">Save Tour programme</button> <a class="btn btn-outline-dark" href="<?= e(base_url('admin/tour_program.php?id='.$id.'&saved=1')) ?>">Reload saved programme</a>
</form>
<script src="<?= e(base_url('assets/js/tour-program-editor.js').'?v='.filemtime(__DIR__.'/../assets/js/tour-program-editor.js')) ?>" defer></script>
<?php endif;
require_once __DIR__.'/../includes/route_map_admin.php';
route_map_admin_form('tour', $record, 'tour_program.php');
require __DIR__.'/_bottom.php'; ?>
