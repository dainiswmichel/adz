# ancestor

The original code, preserved untouched. Nothing here is deployed, built, or
loaded by anything. It exists so there is always a pristine reference to
compare against.

| Directory | What it is | Version | Source |
|---|---|---|---|
| `plugin/` | the publisher plugin, as released | 1.0.8 | downloaded from wordpress.org |
| `network/` | Ad Network Control — the adz.world server | 1.0.1 | author's archive |
| `pro/` | Adz.world Premium | 1.0.1 | author's archive |

All three are byte-identical to their source archives. The only files removed
were macOS `.DS_Store` entries and `__MACOSX` folders, which are not code.

`plugin/` here is the ancestor of `/plugin` at the repository root. That one
deploys to wordpress.org and will diverge as work continues; this one does not
change.

## Not here

Two things were never in any archive, and are missing from this record:

- the visitor-facing code that ran on the adz.world **site** itself — the
  theme, the account screens, the preference interface
- whatever generated the `publisher_css/publisher-*.css` files
