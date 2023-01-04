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

global $wwExtensionDirectory;
require_once "$wwExtensionDirectory/misc.php";

/** 
 * ImportQueue - an object to do the work of importing source/project file
 * contents into wiki pages, including text pages (and importing
 * whole pages, when they come along with the project data). It's used by 
 * Special:ImportProjectFiles, the import api, and the
 * importProjectFiles.php script.
 *
 * It does not take care of registering files in projects, and does not
 * understand Upload objects: it need to be provided the path or text of each
 * file to upload.
 */
class ImportQueue
{
	var $edits;

	public function &lookup_page($pagename) {
		global $wwContext;
	       	$title = Title::newFromText($pagename);
		if (! $title instanceof Title )
			$wwContext->wwInterface->throw_error("Bad page title '{$pagename}' for editing.");
		$pagename = $title->getPrefixedDBKey();
		if ( ! isset($this->edits[$pagename]) )
		{ $this->edits[$pagename] = array();
			$this->edits[$pagename]['pagename'] = $pagename;
			$this->edits[$pagename]['article'] = new Article($title, 0);
			$this->edits[$pagename]['text'] = $title->exists() ?
				$this->edits[$pagename]['article']->getContent() : '';
		}
		return $this->edits[$pagename];
	}

	public function insert_file_element( 
	  $src, $filename, $projectname, $pagename, $filetext, 
	  $extra_attrs='' ) {
		global $wwContext;
		$pgentry =& self::lookup_page($pagename);
		$archived = (!$src and ($filetext != null));
		$insertion = $wwContext->wwStorage->insert_file_element_in_page_text(
			$src, $archived, $filename, $projectname, $filetext,
			$pgentry['text'], $pagename, $extra_attrs );
		if ( $insertion === false ) {
			return false;
		}
		$pgentry['text'] = $insertion[0];
		$pgentry['edited'] = true;
		#wwLog( "insert ". ($src ? 'source' : 'project'). 
		#	" file element in page $pagename." );
		return true;
	}
		
	/* put page text onto a page.
	 * what to do if the page exists? three options: 'append', 'overwrite',
	 * 'leave'.
	 */
	public function insert_page_text( $pagename, $pagetext, 
	  $projectname=null, $if_exists='append' ) {
		global $wwContext;
		if ($projectname !== null) {
			if ($projectname != $pagename)
			{ $pagetext = preg_replace('/project=""/', 
					"project=\"$projectname\"", $pagetext );
			}
			else
			{ $pagetext = str_replace(' project=""', '', $pagetext);
			}
		}
		# Don't append to existing page - it causes duplicate file elements.
		# If page exists, upload to a different page.
		#do {
		#	$sfx = ($index ? "-$index" : '');
		#	$pgentry =& self::lookup_page("$pagename$sfx");
		#} while ($pgentry['text'] != '');
		$pgentry =& self::lookup_page($pagename);
		if ($pgentry['text'] == '' or $if_exists == 'overwrite') {
			$pgentry['text'] = $pagetext;
		} else if ($if_exists == 'append') {
			$pgentry['text'] .= $pagetext;
		} else {
		       	if ($if_exists != 'leave')
				$wwContext->wwInterface->record_error("Unknown value for \$if_exists: "
					. htmlentities($if_exists) . "\n");
			return false;
		}
		$pgentry['edited'] = true;
		#wwLog( "insert ". strlen($pagetext). " characters in page $pagename." );
		return true;
	}

	public function upload_file( $pagename, $filename ) {
	       	$pgentry =& self::lookup_page($pagename);
		if ($pgentry['text'] == '')
			$pgentry['text'] = "Imported by WorkingWiki";
		$pgentry['upload'] = $filename;
	}

