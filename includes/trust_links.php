<?php
declare(strict_types=1);
require_once __DIR__ . '/published_page_links.php';
$trustLinks=booking_page_links_excluding(published_booking_page_links(),[(string)($seoCanonical ?? '')]);
if (!$trustLinks) { return; }
?>
<section class="py-4 border-top"><div class="container" style="max-width: 900px;">
<nav aria-label="Booking information"><h2 class="h4">Booking and travel information</h2><div class="d-flex flex-wrap gap-3">
<?php foreach ($trustLinks as $trustLink): ?><a href="<?= e($trustLink['url']) ?>"><?= e($trustLink['label']) ?></a><?php endforeach; ?>
</div></nav></div></section>
