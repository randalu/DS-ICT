<?php
declare(strict_types=1);

/**
 * Responsive image helpers.
 *
 * `responsive_image()` renders a <picture> with avif/webp <source>s plus an <img> fallback.
 * `image_variant_url()` builds the URL for a width/format variant produced by
 * cli/generate_image_variants.php (sidecar files: name.w800.webp, name.w800.avif, etc.).
 *
 * The fallback <img> always points at the original — so the page degrades gracefully
 * when variants haven't been generated yet.
 */

const RESPONSIVE_IMAGE_DEFAULT_WIDTHS = [400, 800, 1200, 1600];
const RESPONSIVE_IMAGE_DEFAULT_FORMATS = ['avif', 'webp'];

/**
 * Normalise a stored path or same-origin URL to a relative storage path
 * (e.g. "destinations/foo.jpg"). Returns null when the input is truly external.
 */
function image_normalize_storage_path(string $src): ?string
{
    $src = trim(str_replace('\\', '/', $src));
    if ($src === '') {
        return null;
    }

    if (preg_match('~^https?://~i', $src) || str_starts_with($src, '//')) {
        $appHost = parse_url(APP_URL, PHP_URL_HOST);
        $srcHost = parse_url($src, PHP_URL_HOST);
        if ($appHost === null || $srcHost === null || strcasecmp($appHost, $srcHost) !== 0) {
            return null;
        }
        $path = parse_url($src, PHP_URL_PATH);
        if (!$path) {
            return null;
        }
        $src = ltrim($path, '/');
    }

    $src = ltrim($src, '/');
    if (str_starts_with($src, 'assets/uploads/')) {
        return substr($src, strlen('assets/uploads/'));
    }
    if (str_starts_with($src, 'assets/')) {
        return $src;
    }
    if (str_starts_with($src, 'uploads/')) {
        return substr($src, strlen('uploads/'));
    }
    return $src;
}

/**
 * Build the URL for a width/format variant of a stored image path.
 * Returns the original URL when the path is external or cannot be transformed.
 */
function image_variant_url(string $src, int $width, string $format): string
{
    $relative = image_normalize_storage_path($src);
    if ($relative === null) {
        return $src;
    }

    $extPos = strrpos($relative, '.');
    if ($extPos === false) {
        return stored_media_url($relative);
    }

    $stem = substr($relative, 0, $extPos);
    $variant = sprintf('%s.w%d.%s', $stem, $width, ltrim($format, '.'));

    return stored_media_url($variant);
}

/**
 * Resolve a stored relative path to an absolute filesystem path for existence checks.
 * Returns null when the input is external or cannot be located on disk.
 */
function image_variant_filesystem_path(string $src, int $width, string $format): ?string
{
    $relative = image_normalize_storage_path($src);
    if ($relative === null) {
        return null;
    }

    $extPos = strrpos($relative, '.');
    if ($extPos === false) {
        return null;
    }

    $stem = substr($relative, 0, $extPos);
    $variantRel = sprintf('%s.w%d.%s', $stem, $width, ltrim($format, '.'));

    if (str_starts_with($variantRel, 'assets/')) {
        return BASE_PATH . '/' . $variantRel;
    }

    return UPLOAD_BASE_PATH . '/' . $variantRel;
}

/**
 * Render a <picture> element with srcset/sizes for modern formats and an <img> fallback.
 *
 * Options:
 *  - src             (string, required) stored path or absolute URL
 *  - alt             (string, required) alt text — empty string allowed for decorative images
 *  - widths          (int[]) candidate widths (default 400/800/1200/1600)
 *  - formats         (string[]) extra formats to emit as <source> (default ['avif','webp'])
 *  - sizes           (string) CSS sizes attribute (default '100vw')
 *  - width / height  (int) intrinsic dimensions to prevent CLS (highly recommended)
 *  - loading         ('lazy'|'eager') default 'lazy'
 *  - fetchpriority   ('auto'|'high'|'low') default 'auto'
 *  - decoding        ('async'|'sync'|'auto') default 'async'
 *  - class           (string) class for the <img>
 *  - picture_class   (string) class for the wrapping <picture>
 *  - id              (string) optional id on the <img>
 */
