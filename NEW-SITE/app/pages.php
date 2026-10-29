<?php
declare(strict_types=1);

namespace NewSite;

/** Ignore arrays/objects and bound all GET strings before using them as state. */
function query_text(array $query, string $name, int $limit = 100): string
{
    $value = $query[$name] ?? '';
    if (!is_string($value) && !is_int($value)) {
        return '';
    }
    $value = (string) $value;
    if (strlen($value) > $limit || preg_match('/[\x00-\x1f\x7f]/', $value) || preg_match('//u', $value) !== 1) {
        return '';
    }
    return trim($value);
}

function record_options(array $items, string $field): array
{
    $options = [];
    foreach ($items as $item) {
        $value = $item[$field] ?? '';
        if ((is_string($value) || is_int($value)) && (string) $value !== '') {
            $value = (string) $value;
            $options[$value] = ucwords(str_replace(['-', '_'], ' ', $value));
        }
    }
    ksort($options, SORT_NATURAL);
    return $options;
}

function catalog_records(array $catalog): array
{
    $records = is_array($catalog['records'] ?? null) ? $catalog['records'] : [];
    foreach (['destinations' => 'destination', 'journeys' => 'itinerary', 'tours' => 'tour',
        'day_tours' => 'day-tour', 'experiences' => 'experience', 'articles' => 'blog'] as $key => $family) {
        if (isset($records[$family])) {
            continue;
        }
        foreach (($catalog[$key] ?? []) as $record) {
            if (is_array($record) && is_string($record['slug'] ?? null)) {
                $records[$family][$record['slug']] = $record;
            }
        }
    }
    foreach ($records as &$familyRecords) {
        foreach ($familyRecords as &$record) {
            if (isset($record['duration_days']) && !isset($record['duration'])) {
                $record['duration'] = $record['duration_days'];
            }
        }
        unset($record);
    }
    unset($familyRecords);
    return $records;
}

/** Full timeline arrays belong only to detail records, never card summaries. */
function record_card(array $record): array
{
    $duration = $record['duration_days'] ?? $record['duration'] ?? null;
    unset($record['days'], $record['body'], $record['sections'], $record['gallery'], $record['faq']);
    if (is_int($duration) && $duration > 0) {
        $record['days'] = $duration;
        $record['duration_days'] = $duration;
    }
    return $record;
}

/** Per-page options derive from actual records, not invented products or price ranges. */
function filtered_items(array $items, string $family, array $query): array
{
    $fields = match ($family) {
        'destination' => ['region' => 'region'],
        'itinerary' => ['days' => 'duration', 'theme' => 'theme', 'traveller' => 'traveller', 'start' => 'start'],
        'day-tour' => ['region' => 'region', 'difficulty' => 'difficulty', 'price' => 'price_band'],
        'experience' => ['mood' => 'mood'],
        'blog' => ['category' => 'category'],
        default => [],
    };
    $options = $filters = [];
    foreach ($fields as $name => $field) {
        $options[$name] = record_options($items, $field);
        if ($name === 'days') {
            foreach ($options[$name] as $key => $_label) {
                $options[$name][$key] = $key . ' days' . (!empty($items[0]['demo']) ? ' - illustrative sample' : '');
            }
        }
        if ($name === 'price' && isset($options[$name]['unquoted'])) {
            $options[$name]['unquoted'] = 'Not quoted - design sample';
        }
        $candidate = query_text($query, $name, 64);
        $filters[$name] = isset($options[$name][$candidate]) ? $candidate : '';
    }
    if (in_array($family, ['destination', 'day-tour', 'blog', 'search'], true)) {
        $filters['q'] = query_text($query, 'q', 120);
    }
    $filtered = array_filter($items, static function (array $item) use ($filters, $fields): bool {
        foreach ($fields as $name => $field) {
            if ($filters[$name] !== '' && (string) ($item[$field] ?? '') !== $filters[$name]) {
                return false;
            }
        }
        if (($filters['q'] ?? '') !== '') {
            $haystack = (string) ($item['title'] ?? '') . ' ' . (string) ($item['summary'] ?? '');
            if (stripos($haystack, $filters['q']) === false) {
                return false;
            }
        }
        return true;
    });
    return ['items' => array_values($filtered), 'filters' => $filters, 'filter_options' => $options];
}

