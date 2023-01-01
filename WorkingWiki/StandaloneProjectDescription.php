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

class StandaloneProjectDescription extends WorkingWikiProjectDescription
{ // This is a special kind of project that has exactly one source file.
  // It doesn't store a project description, because the source file
  // is all you need to know.
  // Unlike other WorkingWikiProjectDescriptions, it syncs its source file
  // when its tag is encountered during parsing.  It isn't able to look
  // up the source file at other times.  This allows standalone source files
  // to be used in .wikitext files.  LaTeX-formatted math expressions in
  // WW-enhanced wikitext are implemented using standalone source files,
  // and this special design allows them to be used in .wikitext files.

  public function add_to_project_description_text(&$xml)
  { $xml .= "  <standalone/>\n";
    parent::add_to_project_description_text($xml);
  }

  public function is_standalone()
  { return true;
  }

  public function pages_involving_project_files()
  { return array();
  }

  # standalone source files are only looked up in the "currently_parsing" text,
  # which may be from a page, or a .wikitext file, or whatever.
  public function default_locations_for_file( $filename )
  { global $wwContext;
    return array( $wwContext->wwInterface->currently_parsing_key );
  }

  public function proactively_sync_if_needed()
  { # always
    $success = ProjectEngineConnection::call_project_engine(
	    'sync',
	    $this,
	    null,
	    null,
	    true
    );
    if ( $success ) {
	    $this->synced = true;
    }  
    return true;
  }

  # for git: when's the last time anything in the project was edited
  # what to do with this?  I don't think we can use git with these
  public function latest_revision()
  { if ($this->as_of_revision)
      return $this->as_of_revision;
    return 0;
  }
}
