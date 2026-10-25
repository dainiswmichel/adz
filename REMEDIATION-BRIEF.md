# adz.world (`adz-world`) — Remediation Brief

**Baseline commit:** `d56bd87` on branch `claude/wizardly-rubin-6o4v2k` of `github.com/dainiswmichel/adz`
**Plugin version at baseline:** 1.0.8 — the exact code the WordPress.org directory served, committed verbatim and unmodified.
**Verified:** all 75 files parse cleanly under PHP 8.3.6 (`php -l`). Every file:line below was read and confirmed, not inferred from the review email.

---

## 0. Ground rules for whoever works on this

1. **This plugin worked and was approved.** It is 2018-era code with real defects, not garbage. Fix it in place. Do **not** rewrite it, do not "modernize" it wholesale, do not delete features you do not understand.
2. **The verbatim baseline `d56bd87` is the diff reference.** Every change must be reviewable as a diff against the code the directory actually served. That is also how we prove to the Plugins Team that nothing changed beyond what they asked.
3. **Do not skip, stub, disable, or comment out a feature to make a warning go away.** If something cannot be fixed, say so and leave it working.
4. **Do not claim a fix you have not run.** PHP lint, Plugin Check, PHPCS/WPCS, and a real `WP_DEBUG` activation are the evidence. "Should work" is not evidence.
5. **Preserve behaviour.** Where a fix changes behaviour (and some must), state it explicitly in the commit message.
6. Prefix every new global function `adzworld_`. Text domain is `adz-world` and must match the slug exactly.

---

## 1. Situation

On **29 September 2026** the WordPress.org Plugins Team closed `https://wordpress.org/plugins/adz-world/` for a guideline violation (Review ID `GUIDELINES ❗ACT adz-world/dainismichel/29Sep26/T1 29Sep26/4.3`).

- The closure notice is **public as "Guideline Violation" after 60 days** from 29 Sep 2026 unless a corrected version passes code review. That deadline is approximately **28 November 2026**.
- Re-review covers **the entire plugin**, not just the diff.
- Fixes ship by committing a new version to **SVN `trunk/`** plus a new `tags/` directory, with `Version:` in the plugin header and `Stable tag:` in `readme.txt` both bumped. SVN accepts commits even while the plugin is closed.
- The notice's technical findings are real but **incomplete**. The scanner missed the three worst vulnerabilities in the plugin (Section 2). Fixing only the emailed list will not make this plugin safe, and may not pass review.

---

## 2. P0 — Critical security issues the review email did NOT flag

These outrank everything in the official notice. All three are reachable by an **unauthenticated visitor**.

### 2.1 Unauthenticated arbitrary option deletion — `vendor/eof/adz-config.php:2-16`

```php
add_action('init', 'adz_check_post_function');
function adz_check_post_function(){
    if(isset($_POST['adz_ad_options'])){
        if(!empty($_POST['adz_ad_options'])){
            foreach ($_POST['adz_ad_options']['ad_settings'] as $value) {
                delete_option( sanitize_text_field( $value['rotation_id'] ) );
            }
        }
    }
}
```

Hooked on `init`, which runs on **every request including unauthenticated ones**. No nonce, no capability check, no allowlist. Any visitor can POST to any URL on the site:

```
adz_ad_options[ad_settings][0][rotation_id]=siteurl
```

and delete the `siteurl` option — or `home`, `active_plugins`, `admin_email`, `template`. This is a site-destruction and potential takeover primitive. `sanitize_text_field()` does nothing to stop it; it sanitizes the *characters*, not the *authority*.

**Fix:** this handler must only run for a user who can manage options, on an authenticated admin POST, with a verified nonce, and the `rotation_id` must be validated against the rotation ids actually present in the stored `adz_ad_options['ad_settings']` — never taken from the request. Also guard `foreach` with `is_array()`.

### 2.2 Unauthenticated local file inclusion — `adz-world.php:197` and `adz-world.php:267`

```php
require_once(__DIR__.'/adz-templates/'.sanitize_text_field( $_POST['adz_template'] ));
```

Inside `adz_get_advertise_content()`, which is registered for unauthenticated access at `adz-world.php:342` (`wp_ajax_nopriv_adz_get_advertise_content`). **`sanitize_text_field()` does not strip `../`.** A crafted `adz_template` traverses out of `adz-templates/` and includes and executes any `.php` the web user can read, or any uploaded file.

The nonce at `adz-world.php:72-76` is not a defence: `wp_create_nonce('adzdotworld')` is emitted into public page source for logged-out visitors (`includes/adz-helper.php:46`, `:111`, `:174`, `:219`; `includes/adz-shortcode.php:99`, `:142`, `:191`, `:233`), and for anonymous users WordPress nonces are not per-visitor. An attacker fetches a public page, reads the nonce, then exploits.

**Fix:** never build an include path from request input. Resolve the template through an allowlist built server-side from `scandir(ADZ_WORLD.'adz-templates')`, reject anything not an exact match, and fall back to `template1.php`. Same treatment at `adz-world.php:601` and `:652` (those read from stored options, lower risk, but should use the same resolver).

### 2.3 Unauthenticated option read + object-injection sink — `includes/adz-cpt-ads.php:291-302`

```php
function get_template_settings(){
    if(isset($_POST['selected_template'])){
        $template_name = $_POST['selected_template'];
        $template_data = get_option('template_'.$template_name);
        echo json_encode( unserialize($template_data) );
        exit;
    }
}
add_action('wp_ajax_get_template_settings', 'get_template_settings');
add_action('wp_ajax_nopriv_get_template_settings', 'get_template_settings');
```