/** Invalid/oversized pages normalize to 1; valid out-of-range pages clamp to last. */
function paginate_items(array $items, array $query, string $route, array $filters, int $perPage = 9): array
{
    $input = query_text($query, 'page', 6);
    $requested = preg_match('/\A[1-9][0-9]{0,5}\z/D', $input) ? (int) $input : 1;
    $total = count($items);
    $pages = max(1, (int) ceil($total / $perPage));
    $page = min($requested, $pages);
    $link = static fn (int $number): string => $route . '?' . http_build_query(array_filter($filters, static fn ($value): bool => $value !== '') + ['page' => $number], '', '&', PHP_QUERY_RFC3986);
    $links = [];
    // A bounded pager, even for large approved read-only catalogues.
    for ($number = max(1, $page - 2); $number <= min($pages, $page + 2); $number++) {
        $links[] = ['page' => $number, 'url' => $link($number), 'current' => $number === $page];
    }
    return ['items' => array_slice($items, ($page - 1) * $perPage, $perPage),
        'pagination' => ['page' => $page, 'pages' => $pages, 'total' => $total, 'per_page' => $perPage,
            'previous_url' => $page > 1 ? $link($page - 1) : '', 'next_url' => $page < $pages ? $link($page + 1) : '', 'links' => $links]];
}

function page_breadcrumbs(array $resolved): array
{
    $crumbs = [['title' => 'Home', 'url' => '/']];
    if ($resolved['route'] === '/') {
        return $crumbs;
    }
    $parents = ['destination' => ['/destinations', 'Destinations'], 'itinerary' => ['/itineraries', 'Journeys'],
        'tour' => ['/tours', 'Tours'], 'day-tour' => ['/day-tours', 'Day tours'],
        'experience' => ['/experiences', 'Experiences'], 'blog' => ['/blog', 'Journal'],
        'partner' => ['/business-partners', 'Partners']];
    $parent = $parents[$resolved['family'] ?? ''] ?? null;
    if ($parent !== null && $parent[0] !== $resolved['route']) {
        $crumbs[] = ['title' => $parent[1], 'url' => $parent[0]];
    }
    $crumbs[] = ['title' => $resolved['title'], 'url' => $resolved['route']];
    return $crumbs;
}

