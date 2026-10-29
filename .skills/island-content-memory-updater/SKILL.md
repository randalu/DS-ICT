---
name: island-content-memory-updater
description: Use for updating Island Culture Travels project profile, AGENTS, SKILL files, local memory extension notes, workflow docs, stale context corrections, validation snapshots, and future-Codex guidance.
---

# Island Content Memory Updater

## Purpose

Keep Island project memory and workflow documentation aligned with the current repo so future Codex sessions do not rely on stale chat context.

## Scope

- `AGENTS.md`, root `SKILL.md`, `.skills/**/SKILL.md`, `.agents/**`, `references/project-profile.md`, repo workflow docs, and memory extension notes under `C:\Users\Antony\.codex\memories\extensions\ad_hoc\notes\` when explicitly requested.

## May Touch

- Project guidance docs, skill files, agent definition files, profile docs, and memory extension notes when the user explicitly asks for documentation or memory updates.

## Inspect First

- `git status --short --branch`
- Current repo source relevant to the claim being documented.
- Existing docs and skill files to avoid duplicates.
- `composer.json`, `.gitignore`, `composer.lock`, routes, config, or validation output before documenting those facts.
- Memory registry only as historical context; verify drift-prone facts against the repo.

## Safety Rules

- Do not invent project facts, features, routes, domains, redirects, validation results, PR history, or review evidence.
- Keep documentation focused and avoid rewriting unrelated content.
- For memory updates, add a small note under `C:\Users\Antony\.codex\memories\extensions\ad_hoc\notes\`; do not edit the main memory files directly.
- Preserve the distinction between repo-verified facts and unknown external/server state.
- Keep `Island Culture Travels` as the brand name unless current repo content requires otherwise.

## Validation Commands

For documentation-only changes:

```powershell
git diff --check
```

When documentation records validation state, run the commands being recorded:

```powershell
composer validate
composer test
php cli\smoke_post_gates.php --only=dynamic
php cli\lint_image_alt.php
```

Run changed-file or broad PHP syntax only if PHP files changed.

## Reporting Format

- Docs, skill files, or memory notes changed.
- Stale items corrected.
- Evidence used for each current-state claim.
- Facts that could not be verified from the repo.
- Validation results and git status.

## Must Not Do

- Do not present old memory as current fact without verification.
- Do not edit main memory registry files directly.
- Do not use live public domains to prove repo documentation unless explicitly asked.
- Do not create duplicate docs when an existing central file should be updated.
