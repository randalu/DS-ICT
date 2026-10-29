<?php
require_once __DIR__ . '/../includes/auth.php';
admin_require_auth();

/**
 * admin/_gem_save_patches.php
 *
 * Code snippets to ADD to existing _save.php files.
 * This file itself is NOT included anywhere — it is a reference
 * document showing exactly what lines to paste and where.
 *
 * Each section is clearly labelled. Copy-paste the relevant block.
 */

/*
╔══════════════════════════════════════════════════════════════╗
║  A.  destination_save.php  — add new GEM fields to $data[]  ║
╚══════════════════════════════════════════════════════════════╝

FIND in destination_save.php (around line 12, inside $data = [...]):

    'best_time_json'   => '',
    'route_notes_json' => '',

REPLACE WITH:

    'best_time_json'   => '',
    'route_notes_json' => '',
    // ── GEM redesign fields ──────────────────────────────
    'region_slug'      => trim((string) post_input('region_slug',   '')),
    'region_label'     => trim((string) post_input('region_label',  '')),
    'emoji'            => trim((string) post_input('emoji',         '')),
    'stay_duration'    => trim((string) post_input('stay_duration', '')),
    'map_x'            => post_input('map_x', '') !== '' ? (float) post_input('map_x') : null,
    'map_y'            => post_input('map_y', '') !== '' ? (float) post_input('map_y') : null,
    'map_r'            => post_input('map_r', '7.0') !== '' ? (float) post_input('map_r', '7.0') : 7.0,
    'map_initial'      => substr(trim((string) post_input('map_initial', '')), 0, 2),
    // ── End GEM fields ───────────────────────────────────


FIND the best_time_json composition block (around line 35):

    $bestTimeMonths = array_values(array_filter(array_map( ...
    ...
    if ($bestTimeMonths || $bestTimeNote !== '') {
        $data['best_time_json'] = json_encode([...], ...);
    }

REPLACE the entire block with:

    // ── Build best_time_json from new 12-month rating grid ──────────────────
    $bestTimeRatings = (array) post_input('best_time_ratings', []);
    $bestTimeNote    = trim((string) post_input('best_time_note', ''));
    $bestTimeMonths  = [];
    $allMonths = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    $validRatings = ['great','okay','avoid'];
    foreach ($allMonths as $m) {
        $rating = strtolower(trim((string)($bestTimeRatings[$m] ?? '')));
        if (in_array($rating, $validRatings, true)) {
            $bestTimeMonths[] = ['month' => $m, 'rating' => $rating];
        }
    }
    if ($bestTimeMonths || $bestTimeNote !== '') {
        $data['best_time_json'] = json_encode(
            ['months' => $bestTimeMonths, 'note' => $bestTimeNote],
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );
    }
    // ── End best_time_json ──────────────────────────────────────────────────


FIND in $columnValueMap (the array passed to db write, around line 70):

    'best_time_json'   => $data['best_time_json'],
    'route_notes_json' => $data['route_notes_json'],

ADD after those two lines:

    // ── GEM redesign columns (safe — db_column_exists guard below) ──────────
    'region_slug'   => $data['region_slug']   ?: null,
    'region_label'  => $data['region_label']  ?: null,
    'emoji'         => $data['emoji']         ?: null,
    'stay_duration' => $data['stay_duration'] ?: null,
    'map_x'         => $data['map_x'],
    'map_y'         => $data['map_y'],
    'map_r'         => $data['map_r'],
    'map_initial'   => $data['map_initial']   ?: null,
    // ── End GEM columns ─────────────────────────────────────────────────────


FIND the JSON-column null guard (inside the foreach loop building $writeData):

    if (in_array($column, ['best_time_json', 'route_notes_json', 'faq_json'], true) && $value === '') {

REPLACE WITH:

    if (in_array($column, ['best_time_json', 'route_notes_json', 'faq_json'], true) && $value === '') {

(No change needed — the existing guard already handles JSON columns.
 map_x / map_y can be null and that's fine.)

*/


/*
╔══════════════════════════════════════════════════════════════╗
║  B.  experience_save.php  — add category_id + day_arc_json  ║
╚══════════════════════════════════════════════════════════════╝

FIND in experience_save.php, the $data = [...] block (around line 5):

    'title'     => trim((string) post_input('title', '')),
    'slug'      => ...
    ...

ADD these two lines inside the array (after 'best_time'):

    // ── GEM redesign fields ──────────────────────────────
    'category_id'  => ($catId = (int) post_input('category_id', 0)) > 0 ? $catId : null,
    'day_arc_json' => '',    // built below
    // ── End GEM fields ───────────────────────────────────


ADD a new block AFTER the $data array definition, before old_input_set():

    // ── Build day_arc_json from the 4-segment repeater ──────────────────────
    $arcSegments = (array) post_input('day_arc', []);
    $arcDeleteImages = (array) post_input('day_arc_delete_image', []);
    $arcBuilt = [];
    $arcTimesOfDay = ['Morning', 'Midday', 'Afternoon', 'Evening'];
    foreach ($arcTimesOfDay as $i => $tod) {
        $seg = $arcSegments[$i] ?? [];
        $heading = trim((string)($seg['heading'] ?? ''));
        $body    = trim((string)($seg['body']    ?? ''));
        $icon    = trim((string)($seg['icon']    ?? ''));
        // Handle image: keep existing unless delete checkbox ticked
        $imgPath = trim((string)($seg['image_path'] ?? ''));
        if (!empty($arcDeleteImages[$i])) {
            $imgPath = '';
        }
        // Handle new upload
        if (!empty($_FILES['day_arc_images']['name'][$i])) {
            $file = [
                'name'     => $_FILES['day_arc_images']['name'][$i],
                'type'     => $_FILES['day_arc_images']['type'][$i],
                'tmp_name' => $_FILES['day_arc_images']['tmp_name'][$i],
                'error'    => $_FILES['day_arc_images']['error'][$i],
                'size'     => $_FILES['day_arc_images']['size'][$i],
            ];
            if ($file['error'] === UPLOAD_ERR_OK) {
                $uploaded = upload_image($file, 'experiences');
                if ($uploaded) { $imgPath = $uploaded; }
            }
        }
        if ($heading !== '' || $body !== '') {
            $arcBuilt[] = [
                'time_of_day' => $tod,
                'icon'        => $icon ?: '🕐',
                'heading'     => $heading,
                'body'        => $body,
                'image_path'  => $imgPath ?: null,
            ];
        }
    }
    $data['day_arc_json'] = $arcBuilt
        ? json_encode($arcBuilt, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        : '';
    // ── End day_arc_json ────────────────────────────────────────────────────


FIND the $writeData = array_merge($data, [...]) line and add to the merge array:

    'category_id'  => $data['category_id'],
    'day_arc_json' => $data['day_arc_json'] ?: null,

(The existing db_column_exists filter will ignore them on old installs.)

*/


