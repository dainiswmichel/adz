#!/bin/sh
# Every check that runs without a WordPress install.
#
# Usage: sh tools/tests/run-all.sh [candidate-dir]
#
# A clean run means the code is well-formed and internally consistent. It does
# not mean the plugin works: nothing here activates it, opens an admin screen,
# or touches a database.

set -e

ROOT=$(cd "$(dirname "$0")/../.." && pwd)
PLUGIN=${1:-"$ROOT/pre-release-candidates/claude-code-web"}
FAIL=0

echo "Candidate: $PLUGIN"
echo

echo "--- php -l ---"
find "$PLUGIN" -name '*.php' -print0 | xargs -0 -n1 php -l > /dev/null || FAIL=1
echo "ok"
echo

echo "--- call resolution ---"
php "$ROOT/tools/resolve-calls.php" "$PLUGIN" --quiet || FAIL=1
echo

for t in preferences matching rotation; do
	echo "--- test-$t ---"
	php "$ROOT/tools/tests/test-$t.php" "$PLUGIN" | tail -1 || FAIL=1
done
echo

if command -v eslint > /dev/null 2>&1; then
	echo "--- eslint ---"
	if [ -f "$PLUGIN/assets/js/adz-rotation.js" ]; then
		eslint --no-config-lookup -c "$ROOT/tools/eslint.config.mjs" "$PLUGIN/assets/js" || FAIL=1
		echo "ok"
	else
		echo "no assets/js in this candidate; skipped"
	fi
	echo
fi

if command -v phpcs > /dev/null 2>&1; then
	echo "--- phpcs, the sniffs that decide a re-review ---"
	phpcs --standard="$ROOT/phpcs.xml.dist" \
		--sniffs=WordPress.Security.EscapeOutput,WordPress.Security.ValidatedSanitizedInput,WordPress.Security.NonceVerification,WordPress.DB.PreparedSQL,WordPress.DB.PreparedSQLPlaceholders \
		--report=summary "$PLUGIN" || FAIL=1
else
	echo "phpcs not on PATH; run it with --standard=phpcs.xml.dist"
fi

echo
if [ "$FAIL" -eq 0 ]; then
	echo "All checks that can run without WordPress passed."
	echo "Not covered: activation, admin screens, AJAX, database, Plugin Check."
else
	echo "Something failed above."
fi

exit "$FAIL"
