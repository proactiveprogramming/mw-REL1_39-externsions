A simple MediaWiki extension that lets users tweet about their wiki edits.

Installation
------------

Append the following line to your **LocalSettings.php**

``require_once( "$WIKI_DIR/extensions/Tweetiki/Tweetiki.php" );``

Extension Variables
-------------------

You'll need to set your extension variables in your **LocalSettings.php**

- ``$wiki_url`` - Url to your MediaWiki installation with trailing slash preserved.
- ``$api_key`` - The api key of your Twitter app.
- ``$api_secret`` - The api secret of your Twitter app.
