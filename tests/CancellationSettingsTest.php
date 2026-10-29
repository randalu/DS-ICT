<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;
require_once __DIR__ . '/../includes/policy_acceptance.php';

final class CancellationSettingsTest extends TestCase
{
    public function testApprovedCancellationTermsCoverEveryBoundaryAndEscapeNotes(): void
    {
        $settings = ict_cancellation_defaults();
        $this->assertSame($settings, ict_cancellation_validate($settings));
        foreach ([60 => 0, 59 => 25, 30 => 25, 29 => 50, 14 => 50, 13 => 75, 8 => 75, 7 => 100, 0 => 100] as $days => $expected) {
            $band = current(array_filter($settings['bands'], static fn(array $row): bool => $days >= $row['min_days']));
            $this->assertSame($expected, $band['charge_percent']);
        }
        $html = ict_cancellation_draft_html($settings);
        $this->assertFalse(ict_policy_has_review_marker($html));
        $this->assertStringContainsString('Processing target: 14 business days', $html);
        $this->assertStringContainsString('total confirmed tour price', $html);
        $this->assertStringContainsString('minimum refund of zero', $html);
        $this->assertStringContainsString('Do not add the same costs again', $html);
        $this->assertStringContainsString('0–7 days', $html);
        $settings['notice_text'] = '<script>alert(1)</script>';
        $this->assertStringNotContainsString('<script>', ict_cancellation_draft_html($settings));
        $settings['bands'][4]['charge_percent'] = 90;
        $this->assertSame(90, ict_cancellation_validate($settings)['bands'][4]['charge_percent']);
    }

