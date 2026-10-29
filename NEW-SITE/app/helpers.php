<?php
declare(strict_types=1);

namespace NewSite;

function e(string|int|float|null $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function url(string $route = '/', array $query = []): string
{
    $path = normalized_path($route);
    if ($path === null || !isset(route_manifest()[$path])) {
        return '/';
    }
    $suffix = http_build_query($query, '', '&', PHP_QUERY_RFC3986);
    return $path . ($suffix !== '' ? '?' . $suffix : '');
}

function media(string $path): string
{
    return in_array($path, media_allowlist(), true) ? '/media.php?path=' . rawurlencode($path) : '';
}

function icon(string $name, string $class = ''): string
{
    $paths = [
        'arrow-right' => '<path d="M5 12h14m-6-6 6 6-6 6"/>',
        'arrow-left' => '<path d="M19 12H5m6-6-6 6 6 6"/>',
        'arrow-up-right' => '<path d="M6 18 18 6M6 6h12v12"/>',
        'arrow-down' => '<path d="M12 5v14m-6-6 6 6 6-6"/>',
        'chevron-down' => '<path d="m6 9 6 6 6-6"/>',
        'chevron-right' => '<path d="m9 6 6 6-6 6"/>',
        'menu' => '<path d="M4 6h16M4 12h16M4 18h16"/>',
        'close' => '<path d="m6 6 12 12M6 18 18 6"/>',
        'pin' => '<path d="M20 10c0 6-8 11-8 11S4 16 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2"/>',
        'user' => '<circle cx="12" cy="8" r="4"/><path d="M4 22v-2a8 8 0 0 1 16 0v2"/>',
        'account' => '<circle cx="12" cy="8" r="4"/><path d="M4 22v-2a8 8 0 0 1 16 0v2"/>',
        'compass' => '<circle cx="12" cy="12" r="9"/><path d="m16 8-2 6-6 2 2-6 6-2Z"/>',
        'route' => '<circle cx="5" cy="5" r="2"/><circle cx="19" cy="19" r="2"/><path d="M7 5h7a4 4 0 0 1 0 8H9a4 4 0 0 0 0 8h8"/>',
        'leaf' => '<path d="M20 4C9 3 3 8 5 15c2 7 14 6 15-11ZM5 20 16 9"/>',
        'shield' => '<path d="m12 3 8 3v6c0 5-8 9-8 9s-8-4-8-9V6l8-3Z"/><path d="m8 12 3 3 5-6"/>',
        'check' => '<path d="m5 12 4 4L19 6"/>',
        'sun' => '<circle cx="12" cy="12" r="4"/><path d="M12 2v2m0 16v2M2 12h2m16 0h2M5 5l1 1m12 12 1 1M5 19l1-1M18 6l1-1"/>',
    ];
    if (!isset($paths[$name])) {
        return '';
    }
    return '<svg class="' . e($class) . '" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $paths[$name] . '</svg>';
}
