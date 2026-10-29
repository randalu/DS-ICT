#!/usr/bin/env bash
# Run all content-humanization migrations in order
# Idempotent — safe to re-run.
set -euo pipefail

MYSQL="/c/laragon/bin/mysql/mysql-8.4.3-winx64/bin/mysql.exe"
DB="ict"
DIR="$(cd "$(dirname "$0")" && pwd)"

echo "=== Content Rewrite Migrations ==="
echo ""

for f in "$DIR"/2026-07-09-content-huma-*.sql; do
    name="$(basename "$f")"
    echo "→ $name ..."
    if "$MYSQL" -u root "$DB" < "$f" 2>&1; then
        echo "  ✓ $name applied"
    else
        echo "  ✗ FAILED: $name"
        exit 1
    fi
    echo ""
done

echo "=== All content rewrites applied successfully ==="