Three problems at once: registered for `nopriv` so **anyone** can call it; no nonce and no capability check; `unserialize()` on stored data is a PHP object-injection sink if any `template_*` option can be influenced (and per 2.1/3.4 it can). The function name `get_template_settings` is also **unprefixed and highly likely to collide** with another plugin, which is itself a guideline violation.

**Fix:** rename to `adzworld_ajax_get_template_settings`, drop the `nopriv` registration, require `current_user_can('manage_options')` and `check_ajax_referer()`, validate `selected_template` against the template allowlist, stop storing serialized strings (see 3.4) and use `wp_send_json()`.

### 2.4 Unauthenticated de-registration — `vendor/eof/adz-config.php:17-29`

`adz_reset_registration()` deletes the `adz_publisher_options` option — the site's entire network registration and tokens — guarded only by the publicly-readable `adzdotworld` nonce described in 2.2, and registered for `nopriv` at line 29.

**Fix:** drop the `nopriv` registration, require `manage_options`, use a dedicated nonce action created only on the settings screen, and verify with `check_ajax_referer()`.

### 2.5 SQL injection — 4 queries, `$wpdb->prepare()` used **zero times** in the entire plugin

| File | Line |
|---|---|
| `adz-world.php` | 124 |
| `adz-world.php` | 185 |
| `adz-world.php` | 593 |
| `includes/adz-shortcode.php` | 45 |

```php
$adz_views = $wpdb->get_row("SELECT *  FROM ".$wpdb->prefix.'adz_views'
  ." WHERE visitor_ip = '".$visitor_ip."' AND `targate_id` = '"
  .sanitize_text_field( $_POST['target'] )."' ".$cond
  ."  AND ad_date='".date('Y-m-d')."'",OBJECT);
```

`$visitor_ip` is raw `$_SERVER['REMOTE_ADDR']` (attacker-influenced behind proxies). `$cond` is built at `adz-world.php:114-120` by interpolating `$_POST['target_type']` directly into SQL. `sanitize_text_field()` is **not** SQL escaping.

**Fix:** rewrite all four with `$wpdb->prepare()` and `%s`/`%d` placeholders. Table name interpolation of `$wpdb->prefix` is fine; every value must be a placeholder.

---

## 3. The WordPress.org notice items — exact locations and prescribed fixes

### 3.1 🔴 Activation fatal — `includes/adz-helper.php:2-9`

```php
function role_exists( $role ) { ... }
```

Declared with no `function_exists()` guard, and unprefixed. The team reports this fatals on activation in wp-admin.

**Fix:** rename to `adzworld_role_exists()`, update callers, and wrap in `if ( ! function_exists( ... ) )`. Renaming is required by the prefix guideline regardless of whether core currently defines `role_exists()`, and makes the collision impossible either way.

### 3.2 🔴 Hardcoded `admin-ajax.php` — 9 PHP + 2 JS occurrences

`url: "<?php echo site_url();?>/wp-admin/admin-ajax.php"` at:

| File | Lines |
|---|---|
| `includes/adz-helper.php` | 33, 102, 160, 209 |
| `includes/adz-shortcode.php` | 91, 133, 178, 223 |
| `adz-templates/template1.php` | 44 |
| `js/plugin.js` | 7 (as a bare `/wp-admin/admin-ajax.php`) |

**Fix:** this is not a find-and-replace. All seven inline `<script>` blocks in PHP must move to enqueued `.js` files with data passed via `wp_localize_script()` carrying `admin_url('admin-ajax.php')` and a nonce. See 3.3.

### 3.3 🔴 Inline `<script>` in PHP — 7 blocks, with unescaped PHP interpolated into JS

Blocks at `includes/adz-helper.php:18-129` and `:147-235`; `includes/adz-shortcode.php:86-157` and `:172-248`; `adz-templates/template1.php:26-67`; `includes/adz-cpt-ads.php:229-236`; plus the inline `onclick='reset_registration()'` at `vendor/eof/adz-config.php:376`.

Inside these, values are echoed into JavaScript string literals with no escaping — e.g. `includes/adz-helper.php:37-45`, `:59`, `:74`, `:106-109`, `:164-172`, `:185`, `:213-216`:

```php
target_type : '<?php echo $target_type;?>',
adz_template : '<?php echo $adz_template;?>',
var i = <?php echo $ad_visibility;?>;
```

**Fix:** move each block to a file under `js/`, enqueue it on the screens that need it, and pass all dynamic values through `wp_localize_script()` (which JSON-encodes safely). Where a value must stay inline, use `wp_json_encode()` for numbers/objects and `esc_js()` for string literals. Replace the `onclick` with a delegated handler in the enqueued admin file.

### 3.4 🔴 Arbitrary option names from request input

| File | Line | Call |
|---|---|---|
| `adz-world.php` | 93 | `get_option( sanitize_text_field( $_POST['rotations_id'] ) )` |
| `adz-world.php` | 222, 228, 299, 308 | `update_option( sanitize_text_field( $_POST['rotations_id'] ), $roatation_stats )` |
| `includes/adz-cpt-ads.php` | 259 | `update_option( "template_".$_POST['adz_template'], serialize($template_settings), true )` |

The `adz-cpt-ads.php:259` case was **not** in the review email and is worse than the ones that were: `$_POST['adz_template']` is not sanitized at all, and `$_POST["header_text"]`, `$_POST["header_style"]`, `$_POST["footer_text"]`, `$_POST["footer_style"]` (lines 253-256) are stored completely unsanitized, then echoed raw into every visitor's page by `adz-templates/template1.php` (see 3.6). That is a **stored XSS chain from the admin form to every site visitor.**

