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

class WorkingWikiProjectDescription extends ProjectDescription
{ // This class specializes the general ProjectDescription for projects
  // whose file contents are stored in the wiki.
  // It represents what files belong in a given working directory,
  // and how they are compiled and displayed.

  // we can sync the project's source files before each make operation,
  // or we can just do it the first time, since the project directory is 
  // locked and there's no need to do it again.  here we mark whether we've
  // done it so we can skip it subsequently.
  public $synced;

  static $mkfile_warn;
  protected function __construct($xmldata, $page, $as_of_revision)
  { parent::__construct($xmldata,$page, $as_of_revision);
    $this->add_GNUmakefile();
  }

  protected function add_GNUmakefile() {
    // if there's going to be an automatic makefile, we create a
    // special entry for it
    // (FORMERLY: but don't do it for a project that doesn't actually exist)
    global $wwContext, $wwGenerateMakefile;
    if ( $wwGenerateMakefile and $this->options['use-default-makefiles'] 
        //and (($this->project_description_page != '') 
	//  or count($this->project_files) > 0) 
       )
    { #wwLog("Adding GNUmakefile to project with xml:\n$xmldata,\npage $page, revision $as_of_revision\n");
      $automkfilename = 'GNUmakefile';
      if ( isset($this->project_files[$automkfilename]) and
          isset($this->project_files[$automkfilename]['source']) )
      { if (!isset(WorkingWikiProjectDescription::$mkfile_warn))
          WorkingWikiProjectDescription::$mkfile_warn = array();
        if (!isset(WorkingWikiProjectDescription::$mkfile_warn[
                $this->project_name()]))
        { $wwContext->wwInterface->record_warning( 'Project ' 
            . $wwContext->wwInterface->make_manage_project_link($this)
            . ' has the "Use default makefiles" option set, but it has '
            . ' a source file called "' . $automkfilename . '".'
            . ' Using the source file provided instead of generating one.' );
          WorkingWikiProjectDescription::$mkfile_warn[$this->project_name()]
              = true;
        }
      }
      else
      { $this->add_source_file(array('filename'=>$automkfilename,
          'automatic'=>true));
      }
    }
  }

  # In the XML for a local project, we allow: one source-file element
  # per filename and any number of project-file elements associating
  # a filename to a page
  protected function read_xml_element($element)
  { global $wwContext;
    if (parent::read_xml_element($element))
      return true;
    if (((string)$element->getName()) == 'source-file')
    { $filename = (string)$element['filename'];
      if (!ProjectDescription::is_allowable_filename($filename))
      { $wwContext->wwInterface->record_error( "Prohibited filename ‘"
          . htmlspecialchars($filename) . "’." );
        return false;
      }
      # don't throw - just let the later one overwrite the earlier
      #if (array_key_exists($filename,$this->project_files) and
      #    $this->project_files[$filename]['source'] )
      #{ $wwContext->wwInterface->throw_error( 'Error in project description'
        #  . ": duplicate source-file ‘" . htmlspecialchars($filename)
        #  . "’" );
      #}
      $this->project_files[$filename]['source'] = true;
      # handle attributes same as for project-file.  But in particular
      # we'll be looking for the 'page' attribute.
      foreach ( $element->attributes() as $key => $val )
      { if ((string)$key == 'appears')
	{ $title = Title::newFromText( (string)$val );
          if ( $title !== null and $title->getNamespace() !== NS_SPECIAL )
            $this->project_files[$filename]['appears'][(string)$val] = true;
	}
        else if ((string)$key == 'archived')
	{ $title = Title::newFromText( (string)$val );
          if ( $title !== null and $title->getNamespace() !== NS_SPECIAL )
            $this->project_files[$filename]['archived'][(string)$val] = true;
	}
        else
	{ $key = (string)$key;
          if ( $key == 'page' )
          { $title = Title::newFromText( (string)$val );
            if ( $title === null or $title->getNamespace() === NS_SPECIAL )
              continue;
	    $val = $title->getPrefixedText();
	  }
          $this->project_files[$filename][(string)$key] = (string)$val;
	}
      }
      return true;
    }
    return false;
  }

