<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/customer_auth.php';

admin_require_auth();

if (!is_post()) {
    redirect('admin/inquiries.php');
}

guard_post_size();
require_csrf();
ensure_customer_accounts_schema();

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
    redirect($safeReturnTo);
}

$record = fetch_one('SELECT id FROM contact_inquiries WHERE id = :id LIMIT 1', ['id' => $id]);
if (!$record) {
    flash_set('error', 'Inquiry not found.');
    redirect($safeReturnTo);
}

$adminUser = admin_user();
$adminUserId = (int) ($adminUser['id'] ?? 0);
$messageBody = trim((string) post_input('message_body', ''));

try {
    create_contact_inquiry_message($id, 'admin', $messageBody, null, $adminUserId > 0 ? $adminUserId : null, true);
    log_contact_inquiry_activity($id, 'admin_message', null, null, 'Admin sent a visible customer account reply.', $adminUserId > 0 ? $adminUserId : null);
    flash_set('success', 'Customer-visible reply sent.');
} catch (InvalidArgumentException $e) {
    $message = $e->getMessage();
    $allowedMessages = [
        'Message cannot be empty.',
        'Message is too long. Please keep it under ' . CUSTOMER_MESSAGE_MAX_LENGTH . ' characters.',
        'Invalid message sender.',
    ];
    flash_set('error', in_array($message, $allowedMessages, true) ? $message : 'Could not send the customer-visible reply.');
} catch (Throwable) {
    flash_set('error', 'Could not send the customer-visible reply.');
}

redirect($safeReturnTo);
