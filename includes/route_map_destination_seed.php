<?php
declare(strict_types=1);

/** Exact, punctuation-insensitive identities; never fuzzy geocoding. */
function route_map_seed_key(string $value): string
{
    return preg_replace('/[^\p{L}\p{N}]+/u', '', mb_strtolower(trim($value), 'UTF-8')) ?? '';
}

function route_map_seed_catalog(string $file): array
{
    $raw = file_get_contents($file);
    if ($raw === false) { throw new RuntimeException('Destination coordinate catalog is missing.'); }
    $catalog = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
    if (($catalog['version'] ?? null) !== 1 || !is_array($catalog['places'] ?? null)) {
        throw new RuntimeException('Unsupported destination coordinate catalog.');
    }
    $names = []; $slugs = [];
    foreach ($catalog['places'] as $index => $place) {
        if (!is_string($place['slug'] ?? null) || !preg_match('/\A[a-z0-9]+(?:-[a-z0-9]+)*\z/', $place['slug'])
            || !is_array($place['names'] ?? null) || !$place['names']
            || !is_numeric($place['latitude'] ?? null) || !is_numeric($place['longitude'] ?? null)
            || !is_finite((float) $place['latitude']) || !is_finite((float) $place['longitude'])
            || $place['latitude'] < 5.8 || $place['latitude'] > 10 || $place['longitude'] < 79.4 || $place['longitude'] > 82
            || !is_int($place['geoname_id'] ?? null) || $place['geoname_id'] <= 0
            || ($place['source'] ?? null) !== 'https://www.geonames.org/'.$place['geoname_id'].'/'
            || !is_string($place['point_kind'] ?? null) || !is_string($place['source_name'] ?? null)) {
            throw new RuntimeException('Invalid destination coordinate catalog entry.');
        }
        $slug = route_map_seed_key($place['slug']);
        if (isset($slugs[$slug])) { throw new RuntimeException('Duplicate catalog destination slug.'); }
        $slugs[$slug] = $index;
        foreach ($place['names'] as $name) {
            if (!is_string($name) || mb_strlen($name) > 150 || ($key = route_map_seed_key($name)) === '') {
                throw new RuntimeException('Invalid catalog destination name.');
            }
            if (isset($names[$key]) && $names[$key] !== $index) { throw new RuntimeException('Ambiguous catalog destination name.'); }
            $names[$key] = $index;
        }
    }
    return ['data'=>$catalog, 'sha256'=>hash('sha256', $raw), 'names'=>$names, 'slugs'=>$slugs];
}

function route_map_seed_plan(array $destinations, array $locations, array $catalog): array
{
    $linked = []; $unlinked = [];
    foreach ($locations as $location) {
        if ($location['destination_id'] !== null) { $linked[(int) $location['destination_id']] = $location; }
        else { $unlinked[route_map_seed_key((string) $location['name'])] = true; }
    }
    $create = []; $preserve = []; $skipped = []; $identities = [];
    foreach ($destinations as $destination) {
        $id = (int) $destination['id'];
        $entry = ['destination_id'=>$id, 'name'=>(string) $destination['name'], 'slug'=>(string) $destination['slug']];
        if (isset($linked[$id])) { $preserve[] = $entry + ['location_id'=>(int) $linked[$id]['id']]; continue; }
        $name = route_map_seed_key($entry['name']); $slug = route_map_seed_key($entry['slug']);
        $byName = $catalog['names'][$name] ?? null; $bySlug = $catalog['slugs'][$slug] ?? null;
        // A known slug never overrides renamed/conflicting destination content.
        if ($byName === null || ($bySlug !== null && $bySlug !== $byName)) {
            $reason = $bySlug !== null ? 'Destination name/slug needs review.' : 'No exact sourced coordinate match.';
            foreach ($catalog['data']['review_required'] ?? [] as $review) {
                if ($review['slug'] === $entry['slug']) { $reason = $review['reason']; }
            }
            $skipped[] = $entry + ['reason'=>$reason]; continue;
        }
        $place = $catalog['data']['places'][$byName];
        $collision = isset($unlinked[$name]);
        foreach ($place['names'] as $alias) { $collision = $collision || isset($unlinked[route_map_seed_key($alias)]); }
        if ($collision) { $skipped[] = $entry + ['reason'=>'An unlinked map location already uses this name; link/review it manually.']; continue; }
        if ($entry['name'] === '' || mb_strlen($entry['name']) > 150 || $entry['name'] !== strip_tags($entry['name']) || preg_match('/[\x00-\x1f\x7f]/', $entry['name'])) {
            $skipped[] = $entry + ['reason'=>'Destination name needs plain-text review.']; continue;
        }
        $create[] = $entry + ['latitude'=>number_format((float) $place['latitude'], 6, '.', ''),
            'longitude'=>number_format((float) $place['longitude'], 6, '.', ''),
            'coordinate_source'=>'GeoNames CC BY 4.0: '.$place['source'].'; '.$place['point_kind'].'; approximate, not an entrance.',
            'catalog_slug'=>$place['slug']];
        $identities[$place['slug']] = ($identities[$place['slug']] ?? 0) + 1;
    }
    $accepted = [];
    foreach ($create as $entry) {
        if ($identities[$entry['catalog_slug']] > 1) { $skipped[] = array_intersect_key($entry, array_flip(['destination_id','name','slug'])) + ['reason'=>'Multiple destination records match the same catalog place; review duplicates.']; }
        else { $accepted[] = $entry; }
    }
    $plan = ['catalog_sha256'=>$catalog['sha256'], 'destinations'=>count($destinations), 'create'=>$accepted, 'preserve'=>$preserve, 'skipped'=>$skipped,
        'snapshot_sha256'=>hash('sha256', json_encode([$destinations,$locations], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR))];
    $plan['plan_sha256'] = hash('sha256', json_encode($plan, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
    return $plan;
}
