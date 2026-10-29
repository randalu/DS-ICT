<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/customer_auth.php';

$customer = customer_require_auth();

if (!is_post()) {
    redirect('account');
}

guard_post_size();
require_csrf();

$planId = (int) get_input('plan_id', 0);
$dayId = (int) get_input('day_id', 0);
$redirectTarget = $planId > 0 ? 'account/plans/' . $planId . '#requests' : 'account';

try {
    $requestId = create_customer_itinerary_change_request($planId, $dayId, $customer, [
        'requested_title' => post_input('requested_title', ''),
        'requested_summary' => post_input('requested_summary', ''),
        'requested_overnight_location' => post_input('requested_overnight_location', ''),
        'requested_travel_time' => post_input('requested_travel_time', ''),
        'request_reason' => post_input('request_reason', ''),
    ]);
    $plan = find_customer_plan($planId, $customer);
    if ($plan) {
        log_contact_inquiry_activity((int) $plan['contact_inquiry_id'], 'customer_plan_change_requested', null, (string) $requestId, 'Customer requested a day-by-day itinerary change.', null);
    }
    google_tag_event_queue('change_request_submit', [
        'source_page' => 'account_plan',
        'plan_id' => $planId,
    ]);
    flash_set('success', 'Change request sent for admin review.');
} catch (Throwable $e) {
    $message = $e->getMessage();
    $allowedMessages = [
        'Plan not found.',
        'Plan day not found.',
        'Add at least one change or a reason.',
        'Suggested activities are too long. Please keep them under ' . CUSTOMER_CHANGE_SUMMARY_MAX_LENGTH . ' characters.',
        'Change reason is too long. Please keep it under ' . CUSTOMER_CHANGE_REASON_MAX_LENGTH . ' characters.',
    ];
    flash_set('error', in_array($message, $allowedMessages, true) ? $message : 'Could not send the change request. Please try again.');
    if ($message === 'Plan not found.') {
        $redirectTarget = 'account';
    }
}

redirect($redirectTarget);
