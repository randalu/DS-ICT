<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

class AdminLoginRateLimitTest extends TestCase
{
    private string $testIp = '127.0.0.1';
    private string $formKey = 'admin_login';

    protected function setUp(): void
    {
        // Ensure request_ip_address() resolves in CLI
        $_SERVER['REMOTE_ADDR'] = $this->testIp;

        // Clean any previous test data for this IP
        try {
            execute_query(
                'DELETE FROM form_submission_limits WHERE form_key = :key AND ip_address = :ip',
                ['key' => $this->formKey, 'ip' => $this->testIp]
            );
        } catch (Throwable) {
        }
    }

    protected function tearDown(): void
    {
        // Clean up test data
        try {
            execute_query(
                'DELETE FROM form_submission_limits WHERE form_key = :key AND ip_address = :ip',
                ['key' => $this->formKey, 'ip' => $this->testIp]
            );
        } catch (Throwable) {
        }
    }

    public function testRateLimitNotExceededWhenNoAttempts(): void
    {
        $this->assertFalse(admin_login_rate_limit_exceeded($this->testIp, 900, 5));
    }

    public function testRateLimitNotExceededBelowThreshold(): void
    {
        for ($i = 0; $i < 3; $i++) {
            admin_log_login_attempt('test@example.com');
        }

        $this->assertFalse(admin_login_rate_limit_exceeded($this->testIp, 900, 5));
    }

    public function testRateLimitExceededAtThreshold(): void
    {
        for ($i = 0; $i < 5; $i++) {
            admin_log_login_attempt('test@example.com');
        }

        $this->assertTrue(admin_login_rate_limit_exceeded($this->testIp, 900, 5));
    }

    public function testRateLimitNotExceededWithDifferentIp(): void
    {
        for ($i = 0; $i < 10; $i++) {
            admin_log_login_attempt('test@example.com');
        }

        $this->assertFalse(admin_login_rate_limit_exceeded('192.168.1.1', 900, 5));
    }

    public function testRateLimitExceededLogsDifferentUsernames(): void
    {
        $usernames = ['alice', 'bob', 'charlie', 'dave', 'eve'];
        foreach ($usernames as $u) {
            admin_log_login_attempt($u);
        }

        $this->assertTrue(admin_login_rate_limit_exceeded($this->testIp, 900, 5));
    }

    public function testRateLimitEmptyIpReturnsFalse(): void
    {
        $this->assertFalse(admin_login_rate_limit_exceeded('', 900, 5));
    }

    public function testLogLoginAttemptDoesNotThrow(): void
    {
        try {
            admin_log_login_attempt('test@example.com');
            $this->assertTrue(true);
        } catch (Throwable $e) {
            $this->fail('admin_log_login_attempt threw: ' . $e->getMessage());
        }
    }

    public function testRateLimitWindowResetsAfterTime(): void
    {
        // Insert records with a past timestamp
        for ($i = 0; $i < 5; $i++) {
            execute_query(
                "INSERT INTO form_submission_limits (form_key, ip_address, created_at)
                 VALUES (:key, :ip, DATE_SUB(NOW(), INTERVAL 20 MINUTE))",
                ['key' => $this->formKey, 'ip' => $this->testIp]
            );
        }

        // 900 second window should not include old records
        $this->assertFalse(admin_login_rate_limit_exceeded($this->testIp, 900, 5));
    }
}
