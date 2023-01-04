/**
  * File: MOCA_Help_Functions.js
  *
  * Description: Contains Javascript functions used to provide help popups.
  *
  * @author Chrysovalanto Kousetti
  * @email valanto@gmail.com
  *
  */
  
/** Show help popup
   *
   * @params String, String
   */
function smwi_help_show_popup_tip( head, message) {
	var expiredays = 30;
	var exdate=new Date();	
	exdate.setDate(exdate.getDate()+expiredays);
	document.cookie="smwi_help_head"+ "=" +escape(head)+
		((expiredays==null) ? "" : ";expires="+exdate.toGMTString());
		
	document.cookie="smwi_help_body"+ "=" +escape(message)+
		((expiredays==null) ? "" : ";expires="+exdate.toGMTString());

	popup_window = window.open(smwi_help_popup, null,'width=400,height=200,resizable=no,scrollbars=yes,toolbar=no,location=no,status=no,menubar=no,copyhistory=no');
}

/** Closes help popup
   *
   * @params Window
   */
function smwi_help_close_popup_tip (p){
	if(p) p.close;
	return;
}