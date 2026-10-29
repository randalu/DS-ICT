<?php
require_once __DIR__.'/../includes/page_media.php';$landing=blog_landing_record();
?>
<section class="card card-soft mb-4"><div class="card-body"><h2 class="h4">Blog landing page</h2>
<?php if(!db_table_exists('blog_landing')): ?><p>Review and apply the page media migration to enable landing-page editing. Existing Blog widgets remain available.</p>
<?php else: ?><form method="post" action="<?= e(base_url('admin/blog_landing_save.php')) ?>"><?= csrf_field() ?><input type="hidden" name="revision" value="<?= e(page_media_revision('blog_landing',$landing)) ?>">
<?php foreach(['title'=>'Heading','intro'=>'Introduction','cta_title'=>'CTA heading','cta_text'=>'CTA text','meta_title'=>'SEO title','meta_description'=>'SEO description'] as $field=>$label): ?><label class="form-label d-block"><?= e($label) ?><?php if(in_array($field,['intro','cta_text','meta_description'],true)): ?><textarea class="form-control mb-3" rows="3" name="<?= e($field) ?>"><?= e((string)old_input($field,$landing[$field]??'')) ?></textarea><?php else: ?><input class="form-control mb-3" name="<?= e($field) ?>" value="<?= e((string)old_input($field,$landing[$field]??'')) ?>" maxlength="<?= $field==='title'?250:255 ?>" <?= $field==='title'?'required':'' ?>><?php endif; ?></label><?php endforeach; ?>
<button class="btn btn-success">Save Blog landing content</button></form>
<p class="mt-3"><a href="<?= e(base_url('admin/seo_manager.php?type=blog_landing&id=1')) ?>">SEO Manager</a> · <a href="<?= e(base_url('admin/image_manager.php?type=blog_landing&id=1')) ?>">Image Manager</a></p>
<?php require_once __DIR__.'/../includes/page_media_admin.php';page_media_admin_assets();page_media_manager('blog_landing',1);endif; ?></div></section>
