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
 * This is the Sun Grid Engine version of PEBackgroundJobs (for a cluster).
 */

class PEBackgroundSGE extends PEBackgroundJobs
{ 
  /* figure out what background jobs exist and which are finished
   */
  function collect_jobs()
  { $this->backgroundJobs = array();
    #PEMessage::debug_message("in collectBackgroundJobs");
    global $peCacheDirectory;
    $wdp = $peCacheDirectory.'/background';
    #$wdp = explode('/',realpath($peCacheDirectory));
    #$wikiname = array_pop($wdp);
    #$wdp = implode('/',$wdp);
    if (!file_exists($wdp))
      return array();
    if (!is_dir($wdp))
      PEMessage::throw_error("$wdp is not a directory!");
    if (!($dh = opendir($wdp)))
      PEMessage::throw_error("Can't read directory $wdp");
    # record jobids of all job directories found
    while ( ($ent = readdir($dh)) !== false )
    { if ( $ent === '.' or $ent === '..' or !is_dir($wdp.'/'.$ent) )
        continue;
      #PEMessage::debug_message("consider directory $ent");
      $jobid = $ent;
      #PEMessage::debug_message("parse status file $jobid");
      if ( ($status = $this->parseStatusFile($jobid)) === false )
        $status = array();
      #$this->backgroundJobs[$jobid] = $status;
      if (is_array($status))
      { $this->backgroundJobs[$jobid]['projects'] = array();
        if (isset($status['projects']))
        { foreach ( explode(' ',$status['projects']) as $pname )
            $this->backgroundJobs[$jobid]['projects'][$pname] = true;
          unset( $status['projects'] );
	}
        $this->backgroundJobs[$jobid]['focal-project'] =
          isset($status['focal-project']) ? $status['focal-project'] : NULL;
	unset( $status['focal-project'] );
        $this->backgroundJobs[$jobid]['target'] = 
          isset($status['target']) ? $status['target'] : NULL;
	unset( $status['target'] );
      }
      $this->backgroundJobs[$jobid]['status'] = $status;
    }

    # now ask SGE about status of jobs
    //if (count($this->backgroundJobs) > 0)
    { # use qstat to list running and pending jobs
      $sge_command_prefix = '. /usr/local/sge/default/common/settings.sh ; ';
      # better to capture stderr in a separate file though
      $qstat_command = $sge_command_prefix . 'qstat -f -xml 2>&1';
      $qstat_xml = shell_exec($qstat_command);
      #$qstat_xml = file_get_contents('/tmp/qstat-f.xml');
      #peLog("read xml from qstat: ".htmlspecialchars($qstat_xml));
      # annoyingly, when there are no running jobs, we get invalid xml
      # which includes an "element" <>.
      # note, this was true with qstat -j, apparently not with qstat -f
      if (strpos($qstat_xml,'<>') === false)
        try
        { $qstat_xmldata = new SimpleXMLElement($qstat_xml);
          //PEMessage::record_error("qstat xml: "
          //  . htmlspecialchars(print_r($qstat_xmldata, true)));
          # select all job_list elements - some may be inside Queue-List
          # elements, some separately in a job_info element.
          foreach ($qstat_xmldata->xpath('//job_list') as $element)
          { #peLog("element: ".print_r($element,true)."\n");
            $ww_jobid = (string)$element->JB_name[0][0];
            #peLog("qstat lists job $ww_jobid\n");
            if (substr($ww_jobid,0,3) === 'WW_')
            { $jobid = substr($ww_jobid,3);
            }
            else
              $jobid = preg_replace('/.*WorkingWiki.job.(.*?)((,|%2C).*)?$/', '$1',
                $ww_jobid);
            if (isset($this->backgroundJobs[$jobid]))
            { #peLog("qstat match: $jobid\n");
              $this->backgroundJobs[$jobid]['jobnumber']
                = (string)$element->JB_job_number[0][0];
              $this->backgroundJobs[$jobid]['status']['starttime'] 
                = strtotime((string)$element->JAT_start_time[0][0]);
              $state = (string)$element['state'];
	      $statecode = (string)$element->state[0][0];
              #peLog("state = $state\n");
              $this->backgroundJobs[$jobid]['status']['state'] = $statecode;
              $this->backgroundJobs[$jobid]['status']['running'] = ($state == 'running');
            }
          }
        }
        catch (Exception $ex)
        { PEMessage::throw_error( nl2br(
	    "Error attempting to parse qstat output as xml: "
	    . htmlspecialchars($ex->getMessage())
	    . "\nqstat output is:\n" . htmlspecialchars($qstat_xml) ) );
        } 

      # use qacct to query finished jobs
      #$qacct_command = $sge_command_prefix . 'qacct -j \'WW_*\'';
      #peLog( 'Debugging background job list:' );
      $qacct_command = $sge_command_prefix . 'qacct -j';
      $qacct_output = shell_exec($qacct_command);
      #peLog( 'Output of qacct:\n' . $qacct_output );
      if (stristr($qacct_output, 'error') !== FALSE &&
	   strstr($qacct_output, 'jobname') === FALSE)
      { #peLog( 'bad output from qacct' );
	PEMessage::record_error( nl2br("Error output from qacct:\n"
       	  . htmlspecialchars($qacct_output) ) );
      }
      else if (preg_match_all('/===+.*?jobname.+?(WW_\S*|WorkingWiki.job.\d*).*?jobnumber\s+(\d+).*?end_time\s+(\S.*?)$.*?failed\s+(\d+).*?exit_status\s+(\d+)/ms', 
            $qacct_output, $matches, PREG_SET_ORDER)
          > 0)
      { foreach($matches as $match)
        { list($jobid, $jobnumber, $endtime, $failed, $exit_status) 
            = array($match[1],$match[2],$match[3],$match[4],$match[5]);
          $jobid = str_replace('WW_','', $jobid);
          #$jobid = str_replace('WorkingWiki job ','',$jobid);
          $jobid = preg_replace('/WorkingWiki.job./','',$jobid);
	  #peLog( "$jobid $jobnumber $endtime $failed $exit_status" );
          if (array_key_exists($jobid,$this->backgroundJobs))
	  { if (!isset($this->backgroundJobs[$jobid]['status']['succeeded']) or
		$failed or $exit_status != 0 )
            { $this->backgroundJobs[$jobid]['status']['succeeded'] 
                = ($failed == 0 and $exit_status == 0);
	    }
            $this->backgroundJobs[$jobid]['jobnumber'] = $jobnumber;
            $this->backgroundJobs[$jobid]['status']['endtime'] = strtotime($endtime);
          }
        }
      }
      else
      { #peLog( 'confused by qacct output' );
      }
    }
    #peLog( "Internal background jobs:\n". json_encode( $this->backgroundJobs ) );
    return $this->backgroundJobs;
  }

