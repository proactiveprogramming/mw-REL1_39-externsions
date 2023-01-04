<?php
/**
  * File: MOCA_Global_Bits.php
  *
  * Description: Contains general PHP methods used allover the MOCA extension
  *
  * @author Chrysovalanto Kousetti
  * @email valanto@gmail.com
  * 
  */

# Checks mediawiki exists
if( !defined( 'MEDIAWIKI' ) ) {
	echo( "This file is an extension to the MediaWiki software and cannot be used standalone.\n" );
	die( 1 );
}

class MOCA_GlobalBits{

	/**
	 * Takes a list of categories and constructs the html for placing them into a drop down box
	 *
	 * @params Array
	 * @return String
	 */
	static function getListHTML( $values ) {
		$values_html = '';
		if(sizeof($values) > 0 && $values != null){
			foreach($values as $v){
					$values_html .= wfOpenElement('option', array('value'=> $v)).$v.wfCloseElement('option');
			}
		}
		return $values_html;
	}
	
	
	/**
	 * Takes an article name and return's it wiki URL
	 *
	 * @params String, int
	 * @return String
	 */
	static function getArticleURL ($article, $type = 0){
		global $wgArticlePath;
		
		$length = strlen($wgArticlePath) - 3;
		$articlePath = substr($wgArticlePath, 0, $length).'?title=';
		
		if($type == 0)	return $articlePath.$article;
		else if ($type == 1) return $articlePath."Category:".$article;
		else if($type == 2) return $articlePath."Attribute:".$article;
		else return '#';
	}
	
	/**
	 *  Returns a clean categories suggestions array
	 *
	 * @params Array, Array, Array
	 * @return Array
	 */
	function getCleanSuggestedCategoriesArray($matched_categories, $matched_properties, $matched_clean_dpages){
		$search_functions_instance = new MOCA_SearchFunctions;
		// get the category suggestions
		$suggested_categories = $search_functions_instance->findSuggestedCategoriesForPropertyArray( $matched_properties );
		$suggested_categories_by_dpages = $search_functions_instance->findSuggestedCategoriesFordPagesArray( $matched_clean_dpages );
		
		if(sizeof($suggested_categories) > 0 && $suggested_categories != null){
			if(sizeof($suggested_categories_by_dpages) > 0 && $suggested_categories_by_dpages != null){
				$suggested_categories = array_unique(array_merge( $suggested_categories, $suggested_categories_by_dpages, array_diff($suggested_categories, $suggested_categories_by_dpages) ));
			}
		}
		else if(sizeof($suggested_categories_by_dpages) > 0 && $suggested_categories_by_dpages != null){
				$suggested_categories = $suggested_categories_by_dpages;
		}
		
		// remove all categories from the list of suggested categories if the already exist in the wiki text
		if( sizeof($matched_categories )>0  && $matched_categories != null){
			if(sizeof($suggested_categories) > 0 && $suggested_categories != null){
				$suggested_categories = array_diff( $suggested_categories, $matched_categories );
			}
		}
		
		return $suggested_categories;
	
	}
	
	/**
	 *  Returns a clean relations suggestions array
	 *
	 * @params Array, Array, Array
	 * @return Array
	 */
	function getCleanSuggestedRelationsArray($matched_categories, $matched_plain_links, $matched_relations){
		$search_functions_instance = new MOCA_SearchFunctions;
	
		if($matched_plain_links!= null && sizeof($matched_plain_links)>0){
			foreach($matched_plain_links as $mpl){
				
				$just_matched_plain_links[] = $mpl['page'];
			}
		}
		
		$suggested_relations_by_category = $search_functions_instance->findSuggestedRelationsForSCategories( $matched_categories );
		
		$suggested_relations_by_links = $search_functions_instance->findSuggestedRelationsForDPages( $just_matched_plain_links );
		
		$sug_relations_array_1 = array();
		if($matched_relations == null) $matched_relations = array();
		// constructs an array with the relations and the categories they are linked to.
		if( sizeof($suggested_relations_by_category )>0  && $suggested_relations_by_category != null){
			foreach($suggested_relations_by_category as $sr=>$cat){
				if (!in_array($sr, $matched_relations)) {
					$sug_relations_array_1[] = $sr;
				}
			}
		}
		
		$sug_relations_array_2 = array();
		// constructs an array with the relations and the categories they are linked to.
		if( sizeof($suggested_relations_by_links )>0  && $suggested_relations_by_links != null){
			foreach($suggested_relations_by_links as $sr=>$cat){
				if (!in_array($sr, $matched_relations)) {
					$sug_relations_array_2[] = $sr;
				}
			}
		}

		$suggested_relations = null;
		// remove all properties from the list of suggested properties if the already exist in the wiki text
		if( sizeof(($sug_relations_array_1 )>0  && $sug_relations_array_1 != null) || (sizeof($sug_relations_array_2) > 0 && $sug_relations_array_2 != null)){
			$suggested_relations = array_unique(array_merge( $sug_relations_array_1, $sug_relations_array_2, array_diff($sug_relations_array_1, $sug_relations_array_2) ));
		}
		
		
		return $suggested_relations;
	}
	
