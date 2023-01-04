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

# ===== some general purpose functions =====

/**
 * wwfDynamicDisplayInEffect() 
 *
 * true if WW is emitting placeholder div elements, to be filled in with
 * project file content later by javascript calls.
 *
 * false if we are doing the older style of static output, by making and
 * retrieving all project files while assembling the HTML output page.
 *
 * It's true unless 
 *  * it's disabled by a URL parameter or cookie,
 *  * the user disabled it by preferences,
 *  * or $wwEnableAsynchronousProjectFiles is false.
 */
function wwfDynamicDisplayInEffect() {
	global $wwEnableDynamicProjectFiles, $wgUser, $wgRequest;
	if ( ! $wwEnableDynamicProjectFiles ) {
		#wwLog( 'no dynamic display: globally disabled' );
		return false;
	}
	if ( $wgRequest->getVal( 'ww-static-files', false ) ) {
		#wwLog( 'no dynamic display: URL' );
		return false;
	}
	if ( $wgUser->getOption( 'ww-dynamic-display' ) == 'never' ) {
		#wwLog( 'no dynamic display: preferences' );
		return false;
	}
	if ( $wgRequest->getcookie( 'WorkingWiki.no.js', '' ) ) {
		#if ( ! defined( 'MW_API' ) ) {
		#	wwLog( 'no dynamic display: cookie' );
		#}
		return false;
	}
	#wwLog( 'dynamic display in effect' );
	return true;
}

function wwfStaticLinkToCurrentPage( &$parser ) {
	$wgTitle = $parser->getTitle();
	return $wgTitle->getLocalUrl( array( 'ww-static-files' => 1 ) );
}

function wwfGetTimeLimitForMakeJobs() {
	global $peTimeLimitForMake;
	if ( isset( $peTimeLimitForMake ) ) {
		return $peTimeLimitForMake;
	}
	$ret = ProjectEngineConnection::call_project_engine( 'query-time-limit', null );
	return $ret['time-limit'];
}

/*
 * We give MathJax to all browsers with our mathml output, except where
 * the user's preferences say to use the native mathml rendering on their
 * browser, and they're actually using a FireFox browser that has native
 * mathml rendering.
 */
function wwfUseMathJax()
{ if ( ! isset( $_SERVER['HTTP_USER_AGENT'] ) )
    return true;
  global $wgUser;
  if ( ( $wgUser->getOption( 'mathml' ) or
         ( defined('MW_MATH_MATHML') and
           $wgUser->getOption( 'math' ) == MW_MATH_MATHML ) )
      and preg_match( '{Firefox/(\d+)}', $_SERVER['HTTP_USER_AGENT'], $matches )
      and $matches[1] >= 4 )
  { #wwLog( "User agent is '" . htmlspecialchars($_SERVER['HTTP_USER_AGENT'])
    #  . "', using native MathML support" );
    return false;
  }
  return true;
}

# this is used in evaluating arguments to things like for instance, 'make=yes'.
# it returns true if value is 'yes','y','1','true','t',
# false if it's 'no','n','0','false','f'.
function wwfArgumentIsYes( $value )
{ return preg_match('/^(n|no|f|false|0|)$/i', $value) ? false : true;
}

# this is like realpath(), but works with files that don't exist
function wwfRealpath($pathname,$follow_links=true)
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

# Say you've been given a filename F, and you want to be really sure
# D/F is inside D, use wwfIsInDirectory("D/F",D).
# If F is "../sensitive_data.txt" or something, this function will 
# return false.
function wwfIsInDirectory($filename,$dirname,$follow_links=true)
{ $fr = wwfRealpath($filename,$follow_links);
  $dr = wwfRealpath($dirname,$follow_links);
  if (strlen($fr) < strlen($dr) || strncmp($fr,$dr,strlen($dr)))
  { wwLog("$fr is not in $dr");
    return false;
  }
  return true;
}

# true if the final part of the string is the extension mentioned
function wwfSuffixMatches($string, $extension)
{ $len = strlen($extension);
  return substr($string,-$len) == $extension;
}

# function to convert '2048' to '2Kb' etc.
# adapted liberally from Drupal's format_size(), which is GPL v2 or later:
# http://api.drupal.org/api/function/format_size/6
function format_size($size)
{ global $wwContext;
  $suffixes = array('','ww-filesize-KB','ww-filesize-MB','ww-filesize-GB',
                    'ww-filesize-TB');
  $power = 0;
  while ($size >= 1024 and $power < count($suffixes) - 1)
  { $size = round($size / 1024, 1);
    ++$power;
  }
  if ($power == 0)
    return $size;
  else
    return $size . $wwContext->wwInterface->message($suffixes[$power]);
}

# a time long ago, used in comparing timestamps to make sure
# something gets updated.
function epoch()
{ return '19700101000000';
}

/**
* Search a directory (recursively) for files
*
* @param $dir Path to directory to search
* @return mixed Array of relative filenames on success, or false on failure
*/
function wwfFindFiles( $dir ) {
  if( is_dir( $dir ) ) {
    if( $dhl = opendir( $dir ) ) {
      $files = array();
      while( ( $file = readdir( $dhl ) ) !== false ) {
        if ( $file == '.' || $file == '..' ) {
          continue;
        }
        $path = $dir . '/' . $file;
        if( is_dir ( $path ) ) {
          $files_within = wwfFindFiles( $path );
          if ( is_array($files_within) && count($files_within) > 0 )
            foreach ( $files_within as $file_within )
              $files[] = $file . '/' . $file_within;
        } else if( is_file( $path ) ) {
          $files[] = $file;
        }
      }
      return $files;
    } else {
      return false;
    }
  } else {
    return false;
  }
}

