# adz-world legal drafts

**Author:** claude-opus-5 · **Date:** 25 October 2026 · **Status:** draft for review

These are drafts for a lawyer to review, not legal advice, approved policy, or
evidence of compliance. They must not be published as finished pages while they
still contain placeholders.

## Unresolved facts

Every item below is a blank that must be filled by Dainis or counsel. They are
written as `[[PLACEHOLDER]]` throughout so they cannot be published by accident.

| Placeholder | What is needed |
|---|---|
| `[[LEGAL_ENTITY]]` | Exact registered name. "adz-world, USA" is not an entity. |
| `[[ENTITY_TYPE]]` | LLC, corporation, sole proprietorship |
| `[[STATE]]` | State of formation — determines governing law and consumer rules |
| `[[BUSINESS_ADDRESS]]` | Registered address. Required on most policy pages. |
| `[[CONTACT_EMAIL]]` | A monitored address for legal and privacy requests |
| `[[DPO_CONTACT]]` | Required if EU/UK publishers are accepted |
| `[[TAX_ID]]` | For publisher tax reporting |
| `[[PAYOUT_PROVIDER]]` | Stripe or PayPal — unresolved |
| `[[MINIMUM_PAYOUT]]` | Per currency |
| `[[HOLDBACK_PERIOD]]` | How long settled commission is held against reversals |

## Open questions counsel must answer

These change the product, not just the wording.

1. **Money transmission.** adz-world receives commission from merchants and
   disburses part of it to publishers. Holding funds that belong to someone else
   and paying them out may require money-transmitter licensing, state by state.
   Keeping funds out of the plugin does not change this: the hosted business
   still holds them.
2. **Tax reporting.** Payments to US publishers over the threshold require
   1099-NEC or 1099-K reporting; non-US publishers require W-8BEN collection and
   possible withholding.
3. **Affiliate programme terms.** Amazon Associates and most networks prohibit
   sharing commission with third parties, or require disclosure of sub-affiliate
   arrangements. Whether this model is permitted under each programme's terms is
   a per-programme question and a hard launch dependency.
4. **FTC disclosure.** US endorsement guides require affiliate relationships to
   be disclosed to the reader, not only to the publisher. Who is responsible for
   that disclosure appearing — adz-world or the publisher — needs deciding, and
   the plugin should make it easy either way.
5. **Which publisher countries are accepted**, since payout rails, tax and
   sanctions screening all vary.

---

# 1. Service terms and publisher revenue-share agreement

**adz-world Publisher Terms** — draft

**1. The parties.** These terms are between `[[LEGAL_ENTITY]]`, a
`[[ENTITY_TYPE]]` organised under the laws of `[[STATE]]` ("adz-world"), and the
operator of the website that has switched monetization on ("Publisher").

**2. What the service does.** adz-world places its own affiliate identifiers on
adz served through the monetization service, pursues the resulting commission
with merchants and affiliate networks, and accounts to Publisher for it.

**3. Consent.** Monetization applies only to sites where an administrator has
switched it on. Installing the plugin is not consent. Publisher may switch it
off at any time, with immediate effect.

**4. The share.** adz-world retains **10%** of affiliate commission it actually
receives and attributes to Publisher. Publisher is entitled to the remaining
**90%**. The share is calculated on commission received — not on product sales,
advertiser spend, clicks, impressions, or estimated earnings.

**5. When an amount becomes payable.** Commission becomes payable only once
adz-world has received it from the merchant or network and the holdback period
of `[[HOLDBACK_PERIOD]]` has passed without reversal. Amounts shown as pending
are not payable and may never become payable.

**6. Reversals.** Merchants reverse commission for returns, cancellations and
fraud, sometimes months later. A reversal of commission already settled to
Publisher is deducted from Publisher's next settlement. Where no further
settlement follows, adz-world may invoice the reversed amount.

**7. Currency.** Accounts are kept separately per currency. adz-world does not
convert between currencies for accounting purposes. Where a payout provider
converts, the provider's rate and fees apply and are shown on the statement.

**8. Payouts.** Publisher may request a payout of an available balance above
`[[MINIMUM_PAYOUT]]`. Payouts are made by `[[PAYOUT_PROVIDER]]`. Provider fees
are `[[FEE_TREATMENT]]`. adz-world may withhold a payout where it is required by
law, where tax documentation is outstanding, or where fraud is suspected.

**9. Publisher's own affiliate arrangements.** adz-world does not rewrite links
that already carry Publisher's affiliate identifier.

**10. Publisher obligations.** Publisher will not generate clicks or conversions
artificially, will not conceal the advertising nature of adz from readers, and
will comply with the disclosure requirements of their jurisdiction.

