<?php
/* WorkingWiki extension for MediaWiki 1.13 and later
 * Copyright (C) 2010 Lee Worden <worden.lee@gmail.com>
 * http://lalashan.mcmaster.ca/theobio/projects/index.php/WorkingWiki
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 * http://www.gnu.org/copyleft/gpl.html
 */

/**
 * Protect against register_globals vulnerabilities.
 * This line must be present before any global variable is referenced.
 */
if( !defined( 'MEDIAWIKI' ) ) {
  echo( "This is an extension to the MediaWiki package and cannot be run standalone.\n" );
  die( -1 );
}
 
# ===== Extension credits that will show up on Special:Version =====
$wgExtensionCredits['specialpage'][] = array(
  'name'         => 'WorkingWiki',
  'version'      => '1.0',
  'author'       => 'Lee Worden', 
  'url'          => 
    'http://lalashan.mcmaster.ca/theobio/projects/index.php/WorkingWiki',
  'description'  => 
    'Extension to manage source code and computations within MediaWiki.'
);
$wgExtensionCredits['parserhook'][] = array(
  'name'         => 'WorkingWiki',
  'version'      => '1.0',
  'author'       => 'Lee Worden', 
  'url'          => 
    'http://lalashan.mcmaster.ca/theobio/projects/index.php/WorkingWiki',
  'description'  => 
    'Extension to manage source code and computations within MediaWiki.'
      . ' adds &lt;source-file> and &lt;project-file> tags, plus a handful of'
      . ' other tags that it uses internally.  WorkingWiki also provides'
      . ' the use of <nowiki>$$...$$ for math, __DISABLE_MAKE__ and __ENABLE_MAKE__</nowiki>'
      . ' to control the flow of computations on a page, and a few other'
      . ' constructs.'
);

# ===== Import WW variable names and definitions ====

# can't use $wwExtensionDirectory here, because it isn't set yet
require_once dirname( __FILE__ ) . '/WorkingWiki.defs.php';

# ===== Create ProjectDescription: namespace =====

# you can define NS_PROJECTDESCRIPTION in LocalSettings.php or such
# before calling this file, if you need these two to be different numbers.
if ( !defined( 'NS_PROJECTDESCRIPTION' ) ) {
  define('NS_PROJECTDESCRIPTION', 100);
}
define('NS_PROJECTDESCRIPTION_TALK', NS_PROJECTDESCRIPTION + 1);

$wgExtraNamespaces[NS_PROJECTDESCRIPTION]
  = $wwProjectDescriptionNamespaceName;
$wgExtraNamespaces[NS_PROJECTDESCRIPTION_TALK]
  = $wwProjectDescriptionNamespaceName.'_talk';

# ===== Register hooks, create global object, and call in the WW classes =====

# create the globally shared objects to do the work.
require_once $wwExtensionDirectory . "/WWInterface.php";
require_once $wwExtensionDirectory . "/WWStorage.php";
$wwContext = new stdClass;
$wwContext->wwInterface = new WWInterface;
$wwContext->wwStorage = new WWStorage;

# default preferences settings
$wgDefaultUserOptions['mathml'] = false;
$wgDefaultUserOptions['ww-background-jobs-emails'] = $wwAllowBackgroundJobEmails;
$wgDefaultUserOptions['ww-dynamic-display'] = 'ifpossible';
$wgDefaultUserOptions['ww-leekspin'] = false;

# setup functions
$wgHooks['ParserFirstCallInit'][] = 'ww_setup_parserhooks';
if ( ! defined( 'MW_API' ) ) {
  $wgExtensionFunctions[] = 'ww_setup';
}

# register XML tags with the parser
function ww_setup_parserhooks(&$parser){
  global $wwContext;
  #wwLog("SETUP PARSERHOOKS");
  $parser->setHook( 'source-file',
    array( $wwContext->wwInterface, 'source_file_hook' )
  );
  $parser->setHook( 'project-file',
    array( $wwContext->wwInterface, 'project_file_hook' )
  );
  $parser->setHook( 'project-description',
    array( $wwContext->wwInterface, 'render_project_description' )
  );
  $parser->setHook( 'syntaxhighlight_mk',
    array( $wwContext->wwInterface, 'syntaxhighlight_mk_hook' )
  );
  $parser->setHook( 'toggle-make-enabled',
    array( $wwContext->wwInterface, 'toggle_make_enabled_hook' )
  );
  # workaround for SyntaxHighlight_GeSHi bug for MW < 1.24 or so
  $parser->setHook( 'source',
    array( 'SyntaxHighlight_GeSHi', 'parserHook' )
  );
  return true;
}

