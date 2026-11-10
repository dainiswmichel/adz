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

= Configurable rotation shortcode (Codex candidate) =

[adz_rotation slots="ad:123,ad:456,intro" display="flat" controls="both"]

Only explicitly selected slots appear. ad:123 means a published, unprotected
local ad post; intro means the optional permission introduction. No adz or
introduction is inserted automatically. Replace example IDs with your own.
display accepts single, flat or 3d. controls accepts arrows, dots, both or none.
Without JavaScript, configured slides remain readable.

Adz > Rotation sources registers a shortcode supplied by an advertising plugin
already installed on your site. Saving or removing a source requires an
administrator with unfiltered HTML permission and a valid nonce. This uses
the existing plugin's rendering; it does not create network accounts, issue
credentials, or certify any provider's approval of a placement.

[adz_rotation slots="source:existing:9,ad:123:1" display="single"]

This example makes a weighted random choice per uncached page render: nine
parts existing source, one part local ad. It is not a guaranteed count per
ten accesses. A page cache also caches that choice. Carousel modes contain
each selected slot in order; weights apply only to single mode. Provider
units are not refreshed by navigation. Provider rules and existing consent
configuration still apply; no claim of universal network compatibility is made.
Carousel slides other than the active slide become hidden or inert. Providers
that prohibit hidden placements must use single mode, not a carousel. No provider
tag is automatically refreshed or re-requested as a slide changes.

The Dashboard keeps Connections, Affiliate programs, Rotation, Earnings, and
Templates on one administrator screen. Connections accepts the site owner's
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

Candidate remediation behavior: affiliate metadata is saved by the authorized
local advertisement editor even when the advertising network is disconnected.
A failed remote deletion retains the network advertisement ID for a retry.
Transport failures and invalid registration responses do not erase existing
publisher credentials. Settings imports are administrator-only, read their
uploaded JSON locally, and use the same configured-field validation as normal
saves. Exports require both administrative permission and an export nonce.

The legacy flat-response REST adapter now reuses WordPress HTTP transport.
The plugin's network connection uses query credentials; the adapter also
supports explicitly supplied headers and Digest authentication. It does not
implement client-certificate or hand-built Basic authentication.

Candidate earnings statement connection: an administrator may separately opt
in on Adz.world > Earnings and configure a publisher ID and a read-only token.
Only an explicit statement request sends that token to
https://adz-world.com/wp-json/adz-world/v1/earnings. The service also receives
the requesting server's IP address. No earnings request happens automatically
on activation or a public page view. Disconnecting removes the stored token.
The endpoint is a proposed service contract, not a deployed service in this
candidate. No payment-provider secrets belong in a publisher installation.

The plugin is free. Optional adz-world monetization uses adz-world's affiliate
or referral identifiers with explicit publisher consent. adz-world retains
10% of settled affiliate commission actually received; the publisher receives
90%. It is not 10% of product sales. Existing publisher links are not silently
rewritten. Actual settlement ingestion remains a launch dependency. No account
code is fabricated. When an administrator saves and enables an affiliate account
under Adz.world > Affiliate programs, a public page that is
showing a category includes a sponsored link. The link query is that
category text plus the refinement text attached to it. The visitor's
browser sends that query to the network when the link is followed.
The public link contains the publisher's tracking identifiers and does
not contain a secret token. No affiliate request runs on activation or
on a page view. Amazon Associates, Walmart Affiliates through Impact,
Awin (ShareASale closed on 6 October 2025), CJ Affiliate, Rakuten
Advertising, and the eBay Partner Network are the networks with a
builder. The affiliate network attributes qualifying activity to the site
owner's own account and pays that account under its existing terms. adz.world
does not receive or redistribute commission from these direct publisher links.

This candidate contains no payment-provider adapter and cannot initiate a
payment or payout. Real-money eligibility, provider choice, fees, tax handling,
and legal review remain unresolved. Review support under tests/ and review/ is
not intended for the release-plugin payload.

By default this plugin connects to no external service. Your site serves only
the adz you create on it, and no visitor data leaves your server.

The plugin can optionally be connected to the adz-world.com advertising
network. This is off unless you switch it on under Adz.world > Connections by ticking
"Connect to the adz-world.com network".

With it switched on:

* **What the service is.** adz-world.com is a permission-based advertising
  network. It supplies adz to display on your site, and makes the adz you
  publish available to other sites in the network.
* **What is sent, and when.** When a visitor is shown an adz, your site sends
  adz-world.com your publisher ID and access tokens, your referral code, and
  the visitor's IP address, so the network can return an adz and avoid
  repeating one the visitor has already seen. When you publish or delete an
  adz, its title, content and categories are sent. When you register, your site
  URL, site title and administrator email address are sent.
* **Terms and privacy.** Terms of Service: https://adz-world.com/terms/ ,
  Privacy Policy: https://adz-world.com/privacy/

Switching the network setting off stops network ad delivery and synchronization.
The separate earnings connection makes a request only when an administrator
explicitly requests a statement; disconnecting it removes its stored token.

The Earnings tab contains a voluntary support link to
https://dainis.net/donate/. The plugin does not request that page, load its
assets, or send administrator data in the background. The administrator's
browser visits it only after the “Buy Dainis a coffee” button is clicked. The
link contains no affiliate, referral, or tracking parameters.

The optional adzworld_iframe shortcode connects a visitor's browser to the
HTTP or HTTPS URL explicitly entered by the site owner. That destination can
receive normal web-request data such as the visitor's IP address and browser
headers. The shortcode has no default URL and renders nothing until a site
owner supplies one. The destination's own terms and privacy policy apply.

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
