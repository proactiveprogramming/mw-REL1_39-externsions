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
 * PERepositoryInterface
 *
 * ProjectEngine receives an HTTP request for a project file,
 * updates the project's cached working directory from stored project data,
 * updates the target file using make if requested, serves the up-to-data
 * file contents.
 *
 * PERepositoryInterface class updates project files from
 * stored data.  This is an abstract class that has specific instantiations
 * for Git, Subversion, WorkingWiki, etc.
 *
 * Note one of these objects is associated with a PESession
 * object.  To operate among multiple sessions you create multiple
 * RepositoryInterface objects.
*/

abstract class PERepositoryInterface
{
  var $location, $wd;
  var $session;
  // file handle for locking
  var $lock_data;

  # whether to log what we're doing
  static $lock_messages;

  # factory method, the only way to create one of these objects
  static $uri_lookup;
  static $classname_lookup;
  public static function factory( $uri, $session, $request )
  { $uri = PEAPI::resolve_uri_synonyms($uri);
    # Did I comment out the caching because you need one object per session?
    # Because that could be handled.  But was there another reason?
    $key = $session->directory() . ' '. $uri;
    if (!isset(self::$uri_lookup[$key]))
    { if (!isset(self::$classname_lookup))
        self::$classname_lookup = array(
          'pe-ww'          => 'PEWorkingWikiInterface',
          'pe-git'         => 'PEGitInterface',
          'pe-svn'         => 'PESubversionInterface',
          'pe-cvs'         => 'PECVSInterface',
          'pe-resources'   => 'PEResourcesDirectoryInterface',
	  'pe-session-dir' => 'PESessionDirectoryInterface',
	  'pe-project'     => 'PEBasicProjectDirectoryInterface',
	  'file'           => 'PEInPlaceDirectoryInterface',
      );
      @list($type,$location) = explode(':',$uri,2);
      if ( ! isset( self::$classname_lookup[$type] ) ) {
        PEMessage::throw_error("Unknown project type $type");
      }
      $classname = self::$classname_lookup[$type];
      global $peCodeDirectory;
      require_once("$peCodeDirectory/repositories/$classname.php");
      if ( ! $classname::OKToCreate( $uri, $request ) ) {
        PEMessage::throw_error( "Creation of $type project prohibited" );
      }
      $rep = new $classname($location,$session);
      self::$uri_lookup[$key] = $rep;
      $rep->create_wd( $request );
    }
    return self::$uri_lookup[$key];
  }

  public static function OKToCreate( $uri, &$request ) {
    return true;
  }

  public function __construct($location, $session)
  { $this->location = $location;
    $this->session = $session;
    $this->lock_data = false;
  }

  public function uri()
  { return $this->uri_scheme().':'.$this->location;
  }

  abstract public function uri_scheme();

  public function short_project_name( $request )
  { $uri = $this->uri();
    if ( isset( $request['projects'] ) and isset( $request['projects'][$uri] )
         and isset( $request['projects'][$uri]['short-dir'] ) )
    { return $request['projects'][$uri]['short-dir'];
    }
    return $uri;
  }

  public function need_to_create($session)
  { $dir_name = $this->cache_directory_name();
    $path = $session->directory() . '/' . $dir_name;
    return ! is_dir($path);
  }

  protected function create_wd( $request )
  { $this->wd = 
      new PEProjectDirectory($this->cache_directory_path());
    $wdname = $this->wd->directory_name();
    if (!is_dir($wdname))
    { clearstatcache();
      $ocd = $this->obsolete_cache_directory_paths();
      foreach ($ocd as $op)
      { if (is_dir($op))
        { PEMessage::debug_message("migrating " .htmlspecialchars($op)
            . " to " . htmlspecialchars($wdname));
          $parent_dir = dirname($wdname);
          if (!is_dir($parent_dir))
          { if (mkdir($parent_dir,0700,true) === false)
              PEMessage::record_error("could not create parent directory for "
                . htmlspecialchars($wdname));
          }
          if (rename($op, $wdname) === false)
            PEMessage::record_error("could not move " . htmlspecialchars($op)
              . " to " . htmlspecialchars($wdname));
          if (file_exists($this->lockfile_name_for_directory($op)) and
              unlink($this->lockfile_name_for_directory($op)) === false)
            PEMessage::debug_message("could not unlink lock file "
              . htmlspecialchars($this->lockfile_name_for_directory($op)));
          $olp = $this->obsolete_lockfile_names_for_directory($op);
          foreach ($olp as $ol)
          { if (file_exists($ol) and unlink($ol) === false)
              PEMessage::debug_message("could not unlink obsolete lock file "
                . htmlspecialchars($ol));
          }
        }
      }
      $this->sync_from_repo( $request );
    }
  }

