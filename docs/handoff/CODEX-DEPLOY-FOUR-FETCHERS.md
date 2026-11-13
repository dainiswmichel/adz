# Codex deploy: four fetchers and the UI

Source: `pre-release-candidates/claude-code-web/` on branch
`claude/design-display-library`, commit `e7dfd33`.
Target: `pre-release-candidates/dwm-local/` (the release tree).

The live site currently runs code that is in no commit on any branch. Reconcile
that first or this port will be guesswork. Nothing below is safe to apply to a
tree you cannot diff.

## 1. New files, copy whole

| From `claude-code-web/` | What it is |
|---|---|
| `includes/adzworld-products.php` | Shared collector and card renderer. **Load before any provider file.** |
| `includes/adzworld-ebay.php` | eBay Browse |
| `includes/adzworld-etsy.php` | Etsy (Grok's, plus filter registration) |
| `includes/adzworld-bestbuy.php` | Best Buy (Codex's, plus provider stamp) |
| `includes/adzworld-avantlink.php` | AvantLink ProductSearch |

## 2. `adz-world.php`

Five `require_once` lines, directly after the existing
`adzworld-affiliates.php` line, in this order:

```php
require_once __DIR__ . '/includes/adzworld-products.php';
require_once __DIR__ . '/includes/adzworld-ebay.php';
require_once __DIR__ . '/includes/adzworld-etsy.php';
require_once __DIR__ . '/includes/adzworld-bestbuy.php';
require_once __DIR__ . '/includes/adzworld-avantlink.php';
```

## 3. Five filter seams in core

Each provider file registers against these. A provider whose filter is hooked
but never applied is dead code that fetches nothing — that was the state of
the Best Buy fetcher before this work.

| File | Function | Change |
|---|---|---|
| `includes/adzworld-affiliates.php` | `adzworld_affiliate_networks()` | assign to `$networks`, `return apply_filters( 'adzworld_affiliate_networks', $networks );` |
| `includes/adzworld-getpaid.php` | `adzworld_getpaid_affiliate_serves_adz()` | `return apply_filters( 'adzworld_getpaid_affiliate_serves_adz', $serves );` |
| `includes/adzworld-registrations.php` | `adzworld_registration_catalog()` | `return apply_filters( 'adzworld_registration_catalog', $catalog );` |
| `includes/adzworld-registrations.php` | `adzworld_registration_ready()` | `return (bool) apply_filters( 'adzworld_affiliate_account_ready', $ready, $id, $accounts[ $id ] );` |
| `includes/adzworld-affiliates.php` | `adzworld_affiliate_markup()` | collect products **before** testing `$links`; see §5 |

## 4. Other core edits

- `includes/adzworld-affiliates.php`: the eBay entry gains `environment`
  (choices `production`, `sandbox`), `clientid` and `clientsecret`
  (pattern `/^[A-Za-z0-9\-]{10,80}$/D`).
- `includes/adz-preferences.php`: in `adzworld_ad_category()`, the visitor's
  `$pref['categories']` loop runs **before** the fallback, and the fallback is
  `adzworld_ad_assigned_category( $post )`. `adzworld_ad_thing()` is gone —
  update its other caller in `adzworld_catalog_categories()`.
- `includes/adzworld-feedback.php`: `adzworld_render_request_panel()` plus its
  call inside `adzworld_feedback_control()`; the summary label is **Refine**,
  not Improve.
- `includes/adzworld-rotation.php`: the dot loop gains an
  `adzworld-live-position` class for `'r' === $slot['type']`.
- `assets/rotation.css`: the `.adzworld-product*`, `.adzworld-request*` and
  `.adzworld-dots` rules, including the
  `@container adzworld (max-width: 500px)` variant.

## 5. Four things that will break it if missed

1. **Do not rename the card classes to `adzworld-creative`.** That class wraps
   a creative a *network* returned and `rotation.css` carries an explicit
   contract that nothing resizes, restyles or clips one. The cards are
   `adzworld-product`. `tools/test-product-merge.php` asserts this.
2. **Do not restore the early return in `adzworld_affiliate_markup()`.** Etsy,
   Best Buy and AvantLink build a tracked URL per product rather than one
   search link per category, so they add nothing to
   `adzworld_affiliate_links()`. Returning early on an empty link list means a
   site connected only to those renders nothing.
3. **Load `adzworld-products.php` before the provider files.** They register
   against filters it applies.
4. **No `phpcs:ignore`.** The candidate contains none. If your port needs one,
   the port is wrong.

## 6. Verify, and report actual output

```sh
find pre-release-candidates/dwm-local -name '*.php' -print0 | xargs -0 -n1 php -l
.phpcs/vendor/bin/phpcs --standard=WordPress --extensions=php pre-release-candidates/dwm-local/
for t in tools/test-avantlink-fetcher.php tools/test-ebay-fetcher.php \
         tools/test-product-merge.php tools/tests/test-matching.php \
         tools/tests/test-preferences.php; do
  ADZ_SRC=pre-release-candidates/dwm-local php "$t"
done
```

Expected: lint clean; PHPCS **0 errors** with one `base64_encode()` warning in
`adzworld-ebay.php` that HTTP Basic auth requires; **47 / 57 / 31 / 29 / 42**,
zero failures.

## 7. What each provider needs to serve

None of them blocks the release. All four serve products on a developer
credential alone and start earning when the affiliate id is added later.

| Provider | Serves products with | Earns with |
|---|---|---|
| eBay | App ID + Cert ID | + campaign id (EPN) |
| Etsy | keystring + shared secret | + Rakuten site id and Etsy advertiser id |
| Best Buy | API key | + Impact Partner ID |
| AvantLink | affiliate id + website id | the same two; the Buy URL is already tracked |

## 8. Release

Version is `1.0.9` in `adz-world.php` and `readme.txt` `Stable tag`. Both must
agree or `.github/scripts/check-version.sh` fails the deploy. **Dainis decides
the new number.** Do not bump it yourself, do not force-push, do not rewrite
history, do not delete `.git`.

Not verified by anyone: no live call has been made to any of the four APIs.
Each is proven against recorded or documented response shapes only.
