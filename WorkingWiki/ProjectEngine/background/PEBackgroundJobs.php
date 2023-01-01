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

/* This file provides common code for WWBackgroundUnix and WWBackgroundSGE
 */

/* PEBackgroundJobs is an abstract parent class.
 * Subclasses PEUnixBackgroundJobs and PESGEBackgroundJobs do
 * polymorphic things to get the nuts and bolts of background job
 * management done, so that we can use multiple frameworks for job
 * management, i.e. using plain UNIX background jobs on a single
 * server, and Sun Grid Engine scheduling on a cluster.
 * FIXME: merge r23 and r24 from WWBackground.
 */

class PEBackgroundJobs {

  /* this function provides a unique instance of the appropriate class, to
   * do the work.
   */
  static $_instance;
  static function instance()
  { if (!isset(PEBackgroundJobs::$_instance))
    { global $peBackgroundJobSystem;
      if ($peBackgroundJobSystem == 'Unix')
        $class = 'PEBackgroundUnix';
      else if ($peBackgroundJobSystem == 'SGE')
        $class = 'PEBackgroundSGE';
      else # this shouldn't happen, it won't be good
        $class = 'PEBackgroundJobs';
      #PEMessage::debug_message("Instantiate $class");
      PEBackgroundJobs::$_instance = new $class;
    }
    return PEBackgroundJobs::$_instance;
  }

  /* Given a list of projects, pick through the full list of
   * background jobs for the ones where any of these projects are
   * involved.  These are the jobs that will be reported on a page
   * where those projects are found.
   * This is properly a static function, but I need to implement it as
   * an instance function so it will be overridden by subclasses the
   * way I want.
   * Argument $projects is a list of projects' URIs.
   * Returns: a subset of all_jobs().
   * FIXME: need a Special:BackgroundJobs page to allow listing all
   * jobs, for system maintenance.  Trivial to implement, but an
   * irritating complication.  It should also support searching for
   * specific project names.
   */
  function jobs_involving_projects($projects, $bypass_cache=false)
  { if ( $bypass_cache )
      $this->expire_job_listing();
    $bj = $this->all_jobs();
    # check each job dir for projects relevant to the current page
    $result = array();
    if ( $bj === null )
      peLog( "\$bj is null" );
    foreach ($bj as $jobid=>$jobinfo)
    { foreach ($projects as $uri)
      { $uri = PEAPI::resolve_uri_synonyms($uri);
        if (array_key_exists($uri,$jobinfo['projects']))
        { #PEMessage::debug_message("$uri found in job $jobid\n");
          $result[$jobid] = $jobinfo;
          continue;
        }
        #else
        #  PEMessage::debug_message("$uri not found in job $jobid\n");
      }
    }
    return $result;
  }

  /* Provide a list structure summarizing all existing background jobs,
   * whether running or not.
   * Structure is:
   * { jobid => { projects => { ... }, status => { ... } }
   */
  function all_jobs()
  { global $wgMemc, $peBackgroundJobsCacheInterval;

    if ( $peBackgroundJobsCacheInterval == 0 )
      return $this->collect_jobs();

    $key = $this->listing_key();
    $data = $wgMemc->get( $key );
    #peLog( getmypid() . ' ' . $_SERVER['REQUEST_URI'] );
    #if ($data === false) peLog( getmypid() . ' data === false' );
    #peLog( 'memcache background-jobs data is ' . (($data === null) ? 'null' :
	#    ($data === 'generating' ? "'generating'" :
	#    ($data? 'something' : 'something false-ish'))) );

    if ( $data === false or $data === null ) //or $data === 'generating' )
    { #peLog( "Regenerate background jobs listing");
      // careful here - race conditions are possible.
      $data = 'generating';
      // this duration should be long enough to get the listing, even under
      // bad conditions, but not excessive because it can be very bad if
      // a process dies while generating
      if ( $wgMemc->add( $key, $data, 5 * 60 ) ) // 29 * 24 * 60 * 60 ) )
      { // if that atomic add succeeded, we know nobody else is in the 
        // process of regenerating the data, so we do it
        #peLog( "regenerating background jobs listing");
        $data = $this->collect_jobs();
        $wgMemc->set( $key, $data, $peBackgroundJobsCacheInterval - 1 /* in seconds */ );
	#peLog( 'done regenerating' );
      }
      else
      { #peLog( 'attempt to regenerate rejected by memcached.' );
      }
    }
    if ( $data === 'generating' )
    { $recheck_counter = 10;//3;//100;
      while ( $data === 'generating' and $recheck_counter > 0 )
      { sleep( 1 );
        #peLog( 'recheck for cached data' );
        $data = $wgMemc->get( $key );
	$recheck_counter--;
      }
      if ( $recheck_counter == 0 )
      { return array();
      }
      if ( $data === false or $data === null ) // let's be safe
      { #peLog( "from generating to false??? recursing" );
        return $this->all_jobs();
      }
    }
    #if ($data === false) peLog( getmypid() . ' data === false' ); 
    #else peLog( getmypid() . ' data is ' . (($data === null) ? 'null' :
	#    ($data === false) ? 'false' : 
	#    ($data? 'something' : 'something false-ish')) );
    #peLog( "Use data from cache");
    return $data;
  }

