<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/image_manager.php';
if (!admin_check()) { redirect('admin/login.php?return=image_manager'); }
admin_require_auth();
header('Cache-Control: no-store');
header('X-Robots-Tag: noindex, nofollow');
$types = image_manager_types();
$type = is_string(get_input('type')) && isset($types[get_input('type')]) ? get_input('type') : 'destination';
$galleryOnly = get_input('section', '') === 'galleries';
if ($galleryOnly) { $type = 'destination'; }
$definition = $types[$type];
$id = max(0, (int) get_input('id', 0));
$q = mb_substr(trim((string) get_input('q', '')), 0, 120);
$page = max(1, min(100000, (int) get_input('page', 1)));
$record = null; $records = []; $more = false; $error = ''; $cards = []; $notices = [];
try {
    if ($id) {
        $record = image_manager_record($type, $id);
        $record['name']=$record[page_media_definition($type)[1]];
        if (!db_table_exists('media_assets')) { $notices[] = 'Media Library is unavailable in this database. Uploads and library selections require its existing schema; this portal does not install it.'; }
        foreach (['hero_image' => 'Hero', 'cover_image' => 'Cover / thumbnail', 'og_image' => 'OG / social sharing image'] as $slot => $label) {
            if (!db_column_exists($definition['table'], $slot)) { $notices[] = $label . ' is unavailable in this database.'; continue; }
            $cards[] = ['slot' => $slot, 'label' => $label, 'child_id' => 0];
        }
        if (in_array($type, ['destination', 'itinerary'], true) && db_column_exists($definition['table'], 'hero_video_poster')) {
            $cards[] = ['slot' => 'hero_video_poster', 'label' => 'Hero video poster', 'child_id' => 0];
        }
        if ($galleryOnly) { $cards = []; }
        $children = image_manager_children($type, $id);
        if ($type === 'tour') {
            require_once __DIR__ . '/../includes/tour_program.php';
            $program = tour_program_decode($record['program_json'] ?? null);
            foreach (['days'=>'program_day','hotels'=>'program_hotel'] as $group=>$programSlot) {
                foreach ($program[$group] as $index=>$row) { $cards[] = ['slot'=>$programSlot,'label'=>($group==='days'?'Day '.$row['day_start'].': ':'Stay: ').($row['title'] ?? $row['name']).' · '.$row['location'],'child_id'=>$index+1]; }
            }
            foreach ($children as $card) { $cards[] = ['slot' => 'coverage', 'label' => 'Coverage: ' . $card['title'] . ' · ' . $card['location'], 'child_id' => (int) $card['id']]; }
        }
        if ($type === 'day_tour') {
            foreach ($children as $photo) { $cards[] = ['slot' => 'day_tour_gallery', 'label' => 'Day tour gallery photo', 'child_id' => (int) $photo['id']]; }
        }
        if ($type === 'itinerary') {
            $covered = [];
            foreach ($children as $day) {
                $start = (int) $day['day_number']; $end = max($start, (int) ($day['day_end'] ?? $start));
                for ($d = max(1, $start); $d <= min(366, $end); $d++) { $covered[$d] = true; }
                $cards[] = ['slot' => 'day', 'label' => ($start === $end ? 'Day ' . $start : 'Days ' . $start . '–' . $end) . ': ' . $day['title'], 'child_id' => (int) $day['id']];
            }
            if (!db_table_exists('itinerary_days') || !db_column_exists('itinerary_days', 'image_path')) { $notices[] = 'Day images are unavailable in this database.'; }
            else {
                $missing = array_diff(range(1, max(1, min(366, (int) ($record['days'] ?? 1)))), array_keys($covered));
                if ($missing) { $notices[] = 'No day entry for: ' . implode(', ', $missing) . '. Add or adjust day entries in the full admin.'; }
            }
        }

    } else {
        $table = $definition['table'];
        if (!db_table_exists($table)) { throw new InvalidArgumentException('This content is unavailable in this database.'); }
        $titleColumn=page_media_definition($type)[1];
        $columns = ['id', $titleColumn.' AS name'];
        foreach (['hero_image', 'hero_media_type', 'hero_video_poster', 'is_published', 'days'] as $column) {
            if (db_column_exists($table, $column)) { $columns[] = $column; }
        }
        $where = $q !== '' ? ' WHERE '.$titleColumn.' LIKE :q' : '';
        $records = fetch_all('SELECT ' . implode(', ', $columns) . " FROM {$table}{$where} ORDER BY name, id LIMIT 25 OFFSET " . (($page - 1) * 24), $q !== '' ? ['q' => '%' . $q . '%'] : []);
        $more = count($records) > 24; $records = array_slice($records, 0, 24);
    }
} catch (InvalidArgumentException $exception) { $error = $exception->getMessage(); }
catch (Throwable $exception) { $error = 'Image Manager is temporarily unavailable.'; error_log('Image manager page: ' . $exception->getMessage()); }
$cards=array_values(array_filter($cards,static fn($card)=>!in_array($card['slot'],['hero_image','cover_image','og_image','hero_video_poster','day_tour_gallery'],true)));
require_once __DIR__.'/../includes/page_media_admin.php';
require_once __DIR__ . '/../includes/image_manager_view.php';
require_once __DIR__ . '/../includes/activity_pages.php';
require_once __DIR__ . '/../includes/activity_gallery_view.php';
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex,nofollow"><title>Image Manager | <?= e(APP_NAME) ?></title>
<link rel="stylesheet" href="<?= e(base_url('assets/css/image-manager.css') . '?v=' . filemtime(__DIR__ . '/../assets/css/image-manager.css')) ?>">
<link rel="stylesheet" href="<?= e(base_url('assets/css/typography.css') . '?v=' . filemtime(__DIR__ . '/../assets/css/typography.css')) ?>">
<script src="<?= e(base_url('assets/js/image-alt.js')) ?>?v=<?= filemtime(__DIR__.'/../assets/js/image-alt.js') ?>" defer></script>
<script src="<?= e(base_url('assets/js/image-manager.js') . '?v=' . filemtime(__DIR__ . '/../assets/js/image-manager.js')) ?>" defer></script>
<script src="<?= e(base_url('assets/js/destination-gallery.js') . '?v=' . filemtime(__DIR__ . '/../assets/js/destination-gallery.js')) ?>" defer></script>
<?php if($type==='activity'): ?><script src="<?= e(base_url('assets/js/activity-gallery-admin.js').'?v='.filemtime(__DIR__.'/../assets/js/activity-gallery-admin.js')) ?>" defer></script><?php endif; ?><?php page_media_admin_assets(); ?></head>
<body><main class="im-shell" data-manager data-api="<?= e(base_url('admin/image_manager_api.php')) ?>" data-image-limit="<?= image_manager_limit(false) ?>" data-video-limit="<?= image_manager_limit(true) ?>">
<header class="im-header"><div><p>Island Culture Travels · Administration</p><h1>Image Manager</h1></div><a href="<?= e(base_url('admin/dashboard.php')) ?>">Full admin</a></header>
<nav class="im-tabs" aria-label="Content type"><?php foreach ($types as $key => $value): ?><a <?= $key === $type && !$galleryOnly ? 'aria-current="page"' : '' ?> href="<?= e(base_url('admin/image_manager.php?type=' . $key)) ?>"><?= e($value['label']) ?></a><?php endforeach; ?><a <?= $galleryOnly ? 'aria-current="page"' : '' ?> href="<?= e(base_url('admin/image_manager.php?section=galleries')) ?>">Destination Galleries</a></nav>
<?php if ($error): ?><p role="alert" class="im-notice"><?= e($error) ?></p>
<?php elseif (!$record): ?>
<form class="im-search" method="get"><?php if ($galleryOnly): ?><input type="hidden" name="section" value="galleries"><?php endif; ?><input type="hidden" name="type" value="<?= e($type) ?>"><label for="record-search">Find <?= e(strtolower($definition['label'])) ?></label><div><input id="record-search" name="q" value="<?= e($q) ?>" maxlength="120" type="search"><button>Search</button></div></form>
<div class="im-records"><?php foreach ($records as $row): ?><a class="im-record" href="<?= e(base_url('admin/image_manager.php?type=' . $type . '&section=' . ($galleryOnly ? 'galleries' : '') . '&id=' . (int) $row['id'])) ?>">
<?php if (!empty($row['hero_image']) && (($row['hero_media_type'] ?? '') !== 'video' || !empty($row['hero_video_poster']))): ?><img src="<?= e(stored_media_url(($row['hero_media_type'] ?? '') === 'video' ? $row['hero_video_poster'] : $row['hero_image'])) ?>" loading="lazy" alt=""><?php else: ?><span class="im-thumb"><?= ($row['hero_media_type'] ?? '') === 'video' ? 'Video hero' : 'No image' ?></span><?php endif; ?>
<span><strong><?= e($row['name']) ?></strong><small><?= array_key_exists('is_published', $row) ? ((int) $row['is_published'] ? 'Published' : 'Draft') : 'Status unavailable' ?><?= $type === 'itinerary' && isset($row['days']) ? ' · ' . (int) $row['days'] . ' days' : '' ?></small></span><span aria-hidden="true">→</span></a><?php endforeach; ?></div>
<?php if (!$records): ?><p>No matching records.</p><?php endif; ?>
<nav class="im-pagination" aria-label="Record pages"><?php if ($page > 1): ?><a href="<?= e(base_url('admin/image_manager.php?' . http_build_query(['type' => $type, 'section' => $galleryOnly ? 'galleries' : '', 'q' => $q, 'page' => $page - 1]))) ?>">Previous</a><?php endif; ?><span>Page <?= $page ?></span><?php if ($more): ?><a href="<?= e(base_url('admin/image_manager.php?' . http_build_query(['type' => $type, 'section' => $galleryOnly ? 'galleries' : '', 'q' => $q, 'page' => $page + 1]))) ?>">Next</a><?php endif; ?></nav>
<?php else: ?>
<section class="im-intro"><a href="<?= e(base_url('admin/image_manager.php?type=' . $type . '&section=' . ($galleryOnly ? 'galleries' : ''))) ?>">← Choose another <?= e($type === 'day_tour' ? 'day tour package' : $type) ?></a><h2><?= e($record['name']) ?></h2><?php if(in_array($type,['itinerary','tour','day_tour'],true)): ?><p><a href="<?= e(base_url('admin/package_fields.php?type=' . $type . '&id=' . $id)) ?>">Review all package fields and editors</a></p><?php endif; ?>
<p><?= !empty($record['is_published']) ? 'Published: saved replacements and selected public gallery photos appear immediately. Library uploads do not appear publicly.' : 'Draft: saving images does not publish this record.' ?> Text, prices and day details stay unchanged.</p>
<p>Upload limits: images <?= round(image_manager_limit(false) / 1048576, 1) ?> MB; video heroes <?= round(image_manager_limit(true) / 1048576, 1) ?> MB. HEIC: convert to JPG first. Social sites may cache OG previews.</p>
<a href="<?= e(base_url('admin/' . $definition['form'] . '?id=' . $id)) ?>">Edit detailed information in full admin</a></section>
<?php foreach ($notices as $notice): ?><p class="im-notice"><?= e($notice) ?></p><?php endforeach; ?>
<noscript><p class="im-notice">Enable JavaScript to preview and save individual images in this portal.</p></noscript>
<?php page_media_manager($type,$id); ?>
<div class="im-grid"><?php foreach ($cards as $card) { image_manager_card($type, $record, $card); } ?></div>
<?php if($type==='activity'){activity_gallery_manager($record);} ?>
<?php if ($type === 'destination'): ?>
<section data-gallery data-id="<?= $id ?>" data-token="<?= e(csrf_token()) ?>">
<h2>Destination photos and public gallery</h2><p>Upload photos to the destination Media Library first, then select which photos belong in the public gallery. Choose photos for <?= e($record['name']) ?>. Captions are also public image descriptions. Removing a photo retains its file and Media Library asset.</p>
<?php if (image_manager_destination_library_available()): ?>
<form data-gallery-queue class="im-card"><h3>Upload to destination Media Library</h3><p>These uploads stay in the Media Library until you explicitly select them for a public image or gallery.</p><label>Select photos<input type="file" multiple accept="image/jpeg,image/png,image/webp,image/avif,image/gif" data-queue-files></label><div data-queue-items class="im-grid"></div><button type="submit">Upload selected photos</button><p data-queue-status role="status"></p></form>
<h3>Destination Media Library</h3><p>Manage the public gallery using Page images and gallery above.</p>
<div data-gallery-current></div>
<?php else: ?><p class="im-notice">Destination uploads require the existing Media Library destination column. This portal does not install schema.</p><?php endif; ?>
</section>
<?php endif; ?>
<?php endif; ?>
</main>
<dialog data-library-dialog aria-labelledby="library-title"><div class="im-dialog-header"><h2 id="library-title">Media Library</h2><button type="button" data-close>Close</button></div>
<form data-library-search class="im-search"><label for="library-search">Search filenames</label><div><input id="library-search" type="search" maxlength="120"><button>Search</button></div></form>
<label data-destination-filter-label>Browse by destination<select data-destination-filter><option value="">All destinations / all media</option>
<?php if (db_table_exists('destinations') && db_table_exists('media_assets') && db_column_exists('media_assets', 'destination_id')): foreach (fetch_all('SELECT id, name FROM destinations ORDER BY name, id') as $libraryDestination): ?><option value="<?= (int) $libraryDestination['id'] ?>"><?= e($libraryDestination['name']) ?></option><?php endforeach; endif; ?>
</select></label>
<p data-library-status role="status"></p><div data-library-items class="im-library-grid"></div><div class="im-pagination"><button data-prev type="button">Previous</button><span data-library-page></span><button data-next type="button">Next</button></div></dialog>
</body></html>