function page_model(array $page, array $resolved, array $catalog): array
{
    $query = is_array($resolved['query'] ?? null) ? $resolved['query'] : [];
    $records = catalog_records($catalog);
    $family = $resolved['family'] ?? 'support';
    $all = array_values($records[$family] ?? []);
    $page += ['family' => $family, 'record' => null, 'items' => [], 'filters' => [], 'filter_options' => [],
        'pagination' => ['page' => 1, 'pages' => 1, 'total' => 0, 'per_page' => 9, 'links' => [], 'previous_url' => '', 'next_url' => ''],
        'breadcrumbs' => page_breadcrumbs($resolved), 'related' => [], 'support_type' => $resolved['support_type'] ?? '',
        'publication_status' => $resolved['publication_status'] ?? 'sample',
        'empty_message' => 'No matching samples. Clear the filters to explore the available design records.',
        'comparison_options' => [], 'demo' => $page['mode'] === 'fixture'];
    if ($page['page_kind'] === 'not-found' || $page['page_kind'] === 'home') {
        return $page;
    }
    // A configured but unavailable source never receives fixture records or sample guides.
    if ($page['mode'] === 'unavailable') {
        $page['empty_message'] = 'Published content unavailable. No sample records have been substituted.';
        $page['publication_status'] = 'unavailable';
        return $page;
    }
    if (isset($resolved['slug'])) {
        $record = $records[$family][$resolved['slug']] ?? null;
        if ($record === null) {
            if ($family === 'static' && $page['page_kind'] === 'support') {
                $page['publication_status'] = 'unavailable';
                $page['empty_message'] = 'Published service information is not available from this preview source.';
                return $page;
            }
            $page['status'] = 404;
            $page['page_kind'] = 'not-found';
            $page['title'] = 'Page not found | Island Culture Travels';
            return $page;
        }
        $page['record'] = $record;
        $page['title'] = $record['title'] . ' | Island Culture Travels';
        $page['description'] = $record['summary'];
        if (($record['image'] ?? '') !== '') {
            $page['hero'] = ['image' => $record['image'], 'alt' => $record['image_alt'] ?? '', 'place' => $record['title']];
        }
        $page['related'] = array_slice(array_values(array_filter($all, static fn (array $item): bool => ($item['id'] ?? '') !== ($record['id'] ?? ''))), 0, 3);
    }
    if ($page['page_kind'] === 'collection') {
        if (isset($resolved['guide_group'])) {
            $all = array_values(array_filter($all, static fn (array $item): bool => ($item['guide_group'] ?? '') === $resolved['guide_group']));
        }
        if (isset($resolved['duration'])) {
            $all = array_values(array_filter($all, static fn (array $item): bool => (int) ($item['duration'] ?? 0) === $resolved['duration']));
            if ($all === []) {
                $page['status'] = 404;
                $page['page_kind'] = 'not-found';
                return $page;
            }
        }
        if (isset($resolved['category']) && $family === 'tour') {
            // Source behavior: a concrete tour record takes precedence over category fallback.
            $tour = $records['tour'][$resolved['category']] ?? null;
            if ($tour !== null) {
                $page['record'] = $tour;
                $page['items'] = [];
                $page['page_kind'] = 'detail';
                $page['title'] = $tour['title'] . ' | Island Culture Travels';
                $page['description'] = $tour['summary'];
                $page['hero'] = ['image' => $tour['image'] ?? '', 'alt' => $tour['image_alt'] ?? '', 'place' => $tour['title']];
                return $page;
            }
            $category = $catalog['categories'][$resolved['category']] ?? null;
            if (!is_array($category)) {
                $page['status'] = 404;
                $page['page_kind'] = 'not-found';
                return $page;
            }
            $all = array_values(array_filter($records['itinerary'] ?? [], static fn (array $item): bool => in_array($item['slug'] ?? '', $category['itinerary_slugs'] ?? [], true)));
        } elseif (isset($resolved['category'])) {
            $all = array_values(array_filter($all, static fn (array $item): bool => ($item['category'] ?? '') === $resolved['category']));
        }
        $filtered = filtered_items($all, $family, $query);
        $page = array_replace($page, $filtered, paginate_items($filtered['items'], $query, $resolved['route'], $filtered['filters'], $family === 'guide' ? 12 : 9));
    }
    if ($page['page_kind'] === 'support') {
        $journeys = array_values($records['itinerary'] ?? []);
        if ($page['support_type'] === 'plan') {
            $normalized = $query;
            $normalized['days'] = query_text($query, 'd', 3);
            $filtered = filtered_items($journeys, 'itinerary', $normalized);
            $page['filters'] = ['d' => $filtered['filters']['days'], 'theme' => $filtered['filters']['theme']];
            $page['filter_options'] = ['d' => $filtered['filter_options']['days'], 'theme' => $filtered['filter_options']['theme']];
            $page['items'] = $filtered['items'];
        } elseif ($page['support_type'] === 'compare') {
            $allowlist = array_column($journeys, null, 'id');
            $ids = explode(',', query_text($query, 'ids', 500));
            $ids = array_slice(array_values(array_unique(array_filter($ids, static fn (string $id): bool => isset($allowlist[$id])))), 0, 4);
            $page['filters'] = ['ids' => implode(',', $ids)];
            $page['items'] = array_values(array_intersect_key($allowlist, array_flip($ids)));
            $page['comparison_options'] = $journeys;
        } elseif ($page['support_type'] === 'ai') {
            $searchable = [];
            foreach (['destination', 'itinerary', 'tour', 'day-tour', 'experience', 'blog'] as $searchFamily) {
                $searchable = array_merge($searchable, array_values($records[$searchFamily] ?? []));
            }
            $filtered = filtered_items($searchable, 'search', $query);
            $page['filters'] = $filtered['filters'];
            $page['items'] = array_slice($filtered['items'], 0, 24);
        }
        if (in_array($page['support_type'], ['plan', 'compare', 'ai'], true)) {
            $page['pagination']['total'] = count($page['items']);
        }
    }
    $page['items'] = array_map(__NAMESPACE__ . '\\record_card', $page['items']);
    $page['related'] = array_map(__NAMESPACE__ . '\\record_card', $page['related']);
    $page['comparison_options'] = array_map(__NAMESPACE__ . '\\record_card', $page['comparison_options']);
    return $page;
}