/*
╔══════════════════════════════════════════════════════════════╗
║  C.  itinerary_save.php  — add traveller_types, start/end   ║
╚══════════════════════════════════════════════════════════════╝

FIND in itinerary_save.php, the $data = [...] block, after 'category_id':

    'excerpt'  => ...

ADD before 'excerpt':

    // ── GEM redesign fields ──────────────────────────────
    'traveller_types' => '',       // built below
    'start_point'     => trim((string) post_input('start_point', '')),
    'end_point'       => trim((string) post_input('end_point',   '')),
    // ── End GEM fields ───────────────────────────────────


ADD after the $data array (near the best_months block):

    // ── Build traveller_types CSV ────────────────────────────────────────────
    $validTypes = ['couple', 'family', 'honeymoon', 'solo', 'group'];
    // The hidden field 'traveller_types' already holds the CSV built by JS
    $travTypes = trim((string) post_input('traveller_types', ''));
    if ($travTypes !== '') {
        $parsed = array_filter(
            array_map('trim', explode(',', $travTypes)),
            fn($t) => in_array($t, $validTypes, true)
        );
        $data['traveller_types'] = implode(',', array_unique($parsed));
    }
    // ── End traveller_types ──────────────────────────────────────────────────


FIND $columnValueMap and ADD inside it:

    'traveller_types' => $data['traveller_types'] ?: null,
    'start_point'     => $data['start_point']     ?: null,
    'end_point'       => $data['end_point']        ?: null,


FIND best_months_json composition block and REPLACE with the same
structured-JSON approach used for destinations:

    // ── Build best_months_json from 12-month rating grid ────────────────────
    $bestMonthRatings = (array) post_input('best_time_ratings', []);
    $bestMonthsNote   = trim((string) post_input('best_time_note', ''));
    $bestMonthsArr    = [];
    $allMonths = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    $validRatings = ['great','okay','avoid'];
    foreach ($allMonths as $m) {
        $rating = strtolower(trim((string)($bestMonthRatings[$m] ?? '')));
        if (in_array($rating, $validRatings, true)) {
            $bestMonthsArr[] = ['month' => $m, 'rating' => $rating];
        }
    }
    if ($bestMonthsArr || $bestMonthsNote !== '') {
        $data['best_months_json'] = json_encode(
            ['months' => $bestMonthsArr, 'note' => $bestMonthsNote],
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
        );
    }
    // ── End best_months_json ────────────────────────────────────────────────

*/


/*
╔══════════════════════════════════════════════════════════════╗
║  D.  itinerary_days.php  — add highlights_json handling     ║
╚══════════════════════════════════════════════════════════════╝

FIND the save block (inside `if (is_post())`, after $travelTime = ...) :

    $currentImagePath = '';

ADD after $travelTime:

    // ── GEM: parse highlights_json_raw ──────────────────────────────────────
    $highlightsJson = null;
    if (db_column_exists('itinerary_days', 'highlights_json')) {
        $hlRaw = trim((string) post_input('highlights_json_raw', ''));
        if ($hlRaw !== '') {
            $hlDecoded = json_decode($hlRaw, true);
            if (is_array($hlDecoded)) {
                $hlClean = array_values(array_filter(
                    array_map(fn($t) => trim((string)$t), $hlDecoded)
                ));
                $highlightsJson = $hlClean
                    ? json_encode($hlClean, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
                    : null;
            }
        }
    }
    // ── End highlights ───────────────────────────────────────────────────────


FIND the UPDATE query inside `if ($dayId > 0)` and ADD to $sql and $params:

    // Inside the if ($dayId > 0) block, after the image_path conditional:
    if ($highlightsJson !== null && db_column_exists('itinerary_days', 'highlights_json')) {
        $sql .= ', highlights_json = :highlights_json';
        $params['highlights_json'] = $highlightsJson;
    }


FIND the INSERT block and ADD to $columns, $placeholders, $params:

    if ($highlightsJson !== null && db_column_exists('itinerary_days', 'highlights_json')) {
        $columns[]      = 'highlights_json';
        $placeholders[] = ':highlights_json';
        $params['highlights_json'] = $highlightsJson;
    }

*/
?>
