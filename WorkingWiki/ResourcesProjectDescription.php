<?php
/* WorkingWiki extension for MediaWiki 1.13 and later
 * Copyright (C) 2010 Lee Worden <worden.lee@gmail.com>
 * http://lalashan.mcmaster.ca/theobio/projects/index.php/WorkingWiki
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

class ResourcesProjectDescription extends ProjectDescription
{ // There is only one ResourcesProjectDescription.  It's used by 
  // GetProjectFile in the special case of files in the resources
  // directory.
  // It has no corresponding project-description text.

  # ===== class functions =====

  public static function factory()
  { static $unique_instance;
    if (!isset($unique_instance))
      $unique_instance = new ResourcesProjectDescription();
    return $unique_instance;
  }

  # ===== instance functions =====

  // constructor
  protected function __construct()
  { $this->project_description_page = null;
    $this->project_files = array();
    $this->projectname = '';
    $this->uri = '';
  }

  protected function generate_uri()
  { return 'pe-resources:';
  }

  public function project_url_attr()
  { return 'resources=1';
  }

  # produce the XML project description to store on a wiki page
  public function project_description_text()
  { global $wwContext;
    $wwContext->wwInterface->debug_message("Internal error: "
      . "ResourcesProjectDescription::project_description_text() shouldn't "
      . "be called");
    return '';
  }

  # what to send to ProjectEngine
  public function fill_pe_request(&$request, $focal, $sync_sf)
  { global $wwContext;
    $request['projects'][$this->project_uri()] = array();
    if (ProjectEngineConnection::operation_includes_make(
            $request['operation']['name']))
      $wwContext->wwInterface->throw_error("No make operations in resource directory!");
  }

  # add a file to the project, for instance before writing the new 
  # project-description out to its page.
  # if the file is already in there, these values will overwrite the
  # old ones, one by one.  Setting a value to null in $attrs causes the
  # attribute to be removed from the file.
  # The exceptions are 'appears' and 'archived': these are arrays, and
  # the keys within will overwrite the keys in the corresponding arrays
  # in project_files.
  public function add_file_element($attrs)
  { global $wwContext;
    $wwContext->wwInterface->throw_error("Cannot add files to resource directory!");
  }

  public function data_for_dynamic_placeholder() {
	  return 'data-resources="1"';
  }
}
