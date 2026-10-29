<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/booking_workflow.php';

$customer = customer_require_auth();

if (!is_post()) {
    redirect('account');
}

guard_post_size();
require_csrf();
if (!db()->inTransaction()) {
    ensure_customer_accounts_schema();
    ensure_customer_policy_acceptance_schema();
}

$planId = (int) post_input('plan_id', 0);
$plan = find_customer_plan($planId, $customer);
if (!$plan) {
    flash_set('error', 'Itinerary plan not found.');
    redirect('account');
}

$requiredCheckboxes = [
    'accept_booking_terms' => 'Booking terms',
    'accept_cancellation_policy' => 'Cancellation and refund policy',
    'accept_payment_policy' => 'Payment and deposit policy',
    'accept_privacy_policy' => 'Privacy policy',
];
$errors = [];
foreach ($requiredCheckboxes as $field => $label) {
    if (!post_input($field)) {
        $errors[] = $label . ' must be accepted.';
    }
}

if ($errors) {
    flash_set('error', implode(' ', $errors));
    redirect('account/plans/' . $planId);
}

try {
    $input = [];
    foreach (array_merge(array_keys($requiredCheckboxes), ['policy_snapshot_hash', 'marketing_consent', 'quotation_token']) as $field) {
        $input[$field] = post_input($field);
    }
    ict_accept_booking_policies($planId, (int) $customer['id'], $input, request_ip_address(), (string) ($_SERVER['HTTP_USER_AGENT'] ?? ''));
    flash_set('success', 'Your policy acceptance was recorded. The booking is awaiting written confirmation from Island Culture Travels.');
} catch (Throwable) {
    flash_set('error', 'Could not record policy acceptance. Please try again.');
}

redirect('account/plans/' . $planId);
