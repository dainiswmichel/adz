# Codex task: adz-world.com, round 2

Three things were measured on the live site just now. Values are given so you
can confirm them yourself rather than take my word for it.

---

## Defect 1 — `theme.json` was modified in deployment

The file on the server is **not** the file in the repository. Every colour in
the palette differs:

| slug | repository | live site |
|---|---|---|
| base | `#080e1e` | `#ffffff` |
| contrast | `#e9eefb` | `#182f3d` |
| surface | `#111b33` | `#f6f8f9` |
| accent-1 | `#4cc3e0` | `#3e6d86` |
| accent-2 | `#d9a441` | `#2f6b64` |
| accent-3 | `#8fa3d8` | `#3d4d68` |
| accent-4 | — | `#3a6270` |

The live accents (`#3e6d86`, `#2f6b64`, `#3d4d68`, `#3a6270`) are the four
hardcoded tone colours from the plugin's old `rotation.css`. A palette appears
to have been reconstructed from those rather than taken from the repository.

**Why this matters.** The site only *looks* dark because the theme switcher is
currently set to Midnight, which is stored in one browser's `localStorage`. The
theme's own default is now white. A first-time visitor, or anyone on another
device, gets a white site with the plugin's old teal — which is the state we
were trying to leave.

Confirm it yourself:

```bash
curl -s https://adz-world.com/ | grep -o '\-\-wp--preset--color--base: *#[0-9a-f]*' | head -1
# live now:  --wp--preset--color--base: #ffffff
# expected:  --wp--preset--color--base: #080e1e
```

### Fix

Deploy the file **verbatim**. Do not edit `theme.json`, `styles/*.json` or
`assets/variations.css` during deployment.

```bash
git fetch origin claude/design-display-library
git checkout claude/design-display-library
git log --oneline -1     # must be 9929f31 or later

rm -rf wp-content/themes/adz-world-2026
cp -r pre-release-candidates/site-theme/adz-world-2026 wp-content/themes/
wp theme activate adz-world-2026
wp cache flush && wp transient delete --all
```

Then prove the deployed file matches the repository — this check would have
caught the problem:

```bash
diff \
  <(git show origin/claude/design-display-library:pre-release-candidates/site-theme/adz-world-2026/theme.json) \
  wp-content/themes/adz-world-2026/theme.json \
  && echo "theme.json IDENTICAL"
```

If that prints anything other than `theme.json IDENTICAL`, stop and fix it
before continuing.

**If a light default is wanted**, that is Dainis' call to make, and it is not a
`theme.json` edit. Three variations already ship. Appearance → Editor → Styles
→ Browse styles → **Atelier** or **Hairline**, then Save. That sets the site
default for everyone while leaving all three switchable. Editing `theme.json`
to achieve it discards the design system and cannot be switched back.

---

## Defect 2 — the homepage has no content

Measured against the live HTML:

| content | occurrences |
|---|---|
| `waits to be invited` | 0 |
| `The reader asks` | 0 |
| `Untouched creatives` | 0 |
| `is-style-card` | 0 |
| `is-style-lead` | 0 |

The front page is the site title, the rotation panel, and roughly 500px of
empty ground before the footer. A visitor sees a form asking what they want to
buy, with nothing explaining what adz.world is or why they should answer.

### Fix

```bash
wp post create docs/site-pages/home.html \
  --post_type=page --post_status=publish --post_title="adz.world" --porcelain
# note the ID it returns
wp option update show_on_front page
wp option update page_on_front <THAT_ID>
```

Or build it in the editor from the three patterns, which should appear in the
inserter under **adz.world**: "adz.world hero", "adz.world three cards",
"adz.world live rotation". If they are missing from the inserter, the cause is
the header comment block in `patterns/*.php`; say so rather than rebuilding the
content by hand.

**One edit is required.** `home.html` contains a deliberate placeholder:

```
[adz_rotation name="REPLACE_WITH_A_REAL_ROTATION_KEY"]
```

A shortcode naming a rotation that does not exist renders a visible error, so
it is a placeholder rather than a guess. Find a real key:

```bash
wp eval 'print_r( array_keys( adzworld_named_rotations() ) );'
```

Empty array → create them first at **Adz → Starter packs → Display format
showcase**, then re-run and use one of the keys.

Once the homepage exists, the site title block at the top of the template
becomes redundant above the hero. Remove it from the template or let the hero
replace it — the giant "adz.world" heading and the hero headline should not
both be on the page.

---

## Defect 3 — `/terms/` and `/privacy/` still return 404

```bash
curl -o /dev/null -s -w '%{http_code}\n' https://adz-world.com/terms/     # 404
curl -o /dev/null -s -w '%{http_code}\n' https://adz-world.com/privacy/   # 404
```

**This is the only item blocking the WordPress.org review.** Those two URLs are
named in the plugin's `readme.txt` under `== External services ==`, and the
plugins team wrote: *"We will verify that the terms and privacy links exist and
they have the proper content. We will check those links in the next review."*

The content is written and committed at `docs/site-pages/terms.html` and
`docs/site-pages/privacy.html`.

**Ask Dainis for four values first and substitute them in both files.** They are
legal identifiers; do not invent them:

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

Both must return `200` and render as formatted content. Raw `<!-- wp:paragraph -->`
comments on screen mean the file was imported as plain text, not block markup.

Add both to the footer navigation.

---

## The rule that prevents all of this

**Deploy the repository's files unchanged.** If something in them looks wrong,
say so and leave it — do not substitute a judgement during deployment. A
modified file that works is worse than an unmodified one that fails, because
the failure is visible and the modification is not. This round cost a full
cycle to detect by diffing a stylesheet against a `curl`.

The three files that must never be hand-edited on the server:

- `theme.json` — the design system
- `styles/*.json` — the variations
- `assets/variations.css` — **generated** by `tools/build-variations.py` from
  `styles/*.json`; editing it is silently undone by the next regeneration

---

## Acceptance checklist

Run each. Report the actual output. Do not report a check you did not run.

| # | Check | Pass |
|---|---|---|
| 1 | `diff` of deployed vs repo `theme.json` | `IDENTICAL` |
| 2 | `curl \| grep '--wp--preset--color--base'` | `#080e1e` |
| 3 | Front page in a **private window** (no stored choice) | dark navy, cyan accent |
| 4 | Appearance → Editor → Styles → Browse styles | Midnight, Atelier, Hairline all listed |
| 5 | Switcher, bottom right | each option repaints; choice survives reload with no flash; "Site default" returns to Midnight |
| 6 | Front page content | hero, three cards, live rotation — no error notice |
| 7 | Adz → Rotation | shortcode is `[adz_rotation name="..."]`, no `display=` or `controls=` |
| 8 | Real provider slot in devtools | unit at the network's own width and height; no ancestor with `overflow: hidden` |
| 9 | Rotation in a sub-300px column | stacks, button matches input width, chips wrap inside the panel |
| 10 | `wp plugin check adz-world` | paste full output — this has still never been run |
| 11 | `debug.log` with `WP_DEBUG` on | no fatal, warning or notice from adz-world |
| 12 | `/terms/` and `/privacy/` | both `200`, formatted, linked in the footer |

## When you are done

- Commit your changes under `pre-release-candidates/` on your own branch. Do
  not force-push, and do not modify `pre-release-candidates/dwm-local/` or
  `pre-release-candidates/site-theme/`.
- Record the result in `docs/adz-remediation-collaboration-file.html` with a UTC
  timestamp and what each of the twelve checks returned.
- If a check fails, report it with the output. A half-finished deployment
  reported as complete costs more than one reported as blocked.
