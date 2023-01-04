<?php
/* ProjectEngine compute server
 * Copyright (C) 2010 Lee Worden <worden.lee@gmail.com>
 * http://lalashan.mcmaster.ca/theobio/projects/index.php/ProjectEngine
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
 * Operations
 *
 * Abstract base class and concrete subclasses for things PE does to its
 * project files - make a target, remove a file, etc.
 */

abstract class PEOperation {
	# given a request, figure out what operation object will be used and 
	# provide it.
	public static function create_operation($request) {
		$operation_name = $request['operation']['name'];
		if (!$operation_name) {
			PEMessage::record_error("Request doesn't include an operation name.");
			return true;
		}
		$operation_name = strtr($operation_name, '- ', '__');
		$class_name = 'PEOperation_'.$operation_name;
		if ( ! is_subclass_of($class_name, 'PEOperation') ) {
			PEMessage::throw_error( "'" . htmlspecialchars($operation_name)
				. "' operation not recognized.\n" );
		}
		return new $class_name;
	}

	# each subclass must provide _execute().
	# return value is true or false success code.
	# results are passed back by adding to $result.
	abstract protected function _execute($request, $op_uri, $repos, &$result);

	# the outside world calls this.	It does some useful things and calls
	# _execute().
	public function execute($request, $op_uri, $repos, &$result) {
		$op_is_readonly = $this->read_only_okay();
		if ( isset($request['peReadOnly']) and $request['peReadOnly'] 
		      and ! $op_is_readonly ) {
			PEMessage::record_error( "'" 
				. htmlspecialchars(str_replace('PEOperation_', '', get_class($this)))
				. "' operation is not valid for read-only directories." );
			return false;
		}
		if ($op_uri === null and $this->need_project_uri() ) {
			PEMessage::record_error( "Internal error: '" 
				. htmlspecialchars(str_replace('PEOperation_', '', get_class($this)))
				. "' operation requested without a valid project URI." );
			return false;
		}
		try {
			$retval = $this->_execute($request,$op_uri, $repos, $result);
		} catch ( PEAbortOutputException $ex ) {
			if ( ! $op_is_readonly and $op_uri !== null) {
				$repos[$op_uri]->files_are_modified( $request );
			}
			$result['succeeded'] = $ex->succeeded;
			throw $ex;
		} catch ( PEException $ex ) {
			$retval = false;
		}
		if ( ! $op_is_readonly and $op_uri !== null) {
			$repos[$op_uri]->files_are_modified( $request );
		}
		$result['succeeded'] = $retval;
		return $retval;
	}

	# operations that are okay for read-only directories should override
	# this in the obvious way.
	public function read_only_okay() {
		return false;
	}

	# operations that use the persistent session as well as the current session
	# need to say so, for locking purposes.
	public function need_persistent_session() {
		return false;
	}

	# some operations that don't operate on a particular project can turn this
	# off
	public function need_project_uri() {
		return true;
	}

	# merge and destroy are probably the only operations for which the 
	# other session's lockfiles should be deleted on unlock.
	public function delete_lockfiles_after() {
		return false;
	}
}

# we pass the make operation to the session object because a background
# session will do it differently.
class PEOperation_make extends PEOperation {
	protected function _execute($request, $op_uri, $repos, &$result) {
		return $repos[$op_uri]->session->do_make_operation(
			$request, $op_uri, $repos, $result);
	}
}

class PEOperation_query_time_limit extends PEOperation {
	protected function _execute($request, $op_uri, $repos, &$result) {
		global $peTimeLimitForMake;
		$result['time-limit'] = $peTimeLimitForMake;
		return true;
	}
	public function need_project_uri() {
		return false;
	}
}

class PEOperation_remove_and_remake extends PEOperation_make {
	protected function _execute($request, $op_uri, $repos, &$result) {
		$target = $request['operation']['target'];
		$repos[$op_uri]->wd->remove_file($target, $request);
		return parent::_execute($request,$op_uri, $repos, $result);
	}
}

class PEOperation_retrieve extends PEOperation {
	protected function _execute($request, $op_uri, $repos, &$result) {
		$target = $request['operation']['target'];
		$deref = ( isset($request['operation']['dereference-symlinks']) ?
			$request['operation']['dereference-symlinks'] : true );
		if ( isset($request['operation']['output-directly'])
		     and $request['operation']['output-directly']) {
			$ret = $repos[$op_uri]->wd->retrieve_file_contents($target, true, $deref);
			if ($ret[0] == 'p') {
				$headers = $request['operation']['http-headers'];
				foreach ($headers as $header) {
					header($header, false);
				}
				# TODO: the value here should be the local PE's URI
				header( "X-ProjectEngine: ProjectEngine" );
				# this construction is recommended in case size > 2GB
				$fsize = sprintf("%u", filesize($ret[1]));
				header("Content-Length: $fsize");
				$mtime = filemtime($ret[1]);
				if (array_key_exists('if-modified-since',$request['operation'])) {
					$if_since = $request['operation']['if-modified-since'];
					#peLog( 'if-modified-since: ' . $if_since );
					#peLog( 'mtime is ' . $mtime );
					if ($mtime <= $if_since) {
						header("HTTP/1.0 304 Not Modified");
						#peLog( 'return 304' );
						throw new PEAbortOutputException(true);
					}
				}
				header( 'Last-Modified: ' 
					. gmdate( 'D, d M Y H:i:s', $mtime ) . ' GMT' );
				# Don't use readfile!	It gets out of memory errors!
				#readfile($ret[1]);
				#TODO use file_get_contents in chunks - more efficient
				$fp = fopen($ret[1], 'r');
				if ($fp === false) {
					PEMessage::record_error('could not open file');
					return false;
				}
				#peLog("opened $ret[1]");
				while (!feof($fp)) {
					echo fgets($fp, 4096);
				}
				fclose($fp);
				# tell PE that we've got the output taken care of
				throw new PEAbortOutputException(true);
			}
		}
		$ret = $repos[$op_uri]->wd->retrieve_file_contents(
			$target,
			$request['pe-files-are-accessible'],
			$deref);
		if (is_null($ret)) {
			#PEMessage::record_error( 'file not found' );
			return false;
		}
		$result['target-file-contents'] = array($target => $ret);
		return true;
	}
	public function read_only_okay() {
		return true;
	}
}

