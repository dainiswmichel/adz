# adz-world: flagship, settlement, and legal review draft

Status: candidate proposal, not an integrated release, deployed service, approved
legal policy, or WordPress.org compliance statement.

## Candidate baseline and executed verification

This is a complete independent plugin candidate based on the committed
Claude candidate at `e954a87`, not a replacement of the integrated codebase.
Later Claude work is not silently imported. PHP and JavaScript were formatted
with the official WPCS PHPCBF tool; this produces a broad formatting diff in
addition to the functional changes.

**Release status: FAIL / in progress.** Nothing in this directory is a
selected winner, production settlement service, or submission-ready build.

Executed locally on WordPress 7.1.3, PHP 8.4.7, with `WP_DEBUG` and
`WP_DEBUG_LOG` enabled:

| Check | Observed result |
| --- | --- |
| PHP lint | All 46 PHP files passed. |
| JavaScript syntax | All candidate JavaScript files passed `node --check`. |
| Deactivation and activation | Both succeeded. No activation fatal reproduced. |
| Integer accounting and statement assertions | Passed, including integer-limit conservation, currency precision, stale/mismatched/unbalanced statements, and unsupported inputs. |
| Anonymous option-deletion HTTP reproduction against repair | POST returned normally; the disposable probe option remained intact. |
| Removed anonymous reset/template actions | Both returned HTTP 400 as unregistered actions. |
| Template settings authorization | Administrator with a valid nonce succeeded; invalid nonce returned HTTP 403. |
| Local advertising | Public ad rendered over real AJAX without registration or external-service consent. Drafts, password-protected ads, and ordinary posts were rejected by the local response helper. Local sequence uses actual post IDs. Empty-ad shortcode retains article content. |
| Earnings page | Actual authenticated HTTP request displayed an explicit unconnected state. |
| Provider contracts | Stripe and PayPal requests passed mocked WordPress HTTP tests. Exactly three mocked transport calls; conflicting retry and anonymous execution rejected. No real provider account contacted. |
| Full WPCS | **FAIL: 1,083 errors, 314 warnings**, including JavaScript, legacy PHP, and review support. No diagnostic exclusions or suppressions added. |
| New earnings module WPCS | Zero errors, zero warnings. |
| New sandbox module WPCS | Zero errors, one warning for OAuth Basic-auth `base64_encode`; not suppressed. |
| Official Plugin Check | **FAIL: 100 errors, 202 warnings**. Its process returned zero despite the findings; the findings, not that exit code, determine failure. |

Debug-log changes during the completed regression/activation cycle consisted
only of WordPress automatic-update start/complete messages, not PHP diagnostics.
Earlier isolated activation also left the log byte count unchanged. Historical
diagnostics were retained rather than erased to manufacture a clean log.

Reproducible checks from the repository root, with the candidate symlinked as
`adz-world` in the test WordPress:

```sh
find pre-release-candidates/codex-gpt5 -name '*.php' -exec php -l {} \;
phpcs --standard=WordPress pre-release-candidates/codex-gpt5
wp plugin check adz-world --path=/path/to/test-wordpress --format=json
wp eval-file pre-release-candidates/codex-gpt5/tests/provider-contracts.php --path=/path/to/test-wordpress
wp eval-file pre-release-candidates/codex-gpt5/tests/http-regression.php --path=/path/to/test-wordpress
```

The HTTP test requires a running test server and a `127.0.0.1` site URL. Tests
create and clean disposable posts/options/auth sessions. The HTTP fixture
requires external ad serving to be disabled. These tests are not a complete
feature audit, SQL-injection proof, visual review, or provider certification.

Remaining findings include output escaping, legacy request handling, global
naming/documentation, and JavaScript formatting. The staged whitespace check
also reports inherited template/text/style whitespace; it is not a clean check.
The inherited shared
`adz_session_option` is not a per-visitor session; VGM preferences below are
proposed, not implemented. Remote ad delivery and real statement issuance,
settlement, credentials, webhooks, payouts, and flagship UI remain unverified
or unimplemented as described below. The original activation-fatal prediction
was not reproduced; core on this tested installation did not define
`role_exists()`. The actual guideline underlying closure remains unknown.

## Confirmed decisions

- The plugin is free. The optional adz-world monetization service retains 10%
  of settled affiliate commissions actually received; the publisher gets 90%.
- Service ads use adz-world's affiliate/referral codes with explicit publisher
  consent. Existing publisher-authored links are not silently replaced.