function ww_setup() {
  # check for disable-make=true argument in the URL
  global $wwMakeCompletelyDisabled;
  $dis = RequestContext::getMain()->getRequest()->getVal( 'disable-make', null );
  if ($dis !== null and wwfArgumentIsYes($dis))
  { $wwMakeCompletelyDisabled = true;
    $wwContext->wwInterface->record_warning('Making of project files is disabled '
      . 'while evaluating this page.');
  }
  # also list the client-side resources that are always loaded
  global $wgOut;
  $wgOut->addModules( array(
    'ext.workingwiki.top',
    'ext.workingwiki',
  ) );
  global $wwEnableLeekspin;
  if ( $wwEnableLeekspin ) { //and RequestContext::getMain()->getUser()->getOption( 'ww-leekspin' ) ) {
	  $wgOut->addModules( array(
		  'ext.workingwiki.leekspin',
	  ) );
  }
  if ( wwfReadOnly() ) {
    $wgOut->addModules( array(
      'ext.workingwiki.readonly',
    ) );
  }
  if ( wwfDynamicDisplayInEffect() ) {
	  # when using dynamic display, use the pulldown links menus,
	  # even on pages without any dynamic project files
	  $wgOut->addModules( array(
		  'ext.workingwiki.pulldown-altlinks',
	  ) );
  }
  global $wwContext, $wwApiMessages, $wwUseComet;
  $wgOut->addJsConfigVars( array(
    'wwApiMessages' => $wwApiMessages,
    'wwProjectUriBase' => $wwContext->wwStorage->local_uri_base(),
    'wwUseComet' => $wwUseComet, // todo: make this obsolete
  ) );

  if ( $wwUseComet ) {
	  global $wgVersion, $wgResourceModules;
	  #$wgResourceModules['ext.workingwiki.dynamic-project-files']['dependencies'][] = 'ext.workingwiki.comet';
	  $wgOut->addModules( array(
		  'ext.workingwiki.comet',
	  ) );
	  if ( version_compare( $wgVersion, '1.23', '<' ) ) {
		  $wgOut->addJsConfigVars( array(
			  'wwBackwardCompatibleComet' => true,
		  ) );
	  }
  }
  #wwLog( 'check for dynamic display in ww_setup()' );
  if ( wwfDynamicDisplayInEffect() ) {
	  $wgOut->addJsConfigVars( array(
		  'wwTimeLimitForMakeJobs' => wwfGetTimeLimitForMakeJobs(),
	  ) );
	  # FauxRequest breaks this method so hard we can't even
	  # use method_exists to test here
	  $req = RequestContext::getMain()->getRequest();
	  if ( ! $req instanceOf FauxRequest ) {
		  # redirect in case can't do dynamic display.
		  # TODO: only for non-editing non-special pages
		  $url = $req->getRequestURL();
		  if ( strpos( $url, '?' ) !== false ) {
			  $url .= '&ww-static-files=1';
		  } else {
			  $url .= '?ww-static-files=1';
		  }
		  RequestContext::getMain()->getOutput()->addHeadItem(
			  'ww-static-redirect',
			  '<noscript><meta http-equiv="refresh" content="0; url='
			  . $url . "\"/></noscript>\n"
		  );
	  }
  }

  # Disable the XSS protection in Chrome, which prohibits wiki editors 
  # from creating source files that include JavaScript code to run in 
  # the wiki page.
  global $wgRequest;
  $wgRequest->response()->header( 'X-XSS-Protection: 0' );
}

# These hooks mean the WWInterface class has to be loaded for every 
# page.  It would be good to separate that out.

