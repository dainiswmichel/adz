#!/usr/bin/env bash
# Publish the adz-world.com content pages. Run from the repo root on the live
# install. Idempotent: re-running updates nothing and creates duplicates, so
# run it once.
set -euo pipefail

D="docs/site-pages/content"

publish() {
	local file="$1" title="$2" slug="$3"
	local id
	id=$(wp post create "$D/$file" --post_type=page --post_status=publish \
		--post_title="$title" --post_name="$slug" --porcelain)
	printf '  %-42s  id=%s  /%s/\n' "$title" "$id" "$slug"
}

publish 01-what-is-permission-based-advertising.html "What is permission-based advertising?" "permission-based-advertising"
publish 02-how-a-rotation-works.html                 "How a rotation works"                 "how-a-rotation-works"
publish 03-adz-and-sads.html                         "Adz and sads"                         "adz-and-sads"
publish 04-what-the-plugin-sends.html                "What the plugin sends"                "what-the-plugin-sends"
publish 05-for-site-owners.html                      "For site owners"                      "for-site-owners"
publish 06-about.html                                "About adz.world"                      "about"

echo
echo "Now add them to the main menu, then request the AdSense review."
