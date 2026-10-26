# adz-world legal drafts

Status: review draft. This document is not legal advice, not an approved policy, and not proof of compliance. It must not be published as a completed legal page.

Operator name used in these drafts: adz-world, USA.

Unresolved, and left unresolved on purpose:

- legal entity (no corporation, LLC, or other form is named here)
- business address
- applicable US state
- whether the hosted business is money transmission or another regulated activity in any jurisdiction
- tax forms, withholding, and who is the merchant of record
- which affiliate networks, if any, will settle commissions to adz-world
- payout countries
- final payout provider (Stripe, PayPal, both, or neither)
- the WordPress.org guideline that closed the plugin on 29 September 2026 (Review ID GUIDELINES adz-world/dainismichel/29Sep26/T1 29Sep26/4.3). Guideline 10 is relevant to a monetization design. It is not an established explanation of that closure.

The plugin is free. Installation is not consent.

## 1. Service terms and publisher revenue-share agreement

Parties. These terms would be between the publisher who operates a WordPress site and adz-world, USA. The entity that can bind adz-world is not identified yet. Until it is, there is no contract.

Plugin. The adz-world WordPress plugin is free software under GPLv3. Using it to serve adz that the publisher creates on that site does not by itself enroll the publisher in the monetization service.

Opt-in. Monetization requires a clearly disclosed, explicit acceptance by a site administrator (`manage_options`) through the plugin’s Earnings screen. A network checkbox, a settings save, or activation does not accept these terms.

What the service would do. After opt-in, adz served through the adz-world monetization service use adz-world’s affiliate or referral codes. The plugin does not rewrite the publisher’s ad markup and does not replace a referral code or affiliate identifier the publisher already stored.

Commission basis. The only amount that enters the split is a settled affiliate commission actually received by adz-world. adz-world retains 10%. The publisher’s share is 90%. The split is not 10% of product sales, advertiser spend, clicks, impressions, or estimated earnings.

Arithmetic. For one currency, in integer minor units, the platform share is 10% of the absolute settled amount, rounded half-up by `intdiv(absolute + 5, 10)`. The publisher share is the remainder. The two shares equal the settled amount, including for a negative settled amount. That equality does not make the publisher share payable.

Authority. adz-world.com is the proposed authority for verified receipts, attribution, accounting, and payouts. The plugin displays an authenticated statement. It is not the books. No statement service is connected in this candidate.

Currency. Each currency is a separate balance. Unlike currencies are not added. The plugin does not invent an exchange rate.

Network. “Network” is not a settled product. The existing plugin switch keeps its current name and behavior, with the added requirement that the terms be accepted before it can call out. Publishers may later organize their own arrangements. These terms do not create a centralized network business.

No live money movement is authorized from this draft.

## 2. Privacy and data handling

This is a draft notice, not a published privacy policy.

Data that stays on the publisher’s site when the network switch is off:

- ad-view rows in `{prefix}adz_views`, which can include a visitor IP, a target id, a target type, a count, and timestamps, so the same ad is not repeated on the same target the same day
- visitor preference category ids in the `adzworld_prefs` session cookie (the browser is asked to drop it when the session ends; persistence is not guaranteed)
- a logged-in guest’s preference category ids in that user’s meta key `adzworld_pref_terms` on that site
- the monetization-acceptance flag `adzworld_terms_accepted`
- settings the publisher typed, including a referral code, a PayPal email, an adz-world email, and an Amazon affiliate id

Audience, as defined for this draft. The words are additive. A more specific word does not retire “access.”

- Every audience party is an access. That includes a visit, a guest, a member, and a bot.
- An unknown human access is also a visit.
- A guest is a human we know. That party is an access, a visit, and a guest. On a publisher site the profile is the WordPress account.
- A member is a guest who has made an adz-world purchase. That party is an access, a visit, a guest, and a member.
- A bot cannot be a guest. Only a human can be a guest. A bot cannot be a member.
- A bot can access the site as a guest, or access the site as a paying member. The bot remains the accessor. The guest or member is the human role it is accessing as.

