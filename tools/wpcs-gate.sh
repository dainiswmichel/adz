#!/bin/sh

set -eu

ROOT=$(CDPATH= cd -- "$(dirname -- "$0")/.." && pwd)
PHPCS="$ROOT/vendor/bin/phpcs"
PLUGIN="$ROOT/adz-world"
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

if grep -RInE --include='*.php' --include='*.js' --include='*.html' \
	'style=|<style|wp_add_inline_style|\.style[[:space:]]*=' "$PLUGIN" "$THEME"; then
	printf '%s\n' 'Inline or runtime styling is forbidden in production code.' >&2
	exit 1
fi

exec "$PHPCS" \
	--standard="$ROOT/phpcs.xml.dist" \
	--report=full \
	"$PLUGIN" \
	"$THEME"
