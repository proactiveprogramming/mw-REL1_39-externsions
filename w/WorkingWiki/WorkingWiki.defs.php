<?php
/* WorkingWiki extension for MediaWiki 1.21 and later
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

# ===== Configuration variables for WorkingWiki extension =====
# all these isset() calls allow you to customize them beforehand:
# set a given $ww variable in LocalSettings.php before it calls this
# file, and it will use your value.  You can also set it afterward, but
# in some cases this code sets up other variables based on the one you
# want to customize.

# the directory where this code and other files to be used are
$wwExtensionDirectory = dirname(__FILE__);

# this provides the wwfRealpath() function
require_once($wwExtensionDirectory.'/misc.php');

# name of the special namespace for project descriptions
if (!isset($wwProjectDescriptionNamespaceName))
  $wwProjectDescriptionNamespaceName = 'ProjectDescription';

# WW always produces HTML, but WMD can produce LaTeX and other output formats
if (!isset($wwOutputFormat))
  $wwOutputFormat = 'html';

# what kind of files to serve raw rather than as a Special:GetProjectFile page
if (!isset($wwImageExtensions))
  $wwImageExtensions
    = array('png','jpg','jpeg','gif','svg','eps','ps','pdf','dvi',
        'doc','ppt','odt','odf','odp','odc','docx','pptx','xls');

# which kind of images can be used as inline img tags
# SVG is displayed inline using object tag rather than img
if (!isset($wwInlineImageExtensions))
  $wwInlineImageExtensions = array('png','jpg','jpeg','gif','svg',);

# image types that can be displayed as source code
if (!isset($wwTextImageExtensions))
  $wwTextImageExtensions = array('ps','eps','svg');

# which kind of files to present as links rather than inline
if (!isset($wwLinkExtensions))
  $wwLinkExtensions = array('pdf','ps','dvi','xls','doc','ppt','odt', 'odf',
    'odp', 'odc', 'docx','pptx','xcf','wav','mp3','wma','swf','zip',
    'tar','gz', 'tgz', 'mpg', 'avi', 'ogv', 'flv', 'mp4', 'rdata', 'env');

# recognize HTML file types
if (!isset($wwHtmlExtensions))
  $wwHtmlExtensions = array('html','htm','xhtml','xht','html5');

# When people add a source file, ask about adding to a project, or just do it?
if (!isset($wwClickToAdd))
  $wwClickToAdd = false;

# enable/disable the syntax highlighter
if (!isset($wwUseSyntaxHighlighter))
  $wwUseSyntaxHighlighter = true;

# long files crash the syntax highlighter an out-of-memory error, which
# can't be caught and turned into an informative message, so try to avoid
# that
if (!isset($wwMaxLengthForSyntaxHighlighting))
  $wwMaxLengthForSyntaxHighlighting = 200000;

# displaying really long files exceeds the 30 second time limit on PHP
# processes, even without syntax highlighting, so avoid that too by
# offering them  for download only
if (!isset($wwMaxLengthForSourceCodeDisplay))
  $wwMaxLengthForSourceCodeDisplay = 1000000;

# and here's a limit to the size of a file that we will read into memory,
# regardless of how it might be displayed.
if (!isset($wwMaxProjectFileSize))
  $wwMaxProjectFileSize = 1000000;

# rules for displaying project files in transformed form.  For instance,
# in place of a .tex file we display its corresponding .latexml.html5 file,
# that is, use the default make rules to run latexml on the file and display
# it as html.  
# This is an array with regular expressions for keys and 
# the replacement strings for them as their values.
# If the special key 'non_inline_images' is found in this array, it'll be 
# replaced by a pattern matching all the image types in $wwImageExtensions 
# that aren't in $wwInlineImageExtensions, i.e. things like .eps that can't be 
# displayed directly by standard web browsers.
# To add a display transformation put something like
# $wwDisplayTransformations['/\.my-source-filetype$/i'] = '.my-output-filetype';
# in your LocalSettings.php.
# Note the tex transformations here get altered in the hook function below.
if (!isset($wwDisplayTransformations))
{ $wwDisplayTransformations = array(
	# tempting to use '.html' here, but then no .pdf link
    '/\.tex$/i' => '.latexml.html',
    '/\.tex-inline$/i' => '.tex-inline.latexml.html',
    '/\.tex-math$/i' => '.tex-math.latexml.html',
    'non_inline_images' => '.png',
  );
}

# rules defining what shows up in the [log,...] item over by the right 
# margin.  Each key is a pattern, and if it matches, the replacement
# string associated with it is included as if it was inserted into the
# project-file or source-file tag.
if (!isset($wwAltlinksRules))
  $wwAltlinksRules = array(
      '/(.*)\.latexml\.(xhtml|html|html5)$/' => 'pdflink=$1.pdf',
      '/(.*)\.standalone\.(xhtml|html|html5)$/' => 'pdflink=$1.pdf',
    );

# replacements defining special syntax in WW-enhanced wikitext.
# keys are patterns, values are replacement strings.  They'll be used
# in preg_replace in the order they're set in this array.
if (!isset($wwWikitextReplacements))
{ $math_repl =
    "\"$1\".'<source-file filename=\"'.md5('$2').'.tex-math\" standalone=\"yes\">$2</source-file>'";
  $wwWikitextReplacements = array(
	'/([^\\\\]|^)\{\$(.*?[^\\\\]|)\$\}/es' => $math_repl,
	'/([^\\\\]|^)\$\$(.*?[^\\\\]|)\$\$/e' => $math_repl,
	'/([^\\\\]|^)<latex>(.*?[^\\\\]|)<\/latex>/esi' =>
		"'$1<source-file filename=\"'.md5('$2')"
		. ".'.tex-inline\" standalone=\"yes\">"
		. "\\documentclass{article}\n"
		. "\\begin{document}\n"
		. "$2\n\\end{document}\n</source-file>'",
	'/__DISABLE_MAKE__/' => '<toggle-make-enabled enabled=0/>',
	'/__ENABLE_MAKE__/' => '<toggle-make-enabled enabled=1/>',
  );
}

# Given the URI (unique name) of a project - which might be a project
# on another wiki, or on github.com, or somewhere else - where is a 
# suitable "home page" that we can link to for that project?  Here are
# some defaults, but sites using other sources for project data might
# need to extend this list of transformations.
if (!isset($wwLinksForURIs))
  $wwLinksForURIs = array(
      # WW projects on external wikis
    '/^pe-ww:(.*):(.*?)$/i' => '$1/index.php/Special:ManageProject?project=$2',
      # Github projects
    #'/^pe-git:git@github.com:(.*)\.git$/i' => 'https://github.com/$1',
      # more to come, no doubt.
  );

# ProjectEngine is a standalone CGI service, but we also have the option
# of using it within in our process for efficiency.  set this to true to
# use PE as a separate service.
if (!isset($wwUseHTTPForPE))
  $wwUseHTTPForPE = false;

# if using PE within-process, include its php file.
if (!$wwUseHTTPForPE)
{ # Where is it?
  if (!isset($wwPECodeDirectory))
    $wwPECodeDirectory = "$wwExtensionDirectory/ProjectEngine";
  require_once("$wwPECodeDirectory/ProjectEngine.php");
}

# set this if ProjectEngine runs on the same file system as WorkingWiki, or 
# somewhere that it has access to the wiki's images/ directory.  If
# so, we can be efficient by passing it filenames instead of file contents
# for these files.
if (!isset($wwPECanReadFilesFromWiki))
  $wwPECanReadFilesFromWiki = true;

# set this if WW has access to ProjectEngine's cache directories.
# If so it can just point us at project files instead of returning their
# contents when we call for them.
if (!isset($wwPEFilesAreAccessible))
  $wwPEFilesAreAccessible = true;

# this allows "shortcut" URIs, like you can type in
# shortcut-thing:XXY into ManageProject and it will translate that to
# pe-ww://special-location/something-made-from-XXY or something.
# for instance at McMaster's theobio family of wikis we have
# pe-theobio:wikiname:projectname expand to
# pe-ww:http://lalashan.mcmaster.ca/theobio/wikiname:projectname.
# you use it by inserting key-value pairs where the key is a regexp
# and the value is the replacement string to use in preg_replace.
#$wwURITransformations['/^pattern$/'] = 'replacement';

# how to assign environment variables for external projects - just putting
# PROJECT_DIR_(uri) is very cumbersome.  Array of key-value pairs where
# key is regexp, value is replacement string for preg_replace().
# For instance, at McMaster we have a large family of wikis, so we have
# a rule that makes default variable names PROJECT_DIR_wikiname_projectname
# instead of the general form defined here, PROJECT_DIR_projectname.
$wwURIVariableNameTransformations['/^pe-ww:.*:(.*?)$/i']
  = 'PROJECT_DIR_$1';
$wwURIVariableNameTransformations['/^pe-git:(.*\/)?(.*?)\.git$/i']
  = 'PROJECT_DIR_$2';

# if true, use dio_fcntl() instead of flock() for exclusive access to
# working directories.  This option is needed for NFS-mounted working
# directories, but requires the php-dio package to be installed, which
# is not in the default php installation.
if (!isset($wwUseFcntl))
  $wwUseFcntl = false;

# If true, WW puts project data into a special source file called
# GNUmakefile, which controls what make does in the project directory.
# Otherwise, WW adds its special flags to the make command line, which
# is in some ways less intrusive, but harder to replicate when using
# make recursively or outside the wikis.
if (!isset($wwGenerateMakefile))
  $wwGenerateMakefile = true;

# number of seconds ww-make is allowed to take before it terminates
# the make process
if (!isset($wwSecondsAllowedForMake))
  $wwSecondsAllowedForMake = 180;

# nice value for the make processes.  if it's zero we don't use nice.
if (!isset($wwNiceValueForMake))
  $wwNiceValueForMake = 0;

# Special:ImportProjectFiles, Special:ManageProject, and the 
# importProjectFiles script make suggestions where new project files should
# go.  If this is not set, plain wiki pages will be suggested except for 
# file extensions listed in $wwImportImageExtensions.  If it is set, image
# pages will be the default, and $wwImportTextExtensions lists the 
# exceptions.
if (!isset($wwImportAsImageByDefault))
  $wwImportAsImageByDefault = false;

# file types that will be uploaded to File: (aka Image:) pages by default,
# if $wwImportAsImageByDefault is not set.
if (!isset($wwImportImageExtensions))
  $wwImportImageExtensions = $wwImageExtensions;

# file types to import as text, if $wwImportAsImageByDefault is set.
if (!isset($wwImportTextExtensions))
  $wwImportTextExtensions = array('tex','sty','cls','bib','bst','txt',
    'csv','tsv','dat','makefile','mk','c','h','r','pl','sed','awk','mac',
    'html','htm','xhtml','xht','html5','js','css',
    'gp','dot','py','sage','ltxml');

# maybe we should skip these 2 since, as Jonathan points out, users can
# create any kind of file by writing source-file tags; and all project
# files should be validated at display time.
# A: we are skipping these in the new IPF for MW 1.19+

# approved filetypes for importing into text pages (by analogy to 
# $wgFileExtensions)
# note though that files called Makefile, GNUMakefile are considered
# to have extension '.makefile'.
# ignored if either $wgCheckFileExtensions or $wgStrictFileExtensions is false.
if (!isset($wwFileExtensionsForImport))
  $wwFileExtensionsForImport = 
    array_merge($wwImportImageExtensions, $wwImportTextExtensions);

# prohibited filetypes for importing into text pages (by analogy to
# $wgFileBlacklist)
# ignored if either $wgCheckFileExtensions or $wgStrictFileExtensions is false.
if (!isset($wwFileBlacklistForImport))
  $wwFileBlacklistForImport = array('js', 'jsb', 'mhtml', 'mht', 'php', 'phtml', 'php3', 'php4', 'php5', 'phps', 'shtml', 'jhtml', 'cgi', 'exe', 'scr', 'dll', 'msi', 'vbs', 'bat', 'com', 'pif', 'cmd', 'vxd', 'cpl');
  
# should file extensions be on whitelist when uploading source files to
# textual pages?
# (for some reason this is not used in the code)
#if (!isset($wwStrictFileExtensionsForImport))
#  $wwStrictFileExtensionsForImport = false;

# max size file to insert into a textual wiki page
  # default, big enough to wrap it in an arbitrary source-file or 
  # project-file element.  Assume filename can be up to 1024 and 
  # project name can be up to 255 (max length of a Title).
  # that's a prudent length, but it's not what anyone wants
  #  $wwMaxInsertFileSize = $wgMaxArticleSize - 1500;
if (!isset($wwMaxInsertFileSize))
  $wwMaxInsertFileSize = 200000;

# project files are sometimes written into the temp directory temporarily,
# while preparing to archive them in the wiki.  This location does not need
# to be shared across nodes if WW is running on a cluster.
if (!isset($wwTempDirectory))
  $wwTempDirectory = '/tmp';

# where to put the contents of a .tar.gz or such during the import
# project files process.  If on a cluster, this should be a shared 
# directory.
# TODO: isn't this obsolete, now that we use the MultiUpload extension?
if (!isset($wwUnpackDirectory))
  $wwUnpackDirectory = $wwTempDirectory;

# where to put outputs of processes while streaming it to users'
# web browsers.  If on a cluster, this should be a shared directory.
if (!isset($wwStatusFileDirectory))
  $wwStatusFileDirectory = $wwTempDirectory;

# whether to send requests to PE to prune the working directory storage
# set this to false if you handle this a different way, for instance by
# using a cron job to run the prune-directories action every night.
if (!isset($wwPruneDirectoriesAfterPageRequest))
  $wwPruneDirectoriesAfterPageRequest = true;

# whether to validate project files (including source files) for security
# risks before displaying them.
if (!isset($wwValidateProjectFiles))
  $wwValidateProjectFiles = false;

# approved filetypes for project files to display.
# files called 'Makefile' or variants thereof are handed as if
# they had extension 'makefile'.
# only these extensions are allowed if $wwStrictFileExtensionsForDisplay
# is true
$wwFileExtensionsForDisplay = array('html','xhtml','html5','tex','c','h','r',
  'makefile','mk','pl','max','out','log','txt');

# prohibited filetypes for project files to display.
# these types are never allowed.  all others are allowed if
# $wwStrictFileTypesForDisplay is false.
$wwFileBlacklistForDisplay = array('js', 'jsb', 'mhtml', 'mht', 'php', 'phtml', 'php3', 'php4', 'php5', 'phps', 'shtml', 'jhtml', 'cgi', 'exe', 'scr', 'dll', 'msi', 'vbs', 'bat', 'com', 'pif', 'cmd', 'vxd', 'cpl', 'zip', 'jar');

# whether to allow unanticipated file extensions.
# if true, only the extensions in $wwFileExtensionsForDisplay are allowed;
# if false, all extensions are allowed except the ones in 
# $wwFileBlacklistForDisplay.
# if changing to true, consider allowing extension '', which applies to
# files without an extension (except for makefiles).  they are validated
# as if with extension 'txt'.
$wwStrictFileExtensionsForDisplay = false;

# Can't have a huge number of files to upload on the single form -
# for instance, because of php restrictions on number 
# and length of POST values 
if (!isset($wwMaxImportFilesPerPage))
  $wwMaxImportFilesPerPage = 20;

# add user preference to use the browser's native MathML support when available.
# Wikis that also use the Math extension may want to omit this, because
# Math provides a similar preference and we also honor that one.
if (!isset($wwProvideMathmlPreference))
  $wwProvideMathmlPreference = true;

# if true, provide users the option to get email notification when a
# background job completes.
# Only works when PE is using the SGE background job system, not the 
# Unix one.
if (!isset($wwAllowBackgroundJobEmails))
  $wwAllowBackgroundJobEmails = false;

# if true, output will include some WW debug messages, which are not visible
# in the browser unless you use CSS rules to override the display:none that
# is assigned in WW's CSS.
if (!isset($wwOutputDebugMessages))
  $wwOutputDebugMessages = false;

# if true, attempt to load project file content dynamically after page is
# served, rather than making and retrieving them while generating the page.
$wwEnableDynamicProjectFiles = false;

# if true, use Comet operations to get real-time updates from the server
# during WW operations.
$wwUseComet = false;

# if true, use compatibility CSS for the HTML that old versions of latexml
# produce
$wwUsesOldLaTeXML = false;

# if true, display leekspin.com in a popup when page is loading slowly
# not currently working - lw
$wwEnableLeekspin = false;

# Configuration of confirmation, success, and failure messages for
# WW actions (like sync-source-files and so on), which are called both
# CGI fashion using ?ww-action=sync-source-files, and API fashion using
# POST arguments to api.php.  ?ww-action is mainly a gateway to the API
# code, which does in php some of what the client-side WW code does in
# JavaScript.  So this configuration info is used by both the PHP and JS.
# These probably shouldn't need to be customized.
# NOTE it's not enough to add a message to this list - to make it work
# in the client, you also have to add it to 'messages' in the WorkingWiki
# module or elsewhere.
if (!isset($wwApiMessages)) {
	$wwApiMessages = array(
		'default' => array(
			'args' => array( 'action' ),
		),
		'ww-sync-file' => array(
			'args' => array( 'filename' ),
		),
		'ww-sync-all' => array(),
		'ww-clear-directory' => array(
			'args' => array( 'project' ),
		),
		'ww-get-project-file' => array(
			'args' => array( 'filename', 'make', 'source-file' ),
		),
	);
}

# constants used in reporting results of operations
define('WW_SUCCESS',0);
define('WW_WARNING',1);
define('WW_QUESTION',2);
define('WW_ERROR',3);
define('WW_ABORTOUTPUT',4);
define('WW_NOACTION',5);
define('WW_DEBUG',6);

# stored in cache instead of file contents
# TODO: use an exception instead of returning this value
define('WW_FILETOOBIG','WORKINGWIKI-FILE-TOO-BIG-FLAG');
