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
 * PEResourcesDirectoryInterface
 *
 * Subclass of PERepositoryInterface, for interfacing with
 * ProjectEngine's internal 'resources' directory.
*/

class PEResourcesDirectoryInterface 
  extends PERepositoryInterface
{ 
  public function allow_writes()
  { return false;
  }

  public function uri_scheme()
  { return 'pe-resources';
  }

  public function need_to_create($session)
  { return false;
  }

  protected function create_wd( $request )
  { $this->wd = PEDirectory::resources_directory();
  }

  public function cache_directory_name()
  { PEMessage::throw_error('PEResourcesDirectoryInterface::'
        .'cache_directory_name() should not be called '
        . htmlspecialchars(serialize(debug_backtrace())));
  }

  public function sync_from_repo_internal( $request )
  { return true;
  }

  public function lock_directory( $request )
  { return true;
  }
  
  public function unlock_directory( $request, $delete_after = false )
  { return true;
  }
}