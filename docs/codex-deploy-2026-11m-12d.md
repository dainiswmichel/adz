# Codex: bring adz-world.com and the repo back into agreement

Role split for this round: Claude is lead coder, you are deploying. Nothing
below asks you to design or to decide scope. Where a step needs a decision or
a credential, it says to ask Dainis rather than choose.

Every step states its expected output. If one does not match, stop and report
with the output rather than continuing.

---

## Step 0 — sync and prove it

```bash
cd <repo root>
git checkout main
git fetch origin main
git reset --hard origin/main     # only if you have no local work on main
git rev-parse --short HEAD
git merge-base --is-ancestor 0a4a883 HEAD && echo "SYNCED"
ls -l tools/test-refinement-bleed.php
```

Expected: `SYNCED` prints and the test file exists. A clone from before
`60fe879` sees an older `claude-code-web` and nothing below will work. Grok hit
exactly that on 12 November and correctly stopped.

---

## Step 1 — the live site is running code that is not in git

Measured just now:

```bash
curl -s https://adz-world.com/ | grep -c 'colon before refinements'   # 1
git grep -l 'colon before refinements' origin/main -- '*.php'         # no output
```

The front page says *"Use a colon before refinements, or a pipe when the
category itself contains a colon."* That string is in **no commit on any
branch**. The colon/pipe convention is implemented, working and tested on the
server, and the implementation exists nowhere else.

This is first because of what it costs:

- a WordPress.org release built from `main` ships **without** the parser that
  is live and tested
- the external human reviewers read the repository, so they cannot see it
- if the host is rebuilt the work is gone, with no copy anywhere

### Do this

```bash
cd <the live install>
diff -ru <repo>/pre-release-candidates/dwm-local wp-content/plugins/adz-world \
  | grep -v '^Only in wp-content.*: \(tests\|review\)' > /tmp/live-vs-repo.diff
wc -l /tmp/live-vs-repo.diff
```

Copy every live change back into `pre-release-candidates/dwm-local/`. Copy the
files that are actually running; do not rewrite them from memory. Commit with
a message describing what the code does, and push.

Prove it:

```bash
diff -r <repo>/pre-release-candidates/dwm-local wp-content/plugins/adz-world \
  --exclude=tests --exclude=review && echo "REPO MATCHES LIVE"
```

Nothing after this step starts until that prints `REPO MATCHES LIVE`.

---

## Step 2 — port the category integrity fix

A refinement typed under one category still leaks into another on the shipping
tree. Measured:

```bash
ADZ_SRC=pre-release-candidates/dwm-local php tools/test-refinement-bleed.php
# PHP Fatal error: Call to undefined function adzworld_pair_join()
```

The fix is on `main` in `pre-release-candidates/claude-code-web`, which passes
14/14. Full instructions, including the tripwire and the twelve exact
replacements if the tripwire fires, are in:

```
docs/codex-prompt-category-integrity-2026-11m-12d.md
```

Follow that document. Afterwards:

```bash
ADZ_SRC=pre-release-candidates/dwm-local php tools/test-refinement-bleed.php
```

Expected: `14 passed, 0 failed`.

Note: step 1 may change these files. Do step 1 first, then re-read the
tripwire output before copying anything.

---

## Step 3 — `.distignore` is missing from the shipping tree

Without it, `tests/` is in the release ZIP. `tests/` calls
`wp_set_current_user()` sixteen times, which a reviewer reads as a plugin
creating and logging in users. That is the kind of finding that closes a
plugin.

Create `pre-release-candidates/dwm-local/.distignore`:

```
/.git
/.github
/.gitignore
/.distignore
/tests
/review
/node_modules
composer.json
composer.lock
package.json
package-lock.json
phpcs.xml
phpcs.xml.dist
phpunit.xml
phpunit.xml.dist
*.map
.DS_Store
```

Verify:

```bash
test -f pre-release-candidates/dwm-local/.distignore && echo present
grep -qx '/tests' pre-release-candidates/dwm-local/.distignore && echo "tests excluded"
```

---

## Step 4 — four checks the gate lost

`quality-gate.yml` is better than the script it replaced: it parses production
JS and JSON, detects `phpcs:ignore` suppressions, and runs
`wordpress/plugin-check-action`. Keep all of that.

