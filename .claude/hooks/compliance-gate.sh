#!/bin/sh
# Stop hook: refuse to end the turn while changed PHP carries a violation.
#
# Scope is the files this turn changed (git working tree + staged), not the
# whole repository: the integrated source carries thousands of pre-existing
# violations, and gating on those would block every turn forever. Gating on
# what was just touched is what makes the gate usable and still honest.
#
# Prints a JSON decision. {"decision":"block"} stops the turn ending.

HERE=$(dirname "$0")
cd "${CLAUDE_PROJECT_DIR:-/home/user/adz}" || exit 0

block() {
  jq -n --arg r "$1" '{decision:"block", reason:$r}'
  exit 0
}

CHANGED=$(git status --porcelain -uall 2>/dev/null \
  | awk '{ if ($1 != "D") print $NF }' \
  | grep '\.php$' \
  | grep -v '^ancestor/' \
  | grep -v '^\.phpcs/')

[ -n "$CHANGED" ] || exit 0

FAILED=""

# 1. Suppressions. These read to a reviewer as a concealed finding.
SUPPRESS=$(printf '%s\n' "$CHANGED" | while IFS= read -r f; do
  [ -f "$f" ] && grep -Hn 'phpcs:ignore\|phpcs:disable\|@codingStandardsIgnore' "$f"
done)
[ -n "$SUPPRESS" ] && FAILED="${FAILED}
Suppressions in changed files (remove them, do not silence the sniff):
${SUPPRESS}
"

# 2. Programmatic authentication. wordpress.org rejects this outright.
AUTH=$(printf '%s\n' "$CHANGED" | while IFS= read -r f; do
  [ -f "$f" ] && grep -Hn 'wp_set_auth_cookie\|wp_insert_user\|wp_set_current_user' "$f"
done)
[ -n "$AUTH" ] && FAILED="${FAILED}
Programmatic authentication in changed files:
${AUTH}
"

# 3. Coding standards. A check that cannot run is not a pass.
PHPCS=$("$HERE/find-phpcs.sh")
if [ -z "$PHPCS" ]; then
  block "phpcs with the WordPress standard was not found, so the changed PHP could not be checked. That is not a pass. Install it and run the gate before reporting compliance.
Changed files:
${CHANGED}"
fi

EXISTING=$(printf '%s\n' "$CHANGED" | while IFS= read -r f; do [ -f "$f" ] && printf '%s\n' "$f"; done)
if [ -n "$EXISTING" ]; then
  OUT=$(printf '%s\n' "$EXISTING" | xargs "$PHPCS" --standard=WordPress --report=full --no-colors 2>&1)
  CODE=$?
  case "$CODE" in
    0|1) ;;
    2) FAILED="${FAILED}
WordPress Coding Standards errors in changed files:
${OUT}
" ;;
    *) FAILED="${FAILED}
phpcs could not complete (exit ${CODE}). This is not a pass:
${OUT}
" ;;
  esac
fi

[ -n "$FAILED" ] && block "The compliance gate failed on files changed this turn. Fix these rather than reporting them as clean.
${FAILED}"

exit 0