# do some transformations on the page before the parser gets to work:
# to handle things like $$...$$ and __DISABLE_MAKE__.
# TODO: ParserAfterStrip is deprecated post-1.14
$wgHooks['ParserAfterStrip'][] = array( $wwContext->wwInterface, 'catch_incoming_page' );
# the parser tag hooks put custom directives into comments; this function
# processes the comments and does the actual project file work.
$wgHooks['ParserAfterTidy'][] = array( $wwContext->wwInterface, 'render_after_parsing' );
# when we generate HTML ourselves, we 'armor' it to pass by the Parse phase.
$wgHooks['ParserAfterTidy'][] = array( $wwContext->wwInterface, 'disarm_html' );
# after tidy we can get to the syntax highlighted content, to
# put in links in the project description
$wgHooks['ParserAfterTidy'][] = array( $wwContext->wwInterface, 'massage_page_after' );
# seems to work better than doing the css include from the BeforeExec
# hook (i.e. works in special pages)
$wgHooks['BeforePageDisplay'][] = array( $wwContext->wwInterface, 'before_page_display' );
# when a page is edited, this is the time to update the archived project files
$wgHooks['ArticleUpdateBeforeRedirect'][] = array( $wwContext->wwInterface, 'after_edit_updates' );
# put in extra box in sidebar
$wgHooks['SkinTemplateOutputPageBeforeExec'][] = array( $wwContext->wwInterface, 'add_project_box' );
# catch edits, decipher which projects to mark for updating
$wgHooks['EditFilterMerged'][] = array( $wwContext->wwInterface, 'catch_edits' );
# make parser cache hip to MathJax/no MathJax and other distinctions
$wgHooks['PageRenderingHash'][] = array( $wwContext->wwInterface, 'fix_hash' );
# MathML preference for wikis without the Math extension
$wgHooks['GetPreferences'][] = array( $wwContext->wwInterface, 'get_preferences' );
# I also set a couple hooks in SpecialManagePage and SpecialGetProjectFile

$wgAutoloadClasses['WWInterface']
  = "$wwExtensionDirectory/WWInterface.php";
$wgAutoloadClasses['WWStorage']
  = "$wwExtensionDirectory/WWStorage.php";
$wgAutoloadClasses['ProjectDescription']
  = "$wwExtensionDirectory/ProjectDescription.php";
$wgAutoloadClasses['WorkingWikiProjectDescription']
  = "$wwExtensionDirectory/WorkingWikiProjectDescription.php";
$wgAutoloadClasses['StandaloneProjectDescription']
  = "$wwExtensionDirectory/StandaloneProjectDescription.php";
$wgAutoloadClasses['ExternalProjectDescription']
  = "$wwExtensionDirectory/ExternalProjectDescription.php";
$wgAutoloadClasses['ResourcesProjectDescription']
  = "$wwExtensionDirectory/ResourcesProjectDescription.php";
$wgAutoloadClasses['WWAction']
  = "$wwExtensionDirectory/Actions.php";
$wgAutoloadClasses['ProjectEngineConnection']
  = "$wwExtensionDirectory/ProjectEngineConnection.php";

# Special: pages
$wgSpecialPages['GetProjectFile'] = 'SpecialGetProjectFile';
$wgSpecialPages['ManageProject'] = 'SpecialManageProject';
$wgSpecialPages['PE'] = 'SpecialPE';

$wgSpecialPages['ImportProjectFiles'] = 'SpecialImportProjectFiles';
$wgAutoloadClasses['SpecialImportProjectFiles']
  = "$wwExtensionDirectory/SpecialImportProjectFiles.php";
$wgAutoloadClasses['ImportQueue']
  = "$wwExtensionDirectory/ImportQueue.php";

# resource loader 

# This subclass allows me to have the files for ResourceLoader in places
# that aren't accessible by the web server - it will include their content
# inline even in debug mode.
#class ResourceLoaderOfflineFileModule extends ResourceLoaderFileModule {
#  public function supportsURLLoading() {
#    return false;
#  }
#};

$resourceModuleTemplate = array(
  'localBasePath' => dirname(__FILE__).'/resources',
 # 'class' => 'ResourceLoaderOfflineFileModule',
);

/* module for basic WW stuff, always loaded */
$wgResourceModules['ext.workingwiki'] = $resourceModuleTemplate + array(
	'scripts' => array( 'ext.workingwiki.js' ),
	'styles' => array( 'ext.workingwiki.css' ),
	'dependencies' => array(
		'ext.workingwiki.confirm',
		'ext.workingwiki.api',
		'ext.workingwiki.top',
	),
	'messages' => array(
		'ww-default-success',
		'ww-default-failure',
		'ww-get-project-file-success',
		'ww-notify-ok', // note: not needed in 1.21+
	),
);

/* basic WW stuff to be loaded at top of page:
 * CSS to load before HTML gets rendered;
 * minimal JS for use while page is loading.
 */
