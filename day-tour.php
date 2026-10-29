<?php
/**
 * day-tour.php — Day Tour Detail Page
 * Single-day, chauffeur-guided experience with itinerary, gallery,
 * related destinations/itineraries/experiences, and inline booking CTA.
 */

require_once __DIR__ . '/config/bootstrap.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/schema.php';
require_once __DIR__ . '/includes/responsive_image.php';
require_once __DIR__ . '/includes/route_map_view.php';

// ── Column guards ─────────────────────────────────────────────────────────────
$_hasGallery     = db_table_exists('day_tour_gallery');
$_hasDestPivot   = db_table_exists('day_tour_destination');
$_hasItinPivot   = db_table_exists('day_tour_itinerary');
$_hasExpPivot    = db_table_exists('day_tour_experience');
$_hasBestTime    = db_column_exists('day_tours', 'best_time_json');
$_hasFaq         = db_column_exists('day_tours', 'faq_json');
$_hasMeeting     = db_column_exists('day_tours', 'meeting_point');
$_hasDuration    = db_column_exists('day_tours', 'duration_hours');
$_hasStartTime   = db_column_exists('day_tours', 'start_time');
$_hasVehicle     = db_column_exists('day_tours', 'vehicle_type');
$_hasMaxGroup    = db_column_exists('day_tours', 'max_group_size');
$_hasDifficulty  = db_column_exists('day_tours', 'difficulty');
$_hasIncludes    = db_column_exists('day_tours', 'includes');
$_hasExcludes    = db_column_exists('day_tours', 'excludes');
$_hasHighlights  = db_column_exists('day_tours', 'highlights');
$_hasItinerary   = db_column_exists('day_tours', 'itinerary');

// ── Load day tour ─────────────────────────────────────────────────────────────
$slug = trim((string) get_input('slug', ''));
$id   = (int) get_input('id', 0);

if (!db_table_exists('day_tours')) {
    http_response_code(404);
    require __DIR__ . '/404.php';
    exit;
}

$publishedWhere = db_column_exists('day_tours', 'is_published') ? ' AND is_published = 1' : '';

$dayTour = null;
if ($slug !== '') {
    $dayTour = fetch_one(
        "SELECT * FROM day_tours WHERE slug = :slug {$publishedWhere} LIMIT 1",
        ['slug' => $slug]
    );
}
if (!$dayTour && $id > 0) {
    $dayTour = fetch_one(
        "SELECT * FROM day_tours WHERE id = :id {$publishedWhere} LIMIT 1",
        ['id' => $id]
    );
}
if (!$dayTour) {
    http_response_code(404);
    require __DIR__ . '/404.php';
    exit;
}

// ── Gallery ───────────────────────────────────────────────────────────────────
$gallery = [];
if ($_hasGallery) {
    $gallery = fetch_all(
        "SELECT image_path, alt_text FROM day_tour_gallery
          WHERE day_tour_id = :id ORDER BY sort_order ASC, id ASC",
        ['id' => $dayTour['id']]
    );
}

// ── Related destinations ──────────────────────────────────────────────────────
$relatedDestinations = [];
if ($_hasDestPivot && db_table_exists('destinations')) {
    $relatedDestinations = fetch_all(
        "SELECT d.id, d.name, d.slug, d.excerpt, d.cover_image, d.hero_image,
                " . (db_column_exists('destinations', 'emoji') ? "d.emoji," : "'📍' AS emoji,") . "
                " . (db_column_exists('destinations', 'stay_duration') ? "d.stay_duration" : "'' AS stay_duration") . "
         FROM day_tour_destination dtd
         INNER JOIN destinations d ON d.id = dtd.destination_id
         WHERE dtd.day_tour_id = :id AND d.is_published = 1
         ORDER BY dtd.sort_order ASC, d.name ASC
         LIMIT 6",
        ['id' => $dayTour['id']]
    );
}

// ── Related itineraries (multi-day tours that include this day tour) ──────────
$relatedItineraries = [];
if ($_hasItinPivot && db_table_exists('itineraries')) {
    $relatedItineraries = fetch_all(
        "SELECT i.id, i.name, i.slug, i.days, i.excerpt, i.hero_image, i.og_image
         FROM day_tour_itinerary dti
         INNER JOIN itineraries i ON i.id = dti.itinerary_id
         WHERE dti.day_tour_id = :id AND i.is_published = 1
         ORDER BY i.is_featured DESC, i.sort_order ASC, i.days ASC
         LIMIT 6",
        ['id' => $dayTour['id']]
    );
}

// ── Related experiences ───────────────────────────────────────────────────────
$relatedExperiences = [];
if ($_hasExpPivot && db_table_exists('experiences')) {
    $relatedExperiences = fetch_all(
        "SELECT e.id, e.title, e.slug, e.excerpt, e.hero_image, e.og_image
         FROM day_tour_experience dte
         INNER JOIN experiences e ON e.id = dte.experience_id
         WHERE dte.day_tour_id = :id AND e.is_published = 1
         ORDER BY e.sort_order ASC
         LIMIT 4",
        ['id' => $dayTour['id']]
    );
}

// ── Best time JSON ────────────────────────────────────────────────────────────
$bestTimeMonths = [];
if ($_hasBestTime && !empty($dayTour['best_time_json'])) {
    $decoded = json_decode((string)$dayTour['best_time_json'], true);
    if (is_array($decoded) && isset($decoded['months'])) {
        $bestTimeMonths = $decoded['months'];
    } elseif (is_array($decoded)) {
        $bestTimeMonths = $decoded;
    }
}