  function listing_key()
  { global $peBackgroundJobsCacheKey;
    if ( $peBackgroundJobsCacheKey !== false )
    { return $peBackgroundJobsCacheKey;
    }
    return wfMemcKey( 'workingwiki', 'backgroundjobs' );
  }
 
  /*
   * Operations that change the job listing should call this to expire
   * the cached listing data.
   */
  function expire_job_listing()
  { global $wgMemc;
    $key = $this->listing_key();
    peLog( 'expire job listing' );
    $wgMemc->delete( $key );
  }

  /* subclasses must implement: construct the data structure returned
   * by all_jobs().
   */
  function collect_jobs()
  { # this should never be reached in practice.  fake data for testing.
    return array(
    '1' => array( 'projects'=>array('pe-ww:http://localhost/wonder-wiki:Sandbox'=>1,
                                    'pe-ww:http://localhost/wonder-wiki:Handbox'=>1),
                    'status'=>array('running'=>1,'starttime'=>'1283547069',
                                    'username'=>'TJ') ),
      '2' => array( 'projects'=>array('pe-ww:http://localhost/wonder-wiki:Sandbox'=>1),
                    'status'=>array('succeeded'=>1,'endtime'=>'1283547069',
                                    'username'=>'Wonder') ) );
  }

  function jobname($jobid)
  { return $jobid;
  }

  /* WHY IS THIS DEFINED HERE AND IN PEBackgroundSession.php??? A BUG WAITING
   * TO HAPPEN FIXME */
  /* SEE ALSO destroy() */
  function dir_name($jobid)
  { return "background/$jobid";
  }

  /* override the file retrieval code in Special:GetProjectFile to
   * introduce "File not yet available"
  static function GetProjectFileHook(&$project, $filename)
  { return true;
  }
   */

  /* path to background directory, constructed from the job id
   */
  function path($jobid)
  { if ($jobid === null)
      PEMessage::throw_error("Where's the jobid?");
    global $peCacheDirectory;
    return $peCacheDirectory . '/' . $this->dir_name($jobid);
  }

  /* path to the background job's status file
   * THIS IS ALSO DEFINED IN THE SESSION CLASS, WHAT'S WRONG WITH ME?
   */
  function statusFile($jobid)
  { return $this->path($jobid).'/.status';
  }

  /* what projects are involved in a given job.
   * this is evaluated just by looking at all the directory names in
   * the job directory.
   */
  function projectsInJob($jobid)
  { $bgd = $this->path($jobid);
    if (!($dh = opendir($bgd)))
      PEMessage::throw_error("Can't read directory $bgd");
    $pnames = array();
    while ( ($ent = readdir($dh)) !== false )
    { if ($ent != '.' and $ent != '..' and is_dir("$bgd/$ent"))
        $pnames[$ent] = true;
    }
    ksort($pnames);
    return $pnames;
  }

  function destroy($jobid, $request)
  { # first make sure it's dead.
    $this->kill($jobid,$request,false);
    # pause, see if it's still running
    sleep(3);
    $jobs = $this->collect_jobs();
    if (isset( $jobs[$jobid] ) && isset($jobs[$jobid]['running']) && $jobs[$jobid]['running'])
      PEMessage::throw_error("Could not destroy job directory for <code>"
        . htmlspecialchars($jobid) . "</code>: job is not fully terminated.");
    $session = new PEBackgroundSession($jobid);
    $bgdir = $session->directory();
    PEMessage::debug_message("Unlink ".htmlspecialchars($bgdir));
    log_sse_message( "Recursively unlink directory $bgdir\n", $request );
    recursiveUnlink($bgdir, $request, true);
    $pattern = substr_replace($bgdir, '.', strrpos($bgdir,'/')+1, 0) . ':*.lock';
    PEMessage::debug_message("Glob: $pattern");
    foreach (glob($pattern) as $lockfile)
    { PEMessage::debug_message("Unlink $lockfile");
      log_sse_message( "Unlink $lockfile\n", $request );
      if (unlink($lockfile) === false)
        PEMessage::debug_message("Couldn't unlink lockfile " . htmlspecialchars(basename($lockfile)));
    }
    return true;
  }

  function kill($jobid, $request, $report_errors=true)
  { PEMessage::debug_message("Null kill-background-job operation");
    log_sse_request( 'Internal error: null kill-background-job operation', $request );
    return true;
  }

  function parseStatusFile($jobid=null)
  { $statusfile = $this->statusFile($jobid);
    return PESpecialSession::parseStatusFile($statusfile);
  }
}
