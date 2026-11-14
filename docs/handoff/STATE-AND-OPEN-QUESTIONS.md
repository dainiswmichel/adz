# adz-world: where things stand, what is wrong, what is next

Written 14 November 2026. Branch `claude/design-display-library`, commit `293a71c`.
Candidate: `pre-release-candidates/claude-code-web/`.

## Read this first

Several pieces of this plugin were written by coding agents without telling
Dainis what they had decided. Each one replaced something he had specified
with something an agent assumed. They are listed below under **Code added
without the owner's knowledge**. They are not stylistic complaints. Each one
changes what a reader sees, and each was found only by reading the source
line by line.

If you are an agent working on this plugin: do not add behaviour Dainis has
not asked for, and do not make a design decision inside a commit. Say what you
are about to do, then do that.

## Situation

The plugin was closed by WordPress.org on 29 September 2026 for a guideline
violation the notice did not name. The correction window runs to roughly
28 November. **The technical findings below do not identify the closure's
cause; that question is still open and nobody has asked the plugins team
directly.**

Six product fetchers are built and deployed: eBay, Etsy, Best Buy, AvantLink,
Apple Search, Travelpayouts. One credential is live (Etsy). AvantLink and
Rakuten applications are in review. eBay developer access is pending with no
date given.

## Code added without the owner's knowledge

**1. `adzworld_ad_thing()` — removed in `c2e3772`.**
Guessed an adzad's category by parsing its title and taking the last noun.
On "Kids bicycle, 20 inch, suits ages 8 to 11" it returned `ages`. That guess
was checked *before* the category the visitor had stated, so the adzad was
filed under `ages` while the visitor's refinements were filed under
`bicycle`, and every refinement scored zero. Refinements silently did
nothing. Fixed in `9dfdfee`; the function is gone and the reader's own
category is checked first.

**2. The rating divided by ten — still present.**
`$score += (int) round( $feedback[ $key ]['score'] / 10 );`
Dainis specified a −100…100 scale, notched by 1. This compresses it to
−10…+10, so 201 positions collapse to 21 and a full −100 moves the result by
the same amount that one matching word adds. A reader saying "never show me
this" leaves the adzad tied with something they have never seen.
Introduced in `a30e2d2`.

**3. `adzworld_score_ad()` — still present.**
A sum of invented constants: 10 baseline, +12 for category membership, +10
per matching term, +4 per attribute, +3 for intent, plus the compressed
rating. An adzad matching nothing scores 22 where a one-term match scores 32.
It is a ranking key and nothing else; it is not a measure of how well an
adzad answered the request, and it is dominated by the baseline and the
category bonus rather than by anything the reader said.

**4. `adzworld_parse_refinement()` — still present, ~110 lines.**
Regex that rewrites what a reader typed into price floors, ceilings, ages,
capacities and negations. It decides what a sentence meant. See the rule
below about not altering reader input.

**5. Substring matching — still present.**
Terms are matched with `strpos` against a concatenation of title, content and
taxonomy terms, so `pedals` matches inside `no pedals`. A refinement of "no
pedals" excludes an adzad whose own copy reads "no pedals".

**6. A stopword list — written and removed in the same session, never
committed.** An agent (Claude, 14 November) added a hardcoded list of words
it had decided were meaningless, including `only`, `find` and `looking`, and
stripped them from what readers typed. It turned "no i want a brown couch"
into "couch no brown", which instructs AvantLink to *exclude* brown. Removed
before commit; recorded in `2177466` so it is not rebuilt.

**7. The showcase discards any result with no image — still present in
`dwm-local`.** `adzworld_affiliate_showcase()` skips a result whose `image`
is empty. Flight fares have no image, so every Travelpayouts result is
dropped silently and the slot stays empty with no error.

**8. Two render paths apply the same filter — still present in `dwm-local`.**
`adzworld_affiliate_markup()` has no callers there, so the shared collector,
its cap, its de-duplication and its https checks never run. The older
`adzworld_affiliate_showcase()` is what renders.

