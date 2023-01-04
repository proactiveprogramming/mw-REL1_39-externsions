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
 * PEDirectory
 *
 * ProjectEngine receives an HTTP request for a project file,
 * updates the project's cached working directory from stored project data,
 * updates the target file using make if requested, serves the up-to-data
 * file contents.
 *
 * PEDirectory class represents a directory in the file cache.
 * It is used directly for the resources directory, and as a parent class.
 *
 * PEProjectDirectory class manages the working directory and
 * make subprocesses.
*/

class PEDirectory
{ // location of cache directory
  var $path;

  public function __construct($path)
  { $this->path = $path;
  }

  static $_resources_directory;
  public static function resources_directory()
  { if (!isset(self::$_resources_directory))
    { global $peResourcesDirectory;
      self::$_resources_directory 
        = new PEDirectory($peResourcesDirectory);
    }
    return self::$_resources_directory;
  }

  static $_logfiles_directory;
  public static function logfiles_directory()
  { if (!isset(self::$_logfiles_directory))
    { global $peCacheDirectory;
      self::$_logfiles_directory
	  = new PEDirectory( $peCacheDirectory . '/logfiles' );
    }
    return self::$_logfiles_directory;
  }

  public static function session_directory($session)
  { return new PEDirectory($session->directory());
  }

  public function directory_name()
  { return $this->path;
  }

  public function mkdir()
  { if ( is_dir($this->path) )
      return true;
    else if ( file_exists($this->path) )
      return false;
    else if ( is_link($this->path) )
    { unlink( $this->path );
      return mkdir($this->path,0700,true);
    }
  }

  # If we're going to read or write a source file, make sure it's a valid
  # location first.  There are some hazards: the filename could be 
  # '../../../../../../../etc/passwd' for instance...
  # See also the subclass's implementation of this.  It has additional
  # concerns because it writes files while this class only reads.
  public function validate_filename($file)
  { $filepath = $this->path . '/' . $file;
    # if it's a bad filename, refuse to use it
    if (!is_in_directory($filepath,$this->path,/*follow_links*/false))
    { PEMessage::debug_message("'". htmlspecialchars($filepath)
        ."' not in '" . htmlspecialchars($this->path) . "'");
      PEMessage::throw_error("Invalid filename '"
        . htmlspecialchars($file) . "'.");
    }
  }

  # this function is higher level than many in this class.  it returns
  # an array of 
  #  filename => (perms, size, modtime)
  # where perms is a string like "-rw-r--r--",
  # size is an integer,
  # modtime is a unix time (seconds since epoch),
  public function list_directory($subdir)
  { if ($subdir != '.' and $subdir != '')
      $subdir_comp = "/$subdir";
    else
      $subdir_comp = '';
    $handle = opendir( $this->path . $subdir_comp );
    if ( !$handle )
      PEMessage::throw_error( 'Directory \'' . htmlspecialchars($subdir)
        . '\' not found' );
    $files = array();
    while ( ($entry = readdir( $handle )) !== false )
      if ($entry !== '.' && $entry != '..')
        $files[] = $entry;
    closedir( $handle );
    if ($subdir_comp)
      $files[] = '..';
    $ret = array();
    foreach ($files as $file)
    { $filepath = $this->path . $subdir_comp . '/' . $file;
      $stat = lstat($filepath);
      $ret[$file] 
        = array(permstring($stat['mode']), $stat['size'], $stat['mtime']);
    }
    return array('d', $ret);
  }

  public function retrieve_file_contents($target, $local, $deref=false)
  { $this->validate_filename($target);
    $filepath = $this->path . '/' . $target;
    #peLog( "retrieve: $filepath" );
    if (!file_exists($filepath))
      return null;
    if (!$deref and is_link($filepath))
    { $lst = lstat($filepath);
      return array('c', readlink($filepath), $lst['mtime']);
    }
    if (is_dir($filepath))
      return $this->list_directory($target);
    if ($local)
      return array('p', $filepath);
    else
      return array('c', file_get_contents($filepath), filemtime($filepath));
  }

  // recursive inner function for sourceFileHashes()
  protected function makeFileHashes($dir, $prefix)
  { $result = array();
    if ($prefix !== '')
      $prefix = "$prefix/";
    if ($dhl = opendir($dir))
    { while (($file = readdir($dhl)) !== false)
      { if ($file == '.' or $file == '..')
          continue;
        $path = $dir . '/' . $file;
        if (is_dir($path))
          $result = array_merge($result, 
            $this->makeFileHashes($path, "$prefix$file"));
        else if (is_file($path))
          $result["$prefix$file"] = sha1_file($path);
      }
    }
    return $result;
  }

