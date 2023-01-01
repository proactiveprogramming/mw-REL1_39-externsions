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

class WWBackground {	
	/* Get the list of background jobs, figure out which include
	 * projects relating to the current page, report them in order.
	 * $projectnames is a list of names of projects to be reported.
	 * $bypass_cache, if true, causes the list to be generated fresh.
	 */
	static function jobsMessage( $projectnames, $bypass_cache=false ) {
		global $wwContext;
	       	$output = '';
		$map_project_uri = $map_uri_project = array();
		#wwLog("jobsMessage - projectnames: ".serialize($projectnames)."\n");
		foreach ($projectnames as $projectname)
			try {
			       	#$project = $wwContext->wwStorage->find_project_by_name($projectname);
				#if ($project != null) {
				$uri = $wwContext->wwStorage->uri_for_project_name( $projectname );
				       	$map_project_uri[$projectname] = $uri;
					$map_uri_project[$uri] = $projectname;
				#}
			} catch (WWException $ex) {
			       	# if we're given a bad project name, we can assume it doesn't
				# correspond to any background jobs
			}
		$pe_result = ProjectEngineConnection::call_project_engine_lowlevel( array(
			'operation'=> array(
				'name'=>'list-background-jobs',
				'filter'=>array_values($map_project_uri),
				'bypass-cache'=>$bypass_cache
			)
		) );
		$jobs = isset($pe_result['jobs']) ? $pe_result['jobs'] : array();
		# now that we know which jobs to report, we sort and uniq them by project 
		# and by whether they're finished
		if ( ! is_array( $jobs ) ) {
			$jobs = array();
		}
		$runningjobs = $finishedjobs = $uri_keys = array();
		foreach ($jobs as $jobid=>$jobinfo) {
		       	$uris = array_keys($jobinfo['projects']);
			$firsturi = $uris[0];
			$uri_keys[$firsturi] = true;
			if ( isset( $jobinfo['status'] ) and is_array( $jobinfo['status'] )
				and ( ( isset( $jobinfo['status']['running'] )
					and $jobinfo['status']['running'] )
					or isset($jobinfo['status']['state'] ) ) ) {
				#wwLog("$jobid : running or something\n");
				$runningjobs[$firsturi][] = $jobid;
			} else {
				#wwLog("$jobid : finished\n");
				$finishedjobs[$firsturi][] = $jobid;
			}
		}
		# now, with jobs sorted by running/finished and by the alphabetically
		# first project name, report.
		$uri_keys = array_keys($uri_keys);
		#if (count($uri_keys) == 0)
		#	return '';
		sort($uri_keys);
		global $wgVersion;
		if ( version_compare( $wgVersion, '1.17', '<=' ) ) {
			wfLoadExtensionMessages('WorkingWiki');
		}
		foreach ($uri_keys as $projecturi) {
		       	#$projecturi = $map_project_uri[$pname];
			#$project = $wwContext->wwStorage->find_project_by_name($projecturi);
			#$pname = $project->project_name();
			$pname = $wwContext->wwStorage->project_name_for_uri($projecturi);
			#wwLog("look for $projecturi in ".serialize($runningjobs)."\n");
			if (array_key_exists($projecturi,$runningjobs)) {
				foreach ($runningjobs[$projecturi] as $jobid) {
				       	$username = $jobs[$jobid]['status']['username'];
					if ($username != '') {
					       	global $wgUser;
						$userlink = $wgUser->getSkin()->makeLinkObj(
							Title::makeTitle( NS_USER, $username ),
							htmlentities( $username )
						);
					} else {
						$userlink = "unknown";
					}
					$starttime = $jobs[$jobid]['status']['starttime'];
					global $wgLang;
					$starttimestr = $wgLang->timeanddate($starttime, true);
					$target = $jobs[$jobid]['target'];
					if ($target) {
						$target_msg = $wwContext->wwInterface->message('wwb-target',$target);
					} else {
						$target_msg = '';
					}
					if ($jobs[$jobid]['focal-project']) {
						try {
						       	$focal_project = ProjectDescription::normalized_project_name(
								$jobs[$jobid]['focal-project']
							);
						} catch (WWException $ex) {
							$focal_project = '';
						}
					}
					$project_list = '';
					#wwLog("make project_list from ".serialize($jobs[$jobid]['projects'])."\n");
					foreach($jobs[$jobid]['projects'] as $puri=>$t) {
						$project_list .= ', '
							. $wwContext->wwInterface->make_manage_project_link(
								$wwContext->wwStorage->find_project_by_name($puri)
							);
					}
					$project_list = substr($project_list,2);
					if (isset($jobs[$jobid]['status']['state']))
						$state = wfMessage( 'wwb-job-state-'.$jobs[$jobid]['status']['state'] )->parse();
					else
						$state = 'running';
					$output .= '<li>'.$wwContext->wwInterface->message('wwb-job-is-running',
						$jobid, $userlink, $target_msg, $project_list, 
						ucfirst($state), $starttimestr) . "</li>\n";
					global $wgScript;
					if (/*$succeeded !== true and */$target)
						$output .= "<span class='ww-altlinks'>[<a "
							. "href='$wgScript/Special:GetProjectFile?jobid="
							. urlencode($jobid) . '&amp;project='
							. urlencode($focal_project) . '&amp;make=no&amp;filename='
							. urlencode($target) . ".make.log'>log</a>]</span>";
					$output .= '<li class="wwb-action-line">'
						. $wwContext->wwInterface->message('wwb-actions-line-start',$jobid)
						. wwfSanitizeForSpecialPage(
								$wwContext->wwInterface->make_manage_project_link($pname,
									$wwContext->wwInterface->message('wwb-kill-link',$jobid), 
									"ww-action=kill-background-job&ww-action-jobid=".urlencode($jobid),
									false, false, array(),
									array( 'onClick' => 'wwlink(event)')))
						. $wwContext->wwInterface->message('wwb-actions-line-middle',$jobid)
						. "<a href='$wgScript/Special:GetProjectFile?jobid="
								. urlencode($jobid) . '&amp;project=' 
								. urlencode($focal_project) . '&amp;make=no&amp;filename=.\'>'
								. $wwContext->wwInterface->message('wwb-browse-link',$jobid) . '</a>'
						. $wwContext->wwInterface->message('wwb-actions-line-end',$jobid)
						."</li>\n";
				}
			}
		}
		foreach ($uri_keys as $projecturi) {
		       	#$projecturi = $map_project_uri[$pname];
			$project = $wwContext->wwStorage->find_project_by_name($projecturi);
			$pname = $project->project_name();
			#wwLog("look for $projecturi in ".serialize($finishedjobs)."\n");
			if (array_key_exists($projecturi,$finishedjobs))
				foreach ($finishedjobs[$projecturi] as $jobid) {
				       	$succeeded = isset($jobs[$jobid]['status']['succeeded']) ?
						($jobs[$jobid]['status']['succeeded'] != 0) : null;
					@$username = $jobs[$jobid]['status']['username'];
					global $wgUser;
					if ($username != '') {
					       	global $wgUser;
						$userlink = $wgUser->getSkin()->makeLinkObj(
							Title::makeTitle( NS_USER, $username ), htmlentities($username));
					} else {
						$userlink = "unknown";
					}
					@$endtime = $jobs[$jobid]['status']['endtime'];
					@$target = $jobs[$jobid]['target'];
					if (isset($jobs[$jobid]['focal-project']))
						try
						{ $focal_project = ProjectDescription::normalized_project_name(
									$jobs[$jobid]['focal-project']);
						} catch (WWException $ex) {
							$wwContext->wwInterface->debug_message("; "
								. htmlspecialchars(serialize($jobs[$jobid])));
							$focal_project = '';
						}
					if ($target) {
						if ($succeeded) {
						       	$targetlink = '<a href=\''
								. $wwContext->wwInterface->get_project_file_base_url(
									$focal_project,
									$target,
									/*make*//*true*/false,
									/*display*/null, 
									"jobid=".urlencode($jobid) )
								. '\'>' . htmlspecialchars($target) . '</a>';
							$target_msg = $wwContext->wwInterface->message('wwb-target',$targetlink);
						} else {
							$target_msg = $wwContext->wwInterface->message('wwb-target',$target);
						}
					} else {
						$target_msg = '';
					}
					$project_list = '';
					foreach($jobs[$jobid]['projects'] as $puri=>$t)
						$project_list .= ', '
							. $wwContext->wwInterface->make_manage_project_link(
								$wwContext->wwStorage->find_project_by_name($puri)
							);
					$project_list = substr($project_list,2);
					$output .= '<li>';
					if ($endtime != '') {
					       	global $wgLang;
						$endtimestr = $wgLang->timeanddate($endtime, true);
						#wwLog("Converted time $endtime to $endtimestr.\n");
						if ($succeeded)
							$output .= $wwContext->wwInterface->message('wwb-job-succeeded-time',
									$jobid,	$userlink, $target_msg,$project_list, $endtimestr) . "</li>\n";
						else
							$output .= $wwContext->wwInterface->message('wwb-job-failed-time',
								$jobid, $userlink, $target_msg, $project_list, $endtimestr) . "</li>\n";
					} else {
					       	if ($succeeded === true)
							$output .= $wwContext->wwInterface->message('wwb-job-succeeded',
								$jobid, $userlink, $target_msg, $project_list) . "</li>\n";
						else if ($succeeded === false)
							$output .= $wwContext->wwInterface->message('wwb-job-failed',
								$jobid, $userlink, $target_msg, $project_list) . "</li>\n";
						else
							$output .= $wwContext->wwInterface->message('wwb-job-status-unknown',
								$jobid, $target_msg, $project_list) . "</li>\n";
					}
					$output .= '<li class="wwb-action-line" data-jobid="'.urlencode($jobid).'">';
					global $wgScript;
					if (/*$succeeded !== true and */$target)
						$output .= "<span class='ww-altlinks'>[<a "
							. "href='$wgScript/Special:GetProjectFile?jobid="
							. urlencode($jobid) . '&amp;project='
							. urlencode($focal_project) . '&amp;make=no&amp;filename='
							. urlencode($target) . ".make.log'>log</a>]</span>";
					$output .= $wwContext->wwInterface->message('wwb-actions-line-start',$jobid)
						. "<a href='$wgScript/Special:GetProjectFile?jobid="
							. urlencode($jobid) . '&amp;project='
							. urlencode($focal_project) . '&amp;make=no&amp;filename=.\'>'
							. $wwContext->wwInterface->message('wwb-browse-link',$jobid) . '</a>'
						. $wwContext->wwInterface->message('wwb-actions-line-middle',$jobid)
						. wwfSanitizeForSpecialPage(
								$wwContext->wwInterface->make_manage_project_link($pname,
								$wwContext->wwInterface->message('wwb-destroy-link',$jobid), 
								"ww-action=destroy-background-job&ww-action-jobid=".urlencode($jobid), 
								false, false, array('ww-destroy-background-job'),
								array( 'onClick' => 'wwlink(event)' )))
						. $wwContext->wwInterface->message('wwb-actions-line-middle',$jobid)
						. wwfSanitizeForSpecialPage(
								$wwContext->wwInterface->make_manage_project_link($pname,
								$wwContext->wwInterface->message('wwb-merge-link',$jobid), 
								"ww-action=merge-background-job&ww-action-jobid=".urlencode($jobid),
								false, false, array('ww-merge-background-job'),
								array( 'onClick' => 'wwlink(event)' )))
						. $wwContext->wwInterface->message('wwb-actions-line-middle',$jobid)
						. wwfSanitizeForSpecialPage(
								$wwContext->wwInterface->make_manage_project_link($pname,
								$wwContext->wwInterface->message('wwb-retry-link',$jobid), 
								"ww-action=create-background-job&ww-action-project="
									. urlencode($focal_project)
									. "&ww-action-filename="
									. urlencode($target),
								false, false, array('ww-create-background-job'),
								array( 'onClick' => 'wwlink(event)' )))
						. $wwContext->wwInterface->message('wwb-actions-line-end',$jobid)
						."</li>\n";
				}
		}
		if ($output != '') {
		       	global $wgLang;
			$output .= '<span class="wwb-datestamp">(' 
				. $wgLang->timeAndDate(wfTimestampNow(), true) .")</span>\n";
		}
		$output = '<ul class="wwb-jobs-message" id="wwb-jobs-message">'.$output
			. "</ul>\n";
		#$ex = new Exception();
		#wwLog( "jobsMessage() backtrace:\n" . $ex->getTraceAsString() ."\n");
		return $output;
	}

