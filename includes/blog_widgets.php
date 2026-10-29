<?php
declare(strict_types=1);

require_once __DIR__ . '/related_fetch.php';
require_once __DIR__ . '/related_grid.php';

/**
 * Blog widget v2 — resolution, validation, rendering, helpers.
 *
 * Storage shape (v2 stored in blog_posts.sidebar_widgets_json AND in
 * blog_widget_defaults.config_json):
 *
 *   {
 *     "version": 2,
 *     "widgets": [
 *       {
 *         "id": "abc123",                          // stable id (uuid-like)
 *         "type": "related_posts" | "related_itineraries"
 *               | "related_destinations" | "ad" | "cta" | "newsletter",
 *         "position": "sidebar" | "inline_after_p" | "below_post",
 *         "order": 0,                              // sort within position
 *         "visibility": {
 *           "mode": "all" | "category_in" | "post_in",
 *           "ids":  [int, ...]
 *         },
 *         "settings": { ...type-specific... }
 *       }
 *     ]
 *   }
 *
 * Type-specific `settings`:
 *   related_posts:        { count, manual_ids: [int] }
 *   related_itineraries:  { count, manual_ids: [int] }
 *   related_destinations: { count, manual_ids: [int] }
 *   ad:                   { title, html, devices: ['desktop','mobile'], starts_at, ends_at }
 *   cta:                  { title, text, button_label, button_url, whatsapp }
 *   newsletter:           { title, intro, button_label }
 *
 * For inline_after_p: settings.after_paragraph (1-based) selects which <p> to splice after.
 */

const BLOG_WIDGET_TYPES = ['related_posts', 'related_itineraries', 'related_destinations', 'ad', 'cta', 'newsletter'];
const BLOG_WIDGET_POSITIONS = ['sidebar', 'inline_after_p', 'below_post'];
const BLOG_WIDGET_VISIBILITY_MODES = ['all', 'category_in', 'post_in'];
const BLOG_WIDGET_AD_DEVICES = ['desktop', 'mobile'];

/**
 * Coarse mobile UA sniff. Heuristic — used only for ad device targeting,
 * never for security or canonical rendering decisions.
 */
function is_mobile_ua(?string $ua = null): bool
{
    $ua = $ua ?? (string) ($_SERVER['HTTP_USER_AGENT'] ?? '');
    if ($ua === '') {
        return false;
    }
    return (bool) preg_match('~(Android|iPhone|iPod|IEMobile|BlackBerry|Mobile|Opera Mini)~i', $ua);
}

/**
 * Validate + normalise a widget config array. Returns the normalised array.
 * Always returns v2 shape regardless of input (uses upcast for v1).
 */
function validate_widget_config($input): array
{
    $config = is_array($input) ? $input : (json_decode((string) $input, true) ?: []);

    // v1 detection: legacy flat shape with show_related_posts etc.
    if (!isset($config['version']) && (isset($config['show_related_posts']) || isset($config['show_ad']))) {
        return upcast_widget_config_v1_to_v2($config);
    }

    if (($config['version'] ?? 0) !== 2 || !isset($config['widgets']) || !is_array($config['widgets'])) {
        return ['version' => 2, 'widgets' => []];
    }

    $widgets = [];
    $i = 0;
    foreach ($config['widgets'] as $w) {
        if (!is_array($w)) continue;

        $type = (string) ($w['type'] ?? '');
        if (!in_array($type, BLOG_WIDGET_TYPES, true)) continue;

        $position = (string) ($w['position'] ?? 'sidebar');
        if (!in_array($position, BLOG_WIDGET_POSITIONS, true)) {
            $position = 'sidebar';
        }

        $vis = is_array($w['visibility'] ?? null) ? $w['visibility'] : ['mode' => 'all', 'ids' => []];
        $visMode = in_array($vis['mode'] ?? 'all', BLOG_WIDGET_VISIBILITY_MODES, true) ? $vis['mode'] : 'all';
        $visIds = related_unique_positive_ids($vis['ids'] ?? []);

        $widgets[] = [
            'id'         => (string) ($w['id'] ?? widget_new_id()),
            'type'       => $type,
            'position'   => $position,
            'order'      => isset($w['order']) ? (int) $w['order'] : $i,
            'visibility' => ['mode' => $visMode, 'ids' => $visIds],
            'settings'   => widget_normalize_settings($type, (array) ($w['settings'] ?? [])),
        ];
        $i++;
    }

    // Sort by position priority, then `order`.
    $positionOrder = array_flip(BLOG_WIDGET_POSITIONS);
    usort($widgets, static function ($a, $b) use ($positionOrder) {
        $pa = $positionOrder[$a['position']] ?? 99;
        $pb = $positionOrder[$b['position']] ?? 99;
        if ($pa !== $pb) return $pa <=> $pb;
        return ($a['order'] ?? 0) <=> ($b['order'] ?? 0);
    });

    return ['version' => 2, 'widgets' => $widgets];
}

