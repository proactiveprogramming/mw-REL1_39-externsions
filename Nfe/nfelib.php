<?

/**
 * Setup function for the Database
 * Only need to be run once
 */
function NfeSetupTables(){
	$db = wfGetDB( DB_MASTER );
	$db->doQuery('CREATE TABLE IF NOT EXISTS `feedback` (
		  `f_id` int(11) NOT NULL AUTO_INCREMENT,
		  `user_id` int(11) DEFAULT NULL,
		  `page_id` int(11) NOT NULL,
		  `rev_id` int(10) unsigned NOT NULL,
		  `variante` tinyint(3) DEFAULT NULL,
		  `edits` int(10) DEFAULT NULL,
		  `time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
		  PRIMARY KEY (`f_id`)
		)');

	$db->doQuery('CREATE TABLE IF NOT EXISTS `user_feedback` (
		  `ue_id` int(11) DEFAULT NULL,
		  `variante` int(10) DEFAULT NULL,
		  PRIMARY KEY (`ue_id`)
		);');
}

function NfeGetFeedbackForUser($user_id){
	global $wgNfeFeedbackThankYou;
	global $wgNfeFeedbackAmount;
	global $wgNfeFeedbackProcent;
	global $wgNfeFeedbackHighscore;
	global $wgNfeFreedbackRandom;
	global $wgNfeFeedbackNone;
	
	$db = wfGetDB( DB_MASTER );
	$res = $db->doQuery('SELECT variante from user_feedback WHERE ue_id = '.$user_id);
	if($row = $db->fetchObject( $res )){
		$constModus = (int)$row->variante;
	}else{
		$options = array();
		if($wgNfeFeedbackThankYou) $options[] = 0;
		if($wgNfeFeedbackAmount) $options[] = 1;
		if($wgNfeFeedbackProcent) $options[] = 2;
		if($wgNfeFeedbackHighscore) $options[] = 3;
		if($wgNfeFreedbackRandom) $options[] = 4;
		if($wgNfeFeedbackNone) $options[] = 5;
		
		$rand = Rand(0, count($options)-1);
		$db->doQuery('INSERT INTO user_feedback (ue_id, variante) VALUES ('.$user_id.','.$options[$rand].')');
		$constModus = $options[$rand];
	}
	if ($constModus == 4){
		if($wgNfeFeedbackThankYou) $options[] = 0;
		if($wgNfeFeedbackAmount) $options[] = 1;
		if($wgNfeFeedbackProcent) $options[] = 2;
		if($wgNfeFeedbackHighscore) $options[] = 3;
		if($wgNfeFeedbackNone) $options[] = 5;
		$rand = Rand(0, count($options)-1);
		$constModus = $options[$rand];
	}
	return $constModus;
}

function NfeParserHook( &$out, &$text ) {
	global $wgNfeIgnorIP;
	global $wgDelayTime;
	global $wgUser;
	global $wgArticle;
	global $mediaWiki;
	global $wgOut;
	global $action;
	global $wgWikiPath;
	global $wgNfePath;
	global $wgNfeCreatDatabase;
	
	$user_id = $wgUser->getID();
	if($mediaWiki->params["action"] != "view") return true;
	if($user_id == 0 && $wgNfeIgnorIP) return true;
	if(!isset($wgArticle->mRevision)) return true;
	if($wgArticle->mRevision->mUser != $user_id) return true;
	
	$db = wfGetDB( DB_MASTER );
		
	if(!isset($wgNfeCreatDatabase) || $wgNfeCreatDatabase) NfeSetupTables();	
	$constModus = NfeGetFeedbackForUser($user_id);
	$data = $db->doQuery("SELECT * FROM `feedback` 
		WHERE page_id = ".$wgArticle->getTitle()->mArticleID." 
		and rev_id = ".$wgArticle->mRevision->getId());
	
	if(!$db->fetchObject($data)){
		// Read the Time the Article was changes form the database
		$data = $db->doQuery('SELECT * FROM `feedback` WHERE time > (NOW() - INTERVAL '.$wgDelayTime.' SECOND)');
		$recentlyChangedSomething = $db->fetchObject($data);		
		//$daten = $db->doQuery('SELECT rev_timestamp FROM revision WHERE rev_user = "'.$user_id.'" AND rev_page = "'.$wgArticle->getID().'" ORDER BY `rev_id` DESC LIMIT 1 ');
		// Exclude File Uploads
		$found_en = strpos($wgArticle->getTitle(),"File:");
		$found_de = strpos($wgArticle->getTitle(),"Datei:");
		if($found_en === 0 || $found_de === 0){
			$file_upload = true;
		}
		if(!$recentlyChangedSomething){
			if(true){
				//um zu verhindern das ein Datnesatz zweimal gespeichert wird
				$daten = $db->doQuery('SELECT user_id FROM feedback WHERE user_id = "'.$wgUser->getName().'" AND page_id = "'.$wgArticle->getTitle()->getText().'"  AND edits = "'.$wgUser->getEditCount().'"');
				$dat = $db->fetchObject($daten);
				if($dat != null){
					$db->doQuery('DELETE FROM feedback WHERE user_id = "'.$wgUser->getName().'" AND page_id = "'.$wgArticle->getId().'"  AND edits = "'.$wgUser->getEditCount().'"');
				}
				// If this is a FileUpload, note that
				if(isset($file_upload)){
					$variant = $constModus + 10;
				}else{
					$variant = $constModus;
				}
				$db->doQuery('INSERT INTO feedback (f_id, user_id, page_id, rev_id, variante, edits)
						VALUES ( null, "'.$wgUser->getId().'", "'.$wgArticle->getTitle()->mArticleID.'", '.$wgArticle->mRevision->getId().', "'.$variant.'", "'.$wgUser->getEditCount().'");');				
					

				//suche ob ein Inhaltsverzeichnis vorhanden ist
				$htmlText = $text;
				$findMich = '<div class="contentarea">';
				$position = strpos($htmlText, $findMich_ger) + strlen($findMich);

				if ($position != false) {//Falls ja
					$html_start = substr($htmlText, 0, $position);
					$rest3 = $text;
					$text = '';
				}
				else {
					if(isset($file_upload)){
						//TODO:Improve
						$file_start = strpos($htmlText, '</ul><div class', 0);
						$str_feedback_prefix = substr($htmlText, 0, $file_start);
						$str_feedback_prefix = $str_feedback_prefix.'<table width=100%><tr><td align="center">';
						$rest3 = substr($htmlText, $file_start, strlen($htmlText));
						echo "pos=".$rest3;
					}else{
						$rest3 = $text;
						$text = '';
						$str_feedback_prefix = '<table width=100%><tr><td align="right">';
					}
				}
					
				switch($constModus){
					case 0:
						// Displaying a simple Thank you Message
						if($wgNfeEnglish){
							$str_feedback = '
							<span>
							<table width="450" border="1" BORDERCOLOR=#000018>
							<tr>
								<td width="450" height="150" style="background-image:url('.$wgNfePath.'bg.png); text-align: center">
								<h3><font color=#FF0000>Thank you for contributing to the wiki!</font></h3><br>
								<h3>    Visit the <a href="'.$wgWikiURL.'/">main page</a>
								 to add more data into the wiki.</h3></td>
							</tr>
							</table></span>' ;
						}else{
							$str_feedback = '
							<span>
							<table width="450" border="1" BORDERCOLOR=#000018>
							<tr>
								<td width="450" height="150" style="background-image:url('.$wgNfePath.'bg.png); text-align: center">
								<h3><font color=#FF0000>Vielen Dank f&uuml;r die Teilnahme am Wiki!</font></h3><br>
								<h3>Um noch mehr beizutragen besuchen Sie bitte die <a href="'.$wgWikiURL.'/">Hauptseite</a> des Wikis.</h3></td>
							</tr>
							</table></span>' ;
						}
						break;
					case 1:
						// Displaying the amount of contributions
						$daten = $db->doQuery('SELECT user_editcount FROM user WHERE user_id = "'.$user_id.'"');
						$daten = $db->fetchObject($daten);
						//$daten = mysql_fetch_array($daten);
						if($wgNfeEnglish){
							$str_feedback =  '
							<table border="1" BORDERCOLOR=#000018>
							<tr>
								<td width="450" height="150" style="background-image:url('.$wgNfePath.'bg.png); text-align: center">
								<h3><font color=#FF0000>You have contributed with '.$daten->user_editcount.' edits to our wiki so far!</font></h3><br>
								<h3>    Visit the <a href="'.$wgWikiURL.'/">main page</a> to add more data into the wiki.</h3></td>
							</tr>
							</table>';
						}else{
							$str_feedback =  '
							<table border="1" BORDERCOLOR=#000018>
							<tr>
								<td width="450" height="150" style="background-image:url('.$wgNfePath.'bg.png); text-align: center">
								<h3><font color=#FF0000>Sie haben bisher '.$daten->user_editcount.' Bearbeitungen im Wiki!</font></h3><br>
								<h3>Um noch mehr beizutragen besuchen Sie bitte die <a href="'.$wgWikiURL.'/">Hauptseite</a> des Wikis.</h3></td>
							</tr>
							</table>';
						}
						break;
					case 2:
						// Displaying the % of the wiki contributions
						$daten = $db->doQuery('SELECT COUNT( t1.user_id ) AS "higher"
											FROM user as t1, user_feedback as t2
											WHERE t1.user_id = t2.ue_id AND t2.variante < 99 AND t1.user_editcount >= ( 
												SELECT user_editcount
												FROM user
												WHERE user_id = "'.$wgUser->getId().'") ');
						$daten = $db->fetchObject($daten);
						$higher = $daten->higher;
						$daten = $db->doQuery('SELECT COUNT( * ) AS "ges"
											FROM user');
						$daten = $db->fetchObject($daten);
						$ges = $daten->ges;
						$pos = (double)($higher)/($ges);
						$pos = (int)($pos * 100);
						// Fix for first place
						if(!isset($higher) || $higher==0){
							$pos = 1;
						}
						// Fix for last place
						if($higher == $ges){
							$pos = 99;
						}
						if($wgNfeEnglish){
							$str_feedback =  '
							<table border="1" BORDERCOLOR=#000018>
							<tr>
								<td width="450" height="150" style="background-image:url('.$wgNfePath.'bg.png); text-align: center">
								<h3><font color=#FF0000>You are in the top '.$pos.'% of our wiki contributers so far!</font></h3><br>
								<h3>    Visit the <a href="'.$wgWikiURL.'/">main page</a> to add more data into the wiki.</h3></td>
							</tr>
							</table>';
						}else{
							$str_feedback =  '
							<table border="1" BORDERCOLOR=#000018>
							<tr>
								<td width="450" height="150" style="background-image:url('.$wgNfePath.'bg.png); text-align: center">
								<h3><font color=#FF0000>Sie sind bisher in den Top '.$pos.'% der Beitragenden im Wiki!</font></h3><br>
								<h3>Um noch mehr beizutragen besuchen Sie bitte die <a href="'.$wgWikiURL.'/">Hauptseite</a> des Wikis.</h3></td>
							</tr>
							</table>';
						}
						break;
					case 3:
						// Displaying a small poriton of the Highscore
							
						// Getting the count of user_ids which are not on varient 99(e.g. admin) and have a Higher Editcount than this user.
						$sql = 'SELECT COUNT(user_id) as "Number" FROM user as t1, user_feedback as t2 WHERE t1.user_id = t2.ue_id AND t2.variante < 99 AND t1.user_editcount > ( SELECT user_editcount FROM user WHERE user_id = "'.$user_id.'")';
						$qry = $db->doQuery($sql);
						$res = $db->fetchObject($qry);
						$pos = $res->Number;

						// Getting the count of users_ids which ar noch admin and have equal or less amount of edis than this user (excluding himself)
						$sql = 'SELECT COUNT(t1.user_id) as "Number" FROM user as t1, user_feedback as t2 WHERE t1.user_editcount <= ( SELECT user_editcount FROM user WHERE user_id = "'.$user_id.'") AND t1.user_id != "'.$user_id.'" AND t1.user_id = t2.ue_id AND t2.variante <99';
						$qry = $db->doQuery($sql);
						$res = $db->fetchObject($qry);
						$pos2 = $res->Number;

						$str_feedback =  '
						<table border="1" BORDERCOLOR=#000018"><tr><td><table border = "0" style="background-image:url('.$wgNfePath.'bg.png)">
								<tr>
								<td style="text-align:center"></td>
								<td style="text-align:center"><h3><font color=#FF0000> Position </font></h3></td>
								<td style="text-align:center"><h3><font color=#FF0000> Name </font></h3></td>
								<td style="text-align:center"><h3><font color=#FF0000> ';
						if($wgNfeEnglish) {
							$str_feedback = $str_feedback . 'Edits';
						}else{
							$str_feedback = $str_feedback . 'Bearbeitungen';
						}
						$str_feedback = $str_feedback . '</font></h3></td>
								</tr>';
						//Getting the 2 higher users that are not admin
						$qry = $db->doQuery('SELECT t1.user_name, t1.user_editcount FROM user as t1, user_feedback as t2 WHERE t1.user_id = t2.ue_id AND t2.variante < 99 AND t1.user_editcount > (SELECT user_editcount FROM user WHERE user_id = "'.$user_id.'" ) ORDER BY `user_editcount` ASC LIMIT 2');
						$first_highest = $db->fetchObject($qry);	// First postion
						$second_highest = $db->fetchObject($qry);	// Second positon

						//Displaying user 2 postion ahead of this user if aviable
						if($pos > 1){
							$str_feedback =  $str_feedback . '
										<tr><font color=#2e8b57>
											<td style="width:20px"></td>
											<td style="text-align: center; width:60px"><font color=#2e8b57><b>' ;    
							$str_feedback =  $str_feedback . ($pos - 1);
							$str_feedback =  $str_feedback . '</b></font></td><td style="text-align: center ; width:200px"><font color=#2e8b57><b>';
							$str_feedback =  $str_feedback . ($second_highest->user_name);
							$str_feedback =  $str_feedback . '</b></font></td><td style="text-align: center ; width:100px"><font color=#2e8b57><b>';
							$str_feedback =  $str_feedback . ($second_highest->user_editcount);
							$str_feedback =  $str_feedback . '</b></font></td></tr>';
						}
						//Displaying user 1 postion ahead of this user if aviable
						if($pos > 0){
							$str_feedback =  $str_feedback . '<tr><td style="width:20px"></td>
									<td style="text-align: center; width:60px"><font color=#2e8b57><b>';    
							$str_feedback =  $str_feedback . $pos;
							$str_feedback =  $str_feedback . '</b></font></td><td style="text-align: center ; width:200px"><font color=#2e8b57><b>' ;
							$str_feedback =  $str_feedback . ($first_highest->user_name);
							$str_feedback =  $str_feedback . '</b></font></td><td style="text-align: center ; width:100px"><font color=#2e8b57><b>' ;
							$str_feedback =  $str_feedback . ($first_highest->user_editcount);
							$str_feedback =  $str_feedback . '</b></font></td></tr>';
						}
						//Displaying this user
						$str_feedback =  $str_feedback . '<tr><td style="width:20px"></td>
									<td style="text-align: center; width:60px"><font color=#FF0000><b>';   		  
						$str_feedback =  $str_feedback . ($pos + 1);
						$str_feedback =  $str_feedback . '</b></font></td><td style="text-align: center ; width:200px"><font color=#FF0000><b>';
						$str_feedback =  $str_feedback . ($wgUser->getName());
						$str_feedback =  $str_feedback . '</b></font></td><td style="text-align: center ; width:100px"><font color=#FF0000><b>';
						$str_feedback =  $str_feedback . ($wgUser->getEditCount());
						$str_feedback =  $str_feedback . '</b></font></td></tr>';
						// if a user is below this user
						if($pos2 > 0){
							$str_feedback =  $str_feedback . '<tr><td style="width:20px"></td>
									<td style="text-align: center; width:60px"><font color=#2e8b57><b>';    
							$str_feedback =  $str_feedback . ($pos + 2);
							$qry = $db->doQuery('SELECT t1.user_name as user_name, t1.user_editcount as user_editcount FROM user as t1, user_feedback as t2 WHERE t1.user_editcount <= ( SELECT user_editcount FROM user WHERE user_id = "'.$user_id.'" ) AND t1.user_id != "'.$user_id.'" AND t2.variante < 100 AND t1.user_id = t2.ue_id ORDER BY t1.user_editcount DESC LIMIT 2');

							$lower = $db->fetchObject($qry);
							$lowest = $db->fetchObject($qry);
							$str_feedback =  $str_feedback . '</b></font></td><td style="text-align: center ; width:200px"><font color=#2e8b57><b>' ;
							$str_feedback =  $str_feedback . ($lower->user_name);
							$str_feedback =  $str_feedback . '</b></font></td><td style="text-align: center ; width:100px"><font color=#2e8b57><b>' ;
							$str_feedback =  $str_feedback . ($lower->user_editcount);
							$str_feedback =  $str_feedback . '</b></font></td></tr>';
						}
						if($pos2 > 1){
							$str_feedback =  $str_feedback . '<tr><td style="width:20px"></td>
									<td style="text-align: center; width:60px"><font color=#2e8b57><b>';    
							$str_feedback =  $str_feedback . ($pos + 3);
							$str_feedback =  $str_feedback . '</b></font></td><td style="text-align: center ; width:200px"><font color=#2e8b57><b>' ;
							$str_feedback =  $str_feedback . ($lowest->user_name);
							$str_feedback =  $str_feedback . '</b></font></td><td style="text-align: center ; width:100px"><font color=#2e8b57><b>' ;
							$str_feedback =  $str_feedback . ($lowest->user_editcount);
							$str_feedback =  $str_feedback . '</b></font></td></tr>';
						}
						if($wgNfeEnglish){
							$str_feedback =  $str_feedback . '
									 <tr><th colspan="4"><h3>    Visit the <a href="'.$wgWikiURL.'/">main page</a>
									 to add more data into the wiki.</h3></th></tr>    					
							</table></td></tr></table></div>
							';
						}else{
							$str_feedback =  $str_feedback . '
									 <tr><th colspan="4"><h3>Um noch mehr beizutragen besuchen Sie bitte die <a href="'.$wgWikiURL.'/">Hauptseite</a> des Wikis.</h3></td></th></tr>    					
							</table></td></tr></table>
							';
						}
							
							
							
				}
				if ($position != false) {
					$str_feedback =  $str_feedback . '</td></tr></table></td></tr></table>';
				}else{
					$str_feedback =  $str_feedback . '</td></tr></table>';
				}
				$text =  '<div class="floatright">'. $str_feedback .'</div>'. $rest3;
			}
		}
	}
	return true;
}
?>