function wwfRecursiveUnlink( $filename, $del_self )
{ global $wwContext;
  if (!is_link($filename) and is_dir($filename))
  { if (!($handle = opendir($filename)))
      $wwContext->wwInterface->throw_error("Couldn't open dir $filename");
    while( ($entry = readdir($handle)) !== false )
      if ($entry !== '.' and $entry !== '..')
        wwfRecursiveUnlink( $filename.'/'.$entry, true );
  }
  if ($del_self)
  { if (is_dir($filename) and !is_link($filename))
    { if (!rmdir($filename))
        $wwContext->wwInterface->throw_error("Couldn't rmdir $filename");
    }
    else if (file_exists($filename))
    { if (!unlink($filename))
        $wwContext->wwInterface->throw_error("Couldn't unlink $filename");
    }
  }
}

# return true if we shouldn't offer interface options that edit the wiki
function wwfReadOnly()
{ global $wgUser;
  if ( ! function_exists( 'wfReadOnly' ) ) { # in wmd.php, say
	  return true;
  }
  return ( wfReadOnly() or ! $wgUser->isAllowed('edit') );
}

# return true if we can do edits on this page, e.g. inserting a 
# source-file element or modifying a project-description.
# return false if the wiki's marked read-only or the user isn't allowed to
# edit the page or such
# messages will be recorded in $details, unless it's null, in which case
# they'll be reported via the WWInterface class.
function wwfOKToEditPage( $page, &$details )
{ global $wwContext;
  $msgs = array();
  if (is_object($page))
    $title = $page;
  else
    $title = Title::newFromText($page);
  switch (1) # this is strange, but why not?
  { default: # it allows me to use break.
    if (!$title)
    { $msgs[] = "Internal error: bad page title '$page'.";
      break;
    }
    if (wfReadOnly())
    { $msgs[] =
        "Can't do any edits because wiki is read-only.";
      break;
    }
    global $wgUser;
    if ( ($permErrors = $title->getUserPermissionsErrors( 'edit', $wgUser )) 
        or !$wgUser->isAllowed('edit') )
    { $msgs[] = "Not permitted to edit page ‘{$title}’.";
      foreach ($permErrors as $per)
      { $key = array_shift($per);
        $msgs[] = wfMsgReal($key,$per,true);
      }
      break;
    }
    if ( $wgUser->isBlockedFrom( $title, false ) )
    { $msgs[] = "User is blocked from editing ‘{$title}’.";
      break;
    }
    if ( $wgUser->pingLimiter() )
    { $msgs[] = wfMsg( 'actionthrottledtext' );
      break;
    }
    //if ( $this->wasDeletedSinceLastEdit() )
    { // to do
    }
    $aid = $title->getArticleID( /*GAID_FOR_UPDATE*/ );
    if ( 0 == $aid )
    { if ( !$title->userCan( 'create' ) )
      { $msgs[] =
          "Not permitted to create page ‘{$title}’.";
        break;
      }
    }
  }
  if ( count($msgs) > 0 )
  { if ( $details !== null )
      foreach ($msgs as $msg)
        $details[] = array( $msg );
    else
      foreach ($msgs as $msg)
        $wwContext->wwInterface->record_error( $msg );
    return false;
  }
  return true;
}

function wwfHeadersForFile( $fname, $mtime, $attachment=true )
{ $headers = array();
  
  // Infer the browser
  if (isset($_SERVER['HTTP_USER_AGENT'])) {
    $userAgent = strtolower($_SERVER['HTTP_USER_AGENT']);
    if (preg_match('/opera/', $userAgent)) {
        $browser = 'opera';
    }
    elseif (preg_match('/webkit/', $userAgent)) {
        $browser = 'safari';
    }
    elseif (preg_match('/msie/', $userAgent)) {
        $browser = 'msie';
    }
    elseif (preg_match('/mozilla/', $userAgent) && !preg_match('/compatible/', $userAgent)) {
      $browser = 'mozilla';
    }
    #wwLog("UserAgent: $userAgent, browser: $browser");
  }
  if (!isset($browser)) {
      $browser = 'other';
  }
  wwLog("inferred browser type: $browser");

  // wfGetType() is not good with HTML, so we compensate
  if (preg_match('/\.html$|\.htm$|\.html5/i',$fname))
    $type = 'text/html';
  else if (preg_match('/\.xhtml$|\.xht$/i',$fname))
    $type = 'application/xhtml+xml';
  // catch the css case just for speed
  else if (preg_match('/\.css$/i',$fname))
    $type = 'text/css';
  // not sure about javascript
  else if (preg_match('/\.js$/i',$fname))
    $type = 'text/javascript';
  else if (preg_match('/\.json$/i',$fname))
    $type = 'application/json';
  else if (preg_match('/\.jsonp$/i',$fname))
    $type = 'text/javascript';
  else if (function_exists('wfGetType'))
  # note if wfGetType() becomes all sophisticated about looking inside
  # files it won't be able to do that here, because we don't have the 
  # actual file path
    $type = wfGetType( $fname );
  else if (method_exists('StreamFile', 'contentTypeFromPath'))
    $type = StreamFile::contentTypeFromPath( $fname );
  if ( ! $type or $type=="unknown/unknown")
  { $i = strrpos( $fname, '.' );
    $ext = strtolower( $i ? substr( $fname, $i + 1 ) : '' );
    if ( $ext )
    { $magic = MimeMagic::singleton();
      $type = $magic->guessTypesForExtension( $ext );
      #wwLog( "detectMimeType returns $type for ext $ext\n" );
    }
  }
  if ( ! $type )
    $type = 'application/x-wiki';
  #wwLog( getmypid() . " serving raw file $fname as type $type\n");

  $cdisp = ($attachment ? 'attachment' : 'inline');
  #wwLog("cdisp is $cdisp");
  if ($browser == 'safari') {
    $headers[] = "Content-Type: $type";
    $headers[] = "Content-Disposition: $cdisp;filename=\""
      . urlencode( basename( $fname ) ) . "\"";
  }
  elseif ($browser == 'msie') {
    $headers[] = "Content-Type: application/x-unknown";
    $headers[] = "Content-Disposition: $cdisp;filename="
      . urlencode(str_replace(' ','_',basename($fname)));
  }
  else {
    $headers[] = "Content-Type: $type";
    global $wgContLanguageCode;
    $headers[] = "Content-Disposition: $cdisp;filename*=utf-8'"
      . "$wgContLanguageCode'" . urlencode( basename( $fname ) );
  }
  # note, calling code must also provide Last-Modified: or the
  # cache control won't work.
  $headers[] = 'Cache-control: must-revalidate'; #', post-check=0, pre-check=0';
  $headers[] = 'Expires: Thu, 01 Jan 1970 00:00:00 GMT';
  return $headers;
}

