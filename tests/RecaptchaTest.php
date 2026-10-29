<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/recaptcha.php';

class RecaptchaTest extends TestCase
{
    public function testRecaptchaEnabledReturnsBool(): void
    {
        $result = recaptcha_enabled();
        $this->assertIsBool($result);
    }

    public function testRecaptchaSiteKeyReturnsString(): void
    {
        $result = recaptcha_site_key();
        $this->assertIsString($result);
    }

    public function testRecaptchaSecretKeyReturnsString(): void
    {
        $result = recaptcha_secret_key();
        $this->assertIsString($result);
    }

    public function testRecaptchaVerifyNullTokenReturnsFalse(): void
    {
        $this->assertFalse(recaptcha_verify(null));
    }

    public function testRecaptchaVerifyEmptyTokenReturnsFalse(): void
    {
        $this->assertFalse(recaptcha_verify(''));
    }

    public function testRecaptchaVerifyGarbageTokenReturnsFalse(): void
    {
        $this->assertFalse(recaptcha_verify('garbage-token-value'));
    }

    public function testRecaptchaHtmlReturnsString(): void
    {
        $html = recaptcha_html();
        $this->assertIsString($html);
    }

    public function testRecaptchaHtmlDisabledIsEmptyString(): void
    {
        // When recaptcha is disabled or keys are not set, html should be empty
        $enabled = recaptcha_enabled();
        $siteKey = recaptcha_site_key();

        if (!$enabled || $siteKey === '') {
            $this->assertSame('', recaptcha_html());
        } else {
            $html = recaptcha_html();
            $this->assertStringContainsString('g-recaptcha', $html);
            $this->assertStringContainsString($siteKey, $html);
        }
    }

    public function testRecaptchaConfigFromEnvConstants(): void
    {
        // Verify the constants are defined with correct types
        $this->assertIsString(RECAPTCHA_SITE_KEY);
        $this->assertIsString(RECAPTCHA_SECRET_KEY);
        $this->assertIsBool(RECAPTCHA_ENABLED);
    }
}
