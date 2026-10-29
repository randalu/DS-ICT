# Island Culture Travels

Custom PHP 8.3 site for Island Culture Travels, a Sri Lanka private travel and tour business covering destinations, experiences, itineraries, reviews, contact inquiries, customer accounts, business partners, and admin-managed content.

## Local Development

- Run `composer install` after cloning or pulling if `vendor/` is missing.
- `composer.lock` is tracked and should not be removed.
- `vendor/` is ignored and should not be committed.
- PHPUnit is Composer-managed. Use `composer test` as the standard PHPUnit command; do not re-add a tracked `phpunit.phar`.
- Use `composer validate` to check the Composer manifest.
- Treat public URL, route, and slug changes carefully because public pages use canonical URLs, internal links, sitemaps, and inquiry CTAs.
- Do not make destructive database or schema changes without inspecting current schema, migrations, runtime guards, and planning backups or rollback.
- Do not test live public domains unless that is explicitly requested; prefer local Laragon routes and CLI smoke checks.

## Project Areas

- Public site: destinations, experiences, itineraries, reviews, contact/WhatsApp CTAs, SEO metadata, schema, and sitemap/robots behavior where present.
- Admin: content management, auth/session handling, forms, save handlers, POST gates, CSRF, media, redirects, and schema-safe fallbacks.
- Quality tools: Composer-managed PHPUnit, dynamic POST/route smoke checks, image alt linting, and PHP syntax checks.

## Validation

```powershell
composer validate
composer test
git diff --check
php cli\smoke_post_gates.php --only=dynamic
php cli\lint_image_alt.php
```

Run `php -l path\to\changed-file.php` for changed PHP files, or a broader PHP syntax sweep when shared helpers, auth, config, routes, or many PHP files change.
