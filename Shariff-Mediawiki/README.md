Shariff-Mediawiki
=================

A mediawiki-extension for [Shariff](https://github.com/heiseonline/shariff), 
protecting users privacy.

Setup
-----
1. Unpack in the `extensions`-folder of your mediawiki.
2. Edit `Shariff/shariff-backend/index.php` and set the key
   `"domain":` to your domain name.
3. Add `wfLoadExtension('Shariff');` to
   `LocalSettings.php`
4. Write `{{#shariffLike:}}` wherever you want to use social-media-buttons.

