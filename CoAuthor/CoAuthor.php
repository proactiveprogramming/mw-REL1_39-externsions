<?php
 /*
  * Copyright (c) 2010 University of Macau
  *
  * Licensed under the Educational Community License, Version 2.0 (the "License");
  * you may not use this file except in compliance with the License. You may
  * obtain a copy of the License at
  *
  * http://www.osedu.org/licenses/ECL-2.0
  *
  * Unless required by applicable law or agreed to in writing,
  * software distributed under the License is distributed on an "AS IS"
  * BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express
  * or implied. See the License for the specific language governing
  * permissions and limitations under the License.
  */

if( !defined( 'MEDIAWIKI' ) ) {
  echo <<<EOT
To install CoAuthor extension, put the following line in LocalSettings.php:
require_once( "\$IP/extensions/CoAuthor/CoAuthor.php" );
EOT;

  exit( 1 );
}

$wgExtensionCredits['specialpage'][] = 
	array( 'name' => 'CoAuthor',
		'description' => 'Calculates and displays degree of coauthorship for pages',
		'version' => 1.0,
		'author' => 'University of Macau (Libby Tang, Robert P. Biuk-Aghai, José Lopes)',
		'url' => 'http://www.mediawiki.org/wiki/Extension:CoAuthor'
		);

$dir = dirname(__FILE__) . '/';

$wgAutoloadClasses['CoAuthor'] = $dir . 'CoAuthor.body.php';
$wgExtensionMessagesFiles['CoAuthor'] = $dir . 'CoAuthor.i18n.php';
$wgSpecialPages['CoAuthor'] = 'CoAuthor';

$wgHooks['LanguageGetSpecialPageAliases'][] = 'caLanguageGetSpecialPageAliasesHook';
$wgHooks['PageHistoryLineEnding'][] = 'caPageHistoryLineEndingHook';
$wgHooks['SkinTemplateBuildNavUrlsNav_urlsAfterPermalink'][] = 'caSpecialCoAuthorNav'; 
$wgHooks['SkinTemplateToolboxEnd'][] = 'caSpecialCoAuthorToolbox'; 

/**
 * Add the localized special page title. 
 */
function caLanguageGetSpecialPageAliasesHook(&$specialPageArray, $code) {
  wfLoadExtensionMessages( 'CoAuthor' );

  $title = Title::newFromText( wfMsg( 'coauthor-url' ) );
  $specialPageArray['CoAuthor'][] = $title->getDBKey();
      
  return true;
}

/**
 * Add a link to co-authors special page in revision history page.
 */
function caPageHistoryLineEndingHook($history, &$row, &$s, &$classes) {
  global $wgUser;

  $revision = new Revision( $row );
  $userLink = $wgUser->getSkin()->makeLinkObj( SpecialPage::getTitleFor( 'CoAuthor', $revision->getUserText() ), wfMsgHtml( 'coauthor-label' ) );

  // TODO: new link addition method should be platform neutral.
  $rs = str_replace( 'contribs</a>', "contribs</a> | $userLink", $s );

  if ($rs == $s) {
    $userToolPos = strpos( $s, '<span class="mw-usertoollinks">' );
    $parenToolPos = strpos( $s, ')</span>', $userToolPos );
    $rs = substr_replace( $s, ' | ' . $userLink . ')', $parenToolPos, 1 );
  }

  if ($rs == $s) {
    $rs .= ' (' . $userLink . ')';
  }

  $s = $rs;
  
  return true;
}

function caSpecialCoAuthorNav( &$skintemplate, &$nav_urls, &$oldid, &$revid ) {
	if ( $skintemplate->getTitle()->getNamespace() === NS_USER && $revid !== 0 && !empty( $revid ) )
		$nav_urls['coauthor'] = array(
			'args'   => "userText=" . $skintemplate->getTitle()->getDBkey()
		);

	return true;
}

/**
 * add the cite link to the toolbar
 */
function caSpecialCoAuthorToolbox( &$skin ) {
global $wgUser;

	if ( isset( $skin->data['nav_urls']['coauthor'] ) ) {
		echo Html::rawElement(
			'li',
			array( 'id' => 't-coauthor' ),
			$skin->skin->link(
				SpecialPage::getTitleFor( 'CoAuthor' ),
				ucfirst( wfMsg( 'coauthor-label' ) ),
				$wgUser->getSkin()->tooltipAndAccesskeyAttribs( 'coauthor' ),
				$skin->data['nav_urls']['coauthor']['args']
			)
		);
	}

	return true;
}

