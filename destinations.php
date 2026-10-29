<?php
/**
 * destinations.php — GEM redesign
 * Map-first destinations listing with interactive SVG map,
 * region filter pills, card grid, and session-backed route builder.
 */

require_once __DIR__ . '/config/bootstrap.php';
require_once __DIR__ . '/includes/responsive_image.php';

if (is_post()) {
    $action = trim((string) post_input('action', ''));
    if ($action !== 'save_route') {
        http_response_code(400);
        exit;
    }

    guard_post_size();
    require_csrf();

    $postedToken = trim((string) post_input('token', ''));
    $cookieToken = route_session_token(false);
    if ($cookieToken === '' || !hash_equals($cookieToken, $postedToken)) {
        http_response_code(204);
        exit;
    }

    $allowedSlugs = [];
    if (db_table_exists('destinations') && db_column_exists('destinations', 'slug')) {
        $publishedFilter = db_column_exists('destinations', 'is_published') ? ' WHERE is_published = 1' : '';
        $allowedSlugs = array_map(
            static fn(array $row): string => (string) ($row['slug'] ?? ''),
            fetch_all('SELECT slug FROM destinations' . $publishedFilter)
        );
    }

    $slugs = route_session_sanitize_slugs(post_input('dest_slugs', ''), $allowedSlugs, 20);
    route_session_save_slugs($postedToken, 'dest_slugs', $slugs);
    http_response_code(204);
    exit;
}

// ── Route session (wishlist persistence) ─────────────────────────────────────
// Read the visitor's saved route from sessionStorage cookie fallback.
// The JS layer also writes to sessionStorage directly for speed.
$routeToken    = route_session_token();
$routeSlugs    = route_session_fetch_slugs($routeToken, 'dest_slugs');
$routeBarData  = [];

// ── Fetch destinations ────────────────────────────────────────────────────────
// Build SELECT dynamically — gracefully handles installs that haven't
// run the GEM migration yet.
$_hasMapX      = db_column_exists('destinations', 'map_x');
$_hasEmoji     = db_column_exists('destinations', 'emoji');
$_hasRegion    = db_column_exists('destinations', 'region_slug');
$_hasStay      = db_column_exists('destinations', 'stay_duration');
$_hasItinCount = db_column_exists('destinations', 'itinerary_count');

$_selectExtras = implode(', ', array_filter([
    $_hasMapX      ? 'd.map_x, d.map_y, d.map_r, d.map_initial' : null,
    $_hasEmoji     ? 'd.emoji' : null,
    $_hasRegion    ? 'd.region_slug, d.region_label' : null,
    $_hasStay      ? 'd.stay_duration, d.best_time' : null,
    $_hasItinCount ? 'd.itinerary_count' : null,
]));
if ($_selectExtras !== '') {
    $_selectExtras = ', ' . $_selectExtras;
}

// Count itineraries linked to each destination if denorm column missing
$_itinCountExpr = $_hasItinCount
    ? 'd.itinerary_count'
    : '(SELECT COUNT(*) FROM destination_itinerary di
        INNER JOIN itineraries ii ON ii.id = di.itinerary_id AND ii.is_published = 1
        WHERE di.destination_id = d.id) AS itinerary_count';

$destinations = fetch_all(
    "SELECT d.id, d.name, d.slug, d.excerpt, d.hero_image, d.cover_image, d.og_image,
            d.recommended_stay, d.best_time, d.destination_type,
            d.is_featured, d.sort_order,
            {$_itinCountExpr}
            {$_selectExtras}
     FROM destinations d
     WHERE d.is_published = 1
     ORDER BY d.is_featured DESC, d.sort_order ASC, d.name ASC"
);

// ── Build region list from actual data ───────────────────────────────────────
$_regionMap = [
    'cultural' => ['label' => 'Cultural Triangle', 'emoji' => '🏛'],
    'hill'     => ['label' => 'Hill Country',      'emoji' => '⛰'],
    'wildlife' => ['label' => 'Wildlife',           'emoji' => '🐘'],
    'south'    => ['label' => 'South Coast',        'emoji' => '🏖'],
    'east'     => ['label' => 'East Coast',         'emoji' => '🌊'],
];

// Only surface regions that actually have destinations
$_activeRegionSlugs = [];
foreach ($destinations as $d) {
    $rs = $d['region_slug'] ?? '';
    if ($rs !== '' && isset($_regionMap[$rs])) {
        $_activeRegionSlugs[$rs] = true;
    }
}

// Hero image: use first featured destination, or random
$_heroImage = '';
foreach ($destinations as $d) {
    if (!empty($d['hero_image'])) {
        $_heroImage = upload_url($d['hero_image']);
        if ($d['is_featured']) break;
    }
}
$_heroBackground = is_placeholder_media_url($_heroImage) ? '' : $_heroImage;

// ── SEO ───────────────────────────────────────────────────────────────────────
$seoTitle       = 'Sri Lanka Destinations | Private Travel Guide | ' . APP_NAME;
$seoDescription = 'Explore Sri Lanka\'s top destinations — cultural landmarks, hill country, wildlife parks, and coastal beaches. All reachable with a private driver and comfortable vehicle.';
$seoCanonical   = base_url('destinations');
$seoImage       = $_heroImage ?: DEFAULT_OG_IMAGE;
$seoType        = 'website';

$breadcrumbs = [
    ['name' => 'Home',         'url' => base_url()],
    ['name' => 'Destinations', 'url' => $seoCanonical],
];

$schemaItems = [];
foreach ($destinations as $d) {
    $schemaItems[] = [
        'url'   => base_url('destinations/' . $d['slug']),
        'name'  => (string)$d['name'],
        'image' => !empty($d['og_image'])
            ? upload_url($d['og_image'])
            : (!empty($d['hero_image']) ? upload_url($d['hero_image']) : DEFAULT_OG_IMAGE),
    ];
}

$schemaGraph = [
    schema_website(),
    schema_travel_agency(),
    schema_breadcrumbs($breadcrumbs),
    schema_item_list('Sri Lanka Destinations', $schemaItems),
];

$bodyClass = 'page-destinations';
require __DIR__ . '/includes/header.php';

// ── Build JS-safe data payload ────────────────────────────────────────────────
// Only columns that exist are output; everything else falls back to ''.
$_jsDestinations = [];
foreach ($destinations as $d) {
    $_jsDestinations[$d['slug']] = [
        'name'        => (string)$d['name'],
        'region'      => (string)($d['region_slug']  ?? ''),
        'regionLabel' => (string)($d['region_label'] ?? ($d['destination_type'] ?? '')),
        'desc'        => (string)(
            $d['excerpt'] ?: mb_strimwidth(strip_tags((string)($d['intro'] ?? '')), 0, 160, '…')
        ),
        'emoji'       => (string)($d['emoji'] ?? '📍'),
        'stay'        => (string)($d['stay_duration'] ?? $d['recommended_stay'] ?? ''),
        'bestTime'    => (string)($d['best_time'] ?? ''),
        'itinCount'   => (int)($d['itinerary_count'] ?? 0),
        'url'         => base_url('destinations/' . $d['slug']),
        'heroUrl'     => !empty($d['hero_image'])
                            ? upload_url($d['hero_image'])
                            : (!empty($d['cover_image']) ? upload_url($d['cover_image']) : ''),
    ];
}

