#!/bin/sh
# PostToolUse hook: check one edited PHP file against WordPress Coding Standards.
#
# Reads the hook payload on stdin and takes the edited path from it.
# Exit 0  = nothing to check, or the file is clean.
# Exit 2  = blocking: violations found, OR the check could not be run.
#
# "Could not be run" blocks on purpose. phpcs exits 3 when the standard is
# missing or the file is unreadable, and treating that as a pass is how an
# unverified file gets reported as clean.

HERE=$(dirname "$0")

FILE=$(jq -r '.tool_input.file_path // .tool_response.filePath // empty' 2>/dev/null)

[ -n "$FILE" ] || exit 0
case "$FILE" in
  *.php) ;;
  *) exit 0 ;;
esac
[ -f "$FILE" ] || exit 0

# ancestor/ is frozen and must not be reformatted; .phpcs/ is installed tooling.
case "$FILE" in
  */ancestor/*|*/.phpcs/*) exit 0 ;;
esac

PHPCS=$("$HERE/find-phpcs.sh")
if [ -z "$PHPCS" ]; then
  echo "BLOCKED: phpcs with the WordPress standard was not found, so this file could not be checked." >&2
  echo "Install it, then re-check. An unchecked file must not be reported as compliant." >&2
  exit 2
fi

OUT=$("$PHPCS" --standard=WordPress --report=full --no-colors "$FILE" 2>&1)
CODE=$?

case "$CODE" in
  0|1)
    exit 0
    ;;
  2)
    echo "BLOCKED: WordPress Coding Standards violations in $FILE" >&2
    echo "$OUT" >&2
    echo "Fix these before reporting this file as compliant. Do not add phpcs:ignore." >&2
    exit 2
    ;;
  *)
    echo "BLOCKED: phpcs could not check $FILE (exit $CODE). This is not a pass." >&2
    echo "$OUT" >&2
    exit 2
    ;;
esac
