<?php

/**
  * File: MOCA_Search_Functions.php
  *
  * Description: Contains the methods used by other file that involve quering the
  * 			wiki database.
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

class MOCA_SearchFunctions{

	/**
	 * Searches the wikitext for ask declarations and returns an array of twhat is found them.
	 *
	 * @params String
	 * @return Array
	 */
	function findAsk( $text ){
		$ask_result = preg_match_all("/\\<ask(.|\n)*?\\>(.|\n)*?\\<\/ask\\>/", $text, $ask_matches, PREG_OFFSET_CAPTURE); 
		if( $ask_result ) {
			$ask_matched_inst = array();
			$first = true;
			foreach( $ask_matches as $element ){
				if($first){
					$first = false;
					foreach($element as $m){
						$ask_matched_inst [] = array(
											'start'=>$m[1],
											'end'=>(strlen($m[0]) + $m[1])
										);
					}
				} 
			}
		}
		else {
			$ask_matched_inst = array();
		}
		return $ask_matched_inst;
	}
	
	/**
	 * Decides whether you should escape a specific semantic declaration
	 *
	 * @params Array, int, int
	 * @return Boolean
	 */
	function shouldEscape($ask_matched_inst, $start, $end ) {
		$escaped = false;
		if(sizeof($ask_matched_inst) > 0){
			
			foreach($ask_matched_inst as $ask_inst){
				//print_r($ask_inst['start']." - ".$ask_inst['end']."<br />".$m[1]." - ".strlen($m[0]));
				if($start>=$ask_inst['start'] && $end < $ask_inst['end']){
					$escaped = true;
				}
			}
		}
		return $escaped;
	}
	
	
	/**
	 * Searches the wikitext for category declarations and returns an array of twhat is found them.
	 *
	 * @params String
	 * @return Array
	 */
	function findCategories( $text ) {
		// match all categories ^(?!\\<ask\\>)
		//\[\[([\s]*)Category:([\s]*)([^\]]+)([\s]*)\]\]
		//$result = preg_match_all( "/^?(?=(\\<ask\\>))(?(?=(*\\<\/ask\\>))(*\[\[([\s]*)Category:([\s]*)([^\]]+)([\s]*)\]\]))(\[\[([\s]*)Category:([\s]*)([^\]]+)([\s]*)\]\])/", $text, $matches ); // [[Category:Project]]
		$result = preg_match_all( "/\[\[([\s]*)Category:([\s]*)([^\]]+)([\s]*)\]\]/i", $text, $matches, PREG_OFFSET_CAPTURE ); // [[Category:Project]]
		$ask_matched_inst = $this->findAsk( $text );
		
		// clean the result and create categories array
		if( $result ) {
			$clean_matches = array();
			$first = true;
			foreach( $matches as $element ){
				if($first){
					$first = false;

					foreach($element as $m){
						$escaped = $this->shouldEscape($ask_matched_inst, $m[1], ($m[1]+strlen($m[0])));
						if(!$escaped){
							// remove start tags
							$match = preg_replace( "/\[\[([\s]*)Category:([\s]*)/i", "", $m[0] );
							// remove end tags
							$match = preg_replace( "/([\s]*)\]\]/", "", $match );
							// add to array
							$clean_matches[] = preg_replace ("[\s]", "_", $match);
						}
					}
				} 
			}
			// return categories array
			return array_unique($clean_matches);
		}
		// return null if no categories found.
		return null;
	}
	
	
	/**
	 * Searches the wikitext for relation declarations and returns an array of them.
	 *
	 * @params String
	 * @return Array
	 */
	function findRelations ( $text ) {
		// match all relations
		// taken from SMW_Hooks.php of Semanti mediawiki extension
		$result = preg_match_all("(\[\[(([^:][^]]*)::)+([^\|\]]*)(\|([^]]*))?\]\])", $text, $matches, PREG_OFFSET_CAPTURE); 
		$ask_matched_inst = $this->findAsk( $text );
 
		// clean the result and create relations array
		if( $result ) {
			$clean_matches = array();
			$first =true;
			$alternative_text = "";
			foreach( $matches as $element){
				if($first){
					$first = false;				
					foreach($element as $m ){
						$escaped = $this->shouldEscape($ask_matched_inst, $m[1], ($m[1]+strlen($m[0])));
						if(!$escaped){
							// this will be used to extract the relation type
							$match_r = preg_replace( "/\[\[([\s]*)/", "", $m[0] );
							// this will be used to extract the destination page
							$match_c = preg_replace( "/\[\[([^\]]+)::([\s]*)/", "", $m[0] );
							
							$matching_relation = preg_replace( "/([\s]*)::([\s]*)([^\]]+)([\s]*)\]\]/", "", $match_r );
							$matching_category = preg_replace( "/(\|([^]]+))?([\s]*)\]\]/", "", $match_c );
							$alternative_text = preg_replace( "/([^\]^\|]+)\]\]/i", "", $match_c );

							if( $alternative_text !='') {
								$alternative_text = preg_replace( "/([^\]]+)\|([\s]*)/i", "", $match_c );
								$alternative_text = preg_replace( "/([\s]*)\]\]/i", "", $alternative_text );
							}
							
							// add to array
							$clean_matches[] = array ( 	"relation" => preg_replace ("[\s]", "_", $matching_relation),
												"page" => preg_replace ("[\s]", "_", $matching_category),
												"pos" => $m[1],
												"len" => strlen($m[0]),
												"alt" => $alternative_text,
												);
												
						}
					}
				}	
			}
			// return relation array
			return $clean_matches;
		}
		// return null if no relations found
		return null;
	}
	
	
	/**
	 * Searches the wikitext for links declarations and returns an array of them.
	 *
	 * @params String
	 * @return Array
	 */
	function findPlainLinks ( $text ) {
		// match all plain links
		$result = preg_match_all( "/\[\[([\s]*)((:(?=(.+)).+)|(([^=^\]^:]+):?([^=^\]^:]+)(\|([^\]]+))?))\]\]/i", $text, $matches, PREG_OFFSET_CAPTURE ); // [[Germany]]
		$ask_matched_inst = $this->findAsk( $text );
		
		
		if( $result ) {
			$clean_matches = array();
	
			$first = true;
			foreach( $matches as $element ){
				
				// clean the result and create plain links array
				if($first){	
					$first = false;

					foreach($element as $m ){
						$escaped = $this->shouldEscape($ask_matched_inst, $m[1], ($m[1]+strlen($m[0])));
						if(!$escaped){
							if( !preg_match( "/\[\[([\s]*)Category:/i",$m[0])){
								$temp = preg_replace( "/\[\[([\s]*)((:?)|(([^\]]+)::)([\s]*))?/i", "", $m[0] );
								$match_link = preg_replace( "/([\s]*)(\|([^\]]+))?([\s]*)\]\]/i", "", $temp );
								$alternative_text = preg_replace( "/([^\]^\|]+)\]\]/i", "", $temp );
								
								if( $alternative_text !='') {
									$alternative_text = preg_replace( "/([^\]]+)\|([\s]*)/i", "", $temp );
									$alternative_text = preg_replace( "/([\s]*)\]\]/i", "", $alternative_text );
								}
								
								// add to array
								$clean_matches[] = array(
													"page"=>preg_replace ("[\s]", "_", $match_link),
													"pos"=>$m[1],
													"len"=>strlen($m[0]),
													"alt"=>$alternative_text);	
							}
						}
					}					
				}
			}	
			// return plain links array
			return $clean_matches;
		}
		// return null if no plain links found
		return null;
	}
	
	
	
	/**
	 * Searches the wikitext for property declarations and returns an array of them.
	 *
	 * @params String
	 * @return Array
	 */
	function findProperties( $text ) {
		// match all properties
		$result = preg_match_all('(\[\[(([^:][^]]*):=)+((?:[^|\[\]]|\[\[[^]]*\]\])*)(\|([^]]*))?\]\])', $text, $matches, PREG_OFFSET_CAPTURE ); // taken from SMW_Hooks.php of Semanti mediawiki extension
		$ask_matched_inst = $this->findAsk( $text );

		if( $result ) {
			$clean_matches = array();
			$first = true;
			foreach( $matches as $element ){
				// clean the result and create plain links array
				if($first){
					$first = false;
					foreach($element as $m ){				
						$escaped = $this->shouldEscape($ask_matched_inst, $m[1], ($m[1]+strlen($m[0])));
						if(!$escaped){
							$match = preg_replace( "/\{\{([\s]*)/i", "", $m[0] );
							$match = preg_replace( "/\[\[([\s]*)/i", "", $match );
							$match = preg_replace( "/([\s]*)\|([\s]*)([^\}]+)([\s]*)\}\}/", "", $match );
							$match = preg_replace( "/([\s]*):=([^\]]+)(\|([\s]*)([^\]]+)([\s]*))?\]\]/", "", $match );
							// add to property array
							$clean_matches[] = preg_replace ("[\s]", "_", $match);
						}
					}
				}
				$first = false;
			}
			// return property array
			return array_unique($clean_matches);
		}
		// return null if no properties found
		return null;
	}
	

	/*
	 * Queries the database to find all categories
	 * Adopted from SMWE_Hooks
	 *
	 * @return Array
	 */
	function getAllCategories() {
		global $wgDBprefix;
		
		$dbr =& wfGetDB( DB_SLAVE );

		$allcategories = array();
		$new_cat = array();
		$category = array();

		// firstly get the page names of destination pages in the same category as our destination page
		$res = $dbr->query(
			"SELECT {$wgDBprefix}categorylinks.cl_to AS category
			FROM {$wgDBprefix}categorylinks, {$wgDBprefix}page
			GROUP BY category
			ORDER BY category;"
		);
					

		if ( $res )
		{
			while ( $res && $row = $dbr->fetchRow( $res ))
			{
				if ( array_key_exists( 'category' , $row) ) 
				{
					$new_cat = $row[ 'category' ];
					$allcategories[] = $new_cat;
				}
			}
			$dbr->freeResult( $res );
		}

		return $allcategories;
	}
	
	
	/**
	 * Takes as an input a set of categories and by querying the database, it tries to find
	 * property suggestions to give the user.
	 *
	 * @params Array
	 * @return Array
	 */
	function findSuggestedCategoriesFordPagesArray( $dPages ) {
		global $wgDBprefix, $egSMWICategoryAcceptableLimit_relations;
		
		$dbr =& wfGetDB( DB_SLAVE );	

		$multi_dpages = '';
		$multi_dpages_with_relations = '1<2';
		
		$first1 = true;
		$first2 = true;
		$any_with_relations = false;
		$any_without_relations = false;
		
		if($dPages != null && sizeof($dPages)>0){
			foreach($dPages AS $page){
				
					$any_without_relations = true;
					if ($first1) $multi_dpages .= '('.$wgDBprefix.'pagelinks.pl_title = '. $dbr->addQuotes($page['page']);
					else $multi_dpages .= ' || '.$wgDBprefix.'pagelinks.pl_title = '. $dbr->addQuotes($page['page']);
					$first1 = false;
					
				if($page['relation'] != '' ){
					$any_with_relations = true;
					if ($first2) {
						$multi_dpages_with_relations = "";
						$multi_dpages_with_relations .= '('.$wgDBprefix.'smw_relations.object_title = '. $dbr->addQuotes($page['page']).' AND '.$wgDBprefix.'smw_relations.relation_title = '.$dbr->addQuotes($page['relation']).' )';
					}
					else $multi_dpages_with_relations .= ' || ('.$wgDBprefix.'smw_relations.object_title = '. $dbr->addQuotes($page['page']).' AND '.$wgDBprefix.'smw_relations.relation_title = '.$dbr->addQuotes($page['relation']).' )';
					$first2 = false;
				
				}
			}
			
			if(!$first1){
				$multi_dpages = $multi_dpages.')';
			}

			$multi_dpages_with_relations = ', ( IF ('.$multi_dpages_with_relations.', 1, 0)) AS mark';
				
			if($any_with_relations){
				$select_if_relation .= "";
			}
			//print_r("findSuggestedCategoriesFordPagesArray: ".date("H:i:s:u"). "<br />");


			$res = $dbr->query(
				"SELECT obj.category AS category, (obj.total/obj2.total)AS proportion {$multi_dpages_with_relations}
				FROM {$wgDBprefix}smw_relations,
				(
					SELECT {$wgDBprefix}categorylinks.cl_to AS category, {$wgDBprefix}pagelinks.pl_title AS dpage, COUNT(*) AS total
					FROM {$wgDBprefix}categorylinks, {$wgDBprefix}pagelinks
					WHERE {$multi_dpages}
					AND {$wgDBprefix}categorylinks.cl_from  = {$wgDBprefix}pagelinks.pl_from
					GROUP BY {$wgDBprefix}categorylinks.cl_to, {$wgDBprefix}pagelinks.pl_title
				) AS obj,
				(
					SELECT {$wgDBprefix}pagelinks.pl_title AS dpage, COUNT(*) AS total
					FROM {$wgDBprefix}categorylinks, {$wgDBprefix}pagelinks
					WHERE {$multi_dpages}
					AND {$wgDBprefix}categorylinks.cl_from  = {$wgDBprefix}pagelinks.pl_from
					GROUP BY {$wgDBprefix}pagelinks.pl_title
				) AS obj2
				WHERE obj.dpage = obj2.dpage
				GROUP BY category
				ORDER BY mark DESC, proportion DESC;"
			);
			
				
			$related_relations = array();
			
			
			if ( $res ) {
				while ( $res && $row = $dbr->fetchRow( $res ) ) {
					if ( array_key_exists( 'category' , $row) ) {
						$category = $row[ 'category' ];
						$proportion = $row[ 'proportion' ] ;
						if (!in_array($category, $related_relations)) {
							
							if($proportion >= $egSMWICategoryAcceptableLimit_relations ) {
								$related_relations[] = $category;
							}
						}
					}
				}
				$dbr->freeResult( $res );
			}
			return array_unique( $related_relations );
		}
		else return null;
	}
	
	
	/**
	 * Takes as an input a set of properties and by querying the database, it tries to find
	 * category suggestions to give the user.
	 *
	 * @params Array
	 * @return Array
	 */
	function findSuggestedCategoriesForPropertyArray ( $properties ){
		global $wgDBprefix, $egSMWICategoryAcceptableLimit_properties;
		$dbr =& wfGetDB( DB_SLAVE );	
		
		$multi_properties = '';
		$first = true;
		if($properties != null && sizeof($properties)>0){
			foreach($properties AS $prop){
				if ($first) $multi_properties .= '('.$wgDBprefix.'smw_attributes.attribute_title = '. $dbr->addQuotes($prop);
				else $multi_properties .= ' || '.$wgDBprefix.'smw_attributes.attribute_title = '. $dbr->addQuotes($prop);
				$first = false;
			}
			$multi_properties .= ')';
			

			// get all the properties related to this Category
			$res = $dbr->query(
				"SELECT obj2.attribute_title AS property, obj.category, (obj.num_occur / obj2.total) as proportion
				FROM {$wgDBprefix}smw_attributes, 
					(
						SELECT {$wgDBprefix}categorylinks.cl_to AS category,  COUNT(*) AS num_occur
						FROM {$wgDBprefix}categorylinks, {$wgDBprefix}smw_attributes
						WHERE {$multi_properties}
						AND {$wgDBprefix}smw_attributes.subject_id= {$wgDBprefix}categorylinks.cl_from
						GROUP BY {$wgDBprefix}categorylinks.cl_to
						ORDER BY num_occur DESC
					) as obj,
					(
						SELECT {$wgDBprefix}smw_attributes.attribute_title,  COUNT(*) AS total
						FROM {$wgDBprefix}smw_attributes
						WHERE {$multi_properties}
						GROUP BY {$wgDBprefix}smw_attributes.attribute_title
						ORDER BY total DESC
					) as obj2
				GROUP BY obj2.attribute_title, obj.category
				ORDER BY proportion DESC;"
			);
			
			$related_categories = array();
			
			
			if ( $res ) {
				while ( $res && $row = $dbr->fetchRow( $res ) ) {
					if ( array_key_exists( 'category' , $row) ) {
						$category = $row[ 'category' ];
						$property = $row[ 'property' ];	
						$proportion = $row[ 'proportion' ];	
						if (!in_array($category, $related_categories)) {
							if($proportion >= $egSMWICategoryAcceptableLimit_properties){
								$related_categories[] = $category;
							}
						}
					}
				}
				$dbr->freeResult( $res );
			}
			return array_unique( $related_categories );
		}
		else return null;
	}
	
	
	/**
	 * Takes as an input a set of categories and by querying the database, it tries to find
	 * relation suggestions to give the user.
	 *
	 * @params Array, Array
	 * @return Array
	 */
	function findSuggestedRelationsForSCategories ($sCategories){
		global $wgDBprefix, $egSMWIRelationAcceptableLimit_categories;
		$dbr =& wfGetDB( DB_SLAVE );	

		$multi_categories = '';
		
		if($sCategories != null && sizeof($sCategories)>0){
			$first = true;
			foreach($sCategories AS $cat){
				if ($first) $multi_categories .= '('.$wgDBprefix.'categorylinks.cl_to = '. $dbr->addQuotes($cat);
				else $multi_categories .= ' || '.$wgDBprefix.'categorylinks.cl_to = '. $dbr->addQuotes($cat);
				$first = false;
			}
			$multi_categories .= ')';


			
			$res = $dbr->query(
				"SELECT obj2.relation AS relation, obj2.category AS category, AVG(obj2.total/obj.total) AS proportion
				FROM 
					(
						SELECT COUNT(*) AS total, {$wgDBprefix}categorylinks.cl_to AS category 
						FROM {$wgDBprefix}smw_relations,{$wgDBprefix}categorylinks 
						WHERE 
						".$multi_categories."
							AND categorylinks.cl_from =smw_relations.subject_id 
						GROUP BY category
					) AS obj,
					(
						SELECT COUNT(*) AS total, {$wgDBprefix}categorylinks.cl_to AS category , {$wgDBprefix}smw_relations.relation_title AS relation
						FROM {$wgDBprefix}smw_relations, {$wgDBprefix}categorylinks 
						WHERE ".$multi_categories."
						AND {$wgDBprefix}categorylinks.cl_from ={$wgDBprefix}smw_relations.subject_id
						GROUP BY relation
					) AS obj2
				WHERE
					obj.category =obj2.category
				GROUP BY relation
				ORDER BY proportion DESC;"
			);

				
			$related_relations = array();
		
			if ( $res ) {
				while ( $res && $row = $dbr->fetchRow( $res ) ) {
					if ( array_key_exists( 'relation' , $row) ) {
						$relation = $row[ 'relation' ];
						$category = $row['category'];
						$proportion = $row['proportion'];
						
						if (!in_array($relation, $related_relations)) {
							if($proportion >= $egSMWIRelationAcceptableLimit_categories){
								$related_relations[$relation][] =  $category;
								
							}
						}
					}
				}
				$dbr->freeResult( $res );
			}
			
			return $related_relations;
		}
		else return null;
	}


	/**
	 * Takes as an input a set of pages and by querying the database, it tries to find
	 * relation suggestions to give the user.
	 *
	 * @params Array
	 * @return Array
	 */
	function findSuggestedRelationsForDPages ($dPages){
		global $wgDBprefix, $egSMWIRelationAcceptableLimit_dpages;
		$dbr =& wfGetDB( DB_SLAVE );	

		$multi_dpages = '';
		
		if($dPages != null && sizeof($dPages)>0){
			$first = true;
			
			foreach($dPages AS $page){
				if ($first) $multi_dpages .= '('.$wgDBprefix.'smw_relations.object_title = '. $dbr->addQuotes($page);
				else $multi_dpages .= ' || '.$wgDBprefix.'smw_relations.object_title = '. $dbr->addQuotes($page);
				$first = false;
			}
			$multi_dpages .= ')';
			

			$res = $dbr->query(
				"SELECT obj2.relation AS relation, obj2.dpage AS dpage, obj2.total/obj.total AS proportion
				FROM 
					(
						SELECT COUNT(*) AS total, {$wgDBprefix}smw_relations.object_title AS dpage 
						FROM {$wgDBprefix}smw_relations, {$wgDBprefix}categorylinks 
						WHERE 
						".$multi_dpages."
						GROUP BY {$wgDBprefix}smw_relations.object_title
					) AS obj,
					(
						SELECT COUNT(*) AS total, {$wgDBprefix}smw_relations.object_title AS dpage , {$wgDBprefix}smw_relations.relation_title AS relation
						FROM {$wgDBprefix}smw_relations, {$wgDBprefix}categorylinks 
						WHERE ".$multi_dpages."
						GROUP BY {$wgDBprefix}smw_relations.relation_title
					) AS obj2
				WHERE
					obj.dpage =obj2.dpage
				ORDER BY proportion DESC;"
			);
				
			$related_relations = array();
		
			if ( $res ) {
				while ( $res && $row = $dbr->fetchRow( $res ) ) {
					if ( array_key_exists( 'relation' , $row) ) {
						$relation = $row[ 'relation' ];
						$dpage = $row['dpage'];
						$proportion = $row['proportion'];
						if (!in_array($relation, $related_relations)) {
							if($proportion >= $egSMWIRelationAcceptableLimit_dpages) {
								$related_relations[$relation][] =  $dpage;
							}
						}
					}
				}
				$dbr->freeResult( $res );
			}
			
			return $related_relations;
		}
		else return null;
	}
	
	/**
	 * Takes as an input a set of categories and by querying the database, it tries to find
	 * property suggestions to give the user.
	 *
	 * @params Array
	 * @return Array
	 */
	function findSuggestedPropertiesForCategoryArray( $categories ) {
		global $wgDBprefix, $egSMWIPropertyAcceptableLimit_categories;
		$dbr =& wfGetDB( DB_SLAVE );	

		$multi_categories = '';
		$first = true;
		if($categories != null && sizeof($categories)>0){
			foreach($categories AS $cat){
				if ($first) $multi_categories .= '('.$wgDBprefix.'categorylinks.cl_to = '. $dbr->addQuotes($cat);
				else $multi_categories .= ' || '.$wgDBprefix.'categorylinks.cl_to = '. $dbr->addQuotes($cat);
				$first = false;
			}
			$multi_categories .= ')';
			
			
			// get all the properties related to this Category
			$res = $dbr->query(
				"SELECT obj2.property AS property, (obj2.total/obj.total) AS proportion
				FROM 
					(
						SELECT COUNT(*) AS total, {$wgDBprefix}categorylinks.cl_to AS category 
						FROM  {$wgDBprefix}categorylinks 
						WHERE 
						".$multi_categories."
						
						GROUP BY {$wgDBprefix}categorylinks.cl_to
					) AS obj,
					(
						SELECT COUNT(*) AS total, {$wgDBprefix}categorylinks.cl_to AS category , {$wgDBprefix}smw_attributes.attribute_title AS property
						FROM {$wgDBprefix}smw_attributes, {$wgDBprefix}categorylinks 
						WHERE ".$multi_categories."
						AND {$wgDBprefix}categorylinks.cl_from ={$wgDBprefix}smw_attributes.subject_id
						GROUP BY {$wgDBprefix}smw_attributes.attribute_title
					) AS obj2
				WHERE
					obj.category =obj2.category
				ORDER BY proportion DESC;"
			);
			
			
			$related_attributes = array();
			
			
			if ( $res ) {
				while ( $res && $row = $dbr->fetchRow( $res ) ) {
					if ( array_key_exists( 'property' , $row) ) {
						$property = $row[ 'property' ];
						$proportion = $row[ 'proportion' ];
						if (!in_array($property, $related_attributes)) {
							if($proportion >=$egSMWIPropertyAcceptableLimit_categories){
								$related_attributes[] = $property;
							}
						}
					}
				}
				$dbr->freeResult( $res );
			}

			return array_unique( $related_attributes );
		}
		else return null;
	}
	
	
	/**
	   * Takes as an input a single relation and returns suggested categories
	   *
	   * @params String
	   * @return Array
	   */
	function findSuggestedCategoriesForSingleRelation($relation){
		global $wgDBprefix;
		$dbr =& wfGetDB( DB_SLAVE );
		if($relation != null){

				
			$res = $dbr->query(
				"SELECT cl_to AS category, COUNT(*) AS num_occur
				FROM {$wgDBprefix}smw_relations, {$wgDBprefix}categorylinks, {$wgDBprefix}page
				WHERE  {$wgDBprefix}smw_relations.relation_title = ". $dbr->addQuotes($relation)." 
					AND {$wgDBprefix}page.page_id = {$wgDBprefix}categorylinks.cl_from
					AND {$wgDBprefix}smw_relations.object_title = {$wgDBprefix}page.page_title
				GROUP BY category
				ORDER BY num_occur DESC;"
			
			);
		}

		
		$categories = array();
		if ( $res ) {
				while ( $res && $row = $dbr->fetchRow( $res ) ) {
					if ( array_key_exists( 'category' , $row) ) {
						$category = $row[ 'category' ];						
						if (!in_array($category, $categories)) {
							$categories[] = $category;
						}
					}
				}
				$dbr->freeResult( $res );
			}
		else return null;
		
		return array_unique( $categories );
	}
	
	/**
	 * Returns suggested list of destination pages for a single relation type
	 *
	 * @params String
	 * @return Array
	 */
	function findSuggestedDPagesForSingleRelation($relation){
		global $wgDBprefix;
		$dbr =& wfGetDB( DB_SLAVE );
		if($relation != null){

				
			$res = $dbr->query(
				"SELECT page_title AS page, COUNT(*) AS num_occur
				FROM {$wgDBprefix}smw_relations, {$wgDBprefix}page
				WHERE  {$wgDBprefix}smw_relations.relation_title = ". $dbr->addQuotes($relation)." 
					AND {$wgDBprefix}smw_relations.object_title = {$wgDBprefix}page.page_title
				GROUP BY page_title
				ORDER BY num_occur DESC;"
			
			);
			
		}

		
		$pages = array();
		if ( $res ) {
				while ( $res && $row = $dbr->fetchRow( $res ) ) {
					if ( array_key_exists( 'page' , $row) ) {
						$page = $row[ 'page' ];						
						if (!in_array($page, $pages)) {
							$pages[] = $page;
						}
					}
				}
				$dbr->freeResult( $res );
			}
		else return null;
		
		return array_unique( $pages );
	}
	
	/**
	   * Takes as an input a category relation and returns suggested pages
	   *
	   * @params String
	   * @return Array
	   */
	function findSuggestedPagesForSingleCategory($category){
		global $wgDBprefix;
		$dbr =& wfGetDB( DB_SLAVE );
		if($category != null){
				
			$res = $dbr->query(
						"SELECT page_title AS page, COUNT(*) AS num_occur
						FROM {$wgDBprefix}page, {$wgDBprefix}categorylinks
						WHERE {$wgDBprefix}categorylinks.cl_to = ". $dbr->addQuotes($category)."
							AND {$wgDBprefix}page.page_id = {$wgDBprefix}categorylinks.cl_from 
						GROUP BY page
						ORDER BY page;");
		}
		
		

		$pages = array();
		if ( $res ) {
			
				while ( $res && $row = $dbr->fetchRow( $res ) ) {
					if ( array_key_exists( 'page' , $row) ) {
						$page = $row[ 'page' ];							
						if (!in_array($page, $pages)) {
							$pages[] = $page;
						}
					}
				}
				$dbr->freeResult( $res );
		}
		else return null;
		
		return array_unique( $pages );
	}
	
	/**
	 * Returns suggested list of relation types for a single destination page
	 *
	 * @params String
	 * @return Array
	 */
	function findSuggestedRelationsForSingleDPage($dpage) {
		global $wgDBprefix;
		$dbr =& wfGetDB( DB_SLAVE );
		if($dpage != null){
				
			$res = $dbr->query(
						"SELECT DISTINCT relation_title AS relation, COUNT(*) AS num_occur
					FROM {$wgDBprefix}smw_relations, {$wgDBprefix}page
					WHERE
						{$wgDBprefix}page.page_title = ". $dbr->addQuotes($dpage)."
					  AND  {$wgDBprefix}smw_relations.subject_id = {$wgDBprefix}page.page_id
					  AND  {$wgDBprefix}page.page_is_redirect = 0
					GROUP BY relation
					ORDER BY num_occur DESC;");

		}
		
		
		$relations = array();
		if ( $res ) {
			
				while ( $res && $row = $dbr->fetchRow( $res ) ) {
					if ( array_key_exists( 'relation' , $row) ) {
						$relation = $row[ 'relation' ];							
						if (!in_array($relation, $relations)) {
							$relations[] = $relation;
						}
					}
				}
				$dbr->freeResult( $res );
		}
		else return null;
		
		return array_unique( $relations );
	}
	
	/**
	 * Returns suggested list of property values for a single property name
	 *
	 * @params String
	 * @return Array
	 */
	function findSuggestedPropertyValuesForSingleProperty($property) {
		global $wgDBprefix;
		$dbr =& wfGetDB( DB_SLAVE );
		if($property != null){
				
			$res = $dbr->query(
						"SELECT DISTINCT value_xsd AS value
					FROM {$wgDBprefix}smw_attributes
					WHERE
						{$wgDBprefix}smw_attributes.attribute_title = ". $dbr->addQuotes($property)."
					ORDER BY value DESC;");

		}
		else return null;
		
		$values = array();
		if ( $res ) {
			
				while ( $res && $row = $dbr->fetchRow( $res ) ) {
					if ( array_key_exists( 'value' , $row) ) {
						$value = $row[ 'value' ];							
						if (!in_array($relation, $values)) {
							$values[] = $value;
						}
					}
				}
				$dbr->freeResult( $res );
		}
		else return null;
		return array_unique( $values );
	}
	
}
?>