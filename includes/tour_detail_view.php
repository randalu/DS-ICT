<?php
declare(strict_types=1);
require_once __DIR__ . '/route_map_view.php';
$coverageItems = tour_coverage_public_items($tour['coverage_json'] ?? null);
$tourQuoteUrl = base_url('contact?tour=' . rawurlencode($tourName));
$tourWhatsappUrl = site_whatsapp_url('Hi, I am interested in the ' . $tourName . ' tour.');
$hasServices = $includesHtml !== '' || $excludesHtml !== '' || !empty($tour['vehicle_options']) || !empty($tour['pricing_notes']);
$program = tour_program_decode($tour['program_json'] ?? null);
$hasRoute = $program['route'] !== '';
$hasProgram = $program['route'] !== '' || $program['days'] || $program['hotels'] || $program['sections'];
$legacyRoute = !$hasProgram && ($itineraryText !== '' || trim((string)($tour['sample_itinerary'] ?? '')) !== '');
$programmeLastDay = $program['days'] ? max(array_column($program['days'], 'day_end')) : 0;
$hasOverview = !empty($tour['intro']) || !empty($tour['highlights']);
$tourSections = ['tour-overview' => 'Overview'];
if ($coverageItems) { $tourSections['tour-coverage'] = 'Places & Experiences'; }
if ($hasServices) { $tourSections['tour-services'] = 'Inclusions'; }
if ($hasRoute || $legacyRoute) { $tourSections['tour-route'] = 'Route Plan'; }
if ($program['days']) { $tourSections['tour-days'] = 'Daily Plan'; }
if ($program['hotels']) { $tourSections['tour-hotels'] = 'Stays'; }
if ($program['sections']) { $tourSections['tour-planning'] = 'Travel Details'; }
if ($faqItems) { $tourSections['tour-faq'] = 'FAQs'; }
?>
<section class="journey-hero">
    <div class="journey-hero-photo"><?= responsive_image(['src' => $heroImage, 'alt' => $tourName, 'widths' => [800, 1200, 1600], 'sizes' => '100vw', 'width' => 1600, 'height' => 1000, 'loading' => 'eager', 'fetchpriority' => 'high']) ?></div>
    <div class="container journey-hero-content">
        <?php require __DIR__ . '/breadcrumbs.php'; ?>
        <span class="section-eyebrow">Sri Lanka Private Tour</span>
        <h1><?= e($tourName) ?></h1>
        <?php if ($tourExcerpt !== ''): ?><p class="journey-hero-lead"><?= e($tourExcerpt) ?></p><?php endif; ?>
        <?php if ($programmeLastDay > 0): ?><div class="journey-hero-facts"><span><?= (int)$programmeLastDay ?>-day proposed route</span><span>Private and customisable</span></div><?php endif; ?>
        <?php if ($priceLabel !== ''): ?><p class="journey-hero-price"><span>Indicative price</span><?= e($priceLabel) ?></p><?php endif; ?>
        <div class="journey-actions"><a href="<?= e($tourQuoteUrl) ?>" class="btn btn-primary" onclick="if(typeof trackEvent==='function')trackEvent('contact_cta_click',{label:'tour_hero'})">Customise This Tour</a><a href="<?= e($tourWhatsappUrl) ?>" class="btn btn-whatsapp" target="_blank" rel="noopener" onclick="if(typeof trackEvent==='function')trackEvent('whatsapp_click',{label:'tour_hero'})">WhatsApp</a></div>
    </div>