// ── FAQ JSON ──────────────────────────────────────────────────────────────────
$faqs = [];
if ($_hasFaq && !empty($dayTour['faq_json'])) {
    $decoded = json_decode((string)$dayTour['faq_json'], true);
    if (is_array($decoded)) {
        $faqs = array_values(array_filter($decoded, fn($f) => is_array($f) && !empty($f['question']) && !empty($f['answer'])));
    }
}

// ── Content fields ────────────────────────────────────────────────────────────
$dtTitle       = trim((string)($dayTour['name'] ?? ''));
$dtExcerpt     = clean_public_text($dayTour['excerpt']           ?? '');
$dtIntro       = clean_public_text($dayTour['intro']             ?? '');
$dtHighlights  = clean_public_text($dayTour['highlights']        ?? '');
$dtItinerary   = clean_public_text($dayTour['itinerary']         ?? '');
$dtIncludes    = clean_public_text($dayTour['includes']          ?? '');
$dtExcludes    = clean_public_text($dayTour['excludes']          ?? '');
$dtMeeting     = clean_public_text($dayTour['meeting_point']     ?? '');
$dtBestTime    = clean_public_text($dayTour['best_time']         ?? '');

// ── Meta fields ───────────────────────────────────────────────────────────────
$durationHours = $_hasDuration && !empty($dayTour['duration_hours']) ? (float)$dayTour['duration_hours'] : 8.0;
$startTime     = $_hasStartTime && !empty($dayTour['start_time']) ? $dayTour['start_time'] : '08:00:00';
$vehicleType   = $_hasVehicle ? $dayTour['vehicle_type'] : 'Air-conditioned car/van';
$maxGroup      = $_hasMaxGroup && !empty($dayTour['max_group_size']) ? (int)$dayTour['max_group_size'] : 6;
$difficulty    = $_hasDifficulty ? $dayTour['difficulty'] : 'easy';

// Price formatting
$priceFrom = (float)($dayTour['price_from'] ?? 0);
$priceTo   = (float)($dayTour['price_to'] ?? 0);
$currency  = $dayTour['price_currency'] ?? 'USD';
$unit      = $dayTour['price_unit'] ?? 'per person (min 2 pax)';

$priceDisplay = '';
if ($priceFrom > 0 && $priceTo > 0 && $priceFrom !== $priceTo) {
    $priceDisplay = '$' . number_format($priceFrom, 0) . '–' . number_format($priceTo, 0) . ' ' . $unit;
} elseif ($priceFrom > 0) {
    $priceDisplay = 'From $' . number_format($priceFrom, 0) . ' ' . $unit;
}

// ── Hero media ────────────────────────────────────────────────────────────────
$heroMedia = content_hero_media($dayTour);
$pageImage = !empty($dayTour['og_image'])
    ? upload_url($dayTour['og_image'])
    : (!empty($dayTour['hero_image']) ? upload_url($dayTour['hero_image']) : DEFAULT_OG_IMAGE);
$heroBg    = $heroMedia['type'] === 'video'
    ? ($heroMedia['poster_url'] ?: $pageImage)
    : ($heroMedia['url'] ?: $pageImage);
$heroBackground = is_placeholder_media_url($heroBg) ? '' : $heroBg;

// ── SEO ───────────────────────────────────────────────────────────────────────
$pageUrl        = !empty($dayTour['canonical_url'])
    ? $dayTour['canonical_url']
    : base_url('day-tours/' . $dayTour['slug']);
$seoTitle       = $dayTour['meta_title']
    ?: ($dtTitle . ' | Private Day Tour Sri Lanka | ' . APP_NAME);
$seoDescription = $dayTour['meta_description']
    ?: ($dtExcerpt ?: 'Private day tour: ' . $dtTitle . ' with chauffeur-guide. Hotel pickup, flexible pace, entrance fees included. ' . $priceDisplay);
$seoCanonical   = $pageUrl;
$seoImage       = $pageImage;
$seoImageWidth  = 1200;
$seoImageHeight = 675;
$seoType        = 'website';

$breadcrumbs = [
    ['name' => 'Home',       'url' => base_url()],
    ['name' => 'Day Tours',  'url' => base_url('day-tours')],
    ['name' => $dtTitle,     'url' => $pageUrl],
];

$schemaItems = [];
foreach ($relatedItineraries as $it) {
    $schemaItems[] = [
        'url'   => base_url('itineraries/' . $it['slug']),
        'name'  => (string)$it['name'],
        'image' => (package_card_image($it, 'itinerary') ?: DEFAULT_OG_IMAGE),
    ];
}
foreach ($relatedDestinations as $rd) {
    $schemaItems[] = [
        'url'   => base_url('destinations/' . $rd['slug']),
        'name'  => (string)$rd['name'],
        'image' => !empty($rd['cover_image'])
            ? upload_url($rd['cover_image'])
            : (!empty($rd['hero_image']) ? upload_url($rd['hero_image']) : DEFAULT_OG_IMAGE),
    ];
}
foreach ($relatedExperiences as $rel) {
    $schemaItems[] = [
        'url'   => base_url('experiences/' . $rel['slug']),
        'name'  => (string)$rel['title'],
        'image' => !empty($rel['hero_image'])
            ? stored_media_url($rel['hero_image'])
            : (!empty($rel['og_image']) ? upload_url($rel['og_image']) : DEFAULT_OG_IMAGE),
    ];
}

