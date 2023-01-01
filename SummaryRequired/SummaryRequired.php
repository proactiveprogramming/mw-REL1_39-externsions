<?php
/*--------------------------------------------
Solomon Kinard's Extension for MediaWiki
Force summary when saving
Date Created: 2011/03/23
Date Updated: 2011/10/04
Contact: http://solomonkinard.com
Demo: http://www.solomonkinard.com
Download: http://www.solomonkinard.com
--------------------------------------------*/

# Not a valid entry point, skip unless MEDIAWIKI is defined
if ( !defined( 'MEDIAWIKI' ) ) {
        echo 'Not a valid entry point.';
        exit( 1 );
}

$wgExtensionCredits['specialpage'][] = array(
    'path' => __FILE__,
    'name' => 'SummaryRequired',
    'version' => '0.3',
    'author' => array('Solomon Kinard'),
    'url' => 'http://www.solomonkinard.com',
    'description' => 'Require summary when editing a page',
    'descriptionmsg' => 'Require summary when editing a page',
);

##### main extension ####
$dir = dirname( __FILE__ ) . '/';
$wgAutoloadClasses['SummaryRequired'] = $dir . 'SummaryRequired_body.php';
$wgExtensionMessagesFiles['SummaryRequired'] = $dir . 'SummaryRequired.i18n.php';
$wgExtensionAliasesFiles['SummaryRequired'] = $dir . 'SummaryRequired.alias.php';

##### load hooks #####
$wgHooks['EditPage::showEditForm:initial'][] = 'SummaryRequired::fnMyHook';

$srChangeLog = array('0.2' => 'Stable entry',
					'0.3' => 'Fixed strict static call errors.');