	static $no_box_before_html;
	/* when this is called, the parser has been through the current wiki page
	 * and WW has recorded which projects are involved.	So now we insert
	 * a notice for any of those projects that is involved in any background jobs.
	 */
	static function OutputPageBeforeHTML_hook( &$out, &$text ) {
	       	#wwLog("WWBackground OutputPageBeforeHTML hook\n");
		global $wwContext, $wgUser;
		if ( wwfReadOnly() ) {
			return true;
		}
		$projs = $wwContext->wwInterface->get_projects_list_for_page();
		if ( count($projs) > 0
			and ! WWBackground::$no_box_before_html
			and wwRunHooks('WW-OKToInsertBackgroundJobsList', array())) {
			$jm = self::jobsMessage($projs); // ?? bypass_cache=true, maybe?
			#wwLog('jobs message: '.htmlspecialchars($jm) . "\n");
			if ($jm != '') {
			       	$text = $jm . $wwContext->wwInterface->report_errors() . $text;
				$wwContext->wwInterface->include_css('background',null);
				# if there's projects running, we need to update when the page
				# is reloaded, because they may have finished.	This might be
				# overkill - we may just need to resend the page, not reparse it.
				# TODO: how to invalidate the if-modified-since date, but leave 
				# it in parser cache.	Perhaps using OutputPageCheckLastModified hook?
				global $wgTitle;
				if ($wgTitle instanceOf Title and
				     $wgTitle->getNamespace() != NS_SPECIAL )
					$wgTitle->invalidateCache();
			}
		}
		#wwLog( 'wwProjectNames => '. implode( '|', $projs ) );
		#if ( method_exists( 'OutputPage', 'addJsConfigVars' ) )
		#{ $out->addJsConfigVars( array( 
	#			'wwProjectNames' => implode( '|', $projs ),
		#	) );
		#}
		if ( method_exists( $out, 'addModules' ) ) {
		       	$out->addModules( array(
				'ext.workingwiki.background',
				'ext.workingwiki.background.top',
			) );
		}
		$jobid = WWBackground::jobid_for_page();
		if ( $jobid !== null and method_exists( $out, 'addJsConfigVars' ) ) {
			$out->addJsConfigVars( array(
				'wwBackgroundJob' => $jobid,
			) );
		}
		return true;
	}

