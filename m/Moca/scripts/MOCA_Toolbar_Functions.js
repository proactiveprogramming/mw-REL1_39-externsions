/**
  * File: MOCA_Toolbar_Functions.js
  *
  * Description: Contains Javascript functions used by the Semantic wizard
  *
  * @author Chrysovalanto Kousetti
  * @email valanto@gmail.com
  *
  */
  
  /** Cancels adding a new element from the Semantic wizard
   * Adopted from http://www.w3schools.com/js/js_cookies.asp
   *
   */
  function smwifCancelSemanticElementBox() {
		if(document.getElementById(addNewSemanticElementDiv)) document.getElementById(addNewSemanticElementDiv).innerHTML = '';
		document.getElementById(textboxTag).disabled = false;
}

  /** Finds the possition of the caret
     * adopted from http://www.csie.ntu.edu.tw/~b88039/html/jslib/caret.html
     *
     * @params String
     * @return int
     */
function caret(node) {
	node.focus(); 
	/* without node.focus() IE will returns -1 when focus is not on node */
	if(node.selectionStart) return node.selectionStart;
	else if(!document.selection) return 0;
	var c		= "\001";
	var sel	= document.selection.createRange();
	var dul	= sel.duplicate();
	var len	= 0;
	dul.moveToElementText(node);
	sel.text	= c;
	len		= (dul.text.indexOf(c));
	sel.moveStart('character',-1);
	sel.text	= "";
	return len;
}

  /** Finds the selection in the textarea
     * adopted from http://the-stickman.com/web-development/javascript/finding-selection-cursor-position-in-a-textarea-in-internet-explorer/
     *
     * @params String
     * @return Array
     */
function getSelectionStartOfTextarea( textarea_id ) {

	var element = document.getElementById( textarea_id ); 
	var result_array = new Array();
	if( document.selection  && !is_gecko){
		// The current selection 
		var range = document.selection.createRange(); 

		// We'll use this as a 'dummy'  
		var stored_range = range.duplicate(); 
		// Select all text
		stored_range.moveToElementText( element ); 
		if (!range.text) {
			var start_pos = caret(document.getElementById( textarea_id ));
			result_array[0] = start_pos;
			result_array[1] = -1;
			return result_array;
		}
		
		// Now move 'dummy' end point to end point of original range
		stored_range.setEndPoint( 'EndToEnd', range ); 
		// Now we can calculate start and end points
		element.selectionStart = stored_range.text.length - range.text.length; 
		element.selectionEnd = element.selectionStart + range.text.length; 
		result_array[0] = element.selectionStart;
		result_array[1] = element.selectionEnd;
		return result_array;
		
	}
	else if(element.selectionStart || element.selectionStart == '0') {

		result_array[0] = document.getElementById(textarea_id).selectionStart;
		result_array[1] = document.getElementById(textarea_id).selectionEnd;
		
		return result_array;
	}
	return -1;
}

