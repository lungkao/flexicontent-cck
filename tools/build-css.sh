#!/usr/bin/env bash
# ─────────────────────────────────────────────────────────────────────
# build-css.sh — Regenerate every *.min.css in the project from its
# matching source .css (no source map, no deps).
#
# Joomla production loaders pick *.min.css when JDEBUG is off. Editing
# only the *.css source is a no-op for end users until *.min.css is
# regenerated. Run this script after any CSS source edit.
#
# Skips: *.min.css (already minified), *_rtl.css (handled by separate
# RTL pipeline if present — never minified from non-RTL source).
#
# Usage:
#   tools/build-css.sh               # build everything
#   tools/build-css.sh <file.css>... # build only specified sources
# ─────────────────────────────────────────────────────────────────────

set -euo pipefail

ROOT="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT"

minify_one() {
	local src="$1"
	# Skip already-minified and RTL variants
	case "$src" in
		*.min.css|*_rtl.css|*_rtl.min.css) return 0 ;;
	esac
	# Output path: foo.css -> foo.min.css
	local out="${src%.css}.min.css"
	# Skip if no minified pair exists (avoid creating new .min files
	# for sources that are not part of the deployment pipeline)
	[ -f "$out" ] || return 0

	php -r '
		$css = file_get_contents($argv[1]);
		// 1. Strip /* ... */ comments (greedy, multi-line)
		$css = preg_replace("#/\*.*?\*/#s", "", $css);
		// 2. Collapse all whitespace runs to a single space
		$css = preg_replace("/\s+/", " ", $css);
		// 3. Tighten around block/rule punctuation
		$css = preg_replace("/\s*([{};,:>])\s*/", "$1", $css);
		// 4. Drop trailing ; before closing brace
		$css = str_replace(";}", "}", $css);
		file_put_contents($argv[2], trim($css));
	' "$src" "$out"

	# Friendly stat output
	local in_size out_size pct
	in_size=$(wc -c < "$src" | tr -d ' ')
	out_size=$(wc -c < "$out" | tr -d ' ')
	pct=$(( in_size > 0 ? (100 * out_size / in_size) : 100 ))
	printf "  %-60s %6d -> %6d bytes (%d%%)\n" "$out" "$in_size" "$out_size" "$pct"
}

if [ "$#" -gt 0 ]; then
	# Build only files passed as arguments
	echo "Minifying ${#} file(s)..."
	for f in "$@"; do
		minify_one "$f"
	done
else
	# Build every .css under admin/assets/ and site/assets/
	echo "Minifying all admin + site CSS sources..."
	while IFS= read -r f; do
		minify_one "$f"
	done < <(find admin/assets site/assets -type f -name '*.css' \
		! -name '*.min.css' ! -name '*_rtl.css' 2>/dev/null | sort)
fi

# Bump cache-busting hash so browsers fetch the regenerated files.
# FLEXI_VHASH = md5(filemtime(defineconstants.php) + filectime + version).
# Touch the constants file to invalidate the hash without changing code.
if [ -f admin/defineconstants.php ]; then
	touch admin/defineconstants.php
	echo "Bumped FLEXI_VHASH via touch admin/defineconstants.php"
fi

echo "Done."