$wgResourceModules['ext.workingwiki.top'] = $resourceModuleTemplate + array(
	  'scripts' => array( 'ext.workingwiki.top.js' ),
	  'styles' => array( 'ext.workingwiki.top.css' ),
	  'position' => 'top',
	  'dependencies' => array(
		  'jquery.cookie',
	  ),
	  'messages' => array(
		  'ww-clear-directory-confirm-message',
	  ),
);

/* JS to confirm an action.  Loaded on demand by ext.workingwiki.top if
 * something is clicked that needs confirmation.
 */
$wgResourceModules['ext.workingwiki.confirm'] = $resourceModuleTemplate + array(
	  'scripts' => array( 'ext.workingwiki.confirm.js' ),
	  'dependencies' => array(
		  'jquery.ui.dialog',
		  'ext.workingwiki.top',
		  'mediawiki.jqueryMsg',
	  ),
	  'messages' => array(
		  'ww-cancel',
		  'ww-clear-directory-confirm-button',
		  'ww-in-preview-session',
	  ),
);

/* JS to make a WW API call.  Loaded by demand by ext.workingwiki.top if
 * needed to respond to a click.
 */
$wgResourceModules['ext.workingwiki.api'] = $resourceModuleTemplate + array(
	  'scripts' => array( 'ext.workingwiki.api.js' ),
	  'dependencies' => array(
		  'mediawiki.api',
		  'ext.workingwiki.top',
	  ),
);

/* CSS to use when page isn't editable (to make some action links disappear).
 */
$wgResourceModules['ext.workingwiki.readonly'] = $resourceModuleTemplate + array(
	'styles' => array( 'ext.workingwiki.readonly.css' ),
	'position' => 'top',
);

/* JS for inferring suggested page where a source file should go.
 * Used by ImportProjectFiles and ManageProject.
 */
$wgResourceModules['ext.workingwiki.suggestions'] = $resourceModuleTemplate + array(
  'scripts' => array( 'ext.workingwiki.suggestions.js' ),
);

/* JS and CSS for ImportProjectFiles.  Builds on stuff defined in the
 * MultiUpload extension.
 */
$wgResourceModules['ext.workingwiki.importprojectfiles'] = $resourceModuleTemplate + array(
  'scripts' => array( 'ext.workingwiki.importprojectfiles.js' ),
  'styles' => array( 'ext.workingwiki.importprojectfiles.css' ),
  'dependencies' => array(
    'ext.multiupload.shared', # requires MultiUpload extension
    'ext.workingwiki.suggestions',
    'mediawiki.api',
  ),
);

/* resources for list of action links at top of ManageProject and
 * directory listing.
 */
$wgResourceModules['ext.workingwiki.actionheader'] = $resourceModuleTemplate + array(
	'messages' => array(
		'ww-sync-all-success',
		'ww-clear-directory-success',
	),
);

/* resources for ManageProject
 */
$wgResourceModules['ext.workingwiki.manageproject'] = $resourceModuleTemplate + array(
	'scripts' => array( 'ext.workingwiki.manageproject.js' ),
	'messages' => array(
		'ww-sync-file-success',
	),
	'dependencies' => array(
		'ext.workingwiki.manageproject.top',
		'ext.workingwiki.actionheader',
		'ext.workingwiki.suggestions',
		'mediawiki.special',
	),
);

$wgResourceModules['ext.workingwiki.manageproject.top'] = $resourceModuleTemplate + array(
	'styles' => array( 'ext.workingwiki.manageproject.top.css' ),
	'position' => 'top',
);

/* resources for list-directory feature of GetProjectFile
 */
$wgResourceModules['ext.workingwiki.listdirectory'] = $resourceModuleTemplate + array(
	'scripts' => array(
		'ext.workingwiki.listdirectory.js',
	),
	'messages' => array(
		'ww-sync-file-success',
	),
	'position' => 'top',
	'dependencies' => array(
		'ext.workingwiki.actionheader',
	),
);

/* resources for "Click to add" links inserted into article text
 */
$wgResourceModules['ext.workingwiki.clicktoadd'] = $resourceModuleTemplate + array(
	'scripts' => array(
		'ext.workingwiki.clicktoadd.js',
	),
	'position' => 'top',
	'dependencies' => array(
		'ext.workingwiki.top'
	),
);

/* JS for loading project files asynchronously via Ajax/Comet.
 */
