=== adz-world ===
Contributors: dainismichel
Donate link: https://dainis.net/donate/
Tags: advertising, consent, privacy, ads, ad management
Requires at least: 6.2
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.1.1
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Customize the adzExperience with mixed rotations, permission-based adz, and optional advertising providers.

== Description ==

= Customize the adzExperience =

WordPress admins can create their own adz, build rotations, and choose how those
rotations appear on their websites. A rotation can combine local adz, the
permission invitation, another saved rotation, and ads from optional providers.
Audiences no longer need to be subjected only to surveillance-based advertising.

Some optional providers use Surveillance Advertising Display Systems (SADS).
adz-world does not control how those providers collect data, select ads, or
obtain consent. adzAdmins can include their units while also offering locally
created adz and permission-based results from compatible providers.

= Permission-based adz =

Audience members can say what they are looking for, refine their requests, and
rate adz. The plugin uses their words and feedback to improve local matching.
When a compatible provider is connected, refinements also update the search
request sent to that provider. Current providers and their data handling are
listed under External services.

Nothing is stored when someone only looks. Entering a category or refinement,
or rating an adzad, starts a browser session. Its identifier contains the Michel
Time Stamp of that first action plus five random characters. The session ends
with the browser and starts fresh after no more than 24 hours. Signed-in audience
members can choose to save their requests with their WordPress profile when the
site allows it.

Visible, editable controls show the categories and refinements supplied by the
audience member. Ratings from -100 to 100 add positive or negative weight;
untouched adz remain distinct from explicitly rated adz. adzAdmins can customize
the words and ends of rating ranges, the slider colour theme, and the response
shown after feedback.

AdzLogs records the journey only after the first action: the rotation, slot,
exact adzad, rating, and words entered. It does not record adz that were merely
viewed or store IP addresses. Logs expire after the retention period selected by
the adzAdmin, defaulting to 30 days, and can also be capped. Profile-linked logs
are included in WordPress personal-data export and erasure tools. All logs are
removed when the plugin is deleted.

= Build a rotation =

[adz_rotation name="adzworld_showcase"]

Create named rotations under adz-world > Rotation. Each ordered slot can contain
the permission invitation, one published local adzad, an enabled provider unit,
another saved rotation, or a provider shortcode approved by an adzAdmin. Slots
can be repeated. The rotation stores its format, controls, size, and slots.

Single mode shows one slot per page view and advances on the next view. A page
cache may keep the same slot until the page is regenerated. Carousel mode cycles
through the saved list in the browser. It does not automatically refresh or
request another provider ad when the slide changes. Providers that prohibit
hidden placements should be used in single mode. Without JavaScript, configured
slides remain readable.

The shortcode displays the named rotation; it does not silently add ads. The
Dashboard links to Interactive Documentation, Connections, Affiliate programs, Rotation,
Earnings, Templates, and Starter packs screens.

adzAdmins may also register a shortcode from an advertising plugin already
installed on the site. The installed plugin remains responsible for rendering
its unit. adz-world does not create provider accounts, issue credentials, or
reproduce a provider's inventory, targeting, auction, reporting, or payments.

= Other display options =

adz-world can place adz directly in posts and pages, in layover popups, or on
thru pages. adzAdmins can configure exclusions, placement, sequence or random
order, timing, repetition, and templates. Legacy page, popup, shortcode, and
gated-content requests are bound to their saved configuration with WordPress
nonces. The legacy renderer permits safe post HTML and the adzworld_iframe
shortcode, but does not execute unrelated shortcodes or injected scripts.

https://youtu.be/LDVaFYDve9I

== External services ==

By default, adz-world contacts no external service. Local adz and rotations work
without registration. Every connection below is optional and must be configured
and enabled by an adzAdmin. The providers currently supported are listed below.
Availability and behavior are subject to change without notice based on the
providers' decisions.

= adz-world.com network =

The optional adz-world.com connection supplies permission-based adz and can
synchronize adz created by the publisher. It remains off until enabled under
adz-world > Connections. Registration sends the site URL, site title, and the
adzAdmin's email address. Requests for adz send the publisher identifier, access
token, and referral code. They do not send the audience member's IP address,
anonymous session identifier, or WordPress user ID. Publishing or deleting one
network adzad sends its title, content, and categories. Disconnecting stops
network delivery and synchronization.

Terms: https://adz-world.com/terms/
Privacy: https://adz-world.com/privacy/

= Display advertising networks =