</section>
<nav class="journey-section-nav" aria-label="On this tour"><div class="container"><?php foreach ($tourSections as $anchor => $label): ?><a href="#<?= e($anchor) ?>"><?= e($label) ?></a><?php endforeach; ?></div></nav>
<div class="container journey-tour-grid">
    <div class="journey-tour-main">
        <section class="journey-section" id="tour-overview"><div class="package-overview-map"><div>
            <span class="section-eyebrow">Your private journey</span><h2>A closer look at this tour</h2>
            <?php if (!empty($tour['intro'])): ?><div class="content-prose"><?= render_public_content((string) $tour['intro']) ?></div><?php endif; ?>
            <?php if (!empty($tour['highlights'])): ?><div class="journey-highlights"><h3>Tour Highlights</h3><div class="content-prose"><?= render_public_content((string) $tour['highlights']) ?></div></div><?php endif; ?>
        </div><?= route_map_public('tour', $tour) ?></div></section>
        <?php if ($coverageItems): ?>
        <section class="journey-section" id="tour-coverage">
            <span class="section-eyebrow">Places &amp; experiences</span><h2>What you will explore</h2>
            <div class="journey-coverage-list">
                <?php foreach ($coverageItems as $number => $coverage): ?>
                <article class="journey-coverage-card<?= $coverage['image_url'] === '' ? ' journey-coverage-card--text' : '' ?>">
                    <?php if ($coverage['image_url'] !== ''): ?><div class="journey-coverage-photo"><?= responsive_image(['src' => $coverage['image_url'], 'alt' => $coverage['image_alt'] ?: $coverage['title'] . ' at ' . $coverage['location'], 'widths' => [400, 800], 'sizes' => '(min-width: 992px) 400px, 100vw', 'width' => 800, 'height' => 900, 'loading' => 'lazy']) ?></div><?php endif; ?>
                    <div class="journey-coverage-copy">
                        <span class="journey-coverage-number"><?= sprintf('%02d', $number + 1) ?></span>
                        <span class="journey-coverage-status<?= ($coverage['status'] ?? '') === 'optional' ? ' is-optional' : '' ?>"><?= ($coverage['status'] ?? '') === 'optional' ? 'Optional addition' : 'Part of proposed tour' ?></span>
                        <h3><?= e($coverage['title']) ?></h3><p class="journey-location"><?= e($coverage['location']) ?></p>
                        <p><?= nl2br(e($coverage['description'])) ?></p>
                        <?php if ($coverage['links']): ?><div class="journey-coverage-links"><?php foreach ($coverage['links'] as $link): ?><a href="<?= e($link['url']) ?>"><?= e($link['label']) ?> <span aria-hidden="true">↗</span></a><?php endforeach; ?></div><?php endif; ?>
                    </div>
                </article>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>
        <?php if ($hasProgram) { require __DIR__ . '/tour_program_view.php'; } ?>
        <?php if ($legacyRoute): ?><section class="journey-section" id="tour-route"><span class="section-eyebrow">Your route</span><h2>Route plan</h2><?php foreach ([$itineraryText, (string)($tour['sample_itinerary'] ?? '')] as $routeNote): if (trim($routeNote) === '') { continue; } ?><div class="content-prose"><?= render_public_content($routeNote) ?></div><?php endforeach; ?></section><?php endif; ?>
        <?php if ($hasServices): ?>
        <section class="journey-section" id="tour-services"><span class="section-eyebrow">The details</span><h2>What we provide</h2>
            <div class="journey-inclusions">
                <?php if ($includesHtml !== ''): ?><div><h3>Included</h3><div class="content-prose"><?= $includesHtml ?></div></div><?php endif; ?>
                <?php if ($excludesHtml !== ''): ?><div><h3>Not Included</h3><div class="content-prose"><?= $excludesHtml ?></div></div><?php endif; ?>
            </div>
            <?php foreach (['vehicle_options' => 'Vehicle Options', 'pricing_notes' => 'Pricing Notes'] as $field => $label): if (empty($tour[$field])) { continue; } ?><div class="journey-service-note"><h3><?= e($label) ?></h3><div class="content-prose"><?= render_public_content((string) $tour[$field]) ?></div></div><?php endforeach; ?>
        </section>
        <?php endif; ?>
        <section class="journey-customise"><span class="section-eyebrow">Make it yours</span><h2>A tour shaped around you</h2><p>Tell us your dates, group size and preferred pace to discuss changes to this tour.</p><a class="btn btn-primary" href="<?= e($tourQuoteUrl) ?>">Discuss Your Tour</a></section>
        <?php if ($bestTimeBlock !== ''): ?><section class="journey-section" id="tour-season"><?= $bestTimeBlock ?></section><?php endif; ?>

        <?php if ($faqItems): ?><section class="journey-section" id="tour-faq"><h2>Frequently Asked Questions</h2><?php foreach ($faqItems as $faq): ?><details class="journey-disclosure"><summary><?= e($faq['question']) ?></summary><p><?= nl2br(e($faq['answer'])) ?></p></details><?php endforeach; ?></section><?php endif; ?>
    </div>
    <aside class="journey-enquiry" aria-label="Plan this private tour"><span class="section-eyebrow">Your dates. Your pace.</span><h2><?= e($tourCtaTitle) ?></h2><p><?= e($tourCtaText ?: 'Share your dates, group size, and preferred pace. We will tailor this tour into a private route.') ?></p><?php if ($priceLabel !== ''): ?><div class="journey-enquiry-price"><span>Indicative price</span><strong><?= e($priceLabel) ?></strong></div><?php endif; ?><a class="btn btn-primary" href="<?= e($tourQuoteUrl) ?>">Customise This Tour</a><a class="btn btn-whatsapp" href="<?= e($tourWhatsappUrl) ?>" target="_blank" rel="noopener">WhatsApp Us</a></aside>
