<?php
function collectarticlechanges(){
	global $wgRSSpublisher;
	global $wgServer;
	global $wgArticlePath;
	if (isset($wgRSSpublisher['namespaces'])){
		$conditions = '(';
		foreach($wgRSSpublisher['namespaces'] as $namespace) $conditions .= 'page_namespace = '.$namespace.' or ';
		$conditions = substr($conditions,0,-4);
		$conditions = ')';
	}
	else $conditions = 'page_namespace = 0';
	
	if (!isset($wgRSSpublisher['showredirects']) or $wgRSSpublisher['showredirects'] == false) $conditions .= ' and page_is_redirect = 0';
	
	$options['ORDER BY'] = 'page_touched DESC';
	if (isset($wgRSSpublisher['limit'])){
		if ($wgRSSpublisher['limit'] == 'unlimited');
		else $options['LIMIT'] = $wgRSSpublisher['limit'];
	}
	else $options['LIMIT'] = 30;
	
	$i = 0;
	$datetimeformat = 'D, d M Y H:i:s O';
	
	$dbr = wfGetDB( DB_SLAVE );
	$res = $dbr->select('page', array('page_title','page_touched','page_id','page_latest'), $conditions, $fname = 'Database::select',$options);		
	foreach($res as $row) {
		$items[$i]['type'] = 'collectarticlechanges';
		$items[$i]['title'] = str_replace('_', ' ',$row->page_title);
		$items[$i]['link'] = $wgServer . str_replace('$1', urlencode($row->page_title), $wgArticlePath);
		$items[$i]['pubdate'] = date($datetimeformat, strtotime($row->page_touched));
		$items[$i]['unixpubdate'] = $row->page_touched;
		$imageinfo = chooseimagefromarticle($row->page_id);
		if($imageinfo != NULL) {
			$items[$i]['imageurl'] = $imageinfo['htmlurl'];
			$items[$i]['imagesize'] = $imageinfo['size'];
			$items[$i]['imagetype'] = $imageinfo['type'];
			$items[$i]['description'] = wfMsg('rsspublisher-imagelicense');
		}
		$items[$i]['guid'] = $wgServer . str_replace('$1', urlencode($row->page_title), $wgArticlePath) . '&amp;oldid=' . $row->page_latest;
		
		$dbr_revision = wfGetDB( DB_SLAVE );
		$res_revision = $dbr_revision->select('revision', array('rev_text_id','rev_parent_id'), array('rev_page = "'.$row->page_id.'"'), $fname = 'Database::select', array('LIMIT' => 1, 'ORDER BY' => 'rev_timestamp DESC'));		

		foreach($res_revision as $row_revision) {
			
			$dbr_old_text = wfGetDB( DB_SLAVE );
			$res_old_text = $dbr_old_text->select('text', array('old_text'), array('old_id = "'.$row_revision->rev_text_id.'"'), $fname = 'Database::select', array('LIMIT' => 1));		

			foreach($res_old_text as $row_old_text) {
				$oldtext = $row_old_text->old_text;
				if (strlen($oldtext) > 100) $items[$i]['description'] .= ' '.cleantext($oldtext,$items[$i]['title']);
				$items[$i]['description'] .= '<br />'.wfMsg('rsspublisher-linktoarticlechanges').': <a href="'.$wgServer . str_replace('$1', urlencode($row->page_title), $wgArticlePath) . '&amp;diff=next&amp;oldid='.$row_revision->rev_parent_id.'">Link</a>';
			}
		}
		$i++;
	}
	return($items);
}
?>