function widget_new_id(): string
{
    try {
        return bin2hex(random_bytes(6));
    } catch (Throwable) {
        return uniqid('w', true);
    }
}

function widget_normalize_settings(string $type, array $settings): array
{
    switch ($type) {
        case 'related_posts':
        case 'related_itineraries':
        case 'related_destinations':
            return [
                'count'      => max(1, min(10, (int) ($settings['count'] ?? 4))),
                'manual_ids' => array_slice(related_unique_positive_ids($settings['manual_ids'] ?? []), 0, 20),
            ];
        case 'ad': {
            $devices = $settings['devices'] ?? BLOG_WIDGET_AD_DEVICES;
            if (!is_array($devices)) {
                $devices = BLOG_WIDGET_AD_DEVICES;
            }
            $devices = array_values(array_intersect(BLOG_WIDGET_AD_DEVICES, $devices));
            if (!$devices) {
                $devices = BLOG_WIDGET_AD_DEVICES;
            }
            return [
                'title'     => substr(trim((string) ($settings['title'] ?? '')), 0, 120),
                'html'      => widget_sanitize_ad_html((string) ($settings['html'] ?? '')),
                'devices'   => $devices,
                'starts_at' => widget_normalize_datetime((string) ($settings['starts_at'] ?? '')),
                'ends_at'   => widget_normalize_datetime((string) ($settings['ends_at'] ?? '')),
                'after_paragraph' => max(1, (int) ($settings['after_paragraph'] ?? 2)),
            ];
        }
        case 'cta':
            return [
                'title'        => substr(trim((string) ($settings['title'] ?? 'Plan Your Trip')), 0, 160),
                'text'         => substr(trim((string) ($settings['text'] ?? '')), 0, 500),
                'button_label' => substr(trim((string) ($settings['button_label'] ?? 'Get a Quote')), 0, 60),
                'button_url'   => substr(trim((string) ($settings['button_url'] ?? base_url('contact'))), 0, 300),
                'whatsapp'     => !empty($settings['whatsapp']) ? 1 : 0,
                'after_paragraph' => max(1, (int) ($settings['after_paragraph'] ?? 2)),
            ];
        case 'newsletter':
            return [
                'title'        => substr(trim((string) ($settings['title'] ?? 'Stay in the loop')), 0, 160),
                'intro'        => substr(trim((string) ($settings['intro'] ?? 'Monthly Sri Lanka travel tips. No spam.')), 0, 320),
                'button_label' => substr(trim((string) ($settings['button_label'] ?? 'Subscribe')), 0, 40),
                'after_paragraph' => max(1, (int) ($settings['after_paragraph'] ?? 2)),
            ];
    }
    return [];
}

/**
 * Sanitise admin-supplied ad HTML. Allows a small list of inline tags + <a>/<img>/<iframe>.
 * Strips id="" / on*="" handlers and script tags.
 */
function widget_sanitize_ad_html(string $html): string
{
    if ($html === '') return '';
    $allowed = '<p><br><strong><em><b><i><span><a><img><iframe><div><ul><ol><li><h2><h3><h4>';
    $clean = strip_tags($html, $allowed);
    // Drop on*="" handlers via DOM; safer than regex for attribute removal.
    $dom = new DOMDocument('1.0', 'UTF-8');
    @$dom->loadHTML('<?xml encoding="UTF-8"><div>' . $clean . '</div>',
        LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOERROR | LIBXML_NOWARNING);
    $xpath = new DOMXPath($dom);
    foreach ($xpath->query('//*') as $node) {
        /** @var DOMElement $node */
        $remove = [];
        foreach ($node->attributes as $attr) {
            $name = strtolower($attr->nodeName);
            if (str_starts_with($name, 'on') || $name === 'id') {
                $remove[] = $attr->nodeName;
            }
            if ($name === 'href' || $name === 'src') {
                $val = $attr->nodeValue;
                if (preg_match('~^(javascript|data):~i', (string) $val)) {
                    $remove[] = $attr->nodeName;
                }
            }
        }
        foreach ($remove as $a) {
            $node->removeAttribute($a);
        }
    }
    $out = '';
    $root = $dom->getElementsByTagName('div')->item(0);
    if ($root) {
        foreach ($root->childNodes as $child) {
            $out .= $dom->saveHTML($child);
        }
    }
    return $out;
}

