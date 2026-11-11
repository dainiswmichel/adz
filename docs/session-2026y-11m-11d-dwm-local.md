# Session 2026y-11m-11d — dwm-local is the release

Review ID: `GUIDELINES ❗ACT adz-world/dainismichel/29Sep26/T1 29Sep26/4.3`
Public listing: https://wordpress.org/plugins/adz-world/ — closed 29 September 2026, shown as version 1.0.8.
The public “Guideline Violation” label follows 60 days later: 28 November 2026.

## Trees

| Path | Role today |
|---|---|
| `pre-release-candidates/dwm-local/` | Production release directory. Populated from the exact port-8097 winner, with development-only `tests/`, `review/`, and macOS metadata excluded. |
| `167.172.241.113:8096`, `:8097`, `:8098` | Test burners. They stay test burners. |
| `pre-release-candidates/grok-4-6/` | The tree where the 29 September line items were edited. Version header 1.1.0. Not installed on a burner. Not copied into `dwm-local`. |
| `adz-world-wordpress-codebase/` | Older release copy. `readme.txt` still says Requires at least 4.9.6 and Tested up to 4.9.6. |
| `adz-world.com` | Apex still resolves to Namecheap parking `192.64.119.131`. SSH host `adz-world` does not resolve from this machine. No install was made. |

`dwm-local` is the production tree. Burner hashes are not a release. The exact
current tree has now passed WPCS, the official Plugin Check, a fresh
WordPress 7.1.4 install, authenticated rendering of all eight administrator
tabs, and clean deactivate/reactivate testing with `WP_DEBUG` enabled.

## Where the update goes

The plugin page has no upload control. Download, Development, and Advanced View are not the update. A closed plugin still accepts Subversion.

Repository: `https://plugins.svn.wordpress.org/adz-world/`

```
svn checkout https://plugins.svn.wordpress.org/adz-world/ adz-world-svn --username Dainismichel
```

Use the dedicated SVN password from the WordPress.org account profile. Put the plugin files in `trunk/`. `Version` in `adz-world.php` and `Stable tag` in `readme.txt` are the same number, higher than the public `1.0.8`. Commit `trunk`, then copy `trunk` to `tags/<that version>` and commit the tag. Reply to the 29 September email after that commit. The volunteers read the tag. They do not read a zip attached to the plugin webpage.

Do not run that checkout or commit until `adz-world.com` is serving the `dwm-local` plugin and you say to bump the version. Current stable WordPress is 7.1.4 (28 October 2026). `Tested up to` is `7.1`. WordPress 7.2 is beta, scheduled 8 December 2026, and is not a legal `Tested up to` value.

## Shortcode

The page shortcode names a saved rotation. Display, controls, size, and the slot list are stored on that rotation in the Rotation tab.

```
[adz_rotation name="adzworld_showcase"]
```

A rotation cycles and repeats. Five slots are 1, 2, 3, 4, 5, then 1 again. One slot repeated is 1, 1, 1. Slot 1 can be the invitation. Slot 2 can be the connected AdSense unit. Slot 3 can be the ClickBank HopLink. Visitor text is not appended to that HopLink. Those choices are fields on the slot, not attributes on the shortcode.

The earlier zip
`b242e6fd6e414d3b331c2c253c4f53a1b88c8c831b0e3a8837859e8e47251679`
predated the shortcode change and is superseded. The current exact artifact is
`/private/tmp/adz-world-dwm-local-1.0.9-final.zip`, SHA-256
`c14015bfa4f5da53b2d50bd6b7c3b8f31fcc67b07f37febfeae351ac137e8b5b`.
It contains 72 files. That artifact was installed on the isolated port-8099
WordPress site and passed the official Plugin Check with no errors.

## Closure items, as of this session

The email says the listed technical items are not the unnamed guideline that closed the plugin. Asking `plugins@wordpress.org` which guideline is still open.

| Item | grok-4-6 | dwm-local | 8097 |
|---|---|---|---|
| Ajax via `admin_url()` | present | pass: no hard-coded endpoint; localized `admin_url()` is used | pass |
| Requires at least 6.2 | readme says 6.2 | header and readme both 6.2 | pass |
| Tested up to 7.1 | readme says 7.1 | readme says 7.1; clean runtime uses WordPress 7.1.4 | pass |
| Five tags | reduced in that readme | exactly five | pass |
| `screen_icon()` removed | removed | absent | pass |
| Chosen 1.8.2 removed | removed | absent | pass |
| External service disclosed, off by default | readme section exists | provider-by-provider disclosure added; adz-world.com Terms and Privacy URLs still require a live production site | blocked by production DNS/site |
| Shortcode output escaped | edited there | WPCS and Plugin Check clean; runtime contracts passed on identical code | pass |
| Text domain `adz-world` | edited there | WPCS text-domain check clean | pass |
| `rotations_id` cannot name an arbitrary option | `adz_validate_rotation_id()` | configured-rotation allowlist and regression pass | pass |
| `ABSPATH` guard | added on the files they named | all 52 shipped PHP files guarded | pass |
| `role_exists()` fatal | function removed | absent; clean activation passed | pass |
| Plugin Check transcript | none | Plugin Check 2.2.0: `Success: Checks complete. No errors found.` | pass |
| Clean install, `WP_DEBUG` true | not done | exact 72-file ZIP installed on fresh WordPress at port 8099; all eight tabs and frontend HTTP 200; deactivate/reactivate clean; no debug log created | pass |
| SVN tag and reply to the email | not done | not done | not done |

Absence of an old line in the 8097 plugin is not a Plugin Check pass. The
official Plugin Check was therefore run on the exact current zip built from
`dwm-local`; it returned `Success: Checks complete. No errors found.`

## Dwm-local release evidence created this session

The production workflow and version-check script now deploy from
`pre-release-candidates/dwm-local/`. The deployment workflow fails closed on
PHP lint, JavaScript parsing, full WPCS, forbidden inline styling, diagnostic
suppressions, and the official `wordpress/plugin-check-action` before the SVN
deployment step.

The exact current test artifact is
`/private/tmp/adz-world-dwm-local-1.0.9-final.zip`, SHA-256
`c14015bfa4f5da53b2d50bd6b7c3b8f31fcc67b07f37febfeae351ac137e8b5b`.
It contains 72 files and no `tests/`, `review/`, `.DS_Store`, or AppleDouble
files. The fresh port-8099 install has the same aggregate file-manifest hash,
`fe1695069e03899d8d31e8626b305b7f9236e18d886a0f0871aa05fbc34f6bbe`.
The current artifact passed Plugin Check 2.2.0 with no errors, all eight
authenticated administrator tabs and the front end returned HTTP 200, and a
deactivate/reactivate cycle created no debug log. This is test evidence, not
the SVN release.

`adz-world.com` remains blocked before installation: DNS still points at the
Namecheap parking address, no `adz-world` SSH host exists, and no DigitalOcean
provisioning client or authenticated browser connection is available in this
session. The next required infrastructure fact is the production server IP (or
authorization/access to create the planned droplet); DNS can then be pointed to
that host and the Terms and Privacy pages published and verified.
