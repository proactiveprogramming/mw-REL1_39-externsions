# Mediawiki Vue.js Sandbox

This dummy extension template is intended to demonstrate how Vue.js can be
used within a MediaWiki environment. It is based on the Boilerplate
extension.

## Installation

Download this extension into the `extensions` directory of your local
MediaWiki instance. Then enable it in `LocalSettings.php` by adding:

```php
wfLoadExtension( 'VueTest' );
```

All Vue.js code is meant to be handled by ResourceLoader at runtime, so
running `npm install` is not necessary. However, there are a number of
`devDependencies` specified in `package.json` that may be useful for
development and testing.

Once set up, running `npm test` and `composer test` will run automated code checks.

## Usage

This extension adds a new special page, `Special:VueTest`. This page contains
several interactive demos to showcase some of what you can do with Vue, as well as
how this tool can be integrated in a MediaWiki envirionment.

## Codex Demos

This extension also includes the Codex repository as a submodule dependency.
On an older version of Git, you must provide explicit instructions to download
the submodule dependency:

```
git submodule update --init --recursive
```

Newer versions of Git will do this automatically.

### Updating Codex Demos

To update the bundled JS and CSS for the Codex component demos, follow these steps:

1. Update the Codex submodule: `git submodule update --remote --merge`
2. Run the build script: `npm run codex:build-demos`

### Currently supported:

* Single-file Vue components using ES5 (plus require/module.exports)
* Vue 3 (using the compatibility build)
* ES6 in component files (except for `import` and `export`)
* Use of the new [Codex](https://doc.wikimedia.org/codex/main/) component library
* Linting JS, CSS/LESS, and HTML in .vue files
* Less compilation in component style blocks
* CSS Janus works (try uselang=ar to see it in action)
* Vue.js Devtools (use debug=true or set $wgVueDevelopmentMode = true in config
* mw.message and mw.api functionality is provided via the `createMwApp` wrapper

### Not currently supported:

* Using non-ES5 JS (in vue files or elsewhere)
* Scoped component styles
* Hot module reloading in development
* Non-LESS pre-processors
* Non-HTML templates (Jade, etc)

[1]: https://vuejs.org/v2/guide/

