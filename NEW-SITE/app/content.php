<?php
declare(strict_types=1);

namespace NewSite;

function content_state(?array $environment = null): array
{
    if (function_exists(__NAMESPACE__ . '\\read_only_catalog')) {
        try {
            $source = read_only_catalog($environment);
            if (in_array($source['mode'] ?? '', ['fixture', 'unavailable', 'database'], true)
                && (($source['mode'] ?? '') !== 'database' || is_array($source['catalog'] ?? null))) {
                return $source;
            }
        } catch (\Throwable) {
            // Never expose connection details, SQL or exception strings to a visitor.
        }
        return ['mode' => 'unavailable', 'source_message' => 'Published content unavailable. No sample records have been substituted.', 'catalog' => null];
    }
    $environment ??= ['NEW_SITE_DB_DSN' => getenv('NEW_SITE_DB_DSN'),
        'NEW_SITE_DB_USER' => getenv('NEW_SITE_DB_USER'), 'NEW_SITE_DB_PASSWORD' => getenv('NEW_SITE_DB_PASSWORD')];
    foreach (['NEW_SITE_DB_DSN', 'NEW_SITE_DB_USER', 'NEW_SITE_DB_PASSWORD'] as $key) {
        // An explicitly supplied empty value is configuration, not fixture consent.
        if (array_key_exists($key, $environment) && $environment[$key] !== false && $environment[$key] !== null) {
            return ['mode' => 'unavailable', 'source_message' => 'Published content unavailable. A verified database-enforced SELECT-only adapter is not enabled. No database connection was attempted.', 'catalog' => null];
        }
    }
    return ['mode' => 'fixture', 'source_message' => 'Design preview using labelled samples and existing local photography. No database is connected; journeys are illustrative, not published offers.', 'catalog' => null];
}

function build_page(array $resolved, ?array $environment = null): array
{
    $source = content_state($environment);
    $catalog = [];
    if ($source['mode'] === 'fixture') {
        $catalog = require dirname(__DIR__) . '/fixtures/catalog.php';
        $catalog += require dirname(__DIR__) . '/fixtures/pages.php';
    } elseif ($source['mode'] === 'database' && is_array($source['catalog'] ?? null)) {
        $catalog = $source['catalog'];
        foreach (['destinations', 'journeys', 'experiences', 'articles'] as $collection) {
            $catalog[$collection] = array_map(__NAMESPACE__ . '\\record_card', $catalog[$collection] ?? []);
        }
    }
    catalog_route_manifest($source['mode'] === 'database' ? catalog_records($catalog) : []);
    // Resolution happens before source loading; promote only an exact sourced record.
    $sourceRoute = route_manifest($environment)[$resolved['route']] ?? null;
    if (($resolved['status'] ?? 404) === 404 && $sourceRoute !== null && $source['mode'] === 'database') {
        $resolved = array_replace($resolved, $sourceRoute, ['status' => 200]);
    }
    $page = [
        'route' => $resolved['route'], 'title' => $resolved['title'] . ' | Island Culture Travels',
        'description' => 'A local, read-only design preview for private travel in Sri Lanka. Sample content is not a published travel offer.',
        'canonical' => 'https://srilankaprivatetravels.com' . ($resolved['route'] === '/' ? '/' : $resolved['route']),
        'status' => $resolved['status'], 'page_kind' => $resolved['kind'], 'mode' => $source['mode'],
        'source_message' => $source['source_message'],
        'hero' => $catalog['hero'] ?? ['image' => 'assets/images/home-hero/home-3.w1200.webp',
            'alt' => 'Aerial view of a Sri Lankan coastline and palm-covered headland', 'place' => 'Sri Lanka'],
        'destinations' => $catalog['destinations'] ?? [], 'journeys' => $catalog['journeys'] ?? [],
        'experiences' => $catalog['experiences'] ?? [], 'articles' => $catalog['articles'] ?? [],
        'reviews' => [], 'active_journey' => 0,
        'milestones' => [['title' => 'Isolation & content contract', 'status' => 'preview'],
            ['title' => 'Homepage visual direction', 'status' => 'approved'],
            ['title' => 'Page-family design samples', 'status' => 'preview']],
        'route_manifest' => route_manifest($environment),
    ];
    return page_model($page, $resolved, $catalog);
}