  function jobnumber_filename($session_dir)
  { return $session_dir."/.jobnumber";
  }

  function do_make_operation($request, $op_uri, $repos, &$result)
   /* ( &$project,
      $wwCustomMake, $evars, $wwNiceValueForMake, $working_dir_for_make,
      $makefile_args, $product, $logfile, &$make_success) */
  { $target = $request['operation']['target'];
    log_sse_message( "Creating status file for background job\n", $request );
    $statusfile = $repos[$op_uri]->session->status_file_name();
    #PEMessage::debug_message("Create status file: "
    #  . htmlspecialchars($statusfile));
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
        . "\ntarget = \"" . $request['operation']['target'] . "\"\n" ) === false) {
      PEMessage::throw_error("Couldn't write to status file");
      log_sse_message( "Error: couldn't write to status file.\n", $request );
    }

    log_sse_message( "Creating script for submission to cluster\n", $request );
    $jobdir = $repos[$op_uri]->session->directory();
    # create script to direct qsub
    $qsub_output = $this->jobnumber_filename($jobdir);
    $qsub_job_output = "$jobdir/.qsub.log";
    $qsub_script_filename = "$jobdir/.script";
    try {
      if (($qsub_script = fopen($qsub_script_filename,'w')) === false)
        PEMessage::throw_error("Couldn't open script file for writing.");
      $qsub_script_text = "#!/bin/sh\n"
        . "#$ -S /bin/sh\n"; # the default (csh?) doesn't understand 2>&1
      $qsub_script_text .=   # set working directory
          "#$ -wd " . escapeshellarg($jobdir) . "\n";
      $jobname = urlencode($target) . ', project ';
      if (isset($request['projects'][$op_uri])
          and isset($request['projects'][$op_uri]['short-dir']))
        $jobname .= urlencode($request['projects'][$op_uri]['short-dir']);
      else
        $jobname .= urlencode($op_uri);
      $jobname .= ', WorkingWiki job ' . $repos[$op_uri]->session->jobid;
      $qsub_script_text .=   # set job name
        '#$ -N ' . escapeshellarg($jobname) . "\n"; 
  
        #                  # a couple useful job variables for bookkeeping
        #. '#$ -ac WW_jobdir=' . escapeshellarg($jobdir) . "\n"
        #. '#$ -ac WW_username='.escapeshellarg($wgUser->getName()) . "\n"
                          # environment variables for ww-make
      # possibly send email notifications about the job status
      if ( isset($request['operation']['email-notifications']) )
      { $qsub_script_text .= "#$ -m eas\n"; # do send email
        foreach ($request['operation']['email-notifications'] as $email)
          $qsub_script_text .= "#$ -M " . escapeshellarg($email) . "\n";
        PEMessage::record_message( "Will send email notification" );
	log_sse_message( "Instructing cluster to send job-complete email\n", $request );
      }
      else
      { $qsub_script_text .= "#$ -m n\n";    # don't send email
      }
      $env =& $request['operation']['env'];
      if ($env === null) $env = array();
      $env['status-file'] = $statusfile;
      $env['time-limit'] = 0;
      foreach ($env as $key=>$val)
        $qsub_script_text 
          .= '#$ -v '.escapeshellarg($key).'='.escapeshellarg($val) . "\n";
      $qsub_script_text .= # collect output of job (shouldn't be any)
        '#$ -o ' . escapeshellarg($qsub_job_output) . "\n"
        . "#$ -j y\n";     # join stderr of job into same output file
      $logfile = $repos[$op_uri]->session->make_log_path($target,$repos[$op_uri]->wd);
      if ((file_exists($logfile) || is_link($logfile)) && !unlink($logfile))
      { PEMessage::record_error( "couldn't remove ‘"
         . htmlspecialchars($logfile) . "’ before remaking");
      }
      $qsub_script_text .=
        ( $make_command = $repos[$op_uri]->session->
	     make_command($target, $repos, $op_uri, $request)
	     . ' > ' . escapeshellarg( $logfile ) . ' 2>&1' );
      if (fwrite($qsub_script,$qsub_script_text) === false) {
        log_sse_message( "Error: couldn't write to script file.\n", $request );
        PEMessage::throw_error("Couldn't write to qsub script file.");
      }
      if (fclose($qsub_script) === false) {
        log_sse_message( "Error: couldn't close script file.\n", $request );
	PEMessage::throw_error("Couldn't close qsub script file.");
      }
      # invoke qsub to submit the background job
      log_sse_message( "Submitting job to cluster\n", $request );
      $qsub_command = '. /usr/local/sge/default/common/settings.sh && qsub '
        . '-terse ' . escapeshellarg($qsub_script_filename)
        . ' >' . escapeshellarg($qsub_output) . ' 2>&1';
      // TODO: capture output of qsub to SSE stream
      //PEMessage::throw_error(htmlspecialchars($qsub_command));
      #peLog("$qsub_command\n");
      log_sse_message( "$qsub_command\n", $request );
      system($qsub_command, $qsub_exit);
      if ($qsub_exit != 0)
      { if (copy($qsub_output,$repos[$op_uri]->session->
              make_log_path($target, $repos[$op_uri]->wd)) === false)
	  PEMessage::record_error("Failed to copy qsub output from "
	    .".jobnumber file to {$target}.make.log");
        PEMessage::throw_error( nl2br("qsub command failed.\n"
          . htmlspecialchars(file_get_contents($qsub_output)) ) );
      }
      sleep(2); # wait so it will appear on list of running jobs (longer?)
    } catch ( WWException $ex ) {
      if (fwrite($sth,"succeeded = false\n") === false)
        PEMessage::record_error("Failure writing failure to status file");
      if (fclose($sth) === false)
        PEMessage::record_error("Failure closing status file");
      throw $ex;
    }
    if (fclose($sth) === false)
      PEMessage::throw_error("Failure closing status file");
    return true;
  }

  function kill($jobid,$request,$report_errors=true)
  { global $peCacheDirectory;
    $cjobs = $this->collect_jobs();
    if (!isset($cjobs[$jobid]) or !$cjobs[$jobid]['jobnumber'])
      PEMessage::record_error("Job " . htmlspecialchars($jobid)
        . " not found.");
    else
    { $qdel_output = "$peCacheDirectory/qdel.output";
      $jobnumber = $cjobs[$jobid]['jobnumber'];
      $qdel_command = '. /usr/local/sge/default/common/settings.sh && qdel '
        . escapeshellarg($jobnumber) . ' >>' 
        . escapeshellarg($qdel_output) . ' 2>&1';
      #PEMessage::debug_message("$qdel_command\n");
      log_sse_message( "$qdel_command\n", $request );
      # todo: capture qdel output to SSE stream
      system($qdel_command,$qdel_exit);
      #PEMessage::debug_message("exit value of qdel: $qdel_exit");
      $this->expire_job_listing();
      # pause to let it die, to increase odds of successfully removing the
      # directory
      sleep(7);
      return ($qdel_exit == 0);
    }
    return false;
  }

  function jobname($jobid)
  { return 'WW_'.$jobid;
  }
}

