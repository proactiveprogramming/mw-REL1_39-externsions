<?php
/**
 * ITask Tracker System
 * 
 * Setup and Hooks for the BugTracking extension
 *
 */
if (! defined('MEDIAWIKI')) {
	echo 'This file is an extension to the MediaWiki software and cannot be used standalone.';
	die;
}

$wgITaskTrackerExtensionVersion = '2.1';

$wgExtensionCredits['specialpage'][] = array(
	'name'          => 'ITaskTracker',
	'author'        => 'Mis Kronos',
	'email'         => 'mis@kronosinvestments.net',
	'description'   => 'The ITaskTracker extension',
	'url'           => 'https://www.mediawiki.org/wiki/Extension:ITaskTracker',
	'description'   => 'The ITaskTracker extension is iTask list to manage daily activity and project list for each members of your Team. ',
	'version'       => $wgITaskTrackerExtensionVersion
);

$dir = dirname(__FILE__) . '/';
//echo $dir;

// Tell MediaWiki to load the extension body.
$wgExtensionMessagesFiles['ITaskTracker'] = $dir . 'ITaskTracker.i18n.php';

// Autoload the IssueTracker class
$wgAutoloadClasses['ITaskTracker'] = $dir . 'ITaskTracker.body.php'; 


// Let MediaWiki know about your new special page.
$wgSpecialPages['ITaskTracker'] = 'ITaskTracker'; 

// Add Extension Functions
$wgExtensionFunctions[] = 'wfITaskTrackerSetParserHook';

// Add any aliases for the special page
$wgHooks['LanguageGetSpecialPageAliases'][] = 'wfITaskTrackerLocalizedTitle';
$wgHooks['ParserAfterTidy'][] = 'wfITaskTrackerDecodeOutput';

/**
 * A hook to register an alias for the special page
 * @return bool
 */
function wfITaskTrackerLocalizedTitle(&$specialPageArray, $code = 'en') 
{
	// The localized title of the special page is among the messages of the extension:
	//wfLoadExtensionMessages('ITaskTracker');
	  
	// Convert from title in text form to DBKey and put it into the alias array:
	//$textIbug = wfMsg('itasktracker');
        //$textIbug = wfMessage( 'itasktracker' ); 
        $textIbug = 'iTask Tracker';
    
	$titleIbug = Title::newFromText($textIbug);
	
	//$specialPageArray['ITaskTracker'][] = $titleIbug->getDBKey();
	$specialPageArray['ITaskTracker'][] = "ITaskTracker";
        
	return false;
}

/**
 * Register parser hook
 * @return void
 */
function wfITaskTrackerSetParserHook() 
{
	global $wgParser;
////	$wgParser->setHook('Ibugissues', array('ITaskTracker', 'executeRecHook'));
	
}

/**
 * Processes HTML comments with encoded content.
 * 
 * @param OutputPage $out Handle to an OutputPage object presumably $wgOut (passed by reference).
 * @param String $text Output text (passed by reference)
 * @return Boolean Always true to give other hooking methods a chance to run.
 */
function wfITaskTrackerDecodeOutput(&$parser, &$text) 
{
    /* $text = preg_replace(
        '/@ENCODED@([0-9a-zA-Z\\+\\/]+=*)@ENCODED@/e',
        'base64_decode("$1")',
        $text
    );*/
    $text = preg_replace_callback(
        '/@ENCODED@([0-9a-zA-Z\\+\\/]+=*)@ENCODED@/',
        function ($matches) { 
            return base64_decode($matches[0]);
        },
        $text
    );
    return true;
}
