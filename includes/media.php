<?php
declare(strict_types=1);

/**
 * Build a URL for a stored media path.
 * Supports:
 * - absolute URLs (https://...)
 * - paths already under assets/... or /assets/...
 * - upload-relative paths like destinations/file.webp
 */
function stored_media_url(?string $path, string $fallback = ''): string
{
    $fallback = $fallback !== '' ? $fallback : DEFAULT_OG_IMAGE;
    $path = trim((string) $path);

    if ($path === '') {
        return $fallback;
    }

    $path = str_replace('\\', '/', $path);

    if (preg_match('~^https?://~i', $path)) {
        return $path;
    }

    if (str_starts_with($path, '//')) {
        return 'https:' . $path;
    }

    $normalized = ltrim($path, '/');

    if (str_starts_with($normalized, 'assets/')) {
        return base_url($normalized);
    }

    if (str_starts_with($normalized, 'images/')) {
        return base_url('assets/' . $normalized);
    }

    if (str_starts_with($normalized, 'uploads/')) {
        return base_url('assets/' . $normalized);
    }

    return UPLOAD_BASE_URL . '/' . $normalized;
}

function is_placeholder_media_url(?string $url): bool
{
    $value = trim((string) $url);
    if ($value === '') {
        return false;
    }

    $path = parse_url(str_replace('\\', '/', $value), PHP_URL_PATH);
    $path = strtolower((string) ($path ?: $value));

    return str_contains($path, '/placeholders/') || str_contains(basename($path), 'placeholder-');
}

/**
 * Pick a random hero image URL from a record list.
 *
 * Each record is checked using candidate keys in order.
 */
function random_record_hero_image(array $records, array $candidateKeys = ['hero_image'], string $fallback = ''): string
{
    $fallback = $fallback !== '' ? $fallback : DEFAULT_OG_IMAGE;
    $pool = [];

    foreach ($records as $record) {
        if (!is_array($record)) {
            continue;
        }

        foreach ($candidateKeys as $key) {
            $raw = trim((string) ($record[$key] ?? ''));
            if ($raw !== '') {
                $pool[] = $raw;
                break;
            }
        }
    }

    if (!$pool) {
        return $fallback;
    }

    try {
        $index = random_int(0, count($pool) - 1);
    } catch (Throwable) {
        $index = (int) array_rand($pool);
    }

    return stored_media_url($pool[$index], $fallback);
}

/**
 * Build an upload URL from a stored relative path.
 */
function upload_url(?string $relativePath): string
{
    return stored_media_url($relativePath);
}

/**
 * Resolve an upload-relative media path to a safe local filesystem path.
 */
