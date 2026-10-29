<?php
declare(strict_types=1);

/**
 * Escape output safely for HTML.
 */
function e(mixed $value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}

/**
 * Build a site URL.
 */
function base_url(string $path = ''): string
{
    return APP_URL . ($path !== '' ? '/' . ltrim($path, '/') : '');
}

/**
 * Redirect to a local or absolute URL.
 */
function redirect(string $url, int $statusCode = 302): void
{
    $location = preg_match('~^https?://~i', $url) ? $url : base_url($url);
    header('Location: ' . $location, true, $statusCode);
    exit;
}

/**
 * Return current request path.
 */
function request_path(): string
{
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $path = '/' . ltrim($path, '/');

    return ($path === '//') ? '/' : (rtrim($path, '/') ?: '/');
}

/**
 * HTTP method helpers.
 */
function is_post(): bool
{
    return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

/**
 * Abort with a flash error if the POST body exceeded post_max_size.
 * PHP silently drops $_POST + $_FILES when this happens, causing confusing
 * "Name is required" validation failures on image-heavy admin saves.
 */
function guard_post_size(): void
{
    $contentLength = (int) ($_SERVER['CONTENT_LENGTH'] ?? 0);
    if ($contentLength <= 0) {
        return;
    }
    $raw = (string) ini_get('post_max_size');
    $units = ['K' => 1024, 'M' => 1048576, 'G' => 1073741824];
    $unit = strtoupper(substr($raw, -1));
    $max = (int) $raw * ($units[$unit] ?? 1);
    if ($max > 0 && $contentLength > $max) {
        flash_set('error', 'Upload too large. Please reduce image file sizes (max ~20 MB total) and try again.');
        $back = $_SERVER['HTTP_REFERER'] ?? base_url('admin');
        header('Location: ' . $back);
        exit;
    }
}

function is_get(): bool
{
    return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET';
}

/**
 * Request input helpers.
 */
function input(string $key, mixed $default = null): mixed
{
    return $_REQUEST[$key] ?? $default;
}

function get_input(string $key, mixed $default = null): mixed
{
    return $_GET[$key] ?? $default;
}

function post_input(string $key, mixed $default = null): mixed
{
    return $_POST[$key] ?? $default;
}

/**
 * Flash messaging helpers.
 */
function flash_set(string $key, string $message): void
{
    $_SESSION[FLASH_SESSION_KEY][$key] = $message;
}

function flash_get(string $key): ?string
{
    $message = $_SESSION[FLASH_SESSION_KEY][$key] ?? null;
    unset($_SESSION[FLASH_SESSION_KEY][$key]);

    return $message;
}

/**
 * Old input helpers for form validation.
 */
function old_input_set(array $data): void
{
    $_SESSION[OLD_INPUT_SESSION_KEY] = $data;
}

function old_input(string $key, mixed $default = null): mixed
{
    return $_SESSION[OLD_INPUT_SESSION_KEY][$key] ?? $default;
}

function old_input_clear(): void
{
    unset($_SESSION[OLD_INPUT_SESSION_KEY]);
}

/**
 * Slug generator.
 */
function slugify(string $value): string
{
    $value = trim($value);
    $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) ?: $value;
    $value = strtolower($value);
    $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?: '';
    $value = trim($value, '-');

    return $value !== '' ? $value : 'item-' . time();
}

function decode_string_list_json(?string $json): array
{
    if (!$json) {
        return [];
    }
    $data = json_decode((string) $json, true);
    if (!is_array($data)) {
        return [];
    }
    return array_values(array_filter(array_map(
        static fn($v) => trim((string) $v),
        $data
    ), static fn(string $v): bool => $v !== ''));
}

function textarea_lines_to_list(string $text, int $limit = 30): array
{
    $lines = preg_split('/\\r?\\n+/', trim($text)) ?: [];
    $items = [];
    foreach ($lines as $line) {
        $line = trim((string) $line);
        if ($line === '') {
            continue;
        }
        $items[] = $line;
        if (count($items) >= $limit) {
            break;
        }
    }
    return $items;
}

