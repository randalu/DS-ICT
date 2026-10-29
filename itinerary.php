<?php
/**
 * itinerary.php — GEM redesign
 * Visual day-by-day timeline with sticky sidebar and native day disclosures,
 * related destinations, experiences, suggested itineraries, FAQ.
 */

require_once __DIR__ . '/config/bootstrap.php';
require_once __DIR__ . '/includes/structured_sections.php';
require_once __DIR__ . '/includes/responsive_image.php';
require_once __DIR__ . '/includes/route_map_view.php';

// ── Load itinerary ────────────────────────────────────────────────────────────
$slug = trim((string) get_input('slug', ''));
$id   = (int) get_input('id', 0);

if (!db_table_exists('itineraries')) {
    http_response_code(404);
    require __DIR__ . '/404.php';
    exit;
}

$publishedWhere = db_column_exists('itineraries', 'is_published') ? ' AND is_published = 1' : '';

$itinerary = null;
if ($slug !== '') {
    $itinerary = fetch_one(
        "SELECT * FROM itineraries WHERE slug = :slug {$publishedWhere} LIMIT 1",
        ['slug' => $slug]
    );
}
if (!$itinerary && $id > 0) {
    $itinerary = fetch_one(
        "SELECT * FROM itineraries WHERE id = :id {$publishedWhere} LIMIT 1",
        ['id' => $id]
    );
}
if (!$itinerary) {
    http_response_code(404);
    require __DIR__ . '/404.php';
    exit;
}

// ── Column guards ─────────────────────────────────────────────────────────────
$_hasDayEnd       = db_column_exists('itinerary_days', 'day_end');
$_hasDayImage     = db_column_exists('itinerary_days', 'image_path');
$_hasHighlights   = db_column_exists('itinerary_days', 'highlights_json');
$_hasBestMonths   = db_column_exists('itineraries',    'best_months_json');
$_hasTraveller    = db_column_exists('itineraries',    'traveller_types');
$_hasStartPoint   = db_column_exists('itineraries',    'start_point');

// ── Category ──────────────────────────────────────────────────────────────────
$category = null;
if (!empty($itinerary['category_id']) && db_table_exists('itinerary_categories')) {
    $category = fetch_one(
        "SELECT id, name, slug FROM itinerary_categories WHERE id = :id LIMIT 1",
        ['id' => $itinerary['category_id']]
    );
}

// ── Days ──────────────────────────────────────────────────────────────────────
$_dayEndExpr    = $_hasDayEnd ? 'COALESCE(day_end, day_number)' : 'day_number';
$_dayImageSel   = $_hasDayImage    ? 'image_path'        : 'NULL AS image_path';
$_hlSel         = $_hasHighlights  ? ', highlights_json' : '';

$days = db_table_exists('itinerary_days')
    ? fetch_all(
        "SELECT day_number,
                {$_dayEndExpr} AS day_end,
                title, summary, overnight_location, travel_time,
                {$_dayImageSel} {$_hlSel}
         FROM itinerary_days
         WHERE itinerary_id = :id
         ORDER BY day_number ASC",
        ['id' => $itinerary['id']]
    )
    : [];

// ── Related content ───────────────────────────────────────────────────────────
$relatedDestinations = [];
if (db_table_exists('itinerary_destination') && db_table_exists('destinations')) {
    $relatedDestinations = fetch_all(
        "SELECT d.id, d.name, d.slug, d.excerpt, d.cover_image, d.hero_image,
                d.destination_type,
                " . (db_column_exists('destinations','emoji')        ? 'd.emoji,'        : "'📍' AS emoji,") . "
                " . (db_column_exists('destinations','region_label') ? 'd.region_label,' : "'' AS region_label,") . "
                " . (db_column_exists('destinations','stay_duration')? 'd.stay_duration' : "'' AS stay_duration") . "
         FROM itinerary_destination idt
         INNER JOIN destinations d ON d.id = idt.destination_id
         WHERE idt.itinerary_id = :id AND d.is_published = 1
         ORDER BY d.sort_order ASC, d.name ASC
         LIMIT 6",
        ['id' => $itinerary['id']]
    );
}

$relatedExperiences = db_table_exists('experience_itinerary')
    ? fetch_experiences_for_itinerary((int)$itinerary['id'], 4)
    : [];

$relatedPartners = business_partners_public_enabled() && db_table_exists('business_partner_itinerary')
    ? fetch_business_partners_for_itinerary((int)$itinerary['id'], 4)
    : [];

// Suggested itineraries — same category first, then by shared destination
$suggestedItineraries = [];
if ($category && db_column_exists('itineraries', 'category_id')) {
    $suggestedItineraries = fetch_all(
        "SELECT id, name, slug, days, excerpt, hero_image, og_image
         FROM itineraries
         WHERE is_published = 1 AND category_id = :cat AND id != :cur
         ORDER BY is_featured DESC, sort_order ASC, days ASC
         LIMIT 4",
        ['cat' => (int)$category['id'], 'cur' => (int)$itinerary['id']]
    );
}
if (!$suggestedItineraries && db_table_exists('itinerary_destination')) {
    $suggestedItineraries = fetch_all(
        "SELECT DISTINCT i.id, i.name, i.slug, i.days, i.excerpt, i.hero_image, i.og_image,
                i.is_featured, i.sort_order
         FROM itinerary_destination cl
         INNER JOIN itinerary_destination ol ON ol.destination_id = cl.destination_id
         INNER JOIN itineraries i ON i.id = ol.itinerary_id
         WHERE cl.itinerary_id = :cur AND ol.itinerary_id != :excl AND i.is_published = 1
         ORDER BY i.is_featured DESC, i.sort_order ASC, i.days ASC
         LIMIT 4",
        ['cur' => (int)$itinerary['id'], 'excl' => (int)$itinerary['id']]
    );
}

// ── FAQ ───────────────────────────────────────────────────────────────────────
$faqItems = [];
if (!empty($itinerary['faq_json'])) {
    $decoded = json_decode($itinerary['faq_json'], true);
    if (is_array($decoded)) {
        $faqItems = clean_public_faq_items($decoded);
    }
}

// ── Best months ───────────────────────────────────────────────────────────────
$bestMonthsData = [];
if ($_hasBestMonths && !empty($itinerary['best_months_json'])) {
    $bestMonthsData = decode_best_time_json($itinerary['best_months_json']);
    // Convert plain month strings to the structured format the renderer expects
    $bestMonthsData['months'] = array_map(
        static fn($m) => is_string($m)
            ? ['month' => ucfirst($m), 'rating' => '']
            : $m,
        $bestMonthsData['months']
    );
}

// ── Content fields ────────────────────────────────────────────────────────────
$itinDays          = (int)($itinerary['days'] ?? 0);
$itinName          = trim((string)($itinerary['name'] ?? ''));
$itinExcerpt       = clean_public_text($itinerary['excerpt']       ?? '');
$itinIntro         = clean_public_text($itinerary['intro']         ?? '');
$itinHighlights    = clean_public_text($itinerary['highlights']    ?? '');
$itinRouteSummary  = clean_public_text($itinerary['route_summary'] ?? '');
$itinBestFor       = clean_public_text($itinerary['best_for']      ?? '');
$itinTravelNotes   = clean_public_text($itinerary['travel_notes']  ?? '');
$itinPricingNotes  = clean_public_text($itinerary['pricing_notes'] ?? '');
$itinVehicleOpts   = clean_public_text($itinerary['vehicle_options']?? '');
$itinPriceLabel    = format_price_range($itinerary);

// Traveller types
$travellerTypes = [];
if ($_hasTraveller && !empty($itinerary['traveller_types'])) {
    $typeMap = ['couple'=>['icon'=>'👫','label'=>'Couples'],'family'=>['icon'=>'👨‍👩‍👧','label'=>'Families'],
                'honeymoon'=>['icon'=>'💑','label'=>'Honeymoon'],'solo'=>['icon'=>'🧳','label'=>'Solo'],
                'group'=>['icon'=>'👥','label'=>'Groups']];
    foreach (explode(',', $itinerary['traveller_types']) as $t) {
        $t = trim($t);
        if ($t && isset($typeMap[$t])) $travellerTypes[] = $typeMap[$t];
    }
}

