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

class PEBackgroundSession extends PESpecialSession 
{ var $jobid;

  function __construct($jobid)
  { if ($jobid == '')
      $jobid = mt_rand();
    $this->jobid = $jobid;
  }

  public function session_type()
  { return 'background';
  }

  function base_directory()
  { global $peCacheDirectory;
    return $peCacheDirectory.'/background';
  }

  function directory()
  { return $this->base_directory() . '/' . urlencode($this->jobid);
  }

  public function initialize_working_directory($repo, $request)
  { $path = $this->directory() . '/' . $repo->cache_directory_name();
    if (!is_dir($path))
    { if ( ! array_key_exists('okay-to-create-background-job',$request) )
        PEMessage::throw_error("Can not perform operation: background"
          . " session " . htmlspecialchars($this->jobid)
          . " not properly initialized.");

      parent::initialize_working_directory($repo,$request);
      PEMessage::record_message("Created duplicate directory for project '"
        . htmlspecialchars($repo->uri()) ."'.");
    }
    return true;
  }

  public function niceValueForMake()
  { global $peNiceValueForBackgroundMake, $peNiceValueForMake;
    if ($peNiceValueForBackgroundMake == -1)
      return $peNiceValueForMake;
    else
      return $peNiceValueForBackgroundMake;
  }

  public function ioniceClassForMake()
  { global $peIoniceClassForBackgroundMake;
    return $peIoniceClassForBackgroundMake;
  }
  public function ionicePriorityForMake()
  { global $peIonicePriorityForBackgroundMake;
    return $peIonicePriorityForBackgroundMake;
  }

  # now, override the usual make operation.  The subclassed PEBackgroundJobs
  # object knows how to do the actual operation with unix or SGE backgrounding.
  function do_make_operation($request, $op_uri, $repos, &$result)
  { if ( ! isset($request['okay-to-create-background-job']) 
        or (isset($request['background-job']) 
            and $request['background-job'] != 0) )
      PEMessage::throw_error( "Make operations in existing background jobs "
        . "are not supported." );
    $bg = PEBackgroundJobs::instance();
    $bg->expire_job_listing();
    return $bg->do_make_operation($request, $op_uri, $repos, $result);
  }

  # FOREGROUND_ONLY is not defined when running in background, obviously
  public function env_for_make($target, $repos, $op_uri, $request)
  { $env = parent::env_for_make($target, $repos, $op_uri, $request);
    unset($env['FOREGROUND_ONLY']);
    return $env;
  }

  # override the common merge operation, just to make sure the background job
  # is dead before merging the files.
  function merge_into_persistent_session($request, $repos, &$return)
  { $bg = PEBackgroundJobs::instance();
    $bg->kill($this->jobid, $request, false);
    $bg->expire_job_listing();
    return parent::merge_into_persistent_session($request, $repos, $return);
  }
}