$itinerarySegments = array_values(array_filter(array_map('trim', explode("\n", $dtItinerary ?: $dtHighlights))));
$schemaGraph = [
    schema_website(),
    schema_travel_agency(),
    schema_breadcrumbs($breadcrumbs),
    [
        '@type'       => 'TouristTrip',
        'name'        => $dtTitle,
        'url'         => $pageUrl,
        'description' => $seoDescription,
        'image'       => $seoImage,
        'offers'      => [
            '@type'         => 'Offer',
            'price'         => $priceFrom > 0 ? $priceFrom : 85,
            'priceCurrency' => $currency,
            'availability'  => 'https://schema.org/InStock',
            'eligibleRegion' => [
                '@type' => 'Country',
                'name'  => 'LK'
            ],
        ],
        'itinerary'   => [
            '@type' => 'ItemList',
            'itemListElement' => array_map(function($seg, $i) {
                return [
                    '@type' => 'ListItem',
                    'position' => $i + 1,
                    'name' => $seg
                ];
            }, $itinerarySegments, array_keys($itinerarySegments)),
        ],
        'isPartOf'    => ['@id' => base_url() . '#website'],
    ],
];
if ($schemaItems) {
    $schemaGraph[] = schema_item_list($dtTitle . ' Related Content', $schemaItems);
}

$bodyClass = 'page-day-tour';
$extraStylesheets[] = 'assets/css/route-map.css';
require_once __DIR__.'/includes/page_gallery_view.php';
$pageGallery=page_gallery_rows('day_tour',(int)$dayTour['id']);
$seoImageAlt=(string)($dayTour['og_alt']??'');
$extraStylesheets[]='assets/css/page-gallery.css';
if(!empty($dayTour['og_image']))$seoImage=stored_media_url($dayTour['og_image']);
if($pageGallery)$schemaGraph[]=['@type'=>'ImageGallery','name'=>$dtTitle.' photographs','image'=>page_gallery_schema($pageGallery)];
require __DIR__ . '/includes/header.php';

// ── Difficulty badge config ───────────────────────────────────────────────────
$difficultyConfig = [
    'easy'      => ['label' => '🟢 Easy',       'class' => 'difficulty-easy',      'desc' => 'Light walking, suitable for all ages'],
    'moderate'  => ['label' => '🟡 Moderate',   'class' => 'difficulty-moderate',  'desc' => 'Some climbing/steps, moderate fitness'],
    'active'    => ['label' => '🔴 Active',     'class' => 'difficulty-active',    'desc' => 'Strenuous activity, good fitness required'],
];
$diffCfg = $difficultyConfig[$difficulty] ?? $difficultyConfig['easy'];
?>

<!-- ════════════════════════════════════════════════════════════
     HERO
════════════════════════════════════════════════════════════ -->
<section class="dt-detail-hero"
         <?php if ($heroBackground): ?>style="background-image:linear-gradient(160deg,rgba(13,46,26,.9) 0%,rgba(26,90,48,.72) 100%),url('<?= e($heroBackground) ?>');background-size:cover;background-position:center;"<?php endif; ?>>
  <div class="container">
    <nav aria-label="Breadcrumb" style="margin-bottom:1.25rem;">
      <?php require __DIR__ . '/includes/breadcrumbs.php'; ?>
    </nav>

    <span class="section-kicker" style="color:var(--accent-light);">Private Day Tour</span>
    <h1 class="section-heading" style="color:#fff; font-size:clamp(1.8rem,4vw,3rem);">
      <?= e($dtTitle) ?>
    </h1>
    <?php if ($dtExcerpt): ?>
    <p style="color:rgba(255,255,255,.84);font-size:.92rem;line-height:1.75;max-width:560px;margin-top:.6rem;">
      <?= e($dtExcerpt) ?>
    </p>
    <?php endif; ?>

    <!-- Quick-facts chips -->
    <div class="dt-detail-chips" style="margin-top:1.25rem;display:flex;flex-wrap:wrap;gap:.5rem;">
      <span class="dt-detail-chip"><?= e($diffCfg['label']) ?></span>
      <span class="dt-detail-chip">🕐 <?= e($durationHours) ?> hrs</span>
      <span class="dt-detail-chip">👥 Max <?= e($maxGroup) ?> pax</span>
      <span class="dt-detail-chip">🚗 <?= e($vehicleType) ?></span>
      <?php if ($dtBestTime && strlen($dtBestTime) < 40): ?>
      <span class="dt-detail-chip">📅 <?= e($dtBestTime) ?></span>
      <?php endif; ?>
      <?php if ($relatedDestinations): ?>
      <span class="dt-detail-chip">📍 <?= count($relatedDestinations) ?> destination<?= count($relatedDestinations) !== 1 ? 's' : '' ?></span>
      <?php endif; ?>
    </div>

    <!-- CTAs -->
    <div class="dt-detail-ctas" style="margin-top:1.5rem;display:flex;flex-wrap:wrap;gap:.75rem;">
      <a href="<?= e(base_url('contact') . '?day_tour=' . urlencode($dtTitle)) ?>"
         class="btn btn-primary btn-hero-primary"
         onclick="if(typeof trackEvent==='function')trackEvent('contact_cta_click',{label:'day_tour_hero'})">
        Book This Day Tour
      </a>
      <a href="<?= e(site_whatsapp_url('Hi, I\'m interested in the ' . $dtTitle . ' day tour in Sri Lanka.')) ?>"
         class="btn btn-whatsapp btn-hero-wa"
         target="_blank" rel="noopener"
         onclick="if(typeof trackEvent==='function')trackEvent('whatsapp_click',{label:'day_tour_hero'})">
        🟢 WhatsApp Us
      </a>
      <a href="<?= e(base_url('day-tours')) ?>" class="btn btn-ghost btn-hero-ghost">← All Day Tours</a>
    </div>
  </div>
</section>
<!-- ════════════════════════════════════════════════════════════
     INLINE ENQUIRY PROMPT
