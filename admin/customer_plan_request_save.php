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

$requestId = (int) post_input('request_id', 0);
$action = strtolower(trim((string) post_input('action', '')));
$adminUser = admin_user();
$adminUserId = (int) ($adminUser['id'] ?? 0);

try {
    $result = admin_apply_customer_change_request($requestId, $action, $adminUserId, [
        'final_title' => post_input('final_title', ''),
        'final_summary' => post_input('final_summary', ''),
        'final_overnight_location' => post_input('final_overnight_location', ''),
        'final_travel_time' => post_input('final_travel_time', ''),
        'admin_response' => post_input('admin_response', ''),
    ]);

    $inquiryId = (int) ($result['contact_inquiry_id'] ?? 0);
    $adminResponse = trim((string) ($result['admin_response_final'] ?? ''));
    $reviewAction = (string) ($result['review_action'] ?? $action);
    $customerMessage = $adminResponse !== ''
        ? $adminResponse
        : ($reviewAction === 'applied'
            ? 'Your requested day-by-day itinerary change has been reviewed and applied to your private plan.'
            : 'Your requested day-by-day itinerary change has been reviewed. It was not applied to the plan.');

    if ($inquiryId > 0) {
        create_contact_inquiry_message($inquiryId, 'admin', $customerMessage, null, $adminUserId > 0 ? $adminUserId : null, true);
        log_contact_inquiry_activity(
            $inquiryId,
            $reviewAction === 'applied' ? 'customer_plan_change_applied' : 'customer_plan_change_rejected',
            'pending',
            $reviewAction,
            'Admin reviewed a customer itinerary change request.',
            $adminUserId > 0 ? $adminUserId : null
        );
    }

    flash_set('success', $reviewAction === 'applied' ? 'Change request applied to the customer plan.' : 'Change request rejected.');
} catch (Throwable $e) {
    $message = $e->getMessage();
    $allowedMessages = [
        'Change request not found.',
        'Only pending requests can be reviewed.',
        'Invalid review action.',
        'Final day title is required.',
        'Final day title is too long. Please keep it under 255 characters.',
        'Final day summary is too long. Please keep it under ' . CUSTOMER_CHANGE_SUMMARY_MAX_LENGTH . ' characters.',
        'Final overnight location is too long. Please keep it under 180 characters.',
        'Final travel time note is too long. Please keep it under 120 characters.',
        'Admin response is too long. Please keep it under ' . CUSTOMER_MESSAGE_MAX_LENGTH . ' characters.',
    ];
    flash_set('error', in_array($message, $allowedMessages, true) ? $message : 'Could not review the customer change request.');
}

redirect('admin/inquiries.php');
