<?php
/**
 * includes/destination_map.php
 *
 * Reusable interactive Sri Lanka destinations map.
 * Drop into destinations.php with:
 *   require __DIR__ . '/includes/destination_map.php';
 *
 * Expects $destinations array from DB in format:
 *   [['id'=>1,'name'=>'Sigiriya','slug'=>'sigiriya','region_slug'=>'cultural',
 *     'region_label'=>'Cultural Triangle','excerpt'=>'...','emoji'=>'🏔',
 *     'stay_duration'=>'Half day','best_time'=>'Year-round',
 *     'itinerary_count'=>4], ...]
 *
 * If $destinations is not set (e.g. standalone use), a static fallback is used.
 */

// ── Fallback static data (used when $destinations not injected) ──────────────
if (empty($destinations)) {
    $destinations = [
        ['id'=>1,  'name'=>'Sigiriya',          'slug'=>'sigiriya',     'region_slug'=>'cultural', 'region_label'=>'Cultural Triangle', 'excerpt'=>'Ancient rock fortress rising 200m above the jungle — a UNESCO World Heritage Site with frescoes and symmetrical water gardens.', 'emoji'=>'🏔', 'stay_duration'=>'Half day',    'best_time'=>'Year-round', 'itinerary_count'=>4, 'map_x'=>118, 'map_y'=>108, 'map_r'=>8,   'map_initial'=>'S'],
        ['id'=>2,  'name'=>'Dambulla',           'slug'=>'dambulla',    'region_slug'=>'cultural', 'region_label'=>'Cultural Triangle', 'excerpt'=>'Five cave temples packed with 150 Buddha statues. One of the best-preserved Buddhist sites in Asia.',                     'emoji'=>'🪨', 'stay_duration'=>'2–3 hrs',    'best_time'=>'Year-round', 'itinerary_count'=>3, 'map_x'=>104, 'map_y'=>122, 'map_r'=>6.5, 'map_initial'=>'D'],
        ['id'=>3,  'name'=>'Anuradhapura',       'slug'=>'anuradhapura','region_slug'=>'cultural', 'region_label'=>'Cultural Triangle', 'excerpt'=>'An ancient sacred city with massive dagobas and the oldest documented tree in the world.',                               'emoji'=>'🏯', 'stay_duration'=>'Full day',    'best_time'=>'Year-round', 'itinerary_count'=>2, 'map_x'=>94,  'map_y'=>76,  'map_r'=>6.5, 'map_initial'=>'A'],
        ['id'=>4,  'name'=>'Kandy',              'slug'=>'kandy',       'region_slug'=>'hill',     'region_label'=>'Hill Country',      'excerpt'=>'Sri Lanka\'s cultural capital — the Temple of the Tooth Relic and the Royal Botanical Gardens.',                        'emoji'=>'🌿', 'stay_duration'=>'1 night',     'best_time'=>'Year-round', 'itinerary_count'=>7, 'map_x'=>100, 'map_y'=>160, 'map_r'=>7,   'map_initial'=>'K'],
        ['id'=>5,  'name'=>'Ella',               'slug'=>'ella',        'region_slug'=>'hill',     'region_label'=>'Hill Country',      'excerpt'=>'Tea-covered mountains, the Nine Arches Bridge, and one of the most scenic train journeys in the world.',                'emoji'=>'⛰',  'stay_duration'=>'1–2 nights',  'best_time'=>'Apr–Sep',    'itinerary_count'=>6, 'map_x'=>116, 'map_y'=>202, 'map_r'=>6.5, 'map_initial'=>'E'],
        ['id'=>6,  'name'=>'Nuwara Eliya',       'slug'=>'nuwara-eliya','region_slug'=>'hill',     'region_label'=>'Hill Country',      'excerpt'=>'Walk through tea estates, visit a working factory, and watch the mist roll in over the highlands.',                    'emoji'=>'🍃', 'stay_duration'=>'1 night',     'best_time'=>'Apr–Sep',    'itinerary_count'=>5, 'map_x'=>100, 'map_y'=>190, 'map_r'=>6,   'map_initial'=>'N'],
        ['id'=>7,  'name'=>'Yala National Park', 'slug'=>'yala',        'region_slug'=>'wildlife', 'region_label'=>'Wildlife',          'excerpt'=>'One of the highest leopard densities in the world. Also elephants, sloth bears, and crocodiles.',                       'emoji'=>'🐆', 'stay_duration'=>'Full day',    'best_time'=>'Feb–Jul',    'itinerary_count'=>4, 'map_x'=>130, 'map_y'=>248, 'map_r'=>6.5, 'map_initial'=>'Y'],
        ['id'=>8,  'name'=>'Minneriya',          'slug'=>'minneriya',   'region_slug'=>'wildlife', 'region_label'=>'Wildlife',          'excerpt'=>'The Gathering — hundreds of wild elephants converge around the reservoir from June to October.',                         'emoji'=>'🐘', 'stay_duration'=>'Half day',    'best_time'=>'Jun–Oct',    'itinerary_count'=>3, 'map_x'=>118, 'map_y'=>134, 'map_r'=>6,   'map_initial'=>'M'],
        ['id'=>9,  'name'=>'Galle Fort',         'slug'=>'galle-fort',  'region_slug'=>'south',    'region_label'=>'South Coast',       'excerpt'=>'A UNESCO Dutch colonial fort with boutique hotels, galleries, and sunset views from the old ramparts.',                 'emoji'=>'🏛', 'stay_duration'=>'Half–1 day',  'best_time'=>'Nov–Apr',    'itinerary_count'=>5, 'map_x'=>70,  'map_y'=>272, 'map_r'=>6.5, 'map_initial'=>'G'],
        ['id'=>10, 'name'=>'Mirissa',            'slug'=>'mirissa',     'region_slug'=>'south',    'region_label'=>'South Coast',       'excerpt'=>'A crescent bay with calm water and the best whale-watching departure point on the south coast.',                        'emoji'=>'🐋', 'stay_duration'=>'1–2 nights',  'best_time'=>'Nov–Apr',    'itinerary_count'=>4, 'map_x'=>90,  'map_y'=>285, 'map_r'=>5.5, 'map_initial'=>'Mi'],
        ['id'=>11, 'name'=>'Trincomalee',        'slug'=>'trincomalee', 'region_slug'=>'east',     'region_label'=>'East Coast',        'excerpt'=>'Natural deep-water harbour, east coast beaches, and excellent whale watching from March to August.',                    'emoji'=>'🌊', 'stay_duration'=>'1–2 nights',  'best_time'=>'Mar–Aug',    'itinerary_count'=>3, 'map_x'=>150, 'map_y'=>128, 'map_r'=>6.5, 'map_initial'=>'T'],
    ];
}

