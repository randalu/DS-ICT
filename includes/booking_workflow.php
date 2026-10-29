<?php
declare(strict_types=1);
require_once __DIR__ . '/customer_auth.php';
require_once __DIR__ . '/booking_confirmation.php';

/** Direct writes avoid legacy schema initializers (DDL can commit a caller transaction). */
function ict_workflow_message(int $inquiryId, string $sender, string $message, ?int $customerId, ?int $adminId): void
{
    if ($inquiryId <= 0) { throw new RuntimeException('The linked inquiry is unavailable.'); }
    execute_query('INSERT INTO contact_inquiry_messages (inquiry_id, customer_user_id, admin_user_id, sender_type, message_body, is_visible_to_customer) VALUES (:inquiry, :customer, :admin, :sender, :message, 1)', ['inquiry' => $inquiryId, 'customer' => $customerId, 'admin' => $adminId, 'sender' => $sender, 'message' => $message]);
    if ((int) db()->query('SELECT ROW_COUNT()')->fetchColumn() !== 1) { throw new RuntimeException('The workspace message was not saved.'); }
}

function ict_workflow_activity(int $inquiryId, string $action, string $old, string $new, string $note, ?int $adminId): void
{
    execute_query('INSERT INTO contact_inquiry_activity (inquiry_id, action_type, old_value, new_value, note, admin_user_id) VALUES (:inquiry, :action, :old, :new, :note, :admin)', ['inquiry' => $inquiryId, 'action' => $action, 'old' => $old, 'new' => $new, 'note' => $note, 'admin' => $adminId]);
    if ((int) db()->query('SELECT ROW_COUNT()')->fetchColumn() !== 1) { throw new RuntimeException('The workflow activity was not saved.'); }
}

/** Schema guards must run before calling these operations, outside transactions. */
function ict_request_booking_acceptance(int $planId, int $adminId, bool $finalPlanReviewed): void
{
    if ($planId <= 0 || $adminId <= 0 || !$finalPlanReviewed) {
        throw new InvalidArgumentException('Review the final itinerary before requesting acceptance.');
    }
    if (!ict_confirmation_storage_ready()) { throw new RuntimeException('Transactional booking storage is unavailable.'); }
    ict_confirmation_atomic(static function () use ($planId, $adminId): void {
        $plan = fetch_one('SELECT * FROM customer_itinerary_plans WHERE id = :id LIMIT 1 FOR UPDATE', ['id' => $planId]);
        require_once __DIR__ . '/quotation_booking.php';
        quote_assert_binding($planId);
        if ($plan && $plan['customer_user_id'] === null) { throw new RuntimeException('Use admin-recorded customer acceptance for a guest booking.'); }
        $documents = ict_policy_published_documents(true);
        if (count($documents) !== count(ict_policy_required_keys())) {
            throw new RuntimeException('Publish and validate all required policy pages before requesting acceptance.');
        }
        $status = (string) ($plan['booking_status'] ?? '');
        if (!$plan || !in_array($status, ['planning', 'accepted'], true)) {
            throw new RuntimeException('This booking cannot request policy acceptance in its current state.');
        }
        if ($status === 'accepted' && ict_policy_plan_acceptance_state($plan, $documents)['ready_to_confirm']) {
            throw new RuntimeException('Current policy versions are already accepted.');
        }
        if (fetch_all("SELECT id FROM customer_itinerary_change_requests WHERE plan_id = :id AND request_status = 'pending' FOR UPDATE", ['id' => $planId])) {
            throw new RuntimeException('Resolve pending itinerary changes before requesting acceptance.');
        }
        execute_query("UPDATE customer_itinerary_plans SET booking_status = 'awaiting_acceptance', plan_status = 'confirmed', last_admin_update_at = NOW() WHERE id = :id AND booking_status = :old_status", ['id' => $planId, 'old_status' => $status]);
        if ((int) db()->query('SELECT ROW_COUNT()')->fetchColumn() !== 1) { throw new RuntimeException('Booking status changed in another request.'); }
        $inquiryId = (int) $plan['contact_inquiry_id'];
        ict_workflow_message($inquiryId, 'admin', "The final itinerary is ready for your review. Please read and acknowledge the current booking, cancellation, payment, and privacy policies in your customer planning workspace. This is not the operator's written booking confirmation.", null, $adminId);
        ict_workflow_activity($inquiryId, 'booking_acceptance_requested', $status, 'awaiting_acceptance', 'Admin reviewed the final itinerary and requested policy acceptance.', $adminId);
    });
}

