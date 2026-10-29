<?php
declare(strict_types=1);

require_once __DIR__ . '/functions.php';

/** Published, canonical booking information only; never seed or publish CMS rows. */
function published_booking_page_links(): array
{
    $labels = [
        'booking-terms-and-conditions' => 'Booking terms and conditions',
        'cancellation-refund-policy' => 'Cancellation and refund policy',
        'payment-deposit-policy' => 'Payment and deposit policy',
        'book-with-confidence' => 'Book with confidence',
        'trust-safety' => 'Trust and safety',
        'drivers-vehicles' => 'Drivers and vehicles',
        'how-booking-works' => 'How booking works',
        'tour-inclusions-exclusions' => 'Tour inclusions and exclusions',
    ];
    if (!db_table_exists('static_pages') || !db_column_exists('static_pages', 'is_published')) {
        return [];
    }

    $canonicalColumn = db_column_exists('static_pages', 'canonical_url') ? 'canonical_url' : "'' AS canonical_url";
    $placeholders = implode(',', array_fill(0, count($labels), '?'));
    $pages = fetch_all(
        "SELECT slug, {$canonicalColumn} FROM static_pages WHERE is_published = 1 AND slug IN ({$placeholders})",
        array_keys($labels)
    );
    $pages = array_column($pages, null, 'slug');
    $links = [];
    foreach ($labels as $slug => $label) {
        if (!isset($pages[$slug])) {
            continue;
        }
        $url = base_url($slug);
        $canonical = trim((string) ($pages[$slug]['canonical_url'] ?? ''));
        if ($canonical !== '' && rtrim($canonical, '/') !== rtrim($url, '/')) {
            continue;
        }
        $links[] = ['url' => $canonical !== '' ? $canonical : $url, 'label' => $label];
    }
    return $links;
}

/** Normalize an internal URL or request path for footer duplicate/self-link checks. */
function booking_page_link_path(string $url): ?string
{
    $parts = parse_url(trim($url));
    $base = parse_url(APP_URL);
    if ($parts === false || $base === false) {
        return null;
    }
    if (isset($parts['scheme']) || isset($parts['host'])) {
        $scheme = strtolower((string) ($parts['scheme'] ?? $base['scheme'] ?? ''));
        $baseScheme = strtolower((string) ($base['scheme'] ?? ''));
        if ($scheme !== $baseScheme
            || strcasecmp((string) ($parts['host'] ?? ''), (string) ($base['host'] ?? '')) !== 0
            || ($parts['port'] ?? ($scheme === 'https' ? 443 : 80)) !== ($base['port'] ?? ($baseScheme === 'https' ? 443 : 80))) {
            return null;
        }
    }
    $path = '/' . ltrim((string) ($parts['path'] ?? ''), '/');
    $basePath = rtrim((string) ($base['path'] ?? ''), '/');
    if ($basePath !== '' && str_starts_with($path, $basePath . '/')) {
        $path = substr($path, strlen($basePath));
    } elseif ($basePath !== '' && isset($parts['host'])) {
        return null;
    }
    return rtrim($path, '/') ?: '/';
}

function booking_page_links_excluding(array $links, array $excludedUrls): array
{
    $excluded = [];
    foreach ($excludedUrls as $url) {
        $path = booking_page_link_path((string) $url);
        if ($path !== null) {
            $excluded[$path] = true;
        }
    }
    return array_values(array_filter($links, static fn(array $link): bool => !isset($excluded[booking_page_link_path((string) $link['url'])])));
}