// ── Build JS-safe JSON for the data layer ────────────────────────────────────
$js_destinations = [];
foreach ($destinations as $d) {
    $js_destinations[$d['slug']] = [
        'name'        => $d['name'],
        'region'      => $d['region_slug'],
        'regionLabel' => $d['region_label'],
        'desc'        => $d['excerpt'],
        'emoji'       => $d['emoji'],
        'meta'        => [
            '🕐 ' . $d['stay_duration'],
            '📅 ' . $d['best_time'],
            $d['itinerary_count'] . ' ' . ($d['itinerary_count'] === 1 ? 'itinerary' : 'itineraries'),
        ],
        'url'         => base_url('destinations/' . $d['slug']),
    ];
}

// ── Build region list for filter pills ───────────────────────────────────────
$regions = [
    'all'      => ['label'=>'🗺 All Regions',       'emoji'=>''],
    'cultural' => ['label'=>'🏛 Cultural Triangle', 'emoji'=>'🏛'],
    'hill'     => ['label'=>'⛰ Hill Country',       'emoji'=>'⛰'],
    'wildlife' => ['label'=>'🐘 Wildlife',           'emoji'=>'🐘'],
    'south'    => ['label'=>'🏖 South Coast',        'emoji'=>'🏖'],
    'east'     => ['label'=>'🌊 East Coast',         'emoji'=>'🌊'],
];

