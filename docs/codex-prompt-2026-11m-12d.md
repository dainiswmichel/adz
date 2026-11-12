# Codex task: 12 November — commit the live code, fix three defects, then ship

## 0. Blocking: the live site runs code that is not in git

The front page says:

> Enter as much detail as you like. Use a colon before refinements, or a pipe
> when the category itself contains a colon.

The committed code on `main` says:

> Categories use up to 3 words and 48 characters.

That live string appears in **no commit on any branch**:

```bash
git grep -l 'colon before refinements' $(git rev-list --all)   # no match
```

So the colon/pipe convention is implemented and working on the server, and the
implementation is not in the repository.

Three consequences, which is why this is item 0:

1. **Today's WordPress.org submission cannot proceed.** The release would ship
   `main`, which does not contain the parser that is live and tested.
2. **The external human review cannot see it.** Reviewers read the repository.
3. **If the host is rebuilt the work is gone**, with no copy anywhere.

### Do this first

```bash
cd <the live install>
diff -ru <repo>/pre-release-candidates/dwm-local wp-content/plugins/adz-world \
  | grep -v '^Only in wp-content.*: \(tests\|review\)' > /tmp/live-vs-repo.diff
wc -l /tmp/live-vs-repo.diff
```

Copy every live change back into `pre-release-candidates/dwm-local/`, commit it
with a message saying what it does, and push. Do not rewrite it from memory —
copy the files that are actually running and tested.

Then prove the repo and the server match:

```bash
diff -r <repo>/pre-release-candidates/dwm-local wp-content/plugins/adz-world \
  --exclude=tests --exclude=review && echo "REPO MATCHES LIVE"
```

Nothing below should start until that prints `REPO MATCHES LIVE`.

---

## 1. Three defects, measured on the live site

The adz themselves work. Driving the live rotation with
`couch: light fabric, two-seater` produced category `couch` with refinements
`light fabric` and `two-seater`, and slides 1–3 served real adz ("Light fabric
two-seater couch…", "Used car for purchase…", "Bicycle for a nine year old…").
The convention parses correctly. These three are separate.

### 1a. `admin-ajax.php` returns 200 with an empty body

Every call. Measured:

```
AJAX 200  len=0  body=""
```

A handler that finishes with `wp_send_json_success()` or `wp_send_json_error()`
cannot return zero bytes, so either the handler is exiting before those calls
or output is being discarded. The UI currently cannot distinguish success from
failure.

Find the registered `wp_ajax_` / `wp_ajax_nopriv_` handler the panel posts to,
and confirm every exit path ends in `wp_send_json_success()` or
`wp_send_json_error()`. Check for a `die()`/`exit` or a stray `ob_end_clean()`
ahead of them. Verify by asserting the response parses as JSON:

```bash
curl -s -o - -X POST https://adz-world.com/wp-admin/admin-ajax.php \
  -d 'action=<the action>&...' | python3 -c 'import json,sys; json.load(sys.stdin); print("JSON OK")'
```

### 1b. Two empty refinement chips are rendered

After submitting one category with two refinements, the DOM holds **four**
`.adz-panel__chip--category_refinements` elements:

```
0: "light fabric"
1: "two-seater"
2: ""
3: ""
```

Two carry no text. Find where refinement chips are built in
`includes/adz-preference-ui.php` and skip any whose refinement is empty after
trimming, and also stop the empty pair being stored in the first place — both
`$pref['category_refinements'][] = $pair;` sites append without checking that
the part after `--` is non-empty.

### 1c. A bare "1" renders beside every category chip

Category chips read `"couch Edit category ✎ 1"`, `"car 1"`, `"bicycle 1"`. The
`1` is the category slot button from `adzworld_category_slot_control()`,
rendered with no visible label, so it reads as stray output. Either give it a
visible affordance or hide it until there is more than one slot.

### 1d. Refinement chips carry no category in the DOM

Measured: every refinement chip has `attrs={}` — no `data-` attribute naming
its category. A reader cannot tell which category a refinement belongs to, and
the markup cannot group them. Add `data-adzworld-category="<category>"` to each
refinement chip and group them visually under their category.

### Regression test that must be added

`adzworld_normalize_category()` permits `--` inside a category, and
`adzworld_category_refinement_preference()` matches by prefix on
`$category . '--'`. So:

- category `a`, refinement `b--c` → stored `a--b--c`
- category `a--b`, refinement `c` → stored `a--b--c`

Identical strings; prefix-matching `a--` returns the second one's refinement to
category `a`. Add a test in `tests/regression.php` that stores both and asserts
each category reads back only its own refinement. **Confirm it fails first.**

Fix by making the separator unambiguous — escape `-` in both halves before
joining, so `--` cannot occur inside either:

```php
function adzworld_pair_encode( $value ) {
	return str_replace( '-', '%2D', rawurlencode( $value ) );
}
// adzworld_pair_decode() is rawurldecode().
```

Apply at all three sites that build a pair, and where pairs are read.

---

## 2. Styling: minimal

Dainis' direction is ultra-minimal, and no footer info panels. Do not add
sections. The outstanding styling item is the one already specified in
`docs/codex-prompt-2026-11m-11d-round3.md` §1: `.adzworld-slide` has
`padding: 0`, so a local adz renders its text against the frame border. Pad
`.adzworld-creative` / `.adzworld-caption`, never `.adzworld-slide` and never
`.adzworld-provider`.

---

## 3. Version bump and WordPress.org

Only after item 0 prints `REPO MATCHES LIVE` and items 1a–1d are fixed and
pushed.

Dainis sets the version number. Ask him; do not choose one. When he gives it,
all three must match or WordPress.org silently serves the previous release:

```
pre-release-candidates/dwm-local/adz-world.php   Version:
pre-release-candidates/dwm-local/readme.txt      Stable tag:
the GitHub release tag
```

`.github/scripts/check-version.sh` enforces this; run it locally first.

Before submitting, the seven checks dropped when `quality-gate.yml` replaced
`release-gate.sh` need to be back, listed in
`docs/codex-prompt-2026-11m-11d-round3.md` §4. The external-services
disclosure row is the closure notice's item 7 and PHPCS cannot see it.

`/terms/` and `/privacy/` still return **404**. They are named in `readme.txt`
and the plugins team wrote they will check them. Content is on `main` at
`docs/site-pages/`; four placeholders need values from Dainis.

---

## 4. What the external review will need

Reviewers read the repository, so anything that exists only on the server is
invisible to them. After item 0, confirm and report:

- `diff -r` between repo and live is empty
- `wp plugin check adz-world` output in full — this has still never been run
- the `debug.log` from a clean activation with `WP_DEBUG` on
- which of items 1a–1d are fixed, each with the command and its output

## Report

Per item: what changed, the command, its actual output. If something fails, say
so with the output rather than reporting it done.
