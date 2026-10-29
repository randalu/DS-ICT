<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/customer_auth.php';
require_once __DIR__ . '/../includes/booking_workflow.php';

admin_require_auth();

if (!is_post()) {
    redirect('admin/inquiries.php');
}

guard_post_size();
require_csrf();
if (!db()->inTransaction()) {
    ensure_customer_accounts_schema();
    ensure_customer_policy_acceptance_schema();
}

$planId = (int) post_input('plan_id', 0);
$action = strtolower(trim((string) post_input('action', '')));
$admin = admin_user();
$adminId = (int) ($admin['id'] ?? 0);
if ($planId <= 0 || $adminId <= 0) {
    flash_set('error', 'Invalid booking plan or administrator.');
    redirect('admin/inquiries.php');
}

$plan = fetch_one('SELECT * FROM customer_itinerary_plans WHERE id = :id LIMIT 1', ['id' => $planId]);
if (!$plan) {
    flash_set('error', 'Customer plan not found.');
    redirect('admin/inquiries.php');
}

$currentStatus = trim((string) ($plan['booking_status'] ?? 'planning'));
if ($action === 'confirm_booking') {
    $input = post_input('confirmation_evidence', []);
    old_input_set(['plan_id' => $planId, 'confirmation_evidence' => $input]);
    try {
        if (!is_array($input)) { throw new InvalidArgumentException('Complete the confirmation evidence fields.'); }
        ict_confirm_booking($planId, $adminId, $input);
        old_input_clear();
        flash_set('success', 'Evidence and written confirmation saved; the customer planning workspace has been updated.');
    } catch (InvalidArgumentException | RuntimeException $exception) {
        flash_set('error', $exception instanceof PDOException ? 'Could not save confirmation evidence. The booking was not confirmed.' : $exception->getMessage());
    } catch (Throwable) {
        flash_set('error', 'Could not save confirmation evidence. The booking was not confirmed.');
    }
    redirect('admin/inquiries.php#inquiry-' . (int) ($plan['contact_inquiry_id'] ?? 0));
}
$inquiryId = (int) ($plan['contact_inquiry_id'] ?? 0);
if ($action !== 'request_acceptance') {
    flash_set('error', 'Invalid booking action.');
    redirect('admin/inquiries.php#inquiry-' . $inquiryId);
}
try {
    ict_request_booking_acceptance($planId, $adminId, (bool) post_input('final_plan_reviewed'));
    flash_set('success', 'The customer was asked to review and accept the current policies.');
} catch (InvalidArgumentException | RuntimeException $exception) {
    flash_set('error', $exception instanceof PDOException ? 'Could not update the booking status.' : $exception->getMessage());
} catch (Throwable) {
    flash_set('error', 'Could not update the booking status. Please review the plan and try again.');
}
redirect('admin/inquiries.php#inquiry-' . $inquiryId);