adzAdmins enter identifiers from their own provider accounts. An empty or
rejected identifier loads nothing. The provider's official JavaScript loads
only when its rotation slot is displayed. The audience member's browser then
connects to the provider and sends the information normally included in a web
request, which may include an IP address, browser data, referrer, provider
cookies, and publisher or unit identifiers. The provider controls consent,
targeting, inventory, reporting, and payment. Opening the WordPress admin screen
does not contact these providers. A Register control only opens the provider's
sign-up page; adz-world does not create the account.

* Google AdSense — pagead2.googlesyndication.com — Terms: https://www.google.com/adsense/terms/ ; Privacy: https://policies.google.com/privacy
* Google Ad Manager — securepubads.g.doubleclick.net — Terms: https://admanager.google.com/home/terms/ ; Privacy: https://policies.google.com/privacy
* Taboola — cdn.taboola.com — Terms: https://www.taboola.com/policies/publisher-terms-and-conditions ; Privacy: https://www.taboola.com/policies/privacy-policy
* Teads (Outbrain widget) — widgets.outbrain.com — Terms: https://www.outbrain.com/legal/terms-of-use/ ; Privacy: https://www.outbrain.com/privacy/
* Teads inRead — a.teads.tv — Terms: https://www.teads.com/teads-website-terms-and-conditions/ ; Privacy: https://www.teads.com/privacy-policy/
* Media.net — contextual.media.net — Terms: https://www.media.net/terms ; Privacy: https://www.media.net/privacy-policy
* Amazon Publisher Services — c.amazon-adsystem.com — Terms: https://aps.amazon.com/aps/terms-of-service.html ; Privacy: https://www.amazon.com/gp/help/customer/display.html?nodeId=GX7NJQ4ZB8MHFRNJ
* Ezoic — www.ezojs.com, ezoicanalytics.com, cmp.gatekeeperconsent.com, the.gatekeeperconsent.com — Terms: https://www.ezoic.com/terms/ ; Privacy: https://www.ezoic.com/privacy/
* Mediavine — scripts.mediavine.com — Terms: https://www.mediavine.com/terms-of-service/ ; Privacy: https://www.mediavine.com/privacy-policy/
* Raptive — ads.adthrive.com — Terms: https://raptive.com/terms/ ; Privacy: https://raptive.com/privacy/
* Monumetric — monu.delivery — Terms: https://www.monumetric.com/terms/ ; Privacy: https://www.monumetric.com/privacy-policy/
* MGID — jsc.mgid.com — Terms: https://www.mgid.com/terms-and-conditions ; Privacy: https://www.mgid.com/privacy-policy
* RevContent — assets.revcontent.com and trends.revcontent.com — Terms: https://www.revcontent.com/publisher-terms ; Privacy: https://www.revcontent.com/privacy-policy
* Carbon — cdn.carbonads.com — Terms: https://www.buysellads.com/about/terms ; Privacy: https://www.buysellads.com/about/privacy
* Criteo — static.criteo.net — Terms: https://www.criteo.com/terms-and-conditions/ ; Privacy: https://www.criteo.com/privacy/
* Dianomi — www.dianomi.com — Terms: https://www.dianomi.com/legal/terms ; Privacy: https://www.dianomi.com/legal/privacy
* Snigel — cdn.snigelweb.com — Terms: https://snigel.com/terms ; Privacy: https://snigel.com/privacy-policy
* Freestar — a.pub.network — Terms: https://freestar.com/terms/ ; Privacy: https://freestar.com/privacy-policy/

= Permission-based search and affiliate providers =

The following eleven providers can currently use an adjusted search request:
Amazon, Walmart through Impact, Awin, CJ Affiliate, Rakuten Advertising, eBay,
Etsy, Best Buy, AvantLink, Apple Search, and Travelpayouts. Availability is
subject to change without notice based on the providers' decisions.

The request contains the category and refinements entered by the audience
member. For sponsored search links, the browser sends that request only after
the link is followed, together with normal web-request information such as the
IP address, browser data, and referrer. The provider handles attribution and
pays the adzAdmin under its own agreement.

For product APIs, the site server sends the request only when the relevant slot
is displayed and no cached result exists. It sends the provider credentials and
search text, but not the audience member's IP address, session identifier, or
WordPress user ID. Returned product names, images, prices, and links are cached
on the site. The browser may load product images from provider hosts. Disabling
a connection stops new requests; credentials are never printed publicly.