function uploaded_media_filesystem_path(?string $path): ?string
{
    $path = trim(str_replace('\\', '/', (string) $path));
    if ($path === '' || preg_match('~^https?://~i', $path) || str_starts_with($path, '//')) {
        return null;
    }

    $path = ltrim($path, '/');
    if (str_starts_with($path, 'assets/uploads/')) {
        $path = substr($path, strlen('assets/uploads/'));
    } elseif (str_starts_with($path, 'uploads/')) {
        $path = substr($path, strlen('uploads/'));
    }

    if ($path === '' || str_contains($path, '..')) {
        return null;
    }

    $base = realpath(UPLOAD_BASE_PATH);
    if ($base === false) {
        return null;
    }

    $targetDirectory = realpath(UPLOAD_BASE_PATH . '/' . dirname($path));
    if ($targetDirectory === false) {
        return null;
    }

    $candidate = $targetDirectory . DIRECTORY_SEPARATOR . basename($path);
    $base = rtrim($base, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

    return str_starts_with($candidate, $base) ? $candidate : null;
}

/**
 * Delete an uploaded media file and any generated responsive sidecar variants.
 */
function delete_uploaded_media(?string $path): bool
{
    $target = uploaded_media_filesystem_path($path);
    if ($target === null) {
        return false;
    }

    $deleted = false;
    if (is_file($target)) {
        $deleted = @unlink($target) || $deleted;
    }

    $info = pathinfo($target);
    $stem = ($info['dirname'] ?? '') . DIRECTORY_SEPARATOR . ($info['filename'] ?? '');
    foreach (['jpg', 'jpeg', 'png', 'webp', 'avif', 'gif'] as $format) {
        foreach (glob($stem . '.w*.' . $format) ?: [] as $variant) {
            $safeVariant = uploaded_media_filesystem_path(str_replace(realpath(UPLOAD_BASE_PATH) ?: '', '', $variant));
            if ($safeVariant !== null && is_file($safeVariant)) {
                $deleted = @unlink($safeVariant) || $deleted;
            }
        }
    }

    return $deleted;
}

/**
 * Build a URL for a static image in assets/images/.
 * Falls back to DEFAULT_OG_IMAGE if path is empty.
 */
function static_img_url(?string $filename, string $fallback = ''): string
{
    if (!$filename) {
        return $fallback ?: DEFAULT_OG_IMAGE;
    }
    return base_url('assets/images/' . ltrim($filename, '/'));
}

/**
 * Ensure a directory exists.
 */
function ensure_directory(string $path): void
{
    if (!is_dir($path) && !mkdir($path, 0775, true) && !is_dir($path)) {
        throw new RuntimeException('Could not create directory: ' . $path);
    }
}

/**
 * Secure image upload helper.
 * Returns relative path like "destinations/file-name.webp"
 */
function upload_image(array $file, string $subfolder): ?string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('File upload failed with error code: ' . (string) $file['error']);
    }

    $tmpPath = $file['tmp_name'] ?? '';
    if ($tmpPath === '' || !is_uploaded_file($tmpPath)) {
        throw new RuntimeException('Invalid uploaded file.');
    }

    $allowedMimeTypes = [
        'image/jpeg' => 'jpg',
        'image/avif' => 'avif',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        'image/gif'  => 'gif',
    ];

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = $finfo ? finfo_file($finfo, $tmpPath) : false;
    if ($finfo) {
        finfo_close($finfo);
    }

    if (!$mimeType || !isset($allowedMimeTypes[$mimeType])) {
        throw new RuntimeException('Unsupported image type.');
    }

    $extension = $allowedMimeTypes[$mimeType];
    $safeFolder = trim(preg_replace('/[^a-z0-9\-_]/i', '', $subfolder) ?? '', '/');
    $targetDirectory = UPLOAD_BASE_PATH . '/' . $safeFolder;

    ensure_directory($targetDirectory);

    $filename = date('YmdHis') . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
    $targetPath = $targetDirectory . '/' . $filename;

    if (!move_uploaded_file($tmpPath, $targetPath)) {
        $dirWritable = is_writable($targetDirectory) ? 'yes' : 'no';
        $tmpExists = is_file($tmpPath) ? 'yes' : 'no';
        $tmpReadable = is_readable($tmpPath) ? 'yes' : 'no';
        $uploadTmpDir = (string) ini_get('upload_tmp_dir');
        $openBaseDir = (string) ini_get('open_basedir');

        throw new RuntimeException(
            'Failed to move uploaded file.'
            . ' target_dir_writable=' . $dirWritable
            . '; tmp_exists=' . $tmpExists
            . '; tmp_readable=' . $tmpReadable
            . '; target=' . $targetPath
            . '; upload_tmp_dir=' . $uploadTmpDir
            . '; open_basedir=' . $openBaseDir
        );
    }

    // Optimize the original (strip metadata, optionally downscale) so even the fallback <img> is fast.
    try {
        optimize_uploaded_image_file($targetPath, $extension);
    } catch (Throwable) {
        // never fail admin saves due to optimization issues
    }

    // Optional: generate responsive variants on upload (webp/avif/jpg) to avoid a separate CLI run.
    try {
        $enabled = getenv('IMAGE_VARIANTS_ON_UPLOAD');
        $enabled = $enabled === false ? '1' : $enabled;
        if (filter_var($enabled, FILTER_VALIDATE_BOOLEAN)) {
            generate_image_variants_for_uploaded_file($targetPath, $extension);
        }
    } catch (Throwable) {
        // never fail admin saves due to variant generation issues
    }

    return $safeFolder . '/' . $filename;
}

