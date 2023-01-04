TimeConvert
===========

A MediaWiki extension that provides a parser function and Scribunto Lua library
to convert a time to a different time zone.

* Project site: http://github.com/dlh/MediaWiki-TimeConvert
* MediaWiki page: http://www.mediawiki.org/wiki/Extension:TimeConvert

Examples
--------

    {{#timeconvert:2014-01-01 13:00 GMT|America/New_York}}       => 2014-01-01T08:00:00-0500
    {{#timeconvert:2014-01-01 13:00 GMT|America/New_York|g:i A}} => 8:00 AM
    {{#timeconvert:2014-01-01 8:00 AM EST|Etc/GMT|G:i}}          => 13:00

Extension Documentation
-----------------------

    {{#timeconvert:date time|time zone|format}}

* `date time`: A [date time
  string](http://www.php.net/manual/en/datetime.formats.php).
* `time zone`: The [time zone](http://www.php.net/manual/en/timezones.php) to
  convert `date time` to.
* `format`: The [output format](http://www.php.net/manual/en/function.date.php)
  to use. The default is [ISO 8601](http://en.wikipedia.org/wiki/ISO_8601).

Scribunto Lua Library
---------------------

TimeConvert provides a [Scribunto](http://www.mediawiki.org/wiki/Extension:Scribunto) library,
`mw.ext.timeconvert`. Examples:

    local timeconvert = mw.ext.timeconvert.timeconvert
    timeconvert("2014-01-01 13:00 GMT", "America/New_York")          => "2014-01-01T08:00:00-0500"
    timeconvert("2014-01-01 13:00 GMT", "America/New_York", "g:i A") => "8:00 AM"
    timeconvert("2014-01-01 8:00 AM EST", "Etc/GMT", "G:i")          => "13:00"

Download
--------

Using git:

    git clone https://github.com/dlh/MediaWiki-TimeConvert.git TimeConvert

A zip file snapshot of the repository is also available on the project site.

Installation
------------

TimeConvert requires PHP ≥ 5.2.0 and has only been tested on MediaWiki 1.18+.

1. Move the `TimeConvert` directory to your site's `extensions` directory.
2. Edit `LocalSettings.php` and add the following line near the bottom:

        require_once("$IP/extensions/TimeConvert/TimeConvert.php");

License
-------

MIT license. See LICENSE.txt.
