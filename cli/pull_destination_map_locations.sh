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
[[ "$(git config --get remote.origin.url)" == git@github-islandculture:randalu/IslandCulture.git ]] || exit 1
current=$(git rev-parse HEAD)
[[ "$current" == "$base" || "$current" == "$target" ]] || { echo 'Unexpected server revision; stop for review.' >&2; exit 1; }
git fetch origin main
[[ "$(git rev-parse FETCH_HEAD)" == "$target" ]] || { echo 'GitHub main differs from the reviewed target.' >&2; exit 1; }
git merge-base --is-ancestor "$base" "$target"
# This data-only release cannot introduce schema/configuration/dependency changes.
mapfile -d '' changed < <(git diff --name-only -z "$base" "$target")
[[ ${#changed[@]} -gt 0 ]] || exit 1
for path in "${changed[@]}"; do
  case "$path" in
    cli/seed_destination_map_locations.php|cli/pull_destination_map_locations.sh|includes/route_map_destination_seed.php|assets/maps/destination-locations.json|assets/maps/README.md|tests/RouteMapDestinationSeedTest.php|tests/fixtures/route_map_seed_rehearsal.php|references/destination-map-seed-2026-10-25.md) ;;
    *) echo "Unexpected release path: $path" >&2; exit 1;;
  esac
done
git diff --quiet HEAD -- "${changed[@]}" || { echo 'Release files have local edits.' >&2; exit 1; }
git diff --cached --quiet -- "${changed[@]}"
mapfile -d '' collisions < <(git ls-files --others --exclude-standard -z -- "${changed[@]}")
[[ ${#collisions[@]} -eq 0 ]] || { echo 'Untracked release-file collision.' >&2; exit 1; }
if [[ -x /usr/local/lsws/lsphp83/bin/php ]]; then php_bin=/usr/local/lsws/lsphp83/bin/php; else php_bin=$(command -v php); fi
"$php_bin" -r 'exit(PHP_VERSION_ID >= 80300 && extension_loaded("pdo_mysql") ? 0 : 1);'
packet=$(mktemp -d "${HOME}/island-destination-maps-${target:0:7}-XXXXXXXX")
chmod 700 "$packet"
trap 'echo "DESTINATION_MAP_RELEASE_STOPPED. Inspect the last error and private packet: $packet" >&2' ERR
"$php_bin" -d "session.save_path=$packet" "$site/cli/activate_route_maps.php" --check
bash "$site/cli/pull_release.sh" "$target" "$base"
[[ "$(git rev-parse HEAD)" == "$target" ]] || exit 1
git diff --quiet "$target" -- "${changed[@]}"
git diff --cached --quiet -- "${changed[@]}"
for path in "${changed[@]}"; do
  permissions=$(stat -c '%a' "$path")
  (( (8#$permissions & 4) != 0 )) || { echo "Source is not web-readable: $path" >&2; exit 1; }
done
"$php_bin" -d "session.save_path=$packet" "$site/cli/seed_destination_map_locations.php" --dry-run | tee "$packet/plan.json"
plan_sha=$("$php_bin" -r '$p=json_decode(file_get_contents($argv[1]),true,512,JSON_THROW_ON_ERROR); if (($p["result"]??"")!=="DESTINATION_MAP_SEED_PLAN")exit(1); echo $p["plan_sha256"];' "$packet/plan.json")
"$php_bin" -d "session.save_path=$packet" "$site/cli/seed_destination_map_locations.php" --apply --backup-dir="$packet" --expected-plan="$plan_sha" | tee "$packet/result.json"
printf 'DESTINATION_MAP_RELEASE_OK %s\nPrivate import packet: %s\n' "$target" "$packet"
echo 'Check the skipped list, then select the created locations in Admin > Map Manager. Existing routes were preserved.'
