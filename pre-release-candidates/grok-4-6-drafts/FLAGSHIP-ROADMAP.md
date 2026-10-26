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
| `/how-it-works` | Publisher site, access, human visit, guest profile, service as the future book of record |
| `/economics` | Settled-commission basis, minor units, currencies kept apart, what is not a fee |
| `/publishers` | Free plugin, install is not consent, both switches required before any call-out, earnings screen is unconfigured until a statement exists |
| `/access` | Session preferences for an access with no profile, and what a human visit, a guest, and a member are |
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

Access. Every arrival at `/` is an access. The party may be human, AI, or still unknown. With no profile, categories on `/` or `/preferences` are held for the session, and the page says the session may not hold. No email is required. No earnings number appears. The more specific words are added under access. Access is not withdrawn.

Visit. An unknown human access is also a visit. The flagship adds “visit” for a human. A bot’s access is not a visit.

Guest. A human we know. That party is an access, a visit, and a guest. On a publisher site the profile is the WordPress account. `[adzworld_preferences]`, while that account is logged in, saves categories on that profile (`adzworld_pref_terms`). A profile alone does not prove the party is human. A bot cannot be a guest. A bot can access the site as a guest.

Member. A guest who has made an adz-world purchase. That party is an access, a visit, a guest, and a member. The flagship can show a purchase only after adz-world.com has a real purchase record. This candidate does not invent the record, does not treat a login as a purchase, and does not grant a WordPress capability because of one. A bot cannot be a member. A bot can access the site as a paying member. Publisher WooCommerce membership plans already available in a rotation are that publisher’s plans, not this definition.

Publisher. Installs the free plugin. Serves the publisher’s own adz with the network switch off and sees no outbound requirement. Opens Earnings, reads the draft terms, and either leaves or accepts. Acceptance still does not call out until the existing network switch is on. The statement table reads Unconfigured for every bucket. Stripe and PayPal read unconfigured. The referral code field still shows the publisher’s own code. No payout button is offered.

## WordPress community

The plugin remains free and GPLv3. The contribution that can be demonstrated now is the consent mechanic: default is no phone-home, acceptance is a separate administrator action, external calls are described in `readme.txt`, and an access can set categories for a session without creating a profile.

Later, if the operator wants a public note, a make.wordpress.org post can describe that pattern and link the plugin. The post would not say the Plugins Team endorses adz-world, the revenue share, or permission-based advertising as a project of WordPress.

Guideline 10 is the guideline that speaks to monetization and service disclosure. It is not claimed here as the reason the 29 September 2026 closure was issued. The closure’s guideline is still unnamed.

## Roadmap

Through 28 November 2026. Remediation evidence on the candidate, this statement contract, these drafts, and no live payout. The integrated plugin tree is unchanged until a compile file selects individual files. The flagship droplet in the setup spec is not required for this writing.

After a plugin review path exists. Publish the flagship pages in the site map with draft banners on every legal route. Evaluate Stripe and PayPal sandbox accounts on the service, not in the plugin. Record eligibility, countries, fees, and whether either provider will pay this kind of split. Still no live charges.

After a settlement feed is real. Implement the read API in `STATEMENT-CONTRACT.md` on adz-world.com, issue site read credentials, and only then let the plugin display a validated statement. Payouts stay on the service state machine. Provider choice happens here, with the open dependencies answered.

Audience status. The more specific words are added under access. Access stays for every party. An unknown human is also a visit. A known human is an access, a visit, and a guest. A member adds an adz-world purchase. A bot cannot be a guest or a member, and can access the site as either. The purchase record waits on the service. No extra privilege is coded ahead of that record.

Operator identity. Entity, address, and state, decided before any legal route drops its draft banner.
