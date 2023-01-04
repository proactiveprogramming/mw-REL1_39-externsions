<?php
/* WorkingWiki extension for MediaWiki 1.13
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

# code for fancy WW preview operations, with a preview working directory
# separate from the regular working directory.

class WWPreview {
	static $save_in_progress;
	static $enable_parser_cache_normally;
	static $source_files_to_sync;

	# if we are previewing, this gives us a consistent text string to
	# make into a special directory name, for the directory we use to
	# do our make operations while previewing.	This directory needs to
	# be consistent across a series of preview actions, and project-file
	# links from the preview pages as well.
	#
	# things I considered using and rejected:
	#   just the username - what if you preview two different pages in 
	#     the same project at the same time, or preview two different
	#     possible edits to the same page, and want them to be separate?
	#     that's a reasonable expectation.
	#   the edit token - it expires pretty frequently, and that would cause
	#     wasteful remaking of target files.
	#   something in the php session - I think it would be shared across
	#     independent pages the same as the username
	#   username (or session data) plus page name - doesn't help with 
	#     parallel edits to the same page.
	#
	# so here's the scheme: generate a random key and insert it into the 
	#   edit form if there isn't one there already.	make it persistent until
	#   the page is saved, and include it as a special GET argument to 
	#   project-file links (and other operations?).	it's okay to garbage
	#   collect the preview directory if it hasn't been touched in a day or
	#   two - it can always be regenerated (but we'd rather keep it around
	#   to avoid expensive redundant make operations, obviously).
	static $_previewKey;
	static $_previewPage;
	static function previewKey() {
		global $wwContext;
		# if the GET or POST comes with a key, use it
		if (!isset(self::$_previewKey)) {
			global $wgRequest;
			self::$_previewKey = $wgRequest->getText(
				'wwPreviewKey',
				$wgRequest->getText('preview-key','')
			);
			self::$_previewPage = $wgRequest->getText('wwPreviewPage','');
		}
		# otherwise, if we're in fact previewing, introduce a new one.
		# (this includes the initial action=edit page even if it doesn't
		# preview the page per se.)
		if ($wwContext->wwInterface->page_is_preview() and self::$_previewKey == '') {
		 	# set the new key.
			self::$_previewKey = mt_rand();
			global $wgTitle;
			self::$_previewPage = '';
			if ( $wgTitle instanceOf Title ) {
				self::$_previewPage = $wgTitle->getPrefixedDBKey();
			}

			# when we invent a new preview job, we also record the name of the
			# page that's being previewed, because we need it for S:GPF.
			#$pd = self::preview_directory_name();
			#if (mkdir($pd,0700,true) === false)
			#	$wwContext->wwInterface->throw_error("Couldn't create preview directory");
			#$pfn = "$pd/page";
			#if (($pf = fopen($pfn,'w')) === false)
			#	$wwContext->wwInterface->throw_error("Couldn't create internal 'page' file");
			#if (fwrite($pf, $wgTitle->getPrefixedText()) === false)
			#	$wwContext->wwInterface->throw_error("Couldn't record page name");
			#fclose($pf);
		}
		#if (self::$_previewKey != '')
		#	$wwContext->wwInterface->debug_message("previewKey is ".self::$_previewKey);
		return self::$_previewKey;
	}

	# Let JS code know if we're previewing
	static function OutputPageBeforeHTML_hook( &$out, &$text ) {
	 	$pkey = self::previewKey();
		if ($pkey == '') {
			return true;
		}
		if ( method_exists( $out, 'addJsConfigVars' ) ) {
		 	$out->addJsConfigVars( array(
				'wwPreviewKey' => $pkey,
			) );
			if ( isset( WWPreview::$source_files_to_sync ) ) {
				$out->addJsConfigVars( array(
					'wwSourceFilesToSync' => WWPreview::$source_files_to_sync,
				) );
			}
		}
		return true;
	}

	# modify the request data sent to ProjectEngine: if we're previewing,
	# add the preview key to the request, to cause it to use a separate preview
	# directory.	
	# But not if the request only involves inline LaTeX code, because
	# that doesn't need to be previewed separately - the md5 hash in the 
	# filename is unique to the file contents, so there's no risk of variation
	# in the source file.
	# FIXME: can a request ever include a standalone project and a user 
	# project?  Because if so, both will get previewed, which is not desired.
	# Would be ideal to control whether to preview per project, not per request.
	# FIXME: couldn't we do that by marking the project readonly?
	static function PERequest_hook(&$request)
	{ $pkey = self::previewKey();
		global $wwContext;
		if ($pkey == '') {
			return true;
		}
                if ( ! ProjectEngineConnection::session_applies_to_operation( $request['operation']['name'] ) ) {
			return true;
		}
		if (array_key_exists('projects',$request)) {
			foreach ($request['projects'] as $puri=>$pinfo) {
				if (!$wwContext->wwStorage->is_standalone_name($puri)) {
				 	$request['preview'] = $pkey;
				}
			}
		}
		if ( isset( $request['operation'] ) and
		    isset( $request['operation']['project'] ) and
		    ! $wwContext->wwStorage->is_standalone_name( $request['operation']['project'] ) ) {
			$request['preview'] = $pkey;
		}
		if ( $wwContext->wwInterface->page_is_preview() or 
		    ProjectEngineConnection::operation_includes_make( $request['operation']['name']) ) {
			$request['okay-to-create-preview-session'] = true;
		}
		return true;
	}

	static function RenderProjectFile_hook($project, $source, $args, &$ret) {
		$pkey = self::previewKey();
		if ($pkey == '') {
			return true;
		}
		if (WWPreview::$save_in_progress) {
			global $wwContext;
			$wwContext->wwInterface->debug_message(
				"aborting RenderProjectFile for "
				. htmlspecialchars($args['filename'])
				. '.'
			);
			$ret = '(project file)';
			return false;
		}
		return true;
	}

	# when previewing, the only time we can sync source files to the working
	# directory is when parsing the preview page - when we get a GetProjectFile
	# request we don't have the edited source file contents.  So what if we
	# get a page with source file contents but no project files or display=
	# values to trigger a make operation?	We might not sync the source files,
	# then when GetProjectFile tries to make something we'll get the wrong
	# answer.  So we do a sync as soon as we see a source file.  This hook
	# is called for each source-file on the page, but the $project->synced 
	# flag stops us from doing redundant syncs.
	# Note: should we sync when make is disabled (following __DISABLE_MAKE__,
	# for instance)?  I think maybe we should, because for instance
	# <source-file filename=x>xxx</source-file>
	# <project-file filename=x/> should work.
	static function ProactivelySyncIfNeeded_hook( $project ) {
		$pkey = self::previewKey();
		if ($pkey == '') {
			return true;
		}
		if (!$project->synced) {
			global $wwUseComet;
			if ( wwfDynamicDisplayInEffect() and $wwUseComet ) {
				#wwLog( 'Preview: sync postponed for Comet' );
				if ( method_exists( $project, 'all_source_file_contents' ) ) {
					WWPreview::$source_files_to_sync[ $project->project_uri() ] =
						$project->all_source_file_contents();
				}
			} else {
				#wwLog( 'Preview: sync, yes' );
				$success = ProjectEngineConnection::call_project_engine(
					'sync',$project,null,null,true);
				if ($success) {
					$project->synced = true;
				}
			}
		}
		return true;
	}

	# skip make operations during saving after edit/preview.  This is okay
	# because we mark the page to be reparsed the next time it's requested.
	# That way the user gets a sort of feedback on what's going on by seeing
	# make operations possibly take some time during the page load, which I
	# imagine is more intelligible than having the save take a long time.
	# Plus it saves us from a ridiculous situation where you edit but don't
	# preview, just save, and it copies everything
	# to a preview directory in order to make files during the save operation
	# and then merges it back into the main project directory.
	static function MakeTarget_hook($project, $filename, &$ret) {
		if (WWPreview::$save_in_progress) {
			#wwLog("skipping make $filename during save\n");
			$ret = true;
			return false;
		}
		return true;
	}

	# We can sync source files from the preview page into the preview
	# working directory, but only when we have the preview page.	When
	# we go from the page to Special:GetProjectFiles to look at a file
	# in the preview directory, we have to skip syncing because we'll either
	# be missing files, or sync the wrong file contents from the saved
	# version of the page we're previewing.
	static function OKToSyncSourceFiles_hook() {
		global $wwContext;
		$pkey = self::previewKey();
		if ($pkey == '') {
			return true;
		}
		wwLog( 'Preview, sync: compare ' .
			RequestContext::getMain()->getTitle()->getPrefixedDBkey() .
			' with ' .
			self::$_previewPage
		);
		if (RequestContext::getMain()->getTitle()->getPrefixedDBkey() == self::$_previewPage) {
			wwLog("We are previewing but syncing source "
				. "files anyway.");
			return true;
		}
		wwLog("We are previewing and not syncing source "
			. "files.");
		return false;
	}

	# We suspend updating external projects from their repositories during
	# preview, because it seems like it would be confusing.	Maybe we shouldn't
	# update those except by explicit request anyway.
	static function OKToSyncFromExternalRepos_hook() {
		$pkey = self::previewKey();
		if ($pkey == '') {
			return true;
		}
		wwLog( "Don't sync from external repos during preview." );
		return false;
	}

	# during a preview we definitely don't archive project files.
	static function OKToArchiveFiles_hook(&$request) {
		$pkey = self::previewKey();
		if ($pkey == '') {
			return true;
		}
		if ($request['operation']['name'] == 'merge-session') {
			#$wwContext->wwInterface->debug_message("Enable archiving for merge-session.");
			return true;
		}
		#global $wwContext;
		#$wwContext->wwInterface->debug_message("Don't archive during preview.");
		return false;
	}

	# no background makes during previews, too confusing for the programmer
	static function BackgroundMakeOK_hook() {
		$pkey = self::previewKey();
		if ($pkey == '') {
			return true;
		}
		return false;
	}

	# don't list the background jobs during preview, just do one thing at a time.
	static function OKToInsertBackgroundJobsList_hook() {
		$pkey = self::previewKey();
		if ($pkey == '') {
			return true;
		}
		return false;
	}

	# on the edit page (including preview page) we add the preview key to the
	# form data, making one up from scratch if necessary.
	static function showEditForm_fields_hook(&$editpage, &$output) { 
		$output->addHTML( wwfHidden('wwPreviewKey',self::previewKey()) );
		$output->addHTML( wwfHidden('ww-action-preview-key',self::previewKey()) );
		$output->addHTML( wwfHidden('wwPreviewPage',self::$_previewPage) );
		return true;
	}

	# when calling actions like remove-project-file within a preview operation
	static function ApiCallArguments_hook( &$args ) {
		$pkey = self::previewKey();
		if ( $pkey != '' ) {
			$args['preview-key'] = htmlspecialchars($pkey);
		}
		return true;
	}

	# Normally MW parses the page during the save operation, and stores it
	# in the parser cache, then the next thing it does is redirect you to
	# the page, so that when you view it you get it from the cache.
	# I want to do it in a different order: first complete the save to the
	# database, to make sure it succeeded; then merge the preview files to
	# the main project directory; then remake and render the project files 
	# in the main directory.  So I disable the parser cache and skip all 
	# project operations during the save, so then on the next page view
	# we parse the page and all the make operations get done, in the proper
	# sequence.
	static function attemptSave_hook($editpage) {
		WWPreview::$save_in_progress = true;
		global $wgEnableParserCache;
		WWPreview::$enable_parser_cache_normally = $wgEnableParserCache;
		$wgEnableParserCache = false;
		global $wwContext;
		$wwContext->wwInterface->debug_message("Save in progress");
		return true;
	}

	# when we save after previewing, we might be able to merge the changes
	# in project files into the main working directory.
	# But only if there's no possible edit conflict.  We only merge files
	# if nobody has done anything that could conceivably create a conflict
	# in the time since we started editing.	That is, if another preview
	# session has been created since this one was, or anything has been done
	# to the persistent project directory, we don't merge.
	# the signature for this function has changed - I'm supporting the more
	# recent version: if people don't want PHP warnings they should upgrade
	static function ArticleSaveComplete_hook( 
		 &$article, &$user, $text, $summary,
		 $minoredit, $watchthis, $sectionanchor, &$flags, $revision, &$status, 
		 $baseRevId) {
		global $wwContext;
		$pkey = self::previewKey();
		if ($pkey == '') {
			return true;
		}
		# sometimes we save various articles aside from the one being edited
		global $wgTitle;
		if ($article->getTitle() !== $wgTitle) {
			return true;
		}
		$wwContext->wwInterface->debug_message("Merge from preview directory");
		if (is_array($wwContext->wwInterface->projects_in_use)) {
			foreach ($wwContext->wwInterface->projects_in_use as $projectname=>$t) {
				try {
					$project = $wwContext->wwStorage->find_project_by_name($projectname);
					if ($project != null) {
						wwLog("Merge project " . $projectname . " from preview directory");
						ProjectEngineConnection::call_project_engine(
							'merge-session',
							$project
						);
					} else {
						$wwContext->wwInterface->debug_message("Could not access project "
							. htmlspecialchars($projectname));
					}
				} catch (WWException $ex) {
				}
			}
		}
		# and now that we've merged the files, don't use the preview version
		# any more.
		self::$_previewKey = '';
		try {
			$wwContext->wwStorage->update_archived_project_files();
		} catch (WWException $ex) {
		}	
		if (WWPreview::$save_in_progress) {
			global $wgEnableParserCache;
			$wgEnableParserCache = WWPreview::$enable_parser_cache_normally;
			WWPreview::$save_in_progress = false;
		}
		return true;
	}

	# when we're previewing, links to project files need to point to
	# the preview version.
	static function GetProjectFileQuery_hook(&$q) {
		$key = self::previewKey();
		$page = self::$_previewPage;
		if ( $key != '' and !WWPreview::$save_in_progress
		     and !preg_match('/resources=/', $q) ) {
			#wwLog( "given query: $q\n");
			$q = str_replace(
				'&filename=',
				'&wwPreviewKey='
				. urlencode($key)
				. '&wwPreviewPage='
				. urlencode($page)
				. '&ww-action-preview-key='
				. urlencode($key)
				. '&filename=',
				$q
			);
			$q = str_replace(
				'&amp;filename=',
				'&amp;wwPreviewKey='
				. urlencode($key)
				. '&amp;ww-action-preview-key='
				. urlencode($key)
				. '&amp;wwPreviewPage='
				. urlencode($page)
				. '&amp;filename=',
				$q
			);
			#wwLog( "rewrite query for preview: $q\n");
		}
		return true;
	}

	# the preview key gets passed through links to ManageProject too.
	static function MakeManageProjectQuery_hook(&$q, $readonly) {
		$key = self::previewKey();
		$page = self::$_previewPage;
		if (!$readonly and $key != '' and !WWPreview::$save_in_progress) {
			$q .= "&wwPreviewKey="
				. urlencode($key)
				. '&wwPreviewPage='
				. urlencode($page)
				. '&ww-action-preview-key='
				. urlencode($key);
		}
		return true;
	}

	static function session_key_message() {
		global $wwContext;
		// TODO: this is cheap, use a div with its own class.
		// And an internationalized message.
		$wwContext->wwInterface->record_message(
			"Preview session key is <tt>"
			. htmlspecialchars(self::previewKey())
			. "</tt>."
		);
		return $wwContext->wwInterface->report_errors();
	}

	# notify user of preview key, so they can tell which pages are for what
	static function EditPage__showEditForm_initial_hook( $editPage ) {
		$key = self::previewKey();
		if ($key != '') {
			global $wgVersion;
			if ( version_compare( $wgVersion, '1.17', '<=' ) ) {
				wfLoadExtensionMessages('WorkingWiki');
			}
			# use editFormPageTop to put the message just under the page title
			# use editFormTextTop to put the message below the preview, above the 
			# edit form
			$editPage->editFormPageTop .= self::session_key_message();
		}
		return true;
	}

	# add to title of Special:GetProjectFile page (and Special:ManageProject).
	static function GetProjectFile_Headers_hook() {
		global $wwContext;
		$key = self::previewKey();
		if ($key != '') {
			global $wgOut;
			$wgOut->setPageTitleActionText( $wwContext->wwInterface->message('preview') );
			$wgOut->addHTML( self::session_key_message() );
			$wgOut->addHTML(
				"<div class='previewnote'>\n" .
				'<h2 id="mw-previewheader">' .
			 	htmlspecialchars( $wwContext->wwInterface->message( 'preview' ) ) .
				"</h2>"
			);
			$wgOut->addWikiText( $wwContext->wwInterface->message('previewnote') );
			$wgOut->addHTML( "<hr/></div>\n" );
		}
		return true;
	}

	# hide the preview key in the forms on the Special pages, so it gets 
	# passed through
	static function HiddenActionInputs_hook(&$hiddeninputs) {
		$key = self::previewKey();
		if ($key != '') {
			$hiddeninputs .= wwfHidden('wwPreviewKey',$key)
				. wwfHidden('ww-action-preview-key', $key)
				. wwfHidden('wwPreviewPage', self::$_previewPage);
			#wwLog("modified hidden inputs for preview: $hiddeninputs\n");
		}
		return true;
	}

	# hide the preview key in the forms on the Special pages, so it gets 
	# passed through
	static function UploadMissingFilesButton_hook(&$uploads, &$out) {
		$key = self::previewKey();
		if ($key != '') {
			return false;
		}
		return true;
	}

	# when GetProjectFile is asked for a file in a preview context, we have
	# to not re-sync the source files from the saved version of the page that's
	# being previewed!  That's why we recorded which page is being previewed.
	# TODO: it causes error messages when the source/archived files on the page 
	# are missing.  Maybe better to cache it but flag not to use what's on it.
	# No, that doesn't work either.	Actually, maybe don't sync at all unless
	# we're actually previewing the page, in which case do cache it - that works.
	static function CachePageFromDB_hook($title) {
		$pkey = self::previewKey();
		if ($pkey == '') {
			return true;
		}
		global $wgTitle;
		if ($wgTitle->getPrefixedDBKey() == self::$_previewPage) {
			#wwLog(
			#	"Cache page "
			#	. $title->getPrefixedText()
			# 	. "? yes\n"
			#);
			return true;
		}
		#wwLog("Cache page ". self::$_previewPage . "\n");
		return false;
	}

	static function DynamicProjectFilesPlaceholderMessage_hook( &$text, $filename, $projectname, $link ) {
		global $wwContext;
		$pkey = self::previewKey();
		if ($pkey != '') {
			$text = $wwContext->wwInterface->message( 'ww-dynamic-project-file-placeholder-preview', $filename, $projectname );
		}
		return true;
	}
}
