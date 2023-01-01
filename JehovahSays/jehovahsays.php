<?php
/**
 * Allows Custom HTTP Security Headers to be added to the wiki as configured in the LocalSettings.php file.
 *
 *The MIT License (MIT)


 *Copyright (c) 2016 Morgan Shatee Byers


 *Permission is hereby granted, free of charge, to any person obtaining a copy
 *of this software and associated documentation files (the "Software"), to deal
 *in the Software without restriction, including without limitation the rights
 *to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 *copies of the Software, and to permit persons to whom the Software is
 *furnished to do so, subject to the following conditions:
 
 *The above copyright notice and this permission notice shall be included in all
 *copies or substantial portions of the Software.
 
 *THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 *IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 *FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 *AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 *LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 *OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 *SOFTWARE.

 *
 * @file
 * @ingroup Extensions
 * @link https://www.mediawiki.org/wiki/Extension:jehovahsays Documentation
 *
 * @author Morgan Shatee Byers <morgansbyers@gmail.com>
 * @license https://opensource.org/licenses/MIT The MIT License (MIT)
 */

$wgExtensionCredits ['parser'][] = array(
        'path' => __FILE__,
        'name' => "jehovahsays",
        'description' => "Allows Custom HTTP Security Headers to be added to the wiki as configured in the LocalSettings.php file.",
        'version' => 1.1,
        'author' => "Morgan Shatee Byers",
		'license-name' => 'MIT',
        'url' => "http://www.mediawiki.org/wiki/Extension:jehovahsays",
);
//Explicitly defining global variables
$wgSecurity = '<!-- No Security -->';
$wgBottomSecurity = '<!-- No Bottom Security -->';
//Code for adding the top and bottom banners to the wiki
$wgHooks['BeforePageDisplay'][] = 'jehovahsays';
function jehovahsays( OutputPage &$out, Skin &$skin ) {
	global $wgSecurity;
	global $wgBottomSecurity;
	$out->prependHTML( $wgSecurity );
	$out->addHTML( $wgBottomSecurity );
	return TRUE;
}