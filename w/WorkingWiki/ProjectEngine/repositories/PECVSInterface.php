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
 * PECVSInterface
 *
 * Subclass of PERepositoryInterface, for interfacing with
 * cvs repositories
*/

class PECVSInterface extends PERepositoryInterface
{ 
  public function uri_scheme()
  { return 'pe-cvs';
  }

  /* THIS HAS NOT BEEN TESTED AND PROBABLY DOESN'T WORK */
  public function sync_from_repo_internal( $request )
  { $cache_dir = $this->wd->directory_name();
    if (is_dir($cache_dir.'/CVS'))
      $command = 'cd ' . escapeshellarg($cache_dir) . ' && cvs update -d 2>&1';
    else
      $command = "cvs -d ".escapeshellarg($this->location)
        .' checkout '.escapeshellarg($cache_dir).' 2>&1';
    log_sse_message(
        "Updating from repository at " . $this->location . "\n" . $command . "\n",
	$request
    );
    $command_output = shell_exec($command);
    // TODO: pipe the output directly to sse log
    log_sse_message( $command_output, $request );
    PEMessage::record_message('output of ' . htmlspecialchars($command)
      . ':<pre>' . htmlspecialchars($command_output) . '</pre>');
    // $when is not in use until further notice
    return true;
  }
}
