<?php
// MWZenphoto.php
// MediaWiki extension to allow images to be included from a local Zenphoto
// gallery using a <zenphoto> tag within the wiki markup, e.g.:
// 
//  <zenphoto>albumname|filename.jpg</zenphoto>
//
// The image is embedded in a thumbnail size, and linked to the viewing page
// within the gallery. The size defaults to 300, but can be specified, as can
// the alignment. A more complex example:
//
//  <zenphoto>album/subalbum|file.jpg|450px|right|alt=Some alt text</zenphoto>
//
// Version 0.11
// Copyright (C) 2009 Ciaran Gultnieks <ciaran@ciarang.com>
//
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU Affero General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU Affero General Public License for more details.
//
// You should have received a copy of the GNU Affero General Public License
// along with this program.  If not, see <http://www.gnu.org/licenses/>.
//


// Base URL for Zenphoto - can be absolute or relative
$ZP_baseURL='/gallery';

// The image suffix used by Zenphoto for image pages. This is
// set on your Zenphoto options page, as "mod_rewrite suffix"
$ZP_imageSuffix='/view';

$wgExtensionFunctions[]='wfMWZenphoto';
$wgExtensionCredits['parserhook'][]=array(
	'path'=>__FILE__,
	'name'=>'MWZenphoto',
	'description'=>'Enables inclusion of images from Zenphoto',
	'version'=>0.11,
	'author'=>'Ciaran Gultnieks',
	'url'=>'http://ciarang.com/posts/mwzenphoto');

function wfMWZenphoto() {
	global $wgParser;
	$wgParser->setHook('zenphoto','renderZenphoto');
}
 
function renderZenphoto($input) {
	global $ZP_baseURL;
	global $ZP_imageSuffix;

	// Check and handle all parameters...
	$params=explode('|',$input,5);
	if(sizeof($params)<2)
		return '<strong class="error">MWZenphoto: At least Album and Filename must be specified</strong>';
	$album=$params[0];
	$filename=$params[1];
	$params=array_slice($params,2);
	$align='';
	$size='';
	$alt='';
	foreach($params as $p) {
		if(in_array(strtolower($p),array('none','right','left','center'))) {
			$align=strtolower($p);
		} elseif(substr($p,-2)=='px' && is_numeric(substr($p,0,-2))) {
			$size=substr($p,0,-2);
		} elseif(substr($p,0,4)=='alt=') {
			$alt=substr($p,4);
		} else {
			return '<strong class="error">MWZenPhoto: Parameter not recognised - '.$p.'</strong>';
		}
	}
	if($align=='')
		$align='left';
	if($size=='')
		$size='300';

	// Generate HTML output...
	if($align=='left' || $align=='right') {
		$class='float'.$align;
	} else {
		$class='floatnone';
	}
	$out=<<<EOT
<div class="$class"><a href="$ZP_baseURL/$album/$filename$ZP_imageSuffix" class="image" title="$alt"><img alt="$alt" src="$ZP_baseURL/$album/image/$size/$filename"></a></div>
EOT;
	if($align=='center') {
		$out='<div class="center">'.$out.'</div>';
	}
	return $out;
}

?>
