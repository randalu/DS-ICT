<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/activity_pages.php';
admin_require_auth();
if (!is_post()) { header('Allow: POST'); http_response_code(405); exit('Use POST to save an activity page.'); }
guard_post_size();
require_csrf();
$id = max(0, (int) post_input('id', 0));
$old = [];
foreach (['id','revision','confirmed_removals','name','slug','excerpt','meta_title','meta_description','research_sources','research_verified_at','sort_order'] as $key) {
    $value = post_input($key, null);
    if (is_string($value) || is_numeric($value)) { $old[$key] = (string)$value; }
}
if (isset($_POST['examples']) && is_array($_POST['examples'])) { $old['examples'] = $_POST['examples']; }
if (!empty($_POST['is_published'])) { $old['is_published'] = '1'; }
old_input_set($old);
try {
    $id = activity_admin_save($_POST);
    old_input_clear();
    flash_set('success', 'Activity page saved.');
    redirect('admin/activity_form.php?id='.$id);
} catch (InvalidArgumentException|DomainException $error) {
    flash_set('error', $error->getMessage());
} catch (Throwable $error) {
    error_log('Activity save: '.$error->getMessage());
    flash_set('error', 'The activity page could not be saved. Existing content was rolled back.');
}
redirect($id ? 'admin/activity_form.php?id='.$id : 'admin/activity_form.php');