**Fix:**
- The rotation id is already known server-side. `adz-world.php:446` and `:460` legitimately use `$roatations['rotation_id']` read from `adz_ad_options['ad_settings']`. The AJAX handler must do the same: look the rotation up from stored settings and **validate the submitted id against that allowlist**, rejecting anything not found. Never pass request input as an option name.
- For template settings: validate `adz_template` against the template allowlist; sanitize `header_text`/`footer_text` with `wp_kses_post()` (or `sanitize_text_field()` if no markup is intended); restrict `header_style`/`footer_style` to the literal set `left|right|center`; **stop calling `serialize()`** — `update_option()` serializes arrays itself, and the explicit call is what forces the matching `unserialize()` at `template1.php:5`. Fix both ends together and write a one-time migration for existing double-serialized values.
- Add `check_admin_referer()` and `current_user_can('manage_options')` to the handler at `includes/adz-cpt-ads.php:251`. It currently has **no nonce verification at all** — CSRF.

### 3.5 🔴 Unescaped shortcode return values

`includes/adz-shortcode.php:4` → `adz_iframe_shortcode()`, line 12:

```php
$output = '<iframe src="'.$attributes['adz_url'].'" height="'.$attributes['height'].'" width="'.$attributes['width'].'"></iframe>';
```

`includes/adz-shortcode.php:17` → `adz_shortcode()`, lines 52 and 67 echo `$more_text` unescaped.

**Fix:** `esc_url()` on `adz_url`; `absint()` on `height`; `esc_attr()` on `width`; `esc_html()` on `$more_text`. Also consider restricting `adz_url` to an allowlisted host — a shortcode that frames an arbitrary attacker URL is an open redirect/clickjacking surface if any contributor-level user can insert shortcodes.

### 3.6 🔴 Unescaped output in the ad template — `adz-templates/template1.php`

- Lines 72, 97, 110, 111, 126, 127, 136, 137, 152, 153 echo `$setting_array['header_style'|'footer_style'|'header_text'|'footer_text']` unescaped — the sink of the stored-XSS chain in 3.4.
- Lines 88 and 145 echo `$ad_text` — **content fetched over HTTP from adz.world** — directly into the page with no sanitization.
- Lines 85 and 119 run `do_shortcode($ad_text)` on that same remote content, letting the remote server execute any shortcode registered on the publisher's site.

**Fix:** `esc_attr()` for the style attributes, `wp_kses_post()` for the text fields. For `$ad_text`, pass it through `wp_kses()` with an explicit allowed-tag set appropriate to ad creatives. Restrict `do_shortcode()` to the specific `adzworld_iframe` shortcode rather than running the whole shortcode engine on remote input — or drop it and render the iframe directly.

### 3.7 🔴 Direct file access — 16 files, including the main file

Files with no `ABSPATH` guard:

```
adz-world.php                                   (line 26 only *mentions* ABSPATH in a require — there is no guard)
adz-templates/template1.php
classes/network_authorization.class.php
classes/rest-client/PHP-Rest-Client.php
classes/rest-client/RestClient.interface.php
classes/rest-client/WP-Rest-Client.php
classes/rest-client/cache-interface/cache-interface.php
classes/rest-client/cache-interface/wp-cache-class.php
includes/adz-cpt-ads.php
includes/adz-helper.php
includes/adz-shortcode.php
session-handling.php
vendor/eof/adz-config.php
vendor/eof/core/class-eof-loader.php
vendor/eof/core/class-eof.php
vendor/eof/eof.php
vendor/eof/index.php
```

**Fix:** add `if ( ! defined( 'ABSPATH' ) ) exit;` immediately after the opening `<?php` in each. `adz-templates/template1.php` is the highest priority — it executes code and emits output on include.

### 3.8 🔴 Deprecated function — `includes/adz-cpt-ads.php:250`

`screen_icon();` — removed from core behaviour in WP 3.8.

**Fix:** delete the call. It does nothing.

### 3.9 🔴 Unmaintained library — `js/chosen.jquery.js`

Chosen 1.8.2 (confirmed in the file header), last released 2018, archived upstream at `github.com/harvesthq/chosen`. Also `css/chosen.css` and `css/chosen-sprite.png`.

**Fix:** replace with an actively maintained enhanced-select, or with WordPress core's own `wp-util`/`select2` already bundled for admin screens, or a small vanilla multiselect. The only consumer is the multi-select fields in the eof settings framework (`multiple => 'yes'`), so the surface is contained. Removing Chosen entirely and falling back to a native `<select multiple>` is an acceptable, low-risk option.

### 3.10 🔴 Undocumented external service

The plugin contacts **adz.world** from:

| File | Line | What it sends |
|---|---|---|
| `includes/adz-helper.php` | 277-279 | **Visitor IP address** (`$_SERVER['REMOTE_ADDR']`) in a URL query string, to check visitor login state |
| `adz-world.php` | 98, 101, 107, 173 | Publisher id, post token, network token, referral code, Amazon affiliate id, publisher/visitor user ids |
| `adz-world.php` | 564, 568, 576, 584 | Same, for thru-page serving |
| `classes/network_authorization.class.php` | 15 | Registration check |
| `classes/network_authorization.class.php` | 80 | Site URL, site title, **admin email**, PayPal email, adz.world email, referral code, ad preferences |
| `classes/network_authorization.class.php` | 120 | Ad deletion |
| `classes/network_authorization.class.php` | 165 | **Ad post title and full post content** (base64), affiliation network name/URL/id |
| `classes/network_authorization.class.php` | 194 | Ad category list |
| `classes/network_authorization.class.php` | 216 | Premium serving preference |

`readme.txt` contains **no `== External Services ==` section at all.**

**Fix:** add the section (draft in Section 7 below) and confirm that live Terms of Service and Privacy Policy pages exist at adz.world — the team said they will check the links on re-review. Sending a visitor's IP to a third party also has GDPR implications and should be consent-gated, not unconditional.