$_savedRouteSlugs = array_values(array_filter($routeSlugs));
?>

<!-- ════════════════════════════════════════════════════════════
     HERO
════════════════════════════════════════════════════════════ -->
<section class="dest-hero"
         <?php if ($_heroBackground): ?>
         style="background-image: linear-gradient(to bottom, rgba(0,0,0,0.28) 0%, rgba(0,0,0,0.62) 100%), url('<?= e($_heroBackground) ?>'); background-size:cover; background-position:center;"
         <?php endif; ?>>
  <div class="container">
    <nav aria-label="Breadcrumb" style="margin-bottom:1.25rem;">
      <?php require __DIR__ . '/includes/breadcrumbs.php'; ?>
    </nav>
    <span class="section-eyebrow" style="color:var(--accent-light);">Explore the Island</span>
    <h1 class="section-title" style="color:#fff; font-size:clamp(2.2rem,5vw,3.6rem); margin-bottom:.6rem;">Sri Lanka's <em style="font-style:italic; color:var(--brand-200);">Destinations</em></h1>
    <p class="section-lead" style="color:rgba(255,255,255,.84); max-width:520px;">
      Cultural landmarks, hill country, wildlife parks, and coastal beaches —
      all reachable with a private driver and comfortable vehicle.
      Tap any pin or region to explore.
    </p>

    <!-- Region filter pills -->
    <div class="dest-region-pills" id="ict-region-pills" role="navigation" aria-label="Filter by region">
      <button class="dest-region-pill active" data-region="all" type="button">🗺 All Regions</button>
      <?php foreach ($_regionMap as $slug => $r):
          if (!isset($_activeRegionSlugs[$slug])) continue; ?>
      <button class="dest-region-pill" data-region="<?= e($slug) ?>" type="button">
        <?= $r['emoji'] ?> <?= e($r['label']) ?>
      </button>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ════════════════════════════════════════════════════════════
     MAP + LIST PANEL