	/* find out once whether the page is in a background job context and
	 * keep track here */
	static $jobid;
	static function jobid_for_page() {
		if ( ! WWBackground::$jobid ) {
			global $wgRequest;
			WWBackground::$jobid =
				$wgRequest->getVal('jobid',
					$wgRequest->getVal( 'background-job', null ) );
		}
		return WWBackground::$jobid;
	}

	/* this is called from ManageProject and GetProjectFiles, to put list
	 * of running jobs related to the project at the top of the page.
	 */
	static function TopOfSpecialPage_hook( &$page, &$project, $filename ) {
	       	#wwLog("Special page hook\n");
		if ( wwfReadOnly() ) {
			return true;
		}
		if ($project == '')
			return true;
		if ( ! wwRunHooks('WW-OKToInsertBackgroundJobsList', array()) )
			return true;
		global $wwContext, $wgRequest;
		if ($wgRequest->getText('display')=='raw')
			return true;
		if ($project instanceOf ProjectDescription)
			$pname = $project->project_name();
		else
			$pname = $project;
		$output = self::jobsMessage(array($pname), /*bypass_cache*/true);
		$errhtml = $wwContext->wwInterface->report_errors();
		global $wgOut;
		if ($errhtml != '')
			$wgOut->addHTML( wwfSanitizeForSpecialPage($errhtml) );
		if ($output != '') {
		       	$wgOut->addHTML($output);
			$wwContext->wwInterface->include_css('background',null);
		}
		#$projs = $wwContext->wwInterface->get_projects_list_for_page();
		#wwLog( 'wwProjectNames => '. implode( '|', $projs ) );
		#if ( method_exists( 'OutputPage', 'addJsConfigVars' ) )
		#{ $page->getOutput()->addJsConfigVars( array( 
#	'wwProjectNames' => implode( '|', $projs ),
		#	) );
		#}
		if ( method_exists( 'OutputPage', 'addModules' ) ) {
		       	$page->getOutput()->addModules( array(
				'ext.workingwiki.background',
				'ext.workingwiki.background.top',
			) );
		}
		$jobid = WWBackground::jobid_for_page();
		if ( $jobid !== null and method_exists( 'OutputPage', 'addJsConfigVars' ) ) {
			$page->getOutput()->addJsConfigVars( array(
				'wwBackgroundJob' => $jobid,
			) );
		}
		WWBackground::$no_box_before_html = true;
		return true;
	}

