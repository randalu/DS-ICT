<?php
declare(strict_types=1);

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;

// PHPUnit's separate PHP processes do not inherit the parent's -d override.
if (session_status() !== PHP_SESSION_ACTIVE
    && preg_match('/\Aict_(?:r1_trust_audit_20261015|r2_trust_20261020)_[a-f0-9]{6}\z/', (string) getenv('DB_NAME'))) {
    ini_set('session.save_path', __DIR__ . '/../output/session');
}
require_once __DIR__ . '/../includes/functions.php';

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class PublicTrustLinksTest extends TestCase
{
    private const LABELS = [
        'booking-terms-and-conditions' => 'Booking terms and conditions',
        'cancellation-refund-policy' => 'Cancellation and refund policy',
        'payment-deposit-policy' => 'Payment and deposit policy',
        'book-with-confidence' => 'Book with confidence',
        'trust-safety' => 'Trust and safety',
        'drivers-vehicles' => 'Drivers and vehicles',
        'how-booking-works' => 'How booking works',
        'tour-inclusions-exclusions' => 'Tour inclusions and exclusions',
    ];

    private array $savedPages = [];
    private ?array $savedFooter = null;

    protected function setUp(): void
    {
        // These rendered integration cases must never write to the ordinary local DB.
        if (!preg_match('/\Aict_(?:r1_trust_audit_20261015|r2_trust_20261020)_[a-f0-9]{6}\z/', DB_NAME)) {
            $this->markTestSkipped('Requires the disposable R1 public-content database.');
        }
        $this->savedPages = fetch_all('SELECT * FROM static_pages WHERE slug IN (' . $this->placeholders() . ')', array_keys(self::LABELS));
        $this->savedFooter = fetch_one("SELECT * FROM site_settings WHERE setting_key = 'footer_bottom_links'");
        execute_query('DELETE FROM static_pages WHERE slug IN (' . $this->placeholders() . ')', array_keys(self::LABELS));
        foreach (self::LABELS as $slug => $label) {
            execute_query('INSERT INTO static_pages (slug,title,content,is_published,meta_description,canonical_url) VALUES (?,?,?,1,?,?)', [$slug, $label, '<p>Synthetic public link-verification fixture.</p>', 'Synthetic public link-verification fixture.', '']);
        }
        site_setting_set('footer_bottom_links', json_encode([['url' => 'faq', 'label' => 'FAQ'], ['url' => 'contact', 'label' => 'Contact']], JSON_THROW_ON_ERROR));
    }

    protected function tearDown(): void
    {
        if (!preg_match('/\Aict_(?:r1_trust_audit_20261015|r2_trust_20261020)_[a-f0-9]{6}\z/', DB_NAME)) {
            return;
        }
        execute_query('DELETE FROM static_pages WHERE slug IN (' . $this->placeholders() . ')', array_keys(self::LABELS));
        foreach ($this->savedPages as $row) {
            $columns = array_keys($row);
            execute_query('INSERT INTO static_pages (`' . implode('`,`', $columns) . '`) VALUES (' . implode(',', array_fill(0, count($columns), '?')) . ')', array_values($row));
        }
        execute_query("DELETE FROM site_settings WHERE setting_key = 'footer_bottom_links'");
        if ($this->savedFooter !== null) {
            $columns = array_keys($this->savedFooter);
            execute_query('INSERT INTO site_settings (`' . implode('`,`', $columns) . '`) VALUES (' . implode(',', array_fill(0, count($columns), '?')) . ')', array_values($this->savedFooter));
        }
    }

    public function testPublishedPagesAreLinkedOutsideTheInquiryFormAndInTheFooter(): void
    {
        $xpath = $this->renderContact();
        $this->assertLinkSet($xpath, '//nav[@aria-label="Booking and trust"]', array_keys(self::LABELS));
        $this->assertLinkSet($xpath, '//*[@id="contact-booking-information"]', array_keys(self::LABELS));
        $this->assertCount(0, $xpath->query('//*[@id="contact-booking-information"]/ancestor::form'));
        $this->assertCount(0, $xpath->query('//*[@id="contact-booking-information"]//input'));
        $this->assertCount(1, $xpath->query('//form[@id="contact-wizard-form"][@method="post"]'));
        $this->assertSame(base_url('contact'), $xpath->evaluate('string(//form[@id="contact-wizard-form"]/@action)'));
        $this->assertGreaterThan(0, $xpath->query('//form[@id="contact-wizard-form"]//input[@type="hidden"]')->length);
        $this->assertCount(1, $xpath->query('//*[@id="wizard-submit"]'));
        $this->assertSame(0, (int) $xpath->evaluate('count(//form[@id="contact-wizard-form"]//a[contains(@href,"booking-terms-and-conditions")])'));
    }

    public static function policySlugs(): array
    {
        return array_map(static fn(string $slug): array => [$slug], array_keys(self::LABELS));
    }

    #[DataProvider('policySlugs')]
    public function testEachUnpublishedTargetIsOmittedFromBothPlacements(string $slug): void
    {
        execute_query('UPDATE static_pages SET is_published=0 WHERE slug=?', [$slug]);
        $xpath = $this->renderContact();
        $expected = array_values(array_diff(array_keys(self::LABELS), [$slug]));
        $this->assertLinkSet($xpath, '//nav[@aria-label="Booking and trust"]', $expected);
        $this->assertLinkSet($xpath, '//*[@id="contact-booking-information"]', $expected);
    }

    public function testMissingTargetIsOmitted(): void
    {
        execute_query('DELETE FROM static_pages WHERE slug=?', ['book-with-confidence']);
        $xpath = $this->renderContact();
        $this->assertLinkSet($xpath, '//nav[@aria-label="Booking and trust"]', array_values(array_diff(array_keys(self::LABELS), ['book-with-confidence'])));
    }

    public function testEmptyEligibilityHidesBothGroups(): void
    {
        execute_query('UPDATE static_pages SET is_published=0 WHERE slug IN (' . $this->placeholders() . ')', array_keys(self::LABELS));
        $xpath = $this->renderContact();
        $this->assertCount(0, $xpath->query('//nav[@aria-label="Booking and trust"]'));
        $this->assertCount(0, $xpath->query('//*[@id="contact-booking-information"]'));
    }

    public static function conflictingCanonicals(): array
    {
        return [['https://example.invalid/book-with-confidence'], ['different-policy'], ['book-with-confidence?alternate=1']];
    }

    #[DataProvider('conflictingCanonicals')]
    public function testConflictingCanonicalIsNotPromoted(string $canonical): void
    {
        if (!str_starts_with($canonical, 'https://')) {
            $canonical = base_url($canonical);
        }
        execute_query('UPDATE static_pages SET canonical_url=? WHERE slug=?', [$canonical, 'book-with-confidence']);
        $xpath = $this->renderContact();
        $this->assertLinkSet($xpath, '//nav[@aria-label="Booking and trust"]', array_values(array_diff(array_keys(self::LABELS), ['book-with-confidence'])));
        $this->assertLinkSet($xpath, '//*[@id="contact-booking-information"]', array_values(array_diff(array_keys(self::LABELS), ['book-with-confidence'])));
    }

    public function testConfiguredFooterLinksKeepTheirOrderAndAreNotRepeatedInTheNewGroup(): void
    {
        $configured = [['url' => 'contact', 'label' => 'Contact'], ['url' => '/booking-terms-and-conditions/', 'label' => 'Existing booking terms'], ['url' => 'faq', 'label' => 'FAQ']];
        site_setting_set('footer_bottom_links', json_encode($configured, JSON_THROW_ON_ERROR));
        $xpath = $this->renderContact();
        $this->assertLinkSet($xpath, '//nav[@aria-label="Booking and trust"]', array_slice(array_keys(self::LABELS), 1));
        $bottomLinks = $xpath->query('//div[contains(@class,"footer-bottom")]//p/a');
        $this->assertSame('Contact', $bottomLinks->item(0)->textContent);
        $this->assertSame('Existing booking terms', $bottomLinks->item(1)->textContent);
        $this->assertSame('FAQ', $bottomLinks->item(2)->textContent);
        $this->assertSame($configured, json_decode(site_setting_get('footer_bottom_links', ''), true));
    }

    public function testCurrentPolicyIsNotItsOwnNewFooterLink(): void
    {
        $xpath = $this->renderContact('/booking-terms-and-conditions?source=test');
        $this->assertLinkSet($xpath, '//nav[@aria-label="Booking and trust"]', array_slice(array_keys(self::LABELS), 1));
    }

    public function testMissingTableAndOlderTableWithoutCanonicalColumnAreSafe(): void
    {
        require_once __DIR__ . '/../includes/published_page_links.php';
        $this->assertFalse(db_table_exists('r1_trust_static_pages_hold', true));
        db()->exec('RENAME TABLE static_pages TO r1_trust_static_pages_hold');
        try {
            db_table_exists('static_pages', true);
            $this->assertSame([], published_booking_page_links());
            db()->exec('CREATE TABLE static_pages (slug VARCHAR(180), is_published TINYINT)');
            db_table_exists('static_pages', true);
            db_column_exists('static_pages', 'canonical_url', true);
            execute_query('INSERT INTO static_pages (slug,is_published) VALUES (?,1)', ['book-with-confidence']);
            $this->assertSame([base_url('book-with-confidence')], array_column(published_booking_page_links(), 'url'));
        } finally {
            db()->exec('DROP TABLE IF EXISTS static_pages');
            db()->exec('RENAME TABLE r1_trust_static_pages_hold TO static_pages');
            db_table_exists('static_pages', true);
            db_column_exists('static_pages', 'canonical_url', true);
        }
    }

    private function placeholders(): string
    {
        return implode(',', array_fill(0, count(self::LABELS), '?'));
    }

    private function renderContact(string $uri = '/contact'): DOMXPath
    {
        $get = $_GET;
        $server = $_SERVER;
        $request = $_REQUEST;
        $cookies = $_COOKIE;
        $level = ob_get_level();
        try {
            $_GET = $_REQUEST = $_COOKIE = [];
            $_SERVER['REQUEST_METHOD'] = 'GET';
            $_SERVER['REQUEST_URI'] = $uri;
            $recordPageView = false;
            ob_start();
            require __DIR__ . '/../contact.php';
            $html = (string) ob_get_clean();
        } finally {
            while (ob_get_level() > $level) {
                ob_end_clean();
            }
            $_GET = $get;
            $_REQUEST = $request;
            $_COOKIE = $cookies;
            $_SERVER = $server;
        }
        $document = new DOMDocument();
        $previous = libxml_use_internal_errors(true);
        try {
            $document->loadHTML($html);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
        return new DOMXPath($document);
    }

    private function assertLinkSet(DOMXPath $xpath, string $container, array $slugs): void
    {
        $links = $xpath->query($container . '//a');
        $this->assertCount(count($slugs), $links);
        foreach ($slugs as $index => $slug) {
            $this->assertSame(base_url($slug), $links->item($index)->getAttribute('href'));
            $this->assertSame(self::LABELS[$slug], trim($links->item($index)->textContent));
        }
    }
}