    public function testInvalidRangesChargesAndNestedFieldsAreRejected(): void
    {
        $cases = [];
        $input = ict_cancellation_defaults(); $input['bands'][1]['min_days'] = 60; $cases[] = $input;
        $input = ict_cancellation_defaults(); $input['bands'][2]['charge_percent'] = 101; $cases[] = $input;
        $input = ict_cancellation_defaults(); $input['bands'][2]['charge_percent'] = 5; $cases[] = $input;
        $input = ict_cancellation_defaults(); $input['bands'][4]['min_days'] = 1; $cases[] = $input;
        $input = ict_cancellation_defaults(); $input['deposit_rule'] = 'invented'; $cases[] = $input;
        $input = ict_cancellation_defaults(); $input['refund_business_days'] = 0; $cases[] = $input;
        $input = ict_cancellation_defaults(); $input['notice_text'] = []; $cases[] = $input;
        $input = ict_cancellation_defaults(); $input['before_confirmation_text'] = []; $cases[] = $input;
        foreach ($cases as $case) {
            try {
                ict_cancellation_validate($case);
                $this->fail('Invalid policy input accepted.');
            } catch (InvalidArgumentException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    public function testPublicCmsRenderingPreservesReadableChargeAndRefundBands(): void
    {
        $rendered = render_public_content(ict_cancellation_draft_html(ict_cancellation_defaults()));
        $dom = new DOMDocument();
        $dom->loadHTML('<?xml encoding="UTF-8"><body>' . $rendered . '</body>', LIBXML_NOERROR | LIBXML_NOWARNING);
        $items = (new DOMXPath($dom))->query('//ul/li');
        $this->assertCount(5, $items);
        foreach (['60+ days' => [0, 100], '30–59 days' => [25, 75], '14–29 days' => [50, 50], '8–13 days' => [75, 25], '0–7 days' => [100, 0]] as $index => $amounts) {
            $matched = false;
            foreach ($items as $item) {
                if (!str_contains($item->textContent, $index)) { continue; }
                $this->assertStringContainsString('cancellation charge ' . $amounts[0] . '% of the total confirmed tour price', $item->textContent);
                $this->assertStringContainsString('indicative refund if fully paid ' . $amounts[1] . '%', $item->textContent);
                $matched = true;
            }
            $this->assertTrue($matched, 'Missing readable public band: ' . $index);
        }
    }

    public function testOlderDraftKeepsCustomSettingsWhenNewNoteIsAbsent(): void
    {
        $input = ict_cancellation_defaults();
        unset($input['before_confirmation_text']);
        $input['bands'][1]['charge_percent'] = 30;
        $input['refund_text'] = 'An existing custom refund note.';
        $validated = ict_cancellation_validate($input);
        $this->assertSame(30, $validated['bands'][1]['charge_percent']);
        $this->assertSame($input['refund_text'], $validated['refund_text']);
        $this->assertSame(ict_cancellation_defaults()['before_confirmation_text'], $validated['before_confirmation_text']);
    }

    public function testExamplesStayConsistentWithEditedChargesAndDepositRule(): void
    {
        $settings = ict_cancellation_defaults();
        $html = ict_cancellation_draft_html($settings);
        $this->assertStringContainsString('applicable charge is USD 800 and the refund is USD 1200', $html);
        $this->assertStringContainsString('applicable charge of USD 1000 and a refund of USD 0', $html);
        $this->assertStringContainsString('Supplier-cost exception: the schedule is not a guaranteed refund.', $html);

        $settings['bands'][1]['charge_percent'] = 50;
        $this->assertStringContainsString('applicable charge is USD 1000 and the refund is USD 1000', ict_cancellation_draft_html($settings));
        $settings['bands'][1]['charge_percent'] = 5;
        $settings['bands'][2]['charge_percent'] = 10;
        $this->assertStringContainsString('applicable charge of USD 200 and a refund of USD 200', ict_cancellation_draft_html($settings));
        $settings['deposit_rule'] = 'non_refundable_if_disclosed';
        $this->assertStringContainsString('applicable charge of USD 400 and a refund of USD 0', ict_cancellation_draft_html($settings));
        $settings['supplier_rule'] = 'included';
        $this->assertStringNotContainsString('the schedule is not a guaranteed refund', ict_cancellation_draft_html($settings));
    }

    public function testSavePersistsSettingsAndSynchronizesOnlyUnpublishedCms(): void
    {
        ensure_site_settings_schema();
        $savedCache = $GLOBALS['_site_settings_cache'] ?? null;
        $page = fetch_one('SELECT id FROM static_pages WHERE slug = :slug', ['slug' => 'cancellation-refund-policy']);
        $this->assertNotNull($page, 'Local policy draft required.');
        db()->beginTransaction();
        try {
            execute_query('UPDATE static_pages SET is_published = 0 WHERE id = :id', ['id' => $page['id']]);
            $settings = ict_cancellation_defaults();
            $settings['bands'][1]['charge_percent'] = 30;
            $this->assertTrue(ict_cancellation_save($settings));
            $this->assertSame($settings, ict_cancellation_settings());
            $draft = fetch_one('SELECT content, is_published FROM static_pages WHERE id = :id', ['id' => $page['id']]);
            $this->assertSame(ict_policy_documents()['cancellation_refund']['content'], $draft['content']);
            $this->assertSame(0, (int) $draft['is_published']);

            $approved = '<p>Previously approved policy content.</p>';
            execute_query('UPDATE static_pages SET is_published = 1, content = :content WHERE id = :id', ['id' => $page['id'], 'content' => $approved]);
            $published = ict_policy_published_documents()['cancellation_refund'];
            $settings['bands'][1]['charge_percent'] = 35;
            $this->assertFalse(ict_cancellation_save($settings));
            $this->assertSame($published, ict_policy_published_documents()['cancellation_refund']);
            $this->assertTrue(db()->inTransaction(), 'No runtime DDL or commit may escape fixture rollback.');
        } finally {
            if (db()->inTransaction()) { db()->rollBack(); }
            if ($savedCache === null) { unset($GLOBALS['_site_settings_cache']); }
            else { $GLOBALS['_site_settings_cache'] = $savedCache; }
        }
    }

    public function testAdminFormUsesDedicatedPostHandlerAndGuardsBeforeStorage(): void
    {
        $source = file_get_contents(__DIR__ . '/../admin/policy_settings_save.php');
        $this->assertStringContainsString('admin_require_auth();', $source);
        $this->assertStringContainsString('if (!is_post())', $source);
        $this->assertLessThan(strpos($source, 'ict_cancellation_save($input)'), strpos($source, 'require_csrf();'));
        $this->assertLessThan(strpos($source, 'require_csrf();'), strpos($source, 'guard_post_size();'));
        $this->assertStringContainsString('old_input_set', $source);
        $this->assertStringContainsString('old_input_clear', $source);
    }
}
