<?php
declare(strict_types=1);

/**
 * CSRF protection helpers.
 */
function csrf_token(): string
{
    if (empty($_SESSION[CSRF_SESSION_KEY])) {
        $_SESSION[CSRF_SESSION_KEY] = bin2hex(random_bytes(32));
    }

    return $_SESSION[CSRF_SESSION_KEY];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_token" value="' . e(csrf_token()) . '">';
}

function csrf_verify(?string $token): bool
{
    $sessionToken = $_SESSION[CSRF_SESSION_KEY] ?? '';

    return is_string($token) && $sessionToken !== '' && hash_equals($sessionToken, $token);
}

function session_regenerate_active_id(bool $deleteOldSession = true): bool
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        return false;
    }

    return session_regenerate_id($deleteOldSession);
}

function require_csrf(): void
{
    $token = post_input('_token');

    if (!csrf_verify(is_string($token) ? $token : null)) {
        http_response_code(419);
        exit('Invalid or expired CSRF token.');
    }
}

/**
 * Simple active route helper for nav states.
 */
function route_is(string ...$paths): bool
{
    $current = request_path();

    foreach ($paths as $path) {
        $normalized = '/' . trim($path, '/');
        $normalized = $normalized === '/' ? '/' : rtrim($normalized, '/');

        if ($current === $normalized) {
            return true;
        }
    }

    return false;
}

function route_session_token(bool $create = true): string
{
    $token = trim((string) ($_COOKIE['ict_route_token'] ?? ''));
    if (preg_match('/\A[a-f0-9]{64}\z/', $token)) {
        route_session_set_cookie($token);
        return $token;
    }

    if (!$create) {
        return '';
    }

    $token = bin2hex(random_bytes(32));
    $_COOKIE['ict_route_token'] = $token;
    route_session_set_cookie($token);

    return $token;
}

function route_session_set_cookie(string $token): void
{
    if (headers_sent() || !preg_match('/\A[a-f0-9]{64}\z/', $token)) {
        return;
    }

    $secure = strtolower((string) parse_url(APP_URL, PHP_URL_SCHEME)) === 'https'
        || (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off');

    setcookie('ict_route_token', $token, [
        'expires' => time() + 60 * 60 * 24 * 30,
        'path' => '/',
        'secure' => $secure,
        'httponly' => false,
        'samesite' => 'Lax',
    ]);
}

function route_session_sanitize_slugs(mixed $value, array $allowedSlugs = [], int $limit = 30): array
{
    $candidates = is_array($value)
        ? $value
        : preg_split('/\s*,\s*/', str_replace(["\r", "\n"], ',', (string) $value), -1, PREG_SPLIT_NO_EMPTY);

    $allowedMap = [];
    foreach ($allowedSlugs as $slug) {
        $slug = trim((string) $slug);
        if ($slug !== '') {
            $allowedMap[$slug] = true;
        }
    }

    $slugs = [];
    foreach ((array) $candidates as $candidate) {
        $slug = strtolower(trim((string) $candidate));
        if (!preg_match('/\A[a-z0-9-]{1,180}\z/', $slug)) {
            continue;
        }
        if ($allowedMap && !isset($allowedMap[$slug])) {
            continue;
        }
        $slugs[$slug] = $slug;
        if (count($slugs) >= $limit) {
            break;
        }
    }

    return array_values($slugs);
}

function route_session_fetch_slugs(string $token, string $column): array
{
    if (!route_session_storage_available($column) || !preg_match('/\A[a-f0-9]{64}\z/', $token)) {
        return [];
    }

    $where = 'token = :token';
    if (db_column_exists('route_sessions', 'expires_at')) {
        $where .= ' AND expires_at > NOW()';
    }

    $row = fetch_one(
        'SELECT ' . $column . ' FROM route_sessions WHERE ' . $where . ' LIMIT 1',
        ['token' => $token]
    );

    return route_session_sanitize_slugs(is_array($row) ? ($row[$column] ?? '') : '', [], 50);
}

function route_session_save_slugs(string $token, string $column, array $slugs): bool
{
    if (!route_session_storage_available($column) || !preg_match('/\A[a-f0-9]{64}\z/', $token)) {
        return false;
    }

    $writeData = [
        'token' => $token,
        $column => implode(',', route_session_sanitize_slugs($slugs, [], 50)),
    ];

    if (db_column_exists('route_sessions', 'ip_address')) {
        $writeData['ip_address'] = request_ip_address();
    }
    if (db_column_exists('route_sessions', 'user_agent')) {
        $writeData['user_agent'] = mb_substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 300);
    }
    if (db_column_exists('route_sessions', 'expires_at')) {
        $writeData['expires_at'] = date('Y-m-d H:i:s', time() + 60 * 60 * 24 * 30);
    }

    $columns = array_keys($writeData);
    $placeholders = array_map(static fn(string $key): string => ':' . $key, $columns);
    $updateColumns = array_values(array_filter($columns, static fn(string $key): bool => $key !== 'token'));
    $updates = array_map(static fn(string $key): string => $key . ' = VALUES(' . $key . ')', $updateColumns);

    execute_query(
        'INSERT INTO route_sessions (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $placeholders) . ')
         ON DUPLICATE KEY UPDATE ' . implode(', ', $updates),
        $writeData
    );

    return true;
}

function route_session_storage_available(string $column): bool
{
    return in_array($column, ['dest_slugs', 'exp_slugs'], true)
        && db_table_exists('route_sessions')
        && db_column_exists('route_sessions', 'token')
        && db_column_exists('route_sessions', $column);
}

