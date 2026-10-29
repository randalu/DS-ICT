# Activity entry galleries — local implementation

Implemented 25 October 2026. Code is local and uncommitted; production has not been changed.

## Behavior

- Image Manager → Activities → category now groups a 1–5 photograph gallery under each saved entry. Hero, Cover, and Open Graph controls remain above these galleries.
- Select existing Media Library images or upload photographs individually or in a sequential queue with preview, required alt text, progress, and per-file outcomes. Replace images, edit descriptions, move images up/down, and remove assignments without deleting Media Library assets.
- Category editing now preserves entry IDs and gallery ownership, including text changes, title exchanges, and reordering. Revisions reject outdated content/gallery writes; deleting entries with images requires confirmation.
- Draft entries may have no photographs. Existing published text remains visible without galleries. Publishing or saving a published category requires images for every retained entry. The final image of a published entry cannot be removed.
- Public entry galleries use a 3:2 lead photo, selectable thumbnails, responsive image variants, and an entry-specific full-screen viewer. Mobile stacks text and images; tablet/desktop use adjacent columns. Single images have no thumbnails/navigation. Existing entries without images have no empty image frame.
- Viewer support includes previous/next, keyboard arrows, Escape, focus containment/restoration, and pointer swipe handling. It does not activate the global image lightbox. Without JavaScript, the first image and direct original-image links remain available.
- Entry images are included in published-page image sitemap records and ImageObject metadata. Site Audit and image-alt lint report missing galleries, missing descriptions, and missing files. Audit links target the corresponding Image Manager entry.
- Media deletion checks include Activity page images and gallery assignments. Gallery assets also have a database foreign key with DELETE RESTRICT.

## Migration and preservation

Only `database/migrations/2026-10-25-activity-entry-galleries.sql` was applied locally, outside the global migration runner. It adds `activity_example_images` and references existing entries/assets.

Migration SHA-256: `8dbc2d4e85db6ab788b1c7334ad6408f6fabbe134455531a19bab4eaaae81f87`.

The migration ledger records the matching checksum. Existing counts were unchanged: 17 pages, 60 entries, 4 published pages, and 38 Media Library records. No real entry images were assigned automatically.

Temporary rehearsal records exercised 0, 1, 2, and 5 images, portrait images, long titles, library selection/replacement, real HTTP uploads, ordering, removal, and stale saves. Two parallel CLI workers submitted the same four-image gallery revision: one saved the fifth image and the other was rejected as stale. The final count was five.

Rehearsal records and the two temporary uploads/variants were removed. Hashes of all original rows in activity_pages, activity_examples, activity_example_images, and media_assets match the pre-rehearsal hashes. The loopback rehearsal server was stopped.

## Validation

- Full Composer suite: 289 tests, 1,982 assertions, 22 skipped; passed.
- Thirteen gallery tests cover limits, ownership, stale/concurrent requests, ID preservation, title exchanges, confirmed removal, published requirements, invalid media/alt/IDs, responsive markup, and asset deletion protection.
- Targeted PHP/JavaScript syntax, Composer manifest validation, and `git diff --check` pass.
- Responsive-image dry run for Image Manager: 86 scanned, 11 eligible originals, zero writes and zero errors.
- Authenticated API guard probes: guest 401, unsafe GET 405, invalid CSRF 419, oversized request 413, invalid entry ID 422.
- Browser DOM checks cover public galleries, Image Manager, and category editor around 320, 390, 768, 1024, and 1440 CSS pixels. No horizontal overflow or clipped admin inputs; gallery frames retain 3:2 proportions and thumbnail targets remain at least 44px. Exact 768px public check confirms adjacent columns.
- Viewer keyboard navigation, wrap, single-image controls, focus trapping/restoration, global-lightbox exclusion, and no-script original-image fallback were observed in the browser.
- Image-alt lint currently reports 12 published entries awaiting galleries and the same 6 existing Media Library alt-text gaps. These are content work, not automatically populated images.

## Visual acceptance completed — 25 October 2026

The initial screenshot/swipe blocker was resolved in a fresh browser session. Local browser visual acceptance is complete. Production deployment has not been performed.

- Saved screenshots at requested widths 320, 390, 768, 1024, and 1440. The 1024 case measured 1025 CSS pixels due to browser scaling; all other public cases matched exactly.
- Public entry frames retain 3:2 proportions, every thumbnail meets the 44px target within subpixel rounding, and no page overflow was found. Phone layouts stack; tablet/desktop layouts place text and gallery side by side.
- Fixed excessive spacing between thumbnails in two-image galleries. Thumbnails now remain adjacent with a 7px gap and still fit five images on narrow screens.
- Complete category screenshots exercise 0, 1, 2, 5, and 4 images with the real header/footer. Focused screenshots retain production entry markup/styles while omitting other test entries and surrounding site sections for readable component evidence.
- Captured Image Manager at phone/desktop sizes and the category editor on mobile. No clipped inputs, page overflow, or browser errors/warnings were observed.
- Native browser left swipe changed 2/5 to 3/5; reverse swipe returned to 2/5; a vertical gesture kept 2/5 selected. Escape closed the viewer and restored focus.
- A single portrait image opens uncropped with `object-fit: contain`; navigation arrows remain hidden. The saved viewer screenshot includes its counter and close button.
- Thirteen gallery tests / 60 assertions passed again. Fixture PHP syntax and `git diff --check` pass. The earlier full Composer result remains 289 tests / 1,982 assertions / 22 skipped; product PHP/JavaScript did not change in this visual follow-up.

The rehearsal page and unused test assets were removed, its loopback server stopped, temporary viewport overrides cleared, and the original browser tab restored. Cleanup found Media Library records 1861 and 1862 referenced by the real Arts and Crafts entry 73, so it retained those records and assignments. Original baseline hashes differ because non-fixture activity/media edits occurred during acceptance; cleanup separately verified that current non-fixture rows and retained assets were preserved exactly.

Final read at validation time: 17 pages, 60 entries, 3 entry-image assignments, and 41 Media Library assets. These counts can change as editing continues. Image-alt lint reports 11 published entries still awaiting galleries and 6 existing Media Library alt gaps; these content tasks do not invalidate the gallery UI checks.

Current evidence and screenshots: `output/activity-gallery-visual-20261025/`, particularly layouts.json, page-checks.json, interactions.json, single-viewer.json, cleanup.json, public-390.jpg, public-768.jpg, public-1440.jpg, and portrait-viewer-mobile.jpg. Earlier implementation evidence remains under `output/activity-gallery-smoke/`. Both directories are blocked from public HTTP access.

Next: prepare a scoped deployment package separately, and continue selecting relevant photographs for the real entries through Image Manager.
