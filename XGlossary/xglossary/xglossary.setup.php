<?php
// This file is utf-8 encoded and contains some special characters.
// Editing this file with an ASCII editor will potentially destroy it!
/**
 * File containing the init stuff of this extension.
 * File released under the terms of the GNU GPL v3.
 *
 * @file
 */

// Do not access this file directly…
if (!defined('MEDIAWIKI')) {
	die('This file is a MediaWiki extension, it is not a valid entry point');
}

// Autoload classes…
$wgAutoloadClasses['xGlossaryI18n']     = dirname(__FILE__) . '/xglossary.i18n.php';
$wgAutoloadClasses['xGlossaryMain']     = dirname(__FILE__) . '/xglossary.body.php';
$wgAutoloadClasses['xGlossarySettings'] = dirname(__FILE__) . '/xglossary.settings.php';
$wgAutoloadClasses['xGlossaryTests']    = dirname(__FILE__) . '/xglossary.tests.php';


/******************************************************************************
 * The config variables are all regrouped into a (unique) instance of         *
 * xGlossarySettings.                                                          *
 ******************************************************************************/
$wgxGlossarySettings = new xGlossarySettings();

// Glosary extension version…
$wgxGlossaryVersion = "0.1.3";

/******************************************************************************
 * Real setup and init stuff…                                                 *
 ******************************************************************************/

// The setup function…
$wgExtensionFunctions[] = 'efxGlossarySetup';

// The “meta data” about this extension…
$wgExtensionCredits['parserhook'][] = array(
	'name'        => "xGlossary",
	'author'      => "Bastien Montagne",
	'url'         => "http://wiki.blender.org/index.php/User:Mont29/Glossary_Extension",
	'description' => "This extension adds some function hooks to build a "
	               . "glossary page (defined by <code>#glossary:</code>) from the content of children pages, "
	               . "which contain the entries (<code>#glossary_entry:</code>). Then, you can create "
	               . "links to these entries, using <code>#glossary_link:</code> to get the shortdesc.<br />"
	               . "To get a detailed help, create a page with just the <code>#glossary_help:</code> "
	               . "function call…",
	'version'     => $wgxGlossaryVersion,
	);

// The function returning translated “magic words”…
$wgHooks['LanguageGetMagic'][] = 'efxGlossaryLanguageGetMagic';

/*
 * The init function!
 */
function efxGlossarySetup() {
	global $wgParser, $wgMessageCache, $wgxGlossarySettings;
	// The i18n of the messages…
	$wgMessageCache->addMessagesByLang(xGlossaryI18n::getMessages());
	// Override with “settings” messages/texts/…
	$wgxGlossarySettings->update();
	
	$wgParser->setFunctionHook(xGlossaryI18n::MWORD_GLOSSARY,
	                           'xGlossaryMain::renderGlossary');
	$wgParser->setFunctionHook(xGlossaryI18n::MWORD_GLOSSARY_ENTRY,
	                           'xGlossaryMain::renderGlossaryEntry');
	$wgParser->setFunctionHook(xGlossaryI18n::MWORD_GLOSSARY_LINK,
	                           'xGlossaryMain::renderGlossaryLink');
	$wgParser->setFunctionHook(xGlossaryI18n::MWORD_GLOSSARY_HELP,
	                           'xGlossaryMain::renderGlossaryHelp');
	$wgParser->setFunctionHook(xGlossaryI18n::MWORD_GLOSSARY_TEST,
	                           'xGlossaryTests::renderGlossaryTest');
}

/*
 * The function returning all magic words and their aliases and translations.
 * @param array &$magicWords The reference to the array containing all magic words.
 * @param string $lang The language code.
 * @return bool True if no error…
 */
function efxGlossaryLanguageGetMagic(&$magicWords, $lang) {
	foreach(xGlossaryI18n::getMagicWords($lang) as $mword => $trans)
		$magicWords[$mword] = $trans;
	return true;
}







