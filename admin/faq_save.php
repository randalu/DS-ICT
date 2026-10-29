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

$data = [
    'question' => trim((string) post_input('question', '')),
    'answer' => trim((string) post_input('answer', '')),
    'sort_order' => (int) post_input('sort_order', 0),
    'is_published' => post_input('is_published') ? 1 : 0,
];

old_input_set($_POST);

$errors = [];
if ($data['question'] === '') {
    $errors[] = 'Question is required.';
}

if ($data['answer'] === '') {
    $errors[] = 'Answer is required.';
}

if ($errors) {
    flash_set('error', implode(' ', $errors));
    redirect($id > 0 ? 'admin/faq_form.php?id=' . $id : 'admin/faq_form.php');
}

$record = [];
if ($id > 0) {
    $record = fetch_one("SELECT id FROM faq_entries WHERE id = :id LIMIT 1", ['id' => $id]) ?: [];
    if (!$record) {
        flash_set('error', 'FAQ item not found.');
        redirect('admin/faqs.php');
    }
}

if ($id > 0) {
    execute_query("
        UPDATE faq_entries SET
            question = :question,
            answer = :answer,
            sort_order = :sort_order,
            is_published = :is_published
        WHERE id = :id
    ", array_merge($data, ['id' => $id]));
} else {
    execute_query("
        INSERT INTO faq_entries (
            question, answer, sort_order, is_published
        ) VALUES (
            :question, :answer, :sort_order, :is_published
        )
    ", $data);
    $id = (int) last_insert_id();
}

old_input_clear();
indexnow_submit_urls([base_url('faq'), base_url('sitemap.xml')]);
flash_set('success', 'FAQ item saved successfully.');
redirect('admin/faqs.php');
