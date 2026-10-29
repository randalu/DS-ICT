<?php
declare(strict_types=1);

use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/customer_auth.php';

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
final class AdminCredentialSessionTest extends TestCase
{
    private const OLD = [1 => 'Synthetic-Old-Admin-One!', 2 => 'Synthetic-Old-Admin-Two!'];
    private const NEW = [1 => 'Synthetic-New-Admin-One!2026', 2 => 'Synthetic-New-Admin-Two!2026'];

    protected function setUp(): void
    {
        if (!str_starts_with(DB_NAME, 'ict_admin_response_')) {
            $this->markTestSkipped('Requires a dedicated synthetic admin-response database.');
        }
        $_SESSION = [CUSTOMER_SESSION_KEY => 789, CSRF_SESSION_KEY => 'synthetic-csrf'];
        foreach (self::OLD as $id => $password) {
            execute_query('INSERT INTO admins (id, name, username, email, password_hash, is_active) VALUES (:id, :name, :username, :email, :hash, 1)',
                ['id' => $id, 'name' => 'Synthetic Admin', 'username' => 'synthetic-admin-' . $id,
                    'email' => 'synthetic-admin-' . $id . '@example.test', 'hash' => password_hash($password, PASSWORD_BCRYPT, ['cost' => 4])]);
        }
    }

    protected function tearDown(): void
    {
        if (str_starts_with(DB_NAME, 'ict_admin_response_')) {
            execute_query('DELETE FROM admins WHERE id IN (1, 2)');
        }
        $_SESSION = [];
    }

    public function testLoginBindsSessionAndDoesNotExposePasswordHash(): void
    {
        $this->assertTrue(admin_login('synthetic-admin-1', self::OLD[1]));
        $this->assertTrue(admin_check());
        $this->assertArrayHasKey(ADMIN_CREDENTIAL_STAMP_KEY, $_SESSION);
        $this->assertArrayNotHasKey('password_hash', admin_user());
        $this->assertSame(789, customer_id());
        $this->assertNotSame('synthetic-csrf', $_SESSION[CSRF_SESSION_KEY]);
    }

    public function testLegacySessionIsRejectedWithoutClearingCustomerOrCsrf(): void
    {
        $_SESSION[ADMIN_SESSION_KEY] = 1;
        $this->assertFalse(admin_check());
        $this->assertNull(admin_id());
        $this->assertSame(789, customer_id());
        $this->assertSame('synthetic-csrf', $_SESSION[CSRF_SESSION_KEY]);
    }

    public function testResetInvalidatesExistingSessionAndBothNewPasswordsWork(): void
    {
        $this->assertTrue(admin_login('synthetic-admin-1', self::OLD[1]));
        $result = admin_credential_reset(db(), self::NEW);
        $this->assertSame([1, 2], $result['updated_ids']);
        $this->assertFalse(admin_check());
        $this->assertNull(admin_id());
        $this->assertSame(789, customer_id());
        foreach ([1, 2] as $id) {
            $this->assertFalse(admin_login('synthetic-admin-' . $id, self::OLD[$id]));
            $this->assertTrue(admin_login('synthetic-admin-' . $id, self::NEW[$id]));
        }
    }

    public function testStampCannotMoveBetweenAccountsEvenIfHashesAreCopied(): void
    {
        $this->assertTrue(admin_login('synthetic-admin-1', self::OLD[1]));
        execute_query('UPDATE admins SET password_hash = (SELECT hash FROM (SELECT password_hash AS hash FROM admins WHERE id = 1) AS original) WHERE id = 2');
        $_SESSION[ADMIN_SESSION_KEY] = 2;
        $this->assertFalse(admin_check());
        $this->assertSame(789, customer_id());
    }

    public function testInactiveAccountAndLogoutClearAdminStamp(): void
    {
        $this->assertTrue(admin_login('synthetic-admin-1', self::OLD[1]));
        execute_query('UPDATE admins SET is_active = 0 WHERE id = 1');
        $this->assertFalse(admin_check());
        $this->assertArrayNotHasKey(ADMIN_CREDENTIAL_STAMP_KEY, $_SESSION);
        $_SESSION[ADMIN_CREDENTIAL_STAMP_KEY] = 'synthetic-stamp';
        admin_logout();
        $this->assertArrayNotHasKey(ADMIN_CREDENTIAL_STAMP_KEY, $_SESSION);
        $this->assertSame(789, customer_id());
        flash_set('success', 'Synthetic logout');
        $this->assertSame('Synthetic logout', flash_get('success'));
    }
}