/** Archive exactly the policies reviewed by the authenticated plan owner; no notifications leave the DB. */
function ict_accept_booking_policies(int $planId, int $customerId, array $input, ?string $customerIp, string $userAgent): int
{
    if ($planId <= 0 || $customerId <= 0) { throw new InvalidArgumentException('Invalid plan or customer.'); }
    foreach (['accept_booking_terms', 'accept_cancellation_policy', 'accept_payment_policy', 'accept_privacy_policy'] as $field) {
        if (!in_array($input[$field] ?? null, ['1', 1], true)) { throw new InvalidArgumentException('All four required policies must be accepted.'); }
    }
    if (!is_string($input['policy_snapshot_hash'] ?? null)) { throw new InvalidArgumentException('Reload the policy snapshot.'); }
    if (!ict_confirmation_storage_ready()) { throw new RuntimeException('Transactional booking storage is unavailable.'); }
    return ict_confirmation_atomic(static function () use ($planId, $customerId, $input, $customerIp, $userAgent): int {
        $lockedPlan = fetch_one(
            'SELECT id, contact_inquiry_id, booking_status FROM customer_itinerary_plans
             WHERE id = :id AND customer_user_id = :customer_user_id LIMIT 1 FOR UPDATE',
            ['id' => $planId, 'customer_user_id' => $customerId]
        );
        if (!$lockedPlan || (string) ($lockedPlan['booking_status'] ?? '') !== 'awaiting_acceptance') {
            throw new RuntimeException('This plan is not awaiting policy acceptance.');
        }

        $documents = ict_policy_published_documents(true);
        if (count($documents) !== count(ict_policy_required_keys())) {
            throw new RuntimeException('Policy acceptance is not available until all approved policy pages are published.');
        }
        $snapshot = ict_policy_acceptance_snapshot($documents);
        $postedSnapshotHash = trim((string) ($input['policy_snapshot_hash'] ?? ''));
        if ($postedSnapshotHash === '' || !hash_equals($snapshot['hash'], $postedSnapshotHash)) {
            throw new RuntimeException('Policy content changed. Reload the plan and review the current versions.');
        }

        $versions = [];
        foreach ($documents as $key => $document) {
            $versions[$key] = (string) $document['version'];
        }
        $acceptedAt = date('Y-m-d H:i:s');
        $marketingConsent = in_array($input['marketing_consent'] ?? null, ['1', 1], true) ? 1 : 0;
        $userAgent = mb_substr($userAgent, 0, 512);
        $acceptanceData = [
            'plan_id' => $planId,
            'customer_user_id' => $customerId,
            'booking_terms_version' => $versions['booking_terms'],
            'booking_terms_accepted_at' => $acceptedAt,
            'cancellation_policy_version' => $versions['cancellation_refund'],
            'cancellation_policy_accepted_at' => $acceptedAt,
            'payment_policy_version' => $versions['payment_deposit'],
            'privacy_policy_version' => $versions['privacy'],
            'marketing_consent' => $marketingConsent,
            'marketing_consent_at' => $marketingConsent ? $acceptedAt : null,
            'customer_ip' => $customerIp,
            'customer_user_agent' => $userAgent,
            'policy_snapshot_hash' => $snapshot['hash'],
            'policy_snapshot_json' => $snapshot['json'],
            'accepted_at' => $acceptedAt,
        ];
        execute_query(
            'INSERT INTO customer_policy_acceptances
                (plan_id, customer_user_id, booking_terms_version, booking_terms_accepted_at,
                 cancellation_policy_version, cancellation_policy_accepted_at, payment_policy_version,
                 privacy_policy_version, marketing_consent, marketing_consent_at, customer_ip,
                 customer_user_agent, policy_snapshot_hash, policy_snapshot_json, accepted_at)
             VALUES
                (:plan_id, :customer_user_id, :booking_terms_version, :booking_terms_accepted_at,
                 :cancellation_policy_version, :cancellation_policy_accepted_at, :payment_policy_version,
                 :privacy_policy_version, :marketing_consent, :marketing_consent_at, :customer_ip,
                 :customer_user_agent, :policy_snapshot_hash, :policy_snapshot_json, :accepted_at)',
            $acceptanceData
        );

        $acceptanceId = (int) last_insert_id();
        if ($acceptanceId <= 0) { throw new RuntimeException('Policy acceptance archive was not saved.'); }
        execute_query(
            'UPDATE customer_itinerary_plans
             SET booking_terms_version = :booking_terms_version,
                 booking_terms_accepted_at = :booking_terms_accepted_at,
                 cancellation_policy_version = :cancellation_policy_version,
                 cancellation_policy_accepted_at = :cancellation_policy_accepted_at,
                 payment_policy_version = :payment_policy_version,
                 privacy_policy_version = :privacy_policy_version,
                 marketing_consent = :marketing_consent,
                 marketing_consent_at = :marketing_consent_at,
                 customer_ip = :customer_ip,
                 customer_user_agent = :customer_user_agent,
                 policy_snapshot_hash = :policy_snapshot_hash,
                 policy_snapshot_json = :policy_snapshot_json,
                 policy_accepted_at = :policy_accepted_at,
                 booking_status = :booking_status
             WHERE id = :plan_id AND customer_user_id = :customer_user_id AND booking_status = :expected_status',
            array_merge(array_diff_key($acceptanceData, ['accepted_at' => true]), [
                'policy_accepted_at' => $acceptedAt,
                'booking_status' => 'accepted',
                'expected_status' => 'awaiting_acceptance',
            ])
        );
        if ((int) db()->query('SELECT ROW_COUNT()')->fetchColumn() !== 1) {
            throw new RuntimeException('This plan is no longer awaiting policy acceptance.');
        }

        require_once __DIR__ . '/quotation_booking.php';
        quote_check_customer_token($planId, $input);
        ict_workflow_activity(
            (int) ($lockedPlan['contact_inquiry_id'] ?? 0),
            'policy_accepted',
            'awaiting_acceptance',
            'accepted',
            'Customer accepted the current booking, cancellation, payment, and privacy policy versions.',
            null
        );
        ict_workflow_message(
            (int) ($lockedPlan['contact_inquiry_id'] ?? 0),
            'customer',
            'I reviewed and accepted the displayed booking, cancellation, payment, and privacy policy versions. I understand the booking is not confirmed until Island Culture Travels issues written confirmation.',
            $customerId,
            null
        );
        require_once __DIR__ . '/quotation_booking.php';
        quote_bind_customer_acceptance($planId, $acceptanceId);
        return $acceptanceId;
    });
}
