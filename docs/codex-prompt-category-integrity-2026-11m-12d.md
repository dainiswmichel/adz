# Codex task: fix the category integrity bug in dwm-local

Grok attempted this and correctly stopped at a guard. Its report was accurate:
on its disk `tools/test-refinement-bleed.php` did not exist and
`claude-code-web/includes/` held a different, older set of files. Nothing was
changed, which was the right outcome.

**The cause was a stale clone, not a bad premise.** Everything this task needs
is on `origin/main`:

```
0a4a883  Add a runnable test that a refinement cannot leak between categories
60fe879  Replace claude-code-web with production plus the pair-collision fix
```

A working copy from before `60fe879` sees the previous generation of
`claude-code-web` — `adz-ledger.php`, `adz-money.php`, `adz-payouts.php` and
so on, dated 26 October. That is what Grok saw. Step 0 below exists so this
cannot happen again.

Read all of this before running anything. Every step states its expected
output. If any step's output does not match, **stop and report** rather than
continuing or improvising.

---

## The bug, in one line

A refinement typed under one category leaks into another, so a visitor who
asks for a *light fabric couch* and an *electric car* can be shown adz for an
electric couch.

**Cause.** Pairs are stored as `category--refinement` and read back by
matching the prefix `category . '--'`. `adzworld_normalize_category()` only
collapses whitespace, so a category may itself contain `--`. Category `a` with
refinement `b--c` and category `a--b` with refinement `c` then store the
identical string `a--b--c`, and the prefix `a--` matches both.

The fix already exists on `main` in `pre-release-candidates/claude-code-web`,
which passes the test 14/14. Your job is to carry it into
`pre-release-candidates/dwm-local`, the tree that ships to WordPress.org,
which still fails.

---

## Step 0 — sync, and prove you are synced

This is its own step. Do not skip to the `ls`.

```bash
cd <repo root>
git checkout main
git fetch origin main
git reset --hard origin/main      # only if you have no local work on main
git rev-parse --short HEAD
```

Then assert the two commits are present:

```bash
git merge-base --is-ancestor 0a4a883 HEAD && echo "SYNCED: has the test"
git merge-base --is-ancestor 60fe879 HEAD && echo "SYNCED: has the fixed claude-code-web"
```

Expected: both lines print. If either does not, **stop** — your clone is
behind and nothing below will work.

Confirm the test file is on disk:

```bash
ls -l tools/test-refinement-bleed.php
```

Expected: the file exists. If it does not, you are not synced; go back.

## Step 1 — prove the bug is real before changing anything

```bash
ADZ_SRC=pre-release-candidates/dwm-local php tools/test-refinement-bleed.php
```

Expected output:

```
  FAIL  category 'a' does NOT receive category 'a--b' refinement
        a = zebra b--walrus   <-- 'walrus' belongs to 'a--b'
13 passed, 1 failed
```

Exit status 1. If you do not see that failure, stop and report — the starting
state is not what this task assumes.

For contrast, the tree you are copying from should already pass:

```bash
ADZ_SRC=pre-release-candidates/claude-code-web php tools/test-refinement-bleed.php
```

Expected: `14 passed, 0 failed`, exit 0.

## Step 2 — the tripwire

```bash
diff -rq pre-release-candidates/dwm-local pre-release-candidates/claude-code-web
```

Expected: **exactly four lines**, naming only these files as differing:

```
includes/adz-preference-ui.php
includes/adz-preferences.php
includes/adzworld-affiliates.php
includes/adzworld-feedback.php
```

**If the output has more than four lines, or names any other file, STOP.**
That means the trees diverged after this was written and copying would
overwrite someone else's work. Report the extra lines and use Appendix A
instead, which does the same change by exact text replacement and touches
nothing else.

## Step 3 — copy exactly those four files

```bash
for f in adz-preference-ui.php adz-preferences.php adzworld-affiliates.php adzworld-feedback.php; do
  cp "pre-release-candidates/claude-code-web/includes/$f" \
     "pre-release-candidates/dwm-local/includes/$f"
done
```

Do not edit the files by hand. Do not copy any other file.

## Step 4 — confirm the copy

```bash
diff -rq pre-release-candidates/dwm-local pre-release-candidates/claude-code-web && echo "TREES IDENTICAL"
```

Expected: `TREES IDENTICAL` and nothing else.

## Step 5 — the test must now pass

```bash
ADZ_SRC=pre-release-candidates/dwm-local php tools/test-refinement-bleed.php
```

Expected:

```
14 passed, 0 failed
```

Exit status 0. If anything still fails, stop and paste the whole output.

## Step 6 — lint and standards

```bash
find pre-release-candidates/dwm-local -name '*.php' -print0 | xargs -0 -n1 php -l | grep -v '^No syntax errors'
```

