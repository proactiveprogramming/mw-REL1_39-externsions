<?php
/**
  * File: MOCA_Initialize.php
  *
  * Description: This file is the one necessary to get everything started and define
			some of the main functionallity of this extension. You need to
			include this file in the LocalSetting.php file in the installation
			directory of the wiki system.
  *
  * @author Chrysovalanto Kousetti
  * @email valanto@gmail.com
  *
  */

// Security check: checks mediawiki exists
if( !defined( 'MEDIAWIKI' ) ) {
	echo( "This file is an extension to the MediaWiki software and cannot be used standalone.\n" );
	die( 1 );
}

// Extension Credits
$wgExtensionCredits['other'][] = array( 
	'name' => 'MOCA', 
	'version' => '1.0.2',
	'author' => 'Chrysovalanto Kousetti',
	'url'=>'http://sourceforge.net/projects/moca/',
	'description'=>'MOCA: Mediawiki Ontology Convergence Assistant - An ontology convergence tool to promote the use of the Semantic Mediawiki extension. This has been developed in the department of ECS at the University of Southampton for the FREMA project of the LSL Group. Supervisor of the project is Dr David Millard.'
);



// Start GLOBALS DEFINITION
// Path globals
$egSMWIExtensionPath = 'extensions/MOCA';
$egSMWISourcePath = 'includes';
$egSMWIScriptPath = 'scripts';
$egSMWISkinsPath = 'skins';
$egSMWIImagePath = $egSMWISkinsPath.'/images';

// Percent globals. Between 0 and 1
$egSMWICategoryAcceptableLimit_relations = 0.2;
$egSMWICategoryAcceptableLimit_properties = 0.2;
$egSMWIRelationAcceptableLimit_categories = 0.2;
$egSMWIRelationAcceptableLimit_dpages = 0.2;
$egSMWIPropertyAcceptableLimit_categories = 0.2;


// Scripts' names globals
$egSMWIIEOnLoadScriptFileName = 'ie_load.js';
$egSMWIGlobalCSSStyleSheetName = 'MOCA_Global_Style.css';
$egSMWIToolbarCSSStyleSheetName = 'MOCA_Toolbar_Style.css';
$egSMWIQuickFixPanelCSSStyleSheetName = 'MOCA_QuickFixPanel_Style.css';
$egSMWIHelpCSSStyleSheetName = 'MOCA_Help_Style.css';
$egSMWIGeneralScriptsFileName = 'MOCA_General_Functions.js';
$egSMWIQuickFixPanelRelatedScriptsFileName = 'MOCA_QuickFixPanel_Functions.js';
$egSMWIToolbarRelatedScriptsFileName = 'MOCA_Toolbar_Functions.js';
$egSMWIHelpRelatedScriptsFileName = 'MOCA_Help_Functions.js';


// Image Names globals
$egSMWIButtonAddCategoryImageName = 'button_add_category.png';
$egSMWIButtonAddRelationImageName = 'button_add_relation.png';
$egSMWIButtonAddPropertyImageName = 'button_add_property.png';
$egSMWIButtonAddCategorySmallImageName = 'button_add_category_small.png';
$egSMWIButtonAddRelationSmallImageName = 'button_add_relation_small.png';
$egSMWIButtonAddPropertySmallImageName = 'button_add_property_small.png';
$egSMWIButtonAddAskSmallImageName = 'addask_button_small.png';
$egSMWIAjaxIsLoadingTheBoxSuggestionsImageName = 'suggestion_box_is_loading.gif';
$egSMWICheckImageName = 'check_small.gif';
$egSMWIWarnImageName = 'warn_small.gif';
$egSMWICheckLargeImageName = 'check_large.gif';
$egSMWIWarnLargeImageName = 'warn_large.gif';
$egSMWIPlusImageName = 'plus.gif';
$egSMWIMinusImageName = 'minus.gif';
$egSMWIArrowImageName = 'arrow.gif';
$egSMWIAjaxIsLoadingImageName = 'ajax_is_loading_boxes.gif';
$egSMWIAjaxIsLoadingQuickFixPanel = 'ajax_is_loading_quick_fix_panel.gif';
$egSMWISemanticsQuickFixPanelHead = 'semantic_quick_fix_panel.gif';
$egSMWIQFPTopLeftCorner = 'gfp_top_left.gif';
$egSMWIQFPTopRightCorner = 'gfp_top_right.gif';
$egSMWIQFPBottomLeftCorner = 'gfp_bottom_left.gif';
$egSMWIQFPBottomRightCorner = 'gfp_bottom_right.gif';
$egSMWIQFPHorizontalPattern = 'gfp_hor_pattern.gif';
$egSMWIQFPVerticalPattern = 'gfp_ver_pattern.gif';
$egSMWINextRelationSetButton = 'button_next_relation_set.gif';
$egSMWIPreviousRelationSetButton = 'button_previous_relation_set.gif';
$egSMWISemanticWizardImageName = 'semantic_wizard.gif';

