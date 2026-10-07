# adz

Permission-based advertising for WordPress. Visitors say what they want to see;
publishers serve adz that match, without surveillance or a third-party broker.

## Layout

| Directory | What it is | Ships? |
|---|---|---|
| `ancestor/` | the 2018 code, frozen — plugin, network server, premium build | no |
| `live-plugin/` | exactly what is live on wordpress.org right now | **yes** |
| `dev-plugin/` | one candidate per agent, each a full copy | not until promoted |

Three directories for the three real states: what shipped in 2018, what is
live today, what is being built.

`live-plugin/` is a mirror, not a workspace. It is byte-identical to SVN trunk
at all times, so `diff -r live-plugin dev-plugin` always shows exactly what a
release would change. Nothing is edited there directly.

Each candidate under `dev-plugin/` is a complete copy of the plugin, named for
the agent that produced it, so candidates can be compared against what is live
and against each other:

```bash
diff -r live-plugin dev-plugin/claude-opus-5
```

Promotion, when one is chosen:

```bash
rsync -a --delete dev-plugin/<agent>/ live-plugin/
```

Then a release, which deploys `live-plugin/`. No agent deploys anything; only
a promotion into `live-plugin/` can reach wordpress.org.



## Releasing

Publishing a GitHub release deploys `live-plugin/` to the wordpress.org SVN
repository — trunk, plus a matching tag. Nothing deploys on an ordinary push.

Before a release, the version has to agree in three places:

| Where | Field |
|---|---|
| `live-plugin/adz-world.php` | `Version:` |
| `live-plugin/readme.txt` | `Stable tag:` |
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

`dev-plugin/claude-opus-5/` currently holds a version bump to 1.0.9 and
nothing else.