function responsive_image(array $opts): string
{
    $src = trim((string) ($opts['src'] ?? ''));
    if ($src === '') {
        return '';
    }

    $alt = (string) ($opts['alt'] ?? '');
    $widths = $opts['widths'] ?? RESPONSIVE_IMAGE_DEFAULT_WIDTHS;
    $formats = $opts['formats'] ?? RESPONSIVE_IMAGE_DEFAULT_FORMATS;
    $sizes = (string) ($opts['sizes'] ?? '100vw');
    $width = isset($opts['width']) ? (int) $opts['width'] : null;
    $height = isset($opts['height']) ? (int) $opts['height'] : null;
    $loading = (string) ($opts['loading'] ?? 'lazy');
    $fetchpriority = (string) ($opts['fetchpriority'] ?? 'auto');
    $decoding = (string) ($opts['decoding'] ?? 'async');
    $imgClass = (string) ($opts['class'] ?? '');
    $pictureClass = (string) ($opts['picture_class'] ?? '');
    $id = (string) ($opts['id'] ?? '');

    $widths = array_values(array_unique(array_map('intval', (array) $widths)));
    sort($widths);

    $relativePath = image_normalize_storage_path($src);
    $isExternal = ($relativePath === null);
    $imgUrl = stored_media_url($src);

    $sources = '';
    if (!$isExternal) {
        foreach ($formats as $format) {
            $format = strtolower(trim((string) $format));
            if ($format === '') {
                continue;
            }

            $entries = [];
            foreach ($widths as $w) {
                $fsPath = image_variant_filesystem_path($src, $w, $format);
                if ($fsPath === null || !is_file($fsPath)) {
                    continue;
                }
                $entries[] = e(image_variant_url($src, $w, $format)) . ' ' . $w . 'w';
            }

            if (!$entries) {
                continue;
            }

            $mime = $format === 'jpg' ? 'image/jpeg' : 'image/' . $format;
            $sources .= '<source type="' . e($mime) . '" srcset="' . implode(', ', $entries)
                . '" sizes="' . e($sizes) . '">';
        }
    }

    $imgAttrs = [
        'src' => $imgUrl,
        'alt' => $alt,
    ];

    if (!$isExternal) {
        $jpgEntries = [];
        foreach ($widths as $w) {
            $fsPath = image_variant_filesystem_path($src, $w, 'jpg');
            if ($fsPath !== null && is_file($fsPath)) {
                $jpgEntries[] = e(image_variant_url($src, $w, 'jpg')) . ' ' . $w . 'w';
            }
        }
        if ($jpgEntries) {
            $imgAttrs['srcset'] = implode(', ', $jpgEntries);
            $imgAttrs['sizes'] = $sizes;
        }
    }

    if ($width !== null) {
        $imgAttrs['width'] = (string) $width;
    }
    if ($height !== null) {
        $imgAttrs['height'] = (string) $height;
    }
    $imgAttrs['loading'] = $loading;
    $imgAttrs['decoding'] = $decoding;
    if ($fetchpriority !== 'auto') {
        $imgAttrs['fetchpriority'] = $fetchpriority;
    }
    if ($imgClass !== '') {
        $imgAttrs['class'] = $imgClass;
    }
    if ($id !== '') {
        $imgAttrs['id'] = $id;
    }

    $imgHtml = '<img';
    foreach ($imgAttrs as $k => $v) {
        if ($k === 'srcset' || $k === 'sizes') {
            $imgHtml .= ' ' . $k . '="' . $v . '"';
        } else {
            $imgHtml .= ' ' . $k . '="' . e((string) $v) . '"';
        }
    }
    $imgHtml .= '>';

    $pictureOpen = '<picture' . ($pictureClass !== '' ? ' class="' . e($pictureClass) . '"' : '') . '>';

    return $pictureOpen . $sources . $imgHtml . '</picture>';
}
