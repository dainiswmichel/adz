# adz

WordPress plugin remediation workspace for `adz-world`.

## Directory layout

| Directory | Purpose | Deploys? |
|---|---|---|
| `ancestor/` | Frozen 2018 plugin, network server, and premium plugin reference | No |
| `pre-release-candidates/dwm-local/` | Dainis' approved production release source | Yes, on approved release |
| `adz-world-wordpress-codebase/` | Historical integrated source retained for comparison | No |
| `pre-release-candidates/<agent>/` | Independent agent repair candidates | No |
| `../adz-world-com/` | Website project: server/WordPress setup, site-specific code, and deployment configuration | Separately, to adz-world.com |

`pre-release-candidates/dwm-local/` is the production release source, not a running
WordPress site. It is not yet a claim of WordPress.org compliance. Never modify
`ancestor/`.

## Remediation workflow

All agents share [the remediation collaboration file](docs/adz-remediation-collaboration-file.html).
It contains stable task IDs, acceptance criteria, regression requirements and
evidence status. Git tracks document versions; no manual revision machinery is
required. Concurrent contributions are merged and preserved. There are no
agent-specific documentation directories.

Agents work on bounded repairs in their own candidates against an identified
baseline. Each repair must identify changed behavior and tests actually run.
No agent's candidate is authoritative. Compare a candidate with the integrated
source to review its proposed changes:

```bash
diff -qr adz-world-wordpress-codebase pre-release-candidates/claude-code-web
```

Human and AI review selects repairs for integration into
`pre-release-candidates/dwm-local/`. Run one shared verification gate against
that production source, including regression tests, PHP lint, PHPCS/WPCS, Plugin
Check, and real WordPress activation with `WP_DEBUG` and `WP_DEBUG_LOG`.

Build one release ZIP from the integrated source and test that exact ZIP on
clean WordPress. Only approved, tested code goes to the demonstration website
or WordPress.org. Agent candidates never deploy. The sole deployable directory
under `pre-release-candidates/` is `dwm-local/`.

The GitHub deployment workflow is the release gate. It checks matching version
metadata, PHP and JavaScript syntax, full WPCS, forbidden inline styling,
diagnostic suppressions, and the official WordPress Plugin Check before the SVN
deployment step. The existing
[setup specsheet](docs/adz-world-com-do-and-fast-gate-spec-2026-10m-25d.html)
is retained as historical design context.

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

Publishing a GitHub release deploys `pre-release-candidates/dwm-local/` to the
WordPress.org SVN repository — trunk, plus a matching tag. Nothing deploys on
an ordinary push.

Before a release, the version must agree in three places:

| Where | Field |
|---|---|
| `pre-release-candidates/dwm-local/adz-world.php` | `Version:` |
| `pre-release-candidates/dwm-local/readme.txt` | `Stable tag:` |
| The GitHub release | Tag name |

`.github/scripts/check-version.sh` checks these values before deployment. A
release tag must contain only numbers and periods, for example `1.0.9`; do not
prefix it with `v`. A deploy can be rehearsed without releasing: Actions →
Deploy to WordPress.org → Run workflow, with the dry-run box ticked.

The workflow needs two repository secrets under Settings → Secrets and
variables → Actions:

- `SVN_USERNAME` — your WordPress.org username
- `SVN_PASSWORD` — the dedicated SVN password from your WordPress.org profile

The plugin is closed on WordPress.org pending a guideline review. Current
tasks and unresolved questions are tracked in
[the shared remediation collaboration file](docs/adz-remediation-collaboration-file.html).
[The earlier scope](docs/remediation-scope.md) retains historical analysis;
its DONE labels do not establish integrated verification.