// Category Related Functions
	/** Reacts when add category button is pressed
	   *
	   */
	function smwifAddCategoryButtonPressed(){
		if(document.getElementById(addNewSemanticElementDiv)) document.getElementById(addNewSemanticElementDiv).innerHTML = "<br /><span width='100%' id='"+ ajaxIsLoadingTheBoxSuggestions +"' style='display: inline; text-align: center;'><img src ='" + smwi_ajax_is_loading_image_path +"'></img></span>" 
		var textbox = document.getElementById(textboxTag);
		var pos =  getSelectionStartOfTextarea(textboxTag);
		var category = "";

		if( ( pos[0] != pos[1] ) && ( pos[1] != -1 ) && ( typeof( pos[0] ) != "undefined" ) ){

			//insertTags("[[Category:", "]]", "CategoryName");
			category = textbox.value.substring(pos[0], pos[1]);
			sajax_do_call( "smwifAddCategoryButtonPressedWrapper", [pos[0], category], document.getElementById(addNewSemanticElementDiv) );
			//sajax_do_call( "smwifAddCategoryButtonCompletedWrapper", ["Category tags have been successfully added to the selected word!", 1], document.getElementById(addNewSemanticElementDiv) );
			//return;
		} 
		else{
			sajax_do_call( "smwifAddCategoryButtonPressedWrapper", [pos[0], category], document.getElementById(addNewSemanticElementDiv) );
		}
		document.getElementById(textboxTag).disabled = true;
		return;
	}
	
	/** Reacts when add a specific category button is pressed
	   *
	   */
	function smwifAddThisCategoryPressed( radio_obj, category_obj_0, category_obj_1,category_obj_2, pos, category_old, ajax_function ) {

		var radioObj = document.getElementsByName(radio_obj);
		var radioLength = radioObj.length;
		var type = -1;
		for(var i = 0; i < radioLength; i++) {
			if(radioObj[i].checked) {
				type = radioObj[i].value;
			}
		}
		
		if(type ==0 ) category = document.getElementById(category_obj_0).value;
		else if (type == 1 ) category = document.getElementById(category_obj_1).options[document.getElementById(category_obj_1).selectedIndex].value
		else if (type == 2 ) category = document.getElementById(category_obj_2).options[document.getElementById(category_obj_2).selectedIndex].value
		else return;
		
		category = trimString(category);
		if(category == '') {
			sajax_do_call( "smwifAddCategoryButtonCompletedWrapper", ["Category NOT Added because you did not provide a category.", 0], document.getElementById(addNewSemanticElementDiv) );
			if(document.getElementById(addNewSemanticElementDiv)) document.getElementById(addNewSemanticElementDiv).innerHTML = "<br /><span width='100%' id='"+ ajaxIsLoadingTheBoxSuggestions +"' style='display: inline' align='center'><img src ='" + smwi_ajax_is_loading_image_path +"'></img></span>" 
			smwisfStartTheTimeOutTimer( textboxTag , 0);
			document.getElementById(textboxTag).disabled = false;
			window.scrollTo(0,0);
			return;
		}
	
		var textbox = document.getElementById(textboxTag);
		var original_text = textbox.value;
		var extra_len = category_old.length;
		var new_string = "[[Category:" + category + "]]";
		textbox.value = original_text.substring(0, parseInt(pos)) + new_string + original_text.substring((parseInt(pos)+parseInt(extra_len)), original_text.length)
		if(document.getElementById(addNewSemanticElementDiv)) document.getElementById(addNewSemanticElementDiv).innerHTML = "<br /><span width='100%' id='"+ ajaxIsLoadingTheBoxSuggestions +"' style='display: inline' align='center'><img src ='" + smwi_ajax_is_loading_image_path +"'></img></span>" 
		smwisfStartTheTimeOutTimer( textboxTag , 0);
		document.getElementById(textboxTag).disabled = false;
		window.scrollTo(0,0);
		smwifWhereInWikiText(pos, new_string.length);
		sajax_do_call( ajax_function, ["Category Added Successfully!", 1], document.getElementById(addNewSemanticElementDiv) );

	
		return;
	}
	
	/** Reacts when category adding option changes
	   *
	   * @params String, String
	   */
	function smwiCategoryAddOptionChanged( radio_obj, value){
		var radioObj = document.getElementsByName(radio_obj);
		radioObj[value].checked = true;
		
	}