// ── Hero ──────────────────────────────────────────────────────────────────────
$heroMedia     = content_hero_media($itinerary);
$pageImage     = og_image_url($itinerary);
$heroBg        = $heroMedia['type'] === 'video'
                   ? ($heroMedia['poster_url'] ?: $pageImage)
                   : ($heroMedia['url'] ?: $pageImage);
$heroBackground = is_placeholder_media_url($heroBg) ? '' : $heroBg;

// ── SEO ───────────────────────────────────────────────────────────────────────
$pageUrl        = !empty($itinerary['canonical_url'])
                    ? $itinerary['canonical_url']
                    : base_url('itineraries/' . $itinerary['slug']);
$seoTitle       = $itinerary['meta_title']       ?: ($itinName . ' | ' . APP_NAME);
$seoDescription = $itinerary['meta_description'] ?: ($itinExcerpt ?: 'Explore the ' . $itinName . ' with Island Culture Travels.');
$seoCanonical   = $pageUrl;
$seoImage       = $pageImage;
$seoImageWidth  = 1200;
$seoImageHeight = 675;
$seoType        = 'article';

$breadcrumbs = [
    ['name' => 'Home',        'url' => base_url()],
    ['name' => 'Itineraries', 'url' => base_url('itineraries')],
];
if ($category) {
    $breadcrumbs[] = ['name' => (string)$category['name'], 'url' => itinerary_category_url($category)];
}
$breadcrumbs[] = ['name' => $itinName, 'url' => $pageUrl];

$schemaGraph = [
    schema_website(),
    schema_travel_agency(),
    schema_breadcrumbs($breadcrumbs),
    schema_trip(array_merge($itinerary, ['day_images' => []]), $pageUrl),
];
if ($faqItems) {
    $faqSchema = schema_faq_from_items($faqItems);
    if ($faqSchema) $schemaGraph[] = $faqSchema;
}

$bodyClass = 'page-itinerary';
$extraStylesheets[] = 'assets/css/journey-layouts.css';
$extraStylesheets[] = 'assets/css/route-map.css';
require_once __DIR__.'/includes/page_gallery_view.php';
$pageGallery=page_gallery_rows('itinerary',(int)$itinerary['id']);
$seoImageAlt=(string)($itinerary['og_alt']??'');
$extraStylesheets[]='assets/css/page-gallery.css';
if(!empty($itinerary['og_image']))$seoImage=stored_media_url($itinerary['og_image']);
if($pageGallery)$schemaGraph[]=['@type'=>'ImageGallery','name'=>$itinName.' photographs','image'=>page_gallery_schema($pageGallery)];
require __DIR__ . '/includes/header.php';
?>

<!-- ════════════════════════════════════════════════════════════
     HERO
════════════════════════════════════════════════════════════ -->
<section class="itin-detail-hero"
         <?php if ($heroBackground): ?>style="background-image:linear-gradient(160deg,rgba(13,31,20,.9) 0%,rgba(26,74,42,.72) 100%),url('<?= e($heroBackground) ?>');background-size:cover;background-position:center;"<?php endif; ?>>
  <div class="container">
    <nav aria-label="Breadcrumb" style="margin-bottom:1.25rem;">
      <?php require __DIR__ . '/includes/breadcrumbs.php'; ?>
    </nav>

    <span class="section-eyebrow" style="color:var(--accent-light);">
      <?= $itinDays > 0 ? $itinDays . ' Day Private Route' : 'Private Route' ?>
    </span>

    <h1 class="section-title" style="color:#fff; font-size:clamp(1.9rem,4vw,3rem); margin-bottom:.5rem;">
      <span class="itin-title-animate"><?= e($itinName) ?></span>
    </h1>

    <?php if ($itinExcerpt): ?>
    <p class="section-lead" style="color:rgba(255,255,255,.84); font-size:1rem; max-width:560px; margin-top:.25rem;">
      <?= e($itinExcerpt) ?>
    </p>
    <?php endif; ?>

    <!-- Meta pills -->
    <div class="itin-detail-pills">
      <?php if ($itinDays > 0): ?>
        <span class="itin-detail-pill">📅 <?= $itinDays ?> days</span>
      <?php endif; ?>
      <?php if (count($relatedDestinations) > 0): ?>
        <span class="itin-detail-pill">📍 <?= count($relatedDestinations) ?> stops</span>
      <?php endif; ?>
      <?php if ($_hasStartPoint && !empty($itinerary['start_point'])): ?>
        <span class="itin-detail-pill">🚗 From <?= e($itinerary['start_point']) ?></span>
      <?php endif; ?>
      <?php foreach ($travellerTypes as $tt): ?>
        <span class="itin-detail-pill"><?= $tt['icon'] ?> <?= e($tt['label']) ?></span>
      <?php endforeach; ?>
      <?php if ($itinPriceLabel): ?>
        <span class="journey-visible-price"><?= e($itinPriceLabel) ?></span>
      <?php endif; ?>
    </div>

    <!-- CTA strip — design-system buttons -->
    <div class="itin-hero-ctas">
      <a href="<?= e(base_url('contact') . '?itinerary=' . urlencode($itinName)) ?>"
         class="btn btn-primary"
         onclick="if(typeof trackEvent==='function')trackEvent('contact_cta_click',{label:'itinerary_hero'})">
        Enquire About This Route
      </a>
      <a href="<?= e(site_whatsapp_url('Hello, I\'m interested in the ' . $itinName . ' itinerary.')) ?>"
         class="btn btn-whatsapp"
         target="_blank" rel="noopener"
         onclick="if(typeof trackEvent==='function')trackEvent('whatsapp_click',{label:'itinerary_hero'})">
        WhatsApp
      </a>
      <a href="<?= e(base_url('itineraries')) ?>"
         class="btn btn-ghost btn-sm"
         aria-label="Compare Routes">
        ⊞ Compare
      </a>
    </div>
  </div>
</section>
<!-- ════════════════════════════════════════════════════════════
     BODY — timeline + sidebar
