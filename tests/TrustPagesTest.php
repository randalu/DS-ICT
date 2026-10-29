<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;
require_once __DIR__ . '/../includes/trust_pages.php';

final class TrustPagesTest extends TestCase
{
    public function testTemplatesCoverFourStableRoutesWithoutPublishingOrPlaceholders(): void
    {
        $drafts=trust_page_drafts();
        $this->assertSame(['trust-safety','drivers-vehicles','how-booking-works','tour-inclusions-exclusions'],array_keys($drafts));
        foreach ($drafts as $slug=>$page) {
            $this->assertSame(0,$page['is_published']);
            $this->assertSame(base_url($slug),$page['canonical_url']);
            $this->assertSame([],trust_page_publication_issues($page));
            $this->assertStringNotContainsString('<h1',$page['content']);
        }
    }

    public function testPublicationRejectsIncompleteMetadataPlaceholdersAndCanonicalConflict(): void
    {
        $page=trust_page_drafts()['trust-safety'];
        $page['meta_title']='';$page['content'].='<p>{{INSURER}}</p>';$page['canonical_url']='https://example.invalid/other';
        $this->assertGreaterThanOrEqual(3,count(trust_page_publication_issues($page)));
    }

    public function testFaqAcceptsBothSupportedShapesAndRejectsNestedValues(): void
    {
        $page=trust_page_drafts()['trust-safety'];
        foreach (['[{"q":"Who should I contact?","a":"Use your confirmation contacts."}]','[{"question":"Who should I contact?","answer":"Use your confirmation contacts."}]'] as $json) {
            $page['faq_json']=$json;$this->assertSame([],trust_page_publication_issues($page));
        }
        $page['faq_json']='[{"question":["bad"],"answer":"text"}]';
        $this->assertContains('Each FAQ needs a text question and answer.',trust_page_publication_issues($page));
    }

    public function testReviewMarkersAndExtraH1AreNotPublishable(): void
    {
        $page=trust_page_drafts()['trust-safety'];$page['content']='<h1>Heading</h1><p>Working draft for review.</p>';
        $issues=trust_page_publication_issues($page);
        $this->assertContains('Complete owner review before publication.',$issues);
        $this->assertContains('Use H2 headings in content; the public template supplies the H1.',$issues);
    }

    public function testRootLinksRespectDeploymentPrefix(): void
    {
        $this->assertFalse(published_static_page_has_unsafe_root_link('<a href="/privacy_choices.php">Choices</a>','https://example.test'));
        $this->assertFalse(published_static_page_has_unsafe_root_link('<a href="/island/privacy_choices.php">Choices</a>','https://example.test/island'));
        $this->assertTrue(published_static_page_has_unsafe_root_link('<a href="/privacy_choices.php">Choices</a>','https://example.test/island'));
    }

    public function testEmptyOptionalEditorHtmlKeepsPublicCtaFallbacks(): void
    {
        $page=trust_page_drafts()['drivers-vehicles'];$page['cta_text']='<p><br></p>';$page['cta_title']='&nbsp;';
        $page=trust_page_normalize_optional_fields($page);
        $this->assertSame('',$page['cta_text']);$this->assertSame('',$page['cta_title']);
        $this->assertSame([],trust_page_publication_issues($page));
    }
}