### 3.11 🔴 Text domain does not match slug

`'text_domain'` appears as the domain in **49** gettext calls — the literal placeholder left in by the CPT generator — throughout `includes/adz-cpt-ads.php` (lines 6-36, 159-178). The plugin header has **no `Text Domain:` line at all**.

**Fix:** replace every `'text_domain'` with `'adz-world'`; add `Text Domain: adz-world` and `Domain Path: /languages` to the header. Note the bundled eof framework uses its own `'eof'` domain in its own files — leave the vendor framework's strings alone, but do not let `'eof'` leak into plugin-owned strings.

### 3.12 🟡 Too many readme tags — `readme.txt:4`

11 tags; the directory honours 5.

**Fix:** keep five. Suggested: `advertising, consent-based advertising, permission-based advertising, ad rotation, affiliate`.

### 3.13 🔴 `Requires at least` / `Tested up to` — `readme.txt:5-6`

Both read `4.9.6`. Two separate errors: minor versions are ignored (must be major only), and `Tested up to` must name the current stable WordPress major or the plugin is hidden from search.

**Fix:** set `Requires at least:` to the oldest major you will actually support (`5.0` is a defensible floor for this code; do not set it equal to the newest release, which would block installation everywhere older). Set `Tested up to:` to the current stable WordPress major **after testing against it** — confirm the number at `wordpress.org/download/` before committing; do not guess, and never set it beyond the current release. Mirror `Requires at least` in the plugin header. Bump `Requires PHP:` from `5.6` to at least `7.4`.

### 3.14 Plugin header is incomplete — `adz-world.php:2-8`

Present: `Plugin Name`, `Description`, `Version`, `Author`, `Author URI`.
Missing: `Plugin URI`, `License`, `License URI`, `Text Domain`, `Domain Path`, `Requires at least`, `Requires PHP`.

`readme.txt:9` declares `License: GPLv3` but no license is declared in the header and **no `LICENSE` file ships** with the plugin. Note `classes/rest-client/LICENSE.md` is the REST client's own license, not the plugin's.

**Fix:** complete the header and add a GPLv3 `LICENSE` file at the plugin root.

---

## 4. Functional bugs — things that are actually broken right now

These are not style issues. The reviewer did not find them because an automated scanner does not execute code.

### 4.1 Fatal: undefined function — `includes/adz-shortcode.php:38`

```php
$adz_sequence = rtrim(get_publisher_adz(),',');
```

The function is defined as **`adz_get_publisher_adz()`** at `includes/adz-helper.php:241`. `get_publisher_adz()` does not exist anywhere in the plugin (verified by grep across all 41 PHP files). This is a **fatal error on the front end** every time the `[Adzworld]` shortcode is used without an explicit `adz_sequence` attribute — i.e. the documented default usage.

**Fix:** call `adz_get_publisher_adz()`.

### 4.2 Undefined variable passed as the interval — `includes/adz-shortcode.php:69, 71`

`adz_in_page($adz_interval_min, ...)` and `adz_in_popup($adz_interval_min, ...)` — but the variable assigned at line 33 is `$adz_interval_sec`. `$adz_interval_min` is never defined in `adz_shortcode()`, so `null` is passed and the repeat-view interval silently becomes `0` on the second-and-later view path.

**Fix:** pass `$adz_interval_sec`. Check the same name in `adz_in_page()`/`adz_in_popup()` signatures (they name the parameter `$adz_interval_min`, which is merely misleading, not broken).

### 4.3 Undefined variable returned — `includes/adz-shortcode.php:81`

`return $output;` — `$output` is only assigned inside the `if($adz_to_serve)` branch (lines 52, 67, 74). When `$adz_to_serve` is falsy, or when `$adz_display` is neither `page` nor `popup` on the repeat path, `$output` is never set: notice under `WP_DEBUG`, and the shortcode **swallows the post content** instead of returning it.

**Fix:** initialise `$output = $content;` at the top of the function.

### 4.4 Broken markup — unclosed `<div>` — `includes/adz-shortcode.php:52, 67`

```php
$output = '<div id="content_after_ad"><a id="show_adz" ...>' . $more_text . '</a><div>';
```

Trailing `<div>` where `</div>` was intended. Injects an unclosed element into the page on every shortcode render.

### 4.5 Broken markup — unterminated attribute — `adz-templates/template1.php:152`

```php
<div id="adz_footer_content" style="text-align: '.$setting_array['footer_style'].'>
```

The closing `"` is missing, so the rest of the markup is swallowed into the `style` attribute. This is in the non-iframe base64 branch — the **default popup/in-page ad render path**. Compare line 126, which is correct.

### 4.6 `$_SESSION` is used but never started

`$_SESSION['first_time']` is read and written at `adz-world.php:190`, `:194`, `:260`, `:264` and `includes/adz-helper.php:13`, `:14`, `:135`, `:136`. Nothing in the plugin calls `session_start()` — `session-handling.php` implements an unrelated option-based store instead, and its `update_adz_session()` / `get_adz_session()` functions are **never called anywhere** (dead code).

Consequence: the ad-frequency throttle these variables implement **does not work**, and `WP_DEBUG` fills with undefined-index notices. `includes/adz-helper.php:13` papers over one of them with the `@` error-suppression operator.

**Fix:** do not add `session_start()` — PHP sessions are strongly discouraged in WordPress and break on many hosts. Reimplement the throttle with a cookie or a transient keyed by a hashed visitor identifier, and delete the dead `session-handling.php` functions or wire them up properly. Decide and document which, because this changes behaviour (the throttle will start working, which it currently does not).