════════════════════════════════════════════════════════════ -->
<div class="itin-detail-body">

  <!-- ── Main column ────────────────────────────────────── -->
  <div class="itin-main-col" id="itin-main">

    <!-- Scroll-triggered enquiry prompt (shown by JS after 60% scroll) -->
    <div class="itin-scroll-prompt" id="itinScrollPrompt" aria-hidden="true" hidden>
      <div class="itin-scroll-prompt-inner">
        <div>
          <strong>Like this route?</strong>
          <p>Tell us your travel dates and we'll tailor it around you.</p>
        </div>
        <div class="itin-scroll-prompt-actions" style="display:flex; gap:.5rem;">
          <a href="<?= e(base_url('contact') . '?itinerary=' . urlencode($itinName)) ?>"
             class="btn btn-primary btn-sm">Enquire →</a>
          <a href="<?= e(site_whatsapp_url('Hi, I\'m interested in the ' . $itinName)) ?>"
             class="btn btn-whatsapp btn-sm" target="_blank" rel="noopener">WhatsApp</a>
        </div>
      </div>
    </div>

    <!-- Overview -->
    <section class="itin-section" id="overview">
      <div class="package-overview-map"><div>
      <h2 class="itin-section-heading">Itinerary <em>Overview</em></h2>
      <div class="itin-prose"><?= $itinIntro ?></div>
      </div><?= route_map_public('itinerary', $itinerary) ?></div>
    </section>

    <!-- Route Summary + Best For -->
    <?php if ($itinRouteSummary || $itinBestFor): ?>
    <section class="itin-section" id="route-summary">
      <?php if ($itinRouteSummary): ?>
      <h2 class="itin-section-heading">Route <em>Summary</em></h2>
      <div class="itin-prose"><?= $itinRouteSummary ?></div>
      <?php endif; ?>
      <?php if ($itinBestFor): ?>
      <div class="itin-best-for">
        <span class="itin-best-for-label">Best for</span>
        <div class="itin-prose itin-prose--sm"><?= $itinBestFor ?></div>
      </div>
      <?php endif; ?>
    </section>
    <?php endif; ?>

    <!-- Highlights -->
    <?php if ($itinHighlights): ?>
    <section class="itin-section" id="highlights">
      <h2 class="itin-section-heading">Tour <em>Highlights</em></h2>
      <div class="itin-prose"><?= $itinHighlights ?></div>
    </section>
    <?php endif; ?>

    <!-- Best Months strip -->
    <?php if (!empty($bestMonthsData['months'])): ?>
    <section class="itin-section" id="best-months">
      <h2 class="itin-section-heading">Best <em>Months</em> for This Route</h2>
      <?php
      $ratingColors = ['great'=>'#2d6a41','okay'=>'#8f7a28','avoid'=>'#9a3a2a'];
      $ratingLabels = ['great'=>'Great','okay'=>'Okay','avoid'=>'Avoid'];
      ?>
      <div class="best-months-strip" role="list" aria-label="Best months to travel">
        <?php foreach ($bestMonthsData['months'] as $mo):
            $color   = $ratingColors[$mo['rating']] ?? '#c8c4bc';
            $label   = $ratingLabels[$mo['rating']] ?? '';
            $opacity = $mo['rating'] === '' ? '.35' : '.82';
        ?>
        <div class="best-months-cell"
             style="background:<?= $color ?>;opacity:<?= $opacity ?>;"
             title="<?= e($mo['month'] . ': ' . $label) ?>"
             role="listitem"
             aria-label="<?= e($mo['month'] . ': ' . $label) ?>">
          <span class="bmc-month"><?= e($mo['month']) ?></span>
          <?php if ($label): ?>
          <span class="bmc-label"><?= e($label) ?></span>
          <?php endif; ?>
        </div>
        <?php endforeach; ?>
      </div>
      <?php if (!empty($bestMonthsData['note'])): ?>
      <p class="best-months-note"><?= e($bestMonthsData['note']) ?></p>
      <?php endif; ?>
      <p class="itin-section-crosslink">
        <a href="<?= e(base_url('seasonal')) ?>">See all monthly guides →</a>
      </p>
    </section>
    <?php endif; ?>

    <!-- ── Day-by-Day Timeline ───────────────────────────── -->
    <?php if ($days): ?>
    <section class="itin-section" id="day-by-day">
      <h2 class="itin-section-heading">Day-by-Day <em>Plan</em></h2>

      <div class="journey-timeline-controls" data-timeline-controls hidden>
        <button type="button" class="btn btn-outline-dark btn-sm" data-expand-days aria-controls="itinerary-day-cards">Expand all</button>
        <button type="button" class="btn btn-outline-dark btn-sm" data-collapse-days aria-controls="itinerary-day-cards">Collapse all</button>
        <span class="small text-secondary" role="status" aria-live="polite"></span>
      </div>
      <div class="itin-timeline" id="itinerary-day-cards">
        <?php
        // Alternating accent colours for day markers
        $accentCycle = ['var(--brand)', 'var(--accent)'];
        foreach ($days as $di => $day):
            $accentColor  = $accentCycle[$di % 2];
            $dayNum       = (int)$day['day_number'];
            $dayEnd       = (int)($day['day_end'] ?? $dayNum);
            $dayLabel     = $dayNum === $dayEnd ? "Day {$dayNum}" : "Days {$dayNum}–{$dayEnd}";
            $imgUrl       = !empty($day['image_path']) ? stored_media_url((string)$day['image_path']) : '';
            $highlights   = [];
            if ($_hasHighlights && !empty($day['highlights_json'])) {
                $hl = json_decode($day['highlights_json'], true);
                if (is_array($hl)) $highlights = array_filter(array_map('strval', $hl));
            }
            $dayPartners = ($relatedPartners && !empty($day['overnight_location']))
                ? fetch_business_partners_for_day((int)$itinerary['id'], (string)$day['overnight_location'], 3)
                : [];
            $isRight = $di % 2 === 1; // Even = left (0), Odd = right (1)
        ?>
        <article class="tl-item" style="--tl-accent: <?= $accentColor ?>;">
          <div class="tl-marker" style="background: <?= $accentColor ?>;">
            <span class="tl-day-num"><?= e($dayLabel) ?></span>
          </div>

          <div class="tl-horizontal" aria-hidden="true"></div>

          <details class="tl-card"<?= $di === 0 ? ' open' : '' ?>>
            <summary class="tl-summary" data-lightbox-ignore>
              <?php if ($imgUrl): ?><div class="tl-thumb"><?= responsive_image(['src' => $imgUrl, 'alt' => $day['title'] . ($day['overnight_location'] ? ' — ' . $day['overnight_location'] : ''), 'widths' => [400], 'sizes' => '100px', 'width' => 200, 'height' => 200, 'loading' => 'lazy']) ?></div><?php endif; ?>
              <div class="tl-summary-text">
                <span class="tl-day-label" style="color: <?= $accentColor ?>;"><?= e($dayLabel) ?></span>
                <h3 class="tl-day-title"><?= e($day['title']) ?></h3>
                <?php if ($day['overnight_location']): ?>
                <span class="tl-overnight">🌙 Overnight: <?= e($day['overnight_location']) ?></span>
                <?php endif; ?>
                <?php if ($day['travel_time']): ?><span class="journey-day-drive">Drive: <?= e($day['travel_time']) ?></span><?php endif; ?>
              </div>
              <span class="tl-chevron" aria-hidden="true">▾</span>
            </summary>

            <div class="tl-body">
              <?php if ($day['summary']): ?>
              <div class="tl-summary-text-body"><?= clean_public_text($day['summary']) ?></div>
              <?php endif; ?>

              <?php if ($highlights): ?>
              <div class="tl-highlights">
                <?php foreach ($highlights as $hl): ?>
                <span class="tl-highlight-chip"><?= e($hl) ?></span>
                <?php endforeach; ?>
              </div>
              <?php endif; ?>

              <?php if ($day['travel_time']): ?>
              <p class="tl-travel-time">🚗 Drive: <?= e($day['travel_time']) ?></p>
              <?php endif; ?>

              <?php if ($day['overnight_location'] && $dayPartners): ?>
              <div class="tl-stays">
                <h4 class="tl-stays-heading">Recommended Stays near <?= e($day['overnight_location']) ?></h4>
                <div class="tl-stays-grid">
                  <?php foreach ($dayPartners as $partner):
                      $pImg = !empty($partner['hero_image']) ? stored_media_url((string)$partner['hero_image']) : '';
                  ?>
                  <a href="<?= e(base_url('partners/' . $partner['slug'])) ?>" class="tl-stay-card">
                    <div class="tl-stay-img" <?php if ($pImg): ?>style="background-image:url('<?= e($pImg) ?>')"<?php endif; ?>>
                      <?php if (!$pImg): ?><span aria-hidden="true">🏨</span><?php endif; ?>
                    </div>
                    <div class="tl-stay-info">
                      <strong><?= e($partner['business_name']) ?></strong>
                      <span><?= e($partner['location'] ?? $partner['category_name'] ?? '') ?></span>
                    </div>
                  </a>
                  <?php endforeach; ?>
                </div>
                <a href="<?= e(base_url('partners')) ?>" class="tl-stays-more">View all stays →</a>
              </div>
              <?php elseif ($day['overnight_location']): ?>
              <div class="tl-stays">
                <p class="tl-stays-note">Partner hotels available — <a href="<?= e(base_url('contact')) ?>?itinerary=<?= e(rawurlencode($itinName)) ?>">ask us for recommendations</a></p>
              </div>
              <?php endif; ?>
            </div>
          </details>
        </article>

        <?php if ($di < count($days) - 1): ?>
        <!-- Vertical connector between items -->
        <div class="tl-connector" aria-hidden="true">
          <div class="tl-connector-line"></div>
          <div class="tl-connector-dot" style="background: <?= $accentColor ?>;"></div>
          <div class="tl-connector-line"></div>
        </div>
        <?php endif; ?>

        <?php endforeach; ?>
      </div><!-- /itin-timeline -->
    </section>
    <?php endif; ?>

    <!-- Travel Notes -->
    <?php if ($itinTravelNotes): ?>
    <section class="itin-section" id="travel-notes">
      <h2 class="itin-section-heading">Travel <em>Notes</em></h2>
      <div class="itin-prose"><?= $itinTravelNotes ?></div>
    </section>
    <?php endif; ?>

    <!-- Private Travel Details -->
    <?php if ($itinVehicleOpts || $itinPricingNotes): ?>
    <section class="itin-section" id="private-travel-details">
      <h2 class="itin-section-heading">Private Travel <em>Details</em></h2>
      <?php if ($itinVehicleOpts): ?>
      <div class="itin-best-for">
        <span class="itin-best-for-label">Vehicle</span>
        <div class="itin-prose itin-prose--sm"><?= render_public_content($itinVehicleOpts) ?></div>
      </div>
      <?php endif; ?>
      <?php if ($itinPricingNotes): ?>
      <div class="itin-best-for">
        <span class="itin-best-for-label">Pricing</span>
        <div class="itin-prose itin-prose--sm"><?= render_public_content($itinPricingNotes) ?></div>
      </div>
      <?php endif; ?>
    </section>
    <?php endif; ?>

    <!-- Destinations Visited — design-system .card-dest -->
    <?php if ($relatedDestinations): ?>
    <section class="itin-section" id="destinations">
      <h2 class="itin-section-heading">Destinations <em>Visited</em></h2>
      <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(160px, 1fr)); gap:1rem;">
        <?php foreach ($relatedDestinations as $rd):
            $rdImg = !empty($rd['cover_image'])
                ? upload_url($rd['cover_image'])
                : (!empty($rd['hero_image']) ? upload_url($rd['hero_image']) : '');
        ?>
        <a href="<?= e(base_url('destinations/' . $rd['slug'])) ?>" class="card-dest" data-lightbox-ignore>
          <?php if ($rdImg && !is_placeholder_media_url($rdImg)): ?>
            <?= responsive_image([
                'src' => $rdImg,
                'alt' => $rd['name'],
                'widths' => [400, 800],
                'sizes' => '(min-width: 992px) 220px, 100vw',
                'width' => 640,
                'height' => 640,
                'loading' => 'lazy',
            ]) ?>
          <?php elseif ($rdImg): ?>
            <img src="<?= e($rdImg) ?>" alt="<?= e($rd['name']) ?>" loading="lazy" width="640" height="640">
          <?php endif; ?>
          <span class="card-dest-overlay"></span>
          <span class="card-dest-name"><?= e($rd['name']) ?></span>
        </a>
        <?php endforeach; ?>
      </div>
    </section>
    <?php endif; ?>

    <!-- Experiences — design-system .card-exp -->
    <?php if ($relatedExperiences): ?>
    <section class="itin-section" id="experiences">
      <h2 class="itin-section-heading">Experiences <em>on This Route</em></h2>
      <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(240px, 1fr)); gap:1rem;">
        <?php foreach ($relatedExperiences as $exp):
            $expImg = !empty($exp['hero_image'])
                ? stored_media_url($exp['hero_image'])
                : (!empty($exp['og_image']) ? upload_url($exp['og_image']) : '');
        ?>
        <a href="<?= e(experience_url($exp)) ?>" class="card-exp">
          <?php if ($expImg && !is_placeholder_media_url($expImg)): ?>
            <?= responsive_image([
                'src' => $expImg,
                'alt' => $exp['title'],
                'widths' => [400, 800],
                'sizes' => '(min-width: 992px) 360px, 100vw',
                'width' => 640,
                'height' => 480,
                'loading' => 'lazy',
            ]) ?>
          <?php elseif ($expImg): ?>
            <img src="<?= e($expImg) ?>" alt="<?= e($exp['title']) ?>" loading="lazy" width="640" height="480">
          <?php endif; ?>
          <span class="card-exp-overlay"></span>
          <span class="card-exp-body">
            <strong class="card-exp-title"><?= e($exp['title']) ?></strong>
            <?php if (!empty($exp['excerpt'])): ?>
            <span class="card-exp-excerpt"><?= e(mb_strimwidth($exp['excerpt'], 0, 70, '…')) ?></span>
            <?php endif; ?>
          </span>
        </a>
        <?php endforeach; ?>
      </div>
    </section>
    <?php endif; ?>

    <!-- Suggested Itineraries — design-system .card -->
    <?php if ($suggestedItineraries): ?>
    <section class="itin-section" id="suggested">
      <h2 class="itin-section-heading">You Might Also <em>Like</em></h2>
      <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(260px, 1fr)); gap:1.25rem;">
        <?php foreach ($suggestedItineraries as $sg):
            $sgImg = (package_card_image($sg, 'itinerary') ?: '');
            $sgDays = (int)$sg['days'];
            $sgExcerpt = mb_strimwidth((string)($sg['excerpt'] ?? ''), 0, 90, '…');
        ?>
        <a href="<?= e(base_url('itineraries/' . $sg['slug'])) ?>" class="card" data-lightbox-ignore style="text-decoration:none; color:inherit;">
          <div class="card-thumb">
            <?php if ($sgImg && !is_placeholder_media_url($sgImg)): ?>
              <?= responsive_image([
                  'src' => $sgImg,
                  'alt' => package_card_alt($sg, 'itinerary'),
                  'widths' => [400, 800],
                  'sizes' => '(min-width: 992px) 360px, 100vw',
                  'width' => 640,
                  'height' => 480,
                  'loading' => 'lazy',
              ]) ?>
            <?php elseif ($sgImg): ?>
              <img src="<?= e($sgImg) ?>" alt="<?= e($sg['name']) ?>" loading="lazy" width="640" height="480">
            <?php else: ?>
              <div style="width:100%;height:100%;background:linear-gradient(135deg, var(--brand), var(--surface-mid));"></div>
            <?php endif; ?>
            <?php if ($sgDays > 0): ?>
              <span class="card-badge"><?= $sgDays ?> Days</span>
            <?php endif; ?>
          </div>
          <div class="card-body">
            <h3 class="card-title" style="font-size:1.05rem;"><?= e($sg['name']) ?></h3>
            <?php if ($sgExcerpt !== ''): ?>
            <p class="card-excerpt"><?= e($sgExcerpt) ?></p>
            <?php endif; ?>
            <div class="card-footer">
              <span class="card-price-label">Private route</span>
              <span class="card-price"><?= $sgDays > 0 ? $sgDays . ' Days' : 'Custom' ?></span>
            </div>
          </div>
        </a>
        <?php endforeach; ?>
      </div>
    </section>
    <?php endif; ?>

    <!-- FAQ -->
    <?php if ($faqItems):
        $faqRelatedLinks = [];
        foreach (array_slice($relatedDestinations, 0, 3) as $item) {
            $faqRelatedLinks[] = ['label' => $item['name'], 'url' => base_url('destinations/' . $item['slug'])];
        }
        if ($category) {
            $faqRelatedLinks[] = ['label' => (string)$category['name'], 'url' => itinerary_category_url($category)];
        }
        require __DIR__ . '/includes/faq_section.php';
    endif; ?>

  </div><!-- /itin-main-col -->

  <!-- ── Sticky sidebar ─────────────────────────────────── -->
  <aside class="itin-sidebar" id="itinSidebar" aria-label="Trip facts and booking">

    <div class="itin-sidebar-inner">
      <h3 class="itin-sidebar-heading">Trip Facts</h3>

      <dl class="itin-facts">
        <?php if ($itinDays > 0): ?>
        <div class="itin-fact">
          <dt>Duration</dt>
          <dd><?= $itinDays ?> Day<?= $itinDays !== 1 ? 's' : '' ?> / <?= $itinDays - 1 ?> Night<?= $itinDays - 1 !== 1 ? 's' : '' ?></dd>
        </div>
        <?php endif; ?>
        <?php if ($relatedDestinations): ?>
        <div class="itin-fact">
          <dt>Stops</dt>
          <dd><?= implode(' · ', array_slice(array_column($relatedDestinations, 'name'), 0, 3)) ?><?= count($relatedDestinations) > 3 ? ' +' . (count($relatedDestinations) - 3) . ' more' : '' ?></dd>
        </div>
        <?php endif; ?>
        <?php if ($travellerTypes): ?>
        <div class="itin-fact">
          <dt>Best for</dt>
          <dd><?= implode(', ', array_column($travellerTypes, 'label')) ?></dd>
        </div>
        <?php endif; ?>
        <?php if (!empty($bestMonthsData['note'])): ?>
        <div class="itin-fact">
          <dt>Best months</dt>
          <dd><?= e($bestMonthsData['note']) ?></dd>
        </div>
        <?php endif; ?>
        <?php if ($_hasStartPoint && !empty($itinerary['start_point'])): ?>
        <div class="itin-fact">
          <dt>Starts</dt>
          <dd><?= e($itinerary['start_point']) ?></dd>
        </div>
        <?php endif; ?>
        <?php if ($_hasStartPoint && !empty($itinerary['end_point'])): ?>
        <div class="itin-fact">
          <dt>Ends</dt>
          <dd><?= e($itinerary['end_point']) ?></dd>
        </div>
        <?php endif; ?>
        <div class="itin-fact">
          <dt>Vehicle</dt>
          <dd>Private, air-conditioned</dd>
        </div>
        <?php if ($itinPriceLabel): ?>
        <div class="itin-fact">
          <dt>Indicative price</dt>
          <dd><span class="card-price"><?= e($itinPriceLabel) ?></span></dd>
        </div>
        <?php endif; ?>
      </dl>

      <!-- Primary CTAs — design-system buttons -->
      <a href="<?= e(base_url('contact') . '?itinerary=' . urlencode($itinName)) ?>"
         class="btn btn-primary w-100"
         style="margin-bottom:.6rem;"
         onclick="if(typeof trackEvent==='function')trackEvent('contact_cta_click',{label:'itinerary_sidebar'})">
        Enquire About This Route
      </a>
      <a href="<?= e(site_whatsapp_url('Hi, I\'m interested in the ' . $itinName . '.')) ?>"
         class="btn btn-whatsapp w-100"
         target="_blank" rel="noopener"
         onclick="if(typeof trackEvent==='function')trackEvent('whatsapp_click',{label:'itinerary_sidebar'})">
        WhatsApp Us
      </a>

      <!-- Quick enquiry mini-form — form component styles -->
      <div class="itin-sidebar-form" id="itinSidebarForm">
        <p><strong>Like this route?</strong><br>Tell us your dates and we'll tailor it.</p>
        <div class="form-field">
          <label class="form-label" for="sbName" style="font-size:var(--text-xs);">Your name</label>
          <input type="text" class="form-input" id="sbName" placeholder="Your name"
                 autocomplete="name" aria-label="Your name"/>
        </div>
        <div class="form-field" style="margin-top:.6rem;">
          <label class="form-label" for="sbDates" style="font-size:var(--text-xs);">Travel dates</label>
          <input type="text" class="form-input" id="sbDates" placeholder="Travel dates (e.g. Dec 10–18)"
                 aria-label="Travel dates"/>
        </div>
        <a href="#" class="btn btn-primary btn-sm w-100" id="sbSend"
           style="margin-top:.75rem;">
          Send →
        </a>
      </div>

    </div>
  </aside>

