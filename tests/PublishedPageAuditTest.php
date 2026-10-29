<?php
declare(strict_types=1);

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/published_page_audit.php';

final class PublishedPageAuditTest extends TestCase
{
    public function testTrustRouteInventoryMatchesReleasePlan(): void
    {
        $routes = published_static_page_trust_routes();

        $this->assertCount(8, $routes);
        $this->assertSame('/booking-terms-and-conditions', $routes['booking-terms-and-conditions']);
        $this->assertSame('/tour-inclusions-exclusions', $routes['tour-inclusions-exclusions']);
    }

    public function testValidPublishedPageHasNoPlaceholderIssues(): void
    {
        $this->assertSame([], published_static_page_placeholder_issues($this->validPage()));
    }

    public function testOptionalEmptyFieldsUseRendererFallbacks(): void
    {
        $page = $this->validPage([
            'meta_title' => '',
            'meta_description' => '',
            'canonical_url' => '',
            'cta_title' => '',
            'cta_text' => '',
            'hero_image' => '',
            'faq_json' => '',
        ]);

        $this->assertSame([], published_static_page_placeholder_issues($page));
    }

    #[DataProvider('placeholderPageProvider')]
    public function testPublishedPagePlaceholdersAreReported(string $field, string $value, string $reason): void
    {
        $issues = published_static_page_placeholder_issues($this->validPage([$field => $value]));
        $matchingIssues = array_values(array_filter(
            $issues,
            static fn(array $issue): bool => $issue['field'] === $field
                && str_contains($issue['reason'], $reason)
        ));

        $this->assertNotEmpty($matchingIssues, sprintf('Expected %s to report %s.', $field, $reason));
    }

    public static function placeholderPageProvider(): array
    {
        return [
            'placeholder title' => ['title', 'Draft', 'placeholder text'],
            'placeholder content token' => ['content', '<p>{{LEGAL_ENTITY}}</p>', 'unresolved placeholder token'],
            'placeholder metadata' => ['meta_description', 'TBD', 'placeholder text'],
            'placeholder media' => ['hero_image', 'assets/uploads/placeholders/hero.jpg', 'placeholder media'],
            'placeholder FAQ value' => [
                'faq_json',
                '[{"question":"placeholder","answer":"Contact us for details."}]',
                'FAQ item 0 question',
            ],
        ];
    }

    public function testMissingTitleAndVisibleContentArePublishBlockers(): void
    {
        $issues = published_static_page_placeholder_issues($this->validPage([
            'title' => '',
            'content' => '<p></p>',
        ]));

        $this->assertContains(['field' => 'title', 'reason' => 'missing publishable value'], $issues);
        $this->assertContains(['field' => 'content', 'reason' => 'missing publishable value'], $issues);
    }

    public function testPlaceholderTokenExtractionIsUniqueAndCasePreserving(): void
    {
        $tokens = published_static_page_placeholder_tokens('TODO {{POLICY_VERSION}} todo {{POLICY_VERSION}} FIXME');

        $this->assertSame(['{{POLICY_VERSION}}', 'TODO', 'todo', 'FIXME'], $tokens);
    }

    private function validPage(array $overrides = []): array
    {
        return array_merge([
            'id' => 1,
            'title' => 'Booking Terms and Conditions',
            'slug' => 'booking-terms-and-conditions',
            'content' => '<p>These terms explain how approved travel arrangements are confirmed.</p>',
            'faq_json' => '[{"question":"Can the route change?","answer":"Discuss changes with the travel team before confirmation."}]',
            'cta_title' => 'Plan your private Sri Lanka trip',
            'cta_text' => 'Share your dates and route priorities with our team.',
            'hero_image' => 'assets/uploads/pages/booking-terms.jpg',
            'hero_video_poster' => '',
            'meta_title' => 'Booking Terms and Conditions | Island Culture Travels',
            'meta_description' => 'Read the approved booking terms for private Sri Lanka travel planning.',
            'canonical_url' => '',
            'is_published' => 1,
        ], $overrides);
    }
}