### 4.7 Meta fields silently wiped on every post update — `classes/network_authorization.class.php:152-154`

```php
update_post_meta( $post_id, 'affiliation_network_name', sanitize_text_field( $_POST['affiliation_network_name'] ) );
update_post_meta( $post_id, 'affiliation_network_url',  sanitize_text_field( $_POST['affiliation_network_url'] ) );
update_post_meta( $post_id, 'affiliation_id',           sanitize_text_field( $_POST['affiliation_id'] ) );
```

Called from `update_ad()`, which is hooked to `post_updated` (`includes/adz-cpt-ads.php:205`). No `isset()`, no nonce verification, no capability check. Any update path that does not carry those three POST fields — **Quick Edit, bulk edit, a scheduled publish via wp-cron, a REST or WP-CLI update** — overwrites all three meta values with empty strings. Silent data loss.

Meanwhile `includes/adz-cpt-ads.php:66` creates a `meta-box-nonce` field that is **never verified anywhere** in the plugin.

**Fix:** move meta saving into a proper `save_post_adz_ad` handler that verifies the existing nonce, checks `current_user_can('edit_post', $post_id)`, bails on `DOING_AUTOSAVE` and on revisions, and only writes keys actually present in `$_POST`.

### 4.8 Thru-page close button is dead — `adz-templates/template1.php:44-54`

This AJAX request sends no `adz_nonce`. The handler at `adz-world.php:72-76` therefore fails `wp_verify_nonce()`, echoes `continue`, and exits — so the `close_adz` click never records the view and never reloads. Every other AJAX call in the plugin does send the nonce.

**Fix:** pass the nonce through the enqueue/localize refactor in 3.3.

### 4.9 Missing `isset()` guards on request input

`adz-world.php:72` (`$_REQUEST['adz_nonce']`), `:77` (`$_POST['ad_visibility_interval']`), `:199`, `:258`, `:271` (`$_POST['target_type']`, `$_POST['repeat_times']` read raw). Notices under `WP_DEBUG`, which the team explicitly tests for.

### 4.10 `unserialize()` on option data — `adz-templates/template1.php:5`

```php
$setting_array = unserialize($setting_data);
```

Only correct because `includes/adz-cpt-ads.php:259` explicitly `serialize()`s before storing. Both halves must be fixed together (see 3.4), with a migration for existing values. If `$setting_data` is empty or not a serialized string, `unserialize()` returns `false` and every `$setting_array['...']` access below emits a warning.

### 4.11 Inconsistent base URL joining

`$adz_ad_network_base_url` is `'https://adz.world/'` **with** a trailing slash (`adz-world.php:10`). `adz-world.php:98` appends `"wp-json/..."`; `classes/network_authorization.class.php:9`, `:65`, `:108`, `:134`, `:192`, `:210` append `"/wp-json/..."`, producing `https://adz.world//wp-json/...`.

**Fix:** normalise with `trailingslashit()` / `untrailingslashit()` once.

---

## 5. Performance — the plugin makes a blocking HTTP call on every page load

`adz_setings_setup()` is hooked to `init` **unconditionally** (`vendor/eof/adz-config.php:200`) — not gated on `is_admin()`, not on an admin screen check. On **every single request to the site**, front-end and admin, logged in or out, it executes:

- `adz_list_all_adz_type()` (line 80) → `adz_NetworkAuthorization::get_ad_types()` → **blocking `wp_remote_get()` to adz.world** (`classes/network_authorization.class.php:194`).
- `adz_registration_status()` as a dynamic field callback (line 112) → `checkRegistration()` → **a second blocking HTTP call** (`classes/network_authorization.class.php:15`).
- `adz_rotation_setting_array()` (line 123) → `adz_get_filterd_pages()`, `adz_get_all_adz()`, and `adz_get_all_categories()` twice — each running `get_posts()` / `get_terms()` with **`'posts_per_page' => -1`**, loading every published post and page into memory.

Every visitor therefore waits on adz.world before the page renders, and a slow or unreachable adz.world stalls the whole site. On a site with a few thousand posts the unbounded queries alone are crippling.

This also violates the team's activation requirement that a plugin **not contact external servers without explicit consent** — these calls fire before and regardless of registration.

**Fix:**
- Gate the whole settings registration behind `is_admin()`, and build the field options only on the plugin's own settings screen (hook `load-{$page_hook}` or check `get_current_screen()`).
- Cache every adz.world response in a transient (e.g. 12 hours) with a short negative-cache on failure, and set an explicit `timeout` on all `wp_remote_*` calls.
- Replace `posts_per_page => -1` with a bounded query, and use `'fields' => 'ids'` plus `'no_found_rows' => true` where only ids are needed.
- Make all network calls conditional on the publisher having registered and accepted the TOS.

---

## 6. Guideline housekeeping

