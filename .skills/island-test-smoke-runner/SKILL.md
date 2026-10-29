---
name: island-test-smoke-runner
description: Use for running or updating Island Culture Travels validation workflows, Composer/PHPUnit tests, PHP syntax checks, dynamic route/CSRF smoke checks, image alt linting, and local-only validation reports.
---

# Island Test Smoke Runner

## Purpose

Run the right local validation for Island changes and summarize results clearly without touching live public domains.

## Scope

- `composer.json`, `composer.lock`, `tests/**`, `cli/smoke_post_gates.php`, `cli/lint_image_alt.php`, `cli/generate_image_variants.php`, changed PHP files, and validation documentation.

## May Touch

- Tests, CLI validation scripts, validation documentation, and Composer metadata only when the task explicitly involves validation workflow changes.

## Inspect First

- Root `AGENTS.md`, root `SKILL.md`, `references/project-profile.md`.
- `git status --short --branch`
- `composer.json` scripts when test tooling changes.
- The changed file list and blast radius.

## Safety Rules

- Use Composer-managed PHPUnit: `composer test`.
- Keep `composer.lock` tracked and `vendor/` ignored.
- Prefer changed-file `php -l` for narrow PHP edits; use broad syntax sweep for shared helpers, auth, config, routes, or broad PHP changes.
- Use local route/CLI smoke checks, not live public domains.
- Report warnings separately from failures.

## Validation Commands

Baseline:

```powershell
composer validate
composer test
git diff --check
```

Changed PHP:

```powershell
php -l path\to\changed-file.php
```

Broad syntax:

```powershell
$errors = @()
Get-ChildItem -Recurse -Filter *.php -File | Where-Object { $_.FullName -notlike '*\vendor\*' } | ForEach-Object {
    $result = php -l $_.FullName 2>&1
    if ($LASTEXITCODE -ne 0) { $errors += $result }
}
if ($errors.Count) { $errors; exit 1 }
```

Smoke/content:

```powershell
php cli\smoke_post_gates.php --only=dynamic
php cli\lint_image_alt.php
```

Image variants when image handling changes:

```powershell
php cli\generate_image_variants.php --dry-run --widths=400,800
```

## Reporting Format

- Commands run.
- Pass/fail summary and important output.
- Skipped commands with reasons.
- Git status after validation.
- Recommended next validation if risk remains.

## Must Not Do

- Do not treat a narrow check as proof for a broad shared-helper change.
- Do not run live-domain tests unless explicitly asked.
- Do not hide warnings that may matter, such as line-ending or environment warnings.
