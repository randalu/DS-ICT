<?php
declare(strict_types=1);

/**
 * Island Culture Travels
 * Global configuration
 */

// ── Load .env file if present ────────────────────────────────────────────────
(static function (): void {
    $envFile = dirname(__DIR__) . '/.env';
    if (!is_file($envFile)) {
        return;
    }
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        if (!str_contains($line, '=')) {
            continue;
        }
        [$key, $value] = explode('=', $line, 2);
        $key   = trim($key);
        $value = trim($value);
        // Strip surrounding single or double quotes
        if (
            (str_starts_with($value, "'") && str_ends_with($value, "'")) ||
            (str_starts_with($value, '"') && str_ends_with($value, '"'))
        ) {
            $value = substr($value, 1, -1);
        }
        if ($key !== '' && getenv($key) === false) {
            putenv("{$key}={$value}");
            $_ENV[$key]    = $value;
            $_SERVER[$key] = $value;
        }
    }
})();
if (!function_exists('config_trusted_proxy_values')) {
    function config_trusted_proxy_values(): array
    {
        $raw = trim((string) (getenv('TRUSTED_PROXY_IPS') ?: ''));
        if ($raw === '') {
            return [];
        }

        return array_values(array_filter(array_map(
            static fn(string $value): string => trim($value),
            preg_split('/\s*,\s*/', $raw, -1, PREG_SPLIT_NO_EMPTY) ?: []
        ), static fn(string $value): bool => $value !== ''));
    }
}

if (!function_exists('config_ip_matches_trusted_proxy')) {
    function config_ip_matches_trusted_proxy(string $ip, string $trusted): bool
    {
        if ($ip === '' || $trusted === '') {
            return false;
        }

        if ($ip === $trusted) {
            return true;
        }

        if (!str_contains($trusted, '/')) {
            return false;
        }

        [$subnet, $bits] = array_pad(explode('/', $trusted, 2), 2, null);
        if (!is_numeric($bits)) {
            return false;
        }

        $ipBin = inet_pton($ip);
        $subnetBin = inet_pton($subnet);
        if ($ipBin === false || $subnetBin === false || strlen($ipBin) !== strlen($subnetBin)) {
            return false;
        }

        $bits = (int) $bits;
        $maxBits = strlen($ipBin) * 8;
        if ($bits < 0 || $bits > $maxBits) {
            return false;
        }

        $fullBytes = intdiv($bits, 8);
        $remainingBits = $bits % 8;

        for ($i = 0; $i < $fullBytes; $i++) {
            if ($ipBin[$i] !== $subnetBin[$i]) {
                return false;
            }
        }

        if ($remainingBits === 0) {
            return true;
        }

        $mask = chr((0xFF << (8 - $remainingBits)) & 0xFF);

        return (($ipBin[$fullBytes] ?? "\0") & $mask) === (($subnetBin[$fullBytes] ?? "\0") & $mask);
    }
}

