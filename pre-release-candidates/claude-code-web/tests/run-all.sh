#!/bin/sh
# Run every check for this candidate. No WordPress, no network, no credentials.
# Usage, from the repository root:  sh pre-release-candidates/claude-code-web/tests/run-all.sh
set -u
HERE=$(cd "$(dirname "$0")" && pwd)
SRC=$(cd "$HERE/.." && pwd)
FAIL=0

echo "== php -l =="
find "$SRC" -name '*.php' -print0 | xargs -0 -n1 php -l | grep -v "No syntax errors" && FAIL=1
echo "   clean"

echo
echo "== harnesses =="
for t in test-matching test-preferences test-product-merge test-ebay-fetcher \
         test-itunes-fetcher test-avantlink-fetcher test-travelpayouts-fetcher; do
  [ -f "$HERE/$t.php" ] || continue
  OUT=$(ADZ_SRC="$SRC" php "$HERE/$t.php" 2>&1 | tail -1)
  printf "   %-30s %s\n" "$t" "$OUT"
  case "$OUT" in *"0 failed") ;; *) FAIL=1 ;; esac
done

echo
[ "$FAIL" = 0 ] && echo "all checks passed" || echo "SOMETHING FAILED"
exit $FAIL