- adz-world.com is the accounting authority. Publisher WordPress installations
  display authenticated statements; they do not assert payable balances.
- Accounting is multi-currency from the outset. No implicit currency conversion.
- Both Stripe and PayPal are evaluation candidates. Provider choice is TBD.
- The proposed operator is adz-world, USA. Legal entity, state, address, contact
  details, affiliate accounts, provider approvals, and tax treatment are TBD.
- Independent publisher communities/networks remain possible; the existing
  technical "network" terminology is not a settled product requirement.
- No live collection, payouts, publication of legal drafts, or deployment is
  authorized by this candidate.

## What this candidate actually implements

The plugin adds an administrator-only Earnings page with explicit opt-in,
read-only credentials, on-demand statement requests, currency-separated
breakdowns, and explicit errors rather than invented zero balances. The
proposed statement endpoint is NOT deployed by this candidate.

Integer accounting helpers implement a 90/10 split per settled receipt with
half-up rounding to the currency's minor unit. The publisher receives the
remainder, so the shares always equal the receipt. Splitting an aggregate can
differ from summing rounded receipt splits; the service must use receipt-level
entries consistently. Dashboard totals validate conservation, not an incorrect
aggregate rounding assumption.

Sandbox-only Stripe transfers and PayPal Payouts request code is included.
It has no public route, browser-triggered payment button, automatic cron job,
or live execution mode. Execution requires an administrator, explicit
server configuration, and test credentials. Persistent operation identity
rejects changing a request's amount or recipient during a retry.

These are provider adapters, NOT a production settlement backend. A successful
test response means "sandbox submitted," not paid, bank-settled, or legally
authorized to operate. Stripe account transfers are not bank payouts.

The candidate does not yet implement authoritative receipt ingestion,
attribution, a durable double-entry ledger, payout reservations/reconciliation,
provider onboarding, or authenticated statement issuance. It must not collect
or distribute real funds until those are implemented, tested, and approved.

## Flagship table of contents / site map

1. Home: advertising by invitation, with a working permission-first demo.
2. How it works: visitor control, publisher participation, and the exact 90/10.
3. Explore adz: categories, example ads, clear sponsored/affiliate labels.
4. My preferences: temporary visitor choices or saved account preferences.
5. For publishers: install, local ads, optional monetization, setup checklist.
6. Publisher workspace: receipts, estimates, commissions, adjustments, payouts.
7. For advertisers: creative standards, attribution, eligibility, contact.
8. Community: independent communities and proposed member capabilities.
9. Trust center: privacy, accessibility, security reporting, service status.
10. Terms, publisher agreement, affiliate disclosure, privacy, payout policy.
11. Documentation, support, account export/deletion, and contact.

## Experience and visual direction

### Home

An editorial-quality opening: warm ivory, deep ink, vivid coral and electric
blue accents, expressive typography with locally hosted licensed fonts,
generous whitespace, and exceptionally clear economics. "Advertising by
invitation" sits beside a real interactive demonstration, not fake metrics.

A visitor can select example interests before seeing relevant demonstration
ads. Each card identifies the advertiser, why it appears, and its affiliate
relationship. Nothing loads a third-party creative or tracking pixel before
the appropriate consent. A clearly labeled local demo works without that
consent. Motion is restrained, optional, and respects reduced-motion settings.

No dark patterns, fabricated testimonials, earnings promises, or approval
badges. A beautiful interface must remain usable with keyboard navigation,
screen readers, high zoom, limited bandwidth, and no animation.

### Visitors, guests, and members

- Visitor: anonymous, session-scoped preferences, clearly described as
  temporary and not guaranteed to survive browser/session changes. No hidden
  persistent identifier or IP fingerprint to recreate them.
- Guest: a logged-in account can save preferences, update them, withdraw
  optional permissions, and request account-data export/deletion. Login does
  not imply permission for personalized advertising.
- Member: undecided. Proposals for discussion, not implemented entitlements:
  (a) community curator of ad collections with moderation;
  (b) verified community participant who helps set advertiser standards;
  (c) publisher collaborator with explicitly delegated, narrow permissions.
  Membership need not be a paid tier or an exemption from privacy controls.

Saved preferences and permission are separate: an interest selection is not
permission to transmit identity or browsing history to external services.
Preferences are scoped to the site unless cross-site sharing is separately
explained and chosen. Account creation must not silently create a universal
advertising profile.

### Publisher

Install the plugin -> create a local ad -> preview a rotation -> review
external-service/referral disclosures -> optionally enroll in monetization ->
see an explicitly unconnected, pending, or verified dashboard state.

