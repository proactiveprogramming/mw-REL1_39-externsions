<?php

// Converter
require_once ("converter/MPdfConverter.php");


// Handler
function onPageContentSave($article) {

	// Getting the arcticle's title
	$article = $article -> getTitle() -> getDBkey();

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

	// Cheking whether was change of article's content. If was, there is must be stored a value in this variable
	if ($_SESSION["lastNode"] === "")
		return true;

	// Creating node
	$id = substr($_SESSION["lastNode"], strrpos($_SESSION["lastNode"], "/") + 1);
	$node = $session -> getNode($store, $id);

	// Update node's content and saving it
	$node -> cm_name = str_replace("_", " ", $article);
	$node -> addAspect("cm_versionable", null);
	$node -> cm_initialVersion = false;
	$node -> cm_autoVersion = false;
	$node -> updateContent("cm_content", "application/pdf", "UTF-8", $data);
	$session -> save();

	// Zero value
	$_SESSION["lastNode"] = "";

	return true;
}
?>
