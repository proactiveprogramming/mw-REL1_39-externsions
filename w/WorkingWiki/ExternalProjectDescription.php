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

# an external project description just tells you the location of the
# repository where the project actually is.  All we can do with this
# is ask ProjectEngine to do things to it.
class ExternalProjectDescription extends ProjectDescription
{
  public $location;
  public $synced;

  # This kind of project is characterized by an element
  # <external location="..."/>.
  protected function read_xml_element($element)
  { if (parent::read_xml_element($element))
      return true;
    if (((string)$element->getName()) == 'external')
    { $this->location = (string)$element['location'];
      return true;
    }
    return false;
  }

  public function add_to_project_description_text(&$xml)
  { $xml .= "  <external location=\"" 
      . htmlentities($this->location, ENT_COMPAT, 'UTF-8', false) . "\"/>\n";
    parent::add_to_project_description_text($xml);
  }

  protected function generate_uri()
  { return $this->location;
  }

  public function is_external()
  { return true;
  }

  public function fill_pe_request( &$request, $focal, $sync_sf )
  { parent::fill_pe_request($request, $focal, $sync_sf);
    if ($sync_sf and !$this->synced and 
        wwRunHooks('WW-OKToSyncFromExternalRepos', array()))
    { $request['projects'][$this->project_uri()]['sync'] = true;
      $this->synced = true;
    }
  }
}