ClickBank is also available as an affiliate connection, but it is not one of the
eleven providers above. Its HopLink uses only the saved affiliate, vendor, and
optional tracking identifiers. Audience-entered words are never sent to
ClickBank.

Provider-specific behavior:

* Best Buy sends the search text, saved API key, and optional Impact Partner ID
  to https://api.bestbuy.com/v1/products. Results are cached for 15 minutes.
* Amazon Creators API exchanges the saved credential ID and secret for an access
  token at https://api.amazon.com/auth/o2/token,
  https://api.amazon.co.uk/auth/o2/token, or
  https://api.amazon.co.jp/auth/o2/token. It sends the token, marketplace,
  Associates tag, and search text to
  https://creatorsapi.amazon/catalog/v1/searchItems. Results are cached for 15
  minutes; tokens are cached until they expire.
* eBay Browse API exchanges the saved client ID and secret for a token at
  https://api.ebay.com/identity/v1/oauth2/token, or
  https://api.sandbox.ebay.com in sandbox mode. It sends the token, search text,
  and optional campaign ID to /buy/browse/v1/item_summary/search on the same
  host. Tokens are cached for 110 minutes; results for 15 minutes.
* Etsy Open API sends the saved keystring, shared secret, and search text to
  https://api.etsy.com/v3/application/listings/active, then requests returned
  listing images from the same API. Results are cached for 15 minutes. With
  Rakuten identifiers saved, listing links use click.linksynergy.com.
* Apple Search API sends the search text, country US, selected media type, and a
  result limit to https://itunes.apple.com/search. No key is required. The
  optional performance-partner token is added to returned links. Results are
  cached for 15 minutes.
* AvantLink sends the saved affiliate ID, website ID, and search text to
  https://classic.avantlink.com/api.php using ProductSearch. Results are cached
  for 15 minutes.
* Travelpayouts sends a place name to
  https://autocomplete.travelpayouts.com/places2 to find an airport code, cached
  for 7 days. It then sends the API token, origin, destination, currency, and
  optional month to https://api.travelpayouts.com/v1/prices/cheap or
  /v1/prices/direct. Results are cached for 30 minutes. Flight links use
  www.aviasales.com with the saved marker.

* Amazon Associates — Sign up: https://associates.amazon.com/signup ; Agreement: https://affiliate-program.amazon.com/help/operating/agreement/ ; Privacy: https://www.amazon.com/gp/help/customer/display.html?nodeId=GX7NJQ4ZB8MHFRNJ
* Best Buy Developer API and Publisher Program — API terms and branding: https://developer.bestbuy.com/legal ; Privacy: https://www.bestbuy.com/site/help-topics/privacy-policy/pcmcat204400050062.c?id=pcmcat204400050062
* Walmart/Impact — Sign up: https://app.impact.com/login.user ; Program: https://affiliates.walmart.com/ ; Privacy: https://impact.com/privacy-policy/
* Awin — Sign up: https://www.awin.com/us/publishers ; Publisher terms: https://www.awin.com/us/publisher-terms ; Privacy: https://www.awin.com/us/privacy
* CJ Affiliate — Sign up: https://signup.cj.com/member/signup/publisher/ ; Legal terms and privacy: https://www.cj.com/legal
* Rakuten Advertising — Terms: https://rakutenadvertising.com/legal-notices/terms-conditions/ ; Privacy: https://rakutenadvertising.com/legal-notices/services-privacy-policy/
* ClickBank — Sign up: https://accounts.clickbank.com/master/makebank.html ; Client contract: https://support.clickbank.com/hc/en-us/articles/220376507-ClickBank-Client-Contract ; Privacy: https://www.clickbank.com/privacy-policy/
* eBay Partner Network — Agreement: https://partnernetwork.ebay.com/page/network-agreement ; Privacy: https://www.ebay.com/help/policies/member-behavior-policies/user-privacy-notice-privacy-policy?id=4260
* Amazon Creators API — Policy: https://affiliate-program.amazon.com/help/operating/creatorsapipolicy ; Agreement: https://affiliate-program.amazon.com/help/operating/agreement/ ; Privacy: https://www.amazon.com/gp/help/customer/display.html?nodeId=GX7NJQ4ZB8MHFRNJ (also covers amazon.co.uk and amazon.co.jp)
* eBay Browse API — API License Agreement: https://developer.ebay.com/join/api-license-agreement ; Privacy: https://www.ebay.com/help/policies/member-behavior-policies/user-privacy-notice-privacy-policy?id=4260
* Etsy Open API (etsy.com) — API terms: https://www.etsy.com/legal/api ; Privacy: https://www.etsy.com/legal/privacy
* Apple Search API and Apple Services Performance Partners (apple.com) — Terms: https://www.apple.com/legal/internet-services/itunes/ ; Program: https://performance-partners.apple.com/ ; Privacy: https://www.apple.com/legal/privacy/
* AvantLink (avantlink.com) — Terms: https://www.avantlink.com/terms-of-use/ ; Privacy: https://www.avantlink.com/privacy-policy/
* Travelpayouts (travelpayouts.com) and Aviasales (aviasales.com) — Terms: https://www.travelpayouts.com/en/terms ; Privacy: https://www.travelpayouts.com/en/privacy ; Aviasales privacy: https://www.aviasales.com/privacy
* Awin link host (awin1.com) and Rakuten Advertising link host (linksynergy.com) — covered by the Awin and Rakuten Advertising terms and privacy policies above.

