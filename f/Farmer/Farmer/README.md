# Extension:Farmer

The Farmer extension allows you to manage a MediaWiki farm as well as make configuration changes to a wiki via a web interface.

https://www.mediawiki.org/wiki/Extension:Farmer


## Prerequisites
Farmer needs following prerequisites:
 1. MediaWiki 1.34.0

## Installation

### matchByURLRegExp Mode ###

1.run SQL file: farmer.sql

Create Tables farmer_wiki, farmer_extension, farmer_wiki_extension

2.edit file: .htaccess
<pre>
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d 
RewriteRule ^[^\/]+/(.+)$ wiki/$1 [PT,L,QSA]
RewriteRule ^[^\/]+/$ wiki/index.php [PT,L,QSA]
RewriteRule ^[^\/]+$ wiki/index.php [PT,L,QSA]
</pre>

3.edit file: wiki/LocalSettings.php
<pre>
require_once( "$IP/extensions/Farmer/Farmer.php" );
 
// We use the internal _matchByURLHostname function to find the wiki name 
$wgFarmerSettings['wikiIdentifierFunction'] = array( 'MediaWikiFarmer', '_matchByURLRegExp' );
$wgFarmerSettings['matchRegExp'] = '/[^\/]+/';
$wgFarmerSettings['matchOffset'] = 0;

$wgFarmerSettings['onUnknownWiki'] =  array('MediaWikiFarmer', '_redirectTo');
$wgFarmerSettings['redirectToURL'] =  $wgServer . $wgScriptPath;

$wgFarmerSettings['dbAdminUser'] = $wgDBuser;
$wgFarmerSettings['dbAdminPassword'] = $wgDBpassword;
$wgFarmerSettings['databaseName'] = $wgDBname;
$wgFarmerSettings['dbTablePrefixSeparator'] = '_';

$wgFarmerSettings['newDbSourceFile'] = realpath( dirname( __FILE__ ) ) . '/maintenance/tables.sql';
$wgFarmerSettings['defaultWiki'] = substr($wgDBprefix,0,strlen($wgDBprefix)-1); # Change it to one you want required by default

$wgFarmerSettings['scriptUrl'] = '/$1';
$wgFarmerSettings['styleUrl'] = '/$1/skins';
$wgFarmerSettings['uploadPath'] = dirname($IP) . '/wikis/$1/images';
$wgFarmerSettings['uploadUrl'] = '/wikis/$1/images';
$wgFarmerSettings['tmpPath'] = dirname($IP) . '/tmp/wikis/$1';
$wgFarmerSettings['interwikiUrl'] = $wgServer . '/$1/index.php/';
$wgFarmerSettings['defaultSkin'] = $wgDefaultSkin;

//File:Wiki.png
$wgFarmerSettings['logoPath'] = dirname($IP) . '/wikis/$1/images/b/bc/Wiki.png';
$wgFarmerSettings['logoUrl'] = '/wikis/$1/images/b/bc/Wiki.png';

$wgFarmer = new MediaWikiFarmer( $wgFarmerSettings );
$wgFarmer->run();
</pre>

## Using Farmer

<pre>
Special:Farmer
</pre>

### Creating wikis

<pre>
Special:Farmer/create
</pre>

### Managing wikis

<pre>
Special:Farmer/admin
</pre>