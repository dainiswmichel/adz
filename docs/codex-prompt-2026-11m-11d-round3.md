# Codex task: adz-world.com round 3

Four items. 1 and 2 are plugin code. 3 and 4 are deployment.

---

## 1. Local adz text renders flush against the top of the frame

**Symptom.** On the live site, slide 2 shows "Light fabric two-seater couch. A
light colour fabric couch for two people. It is not leather." with the first
line touching the card's top border. It reads as cut off.

**Cause.** Measured on the live page, not inferred:

```
.adzworld-slide  padding: 0px
```

`includes/.../rotation.css` sets `.adzworld-slide { padding: 0; }`. The
invitation slide looks correct because `.adz-panel` carries its own padding.
The local-adz slide has no padded wrapper, so its text starts at the frame
edge. No ancestor clips anything — every one is `overflow: visible` and
`scrollHeight == height`.

**Fix.** Pad the adz content wrappers, not the slide:

```css
.adzworld-rotation .adzworld-creative,
.adzworld-rotation .adzworld-caption,
.adzworld-rotation .adzworld-match-label {
	padding-inline: var(--adz-gap);
}

.adzworld-rotation .adzworld-slide > .adzworld-creative:first-child {
	padding-block-start: var(--adz-gap);
}
```

**Do not** add padding to `.adzworld-slide` itself and **do not** touch
`.adzworld-provider`. Padding on the slide insets a fixed-size provider slot
inside its declared box; a 728x90 unit in a padded 728x90 slot overflows. The
rule that the plugin never alters a received creative still holds.

Verify: load a rotation whose slot is a local adz, and confirm the text has
clear space above it while a provider slot's bounding box is unchanged.

---

## 2. Category and refinement string format

### The convention to implement

A category is up to 3 words, 48 characters (`ADZWORLD_CATEGORY_MAX_WORDS`,
`ADZWORLD_CATEGORY_MAX_LENGTH` — unchanged).

Input is one line. Parse it like this:

| Input | Category | Refinements |
|---|---|---|
| `couch` | `couch` | none |
| `couch: light fabric, two-seater` | `couch` | `light fabric`, `two-seater` |
| `canon: eos r5 \| cheap, used` | `canon: eos r5` | `cheap`, `used` |

Rules, in this order:

1. If the string contains a `|`, split on the **first** `|`. Left is the
   category, right is the refinement list.
2. Otherwise, if it contains a `:`, split on the **first** `:`. Same.
3. Otherwise the whole string is the category and there are no refinements.
4. The refinement list is split on `,`. Trim each. Discard empties.
5. Validate the category with `adzworld_valid_category()` **after** splitting.
   A colon inside a category counts toward the 48 characters; it is not a word
   separator.

### The bug this has to fix

Pairs are currently stored in `$pref['category_refinements']` as
`category . '--' . refinement`, and read back in
`adzworld_category_refinement_preference()` by prefix match:

```php
$prefix = (string) $category . '--';
if ( 0 === strpos( $stored, $prefix ) ) { ... }
```

`adzworld_normalize_category()` collapses whitespace and nothing else, so a
category may itself contain `--`. Then:

- category `a`, refinement `b--c`  → stored `a--b--c`
- category `a--b`, refinement `c`  → stored `a--b--c`

Identical strings. Prefix-matching `a--` returns the second one's refinement to
category `a`. **That is refinements landing under the wrong category.**

### The fix

Make the separator unambiguous by encoding each half:

```php
$pair = rawurlencode( $category ) . '--' . rawurlencode( $refinement );
```

and match on the encoded category:

```php
$prefix = rawurlencode( (string) $category ) . '--';
if ( 0 === strpos( $stored, $prefix ) ) {
	$refinement = rawurldecode( substr( $stored, strlen( $prefix ) ) );
}
```

`rawurlencode` escapes `-` nowhere but escapes nothing that collides: a literal
`-` stays `-`, so `a--b` as a *category* still encodes to `a--b` and the
collision remains. **Escape the hyphen explicitly** so the separator cannot
occur inside either half:

```php
function adzworld_pair_encode( $value ) {
	return str_replace( '-', '%2D', rawurlencode( $value ) );
}
function adzworld_pair_decode( $value ) {
	return rawurldecode( $value );
}
```

`%2D` decodes back to `-` through `rawurldecode`, and no encoded half can then
contain a literal `-`, so `--` is unambiguous. Apply `adzworld_pair_encode()`
everywhere a pair is built — `includes/adz-preference-ui.php` builds them in at
least three places (`improve`, the `else` branch, and `rename_refinement`) —
and `adzworld_pair_decode()` everywhere one is read.

### The cookie encoder also drops colons

`adzworld_encode_preference()` currently does, per term:

```php
$term = sanitize_key( str_replace( array( '_', ' ' ), array( '-', '_' ), $term ) );
```

`sanitize_key()` keeps only `a-z0-9_-`. A colon typed into a category is
silently destroyed on the round trip, so `canon: eos r5` comes back as
`canon-eos-r5` and the convention above cannot survive a page load. Replace
that per-term transform with a reversible one — `rawurlencode()` on write and
`rawurldecode()` on read — and keep the existing `|` facet separator and `:`
key separator, which `rawurlencode` escapes to `%7C` and `%3A` inside values.

The encoded cookie must still survive `sanitize_text_field()` unchanged.
`rawurlencode` output is `A-Za-z0-9-_.~%` only, so it does. **Add a test that
asserts this**, because the current encoding was built specifically so
`sanitize_text_field()` is lossless and that property must not be lost.

### Tests to add

In `tests/regression.php`:

- `couch: light fabric, two-seater` → category `couch`, refinements exactly
  `light fabric` and `two-seater`
- `canon: eos r5 | cheap, used` → category `canon: eos r5`, refinements `cheap`
  and `used`
- Store refinement `b--c` under category `a`, and refinement `c` under category
  `a--b`. Assert each category reads back **only** its own refinement. This is
  the regression test for the bug above; confirm it fails before the fix.
- `adzworld_decode_preference( adzworld_encode_preference( $p ) ) == $p` for a
  preference containing a category with a colon, a pipe and a comma.
- `sanitize_text_field( $encoded ) === $encoded`.

---

## 3. The homepage has no content

`templates/front-page.html` on main is `post-title` + `post-content`. That is
correct. The front-page post is empty, so the page renders a title and a
rotation and ~500px of nothing.

The content exists on branch `claude/design-display-library` and was never
merged to main:

```
pre-release-candidates/site-theme/adz-world-2026/patterns/hero.php
pre-release-candidates/site-theme/adz-world-2026/patterns/value-cards.php
pre-release-candidates/site-theme/adz-world-2026/patterns/rotation-demo.php
docs/site-pages/home.html
```

Cherry-pick those four files onto main, then:

```bash
wp post create docs/site-pages/home.html \
  --post_type=page --post_status=publish --post_title="adz.world" --porcelain
wp option update show_on_front page
wp option update page_on_front <ID>
```

`home.html` contains `[adz_rotation name="REPLACE_WITH_A_REAL_ROTATION_KEY"]`.
Replace it with a real key:

```bash
wp eval 'print_r( array_keys( adzworld_named_rotations() ) );'
```

Empty → **Adz → Starter packs → Display format showcase** creates them first.

With a hero on the page, the `wp:post-title` block at the top of
`front-page.html` duplicates it. Remove the post-title block from that template.

---

## 4. Seven checks were dropped from the gate

`.github/workflows/quality-gate.yml` is better than the script it replaced in
four ways I did not cover — it parses production JS and JSON, detects
`phpcs:ignore` / `@codingStandardsIgnore` suppressions, and runs
`wordpress/plugin-check-action`. Keep all of that.

It dropped seven checks that were in `.github/scripts/release-gate.sh` on
`claude/design-display-library`. Add them to `quality-gate.yml` as additional
steps:

| check | why it matters |
|---|---|
| no `, 'text_domain'` or `, 'eof'` as a gettext domain | notice item: 49 strings used the wrong domain |
| `readme.txt` contains `== External services ==` | **notice item 7** |
| each contacted provider host is disclosed with terms + privacy links — `pagead2.googlesyndication.com`, `securepubads.g.doubleclick.net`, `cdn.taboola.com`, `widgets.outbrain.com`, `hop.clickbank.net` | **notice item 7** |
| `Tags:` ≤ 5 | notice item |
| `Requires at least` and `Tested up to` match `^[0-9]+\.[0-9]+$` | notice items |
| `.distignore` exists and excludes `/tests` and `/review` | `tests/` calls `wp_set_current_user()` 16 times |

The external-services rows matter most: that was the closure notice's item 7,
and it is invisible to PHPCS. A clean PHPCS run says nothing about it.

Source to copy from:

```bash
git show claude/design-display-library:.github/scripts/release-gate.sh
```

Its `# --- 4. readme requirements ---` section is the disclosure check, already
written and passing.

---

## Still outstanding

`https://adz-world.com/terms/` and `/privacy/` both return **404**. Content is
on main at `docs/site-pages/terms.html` and `privacy.html`. Four placeholders —
`[LEGAL ENTITY]`, `[ADDRESS]`, `[JURISDICTION]`, `[CONTACT EMAIL]` — must come
from Dainis before publishing. This is the only item blocking the WordPress.org
review.

## Report

For each of the four items: what you changed, the command you ran, and its
actual output. If an item fails, say so with the output rather than reporting
it done.
