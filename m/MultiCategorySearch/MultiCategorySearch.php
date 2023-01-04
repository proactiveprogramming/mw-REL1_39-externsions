<?php
/**
* Multi-Category Search 1.69
* This MediaWiki extension represents a [[Special:MultiCategorySearch|special page]],
* 	that allows to find pages, included in several specified categories at once.
* Extension setup file.
* Requires MediaWiki 1.8 or higher and MySQL 4.1 or higher.
* Extension's home page: http://www.mediawiki.org/wiki/Extension:Multi-Category_Search
*
* Copyright (c) Moscow, 2008-2017, Iaroslav Vassiliev  <codedriller@gmail.com>
* Distributed under GNU General Public License 2.0 or later (http://www.gnu.org/copyleft/gpl.html)
*/

if ( !defined('MEDIAWIKI') ) {
	echo <<<'EOT'
To install this extension, put the following line in LocalSettings.php:
require_once( "$IP/extensions/MultiCategorySearch/MultiCategorySearch.php" );
EOT;
	exit( 1 );
}

$wgExtensionCredits['specialpage'][] = array(
	'name' => 'Multi-Category Search',
	'version' => 1.69,
	'author' => '[mailto:codedriller@gmail.com Iaroslav Vassiliev]',
	'description' => 'Introduces a special page [[Special:MultiCategorySearch]], that allows ' .
		'users to find pages, which are included in several specified categories at once.',
	'url' => 'http://www.mediawiki.org/wiki/Extension:Multi-Category_Search'
);

$wgExtensionFunctions[] = 'wfSetupMultiCategorySearchExtension';

$dir = dirname(__FILE__) . '/';
$wgAutoloadClasses['MultiCategorySearch'] = $dir . 'MultiCategorySearch_body.php';
$wgExtensionMessagesFiles['MultiCategorySearch'] = $dir . 'MultiCategorySearch.i18n.php';
//$wgExtensionAliasesFiles['MultiCategorySearch'] = $dir . 'MultiCategorySearch.alias.php';
$wgSpecialPages['MultiCategorySearch'] = 'MultiCategorySearch';

function wfSetupMultiCategorySearchExtension() {
	$title = Title::newFromText( 'MultiCategorySearch' );
	return true;
}
?>