<?php
declare(strict_types=1);

require_once __DIR__ . '/functions.php';

function indexnow_is_enabled(): bool
{
    $envEnabled = defined('INDEXNOW_ENABLED') ? (bool) INDEXNOW_ENABLED : false;
    // Allow admin toggle via site_settings to override env when explicitly set.
    if (function_exists('site_setting_get')) {
        $stored = site_setting_get('indexnow_enabled', null);
        if ($stored !== null) {
            $envEnabled = $stored === '1';
        }
    }

    return $envEnabled && indexnow_key() !== '';
}

function indexnow_key(): string
{
    return defined('INDEXNOW_KEY') ? trim((string) INDEXNOW_KEY) : '';
}

function indexnow_endpoint(): string
{
    $endpoint = defined('INDEXNOW_ENDPOINT')
        ? trim((string) INDEXNOW_ENDPOINT)
        : 'https://api.indexnow.org/indexnow';

    return $endpoint !== '' ? $endpoint : 'https://api.indexnow.org/indexnow';
}

function indexnow_timeout_seconds(): int
{
    $timeout = defined('INDEXNOW_TIMEOUT') ? (int) INDEXNOW_TIMEOUT : 3;
    return max(1, $timeout);
}

function indexnow_site_host(): string
{
    $host = parse_url(APP_URL, PHP_URL_HOST);
    return is_string($host) ? strtolower($host) : '';
}

function indexnow_key_location(): string
{
    $configured = defined('INDEXNOW_KEY_LOCATION') ? trim((string) INDEXNOW_KEY_LOCATION) : '';
    if ($configured !== '') {
        return $configured;
    }

    $key = indexnow_key();
    if ($key === '') {
        return '';
    }

    return base_url($key . '.txt');
}

function indexnow_normalize_url(string $url): ?string
{
    $value = trim($url);
    if ($value === '') {
        return null;
    }

    if (str_starts_with($value, '//')) {
        $value = 'https:' . $value;
    } elseif (!preg_match('~^https?://~i', $value)) {
        $value = base_url(ltrim($value, '/'));
    }

    $parts = parse_url($value);
    if (!is_array($parts)) {
        return null;
    }

    $scheme = strtolower((string) ($parts['scheme'] ?? ''));
    $host = strtolower((string) ($parts['host'] ?? ''));
    $siteHost = indexnow_site_host();

    if (($scheme !== 'http' && $scheme !== 'https') || $host === '' || $siteHost === '' || $host !== $siteHost) {
        return null;
    }

    return $value;
}

/**
 * Submit URLs to IndexNow.
 * Never throws to avoid breaking admin saves/deletes.
 */
function indexnow_submit_urls(array $urls): bool
{
    if (!indexnow_is_enabled()) {
        return false;
    }

    $host = indexnow_site_host();
    $key = indexnow_key();
    $keyLocation = indexnow_key_location();

    if ($host === '' || $key === '' || $keyLocation === '') {
        error_log('[indexnow] Missing host/key/keyLocation configuration.');
        return false;
    }

    $normalized = [];
    foreach ($urls as $candidate) {
        if (!is_string($candidate)) {
            continue;
        }

        $normalizedUrl = indexnow_normalize_url($candidate);
        if ($normalizedUrl !== null) {
            $normalized[] = $normalizedUrl;
        }
    }
    $normalized = array_values(array_unique($normalized));

    if (!$normalized) {
        return false;
    }

    if (!function_exists('curl_init')) {
        error_log('[indexnow] cURL extension is not available.');
        return false;
    }

    $payload = [
        'host' => $host,
        'key' => $key,
        'keyLocation' => $keyLocation,
        'urlList' => $normalized,
    ];

    $json = json_encode($payload, JSON_UNESCAPED_SLASHES);
    if ($json === false) {
        error_log('[indexnow] Failed to encode JSON payload.');
        return false;
    }

    $ch = curl_init(indexnow_endpoint());
    if ($ch === false) {
        error_log('[indexnow] Failed to initialize cURL.');
        return false;
    }

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json; charset=utf-8',
        ],
        CURLOPT_POSTFIELDS => $json,
        CURLOPT_CONNECTTIMEOUT => 2,
        CURLOPT_TIMEOUT => indexnow_timeout_seconds(),
        CURLOPT_USERAGENT => APP_NAME . ' IndexNow/1.0',
    ]);

    $response = curl_exec($ch);
    $httpCode = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $curlError = curl_error($ch);

    curl_close($ch);

    if ($response === false) {
        error_log('[indexnow] Request failed: ' . $curlError);
        return false;
    }

    if ($httpCode !== 200) {
        error_log('[indexnow] Unexpected HTTP status ' . $httpCode . ' response=' . (string) $response);
        return false;
    }

    return true;
}
