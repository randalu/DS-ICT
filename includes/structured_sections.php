<?php
declare(strict_types=1);

/**
 * Structured SEO section partials.
 *
 *  - render_best_time_block: month grid + freeform note.
 *  - render_route_notes_block: ordered list of stops with optional driving distance/time.
 *  - render_budget_range_block: price summary using format_price_range().
 *
 * All return strings (do not echo). Empty inputs return an empty string so callers can
 * just `echo render_*()` unconditionally.
 */

const STRUCTURED_MONTHS = [
    'january'   => 'Jan',
    'february'  => 'Feb',
    'march'     => 'Mar',
    'april'     => 'Apr',
    'may'       => 'May',
    'june'      => 'Jun',
    'july'      => 'Jul',
    'august'    => 'Aug',
    'september' => 'Sep',
    'october'   => 'Oct',
    'november'  => 'Nov',
    'december'  => 'Dec',
];

/**
 * @param string|array|null $bestTimeJson  Decoded array or raw JSON string.
 *      Expected shape: { months: ['april','may'], note: 'optional prose' }
 *      OR a flat array of month names for back-compat.
 */
function render_best_time_block($bestTimeJson, array $opts = []): string
{
    $data = is_array($bestTimeJson) ? $bestTimeJson : (json_decode((string) $bestTimeJson, true) ?: []);

    $months = [];
    $note = '';
    if (isset($data['months']) && is_array($data['months'])) {
        $months = $data['months'];
        $note = trim((string) ($data['note'] ?? ''));
    } elseif (array_is_list($data)) {
        $months = $data;
    }

    $months = array_map(static fn($m) => strtolower(trim((string) $m)), $months);
    $active = array_intersect_key(STRUCTURED_MONTHS, array_flip($months));
    if (!$active && $note === '') {
        return '';
    }

    $title = (string) ($opts['title'] ?? 'Best Time to Visit');
    $headingId = trim((string) ($opts['heading_id'] ?? ''));

    $cells = '';
    foreach (STRUCTURED_MONTHS as $key => $label) {
        $isActive = isset($active[$key]);
        $cells .= sprintf(
            '<div class="month-cell %s" title="%s">%s</div>',
            $isActive ? 'is-active' : 'is-inactive',
            e(ucfirst($key)),
            e($label)
        );
    }

    $noteHtml = $note !== '' ? '<p class="best-time-note small text-secondary mb-0">' . e($note) . '</p>' : '';

    $idAttr = $headingId !== '' ? ' id="' . e($headingId) . '"' : '';

    return '<section class="structured-best-time"' . $idAttr . '>'
        . '<div class="detail-block-soft">'
        . '<h2>' . e($title) . '</h2>'
        . '<div class="month-grid" role="list">' . $cells . '</div>'
        . $noteHtml
        . '</div>'
        . '</section>';
}

/**
 * @param string|array|null $routeNotesJson Decoded array or raw JSON.
 *      Expected: [ {stop: 'Negombo', distance: '40 min from CMB', note: 'overnight'}, ... ]
 */
function render_route_notes_block($routeNotesJson, array $opts = []): string
{
    $data = is_array($routeNotesJson) ? $routeNotesJson : (json_decode((string) $routeNotesJson, true) ?: []);
    if (!$data) {
        return '';
    }

    $title = (string) ($opts['title'] ?? 'Route & Driving Notes');
    $headingId = trim((string) ($opts['heading_id'] ?? ''));

    $items = '';
    foreach ($data as $i => $stop) {
        if (!is_array($stop)) {
            continue;
        }
        $name = trim((string) ($stop['stop'] ?? $stop['name'] ?? ''));
        if ($name === '') {
            continue;
        }
        $distance = trim((string) ($stop['distance'] ?? ''));
        $note = trim((string) ($stop['note'] ?? ''));

        $items .= '<li class="route-stop">'
            . '<span class="route-stop-num">' . ($i + 1) . '</span>'
            . '<div class="route-stop-body">'
            . '<div class="route-stop-name">' . e($name) . '</div>'
            . ($distance !== '' ? '<div class="route-stop-distance small text-secondary">' . e($distance) . '</div>' : '')
            . ($note !== '' ? '<div class="route-stop-note small">' . e($note) . '</div>' : '')
            . '</div>'
            . '</li>';
    }
    if ($items === '') {
        return '';
    }

    $idAttr = $headingId !== '' ? ' id="' . e($headingId) . '"' : '';
    return '<section class="structured-route-notes"' . $idAttr . '>'
        . '<h2>' . e($title) . '</h2>'
        . '<ol class="route-stop-list">' . $items . '</ol>'
        . '</section>';
}

/**
 * Render a price range badge/block.
 * Uses format_price_range() from functions.php for the actual formatting.
 */
function render_budget_range_block(array $record, array $opts = []): string
{
    $price = format_price_range($record, true);
    if ($price === '') {
        return '';
    }

    $title = (string) ($opts['title'] ?? 'Indicative Price');
    $note = trim((string) ($opts['note'] ?? 'Per person, twin-share, excluding international flights.'));
    $headingId = trim((string) ($opts['heading_id'] ?? ''));

    $idAttr = $headingId !== '' ? ' id="' . e($headingId) . '"' : '';

    return '<section class="structured-budget"' . $idAttr . '>'
        . '<div class="detail-block-soft">'
        . '<h2>' . e($title) . '</h2>'
        . '<div class="price-range-display">' . e($price) . '</div>'
        . ($note !== '' ? '<p class="small text-secondary mb-0">' . e($note) . '</p>' : '')
        . '</div>'
        . '</section>';
}

/**
 * Decode best_time_json safely into the {months, note} shape used by the renderer
 * and by schema_place().
 */
function decode_best_time_json(?string $json): array
{
    if (!$json) {
        return ['months' => [], 'note' => ''];
    }
    $data = json_decode($json, true);
    if (!is_array($data)) {
        return ['months' => [], 'note' => ''];
    }
    if (isset($data['months']) && is_array($data['months'])) {
        return [
            'months' => array_values(array_filter(array_map(
                static fn($m) => strtolower(trim((string) $m)),
                $data['months']
            ))),
            'note'   => trim((string) ($data['note'] ?? '')),
        ];
    }
    if (array_is_list($data)) {
        return [
            'months' => array_values(array_filter(array_map(
                static fn($m) => strtolower(trim((string) $m)),
                $data
            ))),
            'note' => '',
        ];
    }
    return ['months' => [], 'note' => ''];
}