// MediaWiki element IDs globals
$egSMWIEditorTextboxID = 'wpTextbox1';
$egSMWIMediaWikiEditorID = 'editpage-copywarn';
$egSMWIMediaWikiToolbarID = 'toolbar';
$egSMWIMediaWikiPreviewID = 'wikiPreview';


// Div/ID globals
$egSMWISuggestionDiv = "smwi_suggestion_div";
$egSMWIButtonDiv = "smwi_button_div";
$egSMWIAddNewSemanticElementDiv = "smwi_add_new_semantic_element_div";
$egSMWIQuickFixPanelDiv = "smwi_QuickFixPanelDiv";
$egSMWIQuickFixPanelMainDiv = "smwi_QuickFixPanelMainDiv";
$egSMWIShowPlainLinksToFixDiv = "smwi_ShowPlainLinksToFixDiv";
$egSMWIShowPlainLinksTitleDiv = "smwi_ShowPlainLinksToFixTitleDiv";
$egSMWIAjaxIsLoadingTheBoxSuggestions = 'smwi_ajax_is_loading_the_box_suggestions';


// Constants
$egSMWIWikiTextCookieName = 'smwi_cookie_wikitext';
$egSMWIWikiLinkInfoCookieName = 'smwi_cookie_link_info';
$egSMWIMaxCookieSize = 3000;
$egSMWICompactRelationsResults = true;
$egSMWIMaxRelationsPerResultPage = 5;

// Other globals
$egSMWIText = 'MOCA_Text.php';
$egSMWIHelpPopupFileName = 'Help_Popup.php';
// ATTEMPT START: to fix characters problem
$wgSpecialChars = '‘,&lsquo;|’,&rsquo;|‚,&sbquo;|“,&ldquo;|”,&rdquo;|„,&bdquo;|†,&dagger;|‡,&Dagger;|‰,&permil;|‹,&lsaquo;|›,&rsaquo;';
// ATTEMPT END: to fix characters problem
// End GLOBALS DEFINITION

// Include the main class of the SMWI extension
$wgExtensionFunctions[] = "smwifMain";

// Load extension related classes
$wgAutoloadClasses['MOCA_StartupFunctions'] = $egSMWIExtensionPath . '/' . $egSMWISourcePath . '/MOCA_Startup_Functions.php';
$wgAutoloadClasses['MOCA_SearchFunctions'] = $egSMWIExtensionPath . '/' . $egSMWISourcePath . '/MOCA_Search_Functions.php';
$wgAutoloadClasses['MOCA_Toolbar'] = $egSMWIExtensionPath . '/' . $egSMWISourcePath . '/MOCA_Toolbar.php';
$wgAutoloadClasses['MOCA_QuickFixPanel'] = $egSMWIExtensionPath . '/' . $egSMWISourcePath . '/MOCA_QuickFixPanel.php';
$wgAutoloadClasses['MOCA_GlobalBits'] = $egSMWIExtensionPath . '/' . $egSMWISourcePath . '/MOCA_Global_Bits.php';

// Register hooks	
$wgHooks['AlternateEdit'][] = 'smwifAlternateEdit';		// React to the event of selecting to edit a new or existing wiki page.

// Start REGISTER AJAX FUNCTIONS
// Page loading ajax functions
$wgAjaxExportList[] = 'smwifWikiTextboxEditAjaxWrapper';