	/**
	 *  Returns a clean properties suggestions array
	 *
	 * @params Array, Array
	 * @return Array
	 */
	function getCleanSuggestedPropertiesArray($matched_properties, $matched_categories){
		$suggested_properties = null;
		$search_functions_instance = new MOCA_SearchFunctions;
		// get the category suggestions
		$suggested_properties = $search_functions_instance->findSuggestedPropertiesForCategoryArray( $matched_categories );
		
		// remove all categories from the list of suggested categories if the already exist in the wiki text
		if( sizeof($matched_properties )>0  && $matched_properties != null){
			if(sizeof($suggested_properties) > 0 && $suggested_properties != null){
				$suggested_properties = array_diff( $suggested_properties, $matched_properties );
			}
		}
		return $suggested_properties;
	}

	/**
	 *  Returns HTML to display Adding a category
	 *
	 * @params String, String, String, int, String, String, String, String
	 * @return String
	 */
	function getAddCategoryMultiOptionHTML($p_class, $radio_id, $input_id, $pos, $function, $suggested_categories_html, $all_categories_html, $category){
		global $egMOCA_txt;
		if(sizeof($input_id)<3) return "";
		if(  $suggested_categories_html == ""){
			 $suggested_categories_html = $egMOCA_txt['help_no_cat_suggestions_available'];
		}
		else{
			$suggested_categories_html = wfOpenElement('select', array('id'=> $input_id[1], 'onclick'=>"smwiCategoryAddOptionChanged('{$radio_id}',1)")).$suggested_categories_html.wfCloseElement('select');
		}
		
		if(  $all_categories_html == ""){
			 $all_categories_html = $egMOCA_txt['help_no_cat_available'];
		}
		else{
			$all_categories_html = wfOpenElement('select', array('id'=> $input_id[2], 'onclick'=>"smwiCategoryAddOptionChanged('{$radio_id}',2)")).$all_categories_html.wfCloseElement('select');
		}
		
		$response_html = '';
		$response_html .= wfOpenElement('p').wfOpenElement('span', array('class'=>'smwi_must_fields')).$egMOCA_txt['star'].wfCloseElement('span').wfOpenElement('span', array('class'=>'smwi_in_box_title')).$egMOCA_txt['help_add_category_methods'].wfCloseElement('span').wfOpenElement('span', array('class'=>'smwi_in_box_content'))." - ".wfOpenElement('a', array('onclick'=>"smwi_help_show_popup_tip(\"{$egMOCA_txt['help_category_definition_head']}\",\"{$egMOCA_txt['help_category_definition']}\")", 'style'=>'cursor: pointer;')).$egMOCA_txt['help_category_definition_head'].wfCloseElement('a').wfCloseElement('span').wfCloseElement('p');
		$response_html .= wfOpenElement('p', array('class'=>$p_class)).wfOpenElement('input',array('id'=>$radio_id, 'name'=>$radio_id, 'value'=> 0, 'type'=>'radio', 'checked'=>true)).$egMOCA_txt['help_type_in_category'].":&nbsp;&nbsp;".wfOpenElement('input', array('id'=> $input_id[0], 'value'=>$category,'type'=>'textbox', 'maxlength'=>'50', 'size'=>'15', 'onclick'=>"smwiCategoryAddOptionChanged('{$radio_id}',0)")).wfCloseElement('input').wfCloseElement('p').wfCloseElement('input');
		$response_html .= wfOpenElement('p', array('class'=>$p_class)).wfOpenElement('input',array('id'=>$radio_id, 'name'=>$radio_id, 'value'=> 1, 'type'=>'radio')).$egMOCA_txt['help_wizard_select_category_suggestions'] .":&nbsp;&nbsp;".$suggested_categories_html.wfCloseElement('p').wfCloseElement('input');
		$response_html .= wfOpenElement('p', array('class'=>$p_class)).wfOpenElement('input',array('id'=>$radio_id, 'name'=>$radio_id, 'value'=> 2, 'type'=>'radio')).$egMOCA_txt['help_wizard_select_category_fromall'].":&nbsp;&nbsp;".$all_categories_html.wfCloseElement('p').wfCloseElement('input');
		$response_html .= wfOpenElement('hr').wfCloseElement('hr');
		$response_html .= wfOpenElement('p').wfOpenElement('span', array('class'=>'smwi_must_fields')).$egMOCA_txt['star'].wfCloseElement('span').wfOpenElement('span', array('class'=>'smwi_in_box_content_comment')).$egMOCA_txt['must_complete'].wfCloseElement('span').wfCloseElement('p');
		$response_html .= wfOpenElement('button', array('type'=>'button', 'onclick' => "smwifAddThisCategoryPressed('{$radio_id}','{$input_id[0]}', '{$input_id[1]}', '{$input_id[2]}', '{$pos}', '{$category}', '{$function}')")).$egMOCA_txt['help_wizard_add_category'].wfCloseElement('button');

		return $response_html;
	}
	