class PEOperation_sync extends PEOperation {
	protected function _execute($request, $op_uri, $repos, &$result) {
		# actually PEAPI takes care of this
		return true;
	}
	# we just sync all projects that we're given source files for
	public function need_project_uri() {
		return false;
	}
}

class PEOperation_force_sync extends PEOperation_sync {}

class PEOperation_remove extends PEOperation {
	protected function _execute($request, $op_uri, $repos, &$result) {
		$target = $request['operation']['target'];
		$repos[$op_uri]->wd->remove_file($target, $request);
		return true;
	}
}

class PEOperation_clear_directory extends PEOperation {
	protected function _execute($request, $op_uri, $repos, &$result) {
		$del_self = (isset($request['operation']['delete-self']) ?
			$request['operation']['delete-self'] : false);
		$repos[$op_uri]->wd->clear_directory($request, $del_self);
		return true;
	}
}

class PEOperation_retrieve_archived_files extends PEOperation {
	// not actually necessary to do anything specialized, PEAPI will handle it
	protected function _execute($request, $op_uri, $repos, &$result) {
	}
}

class PEOperation_merge_session extends PEOperation {
	protected function _execute($request, $op_uri, $repos, &$result) {
	       	#PEMessage::debug_message( 'PEOperation_merge_session: '
		#	. json_encode( $request ) . ', ' . json_encode( $op_uri ) . ', '
		#	. implode( '|', array_keys($repos) ) );
		if (array_key_exists('preview',$request)) {
			$session = new PEPreviewSession($request['preview']);
		} else if (array_key_exists('background-job',$request)) {
			$session = new PEBackgroundSession($request['background-job']);
		} else {
			PEMessage::throw_error('Internal error: incomplete merge-session request');
		}
		$session->merge_into_persistent_session($request, $repos, $result);
		return true;
	}
	public function need_project_uri() {
	       	return false;
	}
	public function need_persistent_session() {
	       	return true;
	}
	public function delete_lockfiles_after() {
	       	return true;
	}
}

class PEOperation_list_background_jobs extends PEOperation {
	protected function _execute($request, $op_uri, $repos, &$result) {
	       	$bg = PEBackgroundJobs::instance();
		#peLog( 'request: ' . serialize($request) );
		$result['jobs'] = $bg->jobs_involving_projects(
			$request['operation']['filter'],
			isset($request['operation']['bypass-cache'])? $request['operation']['bypass-cache'] : false
		);
		return true;
	}
	public function read_only_okay() {
	       	return true;
	}
	public function need_project_uri() {
	       	return false;
	}
}

class PEOperation_kill_background_job extends PEOperation {
	protected function _execute($request, $op_uri, $repos, &$result) {
	       	if ( !( array_key_exists('operation',$request) and
		     array_key_exists('jobid',$request['operation']) ) ) {
			PEMessage::throw_error(
				'Internal error: jobid missing from kill-background-job request'
			);
		}
		$jobid = $request['operation']['jobid'];
		$success = PEBackgroundJobs::instance()->kill($jobid,$request,true);
		return $success;
	}
	public function need_project_uri() {
	       	return false;
	}
}

class PEOperation_destroy_background_job extends PEOperation {
	protected function _execute($request, $op_uri, $repos, &$result) {
	       	if ( ! ( array_key_exists('operation',$request) and
		     array_key_exists('jobid',$request['operation']) ) ) {
			PEMessage::throw_error(
				'Internal error: jobid missing from destroy-background-job request'
			);
		}
		$jobid = $request['operation']['jobid'];
		$success = PEBackgroundJobs::instance()->destroy($jobid,$request);
		return $success;
	}
	public function need_project_uri() {
	       	return false;
	}
}

class PEOperation_migrate_all_directories extends PEOperation {
	protected function _execute($request, $op_uri, $repos, &$result) {
		return true; 
	}
}

