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

/*
 * implementation for Special:GetProjectFile
 *
 * This Special page is the interface for retrieving project files from
 * a project's working directory.  It's important for viewing log files,
 * serving images, and getting PDF files from LaTeX projects.
 *
 * Working directories are intended to be stored outside the web server's
 * directory tree, so the project files can not be served directly.
 * Instead, this Special page serves them.  It can do several things:
 *
 *  - present the contents of a file in a wiki page with header, sidebar, etc.
 *  - serve a file directly.  Image pages served this way can be embedded
 *     in HTML pages, binary files can be downloaded, etc.
 *  - present a listing of a working directory or subdirectory, including
 *     HTML forms for some useful operations on the directory's contents.
 *
 * Invocation is Special:GetProjectFile?project=PROJECT&filename=FILE
 * or two variant forms,
 * Special:GetProjectFile/project=PROJECT/filename=FILE
 * and Special:GetProjectFile/PROJECT/FILE
 * where PROJECT and FILE are replaced by the relevant data.
 *
 * When the project argument is omitted, the file is retrieved from 
 * ProjectEngine's resources/ directory instead of from a project 
 * directory.
 *
 * GET arguments can also include
 *   make=[true|false] : whether to invoke make before retrieving the file.
 *      The default is make=true, except when the filename is '.'.
 *   display=raw       : serve the file directly regardless of its type.
 *      The default is to serve text files in wiki pages, binary files
 *      directly, directories as directory pages.  display=raw is ignored
 *      for directories.  Note that if display=raw is explicitly specified
 *      and the file can't be made, this page will output a 404 Error, while
 *      if this argument isn't explicitly included, it will output a wiki 
 *      page reporting the error. 
 */
define( 'MW_NO_OUTPUT_COMPRESSION', 1 );

global $wwExtensionDirectory;
//require_once($wwExtensionDirectory.'/misc.php');

global $IP;
require_once( "$IP/includes/StreamFile.php" );

class SpecialGetProjectFile extends SpecialPage {

	function __construct() {
		parent::__construct('GetProjectFile');
		// need to omit in 1.18, call in older MW
		global $wgVersion;
		if ( version_compare( $wgVersion, '1.17', '<=' ) ) {
			wfLoadExtensionMessages('WorkingWiki');
		}
		$this->mIncludable = false;
		$this->mListed = false;
	}

	function setHeaders() {
		parent::setHeaders();
		global $wgRequest;
		$wgRequest->response()->header('X-Generator: WorkingWiki' );
	}