Expected: **no output at all**.

```bash
phpcs --standard=WordPress --extensions=php --report=summary -q pre-release-candidates/dwm-local/
echo "exit=$?"
```

Expected: `exit=0` with no findings table. If phpcs is not installed, say so
rather than skipping it silently.

## Step 7 — commit and push

```bash
git add pre-release-candidates/dwm-local
git commit -F- <<'EOF'
Port the category integrity fix into dwm-local

A refinement stored under one category leaked into another. Pairs were joined
as category--refinement and read by prefix match, while
adzworld_normalize_category() permits -- inside a category, so category 'a'
with refinement 'b--c' and category 'a--b' with refinement 'c' both stored
'a--b--c' and the prefix 'a--' matched both.

Copies the four corrected files verbatim from
pre-release-candidates/claude-code-web. Both halves of a pair are now escaped
with the hyphen escaped inside them, so neither half can contain the
separator. The escaped form stays inside the characters
sanitize_text_field() leaves untouched, which the stored preference depends
on, and the split still reads pairs written by earlier versions.

Verification, output read:

  ADZ_SRC=pre-release-candidates/dwm-local php tools/test-refinement-bleed.php
    before:  13 passed, 1 failed
    after:   14 passed, 0 failed

  php -l over every PHP file: no errors
  phpcs --standard=WordPress: exit 0

Not run: Plugin Check, and activation inside WordPress.
EOF
git fetch origin main
git push origin main
```

If the push is rejected, another agent pushed first. `git fetch origin main`,
then rebase or merge, then re-run steps 5 and 6 before pushing again. Do not
force-push.

---

## Do not do any of this

- Do not change `adzworld_normalize_category()` to strip `--`. Categories are
  the visitor's own words and are not ours to rewrite.
- Do not change the separator from `--` to something else. Preferences already
  stored in visitors' browsers use `--`, and the split still has to read them.
- Do not touch `pre-release-candidates/claude-code-web`.
- Do not touch the theme, the live site, or any other candidate.
- Do not edit `tools/test-refinement-bleed.php`. If it fails, the code is
  wrong, not the test. Changing a test to make it pass is the one thing that
  must never happen here.
- Do not bump the version number. That is Dainis' decision.
- Do not report a step as done without pasting its actual output.

---

## Appendix A — the manual edits, only if step 1 showed more than four files

Twelve exact replacements. Each `OLD` occurs exactly once in its file; replace
it with `NEW`. Do not reformat anything else.

### A1 — `includes/adz-preferences.php`, add five functions

Insert these immediately **before** the line
`function adzworld_preference_chips( $pref ) {` and **after** that function's
`/** ... */` doc comment — i.e. the doc comment must stay directly attached to
`adzworld_preference_chips`, with these new functions and their own doc
comments placed above it.

```php
/**
 * Join a category and a refinement into one stored pair.
 *
 * @param string $category   Category.
 * @param string $refinement Refinement.
 * @return string Stored pair.
 */
function adzworld_pair_join( $category, $refinement ) {
	return adzworld_pair_escape( $category ) . '--' . adzworld_pair_escape( $refinement );
}

/**
 * Split a stored pair back into its category and refinement.
 *
 * Accepts the unescaped form written by earlier versions.
 *
 * @param string $pair Stored pair.
 * @return array{0:string,1:string} Category and refinement.
 */
function adzworld_pair_split( $pair ) {
	$parts      = explode( '--', (string) $pair, 2 );
	$category   = isset( $parts[0] ) ? $parts[0] : '';
	$refinement = isset( $parts[1] ) ? $parts[1] : '';

	return array( adzworld_pair_unescape( $category ), adzworld_pair_unescape( $refinement ) );
}

/**
 * The prefix a stored pair for this category begins with.
 *
 * @param string $category Category.
 * @return string Prefix including the separator.
 */
function adzworld_pair_prefix( $category ) {
	return adzworld_pair_escape( $category ) . '--';
}

/**
 * Escape one half of a pair so it cannot contain the separator.
 *
 * The hyphen is escaped after rawurlencode(), which leaves it alone.
 *
 * @param string $value Raw value.
 * @return string Escaped value.
 */
function adzworld_pair_escape( $value ) {
	return str_replace( '-', '%2D', rawurlencode( (string) $value ) );
}

/**
 * Reverse adzworld_pair_escape(). Safe on an unescaped legacy value.
 *
 * @param string $value Escaped value.
 * @return string Raw value.
 */
function adzworld_pair_unescape( $value ) {
	return rawurldecode( (string) $value );
}
```

### A2 — `includes/adz-preferences.php`

