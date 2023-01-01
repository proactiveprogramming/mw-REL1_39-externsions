# Mediawiki-OpauthLogin
Handles user authorization based on Mediawiki-Opauth. 
This extension requres [Mediawiki-Opauth](https://github.com/vedmaka/Mediawiki-Opauth) to be installed.

# Installation

1. Put **OpauthLogin** folder into **extensions** folder
2. Add these lines into **LocalSettings.php** at bottom:
```php
require_once "$IP/extensions/OpauthLogin/OpauthLogin.php";
```
3. Run mediawiki update script:
```bash
php maintenance/update.php --quick
```
4. Done. Now users will be handled automatically when logged/registerd
via social networks (via Opauth). Please notice that by default users 
will be created with account names sources from social networks, but in
case of collision it will try to create suffixed account (with numbers).
If user name retrieved from social network is not a valid Mediawiki account
name then it will generate random UID as username and store name in
"real name" field instead.

5. By default plugin will add register button at login / signup pages, but
this behavior can be disabled by setting `$wgOpauthLoginEnableButtons = false;`