function request_ip_address(): string
{
    $remoteAddr = trim((string) ($_SERVER['REMOTE_ADDR'] ?? ''));
    $forwardedFor = trim((string) ($_SERVER['HTTP_X_FORWARDED_FOR'] ?? ''));

    if ($remoteAddr !== '' && $forwardedFor !== '' && request_is_trusted_proxy()) {
        foreach (preg_split('/\s*,\s*/', $forwardedFor, -1, PREG_SPLIT_NO_EMPTY) ?: [] as $candidate) {
            $candidate = trim((string) $candidate);
            if (filter_var($candidate, FILTER_VALIDATE_IP)) {
                return substr($candidate, 0, 45);
            }
        }
    }

    return substr($remoteAddr, 0, 45);
}

function form_submission_limit_exceeded(string $formKey, string $ipAddress, int $windowSeconds = 900, int $maxSubmissions = 5): bool
{
    if ($formKey === '' || $ipAddress === '') {
        return false;
    }

    try {
        ensure_contact_inquiries_extended_schema();

        execute_query(
            'DELETE FROM form_submission_limits WHERE created_at < DATE_SUB(NOW(), INTERVAL 2 DAY)'
        );

        $windowSeconds = max(30, $windowSeconds);
        $maxSubmissions = max(1, $maxSubmissions);

        $windowStart = date('Y-m-d H:i:s', time() - $windowSeconds);
        $row = fetch_one(
            "SELECT COUNT(*) AS total
             FROM form_submission_limits
             WHERE form_key = :form_key
               AND ip_address = :ip_address
               AND created_at >= :window_start",
            [
                'form_key' => substr($formKey, 0, 80),
                'ip_address' => $ipAddress,
                'window_start' => $windowStart,
            ]
        );

        return (int) ($row['total'] ?? 0) >= $maxSubmissions;
    } catch (Throwable) {
        return false;
    }
}

function form_submission_log_hit(string $formKey, string $ipAddress): void
{
    if ($formKey === '' || $ipAddress === '') {
        return;
    }

    try {
        ensure_contact_inquiries_extended_schema();
        execute_query(
            'INSERT INTO form_submission_limits (form_key, ip_address) VALUES (:form_key, :ip_address)',
            [
                'form_key' => substr($formKey, 0, 80),
                'ip_address' => substr($ipAddress, 0, 45),
            ]
        );
    } catch (Throwable) {
    }
}


/**
 * Generate a label tag with proper for/id mapping.
 */
function label_for(string $id, string $text, array $attributes = []): string
{
    $attrString = '';
    foreach ($attributes as $key => $value) {
        $attrString .= ' ' . e($key) . '="' . e((string) $value) . '"';
    }
    return sprintf('<label for="%s"%s>%s</label>', e($id), $attrString, e($text));
}

/**
 * Standard ARIA attribute helpers for form fields.
 *
 * field_state('error',  $id) → 'aria-invalid="true" aria-describedby="<id>-error"'
 * field_state('valid',  $id) → 'aria-invalid="false"'
 * field_state('hint',   $id) → 'aria-describedby="<id>-help"'
 *
 * Plus a pair of render helpers that emit the <div> for the message itself:
 * field_error_message($id, $msg)  → '<div id="<id>-error" role="alert" class="invalid-feedback">…</div>'
 * field_help_message($id,  $msg)  → '<div id="<id>-help"  class="form-text">…</div>'
 */
function field_state(string $state, string $id, bool $hasError = true): string
{
    $parts = [];
    if ($state === 'error' && $hasError) {
        $parts[] = 'aria-invalid="true"';
        $parts[] = 'aria-describedby="' . e($id) . '-error"';
    } elseif ($state === 'valid') {
        $parts[] = 'aria-invalid="false"';
    } elseif ($state === 'hint') {
        $parts[] = 'aria-describedby="' . e($id) . '-help"';
    }
    return implode(' ', $parts);
}

function field_error_message(string $id, string $message): string
{
    $message = trim($message);
    if ($message === '') {
        return '';
    }
    return '<div id="' . e($id) . '-error" class="invalid-feedback d-block" role="alert">' . e($message) . '</div>';
}

function field_help_message(string $id, string $message): string
{
    $message = trim($message);
    if ($message === '') {
        return '';
    }
    return '<div id="' . e($id) . '-help" class="form-text">' . e($message) . '</div>';
}

