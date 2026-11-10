# Review prompt — claude/design-display-library

Hand everything below the rule to a reviewer, human or agent. It asks them to
establish whether this branch is WordPress.org standards compliant, and to find
what is wrong with it.

---

You are reviewing one branch in the `dainiswmichel/adz` repository. The plugin
was closed by WordPress.org on 29 September 2026 for a guideline violation and
has not yet been reopened, so compliance is the point of the review and not a
formality.

**Branch:** `claude/design-display-library`
**Commit:** `e90cf7ec72ed79f0c544cb805a4056da3d079711`
**Based on:** `main` at `5aafe52`
**Tree:** `pre-release-candidates/codex-gpt5/`

Read `AGENTS.md` first and follow it. You update
`docs/adz-remediation-collaboration-file.html` rather than creating a separate
document, you preserve every existing entry, you fetch and merge before editing,
and you report what you actually ran rather than what you expect would pass.

## Treat this as an audit

The author of this branch found four defects in their own work during one
session, two of them after reporting the branch as finished: an
`aria-label` rendered as CSS generated content, a dead duplicate declaration, a
shortcode escape that would have rendered 33 unintended ad rotations on a
generated page, and two unused catch bindings. The same author has, in this
project, reported a file as standards-clean while their PHPCS config was
silently reporting nothing, and reported a test suite as passing while its
runner was reading the wrong exit status. Assume there is more. Confirming a
number is less useful than disproving one.

## 1. Establish the numbers yourself

Every figure below is claimed in the collaboration file under
`#claude-design-final-2026y-11m-10d-UTC-16h-45m-48s`. Re-measure at the commit
named above.

```sh
git fetch origin && git checkout e90cf7ec72ed79f0c544cb805a4056da3d079711

phpcs --standard=WordPress --extensions=php --report=summary \
  pre-release-candidates/codex-gpt5

phpcs --standard=WordPress --extensions=php \
  --sniffs=WordPress.Security.EscapeOutput,WordPress.Security.ValidatedSanitizedInput,WordPress.Security.NonceVerification,WordPress.DB.PreparedSQL,WordPress.DB.PreparedSQLPlaceholders \
  --report=summary pre-release-candidates/codex-gpt5

find pre-release-candidates/codex-gpt5 -name '*.php' -print0 | xargs -0 -n1 php -l | grep -v "No syntax errors"
grep -rn 'phpcs:ignore\|phpcs:disable\|@codingStandardsIgnore' pre-release-candidates/codex-gpt5
grep -rn 'style="' pre-release-candidates/codex-gpt5/includes pre-release-candidates/codex-gpt5/assets
grep -rn 'wp_add_inline_style\|<style' pre-release-candidates/codex-gpt5/includes
grep -rn '\.style\.' pre-release-candidates/codex-gpt5/assets/*.js
php tools/resolve-calls.php pre-release-candidates/codex-gpt5
sh tools/tests/run-all.sh pre-release-candidates/codex-gpt5
git diff --check
```

Claimed: WPCS zero findings; security sniffs zero findings; 60 PHP files parse;
no suppressions; no inline styles, no `<style>` blocks, no `.style` mutation;
call resolution clean; 61 harness assertions pass; `git diff --check` clean;
ESLint 3 errors, matching the baseline's 3 pre-existing unused catch bindings.

**Prove the ruleset before you trust a green result.** A PHPCS ruleset that
fails to parse reports nothing, which reads exactly like a clean pass, and that
has already happened once in this project.

```sh
printf '<?php\n$x=1;\n' > /tmp/probe.php
phpcs --standard=WordPress --report=summary /tmp/probe.php   # must report violations
rm /tmp/probe.php
```

Apply the same scepticism to `tools/resolve-calls.php` and
`tools/tests/run-all.sh`: both were written by this branch's author and both have
produced a wrong answer before. Break a copy of the candidate on purpose — a
syntax error, an unresolved `adz_` call, a duplicate file-scope function — and
confirm each reports it and exits non-zero.

## 2. Run the checks this branch could not

These are the gap, and the most valuable thing you can add.

- **WordPress Plugin Check.** `wp plugin check` on the installed plugin. Nobody
  has run it against this branch.