  protected function lockfile_name_for_directory($path)
  { $base = $this->session->base_directory();
    if (strpos($path, $base) !== 0)
      PEMessage::throw_error("Internal error: Can't construct lockfile name"
        . " from " . htmlspecialchars($path) . ' and ' . htmlspecialchars($base));
    return $base 
      . '/.' . str_replace('/', ':', substr($path, strlen($base)+1)) . '.lock';
  }

  private function obsolete_lockfile_names_for_directory($path)
  { return 
      array(substr_replace($path, '.', strrpos( $path, '/' ) + 1, 0).'.lock');
  }

  private function lockfile_name()
  { return $this->lockfile_name_for_directory($this->wd->path);
  }

  private function pidfile_name()
  { return preg_replace('/.lock$/', '', $this->lockfile_name()) . '.pid';
  }

  public function lock_directory( $request )
  { #PEMessage::debug_message('lock '. $this->wd->path);
    if ($this->lock_data !== false)
    { log_sse_message( "Can't lock " . $this->session->session_type()
            . ' directory for ' . $this->short_project_name( $request )
            . " because I already have it locked.\n",
	    $request/*,
	    'error'*/
      );
      PEMessage::throw_error("Attempt to lock directory "
        . htmlspecialchars($this->wd->path) . " when it is already locked.");
    }
    $lockfile = $this->lockfile_name();
    $parent_dir = explode('/',$lockfile);
    array_pop($parent_dir);
    $parent_dir = implode('/',$parent_dir);
    if (!is_dir($parent_dir))
    { if (mkdir($parent_dir,0700,true) === false)
    { log_sse_message( 'Could not create directory before locking project '
            . $this->short_project_name( $request ) . ' ' . $this->session->session_type()
            . " directory.\n",
	    $request//,
	    //'error'
        );
        PEMessage::throw_error("Could not create directory before locking.");
      }
    }
    //PERepositoryInterface::$lock_messages = true;
    
    # open the lock file
    global $peUseFcntl;
    if ($peUseFcntl)
      $this->lock_data = dio_open($lockfile, O_RDWR|O_CREAT, 0600);
    else
      $this->lock_data = fopen($lockfile,"c+");
    if ($this->lock_data === false)
    { log_sse_message( 'Could not open lockfile to write lock project '
	    . $this->short_project_name( $request ) . ' ' . $this->session->session_type()
	    . " directory.\n",
	    $request//,
	    //'error'
      );
      PEMessage::throw_error('Could not open '
        .htmlspecialchars(var_export($lockfile,true)) . ' for write lock.');
    }

    $my_pid = getmypid();
    # now lock the file handle
    if (PERepositoryInterface::$lock_messages)
      peLog("Locking $lockfile");
    $starttime = microtime(true);
    if ($peUseFcntl)
    { # do a non-blocking attempt first, so we can log if it's locked already
      $pidfilename = $this->pidfile_name();
      if (dio_fcntl($this->lock_data, F_SETLK, Array('type'=>F_WRLCK)) !== 0)
      { if (file_exists($pidfilename))
          $owner_pid = file_get_contents($pidfilename);
        else
          $owner_pid = dio_read($this->lock_data, 1024);
        log_sse_message( 'Project ' . $this->short_project_name( $request ) . ' '
	      . $this->session->session_type() . ' directory is locked by process id '
	      . $owner_pid . ".  Waiting...\n",
	      $request
        );
        if (PERepositoryInterface::$lock_messages)
          peLog("$lockfile is locked by $owner_pid");
        # do blocking lock if it's still needed.
        if (($rval = dio_fcntl($this->lock_data, F_SETLKW, Array('type'=>F_WRLCK))) !== 0)
        { $this->lock_data = false;
	  log_sse_message( "Could not gain write lock.\n", $request/*, 'error'*/ );
          if (PERepositoryInterface::$lock_messages) {
		  peLog("dio_fcntl lock failed ($rval).");
		  #peLog("But so what!  Let's pretend it didn't!");
	  }
          PEMessage::throw_error("Could not write-lock "
            . htmlspecialchars($lockfile) . ".\n");
        }
        if (PERepositoryInterface::$lock_messages)
          peLog("Locked $lockfile after " 
            . ((microtime(true) - $starttime)*1000) . " microseconds");
      }
      else
      { if (PERepositoryInterface::$lock_messages)
          peLog("Locked $lockfile without waiting" );
      }
      log_sse_message( 'Locked project ' . $this->short_project_name( $request ) . ' '
      	. $this->session->session_type() . " directory.\n",
      	$request
      );
      dio_seek($this->lock_data, 0);
      if (!dio_truncate($this->lock_data, 0))
      { PEMessage::record_error("dio_truncate failed on "
          . htmlspecialchars($lockfile));
        log_sse_message( 'dio_truncate() failed on ' . $this->short_project_name( $request )
		. " lockfile.\n", $request/*, 'error'*/ );
      }
      $xhostname = gethostname() . ":$my_pid";
      if (dio_write($this->lock_data, $xhostname) != strlen($xhostname))
      { PEMessage::record_error("Couldn't write to lockfile "
          . htmlspecialchars($lockfile));
        log_sse_message( "Couldn't write process id to lockfile\n", $request/*, 'error'*/ );
      }
      $pidhandle = fopen($pidfilename, "w");
      if ($pidhandle === false)
      { log_sse_message( "Couldn't open process id file for " . $this->short_project_name( $request ) . "\n", $request/*, 'error'*/ );
        PEMessage::record_error("Couldn't open pid file for writing");
      }
      else
      { if (fwrite($pidhandle, $xhostname) === false)
        { log_sse_message( "Couldn't write process id to process id file for "
		. $this->short_project_name( $request ) . ".\n", $request/*, 'error'*/ );
          PEMessage::record_error("Couldn't write pid to file");
	}
        fclose($pidhandle);
      }
    }
    else
    { if (!flock($this->lock_data, LOCK_EX|LOCK_NB, $wouldblock))
      { if ($wouldblock)
        { $owner_pid = fgets($this->lock_data);
          if (PERepositoryInterface::$lock_messages)
            peLog("$lockfile is locked by $owner_pid");
          log_sse_message( 'Project ' . $this->short_project_name( $request ) . ' '
	      . $this->session->session_type() . ' directory is locked by process id '
	      . $owner_pid . ".  Waiting...\n",
	      $request
          );
        }
        if (!flock($this->lock_data, LOCK_EX))
        { fclose($this->lock_data);
          $this->lock_data = false;
          if (PERepositoryInterface::$lock_messages)
            peLog("flock failed.");
	  log_sse_message( "Could not gain write lock.\n", $request/*, 'error'*/ );
          PEMessage::throw_error("Could not write-lock " 
            . htmlspecialchars($lockfile). ".");
        }
        if (PERepositoryInterface::$lock_messages)
          peLog("Locked $lockfile after " 
            . ((microtime(true) - $starttime)*1000) . " microseconds");
      }
      else if (PERepositoryInterface::$lock_messages)
        peLog("Locked $lockfile without waiting");
      log_sse_message( 'Locked project ' . $this->short_project_name( $request ) . ' '
      	. $this->session->session_type() . " directory.\n",
      	$request
      );
      if (!rewind($this->lock_data))
      { PEMessage::record_error("Rewind failed on "
          . htmlspecialchars($lockfile));
        log_sse_message( "rewind() failed on lock file for " . $this->short_project_name( $request ) . ".\n",
	      $request/*,
	      'error'*/
        );
      }
      if (!ftruncate($this->lock_data, 0))
      { PEMessage::record_error("ftruncate failed on "
          . htmlspecialchars($lockfile));
        log_sse_message( "ftruncate() failed on lock file for " . $this->short_project_name( $request ) . ".\n",
	      $request/*,
	      'error'*/
        );
      }
      if (fwrite($this->lock_data, "$my_pid") === false)
      { PEMessage::record_error("Couldn't write to lockfile "
          . htmlspecialchars($lockfile));
        log_sse_message( "Couldn't write process id to lockfile.\n", $request/*, 'error'*/ );
      }
    }
    return true;
  }
  
