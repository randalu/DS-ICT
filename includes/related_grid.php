<?php
declare(strict_types=1);

require_once __DIR__ . '/responsive_image.php';

/**
 * Render a Bootstrap 5 grid of related-content cards.
 *
 * Supported $type values: 'itineraries', 'destinations', 'tours', 'experiences', 'partners', 'posts'.
 *
 * Each item array is the row from the corresponding fetch_related_*() helper.
 *
 * Options:
 *  - heading        (string|null) optional <h2> rendered above the grid
 *  - heading_id     (string)      id for the heading (used by TOC)
 *  - section_class  (string)      class for wrapping <section> (default '')
 *  - cols           (int)         max columns at lg (1-4, default 3)
 *  - empty_message  (string)      fallback text when $items is empty (default '': renders nothing)
 *  - card_class     (string)      extra class on each card
 *  - link_target    (string)      '_self' (default) | '_blank'
 *  - image_widths   (int[])       widths for responsive_image() (default 400/800)
 *  - image_sizes    (string)      sizes attr (default '(min-width:992px) 360px, 100vw')
 */
function render_related_grid(string $type, array $items, array $opts = []): string
{
    $heading = $opts['heading'] ?? null;
    $headingId = trim((string) ($opts['heading_id'] ?? ''));
    $sectionClass = trim((string) ($opts['section_class'] ?? ''));
    $cols = max(1, min(4, (int) ($opts['cols'] ?? 3)));
    $cardClass = trim((string) ($opts['card_class'] ?? ''));
    $linkTarget = (string) ($opts['link_target'] ?? '_self');
    $imageWidths = $opts['image_widths'] ?? [400, 800];
    $imageSizes = (string) ($opts['image_sizes'] ?? '(min-width:992px) 360px, 100vw');
    $emptyMessage = (string) ($opts['empty_message'] ?? '');

    if (!$items) {
        if ($emptyMessage === '') {
            return '';
        }
        $body = '<p class="text-secondary mb-0">' . e($emptyMessage) . '</p>';
        return related_grid_section($heading, $headingId, $sectionClass, $body);
    }

    $colClass = match ($cols) {
        1 => 'row-cols-1',
        2 => 'row-cols-1 row-cols-md-2',
        3 => 'row-cols-1 row-cols-md-2 row-cols-lg-3',
        4 => 'row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4',
        default => 'row-cols-1 row-cols-md-2 row-cols-lg-3',
    };

    $cardsHtml = '';
    foreach ($items as $item) {
        $cardsHtml .= '<div class="col">'
            . related_grid_card($type, $item, $cardClass, $linkTarget, $imageWidths, $imageSizes)
            . '</div>';
    }

    $body = '<div class="row ' . $colClass . ' g-4">' . $cardsHtml . '</div>';

    return related_grid_section($heading, $headingId, $sectionClass, $body);
}

function related_grid_section(?string $heading, string $headingId, string $sectionClass, string $body): string
{
    $sectionAttr = '';
    if ($headingId !== '') {
        $sectionAttr = ' id="' . e($headingId) . '"';
    }
    if ($sectionClass !== '') {
        $sectionAttr .= ' class="' . e($sectionClass) . '"';
    }

    $headingHtml = '';
    if ($heading !== null && $heading !== '') {
        $headingHtml = '<h2 class="related-grid-heading mb-3">' . e($heading) . '</h2>';
    }

    return '<section' . $sectionAttr . '>' . $headingHtml . $body . '</section>';
}

function related_grid_card(string $type, array $item, string $cardClass, string $linkTarget, array $imageWidths, string $imageSizes): string
{
    $href = related_grid_href($type, $item);
    $title = related_grid_title($type, $item);
    $excerpt = trim((string) ($item['excerpt'] ?? ''));
    $item = in_array($type, package_tables(), true) ? package_card_record($item, $type) : $item;
    $imgSrc = related_grid_image($item);
    $meta = related_grid_meta($type, $item);

    $imgHtml = '';
    if ($imgSrc !== '') {
        $imgHtml = responsive_image([
            'src'     => $imgSrc,
            'alt'     => in_array($type, package_tables(), true) ? package_card_alt($item, $type) : $title,
            'sizes'   => $imageSizes,
            'widths'  => $imageWidths,
            'width'   => 800,
            'height'  => 533,
            'class'   => 'card-img-top related-card-img',
            'loading' => 'lazy',
        ]);
    }

    $excerptHtml = '';
    if ($excerpt !== '') {
        $excerptShort = mb_substr($excerpt, 0, 140);
        if (mb_strlen($excerpt) > 140) {
            $excerptShort = rtrim($excerptShort) . '…';
        }
        $excerptHtml = '<p class="card-text small text-secondary mb-0">' . e($excerptShort) . '</p>';
    }

    $metaHtml = $meta !== '' ? '<div class="related-card-meta small text-secondary mb-2">' . $meta . '</div>' : '';

    $linkAttr = ' href="' . e($href) . '"';
    if ($linkTarget !== '_self') {
        $linkAttr .= ' target="' . e($linkTarget) . '" rel="noopener"';
    }

    return '<a' . $linkAttr . ' class="card h-100 related-card text-decoration-none ' . e($cardClass) . '">'
        . $imgHtml
        . '<div class="card-body d-flex flex-column">'
        . $metaHtml
        . '<h3 class="h6 card-title mb-2">' . e($title) . '</h3>'
        . $excerptHtml
        . '</div>'
        . '</a>';
}

function related_grid_href(string $type, array $item): string
{
    $slug = (string) ($item['slug'] ?? '');
    return match ($type) {
        'itineraries'  => base_url('itineraries/' . $slug),
        'destinations' => base_url('destinations/' . $slug),
        'tours'        => base_url('tours/' . $slug),
        'experiences'  => base_url('experiences/' . $slug),
        'partners'     => business_partner_url($item),
        'posts'        => base_url('blog/' . $slug),
        default        => base_url($slug),
    };
}

function related_grid_title(string $type, array $item): string
{
    return (string) ($item['title'] ?? $item['name'] ?? $item['business_name'] ?? $item['country_name'] ?? '');
}

function related_grid_image(array $item): string
{
    return content_image_path($item, ['cover_image','hero_image','og_image']);
}

function related_grid_meta(string $type, array $item): string
{
    $parts = [];
    if ($type === 'itineraries') {
        $days = (int) ($item['days'] ?? 0);
        if ($days > 0) {
            $parts[] = $days . ' days';
        }
        $price = format_price_range($item, true);
        if ($price !== '') {
            $parts[] = $price;
        }
    }
    if ($type === 'posts' && !empty($item['published_at'])) {
        $ts = strtotime((string) $item['published_at']);
        if ($ts) {
            $parts[] = date('d M Y', $ts);
        }
    }
    if ($type === 'partners') {
        $typeLabel = business_partner_types()[(string) ($item['business_type'] ?? '')] ?? '';
        $location = trim((string) ($item['location'] ?? $item['destination_name'] ?? ''));
        if ($typeLabel !== '') {
            $parts[] = $typeLabel;
        }
        if ($location !== '') {
            $parts[] = $location;
        }
    }
    return implode(' &middot; ', array_map('e', $parts));
}
