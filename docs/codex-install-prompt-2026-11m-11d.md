# Codex task: finish the adz-world.com install

## Where things stand

The theme **is** installed and active — the "Theme" switcher is visible at the
bottom right of the live site, which only renders when the child theme's
`functions.php` is running. The plugin is serving; the rotation panel appears.

What is wrong is not your install. Two defects were in the committed theme and
are now fixed:

1. **The theme carried no default appearance.** Every colour lived in
   `styles/*.json`, and WordPress emits only the variation an administrator has
   selected. With none selected the child theme contributed no palette, so the
   site fell through to Twenty Twenty-Five — white ground, yellow button. The
   yellow "Show me" button was not a plugin bug; it was the plugin correctly
   inheriting Twenty Twenty-Five's `accent-1`, the only accent on the page.
2. **No page content was ever shipped.** The homepage had a site title and a
   rotation and nothing else, because the designs existed only in a test
   harness.

`theme.json` now carries a full default palette and token set, and the content
ships as block patterns plus a ready-to-publish file. Pull and re-deploy.

## Step 1 — pull

```bash
git fetch origin claude/design-display-library
git checkout claude/design-display-library
git log --oneline -1    # expect 245e6fd or later
```

If `git log` shows an older commit, stop — you are on a stale checkout and
everything below will reproduce the same result.

## Step 2 — re-copy the theme

Only the theme changed. The plugin at `pre-release-candidates/dwm-local/` is
unchanged; leave it alone if you already installed it.

```bash
rm -rf wp-content/themes/adz-world-2026
cp -r pre-release-candidates/site-theme/adz-world-2026 wp-content/themes/
wp theme activate adz-world-2026
```

**Verify before continuing.** WordPress caches merged `theme.json` output:

```bash
wp cache flush
wp transient delete --all
```

Then load the front page and check:

```bash
curl -s https://adz-world.com/ | grep -o '\-\-wp--preset--color--base: *#[0-9a-f]*' | head -1
```

- **Expected:** `--wp--preset--color--base: #080e1e`
- If it returns nothing or a different colour, the merged `theme.json` is
  stale: flush again, and confirm `wp theme list` shows `adz-world-2026` as
  active with `twentytwentyfive` still installed (it is the parent; removing it
  breaks the child).

The page should now be dark navy with a cyan accent, not white with yellow.

## Step 3 — build the homepage

The content ships two ways. Either is fine.

**From WP-CLI**, fastest:

```bash
wp post create docs/site-pages/home.html \
  --post_type=page --post_status=publish --post_title="adz.world"
wp option update show_on_front page
wp option update page_on_front <ID returned above>
```

**Or from the editor**, if you want to see them: create a page and insert the
three patterns from the inserter under **adz.world** — "adz.world hero",
"adz.world three cards", "adz.world live rotation".

### The one thing you must edit

`home.html` contains a deliberate placeholder:

```
[adz_rotation name="REPLACE_WITH_A_REAL_ROTATION_KEY"]
```

It is a placeholder rather than a guess because a shortcode naming a rotation
that does not exist renders a visible error, which is worse on a front page.
Replace it with a rotation that actually exists:

```bash
wp eval 'print_r( array_keys( adzworld_named_rotations() ) );'
```

If that returns an empty array, create one first: **Adz → Starter packs →
Display format showcase**, which builds one rotation per display format, then
re-run the command and pick a key from the list.

## Step 4 — terms and privacy

Both URLs currently return **404**, and they are named in the plugin's
`readme.txt` under `== External services ==`. The plugins team wrote: *"We will
verify that the terms and privacy links exist and they have the proper content.
We will check those links in the next review."* This is the one item blocking
the WordPress.org review.

The content is written and committed:

```
docs/site-pages/terms.html
docs/site-pages/privacy.html
```

**Ask Dainis for four values and replace them in both files before publishing.**
Do not invent them — they are legal identifiers:

