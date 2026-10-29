<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/indexnow.php';

admin_require_auth();

if (!is_post()) {
    redirect('admin/faqs.php');
}

guard_post_size();
require_csrf();
ensure_faq_entries_table();

$id = (int) post_input('id', 0);

if ($id <= 0) {
    flash_set('error', 'Invalid FAQ ID.');
    redirect('admin/faqs.php');
}

$record = fetch_one('SELECT id FROM faq_entries WHERE id = :id LIMIT 1', ['id' => $id]);
if (!$record) {
    flash_set('error', 'FAQ item not found.');
    redirect('admin/faqs.php');
}

execute_query("DELETE FROM faq_entries WHERE id = :id", ['id' => $id]);
indexnow_submit_urls([base_url('faq'), base_url('sitemap.xml')]);
flash_set('success', 'FAQ item deleted.');
redirect('admin/faqs.php');
