<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/customer_auth.php';

$customer = customer_require_auth();

if (!is_post()) {
    redirect('account');
}

guard_post_size();
require_csrf();

$inquiryId = (int) get_input('id', 0);
$inquiry = find_customer_inquiry($inquiryId, $customer);
if (!$inquiry) {
    flash_set('error', 'Inquiry not found.');
    redirect('account');
}

$messageBody = trim((string) post_input('message_body', ''));

try {
    create_contact_inquiry_message($inquiryId, 'customer', $messageBody, (int) $customer['id'], null, true);
    log_contact_inquiry_activity($inquiryId, 'customer_message', null, null, 'Customer sent a visible account message.', null);
    google_tag_event_queue('customer_message_submit', [
        'source_page' => 'account',
    ]);
    flash_set('success', 'Message sent.');
} catch (InvalidArgumentException $e) {
    $message = $e->getMessage();
    $allowedMessages = [
        'Message cannot be empty.',
        'Message is too long. Please keep it under ' . CUSTOMER_MESSAGE_MAX_LENGTH . ' characters.',
        'Invalid message sender.',
    ];
    flash_set('error', in_array($message, $allowedMessages, true) ? $message : 'Could not send your message. Please try again.');
} catch (Throwable) {
    flash_set('error', 'Could not send your message. Please try again.');
}

redirect('account/inquiries/' . $inquiryId);
