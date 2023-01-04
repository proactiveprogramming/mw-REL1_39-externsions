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
 * setup-chroot-trap.php
 *
 * despite being a php program, this is a command-line script, not a piece
 *  of web interface code.
 *
 * it sets up the executables and library directories you need in your 
 *  chroot directory.  This needs to be run again when the server reboots.
 *  you have to have sudo privileges to run this.  you should probably
 *  run this as sudo.
 *
 * invoked straightforwardly as
 *   php setup-chroot-trap.php
 * it puts all the files needed in the chroot directory:
 *  - individual regular files are hardlinked
 *  - directories are mirrored in there using mount --bind.
 *
 * invoked as
 *   php setup-chroot-trap.php -- --umount
 * it unmounts the mirrored directories.  This is VERY IMPORTANT to do
 * if you're going to remove the chroot directory, so you don't trash 
 * your system by erasing your texmf directories and R libraries and so on.
 *
 * for site-specific customization of the chroot environment, write a 
 * site-chroot-setup.php file, which can define an array 
 * $site_chroot_inclusions and/or modify variables from this file.
 *
 * by Lee Worden, for better or worse.
 */

# what system are we on?
$issue = file_get_contents('/etc/issue');
if (preg_match('/^Mandriva\b.*64/',$issue))
  $this_distribution = 'Mandriva-64bit';
else if (preg_match('/^Ubuntu\b.*intrepid/',$issue)) # is this one right?
  $this_distribution = 'Ubuntu-intrepid';
else if (preg_match('/^Ubuntu\b.*jaunty/',$issue) or 
        preg_match('/^Ubuntu\b.*9\.04/',$issue))
  $this_distribution = 'Ubuntu-jaunty';
else
{ trigger_error("Don't know what system we're on\nissue = $issue\n",
    E_USER_ERROR);
  exit(1);
}

$pe_base = dirname(__FILE__);
$chroot_dir = "$pe_base/chroot-trap";
$httpd_user_lookup = array(
   'Ubuntu-intrepid' => 'www-data',
   'Ubuntu-jaunty' => 'www-data',
   'Mandriva-64bit' => 'apache'
 );
$httpd_user = $httpd_user_lookup[$this_distribution];

$options = array('umount' => false, 'silent' => false);

if (in_array('--silent',$argv))
  $options['silent'] = true;
if (in_array('--umount',$argv))
  $options['umount'] = true;
if (in_array('--dry',$argv) or in_array('--dry-run',$argv))
  $options['dry-run'] = true;

# don't need to be finding executables in weird places
putenv('PATH=/usr/local/bin:/bin:/usr/bin');

# list of things that need to be in the chrooted system directories.
# executables don't need to be full paths - it will use 'which' to find 
# them, for portability.  An array of two is (source,dest). 
# Flat files listed here will be hard-linked, if possible, else copied; 
# directories will be placed using mount --bind.
$needed_files['Unix-general'] = array(
  # ProjectExtension internal files ===
  array("$pe_base/resources", '/resources'),
  # the basic unix stuff
  'rm', 'sh', 'make', 'sort', 'uniq', 'cp', 'mv', 'ln', 'echo', 'rename',
  'mkdir', 'rmdir', 'wget', 'touch', 'cat', 'sed', 'sleep', 'date',
  '/lib/*.so', '/lib/*.so.*', '/usr/lib/*.so', '/usr/lib/*.so.*',
  # /proc seems to be needed, but I worry about security?
  '/proc', '/etc/resolv.conf',
  # now the stuff for R
  'uname', 'R',
  # the stuff for convert
  'convert', 'gs',
  # the stuff for latex
  'egrep', 'perl', 'latex', 'pdftex', 'bibtex', 'dvipdf', 'dvips',
  'cmp', 'kpsewhich', 'pdflatex'
  '/usr/share/texmf', '/usr/local/share/texmf',
  '/usr/share/fonts',
  # and tex4ht
  #'tex4ht', 't4ht', 'mk4ht', 'ht', 'dvipng', 'tidy',
  # and latexml
  'latexml', 'latexmlpost', 'latexmlmath', 'ps', 'xsltproc', #'xpath',
  # gnuplot too
  'gnuplot', '/usr/share/gnuplot',
  # graphviz, woo hoo
  'dot', 'neato',
  # pango is important for both R and graphviz
  '/etc/pango', '/etc/fonts',
);

