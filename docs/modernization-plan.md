# Accord — modernization plan

Relaunch of the adz.world plugin (1.0.8, September 2018) as a standalone,
permission-based advertising plugin for WordPress, shipped as an update to the
existing wordpress.org listing within 60 days.

---

## 1. The name

**Accord** — display name `Accord — Permission-Based Advertising`.

Drawn from the plugin's own language: the 1.0.8 readme describes bringing about
an "Age of Accordance … where all agreements are made in the spirit of enjoyable
mutual consent." An accord is an agreement freely entered by all parties, which
is exactly the three-way arrangement the plugin brokers: visitor, publisher,
advertiser.

Alternates if `Accord` is taken: **Adz Accord**, **Accordance**, **Handshake**.

**Check availability before committing**: search wordpress.org/plugins for the
name, and confirm no trademark conflict in the advertising space.

### The slug does not change

The wordpress.org URL stays `wordpress.org/plugins/adz-world/` forever. Slugs
cannot be renamed after approval. This is a feature, not a problem:

| Path | Install base | Reviews & history | Review queue | Risk |
|---|---|---|---|---|
| **Update existing listing** | kept | kept | none | low |
| New submission under new slug | lost | lost | full re-review | rejection, duplicate flag |

Update in place. The old slug in the URL costs nothing; nobody types plugin URLs.

---

## 2. What the original actually was

Worth stating plainly, because the relaunch keeps the idea and discards the
plumbing.

**The idea:** a visitor agrees to watch an ad in exchange for access to content.
They declare in advance what kinds of ads they are willing to see. The publisher
serves those ads. Nobody is tracked, profiled, or followed between sites.

**The plumbing (all of it now gone or obsolete):**

| Component | Status |
|---|---|
| adz.world central server | gone; domain lost |
| Amazon PA-API 4.0 (`ItemSearch`, `ItemLookup`) | shut down 31 Oct 2019 |
| Amazon Associates earnings codes (the account) | revoked |

**Amazon affiliate ads are in scope now.** The retired API was only used to
auto-generate product ads from a keyword search. An affiliate ad needs no API:
the publisher writes the ad and pastes their own Associates tag into the link.
The per-ad fields for this already exist (`affiliation_network_name`,
`affiliation_network_url`, `affiliation_id`). Keyword auto-generation is the
only part that needs an API, and it is optional.
| EOF options framework (bundled, 2014-era) | unmaintained |
| `chosen.jquery.js` (bundled) | superseded by core's selectWoo |
| Bundled PHP REST client | superseded by `wp_remote_*` |

**The crown jewel — and it is inherently cross-site:** the network held a
`visitor_preference` custom post type. A visitor set their ad preferences ONCE,
at adz.world, with start and stop dates (`show_adz_starting_on`,
`stop_showing_adz_on`). Every participating site then served in accordance with
those preferences.

That portability is the product. A visitor states their terms a single time and
every consenting publisher honours them — the opposite of being profiled
separately by every site they visit. It is not a per-site setting and cannot be
reduced to one without losing the point.

**This means a shared preference store is required, not optional.** It is the
one piece that genuinely cannot be local. What it does NOT have to be is a
single company's server that can be seized or shut off — see Open Decisions.

---

## 3. Architecture

Three parties, no intermediary, no platform account in the loop:

```
  VISITOR                  PUBLISHER                 ADVERTISER
  declares what         runs the site,            pays the publisher
  adz they accept  <->  serves the adz,     <->   directly for placement
  (local account)       holds the gate            (own affiliate link
                                                   or flat fee)
```

Nothing here can be switched off by a third party. There is no API key to
revoke, no affiliate account to terminate, no central server to take down.

### Target file structure

```
accord/
├── accord.php                       Bootstrap: constants, requires, activation
├── readme.txt                       wordpress.org listing
├── uninstall.php                    Clean removal
├── includes/
│   ├── visitor/
│   │   ├── identity.php             Visitor ID (cookie / user), not IP   [DONE]
│   │   ├── state.php                Per-visitor transient state          [DONE]
│   │   └── preferences.php          Declared ad consent + date windows   [NEW]
│   ├── ads/
│   │   ├── post-type.php            adz_ad CPT + ad_taxonomy
│   │   ├── source.php               Local ad lookup and content          [DONE]
│   │   ├── rotation.php             Sequence state, serve-once-then-cycle[DONE]
│   │   └── matching.php             Match ads to declared preferences    [NEW]
│   ├── gate/
│   │   ├── views.php                View records, prepared SQL           [DONE]
│   │   ├── rules.php                Rotation rules: categories/tags/pages
│   │   └── render.php               Thru-page and popup display
│   ├── admin/
│   │   ├── settings.php             WP Settings API (replaces EOF)       [NEW]
│   │   ├── ad-editor.php            Ad meta box, nonce + capability
│   │   └── preferences-ui.php       Visitor-facing consent screen        [NEW]
│   └── compat/
│       └── upgrade.php              1.0.8 -> 2.0 data migration          [NEW]
├── templates/
│   └── template1.php                Ad display template (escaped, a11y)
├── assets/{css,js}/
└── tests/
    ├── bootstrap.php                WordPress stubs + SQLite             [DONE]
    └── run-tests.php                Adversarial suite                    [DONE]
```

