<?php
/**
 * VectorUp is a Vector skin that adds CSS and JavaScript to style MediaWiki and further extensions more modern and consistently
 *
 * Currently "upgraded":
 * * MediaWiki (Buttons, Typography)
 * * SMW (Buttons)
 * * SemanticForms (Form / Input Styles)
 * * HeaderTabs
 * *
 *
 * For more info see http://mediawiki.org/wiki/Extension:VectorUp
 *
 * @file
 * @ingroup Extensions
 * @package MediaWiki
 *
 * @links https://github.com/Fannon/VectorUp/blob/master/README.md Documentation
 * @links https://www.mediawiki.org/wiki/Extension_talk:VectorUp Support
 * @links https://github.com/Fannon/VectorUp/issues Bug tracker
 * @links https://github.com/Fannon/VectorUp Source code
 *
 * @author Simon Heimler (Fannon), 2015
 * @license http://opensource.org/licenses/mit-license.php The MIT License (MIT)
 */

//////////////////////////////////////////
// VARIABLES                            //
//////////////////////////////////////////


//////////////////////////////////////////
// CONFIGURATION                        //
//////////////////////////////////////////


//////////////////////////////////////////
// CREDITS                              //
//////////////////////////////////////////

$wgExtensionCredits['other'][] = array(
   'path'           => __FILE__,
   'name'           => 'VectorUp',
   'author'         => array('Simon Heimler'),
   'version'        => '0.0.1',
   'url'            => 'https://www.mediawiki.org/wiki/Extension:VectorUp',
   'descriptionmsg' => 'vectorup-desc',
   'license-name'   => 'MIT'
);


//////////////////////////////////////////
// RESOURCE LOADER                      //
//////////////////////////////////////////

$wgResourceModules['ext.VectorUp'] = array(
   'scripts' => array(
      'lib/VectorUp.js',
   ),
   'styles' => array(
   ),
   'dependencies' => array(
   ),
   'localBasePath' => __DIR__,
   'remoteExtPath' => 'VectorUp',
);

// Register hooks
$wgHooks['BeforePageDisplay'][] = 'VectorUpOnBeforePageDisplay';

/**
* Add libraries to resource loader
*/
function VectorUpOnBeforePageDisplay( OutputPage &$out, Skin &$skin ) {
  // Add as ResourceLoader Module
  $out->addModules('ext.VectorUp');
  return true;
}