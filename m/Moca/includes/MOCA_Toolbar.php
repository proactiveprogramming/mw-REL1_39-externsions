<?php

/**
  * File: MOCA_Toolbar.php
  *
  * Description: Contains methods that help create the SMWI toolbar interface
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
  class MOCA_Toolbar{
	
	/**
	   * Loads the Add category box for the semantic wizard
	   * 
	   * @return AjaxResponse
	   */
	function getAjaxCategoryBox( $pos, $category ) {
		global $egSMWIWikiTextCookieName;
		$search_functions_instance = new MOCA_SearchFunctions;
		$global_bits_instance = new MOCA_GlobalBits;
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
		
		// get all the categories available
		$all_categories = $search_functions_instance->getAllCategories();
		
		// remove all categories from full list of categories if the already exist in the wiki text
		if( sizeof($matched_categories )>0  && $matched_categories != null){
			if(sizeof($all_categories) > 0 && $all_categories != null){

				$all_categories = array_diff( $all_categories, $matched_categories );
				
			}
		}
		
		$all_categories_html = MOCA_GlobalBits::getListHTML($all_categories);
		
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
				$just_matched_plain_links[] = $mpl['page'];
			}
		}
		
		$suggested_categories = $global_bits_instance->getCleanSuggestedCategoriesArray($matched_categories, $matched_properties, $matched_clean_dpages);
		
		$suggested_categories_html = MOCA_GlobalBits::getListHTML($suggested_categories);

		$response_html = '';
		$response_html .= wfOpenElement('table', array('class' => 'smwi_add_category_toolbar_table'));
			$response_html .= wfOpenElement('tr');
				$response_html .= wfOpenElement('td', array('width' => '50%', 'style'=>'vertical-align: top;'));
					$response_html .= $global_bits_instance->getAddCategoryMultiOptionHTML('smwi_add_category_title','smwi_add_category_radio', array('smwi_add_category_new', 'smwi_add_category_suggested','smwi_add_category_all'), $pos, 'smwifAddCategoryButtonCompletedWrapper', $suggested_categories_html, $all_categories_html, $category);
					$response_html .= wfOpenElement('button', array('type'=>'button', 'onclick' => "smwifCancelSemanticElementBox()"))."Cancel Adding Category".wfCloseElement('button');
				$response_html .= wfCloseElement('td');
				$response_html .= wfOpenElement('td', array('width' => '50%','align' => 'center', 'style'=>'vertical-align: top;'));
				$response_html .= wfCloseElement('td');
			$response_html .= wfCloseElement('tr');
		$response_html .= wfCloseElement('table');
		
		$response = new AjaxResponse();
		$response->addText( $response_html );
		//return the response
		return $response;
		
	}
	
	/**
	   * Loads status message of whether the category addition was completed successfully or not
	   * 
	   * @return AjaxResponse
	   */
	function getAjaxCategoryBoxCompletion( $message, $type ){
		global $egSMWICheckImageName,$egSMWIWarnImageName, $wgScriptPath, $egSMWIExtensionPath, $egSMWIImagePath;

		if($type == 0){
			$class = 'smwi_added_failed';
			$img_name = $egSMWIWarnImageName;
		}
		else {
			$class = 'smwi_added_successfully';
			$img_name = $egSMWICheckImageName;
		}
		$response_html = '';
		$response_html .= wfOpenElement('table', array('class' => 'smwi_add_category_success_toolbar_table'));
			$response_html .= wfOpenElement('tr');
				$response_html .= wfOpenElement('td', array( 'style'=>'vertical-align: top;'));
					$response_html .= wfOpenElement('p', array('class' => $class)).wfOpenElement('img', array('src' => "{$wgScriptPath}/{$egSMWIExtensionPath}/{$egSMWIImagePath}/{$img_name}")).wfCloseElement('img').$message.wfCloseElement('p');
				$response_html .= wfCloseElement('td');
			$response_html .= wfCloseElement('tr');
		$response_html .= wfCloseElement('table');
		$response = new AjaxResponse();
		$response->addText( $response_html );
		//return the response
		return $response;
	}
	
	/**
	   * Loads the Add relation box for the semantic wizard
	   * 
	   * @return AjaxResponse
	   */
	function getAjaxRelationBox ( $pos, $link) {
		global $egSMWIWikiTextCookieName, $egSMWIEditorTextboxID;

		// create an instanse of the MOCA_SearchFunctions class which includes all the necessary functions for searching the database and wikitext
		$search_functions_instance = new MOCA_SearchFunctions;
		$global_bits_instance = new MOCA_GlobalBits;
		
		$cookie_content = "";
		$cookie_size = $_COOKIE[$egSMWIWikiTextCookieName."_size"];
		$i = 0;

		while($_COOKIE[$egSMWIWikiTextCookieName.$i] && $i< $cookie_size){
			$cookie_content.= $_COOKIE[$egSMWIWikiTextCookieName.$i];
			$i++;
		}
		$suggested_relations_html = "";
		
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
		
	
		$response_html = '';
		$response_html .= wfOpenElement('table', array('class' => 'smwi_add_relation_toolbar_table'));
			$response_html .= wfOpenElement('tr');
				$response_html .= wfOpenElement('td', array('width' => '45%', 'style'=>'vertical-align: top;'));
					$response_html .= $global_bits_instance->getAddRelationMultiOptionHTML('smwi_in_box_title','smwi_add_relation_title', 'smwi_add_relation_radio', array('smwi_add_relation_new', 'smwi_add_relation_suggested', 'smwi_add_relation_value', 'smwi_add_relation_alt'), $pos, 'smwifAddRelationButtonCompletedWrapper', $suggested_relations_html, $link, 0);
					$response_html .= wfOpenElement('button', array('type'=>'button', 'onclick' => "smwifCancelSemanticElementBox()"))."Cancel Adding Relation".wfCloseElement('button');
				$response_html .= wfCloseElement('td');
			$response_html .= wfCloseElement('tr');
		$response_html .= wfCloseElement('table');
		
		$response = new AjaxResponse();
		$response->addText( $response_html );
		//return the response
		return $response;
	
	}

	/**
	   * Loads status message of whether the relation addition was completed successfully or not
	   * 
	   * @return AjaxResponse
	   */
	function getAjaxRelationBoxCompletion( $message, $type ){
		global $egSMWICheckImageName,$egSMWIWarnImageName, $wgScriptPath, $egSMWIExtensionPath, $egSMWIImagePath;
		
		if($type == 0){
			$class = 'smwi_added_failed';
			$img_name = $egSMWIWarnImageName;
		}
		else {
			$img_name = $egSMWICheckImageName;
			$class = 'smwi_added_successfully';
		}
		$response_html = '';
		$response_html .= wfOpenElement('table', array('class' => 'smwi_add_relation_success_toolbar_table'));
			$response_html .= wfOpenElement('tr');
				$response_html .= wfOpenElement('td', array( 'style'=>'vertical-align: top;'));
					$response_html .= wfOpenElement('p', array('class' => $class)).wfOpenElement('img', array('src' => "{$wgScriptPath}/{$egSMWIExtensionPath}/{$egSMWIImagePath}/{$img_name}")).wfCloseElement('img').$message.wfCloseElement('p');
				$response_html .= wfCloseElement('td');
			$response_html .= wfCloseElement('tr');
		$response_html .= wfCloseElement('table');
		$response = new AjaxResponse();
		$response->addText( $response_html );
		//return the response
		return $response;
	}
	
	/**
	   * Loads the Add property box for the semantic wizard
	   * 
	   * @return AjaxResponse
	   */
	function getAjaxPropertyBox( $pos, $property_value )  {
		global $egSMWIWikiTextCookieName;
		$search_functions_instance = new MOCA_SearchFunctions;
		$global_bits_instance = new MOCA_GlobalBits;
		$property_value  = preg_replace ("[\s]", "_", $property_value);
		

		$cookie_content = "";
		$cookie_size = $_COOKIE[$egSMWIWikiTextCookieName."_size"];
		$i = 0;

		while($_COOKIE[$egSMWIWikiTextCookieName.$i] && $i< $cookie_size){
			$cookie_content.= $_COOKIE[$egSMWIWikiTextCookieName.$i];
			$i++;
		}
		$matched_categories = $search_functions_instance->findCategories($cookie_content);
		$matched_properties = $search_functions_instance->findProperties($cookie_content);
		$suggested_properties =  $global_bits_instance->getCleanSuggestedPropertiesArray($matched_properties,$matched_categories);
	
		$suggested_properties_html = MOCA_GlobalBits::getListHTML($suggested_properties);
		
		
		$response_html = '';
		$response_html .= wfOpenElement('table', array('class' => 'smwi_add_property_toolbar_table'));
			$response_html .= wfOpenElement('tr');
				$response_html .= wfOpenElement('td', array('width' => '45%', 'style'=>'vertical-align: top;'));
					$response_html .= $global_bits_instance->getAddPropertyMultiOptionHTML('smwi_in_box_title','smwi_add_property_title', 'smwi_add_property_radio', array('smwi_add_property_new', 'smwi_add_property_suggested', 'smwi_add_property_value'), $pos, 'smwifAddPropertyButtonCompletedWrapper', $suggested_properties_html, $property_value);
					$response_html .= wfOpenElement('button', array('type'=>'button', 'onclick' => "smwifCancelSemanticElementBox()"))."Cancel Adding property".wfCloseElement('button');
				$response_html .= wfCloseElement('td');
			$response_html .= wfCloseElement('tr');
		$response_html .= wfCloseElement('table');
		
		$response = new AjaxResponse();
		$response->addText( $response_html );
		//return the response
		return $response;
	}
	
	/**
	   * Loads status message of whether the property addition was completed successfully or not
	   * 
	   * @return AjaxResponse
	   */
	function getAjaxPropertyBoxCompletion( $message, $type ){
		global $egSMWICheckImageName,$egSMWIWarnImageName, $wgScriptPath, $egSMWIExtensionPath, $egSMWIImagePath;
		
		if($type == 0){
			$class = 'smwi_added_failed';
			$img_name = $egSMWIWarnImageName;
		}
		else {
			$img_name = $egSMWICheckImageName;
			$class = 'smwi_added_successfully';
		}
		$response_html = '';
		$response_html .= wfOpenElement('table', array('class' => 'smwi_add_property_success_toolbar_table'));
			$response_html .= wfOpenElement('tr');
				$response_html .= wfOpenElement('td', array( 'style'=>'vertical-align: top;'));
					$response_html .= wfOpenElement('p', array('class' => $class)).wfOpenElement('img', array('src' => "{$wgScriptPath}/{$egSMWIExtensionPath}/{$egSMWIImagePath}/{$img_name}")).wfCloseElement('img').$message.wfCloseElement('p');
				$response_html .= wfCloseElement('td');
			$response_html .= wfCloseElement('tr');
		$response_html .= wfCloseElement('table');
		$response = new AjaxResponse();
		$response->addText( $response_html );
		//return the response
		return $response;
	}
	
	/**
	   * Returns a list of suggested property values for the selected property name
	   * 
	   * @return AjaxResponse
	   */
	function getAjaxPropertyGetValue( $property, $property_textbox ) {
		$search_functions_instance = new MOCA_SearchFunctions;
		$global_bits_instance = new MOCA_GlobalBits;
		$values = $global_bits_instance->getListHTML($search_functions_instance->findSuggestedPropertyValuesForSingleProperty( $property)); 
		$response_html = "";
		$response_html .="&nbsp;&nbsp;Property value suggestions: ".wfElement('select', array('id'=>'smwi_suggested_property_value_selection', 'onmousedown'=>"smwiPropertySuggestedPropertySelected('smwi_suggested_property_value_selection', '{$property_textbox}')")).$values.wfCloseElement('select');
		
		$response = new AjaxResponse();
		$response->addText( $response_html );
		return $response;
	}
	
	/**
	   * Returns a list of suggested destination pages for the selected relation type
	   * 
	   * @return AjaxResponse
	   */
	function getAjaxRelationGetValue($relation, $relation_textbox){
		$search_functions_instance = new MOCA_SearchFunctions;
		$global_bits_instance = new MOCA_GlobalBits;
		$values = $global_bits_instance->getListHTML($search_functions_instance->findSuggestedDPagesForSingleRelation( $relation)); 
		$response_html = "";
		$response_html .= "&nbsp;&nbsp;Destination page suggestions: ".wfElement('select', array('id'=>'smwi_suggested_relation_value_selection', 'onmousedown'=>"smwiPropertySuggestedRelationSelected('smwi_suggested_relation_value_selection', '{$relation_textbox}')")).$values.wfCloseElement('select');
		
		$response = new AjaxResponse();
		$response->addText( $response_html );
		return $response;
	}
}
?>