  // create an array object to tell what files we already have;
  // then the caller can give us the ones we don't have.
  public function fileHashes()
  { return $this->makeFileHashes($this->directory_name(),'');
  }

  // when the caller gives its hashes of archived files, we give it any
  // of those files that are different.
  // todo: what if an 'archived project file' is actually a directory?
  public function updatedFileContents($afh, $local)
  { $cache_dir = $this->directory_name();
    $afc = array();
    if (is_array($afh))
      foreach ($afh as $file=>$hash)
      { $filepath = "{$this->path}/$file";
        if (file_exists($filepath))
        { $filehash = sha1_file($filepath);
          if ($filehash != $hash)
          { $afc[$file] = $this->retrieve_file_contents($file,$local);
            PEMessage::debug_message("Hash of file " . htmlspecialchars($file)
              . " is changed: $filehash != $hash");
          }
          else
            PEMessage::debug_message("Hash of file " . htmlspecialchars($file)
              . " is unchanged: $filehash == $hash");
        }
        else 
        { PEMessage::debug_message("File ". htmlspecialchars($file)
            . " not found");
          if ($hash != '')
            $afc[$file] = null;
        }
      }
    return $afc;
  }
}

class PEProjectDirectory extends PEDirectory
{ 
  # this extends the parent class's implementation by requiring all
  # links found in a project directory to stay within the cache, even
  # if they point to other projects' files
  public function validate_filename($file)
  { parent::validate_filename($file);
    $filepath = $this->path.'/'.$file;
    # if a file in the directory links to the wrong place, we'll just 
    # remove the link, to make sure we're safe before we write anything
    global $peCacheDirectory;
    # temporary?  allow the project directory itself to be a link, because
    # we need those during migration from old to new WW
    if ($file != '.' and
        !is_in_directory($filepath,$peCacheDirectory,/*follow_links*/true))
    { if (unlink($filepath) === false)
        PEMessage::throw_error("Error deleting link "
          . htmlspecialchars($filepath) . ".");
    }
  }

  # when the caller gives us the source files to update, we do it.
  # the arg is an array whose each entry is either
  #  array( 'c', contents, mtime )
  # or
  #  array( 'p', path ) in case of a locally accessible file.
  # use array( filename, null, mtime ) to remove a file.
  public function syncFilesFromContents($sfc, $request, $force_touch)
  { if ( is_array($sfc) )
    { foreach ($sfc as $filename=>$entry)
      { if ($entry[0] == 'c')
          $this->sync_source_file_from_content($filename, $entry[1], 
            $entry[2], $force_touch, $request);
        else
          $this->sync_source_file_from_file($filename, $entry[1], $force_touch, $request);
        log_sse_message( " $filename", $request );
      }
    }
  }

  # When the caller provides a source file's content and modification time:
  # Update it unless it's already the same.
  public function sync_source_file_from_content( $file, $contents, 
    $mtime, $force_touch, $request )
  { $this->validate_filename($file);
    $filepath = $this->path . '/' . $file;
    if ($contents !== null)
    { #if (!file_exists($filepath))
      #  PEMessage::debug_message("File " . htmlspecialchars($filepath)
      #    . " doesn't exist.");
      #else
      #  PEMessage::debug_message("Sync file " . htmlspecialchars($filepath)
      #    . "? sha1_file(" .htmlspecialchars($filepath) . ") = "
      #    . htmlspecialchars(sha1_file($filepath)) . ", sha1(contents) = "
      #    .  htmlspecialchars(sha1($contents)) . ': '
      #    . (sha1_file($filepath) != sha1($contents) ? 'yes.' : 'no.'));
      if (!file_exists($filepath) or sha1_file($filepath) != sha1($contents))
      { $dir = preg_replace('#/[^/]+$#','',$filepath);
        PEMessage::debug_message("Sync file " . htmlspecialchars($file) . ".");
        $this->validate_filename($dir);
        if ($dir == $filepath or $dir == '')
          PEMessage::throw_error("Bad directory name "
            . htmlspecialchars($dir) . ".");
        if (!is_dir($dir))
        { if ((file_exists($dir) or is_link($dir)) and unlink($dir) === false)
            PEMessage::throw_error("Couldn't unlink "
              . htmlspecialchars($dir) . " to make "
              . htmlspecialchars($filepath) . ".");
          if (mkdir($dir,0700,true) === false)
            PEMessage::throw_error("Couldn't create directory "
              . htmlspecialchars($dir) . ".");
        }
        if (file_exists($filepath))
          recursiveUnlink($filepath,null,true);
        if (($write_file = fopen($filepath,"w")) == false)
        { PEMessage::throw_error(
              "Couldn't open ".htmlentities($filepath)." for writing.");
        }
        #if (fwrite($write_file,$text."\n") == false)
        if (fwrite($write_file,$contents) === false)
        { PEMessage::throw_error("Couldn't write to "
              . htmlentities($filepath) . ".");
        }
        if (fclose($write_file) == false)
        { PEMessage::throw_error(
            "Couldn't close ".htmlentities($filepath)." after writing");
        }
        if (!$force_touch and filemtime($filepath) != $mtime)
        { if (touch($filepath, $mtime) === false)
            PEMessage::throw_error("Couldn't set modification time of "
              . htmlentities($filepath) . ".");
        }
      }
      if ($force_touch and file_exists($filepath))
      { if (touch($filepath) === false)
          PEMessage::throw_error("Couldn't update modification time of "
            . htmlspecialchars($filepath) . ".");
      }
    }
  }

