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

/**
 * Maintenance script to upload files into a WorkingWiki project 
 *
 * @file
 * @ingroup Maintenance
 * @author Lee Worden <worden.lee@gmail.com>
 */

# allow large files
ini_set( 'memory_limit', '1000M' );

#ob_end_flush();
$options = array( 'help', 'overwrite', 'norc', 'dry', 
  'pages', 'defaults', 'debug' );
$optionsWithArgs = array( 'projectname', 'user', 'comment' );
require_once( 'commandLine.inc' );
/* Extensions for files to be uploaded to Image: (by default) rather than into
 * source-file tags in main namespace.  */
$image_extensions = array( 'csv', 'eps', 'ps', 'pdf', 'jpg', 'gif',
  'png', 'svg', 'swf', 'mp3', 'wav', 'wma' );
/* Things to expect to omit */
$omit_by_default_patterns = array( '/~$/', '/#$/', '/^\./' );
/* Things to expect to import as targets */
$target_extensions = array( 'out', 'pdf', 'ps' );

/* a global, used during cleanup */
$files_to_cleanup = array();

echo( "Import Project Files\n\n" );

if( count( $args ) < 1 || isset( $options['help'] ) ) {
  showHelp();
} else {

  global $wgDebugLogFile;
  if (isset($options['debug']))
    $wgDebugLogFile = '/dev/tty';

  # Initialise the user for this operation
  $user = isset( $options['user'] )
    ? User::newFromName( $options['user'] )
    : User::newFromName( 'Maintenance script' );
  if( !$user instanceof User )
    die( "invalid user name" );
  $wgUser = $user;

  # Get the upload comment
  if ( isset( $options['comment'] ) )
    $comment =  $options['comment'];
  else
    $comment = 'Importing project files';

  $flags = 0 | ( isset( $options['norc'] ) ? EDIT_SUPPRESS_RC : 0 );
  
  # Create importer object
  $importer = new ImportQueue;

  # disable the limit on file size
  global $wwMaxInsertFileSize;
  $oldMaxInsertFileSize = $wwMaxInsertFileSize;
  $wwMaxInsertFileSize = -1;

  $files_to_import = array();
  $outside_projectname = '';
  foreach ( $args as $file_arg )
  { if (is_dir($file_arg)) {
      while ( substr( $file_arg, -1 ) == '/' )
        $file_arg = substr( $file_arg, 0, -1 );
      $files_within = wwfFindFiles( $file_arg );
      sort( $files_within );
    } else { 
      $files_within = array();
      $unpack = false;
      foreach(ImportProjectFiles::$pkgExtensions as $ext)
      { if (wwfSuffixMatches($file_arg, ".$ext"))
        { $unpack = true;
          if (!isset($options['defaults']))
          { echo("Unpack package file '{$file_arg}'? [Y]\n");
            $user_response = fgets(STDIN);
            if ( preg_match( '/\w/', $user_response, $matches ) )
              $unpack = (strtolower( $matches[0] ) == 'y');
          }
          break;
        }
      }
      if ($unpack)
      { list($success,$unpack_code) = $importer->unpack($file_arg,$file_arg);
        if (!$success)
        { echo("$unpack_code\n");
          cleanup_and_die("Couldn't unpack package file '{$file_arg}'\n");
        }
        $file_arg = ImportQueue::tempDir().'/'
          .ImportQueue::$unpackDirBase.$unpack_code; 
        $files_to_cleanup[] = $file_arg;
        $files_within = wwfFindFiles( $file_arg );
        if (count($files_within))
        { sort( $files_within );
          # if everything in the directory is in a single subdir, don't include
          # that as part of the project filenames
          while (preg_match('/^(.*?)\//',$files_within[0],$matches) and
              strncmp(end($files_within),$matches[0],strlen($matches[0])) == 0)
          { #echo("found common prefix [{$matches[0]}] [{$matches[1]}]\n");
            $file_arg .= '/'.$matches[1];
            foreach ($files_within as &$file)
              $file = substr($file,strlen($matches[0]));
            unset($file); #there's a weird bug if this line isn't here
          }
        }
      }
      else
        $files_to_import[] = $file_arg;
    }
    foreach ($files_within as $file)
    { if (preg_match('/(^|\/)\.workingwiki\/project-description.xml$/', $file))
      { if (isset($outside_project))
        { echo( "  " . wordwrap("Warning: more than one "
            . ".workingwiki/project-description.xml found!  Will use the last "
            . "one seen...\n", 70,
            "\n  ") . "\n" );
        }
        if (isset($options['debug']))
          echo("Reading $file\n");
        try
        { $outside_project = ProjectDescription::newFromXML(
            file_get_contents("$file_arg/$file"), NULL,
            'importProjectFiles.php External Project Description' );
          $outside_projectname = $outside_project->project_name();
        } catch (WWException $ex)
        { echo $wwContext->wwInterface->report_errors_as_text('external project','');
          cleanup_and_die('terminating');
        }
      }
      else if (preg_match('/(^|\/)\.workingwiki\/pages\//', $file))
      { if (isset($options['debug']))
          echo("Including page file $file\n");
        $page_files[] = "$file_arg/$file";
      }
      else if (preg_match('/(^|\/)\.workingwiki($|\/)/', $file))
      { if (isset($options['debug']))
          echo("Skipping file $file\n");
      }
      else
      { if (isset($options['debug']))
          echo("Including project file $file\n");
        $files_to_import[] = array($file_arg,$file);
      }
    }
  }

  # Set the destination project name
  if (isset($options['projectname']))
    $projectname = $options['projectname'];
  else if ( isset( $outside_project ) )
    $projectname = $outside_project->project_name();
  if ( !isset($options['defaults']) )
  { echo( "Destination project name [$projectname]:\n" );
    $user_response = fgets(STDIN);
    if ( preg_match( '/\S/', $user_response, $matches ) )
      $projectname = preg_replace( '/^\s*(\S.*\S)\s*$/', '$1', 
                                   $user_response );
  }
  if ( $projectname == '' )
    cleanup_and_die("No destination project name specified.\n");

  try {
    $project = $wwContext->wwStorage->find_project_by_name($projectname);
    if (is_null($project))
      $project = $wwContext->wwInterface->create_empty_project($projectname);
  } catch( PEException $ex )
  { echo $wwContext->wwInterface->report_errors_as_text('project',$projectname);
    cleanup_and_die('terminating');
  }

  # now: $projectname is the project name we are importing into.
  # $project exists but might be empty, and $outside_project might exist.
  echo("Importing to project '" . $projectname . "'.\n");

  if (isset($project->project_files) and is_array($project->project_files)
      and count($project->project_files) > 0)
  { echo("Project '" . $projectname . "' exists - replace or extend it [E]:\n");
    $user_response = fgets(STDIN);
    if (preg_match('/^\s*r/i', $user_response))
    { echo( "Will replace the existing project.\n");
      $project->project_files = array();
    }
    else
      echo( "Will extend the existing project.\n");
  }

  echo $wwContext->wwInterface->report_errors_as_text('project',$projectname);

  $pagetext = "Imported by importProjectFiles.php script for project '"
    . $projectname . "'.";

  if (isset($page_files))
  { if (isset($options['pages']))
      $do_pages = (strlower($options['pages'][0]) == 'y');
    else if (!isset($options['defaults']))
    { echo( "\nImport wiki pages as well as project files? [Y]\n" );
      $user_response = fgets(STDIN);
      if ( preg_match( '/\w/', $user_response, $matches ) )
        $do_pages = (strtolower( $matches[0] ) == 'y');
      else
        $do_pages = true;
    }
    else
      $do_pages = true;
  }
  $page_renames = array();
  if ($do_pages)
  { foreach ($page_files as $pagefilename) {
      $to_import = true;
      $pagename = preg_replace( '/^.*\.workingwiki\/pages\//', '',
        $pagefilename );
      $pagename = urldecode( $pagename );
      if (!isset($options['defaults']))
      { echo("\nImport page '{$pagename}'? [" . ($to_import ? 'Y':'N') . "]\n" );
        $user_response = fgets(STDIN);
        if ( preg_match( '/\w/', $user_response, $matches ) )
          $to_import = (strtolower( $matches[0] ) == 'y');
      }
      if ( !$to_import )
        continue;
      if (preg_match('/^(File|Image|Media):/i', $pagename))
        $new_pagename = str_replace(str_replace('/','$',$outside_projectname),
          str_replace('/','$',$projectname), $pagename);
      else
        $new_pagename = 
          str_replace($outside_projectname,$projectname,$pagename);
      if (!isset($options['defaults']))
      { echo("  Destination page name: [$new_pagename]\n");
        $user_response = fgets(STDIN);
        if ( preg_match( '/\S/', $user_response, $matches ) )
        { $new_pagename 
            = preg_replace( '/^\s*(\S.*\S)\s*$/', '$1', $user_response );
          # If the user gives a new name for the page, we want any files
          # that would have been imported to this page to go to the new
          # page.  This is complicated: for instance, given project A, file
          # B, on page A/B, suppose we import to project Q, and say we want
          # page A/B to be called Q/Z... then when it comes time to import
          # file B, we find a suggestion "?P/?F", but we have to look up 
          # renames for "A/B", not "Q/B"...
          $page_renames[$pagename] = $new_pagename;
        }
      }
      $pagename = $new_pagename;
      $pgentry = $importer->lookup_page( $pagename );
      if ($pgentry['text'] == '')
      { $if_exists = 'create';
      }
      else
      { if (isset($options['overwrite']))
          $if_exists = 'overwrite';
        else
          $to_import = false;
        if (!isset($options['defaults']))
        { while (1)
          { echo( "  " . wordwrap( "Warning: page '{$pagename}' already "
              . "exists.  Can append the saved content, but it may cause "
              . "duplicate source-file tags. Append/Overwrite/Skip? [S]", 
              70, "\n  ") . "\n");
            $user_response = fgets(STDIN);
            if ( preg_match( '/\S(.*\S)?/', $user_response, $matches ) )
              $answer = strtolower($matches[0]);
            else
              $answer = 's';
            if ($answer == 's')
            { $to_import = false;
              break;
            }
            else if ($answer == 'o')
            { $to_import = true;
              $if_exists = 'overwrite'; 
              break;
            }
            else if ($answer == 'a')
            { $to_import = true;
              $if_exists = 'append';
              break;
            }
          }
        }
      }
      echo("Will " . ($to_import ? $if_exists : 'skip')
       . " page '{$pagename}'...\n");
      if (!$to_import)
        continue;

      $pagetext = file_get_contents( $pagefilename );
      $retval = $importer->insert_page_text( $pagename, $pagetext,
          $projectname, $if_exists );
      if ( ! $retval )
      { echo("Error recording content of $pagefilename for insertion.\n");
        echo $wwContext->wwInterface->report_errors_as_text('project',$projectname);
      }
    }
  }

  #$suggestions = wwfMakePageSuggestions( $outside_project,
  #  $outside_projectname, $project, $page_renames );
  $orig_locations = (isset($outside_project) ?
    wwfMakePageLocations($outside_project) : null);
  #wwLog("\$orig_locations = " . print_r($orig_locations, true) );
  $dest_locations = wwfMakePageLocations($project);
  #wwLog("\$dest_locations = " . print_r($dest_locations, true) );
  #wwLog("\$page_renames = " . print_r($page_renames,true) );

  foreach ($files_to_import as $filename) {
    $to_import = true;
    if (is_array($filename))
    { $project_filename = $filename[1];
      $filename = implode('/',$filename);
    }
    else
      $project_filename = $filename;

    if ( isset($outside_project) )
      $to_import = $outside_project->is_file_source($project_filename);
    else 
      foreach ( $omit_by_default_patterns as $pattern )
        if ( preg_match( $pattern, $project_filename ) ) {
          $to_import = false;
          break;
        }

    if (!isset($options['defaults']))
    { # having some trouble getting these to print on same line as input
      echo( "\nImport file '{$project_filename}'? [" 
            . ($to_import ? 'Y' : 'N') . "]\n" );
      #@ob_flush();
      $user_response = fgets(STDIN);
      if ( preg_match( '/\w/', $user_response, $matches ) )
        $to_import = (strtolower( $matches[0] ) == 'y');
    }

    if ( !$to_import )
      continue;

    # somewhat arbitrary interface here: if the filename comes from
    # the command line as an absolute filename,
    # figure the user might want just the last part of the filename;
    # if it's relative, use the whole thing.
    if ( substr( $project_filename, 0, 1 ) == '/' ) {
      $project_filename = end( explode( '/', $project_filename ) );
    }

    unset($file_page, $import_as_source);
    $file_ext = end( explode( '.', $filename ) );
    if (isset($outside_project))
      $import_as_source = $outside_project->is_file_source($project_filename);
    else
    { if ( array_search( strtolower( $file_ext ), $target_extensions )
           !== false )
        $import_as_source = false;
      else
        $import_as_source = true;
    }
    if (!isset($options['defaults']))
    { echo( "  (S)ource file or (T)arget? [" 
            . ($import_as_source ? 'S' : 'T') . "]\n" );
      $user_response = fgets(STDIN);
      if ( preg_match( '/\w/', $user_response, $matches ) )
        $import_as_source = (strtolower( $matches[0] ) == 's');
      #echo( $import_as_source ? "Source.\n" : "Target.\n" );
    }

    $orig_project_filename = $project_filename;
    if (!isset($options['defaults']))
    { echo("  Import as " . ($import_as_source ? 'source':'project')
        . " filename: [$project_filename]\n" );
      $user_response = fgets(STDIN);
      if ( preg_match( '/\w/', $user_response ) )
      { $project_filename 
          = preg_replace( '/^\s*(\w.*\w)\s*$/', '$1', $user_response );
      }
    }

    $suggestion = wwfGeneratePageSuggestion($orig_project_filename,
      $project_filename, $orig_locations, $outside_projectname,
      $dest_locations, $projectname, $page_renames);
    if ( $suggestion != '' )
    { $file_page = $suggestion;
    }

    do {
      if (!isset($options['defaults']))
      { echo("  Destination page: [$file_page]\n" );
        $user_response = fgets(STDIN);
        if ( preg_match( '/\w/', $user_response ) )
          $file_page = preg_replace( '/^\s*(\w.*\w)\s*$/', '$1', $user_response );
      }

      $title = Title::newFromUrl( $file_page );

      if( !is_object( $title ) ) {
        echo "  page title '{$file_page}' is invalid\n";
      }
    } while ( !is_object( $title ) and !isset($options['defaults']));
    
    echo( "  " . wordwrap("Will import " 
        . ($import_as_source ? 'source':'project') . " file "
        . "'{$project_filename}' to page '{$file_page}'...", 70, "\n  ") 
      . "\n" );
    $attrs = array('filename' => $project_filename );
    if ( $import_as_source )
    { $attrs['page'] = $title->getPrefixedText();
      $project->add_source_file($attrs);
    }
    else
    { $attrs['appears'] = array( $title->getPrefixedText() );
      $project->add_project_file($attrs);
    }

    if( NS_MEDIA == $title->getNamespace() )
      $title = Title::makeTitle( NS_IMAGE, $title->getDBkey() );

    if ( $title->getNamespace() == NS_IMAGE ) {
      #echo( "Importing ‘{$title}’... " );
      if ( !isset( $options['dry'] ) )
        $importer->upload_file( $file_page, $filename );
    } else { # non-image page
      $sz = filesize( $filename );
      if ( $sz !== false and $sz > $oldMaxInsertFileSize ) {
        #if (isset($options['defaults'])) 
        #{ echo "  $filename is more than $oldMaxInsertFileSize bytes — skipping.\n";
        #  continue;
        #}
        if (!isset($options['defaults'])) 
        { echo( "  " . wordwrap("$project_filename is more than "
            . "$oldMaxInsertFileSize bytes - are you sure? [Y]", 70, 
            "\n  ") . "\n" );
          $user_response = fgets(STDIN);
          if ( preg_match( '/\w/', $user_response, $matches )
              && (strtolower( $matches[0] ) != 'y') )
            continue;
        }
      }

      $file_contents = file_get_contents( $filename );
      if ( $file_contents === false ) {
        echo( "  Couldn't read $filename\n" );
        continue;
      }
      $retval = $importer->insert_file_element( $import_as_source,
        $project_filename, $projectname, $file_page, $file_contents );
      if ( ! $retval )
      { echo("  Error inserting $project_filename into page.\n");
        echo $wwContext->wwInterface->report_errors_as_text('project',$projectname);
      }
    }
  }

  echo("\nCommitting changes to the wiki... ");
  $importer->commit( $options['overwrite'] );
  echo( "done.\n" );
  echo $wwContext->wwInterface->report_errors_as_text('project',$projectname);

  $write_pd = true;
  if ( $write_pd ) {
    echo('Updating project-description... ');
    if ( !isset( $options['dry'] ) ) {
      try {
        //$project->write_description_to_page($title->getPrefixedDBKey());
        $wwContext->wwStorage->save_project_description( $project, /*check_perms*/ false );
        $wwContext->wwInterface->invalidate_pages( $project );
      } catch ( PEException $ex )
      { echo $wwContext->wwInterface->report_errors_as_text('project',$projectname);
        cleanup_and_die('terminating');
      }
    }
    echo( "done.\n" );
    echo $wwContext->wwInterface->report_errors_as_text('project',$projectname);
  }
}

cleanup();
exit(0);


function showHelp() {
print <<<EOF
USAGE: php importProjectFiles.php <options> --conf=.../LocalSettings.php <path>

<path> : Path to the project directory to import

Options:

--user <user>
  User to be associated with the edit
--comment <comment>
  Edit summary
--overwrite
  Overwrite existing files when uploading to Image: (aka File:) pages
--pages=false
  Don't include wiki pages, if present along with files
--norc
  Don't update the wiki's recent changes
--dry
  Dry run, don't import anything
--defaults
  Accept all default choices
--debug
  Print out internal details while running
--help
  Show this information

EOF;
}

function cleanup()
{ global $files_to_cleanup;
  foreach($files_to_cleanup as $tempfile)
    wwfRecursiveUnlink($tempfile, true);
}

function cleanup_and_die($message)
{ cleanup();
  die($message);
}
