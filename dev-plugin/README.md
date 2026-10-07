# dev-plugin

Where development happens. One directory per agent, following the same
convention as
[flosc/pre-release-candidates](https://github.com/dainiswmichel/flosc/tree/main/pre-release-candidates):
each directory is named for the model or tool that produced the work, and each
holds a complete candidate copy of the plugin.

| Directory | Produced by | Version | State |
|---|---|---|---|
| `claude-opus-5/` | Claude Opus 5 | 1.0.9 | version bump only |
| `codex-gpt5/` | Codex / GPT-5 | — | empty |
| `dwm-local/` | Dainis, locally | — | empty |
| `github-copilot/` | GitHub Copilot | — | empty |
| `gpt-5-6-sol/` | GPT-5.6 Sol | — | empty |
| `grok-4-6/` | Grok 4.6 | — | empty |
| `opencode-big-pickle/` | OpenCode Big Pickle | — | empty |

Same roster as flosc. An empty directory holds a `.gitkeep` only, since git
does not track directories on their own.

## How a candidate becomes a release

Each candidate is a full copy of the plugin, so it can be compared directly
against what is live:

```bash
diff -r live-plugin dev-plugin/<agent>
```

That shows exactly what releasing that candidate would change. When one is
chosen:

```bash
rsync -a --delete dev-plugin/<agent>/ live-plugin/
```

Then a GitHub release, which deploys `live-plugin/` to wordpress.org.

Nothing here is deployed. Only `live-plugin/` is.

## Testing a candidate

Symlinking a candidate into a local WordPress install means the running site
is the code git is tracking, with no copying back and forth:

```bash
ln -s /path/to/adz/dev-plugin/<agent> \
      ~/Local\ Sites/<site>/app/public/wp-content/plugins/adz-world
```