$wgResourceModules['ext.workingwiki.dynamic-project-files'] = $resourceModuleTemplate + array(
	'scripts' => array(
		'ext.workingwiki.dynamic-project-files.js',
	),
	'messages' => array(
		'ww-dynamic-project-file-failed',
		'ww-messages-legend',
		'ww-dynamic-altlinks-reload',
		'ww-dynamic-altlinks-remake',
		'ww-dynamic-altlinks-download',
	),
	'dependencies' => array(
		'ext.workingwiki',
		'mediawiki.Title',
		'ext.workingwiki.pulldown-altlinks',
		'ext.workingwiki.dynamic-project-files.top',
		// this should be conditional, but I'm having problems with that
		'ext.workingwiki.comet',
	),
);

/* early-load JS for starting the dynamic loading early.
 */
$wgResourceModules['ext.workingwiki.dynamic-project-files.top'] = $resourceModuleTemplate + array(
	'scripts' => array(
		'ext.workingwiki.dynamic-project-files.top.js',
	),
	'styles' => array(
		'ext.workingwiki.dynamic-project-files.top.css',
	),
	'dependencies' => array(
		'ext.workingwiki.top',
	),
	'position' => 'top',
);

/* CSS for pull-down action links
 */
$wgResourceModules['ext.workingwiki.pulldown-altlinks'] = $resourceModuleTemplate + array(
	'styles' => array(
		'ext.workingwiki.pulldown-altlinks.css',
	),
	'position' => 'top',
);

# this works in Mw 1.23 only
# SSE output format for use of API classes with Comet protocol
$wgAPIFormatModules['sse']   = 'WWApiFormatSSE';
$wgAPIFormatModules['ssefm'] = 'WWApiFormatSSE';
/* Comet interactions for server-side operations with realtime client-side
 * updates
 */
$wgResourceModules['ext.workingwiki.comet'] = $resourceModuleTemplate + array(
	'scripts' => array(
		'ext.workingwiki.comet.js',
	),
	'styles' => array(
		'ext.workingwiki.comet.css',
	),
	'messages' => array(
		'ww-comet-dialog-title',
		'ww-comet-dialog-action-title',
		'ww-comet-opening',
		'ww-comet-could-not-connect',
		'ww-comet-connection-failed',
		'ww-comet-timed-out-reconnecting',
		'ww-comet-subscribing',
		'ww-comet-lost-data',
		'ww-comet-lost-connection',
		'ww-comet-error',
		'ww-comet-connected',
		'ww-comet-done-loading-files-in-page',
		'ww-comet-done',
		'ww-comet-syncing-source-files',
		'ww-comet-merging-from-preview',
		'ww-default-status',
		'ww-import-project-files-status',
		'ww-get-project-file-status',
		'ww-list-directory-status',
		'ww-list-resources-directory-status',
		'ww-sync-file-status',
		'ww-sync-all-status',
		'ww-clear-directory-status',
	),
	'dependencies' => array(
		'mediawiki.jqueryMsg',
		'ext.workingwiki',
	),
);
$wgAutoloadClasses['WWApiFormatSSE']
  = "$wwExtensionDirectory/WWApiFormatSSE.php";

/* CSS for LaTeXML documents
 */
$wgResourceModules['ext.workingwiki.latexml'] = $resourceModuleTemplate + array(
	'styles' => array(
		'ext.workingwiki.latexml.css',
		'ext.workingwiki.latexml.customization.css',
	),
	'position' => 'top',
);

/* Leekspin for the bored
 */
$wgResourceModules['ext.workingwiki.leekspin'] = $resourceModuleTemplate + array(
	'scripts' => array(
		'ext.workingwiki.leekspin.top.js',
	),
	'position' => 'top',
);

$wgAutoloadClasses['SpecialGetProjectFile']
  = "$wwExtensionDirectory/SpecialGetProjectFile.php";
$wgAutoloadClasses['SpecialManageProject']
  = "$wwExtensionDirectory/SpecialManageProject.php";
$wgAutoloadClasses['SpecialPE']
  = "$wwExtensionDirectory/SpecialPE.php";

# api.php actions

$wgAutoloadClasses['WWApiBase']
  = "$wwExtensionDirectory/Api.php";

$wgAPIModules['ww-import-project-files'] = 'WWApiImportProjectFiles';
$wgAutoloadClasses['WWApiImportProjectFiles']
  = "$wwExtensionDirectory/Api.php";

