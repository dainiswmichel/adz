# Proposed adz-world statement contract

Status: proposal. No service implements this contract. The plugin does not call a statement URL, does not expose a conversion endpoint, and does not treat an empty table as a balance.

The plugin’s Earnings screen calls `adzworld_statement()`. That function applies the filter `adzworld_statement_payload`. This candidate adds no callback. The result is `state: unconfigured`. A payload is displayed only when server-side code supplies one and `adzworld_validate_statement()` accepts it. Browser fields are not amounts.

## Authority

adz-world.com would be the authority for verified receipts, attribution, accounting, and payouts. The WordPress plugin would be a reader of authenticated statements.

Platform payment secrets (Stripe secret key, PayPal client secret, webhook secrets) stay on the service. They are not options in the plugin and they are not in this repository.

## Proposed read API

`GET https://adz-world.com/wp-json/adzworld-service/v1/statement`

This path is a proposal. Requesting it today does not return this document’s schema, and the plugin does not request it.

Authentication, when the service exists: a site-scoped read credential issued by adz-world.com to that publisher. The credential is not a Stripe or PayPal secret. How it is stored on the publisher site is undecided; this candidate stores none.

Response body, one JSON object:

```json
{
  "fetched_gmt": "2026-11-01 00:00:00",
  "currencies": {
    "USD": {
      "pending_minor": 0,
      "settled_minor": 0,
      "platform_minor": 0,
      "publisher_minor": 0,
      "reversal_minor": 0,
      "adjustment_minor": 0,
      "available_minor": 0,
      "reserved_minor": 0,
      "paid_minor": 0
    }
  }
}
```

Rules the plugin already enforces on a supplied payload:

- `currencies` is a map of three-letter codes.
- Each code must be in `adzworld_currency_exponents()`. Unknown codes fail the statement. The plugin does not guess an exponent.
- Every bucket is a PHP integer of minor units. Strings and floats fail the statement.
- `platform_minor + publisher_minor` must equal `settled_minor`. If not, the whole statement is `unavailable`.
- Currencies are not summed.
- `fetched_gmt` must be `YYYY-MM-DD HH:MM:SS` or the screen says freshness was not supplied.
- The share identity does not prove `available_minor` is payable.

`reserved_minor` is the reserved and in-flight amount still on the service. It is not cash the plugin holds.

## Rounding

`adzworld_split_cents()` splits one integer. Platform minor units are `intdiv(abs(settled) + 5, 10)`. Publisher minor units are the remainder, with the original sign on both shares. Example, labeled as arithmetic wherever it is shown: 1000 → 100 and 900. Five minor units → 1 and 4. Four → 0 and 4. Negative fifteen → -2 and -13.

## Payout state machine

Implemented as `adzworld_payout_transition()`. Nothing in the plugin calls a provider or stores a payout row. Illegal events return an empty string.

| From | Event | To |
| --- | --- | --- |
| none | reserve | reserved |
| reserved | provider_accepted | in_flight |
| reserved | provider_failed | failed |
| in_flight | provider_paid | paid |
| in_flight | provider_failed | failed |
| paid | reversal | reversed |
| failed | reserve | reserved |
| reversed | (none) |  |

A reserve needs an idempotency key accepted by `adzworld_valid_event_id()`: 8 to 64 characters, `[A-Za-z0-9_-]`. The same key must not reserve twice. That store would live on adz-world.com. Replay protection for provider webhooks (signature, timestamp window, stored event id) would also live there. The plugin does not implement those checks because it does not receive provider events.

Ownership: only the service account that owns the settled commission can reserve a payout of the available bucket in that same currency. The amount comes from the service ledger, never from a browser field.

## Providers prepared, not connected

Stripe. Connected-account payouts are documented at https://docs.stripe.com/connect/manage-connected-account-payouts . Sandbox payout testing is documented at https://docs.stripe.com/global-payouts/testing . Payout object statuses there include pending, in_transit, paid, and failed. Sandbox keys stay on the service. `adzworld_provider_state( 'stripe' )` returns `unconfigured`.

PayPal. The Payouts API sandbox create call is `POST https://api-m.sandbox.paypal.com/v1/payments/payouts` with `Authorization: Bearer` and `PayPal-Request-Id` (PayPal’s documented idempotency header). Documentation checked: https://developer.paypal.com/api/payouts/standard (updated 11 August 2026) and https://developer.paypal.com/api/payouts . `adzworld_provider_state( 'paypal' )` returns `unconfigured`.

Neither API collects an affiliate-network commission, and neither API is assumed to permit this revenue-share arrangement. A sandbox simulation inside the plugin would be a fake success, so there is no simulator and no success message.

## Launch dependencies still open

- a real settlement feed: which affiliate network pays adz-world, on what report, with what attribution evidence
- provider account eligibility for this business
- payout countries and currencies the provider will actually pay
- tax, invoices, and who issues them
- foreign exchange, if a commission arrives in one currency and a publisher is paid in another (the plugin will not invent the rate)
- provider fees and who bears them
- chargebacks and how they reduce `available_minor`
- money-transmission and other regulation for the hosted business, including after funds are kept off the plugin
- the legal entity, address, and state in `LEGAL-DRAFTS.md`

Until those exist, the honest plugin state is unconfigured.