	function execute( $par ) {
		global $wgRequest, $wgOut, $wwContext;

		# hackish workaround: GetProjectFile/project=P/filename=F/x=y/....
		# Not supposed to do that way, but works in wikitext internal links.
		# let's also support GetProjectFile/project=P/arg=val/.../F1/F2
		# where F1/F2 is the filename, possibly including slashes.
		# This might actually allow project files to include relative URLs.
		if (strpos($par, '=') !== false) {
			#if (preg_match('/^(.*?)=(.*?)(\/[^\/]*=.*)$/',$par,$matches))
			#	$args['matches'] = print_r($matches,true);
			#wwLog("par = $par\n");
			$terms = explode('/',$par);
			$sterms = array();
			while ( ($term = array_shift($terms)) !== null ) {
				while ( substr($term, strlen($term) - 1) == '\\' ) {
					if ( ($tx = array_shift($terms)) !== null ) {
						$term .= '/' . $tx;
					}
				}
				$sterms[] = $term;
			}
			foreach ($sterms as $term) {
				if(!preg_match('/^(.*)=(.*)$/',$term,$matches)) {
					$val = $wgRequest->getVal('filename');
					if ($val != '') {
						$matches = array($term,'filename',$val.'/'.$term);
					} else {
						$matches = array($term,'filename',$term);
					}
				}
				#wwLog("$term : $matches[1] = $matches[2]\n");
				$val = str_replace( '\/', '/', $matches[2] );
				if (is_callable("WebRequest::setVal")) {
					$wgRequest->setVal($matches[1], $val);
				} else {
					$wgRequest->data[$matches[1]] = $val;
				}
			}
			//while (preg_match('/^(.*?)=(.*?)\/?(.*)$/',$par,$matches))
			//{ #$wgRequest->setVal($matches[1],$matches[2]);
			//	$wgRequest->data[$matches[1]] = $matches[2];
			//	$par = $matches[3];
		 // }
		} else {
			# other odd case: GetProjectFile/P/F (only works when no / in P or F)
			# this is older, should maybe be dropped
			$slash_args = explode( '/', $par );
			if (count($slash_args) == 2) {
				#$args['project'] = $slash_args[0];
				#$args['filename'] = $slash_args[1];
				if (is_callable("WebRequest::setVal")) {
					$wgRequest->setVal('project', $slash_args[0]);
					$wgRequest->setVal('filename',$slash_args[1]);
				} else {
					$wgRequest->data['project'] = $slash_args[0];
					$wgRequest->data['filename'] = $slash_args[1];
				}
			}
		}

		# normal case: GetProjectFile?project=P&filename=F
		$args['project'] = $wgRequest->getText('project');
		$args['filename'] = $wgRequest->getText('filename');

		# if invoked with ?make=false, it will just look for the file
		# as is.  Otherwise it will try to sync source files and make
		# before serving.
		$args['make'] = $wgRequest->getText('make','');
		if ($args['make'] == '') {
			$args['make'] = $wgRequest->getText('remake','');
		}
		if ($args['make'] == '') {
			$args['make'] = true;
		} else {
			$args['make'] = wwfArgumentIsYes( $args['make'] );
		}

		# ?resources=1 forces retrieving the file from the resources 
		# directory (we can also do this by not providing a project argument,
		# but in a preview or background job the missing project name means
		# the file is in the session directory).
		#$args['resources'] = $wgRequest->getText('resources','');
			
		# options for ?display= :
		#  source    : display the source code.
		#              If it's too long for highlighting, it is displayed as 
		#              <pre>; if it's too long for that it falls back to a link.
		#  image     : display image as an image in the wiki page.
		#  html      : display content of an HTML file.  Whether this is HTML 
		#              that actually can be embedded in a wiki page is up to you.
		#  link      : display a link that can be used to download the file.
		#  download  : give up the file itself, without a wiki page.
		#  raw       : same as download, except it doesn't yield a wiki page
		#              even in case of an error.
		#  raw-html  : produce an HTML representation of the file's contents,
		#              without a surrounding wiki page.
		# The raw option is used by WW to provide the images that are
		#  included in other wiki pages.
		# The others are often encountered as defaults, but can be used 
		#  explicitly - for instance in the case of an HTML or SVG file you
		#  have the option whether to display what it looks like (the default)
		#  or its source code.

		$args['display'] = $wgRequest->getText('display',null);

		#if ($args['display'] == 'raw')
		#	$args['display'] = 'download';

		$args['raw-errors'] = 
				($args['display'] == 'raw' or $args['display'] == 'raw-html');
		$args['raw-output'] = 
				($args['raw-errors'] or $args['display'] == 'download');
		if ( $args['display'] == 'raw-html' ) {
			$args['display'] = 'html';
		}

		$args['dynamic'] = (! $args['raw-output']) && wwfDynamicDisplayInEffect();

		# if no filename or project argument, what is there to do?
		if ($args['filename'] == '' and $args['project'] == '') {
			if ($args['display'] == 'raw') {
				$this->raw_error("Error",
					$wwContext->wwInterface->message('ww-need-filename'), 
					'400 Bad Request');
			} else {
				$wgOut->showErrorPage( 'ww-errorpage', 'ww-need-filename' );
			}
			return;
		}

		# if invoked without a filename argument, it will produce a 
		# directory listing
		if ( $args['filename'] == '' or $args['filename'] === false
		     or is_null($args['filename']) ) {
			$args['filename'] = '.';
		}

		# check for CGI arguments to be given to the make process
		# (after validation).
		$cgiArgs = array();
		foreach ($wgRequest->getValues() as $key => $value) {
			if ( preg_match('/^CGI_ARG_/', $key) ) {
				$cgiArgs[$key] = $value;
			}
		}

		# this is unfortunately necessary because of a hack involving NetLogo:
		# the applet needs to request its .nlogo source code file from this
		# Special: page, and it doesn't use the cookies properly; so the .nlogo
		# file pretty much needs to be made available to anonymous visitors.
		# so this hack makes Special:GetProjectFile accessible to all viewers,
		# and now here we make it inaccessible again, except in that special case.
		global $wgOut, $wgTitle, $wgWhitelistRead;
		global $wwInsecureNetLogoHack;
		if ( $wwInsecureNetLogoHack
		     and preg_match('/\.nlogo$/',$args['filename'])
		     and $args['raw-output']
		     and $wgRequest->getCheck('netlogo-request') ) {
			#wwLog("Special access for netlogo source file granted.\n");
		} else {
			if ( is_array($wgWhitelistRead) and 
			     ($key = array_search('Special:GetProjectFile',$wgWhitelistRead)) ) {
				# FIXME: security risk with XXXX? just unset.
				$wgWhitelistRead[$key] = 'XXXX';
			}
			if (!$wgTitle->userCanRead()) {
				if ($args['raw-errors']) {
					$this->raw_error("Login required",
						"You must log in to view this file.",
						'401 Unauthorized');
				} else {
					$wgOut->loginToUse();
					$wgOut->output();
				}
				exit;
			}
		}

		# we might be invoked with a 'ww-action' argument (particularly in
		# the directory-listing case).	If so, do the operation.
		$action_result = WWAction::execute_action($wgRequest);
		if ($action_result['status'] == WW_ABORTOUTPUT) {
			return;
		}
		if ( $action_result['status'] != WW_NOACTION ) {
			# if any errors have been accumulated yet, report them.
			$wgOut->addHTML( wwfSanitizeForSpecialPage(
				$wwContext->wwInterface->report_errors()
			) );
			$wgOut->addHTML( $action_result['html'] );
			$wgOut->addHTML( "<hr/>\n" );
		}

		if ( $args['project'] != '' ) {
			$wwContext->wwInterface->project_is_in_use( $args['project'] );
		}
		try { 
			wwRunHooks('WW-BeforeGetProjectFile', 
				array(&$this,&$args['project'],$args['filename']));
		} catch( WWException $ex ) {
			$errhtml = $wwContext->wwInterface->report_errors();
			if ($errhtml != '') {
				$wgOut->addHTML( wwfSanitizeForSpecialPage($errhtml) . "\n<hr/>\n" );
			}
		}

		global $wgHooks;
		# set hook function to make the 'special page' tab point where I want it to
		# on Special:ManageProject (for MW<1.18.0)
		$wgHooks['SkinTemplateBuildContentActionUrlsAfterSpecialPage'][]
			= array( $wwContext->wwInterface, 'fix_special_tab_old' );
		# hook for special page tabs in MW>=1.18.0
		$wgHooks['SkinTemplateNavigation::SpecialPage'][]
			= array( $wwContext->wwInterface, 'fix_special_tab' );

		# when this page is requested, there are several cases:
		#	can't figure out what the requester wants
		#	make the file and serve it directly, for instance if it's pdf
		#	make the file and include in the special wiki page framework

		//$wgOut->addHTML( "par is $par" );
		//$wgOut->addHTML( "<br/>". print_r($args,true) );
		//$extension = strtolower(preg_replace('/^.*\./','',$args['filename']));
		$extension = ProjectDescription::type_of_file($args['filename']);
		global $wwImageExtensions;

		$err_altlinks = array();
		# arguments have been parsed, try to make the target file.
		try {
			$okay_to_serve_file = false;
			if ( ! ProjectDescription::is_allowable_filename($args['filename']) ) {
				$wwContext->wwInterface->throw_error( "Prohibited filename '".htmlentities($args['filename'])."'." );
			}
			$make_target = preg_replace( '/.make.log$/', '', $args['filename'] );
			if ( $resources = wwfArgumentIsYes( $wgRequest->getText('resources',null) ) ) {
				$project = ResourcesProjectDescription::factory();
				$make_success = false;
				$okay_to_serve_file = true;
				$file_label = $wwContext->wwInterface->message('ww-resources-dir')
					. " > " . htmlentities($args['filename']);
			} else if ($args['project'] != '') {
				$project = $wwContext->wwStorage->find_project_by_name($args['project']);
				if ( ! $project instanceOf ProjectDescription ) {
					$wwContext->wwInterface->throw_error("Project '" . htmlentities($args['project']) 
						. "' not found." );
				}
				$wwContext->wwInterface->default_project_name = $project->project_name();
				if ( wwRunHooks(
					'WW-GetProjectFile-LocateFile',
					array(
						&$wgRequest,
						&$project,
						$args['filename'],
						&$file_label,
						&$filepath,
						&$location_name
					)
				) ) {
					$file_label = htmlentities($args['project'])
						. " > " . htmlentities($args['filename']);
				}

				# make the target if appropriate
				if ( $args['make'] and
				     !wwRunHooks('WW-AllowMakeInSession', array(&$wgRequest, &$bgm))) {
					$wwContext->wwInterface->record_error( $bgm );
					$args['make'] = false;
				}
				if ($args['make'] and $make_target != '.' and ! $args['dynamic']) {
					try {
						$make_success = 
							ProjectEngineConnection::make_target($project,$make_target,array(),$cgiArgs);
					} catch ( WWException $ex ) {
						$make_success = false;
					}
					$okay_to_serve_file = $make_success;
					if ( ! $make_success ) {
						$logfilename = $make_target.'.make.log';
						$wwContext->wwInterface->record_error( 
							# don't include [log] - it appears on the page just below here
							# $wwContext->wwInterface->altlinks_text($project,$args['filename'],array(),true) .
							$wwContext->wwInterface->message(
								'ww-make-failed',
								htmlspecialchars($make_target),
								$wwContext->wwInterface->make_get_project_file_url($project,$logfilename,false)
							)
					       	);
					}
					$wwContext->wwStorage->update_archived_project_files();
				#} else if ($project->is_file_source($args['filename'])) {
				# else if it's a source file, we'll serve what we've got in the wiki
				# FIXME: ??
				#	#$project->sync_source_file($args['filename']);
				#	$okay_to_serve_file = true;
				} else {
					# otherwise hand over whatever's in the working directory
					$make_success = false;
					$okay_to_serve_file = true;
				}
			} else {
				$make_success = false;
				$okay_to_serve_file = true;
				# TODO
				if ( wwRunHooks(
					'WW-GetProjectFile-LocateFile',
					array(
						&$wgRequest,
						&$project,
						$args['filename'],
						&$file_label,
						&$filepath,
						&$location_name
					)
				) ) {
					$file_label = htmlentities($args['filename']);
				}
			}

			# check the file for dangerous scripts or other weird content.
			# skip this step for resources/ files, presuming we can trust
			# them (because the verify function requires a Project object).
			#global $wwValidateProjectFiles;
			#if ($okay_to_serve_file
			#    and $wwValidateProjectFiles 
			#    and $args['project'] != '') {
			#	try { # if this flags something, it will raise an exception.
			#          $wwContext->wwInterface->verify_file_before_displaying(
			#          htmlentities($args['filename']),$project,false);
			#	} catch (WWException $ex) {
			#          $okay_to_serve_file = false;
			#          throw $ex;
			#	}
			#}

			$serve_file_raw =
				( $args['raw-errors'] or
			          ( $args['raw-output'] and $okay_to_serve_file ) );

			$file_exists = false;

			# 'project' argument to send to ProjectEngine - used in 2 different
			# places below
			$proj_arg = $project;
			if ( ( isset($args['resources']) and $args['resources'] ) or
			     ( $proj_arg == '' and
			       wwRunHooks(
					'WW-GetProjectFile-AssumeResourcesDirectory',
					array($wgRequest, $proj_arg)
			       )
			) ) {
				$proj_arg = 'resources';
			}

			# if we're going to serve up the file, set it up here.
			if ($okay_to_serve_file) {
				if (!$args['display']) {
					$args['display'] = $wwContext->wwInterface->default_display_mode(
						ProjectDescription::type_of_file($args['filename'])
					);
					if ($args['display'] == 'link') {
						$args['display'] = 'download';
						$serve_file_raw = $okay_to_serve_file;
					}
					//	$wwContext->wwInterface->record_message("File '" 
					//		. htmlspecialchars($args['filename']) . "' cannot be "
					//		. "displayed directly.	Click the link below to download it.");
				}
				#wwLog("We think file ".$args['filename']." displays as "
					#.$args['display']."\n");
				$file_contents = null;
				if ($args['display'] == 'link' or $args['display'] == 'image') {
					$file_doesnt_need_to_exist = true;
				} else if ($serve_file_raw and $args['display'] == 'html') {
					$file_contents = $wwContext->wwInterface->display_file_contents($project,
						$args['filename'], $file_contents, false, '',
						false, array(), null, /*getprojfile*/true);
					$args['filename'] .= '.html';
				} else if (!$serve_file_raw) {
					try {
						$pe_result = ProjectEngineConnection::call_project_engine(
							'retrieve',
							$proj_arg, 
							array( 'target'=>$args['filename'] )
						);
						if ( is_array($pe_result) and
						     array_key_exists('target-file-contents',$pe_result) ) {
							$file_contents = $pe_result['target-file-contents'][$args['filename']];
						}

						if (is_null($file_contents)) {
							throw new WWException;	
						}
						#	$wwContext->wwInterface->throw_error("Couldn't read file ‘"
						#		. $file_label . "’.");
						$file_exists = true;
						if ($file_contents[0] == 'd') {
							$vpf = $wwContext->wwInterface->message('viewprojectfile');
							$wgOut->setHTMLTitle("$vpf: $file_label");
							$this->setHeaders();
							$wgOut->setPageTitle("$vpf: $file_label");
							wwRunHooks('WW-GetProjectFile-Headers',array());
							$this->list_directory( $file_contents[1], $project, $args['filename'] );
							return;
							# todo - should we have make=yes for directories? do we?
						} else if ($file_contents[0] == 'c') {
							$file_contents = $file_contents[1];
						} else if ($file_contents[0] == 'p') {
							global $wwMaxLengthForSourceCodeDisplay;
							if (filesize($file_contents[1]) > $wwMaxLengthForSourceCodeDisplay) {
								$wwContext->wwInterface->record_error('file '
									. htmlspecialchars( $args['filename'] )
									. ' is too large to display: providing a link instead.' );
								if ($args['display'] == 'html') {
									$check_html_file_intro = file_get_contents(
										$file_contents[1],
										false,
										NULL,
										-1,
										10
									);
								}
								$file_contents = null;
								$args['display'] = 'link';
							} else {
								$file_contents = file_get_contents($file_contents[1]);
							}
						} else {
							$wwContext->wwInterface->throw_error("Bad data received from ProjectEngine");
						}

						# if it's a complete html page, don't display it inline,
						# put it on its own.
						if ($args['display'] == 'html') {
							$check_html_file_intro = $file_contents;
						}
						if ( isset($check_html_file_intro) and
						     preg_match('/<html\b/i', $check_html_file_intro) ) {
							$serve_file_raw = true;
						}
					} catch (WWException $ex) {
						$file_contents = null;
					}
				}
			}

			# In the display=raw case, this produces a 404 error if the file is missing
			if ($serve_file_raw) {
				global $wwInlineImageExtensions, $wwHtmlExtensions;
				$serve_inline = (
					in_array($extension,$wwInlineImageExtensions)
					or in_array($extension,$wwHtmlExtensions)
					or $args['display'] == 'html'
				);
				if (!is_null($file_contents)) {
					$this->serve_file_raw_from_contents(
						$args['filename'],
						$file_contents,
						!$serve_inline
					);
					return;
				} else {
					if ( !empty( $_SERVER['HTTP_IF_MODIFIED_SINCE'] ) ) {
						$modsince = preg_replace(
							'/;.*$/', '', 
							$_SERVER['HTTP_IF_MODIFIED_SINCE']
						);
						$sinceTime = strtotime( $modsince );
					}
					if (!isset($sinceTime)) {
						$sinceTime = -1;
					}
					$pe_result = ProjectEngineConnection::call_project_engine(
						'retrieve',
						$proj_arg, 
						array(
							'target' => $args['filename'],
							'output-directly' => true,
							'http-headers' => wwfHeadersForFile(
								$args['filename'], 
								0,
								!$serve_inline
							),
							'if-modified-since' => $sinceTime
						)
				       	);
					# if successful that call doesn't return after passing the 
					# output through to the client.
					wwLog( "Error in retrieve operation." );
					if ( $args['raw-errors'] ) {
						global $wgOut;
						$wgOut->disable();
						if ( is_array($pe_result) and 
						    ! (isset($pe_result['succeeded']) && $pe_result['succeeded']) ) {
							wwfHTTPError(
								404,
								$args['filename'],
								$wwContext->wwInterface->report_errors()
						    );
						} else {
							wwfHTTPError(
								500,
								'',
								$wwContext->wwInterface->report_errors()
							);
						}
						exit;
					} else {
						$okay_to_serve_file = false;
						# in this case, code below will append an error message with altlinks.
					}
				}
			}

			# if we arrive here we're not serving the file raw.

			$vpf = $wwContext->wwInterface->message('viewprojectfile');
			$wgOut->setHTMLTitle("$vpf: $file_label");
			$this->setHeaders();
			$wgOut->setPageTitle("$vpf: $file_label");
			$r_err = $wwContext->wwInterface->report_errors();
			wwRunHooks('WW-GetProjectFile-Headers',array());
			$wgOut->addHTML( wwfSanitizeForSpecialPage( $r_err ) );

			$wwContext->wwInterface->include_css('get-project-file',null); 

			global $wgScript, $wgTitle;
			$altlinks = array();
			if ( substr($args['filename'],-9) != '.make.log') {
				$altlinks['log'] = '';
			}
			$altlinks = $wwContext->wwInterface->alternatives_for_file( $project, $args['filename'], array(), $altlinks );
			if ($args['project'] != '') {
				if ($project->is_file_source($args['filename'])) {
					$sfpage = $project->project_files[$args['filename']]['page'];
					if ($sfpage == '') {
						$sfc = $project->find_source_file_content($args['filename'],null);
						if (isset($sfc['page'])) {
							$sfpage = $sfc['page'];
						}
					}
					if ($sfpage != '') {
						$altlinks['page'] = array( 'url' => "$wgScript/$sfpage" );
					}
					global $wgAutoloadClasses;
					if ( isset( $wgAutoloadClasses['SpecialMultiUpload'] ) ) {
						$altlinks['upload'] = array( 
							'url' => SpecialPage::getTitleFor( 'ImportProjectFiles' )->getLocalURL(
								'project='.urlencode($project->project_name())
								. '&wpProjFilename1='
								. urlencode($args['filename'])
								. '&wpDestPage1='
								. ($sfpage == '' ? wwfDefaultSuggestion(
									$args['filename'],
									$project->project_name()
								  ) : $sfpage)
								  . '&wpProjFilenameTouched=1'
							  ),
							  'write' => true,
						 );
					}
				} else {
					#if (!$args['make']) {
					if (wwRunHooks('WW-AllowMakeInSession', array(&$wgRequest,&$bgm))) {
						$altlinks['make'] = array(
							'url' => $this->makeQuery(array('filename'=>$make_target,'make'=>'true'),$project),
							'write' => true,
						);
					}
					#}
				}
			}
			if ( $file_exists or isset($file_doesnt_need_to_exist) ) {
				$altlinks['download'] = array(
					'url' => $this->makeQuery(array('display'=>'download','make'=>'false'),$project),
					'class' => 'ww-altlinks-download',
				);
			}
			wwRunHooks(
				'WW-GetProjectFile-altlinks', 
				array($project, $args['filename'], &$this, &$altlinks)
			);

			# if we're going to present the file in the wiki page, set it
			# up here.
			if ($okay_to_serve_file) {
				if ($args['display'] == 'link') {
					$displaymode = 'download';
					unset($altlinks['download']);
				} else {
					$displaymode = $args['display'];
				}
				#wwLog("Now we display file {$args['filename']} as $displaymode\n");
				#else if ($args['display'] == 'html') {
				#	# special security check
				#	$ddm = $wwContext->wwInterface->default_display_mode(
				#			Project::type_of_file($args['filename']));
				#	if ($ddm != 'html') {
				#		$wwContext->wwInterface->throw_error(
				#			"For browser safety reasons, we can"
				#			. " not serve non-html files as raw html."
				#		);
				#	}
				#}
				$output = $wwContext->wwInterface->report_errors();
				if ( $args['dynamic'] ) {
					$slcp = $this->makeQuery(array('filename'=>$args['filename'],'ww-static-files'=>'true'),$project);
					$placeholder = $wwContext->wwInterface->message(
						'ww-dynamic-project-file-placeholder',
						$args['filename'],
						$project->project_name(),
						$slcp
					);
					$parser = null;
					$output .= $wwContext->wwInterface->make_dynamic_placeholder( $project, $args['filename'], $displaymode, /*source*/false, $args['make'], $altlinks, $args, $placeholder, $parser );
				} else { 
					if ( !($file_exists or isset($file_doesnt_need_to_exist)) ) {
						if ( $make_success and $args['project'] != '' ) {
							#$okay_to_serve_file = false;
							$err_altlinks = $altlinks;
							$wwContext->wwInterface->throw_error(
								"‘make "
								. htmlentities($args['filename'])
								. "’ succeeded "
								. "but did not create file ‘" 
								. htmlentities($args['filename'])
								. "’.",
								WW_SUCCESS
							);
						} else {
							#$okay_to_serve_file = false;
							$err_altlinks = $altlinks;
							#$wgOut->addHTML( $wwContext->wwInterface->make_altlinks($altlinks) );
							$wwContext->wwInterface->throw_error(
								$wwContext->wwInterface->make_altlinks($altlinks)
								. "File ‘"
								. $file_label
								. "’ not found in "
								. ($args['project'] ? 'working' : $location_name)
								. ' directory.'
							);
						}
					#} else if (is_array($altlinks) and count($altlinks) > 0) {
					#	$wgOut->addHTML( $wwContext->wwInterface->make_altlinks($altlinks) );
					}

					#$alt_text = $wwContext->wwInterface->make_altlinks($altlinks);
					$output .= $wwContext->wwInterface->display_file_contents($project,
						$args['filename'], $file_contents, $displaymode, $altlinks,
						false, array(), null, /*getprojfile*/true);
				}
				$output_is_html = true;
			}
			else {
				$output = '';
				$wwContext->wwInterface->record_error( "Failed to retrieve $file_label." );
				$err_altlinks = $altlinks;
			}

		} catch (WWException $ex) {
			$output = '';
		} catch (MWException $e) {
			global $wgOut;
			$wgOut->showErrorPage(
				'ww-errorpage',
				wfMessage(
					'ww-getprojectfile-error-retrieving',
					$file_label, 
					wwfSanitizeForSpecialPage(
						$wwContext->wwInterface->report_errors()
					)
					. $e->getHTML()
				)
			);
			return;
		}

		$wgOut->addHTML( wwfSanitizeForSpecialPage(
				$wwContext->wwInterface->report_errors()
		) );
		if (!isset($output_is_html)) {
			global $withinParser;
			++$withinParser;
			$wgOut->addWikiText($output);
			--$withinParser;
		} else {
			$wgOut->addHTML($output);
		}
		return;
	}