function wwfHTTPError( $code, $filename='', $messages='' )
{ header( 'Cache-Control: no-cache' );
  header( 'Content-Type: text/html; charset=utf-8' );
  $encScript = htmlspecialchars( $_SERVER['SCRIPT_NAME'] );
  if ( $code == 404 )
  { header( 'HTTP/1.0 404 Not Found' );
    $encFile = htmlspecialchars( $filename );
    echo "<html><body>
<h1>File not found</h1>
<p>Although this PHP script ($encScript) exists, the project file requested 
($encFile) does not.</p>
$messages</body></html>
";
  } else if ( $code == 500 )
  { header( 'HTTP/1.0 500 Internal Error' );
    echo "<html><body>
Internal error in script $encScript.
$messages</body></html>
";
  } else if ( $code == 200 )
  { header( 'HTTP/1.0 200 OK' );
    echo "<html><body>
$messages</body></html>
";
  } else
  { header( 'HTTP/1.0 500 Unknown Error' );
    echo "<html><body>
Unknown internal error in script $encScript.
$messages</body></html>
";
  }
}

# same as MW's wfStreamFile(), except that the Content-Disposition may be
# attachment, rather than inline.
function wwfStreamFile( $fname, $headers = array(), $content = null,
    $attachment = true) 
{ if ($content === null)
  { $stat = @stat( $fname );
    if ( !$stat ) {
      wwfHTTPError( 404, $fname );
      return false;
    }
    list($size, $mtime) = array($stat['size'], $stat['mtime']);
  }
  else
  { $size = strlen($content);
    $mtime = time();
  }

  wwfStream( $fname, $headers, $content, $size, $mtime, $attachment );
  return true;
}

function wwfStream( $fname, $headers, $content, $size, $mtime, $attachment )
{
  // Cancel output buffering and gzipping if set
  wfResetOutputBuffers();

  foreach ( wwfHeadersForFile( $fname, $mtime, $attachment ) as $header ) {
    header( $header );
  }

  foreach ( $headers as $header ) {
    header( $header );
  }

  if ( !empty( $_SERVER['HTTP_IF_MODIFIED_SINCE'] ) ) {
    $modsince = preg_replace( '/;.*$/', '', $_SERVER['HTTP_IF_MODIFIED_SINCE'] );
    $sinceTime = strtotime( $modsince );
    if ( $mtime <= $sinceTime ) {
      wwLog("returning not-modified header, because $mtime < $sinceTime.");
      header( "HTTP/1.0 304 Not Modified" );
      return;
    }
  }

  header( 'Last-Modified: ' . gmdate( 'D, d M Y H:i:s', $mtime ) . ' GMT' );
  header( 'Content-Length: ' . $size );

  if ($content !== null)
    echo($content);
  else
    readfile( $fname );
}

# the output of report_errors() that works on a plain wiki page doesn't
# work on a special page, probably because it isn't passed through the
# parser.  Links are especially bad, because they need to contain &amp;
# not plain &, or they produce an XML parse error.  Here we correct that.
# note we are now (?) using this for all pages, not just special pages,
# now that we're injecting the html after the Tidy step rather than before.
function wwfSanitizeForSpecialPage( $html )
{ #wwLog("wwfSanitizeForSpecialPage: [$html] -> ");
  $html = preg_replace( '/\&([^;]{6}|[^;]{0,5}$)/', '&amp;$1', $html );
  #$html = preg_replace( '/[\f\x80-\xff]/', '&#9744;', $html );
  #$html = str_replace("\f",' ',$html); # form feed absolutely not allowed
  #wwLog(" [$html]");
  return $html;
}

