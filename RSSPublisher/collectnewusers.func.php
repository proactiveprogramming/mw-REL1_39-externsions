<?php
function collectnewusers(){
	global $wgRSSpublisher;
	global $wgServer;
	global $wgArticlePath;
	
	$options['ORDER BY'] = 'user_registration DESC';
	if (isset($wgRSSpublisher['limit'])){
		if ($wgRSSpublisher['limit'] == 'unlimited');
		else $options['LIMIT'] = $wgRSSpublisher['limit'];
	}
	else $options['LIMIT'] = 30;
	
	$i = 0;
	$datetimeformat = 'D, d M Y H:i:s O';
	
	$dbr = wfGetDB( DB_SLAVE );
	$res = $dbr->select('user', array('user_name','user_registration','user_id'), $conditions, $fname = 'Database::select',$options);		
	foreach($res as $row) {
		$items[$i]['type'] = 'collectnewusers';
		$items[$i]['title'] = str_replace('_', ' ',$row->user_name);
		$items[$i]['link'] = $wgServer . str_replace('$1', 'User:'.str_replace(' ','_',$row->user_name), $wgArticlePath);
		$items[$i]['pubdate'] = date($datetimeformat, strtotime($row->user_registration));
		$items[$i]['unixpubdate'] = $row->user_registration;
		$imageinfo = chooseimagefromarticle();
		if($imageinfo != NULL) {
			$items[$i]['imageurl'] = $imageinfo['htmlurl'];
			$items[$i]['imagesize'] = $imageinfo['size'];
			$items[$i]['imagetype'] = $imageinfo['type'];
			$items[$i]['description'] = wfMsg('rsspublisher-newusers-body',$row->user_name);
		}
		$items[$i]['guid'] = $wgServer . str_replace('$1', 'User:'.urlencode($row->user_name), $wgArticlePath);// . '&amp;oldid=' . $row->user_registration;
		
		$i++;
	}
	return($items);
}
?>