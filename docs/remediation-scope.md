# Remediation scope — wordpress.org closure, 29 September 2026

Source: email from `plugins@wordpress.org`, Review ID
`GUIDELINES ❗ACT adz-world/dainismichel/29Sep26/T1 29Sep26/4.3`

Plugin: https://wordpress.org/plugins/adz-world/ — **closed**
Deadline: 60 days from 29 September 2026 → **28 November 2026**

Their words are quoted. Everything under "Remediation" is ours.

---

## 0. The closure reason — UNKNOWN

> "We would like to inform you that your plugin ... Has been closed as it has
> been found to be in violation of the directory guidelines"

And then, heading the entire list of findings:

> "**Although they are not directly related to the guideline violation**, our
> algorithms detected some technical issues that you should check."

**The email never names which guideline was breached.** Every item below is
explicitly excluded from being the reason.

**Remediation:** cannot be scoped from the email. Requires a reply to
`plugins@wordpress.org` asking which guideline. Everything else in this
document could be completed and the plugin remain closed.

**Status:** OPEN — blocked on the question being asked.

---

## 1. The link to the ajax endpoint may not work in some configurations

> "When you link to the Ajax endpoint, you cannot assume that it's always
> located at `wp-admin/admin-ajax.php`. There are different configurations in
> which that won't work. This means you can't link it statically, you have to
> use a function to determine its location, for example:
> `admin_url( 'admin-ajax.php' );`"

Their examples: `adz-helper.php` lines 33, 102, 160, 209;
`adz-shortcode.php` lines 91, 133, 178, 223 — "out of a total of 9 incidences."

**Remediation:** all 9 replaced with
`<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>`, including the ninth
in `adz-templates/template1.php` which their list did not reach.

**Status:** DONE.

---

## 2. Requires at least value has issues

> "ERROR: Requires at least at readme.txt: '4.9.6' is expected to be the lowest
> WordPress version that the plugin will work on. Example: Requires at least: 6.2"
> "Please, include only the major WordPress version, as the minor version is ignored."

**Remediation:** `Requires at least: 6.2`.

**Status:** DONE.

---

## 3. Tested Up To Value is Out of Date, Invalid, or Missing

> "ERROR: Tested up to: '4.9.6' needs to be just the major version number.
> Example: Tested up to: 7.1"
> "This means your plugin will not show up in searches, as we require plugins to
> be compatible and documented as tested up to the most recent version."

**Remediation:** `Tested up to: 7.1`.

**Status:** DONE in the file. **NOT VERIFIED** — this is a claim that the plugin
was tested on WordPress 7.1. It has not been. See §14.

---

## 4. Too many tags in your readme file

> "The tags list of your readme file accepts a maximum of 5 tags."
> "These tags will be ignored: serve adz, serve ads, earn affiliate income,
> create promotions, restrict access to content, require ad views"

**Remediation:** 11 tags reduced to 5 —
`advertising, consent, privacy, ads, ad management`.

**Status:** DONE.

---

## 5. Using Deprecated Code/Functions

> "`includes/adz-cpt-ads.php:250 screen_icon();`
> ↳ screen_icon() has been deprecated since WordPress version 3.8.0."

**Remediation:** call deleted. The function no longer exists in WordPress.

**Status:** DONE.

---

## 6. Libraries that are no longer maintained are not permitted

> "We no longer accept using any library that is no longer supported or
> maintained by their developers, as they pose a significant security risk."
> "`js/chosen.jquery.js:1 💾 Version 1.8.2`"

**Remediation:** `js/chosen.jquery.js`, `css/chosen.css` and
`css/chosen-sprite.png` deleted. `vendor/eof/js/eof-custom.js` no-ops its
`.chosen()` calls when the library is absent, so the rotation multi-selects fall
back to the browser's own control.

**Status:** DONE. Cosmetic change to the settings screens.

---

## 7. Undocumented use of a 3rd Party / external service

> "When your plugin reach out to external services, you must disclose it. This
> is true even if you are the one providing that service."
> "✨ The plugin sends visitor IP addresses to the adz.world service to check
> visitor login status, but the readme lacks adequate service disclosure and
> Terms or Privacy Policy links."
> "🔗 Please verify that the terms and privacy links exist and they have the
> proper content. **We will check those links in the next review.**"

**Remediation:** two parts.

1. The connection is now **opt-in and off by default** — a new setting,
   "Connect to the adz-world.com network". With it off, no outbound request is
   made at all: both ad fetches are gated, the visitor-IP lookup returns before
   sending, and all six methods of `adz_NetworkAuthorization` return early.
2. `readme.txt` gains the required `== External services ==` section naming the
   service, what is sent, when, and under which conditions, with links to Terms
   and Privacy.

**Status:** DONE in code. **BLOCKED externally** — the disclosure links to
`https://adz-world.com/terms/` and `https://adz-world.com/privacy/`. Those pages
must exist before resubmission. They will be checked.

---

## 8. Callback calls whose return values are output must be properly escaped