- **Unprefixed global functions** (guideline 17, collision risk): `role_exists`, `get_template_settings`, `ad_taxonomy_pub`, `ad_network_meta_box_markup_pub`, `add_ad_network_meta_box_pub`, `set_network_adz_id_column`, `network_adz_id_column`, `start_adz_session`, `update_adz_session`, `get_adz_session`. Rename all to `adzworld_*`. `get_template_settings` is the dangerous one.
- **Admin assets load on every admin screen** — `adz-world.php:60-68` enqueues `chosen.css`, `chosen.jquery.js` and `plugin.js` on `admin_enqueue_scripts` with no screen check, so Chosen's CSS reaches the entire dashboard. `includes/adz-cpt-ads.php:238` prints an inline script into every admin footer. Both are explicitly called out in the team's activation checklist. Scope them to the plugin's own screens.
- **Unescaped admin output** — `includes/adz-cpt-ads.php:77`, `:85`, `:93` (meta box `value=` attributes), `:126` (column cell), `:273` (option values); `vendor/eof/adz-config.php:382` (`echo $reg`). Use `esc_attr()` / `esc_html()`; `$reg` contains intentional markup, so build it with escaped parts.
- **`scandir()` return not checked** — `includes/adz-cpt-ads.php:268`, `vendor/eof/adz-config.php:503`. Returns `false` on failure; `!empty(false)` is false so it degrades quietly, but guard it.
- **Date/time** — `date('Y-m-d')` at `adz-world.php:124`, `:134`, `:135`, `:156`, `:157`, `:185`, `:593` and `includes/adz-shortcode.php:45` uses server-local time. Use `current_time()` / `gmdate()` consistently so the daily view window does not shift with server timezone.
- **Schema** — `adz_views.targate_id` is a typo for `target_id` in the table created at `adz-world.php:34`. Renaming requires a migration; if you fix it, do it properly with a `dbDelta()` version check, otherwise leave it and note it. `visitor_ip varchar(255)` is oversized (45 is enough for IPv6) and there is **no index** on the `visitor_ip`/`targate_id`/`ad_date` lookup, which is the plugin's hottest query.
- **Storing raw visitor IPs** is a GDPR liability. Consider storing a salted hash instead; it serves the same per-visitor-per-day counting purpose.
- **`people.txt`** and `changelog.txt` ship in the package — harmless, but `readme.txt` should carry the changelog in its `== Changelog ==` section.
- `readme.txt:1` uses a full sentence as the plugin name while the header says `adz.world`. The directory takes the name from the readme's first line. Make them agree. (The short description at `readme.txt:12` is 148 characters, inside the 150 limit — this one is fine.)
- `readme.txt:3` donate link and `adz-world.php:7` author URI are `http://`. Use `https://`.

---

## 7. Draft `== External Services ==` section for `readme.txt`

Needs review against what adz.world actually does before committing, and both links must resolve to real pages.

```
== External services ==

This plugin connects to adz.world, the advertising network operated by the plugin
author, to register your site, to fetch the adz that are displayed to your visitors,
and to report the adz you publish. The plugin cannot serve network adz without it.

What is sent, and when:

1. On registration (when you accept the Terms of Service and save the settings page):
   your site URL, site title, site admin email address, your PayPal email address,
   your adz.world email address, your referral code and your selected adz preferences.

2. When an ad is served to a visitor (on page views where an adz rotation applies):
   your publisher id and authentication tokens, your referral code, your Amazon
   affiliate id, the id of the ad being requested, and — where the visitor-targeted
   pool is active — the visitor's IP address, which adz.world uses to determine
   whether that visitor has an adz.world account.

3. When you publish, update or delete an ad (post type "adz_ad"): the ad's title and
   content, and the affiliation network name, URL and id you entered for it.

4. When the settings page is loaded: a request for the current list of ad categories
   and your registration status.

This service is provided by adz.world: [Terms of Service](https://adz.world/terms/)
and [Privacy Policy](https://adz.world/privacy/).
```

---

## 8. New feature — adz.world internal account and earnings panel

**Stated requirement:** replace what was "the network" with an adz.world internal account that tracks adzAdmin earnings, from which the adzAdmin can download the funds they have earned from their permission-based adz. The earnings panel is a tab in the plugin. The flat fee for using the free adz.world plugin is **10%**.

### 8.1 Decisions that must be made before any code is written

Do not guess at these. They change the architecture, and two of them have legal consequences.

1. **Where is the authoritative ledger?** A publisher's own WordPress database cannot be the source of truth for money they are owed — they can edit it. The authoritative balance must live on adz.world, with the plugin holding a reconciled read-only mirror for display. Confirm this is the intent.
2. **Does "the network" pool disappear entirely, or only get renamed?** `next_adz_pool` currently cycles `publisher → network → visitor` (`adz-world.php:96-110`, `:198-246`, `:562-580`, `:602-636`). If the `network` state is retired, existing installs have `next_adz_pool` already set to `'network'` in the database and will stall on an invalid state. A migration is mandatory either way.
3. **"Download the funds" — withdrawal, or statement download?** These are different features. A CSV/PDF statement export is a day's work. A withdrawal that moves real money is a payment-rail integration with compliance obligations. Scope below covers both, flagged separately.
4. **Which payment rail?** The plugin already collects `paypal_email` (`vendor/eof/adz-config.php:59-65`), which points at PayPal Payouts. Stripe Connect is the other realistic option. This decision is adz.world's, not the plugin's — the plugin only requests a payout and displays its status.
5. **What is a billable event?** An ad view? A click? An affiliate conversion? The existing `adz_views` table counts views per visitor-IP per target per day. Earnings cannot be computed until "what earns money, and how much" is defined.
6. **Anti-fraud.** If a view earns money and the publisher controls the site that reports views, the publisher can manufacture views. Validation must happen server-side at adz.world. Say plainly how, before building the panel.
7. **Compliance.** Paying people money implicates money-transmission rules, tax reporting (1099/equivalent), and the 10% fee makes adz.world a paid service for WordPress.org's purposes — which is permitted but must be disclosed in the readme. Worth an hour with an accountant and the WordPress.org guidelines before launch, not after.

### 8.2 Data model (proposed)

Two new tables, created via `dbDelta()` in the activation routine alongside `adz_views`, with a stored schema version option so upgrades are safe.

**`{$wpdb->prefix}adz_earnings`** — append-only ledger. Never `UPDATE` or `DELETE` a row; corrections are new rows with `event_type = 'adjustment'`.