	static function BackgroundMakeOK_hook() {
	       	global $wgRequest;
		$jobid = $wgRequest->getVal('jobid',null);
		if ($jobid !== null)
			return false;
		return true;
	}

	static function PERequest_hook(&$request) {
		if (WWBackground::jobid_for_page() === null)
			return true;
		if ( ! ProjectEngineConnection::session_applies_to_operation( $request['operation']['name'] ) ) {
			return true;
		}
		$request['background-job'] = WWBackground::jobid_for_page();
		if (!array_key_exists('projects',$request) and
			!array_key_exists('project',$request['operation'])) {
			$request['operation']['project'] = 'pe-session-dir'; 
		}
		return true;
	}

	# when listing a background job's files, alter the links so they
	# retrieve the background version of each file
	static function GetProjectFileQuery_hook(&$q) {
		if (WWBackground::jobid_for_page() !== null 
			and !preg_match('/\bresources=/',$q)
			and !preg_match('/\bjobid=/',$q)) {
			$q = str_replace('&filename=',
				'&jobid='.urlencode(WWBackground::jobid_for_page()).'&filename=',$q);
			$q = str_replace('&amp;filename=',
				'&amp;jobid='.urlencode(WWBackground::jobid_for_page()).'&amp;filename=',$q);
		}
		return true;
	}

