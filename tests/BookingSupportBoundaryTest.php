<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
require_once __DIR__ . '/../includes/customer_auth.php';

final class BookingSupportBoundaryTest extends TestCase
{
    private array $customer;
    private int $itineraryId;
    private int $planId;

    protected function setUp(): void
    {
        // Warm existing runtime guards before the transaction: MySQL DDL commits implicitly.
        ensure_customer_accounts_schema();
        $itinerary = fetch_one('SELECT id FROM itineraries WHERE is_published = 1 LIMIT 1');
        $this->assertNotNull($itinerary, 'A local published itinerary is required for this integration test.');
        $this->itineraryId = (int) $itinerary['id'];
        db()->beginTransaction();
        $token = bin2hex(random_bytes(8));
        execute_query('INSERT INTO customer_users (google_sub, email, name) VALUES (:sub, :email, :name)', [
            'sub' => 'support-boundary-' . $token, 'email' => $token . '@example.invalid', 'name' => 'Boundary test',
        ]);
        $this->customer = ['id' => (int) last_insert_id()];
        execute_query('INSERT INTO contact_inquiries (name, message, customer_user_id) VALUES (:name, :message, :customer)', [
            'name' => 'Boundary test', 'message' => 'Transaction-only test fixture', 'customer' => $this->customer['id'],
        ]);
        $inquiryId = (int) last_insert_id();
        execute_query("INSERT INTO customer_itinerary_plans
            (customer_user_id, contact_inquiry_id, source_itinerary_id, plan_title, trip_support_type, plan_status, booking_status)
            VALUES (:customer, :inquiry, :itinerary, 'Boundary test', 'self_booked_adventure', 'confirmed', 'accepted')", [
            'customer' => $this->customer['id'], 'inquiry' => $inquiryId, 'itinerary' => $this->itineraryId,
        ]);
        $this->planId = (int) last_insert_id();
    }

    protected function tearDown(): void
    {
        if (db()->inTransaction()) { db()->rollBack(); }
    }

    public function testActualCustomizerResetsReviewedSupportAndInvalidatesAcceptance(): void
    {
        $documents = ict_policy_documents();
        foreach (ict_policy_required_keys() as $key) {
            $documents[$key]['version'] = ict_policy_version_for_document($documents[$key]['title'], $documents[$key]['content'], $documents[$key]['slug']);
        }
        $snapshot = ict_policy_acceptance_snapshot($documents);
        execute_query('UPDATE customer_itinerary_plans SET booking_terms_version = :terms,
            cancellation_policy_version = :cancel, payment_policy_version = :payment, privacy_policy_version = :privacy,
            policy_snapshot_json = :json, policy_snapshot_hash = :hash, policy_accepted_at = NOW(),
            booking_terms_accepted_at = NOW(), cancellation_policy_accepted_at = NOW() WHERE id = :id', [
            'terms' => $documents['booking_terms']['version'], 'cancel' => $documents['cancellation_refund']['version'],
            'payment' => $documents['payment_deposit']['version'], 'privacy' => $documents['privacy']['version'],
            'json' => $snapshot['json'], 'hash' => $snapshot['hash'], 'id' => $this->planId,
        ]);
        execute_query('INSERT INTO customer_policy_acceptances
            (plan_id, customer_user_id, booking_terms_version, booking_terms_accepted_at, cancellation_policy_version,
             cancellation_policy_accepted_at, payment_policy_version, privacy_policy_version, policy_snapshot_hash, policy_snapshot_json, accepted_at)
            SELECT id, customer_user_id, booking_terms_version, booking_terms_accepted_at, cancellation_policy_version,
             cancellation_policy_accepted_at, payment_policy_version, privacy_policy_version, policy_snapshot_hash, policy_snapshot_json, policy_accepted_at
             FROM customer_itinerary_plans WHERE id = :id', ['id' => $this->planId]);
        $before = fetch_one('SELECT * FROM customer_itinerary_plans WHERE id = :id', ['id' => $this->planId]);
        $this->assertTrue(ict_policy_plan_acceptance_state($before, array_intersect_key($documents, array_flip(ict_policy_required_keys())))['ready_to_confirm']);
        foreach (['accepted', 'awaiting_acceptance', 'planning'] as $status) {
            execute_query("UPDATE customer_itinerary_plans SET booking_status = :status, trip_support_type = 'self_booked_adventure' WHERE id = :id", ['status' => $status, 'id' => $this->planId]);
            $plan = customer_start_itinerary_customization($this->customer, $this->itineraryId, 'private_guide_trip');
            $this->assertSame('private_guide_trip', $plan['trip_support_type']);
            $this->assertSame('planning', $plan['booking_status']);
            $this->assertSame('reviewing', $plan['plan_status']);
            $this->assertNull($plan['policy_accepted_at']);
            $this->assertFalse(ict_policy_plan_acceptance_state($plan, array_intersect_key($documents, array_flip(ict_policy_required_keys())))['ready_to_confirm']);
            $this->assertSame($snapshot['json'], $plan['policy_snapshot_json']);
        }
        $archive = fetch_one('SELECT COUNT(*) AS total FROM customer_policy_acceptances WHERE plan_id = :id', ['id' => $this->planId]);
        $this->assertSame(1, (int) $archive['total']);
        $inquiry = fetch_one('SELECT trip_support_type FROM contact_inquiries WHERE id = :id', ['id' => $before['contact_inquiry_id']]);
        $this->assertSame('private_guide_trip', $inquiry['trip_support_type']);
        $activity = fetch_one("SELECT COUNT(*) AS total FROM contact_inquiry_activity WHERE inquiry_id = :id AND action_type = 'customer_plan_support_changed'", ['id' => $before['contact_inquiry_id']]);
        $this->assertSame(3, (int) $activity['total']);
        $this->assertTrue(db()->inTransaction(), 'Customizer must not commit the caller transaction.');
    }

    public function testConfirmedBookingRequiresAnAmendmentAndUnchangedSupportCanReopen(): void
    {
        execute_query("UPDATE customer_itinerary_plans SET booking_status = 'confirmed', booking_confirmed_at = NOW() WHERE id = :id", ['id' => $this->planId]);
        $before = fetch_one('SELECT * FROM customer_itinerary_plans WHERE id = :id', ['id' => $this->planId]);
        try {
            customer_start_itinerary_customization($this->customer, $this->itineraryId, 'private_guide_trip');
            $this->fail('Confirmed support must not change without an amendment.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('request an amendment', $exception->getMessage());
        }
        $this->assertSame($before, fetch_one('SELECT * FROM customer_itinerary_plans WHERE id = :id', ['id' => $this->planId]));
        $this->assertSame($before, customer_start_itinerary_customization($this->customer, $this->itineraryId, 'self_booked_adventure'));
    }

    public function testSameSupportPreservesAcceptanceAndWriterEnforcesOwnership(): void
    {
        execute_query('UPDATE customer_itinerary_plans SET policy_accepted_at = NOW(), policy_snapshot_json = :json WHERE id = :id', [
            'json' => '{"retained":"acceptance history"}', 'id' => $this->planId,
        ]);
        $before = fetch_one('SELECT * FROM customer_itinerary_plans WHERE id = :id', ['id' => $this->planId]);
        $this->assertSame($before, customer_start_itinerary_customization($this->customer, $this->itineraryId, 'self_booked_adventure'));
        $this->assertNull(customer_update_plan_support_type($this->planId, 0, 'private_guide_trip'));
        $this->assertSame($before, fetch_one('SELECT * FROM customer_itinerary_plans WHERE id = :id', ['id' => $this->planId]));
    }
}