/**
 * Re-encode and optionally downscale uploaded images to reduce file size.
 * This runs after move_uploaded_file(), so it can safely read/write $absolutePath.
 */
function optimize_uploaded_image_file(string $absolutePath, string $extension): void
{
    $extension = strtolower(trim($extension));
    if ($extension === '' || !is_file($absolutePath)) {
        return;
    }

    if (!extension_loaded('gd')) {
        return;
    }

    // Skip GIF optimization to avoid breaking animations.
    if ($extension === 'gif') {
        return;
    }

    $img = gd_load_image_for_extension($absolutePath, $extension);
    if (!$img) {
        return;
    }

    // Normalize EXIF orientation for JPEGs so resized variants and thumbnails are correct.
    if (($extension === 'jpg' || $extension === 'jpeg') && function_exists('exif_read_data')) {
        $rotated = gd_apply_exif_orientation($img, $absolutePath);
        if ($rotated) {
            imagedestroy($img);
            $img = $rotated;
        }
    }

    $maxWidth = (int) (getenv('IMAGE_UPLOAD_MAX_WIDTH') ?: '2400');
    $maxWidth = max(800, min(6000, $maxWidth));

    $srcW = imagesx($img);
    $srcH = imagesy($img);
    if ($srcW > 0 && $srcH > 0 && $srcW > $maxWidth) {
        $resized = gd_resize_image($img, $maxWidth);
        if ($resized) {
            imagedestroy($img);
            $img = $resized;
        }
    }

    gd_write_image_for_extension($img, $absolutePath, $extension);
    imagedestroy($img);
}

/**
 * Generate responsive sidecar variants for a single freshly uploaded image.
 * Uses the same naming convention as cli/generate_image_variants.php.
 */
function generate_image_variants_for_uploaded_file(string $absolutePath, string $extension): void
{
    $extension = strtolower(trim($extension));
    if ($extension === '' || !is_file($absolutePath)) {
        return;
    }

    if (!extension_loaded('gd')) {
        return;
    }

    require_once __DIR__ . '/responsive_image.php';

    // Do not generate variants for GIF.
    if ($extension === 'gif') {
        return;
    }

    // Skip when file already looks like a variant.
    $basename = pathinfo($absolutePath, PATHINFO_FILENAME);
    if (preg_match('/\\.w\\d+$/', (string) $basename)) {
        return;
    }

    $widths = defined('RESPONSIVE_IMAGE_DEFAULT_WIDTHS') ? RESPONSIVE_IMAGE_DEFAULT_WIDTHS : [400, 800, 1200, 1600];
    $formats = defined('RESPONSIVE_IMAGE_DEFAULT_FORMATS') ? RESPONSIVE_IMAGE_DEFAULT_FORMATS : ['webp', 'avif'];
    $formats = array_values(array_unique(array_merge(['jpg'], $formats)));

    $gdInfo = gd_info();
    $canWebp = !empty($gdInfo['WebP Support']);
    $canAvif = !empty($gdInfo['AVIF Support']) && function_exists('imageavif');

    $img = gd_load_image_for_extension($absolutePath, $extension);
    if (!$img) {
        return;
    }

    if (($extension === 'jpg' || $extension === 'jpeg') && function_exists('exif_read_data')) {
        $rotated = gd_apply_exif_orientation($img, $absolutePath);
        if ($rotated) {
            imagedestroy($img);
            $img = $rotated;
        }
    }

    $origWidth = imagesx($img);
    $dir = dirname($absolutePath);
    $stem = $dir . '/' . $basename;

    foreach ($widths as $targetWidth) {
        $targetWidth = (int) $targetWidth;
        if ($targetWidth <= 0) {
            continue;
        }
        if ($targetWidth >= $origWidth) {
            continue; // no upscaling
        }

        $resized = gd_resize_image($img, $targetWidth);
        if (!$resized) {
            continue;
        }

        foreach ($formats as $format) {
            $format = strtolower(trim((string) $format));
            if ($format === '') {
                continue;
            }
            if ($format === 'webp' && !$canWebp) {
                continue;
            }
            if ($format === 'avif' && !$canAvif) {
                continue;
            }

            $variantPath = sprintf('%s.w%d.%s', $stem, $targetWidth, $format);
            if (is_file($variantPath)) {
                continue;
            }

            gd_write_image_for_extension($resized, $variantPath, $format);
        }

        imagedestroy($resized);
    }

    imagedestroy($img);
}

