<?php

require_once ("converter/MPdfConverter.php");

function onTitleMoveComplete(Title &$title, Title &$newtitle, User &$user, $oldid, $newid) {

	// Getting the arcticle's title
	$article = $newtitle -> getDBkey();

	// Initializing the converter
	$pdfConvertor = new MPdfConverter();
	$pdfConvertor -> initialize();
	$data = $pdfConvertor -> getRawPdf(array($article));

	// Creating the repository ref
	$repository = new Repository($GLOBALS['alfURL']);
	try {
		$ticket = $repository -> authenticate($GLOBALS['alfUser'], $GLOBALS['alfPassword']);
	} catch (Exception $e) {
		die('Could not authenticate user "' . $GLOBALS['alfUser'] . '"');
	}

	// Creating the session
	$session = $repository -> createSession($ticket);

	// getting the store
	$store = $session -> getStoreFromString($GLOBALS['alfWikiStore']);

	// getting the space
	$results = $session -> query($store, 'PATH:"' . $GLOBALS['alfWikiSpace'] . '"');
	$wikiSpace = $results[0];

	// Creating node
	$id = substr($_SESSION["lastNode"], strrpos($_SESSION["lastNode"], "/") + 1);
	$node = $session -> getNode($store, $id);

	// Update node's title and saving it
	$node -> cm_name = str_replace("_", " ", $newtitle -> getText());
	$node -> updateContent("cm_content", "application/pdf", "UTF-8", $data);
	$session -> save();

	// Zero value
	$_SESSION["lastNode"] = "";

	return TRUE;
}
?>