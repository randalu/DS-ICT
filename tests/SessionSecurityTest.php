<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/customer_auth.php';

class SessionSecurityTest extends TestCase
{
    private array $createdAdmin = [];
    private array $createdCustomer = [];

    protected function setUp(): void
    {
        $_SESSION = [];
    }

    protected function tearDown(): void
    {
        if (!empty($this->createdAdmin)) {
            execute_query(
                'DELETE FROM admins WHERE id = :id',
                ['id' => $this->createdAdmin['id']]
            );
            $this->createdAdmin = [];
        }

        if (!empty($this->createdCustomer)) {
            execute_query(
                'DELETE FROM customer_users WHERE id = :id',
                ['id' => $this->createdCustomer['id']]
            );
            $this->createdCustomer = [];
        }

        $_SESSION = [];
    }

    public function testSessionRegenerateActiveIdChangesTheCurrentSessionId(): void
    {
        $before = session_id();

        $this->assertTrue(session_regenerate_active_id(true));
        $this->assertNotSame($before, session_id());
    }

    public function testAdminLoginRegeneratesSessionAndSetsAdminId(): void
    {
        $this->createdAdmin = $this->createAdminRecord();
        $before = session_id();

        $this->assertTrue(admin_login($this->createdAdmin['login'], $this->createdAdmin['password']));
        $this->assertNotSame($before, session_id());
        $this->assertSame($this->createdAdmin['id'], admin_id());
    }

    public function testAdminLogoutClearsAdminIdAndKeepsSessionUsableForFlash(): void
    {
        $_SESSION[ADMIN_SESSION_KEY] = 12345;
        $before = session_id();

        admin_logout();

        $this->assertNotSame($before, session_id());
        $this->assertNull(admin_id());

        flash_set('success', 'Signed out.');
        $this->assertSame('Signed out.', flash_get('success'));
    }

    public function testCustomerLoginRegeneratesSessionAndSetsCustomerId(): void
    {
        $this->createdCustomer = $this->createCustomerRecord();
        $before = session_id();

        $user = customer_login_from_google_profile($this->createdCustomer['profile']);

        $this->assertNotSame($before, session_id());
        $this->assertSame($this->createdCustomer['id'], (int) ($user['id'] ?? 0));
        $this->assertSame($this->createdCustomer['id'], customer_id());
    }

    public function testCustomerLogoutClearsCustomerStateAndKeepsSessionUsableForFlash(): void
    {
        $_SESSION[CUSTOMER_SESSION_KEY] = 67890;
        $_SESSION[CUSTOMER_OAUTH_STATE_KEY] = 'state-token';
        $_SESSION[CUSTOMER_OAUTH_RETURN_KEY] = '/account';
        $before = session_id();

        customer_logout();

        $this->assertNotSame($before, session_id());
        $this->assertNull(customer_id());
        $this->assertArrayNotHasKey(CUSTOMER_OAUTH_STATE_KEY, $_SESSION);
        $this->assertArrayNotHasKey(CUSTOMER_OAUTH_RETURN_KEY, $_SESSION);

        flash_set('success', 'Signed out.');
        $this->assertSame('Signed out.', flash_get('success'));
    }

    private function createAdminRecord(): array
    {
        $suffix = uniqid('auth', true);
        $login = 'auth-' . preg_replace('/[^a-z0-9]+/i', '', $suffix);
        $email = $login . '@example.test';
        $password = 'TestPass!123';
        $hash = password_hash($password, PASSWORD_DEFAULT);

        execute_query(
            'INSERT INTO admins (name, email, username, password_hash, is_active)
             VALUES (:name, :email, :username, :password_hash, 1)',
            [
                'name' => 'Session Test Admin',
                'email' => $email,
                'username' => $login,
                'password_hash' => $hash,
            ]
        );

        $id = (int) db()->lastInsertId();

        return [
            'id' => $id,
            'login' => $login,
            'password' => $password,
        ];
    }

    private function createCustomerRecord(): array
    {
        $suffix = uniqid('cust', true);
        $googleSub = 'sub-' . preg_replace('/[^a-z0-9]+/i', '', $suffix);
        $email = $googleSub . '@example.test';
        $profile = [
            'sub' => $googleSub,
            'email' => $email,
            'email_verified' => true,
            'name' => 'Session Test Customer',
            'picture' => 'https://example.test/avatar.png',
        ];

        execute_query(
            "INSERT INTO customer_users
                (google_sub, email, email_verified, name, avatar_url, status, last_login_at)
             VALUES
                (:google_sub, :email, 1, :name, :avatar_url, 'active', NOW())",
            [
                'google_sub' => $googleSub,
                'email' => $email,
                'name' => $profile['name'],
                'avatar_url' => $profile['picture'],
            ]
        );

        $id = (int) db()->lastInsertId();

        return [
            'id' => $id,
            'profile' => $profile,
        ];
    }
}