// Relation Related Functions
	/** Reacts when add relation button is pressed
	   *
	   */
	function smwifAddRelationButtonPressed(){
		if(document.getElementById(addNewSemanticElementDiv)) document.getElementById(addNewSemanticElementDiv).innerHTML = "<br /><span width='100%' id='"+ ajaxIsLoadingTheBoxSuggestions +"' style='display: inline; text-align: center;'><img src ='" + smwi_ajax_is_loading_image_path +"'></img></span>" 
		var textbox = document.getElementById(textboxTag);
		var pos =  getSelectionStartOfTextarea(textboxTag);
		var link;
		
		if (pos[0] == -1 || typeof( pos[0] ) == "undefined")  {
			sajax_do_call( "smwifAddRelationButtonCompletedWrapper", ["Relation tags have not been added since you didn't select or point to something within the editor.", 0], document.getElementById(addNewSemanticElementDiv) );
			return;
		}
		if( ( pos[0] != pos[1] ) && ( pos[1] != -1 ) && ( typeof( pos[0] ) != "undefined" ) ){
			link = textbox.value.substring(pos[0], pos[1])
		} 
		else{
			link = '';
		}
			sajax_do_call( "smwifAddRelationButtonPressedWrapper", [pos[0], link], document.getElementById(addNewSemanticElementDiv) );
		
		document.getElementById(textboxTag).disabled = true;
		return;
	}
	
	/** Reacts when add a specific relation button is pressed
	   *
	   */
	function smwifAddThisRelationPressed( radio_obj, relation_obj_0, relation_obj_1, link_obj, alt_obj, pos, link_old, len , ajax_function, withBrackets ) {
		var radioObj = document.getElementsByName(radio_obj);
		var radioLength = radioObj.length;
		var type = -1;
		for(var i = 0; i < radioLength; i++) {
			if(radioObj[i].checked) {
				type = radioObj[i].value;
			}
		}
		var alt_text = trimString(document.getElementById(alt_obj).value);
		if(alt_text != "")	alt_text = "|"+alt_text;
		
		if(withBrackets)
			var after_length = parseInt(len);
		else
			var after_length = link_old.length;
			
		if(type == 0 ) {
			var relation = document.getElementById(relation_obj_0).value;
			var link = document.getElementById(link_obj).value;
		}
		else if (type == 1) {
			var relation = document.getElementById(relation_obj_1).options[document.getElementById(relation_obj_1).selectedIndex].value;
			var link = document.getElementById(link_obj).value;
		}
		else return;
		
		relation = trimString(relation);
		if(relation == '') {
			sajax_do_call( "smwifAddRelationButtonCompletedWrapper", ["Relation NOT Added because you did not provide a relation type.", 0], document.getElementById(addNewSemanticElementDiv) );
			if(document.getElementById(addNewSemanticElementDiv)) document.getElementById(addNewSemanticElementDiv).innerHTML = "<br /><span width='100%' id='"+ ajaxIsLoadingTheBoxSuggestions +"' style='display: inline' align='center'><img src ='" + smwi_ajax_is_loading_image_path +"'></img></span>" 
			smwisfStartTheTimeOutTimer( textboxTag , 0);
			document.getElementById(textboxTag).disabled = false;
			window.scrollTo(0,0);
			return;
		}
		link = trimString(link);
		if(link == '') {
			sajax_do_call( "smwifAddRelationButtonCompletedWrapper", ["Relation NOT Added because you did not provide a destination page.", 0], document.getElementById(addNewSemanticElementDiv) );
			if(document.getElementById(addNewSemanticElementDiv)) document.getElementById(addNewSemanticElementDiv).innerHTML = "<br /><span width='100%' id='"+ ajaxIsLoadingTheBoxSuggestions +"' style='display: inline' align='center'><img src ='" + smwi_ajax_is_loading_image_path +"'></img></span>" 
			smwisfStartTheTimeOutTimer( textboxTag , 0);
			document.getElementById(textboxTag).disabled = false;
			window.scrollTo(0,0);
			return;
		}
	
		var textbox = document.getElementById(textboxTag);
		var original_text = textbox.value;
		var new_string = "[["+ relation +"::" + link + alt_text + "]]";
		textbox.value = original_text.substring(0, parseInt(pos)) + new_string + original_text.substring((parseInt(pos)+after_length), original_text.length)
		if(document.getElementById(addNewSemanticElementDiv)) document.getElementById(addNewSemanticElementDiv).innerHTML = "<br /><span width='100%' id='"+ ajaxIsLoadingTheBoxSuggestions +"' style='display: inline' align='center'><img src ='" + smwi_ajax_is_loading_image_path +"'></img></span>" 
		document.getElementById(textboxTag).disabled = false;
		window.scrollTo(0,0);
		smwifWhereInWikiText(pos, new_string.length);
		sajax_do_call( "smwifAddRelationButtonCompletedWrapper", ["Relation Added Successfully!", 1], document.getElementById(addNewSemanticElementDiv) );
		smwisfStartTheTimeOutTimer( textboxTag , 0);
		
		return;
		
	}
	
	/** Reacts when relation adding option changes
	   *
	   * @params String, String
	   */
	function smwiRelationAddOptionChanged( radio_obj, value ){
		var radioObj = document.getElementsByName(radio_obj);
		radioObj[value].checked = true;
	}

