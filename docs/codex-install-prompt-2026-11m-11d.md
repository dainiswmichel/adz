# Codex task: put the Claude build on the live site

## What this is

`adz-world.com` currently runs the **grok** candidate. This replaces it with the
Claude build and installs the new site theme. The work is already written,
committed and pushed. Your job is to deploy it, create two missing pages, and
report what you actually observe.

Everything below is on branch **`claude/design-display-library`**, not on `main`:

```
origin/claude/design-display-library   c39adbf
  c39adbf  Make the rotation theme-independent; add the adz.world 2026 site theme
  7012482  Promote dwm-local to production and move rotation parameters off the shortcode
```

Two directories matter:

| Path | Goes to |
|---|---|
| `pre-release-candidates/dwm-local/` | `wp-content/plugins/adz-world/` |
| `pre-release-candidates/site-theme/adz-world-2026/` | `wp-content/themes/adz-world-2026/` |

## Do this first — it is blocking the WordPress.org review

`https://adz-world.com/terms/` and `https://adz-world.com/privacy/` both return
**404**. Those two URLs are named in the plugin's `readme.txt` under
`== External services ==`, and the plugins team wrote *"We will verify that the
terms and privacy links exist and they have the proper content. We will check
those links in the next review."*

Create both as published pages at exactly those slugs, with real content
covering what the readme says is sent: for the adz-world.com network
connection, the publisher ID, access tokens, referral code and visitor IP
address; the adz title, content and categories on publish or delete; and the
site URL, title and administrator email on registration. Do not use a
placeholder or a generated privacy policy stub — the reviewer reads them.

## Install

```bash
git fetch origin claude/design-display-library
git checkout claude/design-display-library
```

Plugin — replace whatever is installed now:

```bash
# Back up the current (grok) install before removing it.
wp plugin deactivate adz-world
mv wp-content/plugins/adz-world wp-content/plugins/adz-world.grok.$(date +%Y%m%d)
cp -r pre-release-candidates/dwm-local wp-content/plugins/adz-world
rm -rf wp-content/plugins/adz-world/tests wp-content/plugins/adz-world/review
wp plugin activate adz-world
```

`tests/` and `review/` are listed in `.distignore` and must not be on a live
site: `tests/` calls `wp_set_current_user()` sixteen times.

Theme:

```bash
cp -r pre-release-candidates/site-theme/adz-world-2026 wp-content/themes/
wp theme activate adz-world-2026
```

It is a child of Twenty Twenty-Five, so Twenty Twenty-Five must stay installed.

## Verify, and report what you actually see

Activate with `WP_DEBUG` and `WP_DEBUG_LOG` on, then check each of these and
say which passed, which failed, and paste the output. Do not report a check you
did not run.

1. **Activation is clean.** No fatal, warning or notice in `debug.log`, and no
   output printed during activation.
2. **The three style variations appear** in Appearance → Editor → Styles →
   Browse styles: **Midnight**, **Atelier**, **Hairline**. Switch between them
   and confirm the whole page repaints, including the rotation panel.
   Screenshot each.
3. **The shortcode is name-only.** Adz → Rotation should now print
   `[adz_rotation name="..."]` with no `display` or `controls` attribute. The
   display format, creative size, navigation and region name are settings on
   that screen. Confirm changing the format there changes the front end.
4. **A creative is not clipped or resized.** Place a rotation holding a real
   provider slot, then in devtools confirm the injected unit has the width and
   height the network set, and that no ancestor has `overflow: hidden`. This is
   the point of the change — a resized or clipped unit is a policy violation.
5. **Narrow container.** Put the same rotation in a sidebar or a column under
   300px. It should stack, the button should match the input width, and the
   category chips should wrap inside the panel.
6. **Legal pages resolve.** `curl -I` both URLs, expect `200`.

## Site content

The site is still a default install: the title is "adz dwm release test", the
footer menu is eight `#` placeholder links, and the WordPress credit is in the
footer. Set a real title and tagline, remove the placeholder menu items, and
remove the Sample Page. Leave the homepage layout alone for now — Dainis is
choosing between the three variations first.

## When you are done

- Commit anything you changed under
  `https://github.com/dainiswmichel/adz/tree/main/pre-release-candidates`,
  on your own branch. Do not force-push and do not touch
  `pre-release-candidates/dwm-local/` or
  `pre-release-candidates/site-theme/` — those are the Claude candidate.
- Add your result to `docs/adz-remediation-collaboration-file.html` with a UTC
  timestamp: what you installed, what each check above actually returned, and
  anything that failed.
- If a check fails, say so plainly with the output. Do not report a pass you
  did not observe.

## What has not been verified yet, and needs to be

Stated so you do not inherit a claim as fact:

- **Plugin Check has never run** on this tree. Run `wp plugin check adz-world`
  and paste the result.
- **The theme has never been installed on a WordPress site.** The three
  variations were rendered by generating the custom properties `theme.json`
  emits, not by WordPress itself. If a variation does not appear in the Site
  Editor, the likely cause is the `styles/*.json` schema or the child theme
  header in `style.css`.
- The plugin's PHP passes `php -l` and PHPCS with the WordPress standard at
  exit 0, and `release-gate.sh` passes. Those were run; the two above were not.
