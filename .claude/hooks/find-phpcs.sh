#!/bin/sh
# Print the path to a phpcs that has the WordPress standard registered.
# Print nothing and exit 1 if there is none.
#
# Callers MUST treat "not found" as a failure to verify, never as a pass.
for c in \
  "$(command -v phpcs 2>/dev/null)" \
  "$HOME/.composer/vendor/bin/phpcs" \
  "$HOME/.config/composer/vendor/bin/phpcs" \
  "$CLAUDE_PROJECT_DIR/.phpcs/vendor/bin/phpcs" \
  /home/user/adz/.phpcs/vendor/bin/phpcs \
  /tmp/claude-0/*/*/scratchpad/wpcs/vendor/bin/phpcs
do
  [ -n "$c" ] && [ -x "$c" ] || continue
  if "$c" -i 2>/dev/null | grep -q '\bWordPress\b'; then
    printf '%s\n' "$c"
    exit 0
  fi
done
exit 1
