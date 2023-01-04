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

/*
 * CLI script for poking around the filesystem to understand what
 * files are locked, etc.
 *
 * command line arguments:
 * --test filename
 *     tell whether the file is locked, and if so how
 * --read-lock filename
 * --write-lock filename
 *     try to lock the file
 * --repeat
 *     keep doing it every second
 * --sleep n
 *     sleep n seconds between repeats
 * --fcntl
 *     use dio_fcntl() for locking instead of flock()
 */

$todo = array();
while (($arg = array_shift($argv)) !== null)
{ if ($arg == '--test')
    $todo[] = array('test',array_shift($argv));
  else if ($arg == '--read-lock')
    $todo[] = array('read-lock',array_shift($argv));
  else if ($arg == '--write-lock')
    $todo[] = array('write-lock',array_shift($argv));
  else if ($arg == '--sleep')
    $sleep = array_shift($argv);
  else if ($arg == '--repeat')
  { $repeat = true;
    if ( ! isset($sleep) )
      $sleep = 1;
  }
  else if ($arg == '--fcntl')
    $fcntl = true;
}
if ( ! isset($sleep) )
  $sleep = 0;
if ( ! isset($repeat) )
  $repeat = false;

while ($repeat or !$did_loop)
{ foreach ($todo as &$item)
  { list($action,$filename) = $item;
    if ($handle[$filename] !== false and $handle[$filename] !== null)
    { if ($fcntl)  dio_close($handle[$filename]);
      else         fclose($handle[$filename]);
    }
    if ($action == 'test')
    { $handle[$filename] = $fcntl ?
        dio_open($filename,O_RDWR) : fopen($filename,"w");
      if ($handle[$filename] === false)
        echo("Couldn't open $filename.\n");
      else {
        if ($fcntl)
          $status = dio_fcntl($handle[$filename], F_GETLK);
        if ($fcntl ?
            ($status['type'] == F_UNLCK) :
            (flock($handle[$filename], LOCK_EX|LOCK_NB)))
          echo("$filename is not locked.\n");
        else if ($fcntl ?
          ($status['type'] == F_RDLCK) :
          (flock($handle[$filename], LOCK_SH|LOCK_NB)))
          echo("$filename is read locked.\n");
	else if ($fcntl ?
	  ($status['type'] == F_WRLCK) : true)
          echo("$filename is write locked.\n");
      }
    }
    else if ($action == 'write-lock')
    { echo("Requesting write lock on $filename.\n");
      $handle[$filename] = $fcntl ?
        dio_open($filename,O_RDWR|O_CREAT,0777) : fopen($filename,"w");
      if ($handle[$filename] === false)
        echo("Couldn't open $filename.\n");
      $rval = ($fcntl ? 
          dio_fcntl($handle[$filename], F_SETLKW, Array('type'=>F_WRLCK)) :
          flock($handle[$filename], LOCK_EX|LOCK_NB));
      if ($rval === 0)
        echo("$filename is write locked.\n");
      else
        echo("Couldn't write lock $filename ($rval).\n");
    }
    else if ($action == 'read-lock')
    { echo("Requesting read lock on $filename.\n");
      $handle[$filename] = $fcntl ?
        dio_open($filename,O_RDWR|O_CREAT,0777) : fopen($filename,"w");
      if ($handle[$filename] === false)
        echo("Couldn't open $filename.\n");
      $rval = ($fcntl ? 
          dio_fcntl($handle[$filename], F_SETLKW, Array('type'=>F_RDLCK)) :
          flock($handle[$filename], LOCK_NB));
      if ($rval === 0)
        echo("$filename is read locked.\n");
      else
        echo("Couldn't read lock $filename ($rval).\n");
    }
    else
      echo("action '$action' not implemented.\n");
  }
  echo("\n");
  $did_loop = true;
  if ($sleep > 0)
    sleep($sleep);
}

?>
