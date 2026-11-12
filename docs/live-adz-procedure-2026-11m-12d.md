# Getting live adz serving on adz-world.com

## What I measured on the live site, 12 November

| | state |
|---|---|
| `ads.txt` | **correct** — `google.com, pub-5083802405715121, DIRECT, f08c47fec0942fa0` |
| AdSense loader on the page | **present** — `adsbygoogle.js?client=ca-pub-5083802405715121` |
| `<ins class="adsbygoogle">` elements | **0** |
| `data-adzworld-mount` elements | **0** |

The loader runs and has nothing to fill. **No code change is needed.** The
plugin only emits an ad mount when a network row passes three gates and a
rotation slot points at it. None of that is configured yet.

## Why nothing renders

`adzworld_network_rotation_sources()` only offers a network as a rotation slot
when `$row['enabled'] === '1'`, and `enabled` is only set to `'1'` when **all
three** of these hold (`adzworld-networks.php`):

1. every official field is valid,
2. `accepted === '1'` — the administrator accepted that host,
3. `enabled === '1'` — the administrator ticked enabled.

For AdSense the official fields and their patterns are:

| field | label in the UI | must match |
|---|---|---|
| `client` | Publisher ID | `/^ca-pub-[0-9]{10,22}$/` |
| `slot` | Ad unit ID | `/^[0-9]{6,20}$/` |

A publisher ID alone is not enough. **An ad unit ID is required**, and that is
the piece AdSense does not give you during onboarding — you create it.

---

## Procedure

### 1. Create an ad unit in AdSense

AdSense → **Ads** → **By ad unit** → **Display ads**. Name it (e.g.
`adz-world rotation`), choose **Responsive**, Create.

The code it shows you contains both values:

```html
<ins class="adsbygoogle"
     data-ad-client="ca-pub-5083802405715121"
     data-ad-slot="1234567890">
```

Take `data-ad-slot` — the 10-digit number. That is the Ad unit ID. The plugin
builds the `<ins>` itself; do not paste the snippet anywhere.

### 2. Enter it in the plugin

WordPress admin → **Adz → Connections** → Google AdSense:

- Publisher ID: `ca-pub-5083802405715121`
- Ad unit ID: the number from step 1
- Tick **accept this host** (`pagead2.googlesyndication.com`)
- Tick **Enabled**
- Save

If Enabled will not stay ticked, one of the two fields failed its pattern.
Check for a trailing space, and that the publisher ID keeps its `ca-` prefix —
AdSense displays it both with and without.

### 3. Add the slot to a rotation

**Adz → Rotation**, open the rotation the front page uses, **Add slot**, and
choose the AdSense entry. It appears in the list as
`Provider — Google AdSense`, stored as `source:network_adsense`. Save.

The slot list is a strict allowlist: if the entry is not offered, step 2 did
not complete, and nothing in step 3 will work around that.

### 4. Confirm the mount reaches the page

```bash
curl -s https://adz-world.com/ | grep -c 'data-adzworld-mount="adsense"'   # expect 1
curl -s https://adz-world.com/ | grep -o 'data-ad-slot="[^"]*"' | head -1
```

If the count is 0 after saving, the rotation is caching: load the page as a
logged-out visitor in a private window, and flush any page cache.

### 5. Expect a delay before anything paints

A brand-new ad unit commonly serves blank for a few hours, and a newly
connected site can sit in review. A blank `<ins>` with `data-ad-status="unfilled"`
means the chain works and Google simply has no fill yet. Check with:

```bash
# in the browser console on the live page
document.querySelector('ins.adsbygoogle')?.dataset.adStatus
```

- `filled` — working
- `unfilled` — wiring correct, no inventory yet
- `undefined` with the element present — the loader has not run; check for an
  ad blocker or a console error
- no element at all — step 3 did not take

---

## Do not break the two rules that got the plugin closed

1. **Never paste the AdSense snippet into a post, widget or theme file.** The
   plugin builds the `<ins>` from the stored identifiers so the markup stays
   escaped and reviewable. A pasted snippet is unescaped third-party script in
   content.
2. **Keep the AdSense slot out of a carousel format.** AdSense prohibits
   serving into a hidden container, and every format except **Single** hides
   the inactive slides. Set the rotation's display format to Single, or give
   AdSense its own rotation. The readme already states this; the Rotation tab
   does not yet warn, which is a code fix still outstanding.

---

## SEO, measured the same way

| | state |
|---|---|
| `<title>` | good — "adz.world – Permission-based advertising that adds value to humans." |
| canonical | good — `https://adz-world.com/` |
| `<h1>` count | 1 |
| **meta description** | **missing** |
| **Open Graph / `og:title`** | **missing entirely** |
| **JSON-LD structured data** | **none** |
| **`robots.txt`** | **404** |
| **`wp-sitemap.xml`** | **404** |

The last two matter most and are the strangest. WordPress serves both
virtually, so a 404 means the request is not reaching WordPress — Apache is
answering. Check in this order:

1. **Settings → Reading** — if "Discourage search engines" is ticked, core
   disables the sitemap. Untick it.
2. **Permalinks** — Settings → Permalinks → Save (rewrites rebuild).
3. **A physical file** — `ls -la` the web root for a real `robots.txt` or
   `sitemap.xml` shadowing the virtual one.
4. **`.htaccess`** — confirm the standard WordPress block is present and that
   `RewriteRule . /index.php [L]` is reached for non-existent files.

Meta description, Open Graph and JSON-LD are not core WordPress. They come
from an SEO plugin or from the theme. Adding four tags to the theme's head is
smaller and faster than installing an SEO plugin on a site this size, and
keeps the page weight where it is.