# Ubuntu-specific files
# intrepid ibex, to be precise
$needed_files['Ubuntu'] =
 array_merge($needed_files['Unix-general'], array(
  'dash',
  # now the stuff for R
  '/usr/lib/R', '/etc/R', 
  # the stuff for perl
  '/usr/share/perl', '/usr/lib/perl', '/usr/local/share/perl',
  '/usr/local/lib/perl', '/usr/share/perl5', '/usr/lib/perl5',
  # the stuff for convert
  '/usr/share/ghostscript',
  # the stuff for latex
  '/var/lib/defoma', '/var/lib/texmf', '/var/lib/tex-common',
  '/etc/texmf', '/usr/share/texmf-texlive',
  # graphviz
  '/usr/lib/graphviz', 
));

$needed_files['Ubuntu-intrepid'] =
  array_merge($needed_files['Ubuntu'], array(
    '/usr/lib/ImageMagick-6.3.7',
    ));

$needed_files['Ubuntu-jaunty'] =
  array_merge($needed_files['Ubuntu'], array(
    '/usr/lib/ImageMagick-6.4.5',
    '/usr/local/lib/R',
    ));

# Mandriva-specific files
# release 2008.1 (Official) for x86_64
$needed_files['Mandriva-64bit'] =
 array_merge($needed_files['Unix-general'], array(
  'bash', 'perl5', '/usr/bin/perl5.10.0',
  '/lib64/*.so', '/lib64/*.so.*', '/usr/lib64/*.so', '/usr/lib64/*.so.*',
  '/usr/lib/perl5',
  # now the stuff for R
  '/usr/lib64/R', '/usr/local/R',
  #'/usr/local/R/2.9.0/bin/R',
  '/usr/lib64/pango',
  # the stuff for convert
  '/etc/alternatives/gs', 'gsc',
  '/usr/lib64/ImageMagick-6.3.8',
  # the stuff for latex
  'pdflatex', #'mktexfmt',
  '/usr/share/texmf-var',
  'basename', # needed by dvipdf
  # graphviz
  '/usr/lib64/graphviz', 
  # misc
  'pdftk', #'ls',
));

$file_list = $needed_files[$this_distribution];
$site_list_file = "$pe_base/site-chroot-setup.php";
if (file_exists($site_list_file))
  require_once($site_list_file);
if (is_array($site_chroot_inclusions))
  $file_list = array_merge($file_list,$site_chroot_inclusions);

# before beginning, we learn what's mounted, so we know whether to unmount
$mounted = array();
{ $mtab_array = file('/etc/mtab');
  if ($mtab_array === false)
    trigger_error('Couldn\'t read /etc/mtab',E_USER_ERROR);
  global $mounted;
  foreach($mtab_array as $entry)
  { $columns = preg_split('/\s+/',preg_replace('/#.*$/','',$entry));
    if (count($columns) >= 2)
    { $mounted[$columns[1]] = true;
      #echo("$columns[1] is mounted\n");
    }
  }
}

//print_r($file_list);

// make all the files on the list
// each entry here can be:
//  an absolute path
//  name of a file that's in the PE directory
//  name of an executable that's on the $PATH
//  a wildcard
//  or a pair (orig, target)
//   (target should not include the chroot directory as prefix)
foreach($file_list as $file_entry)
{ if (is_array($file_entry))
    try_to_link($file_entry[0], $chroot_dir.'/'.$file_entry[1]);
  else
    foreach(glob($file_entry,GLOB_NOCHECK) as $source)
      #if ($source[0] == '/')
        try_to_link($source);
}

// === special case for /tmp ===
$pe_tmp = "/tmp/pe-tmpdir";
if (!file_exists($pe_tmp))
{ if (!$options['silent'])
    echo("mkdir $pe_tmp\n");
  if (!$options['dry-run'] && !mkdir($pe_tmp,0777))
    trigger_error("couldn't mkdir $pe_tmp",E_USER_ERROR);
  try_command("chown root.root $pe_tmp");
  try_command("chmod 1777 $pe_tmp");
}
try_to_link($pe_tmp,"$chroot_dir/tmp");


