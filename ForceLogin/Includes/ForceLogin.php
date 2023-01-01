<?php

$wgHooks['AlternateEdit'][]='redirectOnEdit';

function redirectOnEdit($editPage){
	global $wgUser, $wgOut, $wgTitle, $wgScriptPath;
	if(!$wgUser->isLoggedIn()){
		$wgOut->redirect($wgScriptPath.'/index.php?title=Special:UserLogin&returnto='.$wgTitle.'&returntoquery=action%3Dedit');
	}
	return true;
}