  public function add_to_project_description_text(&$xml)
  { foreach ( $this->project_files as $pf )
    { if ( is_array($pf) and isset($pf['source']) and $pf['source']
            and !isset($pf['automatic']) )
      { unset($pf['source'], $pf['appears'], $pf['updated'], $pf['archived']);
        $xml .= '  <source-file';
        foreach ( $pf as $key => $val )
          $xml .= ' ' . htmlspecialchars($key, ENT_QUOTES|ENT_XML1|ENT_COMPAT, 'UTF-8', false)
            . '="' . htmlspecialchars($val, ENT_QUOTES|ENT_XML1|ENT_COMPAT, 'UTF-8', false) . '"';
        $xml .= "/>\n";
      }
    }
    parent::add_to_project_description_text($xml);
  }

  protected function generate_uri()
  { global $wwContext, $wgServer, $wgScriptPath;
    return $wwContext->wwStorage->local_uri_base() . $this->project_name();
  }

  public function proactively_sync_if_needed()
  { return wwRunHooks(
	  'WW-ProactivelySyncIfNeeded',
	  array( $this )
    );
  }

  # the specific places to look for a given file
  public function default_locations_for_file($filename)
  { global $wwContext;
    $defaults = $wwContext->wwStorage->default_locations();
    $pname = $this->project_name();
    if (($slash = strrpos($filename, '/')) !== false)
      $leaf = substr($filename, $slash+1);
    else
      $leaf = null;
    foreach ($defaults as $default)
    { $location = str_replace('?P', $pname,
        str_replace('?F', $filename, $default));
      if ($leaf !== null)
        $llocation = str_replace('?P', $pname,
          str_replace('?F', $leaf, $default));
      else
        $llocation = '';
      $title = Title::newFromText($location);
      if ($title instanceOf Title and $title->getNamespace() == NS_IMAGE)
      { $location = str_replace('/','$',$location);
        $llocation = str_replace('/','$',$llocation);
      }
      $locations[] = $location;
      if ($llocation)
        $locations[] = $llocation;
    }
    #wwLog("default locations : " . implode(' ', $defaults) . "\n = "
    #  . implode(' ', $locations) . "\n");
    return $locations;
  }

  # what to send to ProjectEngine
  public function fill_pe_request(&$request, $focal, $sync_sf)
  { global $wwContext;
    $uri = $this->project_uri();
    $operation = $request['operation']['name'];
    if (ProjectEngineConnection::operation_includes_make($operation))
    { $varname = ($focal ? 'WW_THIS_DIR' : 
         $request['projects'][$uri]['varname']);
      foreach ($this->source_filenames() as $filename)
      { if (!isset($request['operation']['env']))
          $request['operation']['env'] = array();
        if (!isset($request['operation']['env']['WW_ALL_SOURCE_FILES_UNEXPANDED']))
        { $request['operation']['env']['WW_ALL_SOURCE_FILES_UNEXPANDED'] = '';
          $sep = '';
        }
        else
          $sep = ' ';
        $request['operation']['env']['WW_ALL_SOURCE_FILES_UNEXPANDED'] .= 
            $sep . "$($varname)/$filename";
      }
    }
    #wwLog( "in WorkingWikiProjectDescription::fill_pe_request( " . json_encode( $request )
	#   . ", $focal, $sync_sf, " . json_encode( $context ) . "),\n  this = " . json_encode( $this ) );
    parent::fill_pe_request($request, $focal, $sync_sf);
    #wwLog( "ok_to_sync_source_files returns " . ($wwContext->wwStorage->ok_to_sync_source_files( $context ) ? 'yes' : 'no' ) );
    #wwLog( "in fill_pe_request: $sync_sf | {$this->synced} | " . $wwContext->wwStorage->ok_to_sync_source_files( $context ) );
    if ( $sync_sf and !isset( $request['projects'][$uri]['source-file-contents'] )
	 and !$this->synced and ( $this->is_standalone() or $wwContext->wwStorage->ok_to_sync_source_files() ) )
    { $asf = $this->all_source_file_contents();
      #wwLog("fill_pe_request: asf is " . json_encode($asf) );
      if (is_array($asf) and count($asf) > 0)
      { $request['projects'][$uri]['source-file-contents'] 
          = $asf;
      }
      $this->synced = true;
    }
    else if ($focal and $operation == 'force-sync')
    { if ( ! $wwContext->wwStorage->ok_to_sync_source_files() )
        $wwContext->wwInterface->throw_error("Cannot do sync operation during preview.");
      if (isset($request['operation']['target']))
      { $target = array($request['operation']['target']);
        unset($request['operation']['target']);
      }
      else
      { $target = null;
        $this->synced = true;
      }
      if ( ! isset($request['projects'][$uri]['source-file-contents']) )
        $request['projects'][$uri]['source-file-contents'] =
          $this->all_source_file_contents($target, null, false);
    }
    global $wwGenerateMakefile;
    if ($wwGenerateMakefile)
    { $request['operation']['use-default-makefiles'] = false;
    }
  }