	public function commit( $overwrite_images=false ) {
	       	#wwLog( "commit:" );
		global $wwContext, $wwSuppressWorkingDuringImport;
		$wwSuppressWorkingDuringImport = true;
		# extraordinary measures to keep the page from going into
		# the parser cache while we're saving it. When saving from
		# one of several ImportProjectFiles pages, it's not useful to 
		# parse project-file tags before the rest of the project is uploaded.
		# This intervention postpones parsing until someone views the page.
		# note $image->upload() as well as doEdit() can write to the parser cache.
		global $wgEnableParserCache;
		$epc = $wgEnableParserCache;
		$wgEnableParserCache = false;
		if ( is_array($this->edits) ) {
			foreach ($this->edits as &$edit) {
			       	# do this test even for uploads
				$details = null;
				if ( !wwfOKToEditPage( $edit['article']->getTitle(), $details ) ) {
					# $wwContext->wwInterface->record_error( "Edit operation not permitted." );
					return false;
				}
				if (isset($edit['upload'])) {
				       	#wwLog( "commit upload {$edit['pagename']}" );
					/** (borrowed from Special:Upload)
					 * If the image is protected, non-sysop users won't be able
					 * to modify it by uploading a new revision.
					 */
					global $wgUser;
					$nt = $edit['article']->getTitle();
					$image = wfLocalFile( $nt );
					if ( $image->exists() ) {
					       	if ($overwrite_images) {
							#wwLog( "Image {$edit['pagename']} exists, overwriting." );
						} else {
						       	#wwLog( "Image {$edit['pagename']} exists, skipping." );
							continue;
						}
					}
					$permErrors = $nt->getUserPermissionsErrors( 'edit', $wgUser );
					$permErrorsUpload = $nt->getUserPermissionsErrors( 'upload', $wgUser );
					$permErrorsCreate = ( $nt->exists() ? array() : $nt->getUserPermissionsErrors( 'create', $wgUser ) );

					if( $permErrors || $permErrorsUpload || $permErrorsCreate ) {
						// merge all the problems into one list, avoiding duplicates
						$permErrors = array_merge( $permErrors, wfArrayDiff2( $permErrorsUpload, $permErrors ) );
						$permErrors = array_merge( $permErrors, wfArrayDiff2( $permErrorsCreate, $permErrors ) );
						global $wgParser, $wgOut, $wgTitle;
						$text = $wgOut->formatPermissionsErrorMessage( $permErrors );
						$lparser = new Parser;
						if (method_exists('ParserOptions', 'newFromUser')) {
							$lparserOptions = new ParserOptions($wgUser);
						} else {
						       	$lparserOptions = new ParserOptions;
							$lparserOptions->initialiseFromUser($wgUser);
						}
						$parserOutput = $lparser->parse($text, $wgTitle, $lparserOptions);
						$text = preg_replace('/(\s*\n)+/', "\n", 
							strip_tags($parserOutput->getText()));
						$wwContext->wwInterface->record_error( $text );
						return false;
					}
					# upload needs it to be in an actual file already
					$status = $image->upload( $edit['upload'], 
						'WorkingWiki importation', $edit['text'] );
					if ( WikiError::isError($status) ) {
						$wwContext->wwInterface->record_error( "Error uploading file {$edit['upload']}"
							." to page {$edit['pagename']}: ".$status->getMessage()."\n" );
					} else if (!$status->isGood() ) {
						$wwContext->wwInterface->record_error( "Error uploading file {$edit['upload']}"
							." to page {$edit['pagename']}: ".$status->getWikiText()."\n" );
					}
				} 
				else if ($edit['edited']) {
				       	#wwLog( "commit page {$edit['pagename']}" );
					$edit['article']->doEdit( $edit['text'], 
						'WorkingWiki importation', 0 );
					# this causes it to be done properly the first time the article
					# is actually viewed.
					# unfortunately not reliable because needs to be done at least
					# 1 second after the doEdit().
					#$edit['article']->getTitle()->invalidateCache();
					#wwLog("invalidateCache on page " . 
					#	$edit['article']->getTitle()->getPrefixedText() );
				}
				else {
					#wwLog( "page {$edit['pagename']} not edited" );
				}
			}
		}
		$wgEnableParserCache = $epc;
		$wwSuppressWorkingDuringImport = false;
		#wwLog( "end of commit.");
		return true;
	}

