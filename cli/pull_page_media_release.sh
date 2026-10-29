#!/usr/bin/env bash
# Never enable xtrace: the configuration relay inherits server credentials in memory.
set -Eeuo pipefail
set +x
umask 077
target=${1:?Provide the reviewed target SHA}
base=${2:?Provide the reviewed base SHA}
mode=${3:---check}
[[ "$target" =~ ^[0-9a-f]{40}$ && "$base" =~ ^[0-9a-f]{40}$ ]] || exit 1
[[ "$mode" == --check || "$mode" == --apply ]] || exit 1
(( BASH_VERSINFO[0] >= 4 )) || { echo 'Bash 4 or newer is required.' >&2; exit 1; }
for required in id git mktemp chmod sort cmp tar mkdir stat cat; do
  command -v "$required" >/dev/null || { echo "Missing release prerequisite: $required" >&2; exit 1; }
done
[[ "$(id -un)" == serveradmin ]] || { echo 'Run as serveradmin.' >&2; exit 1; }
site=/home/srilankaprivatetravels.com/public_html
cd "$site"
[[ "$(git rev-parse --show-toplevel)" == "$site" && "$(git branch --show-current)" == main ]] || exit 1
[[ "$(git config --get remote.origin.url)" == git@github-islandculture:randalu/IslandCulture.git ]] || exit 1
current=$(git rev-parse HEAD)
[[ "$current" == "$base" || "$current" == "$target" ]] || { echo 'Live HEAD differs from the reviewed base; stop for review.' >&2; exit 1; }
git fetch origin main
[[ "$(git rev-parse FETCH_HEAD)" == "$target" ]] || { echo 'GitHub main differs from the reviewed target.' >&2; exit 1; }
git merge-base --is-ancestor "$base" "$target"
packet=$(mktemp -d "${HOME}/island-page-media-${target:0:7}-XXXXXXXX")
chmod 700 "$packet"
git show "$target:references/page-media-release-files.txt" | LC_ALL=C sort > "$packet/allowlist.txt"
git diff --name-only "$base" "$target" | LC_ALL=C sort > "$packet/changed.txt"
cmp -s "$packet/allowlist.txt" "$packet/changed.txt" || { echo 'Changed paths differ from the release manifest.' >&2; exit 1; }
mapfile -t changed < "$packet/changed.txt"
(( ${#changed[@]} > 0 )) || { echo 'The reviewed release manifest is empty.' >&2; exit 1; }
for path in "${changed[@]}"; do
  [[ "$path" =~ ^[A-Za-z0-9._/-]+$ && "$path" != /* && "/$path/" != */../* && "/$path/" != */./* ]] || { echo 'Unsupported release path.' >&2; exit 1; }
  case "$path" in .env.example) ;; .env|.env.*|assets/uploads/*|config/*|vendor/*|output/*|data/*) echo "Protected release path: $path" >&2; exit 1;; esac
done
git diff --quiet HEAD -- "${changed[@]}" || { echo 'Release files contain live edits.' >&2; exit 1; }
git diff --cached --quiet -- "${changed[@]}" || exit 1
# Include ignored files too: Git can otherwise overwrite an ignored target-path collision.
[[ -z "$(git ls-files --others -- "${changed[@]}")" ]] || { echo 'Untracked release file collision.' >&2; exit 1; }
if [[ -x /usr/local/lsws/lsphp83/bin/php ]]; then php_bin=/usr/local/lsws/lsphp83/bin/php; else php_bin=$(command -v php); fi
"$php_bin" -r 'exit(PHP_VERSION_ID >= 80300 && extension_loaded("pdo_mysql") && extension_loaded("zlib") && function_exists("proc_open") ? 0 : 1);' || { echo 'PHP 8.3+, pdo_mysql, zlib and proc_open are required.' >&2; exit 1; }
mkdir -m 700 "$packet/candidate"
git archive "$target" | tar -x -C "$packet/candidate"
for required in cli/migrate_page_media_release.php cli/admin_ai_status.php cli/lint_image_alt.php config/config.php; do
  [[ -f "$packet/candidate/$required" && ! -L "$packet/candidate/$required" ]] || { echo "Missing candidate prerequisite: $required" >&2; exit 1; }
done
for path in "${changed[@]}"; do
  if [[ "$path" == *.php ]]; then "$php_bin" -l "$packet/candidate/$path" >/dev/null; fi
done
# Load the existing configuration in memory; never copy or display the server key.
cat > "$packet/relay.php" <<'PHP'
<?php
require getenv('ICT_RELEASE_SITE').'/config/config.php';
ini_set('display_errors','0');
$command=[PHP_BINARY,getenv('ICT_RELEASE_CANDIDATE').'/cli/migrate_page_media_release.php','--plan'];
$process=proc_open($command,[0=>STDIN,1=>STDOUT,2=>STDERR],$pipes,getenv('ICT_RELEASE_CANDIDATE'));
exit(is_resource($process)?proc_close($process):1);
PHP
ICT_RELEASE_SITE="$site" ICT_RELEASE_CANDIDATE="$packet/candidate" "$php_bin" -d display_errors=0 "$packet/relay.php"
echo "RELEASE_PREFLIGHT_OK $target"
if [[ "$mode" == --check ]]; then echo "Private preflight packet: $packet"; exit 0; fi
git archive "$current" > "$packet/source-before.tar"
if [[ "$current" != "$target" ]]; then
  (umask 022; git -c merge.autoStash=false -c rebase.autoStash=false pull --ff-only --no-rebase origin "$target")
fi
[[ "$(git rev-parse HEAD)" == "$target" ]] || exit 1
git diff --quiet "$target" -- "${changed[@]}"
for path in "${changed[@]}"; do
  if [[ -f "$path" ]]; then
    permissions=$(stat -c '%a' "$path")
    (( (8#$permissions & 4) != 0 )) || { echo "Source is not web-readable: $path" >&2; exit 1; }
  fi
done
echo "CODE_PULL_OK $target"
mkdir -m 700 "$packet/database"
"$php_bin" cli/migrate_page_media_release.php --apply --backup-dir="$packet/database"
"$php_bin" cli/migrate_page_media_release.php --plan
"$php_bin" cli/admin_ai_status.php
"$php_bin" cli/lint_image_alt.php > "$packet/image-alt-lint.txt" || echo 'Manual photo/description gaps remain; review the lint report.'
echo "SCHEMA_RELEASE_OK $target"
echo "Private source backup, database backup (if migrations were pending), and lint: $packet"
echo 'Existing server key, publication states and photo assignments are retained.'
echo 'Finish authenticated Generate -> Preview -> Apply -> Save checks for Image Manager, SEO Manager and AI Site Audit.'
