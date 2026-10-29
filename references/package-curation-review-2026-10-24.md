# Package curation and layout review — 24 October 2026

Applied to the normal local database and local source only. No production writes, uploads, deployment, commits or pushes. The owner approved consolidation by matching theme while retaining the itinerary planning guides. Existing dirty checkout changes were preserved; this review's five source-file changes are isolated in `output/package-curation-20261024/task-only.diff`.

## Consolidated packages

| Retained Tour | Content and linked guides |
| --- | --- |
| Wildlife Safari Package, Tour 8 | Six location/experience cards: Habarana / Minneriya / Kaudulla, Udawalawe, Yala / Tissamaharama, optional Bundala, a south-coast finish and Wilpattu as an alternative requiring a revised route. Linked to the existing 7-day wildlife itinerary 1 and 9-day wildlife itinerary 10. |
| Honeymoon Package, Tour 6 | Six location/experience cards: Sigiriya / Habarana, optional Kandy, tea country, optional Ella / rail, optional Yala and a coastal / Galle finish. Linked to the existing 12-day honeymoon itinerary 8. |

The Safari duplicate (Tour 1) is retained in the database and unpublished locally. Its destination relationships are retained and carried to Tour 8. The exact former clean URL `/tours/sri-lanka-wildlife-safari-tour` has a 301 to `/tours/sri-lanka-wildlife-safari-package`. The old duplicate planning-link row is retained but inactive. Existing generic guide-link rows 3 and 8 now point to the matching wildlife/honeymoon itinerary records; a 7-day guide link was added.

All itinerary records, slugs, publication flags, daily content, prices and day-tour galleries remain intact. Tour prices and inclusions/exclusions are unchanged. New wording describes proposed/optional experiences, rather than promising sightings or unquoted extras. The Honeymoon detail hero was deliberately replaced with an existing tea-country photograph; its independent cover shows two beach travellers. Other stored detail heroes and social images were preserved.

## Layout findings and fixes

- Independent covers assigned to 29 records: all 13 itineraries, all 8 retained Tour records (including the unpublished duplicate) and 8 of 10 Day Tours. This means 28 currently published packages have assigned covers. All selected files exist locally and have manually reviewed alt text.
- Eight existing static image files were registered in Media Library without copying, uploading, modifying or deleting the underlying photographs. Existing library images were reused where suitable.
- Collection banners now prefer assigned covers, avoiding random selection from legacy placeholder heroes when covers are available. Each package's detail hero remains a separate field.
- Mobile Tour and itinerary detail pages hide the duplicate floating WhatsApp bubble when their enquiry bar is present. The bars hide while the navigation menu is open.
- Itinerary headings wrap normally instead of clipping behind the old typing effect. Indicative hero prices use readable white text. Day expansion and customisation remain available.

## Verification

- Composer manifest valid. **243 tests / 1,562 assertions**, with **14 existing skips**.
- **83 data-preservation and curation checks** passed, including field allowlists, retained rows, unchanged day content/galleries, 29 cover references, valid coverage cards and linked guides.
- **59 local HTTP checks** passed across collection/home pages, all retained package routes, chosen cover files, coverage text and the old Safari alias. No PHP warning/fatal output was found on those pages.
- Apache returned the expected 301 for the old Tour path in a local subdirectory request. The isolated root-mounted preview also followed the alias to the retained Tour successfully. The rule's root-mounted production target has not been tested live.
- All three changed public PHP files and the curation/router/verification helpers passed syntax checks. Task whitespace checks passed.
- Browser review covered all three collections, both enriched Tours and the 10-day itinerary at measured narrow-mobile and desktop widths (333 and 1388 CSS pixels), plus additional mobile observations at 390/433 CSS pixels. No document overflow was observed. Both Tours show six coverage cards; the itinerary has ten day disclosures. Expand-all opened all ten; mobile menu/enquiry behaviour and price/title fixes were observed.
- Image-alt lint retains six pre-existing blank Media Library descriptions. No new assigned-cover description gaps were introduced.

## Remaining work before release preparation

1. Obtain matching photographs for **Day Tour 7: Anuradhapura** and **Day Tour 10: Trincomalee / Nilaveli**. Both covers remain unassigned rather than showing a different destination. Attempts to retrieve their referenced live files from this environment did not complete; no conclusion about live availability is established.
2. Reconcile legacy detail photography. The local snapshot has **27** published-package hero references that are placeholders or missing locally. This is a local media inventory finding, not proof of a live-site defect. The new collection covers do not replace those independent detail fields. See `legacy-hero-gaps.json`.
3. Review retained guide wording for existing factual/guarantee claims before publishing the combined content. The itinerary day prose was intentionally preserved; the new Tours do not repeat claims of guaranteed wildlife, fixed train seats or automatic romantic extras.
4. After the media/content review, prepare an isolated release candidate with an exact code/data allowlist, compare against current production, and produce a guarded content migration and rollback. Local record/media IDs must not be assumed to match production. This curation helper is local-only and is not a production migration.

