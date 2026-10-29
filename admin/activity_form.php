<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/activity_pages.php';
admin_require_auth();
if (!db_table_exists('activity_pages') || !db_table_exists('activity_examples')) {
    flash_set('error', 'Apply the Activities migration before editing its pages.');
    redirect('admin/activities.php');
}
$id = max(0, (int) get_input('id', 0));
if (get_input('discard','')==='1' || (old_input('id',null)!==null && (int)old_input('id',0)!==$id)) { old_input_clear(); }
$record = $id ? (fetch_one('SELECT * FROM activity_pages WHERE id=:id', ['id'=>$id]) ?? null) : null;
if ($id && !$record) { flash_set('error','Activity page not found.'); redirect('admin/activities.php'); }
$isHub = !empty($record['is_hub']);
$examples = $id && !$isHub ? activity_examples($id) : [];
$formExamples = [];
foreach ($examples as $example) {
    $formExamples[] = [
        'id'=>(int)$example['id'],
        'title' => $example['title'],
        'destinations' => implode(', ', activity_json_list($example['destinations_json'])),
        'sources' => implode("\n", activity_json_list($example['source_urls_json'])),
        'last_verified_at' => (string) ($example['last_verified_at'] ?? ''),
    ];
}
while (count($formExamples) < 5) { $formExamples[] = ['title'=>'','destinations'=>'','sources'=>'','last_verified_at'=>'']; }
$oldExamples = old_input('examples', null);
if (is_array($oldExamples)) { $formExamples = array_slice(array_pad($oldExamples, 5, []), 0, 5); }
$sources = implode("\n", activity_json_list($record['research_sources_json'] ?? null));
$oldSources = old_input('research_sources', null);
if (is_string($oldSources)) { $sources = $oldSources; }
$adminTitle = $id ? ($isHub ? 'Edit Activities Hub' : 'Edit Activity') : 'Add Activity Category';
$adminPage = 'activities';
require __DIR__ . '/_top.php';
?>
<div class="d-flex flex-wrap gap-3 mb-3"><a href="<?= e(base_url('admin/activities.php')) ?>">← Activities</a>
<?php if ($id): ?><a href="<?= e(base_url('admin/image_manager.php?type=activity&id='.$id)) ?>">Manage images</a><a href="<?= e(base_url('admin/seo_manager.php?type=activity&id='.$id)) ?>">Edit SEO in SEO Manager</a><?php if (!empty($record['is_published'])): ?><a target="_blank" rel="noopener" href="<?= e(activity_page_url($record)) ?>">View public page</a><?php endif; ?><?php endif; ?>
</div>
<?php if ($isHub): ?><div class="alert alert-info">The hub is its own page record. It links only to published activity categories.</div><?php endif; ?>
<form method="post" action="<?= e(base_url('admin/activity_save.php')) ?>" data-activity-editor>
    <?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>">
    <input type="hidden" name="revision" value="<?= e((string)old_input('revision',activity_content_revision($id))) ?>">
    <input type="hidden" name="confirmed_removals" value="[]">
    <?php if($id): ?><p><a href="<?= e(base_url('admin/activity_form.php?id='.$id.'&discard=1')) ?>">Reload saved content and discard unsaved edits</a></p><?php endif; ?>
    <div class="row g-4"><div class="col-lg-8">
        <section class="card card-soft mb-4"><div class="card-body"><h2 class="h5">Public content</h2>
            <label class="form-label" for="activity-name">Page name</label><input class="form-control mb-3" id="activity-name" name="name" maxlength="180" required value="<?= e((string) old_input('name', $record['name'] ?? '')) ?>">
            <?php if (!$isHub): ?><label class="form-label" for="activity-slug">URL slug</label><input class="form-control mb-3" id="activity-slug" name="slug" maxlength="180" required pattern="[a-z0-9]+(?:-[a-z0-9]+)*" value="<?= e((string) old_input('slug', $record['slug'] ?? '')) ?>"><?php endif; ?>
            <label class="form-label" for="activity-excerpt">Short Sri Lanka highlight</label><textarea class="form-control" id="activity-excerpt" name="excerpt" rows="3" maxlength="900" required><?= e((string) old_input('excerpt', $record['excerpt'] ?? '')) ?></textarea>
        </div></section>
        <?php if (!$isHub): ?><section class="card card-soft mb-4"><div class="card-body"><h2 class="h5">Activity and destination examples</h2><p class="text-secondary">Keep each entry factual and concise. Enter destinations separated by commas. Add at least three researched examples before publishing.</p>
        <?php foreach ($formExamples as $index => $example): ?>
            <?php $entryId=(int)($example['id']??0); $photoCount=count(activity_gallery_images($entryId)); ?>
            <fieldset class="border rounded p-3 mb-3" data-activity-entry data-entry-id="<?= $entryId ?>" data-photo-count="<?= $photoCount ?>"><legend class="float-none w-auto px-2 small fw-semibold">Example <?= $index+1 ?></legend>
                <input type="hidden" name="examples[<?= $index ?>][id]" value="<?= $entryId ?>">
                <?php if($entryId): ?><div class="d-flex flex-wrap gap-2 mb-3"><a href="<?= e(base_url('admin/image_manager.php?type=activity&id='.$id.'#entry-'.$entryId)) ?>">Manage entry images (<?= $photoCount ?>/5)</a><button type="button" class="btn btn-sm btn-outline-dark" data-entry-move="up">Move up</button><button type="button" class="btn btn-sm btn-outline-dark" data-entry-move="down">Move down</button></div>
                <?php if(!$photoCount): ?><p class="small text-warning">Add at least one entry image before saving this category as published.</p><?php endif; ?>
                <label class="d-block mb-3"><input type="checkbox" name="examples[<?= $index ?>][remove]" value="1" <?= !empty($example['remove'])?'checked':'' ?>> Remove this entry<?= $photoCount?' and its image assignments (files stay in Media Library)':'' ?></label><?php endif; ?>
                <label class="form-label" for="example-title-<?= $index ?>">Activity</label><input class="form-control mb-2" id="example-title-<?= $index ?>" name="examples[<?= $index ?>][title]" maxlength="255" value="<?= e((string)($example['title']??'')) ?>">
                <label class="form-label" for="example-destinations-<?= $index ?>">Destinations</label><input class="form-control mb-2" id="example-destinations-<?= $index ?>" name="examples[<?= $index ?>][destinations]" value="<?= e((string)($example['destinations']??'')) ?>">
                <label class="form-label" for="example-sources-<?= $index ?>">Source URLs, one per line</label><textarea class="form-control mb-2" id="example-sources-<?= $index ?>" name="examples[<?= $index ?>][sources]" rows="2"><?= e((string)($example['sources']??'')) ?></textarea>
                <label class="form-label" for="example-verified-<?= $index ?>">Last verified</label><input class="form-control" type="date" id="example-verified-<?= $index ?>" name="examples[<?= $index ?>][last_verified_at]" value="<?= e((string)($example['last_verified_at']??'')) ?>">
            </fieldset>
        <?php endforeach; ?></div></section><?php endif; ?>
        <section class="card card-soft mb-4"><div class="card-body"><h2 class="h5">Editorial research</h2><label class="form-label" for="activity-sources">Category sources, one URL per line</label><textarea class="form-control mb-3" id="activity-sources" name="research_sources" rows="4"><?= e($sources) ?></textarea><label class="form-label" for="activity-verified">Last verified</label><input class="form-control" type="date" id="activity-verified" name="research_verified_at" value="<?= e((string)old_input('research_verified_at',$record['research_verified_at']??'')) ?>"></div></section>
    </div><div class="col-lg-4">
        <section class="card card-soft mb-4"><div class="card-body"><h2 class="h5">SEO</h2><p class="small text-secondary">Edit these fields here or use SEO Manager; each record gets its own canonical URL.</p>
            <label class="form-label" for="activity-meta-title">SEO title</label><input class="form-control mb-3" id="activity-meta-title" name="meta_title" maxlength="255" value="<?= e((string)old_input('meta_title',$record['meta_title']??'')) ?>">
            <label class="form-label" for="activity-meta-description">Meta description</label><textarea class="form-control" id="activity-meta-description" name="meta_description" rows="3" maxlength="320"><?= e((string)old_input('meta_description',$record['meta_description']??'')) ?></textarea>
        </div></section>
        <section class="card card-soft mb-4"><div class="card-body"><h2 class="h5">Publication</h2>
            <div class="form-check mb-3"><input class="form-check-input" id="activity-published" type="checkbox" name="is_published" value="1" <?= !empty(old_input('is_published', $record['is_published'] ?? 0)) ? 'checked' : '' ?>><label class="form-check-label" for="activity-published">Published</label></div>
            <label class="form-label" for="activity-sort-order">Sort order</label><input class="form-control mb-3" id="activity-sort-order" type="number" min="0" name="sort_order" value="<?= e((string)old_input('sort_order',$record['sort_order']??0)) ?>">
            <button class="btn btn-success w-100" type="submit">Save Activity</button>
        </div></section>
        <?php if ($id): ?><section class="card card-soft"><div class="card-body"><h2 class="h5">Images</h2><p class="small text-secondary">Select hero, card cover, and Open Graph images in Image Manager. Publishing requires all three plus descriptive cover alt text.</p><a class="btn btn-outline-dark w-100" href="<?= e(base_url('admin/image_manager.php?type=activity&id='.$id)) ?>">Open Image Manager</a></div></section><?php endif; ?>
    </div></div>
</form>
<script src="<?= e(base_url('assets/js/activity-editor.js').'?v='.filemtime(__DIR__.'/../assets/js/activity-editor.js')) ?>" defer></script>
<?php require_once __DIR__.'/../includes/page_media_admin.php';page_media_admin_assets();page_media_manager('activity',(int)$id); ?>
<?php require __DIR__ . '/_bottom.php'; ?>
