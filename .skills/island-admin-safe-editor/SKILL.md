---
name: island-admin-safe-editor
description: Use for Island Culture Travels admin CRUD pages, content management features, form/save handler changes, media library work, settings pages, admin redirects, database writes, schema compatibility, and old-input/validation preservation.
---

# Island Admin Safe Editor

## Purpose

Change admin and content-management behavior safely by tracing forms, handlers, auth, CSRF, validation, database writes, and redirects together.

## Scope

- `admin/*.php`, `admin/assets/*`, admin partials, media picker/library files, settings pages, content CRUD pages, and save/delete/status handlers.
- Related helpers in `includes/functions.php`, `includes/auth.php`, media helpers, migrations, and schema files.

## May Touch

- Admin forms, admin save/delete/status handlers, admin partials/assets, related helpers, migrations, tests, and smoke checks needed for the admin change.

## Inspect First

- Root `AGENTS.md`, root `SKILL.md`, and `references/project-profile.md`.
- Matching form and save/delete/status handler.
- Required auth/session calls, CSRF handling, method gates, redirects, and flash/old-input behavior.
- Database tables, optional columns, migrations, guards, and existing helper usage.
- Any public route that reads the admin-managed data.

## Safety Rules

- Preserve `admin_require_auth()`, sessions, POST-only gates, `require_csrf()`, prepared statements, redirects, validation, permissions, and `old_input_set()` behavior.
- Validate JSON fields before saving.
- Use `db_table_exists()` and `db_column_exists()` when older installs may not have a table or column.
- Keep admin changes compatible with public-route fallbacks.
- Do not read `.env` unless explicitly required.
- Avoid destructive DB/schema actions without explicit inspection and approval.

## Validation Commands

```powershell
composer validate
composer test
git diff --check
php -l path\to\changed-admin-file.php
php cli\smoke_post_gates.php --only=dynamic
```

Run the broad PHP syntax sweep when changing shared helpers, auth, config, or many admin files. Run `php cli\lint_image_alt.php` if admin content/media changes can affect public image alt text.

## Reporting Format

- Admin form/handler pair inspected.
- Auth, CSRF, POST gate, redirect, validation, and DB behavior preserved or changed.
- Public read-side impact.
- Validation results.
- Remaining schema or data assumptions.

## Must Not Do

- Do not bypass auth, CSRF, permissions, or POST gates.
- Do not assume DB fields exist without guards or schema evidence.
- Do not silently drop existing field preservation or old-input behavior.
- Do not run destructive schema or data changes without explicit approval.