// === special case for /dev/null ===
$dest   = "$chroot_dir/dev/null";
if (!file_exists($dest))
{ $parent = preg_replace('{[^/]*$}','',$dest);
  global $options;
  if (!file_exists($parent))
  { if (!$options['silent'])
      echo("mkdir $parent\n");
    if (!$options['dry-run'] and !mkdir($parent,0755,true))
      trigger_error("couldn't mkdir $parent",E_USER_ERROR);
  }
  try_command("mknod $dest c 1 3");
  try_command("chown root.root $dest");
  try_command("chmod 0666 $dest");
}

// === before exiting, make the working directory parent and
//     protect the chroot directory from tampering ===
{ $wds = "$chroot_dir/working-directories";
  if (!file_exists($wds))
    try_command("mkdir $wds");
  try_command("chmod 700 $wds");
  try_command("chown $httpd_user.$httpd_user $wds");
}
try_command("chown $httpd_user.$httpd_user $chroot_dir");
try_command("chmod 500 $chroot_dir");
exit(0);

####### end of main code #######

// put $source into the chroot directory at $dest
// if $dest isn't given it's the same path
// for flat files, try hard-link then cp;
// for directories, do mount --bind (unmount first,
//  in case it's already been done).
function try_to_link($source,$dest=NULL)
{ global $options;
  if (!$source or $source=='/')
  { echo("Error - don't try to link /\n");
    return;
  }
  if (strstr($source,'/') == false)
  { if (file_exists("$pe_dir/$source"))
      $source = "$pe_dir/$source";
    else
    { $which = chop(`which $source`);
      if (!$which)
        trigger_error("$source not found",E_USER_ERROR);
      $source = $which;
      #if (!$options['silent'])
      #  echo("which says: $source\n");
    }
  }

  global $chroot_dir;
  if (is_null($dest))
    $dest = "$chroot_dir$source";
  #if (!$options['silent'])
  #  echo("try_to_link $source $dest\n");
  if (!file_exists($source))
    trigger_error("file $source does not exist",E_USER_ERROR);
  if (is_dir($source))
  { if (!file_exists($dest) and !$options['dry-run'] and
        !mkdir($dest,0755,true))
      trigger_error("couldn't mkdir $dest",E_USER_ERROR);
    global $mounted;
    if ($options['umount'])
    { if ($mounted[$dest])
        try_command("umount $dest");
    }
    else
    { #  echo("$dest isn't mounted\n");
      if (!$mounted[$dest])
        try_command("mount --bind $source $dest");
    }
  }
  // else assume that means it's an executable or library, or something
  // I can hard link
  else if ( (!file_exists($dest) && !is_link($dest)) ||
            (!is_dir($source) && filemtime($source) > filemtime($dest)) )
  { $parent = preg_replace('{[^/]*$}','',$dest);
    if (!file_exists($parent))
    { if (!$options['silent'])
        echo("mkdir $parent\n");
      if (!$options['dry-run'] and !mkdir($parent,0755,true))
        trigger_error("couldn't mkdir $parent",E_USER_ERROR);
    }
    if (file_exists($dest) && !is_dir($dest))
    { if (!$options['silent'])
        echo("try unlink $dest before replacing\n");
      if (!$options['dry-run'])
        unlink($dest);
    }
    if (!$options['silent'])
      echo("try link $source $dest\n");
    if ($options['dry-run'])
      $result = true;
    else
      $result = @link($source,$dest);
    if (!$result)
    { $command = "cp -d -p '$source' '$dest'";
      if (!$options['silent'])
        echo "$command\n";
      if ($options['dry-run'])
        $result = true;
      else
      { $result = system($command);
        $result = !$result;
      }
      #$perms = fileperms($source);
      #if (!$perms)
      # trigger_error("couldn't get file permissions for $source",E_USER_ERROR);
      #if (chmod($dest,$perms & 07555))
      # trigger_error("couldn't set file permissions on $dest",E_USER_ERROR);
    }
    if (!$result)
      trigger_error("couldn't link or copy $source to $dest",E_USER_ERROR);
  }
}

function try_command($command)
{ global $options;
  if (!$options['silent'])
    echo("$command\n");
  if (!$options['dry-run'])
  { system($command,$retval);
    if ($retval != 0)
      trigger_error("command $command failed",E_USER_ERROR);
  }
}

?>
