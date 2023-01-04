# mw-rtf

MediaWiki extension for Rich Text Format support

## Setup

```sh
composer install
ln -s /path/to/extenstion /usr/mediawiki/extensions/Rtf
echo "wfLoadExtension( 'Rtf' );" >> /etc/mediawiki/LocalSettings.php
```

## Usage

1. Create an article whose contents are the source of an RTF document.
2. Navigate to *Page information* (`?action=info`).
3. Select *change* for *Page content model*.
4. Choose the *rtf* content model.
5. Confirm your changes.

## Rationale

I am using MediaWiki somewhat like a content management system for my old
computer website. In addition to containing information about the computers in
my collection, it holds documentation extracted from the software running on
those computers. Some of this documentation is stored in Rich Text Format
documents. By adding support for RTF as a MediaWiki content model, these
documents can be searched for and browsed like any other article on the
website; they don't need to be treated as opaque documents or tediously
hand-converted to wikitext like I had been previously doing.

## License

Copyright (C) 2022 Hunter Turcin

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License along
with this program; if not, write to the Free Software Foundation, Inc.,
51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