class PEOperation_export extends PEOperation {
       	var $tempdir, $tarname, $uri_to_dirname;
	function make_tempdir() {
	       	global $peTempDirectory;
		$tempname = gmstrftime("%Y-%m-%d-%H%M%S");
		$tempbase = 'WW_export_'.urlencode($tempname).'_'.mt_rand(0, 9999999);
		$this->tempdir = "$peTempDirectory/$tempbase";
		if ( file_exists($this->tempdir) or is_link($this->tempdir)) {
			PEError::throw_error("Couldn't create temp directory ...$tempbase");
		}
		if ( ! mkdir($this->tempdir, 0700) ) {
			PEError::throw_error("Couldn't make temp directory ...$tempbase");
		}
	}
	function internal_execute($request, $repos, $tar_args) {
	       	$filelist = join("\n",$tar_args);
		$listing_file = "$this->tempdir/files";
		if ( ($fp = fopen($listing_file,"w")) === false ) {
			PEError::throw_error("Couldn't open $listing_file "
				. " for writing."
			);
		}
		if (fwrite($fp, $filelist) == false) {
			PEError::throw_error("Couldn't write to $listing_file.");
		}
		fclose($fp);
		# @@ race condition = security bug in creating this file?
		# seems okay because you have to be apache to read/write in the 
		# temp directory
		$tgzname ="$this->tarname.tgz"; #"$this->tarname.tar.gz"; 
		$tgzfile = "$this->tempdir/$tgzname";
		$ionice_command = '';
		global $peIoniceClassForTar;
		if ($peIoniceClassForTar !== null) {
		       	global $peIonicePriorityForTar;
			$ionice_command = "ionice -c$peIoniceClassForTar "
				. ($peIonicePriorityForTar === null ? '' :
					 "-n$peIonicePriorityForTar ");
		}
		$tar_command = "tar -cvz -T '$listing_file'"
			. " -f '$tgzfile' --ignore-failed-read"
			. " > '$tgzfile.log' 2>&1";
		system($ionice_command.$tar_command,$tar_success);
		if ($tar_success != 0) {
		       	$tempbase = preg_replace('/^.*\//','',$this->tempdir);
			PEMessage::throw_error("Problem creating ...$tempbase/$tgzname");
		}
		# serve the tar.gz file directly without buffering it in memory
		if (ob_get_level()) {
			ob_end_clean();
		}
		header('Content-type: application/x-compressed-tar');
		header("Content-Disposition: attachment; filename=\"$tgzname\"");
		header('Cache-Control: s-maxage=0, must-revalidate, max-age=0');
		readfile($tgzfile);
		recursiveUnlink($this->tempdir, $request,true);
	}
	function wwdname() {
		return ".workingwiki";
	}
	function make_wwdir($request, $repos, $op_uri) {
	       	$wwpath = $this->tempdir.'/'.$this->wwdname();
		if (mkdir($wwpath, 0755) === false) {
			PEMessage::throw_error(
				"Couldn't create "
			       	. $this->wwdname() 
				. " subdirectory."
			);
		}
		# save the project description
		if (array_key_exists('project-description',$request)) {
		       	$pdfile = "$wwpath/project-description.xml";
			if (($write_file = fopen($pdfile,"w")) === false) {
				PEMessage::throw_error("Can't open project-description.xml for writing.");
			}
			# to do: where is this info now?
			if (fwrite($write_file,$request['peProjectDescription']) === false) {
				PEMessage::throw_error("Can't write to project description file.");
			}
			if (fclose($write_file) === false) {
				PEMessage::throw_error("Can't close project description file.");
			}
		}
		if ($request['operation']['use-default-makefiles']) {
		       	$mkfile = "$wwpath/makefile";
			if (($write_file = fopen($mkfile,"w")) === false) {
				PEMessage::throw_error("Can't open convenience makefile for writing.");
			}
			$mktext = '';
			$new_resources = '../' . self::wwdname() . "/resources";
			$mktext .= "export RESOURCES=" . $new_resources . "\n";
			$env = $request['operation']['env'];
			if (is_array($env)) {
				foreach ($env as $k=>$v) {
				       	#if ($k == 'RESOURCES')
					#	$v = $new_resources;
					$mktext .= "export $k=$v\n";
				}
			}
			foreach ($request['projects'] as $uri=>$projinfo) {
			       	$uri = PEAPI::resolve_uri_synonyms($uri);
				if (array_key_exists('varname',$projinfo)) {
					$mktext .= "export " . $projinfo['varname'] . "=../"
						. $this->uri_to_dirname[$uri] . "\n";
				} else {
					$mktext .= "export WW_THIS_DIR=../"
						. $this->uri_to_dirname[$uri] . "\n";
				}
			}
			global $peResourcesDirectory, $peMakefileBefore, $peMakefileAfter;
			$makefiles = $repos[$op_uri]->wd->makefiles();
			$makefiles = array_merge(
				array($peMakefileBefore),
				$makefiles,
				array($peMakefileAfter)
			);
			foreach($makefiles as $makefile) {
			       	if (substr($makefile,0,strlen($peResourcesDirectory))
				     == $peResourcesDirectory) {
					$makefile = $new_resources 
							. substr($makefile,strlen($peResourcesDirectory));
				}
				$mktext .= "include $makefile\n";
			}
			if (fwrite($write_file,$mktext) === false) {
				PEMessage::throw_error("Can't write to convenience makefile.");
			}
			if (fclose($write_file) === false) {
				PEMessage::throw_error("Can't close convenience makefile.");
			}
		}
		return $wwpath;
	}
	function save_pages($directory,$wwpath) {
	       	# save the wikitext of pages that include this project's files
		# FIXME: implement
		return true;
		global $wwContext;
		$project->ensure_directory_exists("$wwpath/pages", 0755);
		foreach ($project->pages_involving_project_files() as $pagename) {
		       	$title = Title::newFromText($pagename);
			$article = new Article($title);
			if ($article->getID() !== 0) {
			       	$pagefile = "$wwpath/pages/".urlencode($title->getPrefixedDBKey());
				PEMessage::debug_message("Write $pagefile\n");
				$pagetext = $article->getContent();
				# strip out the file contents in the tags, and 
				# replace project="this" by project=""
				#
				# this special syntax <source-file...></source-file> distinguishes
				# places where file contents go from empty tags, <source-file.../>.
				#
				# TO DO: only strip out tags belonging to this project, 
				# and, put the project name back when importing the page.
				#PEMessage::debug_message("original pagetext:\n---\n$pagetext\n---\n");
				$offset = 0;
				while (1) {
				       	list( $first, $last ) = $wwContext->wwInterface->find_element( 
						'source-file',
						array( 'project'=>$project->project_name() ),
						$pagetext,
						$offset
					);
					if ( $first === false ) {
						break;
					}
					$oldlen = strlen($pagetext);
					$pagetext =
					       	substr( $pagetext, 0, $first )
						. preg_replace(
							'/<source-file([^>]*)project=\s*".*?"([^>]*)>.*?<\/source-file>/is',
							'<source-file\1project=""\2></source-file>', 
							substr( $pagetext, $first, $last - $first + 1 )
						)
						. substr( $pagetext, $last+1 );
					$offset = $last + 1 + strlen($pagetext) - $oldlen;
				}
				$offset = 0;
				while (1) {
				       	list( $first, $last ) = $wwContext->wwInterface->find_element( 
						'project-file', array( 'project'=>$project->project_name() ),
						$pagetext, $offset );
					if ( $first === false ) {
						break;
					}
					$oldlen = strlen($pagetext);
					$pagetext = substr( $pagetext, 0, $first )
						. preg_replace(
							'/<project-file([^>]*)project=\s*".*?"([^>]*)>.*?<\/project-file>/is',
							'<project-file\1project=""\2></project-file>', 
							substr( $pagetext, $first, $last - $first + 1 ) )
						. substr( $pagetext, $last+1 );
					$offset = $last + 1 + strlen($pagetext) - $oldlen;
				}
				$proj_for_page =&
					$wwContext->wwInterface->find_project_given_page( $title->getPrefixedDBKey() );
				if ($proj_for_page !== null and
					$proj_for_page->project_name() == $project->project_name()) {
					$offset = 0;
					while (1) {
					       	list( $first, $last ) = $wwContext->wwInterface->find_element( 
							'source-file', array( 'project'=>null ),
							$pagetext, $offset );
						if ( $first === false ) {
							break;
						}
						$oldlen = strlen($pagetext);
						$pagetext = substr( $pagetext, 0, $first )
							. preg_replace(
								'/<source-file([^>]*)>.*?<\/source-file>/is',
								'<source-file\1></source-file>', 
								substr( $pagetext, $first, $last - $first + 1 ) )
							. substr( $pagetext, $last+1 );
						$offset = $last + 1 + strlen($pagetext) - $oldlen;
					}
					$offset = 0;
					while (1) {
					       	list( $first, $last ) = $wwContext->wwInterface->find_element( 
							'project-file', array( 'project'=>null ),
							$pagetext, $offset );
						if ( $first === false ) {
							break;
						}
						$oldlen = strlen($pagetext);
						$pagetext = substr( $pagetext, 0, $first )
							. preg_replace(
								'/<project-file([^>]*)>.*?<\/project-file>/is',
								'<project-file\1></project-file>', 
								substr( $pagetext, $first, $last - $first + 1 ) )
							. substr( $pagetext, $last+1 );
						$offset = $last + 1 + strlen($pagetext) - $oldlen;
					}
				}
				#PEMessage::debug_message("eviscerated pagetext:\n---\n$pagetext\n---\n");
				if ( !preg_match('/\S/', $pagetext) or
				    preg_match('/^\s*<(source|project)-file( (project|filename)=\"[^\"]*\")*?\s*\/?'
						.'>(\s*<\/(source|project)-file>)?\s*$/', $pagetext) or
				    preg_match('/^\s*Importing image file\s*$/', $pagetext)) {
					PEMessage::debug_message( "Nothing in page, skipping.\n" );
					continue;
				}
				if (($write_file = fopen($pagefile,"w")) === false) {
					$wwContext->wwInterface->throw_error("Can't open file ‘{$pagefile}’ for writing.");
				}
				if (fwrite($write_file,$pagetext) === false) {
					$wwContext->wwInterface->throw_error("Can't write to file ‘{$pagefile}’.");
				}
				if (fclose($write_file) === false) {
					$wwContext->wwInterface->throw_error("Can't close file ‘{$pagefile}’.");
				}
			}
		}
	}
	function _execute($request,$op_uri, $repos, &$result) {
	       	$this->tarname = "WorkingWiki-export";
		$projects = $request['projects'];
		# sort the projects by URI.	This guarantees that a subpage-project 
		# like "...A/B" comes after "...A", which seems to be what
		# is necessary for the tar filename transformations to work right.
		ksort($projects);
		# first, work out the short names for projects
		foreach ($projects as $uri=>$proj_info) {
		       	$uri = PEAPI::resolve_uri_synonyms($uri);
			$dname = $proj_info['short-dir'];
			# just to make sure
			$dname = str_replace('/','_',$dname);
			#if (!isset($this->tarname))
			#	$this->tarname = $dname;
			$this->uri_to_dirname[$uri]	= $dname;
			if (isset($dirname_count[$dname])) {
				++$dirname_count[$dname];
			} else {
				$dirname_count[$dname] = 1;
			}
		}
		foreach (array_reverse($this->uri_to_dirname) as $uri => $dname) {
			if ($dirname_count[$dname] > 1) {
			       	// this can't happen if $dname is the uri
				#$this->uri_to_dirname[$uri] = "$dname_{$dname_count[$dname]}";
				#--$dname_count[$dname];
				PEMessage::throw_error("Export encountered multiple projects named '"
					. htmlspecialchars($dname) . "' - please resolve this conflict and "
					. "retry.");
			}
		}
		try {
		       	$tempdir = $this->make_tempdir();
			$tar_args = array();
			if (!isset($this->tarname)) {
				PEMessage::throw_error("No projects found.");
			}
			foreach ($projects as $uri=>$proj_info) {
			       	$uri = PEAPI::resolve_uri_synonyms($uri);
				$lrd = preg_replace('/^\//','',$repos[$uri]->wd->path);
				$tar_args[] = "--transform=s|$lrd|{$this->tarname}/"
					. $this->uri_to_dirname[$uri] . '|';
				# note export_wd passes '' as a filename to get $directory->path."/"
				if ($request['operation']['source-files-only']) {
				       	if (array_key_exists('source-file-contents',$proj_info)) {
						foreach ($proj_info['source-file-contents'] as $fname=>$content) {
							$tar_args[] = $repos[$uri]->wd->path."/$fname";
						}
					}
				} else {
					$tar_args[] = $repos[$uri]->wd->path;
				}
			}
			if ($request['operation']['include-ww-directory']) {
			       	$wwpath = $this->make_wwdir($request,$repos,$op_uri);
				$wwdest = $this->tarname.'/'.$this->wwdname();
				$lrd = preg_replace('/^\//','',$wwpath);
				$tar_args[] = "--transform=s|$lrd|$wwdest|";
				$tar_args[] = $wwpath;
				self::save_pages($request,$wwpath);
				# tricky move to put resources/ into .workingwiki/
				global $peResourcesDirectory;
				$lrd = preg_replace('/^\//','',$peResourcesDirectory);
				$tar_args[] = "--transform=s|$lrd|$wwdest/resources|";
				$tar_args[] = $peResourcesDirectory;
			}
			$rval = $this->internal_execute($request, $repos, $tar_args);
		} catch (PEException $ex) {
		       	recursiveUnlink($this->tempdir, $request, true);
			throw new PEAbortOutputException(false);
		}
		# tell PE that we've got the output taken care of
		throw new PEAbortOutputException(true);
		#return $rval;
	}
	public function read_only_okay() {
	       	return true;
	}
}