function gd_load_image_for_extension(string $path, string $extension): ?GdImage
{
    $extension = strtolower(trim($extension));
    return match ($extension) {
        'jpg', 'jpeg' => (function_exists('imagecreatefromjpeg') ? (imagecreatefromjpeg($path) ?: null) : null),
        'png' => (function_exists('imagecreatefrompng') ? (imagecreatefrompng($path) ?: null) : null),
        'webp' => (function_exists('imagecreatefromwebp') ? (imagecreatefromwebp($path) ?: null) : null),
        'avif' => (function_exists('imagecreatefromavif') ? (imagecreatefromavif($path) ?: null) : null),
        default => null,
    };
}

function gd_resize_image(GdImage $src, int $targetWidth): ?GdImage
{
    $srcW = imagesx($src);
    $srcH = imagesy($src);
    if ($srcW <= 0 || $srcH <= 0) {
        return null;
    }

    $targetWidth = max(1, $targetWidth);
    $targetH = (int) round($srcH * ($targetWidth / $srcW));
    $dst = imagecreatetruecolor($targetWidth, $targetH);

    // Preserve transparency.
    imagealphablending($dst, false);
    imagesavealpha($dst, true);
    $transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
    imagefilledrectangle($dst, 0, 0, $targetWidth, $targetH, $transparent);
    imagealphablending($dst, true);

    imagecopyresampled($dst, $src, 0, 0, 0, 0, $targetWidth, $targetH, $srcW, $srcH);
    return $dst;
}

function gd_write_image_for_extension(GdImage $img, string $path, string $extension): bool
{
    $extension = strtolower(trim($extension));
    return match ($extension) {
        'jpg', 'jpeg' => (function_exists('imagejpeg') ? imagejpeg($img, $path, 82) : false),
        'png' => (function_exists('imagepng') ? imagepng($img, $path, 6) : false),
        'webp' => (function_exists('imagewebp') ? imagewebp($img, $path, 80) : false),
        'avif' => (function_exists('imageavif') ? imageavif($img, $path, 60) : false),
        default => false,
    };
}

function gd_apply_exif_orientation(GdImage $img, string $path): ?GdImage
{
    try {
        $exif = @exif_read_data($path);
        $orientation = (int) ($exif['Orientation'] ?? 1);
    } catch (Throwable) {
        return null;
    }

    // Values per EXIF spec. Handle the most common rotations.
    return match ($orientation) {
        3 => (function_exists('imagerotate') ? (imagerotate($img, 180, 0) ?: null) : null),
        6 => (function_exists('imagerotate') ? (imagerotate($img, -90, 0) ?: null) : null),
        8 => (function_exists('imagerotate') ? (imagerotate($img, 90, 0) ?: null) : null),
        default => null,
    };
}

