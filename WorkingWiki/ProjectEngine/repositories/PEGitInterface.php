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
 * PEGitInterface
 *
 * Subclass of PERepositoryInterface, for interfacing with
 * git repositories
*/

class PEGitInterface extends PERepositoryInterface
{ 
  public function uri_scheme()
  { return 'pe-git';
  }

  public function sync_from_repo_internal( $request )
  { $cache_dir = $this->wd->directory_name();
    if (!is_dir($cache_dir))
    { if (is_link($cache_dir) and !unlink($cache_dir))
        PEMessage::throw_error("Couldn't remove link at "
          .htmlspecialchars($cache_dir));
      #if (mkdir($cache_dir,0700,true) === false )
      #  PEMessage::record_error("Couldn't create working directory "
      #    .htmlspecialchars($cache_dir));
    }
    if (is_dir($cache_dir.'/.git'))
      $git_command = 'cd ' . escapeshellarg($cache_dir) . "\\\n  "
      . '&& git pull 2>&1';
    else
    { # can't clone into an existing directory, have to go to extremes
      $this->wd->clear_directory($request, true);
      $loc = str_replace('file:///', '/', $this->location);
      $git_command = "git clone ".escapeshellarg($loc)
        .' '.escapeshellarg($cache_dir).' 2>&1';
    }
    log_sse_message(
        "Updating from repository at " . $this->location . "\n" . $git_command . "\n",
	$request
    );
    $git_output = shell_exec($git_command);
    $output = htmlspecialchars('$ ' . $git_command . "\n" . $git_output);
    // TODO: pipe the output directly to sse log
    log_sse_message( $command_output, $request );
    #PEMessage::record_message( '<pre>$ ' . htmlspecialchars($git_command)
    #  . "\n" . htmlspecialchars($git_output) . '</pre>');
    // $when is not in use until further notice
    $git_command = 'cd ' . escapeshellarg($cache_dir) . "\\\n  "
      . '&& git checkout master 2>&1';
    log_sse_message( $git_command, $request );
    $git_output = shell_exec($git_command);
    $output .= htmlspecialchars('$ ' . $git_command . "\n" . $git_output);
    // TODO: pipe the output directly to sse log
    log_sse_message( $command_output, $request );
    PEMessage::record_message( '<pre>' . $output . '</pre>');
    peLog( "$output" );
    return true;
  }
}
