<?php
// This file is utf-8 encoded and contains some special characters.
// Editing this file with an ASCII editor will potentially destroy it!
/**
 * File containing all constants and i18n stuff needed by this extension, embedded in a class.
 * File released under the terms of the GNU GPL v3.
 *
 * @file
 */

// Do not access this file directly…
if (!defined('MEDIAWIKI')) {
	die('This file is a MediaWiki extension, it is not a valid entry point');
}

/*
 * This class both defines all constants needed, and their translations.
 */
class xGlossaryI18n
{
	/*
	 * Magic words codes…
	 */
	const MWORD_GLOSSARY       = 'glossary';
	const MWORD_GLOSSARY_ENTRY = 'glossary_entry';
	const MWORD_GLOSSARY_LINK  = 'glossary_link';
	const MWORD_GLOSSARY_HELP  = 'glossary_help';
	const MWORD_GLOSSARY_TEST  = 'glossary_test';
	
	private static $mwords = array(
		// English.
		'en' => array(
			self::MWORD_GLOSSARY       => array(0, 'glossary'),
			self::MWORD_GLOSSARY_ENTRY => array(0, 'glossary_entry'),
			self::MWORD_GLOSSARY_LINK  => array(0, 'glossary_link'),
			self::MWORD_GLOSSARY_HELP  => array(0, 'glossary_help'),
			self::MWORD_GLOSSARY_TEST  => array(0, 'glossary_test'),
		),
		// French.
		'fr' => array(
			self::MWORD_GLOSSARY       => array(0, 'glossaire'),
			self::MWORD_GLOSSARY_ENTRY => array(0, 'glossaire_entrée'),
			self::MWORD_GLOSSARY_LINK  => array(0, 'glossaire_lien'),
			self::MWORD_GLOSSARY_HELP  => array(0, 'glossaire_aide'),
			self::MWORD_GLOSSARY_TEST  => array(0, 'glossaire_test'),
		)
	);
	
	/*
	 * Get translated magic words, if available…
	 * @param string $lang Language code.
	 * @return array A merge of magic words in asked language, and default ones.
	 */
	public static function getMagicWords($lang)
	{
		// Default is english version!
		return array_merge(self::$mwords['en'], self::$mwords[$lang]);
	}
	
	/*
	 * All i18n messages/texts/… ids.
	 */
	// Errors.
	const ERR_MISC            = "xg_err_misc";
	const MSG_ERR_TMPL_PARAM  = "xg_errtmpl_param";
	// Templates “id”.
	const TMPL_ERROR          = "xg_tmpl_error";
	const TMPL_GLOSSARY       = "xg_tmpl_glossary";
	const TMPL_GROUP          = "xg_tmpl_group";
	const TMPL_ENTRY          = "xg_tmpl_entry";
	const TMPL_ENTRYSYN       = "xg_tmpl_entrysyn";
	const TMPL_DICT           = "xg_tmpl_dict";
	const TMPL_LINK           = "xg_tmpl_link";
	// Templates messages.
	const TMPL_ENTRYSYN_SDESC = "xg_tmpl_entrysyn_sdesc";
	// The printed name of the “misc” group (ight be overriden by settings).
	const TMPL_MISC_GRP_NAME  = "xg_misc_group_name";
	
	// The translated messages/texts/…
	// Note that some of these might be redifined by the xGlossarySetting class,
	// And that some (like the templates) aren’t even given here!
	private static $messages = array(
		// English.
		"en" => array(
			self::ERR_MISC            => "Sorry, an unknown error occurred!",
			self::MSG_ERR_TMPL_PARAM  => "ERROR: “<code>$1</code>” is void.",
			self::TMPL_ERROR          => "",
			self::TMPL_GLOSSARY       => "",
			self::TMPL_GROUP          => "",
			self::TMPL_ENTRY          => "",
			self::TMPL_ENTRYSYN       => "",
			self::TMPL_DICT           => "",
			self::TMPL_LINK           => "",
			self::TMPL_ENTRYSYN_SDESC => "See the “[[$1|$2]]” entry.",
			self::TMPL_MISC_GRP_NAME  => "Misc",
		),
		// French.
		"fr" => array(
			self::ERR_MISC            => "Désolé, une erreur inconnue est survenue !",
			self::MSG_ERR_TMPL_PARAM  => "ERREUR : “<code>$1</code>” est vide.",
			self::TMPL_ERROR          => "",
			self::TMPL_GLOSSARY       => "",
			self::TMPL_GROUP          => "",
			self::TMPL_ENTRY          => "",
			self::TMPL_ENTRYSYN       => "",
			self::TMPL_DICT           => "",
			self::TMPL_LINK           => "",
			self::TMPL_ENTRYSYN_SDESC => "Voyez l’entrée “[[$1|$2]]”.",
			self::TMPL_MISC_GRP_NAME  => "Divers",
		)
	);
	
	/*
	 * Returns all available translations of messages.
	 * @return array
	 */
	public static function getMessages()
	{
		return self::$messages;
	}
	
	/*
	 * The built-in help!
	 * Not really a constant, as arrays can’t be constant…
	 * Each language is defined in its own page, named “glossary.help.xx.php”…
	 */
	static $GLOSSARY_HELP = array("en" => "");
}

// The help is in separate files, as it is quite a long text!
// Add other language files if you translate it (use en version as reference)!
include_once('xglossary.help.en.php');
include_once('xglossary.help.fr.php');








