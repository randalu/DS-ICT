# Island Culture Travels Agent Instructions

Ask concise questions and confirm the next step when the task has real choices. Provide a recommended option first, state the tradeoff, and mention what can be improved now or later.

## Project Purpose

Island Culture Travels is a custom PHP 8.3 website for a Sri Lanka private travel and tour business. The site supports private chauffeur-guided travel, custom tours, itineraries, airport transfers, destinations, experiences, reviews, customer accounts, business partners, and travel inquiries.

The public experience is built around private vehicle travel, flexible Sri Lanka trip planning, WhatsApp/contact inquiries, and conversion from destination, experience, itinerary, review, guide, and CTA pages.

## Project Workflow

- Start by checking `git status --short --branch` and relevant project context, especially `references/project-profile.md`.
- Composer manages PHPUnit. Use `composer test` as the standard PHPUnit command.
- Keep `composer.lock` tracked. Keep `vendor/` ignored and local-only.
- Prefer small, reversible changes that match the existing PHP helpers and page structure.
- Do not read `.env` unless the task explicitly requires it. Use `.env.example` for non-secret config shape.
- Treat `assets/uploads/*`, `.claude/`, logs, lock files, and local environment files as protected/local artifacts unless explicitly asked otherwise.
- Do not make destructive database or schema changes without inspecting current schema, migrations, runtime guards, and affected queries first.
- Before editing, state the files or areas being changed.
- After editing, run validation matched to the blast radius and report what changed, what was checked, validation results, blockers, recommended next step, and what can be improved.

## Code Patterns

- This is a custom PHP 8.3 site served by Laragon/Apache with `.htaccess` rewrites.
- Use `includes/functions.php` helpers such as `db()`, `fetch_one()`, `fetch_all()`, `execute_query()`, `e()`, `base_url()`, `csrf_field()`, `require_csrf()`, `guard_post_size()`, `db_table_exists()`, and `db_column_exists()`.
- Public pages that need schema helpers should load `includes/schema.php` directly or through `config/bootstrap.php`.
- Admin pages should require `includes/auth.php` and call `admin_require_auth()`.
- Customer account pages should use `includes/customer_auth.php`.
- Admin write handlers should reject unsafe requests, require CSRF, use prepared statements, validate JSON fields before saving, and preserve existing `old_input_set()` behavior where forms already use it.

## Public Site Rules

- Treat public pages as SEO-first, mobile-first, fast-loading, and conversion-focused.
- Preserve public URLs, route names, slugs, canonical patterns, and existing redirects unless the task explicitly requires a migration plan.
- Maintain internal linking between destinations, experiences, itineraries, reviews, seasonal/guide pages, and contact/WhatsApp CTAs.
- Preserve or update meta titles, meta descriptions, canonical URLs, schema output, Open Graph, Twitter metadata, sitemaps, and robots behavior where those patterns already exist.
- Keep image alt text meaningful. Run the image-alt lint when content, media, gallery, destination photo, or image-rendering changes could affect alt quality.
- Do not test live public domains unless explicitly asked. Prefer local route execution, CLI smoke checks, in-app browser checks against local URLs, or `curl.exe` against local Laragon routes.

## UI And UX Rules

- Keep the public experience polished, readable, responsive, and appropriate for a Sri Lanka private travel brand.
- Protect hero sections, CTA hierarchy, card layouts, carousel/rail behavior, footer links, and mobile navigation.
- Inspect existing CSS, includes, templates, and nearby page structure before changing UI.
- Avoid layout-breaking CSS, broad visual rewrites, one-off styling systems, or unnecessary changes to shared includes.
- Match existing design language and component patterns unless the task explicitly asks for a redesign.

## Admin And Backend Rules

- Preserve auth, sessions, redirects, CSRF checks, POST gates, and permission boundaries.
- Before editing save handlers, inspect the matching form, handler, database usage, redirects, validation, old-input behavior, and permissions together.
- Do not assume optional database tables or columns exist. Use schema inspection and compatibility helpers such as `db_table_exists()` and `db_column_exists()` where older installs may differ.
- Prefer schema-safe fallbacks and guards on public routes, sitemap routes, admin pages, and dynamic content pages.
- Use prepared statements through existing helpers and keep JSON validation explicit before saving JSON fields.
- Preserve customer account auth behavior separately from admin auth behavior.

## Schema And Compatibility

- Add migrations under `database/migrations/` using the existing dated naming style.
- Keep runtime guards such as `db_table_exists()` and `db_column_exists()` when code must support older installs.
- Be careful with `install.php`; it is a one-time installer and can self-lock/delete after successful install.
- Do not assume local database tables or columns exist just because current code references them. Confirm with guards or schema inspection when touching older routes.

## Validation

Baseline:

```powershell
git status --short --branch
composer validate
composer test
git diff --check
```

Changed PHP files:

```powershell
php -l path\to\changed-file.php
```

Broad PHP syntax sweep when shared helpers, auth, config, or many PHP files changed:

```powershell
$errors = @()
Get-ChildItem -Recurse -Filter *.php -File | ForEach-Object {
    $result = php -l $_.FullName 2>&1
    if ($LASTEXITCODE -ne 0) { $errors += $result }
}
if ($errors.Count) { $errors; exit 1 }
```

Route, CSRF, and dynamic smoke coverage:

```powershell
php cli\smoke_post_gates.php --only=dynamic
```

Content and image quality:

```powershell
php cli\lint_image_alt.php
```

Image handling changes:

```powershell
php cli\generate_image_variants.php --dry-run --widths=400,800
```

Web smoke checks:

- Verify the local URL first. Laragon may use a virtual host, `APP_URL`, or `http://localhost/island`.
- Use the in-app browser or `curl.exe` for changed public/admin routes when HTTPS is healthy.
- If local HTTPS is broken, explain that blocker and use CLI route execution as a fallback.