**11. Termination.** Either party may terminate at any time. Commission settled
before termination remains payable. Commission pending at termination is paid if
and when received, subject to clause 6.

**12. No guarantee of earnings.** adz-world makes no representation about the
amount Publisher will earn. Past or illustrative figures are not a forecast.

**13. Liability.** `[[LIABILITY_CLAUSE — counsel]]`

**14. Governing law.** `[[STATE]]`, `[[DISPUTE_RESOLUTION — counsel]]`

---

# 2. Privacy and data handling

**adz-world Privacy Policy** — draft

**What we collect from publishers:** site URL, the WordPress user ID and login
of the administrator who switched monetization on, the date and terms version
agreed, the site's publisher identifier and token, payout destination details
held by `[[PAYOUT_PROVIDER]]`, and the statement of commission attributed to the
site.

**What we collect from visitors:** when a visitor clicks a monetized adz, the
click passes through adz-world carrying the publisher identifier, the
destination URL and the ID of the post the adz appeared on. **No visitor
identifier, IP address, cookie or profile is attached by the plugin.**

adz-world records that a click occurred and, if the merchant reports a
conversion, the commission arising. Merchants and affiliate networks operate
their own tracking on their own sites, under their own policies, once the
visitor arrives there. adz-world does not control that and does not receive a
visitor profile from it.

**What we never collect:** visitor browsing history across sites, visitor
preferences, post content, reader email addresses, or any profile of an
individual reader.

**Legal basis (UK/EU):** `[[LEGAL_BASIS — counsel]]`. Publisher data is
processed to perform the contract. Click data is processed on the basis of
`[[BASIS — counsel]]` and the question of whether a pass-through click
constitutes personal data requires review.

**Retention:** statement and payout records are retained for
`[[RETENTION_PERIOD]]` to meet accounting and tax obligations. Click records are
retained for `[[CLICK_RETENTION]]`.

**Your rights:** access, correction, deletion and portability, exercised at
`[[CONTACT_EMAIL]]`. `[[DPO_CONTACT]]` where applicable.

**Sub-processors:** `[[PAYOUT_PROVIDER]]`, `[[HOSTING_PROVIDER]]`,
`[[ANY_OTHERS]]`. A current list must be maintained.

---

# 3. Affiliate disclosure and monetization consent

**The consent screen text** (as implemented in the plugin):

> adz served through the adz-world monetization service carry adz-world's
> affiliate codes. adz-world keeps 10% of commission it actually receives and
> settles 90% to you. The 10% is taken from commission that has been received,
> not from sales, clicks, impressions or estimates.
>
> Links on your adz that already carry your own affiliate identifier are never
> rewritten.

**Reader-facing disclosure.** Separately from publisher consent, readers must be
told that adz carry affiliate links. The wording below is a starting point and
its placement and prominence need review against FTC guidance and equivalent
rules elsewhere.

> Some adz on this site are affiliate links. If you buy something through one,
> this site earns a share of the sale at no extra cost to you.

Whether adz-world provides this automatically or requires the publisher to place
it is an open product decision with a compliance consequence.

---

# 4. Payout, reversal, fee and currency policy

| Item | Draft position | Status |
|---|---|---|
| Share | 10% adz-world / 90% publisher, on commission received | settled |
| Basis | settled affiliate commission, not sales or estimates | settled |
| Currency | separate balances per currency, no conversion for accounting | settled |
| Minimum payout | `[[MINIMUM_PAYOUT]]` per currency | open |
| Holdback | `[[HOLDBACK_PERIOD]]` after settlement before payable | open |
| Payout schedule | on request, or `[[SCHEDULE]]` | open |
| Provider fees | `[[FEE_TREATMENT]]` — borne by adz-world or deducted | open |
| FX on payout | provider's rate, shown on the statement | open |
| Reversal after payout | deducted from next settlement, else invoiced | draft |
| Dormant balances | `[[ESCHEATMENT]]` — unclaimed property rules vary by state | open |
| Payout countries | `[[COUNTRIES]]` | open |
| Tax documentation | W-9 / W-8BEN before first payout | open |

---

# 5. WordPress readme external-service disclosure

Implemented in `readme.txt` in the candidate. It states that the plugin contacts
nothing unless monetization is switched on; what the service is; what is sent
and when; what is never sent; that publisher affiliate links are not rewritten;
and links to the Terms and Privacy pages.

**Dependency:** the Plugins Team stated they will check those links on
re-review. `https://adz-world.com/terms/` and `https://adz-world.com/privacy/`
must resolve to real pages with real content before resubmission. Publishing
these drafts with placeholders intact would fail that check and would be worse
than having no page.
