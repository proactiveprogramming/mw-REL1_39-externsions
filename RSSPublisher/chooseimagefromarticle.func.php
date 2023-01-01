<?php
function chooseimagefromarticle($pageid){
	global $wgRSSpublisher;
	global $wgServer;
	global $wgLogo;
	
//SET DEFAULT VALUES	
	if (!isset($wgRSSpublisher['defaultpic'])) $wgRSSpublisher['defaultpic'] = $wgServer.$wgLogo;
	if (!isset($wgRSSpublisher['imagewidth'])) $wgRSSpublisher['imagewidth'] = 88;

///////////////////////////////////////////////////////////////////////////////
//CONNECT DB	
///////////////////////////////////////////////////////////////////////////////
	$dbr = wfGetDB( DB_SLAVE );

///////////////////////////////////////////////////////////////////////////////
//DEFINE CONDITIONS FOR USABLE PICTURE
///////////////////////////////////////////////////////////////////////////////
//CONDITIONS TO USE ONLY PICTURE FROM ACTUEL PAGE
	$conditions = 'il_from = "'.$pageid.'"';
	
//CONDITION FOR USABLE IMAGES - FILTER ONLY RSS COMPATIBLE IMAGE FORMATS
	$conditions .= ' and (il_to LIKE "%.png" or il_to LIKE "%.jpg" or il_to LIKE "%.jpeg" or il_to LIKE "%.gif" or il_to LIKE "%.svg")';

//GET ALL BLACKLISTET IMAGES AND ADD IT TO THE CONDITIONS
	$res = $dbr->select('categorylinks', array('cl_from'), 'cl_to = "RSSpublisher_blacklist" and cl_type = "file"', $fname = 'Database::select',array('ORDER BY' => 'cl_from'));
	foreach($res as $row) $blacklist_cl_from .= 'page_id = '.$row->cl_from.' or ';	
	$blacklist_cl_from = substr($blacklist_cl_from,0,-3);
	$res = $dbr->select('page', array('page_title'), $blacklist_cl_from, $fname = 'Database::select',array());
	foreach($res as $row) $conditions .= ' and il_to != "'.$row->page_title.'"';

///////////////////////////////////////////////////////////////////////////////
//GET IMAGENAME FROM ARTICLE
///////////////////////////////////////////////////////////////////////////////	
//GET ALL USABLE IMAGENAMES FROM ARTICLE TO RANDOM ARRAY AND CHOOSE THE FIRST ONE
	$res = $dbr->select('imagelinks', array('il_to'), $conditions, $fname = 'Database::select',array('ORDER BY' => 'RAND()'));
	foreach($res as $row) $filelist[] = $row->il_to;
	$orgfilename = $filelist[0];

//CHECK IF IMAGE EXIST AND IF IT COMES FROM LOCAL OR OTHERWISE FROM MEDIAWIKI COMMONS. IF NO IMAGE EXIST, IT WILL CHOOSE THE DEFAULT IMAGE
	if ($orgfilename != NULL){
		$res = $dbr->select('image', array('img_name'), 'img_name = "'.$orgfilename.'"' , $fname = 'DatabaseBase::select',array());
		if($res->result->num_rows > 0) $imageinfo = array('url' => $wgServer.'/thumb.php?f='.$orgfilename.'&w='.$wgRSSpublisher['imagewidth'], 'htmlurl' => $wgServer.'/thumb.php?f='.rawurlencode($orgfilename).'&amp;w='.$wgRSSpublisher['imagewidth']);
		else $imageinfo = array('url' => 'http://commons.wikimedia.org/w/thumb.php?f='.$orgfilename.'&w='.$wgRSSpublisher['imagewidth'], 'htmlurl' => 'http://commons.wikimedia.org/w/thumb.php?f='.rawurlencode ($orgfilename).'&amp;w='.$wgRSSpublisher['imagewidth']);
		$image['file'] = $orgfilename;
		
	}
	else $imageinfo = array('url' => $wgRSSpublisher['defaultpic'], 'file' => substr($wgRSSpublisher['defaultpic'],strrpos($wgRSSpublisher['defaultpic'],'/')+1));

//GET SIZE OF IMAGE
	$head = array_change_key_case(get_headers($imageinfo['url'], TRUE));
	$imageinfo['size'] = $head['content-length'][count($head['content-length']) - 1];

//GET IMAGETYPE
	$imageinfo['type'] = image_type_to_mime_type(exif_imagetype($imageinfo['url']));	
	
//RETURN
	return($imageinfo);
}
?>