class PEOperation_claim_log_key extends PEOperation {
	protected function _execute($request, $op_uri, $repos, &$result) {
		peLog( "claim_log_key" );
		if ( ( $key = sse_log_key( $request ) ) ) {
			$path = path_for_logfile( $key );
			// directory need to be created the first time
			$dirpath = dirname( $path );
			if ( ! file_exists( $dirpath ) ) {
				peLog( 'mkdir ' . dirname( $path ) );
				if ( ! mkdir( dirname( $path ), 0700, true ) ) {
					$result['claimed'] = false;
					return true;
				}
			}
			// attempt to open with exclusive-create option
			@$fp = fopen( $path, 'x' );
			// if succeeded, we are first to create the file.
			if ( $fp ) {
				$result['claimed'] = true;
				fclose( $fp );
			} else {
				$result['claimed'] = false;
			}
		}
		return true;
	}
	public function read_only_okay() {
		// do we need to allow this?  I.e. do we need to allow
		// logging when read-only?
	       	return false;
	}
	public function need_project_uri() {
	       	return false;
	}
}

class PEOperation_append_to_log extends PEOperation {
	protected function _execute($request, $op_uri, $repos, &$result) {
		$text = $request['operation']['text'];
		if ( isset( $request['operation']['event'] ) ) {
		       	$event = $request['operation']['event'];
		} else {
		       	$event = null;
		}
		log_sse_message( $text, $request, $event );
		return true;
	}
	public function read_only_okay() {
	       	return true;
	}
	public function need_project_uri() {
	       	return false;
	}
}

