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

/* PESession provides a sort of context for a given operation on a
 * project.
 *
 * Normally all operations take place in the "persistent session", in which
 * project directories are stored in $peCacheDirectory/persistent, and 
 * operations on the project files accumulate over days and months.
 *
 * Special sessions also exist though, for previewing and running background
 * jobs.  To do an operation in a special session, the relevant projects'
 * persistent directories are copied to a temporary location in a session
 * directory created for the purpose.  At the end of the session, the files
 * may be merged back into the persistent directory.
 *
 * In the future, we may also have "historical sessions" for time travelling
 * back to old revisions of a project.
 */

abstract class PESession
{ abstract public function directory();
  abstract public function is_persistent();
  abstract public function session_type();

  # in the case of special sessions, this tells PEAPI whether we are going
  # to create temporary working directories by copying, so that it will
  # lock the persistent directories to let us do it safely.
  public function need_to_create($repo)
  { return $repo->need_to_create($this);
  }

  public function initialize_working_directory($repo, $request)
  { #$dir_name = $repo->cache_directory_name();
    #$path = $this->directory() . '/' . $dir_name;
    $path = $repo->cache_directory_path_in_session( $this );
    if (!is_dir($path))
    { if (file_exists($path))
        PEMessage::throw_error("Directory " . htmlspecialchars($dir_name)
          . " exists but is not a directory!");
      if (mkdir($path,0700,true) === false )
          PEMessage::throw_error('Failed to create working directory '
            .htmlspecialchars($dir_name).'.');
      PEMessage::debug_message("Created working directory"
          .htmlspecialchars($dir_name).'.');
    }
    return true;
  }

  public function projects_in_directory()
  { return array();
  }

