---
name: island-auth-security-hardener
description: Use for Island Culture Travels admin auth, customer auth, sessions, CSRF, POST gates, safe redirects, form security, SSRF-sensitive media import behavior, account flows, and security hardening reviews or fixes.
---

# Island Auth Security Hardener

## Purpose

Harden authentication, sessions, CSRF, POST gates, redirects, and sensitive request handling without breaking admin or customer workflows.

## Scope

- `includes/auth.php`, `includes/customer_auth.php`, `includes/functions.php`, `config/config.php`, `admin/*`, `account*.php`, form handlers, `subscribe.php`, `track-event.php`, media import/download/delete handlers, and smoke tests.

## May Touch

- Auth/customer-auth helpers, account/admin handlers, security-sensitive form handlers, focused config/session code, tests, and `cli/smoke_post_gates.php` when coverage must change.

## Inspect First

- Root `AGENTS.md`, root `SKILL.md`, and `references/project-profile.md`.
- Entry route, form, handler, auth/session requirements, redirect target, and success/error flow.
- Existing CSRF, `guard_post_size()`, safe redirect, and method-gate patterns.
- Tests and `cli/smoke_post_gates.php` coverage for the touched flow.

## Safety Rules

- Keep admin auth separate from customer auth.
- Preserve session names, cookie security assumptions, CSRF token flow, POST gates, and safe redirects.
- Reject unsafe methods for write handlers.
- Use prepared statements and existing helpers for database work.
- Redact secrets and avoid `.env` unless explicitly required.
- Treat media URL fetching/import behavior as security-sensitive.

## Validation Commands

```powershell
composer validate
composer test
git diff --check
php -l path\to\changed-file.php
php cli\smoke_post_gates.php --only=dynamic
```

Run broad PHP syntax sweep for shared auth/config/helper changes.

## Reporting Format

- Security surface changed.
- Auth/session/CSRF/POST/redirect behavior before and after.
- Tests and smoke gates run.
- Residual risks and recommended next hardening step.

## Must Not Do

- Do not weaken auth, CSRF, POST gates, or redirect safety for convenience.
- Do not expose secrets, OTPs, tokens, raw provider responses, or private account details.
- Do not test live public domains or production accounts.