════════════════════════════════════════════════════════════ -->
<div class="dt-inquiry-prompt">
  <div class="container">
    <div class="dt-inquiry-inner">
      <div class="dt-inquiry-text">
        <strong>Want to add this to your Sri Lanka trip?</strong>
        <p>Tell us your dates and base hotel — we'll confirm availability, timing, and fit it seamlessly into your route.</p>
      </div>
      <div class="dt-inquiry-actions">
        <a href="<?= e(base_url('contact') . '?day_tour=' . urlencode($dtTitle)) ?>"
           class="btn btn-primary btn-brand-sm">Add to My Trip →</a>
        <a href="<?= e(site_whatsapp_url('Hi, I\'m interested in adding ' . $dtTitle . ' to my Sri Lanka trip.')) ?>"
           class="btn btn-whatsapp btn-wa-sm" target="_blank" rel="noopener">🟢 WhatsApp</a>
      </div>
    </div>
  </div>
</div>

<!-- ════════════════════════════════════════════════════════════
     OVERVIEW / INTRO
════════════════════════════════════════════════════════════ -->
<section class="dt-section">
  <div class="container dt-section-prose">
    <div class="package-overview-map"><div>
    <h2 class="dt-section-heading">About This <em>Day Tour</em></h2>
    <div class="dt-prose"><?= $dtIntro ?></div>
    </div><?= route_map_public('day_tour', $dayTour) ?></div>
  </div>
</section>

<!-- ════════════════════════════════════════════════════════════
     HIGHLIGHTS
════════════════════════════════════════════════════════════ -->
<?php if ($dtHighlights): ?>
<section class="dt-section dt-section--alt" id="highlights">
  <div class="container">
    <h2 class="dt-section-heading">Tour <em>Highlights</em></h2>
    <div class="dt-prose"><?= $dtHighlights ?></div>
  </div>
</section>
<?php endif; ?>

<!-- ════════════════════════════════════════════════════════════
     DETAILED ITINERARY
════════════════════════════════════════════════════════════ -->
<?php if ($dtItinerary): ?>
<section class="dt-section" id="itinerary">
  <div class="container">
    <h2 class="dt-section-heading">How the <em>Day Unfolds</em></h2>
    <p class="dt-section-sub">Timings are approximate — your chauffeur-guide adjusts pace to your interests.</p>
    <div class="dt-prose"><?= $dtItinerary ?></div>
  </div>
</section>
<?php endif; ?>

<!-- ════════════════════════════════════════════════════════════
     WHAT'S INCLUDED / EXCLUDED
════════════════════════════════════════════════════════════ -->
<?php if ($dtIncludes || $dtExcludes): ?>
<section class="dt-section dt-section--alt" id="inclusions">
  <div class="container dt-2col">
    <?php if ($dtIncludes): ?>
    <div>
      <h2 class="dt-section-heading">✅ What's <em>Included</em></h2>
      <div class="dt-prose"><?= $dtIncludes ?></div>
    </div>
    <?php endif; ?>
    <?php if ($dtExcludes): ?>
    <div>
      <h2 class="dt-section-heading">❌ What's <em>Not Included</em></h2>
      <div class="dt-prose"><?= $dtExcludes ?></div>
    </div>
    <?php endif; ?>
  </div>
</section>
<?php endif; ?>

<!-- ════════════════════════════════════════════════════════════
     GALLERY
════════════════════════════════════════════════════════════ -->


<!-- ════════════════════════════════════════════════════════════
     PRACTICAL INFO
════════════════════════════════════════════════════════════ -->
<section class="dt-section dt-section--alt" id="practical">
  <div class="container">
    <h2 class="dt-section-heading">Practical <em>Information</em></h2>
    <div class="dt-practical-grid">
      <div class="dt-practical-card">
        <h3>🕐 Duration & Timing</h3>
        <p><strong>Typical duration:</strong> <?= e($durationHours) ?> hours</p>
        <p><strong>Usual start:</strong> <?= e(date('g:i A', strtotime($startTime))) ?> (flexible)</p>
        <p><strong>Pickup:</strong> <?= e($dtMeeting ?: 'Your hotel in the region') ?></p>
      </div>
      <div class="dt-practical-card">
        <h3>🚗 Vehicle & Group</h3>
        <p><strong>Vehicle:</strong> <?= e($vehicleType) ?></p>
        <p><strong>Max group size:</strong> <?= e($maxGroup) ?> passengers</p>
        <p><strong>Guide:</strong> English-speaking chauffeur-guide</p>
      </div>
      <div class="dt-practical-card">
        <h3><?= $diffCfg['label'] ?></h3>
        <p><?= e($diffCfg['desc']) ?></p>
      </div>
      <div class="dt-practical-card">
        <h3>💰 Pricing</h3>
        <p><strong><?= e($priceDisplay ?: 'From $85 per person (min 2 pax)') ?></strong></p>
        <p class="text-secondary small">Private tour — price varies by group size, season, and pickup location. <a href="<?= e(base_url('contact') . '?day_tour=' . urlencode($dtTitle)) ?>">Get exact quote →</a></p>
      </div>
    </div>
  </div>
</section>

<!-- ════════════════════════════════════════════════════════════
     BEST TIME TO VISIT (Monthly strip)
