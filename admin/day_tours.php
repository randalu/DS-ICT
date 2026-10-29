<?php
declare(strict_types=1);
require_once __DIR__.'/../includes/auth.php';
require_once __DIR__.'/../includes/day_tour_admin.php';
admin_require_auth();
$adminPage='day_tours';$adminTitle='Day Tour Packages';
$page=max(1,(int)get_input('page',1));$q=mb_substr(trim((string)get_input('q','')),0,120);$rows=[];$more=false;$unavailable=!db_table_exists('day_tours');
if(!$unavailable){
    $columns=['id','name','slug'];foreach(['hero_image','is_published','duration_hours'] as $column){if(db_column_exists('day_tours',$column))$columns[]=$column;}
    $rows=fetch_all('SELECT '.implode(',',$columns).' FROM day_tours'.($q!==''?' WHERE name LIKE :q':'').' ORDER BY name,id LIMIT 26 OFFSET '.(($page-1)*25),$q!==''?['q'=>'%'.$q.'%']:[]);$more=count($rows)>25;$rows=array_slice($rows,0,25);
}
require __DIR__.'/_top.php';
?>
<div class="card card-soft"><div class="card-body">
<?php if($unavailable): ?><p class="alert alert-warning">Day Tour Packages are unavailable in this database. This editor does not install schema.</p>
<?php else: ?>
<div class="d-flex flex-wrap gap-3 justify-content-between mb-3"><form class="d-flex gap-2" method="get"><input aria-label="Search day tour packages" name="q" value="<?= e($q) ?>" type="search" class="form-control"><button class="btn btn-outline-dark">Search</button></form><a class="btn btn-success" href="<?= e(base_url('admin/day_tour_form.php')) ?>">Add Day Tour Package</a><a class="btn btn-outline-dark" href="<?= e(base_url('admin/image_manager.php?type=day_tour')) ?>">Open in Image Manager</a></div>
<div class="table-responsive"><table class="table align-middle"><thead><tr><th>Name</th><th>Slug</th><th>Duration</th><th>Status</th><th>Actions</th></tr></thead><tbody>
<?php foreach($rows as $row): ?><tr><td><?= e($row['name']) ?></td><td><?= e($row['slug']) ?></td><td><?= e((string)($row['duration_hours']??'')) ?> hours</td><td><?= !empty($row['is_published'])?'Published':'Draft' ?></td><td><div class="d-flex gap-2 flex-wrap"><a class="btn btn-sm btn-outline-dark" href="<?= e(base_url('admin/day_tour_form.php?id='.(int)$row['id'])) ?>">Edit</a><a class="btn btn-sm btn-outline-dark" href="<?= e(base_url('admin/image_manager.php?type=day_tour&id='.(int)$row['id'])) ?>">Images</a><?php if(!empty($row['is_published'])): ?><a class="btn btn-sm btn-outline-secondary" target="_blank" rel="noopener" href="<?= e(base_url('day-tours/'.$row['slug'])) ?>">View</a><?php endif; ?></div></td></tr><?php endforeach; ?>
<?php if(!$rows): ?><tr><td colspan="5">No day tour packages found.</td></tr><?php endif; ?></tbody></table></div>
<nav class="d-flex gap-3 mt-3" aria-label="Day tour pages"><?php if($page>1): ?><a href="?<?= e(http_build_query(['q'=>$q,'page'=>$page-1])) ?>">Previous</a><?php endif; ?><span>Page <?= $page ?></span><?php if($more): ?><a href="?<?= e(http_build_query(['q'=>$q,'page'=>$page+1])) ?>">Next</a><?php endif; ?></nav>
<?php endif; ?></div></div>
<?php require __DIR__.'/_bottom.php'; ?>