function widget_normalize_datetime(string $dt): ?string
{
    $dt = trim($dt);
    if ($dt === '') return null;
    // Accept "YYYY-MM-DD" or "YYYY-MM-DD HH:MM" or "YYYY-MM-DDTHH:MM".
    $dt = str_replace('T', ' ', $dt);
    $ts = strtotime($dt);
    return $ts ? date('Y-m-d H:i:s', $ts) : null;
}

/**
 * Upcast a v1 settings object to a v2 widget list.
 * Reproduces today's behaviour (sidebar related_posts/itineraries/destinations + sidebar ad).
 */
function upcast_widget_config_v1_to_v2(array $v1): array
{
    $widgets = [];
    $order = 0;

    if (!empty($v1['show_related_posts'])) {
        $widgets[] = [
            'id' => widget_new_id(),
            'type' => 'related_posts',
            'position' => 'sidebar',
            'order' => $order++,
            'visibility' => ['mode' => 'all', 'ids' => []],
            'settings' => [
                'count' => (int) ($v1['related_posts_count'] ?? 4),
                'manual_ids' => (array) ($v1['related_post_ids'] ?? []),
            ],
        ];
    }
    if (!empty($v1['show_related_itineraries'])) {
        $widgets[] = [
            'id' => widget_new_id(),
            'type' => 'related_itineraries',
            'position' => 'sidebar',
            'order' => $order++,
            'visibility' => ['mode' => 'all', 'ids' => []],
            'settings' => [
                'count' => (int) ($v1['related_itineraries_count'] ?? 3),
                'manual_ids' => (array) ($v1['related_itinerary_ids'] ?? []),
            ],
        ];
    }
    if (!empty($v1['show_related_destinations'])) {
        $widgets[] = [
            'id' => widget_new_id(),
            'type' => 'related_destinations',
            'position' => 'sidebar',
            'order' => $order++,
            'visibility' => ['mode' => 'all', 'ids' => []],
            'settings' => [
                'count' => (int) ($v1['related_destinations_count'] ?? 3),
                'manual_ids' => (array) ($v1['related_destination_ids'] ?? []),
            ],
        ];
    }
    if (!empty($v1['show_ad']) && trim((string) ($v1['ad_html'] ?? '')) !== '') {
        $widgets[] = [
            'id' => widget_new_id(),
            'type' => 'ad',
            'position' => 'sidebar',
            'order' => $order++,
            'visibility' => ['mode' => 'all', 'ids' => []],
            'settings' => [
                'title' => (string) ($v1['ad_title'] ?? ''),
                'html'  => widget_sanitize_ad_html((string) ($v1['ad_html'] ?? '')),
                'devices' => BLOG_WIDGET_AD_DEVICES,
                'starts_at' => null,
                'ends_at' => null,
            ],
        ];
    }

    return ['version' => 2, 'widgets' => $widgets];
}

/**
 * Resolve the widget list for a given post:
 *   - Loads global defaults (blog_widget_defaults id=1).
 *   - Merges in per-post widgets (per-post wins by widget id).
 *   - Filters by visibility rule.
 *   - Filters ad widgets by device + schedule.
 *   - Returns widgets grouped by position: ['sidebar'=>[], 'inline_after_p'=>[], 'below_post'=>[]].
 */
function widget_resolve_for_post(array $post): array
{
    $globals = widget_load_globals();
    $perPost = validate_widget_config($post['sidebar_widgets_json'] ?? '');

    $merged = [];
    foreach ($globals['widgets'] as $w) {
        $merged[$w['id']] = $w;
    }
    foreach ($perPost['widgets'] as $w) {
        $merged[$w['id']] = $w;
    }

    $postId = (int) ($post['id'] ?? 0);
    $catId = (int) ($post['category_id'] ?? 0);
    $isMobile = is_mobile_ua();
    $now = date('Y-m-d H:i:s');

    $byPosition = ['sidebar' => [], 'inline_after_p' => [], 'below_post' => []];
    foreach ($merged as $w) {
        // Visibility
        $mode = $w['visibility']['mode'];
        $ids = $w['visibility']['ids'];
        if ($mode === 'category_in' && $ids && !in_array($catId, $ids, true)) continue;
        if ($mode === 'post_in' && $ids && !in_array($postId, $ids, true)) continue;

        // Ad-specific filters
        if ($w['type'] === 'ad') {
            $devices = $w['settings']['devices'] ?? BLOG_WIDGET_AD_DEVICES;
            $deviceMatch = $isMobile
                ? in_array('mobile', $devices, true)
                : in_array('desktop', $devices, true);
            if (!$deviceMatch) continue;

            $startsAt = $w['settings']['starts_at'] ?? null;
            $endsAt   = $w['settings']['ends_at'] ?? null;
            if ($startsAt && $startsAt > $now) continue;
            if ($endsAt && $endsAt < $now) continue;

            if (trim((string) ($w['settings']['html'] ?? '')) === '') continue;
        }

        $byPosition[$w['position']][] = $w;
    }
    return $byPosition;
}

