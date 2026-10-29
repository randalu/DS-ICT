<?php
declare(strict_types=1);

/**
 * Shared content card.
 *
 * One markup contract for destinations, journeys, experiences, day tours and
 * articles: media, kicker, title, excerpt, fact chips and an optional price.
 * Every element sits inside the link as inline content, so the whole card is a
 * single tab stop and stays valid HTML.
 *
 * @var array{title:string,url:string,image:string,alt:string,excerpt:string,meta:list<string>,price:string,kicker:string} $card
 * @var string $variant
 * @var bool $eager
 */

$variant = $variant ?? 'default';
$eager = $eager ?? false;
$title = (string) ($card['title'] ?? '');
$meta = array_values(array_filter(array_map('strval', $card['meta'] ?? [])));
?>
<article class="ic-card ic-card--<?= e($variant) ?>">
    <a class="ic-card__link" href="<?= e((string) ($card['url'] ?? '#')) ?>">
        <span class="ic-card__media">
            <img
                src="<?= e((string) ($card['image'] ?? DEFAULT_OG_IMAGE)) ?>"
                alt="<?= e((string) ($card['alt'] ?? $title)) ?>"
                loading="<?= $eager ? 'eager' : 'lazy' ?>"
                decoding="async"
                <?= $eager ? 'fetchpriority="high"' : '' ?>
            >
        </span>
        <span class="ic-card__body">
            <?php if (($card['kicker'] ?? '') !== ''): ?>
                <span class="ic-eyebrow"><?= e((string) $card['kicker']) ?></span>
            <?php endif; ?>
            <span class="ic-card__title"><?= e($title) ?></span>
            <?php if (($card['excerpt'] ?? '') !== ''): ?>
                <span class="ic-card__excerpt"><?= e((string) $card['excerpt']) ?></span>
            <?php endif; ?>
            <?php if ($meta !== [] || ($card['price'] ?? '') !== ''): ?>
                <span class="ic-card__facts">
                    <?php foreach ($meta as $fact): ?>
                        <span class="ic-chip"><?= e($fact) ?></span>
                    <?php endforeach; ?>
                    <?php if (($card['price'] ?? '') !== ''): ?>
                        <span class="ic-card__price">From <?= e((string) $card['price']) ?></span>
                    <?php endif; ?>
                </span>
            <?php endif; ?>
            <span class="ic-card__cta" aria-hidden="true">View <span class="ic-card__arrow">&rarr;</span></span>
        </span>
    </a>
</article>
