#!/usr/bin/env python3
"""Generate the front-end style-variation stylesheet from theme.json.

The three variations in styles/*.json are the single source of truth for every
colour, gradient and stroke. WordPress reads them for the Site Editor, but it
only ever emits the one variation an administrator has chosen, scoped to :root.
A visitor-facing switcher needs all three on the page at once.

So this script reads the same files and writes each variation's properties a
second time, scoped to :root[data-adz-style="<slug>"]. Nothing is retyped: edit
a variation's JSON and re-run this, and the switcher follows.

Specificity is (0,2,0) against the (0,1,0) WordPress emits, so a visitor's
choice wins regardless of which stylesheet loads last. With no attribute set,
nothing here applies and the administrator's chosen variation shows.

Usage: python3 tools/build-variations.py
"""

import glob
import io
import json
import os
import re
import sys

THEME = os.path.join(
    os.path.dirname(os.path.dirname(os.path.abspath(__file__))),
    'pre-release-candidates', 'site-theme', 'adz-world-2026',
)


def kebab(name):
    """Match WordPress' own camelCase to kebab-case conversion for theme.json keys."""
    return re.sub(r'(?<!^)(?=[A-Z])', '-', name).lower()


def flatten(prefix, node, out):
    """Walk settings.custom the way WordPress does, into --wp--custom--a--b."""
    for key, value in node.items():
        name = '%s--%s' % (prefix, kebab(key))
        if isinstance(value, dict):
            flatten(name, value, out)
        else:
            out[name] = value


def properties(data):
    """The custom properties WordPress emits for one theme.json document."""
    out = {}
    settings = data.get('settings', {})
    for colour in settings.get('color', {}).get('palette', []):
        out['--wp--preset--color--%s' % colour['slug']] = colour['color']
    for gradient in settings.get('color', {}).get('gradients', []):
        out['--wp--preset--gradient--%s' % gradient['slug']] = gradient['gradient']
    for family in settings.get('typography', {}).get('fontFamilies', []):
        out['--wp--preset--font-family--%s' % family['slug']] = family['fontFamily']
    if 'custom' in settings:
        flatten('--wp--custom', settings['custom'], out)
    return out


def element_rules(scope, data):
    """The handful of styles.elements rules a variation changes."""
    rules = []
    styles = data.get('styles', {})
    colour = styles.get('color', {})
    if colour:
        decls = []
        if 'background' in colour:
            decls.append('background-color: %s;' % colour['background'])
        if 'text' in colour:
            decls.append('color: %s;' % colour['text'])
        rules.append('%s body { %s }' % (scope, ' '.join(decls)))
    if 'lineHeight' in styles.get('typography', {}):
        rules.append('%s body { line-height: %s; }' % (scope, styles['typography']['lineHeight']))

    elements = styles.get('elements', {})
    heading = elements.get('heading', {}).get('typography', {})
    if heading:
        decls = []
        if 'fontFamily' in heading:
            decls.append('font-family: %s;' % heading['fontFamily'])
        if 'fontWeight' in heading:
            decls.append('font-weight: %s;' % heading['fontWeight'])
        if 'letterSpacing' in heading:
            decls.append('letter-spacing: %s;' % heading['letterSpacing'])
        rules.append('%s :is(h1, h2, h3, h4, h5, h6) { %s }' % (scope, ' '.join(decls)))

    link = elements.get('link', {}).get('color', {}).get('text')
    if link:
        rules.append('%s a:where(:not(.wp-element-button)) { color: %s; }' % (scope, link))

    button = elements.get('button', {}).get('color', {})
    if button:
        decls = []
        if 'background' in button:
            decls.append('background-color: %s;' % button['background'])
        if 'text' in button:
            decls.append('color: %s;' % button['text'])
        rules.append(
            '%s :is(.wp-block-button__link, .wp-element-button) { %s }' % (scope, ' '.join(decls))
        )
    return rules


def main():
    variations = sorted(glob.glob(os.path.join(THEME, 'styles', '*.json')))
    if not variations:
        sys.stderr.write('No style variations found under %s/styles/\n' % THEME)
        return 1

    out = [
        '/*',
        ' * GENERATED FILE -- do not edit.',
        ' *',
        ' * Written by tools/build-variations.py from styles/*.json, which remain',
        ' * the single source of truth. Re-run that script after changing any',
        ' * variation. Each block below repeats what WordPress already emits for',
        ' * the active variation, scoped to a data attribute so a visitor can pick',
        ' * a different one. With no attribute set, none of this applies.',
        ' */',
        '',
    ]
    titles = []
    for path in variations:
        data = json.load(io.open(path, encoding='utf-8'))
        slug = os.path.basename(path)[:-len('.json')]
        titles.append((slug, data.get('title', slug)))
        scope = ':root[data-adz-style="%s"]' % slug

        out.append('/* %s -- %s */' % (data.get('title', slug), path.split('/')[-1]))
        out.append('%s {' % scope)
        for name, value in properties(data).items():
            out.append('\t%s: %s;' % (name, value))
        out.append('}')
        out.append('')
        for rule in element_rules(scope, data):
            out.append(rule)
        out.append('')

    target = os.path.join(THEME, 'assets', 'variations.css')
    io.open(target, 'w', encoding='utf-8').write('\n'.join(out))
    print('wrote %s' % target)
    for slug, title in titles:
        print('  %-12s %s' % (slug, title))
    return 0


if __name__ == '__main__':
    sys.exit(main())
