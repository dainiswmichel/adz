# Best Buy fetcher handoff

The fetcher is implemented in `includes/adzworld-bestbuy.php` and loaded by
`adz-world.php`. It adds the Best Buy API key and Impact Partner ID fields to
the existing affiliate accounts through `adzworld_affiliate_networks`.

It accepts the complete category-plus-refinements request, requests three
products from the official Best Buy Products API, and normalizes each result
to `image`, `title`, `price`, and Best Buy's attributed `affiliateUrl`. It does
not emit the untracked product URL when `affiliateUrl` is missing.

The adapter contributes normalized results through the existing
`adzworld_affiliate_product_results` filter. The assembled production plugin
must retain that filter call in its shared inner-carousel collector. The other
extension hooks used by the adapter are:

- `adzworld_registration_catalog`
- `adzworld_getpaid_affiliate_serves_adz`
- `adzworld_affiliate_account_ready`

Run the focused contract from the repository root:

```sh
php tools/tests/test-bestbuy.php pre-release-candidates/codex-gpt5
```

No Best Buy API key or Impact Partner ID is stored in this candidate. A real
HTTP result remains an integration test for an administrator with both values.
