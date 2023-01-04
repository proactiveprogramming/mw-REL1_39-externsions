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
 * ProjectEngine receives an HTTP request for a project file,
 * updates the project's cached working directory from stored project data,
 * updates the target file using make if requested, serves the up-to-data
 * file contents.
 *
 * ProjectEngine.php contains global definitions for the program.
*/

/* ==== global variable values ===== */

# this is very important: where it will put the working files.
# Create this directory and make it accessible to the web server's userid.
# Consider locating it on a different partition from your system files.
$peCacheDirectory = '/var/cache/ProjectEngine';

# don't alter this, it's inferred automatically.
# the directory where this php source code is.
$peCodeDirectory = dirname(__FILE__);

# location of the PE make program, the custom one that calls the regular make.
$peCustomMake = "$peCodeDirectory/pe-make";

# number of seconds to allow make jobs before stopping them.
# if it's <= 0, time is unlimited.
$peTimeLimitForMake = 180; #300;

# set this to a positive value to use "nice" on all PE make jobs.
$peNiceValueForMake = 0;

# this is the $PATH that will be exported to all make jobs
$peExecutablePath = '/usr/local/bin:/usr/bin:/bin';

# location of the PE makefiles and related things.
$peResourcesDirectory = $peCodeDirectory.'/resources';

# the "before" makefile defines variables before the project's makefiles.
# the last definition of a variable is used.
$peMakefileBefore = $peResourcesDirectory.'/makefile-before';

# the "after" makefile defines rules after the project's makefiles.
# the first definition of a rule is used.
$peMakefileAfter = $peResourcesDirectory.'/makefile-after';

# where to put temporary files - for instance when creating a .tar.gz
# file to export a project
$peTempDirectory = '/tmp';

# if set, .make.log files will include the hostname - possibly useful 
# when jobs are distributed across a cluster
$peReportHostname = false;

# This should be false, unless your peCacheDirectory is NFS mounted.
# In that case you need to set it to true or the file locking won't work
# that prevents overlapping operations in the working directory.
# If this is set to true, you need to have the PHP DIO extension installed.
$peUseFcntl = false;

# true to allow background execution of make jobs without a time limit.
$peUseBackgroundJobs = true;

# your choices here are 'Unix' and 'SGE'
$peBackgroundJobSystem = 'Unix';

# number of seconds to cache data about present and past background jobs
# no caching if zero
$peBackgroundJobsCacheInterval = 60;

# if you don't want to use the default key - usually you do, so leave this
# set to false
$peBackgroundJobsCacheKey = false;

# if this is -1, use the same nice value as for regular make jobs,
# otherwise use this for background make jobs.
$peNiceValueForBackgroundMake = -1;

# if this is non-null, it's used as scheduling class (1, 2, or 3) for
# ionice when starting a background job
$peIoniceClassForBackgroundMake = null;

# if the above is non-null and this is as well, this is the priority
# (0-7) for ionice for background jobs.
$peIonicePriorityForBackgroundMake = null;

# if this is non-null it's the scheduling class for ionice when copying
# and merging session directories
$peIoniceClassForSessionCopies = null;

# and if this and the above are non-null, the priority for copying and merging
$peIonicePriorityForSessionCopies = null;

# if this is non-null it's the scheduling class for ionice when using tar
# and gzip to create export packages
$peIoniceClassForTar = null;

# and if this and the above are non-null, the priority for tar and gzip
$peIonicePriorityForTar = null;

# periodically we garbage-collect the directory cache and remove things
# that are probably abandoned.  This (number of seconds) controls how
# often we look through the cache for things to remove.
# (set this to 0 to disable the feature.)
$pePruneDirectoriesInterval = 24 * 60 * 60; # 1 day

# how old does a preview session get (seconds) before we garbage-collect it
# (set this to 0 to disable the feature.)
$peExpirationTimeForPreviewDirectories = 7 * 24 * 60 * 60; # 1 week

# how old does a persistent project directory get (seconds) before we
# garbage-collect it.  These directories can be exempted from garbage
# collection by creating a file called <directory name>.DONOTERASE
# (set this to 0 to disable the feature.)
$peExpirationTimeForProjectDirectories = 0; //2 * 30 * 24 * 60 * 60; # 2 months

