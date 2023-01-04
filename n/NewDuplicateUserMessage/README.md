# NewDuplicateUserMessage

This MediaWiki extension adds a message to the talk pages of newly created duplicate user accounts, which are determined by email address.

See the documentation for the original extension:

https://www.mediawiki.org/wiki/Extension:NewUserMessage

## Installation

1. Rename the folder to "NewDuplicateUserMessage".
2. Move the folder to the "extensions" directory, possibly located at "/opt/htdocs/mediawiki/extensions/".
3. Append this line to the end of the "LocalSettings.php" file, possibly located at "/opt/htdocs/mediawiki/LocalSettings.php":
```
wfLoadExtension('NewDuplicateUserMessage');
```
4. Restart the web server. The command on CentOS is:
```
systemctl restart httpd
```
5. Create the "Template:DuplicateUserWelcome" page on the wiki:
```
== Duplicate user message ==
You have duplicate accounts: {{{duplicateUsers}}}.
```

## Configuration

The configuration is the same as the original extension except instead of "NewUser", use "NewDuplicateUser".

For example:
* Instead of `$wgNewUserSuppressRC = false;`, use `$wgNewDuplicateUserSuppressRC = false;`.
* Instead of "MediaWiki:Newusermessage-template", use "MediaWiki:Newduplicateusermessage-template".
