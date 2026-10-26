# Prompt for Grok — verify the claude-code-web entry and candidate before live tests

Paste everything below the line into Grok.

---

You are reviewing another agent's work in the `dainiswmichel/adz` repository.
Dainis begins live testing of the adz-world plugin tomorrow. Your job is to
establish whether the `claude-code-web` candidate and its entry in the shared
collaboration file are accurate, and to say plainly where they are not.

Read `AGENTS.md` first and follow it. In particular: you update
`docs/adz-remediation-collaboration-file.html` rather than creating a separate
document; you preserve every existing entry; you fetch and merge before editing
and never force-push; and you report what you actually ran rather than what you
expect would pass.

**Treat this as an audit, not an endorsement.** The agent that wrote this
candidate found several of its own errors during one session: a fatal call to a
function nothing defined, a phantom finding produced by its own analysis tool, a
PHPCS config broken by illegal XML that silently reported nothing, and two
escaping functions applied to the wrong context. The prior probability of more
mistakes is high, not low. Finding one is the useful outcome. Confirming a
number is less useful than disproving it.

## 1. Verify the claims, do not take them

The entry is `#ccw-remediation-notes`, inside `#roadmap-claude-code-web`, in
`docs/adz-remediation-collaboration-file.html`. It carries MTS
`2026-10-26T13:48:18Z` at repository `762524a`. Re-measure at current `main` and
report both figures where they differ; the numbers move whenever anyone pushes.

The specific claims to check:

- Zero WPCS security sniff errors for `claude-code-web`.
- 710 total WPCS errors for `claude-code-web`, and 704 for `codex-gpt5`.
- Zero unresolved plugin-prefixed calls, zero arity mismatches and zero
  duplicate file-scope definitions in all four candidates.
- 98 passing assertions across `tools/tests/`.
- All 51 PHP files in the candidate pass `php -l`.

```sh
git fetch origin main && git merge origin/main

phpcs --standard=phpcs.xml.dist \
  --sniffs=WordPress.Security.EscapeOutput,WordPress.Security.ValidatedSanitizedInput,WordPress.Security.NonceVerification,WordPress.DB.PreparedSQL,WordPress.DB.PreparedSQLPlaceholders \
  --report=summary pre-release-candidates/claude-code-web

phpcs --standard=phpcs.xml.dist --report=summary pre-release-candidates/claude-code-web

php tools/resolve-calls.php pre-release-candidates/claude-code-web
sh tools/tests/run-all.sh pre-release-candidates/claude-code-web
```

Before you trust `phpcs.xml.dist`, prove it parses rather than merely runs. A
ruleset with illegal XML causes PHPCS to report nothing, which reads exactly
like a clean result. This already happened once:

```sh
printf '<?php\n$x=1;\n' > tools/_probe.php
phpcs --standard=phpcs.xml.dist --report=summary tools/_probe.php   # must report violations
rm tools/_probe.php
```

Apply the same scepticism to `tools/resolve-calls.php`. It is a tool written by
the agent whose work you are auditing, and it has already produced one phantom
fatal. Point it at a file you have deliberately broken and confirm it notices.

## 2. Review the risky changes for broken behaviour, not style

PHPCS passing says nothing about whether the plugin still works. These are the
changes most likely to have broken something, in descending order of risk.
Compare each against `ancestor/plugin`, which is the frozen 1.0.8 baseline.

1. **`adz-world.php`, the `adz_get_advertise_content()` handler.** Ten request
   fields that were read across roughly fifty sites are now read once at the top
   into `$adz_in_*` variables. Confirm every original site reads the same value
   it did before, that no site lost a cast or a comparison it relied on, and
   that the loose comparisons still behave the same. Seven `wp_unslash()` calls
   were removed as redundant: confirm each truly was.
2. **`includes/adz-helper.php` and `includes/adz-shortcode.php`.** Forty-one
   values emitted into JavaScript string literals now pass through `esc_js()`,
   and several bare numeric JavaScript values through `absint()`. `esc_js()`
   encodes characters; confirm no value reaching the AJAX handler is altered in
   a way that changes behaviour, especially template names, rotation ids and
   sequences.
3. **`includes/adz-preferences.php`, `adz_get_all_ad_ids()`.** Newly written.
   It caches for five minutes and flushes on save, delete and both trash
   transitions. Confirm the cache cannot serve a stale pool in a way a publisher
   would notice, and that the 200-item cap is sane for a real site.
4. **`vendor/eof/*`.** Roughly ninety escaping changes across the bundled
   options framework, which is the settings UI. This is the surface most likely
   to be visibly broken and the least likely to be covered by any test. Check
   in particular that no `esc_html()` landed where markup was intended and no
   `esc_attr()` landed on a URL.
5. **`includes/adz-cpt-ads.php`.** `adz_ad_columns_head()` now reads the post
   type from `get_current_screen()` instead of `$_GET`. Confirm that filter runs
   in a context where the current screen is available, and that the adz list
   table columns still appear.

## 3. Judge the four deliberate suppressions

The candidate leaves four PHPCS findings unfixed, each with a stated reason:
the buffered settings form in `class-eof-views.php`; two `.json` download
bodies in `class-eof-import-export.php`; the tab read in `class-eof-views.php`;
and the Settings API callback in `class-eof-settings.php`. Decide whether each
reason is correct. If any is wrong, say so and state the fix. A suppression with
a confident comment is still a suppression.

## 4. State what will break in live testing

This matters more tomorrow than any count. No candidate has been activated on
WordPress or run through Plugin Check. Using the code rather than the claims,
report what you expect to fail on a clean install with `WP_DEBUG` and
`WP_DEBUG_LOG` on:

- Activation. Task 13 records an activation fault that remains unidentified by
  anyone. An earlier claim that it was a `role_exists()` fatal was withdrawn as
  unverified. If you can identify the real cause from the code, that is the
  single most valuable thing you can produce.
- The `[adz_rotation]` shortcode, the Rotation admin screen, the preference
  panel and its AJAX endpoint. None has been exercised in a browser.
- `base64_encode` is used six times as the PHP to `window.atob()` transport.
  Plugin Check flags it as possible obfuscation. Say whether you think it will
  draw a rejection and what you would do instead.
- Anything touching the database: the views table, the ledger, payouts.

## 5. Report

Write your findings into `docs/adz-remediation-collaboration-file.html` as your
own section, with your own UTC MTS and the commit you measured at. Do not edit
`#ccw-remediation-notes` or any other agent's section; if you disprove something
in one, say so in yours and reference it. Separate clearly:

- claims you reproduced,
- claims you could not reproduce, with the command and the output,
- defects you found, with file and line,
- what you did not test.

Correct any number in `#ccw-remediation-notes` that you find to be wrong by
stating the correct one in your section. Do not soften a finding to be
agreeable, and do not call the candidate ready for submission on the strength of
static checks: nothing in this repository has been run on WordPress yet.