On the flagship, publisher statements have distinct estimated, settled,
reversed, platform-share, publisher-share, available, reserved, and paid
sections. Each currency has its own statement and history. A payout includes
its currency, provider, known fee policy, and state; no ambiguous combined
"total earnings" across currencies.

### Advertiser and community

Advertisers provide clear creatives and attribution terms, subject to
moderation. Site owners retain editorial control and may develop their own
communities. Independent-service boundaries, referral ownership, and any
fees must be explicit before those services are enabled.

## Roadmap and release gates

### 1. Closure remediation first

Reproduce authorization failures safely; repair and regress them; retain
local advertising; resolve unsafe output, request validation, and legacy
runtime warnings. Run PHP lint, WPCS, Plugin Check, and clean-WordPress tests.
Ask the Plugins Team which guideline caused closure. Deadline:
28 November 2026. Technical findings alone do not identify that guideline.

### 2. Integrated plugin and demonstration site

Select reviewed files through the agreed winner manifest. No whole-slot
replacement. Run the shared gate on integrated source and install the exact
release ZIP on clean WordPress before approval. Implement the flagship in
the existing sibling website project; this document does not create another
site source or authorize a droplet, DNS change, or deployment.

Launch the demonstration with real local ads and no money-moving claims.
Publish only reviewed legal pages, with actual operator/contact information.
Observe errors and accessibility using real journeys, not screenshots alone.

### 3. Settlement service and sandbox evaluation

Build authoritative attribution and receipt ingestion before payable money.
Evaluate both providers with actual sandbox accounts and signed callbacks.
Exercise duplicate receipts, replayed events, retries, concurrency, refunds,
partial reversals, disputes, recipient failures, and provider outages.
Finalize provider choice only after the business model and countries have
been accepted by the provider.

### 4. Real-money release, separately approved

Provider approval, legal review, tax/KYC requirements, tested reconciliation,
data retention, incident response, and a demonstrated no-double-payout
invariant are mandatory. No date or policy approval is invented here.

### 5. Community and member experiments

Discuss and test member proposals with Dainis and actual publishers. Preserve
the free plugin's useful functionality and visitor choice. No guessed paid
gate or dependence on the hosted service for local ads.

## Settlement design and proposed API contract

The proposed `GET https://adz-world.com/wp-json/adz-world/v1/earnings`
accepts an opaque publisher-scoped read-only Bearer token. The server derives
publisher identity from that token, never a caller-supplied publisher ID.
It uses HTTPS, revocation, rate limits, and logs without tokens. The plugin
disallows redirects to avoid credential forwarding.

Response schema: `publisher_id` (string), `generated_at` (Unix integer),
and `currencies` (array of unique currency rows). Each row has `currency`,
`estimated_minor`, `settled_minor`, `reversed_minor`, `service_minor`,
`publisher_minor`, `available_minor`, `reserved_minor`, and `paid_minor`,
all integer minor units. `settled_minor` is net of the displayed
`reversed_minor`; reversals are not deducted a second time.

Invariants per currency:

- settled = service share + publisher share;
- publisher share = available + reserved + paid;
- available may be negative after a paid receipt reverses; this is a liability,
  not a new payout;
- no FX, float math, mixing currencies, or using estimates as cash.

The service must use immutable receipt/event IDs unique to their affiliate
source, verified ownership and provenance, compensating reversal entries,
and transactional reservations. Receipt ingestion from an affiliate network
is not a Stripe charge or PayPal payment; the affiliate networks' APIs/feeds,
contracts, and payment schedules remain required inputs.

Proposed payout states: requested -> reserved -> submitted -> pending ->
confirmed, or explicit failed/reversed/unknown states. Unknown is not failed:
reconcile the existing operation before releasing a reservation or retrying
with a new ID. Only verified final provider events/reconciliation affect
paid balances. Authenticate signatures, deduplicate events, verify expected
account/currency/amount, and handle out-of-order delivery.

The sandbox adapters deliberately do not implement this state machine or
promise long-term idempotency beyond provider retention. Reservations and
durable final-state reconciliation must exist before live use.

Accounting currently recognizes AUD, BHD, CAD, CHF, CLP, EUR, GBP, JPY, KRW,
KWD, NZD, OMR, TND, and USD. The candidate's provider evaluation restricts
payouts to AUD, CAD, CHF, EUR, GBP, JPY, NZD, and USD; account/country/provider
eligibility still applies. Other currency payouts return errors, never
automatic conversion. Currency coverage can expand through reviewed metadata.