════════════════════════════════════════════════════════════ -->
<?php if ($bestTimeMonths): ?>
<section class="dt-section" id="best-time">
  <div class="container">
    <h2 class="dt-section-heading">Best Time to <em>Go</em></h2>
    <div class="dt-best-time-strip" role="list" aria-label="Monthly suitability ratings">
      <?php
      $monthOrder = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
      $ratingColors = [
          'great' => ['bg' => 'var(--brand)', 'fg' => '#fff', 'label' => 'Great'],
          'okay'  => ['bg' => 'var(--accent)', 'fg' => '#fff', 'label' => 'Okay'],
          'avoid' => ['bg' => '#e8e8e8', 'fg' => '#666', 'label' => 'Avoid'],
      ];
      foreach ($monthOrder as $m):
          $rating = 'okay';
          foreach ($bestTimeMonths as $btm) {
              if (($btm['month'] ?? '') === $m) {
                  $rating = $btm['rating'] ?? 'okay';
                  break;
              }
          }
          $c = $ratingColors[$rating] ?? $ratingColors['okay'];
      ?>
      <div class="dt-month-cell" style="background:<?= $c['bg'] ?>;color:<?= $c['fg'] ?>;" role="listitem" aria-label="<?= $m ?>: <?= $c['label'] ?>">
        <span class="dt-month-abbr"><?= $m ?></span>
        <span class="dt-month-rating"><?= $c['label'] ?></span>
      </div>
      <?php endforeach; ?>
    </div>
    <p class="dt-best-time-note">Ratings reflect typical weather & wildlife conditions. Your experience may vary — we'll advise at booking.</p>
  </div>
</section>
<?php endif; ?>

<!-- ════════════════════════════════════════════════════════════
     FAQ
════════════════════════════════════════════════════════════ -->
<?php if ($faqs): ?>
<section class="dt-section dt-section--alt" id="faq">
  <div class="container">
    <h2 class="dt-section-heading">Frequently Asked <em>Questions</em></h2>
    <div class="dt-faq-list">
      <?php foreach ($faqs as $fi => $faq): ?>
      <details class="dt-faq-item">
        <summary><?= e($faq['question']) ?></summary>
        <div class="dt-faq-answer"><?= e($faq['answer']) ?></div>
      </details>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- ════════════════════════════════════════════════════════════
     RELATED DESTINATIONS
════════════════════════════════════════════════════════════ -->
<?php if ($relatedDestinations): ?>
<section class="dt-section dt-section--alt" id="destinations">
  <div class="container">
    <h2 class="dt-section-heading">Destinations <em>Visited</em></h2>
    <div class="dt-dest-scroll">
      <?php foreach ($relatedDestinations as $rd):
          $rdImg = !empty($rd['cover_image'])
              ? upload_url($rd['cover_image'])
              : (!empty($rd['hero_image']) ? upload_url($rd['hero_image']) : '');
      ?>
      <a href="<?= e(base_url('destinations/' . $rd['slug'])) ?>" class="dt-dest-chip">
        <div class="dt-dest-chip-img"
             <?php if ($rdImg): ?>style="background-image:url('<?= e($rdImg) ?>')"<?php endif; ?>
             role="img" aria-label="<?= e($rd['name']) ?>">
          <?php if (!$rdImg): ?><span aria-hidden="true"><?= e($rd['emoji'] ?? '📍') ?></span><?php endif; ?>
        </div>
        <div class="dt-dest-chip-label"><?= e($rd['name']) ?></div>
      </a>
      <?php endforeach; ?>
      <a href="<?= e(base_url('destinations')) ?>" class="dt-dest-chip dt-dest-chip--more">
        <div class="dt-dest-chip-img dt-dest-chip-img--more" aria-hidden="true">→</div>
        <div class="dt-dest-chip-label">All Destinations</div>
      </a>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- ════════════════════════════════════════════════════════════
     RELATED ITINERARIES (Multi-day tours that include this day tour)
════════════════════════════════════════════════════════════ -->
<?php if ($relatedItineraries): ?>
<section class="dt-section" id="itineraries">
  <div class="container">
    <span class="section-kicker">Extend Your Trip</span>
    <h2 class="dt-section-heading">Multi-Day Itineraries That <em>Include This Day Tour</em></h2>
    <p class="dt-section-sub">Use these as starting points — we'll adjust the route around your dates and pace.</p>
    <div class="dt-itin-row">
      <?php foreach ($relatedItineraries as $it):
          $itImg  = (package_card_image($it, 'itinerary') ?: '');
          $itDays = (int)$it['days'];
      ?>
      <a href="<?= e(base_url('itineraries/' . $it['slug'])) ?>" class="dt-itin-card">
        <div class="dt-itin-card-img"
             <?php if ($itImg): ?>style="background-image:url('<?= e($itImg) ?>')"<?php endif; ?>>
          <?php if ($itDays > 0): ?>
          <span class="dt-itin-badge"><?= $itDays ?> days</span>
          <?php endif; ?>
        </div>
        <div class="dt-itin-card-body">
          <strong><?= e($it['name']) ?></strong>
          <?php if ($it['excerpt'] ?? ''): ?>
          <span><?= e(mb_strimwidth($it['excerpt'], 0, 65, '…')) ?></span>
          <?php endif; ?>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
    <p style="margin-top:1.25rem;">
      <a href="<?= e(base_url('itineraries')) ?>" class="dt-all-link">View all itineraries →</a>
    </p>
  </div>
</section>
<?php endif; ?>

<!-- ════════════════════════════════════════════════════════════
     RELATED EXPERIENCES