# sometimes there are various ways to express URIs for a given project, but
# we want them to resolve to the same working directory.  Keys are patterns
# for preg_replace(), values are replacement strings.  By default, let's
# figure that accessing a wiki from https rather than http doesn't make it
# a different wiki with separate project files.
$peURISynonyms = array(
  '/^pe-ww:https:/i' => 'pe-ww:http:',
);

# When using PE as a library to compile and run things in a local directory
# from the command line, you can ask it to process files in place where they
# are.  When PE is part of a server that accepts requests from the internet,
# it must not allow that for security reasons.
$peAllowProcessingInPlace = false;

# profiling can be useful when we're running as part of MediaWiki, but
# otherwise we don't do it.
function PROFILE_IN($s)  {
  if( defined( 'MEDIAWIKI' ) ) 
  { wfProfileIn($s); }
}
function PROFILE_OUT($s) {
  if( defined( 'MEDIAWIKI' ) ) 
  { wfProfileOut($s); }
}

/* ==== include the class definitions ===== */

require_once("$peCodeDirectory/PEAPI.php");
require_once("$peCodeDirectory/PEOperation.php");
require_once("$peCodeDirectory/repositories/PERepositoryInterface.php");
require_once("$peCodeDirectory/PEDirectory.php");
require_once("$peCodeDirectory/PESession.php");
if ($peUseBackgroundJobs)
{ require_once("$peCodeDirectory/background/PEBackgroundJobs.php");
  #require_once("$peCodeDirectory/background/PEBackground"
  #  .$peBackgroundJobSystem.".php");
  require_once("$peCodeDirectory/background/PEBackgroundUnix.php");
  require_once("$peCodeDirectory/background/PEBackgroundSGE.php");
  require_once("$peCodeDirectory/background/PEBackgroundSession.php");
}

/* ===== general-purpose variables, classes and functions ===== */

# PEMessage is to record a message to the user.  Multiple messages can
# be stacked up before they are reported.  A PEException (below) includes
# a PEMessage.
#
# Important note: these messages can contain live HTML code, so the caller
# is responsible for using htmlspecialchars() on any content that might be
# dangerous.
class PEMessage
{ public $message;

  # instance functions - this is a parent class for PEError, etc.
  public function __construct($str)
  { $this->message = $str; }

  public function __toString()
  { return $this->message; }

  public function __toHTML()
  { return "<p class='" . $this->htmlclass() . "'>"
      . $this->__toString() . "</p>\n";
  }

  public function htmlclass()
  { return 'message'; }

  # now class functions, for managing a list of messages
  static $classname_lookup;
  public static function create_message($msg, $type)
  { if (!isset(self::$classname_lookup))
      self::$classname_lookup = array(
        'message'=>'PEMessage', 'error'=>'PEError', 'warning'=>'PEWarning',
        'debug'=>'PEDebugMessage' );
    return new self::$classname_lookup[$type]($msg);
  }

  public static $messages;
  public static function record_message($msg, $type='message')
  { if (is_string($msg))
      $msg = self::create_message($msg,$type);
    if (!is_array(self::$messages))
      self::$messages = array();
    self::$messages[] = $msg;
    # TURN THIS OFF except when actively debugging
    #peLog($msg->__toString());
  }

  public static function record_error($msg)
  { self::record_message($msg,'error'); }

  public static function record_warning($msg)
  { self::record_message($msg,'warning'); }

  public static function debug_message($msg)
  { self::record_message($msg,'debug'); }

  static public function throw_error($err)
  { if (is_string($err))
      $err = new PEError($err);
    PEMessage::record_message($err);
    throw new PEException();
  }

  public static function report_messages(
    $html=true, $add_before='', $add_after='')
  { if (!is_array(self::$messages) or count(self::$messages) <= 0)
      return '';
    if ($html)
    { $dbgonly = true;
      foreach (self::$messages as $msg)
      { $output .= $msg->__toHTML();
        if (!($msg instanceOf PEDebug))
          $dbgonly = false;
      }
      $output = '<fieldset class="pe-messages' . ($dbgonly ? ' debug-only':'')
        . '"><legend>ProjectEngine messages</legend>'."\n"
        . $add_before . $output . $add_after . "</fieldset>\n";
    }
    else
    { foreach (self::$messages as $msg)
        $output .= $msg;
    }
    self::$messages = array();
    return $output;
  }