class PEOperation_sse_retrieve_log extends PEOperation {
       	var $retryd;
	protected function send_event( $text ) {
	       	if ( ! $this->retryd ) {
			#peLog( "retry: 70000" );
			echo "retry: 70000\n";
			$this->retryd = true;
		}
		#peLog( json_encode( $text ) );
		echo $text;
		@ob_flush();
		@ob_end_flush();
		flush();
	}

	protected function _execute($request, $op_uri, $repos, &$result) {
		ob_end_clean();

	       	$this->retryd = false;
		$key = $request['operation']['key'];
		$from = $request['operation']['from'];
		$lastId = isset( $_SERVER['HTTP_LAST_EVENT_ID'] ) ? 
			$_SERVER['HTTP_LAST_EVENT_ID'] : false;
		if ( $lastId and $lastId > $from ) {
			$from = $lastId;
		}
		header( 'Content-type: text/event-stream' );
		if ( isset( $request['operation']['http-headers'] ) ) {
			foreach ($request['operation']['http-headers'] as $header) {
				header($header, false);
			}
		}
		header( 'Cache-Control: no-cache, no-store, max-age=0, must-revalidate' );
		header( 'Pragma: no-cache' );
		# TODO: the value here should be the local PE's URI
		header( 'X-ProjectEngine: ProjectEngine' );
		 
		# send null event immediately, to get retry interval out there,
		# to forestall excessive reconnects
		$this->send_event( ":\n\n" );
		$logpath = path_for_logfile( $key );
		$counter = 300;
		# bufferstart, from, firstbreak, and nextbreak are all absolute
		# positions from the start of the file.
		$buffer = $bufferstart = null;
		$firstbreak = $nextbreak = null;
		$success = false;
		while ( --$counter > 0 and ! connection_aborted() ) {
			#peLog( $counter );
			if ( file_exists( $logpath ) ) {
				clearstatcache( true, $logpath );
				$stat = stat( $logpath );
				# if there's no more to read, and the writer has removed the
				# owner write permission bit, we're done.
				if ( $stat['size'] < $from and ! ( $stat['mode'] & 0000200 ) ) {
					# tell the client that we're done
					$this->send_event( "event: done\ndata:\n\n" );
					@ob_flush();
					@ob_end_flush();
					flush();
					# tell PE that we've got the output taken care of
					throw new PEAbortOutputException(true);
				}
				# otherwise, get at least one event
				if ( $buffer !== null and 
				     ( $from < $bufferstart or $bufferstart + strlen($buffer) < $from ) ) {
					$bufferstart = null;
				}
				if ( $bufferstart === null ) {
					$buffer = '';
					$nextbreak = false;
					if ( $from == 0 ) {
						$bufferstart = $firstbreak = 0;
					} else {
						$bufferstart = $from - 2;
						$firstbreak = false;
					}
				}
				while ( ( $firstbreak === false or $nextbreak === false )
					and $counter > 0 ) {
					if ( $firstbreak === false ) {
						$firstbreak = strpos( $buffer, "\n\n" );
						if ( $firstbreak !== false ) {
							$firstbreak += 2 + $bufferstart;
						}
					}
					if ( $firstbreak !== false ) {
						$nextbreak = strpos( $buffer, "\n\n", $firstbreak - $bufferstart );
						if ( $nextbreak !== false ) {
							$nextbreak += 2 + $bufferstart;
						}
					}
					if ( $nextbreak === false ) {
						$moretext = file_get_contents( $logpath, false, null, $bufferstart + strlen( $buffer ), 4096 );
						if ( $moretext === false ) {
							sleep(1);
							--$counter;
						}
						$buffer .= $moretext;
					}
				}
				# if we get here, we've got a complete event
				$text = substr(
					$buffer,
					$firstbreak - $bufferstart,
					$nextbreak - $firstbreak
				);
				# now $text is one event, with \n\n at end but not start.
				# (except there might be extra \n's at the start, which is ok.)
				#peLog( "text is from $firstbreak to $nextbreak: $text" );
				$this->send_event(
					"id: $nextbreak\n"
					. $text
				);
				@ob_flush();
				@ob_end_flush();
				flush();
				$buffer = substr( $buffer, $nextbreak - $bufferstart );
				$bufferstart = $firstbreak = $from = $nextbreak;
				$nextbreak = false;
			}
			if ( $buffer === false ) {
				#$this->send_event( "event: keepalive\ndata:\n\n" );
				sleep(1);
			}
		}
		# rather than run forever, just bail after a while and let the client
		# reconnect.  Protects against runaway processes.
		exit();
	}
	public function read_only_okay() {
	       	return true;
	}
	public function need_project_uri() {
	       	return false;
	}
}

