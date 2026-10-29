<?php
declare(strict_types=1);
require_once __DIR__.'/../includes/auth.php';
require_once __DIR__.'/../includes/day_tour_admin.php';
admin_require_auth();
$id=max(0,(int)get_input('id',0));$adminPage='day_tours';$adminTitle=$id?'Edit Day Tour Package':'Add Day Tour Package';
if(!db_table_exists('day_tours')){flash_set('error','Day Tour Packages are unavailable in this database.');redirect('admin/day_tours.php');}
try{$record=$id?day_tour_admin_record($id):[];}catch(InvalidArgumentException $error){flash_set('error',$error->getMessage());redirect('admin/day_tours.php');}
$defaults=['duration_hours'=>'8','start_time'=>'08:00','max_group_size'=>'6','vehicle_type'=>'Air-conditioned car/van','difficulty'=>'easy','price_currency'=>'USD','price_unit'=>'per person'];
$revision=day_tour_admin_revision($record);
require __DIR__.'/_top.php';
?>
<div class="d-flex gap-3 flex-wrap mb-3"><a href="<?= e(base_url('admin/day_tours.php')) ?>">← Day Tour Packages</a><?php if($id): ?><a href="<?= e(base_url('admin/image_manager.php?type=day_tour&id='.$id)) ?>">Edit all images in Image Manager</a><?php if(!empty($record['is_published'])): ?><a target="_blank" rel="noopener" href="<?= e(base_url('day-tours/'.$record['slug'])) ?>">View public page</a><?php endif; endif; ?></div>
<form method="post" action="<?= e(base_url('admin/day_tour_save.php')) ?>" enctype="multipart/form-data">
<?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>"><input type="hidden" name="revision" value="<?= e($revision) ?>"><input type="hidden" name="flags_present" value="1">
<div class="row g-4"><div class="col-lg-8">
<?php foreach(day_tour_admin_fields() as $group=>$fields): ?><section class="card card-soft mb-4"><div class="card-body"><h2 class="h5 mb-3"><?= e($group) ?></h2>
<?php foreach($fields as $field=>$definition): if(!db_column_exists('day_tours',$field))continue;[$label,$kind]=$definition;$value=(string)admin_form_value($record,$field,$defaults[$field]??''); ?>
<div class="mb-3"><label class="form-label" for="day-<?= e($field) ?>"><?= e($label) ?><?= $field==='name'?' *':'' ?></label>
<?php if(in_array($kind,['textarea','richtext','json'],true)): ?><textarea id="day-<?= e($field) ?>" name="<?= e($field) ?>" rows="<?= $kind==='richtext'?6:3 ?>" class="form-control<?= $kind==='richtext'?' quill-editor':'' ?>" <?= isset($definition[2])?'maxlength="'.(int)$definition[2].'"':'' ?>><?= e($value) ?></textarea>
<?php elseif($kind==='select'): ?><select id="day-<?= e($field) ?>" name="<?= e($field) ?>" class="form-select"><?php foreach(['easy','moderate','active'] as $option): ?><option value="<?= e($option) ?>" <?= $value===$option?'selected':'' ?>><?= e(ucfirst($option)) ?></option><?php endforeach; ?></select>
<?php else: ?><input id="day-<?= e($field) ?>" name="<?= e($field) ?>" type="<?= e($kind) ?>" value="<?= e($value) ?>" class="form-control" <?= $field==='name'?'required':'' ?> <?= isset($definition[2])?'maxlength="'.(int)$definition[2].'"':'' ?> <?= $kind==='number'?'step="'.e($definition[3]??'1').'" min="0"':'' ?>><?php endif; ?>
<?php if($kind==='json'): ?><p class="form-text">Keep valid JSON. Leave blank for no structured details.</p><?php endif; ?></div>
<?php endforeach; ?></div></section><?php endforeach; ?>
</div><div class="col-lg-4">
<section class="card card-soft mb-4"><div class="card-body"><h2 class="h5">Publish</h2><p class="small text-secondary">Saving a published day tour changes its public content immediately.</p>
<?php foreach(['is_featured'=>'Featured','is_published'=>'Published'] as $field=>$label): if(!db_column_exists('day_tours',$field))continue; ?><div class="form-check mb-2"><input class="form-check-input" id="day-<?= e($field) ?>" type="checkbox" name="<?= e($field) ?>" value="1" <?= admin_checkbox_value($record,$field)?'checked':'' ?>><label class="form-check-label" for="day-<?= e($field) ?>"><?= e($label) ?></label></div><?php endforeach; ?>
<?php if(db_column_exists('day_tours','sort_order')): ?><label for="day-sort">Sort order</label><input id="day-sort" class="form-control mb-3" name="sort_order" type="number" value="<?= e((string)admin_form_value($record,'sort_order',0)) ?>"><?php endif; ?>
<button type="submit" class="btn btn-success w-100"><?= $id?'Update Day Tour Package':'Create Day Tour Package' ?></button></div></section>
<section class="card card-soft mb-4"><div class="card-body"><?php $packageType='day_tour';require __DIR__.'/_package_cover_editor.php'; ?><h2 class="h5">Images</h2><p class="small text-secondary">Current images and gallery photos stay unchanged unless you select replacements. Use Image Manager for destination-filtered library selection and gallery photo replacement.</p>
<?php foreach(['hero_image'=>'Hero image','og_image'=>'OG image'] as $field=>$label): if(!db_column_exists('day_tours',$field))continue; ?><div class="mb-3"><label class="form-label" for="day-<?= e($field) ?>"><?= e($label) ?></label><input id="day-<?= e($field) ?>" type="file" name="<?= e($field) ?>" accept="image/jpeg,image/png,image/webp,image/avif,image/gif" class="form-control"><?php if(!empty($record[$field])): ?><img class="img-fluid rounded mt-2" loading="lazy" src="<?= e(stored_media_url($record[$field])) ?>" alt="<?= e($record['name'].' '.$label) ?>"><?php endif; ?></div><?php endforeach; ?>
<p class="form-text">Images: <?= round(image_manager_limit(false)/1048576,1) ?> MB maximum each; convert HEIC to JPG.</p><?php if($id): ?><a class="btn btn-outline-dark" href="<?= e(base_url('admin/image_manager.php?type=day_tour&id='.$id)) ?>">Open Image Manager</a><?php endif; ?></div></section>
<?php foreach(day_tour_admin_relations() as $key=>$definition): [$pivot,$foreign,$table,$title,$label]=$definition; ?><section class="card card-soft mb-4"><div class="card-body"><h2 class="h5"><?= e($label) ?></h2>
<?php if(!day_tour_admin_relation_available($definition)): ?><p class="text-secondary">These relationships are unavailable in this database.</p><?php else:
$selected=$id?array_map('intval',array_column(fetch_all("SELECT {$foreign} FROM {$pivot} WHERE day_tour_id=:id",['id'=>$id]),$foreign)):[];
$old=old_input($key,null);if(is_array($old))$selected=array_map('intval',$old);
$choices=fetch_all("SELECT id,{$title} AS name FROM {$table} ORDER BY {$title},id"); ?>
<input type="hidden" name="relations_present[<?= e($key) ?>]" value="1"><div style="max-height:280px;overflow:auto"><?php foreach($choices as $choice): ?><div class="form-check mb-2"><input class="form-check-input" id="day-<?= e($key) ?>-<?= (int)$choice['id'] ?>" name="<?= e($key) ?>[]" type="checkbox" value="<?= (int)$choice['id'] ?>" <?= in_array((int)$choice['id'],$selected,true)?'checked':'' ?>><label class="form-check-label" for="day-<?= e($key) ?>-<?= (int)$choice['id'] ?>"><?= e($choice['name']) ?></label></div><?php endforeach; ?></div>
<?php endif; ?></div></section><?php endforeach; ?>
</div></div></form>
<?php if ($id > 0): ?><p><a href="<?= e(base_url('admin/package_fields.php?type=day_tour&id=' . $id)) ?>">Review all package fields and editors</a> · <a href="<?= e(base_url('admin/day_tour_gallery.php?id=' . $id)) ?>">Edit gallery and order</a></p><?php
require_once __DIR__.'/../includes/route_map_admin.php';
route_map_admin_form('day_tour', $record, 'day_tour_form.php');
endif; require_once __DIR__.'/../includes/page_media_admin.php';page_media_admin_assets();page_media_manager('day_tour',(int)$id);
require __DIR__.'/_bottom.php'; ?>