  public static function report_messages_as_array()
  { $ret = array();
    if (!is_array(self::$messages) or count(self::$messages) <= 0)
      return $ret;
    foreach (self::$messages as $msg)
      $ret[] = array($msg->htmlclass(), $msg->message);
    self::$messages = array();
    return $ret;
  }
}

class PEError extends PEMessage
{ public function __toString()
  { return 'Error: ' . $this->message; }
  public function htmlclass()
  { return 'error'; }
}

class PEWarning extends PEMessage
{ public function __toString()
  { return 'Warning: ' . $this->message; }
  public function htmlclass()
  { return 'warning'; }
}

class PEDebugMessage extends PEMessage
{ public function __toString()
  { return '(debug message) ' . $this->message; }
  public function htmlclass()
  { return 'debug'; }
}

# logging for debuggging (nothing to do with SSE, below)
function peLog( $string ) {
	if ( function_exists( 'wwLog' ) ) {
		wwLog( 'ProjectEngine: ' . $string );
	} else {
		error_log( 'ProjectEngine: ' . $string );
	}
}

# throw a PEException to report an error condition.
# these are caught wherever code calls something that might fail.
class PEException extends Exception
{
}

# throw this one when you've just written out the HTTP response yourself.
# it bypasses everything that would otherwise write the usual response.
# if $succeeded is false, we do our best to produce error output.
class PEAbortOutputException extends PEException
{ public $succeeded;
  public function __construct($succ)
  { $this->succeeded = $succ; }
}

### SSE Logging functions

# where to find a logfile when given an 'sse-log-key'.
function path_for_logfile( $key ) {
	return PEDirectory::logfiles_directory()->directory_name() . '/' . $key;
	# return PEDirectory::logfiles_directory()->directory_name() . '/' . '0';
}

# have we been asked to log our progress?  If so, what's the logging key.
function sse_log_key( $request ) {
	if ( is_array( $request ) and isset( $request['sse-log-key'] ) ) {
		return $request['sse-log-key'];
	}
	return false;
}

# write a message to the sse log, if we're logging, else do nothing
function log_sse_message( $text, $request, $event = null ) {
	if ( isset( $request['log-to-stdout'] ) ) {
		if ( $event ) {
			$eventtext = "$event : $text\n";
		} else {
			$eventtext = $text;
		}
		echo $text;
	}
	if ( isset( $request['log-to-stderr'] ) ) {
		if ( $event ) {
			$eventtext = "$event : $text\n";
		} else {
			$eventtext = $text;
		}
		file_put_contents( 'php://stderr', $text );
	}
	#peLog( 'sse_log_key( ' . json_encode($request) . ' )' );
	if ( ( $key = sse_log_key( $request ) ) ) {
                if ( PEDirectory::logfiles_directory()->mkdir() === false ) {
                        PEMessage::record_error('could not create directory for logfiles');
                        return false;
                }
		$path = path_for_logfile( $key );
		peLog( 'logfile path is ' . $path );
		$fp = fopen($path, 'a');
		if ($fp === false) {
			PEMessage::record_error('could not open file');
			peLog( 'log_sse_message could not open ' . $path );
			return false;
		}
		if ( $event ) {
			$eventtext = "event: $event\n";
		} else {
			$eventtext = '';
		}
		peLog( "writing to sse logfile: " . json_encode( $text ) );
		$eventtext .= 'data: ' . str_replace( "\n", "\ndata: ", $text ) . "\n\n";
		if ( fwrite( $fp, $eventtext ) === false ) {
			PEMessage::record_error('error appending to logfile');
			peLog( 'log_sse_message could not append to ' . $path );
			return false;
		}
		if ( fclose($fp) === false ) { 
			PEMessage::record_error('error closing logfile');
			peLog( 'log_sse_message could not close ' . $path );
			return false;
		}
	}
	return true;
}