</div><!-- /itin-detail-body -->

<?php
$ctaTitle = trim((string) ($itinerary['cta_title'] ?? '')) ?: 'Customize ' . $itinName;
$ctaText = trim(strip_tags((string) ($itinerary['cta_text'] ?? ''))) ?: 'Tell us your dates, group size, and travel style, and we will tune this private route around your pace, hotels, driver timing, and must-see stops.';
$ctaButtonUrl = base_url('contact') . '?itinerary=' . urlencode($itinName);
$ctaWhatsappText = 'Hi, I am interested in the ' . $itinName . ' itinerary.';
page_gallery_assets();page_gallery_public($pageGallery,$itinName);
schema_output($schemaGraph);
require __DIR__ . '/includes/cta.php';
?>

<style>
/* ── Hero ────────────────────────────────────────────────── */
.itin-detail-hero {
  background:linear-gradient(160deg,#0d1f14 0%,#1a4a2a 100%);
  padding:var(--site-overlay-hero-offset, 8.75rem) 0 2.75rem;
  position:relative;
  overflow:hidden;
}
.itin-detail-hero::after {
  content:'';position:absolute;inset:0;
  background:repeating-linear-gradient(45deg,transparent,transparent 40px,
    rgba(255,255,255,.012) 40px,rgba(255,255,255,.012) 41px);
  pointer-events:none;
}
.itin-detail-hero .container { position:relative; z-index:1; }

/* Pills */
.itin-detail-pills {
  display:flex;gap:8px;flex-wrap:wrap;margin-top:.8rem;justify-content:center;
}
.itin-detail-pill {
  background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.18);
  color:#fff;font-size:.7rem;font-weight:600;padding:.34rem .8rem;border-radius:999px;
}