Provider references:

- https://docs.stripe.com/api/transfers/create
- https://docs.stripe.com/connect/separate-charges-and-transfers
- https://developer.paypal.com/docs/api/payments.payouts-batch/v1/
- https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/

## Legal drafts: not for publication until reviewed

These are proposed operational terms, not legal advice or a compliance
determination. Unresolved commercial terms are intentionally not disguised
as agreements already made. Qualified legal review is required before launch.

### Service terms and publisher revenue share

Operator: adz-world, USA. **Legal entity, state, business address, effective
date, and legal/support contact: TBD before publication.**

The WordPress plugin is free. Optional hosted monetization requires a
separate affirmative enrollment. Service-served ads carry adz-world affiliate
or referral identifiers, and purchases may generate affiliate commissions.
For eligible affiliate commission actually received and settled, adz-world
retains 10% and allocates 90% to the attributed publisher. This is not a
percentage of merchandise sales and is not a guarantee of income.

Estimated or pending commission is not payable cash. Receipts and reversals
must be attributable to the publisher using the agreed attribution rules.
**Attribution window, competing referrals, eligible transactions, fraud
review, and evidence/dispute procedure: TBD before enrollment.**

Amounts are maintained by currency with currency-appropriate minor units.
The 10% fee is rounded half-up per receipt; the publisher receives the exact
remainder. Reversals restore the original entry's allocation, not a new
percentage calculation on an unrelated aggregate. Paid reversals may create
a negative balance, subject to reviewed recovery terms.

**Payout frequency, minimum, eligible countries, provider fees, FX, tax
withholding, reserves, dispute deadlines, termination treatment, and governing
law: TBD before real-money launch.** These must not silently reduce the
promised 90% share. Any additional deductions need explicit prior agreement.

Publishers remain responsible for permission to place ads, truthful content,
and their own website disclosures. Service discontinuation or consent
withdrawal does not erase legitimate earned balances or recordkeeping duties.
Local plugin features must not be described as requiring service enrollment.

### Privacy notice

Controller/operator and contact: **TBD legal details for adz-world, USA**.
The exact collection inventory, providers, retention schedule, and
international-transfer safeguards require review against deployed behavior.

Proposed data categories: account identity/contact, publisher site details,
explicit preference choices, consent records, affiliate attribution and
settlement references, payout-recipient references, and necessary
security/operational logs. Provider-hosted onboarding should keep card/bank
credentials out of the plugin and adz-world application where possible.

Anonymous visitor choices are temporary session data. Guests may save
preferences to their account. External service requests, identity sharing,
cross-site preferences, and nonessential cookies require the appropriate
separate disclosures and choice; site-owner consent is not automatically
visitor consent.

The legacy remote implementation currently sends visitor IPs and publisher
information when enabled; that behavior requires specific review rather
than a promise that this candidate eliminated all tracking.

Proposed rights processes include access, correction, export, deletion,
withdrawal of optional permissions, and applicable objections/complaints.
Financial records may require limited statutory retention after an account
is deleted. **Actual retention periods, processors, rights contact, lawful
bases, age eligibility, and regional notices: TBD.** No blanket "no tracking"
or universal legal-compliance claim is made.

### Affiliate disclosure and consent copy

"These service ads contain adz-world affiliate/referral links. If an eligible
purchase earns a settled affiliate commission, adz-world receives that
commission, retains 10%, and allocates 90% to the attributed publisher.
Estimated earnings are not guaranteed. The plugin itself is free."

Publisher opt-in must appear next to the actual terms and privacy links and
must be unchecked by default. Withdrawal disables future optional service
participation without deleting legitimately owed balances. No registration,
activation, or unrelated settings save constitutes acceptance.

### Payout policy

Provider selection remains TBD between Stripe and PayPal. Neither adapter is
a real-money payout facility in this candidate. Final rules must specify
eligible recipients/countries, account verification, each currency's minimum,
schedule, fees, conversion consent, tax treatment, reversals, failed payments,
unclaimed funds, dispute process, and termination balances.

Collecting and distributing funds through a hosted service still requires
review of money-services, sanctions, tax, consumer, and privacy obligations.
Locating the logic outside the plugin does not establish an exemption.

## Review deliverables and exclusions

The candidate's `tests/` and `review/` directories are review support, not
release-plugin payload. The per-file integration process selects actual
plugin files and shared regression tests deliberately. This candidate creates
no competing ZIP, gate, deployment, or selected-winner manifest.