# Say you've been given a filename F, and you want to be really sure
# D/F is inside D, use is_in_directory("D/F",D).
# If F is "../sensitive_data.txt" or something, this function will 
# return false.
function is_in_directory($filename,$dirname,$follow_links=true)
{ $fr = realpathname($filename,$follow_links);
  $dr = realpathname($dirname,$follow_links);
  if (strlen($fr) < strlen($dr) || strncmp($fr,$dr,strlen($dr)))
  { return false;
  }
  return true;
}

# this is like realpath(), but works with files that don't exist
function realpathname($pathname,$follow_links=true)
{ $comps = preg_split('{/+}',$pathname);
  if (substr($pathname,0,1) == '/')
    $partial_path = '/';
  else
    $partial_path = '.';
  if ($follow_links and is_link($partial_path))
    $partial_path = realpath($partial_path);
  foreach($comps as $next_comp)
  { if ($next_comp == '.')
      continue;
    if ($next_comp == '..')
    { if ($partial_path == '.')
        $partial_path = '..';
      else if ($partial_path == '/')
        continue;
      else
        $partial_path = preg_replace('{/.*?$}','',$partial_path);
      continue;
    }
    // otherwise
    if (substr($partial_path,-1) != '/')
      $partial_path .= '/';
    $partial_path .= $next_comp;
    if ($follow_links and is_link($partial_path))
      $partial_path = realpath($partial_path);
  }
  return $partial_path;
}

# format the permissions of a file for a directory listing, 
# e.g. -rwxr-xr-x.
function permstring($perms)
{ if (($perms & 0xC000) == 0xC000) {
      // Socket
    $info = 's';
  } elseif (($perms & 0xA000) == 0xA000) {
      // Symbolic Link
    $info = 'l';
  } elseif (($perms & 0x8000) == 0x8000) {
      // Regular
    $info = '-';
  } elseif (($perms & 0x6000) == 0x6000) {
      // Block special
    $info = 'b';
  } elseif (($perms & 0x4000) == 0x4000) {
      // Directory
    $info = 'd';
  } elseif (($perms & 0x2000) == 0x2000) {
      // Character special
    $info = 'c';
  } elseif (($perms & 0x1000) == 0x1000) {
      // FIFO pipe
    $info = 'p';
  } else {
      // Unknown
    $info = 'u';
  }
      // Owner
  $info .= (($perms & 0x0100) ? 'r' : '-');
  $info .= (($perms & 0x0080) ? 'w' : '-');
  $info .= (($perms & 0x0040) ?
           (($perms & 0x0800) ? 's' : 'x' ) :
           (($perms & 0x0800) ? 'S' : '-'));

      // Group
  $info .= (($perms & 0x0020) ? 'r' : '-');
  $info .= (($perms & 0x0010) ? 'w' : '-');
  $info .= (($perms & 0x0008) ?
           (($perms & 0x0400) ? 's' : 'x' ) :
           (($perms & 0x0400) ? 'S' : '-'));

      // World
  $info .= (($perms & 0x0004) ? 'r' : '-');
  $info .= (($perms & 0x0002) ? 'w' : '-');
  $info .= (($perms & 0x0001) ?
           (($perms & 0x0200) ? 't' : 'x' ) :
           (($perms & 0x0200) ? 'T' : '-'));
  return $info;
}

function recursiveUnlink( $filename, $request, $del_self )
{ if (!is_link($filename) and is_dir($filename))
  { if (!($handle = opendir($filename)))
      PEMessage::throw_error("Couldn't open dir $filename");
    while( ($entry = readdir($handle)) !== false )
      if ($entry !== '.' and $entry !== '..')
        recursiveUnlink( $filename.'/'.$entry, $request, true );
    closedir($handle);
  }
  if ($del_self)
  { if (is_dir($filename) and !is_link($filename))
    { log_sse_message( "Rmdir $filename\n", $request );
      if (!rmdir($filename))
        PEMessage::throw_error("Couldn't rmdir $filename");
      # note this error can happen on NFS if something in the directory is
      # open in some process. NFS handles this by making little .nfs-xxx 
      # files that stop you from killing the directory.  Look out for that.
    }
    else if (file_exists($filename) or is_link($filename))
    { log_sse_message( "Unlink $filename\n", $request );
      if (!unlink($filename))
        PEMessage::throw_error("Couldn't unlink $filename");
    }
  }
}

