# Candidate plugins

Each candidate directory is a complete plugin copy. Candidates are for
remediation and testing; none deploy directly.

Each agent works on bounded repairs against an identified baseline. No
candidate is authoritative. Compare a candidate with the integrated source
to review its proposed changes:

```bash
diff -qr ../adz-world-wordpress-codebase claude-code-web
```

Symlink a candidate into a local WordPress installation so the site runs the
same files tracked by Git:

```bash
ln -s /path/to/adz/pre-release-candidates/<candidate> \
      /path/to/wordpress/wp-content/plugins/adz-world
```

Enable `WP_DEBUG` and `WP_DEBUG_LOG` in the local site's `wp-config.php`.
Review and integrate individual repairs into `../adz-world-wordpress-codebase/`,
not whole candidate trees. Record changed behavior and tests actually run.
The shared gate and release ZIP use that integrated source; candidate testing
does not replace integration testing.