════════════════════════════════════════════════════════════ -->
<section class="dest-map-section">

  <!-- ── SVG Map ──────────────────────────────────────────── -->
  <div class="dest-map-panel" id="ict-map-panel">

    <!-- Floating info card -->
    <div class="dest-info-card" id="ict-info-card"
         role="dialog" aria-modal="true" aria-label="Destination details"
         aria-hidden="true">
      <div class="dic-img" id="ict-card-img">
        <span class="dic-region-badge" id="ict-card-region"></span>
        <button class="dic-close" id="ict-card-close" type="button" aria-label="Close">✕</button>
      </div>
      <div class="dic-body">
        <h3 id="ict-card-name"></h3>
        <p  id="ict-card-desc"></p>
        <div class="dic-meta" id="ict-card-meta"></div>
        <div class="dic-footer">
          <a href="#" id="ict-card-link">Explore →</a>
          <button class="dic-add" id="ict-card-add" type="button" aria-label="Add to route">+</button>
        </div>
      </div>
    </div>

    <div class="dest-map-hint" id="ict-map-hint" aria-hidden="true">Tap a pin to explore</div>

    <!-- Sri Lanka SVG -->
    <svg id="ict-map"
         viewBox="0 0 220 345"
         xmlns="http://www.w3.org/2000/svg"
         role="img"
         aria-label="Interactive map of Sri Lanka destinations">
      <title>Sri Lanka Destinations Map</title>

      <!-- Island silhouette -->
      <path d="M95,10 C120,8 145,18 158,35 C172,52 175,72 170,95
               C165,118 158,138 155,158 C152,175 158,188 160,205
               C162,222 156,242 148,258 C140,274 128,288 115,298
               C102,308 88,312 75,308 C62,304 52,292 45,278
               C38,264 35,248 38,232 C41,216 52,202 55,186
               C58,170 52,155 48,140 C44,125 42,108 45,92
               C48,76 56,58 68,44 C78,32 88,12 95,10 Z"
        fill="#dceee0" stroke="#a8ccb0" stroke-width="1.5"/>

      <!-- Region zones -->
      <ellipse class="ict-region-zone" data-region="cultural"
               cx="110" cy="108" rx="32" ry="26"
               fill="rgba(45,106,65,.12)" stroke="rgba(45,106,65,.28)"
               stroke-width="1" stroke-dasharray="4,3"/>
      <ellipse class="ict-region-zone" data-region="hill"
               cx="100" cy="185" rx="30" ry="23"
               fill="rgba(143,122,40,.10)" stroke="rgba(143,122,40,.22)"
               stroke-width="1" stroke-dasharray="4,3"/>
      <ellipse class="ict-region-zone" data-region="south"
               cx="88"  cy="270" rx="36" ry="15"
               fill="rgba(26,74,138,.08)"  stroke="rgba(26,74,138,.18)"
               stroke-width="1" stroke-dasharray="4,3"/>
      <ellipse class="ict-region-zone" data-region="east"
               cx="150" cy="160" rx="16" ry="35"
               fill="rgba(0,100,130,.07)"  stroke="rgba(0,100,130,.16)"
               stroke-width="1" stroke-dasharray="4,3"/>
      <ellipse class="ict-region-zone" data-region="wildlife"
               cx="122" cy="240" rx="20" ry="18"
               fill="rgba(80,40,10,.07)"   stroke="rgba(80,40,10,.15)"
               stroke-width="1" stroke-dasharray="4,3"/>

      <!-- Zone labels -->
      <text x="110" y="93"  font-family="system-ui" font-size="5" font-weight="700"
            fill="#5a6b5e" text-anchor="middle" letter-spacing=".5" pointer-events="none">CULTURAL TRIANGLE</text>
      <text x="100" y="172" font-family="system-ui" font-size="5" font-weight="700"
            fill="#5a6b5e" text-anchor="middle" letter-spacing=".5" pointer-events="none">HILL COUNTRY</text>
      <text x="87"  y="258" font-family="system-ui" font-size="5" font-weight="700"
            fill="#5a6b5e" text-anchor="middle" letter-spacing=".5" pointer-events="none">SOUTH COAST</text>

      <!-- Destination pins — generated from DB data -->
      <?php foreach ($destinations as $d):
          // Only render pin if map coordinates are set
          if (!$_hasMapX || empty($d['map_x']) || empty($d['map_y'])) continue;
          $px = (float)$d['map_x'];
          $py = (float)$d['map_y'];
          $pr = (float)($d['map_r'] ?? 7.0);
          $pi = e($d['map_initial'] ?? strtoupper(substr($d['name'], 0, 1)));
          $isInRoute = in_array($d['slug'], $_savedRouteSlugs, true);
      ?>
      <g class="ict-pin"
         data-id="<?= e($d['slug']) ?>"
         data-region="<?= e($d['region_slug'] ?? '') ?>"
         tabindex="0"
         role="button"
         aria-label="<?= e($d['name']) ?>">
        <circle class="ict-pin-circle"
                cx="<?= $px ?>" cy="<?= $py ?>" r="<?= $pr ?>"
                fill="<?= $isInRoute ? '#c5a84a' : '#2d6a41' ?>"
                stroke="#fff" stroke-width="1.8"/>
        <text x="<?= $px ?>" y="<?= $py + ($pr * 0.45) ?>"
              font-family="system-ui"
              font-size="<?= $pr >= 7 ? '6.5' : '5.5' ?>"
              font-weight="800" fill="#fff"
              text-anchor="middle" pointer-events="none"><?= $pi ?></text>
        <text x="<?= $px ?>" y="<?= $py + $pr + 10 ?>"
              font-family="system-ui" font-size="6"
              font-weight="700" fill="#0c2316"
              text-anchor="middle" pointer-events="none"><?= e($d['name']) ?></text>
      </g>
      <?php endforeach; ?>

      <!-- Compass rose -->
      <g transform="translate(192,24)">
        <circle cx="0" cy="0" r="12" fill="rgba(255,255,255,.88)" stroke="#c0b8a8" stroke-width="1"/>
        <text x="0" y="-3" font-family="system-ui" font-size="7" font-weight="700"
              fill="#5a6b5e" text-anchor="middle">N</text>
        <line x1="0" y1="-1" x2="0" y2="3" stroke="#2d6a41" stroke-width="1.5"/>
      </g>
    </svg>

  </div><!-- /dest-map-panel -->

  <!-- ── Destination list panel ────────────────────────────── -->
  <div class="dest-list-panel" id="ict-list-panel" role="complementary"
       aria-label="Destination list">

    <div class="dest-list-search-wrap" style="display:flex; gap:.75rem; align-items:center; flex-wrap:wrap;">
      <label for="ict-region-filter-select" class="visually-hidden">Filter by region</label>
      <select class="form-select" id="ict-region-filter-select" aria-label="Filter by region" style="max-width:180px;">
        <option value="all">All Regions</option>
        <?php foreach ($_regionMap as $slug => $r): if (!isset($_activeRegionSlugs[$slug])) continue; ?>
        <option value="<?= e($slug) ?>"><?= e($r['label']) ?></option>
        <?php endforeach; ?>
      </select>
      <label for="ict-list-search" class="visually-hidden">Search destinations</label>
      <input class="dest-list-search form-input" type="search" id="ict-list-search"
             placeholder="🔍  Search destinations…" autocomplete="off"
             aria-label="Search destinations" style="flex:1; min-width:160px;"/>
    </div>
    <span class="dest-list-count" id="ict-list-count"
          aria-live="polite"><?= count($destinations) ?> destinations</span>

    <?php
    $_tagClassMap = [
        'cultural' => 'dest-tag-cultural',
        'hill'     => 'dest-tag-scenic',
        'wildlife' => 'dest-tag-wildlife',
        'south'    => 'dest-tag-coast',
        'east'     => 'dest-tag-beach',
    ];
    $_tagLabelMap = [
        'cultural' => 'Cultural',
        'hill'     => 'Scenic',
        'wildlife' => 'Wildlife',
        'south'    => 'Coast',
        'east'     => 'Beach',
    ];
    foreach ($destinations as $d):
        $rSlug   = $d['region_slug'] ?? '';
        $tagCls  = $_tagClassMap[$rSlug]  ?? 'dest-tag-cultural';
        $tagLbl  = $_tagLabelMap[$rSlug]  ?? ucfirst($rSlug ?: 'Destination');
        $emoji   = $d['emoji']            ?? '📍';
        $excerpt = mb_strimwidth((string)($d['excerpt'] ?? ''), 0, 52, '…');
    ?>
    <div class="dest-list-item"
         data-id="<?= e($d['slug']) ?>"
         data-region="<?= e($rSlug) ?>"
         role="button" tabindex="0"
         aria-label="View <?= e($d['name']) ?>">
      <div class="dest-list-thumb" aria-hidden="true"><?= $emoji ?></div>
      <div class="dest-list-info">
        <h4><?= e($d['name']) ?></h4>
        <?php if ($excerpt): ?><p><?= e($excerpt) ?></p><?php endif; ?>
      </div>
      <span class="dest-list-tag <?= $tagCls ?>"><?= e($tagLbl) ?></span>
    </div>
    <?php endforeach; ?>

    <div class="dest-no-results" id="ict-no-results" aria-live="polite" hidden>
      No destinations match your search.
    </div>

  </div><!-- /dest-list-panel -->

</section><!-- /dest-map-section -->

<!-- ════════════════════════════════════════════════════════════
     DESTINATION CARDS
════════════════════════════════════════════════════════════ -->
<section class="dest-cards-section">
  <div class="container">

    <div class="section-head dest-cards-header">
      <div>
        <span class="section-eyebrow">Top Picks</span>
        <h2 class="section-title" style="margin-bottom:0;">Featured <em>Destinations</em></h2>
        <p class="section-lead" style="margin-top:.5rem;">Handpicked places to build your private route around.</p>
      </div>
      <span class="dest-cards-count" id="ict-cards-count" aria-live="polite">
        Showing all <?= count($destinations) ?> destinations
      </span>
    </div>

    <div class="dest-cards-grid" id="ict-dest-cards">

      <?php foreach ($destinations as $d):
          $img = !empty($d['cover_image'])
              ? upload_url($d['cover_image'])
              : (!empty($d['hero_image']) ? upload_url($d['hero_image']) : '');
          $rSlug   = $d['region_slug'] ?? '';
          $isInRoute = in_array($d['slug'], $_savedRouteSlugs, true);
      ?>
      <div class="card-dest dest-card"
           data-id="<?= e($d['slug']) ?>"
           data-region="<?= e($rSlug) ?>">
        <a href="<?= e(base_url('destinations/' . $d['slug'])) ?>" class="card-dest-link" aria-label="View <?= e($d['name']) ?>">
          <?php if ($img && !is_placeholder_media_url($img)): ?>
            <?= responsive_image([
                'src' => $img,
                'alt' => $d['name'],
                'widths' => [400, 800],
                'sizes' => '(min-width: 1280px) 20vw, (min-width: 1060px) 25vw, (min-width: 780px) 33vw, (min-width: 480px) 50vw, 100vw',
                'width' => 640,
                'height' => 640,
                'loading' => 'lazy',
            ]) ?>
          <?php else: ?>
            <div style="position:absolute; inset:0; background:linear-gradient(135deg, var(--brand), var(--surface-mid));"></div>
          <?php endif; ?>
          <span class="card-dest-overlay"></span>
          <span class="card-dest-name"><?= e($d['name']) ?></span>
        </a>
        <button class="dest-card-add <?= $isInRoute ? 'added' : '' ?>"
                type="button"
                data-id="<?= e($d['slug']) ?>"
                aria-label="<?= $isInRoute ? 'Remove ' . e($d['name']) . ' from route' : 'Add ' . e($d['name']) . ' to route' ?>">
          <?= $isInRoute ? '✓' : '+' ?>
        </button>
      </div>
      <?php endforeach; ?>

    </div><!-- /dest-cards-grid -->

  </div>