| Column | Type | Notes |
|---|---|---|
| `id` | `bigint(20) unsigned AUTO_INCREMENT` | |
| `created_at_gmt` | `datetime` | UTC, always |
| `event_type` | `varchar(32)` | `adz_view`, `affiliate_sale`, `adjustment`, `payout` |
| `source_pool` | `varchar(32)` | `publisher`, `visitor`, `account` |
| `ad_post_id` | `bigint(20)` | local `adz_ad` post, nullable |
| `network_ad_id` | `varchar(64)` | adz.world's id, nullable |
| `rotation_id` | `varchar(191)` | nullable |
| `visitor_hash` | `char(64)` | salted hash, never a raw IP |
| `currency` | `char(3)` | ISO 4217 |
| `gross_minor` | `bigint(20)` | **integer minor units (cents). Never a float.** |
| `fee_rate_bp` | `int(11)` | fee rate in basis points at the time of the event — `1000` = 10% |
| `fee_minor` | `bigint(20)` | computed once, at insert |
| `net_minor` | `bigint(20)` | `gross_minor - fee_minor` |
| `status` | `varchar(32)` | `pending`, `settled`, `reversed` |
| `external_ref` | `varchar(191)` | adz.world's transaction id — **unique index**, for idempotent sync |
| `synced_at_gmt` | `datetime` | nullable |

**`{$wpdb->prefix}adz_payouts`**

| Column | Type | Notes |
|---|---|---|
| `id` | `bigint(20) unsigned AUTO_INCREMENT` | |
| `requested_at_gmt` | `datetime` | |
| `requested_by` | `bigint(20)` | WP user id |
| `amount_minor` | `bigint(20)` | |
| `currency` | `char(3)` | |
| `method` | `varchar(32)` | `paypal`, `stripe` |
| `destination_hint` | `varchar(191)` | masked, e.g. `d***@dainis.net` — never store full credentials |
| `status` | `varchar(32)` | `requested`, `approved`, `paid`, `failed`, `cancelled` |
| `idempotency_key` | `char(36)` | **unique index** — see 8.4 |
| `external_ref` | `varchar(191)` | adz.world payout id |
| `failure_reason` | `text` | nullable |
| `updated_at_gmt` | `datetime` | |

Indexes: `(created_at_gmt)`, `(status)`, `(external_ref)` unique on earnings; `(status)`, `(idempotency_key)` unique on payouts.

### 8.3 Fee arithmetic — get this exactly right

- Store money as **integer minor units**. Floats silently lose cents and the losses are not reproducible.
- Store `fee_rate_bp` **on every row**, at insert time. Do not compute the fee at payout time from a global setting: when the rate changes, historical balances must not retroactively change. `1000` basis points = 10%.
- `fee_minor = intdiv( $gross_minor * $fee_rate_bp + 5000, 10000 )` for half-up rounding at the minor unit, then `net_minor = $gross_minor - $fee_minor`. Fix one rounding rule, document it, and use it in exactly one function.
- `available_balance = SUM(net_minor WHERE status='settled') - SUM(amount_minor FROM payouts WHERE status IN ('requested','approved','paid'))`. Pending earnings display separately and are not withdrawable.
- Never mix currencies in one balance. If adz.world is multi-currency, balances and payouts are per-currency.
- The plugin **displays** these numbers; adz.world **decides** them. On any disagreement, the server wins and the mirror is rebuilt.

### 8.4 The earnings tab

Register it as a new section in the existing eof framework, which keys sections by id with a `priority` (see `vendor/eof/adz-config.php:41` and `:119`):

```php
$sections['earnings'] = array(
    'title'    => 'Earnings',
    'id'       => 'earnings',
    'priority' => 3,
    'fields'   => adzworld_earnings_fields(),
);
```

**Put this in a new file `includes/adz-earnings.php`, not in `vendor/eof/`.** `vendor/eof/` is a third-party framework; plugin logic does not belong there, and `adz-config.php` is already overloaded with business logic that should be moved out over time.

Panel contents:
- Available balance, pending balance, lifetime gross, lifetime fees — each with its currency.
- The 10% fee stated plainly on the panel, not buried in the TOS.
- A paginated, filterable ledger table (`WP_List_Table`), server-side paginated — never load the whole ledger.
- CSV export of the ledger for a date range (this is the cheap, safe reading of "download the funds").
- A "Request payout" form: amount (defaulting to full available balance), method, destination confirmation.
- Payout history with status and failure reasons.
- Last-synced timestamp, and a manual "Sync now" button.

**Security requirements for this tab, without exception:**
- `current_user_can('manage_options')` on every render and every handler. Consider a dedicated `adzworld_manage_earnings` capability — note `vendor/eof/adz-config.php:164` has a commented-out `adz_world_manager` role, so a role concept was already intended.
- A distinct nonce action per handler, verified with `check_admin_referer()` / `check_ajax_referer()`. **No `nopriv` handler may ever touch this feature.**
- Payout requests carry a client-generated `idempotency_key`, stored with a unique index, so a double-click or a retried request cannot create two withdrawals. This is the single most important correctness guard in the feature.
- Rate-limit payout requests (one open request at a time is the simplest correct rule).
- Every state change writes an audit row. Money features need a trail that survives disputes.
- All amounts escaped on output; all incoming amounts validated as non-negative integers within the available balance, **re-checked server-side at adz.world** before anything is paid.

### 8.5 Sync

- A scheduled sync (`wp_cron`, hourly is ample) pulling settled transactions since the last cursor, plus the manual button.
- Sync must be **idempotent**: insert on unique `external_ref`, ignore duplicates. Never trust a payload to overwrite an existing row's amounts.
- Responses from adz.world must be authenticated (signed, or at minimum bearer-token over TLS with certificate verification left **on**) and schema-validated before a single row is written. The existing `WPRestClient` usage passes `'None'` for authentication on several calls — unacceptable for money.
- Explicit timeouts and a negative cache on failure; the panel degrades to "last synced X ago", never to a blank or a zero balance, because a zero shown to a publisher who is owed money is the worst possible failure mode.