if(0) {
class WWAction_export_wd extends WWAction_export_sf {
	static function execute(&$request) {
	       	self::not_implemented();
		global $wwContext;
		$lookup = WWAction::look_up_project($request->getText('project'));
		if ($lookup['status'] != WW_SUCCESS) {
			return $lookup;
		}
		$project =& $lookup['project'];
		$locked = $wwContext->wwInterface->lock_projects($project);
		try {
			$dname = $project->working_directory_name();
			$tar_args = array();
			$tar_args[] = $dname;
			# don't list this one, it gets included already
			/*$tar_args[] = */self::make_wwdir($project);
			$wwpath = $project->project_directory()."/".self::wwdname();
			self::save_pages($project,$wwpath);
			# tricky move to put resources/ into .workingwiki/
			global $wwResourcesDirectory;
			$lrd = preg_replace('/^\//','',$wwResourcesDirectory);
			$wwdir = "$dname/".self::wwdname();
			$tar_args[] = "--transform=s|$lrd|$wwdir/resources|";
			$tar_args[] = $wwResourcesDirectory;

			$rval = self::internal_execute($request,$project,$tar_args);
			wwfRecursiveUnlink($project->project_directory()."/".self::wwdname(),true);
		} catch (Exception $ex) {
		       	$wwContext->wwInterface->unlock_projects($locked);
			throw $ex;
		}
		$wwContext->wwInterface->unlock_projects($locked);
		return $rval;
	}
	public static function read_only_okay() {
	       	return true;
	}
}
}

