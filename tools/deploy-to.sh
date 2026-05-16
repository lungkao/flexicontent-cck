#!/usr/bin/env bash
# ─────────────────────────────────────────────────────────────────────
# deploy-to.sh — Sync this FLEXIcontent repo to a Joomla install.
#
# Joomla expects the component flattened: admin/* → administrator/
# components/com_flexicontent/, site/* → components/com_flexicontent/.
# The repo keeps them in admin/ and site/ subdirs for clarity.
# This script bridges the two layouts via rsync.
#
# Usage:
#   tools/deploy-to.sh <joomla-root>
#   tools/deploy-to.sh ~/Sites/joomla54
#
# Behaviour:
#   - Mirrors admin/* into <root>/administrator/components/com_flexicontent/
#   - Mirrors site/*  into <root>/components/com_flexicontent/
#   - Skips dotfiles, tests, docs, tools (dev-only)
#   - Bumps FLEXI_VHASH via touch defineconstants.php so browsers
#     pick up the new build immediately
#   - Uses --update (only newer files) by default, --delete is off
#     so the script never removes Joomla-managed config files
#
# Flags:
#   --full       : rsync without --update (force overwrite all)
#   --delete     : pass --delete to rsync (PRUNE files not in repo —
#                  dangerous; review first)
#   --dry-run    : echo what would change, do nothing
# ─────────────────────────────────────────────────────────────────────

set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

usage() {
	echo "Usage: tools/deploy-to.sh <joomla-root> [--full] [--delete] [--dry-run]" >&2
	exit 1
}

[ "$#" -ge 1 ] || usage
TARGET="${1%/}"; shift || true
[ -d "$TARGET/administrator" ] || { echo "Not a Joomla install: $TARGET (missing administrator/)" >&2; exit 1; }

RSYNC_FLAGS=(-a --update)
DRY=""
for arg in "$@"; do
	case "$arg" in
		--full)    RSYNC_FLAGS=(-a) ;;
		--delete)  RSYNC_FLAGS+=(--delete) ;;
		--dry-run) DRY="--dry-run"; RSYNC_FLAGS+=(--itemize-changes) ;;
		*) echo "Unknown flag: $arg" >&2; usage ;;
	esac
done

EXCLUDES=(
	--exclude='.DS_Store'
	--exclude='*.bak'
	--exclude='__pycache__'
)

ADMIN_DST="$TARGET/administrator/components/com_flexicontent"
SITE_DST="$TARGET/components/com_flexicontent"

[ -d "$ADMIN_DST" ] || { echo "Missing $ADMIN_DST — component not installed?" >&2; exit 1; }
[ -d "$SITE_DST" ]  || { echo "Missing $SITE_DST — component not installed?" >&2; exit 1; }

echo "Deploying $ROOT → $TARGET"
echo "  admin/* → $ADMIN_DST/"
rsync "${RSYNC_FLAGS[@]}" "${EXCLUDES[@]}" $DRY admin/ "$ADMIN_DST/"

echo "  site/*  → $SITE_DST/"
rsync "${RSYNC_FLAGS[@]}" "${EXCLUDES[@]}" $DRY site/ "$SITE_DST/"

# Bump FLEXI_VHASH on the live install so the browser refetches CSS/JS.
# (FLEXI_VHASH = md5(filemtime(defineconstants.php) + filectime + version))
if [ -z "$DRY" ] && [ -f "$ADMIN_DST/defineconstants.php" ]; then
	touch "$ADMIN_DST/defineconstants.php"
	echo "Bumped FLEXI_VHASH on live install."
fi

echo "Done."
echo ""
echo "If the browser still shows stale CSS:"
echo "  1. Joomla admin → System → Maintenance → Clear Cache"
echo "  2. Browser DevTools → Network → tick 'Disable cache' → Cmd+Shift+R"