  # data for sending to ProjectEngine: for each source file, put
  #  array(filename, contents, modtime) 
  # in the array, except if it's stored as an uploaded file (a File: or 
  # Image: page) AND we have a local connection to PE so it can access 
  # the file directly, we put
  #  array(filename, path).
  public function all_source_file_contents($files=null, $as_of_rev=null, $forgiving=true)
  { global $wwContext, $wwPECanReadFilesFromWiki;
    wwProfileIn( __METHOD__ );
    $asfc = array();
    $pname = $this->project_name();
    if ($as_of_rev === null)
      $as_of_rev = $this->as_of_revision;
    if ($as_of_rev === null)
    { if (!array_key_exists($pname,$wwContext->wwInterface->cache_file_contents))
        $wwContext->wwInterface->cache_file_contents[$pname] = array();
      $cache_entry =& $wwContext->wwInterface->cache_file_contents[$pname];
    }
    else
      $cache_entry = array();
    #wwLog("in all_source_file_contents, in which cache_entry is "
    #  . json_encode($cache_entry) );
    #wwLog("in all_source_file_contents(" . serialize($files) . ", $as_of_rev)\n");
    if ($files === null)
      $files = $this->source_filenames();
    #wwLog("files: ". serialize($files)."\n");
    foreach ($files as $filename)
    { $pfe = $this->project_files[$filename];
      #wwLog("pfe for $filename: ". serialize($pfe) . "\n");
      if (!array_key_exists($filename, $cache_entry))
      { $page = (isset($pfe['page']) ? $pfe['page'] : null);
        #wwLog( 'calling find_source_file_content: ' . $this->project_name() . ' > ' . $filename . ', ' . $page );
        $sfc = $this->find_source_file_content($filename,$page,$as_of_rev);
	#wwLog( "find_source_file_content($filename, $page, $as_of_rev): " . json_encode($sfc) );
        if (isset($sfc['text'])) # assume 'touched' is set as well.
	{ if ( function_exists( 'wfTimestamp' ) ) {
	    $ts = wfTimestamp( TS_UNIX, $sfc['touched'] );
	  } else {
	    $ts = new DateTime( 'now' );
	    $ts = $ts->getTimestamp();
	  }
	  $cache_entry[$filename] = ProjectEngineConnection::make_sync_file_entry(
		null,
		$sfc['text'], 
		$ts
	  );
          #wwLog("cache_entry for $filename: "
          #  . serialize($cache_entry[$filename]) . "\n");
          #wwLog("cache_entry: "
            #. serialize($cache_entry) . "\n");
        }
        else if ($sfc['type'] == 'file')
        { $title = Title::newFromText($sfc['page']);
          $file = wfLocalFile( $title );
          if ( ! $file->exists() ) {
            $wwContext->wwInterface->record_error("Source file '"
              . htmlspecialchars($pfe['filename']) 
              . "' not found at location '" . htmlspecialchars($page) . "'.");
	    if ( ! $forgiving ) {
		    throw new WWException;
	    }
	  } else {
            if (method_exists($file, 'getLocalRefPath'))
	    { $filepath = $file->getLocalRefPath();
	      #wwLog( "getLocalRefPath() of {$title->getPrefixedDBKey()} is $filepath\n" );
	    }
            else
            { $filepath = $file->getPath();
	      #wwLog( "getPath() of {$title->getPrefixedDBKey()} is $filepath\n" );
	    }
            if ( $filepath == '' ) {
              $wwContext->wwInterface->record_error("Can't find actual file for '{$sfc['page']}'.\n");
	      if ( ! $forgiving ) {
		    throw new WWException;
	      }
	    }
	    $cache_entry[$filename]  = ProjectEngineConnection::make_sync_file_entry(
		    $filepath, null, null );
	  }
        }
        else if ($sfc['type'] == 'not found')
	{ #wwLog( "source file not found {$pfe['filename']}" );
          if ( ! $forgiving ) {
		$wwContext->wwInterface->throw_error( 'Source file ‘' . htmlspecialchars($pfe['filename'])
			. "’ for project "
			. $wwContext->wwInterface->make_manage_project_link( $this, $pname )
			.' is missing' 
			. ($pfe['page'] ? ' from page '
				. $wgUser->getSkin()->makeLinkObj(
					Title::newFromText($pfe['page']), $pfe['page']
				) : '')
			. '.'
		);
          }
          if (!$wwContext->wwInterface->page_is_preview() and !wwfReadOnly())
          { global $wwClickToAdd;
            if ($wwClickToAdd)
            { global $wgUser;
              $wwContext->wwInterface->record_message(
                "<div class='ww-clickto'>Notice: source file ‘". htmlspecialchars($pfe['filename'])
                ."’ for project "
                . $wwContext->wwInterface->make_manage_project_link( $this, $pname )
                .' is missing' 
                . ($pfe['page'] ? ' from page ' . $wgUser->getSkin()->makeLinkObj(
                    Title::newFromText($pfe['page']), $pfe['page'] ) : '') . '. ('
                . $wwContext->wwInterface->make_manage_project_link( $this,
                    'Click here to remove',
                    'ww-action=remove-source-file&ww-action-filename='
		      . urlencode($pfe['filename']) . '&ww-action-projwd=1'
		      . '&ww-action-project=' . urlencode($pname),
		    false, false, null,
		    array( 'onClick' => "clickTo(event, {action:'ww-remove-file',filename:'"
		      . htmlspecialchars($pfe['filename']) . "',project:'"
		      . htmlspecialchars($this->project_name()) . "',projwd:1})" ) )
                . ' it from the project.)</div>' );
	      global $wgOut;
	      $wgOut->addModules( 'ext.workingwiki.clicktoadd' );
            }
            else
            { $this->remove_file( array( 'filename'=>$pfe['filename'] ) );
              ProjectEngineConnection::call_project_engine(
                'remove', $this, array( 'target' => $pfe['filename'] ) );
              $wwContext->wwInterface->project_is_modified($this->project_name());
              #wwLog( "remove source file " . $pfe['filename'] );
              $wwContext->wwInterface->record_message(
                'Removed missing source file \''
                . htmlspecialchars($pfe['filename'])
                . '\' from project '
                . $wwContext->wwInterface->make_manage_project_link( $this ) . '.' );
              #$wwContext->wwInterface->record_message( "currently parsing is {$wwContext->wwInterface->currently_parsing_key}" );
              global $wgParser;
	      if (is_object($wgParser) and 
		   (!method_exists('Parser', 'getOutput') or
		    is_object($wgParser->getOutput())) )
                $wgParser->disableCache(); # message should go away on reload
            }
          }
        }
      }
      # at this point either it's in the cache, or it's nowhere to be found
      if (isset($cache_entry[$filename]))
        $asfc[$filename] = $cache_entry[$filename];
      #wwLog("asfc for $filename: ". serialize($asfc[$filename]) . "\n");
    }
    # since we might be switching the automatic GNUmakefile feature on and
    # off, take steps to remove that file if it's not supposed to be there
    global $wwGenerateMakefile;
    if (!$wwGenerateMakefile and !isset($asfc['GNUmakefile']))
      $asfc['GNUmakefile'] = array( 'c', null, null );
    #wwLog("all_source_file_contents: ". serialize($asfc) . "\n");
    wwProfileOut( __METHOD__ );
    return $asfc;
  }

