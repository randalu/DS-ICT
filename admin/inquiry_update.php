<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

admin_require_auth();

if (!is_post()) {
    redirect('admin/inquiries.php');
}

guard_post_size();
require_csrf();
ensure_contact_inquiries_extended_schema();

$returnTo = trim((string) post_input('return_to', ''));
$safeReturnTo = (
    $returnTo !== ''
    && str_starts_with($returnTo, 'admin/inquiries.php')
    && !preg_match('/[\r\n]/', $returnTo)
) ? $returnTo : 'admin/inquiries.php';

if (!db_table_exists('contact_inquiries')) {
    flash_set('error', 'Inquiries are not available in the current database.');
    redirect($safeReturnTo);
}

$id = (int) post_input('id', 0);
if ($id <= 0) {
    flash_set('error', 'Invalid inquiry selected.');
    redirect('admin/inquiries.php');
}

$record = fetch_one('SELECT * FROM contact_inquiries WHERE id = :id LIMIT 1', ['id' => $id]);
if (!$record) {
    flash_set('error', 'Inquiry not found.');
    redirect('admin/inquiries.php');
}

$statusOptions = ['new', 'follow_up', 'quoted', 'won', 'lost', 'spam'];
$newStatus = strtolower(trim((string) post_input('inquiry_status', 'new')));
$oldStatus = strtolower(trim((string) ($record['inquiry_status'] ?? 'new')));
$errors = [];
if (!in_array($newStatus, $statusOptions, true)) {
    $errors[] = 'Invalid inquiry status selected.';
    $newStatus = in_array($oldStatus, $statusOptions, true) ? $oldStatus : 'new';
}

$hasPostedAdminNotes = array_key_exists('admin_notes', $_POST);
$hasPostedFollowUp = array_key_exists('follow_up_at', $_POST);
$adminNotes = $hasPostedAdminNotes ? trim((string) post_input('admin_notes', '')) : trim((string) ($record['admin_notes'] ?? ''));
$followUpRaw = $hasPostedFollowUp ? trim((string) post_input('follow_up_at', '')) : trim((string) ($record['follow_up_at'] ?? ''));
$followUpAt = null;
if ($followUpRaw !== '') {
    $ts = strtotime($followUpRaw);
    if ($ts !== false) {
        $followUpAt = date('Y-m-d H:i:s', $ts);
    } else {
        $errors[] = 'Follow-up date is invalid.';
    }
}

if ($hasPostedAdminNotes && mb_strlen($adminNotes) > 5000) {
    $errors[] = 'Admin notes must be 5000 characters or fewer.';
}

if ($errors) {
    flash_set('error', implode(' ', $errors));
    redirect($safeReturnTo);
}

$updateData = [];
$setParts = [];

if (db_column_exists('contact_inquiries', 'inquiry_status')) {
    $setParts[] = 'inquiry_status = :inquiry_status';
    $updateData['inquiry_status'] = $newStatus;
}

if ($hasPostedAdminNotes && db_column_exists('contact_inquiries', 'admin_notes')) {
    $setParts[] = 'admin_notes = :admin_notes';
    $updateData['admin_notes'] = $adminNotes !== '' ? $adminNotes : null;
}

if ($hasPostedFollowUp && db_column_exists('contact_inquiries', 'follow_up_at')) {
    $setParts[] = 'follow_up_at = :follow_up_at';
    $updateData['follow_up_at'] = $followUpAt;
}

if (db_column_exists('contact_inquiries', 'admin_updated_at')) {
    $setParts[] = 'admin_updated_at = NOW()';
}

if (db_column_exists('contact_inquiries', 'last_contacted_at') && in_array($newStatus, ['follow_up', 'quoted', 'won', 'lost'], true)) {
    $setParts[] = 'last_contacted_at = NOW()';
}

if (!$setParts) {
    flash_set('error', 'No updatable fields are available in the current schema.');
    redirect('admin/inquiries.php');
}

$updateData['id'] = $id;
execute_query('UPDATE contact_inquiries SET ' . implode(', ', $setParts) . ' WHERE id = :id', $updateData);

$adminUser = admin_user();
$adminUserId = (int) ($adminUser['id'] ?? 0);
$oldNotes = trim((string) ($record['admin_notes'] ?? ''));
$oldFollowUp = trim((string) ($record['follow_up_at'] ?? ''));

if ($oldStatus !== $newStatus) {
    log_contact_inquiry_activity($id, 'status_changed', $oldStatus, $newStatus, 'Status updated in admin.', $adminUserId > 0 ? $adminUserId : null);
}

if ($hasPostedAdminNotes && $oldNotes !== $adminNotes) {
    log_contact_inquiry_activity($id, 'notes_updated', $oldNotes, $adminNotes, 'Admin notes updated.', $adminUserId > 0 ? $adminUserId : null);
}

$newFollowUpLabel = $followUpAt ?? '';
if ($hasPostedFollowUp && $oldFollowUp !== $newFollowUpLabel) {
    log_contact_inquiry_activity($id, 'follow_up_updated', $oldFollowUp, $newFollowUpLabel, 'Follow-up schedule updated.', $adminUserId > 0 ? $adminUserId : null);
}

flash_set('success', 'Inquiry updated successfully.');
redirect($safeReturnTo);
