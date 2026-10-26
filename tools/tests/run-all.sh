#!/bin/sh
# Every check that runs without a WordPress install.
#
# Usage: sh tools/tests/run-all.sh [candidate-dir]
#
# A clean run means the code is well-formed and internally consistent. It does
# NOT mean the plugin works: nothing here activates it, opens an admin screen,
# or touches a database.
#
# Exit status is 0 only when every check that ran passed. A check that could not
# run is reported as SKIP and does not count as a pass. Output is captured
# rather than piped, because `php ... | tail` reports tail's exit status and
# will hide a fatal as a success. That exact bug was in an earlier version of
# this script, which reported "All checks passed" for a candidate where the
# tests had fatalled.

ROOT=$(cd "$(dirname "$0")/../.." && pwd)
PLUGIN=${1:-"$ROOT/pre-release-candidates/claude-code-web"}

if [ ! -d "$PLUGIN" ]; then
	echo "Not a directory: $PLUGIN"
	exit 2
fi

FAIL=0
SKIP=0
PASS=0

report() {
	# $1 label, $2 exit code, $3 last line of output
	case "$2" in
		0) PASS=$((PASS + 1)); printf '  PASS  %-22s %s\n' "$1" "$3" ;;
		3) SKIP=$((SKIP + 1)); printf '  SKIP  %-22s %s\n' "$1" "$3" ;;
		*) FAIL=$((FAIL + 1)); printf '  FAIL  %-22s (exit %s) %s\n' "$1" "$2" "$3" ;;
	esac
}

echo "Candidate: $PLUGIN"
echo

OUT=$(find "$PLUGIN" -name '*.php' -exec php -l {} \; 2>&1 | grep -v 'No syntax errors')
if [ -z "$OUT" ]; then
	report "php -l" 0 "all files parse"
else
	report "php -l" 1 "$(echo "$OUT" | head -1)"
fi

OUT=$(php "$ROOT/tools/resolve-calls.php" "$PLUGIN" --quiet 2>&1)
report "resolve-calls" "$?" "$(echo "$OUT" | tail -1)"

for t in preferences matching rotation; do
	OUT=$(php "$ROOT/tools/tests/test-$t.php" "$PLUGIN" 2>&1)
	report "test-$t" "$?" "$(echo "$OUT" | tail -1)"
done

if command -v eslint > /dev/null 2>&1; then
	if [ -d "$PLUGIN/assets/js" ]; then
		OUT=$(eslint --no-config-lookup -c "$ROOT/tools/eslint.config.mjs" "$PLUGIN/assets/js" 2>&1)
		report "eslint" "$?" "$(echo "$OUT" | tail -1)"
	else
		report "eslint" 3 "no assets/js in this candidate"
	fi
else
	report "eslint" 3 "eslint not on PATH"
fi

if command -v phpcs > /dev/null 2>&1; then
	OUT=$(phpcs --standard="$ROOT/phpcs.xml.dist" \
		--sniffs=WordPress.Security.EscapeOutput,WordPress.Security.ValidatedSanitizedInput,WordPress.Security.NonceVerification,WordPress.DB.PreparedSQL,WordPress.DB.PreparedSQLPlaceholders \
		--report=summary "$PLUGIN" 2>&1)
	report "phpcs security" "$?" "$(echo "$OUT" | grep -E 'A TOTAL|^$' | head -1)"
else
	report "phpcs security" 3 "phpcs not on PATH"
fi

echo
printf '%d passed, %d failed, %d skipped\n' "$PASS" "$FAIL" "$SKIP"
echo 'Not covered by any of the above: activation, admin screens, AJAX,'
echo 'the database, and Plugin Check. A pass here is not a working plugin.'

[ "$FAIL" -eq 0 ] || exit 1
exit 0