  # occasionally we need to distinguish source files from other files,
  # for instance to decide whether to offer a sync operation on the directory
  # listing page.
  # Not an error if the filename doesn't exist, just means it's a target.
  public function is_file_source($filename)
  { if (isset($this->project_files[$filename]['source']) and
        $this->project_files[$filename]['source'])
      return true;
    return false;
  }

  public function has_source_files()
  { return true;
  }

  public function add_source_file($attrs)
  { global $wwContext;
    #wwLog( "add_source_file: " . json_encode($attrs) );
    if (isset($attrs['page']))
    { if ( class_exists( 'Title' ) )
      { $t = Title::newFromText(wwfSanitizeInput($attrs['page']));
        if ( $t instanceOf Title ) {
          $attrs['page'] = $t->getPrefixedDBKey();
        } else {
          $wwContext->wwInterface->record_error( "Bad page location for add_source_file: "
            . htmlspecialchars( $attrs['page'] ) );
        }
      }
    }
    return $this->add_file_element(array_merge(
      array('automatic'=>null), $attrs, array('source'=>true)));
  }

  public function add_project_file($attrs)
  { return $this->add_file_element($attrs);
  }

  public function source_filenames()
  { $sfn = array();
    foreach ( $this->project_files as $pf )
      if (is_array($pf) and isset($pf['source']) and $pf['source'])
        $sfn[] = $pf['filename'];
    return $sfn;
  }