`[DONE]` = built and tested on branch `claude/vigilant-brown-3i4cds` (25 tests
passing). `[NEW]` = still to build.

---

## 4. What gets removed

Every one of these is also a wordpress.org review risk, so removing them serves
two purposes.

1. **`vendor/eof/`** — ~40 files of a bundled, unmaintained options framework.
   Replace with the WordPress Settings API. Largest single reduction.
2. **`classes/rest-client/`** — bundled REST client with its own cache layer.
   `wp_remote_get()` / `wp_remote_post()` already do this.
3. **`js/chosen.jquery.js`** — bundle a library core already ships (selectWoo).
4. **Amazon keyword auto-generation only** (`get_result_amazon`,
   `get_product_by_code`) — these call retired PA-API 4.0 operations. Affiliate
   ads themselves stay: they are just links with the publisher's tag.
5. **`session-handling.php`** — options-backed pseudo-sessions, superseded by
   per-visitor transients.
6. **`network_authorization.class.php`** — registration against a dead server.
   Keep the file guarded (already no-ops) only if federation is wanted later;
   otherwise delete.

---

## 5. Security work (status)

| Issue | Severity | Status |
|---|---|---|
| SQL injection, 4 queries via `sanitize_text_field` | critical | **fixed** |
| File inclusion via `$_POST['adz_template']` into `require_once` | critical | **fixed** |
| Gate identity on `REMOTE_ADDR`, shared behind NAT | high | **fixed** |
| `$_SESSION` throttle that never ran (no `session_start`) | high | **fixed** |
| Stale visitor identity cache across user change | medium | **fixed** |
| Missing nonce/capability checks on ad meta box writes | high | **to do** |
| Unescaped output in admin meta box fields | medium | **to do** |
| Unescaped `$ad_text` in templates | medium | **to do** |

---

## 6. 60-day schedule

Working back from submission. Two weeks of slack at the end, deliberately —
wordpress.org review is not instant and a rejection costs a cycle.

**Days 1–10 — strip and replace**
- Remove `vendor/eof/`; rebuild settings on the WP Settings API
- Remove bundled REST client and chosen.js
- Remove all Amazon code paths
- Target: plugin loads clean with no bundled third-party code

**Days 11–22 — the consent core**
- `visitor_preference` with start/stop dates, and the matching that honours it
- Visitor-facing preferences screen (shortcode + block)
- Preference portability across sites — DESIGN DECISION REQUIRED, see Open
  Decisions. This is the core of the product and it needs a shared store.
- Target: a visitor states their terms once, and participating sites honour them

**Days 23–32 — finish the gate**
- Nonce and capability checks on every admin write
- Escape all output, admin and front-end
- Rotation rules UI rebuilt on the new settings layer
- 1.0.8 → 2.0 migration: old `wp_adz_views` rows, old option keys

**Days 33–42 — compliance and compatibility**
- PHP 8.3 clean, WordPress 6.8 tested
- `readme.txt`: new display name, current `Tested up to`, honest description,
  changelog, upgrade notice
- Accessibility pass on the ad overlay (keyboard dismissal, focus trap,
  `prefers-reduced-motion`, screen-reader timer announcement)
- GPL compliance check on every remaining file

**Days 43–50 — verification**
- Full test suite green
- Manual run on a real WordPress install: fresh activation and 1.0.8 upgrade
- Plugin Check plugin clean (wordpress.org's own scanner)

**Days 51–60 — submit and respond**
- SVN commit to the existing `adz-world` repository, tagged `2.0.0`
- Respond to any review feedback

---

## 7. Open decisions

These need your call; none block the work above.

1. **Where portable preferences live.** The one thing that cannot be purely
   local. Needs your decision:
   - a new central site you run (fastest; same seizure risk as before)
   - preferences carried by the visitor (signed token / browser-held profile),
     with sites verifying rather than looking up
   - sites syncing preferences peer-to-peer
   This is the central product question, not a nice-to-have.
2. **Revenue mechanics.** The original split 50/50 between you and the site
   owner, settled through the central server and Amazon. With neither, the
   plugin has no payment rail. Options: ship with none (publishers arrange
   their own advertiser deals), or add direct-payment fields per ad.
3. **Premium tier.** The 1.0.8 changelog refers to a premium version. Decide
   whether 2.0 has one, since it shapes the settings layer.
4. **Delete or keep `network_authorization.class.php`.** Keep only if
   federation is on the roadmap.
