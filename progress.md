# Progress Log

## 2025-07-08 — Session Start
- Completed full codebase review (189 PHP files)
- Created planning files: task_plan.md, findings.md, progress.md
- User confirmed all 4 answers: recommended phases, recommended admin seed handling, barrel split, reCAPTCHA from lka.lk
- Phase 1.1: Added admin login rate limiting (auth.php, admin/login.php)
- Phase 1.2: Added CSP-Report-Only header (.htaccess)
- Phase 1.3: Added security warnings to default admin seed (schema.sql, install.php)
- Phase 3.2: Added rate limit check to subscribe.php
- Phase 2.1: Split includes/functions.php into 5 focused modules (db.php, media.php, sessions.php, forms.php, schema_helpers.php) using barrel pattern
- Phase 2.2: Added column allowlist to customer_auth.php (customer_allowed_columns, customer_column_name, customer_column_select) and applied to all dynamic column SQL construction

## 2026-07-10 — Mobile Redesign + Partners Wiring
- Phase 1: Global header, navigation, sticky scroll, hamburger menu, breadcrumbs, bottom nav, back-to-top, scroll spy, title animations
- Phase 2.1: Multi-step inquiry form (4-step Alpine.js wizard)
- Phase 2.2: Itinerary hero redesign (price toggle, title animation, compare button)
- Phase 2.3: Itinerary listing (filter carousels, theme Q&A, card carousel)
- Phase 2.4: Itinerary timeline (zigzag, destinations carousel, moon emoji removed)
- Phase 2.5: Experience page (tag pills, Where to Experience, suggested routes)
- Phase 2.6: CTA section + footer accordion
- Phase 2.7: Mobile menu overlay, language selector, Plan My Trip floating button
- Phase 3: Accessibility (skip nav, aria-current, aria-describedby, focus-visible, prefers-reduced-motion)
- Phase 3: Performance (preconnect, will-change cleanup, image error fallback, touch feedback)
- Phase 4: Audit — 6 gaps found and fixed (theme auto-rotate, timeline, stays placeholder, tags nowrap, CTA side-by-side, bottom padding)
- Phase 5: Partners wiring — fetch_business_partners_for_day() helper, per-day partner cards in timeline
- All commits pushed to main (3d2573d, bf2dfd9, 8a8e31c, 5eb7d24)
- Live server SSH requires interactive password — manual pull needed
