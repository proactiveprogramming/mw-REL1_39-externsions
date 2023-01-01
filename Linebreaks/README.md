# LineBreaks

This is a MediaWiki extension which allows you to use Markdown line breaks.

Specifically it converts `\s\s\n` and `\\\n` to `<br />\n` during the parsing stage.

## Installation

* Clone this repo to your extension folder.
```
git clone --depth 1 https://github.com/dli7319/mediawiki-linebreaks-extension.git /var/www/html/extensions/LineBreaks
```
* Add `wfLoadExtension( 'LineBreaks' );` to your `LocalSettings.php`.

## Options
| Setting              | Default value                 | Description                                   |
| -------------------- | ----------------------------- | --------------------------------------------- |
| `$wgLbUseBackslash`  | `false`                       | Allow `\\\n` linebreaks. Note this may conflict code blocks.|