════════════════════════════════════════════════════════════ -->
<?php if ($relatedExperiences): ?>
<section class="dt-section dt-section--alt" id="experiences">
  <div class="container">
    <h2 class="dt-section-heading">Related <em>Experiences</em></h2>
    <div class="dt-exp-row">
      <?php foreach ($relatedExperiences as $rel):
          $relImg = !empty($rel['hero_image'])
              ? stored_media_url($rel['hero_image'])
              : (!empty($rel['og_image']) ? upload_url($rel['og_image']) : '');
      ?>
      <a href="<?= e(base_url('experiences/' . $rel['slug'])) ?>" class="dt-exp-card">
        <div class="dt-exp-img"
             <?php if ($relImg): ?>style="background-image:url('<?= e($relImg) ?>')"<?php endif; ?>></div>
        <div class="dt-exp-body">
          <strong><?= e($rel['title']) ?></strong>
          <?php if ($rel['excerpt'] ?? ''): ?>
          <span><?= e(mb_strimwidth($rel['excerpt'], 0, 55, '…')) ?></span>
          <?php endif; ?>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- ════════════════════════════════════════════════════════════
     FINAL CTA
════════════════════════════════════════════════════════════ -->
<?php page_gallery_assets();page_gallery_public($pageGallery,$dtTitle); ?>

<section class="dt-section dt-final-cta">
  <div class="container text-center">
    <h2 class="dt-section-heading">Ready to <em>Book</em> or Customize?</h2>
    <p class="lead">Tell us your travel dates, pickup hotel, and any special requests. We'll confirm availability and send a detailed quote within a few hours.</p>
    <div class="dt-final-cta-actions" style="display:flex;flex-wrap:wrap;gap:.75rem;justify-content:center;margin-top:1.5rem;">
      <a href="<?= e(base_url('contact') . '?day_tour=' . urlencode($dtTitle)) ?>" class="btn btn-primary btn-lg">Get My Quote</a>
      <a href="<?= e(site_whatsapp_url('Hi, I\'d like to book the ' . $dtTitle . ' day tour. My dates: [add dates]. Pickup: [add hotel].')) ?>" class="btn btn-whatsapp btn-lg" target="_blank" rel="noopener">🟢 WhatsApp</a>
    </div>
  </div>
</section>

<style>
/* ── Day Tour Detail – Page-level styles ──────────────────── */

/* Overlay header: transparent nav so hero bg shows through */
.page-day-tour .site-shell-header {
  position: fixed !important;
  top: 0; left: 0; width: 100%;
  z-index: 1040;
  background: transparent !important;
  border-bottom-color: transparent !important;
  box-shadow: none !important;
  backdrop-filter: none;
  transition: background-color 220ms ease, backdrop-filter 220ms ease,
              border-color 220ms ease, box-shadow 220ms ease;
}
.page-day-tour .site-shell-header .navbar {
  background: transparent !important;
}
.page-day-tour .site-shell-header .navbar-brand,
.page-day-tour .site-shell-header .nav-link {
  color: #ffffff !important;
}
.page-day-tour .site-shell-header .nav-link:hover {
  color: rgba(255,255,255,.78) !important;
}
.page-day-tour .site-shell-header .navbar .btn-success {
  background: rgba(255,255,255,.14);
  color: #fff; border: 1px solid rgba(255,255,255,.20);
}
.page-day-tour .site-shell-header .navbar .btn-success:hover {
  background: rgba(255,255,255,.22);
}
.page-day-tour.is-scrolled .site-shell-header {
  background: rgba(240,236,227,.92) !important;
  backdrop-filter: blur(12px);
  border-bottom-color: var(--line) !important;
  box-shadow: 0 8px 24px rgba(12,35,22,.08) !important;
}
.page-day-tour.is-scrolled .site-shell-header .navbar-brand,
.page-day-tour.is-scrolled .site-shell-header .nav-link {
  color: var(--text) !important;
}
.page-day-tour.is-scrolled .site-shell-header .navbar .btn-success {
  background: var(--text); color: #fff; border-color: var(--text);
}
@media (max-width: 991.98px) {
  .page-day-tour .site-shell-header .navbar-collapse {
    margin-top: .75rem; padding: 1rem; border-radius: 1rem;
    background: rgba(13,31,20,.88); backdrop-filter: blur(12px);
  }
  .page-day-tour.is-scrolled .site-shell-header .navbar-collapse {
    background: rgba(240,236,227,.97);
  }
  .page-day-tour.is-scrolled .site-shell-header .navbar-collapse .nav-link {
    color: var(--text) !important;
  }
}

