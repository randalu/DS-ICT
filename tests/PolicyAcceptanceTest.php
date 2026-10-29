<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
require_once __DIR__ . '/../includes/policy_acceptance.php';

final class PolicyAcceptanceTest extends TestCase
{
    public function testApprovedPoliciesCarryIdentityAndNoDraftOrTemplateTokens(): void
    {
        $identity = ict_policy_business_identity();
        $documents = ict_policy_documents();
        $this->assertSame('Island Culture Travels', $identity['name']);
        $this->assertSame('163/3 Niwasa Udyana Mawatha, Raddolugama 11390 (Katunayake)', $identity['address']);
        $this->assertCount(5, $documents);
        foreach ($documents as $document) {
            $this->assertNotSame('', trim((string) $document['content']));
            $this->assertStringNotContainsString('{{', (string) $document['content']);
            $this->assertFalse(ict_policy_has_review_marker((string) $document['content']));
            $this->assertStringNotContainsString('Review pending', (string) $document['content']);
            $this->assertStringNotContainsString('Working draft', (string) $document['meta_description']);
        }
        $this->assertStringContainsString('IP address', $documents['privacy']['content']);
        $this->assertStringContainsString('user-agent', strtolower($documents['privacy']['content']));
        $this->assertStringContainsString('First-party website statistics operate for public page visits', $documents['privacy']['content']);
    }

    public function testRequiredAcceptanceVersionsAndColumnsAreComplete(): void
    {
        $versions = ict_policy_versions();
        $this->assertSame(['booking_terms', 'cancellation_refund', 'payment_deposit', 'privacy'], ict_policy_required_keys());
        $this->assertSame(array_keys($versions), ict_policy_required_keys());
        foreach ($versions as $key => $version) {
            $pattern = match ($key) {
                'privacy' => '/^2026-10-23-[a-f0-9]{8}$/',
                'cancellation_refund' => '/^2026-10-14-[a-f0-9]{8}$/',
                default => '/^2026-10-14-1$/',
            };
            $this->assertMatchesRegularExpression($pattern, $version);
        }
        $columns = ict_policy_acceptance_columns();
        foreach (['booking_status', 'booking_terms_version', 'booking_terms_accepted_at',
            'cancellation_policy_version', 'cancellation_policy_accepted_at', 'payment_policy_version',
            'privacy_policy_version', 'marketing_consent', 'marketing_consent_at', 'customer_ip',
            'customer_user_agent', 'policy_snapshot_hash', 'policy_snapshot_json', 'policy_accepted_at',
            'booking_confirmed_at', 'booking_confirmed_by_admin_user_id'] as $column) {
            $this->assertArrayHasKey($column, $columns);
        }
    }

    public function testFinalWordingPreservesInsuranceLimitsAndLegacyReviewGate(): void
    {
        $documents = ict_policy_documents();
        foreach (['booking_terms','book_with_confidence'] as $key) {
            $this->assertStringContainsString('passenger cover while you are in the vehicle', $documents[$key]['content']);
            $this->assertStringContainsString('terms, limits and exclusions', $documents[$key]['content']);
            $this->assertStringContainsString('not a compulsory booking requirement', $documents[$key]['content']);
            $this->assertStringNotContainsString('fully insured', strtolower($documents[$key]['content']));
        }
        $this->assertTrue(ict_policy_has_review_marker('<p>Working draft for review.</p>'));
        $this->assertStringContainsString('optional marketing are separate', $documents['privacy']['content']);
        $this->assertStringContainsString('Analytics disposal is paused', $documents['privacy']['content']);
        $this->assertStringNotContainsString('Folder creation', $documents['privacy']['content']);
    }

    public function testPolicySnapshotHashChangesWhenPublishedTextChanges(): void
    {
        $documents = ict_policy_documents();
        $first = ict_policy_acceptance_snapshot($documents);
        $documents['booking_terms']['content'] .= '<p>Reviewed revision.</p>';
        $second = ict_policy_acceptance_snapshot($documents);
        $this->assertNotSame($first['hash'], $second['hash']);
        $this->assertNotSame($first['json'], $second['json']);
    }

    public function testContentVersionsChangeWhenCmsPolicyTextChanges(): void
    {
        $version = ict_policy_version_for_content('<p>Approved text.</p>');
        $this->assertMatchesRegularExpression('/^v1-[a-f0-9]{32}$/', $version);
        $this->assertNotSame($version, ict_policy_version_for_content('<p>Revised text.</p>'));
    }

