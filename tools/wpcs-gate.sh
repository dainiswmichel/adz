#!/bin/sh

set -eu

ROOT=$(CDPATH= cd -- "$(dirname -- "$0")/.." && pwd)
PHPCS="$ROOT/vendor/bin/phpcs"
PLUGIN="$ROOT/pre-release-candidates/dwm-local"
THEME="$ROOT/pre-release-candidates/site-theme/adz-world-2026"

if [ ! -x "$PHPCS" ]; then
	printf '%s\n' 'The pinned PHPCS executable is missing. Run composer install.' >&2
	exit 1
fi

if grep -RInE --include='*.php' \
	'phpcs:(ignore|disable)|@codingStandardsIgnore' "$PLUGIN" "$THEME"; then
	printf '%s\n' 'WPCS suppressions are forbidden in production code.' >&2
	exit 1
fi

exec "$PHPCS" \
	--standard="$ROOT/phpcs.xml.dist" \
	--report=full \
	"$PLUGIN" \
	"$THEME"
