## MediaWiki Info Extension

Adds a **Info `&action=info` link tab** of regular articles to your MediaWiki Skin allowing quick displaying of page informations.

### Installation

Download and upload the zip file to `/extensions` and extract. Rename directory folder `/Info-#-#-#` to `/Info` and add the following to `LocalSettings.php` to enable this extension.

`wfLoadExtension( 'Info' );`

## Configuration
Control the ability by user group to info content. eg `['*']` would allow anyone, `['user']` allows only users or `['sysop']` would only allow sysops.

Allow viewing Info for a specific user group:
`$wgGroupPermissions['{a user group}']['info'] = true;`

## Additional Information
See https://www.mediawiki.org/wiki/Extension:Info for more information about displaying a page's informations.
