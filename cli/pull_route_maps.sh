#!/usr/bin/env bash
set -Eeuo pipefail
umask 077
target=${1:?Provide the reviewed target commit SHA}
base=${2:?Provide the reviewed server base commit SHA}
[[ "$target" =~ ^[0-9a-f]{40}$ && "$base" =~ ^[0-9a-f]{40}$ ]] || exit 1
[[ "$(id -un)" == serveradmin ]] || { echo 'Run as serveradmin.' >&2; exit 1; }
site=/home/srilankaprivatetravels.com/public_html
cd "$site"
[[ "$(git rev-parse --show-toplevel)" == "$site" && "$(git branch --show-current)" == main ]] || exit 1
[[ "$(git config --get remote.origin.url)" == git@github-islandculture:randalu/IslandCulture.git ]] || { echo 'Unexpected Git origin; stop for review.' >&2; exit 1; }
current=$(git rev-parse HEAD)
[[ "$current" == "$base" || "$current" == "$target" ]] || { echo 'Live revision differs from the reviewed base/target; no changes made.' >&2; exit 1; }
git fetch origin main
[[ "$(git rev-parse FETCH_HEAD)" == "$target" ]] || { echo 'GitHub main differs from this reviewed release; stop for review.' >&2; exit 1; }
git merge-base --is-ancestor "$base" "$target"
mapfile -d '' changed < <(git diff --name-only -z "$base" "$target")
[[ ${#changed[@]} -gt 0 ]] || exit 1
for path in "${changed[@]}"; do
  case "$path" in .env|.env.*|assets/uploads/*|config/*|composer.json|composer.lock) echo "Unexpected protected/dependency change: $path" >&2; exit 1;; esac
done
git diff --quiet HEAD -- "${changed[@]}" || { echo 'Release files have local edits; nothing overwritten.' >&2; exit 1; }
git diff --cached --quiet -- "${changed[@]}" || exit 1
mapfile -d '' collisions < <(git ls-files --others --exclude-standard -z -- "${changed[@]}")
[[ ${#collisions[@]} -eq 0 ]] || { echo 'Untracked release-file collision; stop for review.' >&2; exit 1; }
if [[ -x /usr/local/lsws/lsphp83/bin/php ]]; then php_bin=/usr/local/lsws/lsphp83/bin/php; else php_bin=$(command -v php); fi
"$php_bin" -r 'exit(PHP_VERSION_ID >= 80300 && extension_loaded("pdo_mysql") ? 0 : 1);'
packet=$(mktemp -d "${HOME}/island-route-maps-${target:0:7}-XXXXXXXX")
chmod 700 "$packet"
trap 'echo "ROUTE_MAP_RELEASE_STOPPED. Inspect the last error and private packet: $packet" >&2' ERR
mkdir -p "$packet/cli" "$packet/database/migrations"
git show "$target:cli/activate_route_maps.php" > "$packet/cli/activate_route_maps.php"
git show "$target:database/migrations/2026-10-25-route-maps.sql" > "$packet/database/migrations/2026-10-25-route-maps.sql"
"$php_bin" -l "$packet/cli/activate_route_maps.php" >/dev/null
"$php_bin" -d "session.save_path=$packet" "$packet/cli/activate_route_maps.php" --root="$site" --check
# The existing reviewed pull helper archives old source and checks out under umask 022.
bash "$site/cli/pull_release.sh" "$target" "$base"
[[ "$(git rev-parse HEAD)" == "$target" ]] || exit 1
git diff --quiet "$target" -- "${changed[@]}"
git diff --cached --quiet -- "${changed[@]}"
for path in "${changed[@]}"; do
  if [[ -f "$path" ]]; then
    permissions=$(stat -c '%a' "$path")
    (( (8#$permissions & 4) != 0 )) || { echo "Source not web-readable: $path" >&2; exit 1; }
  fi
  parent=$(dirname "$path")
  while [[ "$parent" != . ]]; do
    if ! git cat-file -e "$base:$parent" 2>/dev/null; then
      permissions=$(stat -c '%a' "$parent")
      (( (8#$permissions & 1) != 0 )) || { echo "New source directory not web-accessible: $parent" >&2; exit 1; }
    fi
    parent=$(dirname "$parent")
  done
done
"$php_bin" -d "session.save_path=$packet" "$site/cli/activate_route_maps.php" --apply --backup-dir="$packet"
printf 'ROUTE_MAP_RELEASE_OK %s\nPrivate migration packet: %s\n' "$target" "$packet"
echo 'Code and map schema are activated. Add verified locations and author package routes in Admin > Map Manager.'