= adzAdmin links and iframe destinations =

The Earnings tab contains a voluntary support link to
https://dainis.net/donate/. It opens only when an adzAdmin clicks it; no
background request is made and no tracking parameter is added.

The optional adzworld_iframe shortcode connects the audience member's browser
to a URL entered by an adzAdmin. That destination receives normal request
information such as the IP address and browser headers. The shortcode has no
default URL and renders nothing until a URL is supplied. The destination's own
terms and privacy policy apply.

== Installation ==

1. Upload the `adz-world` folder to `/wp-content/plugins/`, or install it through
   the WordPress Plugins screen.
2. Activate adz-world.
3. Create and publish a local adzad.
4. Build a named rotation and choose its display settings.
5. Add the rotation shortcode to a post or page.
6. Review External services before enabling any optional connection.



== Frequently Asked Questions ==

= What is an adzad? =

An adzad is one advertisement in the adzExperience. Its editor content or
featured image is the creative. Its title, content, excerpt, and Ad Type can help
match it to an audience member's stated request. The plural is adz.

= What is adz-world really? =

adz-world was created by composer, human-rights advocate, and entrepreneur
Dainis W. Michel. After his father, Rev. Dr. Walter L. Michel, died, Dainis found
carefully numbered VHS tapes from a project they had planned together. He had the
lectures digitized and needed a way to help pay for publishing them without
charging people for access.

That need became adz-world: a way for publishers to build enjoyable, wholesome
advertising ecosystems and help free content become self-sustaining. One of the
first sites to use the plugin was https://www.bible-experts.com, where relevant
adz can help finance access to Rev. Michel's lectures.

= Does the plugin require the adz-world.com service? =
No. Local adz and rotations work without registration or an external-service
connection. Network, provider, and affiliate connections are separate opt-ins
controlled by adzAdmins.



== Screenshots ==

1. Create a local adzad
2. Build an adz rotation
3. Choose where the rotation appears
4. Set the rotation's timing and display type

== Changelog ==

= 1.1.1 =
* Rating bands now store a start and an end, and both are editable.
* Added named slider themes. Standard keeps the existing gradient. Tide is
  the blue theme. Ember is ashy grey, black, and glowing ember orange. An
  adzadmin saves any other theme as three hex colours, and the plugin draws
  the gradient.

= 1.1.0 =
* Renamed the Manual tab to Interactive Documentation and moved it to the final
  tab position.
* Reorganized and shortened the public readme while preserving the external
  service, privacy, and provider disclosures.

= 1.0.9 =
* Remediated the WordPress.org closure findings, including metadata, AJAX URLs,
  text domain, shortcode escaping, and deprecated components.
* Added named mixed-source rotations, provider connections, affiliate programs,
  starter packs, and adzAdmin slot previews.
* Added audience categories, refinements, ratings, editable preferences, and an
  optional profile-saving choice.
* Added AdzLogs with retention controls and WordPress privacy export and erasure.
* Replaced raw-IP view tracking with expiring salted-key transients and removed
  unused authentication, payment-sandbox, and direct-cURL prototypes.
* Moved legacy display behavior to enqueued assets, removed inline styling, and
  added configuration-bound request protection.
* Documented every external service the plugin can contact.


== Upgrade Notice ==
= 1.1.1 =
Adds editable rating-band ranges and two additional slider colour themes.

= 1.1.0 =
Renames and repositions the documentation tab and improves the public readme.

= 1.0.9 =
Security, privacy, compatibility, and WordPress coding-standards remediation.

= 1.0.1 =
* https tweaks

= 1.0.0 =
Initial release
