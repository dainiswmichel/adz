# adz-world.com flagship roadmap, site map, and journeys

Status: candidate proposal for the site. The site is not built here. The shared setup spec remains `docs/adz-world-com-do-and-fast-gate-spec-2026-10m-25d.html`. This file does not replace it. No statistic, testimonial, earnings figure, or “working payout” claim belongs on the site.

The plugin candidate that matches this proposal is `pre-release-candidates/grok-4-6/`. Draft terms live in `LEGAL-DRAFTS.md`. The statement proposal lives in `STATEMENT-CONTRACT.md`.

## Design direction

The flagship should show permission-based advertising by letting a person set it, not by announcing that it is wholesome.

Visual. One full-viewport first screen: a short sentence of what an ad exchange is, the 10/90 rule in words, and a working preference control. Color can start from the plugin’s existing green (`#4A7354` on `#104A1F`) on an off-white field, with white text only on the green band and a contrast check against WCAG AA. No stock photograph of revenue, no dashboard screenshot of a balance, no carousel.

Type and speed. One font stack the browser already has, or a single self-hosted variable font. No third-party font host. Pages are static where the content does not change. No chat widget. Images, if any later, are sized and compressed. Motion is limited to the preference control’s own state, and it respects `prefers-reduced-motion`.

Access. Every control has a visible name. The preference form is reachable by keyboard. Focus is visible. The economics page can be read without a diagram. Layout holds at a narrow viewport and at a wide one: one column under 700px, a two-column text measure above that. Nothing required for understanding is hidden in a hover.

Economics on the page. “adz-world retains 10% of a settled affiliate commission it actually receives. The publisher’s share is 90%.” Under that, the integer example from the plugin, labeled arithmetic: 1000 minor units → 100 and 900. A second line states that the split does not make the amount payable, that currencies are not mixed, and that the public site has no live balance.

Honesty constraints. Unresolved operator facts are not printed as a footer address. Term and privacy routes, if created before the entity exists, carry the same draft banner as `LEGAL-DRAFTS.md`. The Plugins Team is not described as endorsing the plugin or the model.

## Site map

| Path | What a person finds |
| --- | --- |
| `/` | What the exchange is, the 10/90 rule, a session preference control, links onward |
| `/how-it-works` | Publisher site, visitor session, guest account, service as the future book of record |
| `/economics` | Settled-commission basis, minor units, currencies kept apart, what is not a fee |
| `/publishers` | Free plugin, install is not consent, both switches required before any call-out, earnings screen is unconfigured until a statement exists |
| `/visitors` | Session preferences, what is stored, what is not an account |
| `/preferences` | The same session control as the home page, with the persistence limit repeated |
| `/wordpress` | How the project intends to participate in the WordPress community |
| `/roadmap` | The phases below |
| `/legal/terms` | Draft terms. Banner: not a completed legal page |
| `/legal/privacy` | Draft privacy notice. Same banner |
| `/legal/consent` | Affiliate disclosure and the opt-in the plugin actually implements |
| `/legal/payouts` | Payout, reversal, fee, and currency draft |

No `/account` page until a real statement credential exists. A login painted on the flagship would imply a balance.

The setup spec’s operational pages remain the ones a later review fetches (`/terms/` and `/privacy/`). Those routes should serve the draft banner, not a fabricated operator block. Exact DNS, droplet, and compose steps stay in the shared spec.

## Journeys

Visitor. Arrives at `/`. Reads that an ad is shown because the publisher asked for an exchange, not because a script followed them. Sets categories on `/` or `/preferences`. The choice is posted to the publisher’s own WordPress site when this control is the plugin shortcode `[adzworld_preferences]`, or held in the flagship session when the control is the site demo. The page says the session may not hold. The visitor is not asked for an email. No earnings number appears.

Guest. Already has, or creates, a WordPress account on a publisher site. Opens the same shortcode while logged in. The form says the categories are saved on that account on that site (`adzworld_pref_terms`). Logging out does not publish those categories to other sites. The flagship can explain this; it does not need a second account system to explain it.

Publisher. Installs the free plugin. Serves the publisher’s own adz with the network switch off and sees no outbound requirement. Opens Earnings, reads the draft terms, and either leaves or accepts. Acceptance still does not call out until the existing network switch is on. The statement table reads Unconfigured for every bucket. Stripe and PayPal read unconfigured. The referral code field still shows the publisher’s own code. No payout button is offered.

## Member proposals

Member capabilities are not implemented. Three proposals for a later decision:

1. No member tier. Guests are logged-in users with preferences on that one site. Tradeoff: nothing new to misunderstand, and no cross-site preference. Community participation stays in the plugin’s consent behavior rather than in a rank.

2. Publisher-local lists. A publisher, using capabilities WordPress already gives them, groups logged-in guests on that site (for example a private category of ads those guests asked for). Tradeoff: useful on one site, no adz-world-wide privilege, no payment, and no central directory of people.

3. Portable categories. A person later opts in, on the service, to let category slugs travel to other publisher sites. No WordPress capability is added. No email is part of the offer. Tradeoff: this needs the statement service’s account model, a separate consent record, and a deletion path. It is easy to confuse with a paid rank, so the interface would have to say that it is not a purchase. This candidate does not build it.

Paid tiers are not part of any proposal.

## WordPress community

The plugin remains free and GPLv3. The contribution that can be demonstrated now is the consent mechanic: default is no phone-home, acceptance is a separate administrator action, external calls are described in `readme.txt`, and a visitor can set categories without creating an account.

Later, if the operator wants a public note, a make.wordpress.org post can describe that pattern and link the plugin. The post would not say the Plugins Team endorses adz-world, the revenue share, or permission-based advertising as a project of WordPress.

Guideline 10 is the guideline that speaks to monetization and service disclosure. It is not claimed here as the reason the 29 September 2026 closure was issued. The closure’s guideline is still unnamed.

## Roadmap

Through 28 November 2026. Remediation evidence on the candidate, this statement contract, these drafts, and no live payout. The integrated plugin tree is unchanged until a compile file selects individual files. The flagship droplet in the setup spec is not required for this writing.

After a plugin review path exists. Publish the flagship pages in the site map with draft banners on every legal route. Evaluate Stripe and PayPal sandbox accounts on the service, not in the plugin. Record eligibility, countries, fees, and whether either provider will pay this kind of split. Still no live charges.

After a settlement feed is real. Implement the read API in `STATEMENT-CONTRACT.md` on adz-world.com, issue site read credentials, and only then let the plugin display a validated statement. Payouts stay on the service state machine. Provider choice happens here, with the open dependencies answered.

Member choice. One of the three proposals above, decided before any of them is coded.

Operator identity. Entity, address, and state, decided before any legal route drops its draft banner.