// Property Related Functions
	/** Reacts when add property button is pressed
	   *
	   */
	function smwifAddPropertyButtonPressed() {
		if(document.getElementById(addNewSemanticElementDiv)) document.getElementById(addNewSemanticElementDiv).innerHTML = "<br /><span width='100%' id='"+ ajaxIsLoadingTheBoxSuggestions +"' style='display: inline; text-align: center;'><img src ='" + smwi_ajax_is_loading_image_path +"'></img></span>" 
		var textbox = document.getElementById(textboxTag);
		var pos =  getSelectionStartOfTextarea(textboxTag);
		var property_value;

		if (pos[0] == -1 || typeof( pos[0] ) == "undefined")  {
			sajax_do_call( "smwifAddPropertyButtonCompletedWrapper", ["Property tags have not been added since you didn't select or point to something within the editor.", 0], document.getElementById(addNewSemanticElementDiv) );
			return;
		}
		if( ( pos[0] != pos[1] ) && ( pos[1] != -1 ) && ( typeof( pos[0] ) != "undefined" ) ){
			property_value = trimString(textbox.value.substring(pos[0], pos[1]));
		} 
		else{
			property_value = '';
		}
		sajax_do_call( "smwifAddPropertyButtonPressedWrapper", [pos[0], property_value], document.getElementById(addNewSemanticElementDiv) );
		
		document.getElementById(textboxTag).disabled = true;
		return;
	}
	
	/** Reacts when add a specific property button is pressed
	   *
	   */
	function smwifAddThisPropertyPressed( radio_obj, property_obj_0, property_obj_1, value_obj, pos, value_old, ajax_function) {
		var after_length = value_old.length;
		var radioObj = document.getElementsByName(radio_obj);
		var radioLength = radioObj.length;
		var type = -1;
		for(var i = 0; i < radioLength; i++) {
			if(radioObj[i].checked) {
				type = radioObj[i].value;
			}
		}
		
		if(type == 0 ) {
			var property = document.getElementById(property_obj_0).value;
			var value = document.getElementById(value_obj).value;
		}
		else if(type == 1 ) {
			var property = document.getElementById(property_obj_1).options[document.getElementById(property_obj_1).selectedIndex].value;
			var value = document.getElementById(value_obj).value;
		}
		else return;
		
		property = trimString(property);
		if(property == '') {
			sajax_do_call( "smwifAddPropertyButtonCompletedWrapper", ["Property NOT Added because you did not provide a property name.", 0], document.getElementById(addNewSemanticElementDiv) );
			if(document.getElementById(addNewSemanticElementDiv)) document.getElementById(addNewSemanticElementDiv).innerHTML = "<br /><span width='100%' id='"+ ajaxIsLoadingTheBoxSuggestions +"' style='display: inline' align='center'><img src ='" + smwi_ajax_is_loading_image_path +"'></img></span>" 
			smwisfStartTheTimeOutTimer( textboxTag , 0);
			document.getElementById(textboxTag).disabled = false;
			window.scrollTo(0,0);
			return;
		}
		value = trimString(value);
		if(value == ''){
			sajax_do_call( "smwifAddPropertyButtonCompletedWrapper", ["Property NOT Added because you did not provide a property value.", 0], document.getElementById(addNewSemanticElementDiv) );
			if(document.getElementById(addNewSemanticElementDiv)) document.getElementById(addNewSemanticElementDiv).innerHTML = "<br /><span width='100%' id='"+ ajaxIsLoadingTheBoxSuggestions +"' style='display: inline' align='center'><img src ='" + smwi_ajax_is_loading_image_path +"'></img></span>" 
			smwisfStartTheTimeOutTimer( textboxTag , 0);
			document.getElementById(textboxTag).disabled = false;
			window.scrollTo(0,0);
			return;
		}
	
		var textbox = document.getElementById(textboxTag);
		var original_text = textbox.value;
		var new_string = "[["+ property +":=" + value + "]]";
		textbox.value = original_text.substring(0, parseInt(pos)) + new_string + original_text.substring((parseInt(pos)+after_length), original_text.length)
		if(document.getElementById(addNewSemanticElementDiv)) document.getElementById(addNewSemanticElementDiv).innerHTML = "<br /><span width='100%' id='"+ ajaxIsLoadingTheBoxSuggestions +"' style='display: inline' align='center'><img src ='" + smwi_ajax_is_loading_image_path +"'></img></span>" 
		smwisfStartTheTimeOutTimer( textboxTag , 0);
		document.getElementById(textboxTag).disabled = false;
		window.scrollTo(0,0);
		smwifWhereInWikiText(pos, new_string.length);
		sajax_do_call( ajax_function, ["Property Added Successfully!", 1], document.getElementById(addNewSemanticElementDiv) );

		return;
		
	}
	
	/** Reacts when property adding option changes
	   *
	   * @params String, String, String
	   */
	function smwiPropertyAddOptionChanged( radio_obj, value, drop_obj){
		var radioObj = document.getElementsByName(radio_obj);
		radioObj[value].checked = true;
	}

	/** Reacts when property adding option changes
	   *
	   * @params String, String, String
	   */
	function smwiPropertyAddOptionChangedProp(drop_obj, value_div, property_textbox_obj){
		var property = document.getElementById(drop_obj).options[document.getElementById(drop_obj).selectedIndex].value;
		sajax_do_call( 'smwifAddPropertyGetValueWrapper', [property, property_textbox_obj], document.getElementById(value_div) );
	}
	
	/** Reacts when suggested property name is selected
	   *
	   * @params String, String
	   */
	function smwiPropertySuggestedPropertySelected(drop_obj, property_textbox){
		var value = document.getElementById(drop_obj).options[document.getElementById(drop_obj).selectedIndex].value;
		document.getElementById(property_textbox).value = value;
	}
	
	/** Reacts when suggested relation name is selected
	   *
	   * @params String, String
	   */
	function smwiRelationAddOptionChangedRel(drop_obj, value_div, relation_textbox_obj){
		var relation = document.getElementById(drop_obj).options[document.getElementById(drop_obj).selectedIndex].value;
		sajax_do_call( 'smwifAddRelationGetValueWrapper', [relation, relation_textbox_obj], document.getElementById(value_div) );
	}
	
	function smwiPropertySuggestedRelationSelected(drop_obj, relation_textbox){
		var value = document.getElementById(drop_obj).options[document.getElementById(drop_obj).selectedIndex].value;
		document.getElementById(relation_textbox).value = value;
	}