	/**
	 *  Returns HTML to display Adding a relation
	 *
	 * @params String, String, String, String, int, String, String, String, (int), (String), (String), (Boolean)
	 * @return String
	 */
	function getAddRelationMultiOptionHTML($h_class, $p_class, $radio_id, $input_id, $pos, $function, $suggested_relations_html, $link, $length=-1, $alt = "", $relation="", $hasBrackets=false){
		global $egMOCA_txt;
		
		if($length==-1) $length = strlen($link);
		if(sizeof($input_id)<3) return "";
		if(  $suggested_relations_html == ""){
			 $suggested_relations_html = $egMOCA_txt['help_no_rel_available'];
		}
		else{
			$suggested_relations_html = wfOpenElement('select', array('id'=> $input_id[1], 'onclick'=>"smwiRelationAddOptionChanged('{$radio_id}',1)", 'onmousedown'=>"smwiRelationAddOptionChangedRel('{$input_id[1]}', 'smwi_relation_value_suggestions_div', '{$input_id[2]}')")).$suggested_relations_html.wfCloseElement('select').
								wfOpenElement('span', array('id'=>'smwi_relation_value_suggestions_div')).wfCloseElement('span');
		}
		$response_html = '';
		$response_html .= wfOpenElement('p').wfOpenElement('span', array('class'=>'smwi_must_fields')).$egMOCA_txt['star'].wfCloseElement('span').wfOpenElement('span', array('class'=>$h_class)).$egMOCA_txt['help_add_relation_methods'].wfCloseElement('span').wfOpenElement('span', array('class'=>'smwi_in_box_content'))." - ".wfOpenElement('a', array('onclick'=>"smwi_help_show_popup_tip(\"{$egMOCA_txt['help_relation_definition_head']}\",\"{$egMOCA_txt['help_relation_definition']}\")", 'style'=>'cursor: pointer;')).$egMOCA_txt['help_relation_definition_head'].wfCloseElement('a').wfCloseElement('span').wfCloseElement('p');
		$response_html .= wfOpenElement('p', array('class'=>$p_class)).wfOpenElement('input',array('id'=>$radio_id, 'name'=>$radio_id, 'value'=> 0, 'type'=>'radio', 'checked'=>true)).$egMOCA_txt['help_type_in_relation'].":&nbsp;&nbsp;".wfOpenElement('input', array('id'=> $input_id[0],'type'=>'textbox', 'maxlength'=>'50', 'size'=>'15', 'value'=>$relation, 'onclick'=>"smwiRelationAddOptionChanged('{$radio_id}',0)")).wfCloseElement('input').wfCloseElement('p').wfCloseElement('input');
		$response_html .= wfOpenElement('p', array('class'=>$p_class)).wfOpenElement('input',array('id'=>$radio_id, 'name'=>$radio_id, 'value'=> 1, 'type'=>'radio')).$egMOCA_txt['help_wizard_select_relation_suggestions'].":&nbsp;&nbsp;".$suggested_relations_html.wfCloseElement('p').wfCloseElement('input');
		$response_html .= wfOpenElement('hr').wfCloseElement('hr');
		$response_html .= wfOpenElement('p').wfOpenElement('span', array('class'=>$h_class)).$egMOCA_txt['help_wizard_select_dpage'].wfCloseElement('span').wfOpenElement('span', array('class'=>'smwi_in_box_content'))." - ".wfOpenElement('a', array('onclick'=>"smwi_help_show_popup_tip(\"{$egMOCA_txt['help_dpage_definition_head']}\",\"{$egMOCA_txt['help_dpage_definition']}\")", 'style'=>'cursor: pointer;'))."What is a destination page?".wfCloseElement('a').wfCloseElement('span').wfCloseElement('p');
		$response_html .= wfOpenElement('p', array('class'=>$p_class)).wfOpenElement('span', array('class'=>'smwi_must_fields')).$egMOCA_txt['star'].wfCloseElement('span').$egMOCA_txt['help_typein_dpage'].":&nbsp;&nbsp;".wfOpenElement('input', array('id'=> $input_id[2],'type'=>'textbox', 'maxlength'=>'50', 'size'=>'15', 'value' => $link)).wfCloseElement('input').wfCloseElement('p');
		$response_html .= wfOpenElement('hr').wfCloseElement('hr');
		$response_html .= wfOpenElement('p').wfOpenElement('span', array('class'=>$h_class)).$egMOCA_txt['help_wizard_add_alt'].wfCloseElement('span').wfOpenElement('span', array('class'=>'smwi_in_box_content'))." - ".wfOpenElement('a', array('onclick'=>"smwi_help_show_popup_tip(\"{$egMOCA_txt['help_alt_definition_head']}\",\"{$egMOCA_txt['help_alt_definition']}\")", 'style'=>'cursor: pointer;'))."What is an alternative text?".wfCloseElement('a').wfCloseElement('span').wfCloseElement('p');
		$response_html .= wfOpenElement('p', array('class'=>$p_class)).$egMOCA_txt['help_typein_alt'].":&nbsp;&nbsp;".wfOpenElement('input', array('id'=> $input_id[3],'type'=>'textbox', 'maxlength'=>'50', 'size'=>'15', 'value'=>$alt)).wfCloseElement('input').wfCloseElement('p');
		$response_html .= wfOpenElement('hr').wfCloseElement('hr');
		$response_html .= wfOpenElement('p').wfOpenElement('span', array('class'=>'smwi_must_fields')).$egMOCA_txt['star'].wfCloseElement('span').wfOpenElement('span', array('class'=>'smwi_in_box_content_comment')).$egMOCA_txt['must_complete'].wfCloseElement('span').wfCloseElement('p');
		$response_html .= wfOpenElement('button', array('type'=>'button', 'onclick' => "smwifAddThisRelationPressed('{$radio_id}','{$input_id[0]}', '{$input_id[1]}', '{$input_id[2]}', '{$input_id[3]}','{$pos}', '{$link}', {$length}, '{$function}', '{$hasBrackets}')")).$egMOCA_txt['help_wizard_add_relation'].wfCloseElement('button');

		return $response_html;
	}
	