  # the make operation is the same in most all sessions, but not in 
  # background sessions.
  public function do_make_operation($request, $op_uri, $repos, &$result)
  { PROFILE_IN( __METHOD__ );
    $target = $request['operation']['target'];
    $logfile = $this->make_log_path($target,$repos[$op_uri]->wd);
    if ((file_exists($logfile) || is_link($logfile)) && !unlink($logfile))
      PEMessage::record_error( "couldn't remove ‘"
        . htmlspecialchars($logfile) . "’ before remaking");
    #$env = $request['operation']['env'];
    #if ($env === null) $env = false;
    $make_command =
      $this->make_command($target, $repos, $op_uri, $request);
    #peLog( "Make $target" );
    if ( ( $ssekey = sse_log_key( $request ) ) ) {
	    $make_command .= " 2>&1 | stdbuf -o L tee " . escapeshellarg( $logfile )
		    . " | perl -p -e '$|=1;' -e 's/^(.*?)$/data: $1\\ndata:\\n/' 2>&1 >> "
		    . escapeshellarg( path_for_logfile( $ssekey ) );
	    #log_sse_message( $make_command . "\n", $request );
    } else if ( isset( $request['log-to-stdout'] ) and $request['log-to-stdout'] ) {
	    # let it go to stdout and log file
	    $make_command .= " 2>&1 | stdbuf -o L tee "
		    . escapeshellarg( $logfile );
    } else if ( isset( $request['log-to-stderr'] ) ) {
	    $make_command .= " 2>&1 | stdbuf -o L tee "
		    . escapeshellarg( $logfile ) . ' 1>&2';
    } else {
	    $make_command .= ' >' . escapeshellarg($logfile) . ' 2>&1';
    }
    #peLog( "$make_command" );
    PROFILE_IN( 'PESession: make system call' );
    if ( isset( $request['summary-to-stderr'] ) ) {
	    fwrite( STDERR, "make $target... " );
	    fflush( STDERR );
	    //ob_flush();
	    //flush();
    }
    system($make_command, $make_success);
    PROFILE_OUT( 'PESession: make system call' );
    if ($make_success != 0)
    { #PEMessage::record_error( 'make failed' );
      if ( isset( $request['summary-to-stderr'] ) ) {
	    fwrite( STDERR, "failed.\n" );
	    fflush( STDERR );
      } else {
	      peLog( "failed to make " . $target );
      }
      $repos[$op_uri]->wd->unlink_file($target, $request);
      PROFILE_OUT( __METHOD__ );
      return false;
    }
    if ( isset( $request['summary-to-stderr'] ) ) {
	    fwrite( STDERR, "done.\n" );
	    fflush( STDERR );
    }
    $result['target-file-contents']
      = array($target => $repos[$op_uri]->wd->retrieve_file_contents($target,
                  $request['pe-files-are-accessible']));
    #peLog( "result target_file_contents: " . json_encode( $result['target-file-contents'] ) );
    PROFILE_OUT( __METHOD__ );
    return true;
  }
  public function env_for_make($target, $repos, $op_uri, $request)
  { $env = $request['operation']['env'];
    if (!is_array($env))
      $env = array();
    if (!array_key_exists('time-limit',$env))
    { global $peTimeLimitForMake;
      $timelimit = $peTimeLimitForMake;
      if ($timelimit > 0)
        $env['time-limit'] = $timelimit;
    }
    foreach ($request['projects'] as $uri=>$projinfo)
    { $uri = PEAPI::resolve_uri_synonyms($uri);
      if (isset($projinfo['varname']))
      { $env[$projinfo['varname']] = $repos[$uri]->wd->path;
      }
    }
    $env['WW_THIS_DIR'] = $repos[$op_uri]->wd->path;
    global $peResourcesDirectory, $peReportHostname, $peExecutablePath;
    if ($peReportHostname)
      $env['report-hostname'] = 1;
    $env['PATH'] = $peExecutablePath;
    $env['RESOURCES'] = $peResourcesDirectory;
    $env['FOREGROUND_ONLY'] = 1;
    return $env;
  }
  public function niceValueForMake()
  { global $peNiceValueForMake;
    return $peNiceValueForMake;
  }
  public function ioniceClassForMake()
  { return null;
  }
  public function ionicePriorityForMake()
  { return null;
  }
  public function make_command($target, $repos, $op_uri, $request)
  { $nice_value = $this->niceValueForMake();
    $nice_command = ($nice_value == 0 ? '' :
        "nice --adjustment=$nice_value ");
    $ionice_class = $this->ioniceClassForMake();
    if ($ionice_class !== null)
    { $ionice_priority = $this->ionicePriorityForMake();
      $ionice_command = "ionice -c$ionice_class " .
        ($ionice_priority === null ? '' : "-n$ionice_priority ");
    }
    else
      $ionice_command = '';
    $env_command = '';
    foreach ($this->env_for_make($target, $repos, $op_uri,  $request) as $var=>$val)
      $env_command .= escapeshellarg($var).'='.escapeshellarg($val).' ';
    if ($env_command)
      $env_command = 'env ' . $env_command;
    $make_command = $nice_command . $ionice_command . $env_command . 
      $this->basic_make_command($target,$repos[$op_uri]->wd,$request);
    return $make_command;
  }
  public function make_log_path($target,$wd)
  { return "$wd->path/{$target}.make.log";
  }
  public function basic_make_command($target,$wd,$request)
  { global $peCustomMake, $peMakefileBefore, $peMakefileAfter;
    $targetpath = "$wd->path/$target";
    if (0) {
      # this check is disabled because it stops users from making file
      # A a link to /etc/whatever and then asking WW to overwrite it
      # using <project-file filename=A make=true>; but it doesn't stop
      # them from, say, making B depend on A and asking WW to make B, or
      # just writing a rule for A that messes with /etc/whatever.  So
      # what's the point?
      if (!is_in_directory($targetpath,$wd->path))
        PEMessage::throw_error( "Make target '"
          . htmlspecialchars($target) . "' either is outside the working "
          . " directory or is a link to outside the working directory."
          . "  This is not allowed for security reasons." );
    }
    $project_makefiles = $wd->makefiles();
    $makefile_args = '';
    if (!isset($request['operation']['use-default-makefiles'])
        or $request['operation']['use-default-makefiles'])
    { $makefile_args = implode(" -f ", 
        array_map( 'escapeshellarg', $project_makefiles ) );
      if ( $makefile_args != '' )
        $makefile_args = ' -f ' . $makefile_args;
      $makefile_args = " -f " . escapeshellarg($peMakefileBefore)
        . $makefile_args . " -f " . escapeshellarg($peMakefileAfter);
    }
    $make_command = $peCustomMake
      . ' -C ' . escapeshellarg($wd->path)
      . $makefile_args . ' ' . escapeshellarg($target);
    return $make_command;
  }
}