## Review and recovery evidence

- Preview: `http://127.0.0.1:8142/tours/sri-lanka-wildlife-safari-package`; the loopback server uses an isolated clone containing only public content. It has no fake administrator route, no copied account/inquiry data and rejects POST requests. Collection settings use defaults because private settings were not copied.
- `inventory.json`, `before-local.json`, `after-local.json`, `result-local.json` and `plan.json` record exact public-content inputs, changes, assigned media and original rows. Do not rerun the helper: it refuses repeated attempts. A rollback should use these preimages after checking for subsequent edits; no rollback was executed.
- `verification.json`, `http-review.json`, `browser-frames.json`, `composer-test-final.txt`, `image-alt.txt` and screenshots record the checks.

## Cover selections

| Record | Package | Chosen image description |
| --- | --- | --- |
| Itinerary 1 | Sri Lanka Wildlife Safari Itinerary 7 Days | Elephants grazing beside a safari track and jeep in Sri Lanka |
| Itinerary 3 | Sri Lanka Itinerary 5 Days | Sigiriya rock fortress above the surrounding fields and water |
| Itinerary 4 | Sri Lanka Itinerary 7 Days | Temple buildings beside Kandy Lake |
| Itinerary 5 | Sri Lanka Itinerary 10 Days | A blue train crossing Nine Arch Bridge among forested hills |
| Itinerary 6 | Sri Lanka Itinerary 12 Days | Green tea fields and trees in the Sri Lankan hill country |
| Itinerary 7 | Sri Lanka Itinerary 14 Days | The summit ruins of Sigiriya surrounded by green countryside |
| Itinerary 8 | Sri Lanka Honeymoon Itinerary 12 Days | Green tea fields and trees in the Sri Lankan hill country |
| Itinerary 9 | Sri Lanka Family Itinerary 10 Days | Elephants grazing beside a safari track and jeep in Sri Lanka |
| Itinerary 10 | Sri Lanka Wildlife Itinerary 9 Days | A leopard resting on a rock beside a tree |
| Itinerary 11 | Sri Lanka South Coast Itinerary 8 Days | Palm-lined coastline and surf near Mirissa |
| Itinerary 12 | Sri Lanka East Coast Itinerary 10 Days | Sigiriya rock fortress above the surrounding fields and water |
| Itinerary 13 | Sri Lanka Luxury Itinerary 11 Days | Green tea fields and trees in the Sri Lankan hill country |
| Itinerary 14 | Sri Lanka Birding and Nature Itinerary 12 Days | A colourful junglefowl standing on the forest floor |
| Tour 1 | Sri Lanka Wildlife Safari Tour | An elephant walking beside tall grass in a Sri Lankan wildlife park |
| Tour 3 | Classic Sri Lanka Private Tour Package | Sigiriya rock fortress above the surrounding fields and water |
| Tour 4 | Sri Lanka 10 Day Itinerary Package | A blue train crossing Nine Arch Bridge among forested hills |
| Tour 5 | Sri Lanka 14 Day Grand Tour Package | The summit ruins of Sigiriya surrounded by green countryside |
| Tour 6 | Sri Lanka Honeymoon Package | Two travellers carrying surfboards along a palm-lined beach |
| Tour 7 | Sri Lanka Family Tour Package | Elephants grazing beside a safari track and jeep in Sri Lanka |
| Tour 8 | Sri Lanka Wildlife Safari Package | A leopard resting on a rock beside a tree |
| Tour 9 | Luxury Sri Lanka Slow Travel Package | Green tea fields and trees in the Sri Lankan hill country |
| Day Tour 1 | Sigiriya & Dambulla Day Tour | Sigiriya rock fortress above the surrounding fields and water |
| Day Tour 2 | Kandy City Highlights Day Tour | Temple buildings beside Kandy Lake |
| Day Tour 3 | Yala National Park Safari Day Tour | A leopard resting on a rock beside a tree |
| Day Tour 4 | Galle Fort and South Coast Day Tour | Galle Lighthouse and palm trees beside the fort walls |
| Day Tour 5 | Nuwara Eliya Tea Country Day Tour | Green tea fields and trees in the Sri Lankan hill country |
| Day Tour 6 | Ella to Kandy via Little Adam's Peak and Nine Arch Bridge | A blue train crossing Nine Arch Bridge among forested hills |
| Day Tour 7 | Anuradhapura Ancient City Day Tour | Pending matching photograph |
| Day Tour 8 | Mirissa and Whale Watching Day Tour | Palm-lined coastline and surf near Mirissa |
| Day Tour 9 | Udawalawe National Park Safari Day Tour | Elephants grazing beside a safari track and jeep in Sri Lanka |
| Day Tour 10 | Trincomalee and Nilaveli Day Tour | Pending matching photograph |