  # Unlock the working directory
  public function unlock_directory($request, $delete_after = false)
  { #PEMessage::debug_message('unlock '. $this->wd->path);

    if ($this->lock_data === false)
    { log_sse_message( 'Cannot unlock project ' . $this->short_project_name( $request ) . ' '
        . $this->session->session_type()
        . " directory because I never locked it.\n",
        $request/*,
        'error'*/
      );
      PEMessage::throw_error("Internal error: Unlock requested when directory "
        . htmlspecialchars($this->path) . " is not locked.");
    }
    $my_pid = getmypid();
    if (PERepositoryInterface::$lock_messages)
      peLog("Unlock ".$this->lockfile_name());
    global $peUseFcntl;
    if ($peUseFcntl)
    { if (dio_fcntl($this->lock_data, F_SETLK, Array('type'=>F_WRLCK)) !== 0)
      { log_sse_message( 'Failed unlocking project.' );
        PEMessage::throw_error( 'Could not unlock '
          . htmlspecialchars($this->lockfile_name()) . '.');
      }
      dio_close($this->lock_data);
    }
    else
    { if (!flock($this->lock_data, LOCK_UN))
      { log_sse_message( 'Failed unlocking project.' );
        PEMessage::throw_error( 'Could not unlock '
          . htmlspecialchars($this->lockfile_name()) . '.');
      }
      if (!fclose($this->lock_data))
      { log_sse_message( 'fclose() failed on project ' . $this->short_project_name( $request ) . ' '
    	  . $this->session->session_type() . " directory during unlock.\n",
	  $request/*,
	  'error'*/
        );
        PEMessage::throw_error( 'Could not close '
          . htmlspecialchars($this->lockfile_name()) . '.');
      }
    }
    log_sse_message( "Unlocked project " . $this->short_project_name( $request ) . ' '
      . $this->session->session_type() . " directory.\n",
      $request
    );
    $this->lock_data = false;
    if ($delete_after and (unlink($this->lockfile_name()) === false))
    { log_sse_message( 'Unlink failed on project ' . $this->short_project_name( $request ) . ' '
    	. $this->session->session_type() . " directory's lock file after unlocking.\n",
	$request/*,
	'error'*/
      );
      PEMessage::record_error( 'Could not unlink lockfile' );
    }
  }