> "This applies to callbacks registered with functions such as `add_shortcode()`"
> "✨ The shortcode callback returns an iframe using unescaped adz_url, height,
> and width attributes."
> "✨ The shortcode callback returns unescaped shortcode attributes and content."

**Remediation:** `[adzworld_iframe]` escapes src (`esc_url`), height
(`absint`) and width (validated then `esc_attr`). `[Adzworld]` escapes its link
text (`esc_html`).

**Status:** DONE.

---

## 9. Internationalization: Text domain does not match plugin slug

> "This plugin is using the domain 'text_domain' for 49 element(s).
> This plugin is using the domain 'eof' for 2 element(s)."
> "However, the current plugin slug is this: adz-world"

**Remediation:** all 51 changed to `adz-world`.

**Status:** DONE.

---

## 10. Arbitrary input in sensitive function parameters or calls

> "Allowing user input to be passed without control to certain parameters of
> certain functions or to be part of a call creates the risk of privilege
> escalation, data tampering, and/or unexpected code execution."
> "✨ An unrestricted rotations_id from the AJAX request is used as the option
> name, allowing writes to arbitrary WordPress options."

Their examples: `adz-world.php` lines 93, 222, 228, 299, 308.

**Remediation:** rotation ids are generated by the settings UI as
`adz_rotation_<n>` (`vendor/eof/fields/repeat.php:154`). The value from the
request is now matched against that exact pattern and refused otherwise —
`adz_validate_rotation_id()` for reads, `adz_update_rotation_state()` for
writes, which writes nothing when the id is refused. Sanitising never helped:
the problem was which option was addressed, not what it contained.

**Status:** DONE. All 5 sites.

---

## 11. Other possible issues

> "✨ A public AJAX endpoint accepts rotations_id from visitors and uses it as an
> option name, allowing arbitrary WordPress options to be created or overwritten."

Same finding as §10.

**Status:** DONE with §10.

---

## 12. Allowing direct file access to plugin files

> "You can easily prevent this by adding the following code at the beginning of
> all PHP files that could potentially execute code if accessed directly:
> `if ( ! defined( 'ABSPATH' ) ) exit;`"

Their examples: `PHP-Rest-Client.php:3`, `template1.php:3`,
`wp-cache-class.php:2`, `WP-Rest-Client.php:2`, `adz-world.php:9`.

**Remediation:** guard added to every shipped PHP file lacking one, not only
the five listed.

**Status:** DONE.

---

## 13. The plugin has problems when it is activated

> "We installed your plugin in a clean WordPress and activated it, and the
> activation did not go through cleanly."
> "✨ includes/adz-helper.php redeclares WordPress core's role_exists() without a
> function_exists guard, causing a fatal redeclaration when the plugin is
> activated in wp-admin."

**Remediation:** `role_exists()` deleted. Nothing in the plugin called it.

Also found and fixed while here, not in their list: `adz_world_logged_in()` read
`->status` on a `json_decode()` result that is `null` whenever the request
fails. That is a warning on PHP 8, and this section is tested with `WP_DEBUG`
on.

**Status:** DONE for the named fatal.

---

## 14. Their checklist — what they require before re-review

> "✔️ I understand and have completed the necessary corrections indicated."
> "✔️ I conducted a full security and standards review ... Plugin Check, PHPCS + WPCS"
> "✔️ I tested my updated plugin on a clean WordPress installation with WP_DEBUG set to true"
> "⚠️ Do not skip this step."
> "✔️ I created a new version of this plugin and uploaded it to the SVN repository."
> "✔️ I replied to this email."

| Requirement | State |
|---|---|
| corrections completed | items 1–13 done, §0 unknown |
| Plugin Check / PHPCS run | **not run** |
| tested on clean WP, `WP_DEBUG` true | **not done** |
| new version in SVN | not yet |
| replied to the email | not yet |

**The testing requirement cannot be met from this environment.** No database,
no Docker daemon, wordpress.org blocked by the network policy — WordPress core
cannot even be downloaded here. Every change above is verified by syntax check
and by reading their findings against the code. None of it has been run inside
WordPress.

That requirement is theirs, it is marked "do not skip", and it is outstanding.

---

## Summary

| § | Item | Status |
|---|---|---|
| 0 | the guideline violation itself | **UNKNOWN — not named in the email** |
| 1 | ajax endpoint | done |
| 2 | Requires at least | done |
| 3 | Tested up to | done in file, unverified |
| 4 | too many tags | done |
| 5 | deprecated code | done |
| 6 | unmaintained library | done |
| 7 | external service | done in code, needs terms + privacy pages live |
| 8 | shortcode escaping | done |
| 9 | text domain | done |
| 10 | arbitrary input | done |
| 11 | other possible issues | done |
| 12 | direct file access | done |
| 13 | activation fatal | done |
| 14 | their checklist | 1 of 5 |

Three things are not code and cannot be closed here: asking which guideline,
publishing the terms and privacy pages, and testing on a real WordPress install.
