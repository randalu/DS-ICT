<?php

use PHPUnit\Framework\TestCase;

// The file being tested includes functions.php which connects to the database via PDO.
// However, since we are overriding the DB operations via TestSubscribeHandler,
// the actual database connection is never made during tests.
require_once __DIR__ . '/../subscribe.php';

class TestSubscribeHandler extends SubscribeHandler
{
    public array $savedEmails = [];
    public bool $tableEnsured = false;
    public ?Throwable $mockException = null;

    protected function ensureTable(): void
    {
        $this->tableEnsured = true;
    }

    protected function saveSubscriber(string $email): void
    {
        if ($this->mockException !== null) {
            throw $this->mockException;
        }
        $this->savedEmails[] = $email;
    }
}

class SubscribeTest extends TestCase
{
    private TestSubscribeHandler $handler;

    protected function setUp(): void
    {
        parent::setUp();
        $this->handler = new TestSubscribeHandler();
    }

    public function testGetRequestRedirectsToBlog(): void
    {
        $result = $this->handler->handle([], ['REQUEST_METHOD' => 'GET']);

        $this->assertEquals(['redirect' => base_url('blog')], $result);
        $this->assertFalse($this->handler->tableEnsured);
        $this->assertEmpty($this->handler->savedEmails);
    }

    public function testInvalidEmailRedirectsBackWithError(): void
    {
        $referer = base_url('some-page');
        $post = ['email' => 'not-an-email'];
        $server = [
            'REQUEST_METHOD' => 'POST',
            'HTTP_REFERER' => $referer
        ];

        $result = $this->handler->handle($post, $server);

        $this->assertEquals([
            'flash_error' => 'Please enter a valid email address.',
            'redirect' => 'some-page'
        ], $result);
        $this->assertFalse($this->handler->tableEnsured);
        $this->assertEmpty($this->handler->savedEmails);
    }

    public function testValidEmailSavesAndRedirectsBackWithSuccess(): void
    {
        $referer = base_url('another-page');
        $email = 'test@example.com';
        $post = ['email' => $email];
        $server = [
            'REQUEST_METHOD' => 'POST',
            'HTTP_REFERER' => $referer
        ];

        $result = $this->handler->handle($post, $server);

        $this->assertEquals([
            'flash_success' => 'You are subscribed. Thank you!',
            'redirect' => 'another-page'
        ], $result);
        $this->assertTrue($this->handler->tableEnsured);
        $this->assertEquals([$email], $this->handler->savedEmails);
    }

    public function testExternalRefererFallsBackToBlog(): void
    {
        $post = ['email' => 'valid@example.com'];
        $server = [
            'REQUEST_METHOD' => 'POST',
            'HTTP_REFERER' => 'https://example.com/off-site'
        ];

        $result = $this->handler->handle($post, $server);

        $this->assertEquals([
            'flash_success' => 'You are subscribed. Thank you!',
            'redirect' => 'blog'
        ], $result);
        $this->assertTrue($this->handler->tableEnsured);
        $this->assertEquals(['valid@example.com'], $this->handler->savedEmails);
    }

    public function testDatabaseErrorRedirectsBackWithError(): void
    {
        $referer = base_url('error-page');
        $post = ['email' => 'valid@example.com'];
        $server = [
            'REQUEST_METHOD' => 'POST',
            'HTTP_REFERER' => $referer
        ];

        // Ensure APP_DEBUG is false to test the generic error message
        if (!defined('APP_DEBUG')) {
            define('APP_DEBUG', false);
        }

        $this->handler->mockException = new RuntimeException('DB connection failed');

        $result = $this->handler->handle($post, $server);

        // Since APP_DEBUG is evaluated as false or we just check the general logic
        // Let's assert based on APP_DEBUG value.
        $expectedError = defined('APP_DEBUG') && APP_DEBUG ? 'DB connection failed' : 'Subscription failed.';

        $this->assertEquals([
            'flash_error' => $expectedError,
            'redirect' => 'error-page'
        ], $result);
        $this->assertTrue($this->handler->tableEnsured);
    }
}