	# the preview key gets passed through links to ManageProject too.
	static function MakeManageProjectQuery_hook(&$q, $readonly) {
		if (WWBackground::jobid_for_page() !== null 
			and !preg_match('/\bresources=/',$q)
			and !preg_match('/\bjobid=/',$q)) {
			$q .= "&jobid=".urlencode(WWBackground::jobid_for_page());
		}
		return true;
	}

	static function background_session_message() {
		global $wwContext;
		if (WWBackground::jobid_for_page() !== null)
			$wwContext->wwInterface->record_message("Displaying files within background job <tt>"
				. htmlspecialchars(WWBackground::jobid_for_page()) . "</tt>.");
		return $wwContext->wwInterface->report_errors();
	}

	# add text to top of Special:GetProjectFile page (and Special:ManageProject).
	static function GetProjectFile_Headers_hook() {
		global $wgOut;
		$wgOut->addHTML( self::background_session_message() );
		return true;
	}

	# add 'background make' to links offered with file
	static function GetProjectFile_altlinks_hook( 
		$project, $filename, &$specialpage, &$altlinks ) {
		if (!wwRunHooks('WW-BackgroundMakeOK',array()))
			return true;	# do it if NOT in a background job
		global $wwContext;
	        $filename = preg_replace( '/\.make\.log$/', '', $filename );
		$altlinks['background make'] = array(
			'html' => '<a href="' .
				$specialpage->makeQuery( array(
					'make'=>'no',
					'ww-action'=>'create-background-job',
					'ww-action-project'=>$wwContext->wwInterface->default_project_name,
					'ww-action-filename'=>$filename
				    ),
				    $project
				) .
				'" class="ww-background-make-link ww-write-action"' .
				'" onClick="wwlink(event)">background make</a>',
			'write' => true
		);
		return true;
	}

	# a GetProjectFile page request without a project argument is normally
	# a request for a filename from the resources directory.	But if it has
	# a jobid argument it's for a filename in the top-level background job 
	# directory instead.
	static function GetProjectFile_AssumeResourcesDirectory_hook( $request ) {
	       	if ($request->getVal('jobid', null) !== null)
			return false;
		return true;
	}

	# We allow people to make things in persistent and preview sessions, but
	# not in existing background session.s
	static function AllowMakeInSession_hook( &$request, &$message ) {
		if (WWBackground::jobid_for_page() !== null) {
		       	$message = "Make operations are not allowed in background jobs.";
			return false;
		}
		return true;
	}

