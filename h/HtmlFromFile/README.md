# HtmlFromFile MediaWiki extension

## What it does

It includes some HTML snippet in the rendering of a wiki page.

### What exactly it does

In the wiki article you write

```
<htmlfromfile>someName</htmlfromfile>
```

In your `LocalSettings.php` you have something like

```php
$wgHtmlFromFileMappings = array(
	'someName' => '/path/to/some/html/snippet.html',
	'anotherName' => '/path/to/something/else.html',
);
```

Then the snippet associated with the given name (here "someName") is inserted when the wiki page is rendered. Note that the file is re-read on each request.

### Remote Files

*HtmlFromFile* uses the PHP function `file_get_contents`, so depending on your PHP settings, you can also include remote files by setting a URL as file path.

## Installation

Put the `HtmlFromFile.php` file somewhere on your server (typically in a subdirectory of your MediaWiki "extensions" directory). In your wiki's `LocalSettings.php` add this line:

```php
require_once "$IP/extensions/HtmlFromFile/HtmlFromFile.php";
```

Of course you must replace the path with the path where you put the `HtmlFromFile.php` file if you didn't put it in the default location.

## Security Warning

*Never* include html snippets from an untrusted source. They can include code to steal your users' authentication token and post spam all over your wiki.

## License

This whole project is released under the terms of the [CC0 1.0][1]. See `LICENSE` for the full license text.

[1]: https://tldrlegal.com/license/creative-commons-cc0-1.0-universal
