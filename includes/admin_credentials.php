<?php
declare(strict_types=1);

const ADMIN_CREDENTIAL_STAMP_KEY = '_ict_admin_credential_stamp';

function admin_credential_stamp(int $adminId, #[SensitiveParameter] string $passwordHash): string
{
    return hash('sha256', "ict-admin-credential-v1\0" . $adminId . "\0" . $passwordHash);
}

function admin_session_credentials_match(array $session, int $adminId, #[SensitiveParameter] string $passwordHash): bool
{
    $stamp = $session[ADMIN_CREDENTIAL_STAMP_KEY] ?? null;
    return $adminId > 0 && $passwordHash !== '' && is_string($stamp)
        && preg_match('/\A[a-f0-9]{64}\z/', $stamp) === 1
        && hash_equals(admin_credential_stamp($adminId, $passwordHash), $stamp);
}

/** Read-only metadata/target check; no schema helpers or account identifiers returned. */
function admin_credential_reset_preflight(PDO $connection): array
{
    if ($connection->inTransaction()) {
        throw new RuntimeException('Reset requires its own transaction.');
    }
    $driver = $connection->getAttribute(PDO::ATTR_DRIVER_NAME);
    if (!in_array($driver, ['mysql', 'sqlite'], true)) {
        throw new RuntimeException('Unsupported reset database.');
    }
    if ($driver === 'mysql') {
        $table = $connection->query("SHOW TABLE STATUS WHERE Name = 'admins'")->fetch(PDO::FETCH_ASSOC);
        if (strtolower((string) ($table['Engine'] ?? '')) !== 'innodb') {
            throw new RuntimeException('Admins must use transactional InnoDB storage.');
        }
        $columns = $connection->query('SHOW COLUMNS FROM admins')->fetchAll(PDO::FETCH_ASSOC);
        $types = array_column($columns, 'Type', 'Field');
        if (!isset($types['id'], $types['password_hash'], $types['is_active'])
            || preg_match('/^varchar\((\d+)\)$/i', $types['password_hash'], $match) !== 1
            || (int) $match[1] < 60) {
            throw new RuntimeException('Admins schema is incompatible with reset.');
        }
    }
    $rows = $connection->query('SELECT id, is_active FROM admins WHERE id IN (1, 2) ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
    if (array_map(static fn(array $row): int => (int) $row['id'], $rows) !== [1, 2]
        || array_filter($rows, static fn(array $row): bool => (int) $row['is_active'] !== 1)) {
        throw new RuntimeException('Both approved targets must exist and be active.');
    }
    $total = (int) $connection->query('SELECT COUNT(*) FROM admins')->fetchColumn();
    return ['target_ids' => [1, 2], 'targets_active' => true, 'admin_count' => $total,
        'driver' => $driver, 'schema_changes' => false];
}

/** Both approved accounts update together; neither credentials nor hashes are returned. */
function admin_credential_reset(PDO $connection, #[SensitiveParameter] array $passwords): array
{
    if (array_keys($passwords) !== [1, 2]) {
        throw new InvalidArgumentException('Reset targets must be exactly 1 and 2, in order.');
    }
    foreach ($passwords as $password) {
        if (!is_string($password) || strlen($password) < 20 || strlen($password) > 72
            || preg_match('/[\x00-\x1f\x7f]/', $password)) {
            throw new InvalidArgumentException('Use distinct passwords of 20 to 72 bytes without control characters.');
        }
    }
    if (hash_equals($passwords[1], $passwords[2])) {
        throw new InvalidArgumentException('The two new passwords must differ.');
    }
    admin_credential_reset_preflight($connection);
    $newHashes = [];
    foreach ($passwords as $id => $password) {
        $newHashes[$id] = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    }
    $connection->beginTransaction();
    try {
        $locking = $connection->getAttribute(PDO::ATTR_DRIVER_NAME) === 'mysql' ? ' FOR UPDATE' : '';
        $rows = $connection->query('SELECT id, password_hash, is_active FROM admins WHERE id IN (1, 2) ORDER BY id' . $locking)
            ->fetchAll(PDO::FETCH_ASSOC);
        if (array_map(static fn(array $row): int => (int) $row['id'], $rows) !== [1, 2]) {
            throw new RuntimeException('Reset targets changed during preparation.');
        }
        foreach ($rows as $row) {
            if ((int) $row['is_active'] !== 1 || (string) $row['password_hash'] === '') {
                throw new RuntimeException('Reset target is inactive or has no credential.');
            }
            foreach ($passwords as $password) {
                if (password_verify($password, (string) $row['password_hash'])) {
                    throw new InvalidArgumentException('Do not reuse either current account password.');
                }
            }
        }
        $update = $connection->prepare('UPDATE admins SET password_hash = :new_hash WHERE id = :id AND password_hash = :old_hash AND is_active = 1');
        foreach ($rows as $row) {
            $id = (int) $row['id'];
            $update->execute(['new_hash' => $newHashes[$id], 'id' => $id, 'old_hash' => $row['password_hash']]);
            if ($update->rowCount() !== 1) {
                throw new RuntimeException('Reset could not update exactly one target.');
            }
        }
        $verified = $connection->query('SELECT id, password_hash FROM admins WHERE id IN (1, 2) ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
        if (count($verified) !== 2) {
            throw new RuntimeException('Reset verification failed.');
        }
        foreach ($verified as $row) {
            if (!password_verify($passwords[(int) $row['id']], (string) $row['password_hash'])) {
                throw new RuntimeException('Reset verification failed.');
            }
        }
        $connection->commit();
        return ['updated_ids' => [1, 2], 'atomic' => true, 'passwords_or_hashes_returned' => false];
    } catch (Throwable $error) {
        if ($connection->inTransaction()) {
            $connection->rollBack();
        }
        throw $error;
    }
}