// Top toolbar functions
$wgAjaxExportList[] = 'smwifAddCategoryButtonPressedWrapper';
$wgAjaxExportList[] = 'smwifAddCategoryButtonCompletedWrapper';
$wgAjaxExportList[] = 'smwifAddRelationButtonPressedWrapper';
$wgAjaxExportList[] = 'smwifAddRelationButtonCompletedWrapper';
$wgAjaxExportList[] = 'smwifGetTheDPageSugForRelationBoxWrapper';
$wgAjaxExportList[] = 'smwifGetTheRelationSugForDPageBoxWrapper';
$wgAjaxExportList[] = 'smwifAddPropertyButtonPressedWrapper';
$wgAjaxExportList[] = 'smwifAddPropertyButtonCompletedWrapper';
$wgAjaxExportList[] = 'smwifAddPropertyGetValueWrapper';
$wgAjaxExportList[] = 'smwifAddRelationGetValueWrapper';

// QuickFixPanel functions
$wgAjaxExportList[] = 'smwifFixCategoryIssueWrapper';
$wgAjaxExportList[] = 'smwifFixRelationIssueWrapper';
$wgAjaxExportList[] = 'smwifFixPropertyIssueWrapper';
$wgAjaxExportList[] = 'smwifShowPlainLinkToFixWrapper';
// End REGISTER AJAX FUNCTIONS


// Inlcude text file
$egMOCA_txt = array();
include $egSMWIText;


/**
  * The main class of the extension that checks if Ajax is enabled
  * It does not actually return anything but stops the use of this extension
  * if Ajax is not enable (enabled by the $wgUseAjax global)
  *
  * @return bool
  */
function smwifMain() {
	global $wgUseAjax, $smwgScriptPath;

	// Abort if AJAX is not enabled
	if ( !$wgUseAjax ) {
		wfDebug( 'MOCA error: $wgUseAjax is not enabled. Please enable ajax by setting $wgUseAjax=true in the LocalSettings.php file in your wiki root directory.' );
		return;
	}
	
	// Abort if SMW is not installed
	if ( !$smwgScriptPath ) {
		wfDebug( 'MOCA error: This extension requires the semantic mediawiki extension to be installed. To get the semantic mediawiki extension visit: http://meta.wikimedia.org/wiki/Semantic_MediaWiki' );
		return;
	}
}


/**
   * This function is called when the AlternateEdit event
   * has occured. The AlternateEdit event occurs when a user
   * is editing a new or existing wiki page.
   *
   * Once the event occurs and smwifAlternateEdit is called
   * the method calls the static method in MOCA_Toolbar and MOCA_QuickFixPanel
   * and sets some basic attibutes to the edit page in order to
   * make this extension usable.
   *
   * @return bool
   */
function smwifAlternateEdit( &$editpage ) {
	global $wgOut;
	MOCA_StartupFunctions::setHeaders( $wgOut );
	
	return true;
}

/**
  * This method is registered as an Ajax method and has the ability to be called
  * from the javascript as the editor in the wiki's edit page is being rendered
  * This method then call the appropriate functions in order to acquire the appropriate
  * response to return to the edit page.
  *
  * @return AjaxResponse
  */
function smwifWikiTextboxEditAjaxWrapper(  ) {
	$quickfixpanel_instance = new MOCA_QuickFixPanel;
	$response = $quickfixpanel_instance->getAjaxQuickFixPanel();
	return $response;
}

/**
  * This method is registered as an Ajax method and once the user selects the 
  * category tab from toolbar it loads all the appropriate panel and information
  *
  * @return AjaxResponse
  */
function smwifAddCategoryButtonPressedWrapper( $pos, $category ) {
	$toolbar_instance = new MOCA_Toolbar;
	$response = $toolbar_instance->getAjaxCategoryBox($pos, $category);
	return $response;
	
}

/**
  * This method is registered as an Ajax method and once the requested category related operation
  * is completed, it informs the user of the result of the requested operation
  *
  * @return AjaxResponse
  */
function smwifAddCategoryButtonCompletedWrapper ( $message, $type ) {
	$toolbar_instance = new MOCA_Toolbar;
	$response = $toolbar_instance->getAjaxCategoryBoxCompletion( $message, $type );
	return $response;
}

/**
  * This method is registered as an Ajax method and once the user selects the 
  * relation tab from toolbar it loads all the appropriate panel and information
  *
  * @return AjaxResponse
  */
