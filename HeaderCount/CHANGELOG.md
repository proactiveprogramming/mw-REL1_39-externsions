# Changelog
## Version 1
### 1.0.4
* Update extension metadata (shown in Special:Version)
  * Update URL to point to MediaWiki page instead of GitHub repository
  * Add license-name as MIT
* Make license plaintext so it functions properly as linked in Special:Version
* Renew license for 2017
* Fix non-static functions being called statically (#7) (Alexia E. Smith)

### 1.0.3
* Fix incorrect `type` for Special:Version; it is now correctly placed in the parser hooks section instead of other.
* Change error message from an exclamation point to a period (#6) (Eric Schneider)
* License under the MIT license (#5)
* New Lojban translation (#4) (Eric Schneider)

### 1.0.2
* Fix fatal error trying to call functions on null (#3) (Alexia E. Smith)

### 1.0.1
* Fix `invalid magic word headcount` error when loading the extension through the JSON (#2).
* Add the message directory to the PHP loader file (#1) (Alexia E. Smith)

### 1.0.0
* Initial release