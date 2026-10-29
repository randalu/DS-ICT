#!/usr/bin/env bash
set -Eeuo pipefail
umask 077
target=${1:?Provide the reviewed target commit SHA}
base=${2:?Provide the reviewed live base commit SHA}
mode=${3:-}
[[ "$target" =~ ^[0-9a-f]{40}$ && "$base" =~ ^[0-9a-f]{40}$ ]] || exit 1
[[ -z "$mode" || "$mode" == --check ]] || exit 1
[[ "$(id -un)" == serveradmin ]] || { echo 'Run as serveradmin.' >&2; exit 1; }
site=/home/srilankaprivatetravels.com/public_html
cd "$site"
[[ "$(git rev-parse --show-toplevel)" == "$site" && "$(git branch --show-current)" == main ]] || exit 1
[[ "$(git config --get remote.origin.url)" == git@github-islandculture:randalu/IslandCulture.git ]] || exit 1
current=$(git rev-parse HEAD)
if [[ "$current" == "$target" ]]; then echo "CODE_ALREADY_AT_TARGET $target"; exit 0; fi
[[ "$current" == "$base" ]] || { echo 'Live HEAD changed; stop for review.' >&2; exit 1; }
git fetch origin main
[[ "$(git rev-parse FETCH_HEAD)" == "$target" ]] || { echo 'GitHub main differs from the reviewed target.' >&2; exit 1; }
git merge-base --is-ancestor "$base" "$target"
mapfile -d '' changed < <(git diff --name-only -z "$base" "$target")
[[ ${#changed[@]} -gt 0 ]] || exit 1
for path in "${changed[@]}"; do
  case "$path" in .env|.env.*|assets/uploads/*|config/*) echo "Protected path requires separate release review: $path" >&2; exit 1;; esac
done
git diff --quiet HEAD -- "${changed[@]}" || { echo 'Release files have local edits.' >&2; exit 1; }
git diff --cached --quiet -- "${changed[@]}" || exit 1
mapfile -d '' collisions < <(git ls-files --others --exclude-standard -z -- "${changed[@]}")
[[ ${#collisions[@]} -eq 0 ]] || { echo 'Untracked release collision; stop for review.' >&2; exit 1; }
if [[ -x /usr/local/lsws/lsphp83/bin/php ]]; then php_bin=/usr/local/lsws/lsphp83/bin/php; else php_bin=$(command -v php); fi
"$php_bin" -r 'exit(PHP_VERSION_ID >= 80300 ? 0 : 1);'
packet="${HOME}/island-release-${target:0:7}-$(date -u +%Y%m%dT%H%M%SZ)"
mkdir -m 700 "$packet"
mkdir -m 700 "$packet/lint"
for path in "${changed[@]}"; do
  if [[ "$path" == *.php ]]; then
    mkdir -p "$packet/lint/$(dirname "$path")"
    git show "$target:$path" > "$packet/lint/$path"
    "$php_bin" -l "$packet/lint/$path" >/dev/null
  fi
done
if [[ "$mode" == --check ]]; then echo "PREFLIGHT_OK $target"; exit 0; fi
mapfile -d '' previous < <(git diff --name-only --diff-filter=MD -z "$base" "$target")
if [[ ${#previous[@]} -gt 0 ]]; then git archive --format=tar --output="$packet/source-before.tar" "$base" -- "${previous[@]}"; fi
# Backups stay private; only Git's source checkout uses public-source permissions.
(umask 022; git -c merge.autoStash=false -c rebase.autoStash=false pull --ff-only --no-rebase origin "$target")
[[ "$(git rev-parse HEAD)" == "$target" ]] || exit 1
git diff --quiet "$target" -- "${changed[@]}"
for path in "${changed[@]}"; do
  if [[ -f "$path" ]]; then
    permissions=$(stat -c '%a' "$path")
    if (( (8#$permissions & 4) == 0 )); then echo "Release source is not web-readable: $path" >&2; exit 1; fi
  fi
done
printf 'CODE_PULL_OK %s\nPrivate source preimage: %s\nDatabase/content activation remains separate.\n' "$target" "$packet"
