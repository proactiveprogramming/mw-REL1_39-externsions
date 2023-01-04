# YouTrackTicket

YouTrackTicket is an extension for [MediaWiki](https://www.mediawiki.org/)
which will parse all `<youtrack>` tags into links to the respective
[YouTrack](https://www.jetbrains.com/youtrack/) issue page. This extension
encapsulates all references to your YouTrack issues so that any changes to the
server URL can be maintained in a single location instead of editing all pages
with issue IDs.

## Usage

Use the `<youtrack>` tag to specify the YouTrack issue ID:

```
<youtrack>FOOBAR-123</youtrack>
```

This will render a link to the "FOOBAR-123" issue in your YouTrack instance.
The link will contain a small YouTrack logo to its right. This can be
optionally disabled.

## Installation

Follow these steps to install the extension:

1. Download or clone this repository in a new directory called
   `YouTrackTicket` in the `extensions` directory.
2. Add the following to the bottom of `LocalSettings.php`:
```php
require_once "$IP/extensions/YouTrackTicket/YouTrackTicket.php";
```

Navigate to "Special:Version" on your wiki to verify that the extension is
successfully installed.

## Configuration

The following configuration variables are available. At minimum,
**$wgYouTrackTicketUrl** needs to be set in order for YouTrackTicket to work
correctly.

**$wgYouTrackTicketUrl** [string]

This is the URL to the target YouTrack instance and should not contain a
trailing slash. Example:

```php
$wgYouTrackTicketUrl = 'https://support.company.tld';
```

**$wgYouTrackTicketShowImage** [boolean] _(Optional)_

If defined, specifies whether or not to show a small YouTrack logo next to the
rendered link. By default, YouTrackTicket will show the logo.