function media_allowed_mime_types(bool $allowVideo = true): array
{
    $types = [
        'image/jpeg' => ['extension' => 'jpg', 'media_type' => 'image'],
        'image/avif' => ['extension' => 'avif', 'media_type' => 'image'],
        'image/png' => ['extension' => 'png', 'media_type' => 'image'],
        'image/webp' => ['extension' => 'webp', 'media_type' => 'image'],
        'image/gif' => ['extension' => 'gif', 'media_type' => 'image'],
    ];

    if ($allowVideo) {
        $types += [
            'video/mp4' => ['extension' => 'mp4', 'media_type' => 'video'],
            'video/webm' => ['extension' => 'webm', 'media_type' => 'video'],
            'video/quicktime' => ['extension' => 'mov', 'media_type' => 'video'],
        ];
    }

    return $types;
}

function save_media_asset_from_upload(array $file, string $subfolder, bool $allowVideo = true, string $sourceType = 'upload', ?string $sourceUrl = null): array
{
    guard_post_size();
    ensure_social_marketing_schema();

    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        throw new RuntimeException('Choose a media file first.');
    }
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('File upload failed with error code: ' . (string) $file['error']);
    }

    $tmpPath = (string) ($file['tmp_name'] ?? '');
    if ($tmpPath === '' || !is_file($tmpPath)) {
        throw new RuntimeException('Invalid media file.');
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = $finfo ? (string) finfo_file($finfo, $tmpPath) : '';
    if ($finfo) {
        finfo_close($finfo);
    }

    $allowed = media_allowed_mime_types($allowVideo);
    if (!isset($allowed[$mimeType])) {
        throw new RuntimeException($allowVideo ? 'Unsupported media type.' : 'Unsupported image type.');
    }

    $safeFolder = trim(preg_replace('/[^a-z0-9\-_]/i', '', $subfolder) ?? '', '/');
    $targetDirectory = UPLOAD_BASE_PATH . '/' . $safeFolder;
    ensure_directory($targetDirectory);

    $extension = $allowed[$mimeType]['extension'];
    $filename = date('YmdHis') . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
    $targetPath = $targetDirectory . '/' . $filename;

    $moved = is_uploaded_file($tmpPath)
        ? move_uploaded_file($tmpPath, $targetPath)
        : rename($tmpPath, $targetPath);

    if (!$moved) {
        throw new RuntimeException('Failed to save media file.');
    }

    if (($allowed[$mimeType]['media_type'] ?? '') === 'image') {
        try {
            optimize_uploaded_image_file($targetPath, $extension);
        } catch (Throwable) {
        }
    }

    $relativePath = $safeFolder . '/' . $filename;
    $adminUser = function_exists('admin_user') ? admin_user() : null;
    $uploadedBy = is_array($adminUser) ? (int) ($adminUser['id'] ?? 0) : 0;

    $columns = ['file_path', 'file_name', 'mime_type', 'file_size', 'uploaded_by'];
    $values = [
        'file_path' => $relativePath,
        'file_name' => (string) ($file['name'] ?? basename($relativePath)),
        'mime_type' => $mimeType,
        'file_size' => max(0, (int) ($file['size'] ?? (is_file($targetPath) ? filesize($targetPath) : 0))),
        'uploaded_by' => $uploadedBy > 0 ? $uploadedBy : null,
    ];

    if (db_column_exists('media_assets', 'source_type')) {
        $columns[] = 'source_type';
        $values['source_type'] = $sourceType;
    }
    if (db_column_exists('media_assets', 'source_url')) {
        $columns[] = 'source_url';
        $values['source_url'] = $sourceUrl;
    }

    execute_query(
        'INSERT INTO media_assets (' . implode(', ', $columns) . ') VALUES (:' . implode(', :', $columns) . ')',
        $values
    );

    return [
        'media_asset_id' => (int) db()->lastInsertId(),
        'media_type' => (string) $allowed[$mimeType]['media_type'],
        'file_path' => $relativePath,
        'mime_type' => $mimeType,
    ];
}

