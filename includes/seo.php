<?php
declare(strict_types=1);

/**
 * Reusable SEO meta tag output
 *
 * Expected page variables:
 * $seoTitle
 * $seoDescription
 * $seoCanonical
 * $seoImage
 * $seoType
 * $seoRobots
 */

if (!function_exists('seo_filter_canonical')) {
    /**
     * When filter params are present, canonicalize to the unfiltered URL and noindex the page.
     *
     * @return array{canonical:string,robots:string|null}
     */
    function seo_filter_canonical(string $baseUrl, array $activeFilters): array
    {
        $hasFilters = false;
        foreach ($activeFilters as $value) {
            if (is_array($value)) {
                $value = implode(',', $value);
            }
            if (trim((string) $value) !== '') {
                $hasFilters = true;
                break;
            }
        }

        if (!$hasFilters) {
            return ['canonical' => $baseUrl, 'robots' => null];
        }

        return [
            'canonical' => $baseUrl,
            'robots' => 'noindex,follow,max-image-preview:large',
        ];
    }
}

if (!function_exists('seo_normalize_title')) {
    function seo_normalize_title(string $title): string
    {
        $title = trim(preg_replace('/\s+/', ' ', $title) ?? '');
        if ($title === '' || strcasecmp($title, APP_NAME) === 0) {
            return APP_NAME;
        }

        $brand = preg_quote(APP_NAME, '/');
        $title = preg_replace('/\s+[—–-]\s+' . $brand . '$/i', ' | ' . APP_NAME, $title) ?? $title;

        if (!str_contains(strtolower($title), strtolower(APP_NAME))) {
            $title .= ' | ' . APP_NAME;
        }

        return $title;
    }
}

if (!function_exists('seo_normalize_canonical')) {
    /**
     * Keep stored canonical overrides on the configured site origin.
     *
     * Cross-host and malformed values are legacy/editorial data defects, not
     * redirects. Falling back to the current route avoids publishing a
     * canonical that points search engines at an unrelated origin.
     */
    function seo_normalize_canonical(string $candidate, string $fallback): string
    {
        require_once __DIR__ . '/retired_package_pages.php';
        if (package_reference_path_retired($candidate)) { return $fallback; }
        $candidate = trim($candidate);
        $fallback = trim($fallback);
        $fallbackParts = parse_url($fallback);
        $candidateParts = parse_url($candidate);

        if ($fallback === '' || !is_array($fallbackParts) || !is_array($candidateParts)) {
            return $fallback;
        }

        $fallbackScheme = strtolower((string) ($fallbackParts['scheme'] ?? ''));
        $candidateScheme = strtolower((string) ($candidateParts['scheme'] ?? ''));
        $fallbackHost = strtolower((string) ($fallbackParts['host'] ?? ''));
        $candidateHost = strtolower((string) ($candidateParts['host'] ?? ''));
        $fallbackPort = (int) ($fallbackParts['port'] ?? ($fallbackScheme === 'https' ? 443 : 80));
        $candidatePort = (int) ($candidateParts['port'] ?? ($candidateScheme === 'https' ? 443 : 80));

        $sameOrigin = in_array($fallbackScheme, ['http', 'https'], true)
            && $candidateScheme === $fallbackScheme
            && $fallbackHost !== ''
            && $candidateHost === $fallbackHost
            && $candidatePort === $fallbackPort
            && empty($candidateParts['user'])
            && empty($candidateParts['pass'])
            && empty($candidateParts['fragment']);

        return $sameOrigin ? $candidate : $fallback;
    }
}

// Crawl-safe filtered listing pages:
// Pages can provide:
// - $seoFilterCanonicalBase (string): canonical URL for the unfiltered page
// - $seoFilterParams (array): query param names that indicate the page is "filtered"
if (isset($seoFilterCanonicalBase, $seoFilterParams) && is_string($seoFilterCanonicalBase) && is_array($seoFilterParams)) {
    $active = [];
    foreach ($seoFilterParams as $param) {
        $param = trim((string) $param);
        if ($param === '') {
            continue;
        }
        $value = $_GET[$param] ?? null;
        if (is_array($value) && $value) {
            $active[$param] = $value;
        } elseif (is_string($value) && trim($value) !== '') {
            $active[$param] = $value;
        }
    }

    $filtered = seo_filter_canonical($seoFilterCanonicalBase, $active);
    if (!empty($active)) {
        $seoCanonical = $filtered['canonical'];
        if (!empty($filtered['robots'])) {
            $seoRobots = (string) $filtered['robots'];
        }
    }
}

$seoTitle = seo_normalize_title((string) ($seoTitle ?? APP_NAME));
$seoDescription = $seoDescription ?? 'Private tours in Sri Lanka with experienced driver-guides, comfortable vehicles, and custom itineraries.';
$seoCanonical = seo_normalize_canonical(
    (string) ($seoCanonical ?? ''),
    base_url(ltrim(request_path(), '/'))
);
$seoImage = $seoImage ?? DEFAULT_OG_IMAGE;
$seoImageWidth = isset($seoImageWidth) ? (int) $seoImageWidth : null;
$seoImageHeight = isset($seoImageHeight) ? (int) $seoImageHeight : null;
$seoType = $seoType ?? 'website';
$seoRobots = $seoRobots ?? 'index,follow,max-image-preview:large';
$seoOgTitle = trim((string) ($seoOgTitle ?? '')) !== '' ? (string) $seoOgTitle : $seoTitle;
$seoOgDescription = trim((string) ($seoOgDescription ?? '')) !== '' ? (string) $seoOgDescription : (string) $seoDescription;
?>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1">

<title><?= e($seoTitle) ?></title>
<meta name="description" content="<?= e($seoDescription) ?>">
<meta name="robots" content="<?= e($seoRobots) ?>">
<link rel="canonical" href="<?= e($seoCanonical) ?>">

<meta property="og:locale" content="en_US">
<meta property="og:type" content="<?= e($seoType) ?>">
<meta property="og:site_name" content="<?= e(APP_NAME) ?>">
<meta property="og:title" content="<?= e($seoOgTitle) ?>">
<meta property="og:description" content="<?= e($seoOgDescription) ?>">
<meta property="og:url" content="<?= e($seoCanonical) ?>">
<meta property="og:image" content="<?= e($seoImage) ?>">
<?php if(!empty($seoImageAlt)): ?><meta property="og:image:alt" content="<?= e((string)$seoImageAlt) ?>"><?php endif; ?>
<?php if ($seoImageWidth && $seoImageHeight): ?>
<meta property="og:image:width" content="<?= e((string) $seoImageWidth) ?>">
<meta property="og:image:height" content="<?= e((string) $seoImageHeight) ?>">
<?php endif; ?>

<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?= e($seoOgTitle) ?>">
<meta name="twitter:description" content="<?= e($seoOgDescription) ?>">
<meta name="twitter:image" content="<?= e($seoImage) ?>">
<?php if(!empty($seoImageAlt)): ?><meta name="twitter:image:alt" content="<?= e((string)$seoImageAlt) ?>"><?php endif; ?>