	/* alter the behavior of the directory listing feature of GetProjectFile
	 * in the case that it receives a 'jobid' parameter - this requests a
	 * listing from a background job's directory.
	 */
	static function ListDirectorySetup_hook(&$request, $project,
		$dirname, &$dirpath, &$wdname, &$allowActions) {
		if (WWBackground::jobid_for_page() == '')
			return true;
		#wwLog("List directory hook going to work (background)\n");
		#$dirpath = WWBackgroundCommon::instance()->path(WWBackground::jobid_for_page());
		$wdname = '';
		if ($project instanceOf Project) {
		       	#$dirpath .= '/'.$project->working_directory_name();
			$wdname .= $project->project_name();
		}
		if (!is_null($dirname) and $dirname != '.') {
		       	$dirpath .= '/'.$dirname;
			if ($project instanceOf Project)
				$wdname .= ' › ';
			$wdname .= $dirname;
		}
		global $wgVersion;
		if (version_compare($wgVersion, '1.16', '>='))
			$wdname .= ' (background job <code>' . WWBackground::jobid_for_page()
			. '</code>) ';
		else
			$wdname .= ' (background job ' . WWBackground::jobid_for_page() . ') ';
		#wwLog(" ... $wdname\n");
		$allowActions = false;
		return false;
	}

	static function RenderProjectFile_hook($project, $source, $args, &$ret) {
		global $wwContext;
	       	if (!isset($args['make']) or $args['make'] != 'background') {
			return true;
		}
		if ( $project instanceOf ProjectDescription )
			$projectname = $project->project_name();
		else
			$projectname = $project;
		#wwLog("WWBackground taking over rendering of file {$args['filename']} "
		#	. "in project {$project->project_name()}\n");
		$linktext = (isset($args['linktext']) ? $args['linktext'] : $args['filename']);
		if (!wwRunHooks('WW-BackgroundMakeOK',array())) {
		       	$ret = htmlentities($linktext);
			return false;
		}
		$ret = $wwContext->wwInterface->make_manage_project_link( $project,
			htmlentities($linktext), 
			"ww-action=create-background-job&ww-action-project=" . urlencode($projectname)
		          . "&ww-action-filename=" . urlencode($args['filename']), 
			false, false, 'ww-background-make-link', 
			array( 'onClick' => 'wwlink(event)' ) );
		return false;
	}

	/* Add a "background make" button on the ManageProject page's "make" form
	 *
	 * NOTE: the value in the submit button can't be changed without changing
	 * the name of the WWAction class it calls on.	This is a knotty thing to
	 * fix, which is a problem because what about internationalization?
	 * See http://www.peterbe.com/plog/button-tag-in-IE.
	 * Because of bugs in IE6 and IE7, the right way to do these ww-action
	 * buttons is probably this:
	 * <button type="submit" name="ww-action" value="make"
	 *	 onClick="this.value='make'>Machen</button>
	 * <button type="submit" name="ww-action" value="background make"
	 *	 onClick="this.value='background make'>Machen in gebackengrounden
	 * </button>
	 * This won't work in IE6, or in IE7 with JavaScript disabled, but oh well.
	 */
	static function AddToMakeForm_hook(&$make_row) {
		global $wwContext;
	       	if (!wwRunHooks('WW-BackgroundMakeOK',array()))
			$disable = ' disabled="disabled"';
		else
			$disable = '';
		$add_txt = "<input type='submit' name='ww-action' value='background make'"
			. "$disable title='Make the target as a background job'"
			. " class='ww-background-make-button'"
			. " onClick='wwlink(event)'/>";
		# little hack here to make it work in wwlink()
		$add_txt .= '<input type="hidden" name="ww-action" value="create-background-job"/>';
		$make_row = preg_replace('{class=[\'"]ww-make-button[\'"]}i',
			"class='ww-make-button wwb-default-button'",$make_row);
		$make_row = preg_replace('{(</td>\s*</tr>\s*</table>\s*</form>\s*)$}i',
			"$add_txt\\1", $make_row);
		$wwContext->wwInterface->include_css('background',null);
		return true;
	}

  # when calling actions like remove-project-file within a background session
  # currently not used
  static function ApiCallArguments_hook( &$args )
  { return true;
  }

}
