  <?php
  /**
  * File: MOCA_Startup_Functions.php
  *
  * Description: This file contains the MOCA_StartupFunctions class which
  * 			contains methods that are called once the page has loaded
  *			in order to display the various parts of the smwi extension.
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

/**
   * Class: MOCA_StartupFunctions
   *
   * Description: This class contains methods that are called once the page has loaded
   *			in order to display the various parts of the smwi extension.
   */
class MOCA_StartupFunctions{
  
	/**
	  * This method is static and is called on load of the page in order to perform the
	  * necessary operations and initialisations for the extension to work.
	  *
	  * @return boolean
	  */
	static function setHeaders( &$outputPage ) {
		global $egSMWIExtensionPath, $egSMWISkinsPath, $wgScriptPath, $egSMWIScriptPath, $egSMWIExtensionPath, 
			$egSMWIImagePath, $egSMWIWysiwygMainPath,
			$egSMWIGlobalCSSStyleSheetName, $egSMWIToolbarCSSStyleSheetName, $egSMWIQuickFixPanelCSSStyleSheetName, $egSMWIHelpCSSStyleSheetName,
			$egSMWIIEOnLoadScriptFileName, $egSMWIWysiwygMainFileName, 
			$egSMWIGeneralScriptsFileName, $egSMWIQuickFixPanelRelatedScriptsFileName, $egSMWIToolbarRelatedScriptsFileName, $egSMWIHelpRelatedScriptsFileName,
			$egSMWIWikiTextCookieName, $egSMWIButtonAddCategoryImageName,$egSMWIButtonAddRelationImageName, $egSMWIButtonAddPropertyImageName,
			$egSMWIAjaxIsLoadingTheBoxSuggestionsImageName, $egSMWIAjaxIsLoadingImageName, $egSMWIAjaxIsLoadingQuickFixPanel,
			$egSMWIButtonAddCategorySmallImageName, $egSMWIButtonAddRelationSmallImageName, $egSMWIButtonAddPropertySmallImageName, $egSMWIButtonAddAskSmallImageName,
			$egSMWISemanticWizardImageName,
			$egSMWIEditorTextboxID,
			$egSMWISuggestionDiv, $egSMWIButtonDiv, $egSMWIAddNewSemanticElementDiv, $egSMWIQuickFixPanelMainDiv, $egSMWIQuickFixPanelDiv,
			$egSMWIQuickFixCategoryPanelDiv, $egSMWIQuickFixRelationPanelDiv, $egSMWIQuickFixPropertyPanelDiv, $egSMWIShowPlainLinksTitleDiv,
			$egSMWIMediaWikiEditorID, $egSMWIMediaWikiToolbarID,
			$egSMWICategoryRelatedBorder, $egSMWICategoryRelatedBackground,
			$egSMWIHelpWindow, $egSMWIMinusImageName, $egSMWIPlusImageName,
			$egSMWISourcePath, $egSMWIHelpPopupFileName,
			$egMOCA_txt, $egSMWISemanticsQuickFixPanelHead, $egSMWIQFPTopLeftCorner, $egSMWIQFPTopRightCorner, $egSMWIQFPHorizontalPattern,
			$egSMWIMediaWikiPreviewID, $egSMWIMaxCookieSize,
			// ATTEMPT START: to fix characters problem
			$wgSpecialChars
			// ATTEMPT END: to fix characters problem
			;
			
			
		// include the stylesheets used by the extension
		$outputPage->addLink(array(
			'rel'   => 'stylesheet',
			'type'  => 'text/css',
			'media' => 'screen, projection',
			'href'  => $egSMWIExtensionPath . '/' . $egSMWISkinsPath . '/' . $egSMWIGlobalCSSStyleSheetName
		));
		$outputPage->addLink(array(
			'rel'   => 'stylesheet',
			'type'  => 'text/css',
			'media' => 'screen, projection',
			'href'  => $egSMWIExtensionPath . '/' . $egSMWISkinsPath . '/' . $egSMWIToolbarCSSStyleSheetName
		));
		$outputPage->addLink(array(
			'rel'   => 'stylesheet',
			'type'  => 'text/css',
			'media' => 'screen, projection',
			'href'  => $egSMWIExtensionPath . '/' . $egSMWISkinsPath . '/' . $egSMWIQuickFixPanelCSSStyleSheetName
		));
		$outputPage->addLink(array(
			'rel'   => 'stylesheet',
			'type'  => 'text/css',
			'media' => 'screen, projection',
			'href'  => $egSMWIExtensionPath . '/' . $egSMWISkinsPath . '/' . $egSMWIHelpCSSStyleSheetName
		));
		
		// adds a line for warning the user that the extension will not be displayed in case that their javascript is disabled.
		$outputPage->addHTML('
			<noscript>
				<p class = "smwi_no_script_tag">In order to be able to view suggestions and information about the wiki page you are editing please <b style="color:black">enable javascript</b> on your browser. Once you have enabled javascript, simply refresh this page.</p>
			</noscript>
		');
		
		// sets the global javascript variables
		$outputPage->addScript("
		<script type=\"{$wgJsMimeType}\">
			var timeout;
			var popup_window;
			var timeout_waiting_time_editing = 5000;
			var textboxTag = '{$egSMWIEditorTextboxID}';
			var quickfixpanelmainDiv = '{$egSMWIQuickFixPanelMainDiv}';
			var buttonDiv = '{$egSMWIButtonDiv}';
			var addNewSemanticElementDiv = '{$egSMWIAddNewSemanticElementDiv}';
			var categoryButtonName = '{$egSMWICategoryButtonName}';
			var ajaxIsLoadingTheBoxSuggestions = '{$egSMWIAjaxIsLoadingTheBoxSuggestions}';
			var max_cookie_size = '{$egSMWIMaxCookieSize}';
			var smwi_cookie_textarea_name = '{$egSMWIWikiTextCookieName}';
			var smwi_ajax_is_loading_image_path = '{$wgScriptPath}/{$egSMWIExtensionPath}/{$egSMWIImagePath}/{$egSMWIAjaxIsLoadingTheBoxSuggestionsImageName}';
			var smwi_ajax_is_loading_small_image_path = '{$wgScriptPath}/{$egSMWIExtensionPath}/{$egSMWIImagePath}/{$egSMWIAjaxIsLoadingImageName}';
			var smwi_ajax_is_loading_qfp_image_path = '{$wgScriptPath}/{$egSMWIExtensionPath}/{$egSMWIImagePath}/{$egSMWIAjaxIsLoadingQuickFixPanel}';
			var smwi_show_plain_links_fix_title_div = '{$egSMWIShowPlainLinksTitleDiv}';
			var smwi_minus_image = '{$wgScriptPath}/{$egSMWIExtensionPath}/{$egSMWIImagePath}/{$egSMWIMinusImageName}';
			var smwi_plus_image = '{$wgScriptPath}/{$egSMWIExtensionPath}/{$egSMWIImagePath}/{$egSMWIPlusImageName}';
			var smwi_help_popup = '{$wgScriptPath}/{$egSMWIExtensionPath}/{$egSMWIScriptPath}/{$egSMWIHelpPopupFileName}'
			// ATTEMPT START: to fix characters problem
			var smwi_special_chars_array = '{$wgSpecialChars}'
			// ATTEMPT END: to fix characters problem
		</script>
		");
		
		// Include the init() function in order to make the call to the javascript file and change the editor textbox event properties
		// includes the initial look of the extension
		$outputPage->addScript(
			"
			<script type=\"{$wgJsMimeType}\">
			function init() { 
				// quit if this function has already been called
				if (arguments.callee.done) return;

				// flag this function so we don't do the same thing twice
				arguments.callee.done = true;
				
				// create ncessary html elements

				var textbox_div = document.getElementById('{$egSMWIMediaWikiEditorID}');
				var preview_div = document.getElementById('{$egSMWIMediaWikiPreviewID}');
				
				var quickfixpanel_div = document.createElement('div');
				
				var quickfixpanel_div = document.createElement('div');
				quickfixpanel_div.setAttribute('id', '{$egSMWIQuickFixPanelMainDiv}');
				var quickfixpanelheader_div = document.createElement('div');
				quickfixpanelheader_div.setAttribute('id', 'smwi_QuickFixPanelHeaderDiv');
				
				var button_div = document.createElement('div');
				button_div.setAttribute('id', buttonDiv);
				var add_new_semantic_element_div = document.createElement('div');
				add_new_semantic_element_div.setAttribute('id', addNewSemanticElementDiv);
				
				
				var category_button = document.createElement('span');
				var relation_button = document.createElement('span');
				var property_button = document.createElement('span');				
				
				button_div.appendChild(category_button);
				button_div.appendChild(relation_button);
				button_div.appendChild(property_button);
				
				addButton('{$wgScriptPath}/{$egSMWIExtensionPath}/{$egSMWIImagePath}/{$egSMWIButtonAddCategorySmallImageName}','Add Category','[[Category:',']]','CategoryName','mw-editbutton-category');
				addButton('{$wgScriptPath}/{$egSMWIExtensionPath}/{$egSMWIImagePath}/{$egSMWIButtonAddRelationSmallImageName}','Add Relation','[[ReplaceThisWithRelation::',']]','RelationLink','mw-editbutton-relation');
				addButton('{$wgScriptPath}/{$egSMWIExtensionPath}/{$egSMWIImagePath}/{$egSMWIButtonAddPropertySmallImageName}','Add Property','[[ReplaceThisWithPropertyName:=',']]','PropertyValue','mw-editbutton-property');
				addButton('{$wgScriptPath}/{$egSMWIExtensionPath}/{$egSMWIImagePath}/{$egSMWIButtonAddAskSmallImageName}','Add Semantic Inline Query','<ask>','</ask>','Your inline query','mw-editbutton-addinlinequery');
				textbox_div.appendChild(quickfixpanelheader_div);
				textbox_div.appendChild(quickfixpanel_div);
				
				button_div.appendChild(add_new_semantic_element_div);
				preview_div.appendChild(document.createElement('br'));
				preview_div.appendChild(button_div);
				preview_div.appendChild(document.createElement('br'));
				
				
				category_button.setAttribute('id', 'smwi_add_category_button');
				relation_button.setAttribute('id', 'smwi_add_relation_button');
				property_button.setAttribute('id', 'smwi_add_property_button');
				
				category_button.innerHTML = '<img style=\"cursor: pointer;\" src=\"{$wgScriptPath}/{$egSMWIExtensionPath}/{$egSMWIImagePath}/{$egSMWIButtonAddCategoryImageName}\" onclick=\"smwifAddCategoryButtonPressed()\"></img>';
				relation_button.innerHTML =	'<img style=\"cursor: pointer;\" src=\"{$wgScriptPath}/{$egSMWIExtensionPath}/{$egSMWIImagePath}/{$egSMWIButtonAddRelationImageName}\" onclick=\"smwifAddRelationButtonPressed()\"></img>';
				property_button.innerHTML =	'<img style=\"cursor: pointer;\" src=\"{$wgScriptPath}/{$egSMWIExtensionPath}/{$egSMWIImagePath}/{$egSMWIButtonAddPropertyImageName}\" onclick=\"smwifAddPropertyButtonPressed()\"></img><img src=\"{$wgScriptPath}/{$egSMWIExtensionPath}/{$egSMWIImagePath}/{$egSMWISemanticWizardImageName}\"></img>';
				
				quickfixpanelheader_div.innerHTML = '<div style=\"text-align:center;\"><img style=\"cursor: pointer;\" src=\"{$wgScriptPath}/{$egSMWIExtensionPath}/{$egSMWIImagePath}/{$egSMWISemanticsQuickFixPanelHead}\" onclick=\"smwisfStartTheTimeOutTimer( textboxTag , 0);\"></img></div><div style=\"background-image: url({$wgScriptPath}/{$egSMWIExtensionPath}/{$egSMWIImagePath}/{$egSMWIQFPHorizontalPattern});\"><span style=\"text-align:left;\"><img src=\"{$wgScriptPath}/{$egSMWIExtensionPath}/{$egSMWIImagePath}/{$egSMWIQFPTopLeftCorner}\"></img></span><span style=\"position: absolute; right: 10px;\"><img src=\"{$wgScriptPath}/{$egSMWIExtensionPath}/{$egSMWIImagePath}/{$egSMWIQFPTopRightCorner}\"></img></span></div><div \"id\"=\"{$egSMWIQuickFixPanelDiv}></div>';
				
				// add_wysiwyg
				//generate_wysiwyg(\"{$egSMWIEditorTextboxID}\");
				
				// Add the event listener for the suggestion box under the editor
				smwisfAddTextboxMonitoring(\"{$egSMWIEditorTextboxID}\");
			};
			</script>"
		
		);
		
		// Register main js files that includes all Ajax related functions
		$outputPage->addScript( 
			"<script type=\"{$wgJsMimeType}\" src=\"{$wgScriptPath}/{$egSMWIExtensionPath}/{$egSMWIScriptPath}/{$egSMWIGeneralScriptsFileName}\">" .
			"</script>\n" .
			"<script type=\"{$wgJsMimeType}\" src=\"{$wgScriptPath}/{$egSMWIExtensionPath}/{$egSMWIScriptPath}/{$egSMWIQuickFixPanelRelatedScriptsFileName}\">" .
			"</script>\n" .
			"<script type=\"{$wgJsMimeType}\" src=\"{$wgScriptPath}/{$egSMWIExtensionPath}/{$egSMWIScriptPath}/{$egSMWIToolbarRelatedScriptsFileName}\">" .
			"</script>\n" .
			"<script type=\"{$wgJsMimeType}\" src=\"{$wgScriptPath}/{$egSMWIExtensionPath}/{$egSMWIScriptPath}/{$egSMWIHelpRelatedScriptsFileName}\">" .
			"</script>\n" 
		);
		

		// Register js for making init() work in IE as well
		// Solution for IE issue adopted from http://dean.edwards.name/weblog/2005/09/busted/ 
		
		// Create the event listeners for page load for all browsers except IE
		$outputPage->addScript(
			"
			<script type=\"{$wgJsMimeType}\">
			/* for Mozilla */
			
			if (document.addEventListener) {
			document.addEventListener(\"DOMContentLoaded\", init, false);
			}
  
			/* for Safari */
			if (/WebKit/i.test(navigator.userAgent)) { // sniff
			    var _timer = setInterval(function() {
				if (/loaded|complete/.test(document.readyState)) {
				    clearInterval(_timer);
				    init(); // call the onload handler
				}
			    }, 10);
			}
			
			// for Internet Explorer (using conditional comments)
			/*@cc_on @*/
			/*@if (@_win32)
			document.write(\"<script id=__ie_onload defer src=javascript:void(0)><\/script>\");
			var script = document.getElementById(\"__ie_onload\");
			script.onreadystatechange = function() {
			    if (this.readyState == \"complete\") {
				init(); // call the onload handler
			    }
			};
			/*@end @*/
			
			// Other Browsers
			window.onload = init;
			</script>"
		);
		
		return true;
	}
}

?>