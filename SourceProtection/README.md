This MediaWiki extension removes the ability to view the source code of a page if a user does not have edit rights. Documentation can also be found here: https://www.mediawiki.org/wiki/Extension:SourceProtection

## SourceProtection

1.1.0 Removes View Source tab and (if pageForms is installed the edit-Form tab) & History tab from menu and disables action=edit function for users with no edit permissions.
It will also block the viewing of the readonly form.

1.1.1 Added more actions for user without edit permissions to protect the source  of a page more thoroughly.

1.1.2 Checked compatibility with 1.35+. No major changes, just typos or comments added

### Compatibility

* PHP 5.4+
* MediaWiki 1.24+


### Installation

(1) Extract the files in a directory called `SourceProtection` in your `extensions/` folder.

(2a) MW1.22 or lower : Add the following code at the bottom of your "LocalSettings.php" file:
```
require_once "$IP/extensions/sourceProtection/SourceProtection.php";
```
(2b) MW1.22 + : Add the following code at the bottom of your "LocalSettings.php" file:
```
wfLoadExtension( 'SourceProtection' );
```
(3) Go to "Special:Version" on your wiki to verify that the extension is successfully installed.

(4) Done.


### Configuration

There is no need for additional configuration, besides giving users an edit permission of false in the localsettings.

### Note

It makes no sense to install this extension to hide the sourcecode of a page if you do not take various other actions as well.
e.g. If you open the API with default edit rights, source content can still be read. Likewise for anonymous users. If they
receive edit rights, the sourcecode of page can be viewed, edited, etc..