| Placeholder | What it is |
|---|---|
| `[LEGAL ENTITY]` | the company or person operating the service |
| `[ADDRESS]` | its registered address |
| `[JURISDICTION]` | the governing law for the terms |
| `[CONTACT EMAIL]` | the address for privacy requests |

Then:

```bash
wp post create docs/site-pages/terms.html \
  --post_type=page --post_status=publish --post_title="Terms of Service" --post_name=terms
wp post create docs/site-pages/privacy.html \
  --post_type=page --post_status=publish --post_title="Privacy Policy" --post_name=privacy

curl -o /dev/null -s -w '%{http_code}\n' https://adz-world.com/terms/
curl -o /dev/null -s -w '%{http_code}\n' https://adz-world.com/privacy/
```

Both must return `200`, and the pages must render as formatted content, not as
raw `<!-- wp:paragraph -->` comments. Raw comments mean the file was imported
as plain text rather than as block markup.

## Step 5 — tidy the default install

The site is still partly stock: the footer menu is eight `#` placeholder links,
and there is a Sample Page. Remove both, set a real tagline, and remove the
"Designed with WordPress" footer credit.

## Acceptance checklist

Run each and report the actual result. Do not report a check you did not run.

| # | Check | Pass looks like |
|---|---|---|
| 1 | `wp theme list` | `adz-world-2026` active, `twentytwentyfive` installed |
| 2 | Front page colour | dark navy ground, cyan accents — not white and yellow |
| 3 | Appearance → Editor → Styles → Browse styles | Midnight, Atelier, Hairline all listed |
| 4 | Front-end switcher | pill bottom right; each option repaints the page; the choice survives a reload with **no flash** of the previous palette; "Site default" returns to Midnight |
| 5 | Homepage | hero, three cards, and a live rotation that renders rather than showing an error |
| 6 | Adz → Rotation | shortcode shown is `[adz_rotation name="..."]` with **no** `display=` or `controls=` |
| 7 | Creative integrity | with a real provider slot placed, devtools shows the injected unit at the network's own width and height, and no ancestor has `overflow: hidden` |
| 8 | Narrow container | same rotation in a sub-300px column: stacks, button matches input width, chips wrap inside the panel |
| 9 | `wp plugin check adz-world` | paste the full output |
| 10 | `debug.log` with `WP_DEBUG` on | no fatal, warning or notice from adz-world |
| 11 | `/terms/` and `/privacy/` | both `200`, both rendering formatted content |

## When you are done

- Commit anything you changed under
  `https://github.com/dainiswmichel/adz/tree/main/pre-release-candidates`, on
  your own branch. Do not force-push, and do not modify
  `pre-release-candidates/dwm-local/` or
  `pre-release-candidates/site-theme/` — those are the Claude candidate.
- Record the result in `docs/adz-remediation-collaboration-file.html` with a UTC
  timestamp: what you installed and what each of the eleven checks returned.
- **If a check fails, say so with the output.** A half-finished install that is
  reported as done costs more than one reported as blocked.

## Still unverified, so do not inherit it as fact

- **Plugin Check has never run** on this plugin tree. Check 9 is its first run.
- **No part of the theme has been exercised inside WordPress.** The default
  palette, the three variations and the three patterns were all verified by
  generating the custom properties `theme.json` emits and rendering them in a
  browser — not by WordPress itself. If a variation is missing from the Site
  Editor, suspect the `styles/*.json` schema or the `Template:` line in
  `style.css`. If a pattern is missing from the inserter, suspect the header
  comment block in `patterns/*.php`.
- The front-end switcher **was** driven end to end in a headless browser: every
  option applied, the choice persisted, and the attribute was set before paint
  after a reload. That was against a generated harness, not WordPress.
- `assets/variations.css` is generated by `tools/build-variations.py` from
  `styles/*.json`. If you change a variation, re-run that script. Do not
  hand-edit the generated file.
