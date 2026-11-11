#!/usr/bin/env bash
#
# The checks a release has to pass, run against the tree that is actually
# shipped.
#
# Three of the categories the WordPress.org review raised are invisible to
# PHPCS: a static admin-ajax path, an unmaintained bundled library, and a
# readme that does not disclose the external services the code contacts. A
# clean PHPCS run says nothing about them. Each one is checked here by name, so
# a release cannot pass on a WPCS-zero claim alone.
#
# Usage: release-gate.sh [build-dir]

set -uo pipefail

DIR="${1:-${BUILD_DIR:-pre-release-candidates/dwm-local}}"
PHPCS="${PHPCS_BIN:-.phpcs/vendor/bin/phpcs}"
FAILED=0

fail() { echo "::error::$1"; FAILED=1; }
pass() { echo "  OK    $1"; }

echo "Release gate: $DIR"
echo

if [[ ! -f "$DIR/adz-world.php" ]]; then
	fail "$DIR holds no adz-world.php, so there is nothing to release."
	exit 1
fi

# --- 1. PHP syntax -----------------------------------------------------------
echo "PHP lint"
if find "$DIR" -name '*.php' -print0 | xargs -0 -r -n1 php -l > /dev/null 2>&1; then
	pass "every PHP file parses"
else
	fail "a PHP file does not parse."
fi

# --- 2. WordPress Coding Standards ------------------------------------------
echo "WordPress Coding Standards"
if [[ -x "$PHPCS" ]]; then
	# Exit 0 clean, 1 warnings, 2 errors, 3 could not run. A check that could
	# not run is not a pass.
	"$PHPCS" --standard=WordPress --extensions=php --report=summary -q \
		--ignore="$DIR/tests/*,$DIR/review/*" "$DIR"
	case $? in
		0) pass "no errors and no warnings" ;;
		1) fail "WPCS reported warnings." ;;
		2) fail "WPCS reported errors." ;;
		*) fail "WPCS could not run, which is not a pass." ;;
	esac
else
	fail "phpcs is not installed at $PHPCS, so WPCS could not run."
fi

# --- 3. The notice categories PHPCS cannot see ------------------------------
echo "Review categories outside PHPCS"

n=$(grep -rl 'wp-admin/admin-ajax\.php' --include='*.php' --include='*.js' "$DIR" 2>/dev/null \
	| grep -v "$DIR/tests/" | wc -l)
if [[ "$n" -eq 0 ]]; then
	pass "no static admin-ajax.php path"
else
	fail "$n file(s) hardcode wp-admin/admin-ajax.php; use admin_url( 'admin-ajax.php' )."
fi

# A text domain is the last argument of a gettext call, so it is always
# preceded by a comma. Matching the bare string would also hit a class or
# option of the same name, which is not a translation problem.
n=$(grep -rlnE ",[[:space:]]*'(text_domain|eof)'[[:space:]]*\)" --include='*.php' "$DIR" 2>/dev/null | wc -l)
if [[ "$n" -eq 0 ]]; then
	pass "every gettext call uses the adz-world text domain"
else
	fail "$n file(s) still use a text domain other than adz-world."
fi

n=$(grep -rl 'screen_icon\|get_currentuserinfo\|wp_get_http\|create_function' \
	--include='*.php' "$DIR" 2>/dev/null | wc -l)
if [[ "$n" -eq 0 ]]; then
	pass "no deprecated core functions"
else
	fail "$n file(s) call a deprecated core function."
fi

if find "$DIR" -iname '*chosen*' | grep -q .; then
	fail "the unmaintained Chosen library is still bundled."
else
	pass "the Chosen library is gone"
fi

n=$(grep -rlE '^\s*function (role_exists|wp_[a-z_]+)\s*\(' --include='*.php' "$DIR" 2>/dev/null | wc -l)
if [[ "$n" -eq 0 ]]; then
	pass "no core function is redeclared"
else
	fail "$n file(s) redeclare a core function, which fatals on activation."
fi

missing=0
while IFS= read -r f; do
	grep -q "defined( *'ABSPATH' *)" "$f" || { echo "    no ABSPATH guard: $f"; missing=$((missing+1)); }
done < <(find "$DIR" -name '*.php' -not -path "$DIR/tests/*" -not -path "$DIR/review/*")
if [[ "$missing" -eq 0 ]]; then
	pass "every shipped PHP file guards direct access"
else
	fail "$missing shipped PHP file(s) have no ABSPATH guard."
fi

n=$(grep -rE '(get|update|delete)_option\([^)]*\$_(POST|GET|REQUEST|COOKIE)' \
	--include='*.php' "$DIR" 2>/dev/null | wc -l)
if [[ "$n" -eq 0 ]]; then
	pass "no request value reaches an option name"
else
	fail "$n call(s) let request input choose an option name."
fi

# --- 4. readme requirements --------------------------------------------------
echo "readme.txt"
README="$DIR/readme.txt"

tags=$(grep -m1 '^Tags:' "$README" | sed 's/^Tags: *//' | tr ',' '\n' | sed '/^ *$/d' | wc -l)
if [[ "$tags" -le 5 ]]; then
	pass "$tags tags (the directory ignores any beyond five)"
else
	fail "readme declares $tags tags; the directory accepts five."
fi

for field in 'Requires at least' 'Tested up to' 'Stable tag' 'Requires PHP'; do
	value=$(grep -m1 "^$field:" "$README" | sed "s/^$field: *//" | tr -d '[:space:]')
	if [[ -z "$value" ]]; then
		fail "readme has no '$field'."
	elif [[ "$field" == 'Requires at least' || "$field" == 'Tested up to' ]] \
		&& ! [[ "$value" =~ ^[0-9]+\.[0-9]+$ ]]; then
		fail "readme '$field' is '$value'; the directory wants a major version such as 6.2."
	else
		pass "$field: $value"
	fi
done

if grep -q '^== External services ==' "$README"; then
	pass "readme has an External services section"
else
	fail "readme has no '== External services ==' section."
fi

# Every provider whose script the code loads into a visitor's browser has to be
# named in the readme with its own terms and privacy links.
for host_and_name in \
	'pagead2.googlesyndication.com:AdSense' \
	'securepubads.g.doubleclick.net:Ad Manager' \
	'cdn.taboola.com:Taboola' \
	'widgets.outbrain.com:Outbrain' \
	'hop.clickbank.net:ClickBank'
do
	host="${host_and_name%%:*}"
	name="${host_and_name#*:}"
	if grep -rq "$host" --include='*.php' --include='*.js' "$DIR" 2>/dev/null; then
		if grep -qi "$name" "$README" \
			&& grep -i -A 8 "$name" "$README" | grep -qiE 'privacy|terms'; then
			pass "$name is contacted and disclosed"
		else
			fail "the code contacts $host but the readme does not disclose $name with terms and privacy links."
		fi
	fi
done

# --- 5. what must not ship ---------------------------------------------------
echo "Release payload"
if [[ -f "$DIR/.distignore" ]]; then
	pass ".distignore present"
	for excluded in /tests /review; do
		grep -qx -- "$excluded" "$DIR/.distignore" \
			&& pass "$excluded is excluded from the ZIP" \
			|| fail "$excluded is not excluded in .distignore."
	done
else
	fail "$DIR has no .distignore, so tests/ and review/ would ship."
fi

echo
if [[ "$FAILED" -ne 0 ]]; then
	echo "Release gate FAILED."
	exit 1
fi
echo "Release gate passed."
