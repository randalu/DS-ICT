<?php
declare(strict_types=1);
require_once __DIR__ . '/route_map.php';

function route_map_geometry(): array
{
    static $geometry;
    return $geometry ??= json_decode(file_get_contents(__DIR__ . '/../assets/maps/sri-lanka.json'), true, 32, JSON_THROW_ON_ERROR);
}

function route_map_project(float $latitude, float $longitude): array
{
    $projection = route_map_geometry()['projection'];
    $phi = deg2rad($latitude);
    return [$projection['scale'] * deg2rad($longitude) + $projection['translate'][0],
        $projection['translate'][1] - $projection['scale'] * log(tan((M_PI / 2 + $phi) / 2))];
}

function route_map_number(float $value): string { return number_format($value, 2, '.', ''); }

function route_map_stops(array $days, array $locations): array
{
    $stops = [];
    foreach ($days as $day) {
        foreach ($day['stops'] as $id) {
            if (!isset($locations[$id])) { continue; }
            $place = $locations[$id];
            [$x, $y] = route_map_project((float) $place['latitude'], (float) $place['longitude']);
            $stops[] = ['id' => $id, 'day' => $day['day'], 'name' => $place['name'], 'x' => $x, 'y' => $y, 'order' => count($stops) + 1];
        }
    }
    return $stops;
}

/** Labels are optional when crowded; every stop remains in the numbered day list. */
function route_map_svg(array $stops): string
{
    $geometry = route_map_geometry();
    $summary = $stops ? implode('; ', array_map(static fn($s) => $s['order'] . '. Day ' . $s['day'] . ': ' . $s['name'], $stops)) : 'Sri Lanka overview. No package route configured.';
    $svg = '<svg class="route-map-svg" viewBox="0 0 600 760" role="img" aria-label="' . e($summary) . '" xmlns="http://www.w3.org/2000/svg"><title>Sri Lanka journey map</title><desc>' . e($summary) . '</desc>';
    $svg .= '<path class="route-map-land" d="' . e($geometry['path']) . '"/>';
    [$cx, $cy] = route_map_project(8.95, 80.5);
    $svg .= '<text class="route-map-country" x="' . route_map_number($cx) . '" y="' . route_map_number($cy) . '" text-anchor="middle">SRI LANKA</text><text class="route-map-ocean" x="555" y="45" text-anchor="end">N ↑</text><text class="route-map-ocean" x="448" y="715" text-anchor="middle">INDIAN OCEAN</text>';
    foreach ($stops as $i => $stop) {
        if ($i === 0) { continue; }
        $previous = $stops[$i - 1];
        $dx = $stop['x'] - $previous['x']; $dy = $stop['y'] - $previous['y'];
        $distance = hypot($dx, $dy);
        if ($distance < 1) { continue; } // Consecutive nights in one location do not imply travel.
        $bend = min(42, $distance * .2);
        $controlX = ($previous['x'] + $stop['x']) / 2 - $dy / $distance * $bend;
        $controlY = ($previous['y'] + $stop['y']) / 2 + $dx / $distance * $bend;
        $startX = $previous['x'] + $dx / $distance * 18; $startY = $previous['y'] + $dy / $distance * 18;
        $endX = $stop['x'] - $dx / $distance * 22; $endY = $stop['y'] - $dy / $distance * 22;
        if ($distance < 44) { $startX = $previous['x']; $startY = $previous['y']; $endX = $stop['x']; $endY = $stop['y']; }
        $t = .6;
        $ax = (1 - $t) ** 2 * $startX + 2 * (1 - $t) * $t * $controlX + $t ** 2 * $endX;
        $ay = (1 - $t) ** 2 * $startY + 2 * (1 - $t) * $t * $controlY + $t ** 2 * $endY;
        $angle = rad2deg(atan2((1 - $t) * ($controlY - $startY) + $t * ($endY - $controlY), (1 - $t) * ($controlX - $startX) + $t * ($endX - $controlX)));
        $svg .= '<path class="route-map-line" d="M' . route_map_number($startX) . ',' . route_map_number($startY) . ' Q' . route_map_number($controlX) . ',' . route_map_number($controlY) . ' ' . route_map_number($endX) . ',' . route_map_number($endY) . '"/>';
        $svg .= '<path class="route-map-arrow" d="M-7,-5 L7,0 L-7,5 Z" transform="translate(' . route_map_number($ax) . ',' . route_map_number($ay) . ') rotate(' . route_map_number($angle) . ')"/>';
    }
    $markers = [];
    foreach ($stops as $stop) {
        $key = route_map_number($stop['x']) . ':' . route_map_number($stop['y']);
        foreach ($markers as $existingKey => $existing) {
            if (hypot($existing['x'] - $stop['x'], $existing['y'] - $stop['y']) < 32) { $key = $existingKey; break; }
        }
        $markers[$key] ??= $stop + ['days' => [], 'names' => [], 'orders' => []];
        $markers[$key]['days'][$stop['day']] = $stop['day'];
        $markers[$key]['names'][$stop['name']] = $stop['name'];
        $markers[$key]['orders'][] = $stop['order'];
    }
    $boxes = [];
    foreach ($markers as $marker) {
        $x = $marker['x']; $y = $marker['y'];
        $start = in_array(1, $marker['orders'], true);
        $finish = in_array(count($stops), $marker['orders'], true);
        if ($start && $finish && count($stops) > 1) { $svg .= '<circle class="route-map-return" cx="' . route_map_number($x) . '" cy="' . route_map_number($y) . '" r="22"/>'; }
        $cluster = count($marker['names']) > 1;
        $svg .= '<g><title>' . e(implode(' / ', $marker['names']) . ' · Stops ' . implode(', ', $marker['orders']) . ($start ? ' · Start' : '') . ($finish ? ' · Finish' : '')) . '</title><circle class="route-map-marker' . ($start ? ' is-start' : '') . ($finish ? ' is-finish' : '') . '" cx="' . route_map_number($x) . '" cy="' . route_map_number($y) . '" r="16"/><text class="route-map-order" x="' . route_map_number($x) . '" y="' . route_map_number($y) . '">' . $marker['order'] . ($cluster ? '+' : '') . '</text></g>';
        if (count($markers) > 18) { continue; }
        $dayLabel = count($marker['days']) <= 3 ? 'D' . implode('/', $marker['days']) : count($marker['days']) . ' days';
        $name = implode(' / ', $marker['names']);
        $maxName = max(8, 24 - mb_strlen($dayLabel) - 3);
        $name = mb_strlen($name) > $maxName ? mb_substr($name, 0, $maxName - 1) . '…' : $name;
        $label = $dayLabel . ' · ' . $name;
        $width = min(370, mb_strlen($label) * 15.2);
        $left = $x < 290;
        foreach ([-9, -35, 28, -63, 56, -91, 84] as $offset) {
            $lx = $left ? $x - 25 - $width : $x + 25;
            $lx = max(10, min(590 - $width, $lx));
            $ly = $y + $offset;
            if (($lx < $x + 21 && $lx + $width > $x - 21) && abs($ly - $y) < 30) { continue; }
            $box = [$lx, $ly - 28, $lx + $width, $ly + 5];
            if ($box[1] < 10 || $box[3] > 746) { continue; }
            $collides = false;
            foreach ($boxes as $other) { if ($box[0] < $other[2] + 5 && $box[2] > $other[0] - 5 && $box[1] < $other[3] + 5 && $box[3] > $other[1] - 5) { $collides = true; break; } }
            foreach ($markers as $other) { if ($other['x'] > $box[0] - 18 && $other['x'] < $box[2] + 18 && $other['y'] > $box[1] - 18 && $other['y'] < $box[3] + 18) { $collides = true; break; } }
            if ($collides) { continue; }
            $boxes[] = $box;
            $anchorX = $left ? $lx + $width + 3 : $lx - 3;
            $svg .= '<path class="route-map-leader" d="M' . route_map_number($x) . ',' . route_map_number($y) . ' L' . route_map_number($anchorX) . ',' . route_map_number($ly - 7) . '"/><text class="route-map-label" x="' . route_map_number($lx) . '" y="' . route_map_number($ly) . '">' . e($label) . '</text>';
            break;
        }
    }
    return $svg . '</svg>';
}