# clear out some unwanted invisible characters that sometimes arrive
# in GET inputs
function wwfSanitizeInput( $value )
{ $value = (string)$value;
  $sval = str_replace(array("\f", # form feed - very bad in XHTML
      "\xFF","\xFE",   # UTF-16 BOM, in either order
      "\xEF\xBB\xBF",  # UTF-8 BOM
      "\xE2\x80\x8E",  # left-to-right mark
      "\xE2\x80\x8F"   # right-to-left mark
        ), '', $value );
  # remove any non-unicode stuff
  ini_set('mbstring.substitute_character','?');
  $sval = mb_convert_encoding( $sval, 'UTF-8', 'UTF-8' );
  if ($sval !== $value)
  { $pval = $sval;
    if (strlen($pval > 20))
      $pval = substr($pval,0,20).'...';
    //$wwContext->wwInterface->record_message("FYI - something invisible was removed "
    //    . "from '" . htmlspecialchars($pval) . "'");
    //wwLog("before invisible removal it was '$value'");
  }
  return $sval;
}

# both Special:ImportProjectFiles and Special:ManageProject use this
# sequence, which provides the JavaScript routine to suggest page locations
# for project files.  This function both attaches suggest.js to the output 
# and produces some javascript code to configure it.
# tested only for use in Special: pages.
function wwfIncludeSuggestJS($optionaljs='')
{ global $wwContext;
  global $wwImportImageExtensions;
  $imgNs = MWNamespace::getCanonicalName( NS_IMAGE );
  global $wgCapitalLinks;
  $cap = wfBoolToStr( $wgCapitalLinks );
  global $wwImportAsImageByDefault, $wwImportImageExtensions, 
    $wwImportTextExtensions;
  if ($wwImportAsImageByDefault)
    $lookup = 
      "wgTextLookup = { " . implode(':1, ',$wwImportTextExtensions) . ":1 };";
  else
    $lookup = 
      "wgImageLookup = { " . implode(':1, ',$wwImportImageExtensions) . ":1 };";
  $ibd = wfBoolToStr( $wwImportAsImageByDefault );
  global $wgOut;
  $wgOut->addScript( "<script type=\"text/javascript\">
wgCapitalize = {$cap};
wgImportAsImageByDefault = {$ibd};
$lookup
wgImageNamespace = '$imgNs';
$optionaljs</script>" );
  # including the script via GetProjectFile just isn't working because of
  # some bullshit with & and &amp;.
  $noparser = null;
  $wwContext->wwInterface->include_js( 'suggest', $noparser );
}

# the same object as above, but translated to javascript for use in the
# client-side interactive interface (both ImportProjectFiles and 
# ManageProject).
function wwfMakeJSONArray( $array )
{ return json_encode($array);
  $json = '';
  if (is_array($array))
    foreach ($array as $key=>$val)
      $json .= "'".htmlentities($key)."':'".htmlentities($val)."', ";
  return '{ '. preg_replace('/, $/','',$json). '};';
}

# the page suggestions are kind of complex.  Also, they're used in 
# php code in importProjectFiles.php and by JavaScript code in the
# ImportProjectFiles and ManageProject pages.
# Suppose we're importing from a project called AA, exported from some 
# other wiki, into a project called BB.  Source file X was originally on 
# page "Idiosyncratic page name derived from AA and X", but the user just
# told us to import that page as "My weird project BB, file W".
# Then when it's time to import file X the user says it's going to be
# called W in the new project, and we want to offer the correct page name
# as the default.
# There are several relevant sources of data: the page locations in 
# project AA, the page locations in project BB, in the case it's already
# an existing project, and the user's page renames.
# When importing file X:
# * does the local project already have a place for it
# * did the external project have a place for it, and if so, has it been
#   renamed
# * is there a default location, and has that been renamed (?)
#
# To do this, we record the old project's page locations, and the new 
# project's page locations, and which pages are being renamed.
function wwfMakePageLocations(&$project)
{ $projectname = $project->project_name();
  $pl = array();
  foreach ($project->project_files as &$pfentry)
  { $filename = $pfentry['filename'];
    unset($location);
    if (isset($pfentry['source']) and $pfentry['source'] 
          and isset($pfentry['page']))
      $location = $pfentry['page'];
    else if (isset($pfentry['archived']) and is_array($pfentry['archived']))
      foreach ($pfentry['archived'] as $key=>$t)
      { $location = $key; break; }
    if (isset($location))
      $pl[$filename] = str_replace($projectname, '?P',
        str_replace($filename, '?F', $location));
  }
  return $pl;
}

# this actually makes a suggestion based on all that stuff.  It's the
# same as the javascript suggestPage() function in resources/js/suggest.js,
# so any changes made here should be made there too.  This one is used by
# importProjectFiles.php.
function wwfGeneratePageSuggestion(
  $orig_filename, $new_filename, &$old_locations, $old_projectname, 
  &$new_locations, &$new_projectname, &$renames)
{ global $wwContext;
  wwLog("Generate a suggestion for file $new_filename...");
  # does the file have an explicit place in the new project
  $suggestion = $new_locations[$new_filename];
  if ($suggestion != '')
  { $location = str_replace(array('?P','?F'),
      array($new_projectname, $new_filename), $suggestion);
    wwLog("Found location $location in new project.");
    return $location;
  }
  # did the file have an explicit place in the old project
  $suggestion = $old_locations[$orig_filename];
  if ($suggestion != '')
  { # if so, is it a page that's being renamed?
    $old_loc = str_replace(array('?P', '?F'),
      array($old_projectname, $orig_filename), $suggestion);
    if (is_array($renames) and isset($renames[$old_loc]))
    { wwLog("Found location $old_loc in old project, renamed to "
        . $renames[$old_loc] . ".\n");
      return $renames[$old_loc];
    }
    $new_loc = str_replace(array('?P','?F'),
      array($new_projectname, $new_filename), $suggestion);
    wwLog(wordwrap("Found location $old_loc in old project. In the new "
      . "project that translates to $new_loc.\n",70));
    return $new_loc;
  }
  # or, maybe it was at a default location in the old project, and
  # that page is being renamed
  $defaults = $wwContext->wwStorage->default_locations();
  if (($slash = strrpos($orig_filename,'/')) !== false)
    $leaf = substr($orig_filename,$slash+1);
  else
    $leaf = null;
  foreach ($defaults as $def)
  { $def_loc = str_replace(array('?P','?F'), 
      array($old_projectname, $orig_filename), $def);
    if ($def_loc == '') continue;
    $title = Title::newFromText($def_loc);
    if ($title->getNamespace() == NS_IMAGE)
      $def_loc = str_replace('/','$',$def_loc);
    $locations[] = $def_loc;
    if ($leaf !== null)
    { $leaf_loc = str_replace(array('?P','?F'),
        array($old_projectname, $leaf), $def);
      if ($title->getNamespace() == NS_IMAGE)
        $leaf_loc = str_replace( ':', '$', str_replace('/','$',$leaf_loc) );
      if ($leaf_loc != $def_loc)
        $locations[] = $leaf_loc;
    }
  }
  foreach ($locations as $def_loc)
  { #wwLog("check default location $def_loc");
    if ($renames[$def_loc])
    { wwLog("Found default location $def_loc in old project, "
        . "being renamed to {$renames[$def_loc]}.");
      return $renames[$def_loc];
    }
  }
  # could check if a page is being renamed to one of the many possible
  # default locations for the file in the new project, but why?
  # give the default suggestion.
  $location = wwfDefaultSuggestion($new_filename, $new_projectname);
  wwLog("No particular location found, using the default suggestion $location.");
  return $location;
}

function wwfDefaultSuggestion($filename, $projectname)
{ global $wwImportImageExtensions;
  $looks_like_image = false;
  foreach ($wwImportImageExtensions as $ext)
    if (wwfSuffixMatches($filename, $ext))
    { $looks_like_image = true; break; }
  if ($looks_like_image)
    return MWNamespace::getCanonicalName( NS_IMAGE ) . ':' .
      preg_replace('/\//','$', "$projectname\$$filename");
  else
    return "$projectname";
  #  return "$projectname/$filename";
}

# in older versions of MW, you make an <input type="hidden"> by calling
# Xml::hidden().  In newer versions it's changed to Html::hidden().  So
# for compatibility, we call one or the other.
function wwfHidden( $name, $value, $attribs = array() ) 
{ if (method_exists('Html', 'hidden'))
    return Html::hidden( $name, $value, $attribs );
  else
    return Xml::hidden( $name, $value, $attribs );
}

# make an HTML table representing a directory listing from a project directory.
# this is used by Special:GetProjectFile directly, when producing a listing
# page, and also by the ww-list-directory api, when the client-side JS is
# updating a listing page.
function wwfHtmlDirectoryListing( $files, $dirname, $project, $allowActions ) {
  global $wwContext;
  $html = '';
  # make table of file description entries
  $html .= "<table class=\"ww-gpf-ls\">\n";
  if (count($files) > 0)
  { if (ksort($files, SORT_STRING) === false)
      $wwContext->wwInterface->record_error( "Couldn't sort directory contents for "
        . "some reason." );
    
    # phooey, firefox doesn't implement <col/>
    $html .= '<tr><th class="perms">'
      .$wwContext->wwInterface->message('ww-permissions').'</th><th class="size">'.$wwContext->wwInterface->message('ww-filesize')
      .'</th><th class="mtime">'.$wwContext->wwInterface->message('ww-mod-time')
      .'</th><th class="filename">'.$wwContext->wwInterface->message('ww-filename').'</th>'
      . ($allowActions? '<th class="actions"></th>':'') 
      ."</tr>\n";
    global $wgUser;
    foreach ($files as $file=>$listing)
    { if ($dirname == '.')
      { if ($file == '..')
          continue;
        else
          $filename = $file;
      }
      else
      { if ($file == '..')
        { $filename = (strstr($dirname,'/') === false) ? '.' :
            preg_replace('|/.*?$|','',$dirname);
        }
        else
          $filename = $dirname.'/'.$file;
      }
      list($perms, $size, $modtime) = $listing;
      global $wgLang;
      $modtime = $wgLang->timeanddate($modtime,true);
      $size = format_size($size);
      $html .= "  <tr><td class='perms'>$perms</td>"
        ."<td class='size'>$size</td><td class='mtime'>$modtime</td>";
      $html .= "<td class='filename'><a href=\""
        . $wwContext->wwInterface->get_project_file_base_url( $project, $filename, false )
        . "\">" . htmlspecialchars($file) . "</a></td>";
      if ( $allowActions and $file != '..')
      { global $wgScript, $wgTitle;
        $is_source = $project->is_file_source($filename);
        $std_hiddeninputs = 
          wwfHidden( 'title', $wgTitle->getPrefixedDBKey() )
          . wwfHidden( 'project', 
              htmlspecialchars($project->project_name()) ); 
        wwRunHooks('WW-HiddenActionInputs', array(&$std_hiddeninputs));
        $html .= "<td class='actions'>";
        $mlpos = strrpos($filename,'.make.log');
        if ($mlpos === false 
            or $mlpos + strlen('.make.log') != strlen($filename))
        { $html .= 
            "<form action='$wgScript' class='ww-inline-form'>"
            . $std_hiddeninputs;
          $sync_ok = $wwContext->wwStorage->ok_to_sync_source_files();
          if ($is_source)
	  { $wwlink = array(
		  'action' => 'ww-sync-file',
		  'filename' => htmlspecialchars($filename),
		  'content' => false,
		  'project' => htmlspecialchars($project->project_name()),
	    );
	    wwRunHooks( 'WW-Api-Call-Arguments', array( &$wwlink ) );
            $attrs = array(
		      'name' => 'ww-action',
		      'onClick' => 'wwlink(event,' . json_encode($wwlink) . ')'
		);
	        if ( ! $sync_ok ) {
			$attrs['disabled'] = 'disabled';
		}
            $subbut = XML::submitButton( 'sync', $attrs );
            $html .=
              wwfHidden( 'ww-action-filename', $filename )
              . wwfHidden( 'filename', $dirname )
              . $subbut;
          }
          else
            $html .=
              wwfHidden( 'filename', $filename )
              . wwfHidden( 'make', 'yes' )
              . XML::submitButton( 'make', array( 'name'=>'submit' ) );
          $html .= "</form>";
        }
        if (!$is_source)
        { $html .= 
            "<form action='$wgScript' class='ww-inline-form'>"
            . $std_hiddeninputs
            . wwfHidden( 'filename', $dirname )
            . wwfHidden( 'wdonly', 1 )
            . wwfHidden( 'ww-action', 'remove-and-remake' )
            . wwfHidden( 'ww-action-filename', $filename )
            . wwfHidden( 'ww-action-project', $project->project_name() )
	    . XML::submitButton(
		    'rm/make',
		    array(
			    'name' => 'submit',
			    #'onClick' => 'wwlink(event)'
		    )
	    )
            . '</form>';
        }
	$wwlink = array(
		'action' => 'ww-remove-file',
		'project' => htmlspecialchars($project->project_name()),
		'filename' => htmlspecialchars($filename),
		'wdonly' => 1,
	);
        wwRunHooks( 'WW-Api-Call-Arguments', array( &$wwlink ) );
        $html .= "<form action='$wgScript' class='ww-inline-form'>"
          . $std_hiddeninputs
          . wwfHidden( 'filename', $dirname )
	  .  wwfHidden( 'ww-action',
		  ($is_source ? 'remove-source-file' : 'remove-project-file' ) ) 
          . wwfHidden( 'ww-action-filename', $filename )
          . wwfHidden( 'ww-action-project', $project->project_name() )
          . wwfHidden( 'ww-action-wdonly', 1 )
	  . XML::submitButton( 'remove',
		  array(
			  'name' => 'submit',
			  'onClick' => 'wwlink(event, ' . json_encode($wwlink) . ')'
		  )
	  )
          . '</form>';
        $html .= "</td>";
      }
      $html .= "</tr>\n";
    }
  } 
  $html .= "</table>\n";
  return $html;
}

# HTML code for update-prerequisite-projects forms on ManageProject.  This
# output includes both the list of prerequisites (with update/remove forms)
# and the add-new-prerequisite form.
function wwfHtmlPrerequisiteInfo( $project )
{
    global $wwContext, $wgScript;
    $html = '';
    $html .= "<div class='ww-dependencies-section'>";
    $html .= "<h4 class='ww-mpf-h4'>"
      . $wwContext->wwInterface->message( "ww-prerequisite-projects" )
      . "</h4>\n";
    $html .= "<div class='ww-manageprerequisites'>\n";
    $html .= "  <div class='row heading-row'>"
        . "<div class='prerequisite'>"
        . $wwContext->wwInterface->message('ww-prerequisite-name')
        . "</div><div class='varname'>"
        . $wwContext->wwInterface->message('ww-prerequisite-project-dir-var')
        . "</div><div class='readonly'>"
        . $wwContext->wwInterface->message('ww-prerequisite-readonly')
        . "</div></div>\n";
    $hiddeninputs =
      wwfHidden( 'title', 'Special:ManageProject' )
      . wwfHidden( 'project', $project->project_name() )
      . wwfHidden( 'ww-action-project', $project->project_name() );
    wwRunHooks( 'WW-HiddenActionInputs', array( &$hiddeninputs ) );
    $readonly = wwfReadOnly();
    $prereq_index = 0;
  if ( count( $project->depends_on ) ) {
    foreach ($project->depends_on as $depname=>$depinfo)
    { try {
        $pq = $wwContext->wwStorage->find_project_by_name($depname);
      } catch (WWException $ex)
      { }
      $html .= "  <form action='$wgScript' class='ww-inline-form row update-row'>"
	  . "<span class='filename'>"
          . $wwContext->wwInterface->make_manage_project_link( $depname, $depname,
              '', $depinfo['readonly'] )
          . "</span><span class='varname'>"
          . "<tt><input name='ww-action-varname' value='"
          . ( ($depinfo['varname'] == '' && $pq != null) ?
                htmlentities(ProjectDescription::default_varname($pq->project_name())) :
                htmlentities($depinfo['varname']) )
          . "' title=\"Variable name to be used in make operations\""
          . ($readonly ? " disabled='disabled'" : '' )
          . "/></tt></span>"
          . "<span class='readonly'>"
          . wwfHidden( 'prerequisite', $depname )
          . "<input type='checkbox' name='ww-action-readonly'"
          . " id='wpReadOnly$prereq_index' title=\"The prerequisite project"
          . " is safe from being changed by this project's actions.\""
          . ($depinfo['readonly'] ? " checked='checked'" : '')
          . ($readonly ? " disabled='disabled'" : '')
          . "/><label for='wpReadOnly$prereq_index'>"
          . $wwContext->wwInterface->message('ww-prerequisite-is-readonly')
          . "</label></span>";
        if (!$readonly)
        { $html .= "<span class='ww-actions'>"
            . wwfHidden( 'ww-action', 'update-prerequisite' )
            . $hiddeninputs
            . wwfHidden( 'ww-action-prerequisite', $depname )
            . "<input type='submit' name='update' value='update'"
            . " id='ww-update-prereq-$prereq_index' class='update'"
            . " title=\"Update the project's variable name and read-only status\"/>"
            . "<input type='submit' name='remove' value='remove'"
            . " id='ww-update-prereq-$prereq_index' class='remove'"
	    . " title=\"Remove the project from the list of prerequisites\"/>"
	    . "</span>";
	} 
        $html .= "</form>";
        if (!$readonly)
          $html .= "<script type='text/javascript'>"
            . "document.getElementById('ww-update-prereq-$prereq_index').disabled=1;</script>\n";
        ++$prereq_index;
      }
    }

    if ( ! $readonly and
            $project->project_page() and
          wwfOKToEditPage( Title::newFromText($project->project_page()), $details ) )
    { $html .= "  <form action='$wgScript' class='row add-row'>"
	. "<span class='prerequisite'>";
      $html .= $hiddeninputs;
      $html .= wwfHidden( 'ww-action', 'set-prerequisite' )
        . "<input type='text' name='ww-action-prerequisite'"
	. " title='Name or URI of prerequisite project to add.'"
        . " value='' id='ww-add-prereq-name'/></span>\n";
      $html .= "      <span class='varname'>"
        . "<input type='text' name='ww-action-varname'"
        . " title='Variable name to be used in make operations'"
        . " value=''/></span>\n";
      $html .= "      <span class='readonly'>"
        . "<input type='checkbox' name='ww-action-readonly'"
	. " id='wpReadOnly$prereq_index'"
        . " title=\"The prerequisite project"
        . " is safe from being changed by this project's actions.\">"
        . "<label for='wpReadOnly$prereq_index'>"
        . $wwContext->wwInterface->message('ww-prerequisite-is-readonly')
        . "</label></span>\n";
      $html .= "      <span class='ww-actions'>"
        . " <input type='submit' name='button' class='add'"
        ." value='add' title=\"Add prerequisite project\"/></td>\n";
      $html .= "  </span>\n</form>\n";
    }
  $html .= "</div></div>\n";
  return $html;
}

# ==== conditional use of MW functions ====

$wwWfProfileExists = function_exists( 'wfProfileIn' );

function wwProfileIn( $name )
{ global $wwWfProfileExists;
  if ( $wwWfProfileExists )
  { wfProfileIn( $name );
  }
}

function wwProfileOut( $name )
{ global $wwWfProfileExists;
  if ( $wwWfProfileExists )
  { wfProfileOut( $name );
  }
}

function wwRunHooks( $hook, $args ) {
	global $wwWfRunHooksExists;
	if ( ! isset( $wwWfRunHooksExists ) ) {
		$wwWfRunHooksExists = function_exists( 'wfRunHooks' );
	}
	if ( $wwWfRunHooksExists ) {
		return wfRunHooks( $hook, $args );
	}
	return true;
}

# ==== customizable logging ====

$wwLogFunction = function ( $string ) {
	error_log( '[LW] ' . getmypid() . ' ' . $string );
};
// set it to a function using wfDebug or print or whatnot if you want.

function wwLog( $string ) {
	global $wwLogFunction;
	return $wwLogFunction( $string );
}

# ==== custom Exception subclass for clean error management ====

# Generally we use WWInterface::throw_error() instead of using
# this class directly.

class WWException extends Exception
{ # doesn't do anything, really, but we use the class to distinguish
  # kinds of exceptions.
  public function __construct()
  { parent::__construct();
  }
}

# ==== custom Update class for cleaning up the working directories ====
#      after the page is served

class WWPruneDirectoryUpdate {
  function doUpdate()
  { wwProfileIn( __METHOD__ );
    wwLog("starting prune-working-directories update operation...");
    $request = null;
    WWAction::execute_action_by_name('prune-working-directories',$request);
    wwLog("finished prune-working-directories update operation.");
    wwProfileOut( __METHOD__ );
  }
}

# ==== helpers for intervening in EditPage process ====

# form at the top of the edit page to confirm before
# adding/subtracting project files
class EditFormConfirm
{ private $addfiles, $removefiles;
  private $invalidate, $textmd5;
  public function __construct( &$add, &$remove, $invalidate, $md5 )
  { list($this->addfiles,$this->removefiles,$this->invalidate,$this->textmd5) 
      = array($add,$remove,$invalidate,$md5);
  }
  public function doConfirm( &$wgOut )
  { global $wgUser, $wgRequest, $wwContext;
    $skin = $wgUser->getSkin();
    $gpft = SpecialPage::getTitleFor( 'ManageProject' );
    $parser = null;
    $wwContext->wwInterface->include_css('edit-page',$parser);
    $wgOut->addHTML( $wwContext->wwInterface->report_errors() );
    $wgOut->addHTML( "<div class=\"ww-edit-form-confirm\"><hr/>" );
    $wgOut->addWikiText( "Some changes to this page may affect Project"
      . " Extension projects.  Please mark what changes"
      . " should be automatically applied.\n" );
    $counter = 0;
    if (is_array($this->removefiles))
      foreach ( $this->removefiles as $projectname => &$files )
        foreach ( $files as $filename=>&$attrs )
        { $wgOut->addHTML( XML::check( "ww-check-$counter", true, array() )
            . "&nbsp;<label for='ww-check-$counter'>"
            . "remove " . ($attrs['source'] ? 'source' : 'project')
            . " file ‘{$filename}’ from project "
            . $skin->makeLinkObj( $gpft, $projectname, "project=$projectname" )
            //. $skin->link( $gpft, $projectname, array(),
            //    array( 'project'=>$projectname ), array('known') )
            . "</label>\n"
            . wwfHidden( "ww-project-$counter", $projectname ) );
          if ($attrs['source'])
            $wgOut->addHTML( wwfHidden("ww-action-$counter","-s $filename") );
          else
            $wgOut->addHTML( wwfHidden("ww-action-$counter","-p $filename") );
          $wgOut->addHTML( "<br/>\n" );
          ++$counter;
        }
    if (is_array($this->addfiles))
      foreach ( $this->addfiles as $projectname => &$files )
        foreach ( $files as $filename=>&$attrs )
        { $wgOut->addHTML( XML::check( "ww-check-$counter", true, array() )
            . "&nbsp;<label for='ww-check-$counter'>"
            . "add " . ($attrs['source'] ? 'source' : 'project')
            . " file ‘{$filename}’ to project "
            . $skin->makeLinkObj( $gpft, $projectname, "project=$projectname" )
            //. $skin->link( $gpft, $projectname, array(),
            //    array( 'project'=>$projectname ), array('known') )
            . "</label>\n"
            . wwfHidden( "ww-project-$counter", $projectname ) );
          if ( $attrs['ask-upload'] ) # obv. this indenting should be css
          { $checked = $wgRequest->getCheck("ww-upload-$i");
            $wgOut->addHTML( "<br/>\n&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"
              . XML::check( "ww-upload-$counter", $checked, array() )
              . " <label for='ww-upload-$counter'>upload ‘{$filename}’</label>" );
          }
          if ( $attrs['source'] )
          { if ( array_key_exists('page', $attrs) )
              $wgOut->addHTML( wwfHidden("ww-action-$counter","+s $filename") );
            else
              $wgOut->addHTML( wwfHidden("ww-action-$counter","+S $filename") );
          }
          else
            $wgOut->addHTML( wwfHidden("ww-action-$counter","+p $filename") );
          $wgOut->addHTML( "<br/>\n" );
          # doing inclusions here sucks
          if (0 and $wwContext->wwInterface->can_check_file_for_inclusions($filename))
          { $wgOut->addHTML( 
              #'<span class="ww-before-doinclusions">&nbsp;</span>'
              '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'
              . XML::check( "ww-doinclusions-$counter", false, array() )
              . "&nbsp;<label for='ww-doinclusions-$counter'>"
              . "check ‘{$filename}’ for included filenames</label><br/>\n" );
          }
          ++$counter;
        }
    $wgOut->addHTML( "<input type=\"hidden\" name=\"ww-invalidate\""
      . " value=\"$this->invalidate\"/>\n");
    $wgOut->addHTML( "<input type=\"hidden\" name=\"wwConfirmation\""
      . " value=\"$counter\"/>\n" );
    $wgOut->addHTML( "</div>\n" );
    $wgOut->addHTML( "\n<input type=\"hidden\" name=\"wwTextMd5\""
      . " value=\"$this->textmd5\"/>\n" );
    $wgOut->addHTML( "\n<input type=\"submit\" name=\"wpSave\" id=\"wpSave\""
      . " value=\"Save page\" title=\"Save your changes [s]\"/>\n" );
    global $wgShowExceptionDetails; $wgShowExceptionDetails=true;
    global $wgUser, $wgTitle;
    $wgOut->addHTML( $wgUser->getSkin()->makeLinkObj(
      $wgTitle,
      // $wgOut->getTitle(),
      wfMsgExt('cancel', array('parseinline')) ) );
    $wgOut->addHTML( "<hr/>\n" );
  }
};

# similar class for putting an error message atop the edit form, with
# 'Force save' option
class EditFormErrorBox
{ public function doInsertion( &$wgOut )
  { global $wgOut, $wwContext; 
    $p = ''; 
    $fbut = '<br/><input type="submit" name="force" value="Force save"/>';
    $wgOut->addHTML(
      "<br/>\n" . $wwContext->wwInterface->prepend_errors($fbut) . "<br/>\n"
    );
    $parser = null;
  }
};
