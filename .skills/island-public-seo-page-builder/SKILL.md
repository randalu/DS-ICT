---
name: island-public-seo-page-builder
description: Use for Island Culture Travels public page creation or edits involving destinations, experiences, itineraries, tours, reviews, guide pages, contact CTAs, SEO metadata, canonical URLs, schema/JSON-LD, Open Graph, Twitter tags, sitemap/robots behavior, images, and internal linking.
---

# Island Public SEO Page Builder

## Purpose

Build and edit public-facing Island Culture Travels pages while preserving SEO, conversion paths, route stability, and travel-brand quality.

## Scope

- Public route files such as `destinations.php`, `destination.php`, `experiences.php`, `experience.php`, `itineraries.php`, `itinerary.php`, `tour.php`, `tours.php`, `reviews.php`, `seasonal.php`, `theme.php`, `sri_lanka_in_month.php`, `contact.php`, and static page routes.
- SEO/schema/includes: `includes/schema.php`, `includes/seo.php`, `includes/header.php`, `includes/footer.php`, `includes/cta.php`, `includes/related_*`, `sitemap*.php`, `robots.txt`.
- Content/media helpers and public CSS only when needed.

## May Touch

- Public route files, public includes, public CSS, sitemap/robots files, tests, and content/media helpers directly needed for the page or SEO task.

## Inspect First

- `AGENTS.md`, `references/project-profile.md`, and root `SKILL.md`.
- The current route, nearby similar routes, includes used by that route, and related CSS.
- Existing meta/canonical/schema/OG/Twitter patterns on the route.
- Existing internal links to destinations, experiences, itineraries, reviews, guide pages, and contact/WhatsApp CTAs.

## Safety Rules

- Preserve public URLs, route names, slugs, canonical patterns, sitemap behavior, and existing redirects unless the task explicitly requires a migration.
- Keep pages SEO-first, mobile-first, fast-loading, readable, and conversion-focused.
- Keep WhatsApp/contact inquiry CTAs visible and contextually relevant.
- Use schema helpers and existing metadata patterns rather than ad hoc output.
- Do not assert public-domain redirect behavior without repo/server evidence.
- Keep image alt text meaningful and inspect media helpers before changing image rendering.

## Validation Commands

```powershell
composer validate
composer test
git diff --check
php -l path\to\changed-file.php
php cli\smoke_post_gates.php --only=dynamic
php cli\lint_image_alt.php
```

Run the broad PHP syntax sweep if shared includes, route bootstrap, or many PHP files changed.

## Reporting Format

- Public routes/files changed.
- SEO/meta/schema/canonical/OG/Twitter/sitemap effects.
- Internal links and CTA paths preserved or changed.
- Validation results.
- Remaining SEO or content risks.

## Must Not Do

- Do not test live public domains unless explicitly asked.
- Do not break or rename public URLs/slugs casually.
- Do not remove contact/WhatsApp conversion paths without replacement.
- Do not introduce layout-heavy rewrites when a focused page edit is enough.