function route_map_assets(): string
{
    static $loaded = false;
    if ($loaded) { return ''; }
    $loaded = true;
    return '<script defer src="' . e(base_url('assets/js/route-map.js') . '?v=' . filemtime(__DIR__ . '/../assets/js/route-map.js')) . '"></script>';
}

function route_map_card(array $state, ?array $design = null, bool $preview = false): string
{
    if ($design === null) {
        try { $design = route_map_design_state()['design']; } catch (Throwable) { $design = route_map_design_defaults(); }
    }
    $showRoute = $preview || $state['ready'];
    $days = $showRoute ? ($state['document']['days'] ?? []) : [];
    $stops = route_map_stops($days, $state['locations'] ?? []);
    $title = $stops ? $design['title'] : 'Sri Lanka overview';
    $html = '<section class="route-map" aria-label="' . e($title) . '" style="--route-map-color:' . e($design['route_color']) . '"><div class="route-map-heading"><div><h3>' . e($title) . '</h3><p>' . ($stops ? 'Day-by-day route overview' : 'Our Sri Lanka map') . '</p></div><button type="button" class="route-map-enlarge" data-map-enlarge hidden>Enlarge<span class="visually-hidden"> journey map</span></button></div>';
    $html .= route_map_svg($stops);
    $html .= '<div class="route-map-footer"><p>' . e($design['brand']) . '</p><p class="route-map-note">' . ($preview ? 'Unsaved preview · ' : '') . ($stops ? 'Illustrative route · Arrows show travel order' : 'Sri Lanka overview · No package route configured') . '</p>';
    if ($stops) {
        $html .= '<p class="route-map-key">Start: stop 1 · Finish: stop ' . count($stops) . '<br>+ Nearby stops share a marker</p><details class="route-map-stop-list"><summary>View stops by day</summary><ol>';
        $number = 0;
        foreach ($days as $day) {
            if (!$day['stops']) { continue; }
            $names = [];
            foreach ($day['stops'] as $id) {
                if (isset($state['locations'][$id])) { $names[] = ++$number . '. ' . $state['locations'][$id]['name']; }
            }
            $html .= '<li><strong>Day ' . (int) $day['day'] . '</strong><span>' . e(implode(' → ', $names)) . '</span></li>';
        }
        $html .= '</ol></details>';
    }
    $html .= '<p class="route-map-credits">Map: <a href="https://www.naturalearthdata.com/">Natural Earth</a>' . ($stops ? ' · Places: <a href="https://www.geonames.org/">GeoNames</a> (<a href="https://creativecommons.org/licenses/by/4.0/" rel="license">CC BY 4.0</a>) / owner verified' : '') . '</p></div></section>';
    return $html;
}

function route_map_public(string $type, array $record): string
{
    return route_map_card(route_map_state($type, $record)) . route_map_assets();
}
