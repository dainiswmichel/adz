=== adz.world ===
Contributors: dainismichel
Donate link: https://dainis.net/donate/
Tags: advertising, consent, privacy, ads, ad management
Requires at least: 6.2
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.0.9
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Run your website like a TV station. Use the adz.world plugin to create an enjoyable, profitable, wholesome advertising ecosystem for your audience!

== Description ==

= Configurable rotations and permission-based adz =

[adz_rotation name="adzworld_showcase"]

Create named rotations under Adz.world > Rotation. Each ordered slot can contain
the permission invitation, a published local adz, an enabled advertising-network
unit, another saved rotation, or an administrator-approved provider shortcode.
The same slot may be repeated. Display format, controls, size and slots are
stored with the rotation. The list cycles and then repeats. The shortcode names
the saved rotation; it does not silently add advertisements. Without JavaScript,
configured slides remain readable.

Adz.world > Connections registers a shortcode supplied by an advertising plugin
already installed on your site. Saving or removing a source requires an
administrator with unfiltered HTML permission and a valid nonce. This uses
the existing plugin's rendering; it does not create network accounts, issue
credentials, or certify any provider's approval of a placement.

Single mode shows one slot per page view and advances to the next slot on the
next view. A carousel shows the saved list and repeats in the browser. A page
cache can keep one single-mode slot until the page is regenerated. Provider
units are not refreshed by navigation. Provider rules and existing consent
configuration still apply; no claim of universal network compatibility is made.
Carousel slides other than the active slide become hidden or inert. Providers
that prohibit hidden placements must use single mode, not a carousel. No provider
tag is automatically refreshed or re-requested as a slide changes.

The Dashboard links the Manual, Connections, Affiliate programs, Rotation,
Earnings, Templates, and Starter packs screens. Connections accepts the site owner's
existing Google AdSense, Google Ad Manager, Taboola, or Outbrain unit
identifiers. A complete, explicitly enabled unit becomes a named Rotation
source. Other providers remain available through their official WordPress
plugin or existing provider shortcode; adz.world does not recreate their
account, inventory, auction, reporting, or payment system.

The invitation accepts categories and refinements in the visitor's own words.
Logged-out preferences use a browser-session cookie; signed-in preferences use
user metadata. Visible removable pills show what was understood, matching local
adz are prioritised, and explicit -100 to 100 evaluations add positive or
negative weight. Untouched adz remain distinct from an explicit neutral rating.
Configured provider sources remain responsible for their own inventory,
targeting, consent rules, and request behavior.

Legacy page, popup, and gated-content requests now carry a configuration-bound
nonce as well as the public advertising nonce. Changing the target, rotation,
sequence, template, interval, repeat count, or operation invalidates the request.
This is request-integrity protection, not administrator authorization or proof
that an ad was viewed. Public tokens can be replayed for their original
configuration until WordPress expires them. Cached pages containing expired
tokens require a cache refresh. Each signed field is limited to 8192 bytes.

The legacy ad renderer permits ordinary safe post HTML and the plugin's
adzworld_iframe shortcode. Advertising responses cannot execute unrelated site
shortcodes or inject scripts. Existing administrator-registered provider sources
use their installed provider plugin's renderer instead.

<strong>adz.world is the ultimate advertising plugin for creating an enjoyable and profitable advertising ecosystem, serving adz to your audience in a healthy way, generating advertising revenue from freelines, and requiring advertising views in exchange for access to content. Facilitate the dawning of a true Age of Accordance for humanity, where all agreements are made in the spirit of enjoyable mutual consent. Join us and help us become the most wholesome advertising community in the world.</strong>

adz.world lets you easily run adz on your published web content with layover popups, thru pages, or directly within your pages and posts.

adz.world allows your website visitors to select their adz preferences, so that you can earn from purchases they make while surfing your site.