OLD
```php
		$parts = explode( '--', $stored, 2 );
		if ( 2 === count( $parts ) && $category === $parts[0] ) {
```
NEW
```php
		$parts = adzworld_pair_split( $stored );
		if ( '' !== $parts[1] && $category === $parts[0] ) {
```

OLD
```php
				return 0 !== strpos( $stored, $category . '--' );
```
NEW
```php
				return 0 !== strpos( $stored, adzworld_pair_prefix( $category ) );
```

OLD
```php
		$pref['category_slots'][] = $category . '--' . $count;
```
NEW
```php
		$pref['category_slots'][] = adzworld_pair_join( $category, (string) $count );
```

### A3 — `includes/adz-preference-ui.php`

OLD
```php
			$parts = explode( '--', sanitize_text_field( (string) $stored ), 2 );
			if ( 2 !== count( $parts ) || ! in_array( $parts[0], $known, true ) ) {
```
NEW
```php
			$parts = adzworld_pair_split( sanitize_text_field( (string) $stored ) );
			if ( '' === $parts[1] || ! in_array( $parts[0], $known, true ) ) {
```

OLD
```php
				$clean['category_slots'][] = $parts[0] . '--' . $count;
```
NEW
```php
				$clean['category_slots'][] = adzworld_pair_join( $parts[0], (string) $count );
```

OLD
```php
	$prefix      = $active . '--';
```
NEW
```php
	$prefix      = adzworld_pair_prefix( $active );
```

OLD
```php
		$refinement = trim( mb_substr( str_replace( '--', '-', $refinement ), 0, 120 ) );
		$parts      = explode( '--', $old, 2 );

		if ( ! in_array( $old, $pref['category_refinements'], true ) || 2 !== count( $parts ) || '' === $refinement ) {
```
NEW
```php
		$refinement = trim( mb_substr( $refinement, 0, 120 ) );
		$parts      = adzworld_pair_split( $old );

		if ( ! in_array( $old, $pref['category_refinements'], true ) || '' === $parts[0] || '' === $refinement ) {
```

OLD
```php
		$replacement = mb_substr( $parts[0] . '--' . $refinement, 0, 160 );
```
NEW
```php
		$replacement = mb_substr( adzworld_pair_join( $parts[0], $refinement ), 0, 240 );
```

OLD
```php
			$pair = mb_substr( $category . '--' . $text, 0, 160 );
```
NEW
```php
			if ( '' === trim( $text ) ) {
				wp_send_json_error( array( 'message' => __( 'Enter a refinement to keep improving these adz.', 'adz-world' ) ), 400 );
			}

			$pair = mb_substr( adzworld_pair_join( $category, $text ), 0, 240 );
```

OLD
```php
			$pair   = mb_substr( $active . '--' . $text, 0, 160 );
```
NEW
```php
			if ( '' === trim( $text ) ) {
				wp_send_json_error( array( 'message' => __( 'Enter a refinement to keep improving these adz.', 'adz-world' ) ), 400 );
			}

			$pair = mb_substr( adzworld_pair_join( $active, $text ), 0, 240 );
```

OLD
```php
				<li class="adz-panel__chip adz-panel__chip--<?php echo esc_attr( $chip['facet'] ); ?>">
```
NEW
```php
				<li
					class="adz-panel__chip adz-panel__chip--<?php echo esc_attr( $chip['facet'] ); ?>"
					<?php if ( ! empty( $chip['category'] ) ) : ?>
						data-adzworld-category="<?php echo esc_attr( $chip['category'] ); ?>"
					<?php endif; ?>
				>
```

### A4 — `includes/adzworld-feedback.php`

OLD
```php
	$prefix = $category . '--';
```
NEW
```php
	$prefix = adzworld_pair_prefix( $category );
```

### A5 — `includes/adzworld-affiliates.php`

OLD
```php
		$split = explode( '--', (string) $stored, 2 );
```
NEW
```php
		$split = adzworld_pair_split( (string) $stored );
```

### A6 — `includes/adz-preferences.php`, the chip builder

OLD
```php
	foreach ( $pref['category_refinements'] as $category_refinement ) {
		$parts   = explode( '--', $category_refinement, 2 );
		$label   = isset( $parts[1] ) ? $parts[1] : $parts[0];
		$chips[] = array(
			'facet' => 'category_refinements',
			'value' => $category_refinement,
			'label' => $label,
		);
	}
```
NEW
```php
	foreach ( $pref['category_refinements'] as $category_refinement ) {
		list( $pair_category, $pair_refinement ) = adzworld_pair_split( $category_refinement );

		if ( '' === trim( $pair_refinement ) ) {
			continue;
		}

		$chips[] = array(
			'facet'    => 'category_refinements',
			'value'    => $category_refinement,
			'label'    => $pair_refinement,
			'category' => $pair_category,
		);
	}
```

After Appendix A, go back and run steps 3 to 6.
