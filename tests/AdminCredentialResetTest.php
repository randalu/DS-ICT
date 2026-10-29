<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../includes/admin_credentials.php';

final class AdminCredentialResetTest extends TestCase
{
    private PDO $connection;
    private array $before;
    private const OLD_PASSWORDS = [1 => 'Synthetic-Old-Admin-One!', 2 => 'Synthetic-Old-Admin-Two!'];
    private const NEW_PASSWORDS = [1 => 'Synthetic-New-Admin-One!2026', 2 => 'Synthetic-New-Admin-Two!2026'];

    protected function setUp(): void
    {
        $this->connection = new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $this->connection->exec('CREATE TABLE admins (id INTEGER PRIMARY KEY, password_hash TEXT NOT NULL, is_active INTEGER NOT NULL, name TEXT, email TEXT, last_login_at TEXT)');
        $insert = $this->connection->prepare('INSERT INTO admins VALUES (?, ?, 1, ?, ?, ?)');
        foreach ([1, 2, 3] as $id) {
            $insert->execute([$id, password_hash(self::OLD_PASSWORDS[$id] ?? 'Synthetic-Unrelated-Admin!', PASSWORD_BCRYPT, ['cost' => 4]),
                'Synthetic ' . $id, 'admin' . $id . '@example.test', '2026-01-01']);
        }
        $this->before = $this->rows();
    }

    private function rows(): array
    {
        return $this->connection->query('SELECT * FROM admins ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
    }

    public function testBothPasswordsChangeAndUnrelatedStateIsPreserved(): void
    {
        $result = admin_credential_reset($this->connection, self::NEW_PASSWORDS);
        $this->assertSame([1, 2], $result['updated_ids']);
        $after = $this->rows();
        foreach ([0, 1] as $index) {
            $id = $index + 1;
            $this->assertTrue(password_verify(self::NEW_PASSWORDS[$id], $after[$index]['password_hash']));
            $this->assertFalse(password_verify(self::OLD_PASSWORDS[$id], $after[$index]['password_hash']));
            $this->assertFalse(admin_session_credentials_match([ADMIN_CREDENTIAL_STAMP_KEY => admin_credential_stamp($id, $this->before[$index]['password_hash'])], $id, $after[$index]['password_hash']));
            $this->assertSame(12, password_get_info($after[$index]['password_hash'])['options']['cost']);
            unset($after[$index]['password_hash']);
            $original = $this->before[$index];
            unset($original['password_hash']);
            $this->assertSame($original, $after[$index]);
        }
        $this->assertSame($this->before[2], $after[2]);
        $this->assertFalse($this->connection->inTransaction());
        $this->assertStringNotContainsString('Synthetic-New', json_encode($result));
    }

    public function testSecondUpdateFailureRollsBackTheFirst(): void
    {
        $this->connection->exec("CREATE TRIGGER block_second BEFORE UPDATE ON admins WHEN OLD.id = 2 BEGIN SELECT RAISE(ABORT, 'synthetic failure'); END");
        try {
            admin_credential_reset($this->connection, self::NEW_PASSWORDS);
            $this->fail('Expected second-target failure.');
        } catch (PDOException) {
            $this->assertSame($this->before, $this->rows());
            $this->assertFalse($this->connection->inTransaction());
        }
    }

    public function testMissingOrInactiveTargetPreventsAnyUpdate(): void
    {
        $this->connection->exec('UPDATE admins SET is_active = 0 WHERE id = 2');
        $baseline = $this->rows();
        try {
            admin_credential_reset($this->connection, self::NEW_PASSWORDS);
            $this->fail('Inactive target should block reset.');
        } catch (RuntimeException) {
            $this->assertSame($baseline, $this->rows());
        }
        $this->connection->exec('DELETE FROM admins WHERE id = 2');
        $baseline = $this->rows();
        try {
            admin_credential_reset($this->connection, self::NEW_PASSWORDS);
            $this->fail('Missing target should block reset.');
        } catch (RuntimeException) {
            $this->assertSame($baseline, $this->rows());
        }
    }

    public function testMalformedWeakDuplicateOrExtraTargetsAreRejected(): void
    {
        $invalid = [[1 => 'short', 2 => self::NEW_PASSWORDS[2]],
            [1 => str_repeat('a', 73), 2 => self::NEW_PASSWORDS[2]],
            [1 => 'Synthetic-New-Contains' . chr(0), 2 => self::NEW_PASSWORDS[2]],
            [1 => self::NEW_PASSWORDS[1], 2 => self::NEW_PASSWORDS[1]],
            [1 => [], 2 => self::NEW_PASSWORDS[2]],
            [1 => self::NEW_PASSWORDS[1], 2 => self::NEW_PASSWORDS[2], 3 => self::NEW_PASSWORDS[1]]];
        foreach ($invalid as $passwords) {
            try {
                admin_credential_reset($this->connection, $passwords);
                $this->fail('Invalid input should block reset.');
            } catch (InvalidArgumentException) {
                $this->assertSame($this->before, $this->rows());
            }
        }
    }

    public function testNeitherAccountMayReuseEitherExistingPassword(): void
    {
        try {
            admin_credential_reset($this->connection, [1 => self::OLD_PASSWORDS[2], 2 => self::NEW_PASSWORDS[2]]);
            $this->fail('Cross-account reuse should block reset.');
        } catch (InvalidArgumentException) {
            $this->assertSame($this->before, $this->rows());
        }
    }

    public function testExistingTransactionIsNotCommittedOrRolledBack(): void
    {
        $this->connection->beginTransaction();
        try {
            admin_credential_reset($this->connection, self::NEW_PASSWORDS);
            $this->fail('Nested reset should be refused.');
        } catch (RuntimeException) {
            $this->assertTrue($this->connection->inTransaction());
            $this->assertSame($this->before, $this->rows());
        } finally {
            $this->connection->rollBack();
        }
    }

    public function testSessionStampRejectsLegacyMalformedWrongAccountAndEmptyHash(): void
    {
        $hash = $this->before[0]['password_hash'];
        $session = [ADMIN_CREDENTIAL_STAMP_KEY => admin_credential_stamp(1, $hash)];
        $this->assertTrue(admin_session_credentials_match($session, 1, $hash));
        $this->assertFalse(admin_session_credentials_match($session, 2, $hash));
        $this->assertFalse(admin_session_credentials_match($session, 1, ''));
        foreach ([null, [], 42, 'malformed', str_repeat('0', 64)] as $stamp) {
            $this->assertFalse(admin_session_credentials_match([ADMIN_CREDENTIAL_STAMP_KEY => $stamp], 1, $hash));
        }
        $this->assertFalse(admin_session_credentials_match([], 1, $hash));
    }
}
