<?php
declare(strict_types=1);

namespace NewSite\View;

use function NewSite\e;
use function NewSite\icon;
use function NewSite\media;
use function NewSite\url;

function image(string $path, string $alt, string $class = '', string $sizes = '100vw', bool $eager = false): void
{
    $root = dirname(__DIR__, 2);
    $path = ltrim(str_replace('\\', '/', $path), '/');
    $file = realpath($root . '/' . $path);
    $allowed = realpath($root . '/assets/images');
    if (media($path) === '' || $file === false || $allowed === false || !str_starts_with(str_replace('\\', '/', $file), str_replace('\\', '/', $allowed) . '/')) {
        echo '<span class="media-unavailable ' . e($class) . '" role="img" aria-label="' . e($alt) . '"><span>Image unavailable in this preview</span></span>';
        return;
    }
    $dimensions = @getimagesize($file);
    if ($dimensions === false) {
        echo '<span class="media-unavailable ' . e($class) . '">Image unavailable in this preview</span>';
        return;
    }
    $stem = preg_replace('/(?:\.w\d+)?\.(?:jpe?g|png|webp|avif)$/i', '', $path);
    echo '<picture class="' . e($class) . '">';
    foreach (['avif', 'webp'] as $format) {
        $sources = [];
        foreach ([400, 800, 1200] as $width) {
            $candidate = $stem . '.w' . $width . '.' . $format;
            if (media($candidate) !== '' && is_file($root . '/' . $candidate)) {
                $sources[] = media($candidate) . ' ' . $width . 'w';
            }
        }
        if ($sources !== []) {
            echo '<source type="image/' . $format . '" srcset="' . e(implode(', ', $sources)) . '" sizes="' . e($sizes) . '">';
        }
    }
    echo '<img src="' . e(media($path)) . '" alt="' . e($alt) . '" width="' . (int) $dimensions[0] . '" height="' . (int) $dimensions[1] . '" loading="' . ($eager ? 'eager' : 'lazy') . '" decoding="async"' . ($eager ? ' fetchpriority="high"' : '') . '>';
    echo '</picture>';
}

function card(array $card, string $kind = 'journey'): void
{
    $duration = $card['duration_days'] ?? (is_scalar($card['days'] ?? null) ? $card['days'] : null);
    ?>
    <article class="travel-card travel-card--<?= e($kind) ?>">
        <a class="travel-card__link" href="<?= e(url((string) $card['url'])) ?>">
            <div class="travel-card__image">
                <?php image((string) $card['image'], (string) $card['image_alt'], '', '(max-width: 640px) 92vw, (max-width: 1024px) 46vw, 30vw'); ?>
                <span class="image-action" aria-hidden="true"><?= icon('arrow-up-right') ?></span>
            </div>
            <div class="travel-card__body">
                <p class="eyebrow"><?= $kind === 'experience' ? 'A different perspective' : 'Journey inspiration' ?></p>
                <h3><?= e((string) $card['title']) ?></h3>
                <p class="travel-card__summary"><?= e((string) $card['summary']) ?></p>
                <?php if (!empty($duration)): ?><p class="travel-card__facts"><?= e((string) $duration) ?> days<?= !empty($card['demo']) ? ' (illustrative)' : '' ?><?php if (!empty($card['price']) && is_scalar($card['price'])): ?> <span aria-hidden="true">/</span> <?= e((string) $card['price']) ?><?php endif; ?></p><?php endif; ?>
                <?php if (!empty($card['label'])): ?><p class="content-label"><?= e((string) $card['label']) ?></p><?php endif; ?>
            </div>
        </a>
    </article>
    <?php
}