### 8.6 Retiring the network pool

`next_adz_pool` is read and written at `adz-world.php:92`, `:96`, `:100`, `:103`, `:231`, `:233`, `:236`, `:238`, `:244`, `:313`, `:315`, `:319`, `:322`, `:328`, `:548`, `:562`, `:566`, `:570`, `:621`, `:623`, `:626`, `:628`, `:634`, `:673`, `:675`, `:678`, `:680`, `:685`. That is 28 sites across three near-duplicate copies of the same state machine.

Two things follow. First, **extract that state machine into one function** before changing it; editing 28 call sites by hand across three copy-pasted blocks is how this plugin got here. Second, ship a migration that maps any stored `next_adz_pool = 'network'` to a valid state on upgrade, or existing installs stop serving adz.

### 8.7 Readme additions for this feature

The 10% fee and the account/payout service must be disclosed in `== External Services ==` and in the description. A plugin that collects a revenue share through a service the author operates is permitted, but the directory requires it to be stated plainly and up front.

---

## 9. Suggested order of work

Each numbered step should be its own commit, independently reviewable against `d56bd87`.

1. `ABSPATH` guards in all 16 files (3.7). Mechanical, zero risk, removes a whole finding.
2. The four P0 vulnerabilities: option deletion (2.1), LFI (2.2), unauthenticated option read (2.3), unauthenticated de-registration (2.4).
3. `$wpdb->prepare()` on all four queries (2.5).
4. Arbitrary option names → allowlist validation, plus the stored-XSS chain and the `serialize()`/`unserialize()` pair with its migration (3.4, 4.10).
5. The activation fatal (3.1) and the function-prefix renames (6).
6. Output escaping: template (3.6), shortcodes (3.5), admin (6).
7. The functional bugs — 4.1 through 4.9. **4.1 first**: it is a front-end fatal.
8. Nonce and capability checks on every handler; the `save_post` rewrite (4.7).
9. Inline JS → enqueued files with `wp_localize_script()`, which resolves the admin-ajax findings too (3.2, 3.3).
10. Performance: gate `init`, cache remote calls, bound the queries (5).
11. Replace Chosen (3.9).
12. Text domain, plugin header, `LICENSE`, readme metadata, tags, `== External Services ==` (3.10-3.14).
13. Only then: the earnings feature (8). Do not build a money feature on top of unfixed vulnerabilities.

---

## 10. Definition of done — verify, do not assume

1. `php -l` clean on every file (it already is at baseline; keep it that way).
2. **Plugin Check** (`wordpress.org/plugins/plugin-check/`) — zero errors; every remaining warning understood and explainable.
3. **PHPCS with WordPress-Coding-Standards** — zero errors. Document each remaining warning and why it stands.
4. **Clean WordPress install, `WP_DEBUG` and `WP_DEBUG_LOG` both true, current PHP:** activate the plugin. No fatal, no warning, no notice, no deprecation, no "unexpected output" message. Confirm `wp_adz_views` is created. Confirm activation makes **no** external HTTP request.
5. Manual smoke test of every display mode, before and after the fix: popup, within-page, thru-page, the `[Adzworld]` shortcode with and without `adz_sequence`, and `[adzworld_iframe]`. Test both the first view and the repeat view, since several bugs only appear on the repeat path.
6. Regression checks for the specific bugs found: the shortcode with no `adz_sequence` must not fatal (4.1); Quick Edit on an `adz_ad` post must not blank the three affiliation meta fields (4.7); the thru-page close button must record the view and reload (4.8); the footer `style` attribute must be well-formed in rendered output (4.5).
7. Security regression checks, each of which must now fail safely: POST `adz_ad_options[ad_settings][0][rotation_id]=siteurl` to the home page and confirm `siteurl` survives; call the ad AJAX action with `adz_template=../../../../wp-config.php` and confirm nothing is included; call `get_template_settings` while logged out and confirm it is refused; POST a quoted string in `target_type` and confirm no SQL error and no injection.
8. Confirm the adz.world Terms of Service and Privacy Policy URLs in the readme actually resolve — the team said they will check them.
9. Bump `Version:` in the plugin header and `Stable tag:` in `readme.txt` to the same new value. Commit to SVN `trunk/`, then copy to `tags/<version>/`.
10. Reply to the Plugins Team email, concisely. They asked explicitly **not** to be sent a changelog. Confirm the checklist, and explain only the things a re-scan might flag that are correct by design.

---

## 11. What has NOT been verified, and must be

- **The current stable WordPress major version.** This container's network policy blocks `wordpress.org`, so `Tested up to:` could not be confirmed. Check `wordpress.org/download/` and test against it before committing that number.
- **Whether adz.world's REST API is still running**, and whether its endpoints still match what the plugin calls (`wp-json/adz_server/v1/ads`, `/register`, `/categories`, `/ads/{id}`, `/ads/is_visitor_logged`). If the server has changed or gone away, the remediation is a different and larger job than this brief describes.
- **Whether adz.world currently publishes Terms of Service and Privacy Policy pages.**
- **Runtime behaviour.** Everything in Section 4 was established by reading the code, not by executing it on a live WordPress install. The reasoning is given for each so it can be checked rather than trusted — do check it.
- `vendor/eof/` (the eof settings framework, ~20 files) was read only where `adz-config.php` reaches into it. It needs its own pass for escaping and nonces before re-review, since the team reviews the whole package including bundled libraries.
