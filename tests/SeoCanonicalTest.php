<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../includes/functions.php';

final class SeoCanonicalTest extends TestCase
{
    protected function setUp(): void
    {
        ob_start();
        require_once __DIR__ . '/../includes/seo.php';
        ob_end_clean();
    }

    public function testSameOriginCanonicalOverrideIsPreserved(): void
    {
        $this->assertSame(
            'https://srilankaprivatetravels.com/custom-route',
            seo_normalize_canonical(
                'https://srilankaprivatetravels.com/custom-route',
                'https://srilankaprivatetravels.com/current-route'
            )
        );
    }

    public function testCrossHostCanonicalFallsBackToCurrentRoute(): void
    {
        $this->assertSame(
            'https://srilankaprivatetravels.com/destinations/yala',
            seo_normalize_canonical(
                'https://island.sey/destinations/yala',
                'https://srilankaprivatetravels.com/destinations/yala'
            )
        );
    }

    public function testSchemePortCredentialsAndFragmentsMustMatchFallbackOrigin(): void
    {
        $fallback = 'https://srilankaprivatetravels.com/example';

        $this->assertSame($fallback, seo_normalize_canonical('http://srilankaprivatetravels.com/example', $fallback));
        $this->assertSame($fallback, seo_normalize_canonical('https://srilankaprivatetravels.com:8443/example', $fallback));
        $this->assertSame($fallback, seo_normalize_canonical('https://user:pass@srilankaprivatetravels.com/example', $fallback));
        $this->assertSame($fallback, seo_normalize_canonical('https://srilankaprivatetravels.com/example#section', $fallback));
    }
}
