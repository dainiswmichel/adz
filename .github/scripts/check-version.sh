#!/usr/bin/env bash
#
# Fail the release when the version number disagrees across the three places
# WordPress.org reads it from.
#
# A mismatch does not error anywhere in the deploy: WordPress simply keeps
# serving the previous version, with nothing in the logs to say why. This
# turns that silent failure into a loud one.
#
# Usage: check-version.sh <release-tag>

set -euo pipefail

TAG="${1:-}"
PLUGIN_FILE="live-plugin/adz-world.php"
README="live-plugin/readme.txt"

if [[ -z "$TAG" ]]; then
	echo "::error::No release tag supplied."
	exit 1
fi

# A leading "v" is a common tag convention; WordPress versions never carry one.
TAG="${TAG#v}"

HEADER=$(grep -m1 '^ *\* *Version:\|^Version:' "$PLUGIN_FILE" | sed 's/.*Version: *//' | tr -d '[:space:]')
STABLE=$(grep -m1 '^Stable tag:' "$README" | sed 's/^Stable tag: *//' | tr -d '[:space:]')

echo "release tag          : $TAG"
echo "plugin header Version: $HEADER"
echo "readme Stable tag    : $STABLE"

FAILED=0

if [[ "$HEADER" != "$TAG" ]]; then
	echo "::error file=$PLUGIN_FILE::Version header is '$HEADER' but the release tag is '$TAG'."
	FAILED=1
fi

if [[ "$STABLE" != "$TAG" ]]; then
	echo "::error file=$README::Stable tag is '$STABLE' but the release tag is '$TAG'."
	FAILED=1
fi

if [[ "$FAILED" -eq 1 ]]; then
	echo "::error::Refusing to deploy. All three must match or WordPress.org will keep serving the old version."
	exit 1
fi

echo "All three agree. Safe to deploy."
