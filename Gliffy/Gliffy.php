<?php
$wgExtensionCredits['parserhook'][] = array(
  'name'         => 'Gliffy Public',
  'version'      => '1.0',
  'author'       => 'chazbot7', 
  'url'          => 'http://www.mediawiki.org/wiki/Extension:Gliffy_Public',
  'description'  => 'Render public Gliffy diagrams'
);

// This extensions was completely based on Nick Townsend's "Gliffy" extenion on GitHub.
// Link: https://github.com/townsen/gliffy
// I made edits and reuploaded because the project was not being updated on GitHub and no longer worked.
 
if ( defined( 'MW_SUPPORTS_PARSERFIRSTCALLINIT' ) ) {
  $wgHooks['ParserFirstCallInit'][] = 'gliffySetup';
} else {
  $wgExtensionFunctions[] = 'gliffySetup';
}
 
function gliffySetup() {
  global $wgParser;
  $wgParser->setHook( 'gliffy', 'gliffyRender' );
  return true;
}
 
function gliffyRender( $input, $args, $parser) {
  $parser->disableCache();

  if( isset( $args['did'] ) ) {
    $did= $args['did'];
    $html = <<<HTML
<script src="/extensions/Gliffy/embedGliffy.js" type="text/javascript"></script>
<script type="text/javascript"> embedGliffy('$did'); </script>
<br>
HTML;
  }
  else
    $html = "<b>Gliffy drawing ID (did) not supplied</b>";

  return array( $html, "markerType" => 'nowiki' );
 
}
// vim: set ts=8 sw=2 sts=2:
?>