if (!function_exists('request_is_trusted_proxy')) {
    function request_is_trusted_proxy(): bool
    {
        $remoteAddr = trim((string) ($_SERVER['REMOTE_ADDR'] ?? ''));
        if ($remoteAddr === '') {
            return false;
        }

        foreach (config_trusted_proxy_values() as $trustedProxy) {
            if (config_ip_matches_trusted_proxy($remoteAddr, $trustedProxy)) {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('request_is_https')) {
    function request_is_https(): bool
    {
        if ((!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off')
            || (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443)) {
            return true;
        }

        if (!request_is_trusted_proxy()) {
            return false;
        }

        $forwardedProto = strtolower(trim((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')));

        return $forwardedProto === 'https';
    }
}
// ─────────────────────────────────────────────────────────────────────────────

if (session_status() !== PHP_SESSION_ACTIVE) {
    $isHttps = request_is_https();

    session_name('ict_session');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '',
        'secure'   => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

date_default_timezone_set('Asia/Colombo');

$https = request_is_https();

$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$detectedAppUrl = ($https ? 'https://' : 'http://') . $host;
$configuredAppUrl = rtrim(getenv('APP_URL') ?: $detectedAppUrl, '/');
$configuredAppUrl = preg_replace('~^(https?://)www\.~i', '$1', $configuredAppUrl) ?? $configuredAppUrl;

define('APP_NAME', 'Island Culture Travels');
define('APP_ENV', getenv('APP_ENV') ?: 'development');
define('APP_DEBUG', filter_var(getenv('APP_DEBUG') ?: (APP_ENV !== 'production' ? '1' : '0'), FILTER_VALIDATE_BOOLEAN));
define('APP_URL', $configuredAppUrl);
define('BASE_PATH', dirname(__DIR__));
define('PUBLIC_PATH', BASE_PATH);
define('UPLOAD_BASE_PATH', BASE_PATH . '/assets/uploads');
define('UPLOAD_BASE_URL', APP_URL . '/assets/uploads');
define('DEFAULT_OG_IMAGE', APP_URL . '/assets/images/og-default.jpg');

define('DB_DRIVER', getenv('DB_DRIVER') ?: 'mysql');
define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_NAME', getenv('DB_NAME') ?: 'ict');
define('DB_CHARSET', getenv('DB_CHARSET') ?: 'utf8mb4');
define('DB_USERNAME', getenv('DB_USERNAME') ?: 'root');
define('DB_PASSWORD', getenv('DB_PASSWORD') ?: '');

define('CSRF_SESSION_KEY', '_ict_csrf_token');
define('FLASH_SESSION_KEY', '_ict_flash');
define('OLD_INPUT_SESSION_KEY', '_ict_old_input');

define('SITE_PHONE', getenv('SITE_PHONE') ?: '+94 77 949 2427');
define('SITE_PHONE_SECONDARY', getenv('SITE_PHONE_SECONDARY') ?: '+94 77 949 2427');
define('SITE_EMAIL', getenv('SITE_EMAIL') ?: 'hello@islandculturetravels.com');
define('WHATSAPP_NUMBER', getenv('WHATSAPP_NUMBER') ?: '94769192427');
define('SITE_EXPERIENCE_START_YEAR', max(1900, (int) (getenv('SITE_EXPERIENCE_START_YEAR') ?: '2018')));
define('DEFAULT_WHATSAPP_TEXT', getenv('DEFAULT_WHATSAPP_TEXT') ?: 'Hello Island Culture Travels, I would like to plan a Sri Lanka tour.');
define('TAWK_TO_PROPERTY_ID', trim((string) (getenv('TAWK_TO_PROPERTY_ID') ?: '')));
define('TAWK_TO_WIDGET_ID', trim((string) (getenv('TAWK_TO_WIDGET_ID') ?: 'default')));
define('RECAPTCHA_SITE_KEY', trim((string) (getenv('RECAPTCHA_SITE_KEY') ?: '')));
define('RECAPTCHA_SECRET_KEY', trim((string) (getenv('RECAPTCHA_SECRET_KEY') ?: '')));
define(
    'RECAPTCHA_ENABLED',
    filter_var(
        getenv('RECAPTCHA_ENABLED') ?: (RECAPTCHA_SITE_KEY !== '' && RECAPTCHA_SECRET_KEY !== '' ? '1' : '0'),
        FILTER_VALIDATE_BOOLEAN
    )
);

define('INDEXNOW_KEY', trim((string) (getenv('INDEXNOW_KEY') ?: '')));
define('INDEXNOW_KEY_LOCATION', trim((string) (getenv('INDEXNOW_KEY_LOCATION') ?: '')));
define('INDEXNOW_ENDPOINT', trim((string) (getenv('INDEXNOW_ENDPOINT') ?: 'https://api.indexnow.org/indexnow')));
define('INDEXNOW_TIMEOUT', max(1, (int) (getenv('INDEXNOW_TIMEOUT') ?: '3')));
define(
    'INDEXNOW_ENABLED',
    filter_var(
        getenv('INDEXNOW_ENABLED') ?: (INDEXNOW_KEY !== '' ? '1' : '0'),
        FILTER_VALIDATE_BOOLEAN
    )
);
define(
    'TAWK_TO_ENABLED',
    filter_var(
        getenv('TAWK_TO_ENABLED') ?: (TAWK_TO_PROPERTY_ID !== '' ? '1' : '0'),
        FILTER_VALIDATE_BOOLEAN
    )
);
if (APP_DEBUG) {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_STRICT);
}