  # When the caller provides a source file by pointing to an original file
  # on a shared filesystem: update it unless it's the same.
  public function sync_source_file_from_file($file, $orig_path, $force_touch, $request)
  { if (!file_exists($orig_path))
    { PEMessage::throw_error("Original file "
        . htmlspecialchars($orig_path) . " not found." );
    }
    $this->validate_filename($file);
    $filepath = $this->path . '/' . $file;
    if (!file_exists($filepath) or
        sha1_file($orig_path) != sha1_file($filepath))
    { $dir = preg_replace('#/[^/]+$#','',$filepath);
      PEMessage::debug_message("Sync file " . htmlspecialchars($file) . ".");
      $this->validate_filename($dir);
      if ($dir == $filepath or $dir == '')
        PEMessage::throw_error("Bad directory name "
          . htmlspecialchars($dir) );
      if (!is_dir($dir))
      { if ((file_exists($dir) or is_link($dir)) and unlink($dir) === false)
          PEMessage::throw_error("Couldn't unlink "
            . htmlspecialchars($dir) . " to make "
            . htmlspecialchars($filepath) . ".");
        if (mkdir($dir,0700,true) === false)
          PEMessage::throw_error("Couldn't create directory "
            . htmlspecialchars($dir) . ".");
      }
      if (file_exists($filepath))
        recursiveUnlink( $filepath, null, true );
      if (copy($orig_path, $filepath) === false)
        PEMessage::throw_error( "Couldn't copy "
          . htmlspecialchars($orig_path) . ' to '
          . htmlspecialchars($filepath) . '.' );
      if (!$force_touch and file_exists($filepath) and 
          filemtime($filepath) != filemtime($orig_path))
      { if (touch($filepath, filemtime($orig_path)) === false)
          PEMessage::throw_error("Couldn't set modification time of "
            . htmlentities($filepath) . ".");
      } 
    }
    if ($force_touch and file_exists($filepath))
    { if (touch($filepath) === false)
        PEMessage::throw_error("Couldn't update modification time of "
          . htmlspecialchars($filepath) . ".");
    }
  }

  public function makefiles()
  { foreach ( array('GNUmakefile', 'makefile', 'Makefile') as $mf )
      if (file_exists($this->path."/$mf"))
        return array($mf);
    return array();
  }

  public function remove_file($target, $request)
  { $this->validate_filename($target);
    $targetpath = "$this->path/$target";
    recursiveUnlink($targetpath,$request,true);
  }

  public function unlink_file($target, $request)
  { $this->validate_filename($target);
    $targetpath = "$this->path/$target";
    log_sse_message( "Unlink $target\n", $request );
    if (file_exists($targetpath) and !unlink($targetpath))
      PEMessage::throw_error("Couldn't unlink "
        .htmlspecialchars($target) . '.');
  }

  public function clear_directory($request, $del_self=false)
  { recursiveUnlink($this->path, $request, $del_self);
  }
}

class PEInPlaceProjectDirectory extends PEProjectDirectory
{
	public function validate_filename($filename) {
		# we are permissive, because we trust we're running
		# with user privileges only.  And we have to disable
		# the parent class's policy of unlinking anything that
		# isn't in the cache directory
		return true;
	}
}