$wgAPIModules['ww-get-project-file'] = 'WWApiGetProjectFile';
$wgAutoloadClasses['WWApiGetProjectFile']
  = "$wwExtensionDirectory/Api.php";

$wgAPIModules['ww-get-project-data'] = 'WWApiGetProjectData';
$wgAutoloadClasses['WWApiGetProjectData']
  = "$wwExtensionDirectory/Api.php";

$wgAPIModules['ww-get-project-revisions'] = 'WWApiGetProjectRevisions';
$wgAutoloadClasses['WWApiGetProjectRevisions']
  = "$wwExtensionDirectory/Api.php";

$wgAPIModules['ww-get-file-revisions-in-project'] = 'WWApiGetFileRevisionsInProject';
$wgAutoloadClasses['WWApiGetFileRevisionsInProject']
  = "$wwExtensionDirectory/Api.php";

$wgAPIModules['ww-list-directory'] = 'WWApiListDirectory';
$wgAutoloadClasses['WWApiListDirectory']
  = "$wwExtensionDirectory/Api.php";

$wgAPIModules['ww-list-resources-directory'] = 'WWApiListResourcesDirectory';
$wgAutoloadClasses['WWApiListResourcesDirectory']
  = "$wwExtensionDirectory/Api.php";

$wgAPIModules['ww-sync-file'] = 'WWApiSyncFile';
$wgAutoloadClasses['WWApiSyncFile']
  = "$wwExtensionDirectory/Api.php";

$wgAPIModules['ww-sync-all'] = 'WWApiSyncAll';
$wgAutoloadClasses['WWApiSyncAll']
  = "$wwExtensionDirectory/Api.php";

$wgAPIModules['ww-clear-directory'] = 'WWApiClearDirectory';
$wgAutoloadClasses['WWApiClearDirectory']
  = "$wwExtensionDirectory/Api.php";

$wgAPIModules['ww-set-source-file-location'] = 'WWApiSetSourceFileLocation';
$wgAutoloadClasses['WWApiSetSourceFileLocation']
  = "$wwExtensionDirectory/Api.php";

$wgAPIModules['ww-set-project-file-location'] = 'WWApiSetProjectFileLocation';
$wgAutoloadClasses['WWApiSetProjectFileLocation']
  = "$wwExtensionDirectory/Api.php";

$wgAPIModules['ww-remove-file'] = 'WWApiRemoveFile';
$wgAutoloadClasses['WWApiRemoveFile']
  = "$wwExtensionDirectory/Api.php";

$wgAPIModules['ww-set-prerequisite'] = 'WWApiSetPrerequisite';
$wgAutoloadClasses['WWApiSetPrerequisite']
  = "$wwExtensionDirectory/Api.php";

$wgAPIModules['ww-remove-prerequisite'] = 'WWApiRemovePrerequisite';
$wgAutoloadClasses['WWApiRemovePrerequisite']
  = "$wwExtensionDirectory/Api.php";

$wgAPIModules['ww-pass-to-pe'] = 'WWApiPassToPE';
$wgAutoloadClasses['WWApiPassToPE']
  = "$wwExtensionDirectory/Api.php";

# (potentially) multilingual messages
$wgExtensionMessagesFiles['WorkingWiki']
  = "$wwExtensionDirectory/WorkingWiki.i18n.php";

# special page aliases
#$wgExtensionAliasesFiles['WorkingWiki']
#  = "$wwExtensionDirectory/WorkingWiki.alias.php";
# in MW 1.16+, use this instead
$wgExtensionMessagesFiles['WorkingWikiAlias']
  = "$wwExtensionDirectory/WorkingWiki.alias.php";

# include the preview features
require_once "$wwExtensionDirectory/Preview/PreviewHooks.php";

# include the background jobs features
require_once "$wwExtensionDirectory/Background/BackgroundHooks.php";

# for debugging the code
global $wgShowExceptionDetails;
$wgShowExceptionDetails = true;

# GetProjectFile checks for user permissions, and handles failure a little
# differently than the standard
# Disabled.  This was because of an issue with NetLogo, and was causing
# a security hole in 1.19+.  If we need NetLogo (and it hasn't gotten more
# functional since I was using it) I'll have to debug this hack before
# re-enabling.
#$wgWhitelistRead[] = "Special:GetProjectFile";

?>
