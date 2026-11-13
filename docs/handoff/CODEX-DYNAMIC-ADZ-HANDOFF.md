# Codex handoff: dynamic adz integration

Branch `claude/design-display-library`, commit `0411631`.
Source: `pre-release-candidates/claude-code-web/`

## What to port into `pre-release-candidates/dwm-local/`

Nine files. Copy the four new ones whole; apply the five core edits by hand,
because `dwm-local` has diverged.

### New files, copy verbatim

| From `claude-code-web/` | Purpose |
|---|---|
| `includes/adzworld-products.php` | Shared collector and card renderer. Load this **before** any provider file. |
| `includes/adzworld-ebay.php` | eBay Browse fetcher |
| `includes/adzworld-etsy.php` | Etsy fetcher (Grok's, plus filter registration) |
| `includes/adzworld-bestbuy.php` | Best Buy fetcher (Codex's, plus provider stamp) |

### Core edits, five of them

1. **`adz-world.php`** — add four `require_once` lines directly after the
   existing `adzworld-affiliates.php` line, in this order: `adzworld-products.php`,
   `adzworld-ebay.php`, `adzworld-etsy.php`, `adzworld-bestbuy.php`.

2. **`includes/adzworld-affiliates.php`**
   - `adzworld_affiliate_networks()`: assign the array to `$networks` and
     `return apply_filters( 'adzworld_affiliate_networks', $networks );`
   - eBay entry gains `clientid` and `clientsecret` fields, pattern
     `/^[A-Za-z0-9\-]{10,80}$/D`.
   - `adzworld_affiliate_markup()`: collect products **before** testing
     `$links`, and return the product markup when `$links` is empty. See the
     note below — this one is load-bearing.

3. **`includes/adzworld-getpaid.php`** — `adzworld_getpaid_affiliate_serves_adz()`
   returns `apply_filters( 'adzworld_getpaid_affiliate_serves_adz', $serves )`.

4. **`includes/adzworld-registrations.php`**
   - `adzworld_registration_catalog()` returns
     `apply_filters( 'adzworld_registration_catalog', $catalog )`.
   - `adzworld_registration_ready()` returns
     `apply_filters( 'adzworld_affiliate_account_ready', $ready, $id, $accounts[ $id ] )`.

5. **`assets/rotation.css`** — the `.adzworld-product*` block and its
   `@container adzworld (max-width: 500px)` variant.

## Two things that will break it if missed

**Do not rename the card classes to `adzworld-creative`.** That class is the
wrapper around a creative a *network* returned, and `rotation.css` carries an
explicit contract that nothing resizes, restyles or clips one. The collision
deforms the cards and puts plugin rules onto network units. The cards are
`adzworld-product`. `tools/test-product-merge.php` asserts this.

**Do not restore the early return in `adzworld_affiliate_markup()`.** Etsy and
Best Buy build a tracked URL per product, not one search link per category, so
they contribute nothing to `adzworld_affiliate_links()`. Returning early on an
empty link list means a site connected only to those two renders nothing.

## Verification to run after porting

```sh
find pre-release-candidates/dwm-local -name '*.php' -print0 | xargs -0 -n1 php -l
.phpcs/vendor/bin/phpcs --standard=WordPress --extensions=php pre-release-candidates/dwm-local/
ADZ_SRC=pre-release-candidates/dwm-local php tools/test-product-merge.php   # expect 31/0
ADZ_SRC=pre-release-candidates/dwm-local php tools/test-ebay-fetcher.php    # expect 49/0
```

Expected: lint clean, PHPCS 0 errors, one `base64_encode()` warning in
`adzworld-ebay.php` which HTTP Basic auth requires and which is not suppressed.

## Release

Version is `1.0.9` in `adz-world.php` and `readme.txt` `Stable tag`. Both must
agree or `.github/scripts/check-version.sh` fails the deploy. **Dainis decides
the new number.** Do not bump it on your own.

Not verified by anyone yet: no live call has been made to eBay, Etsy or Best
Buy. The code is proven against recorded response shapes, not against the live
APIs. First live call happens when Dainis pastes a developer key.
