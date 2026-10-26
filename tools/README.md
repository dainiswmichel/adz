# tools

Checks that run without a WordPress install, for use before any commit that
touches a candidate.

## resolve-calls.php

```
php tools/resolve-calls.php pre-release-candidates/<agent>
php tools/resolve-calls.php pre-release-candidates/<agent> --quiet   # skip the unresolved list
```

Resolves every function call in a plugin tree against what the tree and PHP
core actually define. Exits non-zero when it finds an arity mismatch or a
duplicate file-scope definition, so it can gate a commit.

It reports three things:

1. **Unresolved calls.** Calls defined nowhere in the tree and not built into
   PHP. WordPress supplies most of them and this process cannot see WordPress,
   so the list is for reading rather than a verdict. Read the plugin-prefixed
   entries first: `adz_`, `adzworld_` and `eof_` names that resolve to nothing
   are the plugin's own bugs.
2. **Arity mismatches** against the tree's own functions.
3. **File-scope functions defined twice**, which is fatal on the second
   definition. Class methods are tracked per class and excluded.

### Why it exists

PHPCS checks style and known-bad patterns. It does not check that the code can
run. A call to a function nothing defines is a fatal the first time that line
executes, and on an activation path it is a plugin that cannot be activated.

Tests do not necessarily catch it either. One got into this repository exactly
that way: `adzworld_match_adz()` called `adz_get_all_ad_ids()`, nothing defined
it, the test harness stubbed the name, and the tests passed. The rotation would
have fatalled on the first real page view. This tool found it.

It also found a bug in itself, which is worth knowing when reading its output.
String interpolation such as `"{$obj->prop}"` opens with a `T_CURLY_OPEN` token
but closes with a plain `}`, so counting only plain braces drove the scope
depth negative, class scope was lost, and every method in the file was reported
as a file-scope function. That read as a duplicate-definition fatal in all four
candidate trees. Fixed, but a reminder to confirm a finding in the source
before acting on it.

## phpcs.xml.dist

At the repository root, so every agent runs the same check:

```
phpcs --standard=phpcs.xml.dist pre-release-candidates/<agent>
phpcs --standard=phpcs.xml.dist tools
```

Plugin code is held to WordPress Coding Standards. `ancestor/` is excluded
because it is frozen. The scripts in `tools/` are excluded from the
WordPress-runtime sniffs only: they print to a terminal and read files
directly, so there is no HTML to escape and `WP_Filesystem` does not exist in a
CLI process.

Useful subsets:

```
# the sniffs that decide a wordpress.org re-review
phpcs --standard=phpcs.xml.dist \
  --sniffs=WordPress.Security.EscapeOutput,WordPress.Security.ValidatedSanitizedInput,WordPress.Security.NonceVerification,WordPress.DB.PreparedSQL,WordPress.DB.PreparedSQLPlaceholders \
  pre-release-candidates/<agent>
```

## What these do not do

Neither runs the plugin. Activation, the admin screens, the AJAX handlers and
anything that needs a database are untested by both. A clean result from both
means the code is well-formed and internally consistent, not that it works.
