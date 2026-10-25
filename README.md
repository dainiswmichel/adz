# adz

WordPress plugin remediation workspace for `adz-world`.

## Directory layout

| Directory | Purpose | Deploys? |
|---|---|---|
| `ancestor/` | Frozen 2018 plugin, network server, and premium plugin reference | No |
| `adz-world-wordpress-codebase/` | Integrated plugin source assembled from reviewed repairs | Yes, on approved release |
| `pre-release-candidates/<agent>/` | Independent agent repair candidates | No |
| `../adz-world-com/` | Website project: server/WordPress setup, site-specific code, and deployment configuration | Separately, to adz-world.com |

`adz-world-wordpress-codebase/` is the production release source, not a running
WordPress site. It is not yet a claim of WordPress.org compliance. Never modify
`ancestor/`.

## Remediation workflow

Agents work on bounded repairs in their own candidates against an identified
baseline. Each repair must identify changed behavior and tests actually run.
No agent's candidate is authoritative. Compare a candidate with the integrated
source to review its proposed changes:

```bash
diff -qr adz-world-wordpress-codebase pre-release-candidates/claude-opus-5
```

Human and AI review selects individual repairs for integration into
`adz-world-wordpress-codebase/`; do not replace the integrated source wholesale
with an agent's candidate. Run one shared verification gate against the
integrated plugin, including regression tests, PHP lint, PHPCS/WPCS, Plugin
Check, and real WordPress activation with `WP_DEBUG` and `WP_DEBUG_LOG`.

Build one release ZIP from the integrated source and test that exact ZIP on
clean WordPress. Only approved, tested code goes to the demonstration website
or WordPress.org. Nothing in `pre-release-candidates/` deploys directly.

The shared gate is not implemented yet. The existing
[setup specsheet](docs/adz-world-com-do-and-fast-gate-spec-2026-10m-25d.html)
describes the proposed gate; its single-candidate source assumptions must be
aligned with this integration workflow before implementation.

## Website and Git boundaries

The sibling `../adz-world-com/` directory is the website project, not another
plugin source. It runs the approved integrated plugin. Its setup and
site-specific source should be versioned; it is currently empty and is outside
this Git repository.

Git records source, setup scripts, test definitions, dependency manifests and
lockfiles, and reviewed status summaries. Local WordPress runs that source;
the ZIP packages it; the live website runs the approved build.

Do not commit local WordPress core, `wp-config.php`, databases, uploads,
credentials, generated ZIPs, raw test logs, caches, or installed tool
dependencies. Runtime data needs separate backups, not source commits.

## Releasing

Publishing a GitHub release deploys `adz-world-wordpress-codebase/` to the
WordPress.org SVN repository — trunk, plus a matching tag. Nothing deploys on
an ordinary push.

Before a release, the version must agree in three places:

| Where | Field |
|---|---|
| `adz-world-wordpress-codebase/adz-world.php` | `Version:` |
| `adz-world-wordpress-codebase/readme.txt` | `Stable tag:` |
| The GitHub release | Tag name |

`.github/scripts/check-version.sh` checks these values before deployment. A
deploy can be rehearsed without releasing: Actions → Deploy to WordPress.org →
Run workflow, with the dry-run box ticked.

The workflow needs two repository secrets under Settings → Secrets and
variables → Actions:

- `SVN_USERNAME` — your WordPress.org username
- `SVN_PASSWORD` — the dedicated SVN password from your WordPress.org profile

The plugin is closed on WordPress.org pending a guideline review. The
remediation scope and unresolved questions are tracked in
[`docs/remediation-scope.md`](docs/remediation-scope.md).