function import_media_asset_from_url(string $url, string $subfolder, bool $allowVideo = true): array
{
    $url = trim($url);
    if (!preg_match('~^https?://~i', $url)) {
        throw new RuntimeException('Enter a valid image or video URL.');
    }

    $maxRedirects = 5;
    $redirectCount = 0;
    $data = false;

    while ($redirectCount <= $maxRedirects) {
        $host = parse_url($url, PHP_URL_HOST);
        if (!$host) {
            throw new RuntimeException('Invalid URL host.');
        }

        $hostClean = trim($host, '[]');
        $ips = [];

        if (filter_var($hostClean, FILTER_VALIDATE_IP)) {
            $ips[] = $hostClean;
        } else {
            $records = @dns_get_record($hostClean, DNS_A | DNS_AAAA);
            if ($records) {
                foreach ($records as $record) {
                    if (isset($record['ip'])) {
                        $ips[] = $record['ip'];
                    } elseif (isset($record['ipv6'])) {
                        $ips[] = $record['ipv6'];
                    }
                }
            }
            if (empty($ips)) {
                $ipv4s = gethostbynamel($hostClean);
                if ($ipv4s) {
                    $ips = array_merge($ips, $ipv4s);
                }
            }
        }

        if (empty($ips)) {
            throw new RuntimeException('Could not resolve host.');
        }

        foreach ($ips as $ip) {
            if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                throw new RuntimeException("Security error: URL resolves to a restricted network address.");
            }
        }

        $context = stream_context_create([
            'http' => [
                'timeout' => 12,
                'follow_location' => 0,
                'user_agent' => 'IslandCultureMediaImporter/1.0',
                'ignore_errors' => true,
            ],
        ]);

        $data = @file_get_contents($url, false, $context);
        if ($data === false) {
            throw new RuntimeException('Could not download media from the URL.');
        }

        $status_line = $http_response_header[0] ?? '';
        preg_match('{HTTP\/\S*\s(\d{3})}', $status_line, $match);
        $status = (int) ($match[1] ?? 0);

        if ($status >= 300 && $status < 400) {
            $location = '';
            foreach ($http_response_header as $header) {
                if (stripos($header, 'Location:') === 0) {
                    $location = trim(substr($header, 9));
                    break;
                }
            }
            if (!$location) {
                throw new RuntimeException('Redirect missing Location header.');
            }

            // Handle relative redirects
            if (parse_url($location, PHP_URL_HOST) === null) {
                $parts = parse_url($url);
                $url = $parts['scheme'] . '://' . $parts['host'] . (isset($parts['port']) ? ':' . $parts['port'] : '') . '/' . ltrim($location, '/');
            } else {
                $url = $location;
            }

            if (!preg_match('~^https?://~i', $url)) {
                throw new RuntimeException('Redirected to invalid protocol.');
            }
            $redirectCount++;
            continue;
        }

        if ($status >= 400) {
            throw new RuntimeException("HTTP error: $status");
        }

        break;
    }

    if ($redirectCount > $maxRedirects) {
        throw new RuntimeException('Too many redirects.');
    }

    if ($data === false || $data === '') {
        throw new RuntimeException('Could not download media from the URL.');
    }
    if (strlen($data) > 30 * 1024 * 1024) {
        throw new RuntimeException('Remote media file is too large. Keep imports under 30 MB.');
    }

    $tmp = tempnam(sys_get_temp_dir(), 'ict_media_');
    if ($tmp === false) {
        throw new RuntimeException('Could not create temporary import file.');
    }
    file_put_contents($tmp, $data);

    $name = basename((string) parse_url($url, PHP_URL_PATH)) ?: 'remote-media';
    return save_media_asset_from_upload([
        'name' => $name,
        'tmp_name' => $tmp,
        'size' => strlen($data),
        'error' => UPLOAD_ERR_OK,
    ], $subfolder, $allowVideo, 'url_import', $url);
}