  # remove a file from the project description.
  # if it's a source file, it's removed altogether.  
  # If a project file, any 'appears' and/or 'archived' values in
  # $attrs are removed.
  public function remove_file($attrs)
  { global $wwContext;
    if (!array_key_exists('filename',$attrs))
      $wwContext->wwInterface->throw_error("Can't remove file without filename");
    $filename = $attrs['filename'];
    if (!array_key_exists($filename, $this->project_files))
      return false;
    if (isset($this->project_files[$filename]['source']))
      unset($this->project_files[$filename]);
    else foreach (array('appears','archived') as $aname)
    { if (array_key_exists($aname,$attrs))
      { foreach ($attrs[$aname] as $pg=>$t)
          unset($this->project_files[$filename][$aname][$pg]);
        if (!count($this->project_files[$filename][$aname]))
          unset($this->project_files[$filename]);
      }
    }
    return true;
  }

  # what pages display files from this project, for cache invalidation.
  # including source files
  # (because, for instance, a source-file might be a latex file that
  # needs to be recompiled to html for display when something else is 
  # changed).
  public function pages_involving_project_files()
  { global $wwContext;
    $involves = array_flip(parent::pages_involving_project_files());
    foreach ($this->project_files as &$pf)
      if (isset($pf['source']) and $pf['source'])
      { if (array_key_exists('page',$pf))
          $involves[$wwContext->wwStorage->page_cache_key($pf['page'])] = true;
        else # find implicit location of source file
        { $sfc = $this->find_source_file_content($pf['filename'],null);
          if (isset($sfc['page']))
            $involves[$wwContext->wwStorage->page_cache_key($sfc['page'])] = true;
        }
      }
    #wwLog( "pages_involving_project_files for {$this->project_name()}: "
    #  . implode(", ", array_keys($involves)) . "\n");
    return array_keys($involves);
  }

  # for git: when's the last time anything in the project was edited
  public function latest_revision()
  { global $wwContext;
    if ($this->as_of_revision)
      return $this->as_of_revision;
    $lastrev = null;
    $pages = $this->pages_involving_project_files();
    if ( isset($this->project_description_page) )
      $pages[] = $this->project_description_page;
    foreach ($pages as $page)
    { $title = Title::newFromText($page);
      if ( method_exists( 'WikiPage', 'factory' ) ) # MW 1.21
      { try {
	$wikipage = WikiPage::factory( $title );
        $rev = $wikipage->getRevision();
        } catch ( MWException $ex ) {
		$wwContext->wwInterface->record_warning( "Caught an exception internally trying to access page \"{$title->getPrefixedText()}\".  This may or may not indicate a problem." );
		continue;
	}
      }
      else
      { $article = new Article($title, 0);
        #$rev = $article->getRevisionFetched(); # doesn't work in 1.13
        $article->fetchContent();
        $rev = $article->mRevision;
      }
      if ( $lastrev === null or 
           ( $rev !== null and $lastrev->getId() < $rev->getId() ) )
        $lastrev = $rev;
    }
    return $lastrev;
  }

