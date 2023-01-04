# MediaWiki ShortUrlApi extension
© 2014 Daniel Norton d/b/a WeirdoSoft - www.weirdosoft.com

This is a MediaWiki extension that provides an API for the ShortUrl extension.
It adds:
 - A new property to page queries (action=query&prop=shorturl):
   - path: ShortUrl path (e.g. "/Special:ShortUrl/q45t")
   - code: ShortUrl code (e.g. "q45t")

 - A new query action (action=shorturl), which returns details about specified short URL codes:
   - template: a string formatting template for ShortUrl paths (e.g. "/Special:ShortUrl/$1")
   - an array of details for each code specified in the query

## Installation
1. Review installation instructions at https://www.mediawiki.org/wiki/Manual:Extensions.
2. Review ShortUrl installation instructions at https://www.mediawiki.org/wiki/Extension:ShortUrl.
3. Confirm that the ShortUrl extension is installed. (See #1 & #2.)
4. Install this ShortUrlApi extension. (See #1.)
5. Confirm installation at Special:Version.

## Configuration
1. There is no configuration for this extension.
2. There is no #2.

## Documentation
For API usage, see the self-documenting API home page, available
after installation on the target wiki.
e.g. https://en.wikipedia.org/w/api.php

The latest internal source documentation is at
https://danorton.github.io/wikimedia/mediawiki/extensions/ShortUrlApi/dox/

## License
**GPL v3**

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <https://www.gnu.org/licenses/>.

## Releases
 - **PENDING** 1.0.3-alpha - Initial verion to Gerrit
 - 1.0.2-alpha - bug fixes, enhancements
   - Enhancements:
     - #5 - with action=shorturl, return codes as associative array
     - #3 - added details to auto-generated API help page
   - Bug fixes:
     - #2 - Fixed bug with page query continuation

 - 1.0.1-alpha - Initial release