function admin_resolve_media_field(string $field, string $subfolder, array $current = [], bool $allowVideo = false, ?array &$createdPaths = null): array
{
    $path = trim((string) ($current['path'] ?? ''));
    $assetId = (int) ($current['asset_id'] ?? 0);
    $mediaType = trim((string) ($current['media_type'] ?? ''));

    if (!empty($_FILES[$field]['name'])) {
        $saved = save_media_asset_from_upload($_FILES[$field], $subfolder, $allowVideo);
        if ($createdPaths !== null) { $createdPaths[] = $saved['file_path']; }
        return ['path' => $saved['file_path'], 'asset_id' => $saved['media_asset_id'], 'media_type' => $saved['media_type']];
    }

    $selectedAssetId = (int) post_input($field . '_media_asset_id', 0);
    if ($selectedAssetId > 0) {
        $asset = fetch_one('SELECT id, file_path, mime_type FROM media_assets WHERE id = :id LIMIT 1', ['id' => $selectedAssetId]);
        if ($asset) {
            $mime = strtolower((string) ($asset['mime_type'] ?? ''));
            $type = str_starts_with($mime, 'video/') ? 'video' : 'image';
            if ($type === 'video' && !$allowVideo) {
                throw new RuntimeException('Video is not allowed for this field.');
            }
            return ['path' => (string) $asset['file_path'], 'asset_id' => (int) $asset['id'], 'media_type' => $type];
        }
    }

    $url = trim((string) post_input($field . '_url', ''));
    if ($url !== '') {
        $saved = import_media_asset_from_url($url, $subfolder, $allowVideo);
        if ($createdPaths !== null) { $createdPaths[] = $saved['file_path']; }
        return ['path' => $saved['file_path'], 'asset_id' => $saved['media_asset_id'], 'media_type' => $saved['media_type']];
    }

    return ['path' => $path, 'asset_id' => $assetId > 0 ? $assetId : null, 'media_type' => $mediaType !== '' ? $mediaType : 'image'];
}

/** Identify video paths even on older query projections without media-type columns. */
function media_path_is_video(string $path): bool
{
    $path = (string) parse_url(str_replace('\\', '/', trim($path)), PHP_URL_PATH);
    return in_array(strtolower(pathinfo(rawurldecode($path), PATHINFO_EXTENSION)), ['mp4','webm','mov','m4v','ogv','avi','mkv'], true);
}

/** Select a still for an image-only slot; never change the underlying video assignment. */
function content_image_path(array $record, array $keys = ['hero_image','og_image','cover_image'], string $fallback = ''): string
{
    foreach ($keys as $key) {
        $path = trim((string) ($record[$key] ?? ''));
        if (($key === 'hero_image' && strtolower(trim((string) ($record['hero_media_type'] ?? ''))) === 'video') || media_path_is_video($path)) {
            $path = $key === 'hero_image' ? trim((string) ($record['hero_video_poster'] ?? '')) : '';
        }
        if ($path !== '' && !media_path_is_video($path)) { return $path; }
    }
    return media_path_is_video($fallback) ? '' : $fallback;
}

/** Add optional poster metadata without requiring newer columns on older installs. */
function content_image_select_fragment(string $table, string $alias = '', bool $withCover = false): string
{
    if (!preg_match('/^[a-z_]+$/D', $table) || ($alias !== '' && !preg_match('/^[a-z_]+$/D', $alias))) { throw new InvalidArgumentException('Invalid media query identifier.'); }
    $prefix = $alias !== '' ? $alias . '.' : '';
    return implode(', ', array_map(static fn($field) => (db_column_exists($table, $field) ? $prefix . $field : 'NULL') . ' AS ' . $field, array_merge(['hero_media_type','hero_video_poster'],$withCover?['cover_image','cover_alt']:[])));
}

