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
PLUGIN_FILE="pre-release-candidates/dwm-local/adz-world.php"
README="pre-release-candidates/dwm-local/readme.txt"

HEADER=$(grep -m1 '^ *\* *Version:\|^Version:' "$PLUGIN_FILE" | sed 's/.*Version: *//' | tr -d '[:space:]')
STABLE=$(grep -m1 '^Stable tag:' "$README" | sed 's/^Stable tag: *//' | tr -d '[:space:]')

echo "release tag          : ${TAG:-not supplied; source agreement only}"
echo "plugin header Version: $HEADER"
echo "readme Stable tag    : $STABLE"

FAILED=0

if [[ ! "$HEADER" =~ ^[0-9]+(\.[0-9]+)+$ ]]; then
	echo "::error file=$PLUGIN_FILE::Version header '$HEADER' is not numeric dotted version data."
	FAILED=1
fi

if [[ ! "$STABLE" =~ ^[0-9]+(\.[0-9]+)+$ ]]; then
	echo "::error file=$README::Stable tag '$STABLE' is not numeric dotted version data."
	FAILED=1
fi

if [[ "$HEADER" != "$STABLE" ]]; then
	echo "::error::Plugin Version '$HEADER' and readme Stable tag '$STABLE' do not agree."
	FAILED=1
fi

if [[ -n "$TAG" ]]; then
	# The deploy action uses the GitHub release tag as the SVN directory name.
	# WordPress.org release tags may contain only numbers and periods, so fail
	# instead of accepting a leading "v" and publishing the wrong path.
	if [[ ! "$TAG" =~ ^[0-9]+(\.[0-9]+)+$ ]]; then
		echo "::error::Release tag '$TAG' is invalid. Use a numeric tag such as 1.0.9 (no leading v)."
		FAILED=1
	elif [[ "$HEADER" != "$TAG" ]]; then
		echo "::error file=$PLUGIN_FILE::Version header is '$HEADER' but the release tag is '$TAG'."
		FAILED=1
	fi
fi

if [[ "$FAILED" -eq 1 ]]; then
	echo "::error::Refusing to deploy. All three must match or WordPress.org will keep serving the old version."
	exit 1
fi

if [[ -n "$TAG" ]]; then
	echo "All three agree. Safe to proceed to the remaining release gates."
else
	echo "Plugin Version and readme Stable tag agree."
fi
