---
name: island-public-uiux-linking-reviewer
description: Use for Island Culture Travels public UI/UX, responsive layout, navigation, footer, hero, card, carousel or rail behavior, mobile views, internal linking, CTA hierarchy, and visual consistency review or fixes.
---

# Island Public UI/UX Linking Reviewer

## Purpose

Review or improve the public user experience without damaging conversion flow, responsive layout, navigation, footer links, or internal discovery paths.

## Scope

- Public CSS: `assets/css/style.css`, `assets/css/new-home.css`, `assets/css/detail-redesign.css`.
- Public includes: `includes/header.php`, `includes/footer.php`, `includes/cta.php`, `includes/sticky_cta.php`, `includes/related_grid.php`, `includes/destination_map.php`.
- Public route files and JavaScript when UI behavior or linking changes.

## May Touch

- Public CSS, public includes, affected public routes, small public JavaScript behavior, and related tests or smoke docs when needed.

## Inspect First

- Root `AGENTS.md`, root `SKILL.md`, and `references/project-profile.md`.
- Existing CSS and include/template patterns for the target component.
- Desktop and mobile layout assumptions, especially hero sections, nav, cards, carousels/rails, footer links, and CTAs.
- Current links between destinations, experiences, itineraries, reviews, guides, and contact/WhatsApp actions.

## Safety Rules

- Keep the experience polished, readable, responsive, travel-brand appropriate, and mobile-first.
- Protect hero readability, CTA hierarchy, card density, carousel/rail behavior, footer utility links, and mobile navigation.
- Prefer local, reversible CSS/template changes over broad visual rewrites.
- Keep text within containers and avoid overlapping UI.
- Preserve internal links and CTA destinations unless intentionally improving them.

## Validation Commands

```powershell
composer validate
composer test
git diff --check
php -l path\to\changed-file.php
php cli\smoke_post_gates.php --only=dynamic
php cli\lint_image_alt.php
```

Use local browser or local route checks when visual behavior changes. Do not use live public domains.

## Reporting Format

- UI areas reviewed or changed.
- Links and CTAs checked.
- Mobile/responsive risks.
- Validation and local visual checks run.
- Improvements left for later.

## Must Not Do

- Do not create a new design system when existing CSS/includes solve the task.
- Do not remove navigation, footer, review, guide, itinerary, destination, experience, or contact paths without a clear replacement.
- Do not test production domains.