function smwifAddRelationButtonPressedWrapper ( $pos, $link ) {
	$toolbar_instance = new MOCA_Toolbar;
	$response = $toolbar_instance->getAjaxRelationBox($pos, $link);
	return $response;	
}

/**
  * This method is registered as an Ajax method and once the requested relation related operation
  * is completed, it informs the user of the result of the requested operation
  *
  * @return AjaxResponse
  */
function smwifAddRelationButtonCompletedWrapper ( $message, $type ) {
	$toolbar_instance = new MOCA_Toolbar;
	$response = $toolbar_instance->getAjaxRelationBoxCompletion( $message, $type );
	return $response;
}


  
/**
  * This method is registered as an Ajax method and once the user selects the 
  * property tab from toolbar it loads all the appropriate panel and information
  *
  * @return AjaxResponse
  */
function smwifAddPropertyButtonPressedWrapper ( $pos, $property_value ) {
	$toolbar_instance = new MOCA_Toolbar;
	$response = $toolbar_instance->getAjaxPropertyBox($pos, $property_value);
	return $response;	
}

/**
  * This method is registered as an Ajax method and once the requested property related operation
  * is completed, it informs the user of the result of the requested operation
  *
  * @return AjaxResponse
  */
function smwifAddPropertyButtonCompletedWrapper ( $message, $type ) {
	$toolbar_instance = new MOCA_Toolbar;
	$response = $toolbar_instance->getAjaxPropertyBoxCompletion( $message, $type );
	return $response;
}

/**
  * This method is registered as an Ajax method and once the user selects to
  * fix a category related issue it loads the appropriate panel and information.
  *
  * @return AjaxResponse
  */
function smwifFixCategoryIssueWrapper ( $issue_code ) {
	$quickfixpanel_instance = new MOCA_QuickFixPanel;
	$response = $quickfixpanel_instance->getCategoryIssueQuickFixPanel($issue_code);
	return $response;
}

/**
  * This method is registered as an Ajax method and once the user selects to
  * fix a relation related issue it loads the appropriate panel and information.
  *
  * @return AjaxResponse
  */
function smwifFixRelationIssueWrapper( $issue_code, $link, $pos, $len, $alt, $type ) {
	
	$quickfixpanel_instance = new MOCA_QuickFixPanel;
	$response = $quickfixpanel_instance->getRelationIssueQuickFixPanel( $issue_code, $link, $pos, $len, $alt, $type );
	return $response;
}

/**
  * This method is registered as an Ajax method and once the user selects to
  * fix a property related issue it loads the appropriate panel and information.
  *
  * @return AjaxResponse
  */
function smwifFixPropertyIssueWrapper ( $issue_code ) {
	$quickfixpanel_instance = new MOCA_QuickFixPanel;
	$response = $quickfixpanel_instance->getPropertyIssueQuickFixPanel( $issue_code );
	return $response;
}

/**
  * This method is registered as an Ajax method and once the user selects to
  * view all the plain links and relations it loads the appropriate list of them and
  * available actions.
  *
  * @return AjaxResponse
  */
function smwifShowPlainLinkToFixWrapper ( $offset ) {
	$quickfixpanel_instance = new MOCA_QuickFixPanel;
	$response = $quickfixpanel_instance->getPlainLinkToFix( $offset );
	return $response;
}

/**
  * This method is registered as an Ajax method and once the user selects a property from
  * the suggested list of properties it loads the appropriate suggestion list of property values.
  *
  * @return AjaxResponse
  */
function smwifAddPropertyGetValueWrapper ($property, $property_textbox) {
	$toolbar_instance = new MOCA_Toolbar;
	$response = $toolbar_instance->getAjaxPropertyGetValue( $property, $property_textbox );
	return $response;
}

/**
  * This method is registered as an Ajax method and once the user selects a relation type from
  * the suggested list of relation types it loads the appropriate suggestion list of destination pages.
  *
  * @return AjaxResponse
  */
function smwifAddRelationGetValueWrapper($relation, $relation_textbox){
	$toolbar_instance = new MOCA_Toolbar;
	$response = $toolbar_instance->getAjaxRelationGetValue( $relation, $relation_textbox );
	return $response;
}
?>