class PEPersistentSession extends PESession
{ static public function persistent_session()
  { static $permsess;
    if ( ! $permsess instanceOf PEPersistentSession )
      $permsess = new PEPersistentSession;
    return $permsess;
  }

  public function base_directory()
  { global $peCacheDirectory;
    return $peCacheDirectory.'/persistent';
  }

  public function directory()
  { return $this->base_directory();
  }

  # the preview filename is always associated with the persistent session -
  # it keeps track of who is most recent to preview the project, because
  # the only time a preview session gets merged back to persistent files is
  # if there have been no intervening operations on the persistent files, 
  # including starting a newer preview session.
  public static function preview_file_name($uri, $request)
  { $psess = PEPersistentSession::persistent_session();
    $prepo = PERepositoryInterface::factory($uri,$psess,$request);
    $dpath = $prepo->cache_directory_path();
    return substr_replace($dpath, '.', strrpos( $dpath, '/' ) + 1, 0).'.preview';
  }

  public function is_persistent()
  { return true;
  }

  public function session_type()
  { return 'persistent';
  }

  # the merge log filename is also associated with the persistent session
  # because after merging we destroy the other session.
  public static function merge_log_filename($uri, $request)
  { $psess = PEPersistentSession::persistent_session();
    $prepo = PERepositoryInterface::factory($uri, $psess, $request);
    $dpath = $prepo->cache_directory_path();
    return substr_replace($dpath, '.', strrpos( $dpath, '/' ) + 1, 0).'.merge.log';
  }
}

# PEPreviewSession and PEBackgroundSession both derive from PESpecialSession.
abstract class PESpecialSession extends PESession
{ # unlike in the persistent session, we create each working directory
  # by copying all the current source and target files from the persistent
  # copy.  we trust that both directories are locked when this is called.
  # return true if we copied the directory, false if not.
  public function initialize_working_directory($repo, $request)
  { $dir_name = $repo->cache_directory_name();
    $path = $this->directory() . '/' . $dir_name;
    if (!is_dir($path))
    { if (file_exists($path))
        PEMessage::throw_error("Directory " . htmlspecialchars($dir_name)
          . " exists but is not a directory!");
      $psess = PEPersistentSession::persistent_session();
      $ppath = $psess->directory() . '/' . $dir_name;
      if (is_dir($ppath))
      { $dest_parent = dirname($path);
        if (!file_exists($dest_parent) and
            mkdir($dest_parent, 0700, true) === false)
          PEMessage::throw_error("Could not create parent directory for "
            . htmlspecialchars($repo->uri()));
        $cp = $this->ioniceCommandForCopy()
          . 'cp --recursive --preserve --verbose ' . escapeshellarg($ppath)
          . ' ' . escapeshellarg($path);
        log_sse_message(
	        'Creating '
	        . $this->session_type()
	        . ' copy of project '
		. $repo->short_project_name( $request )
		. " directory\n"
		. "$cp\n",
		$request
	);
        if ( ( $ssekey = sse_log_key( $request ) ) ) {
		$cp .= " 2>&1 | perl -p -e '$|=1;' -e 's/^(.*?)$/data: $1\\ndata:\\n/' 2>&1 >> "
			. escapeshellarg( path_for_logfile( $ssekey ) );
	} else if ( isset( $request['log-to-stdout'] ) ) {
		# let it go to stdout
	} else if ( isset( $request['log-to-stderr'] ) ) {
		$cp .= ' 1>&2';
	} else {
		$cplog = substr_replace($path, '.', strrpos( $path, '/' ) + 1, 0).'.create.log';
		#$cplog = $path.'.create.log';
		$cp .= ' >> '.escapeshellarg($cplog).' 2>&1';
	}
        system("echo $cp >$cplog", $cp_success);
        PEMessage::debug_message(htmlspecialchars($cp));
        #peLog("ProjectEngine: BEGIN recursive copy to $path");
        system($cp, $cp_success);
        if ($cp_success != 0)
        { #peLog("ProjectEngine: END recursive copy to $path (failed)");
          PEMessage::throw_error('Failed to copy working directory '
            .htmlspecialchars($ppath).' to temporary directory '
            .htmlspecialchars($path).'.');
        }
        #peLog("ProjectEngine: END recursive copy to $path (succeeded)");
        return true;
      }
      return parent::initialize_working_directory($repo,$request);
    }
    return false;
  }