	/**
	 *  Returns HTML to display Adding a category
	 *
	 * @params String, String, String, int, String, String, String
	 * @return String
	 */
	function getAddPropertyMultiOptionHTML($h_class, $p_class, $radio_id, $input_id, $pos, $function, $suggested_properties_html, $value){
		global $egMOCA_txt;
		if(sizeof($input_id)<3) return "";
		if(  $suggested_properties_html == ""){
			 $suggested_properties_html = $egMOCA_txt['help_no_prop_available'];
		}
		else{
			$suggested_properties_html = wfOpenElement('select', array('id'=> $input_id[1], 'onclick'=>"smwiPropertyAddOptionChanged('{$radio_id}',1)", 'onmousedown'=>"smwiPropertyAddOptionChangedProp('{$input_id[1]}', 'smwi_property_value_suggestions_div', '{$input_id[2]}')")).$suggested_properties_html.wfCloseElement('select').
								wfOpenElement('span', array('id'=>'smwi_property_value_suggestions_div')).wfCloseElement('span');
		}
		
		$response_html = '';
		$response_html .= wfOpenElement('p').wfOpenElement('span', array('class'=>'smwi_must_fields')).$egMOCA_txt['star'].wfCloseElement('span').wfOpenElement('span', array('class'=>$h_class)).$egMOCA_txt['help_wizard_select_property_suggestions'].wfCloseElement('span').wfOpenElement('span', array('class'=>'smwi_in_box_content'))." - ".wfOpenElement('a', array('onclick'=>"smwi_help_show_popup_tip(\"{$egMOCA_txt['help_property_definition_head']}\",\"{$egMOCA_txt['help_property_definition']}\")", 'style'=>'cursor: pointer;')).$egMOCA_txt['help_property_definition_head'].wfCloseElement('a').wfCloseElement('span').wfCloseElement('p');
		$response_html .= wfOpenElement('p', array('class'=>$p_class)).wfOpenElement('input',array('id'=>$radio_id, 'name'=>$radio_id, 'value'=> 0, 'type'=>'radio', 'checked'=>true)).$egMOCA_txt['help_type_in_relation'].":&nbsp;&nbsp;".wfOpenElement('input', array('id'=> $input_id[0],'type'=>'textbox', 'maxlength'=>'50', 'size'=>'15', 'onclick'=>"smwiPropertyAddOptionChanged('{$radio_id}',0)")).wfCloseElement('input').wfCloseElement('p').wfCloseElement('input');
		$response_html .= wfOpenElement('p', array('class'=>$p_class)).wfOpenElement('input',array('id'=>$radio_id, 'name'=>$radio_id, 'value'=> 1, 'type'=>'radio')).$egMOCA_txt['help_select_property_suggestion'].":&nbsp;&nbsp;".$suggested_properties_html.wfCloseElement('p').wfCloseElement('input');
		$response_html .= wfOpenElement('hr').wfCloseElement('hr');
		$response_html .= wfOpenElement('p').wfOpenElement('span', array('class'=>$h_class)).$egMOCA_txt['help_wizard_select_pvalue'].wfCloseElement('span').wfOpenElement('span', array('class'=>'smwi_in_box_content'))." - ".wfOpenElement('a', array('onclick'=>"smwi_help_show_popup_tip(\"{$egMOCA_txt['help_propertyvalue_definition_head']}\",\"{$egMOCA_txt['help_propertyvalue_definition']}\")", 'style'=>'cursor: pointer;')).$egMOCA_txt['help_propertyvalue_definition_head'].wfCloseElement('a').wfCloseElement('span').wfCloseElement('p');
		$response_html .= wfOpenElement('p', array('class'=>$p_class)).wfOpenElement('span', array('class'=>'smwi_must_fields')).$egMOCA_txt['star'].wfCloseElement('span').$egMOCA_txt['help_type_in_pvalue'].":&nbsp;&nbsp;".wfOpenElement('input', array('id'=> $input_id[2],'type'=>'textbox', 'maxlength'=>'50', 'size'=>'15', 'value' => $value)).wfCloseElement('input').wfCloseElement('p');
		$response_html .= wfOpenElement('hr').wfCloseElement('hr');
		$response_html .= wfOpenElement('p').wfOpenElement('span', array('class'=>'smwi_must_fields')).$egMOCA_txt['star'].wfCloseElement('span').wfOpenElement('span', array('class'=>'smwi_in_box_content_comment')).$egMOCA_txt['must_complete'].wfCloseElement('span').wfCloseElement('p');
		$response_html .= wfOpenElement('button', array('type'=>'button', 'onclick' => "smwifAddThisPropertyPressed('{$radio_id}','{$input_id[0]}', '{$input_id[1]}', '{$input_id[2]}', '{$pos}', '{$value}', '{$function}')"))."Add Property".wfCloseElement('button');
		return $response_html;
	}
	
	
}

?>