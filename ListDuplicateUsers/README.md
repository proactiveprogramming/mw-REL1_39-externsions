# ListDuplicateUsers

This is a MediaWiki extension that creates a special page that lists duplicate user accounts, which are determined by email address.

## Installation

1. Rename the folder to "ListDuplicateUsers".
2. Move the folder to the "extensions" directory, possibly located at "/opt/htdocs/mediawiki/extensions/".
3. Append these lines to the end of the "LocalSettings.php" file, possibly located at "/opt/htdocs/mediawiki/LocalSettings.php":
```
wfLoadExtension('ListDuplicateUsers');
$wgGroupPermissions['listduplicateusers']['listduplicateusers'] = true;
$wgGroupPermissions['bureaucrat']['listduplicateusers'] = true;
```
4. Restart the web server. The command on CentOS is:
```
systemctl restart httpd
```
5. Access the special page "Special:ListDuplicateUsers" on the wiki.