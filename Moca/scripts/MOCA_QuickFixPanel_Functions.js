/**
  * File: MOCA_QuickFixPanel_Functions.js
  *
  * Description: Contains Javascript functions used by the QuickFixPanel
  *
  * @author Chrysovalanto Kousetti
  * @email valanto@gmail.com
  *
  */
  
/** Detects the change in the editor textbarea
   *
   * @params String
   */
function smwisfTextboxChanged( textboxTag ) {
	document.getElementById(quickfixpanelmainDiv).innerHTML="<br /><div style='vertical-align: middle; text-align: center;' ><p class='smwi_in_box_title'><img src='"+smwi_ajax_is_loading_qfp_image_path+"'></img> Generating Quick Fix Panel</p></div><br />";

	sajax_do_call( "smwifWikiTextboxEditAjaxWrapper", [], document.getElementById(quickfixpanelmainDiv) );
}

/** Adds monitoring to the editor textbarea
   *
   * @params String
   */
function smwisfAddTextboxMonitoring( textboxTag ) {
	var textbox = document.getElementById( textboxTag );
	textbox.onkeypress = function() {smwisfStartEditingWikiText( textboxTag , textbox);};
	smwisfStartTheTimeOutTimer( textboxTag , 0);	
}

/** Detects the start of editing in the wiki
   *
   * @params String, String
   */
function smwisfStartEditingWikiText (textboxTag, textbox) {
	document.getElementById(quickfixpanelmainDiv).innerHTML="<br /><div style='vertical-align: middle; text-align: center;' ><p class='smwi_in_box_title'><img src='"+smwi_ajax_is_loading_qfp_image_path+"'></img> Waiting for you to finish editing the wikitext</p></div><br />";
	smwisfStartTheTimeOutTimer(textboxTag, timeout_waiting_time_editing);
}

/** Starts the editing timer and sets the cookie
   *
   * @params String, String
   */
function smwisfStartTheTimeOutTimer(textboxTag, timeout_waiting_time){
	setCookie( smwi_cookie_textarea_name, document.getElementById(textboxTag).value, 30 );
	clearTimeout(timeout);
	timeout =setTimeout("smwisfTextboxChanged('" + textboxTag + "')", timeout_waiting_time);
}

/** Calls ajax when user wants to fix a category
   *
   * @params String, int
   */
function smwisfFixCategoryIssue (category_div, issue_code){
	document.getElementById(category_div).innerHTML="<br /><div style='vertical-align: middle; text-align: center;'><p class='smwi_in_box_title'><img  src='"+smwi_ajax_is_loading_qfp_image_path+"'></img>Loading panel to add new category!</p></div><br />";
	sajax_do_call( "smwifFixCategoryIssueWrapper", [issue_code], document.getElementById(category_div) );
}

/** Calls ajax when user wants to fix a relation
   *
   * @params String, int, String, int, int, String, int
   */
function smwisfFixRelationIssue (relation_div, issue_code, link, pos, len, alt, type){
	 if(issue_code == -1){
		if(document.getElementById(smwi_show_plain_links_fix_title_div)) document.getElementById(smwi_show_plain_links_fix_title_div).innerHTML = '<img onclick="smwisfHidePlainLinks(\''+relation_div+'\', \''+smwi_show_plain_links_fix_title_div+'\')" style="cursor: pointer;" src="' + smwi_minus_image + '"></img><a onclick="smwisfHidePlainLinks(\''+relation_div+'\', \''+smwi_show_plain_links_fix_title_div+'\')" style="cursor: pointer;">Hide all current relations and plain links<a>';
		sajax_do_call( "smwifShowPlainLinkToFixWrapper", [0], document.getElementById(relation_div) );
		document.getElementById(relation_div).innerHTML="<br /><div style='vertical-align: middle; text-align: center;' ><p class='smwi_in_box_title'><img src='"+smwi_ajax_is_loading_qfp_image_path+"'></img>Loading relation information...</p></div><br />";

		return;
	 }
	 else{
		sajax_do_call( "smwifFixRelationIssueWrapper", [issue_code, link, pos,len, alt, type], document.getElementById(relation_div) );
		if(issue_code == 2)
			document.getElementById(relation_div).innerHTML="<br /><div style='vertical-align: middle; text-align: center;' ><p class='smwi_in_box_title'><img src='"+smwi_ajax_is_loading_qfp_image_path+"'></img>Loading information about "+link+"...</p></div><br />";
		else if (issue_code == 1)
			document.getElementById(relation_div).innerHTML="<br /><div style='vertical-align: middle; text-align: center;' ><p class='smwi_in_box_title'><img src='"+smwi_ajax_is_loading_qfp_image_path+"'></img>Loading information about "+link+"...</p></div><br />";
		else
			document.getElementById(relation_div).innerHTML="<br /><div style='vertical-align: middle; text-align: center;' ><p class='smwi_in_box_title'><img src='"+smwi_ajax_is_loading_qfp_image_path+"'></img>Loading panel to add new relation!</p></div><br />";

	}
}

/** Calls ajax to get the next set of relations
   *
   * @params String, int
   */
function showNextSetOfRelations(relation_div, offset){
		sajax_do_call( "smwifShowPlainLinkToFixWrapper", [offset], document.getElementById(relation_div) );
		document.getElementById(relation_div).innerHTML="<br /><div style='vertical-align: middle; text-align: center;' ><p class='smwi_in_box_title'><img src='"+smwi_ajax_is_loading_qfp_image_path+"'></img>Loading relation information...</p></div><br />";
}

/** Calls ajax to get the previous set of relations
   *
   * @params String, int
   */
function showPreviousSetOfRelations(relation_div, offset){
		sajax_do_call( "smwifShowPlainLinkToFixWrapper", [offset], document.getElementById(relation_div) );
		document.getElementById(relation_div).innerHTML="<br /><div style='vertical-align: middle; text-align: center;' ><p class='smwi_in_box_title'><img src='"+smwi_ajax_is_loading_qfp_image_path+"'></img>Loading relation information...</p></div><br />";
}

/** Calls ajax when user wants to fix a property
   *
   * @params String, int
   */
function smwisfFixPropertyIssue( property_div, issue_code ){

	document.getElementById(property_div).innerHTML="<br /><div style='vertical-align: middle; text-align: center;' ><p class='smwi_in_box_title'><img src='"+smwi_ajax_is_loading_qfp_image_path+"'></img>Loading panel to add new property!</p></div><br />";
	sajax_do_call( "smwifFixPropertyIssueWrapper", [issue_code], document.getElementById(property_div) );

}

/** Hides the view of current plain links and relations
   *
   * @params String, String
   */
function smwisfHidePlainLinks (relation_div, title_div){
	document.getElementById(relation_div).innerHTML = "";
	if(document.getElementById(title_div)) document.getElementById(title_div).innerHTML = '<img onclick="smwisfFixRelationIssue(\''+relation_div+'\', -1, \'\', 0, 0,\'\')" style="cursor: pointer;" src="' + smwi_plus_image + '"></img><a onclick="smwisfFixRelationIssue(\''+relation_div+'\', -1, \'\', 0, 0,\'\')" style="cursor: pointer;">View all current relations and plain links<a>';
}