class PEOperation_prune_directories extends PEOperation {
       	# go through the cache directories and prune away things that are old or
	# otherwise orphaned
	function _execute($request, $op_uri, $repos, &$result) {
	       	global $peCacheDirectory;
		return self::prune($peCacheDirectory, $request);
	}
	public function need_project_uri() {
	       	return false;
	}
	static function prune($dir, $request) {
	       	#PEMessage::debug_message("prune ".htmlspecialchars($dir));
		global $pePruneDirectoriesInterval, $peCacheDirectory;
		if ( $pePruneDirectoriesInterval == 0 ) {
			PEMessage::debug_message("skipping directory prune operation.");
			return true;
		}
		$now = time();
		if ($dir == $peCacheDirectory) {
		       	$last_fname = "$dir/.last-pruned";
			if (file_exists($last_fname)) {
			       	$last_time = filemtime($last_fname);
				if ($last_time === false) {
					PEMessage::debug_message(".last-pruned file exists but can't be statted.");
				}
				PEMessage::debug_message( "last time pruned was "
					. (int)(($now - $last_time)/(3600*24)) . " days ago\n" );
				if ($now - $last_time <= $pePruneDirectoriesInterval) {
				       	PEMessage::debug_message("Not time to prune again yet.");
					log_sse_message( "Skipping prune operation\n", $request );
					return true;
				}
				PEMessage::debug_message("Last prune was more than "
					. (int)$pePruneDirectoriesInterval 
					. " seconds ago - doing prune operation.");
			} else {
				PEMessage::debug_message(".last-pruned file not found");
			}
			# do the touch now, not after, even though we might fail, so that
			# concurrent processes won't also decide to prune stuff
			# TODO: make this actually atomic test-and-set
			if (touch($last_fname) === false) {
				PEMessage::debug_message("Can't touch last-cleaned file\n");
			}
		}
		PEMessage::debug_message("exploring:      $dir"); 
		# The cache directory contains session directories: 
		# persistent/, preview/*/, and background/*/.
		# We have several tasks:
		#	- remove old, abandoned preview sessions
		#	- remove old, abandoned projects in persistent session
		# Questions:
		#	- do we leave old background sessions forever?	What if they belong
		#		to projects that have been abandoned?
		#	- there will probably need to be a "DO NOT ERASE" interface for 
		#		projects that should be retained even if they're untouched.
		#	- should we remove things based on age?	Would it be better to use
		#		total cache size as the criterion?
		
		global $peExpirationTimeForPreviewDirectories;
		global $peExpirationTimeForProjectDirectories;
		if (!($dh = opendir($dir))) {
		       	PEMessage::debug_message("can't read:  	$dir");
			log_sse_message( "Error: can't read $dir\n", $request );
			return false;
		}
		$success = true;
		while ( ( $file = readdir( $dh ) ) !== false ) {
			if ( $file == '.' || $file == '..' || $file == '.last-cleaned') {
				continue;
			}
			$path = $dir . '/' . $file;
			# if the file has a DONOTERASE file, do not erase it!
			if ( substr_compare($file, '.DONOTERASE', -11) === 0 or 
			     file_exists("$path.DONOTERASE") ) {
				PEMessage::debug_message("DONOTERASE:    $path\n");
				log_sse_message( "Respect $path.DONOTERASE\n", $request );
				continue;
			}
			if( is_dir ( $path ) ) {
				if ( substr( $file, 0, 8 ) === 'preview_' or
				    substr( $file, 0, 9 ) === '_preview_' ) {
					# if it's a preview session directory with this kind of name, it's old
					PEMessage::debug_message("expire obsolete preview session: $path\n");
					recursiveUnlink( $path, $request, true );
				} else if ($file === 'preview') {
					# if it's the preview/ directory, go into it
				       	self::prune($path, $request);
				} else if (substr($dir, -7) === 'preview'
					 and $peExpirationTimeForPreviewDirectories > 0) {
					# if it's a current-style preview directory, see if it's old
					$lastmod = self::lastTimeAccessed( $path );
					#PEMessage::debug_message( htmlspecialchars($path)
					#	. " last accessed: $lastmod; now is $now");
					if ($lastmod > 0 and 
					    $now - $lastmod > $peExpirationTimeForPreviewDirectories) {
						PEMessage::debug_message("expired:          $path: "
									. (int)(($now - $lastmod) / (3600 * 24)) . " days old\n");
						recursiveUnlink($path,$request,true);
					} else {
						PEMessage::debug_message("not expired:	   $path: "
									. (int)(($now - $lastmod) / (3600 * 24)) . " days old\n");
					}
				}
				else if ( $file === 'persistent'
					 and $peExpirationTimeForProjectDirectories > 0) {
					# for the persistent directory, expire any abandoned projects.
					# what is a project? it's a directory that has a lockfile.
					# we traverse the persistent directory tree looking for projects.
					return self::prune_within_persistent($path, $request);
				} else { 
					# TO DO: delete abandoned background sessions.
					# for an unrecognized directory, ignore it.
					PEMessage::debug_message("no action:			 $path\n");
				}
			} else if (substr($file, -5) == '.lock'
				   and !is_dir($dir.'/'.substr($file, 1, strpos($file, ':', 1)-1))) {
				PEMessage::debug_message("orphan lockfile:	$path\n");
				log_sse_message( "Unlink $path\n", $request );
				unlink($path);
			} else {
				PEMessage::debug_message("no action:			 $path\n");
			}
		}
		return $success;
	}
	static function prune_within_persistent($dir, $request) {
	       	$success = true;
		$now = time();
		global $peCacheDirectory;
		$pers_dir = $peCacheDirectory . '/persistent';
		if (!($ph = opendir($dir))) {
		       	PEMessage::debug_message("can't read:  	   $dir");
			return false;
		}
		while ( ( $file = readdir( $ph ) ) !== false ) {
			if ( $file == '.' || $file == '..' ) {
				continue;
			}
			$path = $dir . '/' . $file;
			# if the file has a DONOTERASE file, do not erase it
			if (file_exists("$path.DONOTERASE") or 
			    substr_compare($file, '.DONOTERASE', -11) === 0) {
				PEMessage::debug_message("DONOTERASE:		$path\n");
				log_sse_message( "Respect $path.DONOTERASE\n", $request );
				continue;
			} else if ( is_dir( $path ) ) {
				# if it's a directory...
				# directories that have lockfiles are project directories,
				# see whether to expire it.
				#$lockpath = preg_replace('/\/([^\/]*)$/', '/.$1.lock', $path);
				$lockpath = $pers_dir . '/.' 
					. str_replace('/',':',substr($path, strlen($pers_dir)+1)) . '.lock';
				if (file_exists($lockpath)) {
					$lastmod = self::lastTimeAccessed( $path );
					PEMessage::debug_message( htmlspecialchars($path)
						. " last accessed: $lastmod (now = $now)");
					global $peExpirationTimeForProjectDirectories;
					if (($lastmod > 0) and
					    ($now - $lastmod > $peExpirationTimeForProjectDirectories)) {
						PEMessage::debug_message("expired:         $path: "
									. (int)(($now - $lastmod) / (3600 * 24)) . " days old\n");
						recursiveUnlink($path,$request,true);
						log_sse_message( "Unlink $lockpath\n", $request );
						unlink($lockpath);
					} else {
						PEMessage::debug_message("not expired:	   $path: "
							. (int)(($now - $lastmod) / (3600 * 24)) . " days old\n");
						log_sse_message( "Still in use: $path\n", $request );
					}
				} else {
					# if it doesn't have a lockfile, recurse into it looking for
					# directories that do.
				       	PEMessage::debug_message("did not find lockfile $lockpath\n");
					PEMessage::debug_message("recurse into:	$path\n");
					$success = $success and self::prune_within_persistent($path, $request);
				}
			} else if ( substr_compare( $file, '.lock', -5 ) === 0 ) {
				# if it's a lock file, does the directory for it exist?
				#$dirpath = preg_replace('/\/\.(.*?).lock$/', '/$1', $path );
				$dirpath = $pers_dir . '/' . str_replace(':','/', substr($file,1,-5));
				PEMessage::debug_message("is $path orphaned? checking $dirpath");
				if ( $dirpath === $path or !file_exists( $dirpath ) ) {
					PEMessage::debug_message("orphan lockfile: $path\n");
					log_sse_message( "Unlink $path\n", $request );
					unlink($path);
				} else {
					PEMessage::debug_message("ignore lockfile: $path\n");
				}
			} else if ( substr_compare( $file, '.merge.log', -10 ) === 0 ) {
				# remove merge logs, they only have short term interest
				PEMessage::debug_message("remove merge log: $path\n" );
				log_sse_message( "Unlink $path\n", $request );
				unlink($path);
			}
		}
		closedir( $ph );
		return $success;
	}
	# when's the last time something happened in this directory?
	# i.e. what's the biggest of all the modtimes + access times of its files.
	static function lastTimeAccessed($dir) {
	       	$st = stat($dir);
		if ($st === false) {
		       	PEMessage::record_error("Could not stat " . htmlspecialchars($dir));
			return time() + 1000;
		}
		#PEMessage::debug_message("stat " . htmlspecialchars($dir)
		#	. ": {$st['mtime']}, {$st['atime']}");
		$mrtime = max($st['mtime'], $st['atime']);
		if (!($dhl = opendir($dir))) {
		       	PEMessage::record_error("Could not read " . htmlspecialchars($dir));
			return time() + 1000;
		}
		while (	( $file = readdir( $dhl ) ) !== false ) {
			if ( $file == '.' or $file == '..' ) {
				continue;
			}
			$path = $dir . '/' . $file;
			if ( is_link( $path ) ) {
				# symbolic links are a hassle, their access times can't be
				# controlled, and we shouldn't descend into linked directories
				//PEMessage::debug_message("Do not stat link " . htmlspecialchars($path));
				continue;
			} else if ( is_dir ( $path ) ) {
				$mtime = self::lastTimeAccessed($path);
			} else {
				$fst = stat($path);
				if ($fst === false) {
				       	PEMessage::record_error("Could not stat " . htmlspecialchars($path));
					return time() + 1000;
				}
				$mtime = max($fst['mtime'], $fst['atime']);
				#PEMessage::debug_message("stat " . htmlspecialchars($path)
				#	. ": {$fst['mtime']}, {$fst['atime']}");
				# make sure we don't change the atime by looking
				if (!touch($path, $fst['mtime'], $fst['atime'])) {
					PEMessage::debug_message("Couldn't reset stat info for "
						. htmlspecialchars($path) );
				}
				# touch changes ctime for sure, so we don't check that.
			}
			if ($mrtime < $mtime) {
				$mrtime = $mtime;
			}
		}
		# Whoa!	We changed the atime by looking through the directory! 
		# put it back to what it was!
		if (!touch($dir, $st['mtime'], $st['atime'])) {
			PEMessage::debug_message("Couldn't reset stat info for "
				. htmlspecialchars($dir) );
		}
		#PEMessage::debug_message("Last accessed time for " . htmlspecialchars($dir)
		#	. " is " . $mrtime . "\n");
		return $mrtime;
	}

}

