<?php

/**
  * File: MOCA_QuickFixPanel.php
  *
  * Description: Contains methods that help create the SMWI QuickFixPanel interface
			and interaction functionallity.
  *
  * @author Chrysovalanto Kousetti
  * @email valanto@gmail.com
  *
  */
  
// Checks mediawiki exists
if( !defined( 'MEDIAWIKI' ) ) {
	echo( "This file is an extension to the MediaWiki software and cannot be used standalone.\n" );
	die( 1 );
}
  
 // A class containing methods for rendering the Edit Interface
  class MOCA_QuickFixPanel{
  
  	/**
	   * Loads the QuickFixPanel
	   * 
	   * @return AjaxResponse
	   */
	function getAjaxQuickFixPanel() {
		global $egSMWIWikiTextCookieName,$egSMWIQuickFixPanelDiv, $egSMWIQuickFixPanelMainDiv, $egMOCA_txt,
		$egSMWIQFPHorizontalPattern, $egSMWIQFPBottomLeftCorner, $egSMWIQFPBottomRightCorner, $egSMWIQFPVerticalPattern,
		$wgScriptPath, $egSMWIExtensionPath, $egSMWIImagePath;
		
		$search_functions_instance = new MOCA_SearchFunctions;
		$cookie_content = "";
		$cookie_size = $_COOKIE[$egSMWIWikiTextCookieName."_size"];
		$i = 0;

		while($_COOKIE[$egSMWIWikiTextCookieName.$i] && $i< $cookie_size){
			$cookie_content.= $_COOKIE[$egSMWIWikiTextCookieName.$i];
			$i++;
		}
		
		// find all matched categories
		$matched_categories = $search_functions_instance->findCategories( $cookie_content );
		// find all matched relations
		$matched_relations =  $search_functions_instance->findRelations( $cookie_content );
		// find all matched plain links
		$matched_plain_links = $search_functions_instance->findPlainLinks( $cookie_content );
		// find all matched properties
		$matched_properties =  $search_functions_instance->findProperties( $cookie_content );
		
		$category_section_result = $this->getAjaxCategoryPanel($matched_categories);
		$relation_section_result = $this->getAjaxRelationPanel($matched_relations, $matched_plain_links);
		$property_section_result = $this->getAjaxPropertyPanel($matched_properties, $matched_categories);
		$response_html = '';
		
		//$response_html .=  wfOpenElement('div',array('id'=>$egSMWIQuickFixPanelMainDiv));
		if( ($category_section_result == "") && ($relation_section_result == "") && ($property_section_result == "") ){
			$response_html .= wfOpenElement('table', array('class'=>'smwi_quickFixPanel_withsuggestions_table', 'width'=>'100%'));
				$response_html .= wfOpenElement('tr', array('width'=>'100%'));
					$response_html .= wfOpenElement('p', array('class'=>'smwi_quickFixPanel_nosuggestions')).$egMOCA_txt['help_quickfixpanel_nosuggestions'].wfCloseElement('p');
				$response_html .= wfCloseElement('tr');
				$response_html .= wfOpenElement('tr', array('width'=>'100%'));
				$response_html .= wfCloseElement('tr');
				$response_html .= wfOpenElement('tr', array('width'=>'100%'));	
						$response_html .= wfOpenElement('div',array('id'=>$egSMWIQuickFixPanelDiv)).wfCloseElement('div');
				$response_html .= wfCloseElement('tr');
				
			$response_html .= wfCloseElement('table');
			return;
		
		}
			
			$response_html .= wfOpenElement('table', array('class'=>'smwi_quickFixPanel_withsuggestions_table', 'width'=>'100%'));
				$response_html .= wfOpenElement('tr', array('width'=>'100%'));
					$response_html .= wfOpenElement('td', array('width'=>'55%', 'style'=>'vertical-align: top; border-right: medium dotted rgb(216,133,10);'));
						if( $category_section_result != "" ) {
							$response_html .= wfOpenElement('div', array('style'=>'border-bottom: medium dotted rgb(216,133,10); '));
							$response_html .= $category_section_result;
							$response_html .= wfCloseElement('div');
						}
						if( $relation_section_result != "" ) {
							$response_html .= wfOpenElement('div', array('style'=>'border-bottom: medium dotted rgb(216,133,10);'));
							$response_html .= $relation_section_result;
							$response_html .= wfCloseElement('div');
						}
						if( $property_section_result != "" ) {
							$response_html .= $property_section_result;
						}
					$response_html .= wfOpenElement('td', array('width'=>'45%'));
						$response_html .=  wfOpenElement('div',array('id'=>$egSMWIQuickFixPanelDiv, 'style'=>'vertical-align: top;')).wfOpenElement('table', array( 'class'=>'smwi_quickFixPanel_issuesfix_table')).wfOpenElement('tr').wfOpenElement('td', array('style'=>'vertical-align: top;')).
						wfOpenElement('p', array('class'=>'smwi_quickFixPanel_content_b')).$egMOCA_txt['help_quickfixpanel_howto_use_header'].wfCloseElement('p').wfOpenElement('p', array('class'=>'smwi_quickFixPanel_content')).$egMOCA_txt['help_quickfixpanel_howto_use'].wfCloseElement('p').
									wfOpenElement('br').wfOpenElement('p', array('class'=>'smwi_in_box_message')).$egMOCA_txt['help_quickfixpanel_contribution'].wfCloseElement('p').wfCloseElement('td').wfCloseElement('tr').wfCloseElement('table').wfCloseElement('div');
					$response_html .= wfCloseElement('td');
				$response_html .= wfCloseElement('tr');
			$response_html .= wfCloseElement('table');
			
			$response_html .= wfOpenElement('div', array('style'=>"background-image: url({$wgScriptPath}/{$egSMWIExtensionPath}/{$egSMWIImagePath}/{$egSMWIQFPHorizontalPattern})"));
			$response_html .= wfOpenElement('span', array('style'=>'text-align:left;')).wfOpenElement('img',array('src'=>"{$wgScriptPath}/{$egSMWIExtensionPath}/{$egSMWIImagePath}/{$egSMWIQFPBottomLeftCorner}")).wfCloseElement('img').wfCloseElement('span');
			$response_html .= wfOpenElement('span', array('style'=>'position: absolute; right: 10px;')).wfOpenElement('img',array('src'=>"{$wgScriptPath}/{$egSMWIExtensionPath}/{$egSMWIImagePath}/{$egSMWIQFPBottomRightCorner}")).wfCloseElement('img').wfCloseElement('span');
			$response_html .= wfCloseElement('div');
			$response_html .= wfOpenElement('br').wfCloseElement('br');
		
		
		$response = new AjaxResponse();
		$response->addText( $response_html );
		//return the response
		return $response;
	}
	
	/**
	   * Loads the Category part of the QuickFixPanel checklist
	   * 
	   * @return AjaxResponse
	   */
	function getAjaxCategoryPanel($matched_categories) {
		global $wgScriptPath, $egSMWIExtensionPath, $egSMWIImagePath, $egSMWICheckLargeImageName, $egSMWIWarnLargeImageName,
			$egSMWIQuickFixPanelDiv, $egSMWIPlusImageName, $egSMWIMinusImageName, $egSMWIArrowImageName, $egMOCA_txt;
		
		$check_image_code = wfOpenElement('img', array('src' => "{$wgScriptPath}/{$egSMWIExtensionPath}/{$egSMWIImagePath}/{$egSMWICheckLargeImageName}")).wfCloseElement('img');
		$warn_image_code = wfOpenElement('img', array('src' => "{$wgScriptPath}/{$egSMWIExtensionPath}/{$egSMWIImagePath}/{$egSMWIWarnLargeImageName}")).wfCloseElement('img');
		$plus_image_code = wfOpenElement('img', array('src' => "{$wgScriptPath}/{$egSMWIExtensionPath}/{$egSMWIImagePath}/{$egSMWIPlusImageName}")).wfCloseElement('img');
		$minus_image_code = wfOpenElement('img', array('src' => "{$wgScriptPath}/{$egSMWIExtensionPath}/{$egSMWIImagePath}/{$egSMWIMinusImageName}")).wfCloseElement('img');
		$arrow_image_code = wfOpenElement('img', array('src' => "{$wgScriptPath}/{$egSMWIExtensionPath}/{$egSMWIImagePath}/{$egSMWIArrowImageName}")).wfCloseElement('img');


		$inner_response_html = wfOpenElement('table',array('style'=>'table-layout: fixed')).wfOpenElement('tr').wfOpenElement('td', array('width'=>'150px'));
		$response_html = '';
		
		if($matched_categories == null || sizeof($matched_categories) == 0){
			$status_txt = $egMOCA_txt['help_quickfixpanel_category_nocategory'];
			$rec = $egMOCA_txt['help_quickfixpanel_category_nocategory_r'];
			$issue_code = 1;
			$image = $warn_image_code;

		}
		else {
			if(sizeof($matched_categories) == 1){
				$status_txt = $egMOCA_txt['help_quickfixpanel_category_onecategory']; 
				$rec = $egMOCA_txt['help_quickfixpanel_category_onecategory_r'];
			}
			else{
				$status_txt = $egMOCA_txt['help_quickfixpanel_category_morethanonecategory']; 
				$rec =$egMOCA_txt['help_quickfixpanel_category_morethanonecategory_r'];
			}
			$issue_code = 0;
			$image = $check_image_code;
		}
		
		$inner_response_html .= 	
				wfOpenElement('span', array('class'=>'smwi_in_box_content')).
						wfOpenElement('p', array('class'=>'smwi_quickFixPanel_category_title')).
							$image."&nbsp;&nbsp;".$egMOCA_txt['help_quickfixpanel_category_header'].
						wfCloseElement('p').
					wfCloseElement('span').
					wfCloseElement('td').
					wfOpenElement('td').
					wfOpenElement('span', array('class'=>'smwi_in_box_content')).
						wfOpenElement('span', array('class'=>'smwi_quickFixPanel_category_text ')).
							wfOpenElement('b')."Status: ".wfCloseElement('b').$status_txt."&nbsp;- ".
							wfOpenElement('a', array('onclick'=>"smwi_help_show_popup_tip(\"{$egMOCA_txt['help_category_definition_head']}\",\"{$egMOCA_txt['help_category_definition']}\")", 'style'=>'cursor: pointer; font-weight:normal;')).$egMOCA_txt['help_category_definition_head'].wfCloseElement('a').
						wfCloseElement('span').
				wfCloseElement('span').
				wfOpenElement('p', array('class'=>'smwi_quickFixPanel_category_text ')).
					"<b>Recommendation: </b>".$rec.
				wfCloseElement('p');
			
		$inner_response_html .= wfOpenElement('p', array('class'=>'smwi_in_box_content_b')).$arrow_image_code."&nbsp;&nbsp;".wfOpenElement('a', array('onclick'=>"smwisfFixCategoryIssue('{$egSMWIQuickFixPanelDiv}', '{$issue_code}')", 'style'=>'cursor: pointer;')).$egMOCA_txt['help_quickfixpanel_category_add_newcategory'].wfCloseElement('a').wfCloseElement('p');

		
		$response_html .= $inner_response_html.wfCloseElement('td').wfCloseElement('tr').wfCloseElement('table');

				
		//return the response
		return $response_html;
	}
	
	/**
	   * Loads the Category Fixing panel
	   * 
	   * @return AjaxResponse
	   */
	function getCategoryIssueQuickFixPanel($issue_code) {
		global $egSMWIWikiTextCookieName,$egSMWIQuickFixPanelDiv, $egMOCA_txt;
		
		$search_functions_instance = new MOCA_SearchFunctions;
		$cookie_content = "";
		$cookie_size = $_COOKIE[$egSMWIWikiTextCookieName."_size"];
		$i = 0;

		while($_COOKIE[$egSMWIWikiTextCookieName.$i] && $i< $cookie_size){
			$cookie_content.= $_COOKIE[$egSMWIWikiTextCookieName.$i];
			$i++;
		}
	
		$global_bits_instance = new MOCA_GlobalBits();
		$search_functions_instace = new MOCA_SearchFunctions();
		
		// find all matched categories
		$matched_categories = $search_functions_instance->findCategories( $cookie_content );
		// find all matched relations
		$matched_relations =  $search_functions_instance->findRelations( $cookie_content );
		// find all matched plain links
		$matched_plain_links = $search_functions_instance->findPlainLinks( $cookie_content );
		// find all matched properties
		$matched_properties =  $search_functions_instance->findProperties( $cookie_content );
		
		$matched_clean_dpages = array();
		
		if($matched_relations!= null && sizeof($matched_relations)>0){
			foreach($matched_relations as $mr){
				$matched_clean_dpages[] = array('relation' =>$mr['relation'], 
										'page' => $mr['page']);
			}
		}
		if($matched_plain_links!= null && sizeof($matched_plain_links)>0){
			foreach($matched_plain_links as $mpl){
				$matched_clean_dpages[] = array('relation' => '', 
										'page' => $mpl['page']);
			}
		}
		
		$response_html = '';
		$inner_response_html = '';
		$suggested_categories = $global_bits_instance->getCleanSuggestedCategoriesArray($matched_categories, $matched_properties, $matched_clean_dpages);
		$all_categories = $search_functions_instance->getAllCategories();
		$suggested_categories_html = MOCA_GlobalBits::getListHTML($suggested_categories);
		$all_categories_html =  MOCA_GlobalBits::getListHTML($all_categories);
		
		if($issue_code == 1){
			//$inner_response_html .= wfOpenElement('td', array('rowspan'=>'3'));
			$inner_response_html_1 = wfOpenElement('p', array('class'=>'smwi_in_box_content')).
									"<b>{$egMOCA_txt['help_category_nocategory']}</b>{$egMOCA_txt['help_category_why_add']}<b>{$egMOCA_txt['help_category_recommendation']}</b>"
									.wfCloseElement('p');	
			$inner_response_html_2 =  $global_bits_instance->getAddCategoryMultiOptionHTML('smwi_add_category_title','smwi_qfp_add_category_radio', array('smwi_qfp_add_category_new', 'smwi_qfp_add_category_suggested','smwi_qfp_add_category_all', ""), 0, 'smwifAddCategoryButtonCompletedWrapper', $suggested_categories_html, $all_categories_html,"");


		}
		elseif($issue_code == 0){
			$inner_response_html_1 = wfOpenElement('p', array('class'=>'smwi_in_box_content')).
									"<b>{$egMOCA_txt['help_category_oneormorecategory']}</b>{$egMOCA_txt['help_category_why_add']}<b>{$egMOCA_txt['help_category_recommendation']}</b>"
									.wfCloseElement('p');	
			$inner_response_html_2 =  $global_bits_instance->getAddCategoryMultiOptionHTML('smwi_add_category_title','smwi_qfp_add_category_radio', array('smwi_qfp_add_category_new', 'smwi_qfp_add_category_suggested','smwi_qfp_add_category_all', ""), 0, 'smwifAddCategoryButtonCompletedWrapper', $suggested_categories_html, $all_categories_html,"");

		}
		else{
			$inner_response_html_1 = "";
			$inner_response_html_2 = "";
			return;
		}
		
		
			//$response_html .= wfOpenElement('div',array('id'=>$egSMWIQuickFixPanelDiv));
			$response_html .= wfOpenElement('table', array( 'width'=>'100%', 'class'=>'smwi_quickFixPanel_issuesfix_table'));
				$response_html .= wfOpenElement('tr', array('width'=>'100%'));
					$response_html .= wfOpenElement('td', array('width'=>'100%', 'style'=>'vertical-align:top;'));
						$response_html .= wfOpenElement('p', array('class'=>'smwi_quickFixPanel_moreInformation_title')).$egMOCA_txt['help_quickfixpanel_moreinformation'].wfCloseElement('p');
						$response_html .= $inner_response_html_1;
					$response_html .= wfCloseElement('td');
				$response_html .= wfCloseElement('tr');
				$response_html .= wfOpenElement('hr').wfCloseElement('hr');
				$response_html .= wfOpenElement('tr');
					$response_html .= wfOpenElement('td', array('width'=>'100%', 'style'=>'vertical-align:top;'));
						$response_html .= wfOpenElement('p', array('class'=>'smwi_quickFixPanel_moreInformation_title')).$egMOCA_txt['help_quickfixpanel_add_categorywizard'].wfCloseElement('p');
						$response_html .=  $inner_response_html_2;
					$response_html .= wfCloseElement('td');
				$response_html .= wfCloseElement('tr');
			$response_html .= wfCloseElement('table');
			//$response_html .= wfCloseElement('div');
	
		
		
		$response = new AjaxResponse();
		$response->addText( $response_html );
		//return the response
		return $response;
	
	}
	
	/**
	   * Loads the Relation part of the QuickFixPanel checklist
	   * 
	   * @return AjaxResponse
	   */
	function getAjaxRelationPanel($matched_relations, $matched_plain_links) {
		global $wgScriptPath, $egSMWIExtensionPath, $egSMWIImagePath, $egSMWICheckLargeImageName, $egSMWIWarnLargeImageName, $egMOCA_txt,
			$egSMWIQuickFixPanelDiv, $egSMWIShowPlainLinksToFixDiv, $egSMWIPlusImageName, $egSMWIMinusImageName, $egSMWIArrowImageName, $egSMWIShowPlainLinksTitleDiv;
		
		$check_image_code = wfOpenElement('img', array('src' => "{$wgScriptPath}/{$egSMWIExtensionPath}/{$egSMWIImagePath}/{$egSMWICheckLargeImageName}")).wfCloseElement('img');
		$warn_image_code = wfOpenElement('img', array('src' => "{$wgScriptPath}/{$egSMWIExtensionPath}/{$egSMWIImagePath}/{$egSMWIWarnLargeImageName}")).wfCloseElement('img');
		$plus_image_code = wfOpenElement('img', array('src' => "{$wgScriptPath}/{$egSMWIExtensionPath}/{$egSMWIImagePath}/{$egSMWIPlusImageName}", 'onclick'=>"smwisfFixRelationIssue('{$egSMWIShowPlainLinksToFixDiv}', -1,'',0,0)", 'style'=>'cursor: pointer;')).wfCloseElement('img');
		$minus_image_code = wfOpenElement('img', array('src' => "{$wgScriptPath}/{$egSMWIExtensionPath}/{$egSMWIImagePath}/{$egSMWIMinusImageName}")).wfCloseElement('img');
		$arrow_image_code = wfOpenElement('img', array('src' => "{$wgScriptPath}/{$egSMWIExtensionPath}/{$egSMWIImagePath}/{$egSMWIArrowImageName}")).wfCloseElement('img');

		$inner_response_html = wfOpenElement('table',array('style'=>'table-layout: fixed')).wfOpenElement('tr').wfOpenElement('td', array('width'=>'150px'));
		$response_html = '';
		if($matched_relations == null && sizeof($matched_relations) == 0 && $matched_plain_links == null && sizeof($matched_plain_links) == 0){

			$image = $warn_image_code;
			$status_txt = $egMOCA_txt['help_quickfixpanel_relation_norelation'];
			$rec = $egMOCA_txt['help_quickfixpanel_relation_norelation_r'];
			$view_all_relations = "";
			$issue_code = 1;
		}
		else{
			if($matched_plain_links == null || sizeof($matched_plain_links) == 0){
				$image = $check_image_code;
				$status_txt = $egMOCA_txt['help_quickfixpanel_relation_nolinks_without_relationtype'];
				$rec = $egMOCA_txt['help_quickfixpanel_relation_nolinks_without_relationtype_r'];
				$issue_code = 0;

			}
			else {
				$image = $warn_image_code;
				$status_txt = $egMOCA_txt['help_quickfixpanel_relation_oneormorerelationsandlinks'];
				$rec = $egMOCA_txt['help_quickfixpanel_relation_oneormorerelationsandlinks_r'];
				$issue_code = 3;

			}
				$view_all_relations = wfOpenElement('p', array('class'=>'smwi_in_box_content_b')).
								wfOpenElement('span', array('id'=>$egSMWIShowPlainLinksTitleDiv)).
									$plus_image_code."&nbsp;&nbsp;".
									wfOpenElement('a', array('onclick'=>"smwisfFixRelationIssue('{$egSMWIShowPlainLinksToFixDiv}', -1,'',0,0)", 'style'=>'cursor: pointer;')).
										$egMOCA_txt['help_quickfixpanel_relation_view_all_relationandlinks'].
									wfCloseElement('a').
								wfCloseElement('span').
							wfCloseElement('p');
		}
		
		$inner_response_html .= wfOpenElement('p').
							wfOpenElement('span', array('class'=>'smwi_quickFixPanel_relation_title')).
								$image."&nbsp;&nbsp;".$egMOCA_txt['help_quickfixpanel_relation_header'].
							wfCloseElement('span').
						wfCloseElement('td').
						wfOpenElement('td').
						wfOpenElement('span', array('class'=>'smwi_quickFixPanel_relation_title')).
							wfOpenElement('span', array('class'=>'smwi_quickFixPanel_relation_text ')).
								wfOpenElement('p')."<b>Status: </b>".$status_txt."&nbsp;- ".
									wfOpenElement('a', array('onclick'=>"smwi_help_show_popup_tip(\"{$egMOCA_txt['help_relation_definition_head']}\",\"{$egMOCA_txt['help_relation_definition']}\")", 'style'=>'cursor: pointer; font-weight: normal; ')).
										$egMOCA_txt['help_relation_definition_head'].
									wfCloseElement('a').
								wfCloseElement('p').
								wfOpenElement('p')."<b>Recommendation: </b>".$rec.wfCloseElement('p').
							wfCloseElement('span').
						$view_all_relations.
						wfOpenElement('div', array('id'=>$egSMWIShowPlainLinksToFixDiv)).wfCloseElement('div').
						wfOpenElement('p', array('class'=>'smwi_in_box_content_b')).
							$arrow_image_code."&nbsp;&nbsp;".
							wfOpenElement('a', array('onclick'=>"smwisfFixRelationIssue('{$egSMWIQuickFixPanelDiv}', '{$issue_code}', '', 0,0, '')", 'style'=>'cursor: pointer;')).
								$egMOCA_txt['help_quickfixpanel_relation_add_newrelation'].
							wfCloseElement('a').
							wfCloseElement('span').
						wfCloseElement('p');

		
		$response_html .= $inner_response_html.wfCloseElement('td').wfCloseElement('tr').wfCloseElement('table');

				
		//return the response
		return $response_html;
	}
	
	/**
	   * Loads the Relation Fixing panel
	   * 
	   * @return AjaxResponse
	   */
	function getRelationIssueQuickFixPanel($issue_code, $link, $pos, $len, $alt, $type){
		global $egSMWIWikiTextCookieName,$egSMWIQuickFixPanelDiv, $egMOCA_txt;
		$cookie_content = "";
		$cookie_size = $_COOKIE[$egSMWIWikiTextCookieName."_size"];
		$i = 0;

		while($_COOKIE[$egSMWIWikiTextCookieName.$i] && $i< $cookie_size){
			$cookie_content.= $_COOKIE[$egSMWIWikiTextCookieName.$i];
			$i++;
		}
	
		$global_bits_instance = new MOCA_GlobalBits();
		$search_functions_instance = new MOCA_SearchFunctions();
		
		
		if($link == ""){
			$matched_categories = $search_functions_instance->findCategories( $cookie_content );
			$matched_relations =  $search_functions_instance->findRelations( $cookie_content );
			$matched_plain_links = $search_functions_instance->findPlainLinks( $cookie_content );

			$suggested_relations = $global_bits_instance->getCleanSuggestedRelationsArray($matched_categories, $matched_plain_links, $matched_relations);

			$suggested_relations_html = MOCA_GlobalBits::getListHTML($suggested_relations);
		
		}
		else {
			$suggested_relations_array = null;
			
			$suggested_relations = $search_functions_instance->findSuggestedRelationsForDPages(array($link));
			if($suggested_relations!= null && sizeof($suggested_relations) > 0){
				foreach($suggested_relations AS $key=>$sr){
					$suggested_relations_array[] = $key;
				}
				$suggested_relations_html = MOCA_GlobalBits::getListHTML($suggested_relations_array);
			}
		}
		
		if($issue_code == 0){
			$inner_response_html_1 = wfOpenElement('p', array('class'=>'smwi_in_box_content')).
									"<b>{$egMOCA_txt['help_relation_nolinks_without_relationtype']}</b>{$egMOCA_txt['help_relation_why_add']}<b>{$egMOCA_txt['help_relation_recommendation']}</b>"
									.wfCloseElement('p');	
			$inner_response_html_2 .= $global_bits_instance->getAddRelationMultiOptionHTML('smwi_in_box_title','smwi_add_relation_title', 'smwi_add_relation_radio', array('smwi_qfp_add_relation_new', 'smwi_add_qfp_relation_suggested', 'smwi_qfp_add_relation_value', 'smwi_qfp_add_relation_alt'), $pos, 'smwifAddRelationButtonCompletedWrapper', $suggested_relations_html, $link, -1,$alt);
		}
		elseif($issue_code == 1 && $type != ""){
			$inner_response_html_1 = wfOpenElement('p', array('class'=>'smwi_in_box_content')).
									"<b>{$egMOCA_txt['help_relation_norelation']}</b>{$egMOCA_txt['help_relation_why_add']}<b>{$egMOCA_txt['help_relation_recommendation']}</b>"
									.wfCloseElement('p');	
			$inner_response_html_2 .= $global_bits_instance->getAddRelationMultiOptionHTML('smwi_in_box_title','smwi_add_relation_title', 'smwi_add_relation_radio', array('smwi_qfp_add_relation_new', 'smwi_add_qfp_relation_suggested', 'smwi_qfp_add_relation_value', 'smwi_qfp_add_relation_alt'), $pos, 'smwifAddRelationButtonCompletedWrapper', $suggested_relations_html, $link, $len, $alt, $type);

		}
		elseif($issue_code == 2){
			$inner_response_html_1 = wfOpenElement('p', array('class'=>'smwi_in_box_content')).
									"<b>{$egMOCA_txt['help_relation_norelationtype']}</b>{$egMOCA_txt['help_relation_why_add']}<b>{$egMOCA_txt['help_relation_recommendation']}</b>"
									.wfCloseElement('p');	
			$inner_response_html_2 .= $global_bits_instance->getAddRelationMultiOptionHTML('smwi_in_box_title','smwi_add_relation_title', 'smwi_add_relation_radio', array('smwi_qfp_add_relation_new', 'smwi_add_qfp_relation_suggested', 'smwi_qfp_add_relation_value', 'smwi_qfp_add_relation_alt'), $pos, 'smwifAddRelationButtonCompletedWrapper', $suggested_relations_html, $link, $len, $alt, "",true);

		}
		elseif($issue_code == 3){
			$inner_response_html_1 = wfOpenElement('p', array('class'=>'smwi_in_box_content')).
									"<b>{$egMOCA_txt['help_relation_norelationtype2']}</b>{$egMOCA_txt['help_relation_why_add']}<b>{$egMOCA_txt['help_relation_recommendation']}</b>"
									.wfCloseElement('p');	
			$inner_response_html_2 .= $global_bits_instance->getAddRelationMultiOptionHTML('smwi_in_box_title','smwi_add_relation_title', 'smwi_add_relation_radio', array('smwi_qfp_add_relation_new', 'smwi_add_qfp_relation_suggested', 'smwi_qfp_add_relation_value', 'smwi_qfp_add_relation_alt'), $pos, 'smwifAddRelationButtonCompletedWrapper', $suggested_relations_html, $link, $len, $alt);

		}
		else{
			$inner_response_html_1 = "";
			$inner_response_html_2 = "";
			return;
		}
		
		
			//$response_html .= wfOpenElement('div',array('id'=>$egSMWIQuickFixPanelDiv));
			$response_html .= wfOpenElement('table', array( 'width'=>'100%', 'class'=>'smwi_quickFixPanel_issuesfix_table'));
				$response_html .= wfOpenElement('tr', array('width'=>'100%'));
					$response_html .= wfOpenElement('td', array('width'=>'100%', 'style'=>'vertical-align:top;'));
						$response_html .= wfOpenElement('p', array('class'=>'smwi_quickFixPanel_moreInformation_title')).$egMOCA_txt['help_quickfixpanel_moreinformation'].wfCloseElement('p');
						$response_html .= $inner_response_html_1;
					$response_html .= wfCloseElement('td');
				$response_html .= wfCloseElement('tr');
				$response_html .= wfOpenElement('hr').wfCloseElement('hr');
				$response_html .= wfOpenElement('tr');
					$response_html .= wfOpenElement('td', array('width'=>'100%', 'style'=>'vertical-align:top; '));
						$response_html .= wfOpenElement('p', array('class'=>'smwi_quickFixPanel_moreInformation_title')).$egMOCA_txt['help_quickfixpanel_add_relationwizard'].wfCloseElement('p');
						$response_html .=  $inner_response_html_2;
					$response_html .= wfCloseElement('td');
				$response_html .= wfCloseElement('tr');
			$response_html .= wfCloseElement('table');
			//$response_html .= wfCloseElement('div');
	
		
		
		$response = new AjaxResponse();
		$response->addText( $response_html );
		//return the response
		return $response;
	
	}
	
	/**
	   * Loads the list of plain links and relations for fixing and editing
	   * 
	   * @return AjaxResponse
	   */
	function getPlainLinkToFix($offset) {
		global $wgScriptPath, $egSMWIExtensionPath, $egSMWIImagePath, $egSMWICheckImageName, $egSMWIWarnImageName, $egSMWIArrowImageName,
			$egSMWIQuickFixPanelDiv, $egSMWIShowPlainLinksToFixDiv, $egSMWIWikiTextCookieName,
			$egSMWIMaxRelationsPerResultPage, $egSMWICompactRelationsResults, $egSMWIShowPlainLinksToFixDiv,
			$egSMWINextRelationSetButton, $egSMWIPreviousRelationSetButton;
		
		$cookie_content = "";
		$cookie_size = $_COOKIE[$egSMWIWikiTextCookieName."_size"];
		$i = 0;

		while($_COOKIE[$egSMWIWikiTextCookieName.$i] && $i< $cookie_size){
			$cookie_content.= $_COOKIE[$egSMWIWikiTextCookieName.$i];
			$i++;
		}
	
		$global_bits_instance = new MOCA_GlobalBits();
		$search_functions_instance = new MOCA_SearchFunctions();
		$check_image_code = wfOpenElement('img', array('src' => "{$wgScriptPath}/{$egSMWIExtensionPath}/{$egSMWIImagePath}/{$egSMWICheckImageName}")).wfCloseElement('img');
		$warn_image_code = wfOpenElement('img', array('src' => "{$wgScriptPath}/{$egSMWIExtensionPath}/{$egSMWIImagePath}/{$egSMWIWarnImageName}")).wfCloseElement('img');
		$arrow_image_code = wfOpenElement('img', array('src' => "{$wgScriptPath}/{$egSMWIExtensionPath}/{$egSMWIImagePath}/{$egSMWIArrowImageName}")).wfCloseElement('img');

		$matched_relations =  $search_functions_instance->findRelations( $cookie_content );
		$matched_plain_links = $search_functions_instance->findPlainLinks( $cookie_content );
		$merged_array=array();
		
		if($matched_plain_links != null  && sizeof($matched_plain_links)>0){
			foreach($matched_plain_links as $mpl){
				if($mpl['alt'] == "") $mpl['alt'] = $mpl['page'];
				$merged_array[] = array(	
									'dpage'=>$mpl['page'],
									'relation'=>"",
									'pos'=>$mpl['pos'],
									'length'=>$mpl['len'],
									'alt'=>$mpl['alt'],
									'flag'=> 0
								);
			}
		}
		
		if($matched_relations != null  && sizeof($matched_relations)>0){
			foreach($matched_relations as $mr){
				if($mr['alt'] == "") $mr['alt'] = $mr['page'];
				$merged_array[] = array(
									'dpage'=>$mr['page'],
									'relation'=>$mr['relation'],
									'pos'=>$mr['pos'],
									'length'=>$mr['len'],
									'alt'=>$mr['alt'],
									'flag'=> 1				
								);
			}
		}

		
		$response_html  = "";
		
		$response_html .=wfOpenElement('span', array('class'=>'smwi_in_box_content'));
			$response_html .= wfOpenElement('table');
				$response_html .= wfOpenElement('tr');
					$response_html .= wfOpenElement('td', array('width'=>'5%'));
					$response_html .= wfCloseElement('td');
					$response_html .= wfOpenElement('td', array('width'=>'95%'));
		
		if($merged_array != null  && sizeof($merged_array)>0  && $offset != -1){
			if($egSMWICompactRelationsResults) $max_relations = $egSMWIMaxRelationsPerResultPage;
			else $max_relations = sizeof($merged_array);
			
			if(sizeof($merged_array) > $offset){
				if(sizeof($merged_array) > ($offset + $max_relations)) {
					$temp = array();
					
					for($i = $offset; $i<($offset + $max_relations); $i++){
						$temp[] = $merged_array[$i];
					}
					$merged_array= $temp;
					$previous_offset = $offset - $max_relations;
					$next_offset = $offset + $max_relations;
				}
				else {
					if($max_relations == ( sizeof($merged_array) - $offset)) $limit = $max_relations;
					else $limit = sizeof($merged_array) - $offset;
	
					$temp = array();
					//print_r($limit);
					for($i = $offset; $i<($offset + $limit ); $i++){
						$temp[] = $merged_array[$i];
					}
					$merged_array= $temp;
					$previous_offset = $offset - $max_relations;
					$next_offset = -1;
				}


				foreach($merged_array as $key =>$ma){
					$response_html .= wfOpenElement('ul');
						if($ma['flag']==0)	$response_html .= wfOpenElement('li').$warn_image_code."&nbsp;&nbsp;Link to ".wfOpenElement('b').wfOpenElement('a', array('href'=>MOCA_GlobalBits::getArticleURL($ma['dpage']))).$ma['alt'].wfCloseElement('a').wfCloseElement('b')." found ".wfOpenElement('a', array('onclick'=>"smwifWhereInWikiText('".$ma['pos']."','".$ma['length']."')", 'style'=>'cursor: pointer;'))."here".wfCloseElement('a').": has no relation type.".wfOpenElement('a', array('onclick'=>"smwisfFixRelationIssue('{$egSMWIQuickFixPanelDiv}', 2, '".$ma['dpage']."', '".$ma['pos']."', '".$ma['length']."', '".$ma['alt']."')", 'style'=>'cursor: pointer;'))."Fix".wfCloseElement('a').wfCloseElement('li');
						elseif($ma['flag']==1) $response_html .= wfOpenElement('li').$check_image_code."&nbsp;&nbsp;Link to ".wfOpenElement('b').wfOpenElement('a', array('href'=>MOCA_GlobalBits::getArticleURL($ma['dpage']))).$ma['alt'].wfCloseElement('a').wfCloseElement('b')." found ".wfOpenElement('a', array('onclick'=>"smwifWhereInWikiText('".$ma['pos']."','".$ma['length']."')", 'style'=>'cursor: pointer;'))."here".wfCloseElement('a').": already has a relation type \"{$ma['relation']}\". ".wfOpenElement('a', array('onclick'=>"smwisfFixRelationIssue('{$egSMWIQuickFixPanelDiv}', 1, '".$ma['dpage']."', '".$ma['pos']."', '".$ma['length']."', '".$ma['alt']."', '".$ma['relation']."')", 'style'=>'cursor: pointer;'))."Edit".wfCloseElement('a').wfCloseElement('li');

					$response_html .= wfCloseElement('ul');
				}
			}
			else $response_html .= wfOpenElement('p').$egMOCA_txt['help_relation_no_relations_found'].wfCloseElement('p');
			
			$next_image_code = wfOpenElement('img', array('src' => "{$wgScriptPath}/{$egSMWIExtensionPath}/{$egSMWIImagePath}/{$egSMWINextRelationSetButton}",'onclick'=>"showNextSetOfRelations('{$egSMWIShowPlainLinksToFixDiv}','{$next_offset}')", 'style'=>'cursor: pointer;')).wfCloseElement('img');
			$previous_image_code = wfOpenElement('img', array('src' => "{$wgScriptPath}/{$egSMWIExtensionPath}/{$egSMWIImagePath}/{$egSMWIPreviousRelationSetButton}", 'onclick'=>"showPreviousSetOfRelations('{$egSMWIShowPlainLinksToFixDiv}','{$previous_offset}')", 'style'=>'cursor: pointer;')).wfCloseElement('img');
			//$next_image_code = wfOpenElement('a', array('onclick'=>"showNextSetOfRelations('{$egSMWIShowPlainLinksToFixDiv}','{$next_offset}')", 'style'=>'cursor: pointer;'))."Next >>".wfCloseElement('a');
			//$previous_image_code = wfOpenElement('a', array('onclick'=>"showNextSetOfRelations('{$egSMWIShowPlainLinksToFixDiv}','{$previous_offset}')", 'style'=>'cursor: pointer;'))."<< Previous".wfCloseElement('a');

			$response_html .= wfOpenElement('div', array('align'=>'center'));
			if($offset >= $max_relations){
				$response_html .= $previous_image_code;
			}
			if($next_offset != -1){
				$response_html .= $next_image_code;
			}
			$response_html .= wfCloseElement('div');
		}
		else{
			$response_html .= wfOpenElement('p').$egMOCA_txt['help_relation_no_relations_found'].wfCloseElement('p');
		}	
	
					$response_html .= wfCloseElement('td');
			$response_html .= wfCloseElement('tr');
		$response_html .= wfCloseElement('table');
		$response_html .=wfCloseElement('span');
		
		
		$response = new AjaxResponse();
		$response->addText( $response_html );
		//return the response
		return $response;
	
	}
	
	/**
	   * Loads the Property part of the QuickFixPanel checklist
	   * 
	   * @return AjaxResponse
	   */
	function getAjaxPropertyPanel($matched_properties, $matched_categories) {
		global $wgScriptPath, $egSMWIExtensionPath, $egSMWIImagePath, $egSMWICheckLargeImageName, $egSMWIWarnLargeImageName,
			$egSMWIQuickFixPanelDiv, $egSMWIShowPlainLinksToFixDiv, $egSMWIMinusImageName, $egSMWIArrowImageName, $egMOCA_txt;
		
		$global_bits_instance = new MOCA_GlobalBits();
		$search_functions_instace = new MOCA_SearchFunctions();
		$suggested_properties = $global_bits_instance->getCleanSuggestedPropertiesArray($matched_properties, $matched_categories);
		
		$check_image_code = wfOpenElement('img', array('src' => "{$wgScriptPath}/{$egSMWIExtensionPath}/{$egSMWIImagePath}/{$egSMWICheckLargeImageName}")).wfCloseElement('img');
		$warn_image_code = wfOpenElement('img', array('src' => "{$wgScriptPath}/{$egSMWIExtensionPath}/{$egSMWIImagePath}/{$egSMWIWarnLargeImageName}")).wfCloseElement('img');
		$minus_image_code = wfOpenElement('img', array('src' => "{$wgScriptPath}/{$egSMWIExtensionPath}/{$egSMWIImagePath}/{$egSMWIMinusImageName}")).wfCloseElement('img');
		$arrow_image_code = wfOpenElement('img', array('src' => "{$wgScriptPath}/{$egSMWIExtensionPath}/{$egSMWIImagePath}/{$egSMWIArrowImageName}")).wfCloseElement('img');

		$inner_response_html = wfOpenElement('table',array('style'=>'table-layout: fixed')).wfOpenElement('tr').wfOpenElement('td', array('width'=>'150px'));
		$response_html = '';
		if($matched_properties == null && sizeof($matched_properties) == 0 ){
			$image= $warn_image_code;
			$status_txt = $egMOCA_txt['help_quickfixpanel_noproperty'];
			$rec = $egMOCA_txt['help_quickfixpanel_noproperty_r'];
			$issue_code = 0;
		}
		else{
			if($matched_properties != null && sizeof($matched_properties) == 1 && sizeof($suggested_properties) == 0 ){
				$image = $check_image_code;
				$status_txt = $egMOCA_txt['help_quickfixpanel_oneproperty'];
				$rec = $egMOCA_txt['help_quickfixpanel_oneproperty_r'];
				$issue_code = 1;
			}
			elseif ($matched_properties != null && sizeof($matched_properties) >= 1 && sizeof($suggested_properties) == 0 ){
				$image = $check_image_code;
				$status_txt = $egMOCA_txt['help_quickfixpanel_morethanoneproperty'];
				$rec = $egMOCA_txt['help_quickfixpanel_morethanoneproperty_r'];
				$issue_code = 1;
			}
			elseif(sizeof($suggested_properties) > 0){
				$image = $warn_image_code;
				$status_txt = $egMOCA_txt['help_quickfixpanel_recommendedexists'];
				$rec = $egMOCA_txt['help_quickfixpanel_recommendedexists_r'];
				$issue_code = 2;
			}
			else
				return;
		}
		
		$inner_response_html .= 	wfOpenElement('p', array('class'=>'smwi_quickFixPanel_property_title')).
								$image."&nbsp;&nbsp;".$egMOCA_txt['help_quickfixpanel_property_header'].
									wfCloseElement('p').
							wfCloseElement('td').
							wfOpenElement('td').
								wfOpenElement('p', array('class'=>'smwi_quickFixPanel_property_text ')).
									"<b>Status: </b>".$status_txt."&nbsp;-&nbsp;".
									wfOpenElement('a', array('onclick'=>"smwi_help_show_popup_tip(\"{$egMOCA_txt['help_property_definition_head']}\",\"{$egMOCA_txt['help_property_definition']}\")", 'style'=>'cursor: pointer; font-weight: normal;')).
										$egMOCA_txt['help_property_definition_head'].
									wfCloseElement('a').
								wfCloseElement('p').
								wfOpenElement('p', array('class'=>'smwi_quickFixPanel_property_text ')).
									"<b>Recommendation: </b>".$rec.
								wfCloseElement('p').
							wfCloseElement('p').
							wfOpenElement('p', array('class'=>'smwi_in_box_content_b')).
								$arrow_image_code."&nbsp;&nbsp;".
								wfOpenElement('a', array('onclick'=>"smwisfFixPropertyIssue('{$egSMWIQuickFixPanelDiv}', '{$issue_code}')", 'style'=>'cursor: pointer;')).
									$egMOCA_txt['help_quickfixpanel_property_add_newproperty'].
								wfCloseElement('a').
							wfCloseElement('p');


		
		
		
		$response_html .= $inner_response_html.wfCloseElement('td').wfCloseElement('tr').wfCloseElement('table');

				
		//return the response
		return $response_html;
		$response = new AjaxResponse();
		$response->addText( $response_html );
		//return the response
		return $response;
	}
	
	/**
	   * Loads the Relation Fixing panel
	   * 
	   * @return AjaxResponse
	   */
	function getPropertyIssueQuickFixPanel ($issue_code ) {
		global $egSMWIWikiTextCookieName,$egSMWIQuickFixPanelDiv, $egMOCA_txt;
		$search_functions_instance = new MOCA_SearchFunctions;
		$cookie_content = "";
		$cookie_size = $_COOKIE[$egSMWIWikiTextCookieName."_size"];
		$i = 0;

		while($_COOKIE[$egSMWIWikiTextCookieName.$i] && $i< $cookie_size){
			$cookie_content.= $_COOKIE[$egSMWIWikiTextCookieName.$i];
			$i++;
		}
	
		$global_bits_instance = new MOCA_GlobalBits();
		$search_functions_instace = new MOCA_SearchFunctions();
		
		// find all matched categories
		$matched_categories = $search_functions_instance->findCategories( $cookie_content );
		// find all matched properties
		$matched_properties =  $search_functions_instance->findProperties( $cookie_content );
		
		
		$response_html = '';
		$inner_response_html = '';
		$suggested_properties = $global_bits_instance->getCleanSuggestedPropertiesArray($matched_properties, $matched_categories);
		$suggested_properties_html = MOCA_GlobalBits::getListHTML($suggested_properties);
		
		if($issue_code == 0){
			$inner_response_html_1 = wfOpenElement('p', array('class'=>'smwi_in_box_content')).
									"<b>{$egMOCA_txt['help_property_noproperty']}</b>{$egMOCA_txt['help_property_why_add']}<b>{$egMOCA_txt['help_property_recommendation']}</b>"
									.wfCloseElement('p');	
			$inner_response_html_2 =  $global_bits_instance->getAddPropertyMultiOptionHTML('smwi_in_box_title', 'smwi_add_property_title','smwi_qfp_add_property_radio', array('smwi_qfp_add_property_new', 'smwi_qfp_add_property_suggested', 'smwi_qfp_add_property_value'), 0, 'smwifAddPropertyButtonCompletedWrapper', $suggested_properties_html, "");
		}
		elseif($issue_code == 1){
			$inner_response_html_1 = wfOpenElement('p', array('class'=>'smwi_in_box_content')).
									"<b>{$egMOCA_txt['help_property_oneormore']}</b>{$egMOCA_txt['help_property_why_add']}<b>{$egMOCA_txt['help_property_recommendation']}</b>"
									.wfCloseElement('p');	
			$inner_response_html_2 =  $global_bits_instance->getAddPropertyMultiOptionHTML('smwi_in_box_title', 'smwi_add_property_title','smwi_qfp_add_property_radio', array('smwi_qfp_add_property_new', 'smwi_qfp_add_property_suggested', 'smwi_qfp_add_property_value'), 0, 'smwifAddPropertyButtonCompletedWrapper', $suggested_properties_html, "");
		}
		elseif($issue_code == 2){
			$inner_response_html_1 = wfOpenElement('p', array('class'=>'smwi_in_box_content')).
									"<b>{$egMOCA_txt['help_property_recommendedexists']}</b>{$egMOCA_txt['help_property_why_add']}<b>{$egMOCA_txt['help_property_recommendation']}</b>"
									.wfCloseElement('p');	
			$inner_response_html_2 =  $global_bits_instance->getAddPropertyMultiOptionHTML('smwi_in_box_title', 'smwi_add_property_title','smwi_qfp_add_property_radio', array('smwi_qfp_add_property_new', 'smwi_qfp_add_property_suggested', 'smwi_qfp_add_property_value'), 0, 'smwifAddPropertyButtonCompletedWrapper', $suggested_properties_html, "");
		}
		else{
			$inner_response_html_1 = "";
			$inner_response_html_2 = "";
			return;
		}
		
		
			//$response_html .= wfOpenElement('div',array('id'=>$egSMWIQuickFixPanelDiv));
			$response_html .= wfOpenElement('table', array( 'width'=>'100%', 'class'=>'smwi_quickFixPanel_issuesfix_table'));
				$response_html .= wfOpenElement('tr', array('width'=>'100%'));
					$response_html .= wfOpenElement('td', array('width'=>'100%', 'style'=>'vertical-align:top;'));
						$response_html .= wfOpenElement('p', array('class'=>'smwi_quickFixPanel_moreInformation_title')).$egMOCA_txt['help_quickfixpanel_moreinformation'].wfCloseElement('p');
						$response_html .= $inner_response_html_1;
					$response_html .= wfCloseElement('td');
				$response_html .= wfCloseElement('tr');
				$response_html .= wfOpenElement('hr').wfCloseElement('hr');
				$response_html .= wfOpenElement('tr');
					$response_html .= wfOpenElement('td', array('width'=>'100%', 'style'=>'vertical-align:top;'));
						$response_html .= wfOpenElement('p', array('class'=>'smwi_quickFixPanel_moreInformation_title')).$egMOCA_txt['help_quickfixpanel_add_propertywizard'].wfCloseElement('p');
						$response_html .=  $inner_response_html_2;
					$response_html .= wfCloseElement('td');
				$response_html .= wfCloseElement('tr');
			$response_html .= wfCloseElement('table');
			//$response_html .= wfCloseElement('div');
	
		
		
		$response = new AjaxResponse();
		$response->addText( $response_html );
		//return the response
		return $response;
	
	}
}

?>