<?php

function onArticleFromTitle(Title &$title, &$article) {
	// Some help for ExternalStoreAlfresco module. Code entirely was taken from ExternalStoreAlfresco. Why developers of one didn't do it earlie?
	$url = null;
	$fieldName = 'old_text';
	$revision = Revision::newFromTitle($title);
	if (isset($revision)) {
		$dbr = wfGetDB(DB_SLAVE);
		$row = $dbr -> selectRow('text', array('old_text', 'old_flags'), array('old_id' => $revision -> getTextId()), 'ExternalStoreAlfresco::alfArticleSave');
		$url = $row -> $fieldName;
	}

	// Store the details of the current article in the session
	$_SESSION["title"] = ExternalStoreAlfresco::getTitle($title);
	$_SESSION["lastVersionUrl"] = $url;

	return TRUE;

}
?>