/* ── Hero ───────────────────────────────────────────────── */
.dt-detail-hero {
  background: linear-gradient(160deg,#0d2e1a 0%,#1a5c38 60%,#2d6a41 100%);
  padding: var(--site-overlay-hero-offset, 8.75rem) 0 2.5rem;
  position: relative;
  overflow: hidden;
}
.dt-detail-hero::before {
  content: '';
  position: absolute; inset: 0;
  background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none'%3E%3Cg fill='%23ffffff' fill-opacity='0.03'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
  pointer-events: none;
}
.dt-detail-hero .section-kicker { color: var(--accent-light); }
@media (max-width: 679.98px) {
  .dt-detail-hero { padding-top: var(--site-overlay-hero-offset, 9rem); }
}

/* Hero chips */
.dt-detail-chip {
  background: rgba(255,255,255,.10);
  border: 1px solid rgba(255,255,255,.18);
  color: rgba(255,255,255,.84);
  font-size: .72rem; font-weight: 600;
  padding: .32rem .8rem; border-radius: 999px;
  white-space: nowrap;
  letter-spacing: .02em;
}

/* Hero CTAs */
.dt-detail-ctas { margin-top: 1.5rem; display: flex; flex-wrap: wrap; gap: .75rem; }
.btn-hero-primary {
  display: inline-flex; align-items: center; gap: .35rem;
  background: var(--accent); color: #fff;
  font-size: .82rem; font-weight: 700;
  padding: .62rem 1.5rem; border-radius: 999px;
  text-decoration: none; transition: background 150ms;
}
.btn-hero-primary:hover { background: #7a6922; color: #fff; }
.btn-hero-wa {
  display: inline-flex; align-items: center; gap: .35rem;
  background: #25d366; color: #fff;
  font-size: .82rem; font-weight: 700;
  padding: .62rem 1.5rem; border-radius: 999px;
  text-decoration: none; transition: opacity 150ms;
}
.btn-hero-wa:hover { opacity: .85; color: #fff; }
.btn-hero-ghost {
  display: inline-flex; align-items: center; gap: .35rem;
  background: rgba(255,255,255,.10); color: rgba(255,255,255,.78);
  font-size: .78rem; font-weight: 600;
  padding: .54rem 1.2rem; border-radius: 999px;
  text-decoration: none; transition: background 150ms, color 150ms;
  border: 1px solid rgba(255,255,255,.20);
}
.btn-hero-ghost:hover { background: rgba(255,255,255,.18); color: #fff; }

/* ── Enquiry prompt bar ──────────────────────────────────── */
.dt-inquiry-prompt {
  background: var(--surface-alt); padding: 1.25rem 0;
  border-bottom: 1px solid var(--line);
}
.dt-inquiry-inner {
  display: flex; align-items: center; justify-content: space-between; gap: 1rem;
  flex-wrap: wrap;
}
.dt-inquiry-text strong { font-size: .88rem; color: var(--text); display: block; }
.dt-inquiry-text p { font-size: .76rem; color: var(--muted); margin: .15rem 0 0; }
.dt-inquiry-actions { display: flex; gap: .6rem; flex-shrink: 0; }
.btn-brand-sm {
  display: inline-flex; align-items: center; gap: .3rem;
  background: var(--brand); color: #fff;
  font-size: .74rem; font-weight: 700;
  padding: .48rem 1.1rem; border-radius: 999px;
  text-decoration: none; transition: background 150ms; white-space: nowrap;
}
.btn-brand-sm:hover { background: var(--brand-dark); color: #fff; }
.btn-wa-sm {
  display: inline-flex; align-items: center; gap: .3rem;
  background: #25d366; color: #fff;
  font-size: .74rem; font-weight: 700;
  padding: .48rem 1.1rem; border-radius: 999px;
  text-decoration: none; transition: opacity 150ms; white-space: nowrap;
}
.btn-wa-sm:hover { opacity: .85; color: #fff; }

/* ── Generic section template ────────────────────────────── */
.dt-section {
  padding: 2.5rem 0;
}
.dt-section--alt {
  background: var(--surface-alt);
}
.dt-section-heading {
  font-size: clamp(1.3rem, 2.2vw, 1.75rem);
  font-weight: 700; color: var(--text); margin-bottom: .75rem;
  font-family: var(--font-display);
}
.dt-section-heading em { font-style: italic; color: var(--accent); }
.dt-section-sub { font-size: .82rem; color: var(--muted); margin-top: -.25rem; margin-bottom: 1.25rem; }

/* Content prose */
.dt-prose { font-size: .88rem; line-height: 1.85; color: var(--text-body); }
.dt-prose p { margin-bottom: .85rem; }
.dt-prose ul, .dt-prose ol { padding-left: 1.25rem; margin-bottom: .85rem; }
.dt-prose li { margin-bottom: .4rem; }
.dt-prose strong { color: var(--text); }

/* ── 2-column (included / excluded) ──────────────────────── */
.dt-2col {
  display: grid;
  grid-template-columns: 1fr;
  gap: 1.5rem;
}
@media (min-width: 640px) {
  .dt-2col { grid-template-columns: 1fr 1fr; }
}

/* ── Gallery grid ────────────────────────────────────────── */
.dt-gallery-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: .65rem;
}
@media (min-width: 720px) {
  .dt-gallery-grid { grid-template-columns: 1fr 1fr 1fr; }
}
.dt-gallery-cell { border-radius: var(--radius-sm); overflow: hidden; }
.dt-gallery-cell img { width: 100%; height: 200px; object-fit: cover; display: block; }
@media (min-width: 720px) {
  .dt-gallery-cell img { height: 220px; }
}

/* ── Practical info grid ─────────────────────────────────── */
.dt-practical-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: .85rem;
}
@media (min-width: 560px) {
  .dt-practical-grid { grid-template-columns: 1fr 1fr; }
}
@media (min-width: 960px) {
  .dt-practical-grid { grid-template-columns: 1fr 1fr 1fr 1fr; }
}
.dt-practical-card {
  background: var(--surface);
  border: 1px solid var(--line);
  border-radius: var(--radius-md);
  padding: 1.15rem;
  box-shadow: var(--shadow-sm);
}
.dt-practical-card h3 {
  font-size: .88rem; font-weight: 700; color: var(--text);
  margin-bottom: .55rem;
}
.dt-practical-card p {
  font-size: .74rem; color: var(--text-body); line-height: 1.6;
  margin-bottom: .3rem;
}
.dt-practical-card p:last-child { margin-bottom: 0; }

/* ── Best-time monthly strip ─────────────────────────────── */
.dt-best-time-strip {
  display: flex; gap: 3px; flex-wrap: wrap;
}
.dt-month-cell {
  flex: 1 0 0; min-width: 50px;
  text-align: center;
  padding: .5rem .25rem;
  border-radius: 6px;
  font-size: .7rem; font-weight: 700;
  line-height: 1.3;
  transition: transform 150ms;
}
.dt-month-cell:hover { transform: scale(1.04); }
.dt-month-abbr { display: block; font-size: .65rem; text-transform: uppercase; letter-spacing: .03em; }
.dt-month-rating { display: block; font-size: .55rem; opacity: .85; }
.dt-best-time-note { font-size: .72rem; color: var(--muted); margin-top: .85rem; }

/* ── FAQ ─────────────────────────────────────────────────── */
.dt-faq-list {
  display: flex; flex-direction: column; gap: .4rem;
  max-width: 720px;
}
.dt-faq-item {
  background: var(--surface);
  border: 1px solid var(--line);
  border-radius: var(--radius-sm);
  overflow: hidden;
  transition: box-shadow 150ms;
}
.dt-faq-item[open] { box-shadow: var(--shadow-sm); }
.dt-faq-item summary {
  padding: .85rem 1rem;
  font-size: .82rem; font-weight: 700; color: var(--text);
  cursor: pointer;
  list-style: none;
  display: flex; align-items: center; justify-content: space-between;
}
.dt-faq-item summary::-webkit-details-marker { display: none; }
.dt-faq-item summary::after {
  content: '+'; font-size: 1.1rem; color: var(--brand);
  transition: transform 200ms; flex-shrink: 0; margin-left: .75rem;
}
.dt-faq-item[open] summary::after { transform: rotate(45deg); }
.dt-faq-answer {
  padding: 0 1rem .85rem;
  font-size: .78rem; color: var(--text-body); line-height: 1.7;
}

/* ── Destination chips (scrollable) ──────────────────────── */
.dt-dest-scroll {
  display: flex; gap: .7rem; overflow-x: auto;
  -webkit-overflow-scrolling: touch;
  padding-bottom: .5rem;
  scrollbar-width: thin;
  scrollbar-color: var(--line) transparent;
}
.dt-dest-chip {
  display: flex; flex-direction: column; align-items: center;
  gap: .4rem; text-decoration: none;
  flex-shrink: 0; width: 100px;
  transition: transform 150ms;
}
.dt-dest-chip:hover { transform: translateY(-3px); }
.dt-dest-chip-img {
  width: 72px; height: 72px;
  border-radius: 50%;
  background: linear-gradient(135deg,#2d6a41,#8f7a28);
  background-size: cover; background-position: center;
  display: flex; align-items: center; justify-content: center;
  font-size: 1.3rem;
  transition: box-shadow 200ms;
}
.dt-dest-chip:hover .dt-dest-chip-img { box-shadow: 0 4px 14px rgba(12,35,22,.14); }
.dt-dest-chip-label {
  font-size: .66rem; font-weight: 600; color: var(--text);
  text-align: center; line-height: 1.3;
}
.dt-dest-chip--more .dt-dest-chip-img--more {
  background: var(--surface-alt); border: 1px solid var(--line);
  font-size: 1.1rem; color: var(--muted);
}

/* ── Related itineraries ─────────────────────────────────── */
.dt-itin-row {
  display: flex; gap: .85rem; overflow-x: auto;
  -webkit-overflow-scrolling: touch; padding-bottom: .5rem;
  scrollbar-width: thin; scrollbar-color: var(--line) transparent;
}
.dt-itin-card {
  flex-shrink: 0; width: 240px;
  border-radius: var(--radius-sm);
  overflow: hidden; border: 1px solid var(--line);
  background: var(--surface);
  text-decoration: none;
  transition: transform 200ms, box-shadow 200ms;
}
.dt-itin-card:hover { transform: translateY(-3px); box-shadow: var(--shadow-md); }
.dt-itin-card-img {
  height: 130px;
  background: linear-gradient(160deg,#1a5c38,#2d6a41);
  background-size: cover; background-position: center;
  position: relative;
}
.dt-itin-badge {
  position: absolute; bottom: .55rem; left: .55rem;
  background: rgba(0,0,0,.45); color: #fff;
  font-size: .62rem; font-weight: 700;
  padding: .18rem .5rem; border-radius: 3px;
}
.dt-itin-card-body {
  padding: .75rem;
  display: flex; flex-direction: column; gap: .2rem;
}
.dt-itin-card-body strong { font-size: .78rem; color: var(--text); }
.dt-itin-card-body span { font-size: .66rem; color: var(--muted); }
.dt-all-link { font-size: .78rem; font-weight: 700; color: var(--brand); }

/* ── Related experiences ─────────────────────────────────── */
.dt-exp-row {
  display: flex; gap: .85rem; overflow-x: auto;
  -webkit-overflow-scrolling: touch; padding-bottom: .5rem;
  scrollbar-width: thin; scrollbar-color: var(--line) transparent;
}
.dt-exp-card {
  flex-shrink: 0; width: 210px;
  border-radius: var(--radius-sm);
  overflow: hidden; border: 1px solid var(--line);
  background: var(--surface);
  text-decoration: none;
  transition: transform 200ms, box-shadow 200ms;
}
.dt-exp-card:hover { transform: translateY(-3px); box-shadow: var(--shadow-md); }
.dt-exp-img {
  height: 120px;
  background: linear-gradient(135deg,#2d6a41,#8f7a28);
  background-size: cover; background-position: center;
}
.dt-exp-body {
  padding: .7rem;
  display: flex; flex-direction: column; gap: .2rem;
}
.dt-exp-body strong { font-size: .76rem; color: var(--text); }
.dt-exp-body span { font-size: .65rem; color: var(--muted); }

/* ── Final CTA ───────────────────────────────────────────── */
.dt-final-cta { text-align: center; }
.dt-final-cta .lead { font-size: .88rem; color: var(--muted); max-width: 560px; margin: 0 auto; }

/* ── Utility ─────────────────────────────────────────────── */
.text-secondary { color: var(--muted); }
.small { font-size: .72rem; }
</style>

<?php
schema_output($schemaGraph);
require __DIR__ . '/includes/footer.php';