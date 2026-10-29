#!/usr/bin/env bash
set -Eeuo pipefail
umask 077
target=${1:?Provide reviewed release SHA}
base=${2:?Provide reviewed live base SHA}
mode=${3:---check}
[[ "$target" =~ ^[0-9a-f]{40}$ && "$base" =~ ^[0-9a-f]{40}$ ]] || exit 1
[[ "$mode" == --check || "$mode" == --apply ]] || exit 1
[[ "$(id -un)" == serveradmin ]] || { echo 'Run as serveradmin.' >&2; exit 1; }
site=/home/srilankaprivatetravels.com/public_html
cd "$site"
[[ "$(git rev-parse --show-toplevel)" == "$site" && "$(git branch --show-current)" == main ]] || exit 1
[[ "$(git config --get remote.origin.url)" == git@github-islandculture:randalu/IslandCulture.git ]] || exit 1
current=$(git rev-parse HEAD)
[[ "$current" == "$base" || "$current" == "$target" ]] || { echo 'Live base changed; stop for review.' >&2; exit 1; }
git fetch origin main
[[ "$(git rev-parse FETCH_HEAD)" == "$target" ]] || { echo 'Remote main changed; stop for review.' >&2; exit 1; }
git merge-base --is-ancestor "$base" "$target"
packet=$(mktemp -d "${HOME}/island-activities-ai-${target:0:7}-XXXXXXXX")
git show "$target:references/activities-shared-openai-files.txt" | LC_ALL=C sort > "$packet/allowlist.txt"
git diff --name-only "$base" "$target" | LC_ALL=C sort > "$packet/changed.txt"
cmp -s "$packet/allowlist.txt" "$packet/changed.txt" || { echo 'Changed paths differ from release manifest.' >&2; exit 1; }
mapfile -t changed < "$packet/changed.txt"
for path in "${changed[@]}"; do
  case "$path" in .env.example) ;; .env|.env.*|assets/uploads/*|config/*|vendor/*|output/*) echo "Protected release path: $path" >&2; exit 1;; esac
done
git diff --quiet HEAD -- "${changed[@]}" || { echo 'Release files have live edits.' >&2; exit 1; }
git diff --cached --quiet -- "${changed[@]}" || exit 1
[[ -z "$(git ls-files --others --exclude-standard -- "${changed[@]}")" ]] || { echo 'Untracked release collision.' >&2; exit 1; }
if [[ -x /usr/local/lsws/lsphp83/bin/php ]]; then php_bin=/usr/local/lsws/lsphp83/bin/php; else php_bin=$(command -v php); fi
"$php_bin" -r 'exit(PHP_VERSION_ID >= 80300 ? 0 : 1);'
mkdir -m 700 "$packet/preflight"
for path in "${changed[@]}"; do
  if [[ "$path" == *.php ]]; then
    mkdir -p "$packet/preflight/$(dirname "$path")"
    git show "$target:$path" > "$packet/preflight/$path"
    "$php_bin" -l "$packet/preflight/$path" >/dev/null
  fi
done
# Preflight runs candidate schema checks against live configuration without copying secrets.
git archive "$target" | tar -x -C "$packet/preflight"
# A fresh child inherits configuration; its BASE_PATH must point to the candidate.
cat > "$packet/relay.php" <<'PHP'
<?php
require getenv('ICT_RELEASE_SITE').'/config/config.php';
$command=[PHP_BINARY,getenv('ICT_RELEASE_CANDIDATE').'/cli/activate_activities_ai.php','--check'];
$process=proc_open($command,[0=>STDIN,1=>STDOUT,2=>STDERR],$pipes,getenv('ICT_RELEASE_CANDIDATE'));
exit(is_resource($process)?proc_close($process):1);
PHP
ICT_RELEASE_SITE="$site" ICT_RELEASE_CANDIDATE="$packet/preflight" "$php_bin" "$packet/relay.php"
echo "RELEASE_PREFLIGHT_OK $target"
if [[ "$mode" == --check ]]; then echo "Private preflight packet: $packet"; exit 0; fi
git archive "$base" > "$packet/source-before.tar"
# Before the pull, the helper exists only in the candidate archive.
ICT_RELEASE_SITE="$site" ICT_RELEASE_CANDIDATE="$packet/preflight" "$php_bin" -r 'require getenv("ICT_RELEASE_SITE")."/config/config.php"; $command=[PHP_BINARY,getenv("ICT_RELEASE_CANDIDATE")."/cli/backup_activities_ai.php",$argv[1]]; $p=proc_open($command,[0=>STDIN,1=>STDOUT,2=>STDERR],$pipes);exit(is_resource($p)?proc_close($p):1);' "$packet/database-before.json"
(umask 022; git -c merge.autoStash=false -c rebase.autoStash=false pull --ff-only --no-rebase origin "$target")
[[ "$(git rev-parse HEAD)" == "$target" ]] || exit 1
git diff --quiet "$target" -- "${changed[@]}"
for path in "${changed[@]}"; do
  if [[ -f "$path" ]]; then
    permissions=$(stat -c '%a' "$path")
    (( (8#$permissions & 4) != 0 )) || { echo "Source is not web-readable: $path" >&2; exit 1; }
  fi
done
echo "CODE_PULL_OK $target"
"$php_bin" cli/activate_activities_ai.php --apply
"$php_bin" cli/seed_activity_pages.php
"$php_bin" cli/backfill_media_alt.php --check > "$packet/alt-backfill-before.json"
"$php_bin" cli/backfill_media_alt.php --apply > "$packet/alt-backfill-result.json"
"$php_bin" cli/activate_activities_ai.php --check
"$php_bin" cli/lint_image_alt.php --only=activity_entry_images > "$packet/activity-alt-lint.txt" || echo 'Review Activity gallery warnings before publishing drafts.'
echo "SCHEMA_AND_DRAFT_ACTIVATION_OK $target"
"$php_bin" cli/admin_ai_status.php
public_url=$("$php_bin" -r 'require "config/config.php";echo rtrim(APP_URL,"/");')
[[ "$public_url" == https://* ]] || { echo 'Expected HTTPS public URL; review HTTP acceptance manually.' >&2; exit 1; }
for route in assets/js/image-alt.js assets/css/activities.css sitemap-activities.xml; do
  status=$(curl --silent --show-error --max-time 25 --output /dev/null --write-out '%{http_code}' "$public_url/$route")
  [[ "$status" == 200 ]] || { echo "HTTP acceptance failed: $route returned $status" >&2; exit 1; }
done
for route in admin/image_alt_api.php admin/seo_manager_api.php admin/site_audit_api.php; do
  status=$(curl --silent --show-error --max-time 25 --output /dev/null --write-out '%{http_code}' "$public_url/$route")
  [[ "$status" == 401 ]] || { echo "Guest API acceptance failed: $route returned $status" >&2; exit 1; }
done
echo 'PUBLIC_HTTP_AND_GUEST_API_CHECKS_OK'
echo "Private backup and reports: $packet"
echo 'Authenticated browser acceptance and server OpenAI key configuration remain separate checks.'