</div>
<?php if ($relatedDestinations): ?><section class="journey-related"><div class="container"><span class="section-eyebrow">Discover the places</span><h2>Related destinations</h2><div class="journey-related-grid"><?php foreach ($relatedDestinations as $dest): ?><a class="card journey-related-card" data-lightbox-ignore href="<?= e(base_url('destinations/' . $dest['slug'])) ?>"><?= responsive_image(['src' => stored_media_url(content_image_path($dest)), 'alt' => $dest['name'], 'widths' => [400, 800], 'sizes' => '(min-width: 992px) 33vw, 100vw', 'width' => 800, 'height' => 600, 'loading' => 'lazy']) ?><div class="card-body"><h3><?= e($dest['name']) ?></h3><?php if (!empty($dest['excerpt'])): ?><p><?= e(excerpt_words($dest['excerpt'], 24)) ?></p><?php endif; ?></div></a><?php endforeach; ?></div></div></section><?php endif; ?>
<?php foreach (['tours' => ['More private tours', $relatedTours]] as $path => [$heading, $items]): if (!$items) { continue; } ?>
<section class="journey-related"><div class="container"><span class="section-eyebrow"><?= $path === 'itineraries' ? 'Customise the daily flow' : 'Keep exploring' ?></span><h2><?= e($heading) ?></h2><div class="journey-related-grid"><?php foreach ($items as $item): ?><a class="card journey-related-card" data-lightbox-ignore href="<?= e(base_url($path . '/' . $item['slug'])) ?>"><?= responsive_image(['src' => package_card_image($item, $path), 'alt' => package_card_alt($item, $path), 'widths' => [400, 800], 'sizes' => '(min-width: 992px) 33vw, 100vw', 'width' => 800, 'height' => 600, 'loading' => 'lazy']) ?><div class="card-body"><?php if (!empty($item['days'])): ?><span class="section-eyebrow"><?= (int) $item['days'] ?> days</span><?php endif; ?><h3><?= e($item['name']) ?></h3><p><?= e(excerpt_words((string) ($item['excerpt'] ?? ''), 22)) ?></p><?php $itemPrice = format_price_range($item); if ($itemPrice !== ''): ?><p class="card-price"><?= e($itemPrice) ?></p><?php endif; ?></div></a><?php endforeach; ?></div></div></section>
<?php endforeach; ?>
<div class="journey-mobile-enquiry" aria-label="Tour enquiry"><a class="btn btn-primary" href="<?= e($tourQuoteUrl) ?>">Customise This Tour</a><a class="btn btn-whatsapp" href="<?= e($tourWhatsappUrl) ?>" target="_blank" rel="noopener">WhatsApp</a></div>

<?php page_gallery_assets();page_gallery_public($pageGallery,$tourName); ?>