  # locations of all File: (Image:) pages storing source files - they
  # get special handling so that a new upload will cause the 
  # pages referencing the project to be reparsed.
  public function source_image_pages()
  { $ret = array();
    foreach ($this->project_files as $pf)
      if (isset($pf['source']) and $pf['source'])
      { # try to get the wiki page it's on
        $sfpage = (isset($pf['page']) ? $pf['page'] : null);
        $sfc = $this->find_source_file_content($pf['filename'],$sfpage);
        if ($sfc['type'] == 'file' and isset($sfc['page']))
        { $title = Title::newFromText($sfc['page']);
          if( NS_MEDIA == $title->getNamespace() ) 
            $title = Title::makeTitle( NS_IMAGE, $title->getDBkey() );
          $ret[] = $title->getPrefixedDBKey();
        }
      }
    return $ret;
  }

  # see WWStorage::find_file_content()
  # note this function doesn't check in $project_files for a page location
  # if $pagename is null, it only looks in default locations.
  public function find_source_file_content($filename, $pagename, $as_of_rev=null)
  { global $wwContext;
    #wwLog( 'in find_source_file_content ' . $filename );
    if ($as_of_rev === null)
      $as_of_rev = $this->as_of_revision;
    if (isset($this->project_files[$filename]['automatic']))
    { if ($as_of_rev === null)
      { $touched = $wwContext->wwStorage->page_mod_time($this->project_page());
      }
      else
        $touched = 0;
      #wwLog( "find_source_file_content($filename) doing automatic file" );
      return array( 'type'=>'automatic',
        'text' => $this->generate_source_file_content($filename),
        'touched' => $touched );
    }
    #wwLog( "find_source_file_content($filename) passing to WWStorage" );
    return $wwContext->wwStorage->find_file_content($filename, $this,
      $pagename, /*src*/true, $as_of_rev);
  }

  # auto-generated makefile is a special case.  Don't look for it,
  # generate it.
  public function generate_source_file_content($filename)
  { global $wwContext;
    if ($filename !== 'GNUmakefile')
      $wwContext->wwInterface->throw_error( "Can't automatically generate filename "
        . htmlspecialchars($filename) );
    $source_filenames = implode(' ', $this->source_filenames());
    $this->assemble_transitive_dependencies();
    $text = "### GNUmakefile automatically generated by WorkingWiki ###\n";
    $text .= "WW_THIS_PROJECT_SOURCE_FILES = " . $source_filenames . "\n";
    $text .= "PREREQUISITE_PROJECTS ="; 
    foreach ($this->depends_on_transitively as $uri => $depinfo)
      $text .= ' ' . $depinfo['varname'];
    $text .= "\n";
    $text .= "ifndef RESOURCES\n";
    $text .= "  # ProjectEngine provides RESOURCES and the prerequisite project\n";
    $text .= "  # locations.  If they aren't provided, we're in an exported directory.\n";
    $text .= "  export RESOURCES = " . $this->offline_resources_path() . "\n";
    foreach ($this->depends_on_transitively as $uri => $depinfo) {
      $text .= "  export {$depinfo['varname']} = ../{$depinfo['short-dir']}\n";
    }
    foreach ($this->env_for_make_jobs() as $k => $v) {
      # note MW_PAGENAME won't be set right for make jobs outside the wiki
      $text .= "  export $k = $v\n";
    }
    $text .= "endif\n";
    $text .= "include $(RESOURCES)/makefile-before\n";
    $text .= "ifeq ($(filter makefile, $(wildcard makefile)),makefile)\n";
    $text .= "  include makefile\n";
    $text .= "else ifeq ($(filter Makefile, $(wildcard Makefile)),Makefile)\n";
    $text .= "  include Makefile\n";
    $text .= "endif\n";
    $text .= "include $(RESOURCES)/makefile-after\n";
    return $text;
  }

  public function offline_resources_path() {
	  return "../.workingwiki/resources";
  }

  public function project_revisions($fetch_from)
  { global $wwContext;
    return $wwContext->wwStorage->project_revisions($this, $fetch_from);
  }

  public function export_git()
  { global $wgOut;
    $wgOut->disable();
    header("Content-type: text/plain");
    header("Content-Disposition: attachment;filename=\""
      . addslashes($this->project_name()) . ".git\"");
    $gitter = new DumpProjectToGit;
    $output = $gitter->dumpProject($this);
    header("Content-Length: ". strlen($output));
    echo $output;
  }
}