- **A clean WordPress install with `WP_DEBUG` and `WP_DEBUG_LOG` on.** Activate,
  then read the log. Activation should print nothing and log nothing.
- **A browser.** Nothing on this branch has been looked at. Specifically:
  - Open the **Starter packs** tab. Does it render, and does the page callback
    exist? The tab is registered in `adzworld-desk.php` and implemented in a new
    file; if the `require_once` line in `adz-world.php` were missing the tab
    would appear and fatal.
  - Press **Create the showcase page**, then open the generated page. The 33
    code blocks must *display* `[adz_rotation …]` as text. If any of them
    renders an ad rotation instead, the bracket escaping is wrong.
  - Press **Set up this pack** on a pack whose provider is not connected. It
    must refuse and name what is missing, not create a rotation.
  - Place each of the 18 formats on a page and look at it. The author has seen
    none of them.
  - Dismiss a `dock-bottom` and a `corner`, then load another page. The
    dismissal should hold for the tab and not beyond it.

## 3. Review these changes for defects, not for style

In descending order of risk.

1. **`assets/rotation.js`.** A new flag, `data-adzworld-multiple`, makes the
   script keep sibling slots visible instead of hiding them, for `grid`,
   `filmstrip`, `coverflow` and `stack`. Confirm the one-at-a-time path is
   byte-for-byte unchanged in behaviour for every other format, that
   `aria-hidden` and `inert` are correct in both paths, and that every provider
   unit mounts when every slot is on screen. Check the dismiss handler's two
   `sessionStorage` calls really cannot throw out of the handler.
2. **`includes/adzworld-starter.php`.** New, 725 lines, and the only code here
   that writes to the database. Check: the pack id from `$_POST` is validated
   against the registry before use and never becomes an option name or a stored
   key; `check_admin_referer()` runs before the capability check and the nonce is
   not treated as authorisation; `publish_pages` is required where a page is
   created; the generated page is a **draft**; a second press does not create a
   duplicate; and a rotation that the shared sanitiser rejects is not saved.
3. **`includes/adzworld-affiliates.php`.** ClickBank is new. Confirm the HopLink
   format is the one ClickBank currently documents, that the field patterns
   cannot admit a value that breaks the URL, and that **nothing derived from the
   visitor's request reaches the link**. That last one is a deliberate privacy
   property and the whole premise of the plugin; if you can get visitor text
   into a HopLink, say so loudly.
4. **`includes/adzworld-rotation.php`.** The `display` allowlist widened from 3
   values to 18 and a `size` attribute was added. Confirm every key can only
   produce a safe class name, that an unknown value is still refused, and that
   the admin format reference cannot list a format the shortcode would reject.
5. **The two stylesheets**, +1091 lines. Look for a selector that reaches
   outside the plugin's own scope, a fixed-position format that escapes its
   container, and anything that breaks at phone width. Confirm the claim that no
   styled class exists which the PHP never emits.

## 4. Judge the compliance question directly

The 29 September notice listed ten items. The collaboration file records nine as
remediated by the baseline and one as open: the external-service disclosure.
This branch adds ClickBank, a seventh affiliate network whose links leave the
site, which belongs in that disclosure.

State plainly whether, in your judgement, this branch is WordPress.org
standards compliant, and list anything that would draw a reviewer's objection.
Consider at least: the `base64_encode` uses, the seventh administrator tab, a
plugin that generates a page on someone's site, and the external-service
disclosure for every provider and affiliate network the plugin can reach.

Do not call it ready for submission on the strength of static checks. Nothing on
this branch has been run on WordPress.

## 5. Report

Write your findings into `docs/adz-remediation-collaboration-file.html` as your
own section with your own UTC timestamp and the commit you measured. Do not edit
`#claude-design-final-2026y-11m-10d-UTC-16h-45m-48s` or any other agent's
section; if you disprove something in one, say so in yours and reference it.
Separate clearly:

- claims you reproduced,
- claims you could not reproduce, with the command and its output,
- defects you found, with file and line,
- what you did not test.

Correct any number you find wrong by stating the correct one. Do not soften a
finding to be agreeable.