// ── Helper: safely output HTML attribute ─────────────────────────────────────
function map_e(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
?>

<!-- ════════════════════════════════════════════════════════════
     DESTINATION MAP COMPONENT
     File: includes/destination_map.php
════════════════════════════════════════════════════════════ -->

<style>
/* ── Map component scoped styles ─────────────────────────────────────────── */

/* Region filter pills */
.region-pills { display:flex; gap:.45rem; flex-wrap:wrap; margin-top:1.35rem; }
.region-pill {
  background:rgba(255,255,255,.10); border:1px solid rgba(255,255,255,.18);
  color:rgba(255,255,255,.78); font-size:.7rem; font-weight:600;
  letter-spacing:.03em; padding:.35rem .85rem; border-radius:999px;
  cursor:pointer; transition:background 180ms,border-color 180ms,color 180ms;
  user-select:none;
}
.region-pill:hover { background:rgba(255,255,255,.18); color:#fff; }
.region-pill.active { background:var(--accent); border-color:var(--accent); color:#fff; }

/* Map + list grid */
.map-section { display:grid; grid-template-columns:1fr; }
@media(min-width:680px){ .map-section{ grid-template-columns:1fr 340px; min-height:520px; } }

/* Map panel */
.dest-map-panel {
  background:var(--surface-alt); position:relative;
  display:flex; align-items:center; justify-content:center;
  padding:1.5rem 1rem 1rem; min-height:320px; overflow:hidden;
}
#ict-map { width:100%; max-width:300px; height:auto; display:block; }

/* Pins */
.ict-pin { cursor:pointer; }
.ict-pin .pin-circle { transition:fill 200ms; }
.ict-pin:hover .pin-circle,
.ict-pin.active .pin-circle { fill:var(--accent); }
.ict-pin.active .pin-circle { filter:drop-shadow(0 0 6px rgba(197,168,74,.55)); }

/* Region zones */
.region-zone { transition:opacity 300ms; }
.region-zone.dimmed { opacity:.18; }

/* Floating info card */
.dest-info-card {
  position:absolute; z-index:20; width:210px;
  background:#fff; border-radius:var(--radius-md);
  box-shadow:var(--shadow-lg); border:1px solid var(--line);
  overflow:hidden;
  opacity:0; transform:scale(.92) translateY(6px);
  transition:opacity 200ms ease, transform 200ms ease;
  pointer-events:none;
}
.dest-info-card.visible {
  opacity:1; transform:scale(1) translateY(0); pointer-events:auto;
}
.dest-info-card.pos-right { left:12px; top:50%; transform:translateY(-50%); }
.dest-info-card.pos-right.visible { opacity:1; transform:translateY(-50%); }
.dest-info-card.pos-left  { right:12px; top:50%; transform:translateY(-50%); }
.dest-info-card.pos-left.visible  { opacity:1; transform:translateY(-50%); }
.dest-info-card.pos-top   { bottom:12px; left:50%; transform:translateX(-50%) scale(.92); }
.dest-info-card.pos-top.visible   { opacity:1; transform:translateX(-50%) scale(1); }

.dic-img {
  height:110px; position:relative;
  display:flex; align-items:flex-end; padding:.6rem;
  background:linear-gradient(135deg,#1a4a2a,#2d6a41); transition:background 300ms;
}
.dic-region-badge {
  background:rgba(0,0,0,.42); color:#fff; font-size:.58rem; font-weight:700;
  letter-spacing:.08em; text-transform:uppercase; padding:.2rem .5rem; border-radius:3px;
}
.dic-close {
  position:absolute; top:.5rem; right:.5rem; width:24px; height:24px;
  background:rgba(0,0,0,.32); border-radius:50%;
  display:flex; align-items:center; justify-content:center;
  color:#fff; font-size:.8rem; cursor:pointer; border:none; line-height:1;
}
.dic-body { padding:.85rem; }
.dic-body h3 { font-size:.95rem; font-weight:700; color:var(--text); margin-bottom:.3rem; }
.dic-body p  { font-size:.72rem; color:var(--muted); line-height:1.5; margin-bottom:.6rem; }
.dic-meta { display:flex; gap:.4rem; flex-wrap:wrap; margin-bottom:.65rem; }
.dic-meta-item {
  font-size:.58rem; font-weight:600; color:var(--brand);
  background:var(--brand-soft); padding:.15rem .45rem; border-radius:3px;
}
.dic-footer {
  display:flex; align-items:center; justify-content:space-between;
  padding-top:.55rem; border-top:1px solid var(--line);
}
.dic-footer a { font-size:.72rem; font-weight:700; color:var(--brand); text-decoration:none; }
.dic-add {
  width:28px; height:28px; border-radius:50%;
  background:var(--brand-soft); border:1px solid var(--brand); color:var(--brand);
  font-size:.9rem; font-weight:700;
  display:flex; align-items:center; justify-content:center;
  cursor:pointer; transition:background 150ms,color 150ms; flex-shrink:0;
}
.dic-add.added { background:var(--brand); color:#fff; }

/* Map hint */
.map-hint {
  position:absolute; bottom:.75rem; left:50%; transform:translateX(-50%);
  background:rgba(12,35,22,.55); color:rgba(255,255,255,.78);
  font-size:.62rem; font-weight:600; padding:.28rem .7rem;
  border-radius:999px; white-space:nowrap; pointer-events:none;
  transition:opacity 400ms;
}
.map-hint.hidden { opacity:0; }

/* List panel */
.dest-list-panel {
  border-top:1px solid var(--line); overflow-y:auto; max-height:400px;
  -webkit-overflow-scrolling:touch;
}
@media(min-width:680px){
  .dest-list-panel { border-top:none; border-left:1px solid var(--line); max-height:520px; }
}
.list-search-wrap {
  padding:.85rem 1.1rem .7rem; border-bottom:1px solid var(--line);
  background:var(--surface); position:sticky; top:0; z-index:5;
}
.dest-list-search {
  width:100%; border:1px solid var(--line-strong); border-radius:999px;
  padding:.42rem .9rem; font-size:.8rem; color:var(--text);
  font-family:var(--font-sans); background:var(--surface-alt); outline:none;
  transition:border-color 150ms;
}
.dest-list-search:focus { border-color:var(--brand); }
.list-count-label {
  font-size:.6rem; font-weight:600; color:var(--muted);
  letter-spacing:.08em; text-transform:uppercase; padding:.4rem 1.1rem 0; display:block;
}
.dest-list-row {
  display:flex; align-items:center; gap:.8rem; padding:.75rem 1.1rem;
  border-bottom:1px solid var(--line); cursor:pointer;
  transition:background 150ms; border-left:3px solid transparent;
}
.dest-list-row:hover { background:var(--surface-alt); }
.dest-list-row.active { background:var(--surface-alt); border-left-color:var(--brand); }
.dest-list-row.hidden { display:none; }
.dest-list-thumb {
  width:44px; height:44px; border-radius:var(--radius-sm); flex-shrink:0;
  display:flex; align-items:center; justify-content:center; font-size:1.1rem;
  background:linear-gradient(135deg,#2d6a41,#8f7a28); transition:transform 200ms;
}
.dest-list-row:hover .dest-list-thumb,
.dest-list-row.active .dest-list-thumb { transform:scale(1.06); }
.dest-list-info { flex:1; min-width:0; }
.dest-list-info h4 { font-size:.85rem; font-weight:700; color:var(--text); margin-bottom:.06rem; }
.dest-list-info p  { font-size:.68rem; color:var(--muted); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.dest-list-tag {
  flex-shrink:0; font-size:.56rem; font-weight:700; letter-spacing:.07em;
  text-transform:uppercase; padding:.15rem .4rem; border-radius:3px;
}
.tag-cultural { color:#5a3a00; background:rgba(143,122,40,.12); }
.tag-wildlife  { color:#1a4a1a; background:rgba(45,106,65,.12); }
.tag-scenic    { color:#1a3a5a; background:rgba(26,74,138,.10); }
.tag-coast     { color:#1a3a5a; background:rgba(26,100,160,.10); }
.tag-beach     { color:#004a5a; background:rgba(0,100,130,.10); }
.tag-heritage  { color:#5a1a1a; background:rgba(130,40,40,.10); }
.no-results-msg { padding:1.5rem 1.1rem; text-align:center; color:var(--muted); font-size:.8rem; display:none; }
.no-results-msg.visible { display:block; }

/* Route bar */
.dest-route-bar {
  background:var(--surface-dark); padding:1rem 1.5rem;
  display:flex; flex-direction:column; gap:.75rem;
}
@media(min-width:600px){
  .dest-route-bar { flex-direction:row; align-items:center; justify-content:space-between; }
}
.route-bar-text p { color:rgba(255,255,255,.65); font-size:.78rem; }
.route-bar-text strong { color:#fff; }
.route-spots { display:flex; gap:.4rem; flex-wrap:wrap; align-items:center; }
.route-spot {
  background:rgba(255,255,255,.10); border:1px solid rgba(255,255,255,.2);
  color:#fff; font-size:.66rem; font-weight:600; padding:.26rem .65rem;
  border-radius:999px; display:flex; align-items:center; gap:.3rem;
  animation:spotIn 200ms ease;
}
@keyframes spotIn { from{transform:scale(.8);opacity:0} to{transform:scale(1);opacity:1} }
.route-spot-rm {
  color:rgba(255,255,255,.4); cursor:pointer; font-size:.7rem;
  border:none; background:none; line-height:1; padding:0;
}
.route-spot-rm:hover { color:rgba(255,255,255,.8); }
.route-empty-hint { font-size:.68rem; color:rgba(255,255,255,.3); font-style:italic; }

/* Toast */
.ict-toast {
  position:fixed; bottom:1.5rem; left:50%;
  transform:translateX(-50%) translateY(80px);
  background:var(--surface-dark); color:#fff;
  font-size:.8rem; font-weight:600; padding:.65rem 1.25rem;
  border-radius:999px; box-shadow:var(--shadow-lg);
  z-index:9999; transition:transform 280ms cubic-bezier(.34,1.56,.64,1),opacity 280ms;
  opacity:0; white-space:nowrap; pointer-events:none;
}
.ict-toast.show { transform:translateX(-50%) translateY(0); opacity:1; }
</style>

<!-- Region filter pills (rendered inside .dest-hero by caller) -->
<div class="region-pills" id="ict-region-pills">
<?php foreach ($regions as $slug => $r): ?>
  <button class="region-pill<?= $slug === 'all' ? ' active' : '' ?>"
          data-region="<?= map_e($slug) ?>"
          type="button">
    <?= map_e($r['label']) ?>
  </button>
<?php endforeach; ?>
</div>

<!-- Map section -->
<div class="map-section">

  <!-- Map panel -->
  <div class="dest-map-panel" id="ict-map-panel">

    <!-- Floating info card -->
    <div class="dest-info-card" id="ict-info-card" role="dialog" aria-modal="true" aria-label="Destination details">
      <div class="dic-img" id="ict-card-img">
        <span class="dic-region-badge" id="ict-card-region"></span>
        <button class="dic-close" id="ict-card-close" aria-label="Close">✕</button>
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

    <div class="map-hint" id="ict-map-hint">Tap a pin to explore</div>

    <!-- SVG Map -->
    <svg id="ict-map" viewBox="0 0 220 345" xmlns="http://www.w3.org/2000/svg"
         role="img" aria-label="Interactive map of Sri Lanka destinations">
      <title>Sri Lanka Destinations Map</title>

      <!-- Island -->
      <path d="M95,10 C120,8 145,18 158,35 C172,52 175,72 170,95 C165,118 158,138 155,158 C152,175 158,188 160,205 C162,222 156,242 148,258 C140,274 128,288 115,298 C102,308 88,312 75,308 C62,304 52,292 45,278 C38,264 35,248 38,232 C41,216 52,202 55,186 C58,170 52,155 48,140 C44,125 42,108 45,92 C48,76 56,58 68,44 C78,32 88,12 95,10 Z"
        fill="#dceee0" stroke="#a8ccb0" stroke-width="1.5"/>

      <!-- Region zones -->
      <ellipse class="region-zone" data-region="cultural" cx="110" cy="108" rx="32" ry="26" fill="rgba(45,106,65,.12)" stroke="rgba(45,106,65,.25)" stroke-width="1" stroke-dasharray="4,3"/>
      <ellipse class="region-zone" data-region="hill"     cx="100" cy="185" rx="30" ry="23" fill="rgba(143,122,40,.10)" stroke="rgba(143,122,40,.22)" stroke-width="1" stroke-dasharray="4,3"/>
      <ellipse class="region-zone" data-region="south"    cx="88"  cy="270" rx="36" ry="15" fill="rgba(26,74,138,.08)"  stroke="rgba(26,74,138,.18)"  stroke-width="1" stroke-dasharray="4,3"/>
      <ellipse class="region-zone" data-region="east"     cx="150" cy="160" rx="16" ry="35" fill="rgba(0,100,130,.07)"  stroke="rgba(0,100,130,.16)"  stroke-width="1" stroke-dasharray="4,3"/>
      <ellipse class="region-zone" data-region="wildlife" cx="122" cy="240" rx="20" ry="18" fill="rgba(80,40,10,.07)"   stroke="rgba(80,40,10,.15)"   stroke-width="1" stroke-dasharray="4,3"/>

      <!-- Zone labels -->
      <text x="110" y="93"  font-family="system-ui" font-size="5" font-weight="700" fill="#5a6b5e" text-anchor="middle" letter-spacing=".5" pointer-events="none">CULTURAL TRIANGLE</text>
      <text x="100" y="172" font-family="system-ui" font-size="5" font-weight="700" fill="#5a6b5e" text-anchor="middle" letter-spacing=".5" pointer-events="none">HILL COUNTRY</text>
      <text x="87"  y="258" font-family="system-ui" font-size="5" font-weight="700" fill="#5a6b5e" text-anchor="middle" letter-spacing=".5" pointer-events="none">SOUTH COAST</text>

      <!-- Destination pins (generated from PHP data) -->
      <?php foreach ($destinations as $d): ?>
      <g class="ict-pin"
         data-id="<?= map_e($d['slug']) ?>"
         data-region="<?= map_e($d['region_slug']) ?>"
         tabindex="0"
         role="button"
         aria-label="<?= map_e($d['name']) ?>">
        <circle class="pin-circle"
                cx="<?= (float)$d['map_x'] ?>"
                cy="<?= (float)$d['map_y'] ?>"
                r="<?= (float)$d['map_r'] ?>"
                fill="#2d6a41" stroke="#fff" stroke-width="1.8"/>
        <text x="<?= (float)$d['map_x'] ?>"
              y="<?= (float)$d['map_y'] + 3.5 ?>"
              font-family="system-ui"
              font-size="<?= $d['map_r'] >= 7 ? '6.5' : '5.5' ?>"
              font-weight="800" fill="#fff" text-anchor="middle"
              pointer-events="none"><?= map_e($d['map_initial']) ?></text>
        <text x="<?= (float)$d['map_x'] ?>"
              y="<?= (float)$d['map_y'] + (float)$d['map_r'] + 11 ?>"
              font-family="system-ui" font-size="6"
              font-weight="700" fill="#0c2316" text-anchor="middle"
              pointer-events="none"><?= map_e($d['name']) ?></text>
      </g>
      <?php endforeach; ?>

      <!-- Compass -->
      <g transform="translate(192,24)">
        <circle cx="0" cy="0" r="12" fill="rgba(255,255,255,.85)" stroke="#c0b8a8" stroke-width="1"/>
        <text x="0" y="-4" font-family="system-ui" font-size="7" font-weight="700" fill="#5a6b5e" text-anchor="middle">N</text>
        <line x1="0" y1="-1" x2="0" y2="3" stroke="#2d6a41" stroke-width="1.5"/>
      </g>
    </svg>

  </div><!-- /dest-map-panel -->

  <!-- List panel -->
  <div class="dest-list-panel" id="ict-list-panel">
    <div class="list-search-wrap">
      <input class="dest-list-search" type="search" id="ict-list-search"
             placeholder="🔍  Search destinations…" autocomplete="off"/>
    </div>
    <span class="list-count-label" id="ict-list-count"><?= count($destinations) ?> destinations</span>

    <?php
    $tag_map = [
        'cultural' => 'tag-cultural',
        'hill'     => 'tag-scenic',
        'wildlife' => 'tag-wildlife',
        'south'    => 'tag-coast',
        'east'     => 'tag-beach',
    ];
    $tag_label_map = [
        'cultural' => 'Cultural',
        'hill'     => 'Scenic',
        'wildlife' => 'Wildlife',
        'south'    => 'Coast',
        'east'     => 'Beach',
    ];
    foreach ($destinations as $d):
        $tag_class = $tag_map[$d['region_slug']] ?? 'tag-cultural';
        $tag_label = $tag_label_map[$d['region_slug']] ?? ucfirst($d['region_slug']);
    ?>
    <div class="dest-list-row"
         data-id="<?= map_e($d['slug']) ?>"
         data-region="<?= map_e($d['region_slug']) ?>">
      <div class="dest-list-thumb"><?= $d['emoji'] ?></div>
      <div class="dest-list-info">
        <h4><?= map_e($d['name']) ?></h4>
        <p><?= map_e(mb_strimwidth($d['excerpt'], 0, 55, '…')) ?></p>
      </div>
      <span class="dest-list-tag <?= $tag_class ?>"><?= map_e($tag_label) ?></span>
    </div>
    <?php endforeach; ?>

    <div class="no-results-msg" id="ict-no-results">No destinations match your search.</div>
  </div>

</div><!-- /map-section -->

<!-- Route bar (sticky to bottom of section) -->
<div class="dest-route-bar" id="ict-route-bar">
  <div class="route-bar-text">
    <p><strong id="ict-route-label">Your route</strong></p>
    <p style="font-size:.68rem;color:rgba(255,255,255,.4);margin-top:.1rem;" id="ict-route-sub">
      Tap + on any destination to start building your route.
    </p>
  </div>
  <div class="route-spots" id="ict-route-spots">
    <span class="route-empty-hint" id="ict-route-empty">No stops added yet</span>
  </div>
  <a href="<?= e(base_url('plan')) ?>" class="btn-gold" id="ict-route-btn"
     style="opacity:.4;pointer-events:none;">→ Build My Route</a>
</div>

<!-- Toast -->
<div class="ict-toast" id="ict-toast" aria-live="polite"></div>

<script>
(function() {
/* ── Data injected from PHP ─────────────────────────────── */
const DESTINATIONS = <?= json_encode($js_destinations, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?>;

/* ── State ──────────────────────────────────────────────── */
let activePin    = null;
let activeRegion = 'all';
let searchQuery  = '';
let route        = [];

/* ── Elements ───────────────────────────────────────────── */
const mapPanel   = document.getElementById('ict-map-panel');
const infoCard   = document.getElementById('ict-info-card');
const cardImg    = document.getElementById('ict-card-img');
const cardRegion = document.getElementById('ict-card-region');
const cardName   = document.getElementById('ict-card-name');
const cardDesc   = document.getElementById('ict-card-desc');
const cardMeta   = document.getElementById('ict-card-meta');
const cardLink   = document.getElementById('ict-card-link');
const cardAdd    = document.getElementById('ict-card-add');
const cardClose  = document.getElementById('ict-card-close');
const listPanel  = document.getElementById('ict-list-panel');
const listSearch = document.getElementById('ict-list-search');
const listCount  = document.getElementById('ict-list-count');
const noResults  = document.getElementById('ict-no-results');
const routeBar   = document.getElementById('ict-route-bar');
const routeSpots = document.getElementById('ict-route-spots');
const routeBtn   = document.getElementById('ict-route-btn');
const routeLabel = document.getElementById('ict-route-label');
const routeSub   = document.getElementById('ict-route-sub');
const routeEmpty = document.getElementById('ict-route-empty');
const mapHint    = document.getElementById('ict-map-hint');
const toast      = document.getElementById('ict-toast');

/* ── Toast ──────────────────────────────────────────────── */
let toastTimer;
function showToast(msg) {
  toast.textContent = msg;
  toast.classList.add('show');
  clearTimeout(toastTimer);
  toastTimer = setTimeout(() => toast.classList.remove('show'), 2400);
}

/* ── Info card ──────────────────────────────────────────── */
function openCard(id) {
  const d = DESTINATIONS[id];
  if (!d) return;
  const added = route.includes(id);

  cardImg.style.background = d.cardGrad || 'linear-gradient(135deg,#1a4a2a,#2d6a41)';
  cardRegion.textContent   = d.regionLabel;
  cardName.textContent     = d.name;
  cardDesc.textContent     = d.desc;
  cardMeta.innerHTML       = (d.meta || []).map(m => `<span class="dic-meta-item">${m}</span>`).join('');
  cardLink.textContent     = `Explore ${d.name} →`;
  cardLink.href            = d.url || '#';
  cardAdd.className        = 'dic-add' + (added ? ' added' : '');
  cardAdd.textContent      = added ? '✓' : '+';
  cardAdd.dataset.id       = id;

  // Position
  const pw = mapPanel.offsetWidth;
  infoCard.classList.remove('pos-right','pos-left','pos-top');
  if (pw >= 400)      infoCard.classList.add('pos-right');
  else if (pw >= 260) infoCard.classList.add('pos-left');
  else                infoCard.classList.add('pos-top');

  infoCard.classList.add('visible');
  activePin = id;

  document.querySelectorAll('.ict-pin').forEach(p => p.classList.remove('active'));
  const pin = document.querySelector(`.ict-pin[data-id="${id}"]`);
  if (pin) pin.classList.add('active');

  highlightListRow(id);
  mapHint.classList.add('hidden');
}

function closeCard() {
  infoCard.classList.remove('visible');
  document.querySelectorAll('.ict-pin').forEach(p => p.classList.remove('active'));
  document.querySelectorAll('.dest-list-row').forEach(r => r.classList.remove('active'));
  activePin = null;
}

function highlightListRow(id) {
  document.querySelectorAll('.dest-list-row').forEach(r => {
    r.classList.toggle('active', r.dataset.id === id);
  });
  const el = listPanel.querySelector(`.dest-list-row[data-id="${id}"]`);
  if (el) el.scrollIntoView({ block:'nearest', behavior:'smooth' });
}

/* ── Region filter ──────────────────────────────────────── */
function applyFilter(region) {
  activeRegion = region;
  document.querySelectorAll('.region-pill').forEach(p => {
    p.classList.toggle('active', p.dataset.region === region);
  });

  let listVis = 0;
  document.querySelectorAll('.dest-list-row').forEach(el => {
    const show = (region === 'all' || el.dataset.region === region);
    el.classList.toggle('hidden', !show);
    if (show) listVis++;
  });
  listCount.textContent = `${listVis} destination${listVis !== 1 ? 's' : ''}`;
  noResults.classList.toggle('visible', listVis === 0 && searchQuery === '');

  document.querySelectorAll('.region-zone').forEach(z => {
    z.classList.toggle('dimmed', region !== 'all' && z.dataset.region !== region);
  });

  if (activePin && region !== 'all' && DESTINATIONS[activePin]?.region !== region) closeCard();
}

/* ── Search ─────────────────────────────────────────────── */
function applySearch(q) {
  searchQuery = q.toLowerCase().trim();
  let vis = 0;
  document.querySelectorAll('.dest-list-row').forEach(el => {
    const name  = el.querySelector('h4').textContent.toLowerCase();
    const desc  = el.querySelector('p').textContent.toLowerCase();
    const matchR = activeRegion === 'all' || el.dataset.region === activeRegion;
    const matchS = !searchQuery || name.includes(searchQuery) || desc.includes(searchQuery);
    const show   = matchR && matchS;
    el.classList.toggle('hidden', !show);
    if (show) vis++;
  });
  listCount.textContent = `${vis} destination${vis !== 1 ? 's' : ''}`;
  noResults.classList.toggle('visible', vis === 0);
}

/* ── Route ──────────────────────────────────────────────── */
function toggleRoute(id) {
  const d = DESTINATIONS[id];
  if (!d) return;
  const idx = route.indexOf(id);
  if (idx === -1) { route.push(id); showToast(`✓ ${d.name} added to your route`); }
  else            { route.splice(idx, 1); showToast(`Removed ${d.name}`); }
  renderRouteBar();
  updateAddBtns(id);
  // Persist to sessionStorage for cross-page wishlist
  try { sessionStorage.setItem('ict_route', JSON.stringify(route)); } catch(e){}
}

function updateAddBtns(id) {
  const added = route.includes(id);
  if (activePin === id) {
    cardAdd.className   = 'dic-add' + (added ? ' added' : '');
    cardAdd.textContent = added ? '✓' : '+';
  }
}

function renderRouteBar() {
  // Remove old spots (keep empty hint)
  routeSpots.querySelectorAll('.route-spot').forEach(s => s.remove());

  route.forEach(id => {
    const d = DESTINATIONS[id];
    if (!d) return;
    const spot = document.createElement('div');
    spot.className = 'route-spot';
    spot.dataset.id = id;
    spot.innerHTML = `${d.emoji} ${d.name} <button class="route-spot-rm" type="button" data-id="${id}" aria-label="Remove ${d.name}">✕</button>`;
    routeSpots.insertBefore(spot, routeEmpty);
  });

  const has = route.length > 0;
  routeEmpty.style.display = has ? 'none' : '';
  routeBtn.style.opacity       = has ? '1' : '.4';
  routeBtn.style.pointerEvents = has ? 'auto' : 'none';
  routeLabel.textContent = has ? `Your route — ${route.length} stop${route.length > 1 ? 's' : ''}` : 'Your route';
  routeSub.textContent   = has ? "We'll build an itinerary around these. Send us your dates." : 'Tap + on any destination to start building your route.';

  // Update route button href with selected slugs
  if (has) {
    const params = new URLSearchParams({ destinations: route.join(',') });
    routeBtn.href = `<?= e(base_url('plan')) ?>?${params}`;
  }
}

/* ── Events ─────────────────────────────────────────────── */
// Pins
document.querySelectorAll('.ict-pin').forEach(pin => {
  const act = () => {
    const id = pin.dataset.id;
    activePin === id ? closeCard() : openCard(id);
  };
  pin.addEventListener('click', act);
  pin.addEventListener('keydown', e => { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); act(); } });
});

// Close card
cardClose.addEventListener('click', closeCard);
mapPanel.addEventListener('click', e => {
  if (!e.target.closest('.ict-pin') && !e.target.closest('.dest-info-card')) closeCard();
});

// Card add button
cardAdd.addEventListener('click', () => { if (activePin) toggleRoute(activePin); });

// Region pills
document.querySelectorAll('.region-pill').forEach(p => {
  p.addEventListener('click', () => applyFilter(p.dataset.region));
});

// List rows
document.querySelectorAll('.dest-list-row').forEach(row => {
  row.addEventListener('click', () => openCard(row.dataset.id));
});

// Route spot remove
routeSpots.addEventListener('click', e => {
  const btn = e.target.closest('.route-spot-rm');
  if (btn) toggleRoute(btn.dataset.id);
});

// Search
listSearch.addEventListener('input', () => applySearch(listSearch.value));

// Escape key
document.addEventListener('keydown', e => {
  if (e.key === 'Escape' && activePin) closeCard();
});

// Auto-hide hint
setTimeout(() => mapHint.classList.add('hidden'), 4000);

// Restore route from sessionStorage on load
try {
  const saved = JSON.parse(sessionStorage.getItem('ict_route') || '[]');
  if (Array.isArray(saved)) { route = saved.filter(id => DESTINATIONS[id]); renderRouteBar(); }
} catch(e) {}

})(); // end IIFE
</script>
