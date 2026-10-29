# Content readiness

Measured against the live `ict` database on 2026-10-29 (Phase 0), not copied
from the plan. Re-measure before each phase gate with
`build/scripts/probe_content.php` (local helper, not committed).

## Published inventory

| Family | Published rows |
|---|---|
| Destinations | 43 (6 featured) |
| Itineraries | 13 (127 days) |
| Tours | 7 |
| Day tours | 10 |
| Experiences | 26 |
| Blog posts | 21 |
| Country pages | 20 |
| Static pages | 18 total, 5 unpublished |
| Activity pages | 17 |
| FAQ entries | **0** |
| Reviews | **0 published** (1 placeholder, unpublished in Phase 0) |
| Business partners | **0** |
| Media assets | 41 rows |

## Gaps that block launch (not Phase 1)

| Item | Measured | Blocker for |
|---|---|---|
| Destination heroes | 12 with no hero, 27 pointing at placeholder artwork | Phase 3 detail pages, Phase 8 |
| Destination enrichment | 35 missing `region_slug`, 42 missing `best_time_json` | Phase 2 region filters, Phase 3 |
| Destination coordinates | stored in `map_x`/`map_y`, not `lat`/`lng`; no row has real geo coordinates | Phase 2 map teaser |
| Reviews | none verified; consent not confirmed | trust strip, AggregateRating schema |
| FAQ | 0 rows | `/faq`, FAQPage schema |
| Legal pages | 5 unpublished: `privacy-policy`, `booking-terms-and-conditions`, `cancellation-refund-policy`, `payment-deposit-policy`, `book-with-confidence` | footer legal links currently 404 — see note |
| Partners | 0 rows, directory disabled in Phase 0 | `/business-partners` intentionally 404s until populated |
| Media files | `assets/uploads/` is empty locally, so 41 media rows resolve to missing files | visual sign-off, image alt lint |
| Itinerary pricing | 1 itinerary has no `price_from` | price display falls back to hidden |
| Homepage hero media | hero references an `assets/uploads/homepage/*.png` file that is missing locally | home hero falls back to `assets/images/galle-lighthouse.jpg` |

## Phase 0 changes already applied

Migration `database/migrations/2026-11-02-remodel-phase0-safety.sql`:

1. `business_partners_public_enabled` → `0`, so `/business-partners` 404s instead
   of rendering an empty directory.
2. Deleted `ai_api_key` and `test_key` from `site_settings`. **The OpenRouter key
   that lived there still needs rotating at the provider** — it was readable by
   any admin screen.
3. Unpublished the only review row (`Test Guest - Remove Later`). The public
   reviews page already filtered it; the data now matches that intent.

## Note on footer legal links

The v2 footer links to the five policy pages by their real slugs. Those pages
exist in `static_pages` but are unpublished, so the links resolve to 404 until
legal sign-off in Phase 8. This is a content gate, not a routing bug: the URLs
are correct and will start returning 200 the moment the rows are published.