It has zero matches for `External services`, `pagead2` or `text_domain`:

```bash
git show origin/main:.github/workflows/quality-gate.yml | grep -c 'External services\|pagead2\|text_domain'   # 0
```

Those were **item 7 of the closure notice** and PHPCS cannot see any of them.
Nothing currently stops a release that breaks the disclosure again.

Add four steps to `quality-gate.yml`, against `$BUILD`:

1. No gettext call uses a domain other than `adz-world`:
   `grep -rnE ",[[:space:]]*'(text_domain|eof)'[[:space:]]*\)" --include='*.php' "$BUILD"` must find nothing.
2. `readme.txt` contains `== External services ==`.
3. For each provider host the code contacts — `pagead2.googlesyndication.com`,
   `securepubads.g.doubleclick.net`, `cdn.taboola.com`, `widgets.outbrain.com`,
   `hop.clickbank.net` — if the host appears in the code, the provider's name
   must appear in `readme.txt` within eight lines of the words `terms` or
   `privacy`.
4. `.distignore` exists and contains `/tests` and `/review`.

A working implementation of all four is in
`git show claude/design-display-library:.github/scripts/release-gate.sh`,
section `# --- 4. readme requirements ---`. Copy the logic, not the file.

---

## Step 5 — terms and privacy, 404 since this began

```bash
curl -o /dev/null -s -w '%{http_code}\n' https://adz-world.com/terms/     # 404
curl -o /dev/null -s -w '%{http_code}\n' https://adz-world.com/privacy/   # 404
```

Both URLs are named in `readme.txt` under `== External services ==`, and the
plugins team wrote they will check them at the next review. **This is the only
item that blocks the WordPress.org submission outright.**

Content is written and on `main` at `docs/site-pages/terms.html` and
`privacy.html`.

**Ask Dainis for these four and substitute them in both files. Do not invent
them — they are legal identifiers.**

| Placeholder | What it is |
|---|---|
| `[LEGAL ENTITY]` | the company or person operating the service |
| `[ADDRESS]` | its registered address |
| `[JURISDICTION]` | the governing law for the terms |
| `[CONTACT EMAIL]` | the address for privacy requests |

```bash
wp post create docs/site-pages/terms.html \
  --post_type=page --post_status=publish --post_title="Terms of Service" --post_name=terms
wp post create docs/site-pages/privacy.html \
  --post_type=page --post_status=publish --post_title="Privacy Policy" --post_name=privacy
```

Both must return `200` and render as formatted content. Raw
`<!-- wp:paragraph -->` on screen means the file was imported as plain text,
not block markup. Add both to the footer navigation.

---

## Step 6 — AdSense serves nothing because no slot exists

Measured on the live page:

```
ads.txt                     correct, pub-5083802405715121, DIRECT
adsbygoogle.js loader       present, correct publisher id
<ins class="adsbygoogle">   0
data-adzworld-mount         0
```

The loader runs with nothing to fill. **No code change is needed.** The plugin
emits a mount only when a network row passes three gates and a rotation points
at it.

This needs Dainis' AdSense account, so hand him the procedure at
`docs/live-adz-procedure-2026-11m-12d.md` rather than attempting it. The short
version: AdSense → Ads → By ad unit → Display ads → Responsive, take the
`data-ad-slot` number, then Adz → Get Paid → Google AdSense, paste the
publisher id and that ad unit id, accept the host, tick Enabled, save, then
Adz → Rotation → Add slot → Google AdSense.

**One constraint that must not be broken:** set that rotation's display format
to **Single**. Every other format hides inactive slides, and the networks
prohibit serving into a hidden container.

---

## Do not do any of this

- Do not change the front page. Dainis has approved it as it stands.
- Do not edit `tools/test-refinement-bleed.php`. If it fails, the code is
  wrong, not the test.
- Do not touch `pre-release-candidates/claude-code-web`. That is the lead
  coder's tree.
- Do not bump the version or tag a release. Dainis decides when.
- Do not invent the four legal values.
- Do not force-push. `main` has had several pushes today; if yours is
  rejected, fetch and merge, then re-run the checks before pushing again.
- Do not report a step as done without pasting its actual output.

## Report

Per step: what you changed, the command, its actual output. If a step is
blocked on Dainis, say which value or action you are waiting for.
