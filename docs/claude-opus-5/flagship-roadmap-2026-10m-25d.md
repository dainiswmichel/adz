# adz-world.com — flagship concept, site map and journeys

**Author:** claude-opus-5 · **Date:** 25 October 2026 · **Status:** proposal for discussion

No figure, testimonial or earnings claim in this document is real. Where a
number would appear, the placeholder says so. The site must not launch with
invented statistics, and must not suggest the WordPress Plugins Team endorses
adz-world.

## The idea the site has to carry

Every other advertising site argues from the advertiser's side: reach, targeting,
conversion. This one argues from the reader's: **you were never asked.**

The site's job is not to describe permission-based advertising. It is to be the
place where a visitor experiences it, within about fifteen seconds of arriving,
and notices the difference themselves.

## Demonstrated, not advertised

adz-world.com runs ordinary surveillance advertising in its own slots — Google
and the rest, exactly as a normal publisher would. Among them, in rotation, the
panel appears:

> **Would you like the adz on this site to be based on what you actually want?**

A visitor types what they want. From that moment the ad slots on that page serve
against what they typed. The contrast is the argument, and it is made with the
site's own inventory rather than with a claim.

This is the only part of the site that cannot be compromised. If it is slow,
ugly, or produces a poor match, nothing else on the site matters.

## Site map

```
/                       the demonstration, above the fold
/how-it-works           the three parties, the money, in plain words
/for-publishers         what a site owner gets, and the 10%
    /get-started        install, switch on, first adz
    /earnings           how the statement works, when money is payable
/for-advertisers        how a producer lists an offer
    /list-an-offer      self-serve, performance-based, no budget required
/for-readers            what is and is not recorded about you
    /your-preferences   set, change, expire, delete
/the-case               why surveillance advertising wastes advertiser money
/plugin                 the WordPress plugin
/pricing                one line: 10% of commission received. Nothing else.
/terms  /privacy  /disclosure
/account                sign in — preferences, and for publishers, earnings
```

Eleven top-level destinations. A site about not wasting people's attention
cannot have a cluttered navigation.

## Visitor-to-publisher journeys

### A reader who has never heard of this

1. Arrives on an article from search or a link.
2. Sees ordinary ads, ignores them, as always.
3. In one slot, the panel: *would you like the adz here to be based on what you actually want?*
4. Types "two-seater couch". No sign-up, no email, no account.
5. The slots change. One shows a couch. One shows a local upholsterer.
6. **The moment.** They came for an article and the advertising became useful.
7. A quiet line under the adz: *you told us what you wanted. We didn't follow you here.*

Nothing is required of them. The session carries the preference. If they close
the browser and lose it, that is the design, not a failure — there is no profile
to restore because none was built.

### A reader who comes back

1. Returns. The preference may or may not have survived.
2. If it has: adz still match. If not: the panel offers again, no lecture.
3. Offered, once, lightly: *save this so it follows you here?* — that is the
   guest step, and the only reason to take it is that it is useful.

### A publisher

1. Arrives at `/for-publishers` from the plugin directory or a link.
2. Reads one screen: your readers choose their adz, you keep 90% of commission.
3. `/get-started`: install the plugin, switch monetization on, write one adz.
4. Sees their own site serving a matched adz within the first session.
5. Returns later to `Adz > Earnings` for a statement that distinguishes pending
   from settled, and says plainly what is theirs.

### A producer with no ad budget

1. Arrives at `/for-advertisers`. The headline is the offer: **you pay only when
   it works.**
2. Creates a free profile, lists what they sell, the offer, the area they serve.
3. No budget, no bid, no minimum.
4. Their offer appears when somebody types something it answers.

## Roadmap

**Phase 1 — the demonstration.** The home page and the panel, working, on real
inventory. Nothing else needs to exist. Until this works there is no site.

**Phase 2 — the reader's side.** `/for-readers`, `/your-preferences`, the guest
account, set/change/expire/delete. The promise is checkable on screen rather
than asserted in a policy.

**Phase 3 — the publisher's side.** `/for-publishers`, `/get-started`,
`/earnings`, `/plugin`. Matched to whatever the plugin actually does at that
point, not to what it is planned to do.

**Phase 4 — the producer's side.** `/for-advertisers`, `/list-an-offer`,
self-serve listing. This is where local supply comes from.

**Phase 5 — the argument.** `/the-case`, with real figures from real sources,
cited. Nothing fabricated. Advertisers respond to waste numbers in a way they do
not respond to ethics.

Phases 4 and 5 depend on the settlement service existing. Phases 1 to 3 do not.

## Design direction

Fast enough that the demonstration never stutters — a site arguing that
advertising should respect people cannot be slow because of its own scripts.
Accessible to WCAG 2.2 AA, because excluding readers while claiming to respect
them would be incoherent. Responsive, since most readers arrive on a phone.
Restrained: one typeface, generous space, no carousels, no modals, no cookie
wall. The absence of the usual hostile furniture is itself part of the argument.

## VGM — visitors, guests, members

Settled:

- **Visitors** — preferences live in a session. Persistence is not guaranteed
  and this is by design: no account, no profile, nothing to lose.
- **Guests** — logged in, preferences persist across sessions and devices, with
  the start and stop dates the 2018 `visitor_preference` record already carried.

**Members — undecided.** Four proposals, with what each costs.

**(a) Earning.** Members accrue a share of commission from their own purchases,
redeemable against a participating publisher's subscription. Strongest reason to
sign up; the reader is paid for attention they chose to give.
*Cost:* needs purchase attribution back to a person, which means a per-visitor
sub-identifier reaching the merchant. That is a real privacy trade and it must
be the member's explicit choice, not a condition of membership.

**(b) Portability.** A member's preferences are honoured at every participating
site, set once. The clearest expression of the original idea.
*Cost:* needs a shared preference store, which is the one piece that cannot be
local. Architecturally the largest item on this list.

**(c) Standing requests.** A member posts what they are looking for — "a bicycle
for a nine-year-old, by March" — and producers answer it.
*Cost:* inverts the model from matching to a marketplace. Needs moderation.
Possibly the most valuable thing here and certainly the most work.

**(d) Transparency.** A member sees every adz they were shown, why, and what it
earned the publisher. Nothing new is collected; it is a view onto what already
exists.
*Cost:* lowest of the four. Also the most defensible, since it proves the claim
rather than asserting it.

**No paid tier is proposed.** Charging readers to escape advertising is the
model this is arguing against.

Recommendation for discussion rather than decision: (d) is cheap, provable and
consistent. (a) is the strongest incentive and carries the real trade-off. (b)
is the original vision and the biggest build. (c) is the most interesting and
the least defined.

## Contributing to the WordPress community

Things that are genuinely useful and claim no endorsement:

- Release the plugin GPL and keep the free tier genuinely free — no feature
  withheld to force an upgrade.
- Publish the external-service disclosure as a worked example. The directory's
  guidance is thin and most plugins get it wrong.
- Contribute the consent pattern itself: a plugin that monetizes a publisher's
  site, asks first, and takes no for an answer, is a pattern the ecosystem is
  short of.
- Answer support forum threads about permission-based advertising without
  pitching.

**Never claim or imply that the Plugins Team endorses adz-world.** They review
plugins; they do not endorse them. Saying otherwise would be false and would
jeopardise the listing.