  public function ioniceCommandForCopy()
  { global $peIoniceClassForSessionCopies;
    if ($peIoniceClassForSessionCopies !== null)
    { global $peIonicePriorityForSessionCopies;
      return "ionice -c$peIoniceClassForSessionCopies "
        . ($peIonicePriorityForSessionCopies === null ? '' :
           "-n$peIonicePriorityForSessionCopies ");
    }
    return '';
  }

  public function is_persistent()
  { return false;
  }

  function status_file_name()
  { return $this->directory().'/.status';
  }

  /* what's in the status file, as an associative array
   * WARNING: DON'T OUTPUT VALUES FROM THIS DATA TO THE USER!
   * This is a potential security leak, because parse_ini_file expands
   * PHP-defined constant values.  Code written in the wiki may be able
   * to modify the status file.  So if for instance, we had a constant
   * somewhere in the code (MW or WW) called DB_PASSWD, some wiki user
   * could write a makefile that puts a line like
   *   innocent_variable = DB_PASSWD
   * into the status file and then the database password would get
   * printed out to the wiki page!
   * Security-wise, it'd be better to use a different format, or at least
   * a different parsing routine.  The simple format is good when pe-make
   * adds lines to the status file.
   */
  static function parseStatusFile($statusfile)
  { if (!file_exists($statusfile))
      return false;
    return parse_ini_file($statusfile);
  }
  
  public function projects_in_directory()
  { $status = PESpecialSession::parseStatusFile($this->status_file_name());
    if (isset($status['projects']))
    { return array_map( 'PEAPI::resolve_uri_synonyms', 
                explode(' ',$status['projects']));
    }
    else
      return array();
  }

