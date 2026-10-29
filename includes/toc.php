<?php
declare(strict_types=1);

/**
 * Table of Contents helpers.
 *
 * Two entry points:
 *   toc_inject_ids($html, $opts)  — adds slugified id="" to <h2>/<h3> in a content blob.
 *   render_toc($html, $opts)      — returns the TOC <nav>; assumes the caller already
 *                                    ran toc_inject_ids() on the same blob.
 *
 * Workflow per landing page:
 *   $body = toc_inject_ids($rawHtml);
 *   echo render_toc($body);
 *   echo $body;
 *
 * `render_toc` returns an empty string when fewer than 2 headings exist.
 */

/**
 * Inject id="" attributes onto h2/h3 headings inside an HTML blob.
 * Existing ids are preserved; missing ones are slugified from the heading text.
 */
function toc_inject_ids(string $html, array $opts = []): string
{
    $html = trim($html);
    if ($html === '') {
        return $html;
    }

    $tags = $opts['tags'] ?? ['h2', 'h3'];
    $prefix = (string) ($opts['id_prefix'] ?? 'toc-');

    $dom = new DOMDocument('1.0', 'UTF-8');
    $loadOk = @$dom->loadHTML(
        '<?xml encoding="UTF-8"><div>' . $html . '</div>',
        LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOERROR | LIBXML_NOWARNING
    );
    if (!$loadOk) {
        return $html;
    }

    $used = [];
    foreach ($tags as $tag) {
        $nodes = $dom->getElementsByTagName($tag);
        // collect first to avoid live-list mutation issues
        $list = [];
        foreach ($nodes as $node) { $list[] = $node; }

        foreach ($list as $node) {
            /** @var DOMElement $node */
            $existing = trim((string) $node->getAttribute('id'));
            if ($existing !== '') {
                $used[$existing] = true;
                continue;
            }

            $text = trim($node->textContent);
            if ($text === '') {
                continue;
            }

            $base = $prefix . toc_slugify($text);
            $id = $base;
            $i = 2;
            while (isset($used[$id])) {
                $id = $base . '-' . $i;
                $i++;
            }
            $used[$id] = true;
            $node->setAttribute('id', $id);
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

/**
 * Render the sticky TOC nav for an HTML blob whose headings already have ids.
 * Returns '' when there are fewer than 2 eligible headings.
 */
function render_toc(string $html, array $opts = []): string
{
    $tags = $opts['tags'] ?? ['h2', 'h3'];
    $title = (string) ($opts['title'] ?? 'On this page');
    $class = (string) ($opts['class'] ?? 'toc-sticky d-none d-lg-block');
    $minHeadings = (int) ($opts['min_headings'] ?? 2);

    $items = toc_extract_headings($html, $tags);
    if (count($items) < $minHeadings) {
        return '';
    }

    $li = '';
    foreach ($items as $item) {
        $li .= sprintf(
            '<li class="toc-item toc-level-%d"><a href="#%s">%s</a></li>',
            (int) $item['level'],
            e($item['id']),
            e($item['text'])
        );
    }

    return sprintf(
        '<nav class="%s" aria-label="Page contents">'
            . '<div class="toc-title">%s</div>'
            . '<ol class="toc-list">%s</ol>'
        . '</nav>',
        e($class),
        e($title),
        $li
    );
}

/**
 * Render a mobile-first collapsible TOC (uses native <details>).
 * Useful below the hero on small screens where the sticky version is hidden.
 */
function render_toc_mobile(string $html, array $opts = []): string
{
    $tags = $opts['tags'] ?? ['h2', 'h3'];
    $title = (string) ($opts['title'] ?? 'On this page');
    $minHeadings = (int) ($opts['min_headings'] ?? 2);

    $items = toc_extract_headings($html, $tags);
    if (count($items) < $minHeadings) {
        return '';
    }

    $li = '';
    foreach ($items as $item) {
        $li .= sprintf(
            '<li class="toc-item toc-level-%d"><a href="#%s">%s</a></li>',
            (int) $item['level'],
            e($item['id']),
            e($item['text'])
        );
    }

    return sprintf(
        '<details class="toc-mobile d-lg-none">'
            . '<summary>%s</summary>'
            . '<ol class="toc-list">%s</ol>'
        . '</details>',
        e($title),
        $li
    );
}

/**
 * Render a sticky TOC from an explicit list of items.
 * Each item: ['id' => 'why-visit', 'text' => 'Why Visit', 'level' => 2 (default)].
 * Use this when section h2s live in PHP markup (not inside one HTML blob).
 */
function render_toc_from_items(array $items, array $opts = []): string
{
    $items = array_values(array_filter($items, static function ($it) {
        return is_array($it) && !empty($it['id']) && !empty($it['text']);
    }));
    $minHeadings = (int) ($opts['min_headings'] ?? 2);
    if (count($items) < $minHeadings) {
        return '';
    }

    $title = (string) ($opts['title'] ?? 'On this page');
    $class = (string) ($opts['class'] ?? 'toc-sticky d-none d-lg-block');

    $li = '';
    foreach ($items as $it) {
        $level = (int) ($it['level'] ?? 2);
        $li .= sprintf(
            '<li class="toc-item toc-level-%d"><a href="#%s">%s</a></li>',
            $level,
            e((string) $it['id']),
            e((string) $it['text'])
        );
    }

    return sprintf(
        '<nav class="%s" aria-label="Page contents">'
            . '<div class="toc-title">%s</div>'
            . '<ol class="toc-list">%s</ol>'
        . '</nav>',
        e($class),
        e($title),
        $li
    );
}

/**
 * Mobile-collapsible variant of render_toc_from_items.
 */
function render_toc_mobile_from_items(array $items, array $opts = []): string
{
    $items = array_values(array_filter($items, static function ($it) {
        return is_array($it) && !empty($it['id']) && !empty($it['text']);
    }));
    $minHeadings = (int) ($opts['min_headings'] ?? 2);
    if (count($items) < $minHeadings) {
        return '';
    }

    $title = (string) ($opts['title'] ?? 'On this page');

    $li = '';
    foreach ($items as $it) {
        $level = (int) ($it['level'] ?? 2);
        $li .= sprintf(
            '<li class="toc-item toc-level-%d"><a href="#%s">%s</a></li>',
            $level,
            e((string) $it['id']),
            e((string) $it['text'])
        );
    }

    return sprintf(
        '<details class="toc-mobile d-lg-none">'
            . '<summary>%s</summary>'
            . '<ol class="toc-list">%s</ol>'
        . '</details>',
        e($title),
        $li
    );
}

function toc_extract_headings(string $html, array $tags): array
{
    $items = [];
    if (trim($html) === '') {
        return $items;
    }

    $dom = new DOMDocument('1.0', 'UTF-8');
    $loadOk = @$dom->loadHTML(
        '<?xml encoding="UTF-8"><div>' . $html . '</div>',
        LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOERROR | LIBXML_NOWARNING
    );
    if (!$loadOk) {
        return $items;
    }

    foreach ($tags as $tag) {
        $level = (int) substr((string) $tag, 1);
        $nodes = $dom->getElementsByTagName($tag);
        foreach ($nodes as $node) {
            $id = trim((string) $node->getAttribute('id'));
            $text = trim($node->textContent);
            if ($id === '' || $text === '') {
                continue;
            }
            $items[] = ['level' => $level, 'id' => $id, 'text' => $text];
        }
    }

    return $items;
}

function toc_slugify(string $text): string
{
    $text = strip_tags($text);
    $text = preg_replace('/&[a-z0-9#]+;/i', '', $text) ?? $text;
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9\s\-_]/', '', $text) ?? $text;
    $text = preg_replace('/[\s_]+/', '-', trim($text)) ?? $text;
    $text = preg_replace('/-+/', '-', $text) ?? $text;
    return trim($text, '-');
}