function content_hero_media(array $record): array
{
    $mediaType = trim((string) ($record['hero_media_type'] ?? ''));
    $path = trim((string) ($record['hero_image'] ?? ''));
    $poster = trim((string) ($record['hero_video_poster'] ?? ''));

    if ($mediaType === '') {
        $mediaType = 'image';
    }
    if ($mediaType !== 'video') {
        $mediaType = 'image';
    }

    return [
        'type' => $mediaType,
        'url' => $path !== '' ? stored_media_url($path) : '',
        'poster_url' => $poster !== '' ? stored_media_url($poster) : ($mediaType === 'video' ? DEFAULT_OG_IMAGE : ''),
    ];
}

function homepage_upload_media_asset(array $file, string $subfolder = 'homepage-hero'): array
{
    guard_post_size();
    ensure_social_marketing_schema();

    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        throw new RuntimeException('Choose a media file first.');
    }

    $tmpPath = (string) ($file['tmp_name'] ?? '');
    if ($tmpPath === '' || !is_uploaded_file($tmpPath)) {
        throw new RuntimeException('Invalid uploaded file.');
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = $finfo ? (string) finfo_file($finfo, $tmpPath) : '';
    if ($finfo) {
        finfo_close($finfo);
    }

    $imageTypes = ['image/jpeg', 'image/avif', 'image/png', 'image/webp', 'image/gif'];
    $videoTypes = [
        'video/mp4' => 'mp4',
        'video/webm' => 'webm',
        'video/quicktime' => 'mov',
    ];

    if (in_array($mimeType, $imageTypes, true)) {
        $filePath = upload_image($file, $subfolder);
        $mediaType = 'image';
    } elseif (isset($videoTypes[$mimeType])) {
        if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Video upload failed with error code: ' . (string) $file['error']);
        }
        $safeFolder = trim(preg_replace('/[^a-z0-9\-_]/i', '', $subfolder) ?? '', '/');
        $targetDirectory = UPLOAD_BASE_PATH . '/' . $safeFolder;
        ensure_directory($targetDirectory);
        $filename = date('YmdHis') . '_' . bin2hex(random_bytes(8)) . '.' . $videoTypes[$mimeType];
        $targetPath = $targetDirectory . '/' . $filename;
        if (!move_uploaded_file($tmpPath, $targetPath)) {
            throw new RuntimeException('Failed to save uploaded video.');
        }
        $filePath = $safeFolder . '/' . $filename;
        $mediaType = 'video';
    } else {
        throw new RuntimeException('Unsupported hero media type. Use JPG, PNG, WebP, AVIF, GIF, MP4, WebM, or MOV.');
    }

    $adminUser = function_exists('admin_user') ? admin_user() : null;
    $uploadedBy = is_array($adminUser) ? (int) ($adminUser['id'] ?? 0) : 0;
    execute_query(
        "INSERT INTO media_assets (file_path, file_name, mime_type, file_size, uploaded_by)
         VALUES (:file_path, :file_name, :mime_type, :file_size, :uploaded_by)",
        [
            'file_path' => (string) $filePath,
            'file_name' => (string) ($file['name'] ?? basename((string) $filePath)),
            'mime_type' => $mimeType,
            'file_size' => max(0, (int) ($file['size'] ?? 0)),
            'uploaded_by' => $uploadedBy > 0 ? $uploadedBy : null,
        ]
    );

    return [
        'media_asset_id' => (int) db()->lastInsertId(),
        'media_type' => $mediaType,
        'file_path' => (string) $filePath,
        'mime_type' => $mimeType,
    ];
}

function daily_rotating_image(array $images, ?int $dayIndex = null): string
{
    $images = array_values(array_filter($images));

    if (!$images) {
        return DEFAULT_OG_IMAGE;
    }

    $dayIndex ??= (int) date('z');

    return $images[$dayIndex % count($images)];
}


require_once __DIR__ . '/package_media.php';