    public function testWorkingDraftMarkerPreventsPolicyActivation(): void
    {
        $this->assertTrue(ict_policy_has_review_marker('<p><strong>Working draft for review.</strong> Confirm details.</p>'));
        $this->assertFalse(ict_policy_has_review_marker('<p>Approved policy text after review.</p>'));
    }

    public function testAcceptanceIsDistinctFromOperatorBookingConfirmation(): void
    {
        $documents = [];
        foreach (ict_policy_required_keys() as $key) {
            $documents[$key] = ['slug' => $key . '-policy', 'title' => ucfirst(str_replace('_', ' ', $key)),
                'content' => '<p>Current reviewed policy content for ' . $key . '.</p>'];
            $documents[$key]['version'] = ict_policy_version_for_document($documents[$key]['title'], $documents[$key]['content'], $documents[$key]['slug']);
        }
        $snapshot = ict_policy_acceptance_snapshot($documents);
        $this->assertStringContainsString($documents['booking_terms']['content'], $snapshot['json']);
        $plan = ['booking_terms_version' => $documents['booking_terms']['version'],
            'cancellation_policy_version' => $documents['cancellation_refund']['version'],
            'payment_policy_version' => $documents['payment_deposit']['version'],
            'privacy_policy_version' => $documents['privacy']['version'], 'policy_accepted_at' => '2026-09-26 12:00:00',
            'policy_snapshot_hash' => $snapshot['hash'], 'policy_snapshot_json' => $snapshot['json']];
        $state = ict_policy_plan_acceptance_state($plan, $documents);
        $this->assertTrue($state['accepted']);
        $this->assertTrue($state['ready_to_confirm']);
        $this->assertSame('Accepted, awaiting operator confirmation', ict_booking_status_label('accepted'));
        $this->assertSame('Booking confirmed', ict_booking_status_label('confirmed'));
        $documents['booking_terms']['content'] .= '<p>New version.</p>';
        $documents['booking_terms']['version'] = ict_policy_version_for_document($documents['booking_terms']['title'], $documents['booking_terms']['content'], $documents['booking_terms']['slug']);
        $changedState = ict_policy_plan_acceptance_state($plan, $documents);
        $this->assertTrue($changedState['accepted'], 'Original acceptance remains in the snapshot history.');
        $this->assertFalse($changedState['current_versions']);
        $this->assertFalse($changedState['ready_to_confirm']);
        $plan['policy_snapshot_hash'] = str_repeat('0', 64);
        $this->assertFalse(ict_policy_plan_acceptance_state($plan, $documents)['accepted']);
    }

    public function testAcceptanceAndAdminConfirmationHandlersKeepTheirSecurityGates(): void
    {
        $customerHandler = file_get_contents(__DIR__ . '/../account_plan_policy_accept.php');
        $adminHandler = file_get_contents(__DIR__ . '/../admin/customer_plan_booking_status_save.php');
        $pageSaveHandler = file_get_contents(__DIR__ . '/../admin/page_save.php');
        $this->assertIsString($customerHandler);
        $this->assertLessThan(strpos($customerHandler, 'ensure_customer_accounts_schema();'), strpos($customerHandler, 'require_csrf();'));
        $workflowHelper = file_get_contents(__DIR__ . '/../includes/booking_workflow.php');
        $this->assertStringContainsString('ict_accept_booking_policies(', $customerHandler);
        $this->assertStringContainsString('LIMIT 1 FOR UPDATE', $workflowHelper);
        $this->assertStringContainsString('booking_status = :expected_status', $workflowHelper);
        $this->assertStringContainsString("'booking_status' => 'accepted'", $workflowHelper);
        $this->assertStringContainsString('quotation_token', $customerHandler);
        $this->assertStringContainsString('quote_check_customer_token', $workflowHelper);
        $this->assertStringNotContainsString('plan_status =', $customerHandler);
        $this->assertIsString($adminHandler);
        $this->assertLessThan(strpos($adminHandler, 'ensure_customer_accounts_schema();'), strpos($adminHandler, 'require_csrf();'));
        $this->assertStringContainsString('admin_require_auth();', $adminHandler);
        $this->assertStringContainsString('ict_confirm_booking($planId, $adminId, $input)', $adminHandler);
        $confirmationHelper = file_get_contents(__DIR__ . '/../includes/booking_confirmation.php');
        $this->assertStringContainsString('booking_confirmed_by_admin_user_id', $confirmationHelper);
        $this->assertStringContainsString('quote_assert_binding', $confirmationHelper);
        $this->assertIsString($pageSaveHandler);
        $this->assertStringContainsString('trust_page_publication_issues', $pageSaveHandler);
        $this->assertStringContainsString('trust_owner_approved', $pageSaveHandler);
    }
}
