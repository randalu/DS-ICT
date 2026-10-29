<?php
declare(strict_types=1);
require_once __DIR__ . '/quotations.php';
require_once dirname(__DIR__) . '/vendor/autoload.php';

/** Only project-owned raster media; paths, URLs, SVG and arbitrary wrappers are never rendered. */
function quote_image_bytes(string $path): ?string
{
    if ($path === '') { return null; }
    $path = str_replace('\\', '/', $path);
    if (str_starts_with($path, APP_URL . '/')) { $path = substr($path, strlen(APP_URL) + 1); }
    $path = ltrim($path, '/');
    if (!preg_match('~^assets/(?:uploads|images)/[^\x00]+\.(?:jpe?g|png|webp)$~i', $path) || str_contains($path, '..') || str_contains($path, ':')) { throw new InvalidArgumentException('Choose a local raster image from the media library.'); }
    $root = realpath(dirname(__DIR__) . '/assets'); $file = realpath(dirname(__DIR__) . '/' . $path);
    if (!$file) { return null; }
    if (!$root || !str_starts_with(strtolower(str_replace('\\', '/', $file)), strtolower(str_replace('\\', '/', $root)) . '/') || filesize($file) > 12000000) { throw new InvalidArgumentException('Image is outside the media boundary or too large.'); }
    $size = @getimagesize($file);
    if (!$size || $size[0] * $size[1] > 24000000 || !in_array($size[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true)) { throw new InvalidArgumentException('Unsupported or oversized image.'); }
    $source = @imagecreatefromstring((string) file_get_contents($file));
    if (!$source) { throw new InvalidArgumentException('Could not decode the selected image.'); }
    $ratio = min(1, 1200 / $size[0], 900 / $size[1]); $w = max(1, (int) ($size[0] * $ratio)); $h = max(1, (int) ($size[1] * $ratio));
    $target = imagecreatetruecolor($w, $h); imagefill($target, 0, 0, imagecolorallocate($target, 255, 255, 255)); imagecopyresampled($target, $source, 0, 0, 0, 0, $w, $h, $size[0], $size[1]);
    ob_start(); imagejpeg($target, null, 82); $bytes = (string) ob_get_clean(); imagedestroy($source); imagedestroy($target);
    return 'data:image/jpeg;base64,' . base64_encode($bytes);
}
function quote_assets(array $document): array
{
    $assets = [];
    $paths = [$document['cover_image'], 'assets/images/island_culture_travels_logo.png'];
    foreach ($document['days'] as $day) { $paths[] = $day['image']; }
    foreach (array_unique(array_filter($paths)) as $path) { $assets[$path] = quote_image_bytes($path); }
    if (strlen(quote_json($assets)) > 24000000) { throw new InvalidArgumentException('Too many quotation images; use fewer images.'); }
    return $assets;
}
function quote_day_label(array $day): string { return 'Day ' . $day['start'] . ($day['end'] !== $day['start'] ? ' - ' . $day['end'] : ''); }
function quote_status_label(string $status): string { return match ($status) { 'included' => 'Included', 'optional' => 'Optional - extra charge', default => 'Not included' }; }
function quote_pdf_html(array $row, array $assets): string
{
    $d = $row['document']; $active = array_filter($d['options'], static fn($o) => $o['enabled']);
    $image = static function (string $path, string $class, string $alt) use ($assets): string {
        $data = $assets[$path] ?? null;
        return $data ? '<img class="' . $class . '" src="' . $data . '" alt="' . e($alt) . '">' : '<div class="image-fallback">ISLAND CULTURE TRAVELS<br>Private Sri Lanka journeys</div>';
    };
    ob_start(); ?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><title><?= e($row['reference']) ?></title><style>
@page{margin:60px 42px 48px}
body{font-family:DejaVu Sans,sans-serif;color:#173b42;font-size:12px;line-height:1.3}
h1{font-size:22px;line-height:1.15;margin:5px 0 7px}h2{font-size:14px;color:#0b6268;margin:12px 0 5px}h3{font-size:12px;margin:5px 0 3px}p{margin:4px 0}
h2,h3,.section-title{page-break-after:avoid}.plan{white-space:pre-wrap}.muted{color:#536d72}
table{width:100%;border-collapse:collapse;margin:6px 0}th,td{border:1px solid #d8e4e2;padding:5px 7px;text-align:left;vertical-align:top;word-wrap:break-word}th{background:#e8f2f0}tr{page-break-inside:avoid}
.masthead{margin:0 0 7px}.masthead td{border:0;padding:0 12px 0 0}.masthead .cover-cell{width:130px;padding:0;text-align:right}.logo{width:85px}.cover{width:130px;height:auto;max-height:100px}
.image-fallback{background:#0b6268;color:#fff;padding:12px;font-size:11px}.summary{background:#ecf5f3;padding:8px 10px;margin:7px 0}.price{font-size:18px;font-weight:bold}.price-note{font-size:11px}
.day{border-top:1px solid #d9b64f;padding-top:6px;margin-top:10px}.day-heading{page-break-inside:avoid;page-break-after:avoid}.day h2{margin:0 0 5px}.day-image{float:right;width:125px;height:auto;max-height:90px;margin:0 0 6px 12px}.day:after{content:"";display:block;clear:both}.day-heading .image-fallback{float:right;width:101px;margin:0 0 6px 12px}
.experience,.hotel,.closing{page-break-inside:avoid}.experience{margin:3px 0 5px}.experience-status{font-size:11px}.services{font-size:11px;line-height:1.25}.services th,.services td{padding:4px 6px}.services .service-status{width:22%}
.included{color:#126346}.optional{color:#84590c}.not_included{color:#a23c3c}.signoff{white-space:pre-wrap;border-top:1px solid #d9b64f;padding-top:6px;margin-top:7px}.draft{color:#a23c3c}
</style></head><body>
<table class="masthead"><tr><td><?php if (!empty($assets['assets/images/island_culture_travels_logo.png'])): ?><img class="logo" src="<?= $assets['assets/images/island_culture_travels_logo.png'] ?>" alt="Island Culture Travels"><?php endif; ?><h1><?= e($d['title']) ?></h1></td><td class="cover-cell"><?= $image($d['cover_image'], 'cover', $d['title']) ?></td></tr></table>
<p><?= nl2br(e($d['intro'])) ?></p>
<div class="summary"><strong>Prepared for <?= e($d['customer']['name']) ?></strong><br><?= e($d['customer']['phone']) ?> <?= e($d['customer']['email']) ?><br>
<?= (int) $d['customer']['adults'] ?> adults · <?= (int) $d['customer']['children'] ?> children <?= $d['customer']['ages'] !== '' ? '(ages: ' . e($d['customer']['ages']) . ')' : '' ?><br>
Arrival: <?= e(str_replace('T', ' ', $d['arrival'])) ?> · Departure: <?= e(str_replace('T', ' ', $d['departure'])) ?><br>Vehicle: <?= e($d['vehicle']) ?>
<?php if ($d['valid_until'] !== ''): ?><br>Quotation valid until <?= e($d['valid_until']) ?><?php endif; ?></div>
<h2 class="section-title">Your quotation options</h2><table><tr><?php foreach ($active as $key => $o): ?><th>Option <?= $key ?> · <?= e($o['label']) ?></th><?php endforeach; ?></tr><tr>
<?php foreach ($active as $key => $o): ?><td><?php try { $total = quote_option_total($d, $key); } catch (InvalidArgumentException) { $total = null; } ?>
<div class="price"><?= e($d['currency']) ?> <?= $total === null ? 'Price to complete' : quote_money($total) ?></div><span class="price-note">Whole-group total</span>
<?php if ($o['basis'] === 'person'): ?><br>Adults: <?= (int) $d['customer']['adults'] ?> × <?= e($o['adult_rate']) ?><br>Children: <?= (int) $d['customer']['children'] ?> × <?= e($o['child_rate'] ?: '0.00') ?><?php endif; ?>
<br>Hotels: <?= $key === 'A' ? 'Included as specified below' : 'Not included' ?>
<?php if ($o['deposit'] !== '' && $total !== null): ?><br>Deposit: <?= e($d['currency']) ?> <?= quote_money(quote_cents($o['deposit'])) ?> · Balance: <?= e($d['currency']) ?> <?= quote_money($total - quote_cents($o['deposit'])) ?><?php endif; ?></td><?php endforeach; ?></tr></table>
<?php foreach ($d['days'] as $day): ?><div class="day"><div class="day-heading"><?= $image($day['image'], 'day-image', $day['title']) ?><h2><?= e(quote_day_label($day)) ?> · <?= e($day['title']) ?></h2><?php if ($day['date'] !== ''): ?><p class="muted"><?= e($day['date']) ?></p><?php endif; ?></div>
<h3>Travel plan</h3><div class="plan"><?= e($day['plan']) ?></div><p class="muted">Overnight: <?= e($day['overnight'] ?: 'Not specified') ?><?= $day['distance'] !== '' ? ' · Approx. distance: ' . e($day['distance']) : '' ?><?= $day['travel_time'] !== '' ? ' · Approx. travel time: ' . e($day['travel_time']) : '' ?></p>
<?php if ($day['activities']): ?><h3>Experiences covered</h3><?php foreach ($day['activities'] as $a): ?><p class="experience"><strong><?= e($a['name']) ?></strong><?= $a['description'] !== '' ? ' — ' . nl2br(e($a['description'])) : '' ?><br><span class="experience-status"><?php foreach ($active as $key => $o): ?><span class="<?= $a['status'][$key] ?>">Option <?= $key ?>: <?= e(quote_status_label($a['status'][$key])) ?></span> &nbsp; <?php endforeach; ?></span></p><?php endforeach; ?><?php endif; ?></div><?php endforeach; ?>
<?php if (($d['options']['A']['enabled'] ?? false) && $d['hotels']): ?><h2 class="section-title">Hotel arrangements · Option A</h2><?php foreach ($d['hotels'] as $h): ?><p class="hotel"><strong><?= e($h['name']) ?></strong><?= $h['alternative'] !== '' ? ' or ' . e($h['alternative']) : '' ?> · <?= e($h['location']) ?><br>From Day <?= (int) $h['day'] ?> · <?= (int) $h['nights'] ?> night(s) · <?= e($h['star']) ?> · Room: <?= e($h['room']) ?><br>Occupancy: <?= e($h['occupancy']) ?> · Meals: <?= e($h['meals']) ?></p><?php endforeach; ?><?php endif; ?>
<h2 class="section-title">Inclusions and service comparison</h2><table class="services"><thead><tr><th>Service</th><?php foreach ($active as $key => $o): ?><th class="service-status">Option <?= $key ?></th><?php endforeach; ?></tr></thead><tbody><?php foreach ($d['inclusions'] as $i): ?><tr><td><?= e($i['name']) ?></td><?php foreach ($active as $key => $o): ?><td class="<?= $i['status'][$key] ?>"><?= e(quote_status_label($i['status'][$key])) ?></td><?php endforeach; ?></tr><?php endforeach; ?></tbody></table>
<?php if ($d['exclusions'] !== ''): ?><h2>Additional exclusions</h2><div class="plan"><?= e($d['exclusions']) ?></div><?php endif; ?>
<div class="closing"><?php if ($d['payment_notes'] !== ''): ?><h2>Payment schedule and notes</h2><div class="plan"><?= e($d['payment_notes']) ?></div><?php endif; ?>
<p class="muted">This quotation is a proposal. Booking confirmation is issued separately after agreement and the required checks.</p><div class="signoff"><?= e($d['footer']) ?></div></div></body></html>
<?php return (string) ob_get_clean();
}
function quote_render_pdf(array $row, array $assets): string
{
    $temp = sys_get_temp_dir() . '/ict-quotation-' . substr(hash('sha256', dirname(__DIR__)), 0, 16);
    if (!is_dir($temp) && !mkdir($temp, 0700)) { throw new RuntimeException('Private PDF cache is unavailable.'); }
    $real = realpath($temp); $public = realpath(dirname(__DIR__));
    if (!$real || !$public || is_link($temp) || str_starts_with(strtolower($real), strtolower($public))) { throw new RuntimeException('PDF cache must be outside the application directory.'); }
    if (PHP_OS_FAMILY !== 'Windows' && (fileperms($real) & 0077) !== 0) { throw new RuntimeException('PDF cache requires private permissions.'); }
    $options = new Dompdf\Options(['isRemoteEnabled' => false, 'isPhpEnabled' => false, 'isJavascriptEnabled' => false, 'chroot' => [$real], 'tempDir' => $real, 'fontCache' => $real]);
    $pdf = new Dompdf\Dompdf($options); $pdf->setPaper('A4'); $pdf->loadHtml(quote_pdf_html($row, $assets), 'UTF-8'); $pdf->render();
    $pdf->getCanvas()->page_script(static function (int $number, int $count, $canvas, $fonts) use ($row): void {
        $font = $fonts->getFont('Helvetica');
        $canvas->text(31, 22, 'ISLAND CULTURE TRAVELS - ' . $row['reference'] . ' - R' . $row['revision'] . ($row['state'] === 'draft' ? ' - DRAFT' : ''), $font, 7, [0.1, 0.3, 0.33]);
        $canvas->line(31, 37, 564, 37, [0.85, 0.71, 0.31], 0.7);
        $canvas->text(31, 816, 'Island Culture Travels - Private Sri Lanka journeys', $font, 6, [0.3, 0.4, 0.4]);
        $canvas->text(530, 816, $number . ' / ' . $count, $font, 7, [0.3, 0.4, 0.4]);
    });
    return $pdf->output();
}
function quote_issue(int $id, int $version, int $admin): array
{
    $row = quote_get($id); if ($row['state'] !== 'draft' || (int) $row['lock_version'] !== $version) { throw new DomainException('Reload the latest draft before issuing.'); }
    quote_validate_issue($row['document']); $assets = quote_assets($row['document']);
    $row['state'] = 'issued'; $bytes = quote_render_pdf($row, $assets);
    return ict_confirmation_atomic(static function () use ($id, $version, $row, $assets, $bytes, $admin): array {
        $current = quote_get($id, true);
        if ($current['state'] !== 'draft' || (int) $current['lock_version'] !== $version || $current['document_json'] !== $row['document_json']) { throw new DomainException('The draft changed during PDF preparation. Review and issue again.'); }
        execute_query("UPDATE quotation_revisions SET state='issued',assets_json=:assets,pdf_bytes=:pdf,pdf_sha256=:hash,issued_at=:now,lock_version=lock_version+1 WHERE id=:id", ['assets' => quote_json($assets), 'pdf' => $bytes, 'hash' => hash('sha256', $bytes), 'now' => quote_now(), 'id' => $id]);
        quote_event($id, $admin, 'issued', ['sha256' => hash('sha256', $bytes)]); return ['id' => $id, 'version' => $version + 1];
    });
}
