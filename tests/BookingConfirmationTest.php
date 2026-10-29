<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;
require_once __DIR__ . '/../includes/customer_auth.php';
require_once __DIR__ . '/../includes/booking_confirmation.php';
require_once __DIR__ . '/../includes/booking_workflow.php';

final class BookingConfirmationTest extends TestCase
{
    private int $planId;
    private int $inquiryId;
    private int $customerId;
    private int $adminId;
    private array $input;

    protected function setUp(): void
    {
        // Existing guards run before fixtures: no DDL inside a test transaction.
        ensure_customer_accounts_schema();
        ensure_site_settings_schema();
        $this->assertTrue(ict_confirmation_storage_ready());
        $admin = fetch_one('SELECT id FROM admins WHERE is_active = 1 LIMIT 1');
        $this->assertNotNull($admin);
        $this->adminId = (int) $admin['id'];
        db()->beginTransaction();
        foreach (ict_policy_required_keys() as $key) {
            $doc = ict_policy_documents()[$key];
            execute_query('UPDATE static_pages SET is_published = 1, title = :title, meta_title = :meta, meta_description = :description, content = :content WHERE slug = :slug', [
                'slug' => $doc['slug'], 'title' => 'Synthetic ' . $key, 'meta' => 'Synthetic policy',
                'description' => 'Isolated transaction policy fixture.', 'content' => '<p>Isolated synthetic policy content used solely to exercise confirmation checks in a rolled-back local transaction.</p>',
            ]);
        }
        $documents = ict_policy_published_documents();
        $this->assertCount(4, $documents);
        $snapshot = ict_policy_acceptance_snapshot($documents);
        $token = bin2hex(random_bytes(8));
        execute_query('INSERT INTO customer_users (google_sub, email, name) VALUES (:sub, :email, :name)', ['sub' => 'confirmation-' . $token, 'email' => $token . '@example.invalid', 'name' => 'Synthetic confirmation fixture']);
        $this->customerId = (int) last_insert_id();
        execute_query('INSERT INTO contact_inquiries (name, message, customer_user_id) VALUES (:name, :message, :customer)', ['name' => 'Synthetic confirmation fixture', 'message' => 'Rollback-only fixture', 'customer' => $this->customerId]);
        $this->inquiryId = (int) last_insert_id();
        execute_query("INSERT INTO customer_itinerary_plans (customer_user_id, contact_inquiry_id, plan_title, plan_status, booking_status, booking_terms_version, cancellation_policy_version, payment_policy_version, privacy_policy_version, policy_snapshot_hash, policy_snapshot_json, policy_accepted_at, booking_terms_accepted_at, cancellation_policy_accepted_at)
            VALUES (:customer, :inquiry, 'Synthetic confirmation', 'confirmed', 'accepted', :terms, :cancel, :payment, :privacy, :hash, :json, '2026-10-12 12:00:00', '2026-10-12 12:00:00', '2026-10-12 12:00:00')", [
            'customer' => $this->customerId, 'inquiry' => $this->inquiryId, 'terms' => $documents['booking_terms']['version'], 'cancel' => $documents['cancellation_refund']['version'],
            'payment' => $documents['payment_deposit']['version'], 'privacy' => $documents['privacy']['version'], 'hash' => $snapshot['hash'], 'json' => $snapshot['json'],
        ]);
        $this->planId = (int) last_insert_id();
        $acceptanceId = $this->appendAcceptance();
        $this->input = ['quote_reference' => 'SYN-QUOTE-01 v1', 'payment_status' => 'funds_received', 'payment_reference' => 'SYN-RECEIPT-01', 'supplier_reference' => 'SYN-AVAILABILITY-01',
            'payment_verified' => '1', 'supplier_verified' => '1', 'final_services_reviewed' => '1', 'accepted_snapshot_hash' => $snapshot['hash'], 'acceptance_record_id' => (string) $acceptanceId];
    }

    protected function tearDown(): void { if (db()->inTransaction()) { db()->rollBack(); } }

    private function appendAcceptance(): int
    {
        execute_query('INSERT INTO customer_policy_acceptances (plan_id, customer_user_id, booking_terms_version, booking_terms_accepted_at, cancellation_policy_version, cancellation_policy_accepted_at, payment_policy_version, privacy_policy_version, policy_snapshot_hash, policy_snapshot_json, accepted_at)
            SELECT id, customer_user_id, booking_terms_version, booking_terms_accepted_at, cancellation_policy_version, cancellation_policy_accepted_at, payment_policy_version, privacy_policy_version, policy_snapshot_hash, policy_snapshot_json, policy_accepted_at FROM customer_itinerary_plans WHERE id = :id', ['id' => $this->planId]);
        return (int) last_insert_id();
    }

    private function assertUnconfirmed(): void
    {
        $plan = fetch_one('SELECT booking_status, booking_confirmed_at FROM customer_itinerary_plans WHERE id = :id', ['id' => $this->planId]);
        $this->assertSame('accepted', $plan['booking_status']);
        $this->assertNull($plan['booking_confirmed_at']);
        $this->assertSame(0, (int) fetch_one('SELECT COUNT(*) AS total FROM contact_inquiry_messages WHERE inquiry_id = :id', ['id' => $this->inquiryId])['total']);
        $this->assertSame(0, (int) fetch_one('SELECT COUNT(*) AS total FROM contact_inquiry_activity WHERE inquiry_id = :id', ['id' => $this->inquiryId])['total']);
        $this->assertTrue(db()->inTransaction());
    }

    private function resetToPlanning(): void
    {
        execute_query('DELETE FROM customer_policy_acceptances WHERE plan_id = :id', ['id' => $this->planId]);
        execute_query("UPDATE customer_itinerary_plans SET booking_status = 'planning', plan_status = 'draft', policy_snapshot_hash = NULL, policy_snapshot_json = NULL, policy_accepted_at = NULL, booking_terms_accepted_at = NULL, cancellation_policy_accepted_at = NULL WHERE id = :id", ['id' => $this->planId]);
    }

    private function rehearseFlow(string $paymentStatus): void
    {
        $this->resetToPlanning();
        ict_request_booking_acceptance($this->planId, $this->adminId, true);
        $this->assertSame('awaiting_acceptance', fetch_one('SELECT booking_status FROM customer_itinerary_plans WHERE id = :id', ['id' => $this->planId])['booking_status']);
        $acceptanceInput = array_fill_keys(['accept_booking_terms', 'accept_cancellation_policy', 'accept_payment_policy', 'accept_privacy_policy'], '1');
        $acceptanceInput['policy_snapshot_hash'] = $this->input['accepted_snapshot_hash'];
        $id = ict_accept_booking_policies($this->planId, $this->customerId, $acceptanceInput, null, 'Synthetic rehearsal');
        $accepted = fetch_one('SELECT * FROM customer_itinerary_plans WHERE id = :id', ['id' => $this->planId]);
        $this->assertSame('accepted', $accepted['booking_status']);
        $this->assertNull($accepted['booking_confirmed_at']);
        $this->assertSame(0, (int) $accepted['marketing_consent']);
        $archive = fetch_one('SELECT * FROM customer_policy_acceptances WHERE id = :id', ['id' => $id]);
        $this->assertSame($accepted['policy_snapshot_json'], $archive['policy_snapshot_json']);
        $this->assertSame($accepted['policy_snapshot_hash'], $archive['policy_snapshot_hash']);
        $this->input['acceptance_record_id'] = (string) $id;
        $this->input['payment_status'] = $paymentStatus;
        if ($paymentStatus === 'no_payment_due') { $this->input['payment_reference'] = 'SYN-QUOTE-01 section 4: no payment due at confirmation'; }
        $evidenceId = ict_confirm_booking($this->planId, $this->adminId, $this->input);
        $evidence = json_decode(fetch_one('SELECT note FROM contact_inquiry_activity WHERE id = :id', ['id' => $evidenceId])['note'], true, 16, JSON_THROW_ON_ERROR);
        $this->assertSame($id, $evidence['acceptance_record_id']);
        $this->assertSame($paymentStatus, $evidence['payment_status']);
        $this->assertSame('confirmed', fetch_one('SELECT booking_status FROM customer_itinerary_plans WHERE id = :id', ['id' => $this->planId])['booking_status']);
        $this->assertSame(3, (int) fetch_one('SELECT COUNT(*) AS total FROM contact_inquiry_messages WHERE inquiry_id = :id', ['id' => $this->inquiryId])['total']);
        $this->assertTrue(db()->inTransaction(), 'Every stage must preserve the rollback-only parent transaction.');
    }

    public function testCompleteFlowWithReceivedFunds(): void { $this->rehearseFlow('funds_received'); }
    public function testCompleteFlowWithExplicitNoPaymentDue(): void { $this->rehearseFlow('no_payment_due'); }

    public function testRequestAndAcceptanceRejectUnsafeTransitionsWithoutPartialWrites(): void
    {
        $this->resetToPlanning();
        try { ict_request_booking_acceptance($this->planId, $this->adminId, false); $this->fail('Unreviewed plan allowed.'); }
        catch (InvalidArgumentException) { $this->addToAssertionCount(1); }
        ict_request_booking_acceptance($this->planId, $this->adminId, true);
        $input = array_fill_keys(['accept_booking_terms', 'accept_cancellation_policy', 'accept_payment_policy', 'accept_privacy_policy'], '1');
        $input['policy_snapshot_hash'] = $this->input['accepted_snapshot_hash'];
        foreach ([['customer' => $this->customerId + 999999, 'input' => $input], ['customer' => $this->customerId, 'input' => array_replace($input, ['policy_snapshot_hash' => str_repeat('0', 64)])], ['customer' => $this->customerId, 'input' => array_replace($input, ['accept_privacy_policy' => 'yes'])]] as $bad) {
            try { ict_accept_booking_policies($this->planId, $bad['customer'], $bad['input'], null, 'Synthetic'); $this->fail('Unsafe acceptance allowed.'); }
            catch (InvalidArgumentException | RuntimeException) { $this->addToAssertionCount(1); }
        }
        $this->assertSame('awaiting_acceptance', fetch_one('SELECT booking_status FROM customer_itinerary_plans WHERE id = :id', ['id' => $this->planId])['booking_status']);
        $this->assertSame(0, (int) fetch_one('SELECT COUNT(*) AS total FROM customer_policy_acceptances WHERE plan_id = :id', ['id' => $this->planId])['total']);
        $this->assertSame(1, (int) fetch_one('SELECT COUNT(*) AS total FROM contact_inquiry_messages WHERE inquiry_id = :id', ['id' => $this->inquiryId])['total']);
        $this->assertTrue(db()->inTransaction());
    }

    public function testAcceptanceRequestRejectsPendingChangesAndUnpublishedPolicies(): void
    {
        $this->resetToPlanning();
        execute_query("UPDATE static_pages SET is_published = 0 WHERE slug = 'privacy-policy'");
        try { ict_request_booking_acceptance($this->planId, $this->adminId, true); $this->fail('Unpublished policies allowed.'); }
        catch (RuntimeException) { $this->addToAssertionCount(1); }
        execute_query("UPDATE static_pages SET is_published = 1 WHERE slug = 'privacy-policy'");
        execute_query("INSERT INTO customer_itinerary_change_requests (plan_id, plan_day_id, customer_user_id, request_reason) VALUES (:plan, 0, :customer, 'Synthetic pending change')", ['plan' => $this->planId, 'customer' => $this->customerId]);
        try { ict_request_booking_acceptance($this->planId, $this->adminId, true); $this->fail('Pending changes allowed.'); }
        catch (RuntimeException) { $this->addToAssertionCount(1); }
        $this->assertSame('planning', fetch_one('SELECT booking_status FROM customer_itinerary_plans WHERE id = :id', ['id' => $this->planId])['booking_status']);
        $this->assertSame(0, (int) fetch_one('SELECT COUNT(*) AS total FROM contact_inquiry_messages WHERE inquiry_id = :id', ['id' => $this->inquiryId])['total']);
        $this->assertTrue(db()->inTransaction());
    }

    public function testChangedPolicyCanBeReacceptedWithoutRewritingPriorArchive(): void
    {
        $oldArchive = fetch_one('SELECT * FROM customer_policy_acceptances WHERE id = :id', ['id' => $this->input['acceptance_record_id']]);
        try { ict_request_booking_acceptance($this->planId, $this->adminId, true); $this->fail('Current acceptance needlessly reopened.'); }
        catch (RuntimeException) { $this->addToAssertionCount(1); }
        execute_query("UPDATE static_pages SET content = CONCAT(content, '<p>Revised policy.</p>') WHERE slug = 'booking-terms-and-conditions'");
        ict_request_booking_acceptance($this->planId, $this->adminId, true);
        $input = array_fill_keys(['accept_booking_terms', 'accept_cancellation_policy', 'accept_payment_policy', 'accept_privacy_policy'], '1');
        $input['policy_snapshot_hash'] = ict_policy_acceptance_snapshot(ict_policy_published_documents())['hash'];
        $input['marketing_consent'] = '1';
        $newId = ict_accept_booking_policies($this->planId, $this->customerId, $input, null, 'Synthetic reacceptance');
        $this->assertGreaterThan((int) $oldArchive['id'], $newId);
        $this->assertSame($oldArchive, fetch_one('SELECT * FROM customer_policy_acceptances WHERE id = :id', ['id' => $oldArchive['id']]));
        $new = fetch_one('SELECT * FROM customer_policy_acceptances WHERE id = :id', ['id' => $newId]);
        $this->assertSame($input['policy_snapshot_hash'], $new['policy_snapshot_hash']);
        $this->assertSame(1, (int) $new['marketing_consent']);
        $this->assertNotNull($new['marketing_consent_at']);
        try { ict_accept_booking_policies($this->planId, $this->customerId, $input, null, 'Repeated'); $this->fail('Repeated acceptance allowed.'); }
        catch (RuntimeException) { $this->addToAssertionCount(1); }
        $this->assertSame(2, (int) fetch_one('SELECT COUNT(*) AS total FROM customer_policy_acceptances WHERE plan_id = :id', ['id' => $this->planId])['total']);
    }

    public function testFailureAfterRequestAndAcceptanceRollsBackBothStages(): void
    {
        $this->resetToPlanning();
        try {
            ict_confirmation_atomic(function (): void {
                ict_request_booking_acceptance($this->planId, $this->adminId, true);
                $input = array_fill_keys(['accept_booking_terms', 'accept_cancellation_policy', 'accept_payment_policy', 'accept_privacy_policy'], '1');
                $input['policy_snapshot_hash'] = $this->input['accepted_snapshot_hash'];
                ict_accept_booking_policies($this->planId, $this->customerId, $input, null, 'Synthetic rollback');
                throw new PDOException('Synthetic failure after acceptance');
            });
            $this->fail('Failure swallowed.');
        } catch (PDOException) { $this->addToAssertionCount(1); }
        $this->assertSame('planning', fetch_one('SELECT booking_status FROM customer_itinerary_plans WHERE id = :id', ['id' => $this->planId])['booking_status']);
        foreach (['contact_inquiry_messages', 'contact_inquiry_activity'] as $table) {
            $this->assertSame(0, (int) fetch_one('SELECT COUNT(*) AS total FROM ' . $table . ' WHERE inquiry_id = :id', ['id' => $this->inquiryId])['total']);
        }
        $this->assertSame(0, (int) fetch_one('SELECT COUNT(*) AS total FROM customer_policy_acceptances WHERE plan_id = :id', ['id' => $this->planId])['total']);
        $this->assertTrue(db()->inTransaction());
    }

    public function testFundsEvidenceIsBoundToAcceptedPlanAndRepeatIsRejected(): void
    {
        $id = ict_confirm_booking($this->planId, $this->adminId, $this->input);
        $row = fetch_one('SELECT * FROM contact_inquiry_activity WHERE id = :id', ['id' => $id]);
        $evidence = json_decode($row['note'], true, 16, JSON_THROW_ON_ERROR);
        $this->assertSame(ICT_CONFIRMATION_EVIDENCE_ACTION, $row['action_type']);
        $this->assertSame($this->planId, $evidence['plan_id']);
        $this->assertSame($this->adminId, $evidence['operator_admin_id']);
        $this->assertSame($this->input['accepted_snapshot_hash'], $evidence['accepted_snapshot_hash']);
        $this->assertSame((int) $this->input['acceptance_record_id'], $evidence['acceptance_record_id']);
        $this->assertSame('funds_received', $evidence['payment_status']);
        $this->assertStringEndsWith('+05:30', $evidence['recorded_at']);
        $this->assertSame('confirmed', fetch_one('SELECT booking_status FROM customer_itinerary_plans WHERE id = :id', ['id' => $this->planId])['booking_status']);
        try { ict_confirm_booking($this->planId, $this->adminId, $this->input); $this->fail('Repeat confirmation allowed.'); }
        catch (RuntimeException) { $this->addToAssertionCount(1); }
        $this->assertSame(1, (int) fetch_one('SELECT COUNT(*) AS total FROM contact_inquiry_messages WHERE inquiry_id = :id', ['id' => $this->inquiryId])['total']);
        $this->assertSame(1, (int) fetch_one('SELECT COUNT(*) AS total FROM contact_inquiry_activity WHERE inquiry_id = :id', ['id' => $this->inquiryId])['total']);
        $this->assertTrue(db()->inTransaction(), 'Confirmation must not commit caller fixtures.');
    }

    public function testExplicitNoPaymentDueQuoteTermIsRecorded(): void
    {
        $this->input['payment_status'] = 'no_payment_due';
        try { ict_confirm_booking($this->planId, $this->adminId, array_replace($this->input, ['payment_reference' => ''])); $this->fail('No-payment exception lacked an explicit term reference.'); }
        catch (InvalidArgumentException) { $this->addToAssertionCount(1); }
        $this->assertUnconfirmed();
        $this->input['payment_reference'] = 'SYN-QUOTE-01 section 4: no payment due at confirmation';
        $id = ict_confirm_booking($this->planId, $this->adminId, $this->input);
        $saved = json_decode(fetch_one('SELECT note FROM contact_inquiry_activity WHERE id = :id', ['id' => $id])['note'], true, 16, JSON_THROW_ON_ERROR);
        $this->assertSame('no_payment_due', $saved['payment_status']);
        $this->assertSame($this->input['payment_reference'], $saved['payment_reference']);
    }

    public function testMissingMalformedAndUncheckedEvidenceNeverConfirms(): void
    {
        foreach (['quote_reference', 'payment_reference', 'supplier_reference', 'payment_verified', 'supplier_verified', 'final_services_reviewed', 'accepted_snapshot_hash', 'acceptance_record_id'] as $key) {
            $invalid = $this->input; unset($invalid[$key]);
            try { ict_confirm_booking($this->planId, $this->adminId, $invalid); $this->fail('Incomplete evidence allowed.'); }
            catch (InvalidArgumentException) { $this->addToAssertionCount(1); }
        }
        foreach ([['payment_status' => 'waived'], ['payment_reference' => []], ['quote_reference' => str_repeat('x',191)], ['supplier_reference' => "hidden\ntext"], ['quote_reference' => "\xff"], ['payment_verified' => 'yes']] as $change) {
            try { ict_confirm_booking($this->planId, $this->adminId, array_replace($this->input,$change)); $this->fail('Malformed evidence allowed.'); }
            catch (InvalidArgumentException) { $this->addToAssertionCount(1); }
        }
        $this->assertUnconfirmed();
    }

    public function testNewAcceptanceRecordInvalidatesAnOldFormEvenWithSameTimeAndPolicies(): void
    {
        $this->appendAcceptance();
        try { ict_confirm_booking($this->planId, $this->adminId, $this->input); $this->fail('Stale acceptance form allowed.'); }
        catch (RuntimeException $exception) { $this->assertStringContainsString('Acceptance changed', $exception->getMessage()); }
        $this->assertUnconfirmed();
    }

    public function testChangedPolicyAndPendingChangesPreventConfirmation(): void
    {
        execute_query("UPDATE static_pages SET content = CONCAT(content, '<p>New policy revision.</p>') WHERE slug = 'booking-terms-and-conditions'");
        try { ict_confirm_booking($this->planId, $this->adminId, $this->input); $this->fail('Stale policies allowed.'); }
        catch (RuntimeException) { $this->addToAssertionCount(1); }
        $this->assertUnconfirmed();
        // A pending request independently blocks before an evidence write.
        execute_query("INSERT INTO customer_itinerary_change_requests (plan_id, plan_day_id, customer_user_id, request_reason) VALUES (:plan, 0, :customer, 'Synthetic pending change')", ['plan' => $this->planId, 'customer' => $this->customerId]);
        // Restore only fixture policy content so the request is the blocking condition.
        execute_query("UPDATE static_pages SET content = REPLACE(content, '<p>New policy revision.</p>', '') WHERE slug = 'booking-terms-and-conditions'");
        try { ict_confirm_booking($this->planId, $this->adminId, $this->input); $this->fail('Pending changes allowed.'); }
        catch (RuntimeException $exception) { $this->assertStringContainsString('pending', $exception->getMessage()); }
        $this->assertUnconfirmed();
    }

    public function testFailureRollsBackEvidenceStatusAndMessageTogether(): void
    {
        try {
            ict_confirmation_atomic(function (): void {
                ict_confirm_booking($this->planId, $this->adminId, $this->input);
                throw new PDOException('Synthetic persistence failure before outer commit');
            });
            $this->fail('Synthetic persistence failure swallowed.');
        } catch (PDOException) { $this->addToAssertionCount(1); }
        $this->assertUnconfirmed();
    }

    public function testNoPublishedPolicyDoesNotActivateConfirmation(): void
    {
        execute_query("UPDATE static_pages SET is_published = 0 WHERE slug = 'privacy-policy'");
        try { ict_confirm_booking($this->planId, $this->adminId, $this->input); $this->fail('Unpublished policies allowed.'); }
        catch (RuntimeException) { $this->addToAssertionCount(1); }
        $this->assertUnconfirmed();
    }

    public function testFormPreservesRejectedInputsButClearsChecksForANewerAcceptance(): void
    {
        $savedOld = $_SESSION[OLD_INPUT_SESSION_KEY] ?? null;
        try {
            $input = $this->input;
            $input['quote_reference'] = '<script>synthetic</script>';
            old_input_set(['plan_id' => $this->planId, 'confirmation_evidence' => $input]);
            $confirmationPlanId = $this->planId;
            $confirmationAcceptance = ['id' => (int) $input['acceptance_record_id'], 'policy_snapshot_hash' => $input['accepted_snapshot_hash']];
            ob_start(); require __DIR__ . '/../admin/_booking_confirmation_form.php'; $html = ob_get_clean();
            $this->assertStringContainsString('&lt;script&gt;synthetic&lt;/script&gt;', $html);
            $this->assertStringNotContainsString('<script>', $html);
            $this->assertSame(3, substr_count($html, 'checked required'));
            $confirmationAcceptance['id']++;
            ob_start(); require __DIR__ . '/../admin/_booking_confirmation_form.php'; $fresh = ob_get_clean();
            $this->assertStringNotContainsString('synthetic&lt;/script&gt;', $fresh);
            $this->assertSame(0, substr_count($fresh, 'checked required'));
        } finally {
            if (is_array($savedOld)) { old_input_set($savedOld); } else { old_input_clear(); }
        }
    }

    public function testOwnedTransactionCommitsCleanlyAndRollsBackAWriteOnFailure(): void
    {
        db()->rollBack(); // Remove setup fixtures before exercising the standalone branch.
        $this->assertTrue(ict_confirmation_atomic(static fn(): bool => db()->inTransaction()));
        $this->assertFalse(db()->inTransaction());
        $createdId = 0;
        try {
            ict_confirmation_atomic(static function () use (&$createdId): void {
                execute_query("INSERT INTO contact_inquiries (name, message) VALUES ('Synthetic owned rollback', 'Rollback-only transaction branch check')");
                $createdId = (int) last_insert_id();
                throw new PDOException('Synthetic standalone persistence failure');
            });
            $this->fail('Standalone failure swallowed.');
        } catch (PDOException) { $this->addToAssertionCount(1); }
        $this->assertGreaterThan(0, $createdId);
        $this->assertFalse(db()->inTransaction());
        $this->assertNull(fetch_one('SELECT id FROM contact_inquiries WHERE id = :id', ['id' => $createdId]));
    }
}