	function raw_error($title, $msg, $errcode='404 Not Found') {
		global $wgOut;
		$wgOut->disable();
		header( "HTTP/1.0 $errcode" );
		header( 'Cache-Control: no-cache' );
		header( 'Content-Type: text/html; charset=utf-8' );
		header( 'X-Generator: WorkingWiki' );
		echo "<html><body>
<h1>$title</h1>
<p>$msg</p>
</body></html>
";
	}

	function serve_file_raw( $filename, $filepath, $attachment=true ) {
		global $wgOut;
		$wgOut->disable();
		wwfStreamFile($filepath, array(), null, $attachment);
	}

	function serve_file_raw_from_contents( $filename, $contents, $attachment ) {
		global $wgOut;
		$wgOut->disable();
		wwfStreamFile($filename, array(), $contents, $attachment);
	}

	function list_directory( $files, $project, $dirname ) {
		global $wgOut, $wgUser, $wgRequest, $wwContext;

		# set up output page
		$this->setHeaders();
		$wwContext->wwInterface->include_css('get-project-file',null); 
		$this->getOutput()->addModules( 'ext.workingwiki.listdirectory' );
		$this->getOutput()->addJsConfigVars(
		       	array(
				'wwCurrentDirectory' => $dirname,
			)
		);

		try {
			if ($project instanceOf ProjectDescription) {
				$wgOut->addHTML("\n<!-- projects: {$project->project_name()} -->\n");
				$pname = $project->project_name();
			} else {
				$project = null;
			}

			# allow preview/background code to intervene
			if ( wwRunHooks(
				'WW-ListDirectorySetup',
				array(
					&$wgRequest,
				       	$project,
					$dirname,
					&$dirpath,
					&$wdname,
					&$allowActions
				)
			) ) {
				if ($project !== null) {
					$wdname = $project->project_name();
				} else {
					$wdname = "Resources";
				}
				if ($dirname != '.') {
					$wdname .= " › $dirname";
				}
				$allowActions = ($project !== null and ! wwfReadOnly());
			}
			# escaping wdname not needed because wgOut will do it.
			# plus, the background extension puts html characters into this.
			$pagetitle = $wwContext->wwInterface->message('directorycontents', $wdname);
			$wgOut->setPageTitle( $pagetitle );

			$wgOut->addHTML(
				wwfSanitizeForSpecialPage( $wwContext->wwInterface->report_errors() )
		       	);

			$sync_ok = $wwContext->wwStorage->ok_to_sync_source_files();

			# header of action links
			if ($project !== null) {
				$wgOut->addHTML( '<p class="ww-mp-action-links">' );

				if ($project->has_source_files()) {
					$wgOut->addHTML(
						$wgUser->getSkin()->makeLinkObj(
							SpecialPage::getTitleFor( 'ManageProject' ),
							$wwContext->wwInterface->message( 'ww-export-sf' ),
							$wwContext->wwInterface->make_manage_project_query(
								$project,
								'ww-action=export-sf',
								false
							)
						)
				       	);
					$wgOut->addHTML( ' · ' );
				}

				$wgOut->addHTML(
					$wgUser->getSkin()->makeLinkObj(
						SpecialPage::getTitleFor( 'ManageProject' ),
						$wwContext->wwInterface->message( 'ww-export-wd' ),
						$wwContext->wwInterface->make_manage_project_query(
							$project,
							'ww-action=export-wd',
							false
						)
					)
				);

				$wgOut->addHTML( "</p>\n" );

				if ( $allowActions ) {
					$wgOut->addHTML( '<p class="ww-gpf-ls-action-links">' );
					$wgOut->addHTML(
						'<a href="'
						. $wwContext->wwInterface->get_project_file_base_url(
							$project,
							$dirname,
							false,
							null,
							'ww-action=clear-directory&ww-action-project='
							. htmlspecialchars( $project->project_name() )
					       	)
						. '" onClick="wwlink(event)">'
						. $wwContext->wwInterface->message( 'ww-cleardirectory' )
						. '</a>'
					);
					if ($sync_ok) {
						$wgOut->addHTML( ' · ' );
						$wgOut->addHTML(
							'<a href="'
							. $wwContext->wwInterface->get_project_file_base_url(
								$project,
								$dirname,
								false,
								null,
								'ww-action=sync-all&ww-action-project='
								. htmlspecialchars( $project->project_name() )
							)
							. '" onClick="wwlink(event)">'
							.	$wwContext->wwInterface->message( 'ww-sync-all' )
							. "</a>"
						);
					}

					$wgOut->addHTML( "</p>\n" );
				}
			}
			$wgOut->addHTML( wwfHtmlDirectoryListing( $files, $dirname, $project, $allowActions ) );
		} catch (WWException $ex) {
		}
		$wgOut->addHTML( wwfSanitizeForSpecialPage(
			$wwContext->wwInterface->report_errors()
	       	) );
		return;
	}

