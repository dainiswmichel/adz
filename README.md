# adz

Permission-based advertising for WordPress. Visitors say what they want to see;
publishers serve adz that match, without surveillance or a third-party broker.

## Layout

| Directory | What it is | Ships? |
|---|---|---|
| `plugin/` | the WordPress plugin | **yes** — deployed to wordpress.org |
| `network/` | Ad Network Control 1.0.1, the adz.world server | no |
| `pro/` | Adz.world Premium 1.0.1 | no |

Only `plugin/` is deployed. The other two are preserved baselines, imported
verbatim from the original archives.

## Releasing

Publishing a GitHub release deploys `plugin/` to the wordpress.org SVN
repository — trunk, plus a matching tag. Nothing deploys on an ordinary push.

Before a release, the version has to agree in three places:

| Where | Field |
|---|---|
| `plugin/adz-world.php` | `Version:` |
| `plugin/readme.txt` | `Stable tag:` |
| the GitHub release | tag name |

A mismatch does not error during deploy — WordPress.org simply keeps serving
the previous version, silently. `.github/scripts/check-version.sh` runs first
and fails the workflow instead.

A deploy can be rehearsed without releasing: Actions → Deploy to WordPress.org →
Run workflow, with the dry-run box ticked.

### One-time setup

Two repository secrets, under Settings → Secrets and variables → Actions:

- `SVN_USERNAME` — your wordpress.org username
- `SVN_PASSWORD` — the dedicated SVN password from your wordpress.org profile,
  under Account & Security → SVN credentials. The ordinary login password does
  not work for SVN, since two-factor authentication became mandatory.

## Status

The plugin is currently **closed** on wordpress.org — a guideline violation
notice dated 29 September 2026, with 60 days to pass a code review.

Branch `claude/vigilant-brown-3i4cds` carries work against that notice. It has
never run inside WordPress and is not merged here.
