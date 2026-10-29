<?php
declare(strict_types=1);

namespace ICT\Support;

/**
 * Turns stored media paths into usable URLs, alt text and image types.
 *
 * Storage is inconsistent by design in the legacy data: values may be a bare
 * uploads-relative path, an assets/ path, or an absolute URL. Some records
 * point at an .mp4 hero. Every template goes through here so those cases are
 * handled once instead of per view.
 */
final class MediaResolver
{
    private const VIDEO_EXTENSIONS = ['mp4', 'webm', 'ogv', 'mov', 'm4v'];

    private const ALT_COLUMNS = ['hero_alt', 'cover_alt', 'og_alt', 'image_alt', 'alt'];

    /** Resolve any stored media value to a browser URL. */
    public static function url(?string $path): string
    {
        $value = trim((string) $path);

        if ($value === '') {
            return DEFAULT_OG_IMAGE;
        }

        if (preg_match('~^https?://~i', $value) === 1) {
            return $value;
        }

        if (str_starts_with($value, 'assets/')) {
            return base_url($value);
        }

        return stored_media_url($value);
    }

    public static function isVideo(?string $path): bool
    {
        $extension = strtolower(pathinfo(trim((string) $path), PATHINFO_EXTENSION));

        return in_array($extension, self::VIDEO_EXTENSIONS, true);
    }

    /**
     * Pick the best alt text a record already carries, falling back to the
     * descriptive default the caller supplies. Never returns an empty string,
     * so published cards cannot ship an unlabelled image.
     */
    public static function alt(array $row, string $fallback): string
    {
        foreach (self::ALT_COLUMNS as $column) {
            $value = trim((string) ($row[$column] ?? ''));
            if ($value !== '') {
                return $value;
            }
        }

        $fallback = trim($fallback);

        return $fallback !== '' ? $fallback : APP_NAME;
    }

    /**
     * Build the image payload a card or hero needs.
     *
     * @param array<string,mixed> $row
     * @param list<string>        $pathKeys preference order for the image column
     * @return array{url:string,alt:string,is_video:bool,poster:string}
     */
    public static function forRow(array $row, string $fallbackAlt, array $pathKeys = ['hero_image', 'cover_image', 'og_image']): array
    {
        $path = '';
        foreach ($pathKeys as $key) {
            $candidate = trim((string) ($row[$key] ?? ''));
            if ($candidate !== '') {
                $path = $candidate;
                break;
            }
        }

        $isVideo = self::isVideo($path);
        $poster = $isVideo
            ? self::url((string) ($row['hero_video_poster'] ?? $row['cover_image'] ?? ''))
            : self::url($path);

        return [
            'url' => $isVideo ? $poster : self::url($path),
            'alt' => self::alt($row, $fallbackAlt),
            'is_video' => $isVideo,
            'poster' => $poster,
        ];
    }

    /** True when a resolved URL is the generic placeholder artwork. */
    public static function isPlaceholder(string $url): bool
    {
        if ($url === '') {
            return true;
        }

        if (function_exists('is_placeholder_media_url') && is_placeholder_media_url($url)) {
            return true;
        }

        return str_contains($url, '/placeholders/');
    }
}
