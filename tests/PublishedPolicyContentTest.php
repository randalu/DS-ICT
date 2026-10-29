<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../includes/policy_acceptance.php';

final class PublishedPolicyContentTest extends TestCase
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
    }

    public function testRequiredAcceptanceVersionsAndColumnsAreComplete(): void
    {
        $versions = ict_policy_versions();

        $this->assertSame(
            ['booking_terms', 'cancellation_refund', 'payment_deposit', 'privacy'],
            ict_policy_required_keys()
        );
        $this->assertSame(array_keys($versions), ict_policy_required_keys());
        foreach ($versions as $key => $version) {
            $pattern = $key === 'privacy' ? '/^2026-10-23-[a-f0-9]{8}$/'
                : ($key === 'cancellation_refund' ? '/^2026-10-14-[a-f0-9]{8}$/' : '/^2026-10-14-1$/');
            $this->assertMatchesRegularExpression($pattern, $version);
        }

        $columns = ict_policy_acceptance_columns();
        foreach ([
            'booking_status',
            'booking_terms_version',
            'booking_terms_accepted_at',
            'cancellation_policy_version',
            'cancellation_policy_accepted_at',
            'payment_policy_version',
            'privacy_policy_version',
            'marketing_consent',
            'marketing_consent_at',
            'customer_ip',
            'customer_user_agent',
            'policy_snapshot_hash',
            'policy_snapshot_json',
            'policy_accepted_at',
            'booking_confirmed_at',
            'booking_confirmed_by_admin_user_id',
        ] as $column) {
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
            $documents[$key] = [
                'slug' => $key . '-policy',
                'title' => ucfirst(str_replace('_', ' ', $key)),
                'content' => '<p>Current reviewed policy content for ' . $key . '.</p>',
            ];
            $documents[$key]['version'] = ict_policy_version_for_document(
                $documents[$key]['title'],
                $documents[$key]['content'],
                $documents[$key]['slug']
            );
        }

        $snapshot = ict_policy_acceptance_snapshot($documents);
        $this->assertStringContainsString($documents['booking_terms']['content'], $snapshot['json']);
        $plan = [
            'booking_terms_version' => $documents['booking_terms']['version'],
            'cancellation_policy_version' => $documents['cancellation_refund']['version'],
            'payment_policy_version' => $documents['payment_deposit']['version'],
            'privacy_policy_version' => $documents['privacy']['version'],
            'policy_accepted_at' => '2026-09-26 12:00:00',
            'policy_snapshot_hash' => $snapshot['hash'],
            'policy_snapshot_json' => $snapshot['json'],
        ];

        $state = ict_policy_plan_acceptance_state($plan, $documents);
        $this->assertTrue($state['accepted']);
        $this->assertTrue($state['ready_to_confirm']);
        $this->assertSame('Accepted, awaiting operator confirmation', ict_booking_status_label('accepted'));
        $this->assertSame('Booking confirmed', ict_booking_status_label('confirmed'));

        $documents['booking_terms']['content'] .= '<p>New version.</p>';
        $documents['booking_terms']['version'] = ict_policy_version_for_document(
            $documents['booking_terms']['title'],
            $documents['booking_terms']['content'],
            $documents['booking_terms']['slug']
        );
        $changedState = ict_policy_plan_acceptance_state($plan, $documents);
        $this->assertTrue($changedState['accepted'], 'Original acceptance remains in the snapshot history.');
        $this->assertFalse($changedState['current_versions']);
        $this->assertFalse($changedState['ready_to_confirm']);

        $plan['policy_snapshot_hash'] = str_repeat('0', 64);
        $this->assertFalse(ict_policy_plan_acceptance_state($plan, $documents)['accepted']);
    }

}