/* Hero CTAs — using design-system .btn */
.itin-hero-ctas {
  display:flex;gap:.75rem;margin-top:1.5rem;align-items:center;flex-wrap:wrap;
}
.itin-hero-ctas .btn { min-height:44px; }
.itin-hero-ctas .btn-primary { background:var(--brand); border-color:var(--brand); }
.itin-hero-ctas .btn-primary:hover { background:var(--brand-dark); border-color:var(--brand-dark); }
.itin-hero-ctas .btn-whatsapp { background:#25d366; border-color:#25d366; color:#fff; }
.itin-hero-ctas .btn-ghost { background:rgba(255,255,255,.12); border-color:rgba(255,255,255,.3); color:#fff; }

/* Breadcrumbs on dark hero */
.itin-detail-hero .breadcrumb,
.itin-detail-hero .breadcrumb-item,
.itin-detail-hero .breadcrumb-item.active,
.itin-detail-hero .breadcrumb-item a {
  color: rgba(255,255,255,.82) !important;
}
.itin-detail-hero .breadcrumb-item a:hover { color:#fff !important; }
.itin-detail-hero .breadcrumb-item + .breadcrumb-item::before { color: rgba(255,255,255,.45); }

/* ── Title typing animation ─────────────────────────── */
.itin-title-animate {
    overflow: hidden;
    white-space: nowrap;
    border-right: 3px solid var(--accent-light, #c9a84c);
    display: inline-block;
    max-width: 100%;
    vertical-align: bottom;
    animation: typing 1.8s steps(30, end) forwards,
               blink-caret 0.75s step-end 4 forwards;
}

@keyframes typing {
    from { width: 0; }
    to { width: 100%; }
}

@keyframes blink-caret {
    0%, 100% { border-color: transparent; }
    50% { border-color: var(--accent-light, #c9a84c); }
}

/* ── Price toggle ───────────────────────────────────── */
.itin-price-toggle {
    display:inline-flex;
    align-items:center;
    cursor:pointer;
    user-select:none;
    transition:all 0.3s ease;
}
.itin-price-toggle:focus-visible { outline:2px solid rgba(255,255,255,.5);outline-offset:2px;border-radius:999px; }

.itin-price-icon {
    display:inline-flex;
    align-items:center;
    justify-content:center;
    width:36px;height:36px;
    font-size:1.1rem;
    border-radius:50%;
    background:rgba(255,255,255,0.12);
    animation:pricePulse 2s ease-in-out infinite;
    transition:all 0.3s ease;
}

.itin-price-text {
    background:rgba(255,255,255,0.12);
    border:1px solid rgba(255,255,255,0.18);
    color:#fff;
    font-size:.7rem;
    font-weight:600;
    padding:.34rem .8rem;
    border-radius:999px;
    white-space:nowrap;
    display:inline-flex;
    align-items:center;
    gap:.3rem;
}
.itin-price-text small { opacity:.7;font-size:.6rem;font-weight:400; }

@keyframes pricePulse {
    0%, 100% { box-shadow: 0 0 0 0 rgba(255,255,255,0.3); }
    50% { box-shadow: 0 0 0 8px rgba(255,255,255,0); }
}

/* ── Compare button ─────────────────────────────────── */
.btn-hero-compare {
    display:inline-flex;
    align-items:center;
    justify-content:center;
    width:44px;height:44px;
    border-radius:50%;
    background:rgba(255,255,255,0.12);
    color:#fff;
    border:1px solid rgba(255,255,255,0.25);
    text-decoration:none;
    font-size:1.2rem;
    transition:all 0.3s ease;
    flex-shrink:0;
}
.btn-hero-compare:hover { background:rgba(255,255,255,0.2); }


/* ── Body layout ─────────────────────────────────────────── */
.itin-detail-body {
  display:grid;
  grid-template-columns:1fr;
  max-width:1100px;
  margin:0 auto;
  padding:0 1.25rem;
}
@media (min-width:860px) {
  .itin-detail-body {
    grid-template-columns:1fr 280px;
    gap:0;
    align-items:start;
    padding:0;
  }
}

/* Main column */
.itin-main-col { padding:2rem 0; }
@media (min-width:860px) { .itin-main-col { padding:2.5rem 2rem 2.5rem 0; } }

/* Section */
.itin-section { margin-bottom:2.5rem; padding-bottom:2.5rem; border-bottom:1px solid var(--line); }
.itin-section:last-child { border-bottom:none; }
.itin-section-heading {
  font-family:var(--font-display,Georgia,serif);
  font-size:clamp(1.4rem,2.5vw,1.9rem);
  font-weight:400;
  color:var(--text);
  margin-bottom:1.1rem;
}
.itin-section-heading em { font-style:italic; }
.itin-prose { font-size:.9rem; line-height:1.8; color:var(--text-body); }
.itin-prose--sm { font-size:.82rem; }
.itin-section-crosslink { margin-top:.75rem; }
.itin-section-crosslink a { font-size:.8rem; font-weight:600; color:var(--brand); text-decoration:none; }

/* Best For */
.itin-best-for {
  background:var(--surface-alt);
  border-radius:var(--radius-md);
  padding:1rem 1.25rem;
  margin-top:1rem;
  display:flex;
  gap:.75rem;
  align-items:flex-start;
}
.itin-best-for-label {
  font-size:.65rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;
  color:var(--accent);flex-shrink:0;padding-top:.2rem;
}

/* ── Best Months strip ───────────────────────────────────── */
.best-months-strip {
  display:grid;
  grid-template-columns:repeat(12,1fr);
  border-radius:var(--radius-sm);
  overflow:hidden;
  border:1px solid var(--line);
  height:52px;
  margin-bottom:.75rem;
}
.best-months-cell {
  display:flex;flex-direction:column;align-items:center;justify-content:center;
  gap:.1rem;cursor:default;
}
.bmc-month { font-size:.6rem;font-weight:700;color:#fff;letter-spacing:.02em; }
.bmc-label { font-size:.52rem;color:rgba(255,255,255,.75); }
.best-months-note { font-size:.78rem;color:var(--muted);margin-bottom:.5rem; }
@media (max-width:480px) { .bmc-label { display:none; } .best-months-strip { height:38px; } }

/* ── Timeline (Left cards, center spine, right expand) ───── */
.itin-timeline {
  position: relative;
  max-width: 1100px;
  margin: 0 auto;
}

/* Vertical spine - center */
.itin-timeline::before {
  content: '';
  position: absolute;
  left: 50%;
  top: 0;
  bottom: 0;
  width: 3px;
  transform: translateX(-50%);
  background: linear-gradient(to bottom,
    var(--brand) 0%,
    var(--accent) 33%,
    var(--brand) 66%,
    var(--accent) 100%);
  opacity: 0.18;
  pointer-events: none;
  z-index: 0;
}

/* Timeline item */
.tl-item {
  position: relative;
  display: flex;
  justify-content: center;
  align-items: flex-start;
  margin-bottom: 2.5rem;
  padding-top: 1.5rem;
}

/* Day marker circle - on the RIGHT side of spine */
.tl-marker {
  position: absolute;
  left: 50%;
  top: 0;
  transform: translateX(4px);
  z-index: 2;
  display: flex;
  justify-content: center;
  width: 56px;
}

.tl-day-num {
  width: 48px;
  height: 48px;
  border-radius: 50%;
  color: #fff;
  font-size: 0.7rem;
  font-weight: 800;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  box-shadow:
    0 0 0 4px rgba(45,106,65,0.15),
    0 4px 16px rgba(0,0,0,0.2);
  transition: transform 0.25s ease, box-shadow 0.25s ease;
  text-align: center;
  line-height: 1.2;
  padding: 0.25rem;
}

.tl-item:hover .tl-day-num,
.tl-item:has(.tl-card[open]) .tl-day-num {
  transform: translateX(4px) scale(1.12);
  box-shadow:
    0 0 0 5px rgba(45,106,65,0.2),
    0 6px 20px rgba(0,0,0,0.25);
}

/* Card - LEFT side of spine */
.tl-card {
  width: calc(50% - 3rem);
  max-width: 400px;
  min-width: 280px;
  margin-right: auto;
  margin-left: 0;
  background: var(--surface);
  border: 1px solid var(--line);
  border-radius: 1rem;
  overflow: visible;
  box-shadow: var(--shadow-md);
  transition: box-shadow 0.25s ease, transform 0.25s ease;
}

.tl-item:hover .tl-card {
  box-shadow: var(--shadow-lg);
  transform: translateY(-3px);
}
.tl-item:has(.tl-card[open]) .tl-card {
  box-shadow: var(--shadow-lg);
}

/* Horizontal connector from card to spine */
.tl-horizontal {
  position: absolute;
  top: 1.75rem;
  left: calc(50% - 3rem);
  width: calc(3rem - 24px);
  height: 2px;
  background: linear-gradient(to right, var(--line-strong), transparent);
  z-index: 0;
  pointer-events: none;
}

/* Expanded body - RIGHT side of spine, clears markers */
.tl-card[open] > .tl-body {
  position: absolute;
  left: calc(50% + 4.5rem);
  top: 0;
  width: calc(50% - 5rem);
  max-width: 420px;
  min-width: 260px;
  background: var(--surface);
  border: 1px solid var(--line);
  border-radius: 1rem;
  box-shadow: var(--shadow-lg);
  padding: 1.25rem;
  z-index: 10;
  animation: tl-expand-in 0.3s ease;
}

@keyframes tl-expand-in {
  from { opacity: 0; transform: translateX(-12px); }
  to { opacity: 1; transform: translateX(0); }
}

/* Vertical connector between items */
.tl-connector {
  position: absolute;
  left: 50%;
  transform: translateX(-50%);
  width: 100%;
  height: 2.5rem;
  pointer-events: none;
  z-index: 0;
  top: 100%;
  margin-top: -1.25rem;
}

.tl-connector-line {
  position: absolute;
  left: 50%;
  transform: translateX(-50%);
  width: 3px;
  background: linear-gradient(to bottom, var(--brand), var(--accent));
  opacity: 0.3;
}
.tl-connector-line:first-child {
  top: 0;
  bottom: 50%;
}
.tl-connector-line:last-child {
  top: 50%;
  bottom: 0;
}

.tl-connector-dot {
  position: absolute;
  left: 50%;
  top: 50%;
  transform: translate(-50%, -50%);
  width: 10px;
  height: 10px;
  border-radius: 50%;
  border: 3px solid #fff;
  box-shadow: 0 0 0 2px var(--line);
  z-index: 1;
}

/* Details/summary styling */
.tl-card > summary { list-style: none; }
.tl-card > summary::-webkit-details-marker { display: none; }

.tl-summary {
  display: flex;
  align-items: flex-start;
  gap: 1rem;
  padding: 1.1rem 1.25rem;
  cursor: pointer;
  user-select: none;
  transition: background 150ms;
}
.tl-summary:hover { background: var(--surface-alt); }

.tl-thumb {
  width: 72px;
  height: 64px;
  border-radius: 0.6rem;
  flex-shrink: 0;
  background: linear-gradient(135deg, var(--brand), var(--brand-dark));
  background-size: cover;
  background-position: center;
  border: 1px solid var(--line);
}
@media (max-width: 420px) { .tl-thumb { display: none; } }

.tl-summary-text { flex: 1; min-width: 0; }
.tl-day-label {
  font-size: 0.62rem;
  font-weight: 800;
  letter-spacing: 0.1em;
  text-transform: uppercase;
  color: var(--brand);
  display: block;
  margin-bottom: 0.2rem;
}
.tl-day-title {
  font-size: 0.95rem;
  font-weight: 700;
  color: var(--text);
  margin-bottom: 0.15rem;
  line-height: 1.35;
}
.tl-overnight { font-size: 0.7rem; color: var(--muted); display: block; }

.tl-chevron {
  font-size: 0.7rem;
  color: var(--muted);
  flex-shrink: 0;
  transition: transform 200ms;
  margin-top: 0.2rem;
}
.tl-card[open] > summary .tl-chevron { transform: rotate(180deg); }

.tl-body {
  padding: 0.25rem 1.25rem 1.25rem;
  border-top: 1px solid var(--line);
}
.tl-summary-text-body { font-size: 0.85rem; line-height: 1.75; color: var(--text-body); margin-top: 0.7rem; }
.tl-highlights { display: flex; gap: 0.4rem; flex-wrap: wrap; margin-top: 0.7rem; }
.tl-highlight-chip {
  font-size: 0.66rem;
  font-weight: 600;
  background: var(--surface-alt);
  color: var(--text-body);
  padding: 0.22rem 0.6rem;
  border-radius: 999px;
}
.tl-travel-time { font-size: 0.78rem; color: var(--muted); margin-top: 0.6rem; }

/* Stays section */
.tl-stays {
  margin-top: 1rem;
}
.tl-stays-heading {
  font-size: 0.75rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.08em;
  color: var(--muted);
  margin-bottom: 0.75rem;
}
.tl-stays-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
  gap: 0.75rem;
}
.tl-stay-card {
  text-decoration: none;
  color: inherit;
  border-radius: 0.75rem;
  overflow: hidden;
  background: var(--surface-alt);
  border: 1px solid var(--line);
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.tl-stay-card:hover {
  transform: translateY(-3px);
  box-shadow: var(--shadow-md);
}
.tl-stay-img {
  height: 80px;
  background: linear-gradient(135deg, var(--brand), var(--accent));
  background-size: cover;
  background-position: center;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
}
.tl-stay-info {
  padding: 0.6rem 0.75rem;
  display: flex;
  flex-direction: column;
  gap: 0.15rem;
}
.tl-stay-info strong {
  font-size: 0.78rem;
  color: var(--text);
}
.tl-stay-info span {
  font-size: 0.68rem;
  color: var(--muted);
}
.tl-stays-more {
  display: inline-block;
  margin-top: 0.75rem;
  font-size: 0.78rem;
  font-weight: 600;
  color: var(--brand);
  text-decoration: none;
}
.tl-stays-more:hover { color: var(--brand-dark); }
.tl-stays-note {
  font-size: 0.82rem;
  color: var(--muted);
}
.tl-stays-note a { color: var(--brand); font-weight: 600; }

/* Responsive: stack on mobile */
@media (max-width: 768px) {
  .itin-timeline::before {
    left: 1.5rem;
  }
  .tl-marker {
    left: 0;
    transform: none;
  }
  .tl-item:hover .tl-day-num,
  .tl-item:has(.tl-card[open]) .tl-day-num {
    transform: scale(1.12);
  }
  .tl-item {
    justify-content: flex-start;
    padding-left: 4.5rem;
  }
  .tl-card {
    width: 100%;
    max-width: none;
    margin-left: 0;
    margin-right: 0;
  }
  .tl-horizontal {
    left: 2.75rem;
    width: 1.5rem;
    background: linear-gradient(to right, var(--line-strong), transparent);
  }
  /* On mobile, expanded body goes below the card */
  .tl-card[open] > .tl-body {
    position: static;
    width: 100%;
    max-width: none;
    margin-top: 0.75rem;
    border-radius: 0.75rem;
    animation: none;
  }
}

/* ── Destinations tile grid ──────────────────────────────── */
.dest-carousel-wrapper { position:relative; border-radius:1rem; }
.dest-carousel-track {
  display:grid; grid-template-columns:repeat(auto-fill, minmax(260px, 1fr)); gap:0.75rem;
}
.dest-carousel-item {
  position:relative; min-height:180px; border-radius:0.75rem; overflow:hidden;
  background-size:cover; background-position:center;
  text-decoration:none; color:#fff;
  transition:transform .25s ease, box-shadow .25s ease;
}
.dest-carousel-item:hover { transform:translateY(-3px); box-shadow:0 8px 24px rgba(0,0,0,.2); }
.dest-carousel-item-inner {
  position:absolute; top:0; bottom:0; left:0; right:0;
  padding:1.25rem;
  background:linear-gradient(transparent 40%,rgba(0,0,0,.7));
}
.dest-carousel-item-inner strong { display:block; font-size:1rem; font-weight:700; margin-bottom:.2rem; }
.dest-carousel-item-inner span { font-size:.75rem; opacity:.8; }
.dest-carousel-controls { display:none; }
.dest-carousel-dot { display:none; }
.itin-dest-arrow { display:none; }

/* ── Experience row ──────────────────────────────────────── */
.itin-exp-row { display:flex;gap:.75rem;flex-wrap:wrap; }
.itin-exp-card {
  display:flex;align-items:center;gap:.6rem;
  background:var(--surface);border:1px solid var(--line);
  border-radius:var(--radius-md);text-decoration:none;color:var(--text);
  padding:.6rem .75rem;flex:1;min-width:200px;max-width:320px;
  transition:box-shadow 150ms;
}
.itin-exp-card:hover { box-shadow:var(--shadow-sm); }
.itin-exp-card-img {
  width:48px;height:48px;border-radius:.5rem;flex-shrink:0;
  background:linear-gradient(135deg,var(--brand),var(--brand-dark));
  background-size:cover;background-position:center;
  border:1px solid var(--line);
}
.itin-exp-card-body strong { font-size:.82rem;font-weight:700;display:block;margin-bottom:.15rem; }
.itin-exp-card-body span   { font-size:.7rem;color:var(--muted); }

/* ── Suggested itineraries ───────────────────────────────── */
.itin-suggested-row { display:grid;grid-template-columns:repeat(2,1fr);gap:.75rem; }
@media (max-width:480px) { .itin-suggested-row { grid-template-columns:1fr; } }
.itin-suggested-card {
  display:flex;flex-direction:column;
  background:var(--surface);border:1px solid var(--line);
  border-radius:var(--radius-md);overflow:hidden;
  text-decoration:none;color:var(--text);
  transition:transform 150ms,box-shadow 150ms;
}
.itin-suggested-card:hover { transform:translateY(-3px);box-shadow:var(--shadow-sm); }
.itin-suggested-img {
  height:100px;
  background:linear-gradient(160deg,var(--surface-dark),var(--brand-dark));
  background-size:cover;background-position:center;
  position:relative;
}
.itin-suggested-badge {
  position:absolute;top:.6rem;right:.6rem;
  background:#fff;color:var(--brand);
  font-size:.65rem;font-weight:700;
  padding:.18rem .5rem;border-radius:999px;
}
.itin-suggested-body { padding:.75rem; }
.itin-suggested-body strong { font-size:.82rem;font-weight:700;display:block;margin-bottom:.2rem; }
.itin-suggested-body span   { font-size:.7rem;color:var(--muted); }

/* ── Recommended stays ──────────────────────────────────── */
.tl-stays {
  margin-top:.75rem;
  padding:.65rem .85rem;
  background:var(--surface-alt);
  border:1px solid var(--line);
  border-left:3px solid var(--accent);
  border-radius:var(--radius-sm,6px);
  font-size:.75rem;
}
.tl-stays-label {
  font-weight:700;color:var(--text);display:block;margin-bottom:.4rem;
}
.tl-stays-note {
  margin:0;color:var(--muted);line-height:1.5;
}
.tl-stays-note a {
  color:var(--brand);text-decoration:underline;font-weight:600;
}
.tl-stays-grid {
  display:flex;gap:.5rem;overflow-x:auto;scrollbar-width:none;
  padding-bottom:.25rem;
}
.tl-stays-grid::-webkit-scrollbar { display:none; }
.tl-stay-card {
  flex:0 0 140px;min-height:100px;
  border-radius:var(--radius-sm,6px);overflow:hidden;
  border:1px solid var(--line);
  background:var(--surface);
  text-decoration:none;color:var(--text);
  transition:transform .15s,box-shadow .15s;
  position:relative;
}
.tl-stay-card:hover { transform:translateY(-2px);box-shadow:var(--shadow-sm); }
.tl-stay-img {
  height:56px;
  background:linear-gradient(135deg,var(--brand),var(--accent));
  background-size:cover;background-position:center;
  display:flex;align-items:center;justify-content:center;font-size:1.2rem;
}
.tl-stay-info {
  padding:.35rem .45rem;
}
.tl-stay-info strong {
  display:block;font-size:.68rem;font-weight:700;
  white-space:nowrap;overflow:hidden;text-overflow:ellipsis;
  line-height:1.2;
}
.tl-stay-info span {
  font-size:.6rem;color:var(--muted);
  white-space:nowrap;overflow:hidden;text-overflow:ellipsis;
  display:block;line-height:1.3;
}

/* ── Scroll prompt ───────────────────────────────────────── */
.itin-scroll-prompt {
  background:var(--surface);
  border:1px solid var(--line);
  border-left:4px solid var(--brand);
  border-radius:var(--radius-md);
  box-shadow:var(--shadow-md);
  margin-bottom:2rem;
}
.itin-scroll-prompt-inner {
  display:flex;flex-direction:column;gap:.75rem;
  padding:1.1rem 1.25rem;
}
@media (min-width:560px) {
  .itin-scroll-prompt-inner { flex-direction:row;align-items:center;justify-content:space-between; }
}
.itin-scroll-prompt strong { font-size:.9rem;color:var(--text);display:block;margin-bottom:.2rem; }
.itin-scroll-prompt p      { font-size:.78rem;color:var(--muted);margin:0; }
.itin-scroll-prompt-actions { display:flex;gap:.5rem;flex-wrap:wrap; }
.btn-brand-sm {
  display:inline-block;background:var(--brand);color:#fff;
  font-size:.78rem;font-weight:700;padding:.52rem 1.1rem;
  border-radius:999px;text-decoration:none;white-space:nowrap;
}
.btn-wa-sm {
  display:inline-block;background:#25d366;color:#fff;
  font-size:.78rem;font-weight:700;padding:.52rem 1rem;
  border-radius:999px;text-decoration:none;white-space:nowrap;
}

/* ── Sidebar ─────────────────────────────────────────────── */
.itin-sidebar {
  /* Mobile: above timeline */
  order:-1;
  border-bottom:1px solid var(--line);
  background:var(--surface-alt);
  padding:1.25rem;
}
@media (min-width:860px) {
  .itin-sidebar {
    order:0;
    border-bottom:none;
    border-left:1px solid var(--line);
    padding:2rem 1.5rem;
    position:sticky;
    top:80px;
    max-height:calc(100vh - 100px);
    overflow-y:auto;
    scrollbar-width:thin;
  }
}
.itin-sidebar-heading {
  font-size:.75rem;font-weight:700;
  letter-spacing:.08em;text-transform:uppercase;
  color:var(--text);margin-bottom:.85rem;
}

/* Facts grid: 2-col on mobile, 1-col on sidebar */
.itin-facts { display:grid;grid-template-columns:repeat(2,1fr);gap:0; }
@media (min-width:860px) { .itin-facts { grid-template-columns:1fr; } }
.itin-fact {
  padding:.55rem .25rem;border-bottom:1px solid var(--line);
}
@media (min-width:860px) {
  .itin-fact { display:flex;justify-content:space-between;align-items:flex-start;gap:.5rem; }
}
.itin-fact dt { font-size:.65rem;color:var(--muted);display:block;margin-bottom:.08rem; }
.itin-fact dd { font-size:.78rem;font-weight:700;color:var(--text);margin:0; }
@media (min-width:860px) {
  .itin-fact dt { margin-bottom:0; }
  .itin-fact dd { text-align:right;max-width:55%; }
}

.itin-sidebar-cta {
  display:block;
  background:var(--brand);color:#fff;
  font-size:.84rem;font-weight:700;
  padding:.82rem 1rem;border-radius:var(--radius-sm);
  text-align:center;text-decoration:none;
  margin-top:1rem;transition:background 150ms;
}
.itin-sidebar-cta:hover { background:var(--brand-dark); }
.itin-sidebar-cta--sm   { font-size:.76rem;padding:.55rem 1rem;margin-top:0; }
.itin-sidebar-wa {
  display:flex;align-items:center;justify-content:center;gap:.4rem;
  background:#25d366;color:#fff;
  font-size:.82rem;font-weight:600;
  padding:.7rem 1rem;border-radius:var(--radius-sm);
  text-decoration:none;margin-top:.5rem;
}
.itin-sidebar-form {
  background:var(--surface);border:1px solid var(--line);
  border-radius:var(--radius-md);padding:.9rem;margin-top:1rem;
}
.itin-sidebar-form p { font-size:.72rem;color:var(--muted);line-height:1.5;margin-bottom:.55rem; }
.itin-sidebar-form strong { color:var(--text); }
.itin-sidebar-form input {
  width:100%;border:1px solid var(--line-strong);
  border-radius:var(--radius-sm);
  padding:.42rem .72rem;font-size:.75rem;
  font-family:var(--font-sans);color:var(--text);
  background:var(--surface-alt);margin-bottom:.38rem;outline:none;
  transition:border-color 150ms;
}
.itin-sidebar-form input:focus { border-color:var(--brand); }
</style>

<script>
(function () {
'use strict';
// ── Sidebar quick form — pre-fills WhatsApp ───────────────
const sbSend  = document.getElementById('sbSend');
const sbName  = document.getElementById('sbName');
const sbDates = document.getElementById('sbDates');
const itinName = <?= json_encode($itinName, JSON_HEX_TAG) ?>;
const waBase   = <?= json_encode(base_url('contact'), JSON_HEX_TAG) ?>;

if (sbSend && sbName && sbDates) {
    sbSend.addEventListener('click', function(e) {
        e.preventDefault();
        const name  = sbName.value.trim();
        const dates = sbDates.value.trim();
        if (!name && !dates) { sbName.focus(); return; }
        const msg = `Hi, my name is ${name}. I'm interested in the ${itinName} itinerary${dates ? ' for ' + dates : ''}.`;
        const url = `<?= e(rtrim(base_url('contact'), '/')) ?>?itinerary=${encodeURIComponent(itinName)}&name=${encodeURIComponent(name)}&dates=${encodeURIComponent(dates)}`;
        window.location.href = url;
    });
}

})();
</script>

<script src="<?= e(base_url('assets/js/journey-layouts.js') . '?v=' . filemtime(__DIR__ . '/assets/js/journey-layouts.js')) ?>" defer></script>
<?php
$stickyQuoteUrl = base_url('contact') . '?itinerary=' . rawurlencode($itinName);
$stickyQuoteLabel = 'Enquire';
require __DIR__ . '/includes/trust_links.php';
require __DIR__ . '/includes/footer.php';
?>
