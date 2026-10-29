<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../includes/functions.php';

class ProxyTrustTest extends TestCase
{
    private array $serverBackup = [];
    private ?string $trustedProxyBackup = null;

    protected function setUp(): void
    {
        $this->serverBackup = [
            'REMOTE_ADDR' => $_SERVER['REMOTE_ADDR'] ?? null,
            'HTTP_X_FORWARDED_FOR' => $_SERVER['HTTP_X_FORWARDED_FOR'] ?? null,
            'HTTP_X_FORWARDED_PROTO' => $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? null,
            'HTTPS' => $_SERVER['HTTPS'] ?? null,
            'SERVER_PORT' => $_SERVER['SERVER_PORT'] ?? null,
        ];
        $this->trustedProxyBackup = getenv('TRUSTED_PROXY_IPS') !== false ? (string) getenv('TRUSTED_PROXY_IPS') : null;
    }

    protected function tearDown(): void
    {
        foreach ($this->serverBackup as $key => $value) {
            if ($value === null) {
                unset($_SERVER[$key]);
                continue;
            }

            $_SERVER[$key] = $value;
        }

        if ($this->trustedProxyBackup === null) {
            putenv('TRUSTED_PROXY_IPS');
            unset($_ENV['TRUSTED_PROXY_IPS'], $_SERVER['TRUSTED_PROXY_IPS']);
        } else {
            putenv('TRUSTED_PROXY_IPS=' . $this->trustedProxyBackup);
            $_ENV['TRUSTED_PROXY_IPS'] = $this->trustedProxyBackup;
            $_SERVER['TRUSTED_PROXY_IPS'] = $this->trustedProxyBackup;
        }
    }

    public function testRequestIpAddressIgnoresForwardedForWithoutTrustedProxy(): void
    {
        putenv('TRUSTED_PROXY_IPS');
        unset($_ENV['TRUSTED_PROXY_IPS'], $_SERVER['TRUSTED_PROXY_IPS']);
        $_SERVER['REMOTE_ADDR'] = '198.51.100.9';
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '203.0.113.7, 10.0.0.1';

        $this->assertSame('198.51.100.9', request_ip_address());
    }

    public function testRequestIpAddressUsesForwardedForWhenProxyTrusted(): void
    {
        putenv('TRUSTED_PROXY_IPS=127.0.0.1');
        $_ENV['TRUSTED_PROXY_IPS'] = '127.0.0.1';
        $_SERVER['TRUSTED_PROXY_IPS'] = '127.0.0.1';
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '203.0.113.7, 10.0.0.1';

        $this->assertSame('203.0.113.7', request_ip_address());
    }

    public function testRequestIsHttpsOnlyTrustsForwardedProtoFromTrustedProxy(): void
    {
        putenv('TRUSTED_PROXY_IPS=127.0.0.1');
        $_ENV['TRUSTED_PROXY_IPS'] = '127.0.0.1';
        $_SERVER['TRUSTED_PROXY_IPS'] = '127.0.0.1';
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $_SERVER['HTTP_X_FORWARDED_PROTO'] = 'https';
        $_SERVER['HTTPS'] = '';
        $_SERVER['SERVER_PORT'] = '80';

        $this->assertTrue(request_is_https());

        putenv('TRUSTED_PROXY_IPS');
        unset($_ENV['TRUSTED_PROXY_IPS'], $_SERVER['TRUSTED_PROXY_IPS']);

        $this->assertFalse(request_is_https());
    }
}
