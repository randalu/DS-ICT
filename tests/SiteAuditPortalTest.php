<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/site_audit_portal.php';

final class SiteAuditPortalTest extends TestCase
{
    public function testResourceGroupingRetainsCertaintyAndPageSpecificCauses(): void
    {
        $base=['id'=>1,'type'=>'resource_failure','target_url'=>'https://example.test/image.jpg','page_url'=>'https://example.test/a','certainty'=>'confirmed'];
        $rows=[$base,array_replace($base,['id'=>2,'page_url'=>'https://example.test/b','type'=>'broken_image']),array_replace($base,['id'=>3,'certainty'=>'inconclusive']),array_replace($base,['id'=>4,'target_url'=>'','type'=>'missing_media_assignment']),array_replace($base,['id'=>5,'target_url'=>'','type'=>'missing_media_assignment','page_url'=>'https://example.test/b'])];
        $groups=audit_issue_groups($rows);
        $this->assertCount(4,$groups);
        $this->assertSame(2,$groups[0]['occurrences']);
        $this->assertSame(2,$groups[0]['affected_pages']);
        $this->assertSame(['resource_failure','broken_image'],$groups[0]['rules']);
        $this->assertSame('inconclusive',$groups[1]['certainty']);
        $this->assertSame(5,array_sum(array_column($groups,'occurrences')));
    }
    public function testStableFingerprintDoesNotChangeWithDetailOrSeverity(): void
    {
        $raw=['page_url'=>'https://example.test/a','type'=>'broken_image','target_url'=>'https://example.test/x.jpg','source_location'=>'#hero','certainty'=>'confirmed','detail'=>'HTTP 404'];
        $a=audit_evidence($raw);$b=audit_evidence(array_replace($raw,['detail'=>'HTTP 410','severity'=>'high']));
        $this->assertSame($a['fingerprint'],$b['fingerprint']);
        $this->assertNotSame($a['fingerprint'],audit_evidence(array_replace($raw,['target_url'=>'https://example.test/y.jpg']))['fingerprint']);
        $this->assertSame('inconclusive',audit_evidence(array_replace($raw,['certainty'=>'definitely']))['certainty']);
    }
    public function testPublicContextDropsQueriesCredentialsAndPrivatePaths(): void
    {
        $this->assertSame('https://example.test/a',audit_public_url('https://example.test/a?token=secret#customer'));
        $this->assertSame('',audit_public_url('https://example.test/admin/inquiries.php'));
        $this->assertStringNotContainsString('secret',audit_redact_text('See https://example.test/a?token=secret owner@example.test'));
        $this->assertStringNotContainsString('owner@example.test',audit_redact_text('owner@example.test'));
        $this->assertStringNotContainsString('sk-abcdef',audit_redact_text('sk-abcdef'));
    }
    public function testReviewRequiresExactSelectedIdsAndBoundedFields(): void
    {
        $row=['issue_id'=>17,'priority'=>'high','explanation'=>'Measured failure','action'=>'Review the image','dependencies'=>[],'verification'=>['Recheck']];
        $this->assertSame(17,audit_decode_review(audit_json(['recommendations'=>[$row]]),[17])['recommendations'][0]['issue_id']);
        foreach ([['recommendations'=>[array_replace($row,['issue_id'=>18])]],['recommendations'=>[$row,$row]],['recommendations'=>[array_replace($row,['priority'=>'urgent'])]],['recommendations'=>[array_replace($row,['verification'=>[123]])]],['recommendations'=>[array_replace($row,['execute'=>'DROP'])]]] as $bad) {
            try { audit_decode_review(audit_json($bad),[17]); $this->fail('Invalid recommendation accepted'); } catch (RuntimeException) { $this->addToAssertionCount(1); }
        }
    }
    public function testEditorMappingOnlyEnablesSupportedPortalTypes(): void
    {
        $this->assertArrayHasKey('seo',audit_editor_links(['type'=>'destination','id'=>1]));
        $this->assertArrayHasKey('images',audit_editor_links(['type'=>'itinerary','id'=>1]));
        foreach(['experience','blog','page','blog_landing'] as $type){$this->assertArrayHasKey('seo',audit_editor_links(['type'=>$type,'id'=>1]));$this->assertArrayHasKey('images',audit_editor_links(['type'=>$type,'id'=>1]));}
        $this->assertSame([],audit_editor_links(['type'=>'inquiry','id'=>1]));
    }
}
