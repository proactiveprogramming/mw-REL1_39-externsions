/**
  * File: MOCA_General_Functions.js
  *
  * Description: Contains Javascript functions used all over the extension
  *
  * @author Chrysovalanto Kousetti
  * @email valanto@gmail.com
  *
  */

/** Sets the cookies of the wiki editor
   * Adopted from http://www.w3schools.com/js/js_cookies.asp
   *
   * @params String, String, int
   */
function setCookie( c_name,value,expiredays ) {
	var exdate=new Date();
	var value_array = new Array();
	var i = 0;
	while(value.length > parseInt(max_cookie_size)){
		var value_1 = value.substring(0, (parseInt(max_cookie_size)-1));
		var value = value.substring(parseInt(max_cookie_size), value.length);
		value_array[i] = value_1;
		i++;
	}
	value_array[i] = value;
	exdate.setDate(exdate.getDate()+expiredays);
	i = 0
	for(v in value_array){
		var cookiename = c_name + i;
			document.cookie=cookiename+ "=" +escape(value_array[v])+
		((expiredays==null) ? "" : ";expires="+exdate.toGMTString())	
		i++;
	}
		document.cookie=c_name+"_size"+ "=" +escape(i)+
		((expiredays==null) ? "" : ";expires="+exdate.toGMTString());
}

/** Trims a piece of string from surrounding spaces and returns it.
   * adopted from http://www.pbdr.com/jscript/trimstr.htm
   *
   * @params String
   * @return String
   */
function trimString(sInString) {
	if(sInString != "" && sInString !=null){
		sInString = sInString.replace( /^\s+/g, "" );// strip leading
		return sInString.replace( /\s+$/g, "" );// strip trailing
	}
	else return "";
}

/** Trims a piece of string from surrounding spaces and returns it.
   * adopted from http://forum.weborum.com/lofiversion/index.php/t3317.html
   * adopted from http://www.din.or.jp/~hagi3/JavaScript/JSTips/Mozilla/Samples/NSHTMLTextAreaElement.htm
   * adopted from http://blog.vishalon.net/Post/57.aspx
   *
   * @params int, int
   */
function smwifWhereInWikiText(start, len){
	var spare = 0;
	var textarea = document.getElementById(textboxTag);
	var part_text = textarea.value.substring(0, parseInt(start));
	
	// ATTEMPT START: to fix characters problem
	var special_chars = smwi_special_chars_array.split('|');
	var i = 0;
	var special_chars_multi = new Array();
	for (sChar in special_chars){
		special_chars_multi[i] = special_chars[sChar].split(',');
		i++;
	}
	
	for (s1 in special_chars_multi){
		special_chars_multi2 = special_chars_multi[s1];
		spare+= part_text.split(special_chars_multi2[0]).length -1;
		
	}
	// ATTEMPT END: to fix characters problem
	
	if(textarea.setSelectionRange){
		textarea.setSelectionRange(parseInt(start), (parseInt(start)+parseInt(len)));
	}
	else{
		
		
		spare += part_text.split("\n").length-1;
		var range = textarea.createTextRange();
		range.collapse(true);
		
		range.moveStart('character',(parseInt(start)-parseInt(spare)) );
		range.moveEnd('character',parseInt(len));
		range.select();
	}
	textarea.focus();
}
