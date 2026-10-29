<?php
declare(strict_types=1);

namespace NewSite;

function request_guard(array $server): int
{
    if (!in_array($server['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true)) {
        return 403;
    }
    foreach (['HTTP_FORWARDED', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED_HOST'] as $header) {
        if (isset($server[$header])) {
            return 403;
        }
    }
    $host = $server['HTTP_HOST'] ?? '';
    if (!is_string($host) || !preg_match('/\A(127\.0\.0\.1|localhost|\[::1\])(?::([0-9]{1,5}))?\z/iD', $host, $match)) {
        return 403;
    }
    $port = (int) ($match[2] ?? 80);
    if ($port < 1 || $port > 65535 || $port !== (int) ($server['SERVER_PORT'] ?? 0)) {
        return 403;
    }
    return in_array($server['REQUEST_METHOD'] ?? '', ['GET', 'HEAD'], true) ? 200 : 405;
}

function response_headers(): void
{
    header_remove('X-Powered-By');
    header('X-Robots-Tag: noindex, nofollow, noarchive');
    header('Cache-Control: no-store, private');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: no-referrer');
    header('X-Frame-Options: DENY');
    header("Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=()");
    header("Content-Security-Policy: default-src 'none'; script-src 'self'; style-src 'self'; img-src 'self'; font-src 'self'; connect-src 'none'; media-src 'none'; object-src 'none'; base-uri 'none'; form-action 'self'; frame-ancestors 'none'");
}

function stop_response(int $status, string $message): never
{
    http_response_code($status);
    if ($status === 405) {
        header('Allow: GET, HEAD');
    }
    header('Content-Type: text/plain; charset=UTF-8');
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'HEAD') {
        echo $message;
    }
    exit;
}

function enforce_request(): void
{
    // Runtime errors must never reach the original application's configured log.
    ini_set('display_errors', '0');
    ini_set('log_errors', '0');
    response_headers();
    $expected = realpath(dirname(__DIR__) . '/public');
    $actual = realpath((string) ($_SERVER['DOCUMENT_ROOT'] ?? ''));
    if (PHP_SAPI !== 'cli-server' || $expected === false || $actual !== $expected) {
        stop_response(403, 'Preview requires its isolated loopback runtime.');
    }
    $status = request_guard($_SERVER);
    if ($status !== 200) {
        stop_response($status, $status === 405 ? 'Read-only preview: method not allowed.' : 'Preview access denied.');
    }
}

function normalized_path(string $path): ?string
{
    if ($path === '' || strlen($path) > 512 || preg_match('/[\x00-\x20\x7f\\\\?#]/', $path)) {
        return null;
    }
    $path = rawurldecode($path);
    $path = strtolower($path);
    if ($path === '/') {
        return '/';
    }
    if (!preg_match('~\A/[a-z0-9][a-z0-9/_.-]*\z~D', $path)
        || str_contains($path, '//') || str_contains($path, '..')
        || preg_match('~(?:^|/)\.[^/]*(?:/|$)~', $path)) {
        return null;
    }
    return rtrim($path, '/');
}

function request_path(array $server): ?string
{
    $uri = $server['REQUEST_URI'] ?? '/';
    if (!is_string($uri) || !str_starts_with($uri, '/') || str_starts_with($uri, '//')) {
        return null;
    }
    return normalized_path(explode('?', $uri, 2)[0]);
}

function contained_file(string $root, string $relative): ?string
{
    if ($relative === '' || preg_match('~(?:\A/|\\\\|\x00|(?:\A|/)\.{1,2}(?:/|\z)|:)~', $relative)) {
        return null;
    }
    $base = realpath($root);
    $file = $base === false ? false : realpath($base . DIRECTORY_SEPARATOR . $relative);
    if ($base === false || $file === false || !is_file($file)
        || !str_starts_with($file, $base . DIRECTORY_SEPARATOR)) {
        return null;
    }
    return $file;
}

function public_asset(string $path): ?array
{
    if (!str_starts_with($path, '/assets/')) {
        return null;
    }
    $types = ['css' => 'text/css; charset=UTF-8', 'js' => 'text/javascript; charset=UTF-8',
        'woff' => 'font/woff', 'woff2' => 'font/woff2', 'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp', 'avif' => 'image/avif', 'svg' => 'image/svg+xml'];
    $type = $types[strtolower(pathinfo($path, PATHINFO_EXTENSION))] ?? null;
    $file = contained_file(dirname(__DIR__) . '/public/assets', substr($path, 8));
    if ($file === null || $type === null) {
        return null;
    }
    return ['file' => $file, 'type' => $type];
}