[youtube  https://youtu.be/LDVaFYDve9I]



= Run your website like a TV station =

Set “within page” adz to come up every 7 minutes and relax. Especially if you prepare your audience and let them know that they will be served adz that offer related products, there to help and not disturb their surfing experience, well then you’re on the road to having a healthy consent-based advertising ecosystem! Good for you!



= Customize the appearance of your adz =
you can make your own adz or serve adz from external ad networks, so the adz themselves can be styled any way you want. Your adz.world template header and footer can be customized so that you always have the perfect message for your audience!

<strong>Features available in adz.world include:</strong>

* make sure adz never show on these pages
* adz rotation name
* show adz on selected pages or posts, within selected categories, or where selected tags are present
* show adz in sequence or randomize. currently random always serves all selected adz
* select which adz to serve by highlighting their titles
* number of seconds to serve adz, number of seconds to allow access to content
* loop adz infinite yes/no
* duplicate adz rotation
* trash adz rotation



== External services ==

By default, adz.world contacts no external service. Local advertisements and
rotations work without registration. Every connection below is optional and
must be configured by a WordPress administrator.

= adz-world.com network and earnings =

The optional adz-world.com connection supplies permission-based adz and can
synchronize advertisements created by the publisher. It is off until an
administrator enables it under Adz.world > Connections. Registration sends the
site URL, site title and administrator email address. Network requests send the
publisher identifier, access token, referral code and the visitor IP address
when requesting an adz or checking network visitor state. Publishing or deleting
a network adz sends that adz's title, content and categories. Switching the
connection off stops network delivery and synchronization.

The separate Earnings connection is also off by default. Only an administrator's
explicit statement request sends the saved publisher identifier and read-only
token to https://adz-world.com/wp-json/adz-world/v1/earnings. The server also
receives the requesting server's IP address. Disconnecting removes the token.

Terms: https://adz-world.com/terms/
Privacy: https://adz-world.com/privacy/

= Display advertising networks =

An administrator can optionally connect an existing Google AdSense, Google Ad
Manager, Taboola, or Outbrain publisher unit. The plugin stores the publisher
and unit identifiers entered by the administrator. The provider's official
JavaScript is loaded only when that provider's rotation slot is displayed. The
visitor's browser then connects directly to the provider host and sends the
normal web-request information required by that provider, which can include the
visitor IP address, browser information, referrer, provider cookies and the
configured publisher/unit identifiers. The provider controls targeting,
consent, inventory, reporting and payment.

* Google AdSense — Terms: https://www.google.com/adsense/terms/ ; Privacy: https://policies.google.com/privacy
* Google Ad Manager — Terms: https://admanager.google.com/home/terms/ ; Privacy: https://policies.google.com/privacy
* Taboola — Terms: https://www.taboola.com/policies/publisher-terms-and-conditions ; Privacy: https://www.taboola.com/policies/privacy-policy
* Outbrain — Terms: https://www.outbrain.com/legal/terms-of-use/ ; Privacy: https://www.outbrain.com/privacy/

Connections also links to official onboarding information for Media.net,
Amazon Publisher Services, Ezoic, Mediavine, Raptive and Monumetric. Merely
viewing the WordPress administration screen does not contact those providers.
Their sites are opened only when the administrator follows a link, or their
separately installed integration is used.

= Affiliate links =

An administrator can save and enable identifiers for Amazon Associates,
Walmart Affiliates through Impact, Awin, CJ Affiliate, Rakuten Advertising,
ClickBank, or eBay Partner Network. No affiliate request runs on activation or
page view. A public sponsored link contains the publisher's identifiers. Except
for ClickBank, its destination search uses the category and refinements entered
by the visitor. ClickBank uses only the administrator's saved affiliate,
vendor, and optional tracking identifiers; visitor preference text is not sent
to ClickBank. When a visitor follows a sponsored link, the browser connects to
that network and sends the URL parameters plus normal request information such
as IP address, browser data and referrer. The network performs attribution and
pays the publisher under its own agreement.

* Amazon Associates — Agreement: https://affiliate-program.amazon.com/help/operating/agreement/ ; Privacy: https://www.amazon.com/gp/help/customer/display.html?nodeId=GX7NJQ4ZB8MHFRNJ
* Walmart/Impact — Program: https://affiliates.walmart.com/ ; Privacy: https://impact.com/privacy-policy/
* Awin — Publisher terms: https://www.awin.com/us/publisher-terms ; Privacy: https://www.awin.com/us/privacy
* CJ Affiliate — Legal terms and privacy: https://www.cj.com/legal
* Rakuten Advertising — Terms: https://rakutenadvertising.com/legal-notices/terms-conditions/ ; Privacy: https://rakutenadvertising.com/legal-notices/services-privacy-policy/
* ClickBank — Client contract: https://support.clickbank.com/hc/en-us/articles/220376507-ClickBank-Client-Contract ; Privacy: https://www.clickbank.com/privacy-policy/
* eBay Partner Network — Agreement: https://partnernetwork.ebay.com/page/network-agreement ; Privacy: https://www.ebay.com/help/policies/member-behavior-policies/user-privacy-notice-privacy-policy?id=4260

= Administrator links and iframe destinations =

The Earnings tab contains a voluntary support link to
https://dainis.net/donate/. It is opened only when the administrator clicks the
button; no background request is made and no tracking parameter is added.

The optional adzworld_iframe shortcode connects a visitor's browser to the URL
explicitly entered by the site owner. The destination receives normal request
information such as IP address and browser headers. The shortcode has no
default URL and renders nothing until a site owner supplies one. The selected
destination's own terms and privacy policy apply.

== Installation ==

WordPress Installation Instructions:

1. Upload the adz.world plugin to your /wp-content/plugins/ directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Create a local advertisement and configure where it should appear
4. Review the external-service disclosure before optionally enabling the
   adz-world.com network or earnings connection
5. Save the desired ad rotation and display settings



== Frequently Asked Questions ==

= What is adz.world really? =
adz.world is the brainchild of composer, human rights advocate, and entrepreneur dainis w michel. The goal of adz.world is for website publishers to be able to generate income without necessarily charging money for access to content. Dainis W. Michel was confronted with a dilemma, when his father left a project behind that they had planned to do together.

Upon the passing of Rev. Dr. Walter L. Michel, his son Dainis found numbered and labeled VHS tapes, almost perfectly arranged, in a closet, dust-free, as if they’d planned to meet and continue where they’d left off 20 years prior.

Dainis had the tapes digitized, but found himself in a quandary regarding how he could offset the expenses of putting his father’s digitized lectures online.

Instead of charging for access or creating a physical product, Dainis developed the adz.world plugin to give web publishers a way to create healthy advertising ecosystems that their audiences would enjoy — so that websites that provide content that people generally don’t pay for could become self-sustaining and could generate revenue & ensure their longevity.

One of the first websites to deploy the adz.world plugin is https://www.bible-experts.com, where visitors can increase their knowledge of the Bible while experiencing faith-related advertisements that help finance the website. As is one of the fundamental purposes of the adz.world plugin, while learning about the Bible, if visitors have expressed interest in other products in their adz.world accounts, then bible-experts.com can receive affiliate commissions for the purchase of really any products purchased while visiting bible-experts.com.

= Does the plugin require the adz-world.com service? =
No. Local advertisements and rotations work without registration or an
external-service connection. The network and earnings connections are separate,
administrator-controlled opt-ins.



== Screenshots ==

1. Register and accept TOS and our Data Protection Mandate
2. Create an adz rotation
3. Set where to display your adz rotation
4. Set adz rotation display timing and type

== Changelog ==

= 1.0.9 =
* Remediated the WordPress.org closure findings: portable AJAX URLs, current metadata, five tags, matching text domain, escaped shortcodes, configured rotation identifiers, and removal of deprecated Chosen and screen-icon code.
* Removed PHP/HTML/JavaScript inline styling and runtime style mutation in favor of enqueued styles and state classes.
* Replaced raw-IP custom-table view tracking with expiring, salted-key WordPress transients.
* Removed unused direct-cURL, client-certificate, Basic-auth, and payment-sandbox prototypes.
* Scoped administrator assets to adz-world screens, removed the iframe's external default URL, and added explicit local/network consent boundaries.
* Added safer template settings and WPCS-clean code and tests.


== Upgrade Notice ==
= 1.0.9 =
Security, privacy, compatibility, and WordPress coding-standards remediation.

= 1.0.1 =
* https tweaks

= 1.0.0 =
Initial release
