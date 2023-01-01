# About

This is a [MediaWiki](https://www.mediawiki.org/) extension that allows for users to choose between various syntax highlighting themes for the [SyntaxHighlight](https://www.mediawiki.org/wiki/Extension:SyntaxHighlight) extension.  More information can be found on the [extension page](https://www.mediawiki.org/wiki/Extension:SyntaxHighlightThemes).

# Requirements

This version of the extension has been tested with MediaWiki 1.35.

# Installation

Add this line to your LocalSettings.php:

```php
wfLoadExtension( 'SyntaxHighlightThemes' );
```

This extension requires the [SyntaxHighlight](https://www.mediawiki.org/wiki/Extension:SyntaxHighlight) extension to be installed.

# Usage

An option will be available under a user's preferences to change the syntax highlighting theme.  All themes available from [Pygments](https://pygments.org/) are supported.
