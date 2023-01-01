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
 * PEBasicProjectDirectoryInterface
 *
 * Subclass of PERepositoryInterface, for interfacing with
 * projects stored in the ProjectEngine by sending the files in PE requests,
 * without complex directory names, remote repository calls, or preview or
 * background sessions.
*/

class PEBasicProjectDirectoryInterface extends PERepositoryInterface
{ 
	public function uri_scheme() {
		return 'pe-project';
	}

	public function cache_directory_name() {
		return $this->location;
	}

	public function cache_directory_path() {
		global $peCacheDirectory;
		return "$peCacheDirectory/{$this->location}";
	}

	public function sync_from_repo_internal( $request ) {
	}

	protected function lockfile_name_for_directory( $path ) {
		return "$path/.workingwiki/.lock";
	}
}
