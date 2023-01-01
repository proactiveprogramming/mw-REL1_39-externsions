<?php
function cleantext($string,$title){
	$string = trim($string);
	$string = str_replace('{{PAGENAME}}',$title,$string);
	$string = str_replace("[[","",$string);
	$string = str_replace("]]","",$string);
	$string = str_replace("'''",'',$string);
	$string = str_replace("''",'',$string);
	$string = strip_tags($string);
	$string = str_replace('  ',' ',$string);
	
	
	
	while(substr_count($string,'{') >= 1 and substr_count($string,'}') >= 1){
		$end = strpos($string,'}');	
		$start = strrpos(substr($string,0,$end),'{');
		$string = substr($string,0,$start) . substr($string,$end + 1);
	}

	$string = substr($string,0,strpos($string,"\n\n"));
	
	return($string);
}
?>