</section>

<!-- ════════════════════════════════════════════════════════════
     ROUTE BUILDER BAR
════════════════════════════════════════════════════════════ -->
<div class="dest-route-bar" id="ict-route-bar" aria-label="Your route">
  <div class="dest-route-bar-text">
    <strong id="ict-route-label">Your route</strong>
    <span id="ict-route-sub">Tap + on any destination to start building your route.</span>
  </div>
  <div class="dest-route-spots" id="ict-route-spots">
    <span class="dest-route-empty-hint" id="ict-route-empty">No stops added yet</span>
  </div>
  <a href="<?= e(base_url('plan')) ?>" class="btn-gold" id="ict-route-btn"
     aria-disabled="true" tabindex="-1">→ Build My Route</a>
</div>

<!-- Toast notification -->
<div class="ict-toast" id="ict-toast" role="status" aria-live="polite"></div>

<!-- ════════════════════════════════════════════════════════════
     STYLES  (scoped to this page)
════════════════════════════════════════════════════════════ -->
<style>
/* ── Hero ────────────────────────────────────────────────── */
.dest-hero {
  background: linear-gradient(160deg,#0d2e1a 0%,#1a4a2a 60%,#2d6a41 100%);
  padding: var(--site-overlay-hero-offset, 8.75rem) 0 2.25rem;
  position: relative;
  overflow: hidden;
}
.dest-hero::before {
  content:'';
  position:absolute;
  inset:0;
  background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none'%3E%3Cg fill='%23ffffff' fill-opacity='0.03'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
  pointer-events:none;
}
.dest-hero .section-kicker,
.dest-hero .section-eyebrow { color: var(--accent-light); }
.dest-hero .section-heading,
.dest-hero .section-title { color: #fff; }
.dest-hero .section-heading em,
.dest-hero .section-title em { font-style:italic; color: var(--accent-light); }
.dest-hero .lead,
.dest-hero .section-lead { color: rgba(255,255,255,.86) !important; }
.dest-hero .breadcrumb,
.dest-hero .breadcrumb-item,
.dest-hero .breadcrumb-item a,
.dest-hero .breadcrumb-item.active { color: rgba(255,255,255,.82) !important; }
.dest-hero .breadcrumb-item a:hover { color:#fff !important; }
.dest-hero .breadcrumb-item + .breadcrumb-item::before { color: rgba(255,255,255,.45); }
@media (max-width: 679.98px) {
  .dest-hero {
    padding-top: var(--site-overlay-hero-offset, 9rem);
  }
}

/* Region pills */
.dest-region-pills {
  display: flex;
  gap: .45rem;
  flex-wrap: wrap;
  margin-top: 1.4rem;
}
.dest-region-pill {
  background: rgba(255,255,255,.10);
  border: 1px solid rgba(255,255,255,.18);
  color: rgba(255,255,255,.8);
  font-size: .72rem;
  font-weight: 600;
  letter-spacing: .03em;
  padding: .36rem .88rem;
  border-radius: 999px;
  cursor: pointer;
  transition: background 180ms, border-color 180ms, color 180ms;
  user-select: none;
}
.dest-region-pill:hover  { background: rgba(255,255,255,.18); color: #fff; }
.dest-region-pill.active { background: var(--accent); border-color: var(--accent); color: #fff; }

/* ── Map section ─────────────────────────────────────────── */
.dest-map-section {
  display: grid;
  grid-template-columns: 1fr;
}
@media (min-width: 680px) {
  .dest-map-section {
    grid-template-columns: 1fr 340px;
    min-height: 520px;
  }
}

/* Map panel */
.dest-map-panel {
  background: var(--surface-alt);
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1.5rem 1rem 1rem;
  min-height: 320px;
  overflow: hidden;
}
#ict-map { width: 100%; max-width: 300px; height: auto; display: block; }

/* Pins */
.ict-pin { cursor: pointer; }
.ict-pin .ict-pin-circle { transition: fill 200ms; }
.ict-pin:hover .ict-pin-circle,
.ict-pin.active .ict-pin-circle { fill: var(--accent); }
.ict-pin.active .ict-pin-circle {
  filter: drop-shadow(0 0 6px rgba(197,168,74,.55));
}
.ict-pin:focus { outline: none; }
.ict-pin:focus .ict-pin-circle { fill: var(--accent-light); }

/* Region zones */
.ict-region-zone { transition: opacity 280ms; }
.ict-region-zone.dimmed { opacity: .15; }

/* ── Info card ───────────────────────────────────────────── */
.dest-info-card {
  position: absolute;
  z-index: 20;
  width: 215px;
  background: #fff;
  border-radius: var(--radius-md);
  box-shadow: var(--shadow-lg);
  border: 1px solid var(--line);
  overflow: hidden;
  opacity: 0;
  transform: scale(.92) translateY(6px);
  transition: opacity 200ms ease, transform 200ms ease;
  pointer-events: none;
}
.dest-info-card[aria-hidden="false"] {
  opacity: 1;
  transform: scale(1) translateY(0);
  pointer-events: auto;
}
/* Smart position classes */
.dest-info-card.pos-right { left: 12px; top: 50%; transform: translateY(-50%); }
.dest-info-card.pos-right[aria-hidden="false"] { opacity:1; transform:translateY(-50%); }
.dest-info-card.pos-left  { right:12px; top: 50%; transform: translateY(-50%); }
.dest-info-card.pos-left[aria-hidden="false"]  { opacity:1; transform:translateY(-50%); }
.dest-info-card.pos-top   { bottom:12px; left:50%; transform:translateX(-50%) scale(.92); }
.dest-info-card.pos-top[aria-hidden="false"]   { opacity:1; transform:translateX(-50%) scale(1); }

.dic-img {
  height: 110px;
  position: relative;
  display: flex;
  align-items: flex-end;
  padding: .6rem;
  background: linear-gradient(135deg,#1a4a2a,#2d6a41);
  transition: background 300ms;
  background-size: cover;
  background-position: center;
}
.dic-region-badge {
  background: rgba(0,0,0,.42);
  color: #fff;
  font-size: .58rem;
  font-weight: 700;
  letter-spacing: .08em;
  text-transform: uppercase;
  padding: .2rem .5rem;
  border-radius: 3px;
}
.dic-close {
  position: absolute;
  top: .5rem; right: .5rem;
  width: 24px; height: 24px;
  background: rgba(0,0,0,.35);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #fff;
  font-size: .8rem;
  cursor: pointer;
  border: none;
  line-height: 1;
}
.dic-body { padding: .85rem; }
.dic-body h3 { font-size: .95rem; font-weight: 700; color: var(--text); margin-bottom: .28rem; }
.dic-body p  { font-size: .72rem; color: var(--muted); line-height: 1.5; margin-bottom: .6rem; }
.dic-meta { display: flex; gap: .38rem; flex-wrap: wrap; margin-bottom: .65rem; }
.dic-meta-item {
  font-size: .58rem;
  font-weight: 600;
  color: var(--brand);
  background: var(--brand-soft);
  padding: .14rem .44rem;
  border-radius: 3px;
}
.dic-footer {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding-top: .55rem;
  border-top: 1px solid var(--line);
}
.dic-footer a { font-size: .72rem; font-weight: 700; color: var(--brand); text-decoration: none; }
.dic-footer a:hover { color: var(--brand-dark); }
.dic-add {
  width: 28px; height: 28px;
  border-radius: 50%;
  background: var(--brand-soft);
  border: 1px solid var(--brand);
  color: var(--brand);
  font-size: .9rem;
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: background 150ms, color 150ms;
  flex-shrink: 0;
  line-height: 1;
}
.dic-add.added { background: var(--brand); color: #fff; }

/* Map hint */
.dest-map-hint {
  position: absolute;
  bottom: .75rem;
  left: 50%;
  transform: translateX(-50%);
  background: rgba(12,35,22,.55);
  color: rgba(255,255,255,.78);
  font-size: .62rem;
  font-weight: 600;
  padding: .28rem .7rem;
  border-radius: 999px;
  white-space: nowrap;
  pointer-events: none;
  transition: opacity 400ms;
}
.dest-map-hint.hidden { opacity: 0; }

/* ── List panel ──────────────────────────────────────────── */
.dest-list-panel {
  border-top: 1px solid var(--line);
  overflow-y: auto;
  max-height: 400px;
  -webkit-overflow-scrolling: touch;
}
@media (min-width: 680px) {
  .dest-list-panel {
    border-top: none;
    border-left: 1px solid var(--line);
    max-height: 520px;
  }
}
.dest-list-search-wrap {
  padding: .85rem 1.1rem .7rem;
  border-bottom: 1px solid var(--line);
  background: var(--surface);
  position: sticky;
  top: 0;
  z-index: 5;
}
.dest-list-search {
  width: 100%;
  border: 1px solid var(--line-strong);
  border-radius: 999px;
  padding: .42rem .9rem;
  font-size: .8rem;
  color: var(--text);
  font-family: var(--font-sans);
  background: var(--surface-alt);
  outline: none;
  transition: border-color 150ms;
}
.dest-list-search:focus { border-color: var(--brand); }
.dest-list-search.form-input {
  border-radius: 999px;
  padding: .42rem .9rem;
  font-size: .8rem;
  background: var(--surface-alt);
  border-width: 1px;
}
.dest-list-search-wrap .form-select {
  border-radius: 999px;
  font-size: .8rem;
  padding: .42rem .9rem;
  background-color: var(--surface-alt);
}
.dest-list-count {
  font-size: .6rem;
  font-weight: 600;
  color: var(--muted);
  letter-spacing: .08em;
  text-transform: uppercase;
  padding: .4rem 1.1rem 0;
  display: block;
}
.dest-list-item {
  display: flex;
  align-items: center;
  gap: .8rem;
  padding: .75rem 1.1rem;
  border-bottom: 1px solid var(--line);
  cursor: pointer;
  transition: background 150ms;
  border-left: 3px solid transparent;
}
.dest-list-item:hover  { background: var(--surface-alt); }
.dest-list-item.active { background: var(--surface-alt); border-left-color: var(--brand); }
.dest-list-item.hidden { display: none; }
.dest-list-thumb {
  width: 44px; height: 44px;
  border-radius: var(--radius-sm);
  flex-shrink: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.1rem;
  background: linear-gradient(135deg,#2d6a41,#8f7a28);
  transition: transform 200ms;
}
.dest-list-item:hover .dest-list-thumb,
.dest-list-item.active .dest-list-thumb { transform: scale(1.06); }
.dest-list-info { flex: 1; min-width: 0; }
.dest-list-info h4 { font-size: .85rem; font-weight: 700; color: var(--text); margin-bottom: .05rem; }
.dest-list-info p  { font-size: .68rem; color: var(--muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin: 0; }
.dest-list-tag {
  flex-shrink: 0;
  font-size: .56rem;
  font-weight: 700;
  letter-spacing: .07em;
  text-transform: uppercase;
  padding: .14rem .4rem;
  border-radius: 3px;
}
.dest-tag-cultural { color: #5a3a00; background: rgba(143,122,40,.12); }
.dest-tag-wildlife { color: #1a4a1a; background: rgba(45,106,65,.12); }
.dest-tag-scenic   { color: #1a3a5a; background: rgba(26,74,138,.10); }
.dest-tag-coast    { color: #1a3a5a; background: rgba(26,100,160,.10); }
.dest-tag-beach    { color: #004a5a; background: rgba(0,100,130,.10); }
.dest-no-results {
  padding: 1.5rem 1.1rem;
  text-align: center;
  color: var(--muted);
  font-size: .8rem;
}

/* ── Cards section ───────────────────────────────────────── */
.dest-cards-section {
  padding: 2.5rem 0;
  background: var(--bg);
}
.dest-cards-header {
  display: flex;
  align-items: flex-end;
  justify-content: space-between;
  gap: 1rem;
  margin-bottom: 1.5rem;
  flex-wrap: wrap;
}
.dest-cards-count {
  font-size: .72rem;
  color: var(--muted);
  white-space: nowrap;
}
.dest-cards-grid {
  display: grid;
  grid-template-columns: 1fr;
  gap: 1.1rem;
}
@media (min-width: 480px) { .dest-cards-grid { grid-template-columns: repeat(2, 1fr); } }
@media (min-width: 780px) { .dest-cards-grid { grid-template-columns: repeat(3, 1fr); } }
@media (min-width: 1060px){ .dest-cards-grid { grid-template-columns: repeat(4, 1fr); } }
@media (min-width: 1280px){ .dest-cards-grid { grid-template-columns: repeat(5, 1fr); } }
.dest-cards-header.section-head { margin-bottom: 2rem; }
.dest-cards-header .section-eyebrow { margin-bottom: .5rem; }
.dest-cards-header .section-lead { font-size: var(--text-base); }

.dest-card {
  border-radius: var(--radius-lg);
  overflow: hidden;
  box-shadow: var(--shadow-sm);
  border: 1px solid var(--line);
  background: var(--surface);
  transition: transform 200ms, box-shadow 200ms;
}
.dest-card:hover { transform: translateY(-4px); box-shadow: var(--shadow-md); }
.dest-card.hidden { display: none; }
.dest-card.highlighted { box-shadow: 0 0 0 2px var(--brand), var(--shadow-md); }

/* ── Square destination cards — design system .card-dest ───────── */
.card-dest.dest-card {
  aspect-ratio: 1;
  border-radius: var(--radius-lg);
  overflow: hidden;
  position: relative;
  display: flex;
  align-items: center;
  justify-content: center;
  background: var(--surface-dark);
  box-shadow: var(--shadow-sm);
  border: 1px solid var(--line);
  transition: transform var(--transition-med), box-shadow var(--transition-med);
}
.card-dest.dest-card:hover {
  transform: translateY(-4px);
  box-shadow: var(--shadow-lg);
}
.card-dest.dest-card.hidden { display: none; }
.card-dest.dest-card .card-dest-link {
  position: absolute;
  inset: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  text-decoration: none;
  overflow: hidden;
}
.card-dest.dest-card img,
.card-dest.dest-card picture {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform var(--transition-slow);
}
.card-dest.dest-card picture img { width:100%; height:100%; object-fit:cover; }
.card-dest.dest-card:hover img,
.card-dest.dest-card:hover picture img { transform: scale(1.08); }
.card-dest.dest-card .card-dest-overlay {
  position: absolute;
  inset: 0;
  background: rgba(0,0,0,0.38);
  transition: background var(--transition-med);
}
.card-dest.dest-card:hover .card-dest-overlay { background: rgba(0,0,0,0.26); }
.card-dest.dest-card .card-dest-name {
  position: relative;
  z-index: 1;
  color: #fff;
  font-family: var(--font-display);
  font-size: var(--text-2xl);
  font-weight: 700;
  letter-spacing: 0.02em;
  text-shadow: 0 2px 8px rgba(0,0,0,0.5);
  text-align: center;
  padding: 1rem;
}
.card-dest.dest-card .dest-card-add {
  position: absolute;
  top: .7rem; right: .7rem;
  z-index: 2;
}

.dest-card-img {
  height: 155px;
  position: relative;
  display: flex;
  align-items: flex-end;
  padding: .8rem;
  background: linear-gradient(160deg,#1a5c38,#2d6a41);
  background-size: cover;
  background-position: center;
}
.dest-card-region-badge {
  background: rgba(0,0,0,.42);
  color: #fff;
  font-size: .58rem;
  font-weight: 700;
  letter-spacing: .07em;
  text-transform: uppercase;
  padding: .2rem .5rem;
  border-radius: 3px;
}
.dest-card-add {
  position: absolute;
  top: .7rem; right: .7rem;
  width: 30px; height: 30px;
  background: rgba(255,255,255,.92);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: .85rem;
  font-weight: 700;
  color: var(--brand);
  cursor: pointer;
  box-shadow: 0 2px 8px rgba(0,0,0,.12);
  transition: background 150ms, color 150ms;
  border: none;
  font-family: var(--font-sans);
  line-height: 1;
}
.dest-card-add.added { background: var(--brand); color: #fff; }
.dest-card-body { padding: .9rem; }
.dest-card-body h3 { font-size: .9rem; font-weight: 700; color: var(--text); margin-bottom: .22rem; }
.dest-card-body h3 a { color: inherit; text-decoration: none; }
.dest-card-body h3 a:hover { color: var(--brand); }
.dest-card-body p  { font-size: .72rem; color: var(--muted); line-height: 1.5; margin-bottom: .55rem; }
.dest-card-meta { display: flex; gap: .4rem; flex-wrap: wrap; }
.dest-card-meta-item {
  font-size: .6rem;
  font-weight: 600;
  color: var(--brand);
  background: var(--brand-soft);
  padding: .15rem .45rem;
  border-radius: 3px;
}
.dest-card-details-link {
  display: inline-block;
  margin-top: .65rem;
  font-size: .72rem;
  font-weight: 700;
  color: var(--brand);
  text-decoration: none;
  transition: color 150ms;
}
.dest-card-details-link:hover {
  color: var(--brand-dark);
  text-decoration: underline;
}

/* ── Route bar ───────────────────────────────────────────── */
.dest-route-bar {
  background: var(--surface-dark);
  padding: 1rem 1.5rem;
  display: flex;
  flex-direction: column;
  gap: .7rem;
}
@media (min-width: 600px) {
  .dest-route-bar {
    flex-direction: row;
    align-items: center;
    justify-content: space-between;
  }
}
.dest-route-bar-text strong { color: #fff; font-size: .88rem; display: block; }
#ict-route-sub { color: rgba(255,255,255,.74); font-size: .7rem; display: block; margin-top: .1rem; }
.dest-route-spots { display: flex; gap: .4rem; flex-wrap: wrap; align-items: center; }
.dest-route-spot {
  background: rgba(255,255,255,.10);
  border: 1px solid rgba(255,255,255,.2);
  color: #fff;
  font-size: .66rem;
  font-weight: 600;
  padding: .26rem .65rem;
  border-radius: 999px;
  display: flex;
  align-items: center;
  gap: .3rem;
  animation: spotIn 200ms ease;
}
@keyframes spotIn { from{transform:scale(.8);opacity:0}to{transform:scale(1);opacity:1} }
.dest-route-spot-rm {
  color: rgba(255,255,255,.68);
  cursor: pointer;
  font-size: .7rem;
  border: none;
  background: none;
  line-height: 1;
  padding: 0;
}
.dest-route-spot-rm:hover { color: rgba(255,255,255,.85); }
.dest-route-empty-hint { font-size: .68rem; color: rgba(255,255,255,.66); font-style: italic; }
.btn-gold {
  display: inline-flex;
  align-items: center;
  gap: .35rem;
  background: var(--accent);
  color: #fff;
  font-size: .78rem;
  font-weight: 700;
  padding: .58rem 1.25rem;
  border-radius: 999px;
  text-decoration: none;
  white-space: nowrap;
  flex-shrink: 0;
  transition: background 150ms, opacity 200ms;
}
.btn-gold[aria-disabled="true"] { opacity: .4; pointer-events: none; }

/* ── Toast ───────────────────────────────────────────────── */
.ict-toast {
  position: fixed;
  bottom: 1.5rem;
  left: 50%;
  transform: translateX(-50%) translateY(80px);
  background: var(--surface-dark);
  color: #fff;
  font-size: .8rem;
  font-weight: 600;
  padding: .65rem 1.3rem;
  border-radius: 999px;
  box-shadow: var(--shadow-lg);
  z-index: 9999;
  transition: transform 280ms cubic-bezier(.34,1.56,.64,1), opacity 280ms;
  opacity: 0;
  white-space: nowrap;
  pointer-events: none;
}
.ict-toast.show { transform: translateX(-50%) translateY(0); opacity: 1; }
</style>

<!-- ════════════════════════════════════════════════════════════
     JAVASCRIPT
════════════════════════════════════════════════════════════ -->
<script>
(function () {
'use strict';

/* ── Data from PHP ──────────────────────────────────────── */
const DESTINATIONS = <?= json_encode($_jsDestinations, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?>;

/* ── State ──────────────────────────────────────────────── */
let activePin    = null;
let activeRegion = 'all';
let searchQuery  = '';
let route        = <?= json_encode(array_values($_savedRouteSlugs), JSON_UNESCAPED_UNICODE) ?>;

/* ── Elements ───────────────────────────────────────────── */
const mapPanel    = document.getElementById('ict-map-panel');
const infoCard    = document.getElementById('ict-info-card');
const cardImg     = document.getElementById('ict-card-img');
const cardRegion  = document.getElementById('ict-card-region');
const cardName    = document.getElementById('ict-card-name');
const cardDesc    = document.getElementById('ict-card-desc');
const cardMeta    = document.getElementById('ict-card-meta');
const cardLink    = document.getElementById('ict-card-link');
const cardAdd     = document.getElementById('ict-card-add');
const cardClose   = document.getElementById('ict-card-close');
const listPanel   = document.getElementById('ict-list-panel');
const listSearch  = document.getElementById('ict-list-search');
const listCount   = document.getElementById('ict-list-count');
const noResults   = document.getElementById('ict-no-results');
const routeBar    = document.getElementById('ict-route-bar');
const routeSpots  = document.getElementById('ict-route-spots');
const routeBtn    = document.getElementById('ict-route-btn');
const routeLabel  = document.getElementById('ict-route-label');
const routeSub    = document.getElementById('ict-route-sub');
const routeEmpty  = document.getElementById('ict-route-empty');
const mapHint     = document.getElementById('ict-map-hint');
const toast       = document.getElementById('ict-toast');
const cardsCount  = document.getElementById('ict-cards-count');

/* ── Toast ──────────────────────────────────────────────── */
let _toastTimer;
function showToast(msg) {
    toast.textContent = msg;
    toast.classList.add('show');
    clearTimeout(_toastTimer);
    _toastTimer = setTimeout(() => toast.classList.remove('show'), 2500);
}

/* ── Info card ──────────────────────────────────────────── */
function openCard(slug) {
    const d = DESTINATIONS[slug];
    if (!d) return;

    const added = route.includes(slug);
    cardRegion.textContent   = d.regionLabel || '';
    cardName.textContent     = d.name;
    cardDesc.textContent     = d.desc;
    cardMeta.innerHTML       = [
        d.stay     ? `<span class="dic-meta-item">🕐 ${d.stay}</span>`     : '',
        d.bestTime ? `<span class="dic-meta-item">📅 ${d.bestTime}</span>` : '',
        d.itinCount ? `<span class="dic-meta-item">${d.itinCount} itinerar${d.itinCount===1?'y':'ies'}</span>` : '',
    ].join('');
    cardLink.textContent     = `Explore ${d.name} →`;
    cardLink.href            = d.url;
    cardAdd.className        = 'dic-add' + (added ? ' added' : '');
    cardAdd.textContent      = added ? '✓' : '+';
    cardAdd.dataset.id       = slug;

    // Hero image in card
    if (d.heroUrl) {
        cardImg.style.backgroundImage = `url('${d.heroUrl}')`;
    } else {
        cardImg.style.backgroundImage = '';
    }

    // Position card
    const pw = mapPanel.offsetWidth;
    infoCard.classList.remove('pos-right','pos-left','pos-top');
    if      (pw >= 400) infoCard.classList.add('pos-right');
    else if (pw >= 260) infoCard.classList.add('pos-left');
    else                infoCard.classList.add('pos-top');

    infoCard.setAttribute('aria-hidden', 'false');
    activePin = slug;

    // Highlight pin
    document.querySelectorAll('.ict-pin').forEach(p => p.classList.remove('active'));
    const pin = document.querySelector(`.ict-pin[data-id="${slug}"]`);
    if (pin) pin.classList.add('active');

    // Highlight list item
    highlightListItem(slug);
    mapHint.classList.add('hidden');
}

function closeCard() {
    infoCard.setAttribute('aria-hidden', 'true');
    document.querySelectorAll('.ict-pin').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.dest-list-item').forEach(r => r.classList.remove('active'));
    activePin = null;
}

function highlightListItem(slug) {
    document.querySelectorAll('.dest-list-item').forEach(r => {
        r.classList.toggle('active', r.dataset.id === slug);
    });
    const el = listPanel.querySelector(`.dest-list-item[data-id="${slug}"]`);
    if (el) el.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
}

/* ── Region filter ──────────────────────────────────────── */
function applyRegionFilter(region) {
    activeRegion = region;

    document.querySelectorAll('.dest-region-pill').forEach(p => {
        p.classList.toggle('active', p.dataset.region === region);
    });
    const regionSelectEl = document.getElementById('ict-region-filter-select');
    if (regionSelectEl && regionSelectEl.value !== region) {
        regionSelectEl.value = region;
    }

    let listVis = 0;
    document.querySelectorAll('.dest-list-item').forEach(el => {
        const show = region === 'all' || el.dataset.region === region;
        el.classList.toggle('hidden', !show);
        if (show) listVis++;
    });
    listCount.textContent = `${listVis} destination${listVis !== 1 ? 's' : ''}`;
    noResults.hidden = !(listVis === 0 && searchQuery === '');

    let cardVis = 0;
    document.querySelectorAll('.dest-card').forEach(el => {
        const show = region === 'all' || el.dataset.region === region;
        el.classList.toggle('hidden', !show);
        if (show) cardVis++;
    });
    cardsCount.textContent = region === 'all'
        ? `Showing all ${cardVis} destinations`
        : `Showing ${cardVis} destination${cardVis !== 1 ? 's' : ''} in this region`;

    document.querySelectorAll('.ict-region-zone').forEach(z => {
        z.classList.toggle('dimmed', region !== 'all' && z.dataset.region !== region);
    });

    if (activePin && region !== 'all' && DESTINATIONS[activePin]?.region !== region) {
        closeCard();
    }
}

/* ── Search ─────────────────────────────────────────────── */
function applySearch(q) {
    searchQuery = q.toLowerCase().trim();
    let vis = 0;
    document.querySelectorAll('.dest-list-item').forEach(el => {
        const name  = el.querySelector('h4')?.textContent.toLowerCase() || '';
        const desc  = el.querySelector('p')?.textContent.toLowerCase()  || '';
        const matchR = activeRegion === 'all' || el.dataset.region === activeRegion;
        const matchS = !searchQuery || name.includes(searchQuery) || desc.includes(searchQuery);
        const show   = matchR && matchS;
        el.classList.toggle('hidden', !show);
        if (show) vis++;
    });
    listCount.textContent = `${vis} destination${vis !== 1 ? 's' : ''}`;
    noResults.hidden = vis > 0;
}

/* ── Route ──────────────────────────────────────────────── */
function toggleRoute(slug) {
    const d = DESTINATIONS[slug];
    if (!d) return;
    const idx = route.indexOf(slug);
    if (idx === -1) {
        route.push(slug);
        showToast(`✓ ${d.name} added to your route`);
    } else {
        route.splice(idx, 1);
        showToast(`Removed ${d.name}`);
    }
    renderRouteBar();
    syncAllAddButtons(slug);
    persistRoute();
}

function syncAllAddButtons(slug) {
    const added = route.includes(slug);
    // Card add buttons
    document.querySelectorAll(`.dest-card-add[data-id="${slug}"]`).forEach(btn => {
        btn.textContent  = added ? '✓' : '+';
        btn.classList.toggle('added', added);
        btn.setAttribute('aria-label',
            (added ? 'Remove ' : 'Add ') + (DESTINATIONS[slug]?.name || slug) +
            (added ? ' from' : ' to') + ' route'
        );
    });
    // Map pin colour
    const pin = document.querySelector(`.ict-pin[data-id="${slug}"] .ict-pin-circle`);
    if (pin) pin.style.fill = added ? '#c5a84a' : (activePin === slug ? 'var(--accent)' : '#2d6a41');
    // Info card button
    if (activePin === slug) {
        cardAdd.className   = 'dic-add' + (added ? ' added' : '');
        cardAdd.textContent = added ? '✓' : '+';
    }
}

function renderRouteBar() {
    // Remove old spot elements
    routeSpots.querySelectorAll('.dest-route-spot').forEach(s => s.remove());

    route.forEach(slug => {
        const d = DESTINATIONS[slug];
        if (!d) return;
        const spot = document.createElement('div');
        spot.className    = 'dest-route-spot';
        spot.dataset.slug = slug;
        spot.innerHTML    =
            `${d.emoji} ${d.name} ` +
            `<button class="dest-route-spot-rm" type="button" data-slug="${slug}" ` +
            `aria-label="Remove ${d.name} from route">✕</button>`;
        routeSpots.insertBefore(spot, routeEmpty);
    });

    const has = route.length > 0;
    routeEmpty.style.display = has ? 'none' : '';
    routeBtn.setAttribute('aria-disabled', has ? 'false' : 'true');
    if (has) routeBtn.removeAttribute('tabindex');
    else routeBtn.setAttribute('tabindex', '-1');

    routeLabel.textContent = has
        ? `Your route — ${route.length} stop${route.length > 1 ? 's' : ''}`
        : 'Your route';
    routeSub.textContent = has
        ? "We'll build an itinerary around these. Send us your dates to get started."
        : 'Tap + on any destination to start building your route.';

    // Update href with slug params
    if (has) {
        routeBtn.href = `<?= e(base_url('plan')) ?>?destinations=${encodeURIComponent(route.join(','))}`;
    }
}

function persistRoute() {
    // sessionStorage for instant cross-tab sync
    try { sessionStorage.setItem('ict_route', JSON.stringify(route)); } catch(e) {}

    // Server-side persistence via fetch (fire-and-forget)
    const token = getCookie('ict_route_token');
    if (token) {
        fetch(window.location.href, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=save_route&dest_slugs=${encodeURIComponent(route.join(','))}&token=${encodeURIComponent(token)}&_token=<?= csrf_token() ?>`,
            keepalive: true,
        }).catch(() => {});
    }
}

function getCookie(name) {
    const v = document.cookie.match('(^|;)\\s*' + name + '\\s*=\\s*([^;]+)');
    return v ? v.pop() : '';
}

/* ── Events ─────────────────────────────────────────────── */
// Pins
document.querySelectorAll('.ict-pin').forEach(pin => {
    const activate = () => {
        const slug = pin.dataset.id;
        activePin === slug ? closeCard() : openCard(slug);
    };
    pin.addEventListener('click', activate);
    pin.addEventListener('keydown', e => {
        if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); activate(); }
    });
});

// Close card
cardClose.addEventListener('click', closeCard);
mapPanel.addEventListener('click', e => {
    if (!e.target.closest('.ict-pin') && !e.target.closest('.dest-info-card')) closeCard();
});

// Card add button (info card)
cardAdd.addEventListener('click', () => { if (activePin) toggleRoute(activePin); });

// Region pills
document.querySelectorAll('.dest-region-pill').forEach(pill => {
    pill.addEventListener('click', () => applyRegionFilter(pill.dataset.region));
});
const regionSelect = document.getElementById('ict-region-filter-select');
if (regionSelect) {
    regionSelect.addEventListener('change', () => applyRegionFilter(regionSelect.value));
}

// List items
document.querySelectorAll('.dest-list-item').forEach(item => {
    const activate = () => openCard(item.dataset.id);
    item.addEventListener('click', activate);
    item.addEventListener('keydown', e => {
        if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); activate(); }
    });
});

// Card add buttons (card grid)
document.querySelectorAll('.dest-card-add').forEach(btn => {
    btn.addEventListener('click', e => {
        e.stopPropagation();
        toggleRoute(btn.dataset.id);
    });
});

// Card click → open info card + scroll map into view on mobile (legacy rect cards); square card-dest uses direct link navigation
document.querySelectorAll('.dest-card').forEach(card => {
    card.addEventListener('click', e => {
        if (e.target.closest('.dest-card-add') || e.target.closest('.dest-card-details-link') || e.target.closest('.card-dest-link')) return;
        const slug = card.dataset.id;
        if (!slug) return;
        openCard(slug);
        if (window.innerWidth < 680) {
            mapPanel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
    });
});

// Route spot remove
routeSpots.addEventListener('click', e => {
    const btn = e.target.closest('.dest-route-spot-rm');
    if (btn) toggleRoute(btn.dataset.slug);
});

// Search
listSearch.addEventListener('input', () => applySearch(listSearch.value));

// Escape key
document.addEventListener('keydown', e => {
    if (e.key === 'Escape' && activePin) closeCard();
});

// Auto-hide hint
setTimeout(() => mapHint?.classList.add('hidden'), 4000);

// Init: restore route from sessionStorage if server had nothing
(function initRoute() {
    if (route.length === 0) {
        try {
            const saved = JSON.parse(sessionStorage.getItem('ict_route') || '[]');
            if (Array.isArray(saved) && saved.length > 0) {
                route = saved.filter(s => DESTINATIONS[s]);
            }
        } catch(e) {}
    }
    renderRouteBar();
    // Sync add-button states for any pre-loaded route
    route.forEach(slug => syncAllAddButtons(slug));
})();

})(); // end IIFE
</script>

<?php schema_output($schemaGraph); ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