	function makeQuery($args, $project) {
		# works like WebQuery::appendQuery()
		#$args = array_merge($_GET,$args);
		global $wgRequest;
		#wwLog("makeQuery: ".serialize($args).", {$project->project_name()}\n");
		#$args = array_merge($wgRequest->getValues(),$args);
		#unset($args['title']);
		if (is_string($project)) {
			$args['project'] = $project;
		} else if ($project instanceOf ProjectDescription) {
			$args['project'] = $project->project_name();
		}
		if (!isset($args['project'])) {
			$x = $wgRequest->getVal('project');
			if ($x !== null) {
				$args['project'] = $x;
			}
		}
		if (!isset($args['filename'])) {
			$x = $wgRequest->getVal('filename');
			if ($x !== null) {
				$args['filename'] = $x;
			}
		}
		#wwLog("addToQuery: ".serialize($args)."\n");
		$query = '';
		foreach ($args as $k=>$v) {
			if (!is_array($v)) {
				$query .= '&'.urlencode($k).'='.urlencode($v);
			}
		}
		global $wgTitle;
		#wwLog("makeQuery before sanitize: ".$query."\n");
		$query = wwfSanitizeForSpecialPage($wgTitle->getLocalURL(substr($query,1)));
		#wwLog("makeQuery after sanitize: ".$query."\n");
		wwRunHooks('WW-GetProjectFileQuery', array(&$query));
		#wwLog("makeQuery after hook: ".$query."\n");
		# put the filename last
		$query = preg_replace('/(\?|&amp;)(filename=.*?)(&amp;)(.*)$/i','$1$4$3$2',$query);
		#wwLog("makeQuery after reorder: ".$query."\n");
		return $query;
	}

}

?>
