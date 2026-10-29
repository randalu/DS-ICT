# Deployment Checklist — AI Search + CSP Enforcing + CI Pipeline

Run through these steps in order after merging the sprint PR.

---

## Pre-flight

- [ ] **Backup the database** (snapshot or `mysqldump`) — the `search_index` table will be cleared
- [ ] **Set `ai_api_key` in Admin → AI Settings** (OpenRouter key, starts with `sk-or-v1-`)
- [ ] **Set embedding model** to `openai/text-embedding-3-small` (default, recommended)
- [ ] **Commit the migration** `database/migrations/2026-08-28-remove-legacy-gemini-key.sql` if not already applied

---

## Database migration

```bash
php cli/migrate.php
```

Expected output:
```
Running migrations...
Applying: 2026-09-01-ai-search-schema.sql...
  -> Success
Applying: 2026-08-28-remove-legacy-gemini-key.sql...
  -> Success
Successfully applied 2 migration(s).
```

---

## Re-seed the AI search index

**Critical:** The old index used Gemini 768-dim embeddings. The new code uses OpenAI 1536-dim embeddings via OpenRouter. The two are incompatible — the index must be rebuilt.

```bash
# Preview what will be indexed
php cli/reseed_ai_search.php --dry-run

# Run the actual re-seed (clears old index, rebuilds with OpenRouter embeddings)
php cli/reseed_ai_search.php
```

Expected output:
```
=== AI Search Re-seed (OpenRouter) ===
API key: sk-or-v1-... (masked)
Embedding model: openai/text-embedding-3-small

Cleared search_index table. (N rows removed)

Found N content items to index.
Embedding model: openai/text-embedding-3-small

Indexing...
  [OK] destination: Sigiriya ...
  [OK] itinerary: 7-Day Classic Sri Lanka ...
...
=== Done ===
Indexed : N
Failed  : 0
Avg dim : 1536 (expected 1536)
```

If any items fail, check the OpenRouter key has credits / the embedding model is still available.

---

## Verify search works

1. Visit `/ai-search` in a browser
2. Enter a query: "elephants and ancient ruins"
3. Confirm results appear with relevance scores

---

## Monitor CSP violations (first 24 hours)

```bash
# Check for violations
php cli/csp_monitor.php

# Last 10 raw entries
php cli/csp_monitor.php --tail=10

# 48-hour window
php cli/csp_monitor.php --hours=48

# Machine-readable output for log pipelines
php cli/csp_monitor.php --json
```

Expected: `No violations. ✓` — if violations appear, check `data/csp.log`.

**Common violations and fixes:**

| Blocked | Fix in `.htaccess` |
|---|---|
| `https://www.google-analytics.com` | Already in `connect-src` |
| `https://cdn.jsdelivr.net` | Already in `script-src`/`style-src` |
| Inline style blocks | These are `style-src 'unsafe-inline'` — expected |
| `blob:` data URIs | Already in `img-src` |

If something unexpected trips the policy, add it to the relevant CSP directive. Example:
```
Header set Content-Security-Policy "... img-src 'self' data: blob: https: https://new-cdn.example.com"
```

---

## CI pipeline

The GitHub Actions pipeline runs on every push/PR to `main`. It checks:

1. **PHP syntax** (8.1, 8.2, 8.3)
2. **PHPUnit** (65 tests, all must pass)
3. **POST gate smoke runner** (CSRF/reject coverage)
4. **Security sanity** — CSP enforcing, secret-file blocks, migration idempotency

To run locally:
```bash
# Full suite
composer test

# Just lint
php -l $(find . -name '*.php' -not -path './vendor/*' -not -path './.git/*')

# Just PHPUnit
vendor/bin/phpunit --configuration=phpunit.xml

# Just smoke
php cli/smoke_post_gates.php
```

---

## Rollback plan

If something goes wrong after deploy:

1. **CSP too strict?** Change `Content-Security-Policy` back to `Content-Security-Policy-Report-Only` in `.htaccess`. Violations will log but not block.

2. **Search broken?** Revert `includes/ai_search.php` to the Gemini version and re-run:
   ```bash
   php cli/seed_ai_content.php  # old seeder
   ```

3. **Database migration?** Migrations are idempotent — running `migrate.php` again is safe.
   To remove the legacy key cleanup migration if needed:
   ```sql
   INSERT INTO schema_migrations (version) VALUES ('2026-08-28-remove-legacy-gemini-key.sql');
   ```

---

## After deployment

- [ ] Confirm no CSP violations in `data/csp.log` after 24 hours
- [ ] Confirm AI search returns relevant results for 3+ queries
- [ ] Confirm PHPUnit passes in CI
- [ ] Mark deployment complete