  # copy updated files back into the persistent working directories
  # (if appropriate).
  public function merge_into_persistent_session($request, $repos, &$result)
  { if (!is_dir($this->directory())) {
      # this was an exception, not so good when saving a page with comet
      PEMessage::record_error("Session directory does not exist.");
      return;
    }
    $uris = $this->projects_in_directory();

    #peLog( "request: " . json_encode($request) );
    #peLog( "URIs in dir: " . json_encode($uris) );
    #peLog( "repos: " . json_encode($repos) );

    # good grief!  merge from preview sends project names and they get 
    # locked by the PEAPI object, but merge from background doesn't and
    # we need to lock them here!
  # NOTE: trying leaving them out in Comet version of merge from preview as well
    $projects_in_request = array();
    if ( isset($request['projects']) ) {
	    foreach ( $request['projects'] as $k=>$v ) {
		    $projects_in_request[$k] = true;
	    }
    }
    if ( isset($request['operation']) and isset($request['operation']['project']) ) {
	    $projects_in_request[ $request['operation']['project'] ] = true;
    }
    unset( $projects_in_request[ 'pe-session-dir' ] );
    #peLog( 'projects_in_request == ' . json_encode( $projects_in_request ) );
    $lock_repos = (count( array_keys( $projects_in_request ) ) <= 0);
    #peLog( 'lock_repos == ' . $lock_repos);
    $psess = PEPersistentSession::persistent_session();
    if (is_array($repos))
      foreach ($repos as $uri=>$rep)
      { #peLog("For " . htmlspecialchars($uri) . ', ' . json_encode($rep->session));
      }
    foreach ($uris as $uri)
    { if (!$lock_repos)
      { $sp_repo = $repos[$uri];
        if (!$sp_repo)
          PEMessage::throw_error("Project " . htmlspecialchars($uri) 
            . " not locked.");
        if ( ! $sp_repo->session instanceOf PESpecialSession )
          PEMessage::throw_error("Wrong repo for project " 
            . htmlspecialchars($uri) );
      }
      $perm_repos[$uri] = PERepositoryInterface::factory($uri,$psess,$request);
      if ($lock_repos)
        $prev_repos[$uri] = PERepositoryInterface::factory($uri,$this,$request);
    }
    if ($lock_repos)
    { PEAPI::lock_repos($prev_repos, $request);
      PEAPI::lock_repos($perm_repos, $request);
    }

    $to_sess_dir = $psess->directory();
    $from_sess_dir = $this->directory();
    $success = true;
    foreach($uris as $uri)
    { if ( $this->okay_to_merge_project_to_persistent($uri,$request) )
      { if (is_array($repos) and isset($repos[$uri]))
          $from_repo = $repos[$uri];
        else if (isset($prev_repos[$uri]))
	  $from_repo = $prev_repos[$uri];
        else
          PEMessage::throw_error("Can't merge ". htmlspecialchars($uri));
        $from_proj_dir = $from_repo->cache_directory_path();
        if (is_dir($from_proj_dir))
        { $to_proj_dir = $perm_repos[$uri]->cache_directory_path();
          $cplog = PEPersistentSession::merge_log_filename($uri, $request);
          #peLog("ProjectEngine: BEGIN recursive merge from $from_proj_dir");
	  log_sse_message( "Merging project "
		  . $from_repo->short_project_name( $request )
		  . ' '
		  . $from_repo->session->session_type()
		  . " directory\n",
		  $request
	  );
          if (!$this->execute_merge($from_proj_dir, $to_proj_dir, $request))
          { PEMessage::record_error("Failed to merge from project " 
              . htmlspecialchars($uri) . ".");
            $success = false;
          }
          $perm_repos[$uri]->files_are_modified( $request );
        }
      }
    }

    if ($success)
    { #peLog("Remove session directory");
      try {
        recursiveUnlink($from_sess_dir, $request, true);
      } catch (PEException $ex)
      { // errors get reported, but they don't stop the show.
      }
      $result['projects'] = $uris;
    }

    if ($lock_repos)
    { PEAPI::unlock_repos($perm_repos, $request);
      PEAPI::unlock_repos($prev_repos, $request, true);
    }
  }

  # the core of the above
  public function execute_merge($from_dir, $to_dir, $request)
  { $success = true;
    $dest_dir = preg_replace('/\/[^\/]*$/','',$to_dir);
    
    $cp = $this->ioniceCommandForCopy()
      . 'cp --recursive --preserve --update --verbose '
      . escapeshellarg($from_dir) .' '. escapeshellarg($dest_dir);
    log_sse_message( "$cp\n", $request );
    if ( ( $ssekey = sse_log_key( $request ) ) ) {
      $cp .= " 2>&1 | perl -p -e '$|=1;' -e 's/^(.*?)$/data: $1\\ndata:\\n/' 2>&1 >> "
        . escapeshellarg( path_for_logfile( $ssekey ) );
    } else if ( isset( $request['log-to-stdout'] ) ) {
      # let output go
    } else if ( isset( $request['log-to-stderr'] ) ) {
      $cp .= ' 1>&2';
    } else {
      $cp .= ' > /dev/null 2>&1';
    }
    PEMessage::debug_message($cp);
    #peLog( "$cp" );
    system($cp,$cp_success);
    #peLog("ProjectEngine: END recursive merge from $from_proj_dir ("
    # . ($cp_success ? 'succeeded':'failed') . ")");
    if ($cp_success != 0)
      $success = false;
    return $success;
  }

  # this is go in general, but sometimes not after previewing.
  public function okay_to_merge_project_to_persistent($uri,$request)
  { return true;
  }
}

class PEPreviewSession extends PESpecialSession
{ var $key;