The plugin does not record a purchase, does not grant a WordPress capability for one, and does not send preference slugs as a member attribute. A purchase record would belong to adz-world.com. The existing rotation field that can target a WooCommerce membership plan is the publisher’s own plan. It is not this member definition.

Data that can leave the site only after both the terms are accepted and the existing network switch is on:

- publisher id and access tokens already stored for that connection
- the publisher referral code as stored
- `platform_referral_code=adzworld` added beside that code, not instead of it
- site URL, site title, administrator email
- adz-world email, PayPal email, and Amazon affiliate id if the publisher saved them
- preference categories configured in settings, and categories on an ad the publisher publishes
- visitor IP on a network ad request
- ad title and content when an ad is published or deleted

Activation does not transmit any of this.

The plugin does not store a Stripe secret, a PayPal secret, or a statement credential. Payment-provider secrets belong on the service, outside publisher installations and outside source control.

Purpose. Local view rows exist to pace ads. Preference data exists to let a person limit categories. Outbound fields exist to request and publish adz through the service the publisher opted into.

Retention and deletion are unresolved until the operator and the service exist. A publisher can disconnect the switch. That stops further outbound requests. It does not by itself delete rows already stored on the publisher’s site or on a future service.

## 3. Affiliate disclosure and explicit monetization consent

Disclosure to the publisher, before acceptance: ads served through the monetization service would carry adz-world’s affiliate or referral code; adz-world would retain 10% of settled affiliate commission it actually receives; the publisher share would be 90%; the plugin is not the record of earnings; links already on the site are not rewritten; stored affiliate identifiers are not replaced.

Consent control in this candidate: the Earnings screen form posts to `admin-post.php` with action `adzworld_accept_terms`, a nonce, and a `manage_options` check. The option is set to `1` only in that handler. There is no acceptance on activation.

Disclosure to visitors is unresolved as page copy. A publisher who earns affiliate commissions because a visitor follows an ad needs a disclosure that fits the publisher’s own jurisdiction and the affiliate program’s rules. This draft does not supply that visitor-facing sentence as a finished notice, because the programs are not identified.

The house code used in the query argument is the literal `adzworld`.

## 4. Payout, reversal, fee, and currency policy

Fee. The only fee defined here is the 10% share of settled affiliate commission actually received by adz-world. There is no subscription, setup fee, or percentage of sales in this draft.

Payable is not the same as calculated. A statement would need separate integer minor-unit buckets per currency:

- estimates / pending
- verified settled affiliate commission
- adz-world 10% share
- publisher 90% share
- reversals / refunds
- adjustments
- available
- reserved / in-flight
- paid

The 10% and 90% must sum to the settled bucket. Available, reserved, and paid are separate facts. A reversal reduces what can be paid. It is not hidden inside the percentage.

Currency. Buckets are not converted and not summed across currencies. If a currency’s minor-unit exponent is unknown to the plugin, the screen does not invent a decimal display.

Payouts. No payout is available from the plugin. Stripe and PayPal are both unconfigured. Sandbox evaluation would happen on adz-world.com, using official APIs, with secrets kept off publisher sites. Final provider choice is open. No live charge or live payout is authorized.

A publisher’s PayPal email in Adz Settings is a value the publisher typed. Storing it does not pay that address.

Reversals, fees charged by a provider, chargebacks, tax withholding, and foreign-exchange conversion are launch dependencies. None of them is calculated in the plugin.

Keeping settlement and payout off the publisher’s server does not establish that the hosted business is exempt from money-transmission or other regulation. That review is still required for the operator, the state, and each payout country.

## 5. WordPress readme external-service disclosure

The candidate `readme.txt` External services section is the disclosure intended to ship with the plugin. It states that the default is no connection, that activation does not call out, that terms acceptance and the network switch are both required, what can be sent, that referral codes are not replaced, that the earnings screen is not a balance, the 10/90 settled-commission basis, and that Stripe and PayPal are not called. Draft term and privacy URLs are named as drafts.

Those URLs must not be filled with a placeholder entity name, address, or contact presented as final.
