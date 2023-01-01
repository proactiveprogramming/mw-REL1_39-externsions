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

/* This is the plain-unix version of PEBackgroundJobs.
 * It runs jobs on the same computer as the web server, and manages them
 * itself.  For the Sun Grid Engine version (for e.g. a Beowulf cluster),
 * use PEBackgroundSGE.php instead.
 */

class PEBackgroundUnix extends PEBackgroundJobs
{ 
  /* figure out what background jobs exist and which are finished
   */
  function collect_jobs()
  { $backgroundJobs = array();
    global $peCacheDirectory;
    $backgroundjobsdir = $peCacheDirectory.'/background';
    if (!file_exists($backgroundjobsdir))
      return array();
    if (!is_dir($backgroundjobsdir))
      PEMessage::throw_error(htmlspecialchars($backgroundjobsdir)
        ." is not a directory!");
    if (!($dh = opendir($backgroundjobsdir)))
      PEMessage::throw_error("Can't read directory "
        . htmlspecialchars($backgroundjobsdir));
    while ( ($ent = readdir($dh)) !== false )
    { if ( $ent === '..' or $ent === '.' or
           !is_dir($backgroundjobsdir.'/'.$ent) )
        continue;
      $jobid = $ent;
      if ( ($status = $this->parseStatusFile($jobid)) === false )
        PEMessage::debug_message("failed to parse job statusfile for "
          .htmlspecialchars($jobid) . "\n");
      if (($pid = $status['pid']) !== false and $pid != ''
          and file_exists('/proc/'.trim($pid)))
        $status['running'] = true;
      $backgroundJobs[$jobid]['status'] = $status;
      $backgroundJobs[$jobid]['projects'] = 
        array_flip(explode(' ',$status['projects']));
      $backgroundJobs[$jobid]['focal-project'] =
      $status['focal-project'];
      $backgroundJobs[$jobid]['target'] = $status['target'];
    }
    #PEMessage::debug_message("Collected background jobs: ". 
    #        htmlspecialchars(serialize($backgroundJobs)));
    return $backgroundJobs;
  }

  function do_make_operation($request, $op_uri, $repos, &$result)
  { $target = $request['operation']['target'];
    $statusfile = $repos[$op_uri]->session->status_file_name();
    if (($sth = fopen($statusfile,"w")) === false)
      PEMessage::throw_error("Couldn't create status file");
    if (array_key_exists('project',$request['operation']))
      $focal = $request['operation']['project'];
    else if (array_key_exists('projects',$request))
    { reset($request['projects']);
      $focal = key($request['projects']);
    }
    else 
      $focal = '';
    if (fwrite($sth, "projects = \""
        . implode(' ',array_map(array('PEAPI','resolve_uri_synonyms'), array_keys($request['projects']))) . "\""
        #. implode(' ',array_keys($request['projects'])) . "\""
        . "\nfocal-project = \"" . $focal . "\""
        . "\nusername = \"" . $request['user-name'] . "\""
        . "\ntarget = \"" . $request['operation']['target'] . "\"\n" ) === false)
      PEMessage::throw_error("Couldn't write to status file");
    if (fclose($sth) === false)
      PEMessage::throw_error("Failure closing status file");
    $logfile = $repos[$op_uri]->session->make_log_path($target,$repos[$op_uri]->wd);
    if ((file_exists($logfile) || is_link($logfile)) && !unlink($logfile))
    { PEMessage::record_error( "couldn't remove ‘"
        . htmlspecialchars($logfile) . "’ before remaking");
    }
    $env =& $request['operation']['env'];
    if ($env === null) $env = array();
    $env['status-file'] = $statusfile;
    $env['time-limit'] = 0;
    $options = array();
    $make_command = $repos[$op_uri]->session->
      make_command($target, $repos, $op_uri, $request)
      . ' > ' . escapeshellarg( $logfile )
      . ' 2>&1 &';
    PEMessage::debug_message(htmlspecialchars($make_command));
    log_sse_message( "$make_command\n", $request );
    system($make_command, $make_success);
    if ($make_success != 0)
    { PEMessage::record_error( 'Failed to start background make process.' );
      return false;
    }
    # give it a chance to create status file before we make the
    # list of running jobs
    sleep(2); 
    return true;
  }

  function kill($jobid, $request, $report_errors=true)
  { $status = $this->parseStatusFile($jobid);
    if ( ($status = $this->parseStatusFile($jobid)) === false )
      PEMessage::debug_message("failed to parse job statusfile for $jobid\n");
    $pid = trim($status['pid']);
    if ($pid == '')
    { if ($report_errors)
        PEMessage::record_error("Can't identify process id for job "
                                   ."<code>$jobid</code>.");
      return false;
    }
    if (!file_exists("/proc/$pid"))
    { if ($report_errors)
        PEMessage::record_error('Job <code>'
          . htmlspecialchars($jobid) . '</code> (pid '
          . htmlspecialchars($pid). ') is not running.');
      return false;
    }
    $kill_command = "kill $pid";
    log_sse_command( "$kill_command\n", $request );
    PEMessage::debug_message($kill_command);
    $kill_output = shell_exec($kill_command);
    $this->expire_job_listing();
    return true;
  }
}