  static public function preview_session($key)
  { return new PEPreviewSession($key);
  }

  public function __construct($key)
  { $this->key = preg_replace('/_.*$/','',$key);
  }

  public function session_type()
  { return 'preview';
  }

  public function base_directory()
  { global $peCacheDirectory;
    return $peCacheDirectory."/preview";
  }

  public function directory()
  { return $this->base_directory().'/'.urlencode($this->key);
  }

  public function initialize_working_directory($repo, $request)
  { $path = $this->directory() . '/' . $repo->cache_directory_name();
    if (!is_dir($path))
    { if ( ! array_key_exists('okay-to-create-preview-session',$request) )
        PEMessage::throw_error("Can not perform operation: preview"
          . " session " . htmlspecialchars($this->key)
          . " does not exist.");

      $pfilename = PEPersistentSession::preview_file_name($repo->uri(), $request);
      # both the preview and persistent directory are locked when we get here
      if (($pfile = fopen($pfilename,"w")) === null)
        PEMessage::throw_error("Couldn't create .preview file for "
          . htmlspecialchars($repo->uri()));
      if (fwrite($pfile,$this->key) === false)
        PEMessage::throw_error("Couldn't write to .preview file for "
          . htmlspecialchars($repo->uri()));
      if (fclose($pfile) === false)
        PEMessage::throw_error("Couldn't close .preview file for "
          . htmlspecialchars($repo->uri()));
      PEMessage::debug_message("Created .preview file for "
        . htmlspecialchars($repo->uri()) . ': '
        . htmlspecialchars($this->key));

      parent::initialize_working_directory($repo,$request);
      PEMessage::record_message("Created preview directory for project '"
        . htmlspecialchars($repo->uri()) ."'.");

      $sfilename = $this->status_file_name();
      if (($sfile = fopen($sfilename,"w")) === null)
        PEMessage::throw_error("Couldn't create status file for preview session "
          . htmlspecialchars($this->key));

      if (fwrite($sfile,
              "projects = \"".implode(' ',array_keys($request['projects'])).'"')
            === false)
        PEMessage::throw_error("Couldn't write to status file for preview session "
          . htmlspecialchars($this->key));
      if (fclose($sfile) === false)
        PEMessage::throw_error("Couldn't close status file for preview session "
        . htmlspecialchars($this->key));
      #PEMessage::debug_message("Created status file for preview session "
      #  . htmlspecialchars($this->key));
    }
    return true;
  }

  public function okay_to_merge_project_to_persistent($uri,$request)
  { $pfilename = PEPersistentSession::preview_file_name($uri,$request);
    if (file_exists($pfilename))
    { $approved_key = file_get_contents($pfilename);
      $ok = ($approved_key == $this->key);
    }
    else
      $ok = true;
    if ( ! $ok )
    { PEMessage::record_message("Not merging project files from preview "
        . "directory, because of intervening operations on the project "
        . "directory.");
      return false;
    }
    PEMessage::record_message("Merging project files from preview directory.");
    return true;
  }
  
  # specialized quick merge
  # since merge from preview only happens when the persistent directory
  # hasn't been touched since the preview session was created, we don't
  # need to literally merge, just move the preview directory into the place
  # of the persistent one
  public function execute_merge($from_dir, $to_dir, $request)
  { $temp_name = "$to_dir.temporary.".getmypid();
    #peLog("Trying experimental merge from preview\n");
    log_sse_message( "Rename $to_dir --> $temp_name\n", $request );
    if ( ! rename($to_dir, $temp_name) )
    { PEMessage::record_error("Rename from " . htmlspecialchars($to_dir)
        . " to " . htmlspecialchars($temp_name) . " failed");
      return false;
    }
    log_sse_message( "Rename $from_dir --> $to_dir\n", $request );
    if (! rename($from_dir, $to_dir) )
    { PEMessage::record_error("Rename from " . htmlspecialchars($from_dir)
        . " to " . htmlspecialchars($to_dir) . " failed");
      return false;
    }
    recursiveUnlink($temp_name, $request, true);
    return true;
  }

}