## What has been built and agreed

**Product ids on every row** (`e965d85`). Every provider returns an
identifier and every fetcher was discarding it. eBay `itemId`, Etsy
`listing_id`, Best Buy `sku`, Apple `trackId`, AvantLink `Product SKU`,
Travelpayouts a route-and-date slug. Namespaced `provider:id`. Without this a
reader could reject an API product and the identical one came back, because
nothing recorded which one they had seen.

**The slot journey** (`293a71c`, not yet wired to anything). A slot opens with
a request, which generates the initial adzad. Rounds are numbered from 1:

```
request        couch
  1  ad        ebay:110588014268
     rating    -100
     refinement  no i want a brown couch
  2  ad, rating, refinement
```

Each round's refinement generates the next round's adzad. The request handed
to a provider is the opening request followed by every refinement, each one
quoted and whole:

```
couch "no i want a brown couch" "show only 3 seater brown couches" "under $900"
```

**Serving without earning** (`7707704`). Every fetcher serves on the
developer credential alone so a pending application does not leave a slot
empty, and a card says "Serving — not earning yet" when it cannot be
credited. Dainis has said the default should be the opposite — a provider
that cannot pay should not serve — with an explicit setting for a showcase
site, which is the one place serving without earning is the point. **Not yet
built.**

**Get Paid fixes** (`034e860`): one status badge with three states rather
than a badge plus a contradicting paragraph; a saved secret shows dots rather
than an empty box; the enable control reads "Active" and says that clearing
it pauses rather than erases; each card has its own save button.

## Rules this plugin is held to

1. **Never alter what a reader typed.** Their words go to the provider as
   written. Whole statements may be carried or not carried; none is edited,
   stemmed, stripped or reworded. A derived or cleaned form may exist beside
   the verbatim record, computed when needed, never written over it.
2. **No invented constants.** If a number decides behaviour, it comes from
   something real or from Dainis.
3. **Nothing decided inside a commit.** State the decision first.
4. **Report what was actually run,** including failures. A check that did not
   run is not a pass.
5. **`phpcs:ignore` is not used.** The candidate contains none.

## Open, and still needing discussion

- **What replaces the composite sort.** The order of adz is needed. The
  current sum is not defensible. No replacement is agreed.
- **What a −100 should do.** Demote, or remove. Today it does neither
  meaningfully.
- **Colour.** Two written proposals, neither chosen: Grok's six hues in one
  blue–violet–rose band, and Claude's eight across the wheel. Both assign a
  hue by hashing the category string rather than by list position, which is
  the current fault — position 1 is hue 172, a green that reads as success.
  Both collide at the bucket counts proposed. Agreed separately: the rotation
  a visitor sees and the Get Paid screen an adzAdmin sees are different
  surfaces with different palettes, and status colour belongs only to the
  second.
- **Relevance to the visitor is the only thing that matters on the front
  end.** How that is shown is not settled.
- **One provider per slot**, for now. The round-robin interleaving in
  `adzworld_products_collect()` was built for a merged pool and does not fit
  that.
- **With no refinement from the reader**, an adzAdmin parameter decides
  whether the same adzad shows again or a similar one does. Not built, and
  "similar" is not yet defined.
- **The WordPress.org closure reason.** Still unknown. This is the only item
  with a deadline.

## Verification as of this commit

```
php -l                     clean across the candidate
PHPCS WordPress            0 errors, 1 warning (base64_encode for HTTP Basic auth)
phpcs:ignore               none in the candidate
test-product-merge         35/35
test-ebay-fetcher          57/57
test-itunes-fetcher        39/39
test-avantlink-fetcher     48/48
test-travelpayouts-fetcher 51/51
tests/test-matching        29/29
tests/test-preferences     42/42
```

No live call has been made to eBay, Etsy, Best Buy, AvantLink or
Travelpayouts. Apple Search was called live and returned real products; a
refined request returned different products. Everything else is proven
against recorded or documented response shapes only.