/**
 * Load global widget defaults (returns v2 shape; safe when table is missing).
 */
function widget_load_globals(): array
{
    if (!db_table_exists('blog_widget_defaults')) {
        return ['version' => 2, 'widgets' => []];
    }
    try {
        $row = fetch_one("SELECT config_json FROM blog_widget_defaults WHERE id = 1 LIMIT 1");
    } catch (Throwable) {
        return ['version' => 2, 'widgets' => []];
    }
    if (!$row || empty($row['config_json'])) {
        return ['version' => 2, 'widgets' => []];
    }
    return validate_widget_config((string) $row['config_json']);
}

/**
 * Render a single widget to HTML. $context provides the host post for related fetchers.
 */
function widget_render(array $widget, array $context = []): string
{
    $post = $context['post'] ?? [];
    $type = $widget['type'];
    $s = $widget['settings'];

    switch ($type) {
        case 'related_posts': {
            $catId = (int) ($post['category_id'] ?? 0);
            $items = fetch_related_posts(
                (int) ($post['id'] ?? 0),
                $catId > 0 ? $catId : null,
                (int) $s['count'],
                $s['manual_ids']
            );
            return widget_block(
                'Related Posts',
                widget_compact_list_posts($items)
            );
        }
        case 'related_itineraries': {
            $items = fetch_related_itineraries(
                0, null, (int) $s['count'], $s['manual_ids']
            );
            return widget_block(
                'Related Itineraries',
                widget_compact_list_itineraries($items)
            );
        }
        case 'related_destinations': {
            $items = fetch_related_destinations(
                0, (int) $s['count'], $s['manual_ids']
            );
            return widget_block(
                'Related Destinations',
                widget_compact_list_destinations($items)
            );
        }
        case 'ad': {
            $title = $s['title'] !== '' ? '<h2 class="h6 mb-3">' . e($s['title']) . '</h2>' : '';
            return '<div class="floating-card mb-4 widget-ad">'
                . $title
                . '<div class="small text-secondary widget-ad-body">' . $s['html'] . '</div>'
                . '</div>';
        }
        case 'cta': {
            $btn = '<a href="' . e($s['button_url']) . '" class="btn btn-success btn-sm">' . e($s['button_label']) . '</a>';
            $waBtn = '';
            if (!empty($s['whatsapp']) && function_exists('site_whatsapp_url')) {
                $waBtn = ' <a href="' . e(site_whatsapp_url(DEFAULT_WHATSAPP_TEXT)) . '" class="btn btn-outline-success btn-sm" target="_blank" rel="noopener">WhatsApp</a>';
            }
            return '<div class="floating-card mb-4 widget-cta">'
                . '<h2 class="h6 mb-2">' . e($s['title']) . '</h2>'
                . ($s['text'] !== '' ? '<p class="small text-secondary mb-3">' . e($s['text']) . '</p>' : '')
                . $btn . $waBtn
                . '</div>';
        }
        case 'newsletter': {
            return '<div class="floating-card mb-4 widget-newsletter">'
                . '<h2 class="h6 mb-2">' . e($s['title']) . '</h2>'
                . ($s['intro'] !== '' ? '<p class="small text-secondary mb-3">' . e($s['intro']) . '</p>' : '')
                . '<form class="d-flex gap-2" method="post" action="' . e(base_url('subscribe.php')) . '">'
                . csrf_field()
                . '<input type="email" name="email" class="form-control form-control-sm" placeholder="you@example.com" required>'
                . '<button type="submit" class="btn btn-success btn-sm">' . e($s['button_label']) . '</button>'
                . '</form>'
                . '</div>';
        }
    }
    return '';
}

function widget_block(string $title, string $body): string
{
    if ($body === '') return '';
    return '<div class="floating-card mb-4">'
        . '<h2 class="h6 mb-3">' . e($title) . '</h2>'
        . $body
        . '</div>';
}