	public static function tempDir() {
		global $wwContext, $wwUnpackDirectory;
		if (!is_dir($wwUnpackDirectory))
			$wwContext->wwInterface->throw_error("Unpack directory not found.");
		return $wwUnpackDirectory;
		$prefix = $wgTmpDirectory;
		//$prefix = sys_get_temp_dir();
		while ($prefix[strlen($prefix)-1] == '/')
			$prefix = substr($prefix,0,-1);
		return $prefix;
	}

	static public $pkgExtensions = array('tar','tgz','tar.gz','zip');
	static public $unpackDirBase = 'WW_ImportProject_';

	# given a package (e.g. tar.gz or .zip file), unpack it into a temporary
	# location.	
	# $pkgFile is the file to be unpacked, $srcName is the name the file's
	# supposed to have ($pkgFile might be /tmp/php192ujhK and $srcName be
	# project_A.tar.gz).
	# In case of success, return value is array(true,location_code), where
	# location_code is the unique part of the path such that 
	# tempDir().'/'.$unpackDirBase.location_code is the name of the directory 
	# where the unpacked files are.
	# In case of error, returns array(false, error_message).
	public function unpack($pkgFile,$srcName) {
	       	#wwLog("unpack $pkgFile as $srcName");
		try {
			$unpackLocation
				= realpath( tempnam( ImportQueue::tempDir(),
						ImportQueue::$unpackDirBase ) );
			$prefix = realpath( ImportQueue::tempDir() ).'/'.
				ImportQueue::$unpackDirBase;
			#wwLog("unpackLocation is $unpackLocation, prefix is $prefix");
		} catch ( WWException $ex ) {
		       	return array(false, "Error locating temp directory for unpacking." );
		}
		if (strncmp($unpackLocation,$prefix,strlen($prefix)) != 0) {
		       	#wwLog( "Temp directory $unpackLocation doesn't start with $prefix!" );
			return array(false, "Temp directory " . htmlentities($unpackLocation) .
				" doesn't start with " . htmlentities($prefix) . "!" );
		}
		$locationCode = substr($unpackLocation,strlen($prefix));
		#wwLog( "location code is " . $locationCode );
		if ( file_exists($unpackLocation) and ! unlink($unpackLocation) ) {
		       	return array(false, "Couldn't unlink $unpackLocation");
		}
		if ( ! mkdir($unpackLocation) ) {
		       	return array(false, "Couldn't make temp dir $unpackLocation");
		}
		if ( ! chmod($unpackLocation, 0700) ) {
		       	return array(false,
				"Couldn't set restricted permissions on temp dir $unpackLocation");
		}
		# tar seems trustworthy not to extract files into other locations
		# TODO (found on Talk:WorkingWiki page):
		# When unpacking uploaded .tar.gz files: [http://en.wikipedia.org/wiki/Tar_(file_format) Wikipedia says]: GNU tar by default refuses to create or extract absolute paths, but is still vulnerable to parent-directory references.  So I would need to check tar contents for ../ before extracting.  In practice it seems to be catching this case — but I should trap it explicitly anyway?
		if ( wwfSuffixMatches($srcName,'.tgz') or wwfSuffixMatches($srcName,'.tar.gz')) {
		       	$unpack_command = 'tar -xz -C '.escapeshellarg($unpackLocation)
				.' -f ' . escapeshellarg($pkgFile);
		} else if ( wwfSuffixMatches($srcName,'.tar') ) {
		       	$unpack_command = 'tar -x -C ' . escapeshellarg($unpackLocation)
				. ' -f ' . escapeshellarg($pkgFile);
		}
		# unzip also rejects files outside of the extraction directory
		else if ( wwfSuffixMatches($srcName, '.zip') ) {
		       	$unpack_command = 'unzip -q ' . escapeshellarg($pkgFile)
				. ' -d ' . escapeshellarg($unpackLocation);
		} else {
		       	return array( false, "Unknown filetype $srcName" );
		}
		//$resultDetails = array('internal'=> "Command: $unpack_command\n" );
		//return CustomUploadForm::INTERNAL_ERROR;
		system($unpack_command, $unpack_success);
		if ($unpack_success != 0) {
		       	return array(false,
				"command “{$unpack_command}” failed with return code $unpack_success");
		}
		return array(true, $locationCode);
	}

};
