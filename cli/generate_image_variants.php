<?php
declare(strict_types=1);

/**
 * Generate WebP/AVIF/JPG width variants for every image under assets/uploads/.
 *
 * Sidecar naming: <stem>.w<width>.<format> next to the original. Example:
 *   destinations/sigiriya-rock.jpg
 *   -> destinations/sigiriya-rock.w400.jpg
 *      destinations/sigiriya-rock.w400.webp
 *      destinations/sigiriya-rock.w400.avif
 *      destinations/sigiriya-rock.w800.jpg
 *      ... etc.
 *
 * Skips:
 *  - non-image files
 *  - existing variant sidecars (filename matches *.w<digits>.*)
 *  - originals smaller than the requested width (no upscaling)
 *  - variants already on disk (unless --force)
 *
 * Usage:
 *   php cli/generate_image_variants.php                  # full run
 *   php cli/generate_image_variants.php --dry-run        # report only
 *   php cli/generate_image_variants.php --force          # regenerate existing
 *   php cli/generate_image_variants.php --widths=400,800 # custom widths
 *   php cli/generate_image_variants.php --formats=webp   # subset of formats
 *   php cli/generate_image_variants.php --path=destinations
 *   php cli/generate_image_variants.php --root=assets/images/home-hero
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "This script must be run from the command line.\n");
    exit(1);
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/responsive_image.php';

$args = array_slice($argv, 1);
$flags = [
    'dry-run' => false,
    'force'   => false,
    'widths'  => RESPONSIVE_IMAGE_DEFAULT_WIDTHS,
    'formats' => array_values(array_unique(array_merge(['jpg'], RESPONSIVE_IMAGE_DEFAULT_FORMATS))),
    'root'    => '',
    'path'    => '',
];

foreach ($args as $arg) {
    if ($arg === '--dry-run') { $flags['dry-run'] = true; continue; }
    if ($arg === '--force')   { $flags['force']   = true; continue; }
    if (str_starts_with($arg, '--widths=')) {
        $flags['widths'] = array_values(array_filter(array_map('intval', explode(',', substr($arg, 9)))));
        continue;
    }
    if (str_starts_with($arg, '--formats=')) {
        $flags['formats'] = array_values(array_filter(array_map('strtolower', array_map('trim', explode(',', substr($arg, 10))))));
        continue;
    }
    if (str_starts_with($arg, '--root=')) {
        $flags['root'] = trim(substr($arg, 7));
        continue;
    }
    if (str_starts_with($arg, '--path=')) {
        $flags['path'] = trim(substr($arg, 7));
        continue;
    }
}

if (!extension_loaded('gd')) {
    fwrite(STDERR, "ext-gd is required.\n");
    exit(1);
}

$gdInfo = gd_info();
$canWebp = !empty($gdInfo['WebP Support']);
$canAvif = !empty($gdInfo['AVIF Support']);

$root = UPLOAD_BASE_PATH;
if ($flags['root'] !== '') {
    $candidate = str_replace('\\', '/', trim($flags['root']));
    if (preg_match('~^[a-zA-Z]:/~', $candidate) || str_starts_with($candidate, '/')) {
        $root = $candidate;
    } else {
        $root = rtrim(BASE_PATH, '/\\') . '/' . trim($candidate, '/');
    }
}
if ($flags['path'] !== '') {
    $root = rtrim($root, '/\\') . '/' . trim($flags['path'], '/');
}

$printPrefix = rtrim(str_replace('\\', '/', $root), '/') . '/';
if (!is_dir($root)) {
    fwrite(STDERR, "Not a directory: $root\n");
    exit(1);
}

$counters = ['scanned' => 0, 'eligible' => 0, 'written' => 0, 'skipped_existing' => 0, 'skipped_small' => 0, 'errors' => 0];

$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));

foreach ($it as $file) {
    if (!$file->isFile()) {
        continue;
    }
    $counters['scanned']++;

    $path = $file->getPathname();
    $ext = strtolower($file->getExtension());

    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
        continue;
    }

    // Skip existing variants — matches anything ending in .w123.<ext>
    $basename = $file->getBasename('.' . $ext);
    if (preg_match('/\.w\d+$/', $basename)) {
        continue;
    }

    $counters['eligible']++;

    $img = @load_image($path, $ext);
    if (!$img) {
        $counters['errors']++;
        fwrite(STDERR, "Could not load: $path\n");
        continue;
    }

    $origWidth = imagesx($img);

    foreach ($flags['widths'] as $targetWidth) {
        if ($targetWidth >= $origWidth) {
            $counters['skipped_small']++;
            continue;
        }

        $resized = null;
        foreach ($flags['formats'] as $format) {
            if ($format === 'webp' && !$canWebp) continue;
            if ($format === 'avif' && !$canAvif) continue;

            $variantPath = sprintf('%s/%s.w%d.%s', dirname($path), $basename, $targetWidth, $format);

            if (!$flags['force'] && is_file($variantPath)) {
                $counters['skipped_existing']++;
                continue;
            }

            if ($resized === null) {
                $resized = resize_image($img, $targetWidth);
                if (!$resized) {
                    $counters['errors']++;
                    continue;
                }
            }

            if ($flags['dry-run']) {
                $counters['written']++;
                printf("[dry-run] %s\n", str_replace($printPrefix, '', str_replace('\\', '/', $variantPath)));
                continue;
            }

            if (write_image($resized, $variantPath, $format)) {
                $counters['written']++;
                printf("✓ %s\n", str_replace($printPrefix, '', str_replace('\\', '/', $variantPath)));
            } else {
                $counters['errors']++;
                fwrite(STDERR, "Failed to write: $variantPath\n");
            }
        }

        if ($resized) {
            imagedestroy($resized);
        }
    }

    imagedestroy($img);
}

echo "\n";
echo "scanned: {$counters['scanned']}\n";
echo "eligible originals: {$counters['eligible']}\n";
echo "variants written: {$counters['written']}\n";
echo "skipped (already exists): {$counters['skipped_existing']}\n";
echo "skipped (smaller than target): {$counters['skipped_small']}\n";
echo "errors: {$counters['errors']}\n";

exit($counters['errors'] > 0 ? 1 : 0);

// ──────────────────────────────────────────────────────────────────────────

function load_image(string $path, string $ext): ?GdImage
{
    return match ($ext) {
        'jpg', 'jpeg' => imagecreatefromjpeg($path) ?: null,
        'png'         => imagecreatefrompng($path) ?: null,
        'webp'        => imagecreatefromwebp($path) ?: null,
        default       => null,
    };
}

function resize_image(GdImage $src, int $targetWidth): ?GdImage
{
    $srcW = imagesx($src);
    $srcH = imagesy($src);
    if ($srcW <= 0 || $srcH <= 0) {
        return null;
    }
    $targetH = (int) round($srcH * ($targetWidth / $srcW));
    $dst = imagecreatetruecolor($targetWidth, $targetH);
    imagealphablending($dst, false);
    imagesavealpha($dst, true);
    $transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
    imagefilledrectangle($dst, 0, 0, $targetWidth, $targetH, $transparent);
    imagealphablending($dst, true);
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $targetWidth, $targetH, $srcW, $srcH);
    return $dst;
}

function write_image(GdImage $img, string $path, string $format): bool
{
    return match ($format) {
        'jpg', 'jpeg' => imagejpeg($img, $path, 82),
        'png'         => imagepng($img, $path, 6),
        'webp'        => imagewebp($img, $path, 80),
        'avif'        => imageavif($img, $path, 60),
        default       => false,
    };
}
