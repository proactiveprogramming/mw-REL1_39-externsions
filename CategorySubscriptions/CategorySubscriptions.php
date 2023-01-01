<?php

#Alert the user that this is not a valid entry point to MediaWiki if they try to access the skin file directly.
if (!defined('MEDIAWIKI')) {
        echo <<<EOT
To install my extension, put the following line in LocalSettings.php:
require_once( "$IP/extensions/CategorySubscriptions/CategorySubscriptions.php" );

Then run the install script found in the maintenance folder.  The script has to be copied to main maintenance folder.
EOT;
        exit( 1 );
}

$dir = dirname(__FILE__) . '/';
 
$wgAutoloadClasses['CategorySubscriptions'] = $dir.'CategorySubscriptions_body.php'; # Tell MediaWiki to load the extension body.
$wgExtensionMessagesFiles['CategorySubscriptions'] = $dir . 'CategorySubscriptions.i18n.php';
$wgSpecialPages['CategorySubscriptions'] = 'CategorySubscriptions'; # Let MediaWiki know about your new special page.
$wgHooks['LanguageGetSpecialPageAliases'][] = 'CategorySubscriptionsLocalizedPageName'; # Add any aliases for the special page.

# hook into toolbox for link to add category subscriptions
$wgHooks['SkinTemplateBuildNavUrlsNav_urlsAfterPermalink'][] = 'wfSpecialCategorySubscriptionsNav';
$wgHooks['MonoBookTemplateToolboxEnd'][] = 'wfSpecialCategorySubscriptionsToolbox'; 

function CategorySubscriptionsLocalizedPageName(&$specialPageArray, $code) {
  # The localized title of the special page is among the messages of the extension:
  wfLoadExtensionMessages('CategorySubscriptions');
 
 
  # Convert from title in text form to DBKey and put it into the alias array:
  $title = Title::newFromText('CategorySubscriptions');
  $specialPageArray['CategorySubscriptions'][] = $title->getDBKey();
  
  return true;
}


function wfSpecialCategorySubscriptionsNav( &$skintemplate, &$nav_urls ) {

    $nav_urls['categorysubscriptions'] = array(
		'text' => wfMsg( 'categorysubscriptions_print_link' ),
		'href' => $skintemplate->makeSpecialUrl( 'CategorySubscriptions/' . wfUrlencode("{$skintemplate->thispage}") )
	);
	return true;

}
 

function wfSpecialCategorySubscriptionsToolbox( &$monobook ) {

    if ( isset( $monobook->data['nav_urls']['categorysubscriptions'] ) )                
            if ( $monobook->data['nav_urls']['categorysubscriptions']['href'] == '' ) {
                    ?><li id="t-ispdf"><?php echo $monobook->msg( 'categorysubscriptions_print_link' ); ?></li><?php
            } else {
                    ?><li id="t-pdf"><?php
                            ?><a href="<?php echo htmlspecialchars( $monobook->data['nav_urls']['categorysubscriptions']['href'] ) ?>"><?php
                                    echo $monobook->msg( 'categorysubscriptions_print_link' );
                            ?></a><?php
                    ?></li><?php
            }

    return true;
}