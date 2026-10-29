<?php
declare(strict_types=1);

require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/admin_credentials.php';

const ADMIN_SESSION_KEY = '_ict_admin_user_id';
const ADMIN_CREDENTIAL_BINDING_VERSION = 1;

function admin_id(): ?int
{
    $id = $_SESSION[ADMIN_SESSION_KEY] ?? null;
    return is_numeric($id) ? (int) $id : null;
}

function admin_user(): ?array
{
    static $cached = null;
    static $loaded = false;

    if ($loaded) {
        return $cached;
    }

    $loaded = true;
    $id = admin_id();

    if (!$id) {
        unset($_SESSION['_ict_seo_connection']);
        return $cached = null;
    }

    $cached = fetch_one(
        "SELECT id, name, email, username, is_active, last_login_at, password_hash
         FROM admins
         WHERE id = :id AND is_active = 1
         LIMIT 1",
        ['id' => $id]
    );

    if (!$cached || !admin_session_credentials_match($_SESSION, (int) $cached['id'], (string) $cached['password_hash'])) {
        unset($_SESSION[ADMIN_SESSION_KEY], $_SESSION[ADMIN_CREDENTIAL_STAMP_KEY], $_SESSION['_ict_seo_connection']);
        return $cached = null;
    }
    unset($cached['password_hash']);
    return $cached;
}

function admin_check(): bool
{
    return admin_user() !== null;
}

function admin_require_auth(): void
{
    if (!admin_check()) {
        flash_set('error', 'Please log in to continue.');
        redirect('admin/login.php');
    }
}

function admin_guest_only(): void
{
    if (admin_check()) {
        redirect('admin/dashboard.php');
    }
}

function admin_login(string $login, string $password): bool
{
    $user = fetch_one(
        "SELECT *
         FROM admins
         WHERE (email = :email_login OR username = :username_login)
           AND is_active = 1
         LIMIT 1",
        [
            'email_login' => $login,
            'username_login' => $login,
        ]
    );

    if (!$user || !password_verify($password, (string) $user['password_hash'])) {
        admin_log_login_attempt($login);
        return false;
    }

    unset($_SESSION['_ict_seo_connection']);
    session_regenerate_active_id(true);
    $_SESSION[ADMIN_SESSION_KEY] = (int) $user['id'];
    $_SESSION[ADMIN_CREDENTIAL_STAMP_KEY] = admin_credential_stamp((int) $user['id'], (string) $user['password_hash']);
    $_SESSION[CSRF_SESSION_KEY] = bin2hex(random_bytes(32));

    execute_query(
        "UPDATE admins SET last_login_at = NOW() WHERE id = :id",
        ['id' => $user['id']]
    );

    return true;
}

function admin_login_rate_limit_exceeded(string $ipAddress, int $windowSeconds = 900, int $maxAttempts = 5): bool
{
    if ($ipAddress === '') {
        return false;
    }

    try {
        db()->exec(
            "DELETE FROM form_submission_limits WHERE created_at < DATE_SUB(NOW(), INTERVAL 2 DAY)"
        );

        $windowSeconds = max(30, $windowSeconds);
        $maxAttempts = max(1, $maxAttempts);

        $windowStart = date('Y-m-d H:i:s', time() - $windowSeconds);
        $row = fetch_one(
            "SELECT COUNT(*) AS total
             FROM form_submission_limits
             WHERE form_key = 'admin_login'
               AND ip_address = :ip_address
               AND created_at >= :window_start",
            [
                'ip_address' => substr($ipAddress, 0, 45),
                'window_start' => $windowStart,
            ]
        );

        return (int) ($row['total'] ?? 0) >= $maxAttempts;
    } catch (Throwable) {
        return false;
    }
}

function admin_log_login_attempt(string $login): void
{
    try {
        $ip = request_ip_address();
        if ($ip === '') {
            return;
        }

        db()->exec(
            "DELETE FROM form_submission_limits WHERE created_at < DATE_SUB(NOW(), INTERVAL 2 DAY)"
        );

        execute_query(
            "INSERT INTO form_submission_limits (form_key, ip_address) VALUES ('admin_login', :ip_address)",
            ['ip_address' => $ip]
        );
    } catch (Throwable) {
    }
}

function admin_logout(): void
{
    unset($_SESSION[ADMIN_SESSION_KEY], $_SESSION[ADMIN_CREDENTIAL_STAMP_KEY], $_SESSION['_ict_seo_connection']);
    session_regenerate_active_id(true);
}

function admin_form_value(array $record, string $key, mixed $default = ''): mixed
{
    $old = old_input($key, '__ICT_NO_OLD__');
    if ($old !== '__ICT_NO_OLD__') {
        return $old;
    }

    return $record[$key] ?? $default;
}

function admin_checkbox_value(array $record, string $key, int|bool $default = 0): bool
{
    $old = old_input($key, '__ICT_NO_OLD__');
    if ($old !== '__ICT_NO_OLD__') {
        return (string) $old === '1';
    }

    return (int) ($record[$key] ?? $default) === 1;
}