  public function files_are_modified( $request )
  { # If anything at all happens in the persistent project directory, expire 
    # the preview file.  (This could probably be a little less dire...)
    if ( $this->session instanceOf PEPersistentSession )
    { $pfilename = PEPersistentSession::preview_file_name($this->uri(),$request);
      # avoid race condition by unlinking file without using file_exists()
      # first
      //PEMessage::debug_message("Expiring .preview file for "
      //  . htmlspecialchars($this->uri()));
      @unlink($pfilename);
    }
  }

  public function cache_directory_path()
  { return $this->session->directory().'/'.$this->cache_directory_name();
  }

  public function sync_from_repo( $request )
  { if (!$this->session instanceOf PEPersistentSession)
      return;
    $this->sync_from_repo_internal( $request );
    $this->files_are_modified( $request );
  }

  public function cache_directory_name()
  { return PEAPI::uri_to_dir($this->uri());
  }

  # if we ever change the uri_to_dir() mapping, there might be stuff
  # in the old location.  If so, we can find it and migrate it to the 
  # new location.  This function should return an array of possible old
  # names.
  public function obsolete_cache_directory_names()
  { return PEAPI::uri_to_dir_obsolete($this->uri());
  }

  public function obsolete_cache_directory_paths()
  { $cdp = array();
    foreach ($this->obsolete_cache_directory_names() as $cdn)
    { $cdp[] = $this->session->directory() . '/' . $cdn;
    }
    return $cdp;
  }

  public abstract function sync_from_repo_internal( $request );
}