function widget_compact_list_posts(array $items): string
{
    if (!$items) return '';
    $li = '';
    foreach ($items as $r) {
        $date = !empty($r['published_at']) ? date('d M Y', strtotime((string) $r['published_at'])) : '';
        $li .= '<li class="mb-2">'
            . '<a href="' . e(base_url('blog/' . $r['slug'])) . '" class="text-decoration-none" style="color:var(--text);">'
            . e((string) $r['title'])
            . ($date ? '<div class="small text-secondary">' . e($date) . '</div>' : '')
            . '</a></li>';
    }
    return '<ul class="list-unstyled mb-0">' . $li . '</ul>';
}

function widget_compact_list_itineraries(array $items): string
{
    if (!$items) return '';
    $li = '';
    foreach ($items as $r) {
        $days = (int) ($r['days'] ?? 0);
        $li .= '<li class="mb-2">'
            . '<a href="' . e(base_url('itineraries/' . $r['slug'])) . '" class="text-decoration-none" style="color:var(--text);">'
            . e((string) $r['name'])
            . ($days ? '<div class="small text-secondary">' . $days . ' days</div>' : '')
            . '</a></li>';
    }
    return '<ul class="list-unstyled mb-0">' . $li . '</ul>';
}

function widget_compact_list_destinations(array $items): string
{
    if (!$items) return '';
    $li = '';
    foreach ($items as $r) {
        $li .= '<li class="mb-2">'
            . '<a href="' . e(base_url('destinations/' . $r['slug'])) . '" class="text-decoration-none" style="color:var(--text);">'
            . e((string) $r['name'])
            . '</a></li>';
    }
    return '<ul class="list-unstyled mb-0">' . $li . '</ul>';
}

/**
 * Splice inline_after_p widgets into a content body after the requested paragraph.
 */
function widget_splice_inline(string $contentHtml, array $widgets, array $context = []): string
{
    if (!$widgets) return $contentHtml;

    $dom = new DOMDocument('1.0', 'UTF-8');
    $loaded = @$dom->loadHTML(
        '<?xml encoding="UTF-8"><div>' . $contentHtml . '</div>',
        LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOERROR | LIBXML_NOWARNING
    );
    if (!$loaded) {
        return $contentHtml;
    }
    $root = $dom->getElementsByTagName('div')->item(0);
    if (!$root) {
        return $contentHtml;
    }

    // Collect <p> elements in order (only direct descendants of root level body).
    $paragraphs = [];
    foreach ($root->childNodes as $child) {
        if ($child instanceof DOMElement && strtolower($child->nodeName) === 'p') {
            $paragraphs[] = $child;
        }
    }

    // Group widgets by after_paragraph.
    $byAfter = [];
    foreach ($widgets as $w) {
        $n = max(1, (int) ($w['settings']['after_paragraph'] ?? 2));
        $byAfter[$n][] = $w;
    }

    foreach ($byAfter as $n => $ws) {
        $idx = $n - 1;
        if (!isset($paragraphs[$idx])) continue;
        $anchor = $paragraphs[$idx];
        foreach ($ws as $w) {
            $rendered = widget_render($w, $context);
            if ($rendered === '') continue;
            $frag = $dom->createDocumentFragment();
            @$frag->appendXML('<div class="widget-inline">' . widget_xml_escape_for_fragment($rendered) . '</div>');
            // appendXML rejects bare HTML — fall back to importing via a temp doc.
            if (!$frag->hasChildNodes()) {
                $temp = new DOMDocument('1.0', 'UTF-8');
                @$temp->loadHTML('<?xml encoding="UTF-8"><div class="widget-inline">' . $rendered . '</div>',
                    LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOERROR | LIBXML_NOWARNING);
                $tempRoot = $temp->getElementsByTagName('div')->item(0);
                if ($tempRoot) {
                    $imported = $dom->importNode($tempRoot, true);
                    if ($anchor->nextSibling) {
                        $anchor->parentNode->insertBefore($imported, $anchor->nextSibling);
                    } else {
                        $anchor->parentNode->appendChild($imported);
                    }
                }
                continue;
            }
            if ($anchor->nextSibling) {
                $anchor->parentNode->insertBefore($frag, $anchor->nextSibling);
            } else {
                $anchor->parentNode->appendChild($frag);
            }
        }
    }

    $out = '';
    foreach ($root->childNodes as $child) {
        $out .= $dom->saveHTML($child);
    }
    return $out;
}

/**
 * Helper kept for the appendXML path; in practice we use loadHTML import above.
 */
function widget_xml_escape_for_fragment(string $html): string
{
    return $html;
}
