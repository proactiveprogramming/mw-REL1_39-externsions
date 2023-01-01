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
 * ===== This class pretty much implements a source code repository =====
 *       inside MediaWiki.  Source files are stored on wiki pages,
 *       both in the text of text pages and as uploaded files on
 *       File: (aka Image:) pages.
 */

class WWStorage {

  # $pagetext_cache[pagename] contains the content of page 
  # 'pagename', if it's already been retrieved.
  # pagename is normalized using Title::getPrefixedDBKey(),
  # to avoid different callers using different names for the same 
  # page.
  # The key can also be '' if it's wikitext that's not from any page.
  public $pagetext_cache = array();

  # we remember the location of project files to be archived here, so
  # we can archive them when parsing is done
  public $to_archive = array();

  # we sequester files to be archived here, in some cases
  public $seq_dir = null;

  # to prevent recursive archiving of project files
  public $archiving_in_progress = false;

  # return the <project-description> from this page, or false if not found.
  # it should either match the project name, or not have a name attribute.
  public function
    find_project_description_on_page($projectname,$pagename,$as_of_revision=null)
  { wwProfileIn( __METHOD__ );
    //wwLog("Look for project description $projectname on $pagename, as of $as_of_revision\n");
    $pagetext = $this->retrieve_page($pagename, $as_of_revision);
    #wwLog("Seeking project-description on page ‘{$pagename}’.\n");
    wwProfileOut( __METHOD__ );
    return $this->find_project_description_in_page_text($projectname,$pagetext);
  }

  # get the project description from a page's contents
  public function
    find_project_description_in_page_text($projectname, $pagetext)
  { #wwLog("page text:\n$pagetext\n");
    wwProfileIn( __METHOD__ );
    if ($projectname)
      $projectname = ProjectDescription::normalized_project_name($projectname);
    if (preg_match_all(
          '/<project-description.*?'.'>.*?<\/project-description>/is',
          $pagetext,$pd_matches))
      foreach ($pd_matches[0] as $pd)
      { #wwLog("try a match: $pd...\n");
        if ( !$projectname )
          return $pd;
        else if ( !preg_match('/<project\b[^>*?]name="([^"]*)"/i',$pd,$nm_matches) )
        { #wwLog("found anonymous project description for '$projectname': using.\n");
          wwProfileOut( __METHOD__ );
          return $pd;
        }
        else 
        { if ( ProjectDescription::normalized_project_name($nm_matches[1])
                == $projectname )
          { wwProfileOut( __METHOD__ );
            return $pd;
          }
        }
      }

    #wwLog("Did not find project-description for '$projectname'.\n");
    wwProfileOut( __METHOD__ );
    return false;
  }

  # search on several pages for project-description element, return
  # array(false,false) if not found, array(xml,pagename) if found
  public function 
    find_project_description_by_name($projectname, $as_of_revision=null)
  { wwProfileIn( __METHOD__ );
    #wwLog("find_project_description_by_name($projectname)\n");
    $norm_projectname = 
      ProjectDescription::normalized_project_name($projectname);
    # check first in case we've already loaded it.  This makes no 
    # difference to find_project_by_name, because it won't call this 
    # function if so, but this is also called by make_project_box...

    # alas, this does make a difference, because if no project
    # description exists, and we create a Project object anyway, this will
    # subsequently create a false positive by finding a description

    #if (is_array(Project::$project_cache) and
    #    array_key_exists($projectname,Project::$project_cache))
    #  return Project::$project_cache[$projectname]->project_description_text();
    foreach ( $this->pages_where_project_description_might_be($norm_projectname)
               as $page )
    { #wwLog("look for $projectname on $page\n");
      $xml = $this->find_project_description_on_page($norm_projectname,
                $page, $as_of_revision);
      if ($xml)
      { wwProfileOut( __METHOD__ );
        return array($xml,$page);
      }
    }
    #wwLog("Project $projectname not found\n");
    wwProfileOut( __METHOD__ );
    return array(false,false);
  }

  # places to look when looking for a project's description
  public function
    pages_where_project_description_might_be($projectname)
  { global $wwProjectDescriptionNamespaceName;
    $norm_projectname = 
      ProjectDescription::normalized_project_name($projectname);
    $pages = array(
      "$wwProjectDescriptionNamespaceName:$norm_projectname" );
      #, $norm_projectname );
    #if (substr($norm_projectname,-1) != '/')
    #  $pages = array_merge( $pages, array( $norm_projectname.'/' ) );
    return $pages;
  }

  # generate inferred project-description describing the source-files
  # found on a given page
  # (except those that belong to a different project than the one named).
  # return: xml project description, or null if page doesn't exist
  #
  # note: not used any more, except maybe for historical reconstruction
  public function
    create_implicit_project_description($projectname,$pagename,$pagetext=null,
      $as_of_revision=null)
  { #wwLog("create_implicit_project_description: $projectname, $pagename"
    #  . ($pagetext === null ? ' (no pagetext)': ' (with pagetext)') . "\n");
    $projectname = ProjectDescription::normalized_project_name($projectname);
    $sflists = $this->find_project_files_on_page($pagename,$pagetext,false,true,$as_of_revision);
    #wwLog("find_project_files_on_page($pagename) as of $as_of_revision returns:\n"
    #  . serialize($sflists) . "\n");
    $xml = "<project-description><project name=\""
      . htmlentities($projectname, ENT_QUOTES|ENT_XML1) . "\">\n";
    # source-file elements with the right projectname or none
    # source files that have the right project name
    # have precedence over those that have none specified
    if (isset($sflists['']) and is_array($sflists['']))
      $sflist = $sflists[''];
    else
      $sflist = array();
    if (isset($sflists[$projectname]) and is_array($sflists[$projectname]))
    { $sflist = array_merge($sflist,$sflists[$projectname]);
    }
    foreach ($sflist as $key => &$sf_entry)
    { //wwLog( 'source: '.$sf_entry['source']."\nstandalone: "
      //  . wwfArgumentIsYes($sf_entry['attributes']['standalone'])."\n" );
      if ( isset($sf_entry['source'])
          and (!isset($sf_entry['attributes']['standalone'])
               or !wwfArgumentIsYes($sf_entry['attributes']['standalone'])) )
      { $xml .= '  <source-file filename="'
          . htmlentities($sf_entry['attributes']['filename']) . '"';
        if (!isset($sf_entry['declaration-only']))
          $xml .= ' page="' . htmlentities($pagename) . '"';
        $xml .= "/>\n";
      }
    }
    $xml .= "</project></project-description>\n";
    #wwLog("Created implicit project xml from page $pagename:\n$xml\n");
    return $xml;
  }

  # replace the project description with the current one, wherever it is.
  public function save_project_description( $project, $check_perms = true )
  { wwProfileIn( __METHOD__ );
    global $wwContext;
    #wwLog("save project description ".$project->project_name());
    #wwLog( count($project->project_files) .
	#" project files in " . $project->project_name() );
    $pd = $project->project_description_text();
    foreach ( $this->pages_where_project_description_might_be(
                $project->project_name()) as $page )
    { if ($this->replace_project_description($pd, $page,
                                                 false, $check_perms))
      { $wwContext->wwInterface->invalidate_pages( $project, null );
        wwProfileOut( __METHOD__ );
        return true;
      }
    }
    // didn't find anything to replace... write it to the standard location
    global $wwProjectDescriptionNamespaceName;
    $page = $wwProjectDescriptionNamespaceName.':'
      . $project->project_name();
    $project->project_description_page = $page;
    $ret = $this->replace_project_description( $pd, $page, true, $check_perms );
    $wwContext->wwInterface->invalidate_pages( $project, null );
    wwProfileOut( __METHOD__ );
    return $ret;
  }

  # use to update or remove a project description.
  # to remove, use '' for $replacement.
  # returns: true if it did a replacement,
  # false if the project description wasn't found.
  # interesting: if there were ever multiple project descriptions
  # on one page (which is supposedly allowed), this might replace the
  # wrong one because it doesn't check the name. Not worth fixing though
  # because we don't really expect anyone to hand-insert project 
  # descriptions on mainspace pages, and we're putting them all on 
  # ProjectDescription: pages now.  To do: don't allow them anywhere else.
  private function 
    replace_project_description($replacement, $page,
      $add_if_not_found = false, $check_perms = true)
  { wwProfileIn( __METHOD__ );
    $title = Title::newFromText($page);
    $details = null;
    if ( $check_perms and !wwfOKToEditPage( $title, $details ) )
    { #wwLog("replace_project_description failed.\n");
      wwProfileOut( __METHOD__ );
      return false;
    }
    $article = new Article($title, 0);
    if ($article->getID() !== 0)
      $content = $article->getContent();
    else
      $content = '';
    list($pdstart,$pdend) =
      $this->find_element('project-description',array(),$content,0);
    if ($pdstart === false)
    { # did not find one on the page
      if (!$add_if_not_found)
      { wwProfileOut( __METHOD__ );
        return false;
      }
      $content .= $replacement;
      $comment = 'WorkingWiki created project description';
    }
    else
    { # found an old project-description: replace it
      $content = substr($content,0,$pdstart) . $replacement 
        . substr($content,$pdend+1);
      if (strlen($replacement) > 0)
        $comment  = 'WorkingWiki replaced project description';
      else
        $comment = 'WorkingWiki removed project description';
    }
    $article->doEdit( $content, $comment, EDIT_SUPPRESS_RC );
    $this->pagetext_cache[$this->page_cache_key($page)]['text'] = $content;
    #wwLog("Replaced project description on page "
    #  . $this->page_cache_key($page));
    wwProfileOut( __METHOD__ );
    return true;
  }

  # find_project_by_name: given a project name:
  #   search on the appropriate pages for project description;
  #   if not found, return an empty project.
  #   (except if $create=false, in which case return null unless the
  #    project has already been found and cached.)
  # Used by Special:GetProjectFile, and when project= is found in an
  # element on a wiki page.
  public function
    find_project_by_name($pname, $create=true, $as_of_revision=null)
  { wwProfileIn( __METHOD__ );
    global $wwContext;
    try {
      $projectname = ProjectDescription::normalized_project_name($pname);
      $is_external = $this->is_project_uri($projectname);
      #wwLog("External, $projectname ? ". ($is_external ? 'yes':'no'). "\n");
      #wwLog("Find project by name $pname ($projectname): create=$create\n");
      if ($as_of_revision == null and
          isset(ProjectDescription::$project_cache) and
          array_key_exists($projectname,ProjectDescription::$project_cache))
      { wwProfileOut( __METHOD__ );
        #wwLog("Found in cache.\n");
        return ProjectDescription::$project_cache[$projectname];
      }
      if (!$create)
      { wwProfileOut( __METHOD__ );
        return null;
      }
      #wwLog("Project $projectname not found in cache, creating\n" );
      if ($this->is_standalone_name($projectname))
      { # the name is 'Standalone?filename'.
        #wwLog("create standalone project\n");
        $parts = explode('?',$projectname);
        $rv = $this->create_standalone_project($parts[1]);
        wwProfileOut( __METHOD__ );
        return $rv;
      }
      # try to find a project description somewhere in the wiki
      list($xml,$pagename)
        = $this->find_project_description_by_name(
            $projectname, $as_of_revision);
      # if not, use the content of page with the project's name
      # for historical reconstruction only
      if (!$xml and !$is_external and $as_of_revision !== null)
      { #wwLog("project description for '{$projectname}' not found, try implicit\n");
        $xml = $this->create_implicit_project_description(
                  $projectname,$projectname, null, $as_of_revision);
        #wwLog( "create implicit project description $projectname:\n$xml\n" );
      }
      if (!$xml and !$is_external)
      { # no implicit - let it be empty
        $xml = $wwContext->wwInterface->create_empty_project_description($projectname);
        $pagename = null;
      }
      if (!$xml and $is_external)
      { $xml = $this->create_project_description_for_uri( $projectname );
        $pagename = null;
      }
      if (!$xml)
      { #wwLog("Project ‘{$projectname}’ not found");
        wwProfileOut( __METHOD__ );
        return null;
      }
      #wwLog("XML for project $projectname:\n$xml\n");
      // ProjectDescription constructor adds it to the cache
    }catch( Exception $ex )
    { wwProfileOut( __METHOD__ );
      throw $ex;
    }
    wwProfileOut( __METHOD__ );
    try {
      $project = ProjectDescription::newFromXML($xml,$pagename,null,$as_of_revision);
    } catch ( WWException $ex ) {
      return null;
    }
    return $project; 
  }

  # find_project_given_page: given a wiki page, and not a project name,
  # come up with a ProjectDescription for items found there.  
  # Use this function when you find a source-file or project-file tag
  # without a project= attribute.
  public function
    find_project_given_page($pagename)
  { wwProfileIn( __METHOD__ );
    global $wwContext;
    while (substr($pagename,-1) == '/')
      $pagename = substr($pagename,0,-1);
    try {
      $pt = Title::newFromText( $pagename );
      if ( $pt === null )
        throw new MWException( "Bad title" );
    } catch ( MWException $ex ) {
      $wwContext->wwInterface->throw_error( "Internal error: Bad page given to find_project_given_page()" );
    }

    # on special pages, there is none.  WW special pages can assign
    # projects explicitly.
    if ( $pt->getNamespace() == NS_SPECIAL )
      return null;

    $pagename = $pt->getPrefixedDBKey();
    #wwLog("find_project_given_page( $pagename )\n");

    # this is no good, but it'll work for now.
    # it's just for the case of using Special:GetProjectFile to parse
    # a wikitext file with project-file tags in it - in that case the
    # default project shouldn't be the page title (Special:GetProjectFile)
    # but the project specified in the GET parameters.
    #$title = Title::newFromText($pagename);
    #global $wgTitle, $wgRequest;
    #if ($title->getNamespace() == NS_SPECIAL and 
    #    $title->getPrefixedText() == $wgTitle->getPrefixedText())
    #  $orig_projectname = $wgRequest->getText('project');
    #else
      $orig_projectname = ProjectDescription::normalized_project_name($pagename);
    if (!isset(ProjectDescription::$project_cache))
      ProjectDescription::$project_cache = array();
    if (array_key_exists($orig_projectname,ProjectDescription::$project_cache)
    	and ProjectDescription::$project_cache[$orig_projectname]->project_description_page)
    { wwProfileOut( __METHOD__ );
      return ProjectDescription::$project_cache[$orig_projectname];
    }
    // first: look for a project-description matching the name of the page
    list($xml,$ppage)
      = $this->find_project_description_by_name($orig_projectname);
    if ($xml)
    { wwProfileOut( __METHOD__ );
      return ProjectDescription::newFromXML($xml,$ppage);
    }
    // second: if it's a subpage, try the same for parent pages, in order
    $projectname = $orig_projectname;
    if (strpos($projectname,'/') === false)
    { return $this->find_project_by_name($pagename);
    }
    while( ($last_slash = strrpos($projectname,'/')) !== false 
          && $last_slash > 0 )
    { $projectname = substr($projectname,0,$last_slash);
      if (!array_key_exists($projectname,ProjectDescription::$project_cache))
      { list($xml,$ppage)
          = $this->find_project_description_by_name($projectname);
        if ( $xml ) // this project will put itself in the cache
          $project = ProjectDescription::newFromXML($xml,$ppage);
      }
      if (array_key_exists($projectname,ProjectDescription::$project_cache))
      { wwProfileOut( __METHOD__ );
        # it's a special, ambiguous case if we're on a subpage that 
        # doesn't have its own project and the parent doesn't use this 
        # page yet.  In that case, we return null and don't process 
        # files on this page until the editor tells us what project
        # to use.
        $project = ProjectDescription::$project_cache[$projectname];
	$page_project = $this->find_project_by_name($pagename);
	if (!in_array($pagename, $project->pages_involving_project_files()))
	{ #wwLog("find_project_given_page($pagename) returns null\n");
	  return null;
	}
	else
	{ #wwLog("find_project_given_page($pagename) returns {$project->project_name()}\n");
          wwProfileOut( __METHOD__ );
          return $project;
	}
      }
    }
    wwProfileOut( __METHOD__ );
    return null;
  }

  # create_standalone_project:
  #   project name is "Standalone?filename".
  # It's used in the case of <source-file filename="filename" standalone="yes">
  # this is a unique project containing only that source file.  WW uses
  # these kind of tags internally for inline latex files.
  public function create_standalone_project($filename)
  { $projectname = $this->create_standalone_project_name($filename);
    $projectname = ProjectDescription::normalized_project_name($projectname);
    #wwLog("create_standalone_project $projectname\n");
    if (!isset(ProjectDescription::$project_cache))
      ProjectDescription::$project_cache = array();
    if (array_key_exists($projectname,ProjectDescription::$project_cache))
      return ProjectDescription::$project_cache[$projectname];
    #wwLog("Project $projectname not found in cache - creating\n");
    $xml = "<project-description><project name=\""
      . htmlspecialchars($projectname, ENT_QUOTES|ENT_XML1) . "\">\n"
      . "  <standalone/>\n"
      . "  <source-file filename=\"" . htmlspecialchars($filename, ENT_QUOTES|ENT_XML1) 
      . "\"/>\n"
      . "</project></project-description>\n";
    return ProjectDescription::newFromXML($xml,null);
  }

  # Page titles containing '?' are permitted, but raise lots of problems in
  # MediaWiki.  So this project name could conflict with another one, but
  # if so, the user will be running into problems and wanting to change their
  # page title anyway.
  # $filename should be descriptive enough that if it's reused, it's
  # referring to the same file contents, i.e. a hash.
  # this name needs to start with capital S in case wiki is configured
  # to enforce initial capital on titles
  public function
    create_standalone_project_name($filename)
  { return "Standalone?$filename";
  }

  public function is_standalone_name($name)
  { $std = 'Standalone';
    return ((strncasecmp($name,$std,strlen($std)) === 0)
      or preg_match("/^pe-ww:[^\?]*:$std/", $name));
  }

  public function create_project_description_for_uri( $uri )
  { return "<project-description><project name=\""
      . htmlentities($uri, ENT_QUOTES|ENT_XML1) . "\">\n"
      . "  <external location=\"" . htmlentities($uri) . "\"/>\n"
      . "</project></project-description>\n";
  }

  public function is_project_uri($candidate)
  { return preg_match('/^pe-.*:/',$candidate);
  }

  public function local_uri_base()
  { global $wgServer, $wgScriptPath;
    return "pe-ww:$wgServer$wgScriptPath:";
  }

  public function uri_for_project_name( $name )
  { if ( $this->is_project_uri( $name ) )
    { return $name;
    }
    return $this->local_uri_base() . ProjectDescription::normalized_project_name( $name );
  }

  public function project_name_for_uri( $uri )
  { $base = $this->local_uri_base();
    if ( strpos( $base, $uri ) == 0 )
    { return substr( $uri, strlen( $base ) );
    }
    return $uri;
  }

  # given a project name, does the wiki have that project?  this is
  # used to decide when a project link should be red.
  public function project_is_known($vpname)
  { # '' is an error in find_project_by_name, but in this case it's 
    # just not known
    if ($vpname == '')
      return false;
    # all external project URIs are treated as known.
    if ($this->is_project_uri($vpname))
    { #wwLog("$vpname is known because it's a URI\n");
      return true;
    }
    # if the project is in the cache and nonempty, it exists.
    # NOT TRUE!
    #$project = $this->find_project_by_name($vpname,false);
    #if ($project !== null and is_array($project->project_files)
    #    and count($project->project_files) > 0)
    #{ wwLog("$vpname is known because of find_project_by_name\n");
    #wwLog("it has project files: ". serialize($project->project_files)."\n");
    #  return true;
    #}
    # if there's a project description it exists.
    list($pd,$ppage) = $this->find_project_description_by_name($vpname);
    if ($pd != '')
    { #wwLog("$vpname is known because it has a PD: $pd\n");
      return true;
    }
    # if it's the special name format for a standalone, it might not
    # exist, but let's assume we wouldn't be asking about it if it didn't
    if ($this->is_standalone_name($vpname))
    { #wwLog("$vpname is known because it's a standalone\n");
      return true;
    }
    # If that doesn't do it, no project.  No more implicit projects.

    # if no project description and no page by that name, no project.
    #$pptitle = $this->make_page_title($vpname);
    #if ($pptitle->getArticleID() == 0)
    #  return false;
    # otherwise if the page does exist, it comes to whether there are any
    # source or project files there.
    #$project = $this->find_project_by_name($vpname);
    #return (is_array($project->project_files) 
    #          and count($project->project_files) > 0);
    return false;
  }

  # general purpose function to find an XML-style element in a chunk
  # of text.
  # $tag             the tag to search for
  # $requirements    attribute-value pairs that the tag has to have
  #                  X=>null means X attribute must not be set
  # $pagetext        the text to search in
  # $offset          position to start searching
  # $nonempty        if true, return only an element that isn't empty
  #                  (i.e. for finding an archived project file)
  #
  # returns: array(first,last) where substr($pagetext,first,last-first)
  # is the element; array(false,false) if not found.
  #
  # You can pass last back in as the offset to search for another element.
  public function find_element($tag,$requirements,$pagetext,$offset=0,
                               $nonempty=false)
  { global $wwContext;
    # find one, then see if it matches the requirements
    //foreach ($requirements as $k=>$v)
    //  $reqstr .= ", $k=$v";
    //$reqstr = substr($reqstr,2);
    //$wwContext->wwInterface->debug_message( "Seeking $tag with $reqstr" );
    wwProfileIn( __METHOD__ );
    while(($openpos = stripos($pagetext,"<$tag",$offset)) !== false)
    { //$wwContext->wwInterface->record_error("Found candidate &lt;$tag "
      //  .'at position '.$openpos);
      $closepos = false;
      # is it really the right tag?
      if (!preg_match("{^<$tag\b}i",substr($pagetext,$openpos,strlen($tag)+3)))
      { $offset = $openpos + strlen($tag);
        continue;
      }
      //$wwContext->wwInterface->record_error("Found &lt;$tag at position "
      //  .$openpos);
      $sfentry = array();
      # find the >
      if (!($openendpos = strpos($pagetext,'>',$openpos)))
      { $wwContext->wwInterface->throw_error("Malformed $tag tag ");
        //continue;
      }
      # check whether it's />
      if ($pagetext{$openendpos-1} == '/')
      { $closepos = $openendpos - 1;
        //$wwContext->wwInterface->record_error('Found &lt;$tag/&gt;'
        //  ." at positions $openpos .. $openendpos");
      }
      //else
      //  $wwContext->wwInterface->record_error("Found &lt;$tag&gt;"
      //    ." at positions $openpos .. $openendpos");
      $opentag = substr($pagetext,$openpos,$openendpos + 1 - $openpos);
      # get attributes from the opening tag and compare to requirements
      if (preg_match_all('{\b(\w+)=\s*(".*?"|\'.*?\'|\S+)}',$opentag,$attrmatches,
             PREG_SET_ORDER) == false)
        $attrmatches = array();
      //$wwContext->wwInterface->record_error("Error parsing attributes in $tag tag");
      $attrs = array();
      foreach ($attrmatches as $am)
      { list($k,$v) = array($am[1],$am[2]);
        $v = trim($v,'"\'>');
        //$wwContext->wwInterface->debug_message("$k=$v");
        if ($k == 'project')
          try {
            $v = ProjectDescription::normalized_project_name($v);
          } catch (WWException $ex)
          { $v = ''; }
        $attrs[$k] = $v;
      }
      # if key=>val is a requirement, key=>val has to be found;
      # if key=>null is a requirement, key has to be absent
      $mismatch = false;
      foreach ($requirements as $key=>$val)
      { $ak = (isset($attrs[$key]) ? $attrs[$key] : null);
        if ( $ak != $val )
        { $mismatch = true;
          //$wwContext->wwInterface->record_error(
          //  "Mismatch at $key: $attrs[$key] is not $val");
          //break; # break out of this little foreach
        }
      }
      if (!$mismatch)
        break;
      # else continue in the big loop
      $offset = $openendpos;
    }
    # no luck?
    if ($openpos === false)
    { wwProfileOut( __METHOD__ );
      return array(false,false);
    }
    # found a correct opening tag
    # if it's an empty tag, done
    if ($closepos !== false)
    { wwProfileOut( __METHOD__ );
      return array($openpos,$openendpos);
    }
    # else find the closing tag
    $closepos = $openpos;
    while(1)
    { $closepos = stripos($pagetext,"</$tag",$closepos);
      if ($closepos === false)
      { $wwContext->wwInterface->record_error("Missing /$tag "
          ." in page ‘" . htmlspecialchars($pagename) . "’");
        $closepos = strlen($pagetext);
        break;
      }
      # careful - if it's </$tag-plus-this-and-that> skip it
      if (preg_match("{^</$tag\b}i",
          substr($pagetext,$closepos,strlen($tag) + 4)))
        break;
      ++$closepos;
    }
    if ($closepos === false)
    { wwProfileOut( __METHOD__ );
      return array(false,false);
    }
    # find the >
    if (!($closeendpos = strpos($pagetext,'>',$closepos)))
      $wwContext->wwInterface->throw_error("Malformed /$tag tag ");
    //$wwContext->wwInterface->record_error('Found &lt;/$tag&gt; at position '
    //  .$closepos);
    wwProfileOut( __METHOD__ );
    return array($openpos,$closeendpos);
  }

  // find the content of a source file or archived project file
  // on a wikitext page.
  // $project can be either an object or a string.
  // returns an array with keys 'text' and 'touched'.
  // 'text' is NULL if the file isn't found.
  public function find_file_content_on_page(
      $project, $fname, $fpage, $src, $as_of_revision = null)
  { wwProfileIn( __METHOD__ );
    global $wwContext;
    $touched = $this->page_mod_time($fpage);
    if ($project instanceOf ProjectDescription)
      $projectname = $project->project_name();
    else
      $projectname = $project;

    // look on the wiki page for the element
    #wwLog("Seeking filename $fname on page $fpage"
    #  . ($as_of_revision === null ? '' : " as of revision $as_of_revision")
    #  ."\n");
    $flist = $this->find_project_files_on_page($fpage, null, false, true, $as_of_revision);
    #wwLog("find_project_files_on_page($fpage, $as_of_revision) returns "
    #  . json_encode($flist) );
    if ($src)
    { if (isset($flist[$projectname]) and
          isset($flist[$projectname][$fname]))
        $fent = $flist[$projectname][$fname];
      if ((!isset($fent) or !is_array($fent)) and
          isset($flist['']) and isset($flist[''][$fname]))
        $fent = $flist[''][$fname];
      // for a source file, it needs to appear as a source-file definition
      // (i.e. with file contents), not as an empty source-file declaration.
      if (isset($fent) and is_array($fent) and
          $fent['source'] and !isset($fent['declaration-only']))
      { // acceptable? grab it.
        //$wwContext->wwInterface->debug_message("found");
        wwProfileOut( __METHOD__ );
        return array('text' => $fent['content'],
                     'touched' => $touched);
      }
    }
    else
    { if (isset($flist[$projectname]))
        foreach ($flist[$projectname] as $key=>&$ent)
          // for a project file, it needs to be archived and not be 
          // a source file.
          if ($ent['attributes']['filename'] == $fname 
              and array_key_exists('content',$ent)
              and !$ent['source'])
            $fent =& $ent;
      if (!isset($fent))
      { if (isset($flist['']) and is_array($flist['']))
          foreach ($flist[''] as $key=>&$ent)
            if ($ent['attributes']['filename'] == $fname 
                and array_key_exists('content',$ent)
                and !$ent['source'])
              $fent =& $ent;
      }
      //$fpage = $this->page_cache_key($fpage);
      //$wwContext->wwInterface->debug_message("fpage: $fpage fent: "
      //  .htmlspecialchars(print_r($fent,true)));
      //$wwContext->wwInterface->debug_message(print_r($flist[$this->project_name()],true));
      //$wwContext->wwInterface->debug_message(print_r($flist[''],true));
      if (isset($fent))
      { wwProfileOut( __METHOD__ );
        return array('text' => $fent['content'], 'touched' => $touched);
      }
    }
    wwProfileOut( __METHOD__ );
    return array('text' => null, 'touched' => epoch());
  }

  # used by catch_edits to glean which files are mentioned on the page
  # returns an array with entries such as
  # $project_files['projectname']['filename']['source'] 
  #   = (whether file 'filename' attached to project 'projectname' is a
  #      source-file)
  #
  # $text is the wikitext of the page, but it may or may not be what's
  #    in the database
  # $new is true when it's the text that's replacing the old text — we
  #    do error checking a little differently.
  #
  # this doesn't notice when one is standalone=yes
  # and it seems redundant.  should be merged with other similar functions.
  public function project_files_referenced( $text, $pagename, $new=false )
  { global $wwContext;
    wwProfileIn( __METHOD__ );
    $project_files = array();
    # first remove any project-description, to avoid confusion
    $opos = 0;
    while( ($opos = stripos($text, '<project-description',$opos)) !== false)
    { if (!preg_match( '/<project-description\b/i', $text,
                       $matches, PREG_OFFSET_CAPTURE, $opos ) )
      { ++$opos;
        continue;
      }
      $cpos = stripos($text, '</project-description',$opos);
      # could check \b but can't be bothered
      if ($cpos === false)
        $cpos = strlen($text);
      $epos = strpos($text, '>', $cpos);
      if ($epos === false)
      { $wwContext->wwInterface->record_error( "Malformed project-description "
          . "element in page " . htmlentities($pagename) . "." );
        $epos = strlen($text);
      }
      # if we get to here, remove the project-description.
      $text = substr($text,0,$opos) . substr($text,$epos+1);
    }
    # now investigate all the remaining source-file and project-file tags
    $opos = 0;
    while( ($opos = strpos($text,'<',$opos)) !== false )
    { $text = substr($text,$opos);
      #wwLog("in project_files_referenced, text: ".substr($text,0,50)." ...\n");
      $opos = 0;
      if (preg_match('/^<(source-file|project-file)\b/i', $text))
      { # distinguish source from target
        if (preg_match('/^<source-file/i', $text))
        { $source = true;
          # test whether it's a source-file definition
          if (preg_match('/^<source-file([^>]*[^\/]|)>/i', $text) > 0)
            $source_definition = true;
          else
            $source_definition = false;
          #wwLog(" looks like a source file\n");
        }
        else
        { $source = false;
          $source_definition = false;
          #wwLog(" looks like a project file\n");
        }
        # set filename attribute, if found
        if (preg_match(
              '/^<(source-file|project-file)[^>]+filename=\s*\"?(.*?)\"?[\s|\/>|>]/i',
              $text, $matches))
        { $filename = $matches[2];
          #wwLog("filename found: '$filename'\n");
        }
        else
        { if ($new)
            $wwContext->wwInterface->record_warning( 'Could not find filename for '
              . ($source ? 'source' : 'project') 
              . '-file tag.  Please check for typing errors.' );
          ++$opos;
          continue;
        }
        if (!ProjectDescription::is_allowable_filename($filename))
        { $wwContext->wwInterface->record_error("Prohibited "
            . ($source ? 'source':'project')
            ." file name ‘".htmlentities($filename)."’.");
          ++$opos;
          continue;
        }
        # set project attribute
        if (preg_match('/^<(source-file|project-file)[^>]+project=\s*\"?(.*?)\"?[\s\/>]/i',
              $text, $matches))
        { $projectname = ProjectDescription::normalized_project_name($matches[2],false);
          if ($projectname === null)
            $projectname = $matches[2];
        }
        else
          $projectname = '';
        # record its attributes
        $attrs = array('filename'=>$filename, 'source'=>$source, 
                       'def'=>$source_definition);
        if ($source_definition)
        { $attrs['page'] = $pagename;
        }
        else
        { $attrs['appears'] = array($pagename=>true);
        }
        # throwing this as an error is very disruptive to saving.
        # just report a warning.
        # I think even the warning is redundant right now.
        //if ($new and $attrs['def'] and 
        //    $project_files[$projectname][$filename]['def'])
        //  $wwContext->wwInterface->record_warning("Source file ‘{$filename}’"
        //    . " is defined more than once on page ‘{$pagename}’.");

        # is this right?  what to do if it's referenced on the page more 
        # than once (like, as a source-file and again as a project-file)?
        if (array_key_exists($projectname,$project_files) and
            array_key_exists($filename,$project_files[$projectname]))
          $attrs = array_merge($project_files[$projectname][$filename],
            $attrs);
        $project_files[$projectname][$filename] = $attrs;
        //$wwContext->wwInterface->debug_message("filename $filename "
        //  . "project '$projectname' source '$source' def '$source_definition'");
        //wwLog("filename $filename "
        //  . "project '$projectname' source '$source' def '$source_definition'\n");
      }
      ++$opos;
    }
    wwProfileOut( __METHOD__ );
    return $project_files;
  }

  # Title class caches the objects it makes, so we don't need to.
  public function validate_page_title($name)
  { global $wwContext;
    if ( class_exists( 'Title' ) )
    { $title = Title::newFromText($name);
      if (!is_object($title)) {
        $name = null;
      }
      $name = $title->getPrefixedDBKey();
    }
    else
    { if ( $name === '' )
      { $name = null;
      }
    }
    if ( $name === null )
        $wwContext->wwInterface->throw_error("Invalid page title ‘" 
          . htmlspecialchars($name) . "’");
    return $name;
  }

  # given the name of a page, call page_cache_key($name)
  # to get a normalized key for the cache.  This prevents duplication
  # in the cache.
  public function page_cache_key($title)
  { if ( $title === '' or $title === null )
      return '';
    if ( class_exists( 'Title' ) and $title instanceOf Title ) {
      return $title->getPrefixedDBKey();
    }
    return $this->validate_page_title( $title );
  }

  # load page from database into page cache
  public function cache_page_from_db($pagename)
  { global $wwContext;

    wwProfileIn( __METHOD__ );
    #wwLog("cache_page_from_db \"$pagename\"");
    #$e = new Exception(); wwLog( $e->getTraceAsString() );
    $key = $this->page_cache_key($pagename);
    # what's best to do when an article doesn't exist?
    # currently, just caching it as empty string and old mod time.
    if ((!array_key_exists($key,$this->pagetext_cache) or
        !array_key_exists('text',$this->pagetext_cache[$key])) and
        wwRunHooks('WW-CachePageFromDB', array($key)))
    { #wwLog( "pagetext keys are: " . implode(', ', array_keys( $this->pagetext_cache ) ) );
      #$e = new Exception(); wwLog( $e->getTraceAsString() );
      # Do I need to be careful with oldid as when I used to use new Article($title, /*oldid*/0)?
      try {
        $title = Title::newFromText( $pagename );
        #wwLog( "** trying WikiPage::factory( \"{$title->getPrefixedText()}\" )\n" );
        $wikipage = WikiPage::factory( $title );
        $content = $wikipage->getContent();
        if ( $content ) 
          $text = $content->getContentHandler()->getContentText( $content );
        else
          $text = '';
        $touched = $wikipage->getTouched();
      } catch ( MWException $ex )
      { $wwContext->wwInterface->record_warning( "Caught an internal exception trying to access page \"{$title->getPrefixedText()}\".  This may or may not indicate a problem." );
        $text = '';
        $touched = wfTimestampNow();
      }
      $this->pagetext_cache[$key] =
        array('text' => $wwContext->wwInterface->replace_inlines($text),
              'touched' => $touched);
      //wwLog("cache_page_from_db $pagename cached " . strlen($text) 
      //  . " characters\n");
      //$wwContext->wwInterface->debug_message("Added page '".$key
      //  ."' to cache: Touched = ".$article->getTouched());
    }
    //else
    //  $wwContext->wwInterface->debug_message("Retrieved page '".$key
    //    ."' from cache (".strlen($this->pagetext_cache[$key]['text'])
    //    ." chars): Touched = "
    //    .$this->pagetext_cache[$key]['touched']);
    wwProfileOut( __METHOD__ );
  }

  public function clear_from_cache($pagename)
  { $key = $this->page_cache_key($pagename);
    unset($this->pagetext_cache[$key]);
    #wwLog("removed $key from pagetext_cache\n");
  }

  // retrieve content of a page from the page cache
  public function retrieve_page($pagename, $as_of_revision=null)
  { if ($as_of_revision !== null)
    { $title = Title::newFromText($pagename);
      $db = wfGetDB( DB_SLAVE );
      #$db->debug(true);
      # select revisions of the page before the one named
      $fields = array_merge(Revision::selectFields(), Revision::selectTextFields(),
        array( 'page_namespace', 'page_title' ) );
      #wwLog( "get revs of {$pagename}\n" );
      $res = $db->select( 
        array('page','revision','text'),
        $fields,
        array( 'page_namespace' => $title->getNamespace(),
               'page_title' => $title->getDBKey(),
               'page_id=rev_page',
               'old_id=rev_text_id',
               'rev_id <= '.$as_of_revision ),
        __FUNCTION__,
        array('ORDER BY rev_text_id') );
      $revs = $db->resultObject($res);
      unset($last_row);
      while ( ($row = $revs->next()) !== false )
      { #wwLog( "row: " . serialize($row) . "\n");
        $last_row = $row;
      }
      if (isset($last_row))
      { $rev = new Revision($last_row);
        #wwLog("retrieve_page() found revision " . $rev->getID()."\n");
        $deleted = $rev->isDeleted( 15 );
        if ($deleted)
        { #wwLog("revision ".$rev->getID()." is deleted.\n");
          return null;
        }
        # forget this, if it's deleted it shouldn't get found
        if ($deleted)
        { $res = $db->select( array('logging'),
            array('log_timestamp'),
            array('log_type' => 'delete',
                  'log_namespace' => $title->getNamespace(),
                  'log_title' => $title->getDBKey()),
            __FUNCTION__ );
          $logs = $db->resultObject($res);
          if ($logs->numRows() == 0)
          { $deleted = false;
            #wwLog(" ... but not as of revision $as_of_revision.\n");
          }
        }
        if (!$deleted)
        { # don't cache
          $pagetext = $rev->getText();
          #wwLog("retrieve_page returns \"" 
          #  . (strlen($pagetext) > 19 ? substr($pagetext,0,19).'...' : $pagetext)
          #  . "\".\n");
          return $pagetext;
        }
      }
      #wwLog("retrieve_page returns null.\n");
      return null;
    } else {
      $key = $this->page_cache_key($pagename) ;
      if ( ! isset( $this->pagetext_cache[ $key ] ) or
            ! isset( $this->pagetext_cache[ $key ][ 'text' ] ) ) {
        //$wwContext->wwInterface->debug_message( 
        //  "cache from db via retrieve_page($pagename)" );
        $this->cache_page_from_db($pagename);
      }
      return $this->pagetext_cache[ $key ][ 'text' ];
    }
  }

  // last-modified-time of page, using page cache.
  // it will be now, if the page is being submitted.
  // 1/1/1970 if the page isn't found.
  public function page_mod_time($pagename)
  { $key = $this->page_cache_key($pagename);
    if ( $key === '' )
      return wfTimestampNow();
    if ( ! isset( $this->pagetext_cache[$key] ) or
         ! isset( $this->pagetext_cache[$key]['touched'] ) ) {
      #wwLog( "cache from db via page_mod_time($pagename)" );
      #$e = new Exception;
      #wwLog("Backtrace: " . $e->getTraceAsString());
      $this->cache_page_from_db($key);
    }
    if (isset($this->pagetext_cache[$key]))
      return $this->pagetext_cache[$key]['touched'];
    return wfTimestamp(TS_MW,epoch());
  }

  // special case : the page currently being displayed might be
  // coming from an edit or preview (or view old revision?), so
  // we'll get wrong results if we use the stored version of that
  // page.  Catch the relevant version at the beginning of parsing 
  // and get it into our page cache.
  // It may also come from a .wikitext file that's not on any page...
  public function
    cache_text_directly( $pagetext, $pagename, $modtime=null  )
  { wwProfileIn( __METHOD__ );
    global $wwContext;
    $key = $this->page_cache_key($pagename);
    #wwLog("cache_text_directly \"$key\": [[$pagetext]]");
    if (!isset($this->pagetext_cache[$key]))
      $this->pagetext_cache[$key] = array();
    // if it comes from the database it has a modtime.  If not, it's new.
    if (is_null($modtime))
      $modtime = wfTimestamp(TS_MW, wfTimestampNow());
    // trust the caller to do this beforehand
    //$pagetext = $wwContext->wwInterface->replace_inlines($pagetext);
    // append, don't replace, in case it's called more than once.
    // this is to be searched in for xml elements, not to be displayed,
    // so it's okay if multiple things get piled in.
   
    // try an audacious modification: only use the first submission
    //if (isset($this->pagetext_cache[$key]))
    //{ $wwContext->wwInterface->debug_message( "Page $key already cached." );
    //  return;
    //}
    if (!isset($this->pagetext_cache[$key]['text']))
      $this->pagetext_cache[$key]['text'] = '';
    $this->pagetext_cache[$key]['text'] .= $pagetext;
    $this->pagetext_cache[$key]['touched'] = $modtime;
    $this->pagetext_cache[$key]['project-files'] = null;
    #wwLog("cache_text_directly $key"
      #. ":\n[[" . $this->pagetext_cache[$key]['text'] . "]]"
      #. " added " .strlen($pagetext) . " characters to pagetext_cache"
      #. "\n");
    wwProfileOut( __METHOD__ );
  }

  # find all the source-file and project-file elements in a
  # page's text, and store them in the page cache.
  # The cache will be queried later for each of the source files, etc.
  #
  # Arguments:
  #  $pagename   page to be parsed.  the result will be cached with that
  #              page's information, if $pagename is non-null.
  #  $pagetext   text to be parsed, if present.  if $pagetext is null, the 
  #              text will be retrieved from the database.
  #
  # Return value is an array $pflist, with
  #  $pflist[$projectname][$filename] =
  #    array( 'attributes' => array('filename'=>$filename,...),
  #           'source' => true,
  #           'content' => [source file text],
  #           'position' => array( [start], [end] ) )
  # for a source-file, and
  #  $pflist[$projectname][n] =
  #    array( 'attributes' => array('filename'=>xyz,...),
  #           'source' => false,
  #           'content' => [project file text, if archived],
  #           'position' => array( [start], [end] ) )
  # for a project-file, where n is a whole number.
  # 
  # The special projectname '' is used for file tags that don't specify
  # a project name.  We'll figure out later what project to use with them.
  #
  # Source file declarations rather than definitions, i.e.
  # <source-file filename="X"/> to flag a file that's defined somewhere 
  # else, are included with 'declaration-only'=>true rather than 
  # 'content'.  [This is obsolete - don't use it in new pages.]
  #
  # 'position' records the character positions of the first '<' and last
  # '>' of the element. 
  #
  # if $extras is true, it also identifies certain other parts of the text
  # that are skipped by replace_inlines() because they can contain $$...$$
  # or other such constructs that should be left alone.
  #
  # FIXME the 'extras' and 'cache-filled' are mixed with project names, 
  # could collide
  public function find_project_files_on_page(
      $pagename,$pagetext=null,$extras=false, $report_errors=true, $as_of_revision=null)
  { global $wwContext;
    wwProfileIn( __METHOD__ );
    if ($pagename === null and $pagetext === null)
    { $wwContext->wwInterface->throw_error("Internal error: either pagename or pagetext"
        . " must be supplied to $this->find_project_files_on_page().\n");
    }
    $key = $this->page_cache_key($pagename);
    #wwLog("find_project_files_on_page($pagename, ("
    #  .strlen($pagetext) ." chars), $extras, $as_of_revision)\n");

    if ($pagename !== null and $as_of_revision === null and $extras === false)
    { if ( isset($this->pagetext_cache[$key]) and
          isset($this->pagetext_cache[$key]['project-files']) and
	  isset($this->pagetext_cache[$key]['project-files']['cache-filled']) and
	  $this->pagetext_cache[$key]['project-files']['cache-filled'] )
      { wwProfileOut( __METHOD__ );
        return $this->pagetext_cache[$key]['project-files'];
      }
      # else
      //$wwContext->wwInterface->debug_message("pagetext_cache['$key']['project-files']"
      //  ."['cache-filled'] = false" );
      if (!isset($this->pagetext_cache[$key]))
        $this->pagetext_cache[$key] = array();
      if (!isset($this->pagetext_cache[$key]['project-files']))
        $this->pagetext_cache[$key]['project-files'] = array();
      $retval =& $this->pagetext_cache[$key]['project-files'];
    }
    else
      $retval = array();

    if ($pagetext === null)
    { #wwLog("Retrieve page $pagename - key $key\n");
      $pagetext = $this->retrieve_page($key, $as_of_revision);
    }

    # I've tried several ways of extracting the source-file
    # elements - reading the whole page into an XML parser didn't
    # work easily, so I was doing regular expression matches,
    # but that fails when the text is over a few KB long, so now
    # I'm doing a third way using strpos().  It's probably faster
    # anyway.

    # XML parser code
    # encase the page text to make the XML parser comfortable
    //$xmltext = "<?xml version='1.0' standalone='yes'?"
    //  .">\n<xml-outer>\n".$pagetext."</xml-outer>\n";
    //$wwContext->wwInterface->debug_message("attempt to parse:\n"
    //  .htmlspecialchars($xmltext)."\n");
    //$xml_obj = new SimpleXMLElement($xmltext);
    //$wwContext->wwInterface->debug_message("xml_obj:\n".print_r($xml_obj,TRUE));

    //$wwContext->wwInterface->debug_message("page $pagename before cleaning up is: "
    //  . htmlentities($pagetext));

    $origtext = $pagetext;

    # get rid of any project-description that might be on the page,
    # because it tends to confuse the search.  replace it with spaces,
    # so the positions of the other elements will be correct.

    //$ln = strlen($pagetext);
    //$pagetext = preg_replace(
    //    '/(<project-description\b.*?<\/project-description\b.*?'.'>)/ise',
    //    'str_repeat(" ",strlen("$1"))', $pagetext);
    //$wwContext->wwInterface->debug_message("Cleaning project description from "
    //  .$pagename.": shrank from ".$ln." to ".strlen($pagetext));

    if (preg_match_all(
         '/<project-description\b.*?<\/project-description\b.*?'.'>/ise',
         $pagetext, $matches_pd, PREG_OFFSET_CAPTURE) === false)
      $wwContext->wwInterface->record_error('Internal error in string matching '
        . 'in find_project_files_on_page()');

    # get rid of <nowiki> segments and HTML comments the same way.  
    # Can't use StringUtils::delimiterReplace() because it won't preserve  
    # the positions in the file.
    
    if (preg_match_all( '/<nowiki\b.*?<\/nowiki>/is', $pagetext, $matches_nw,
                        PREG_OFFSET_CAPTURE ) === false )
      $wwContext->wwInterface->record_error('Internal error in preg_match_all for nowiki');
    if (preg_match_all( '/<!--.*?-->/s',$pagetext,$matches_c,
                          PREG_OFFSET_CAPTURE ) === false)
      $wwContext->wwInterface->record_error('Internal error in preg_match_all for comments');
    # careful here not to match <source-file
    if (preg_match_all( '/<source(\s.*?'.'>|>).*?<\/source\s*>/is',$pagetext,$matches_s,
                          PREG_OFFSET_CAPTURE ) === false)
    { $wwContext->wwInterface->record_error('Internal error in preg_match_all for source');
      #wwLog("pagetext is $pagetext\n");
    }
    if (preg_match_all( '/<syntaxhighlight\b.*?<\/syntaxhighlight\s*>/is',$pagetext,$matches_sh,
                          PREG_OFFSET_CAPTURE ) === false)
      $wwContext->wwInterface->record_error('Internal error in preg_match_all for syntaxhighlight');
    if (preg_match_all( '/<syntaxhighlight_mk\b.*?<\/syntaxhighlight_mk\s*>/is',$pagetext,$matches_sm,
                          PREG_OFFSET_CAPTURE ) === false)
      $wwContext->wwInterface->record_error('Internal error in preg_match_all for syntaxhighlight_mk');
    if (preg_match_all( '/<pre\b.*?<\/pre>/is',$pagetext,$matches_p,
                          PREG_OFFSET_CAPTURE ) === false)
      $wwContext->wwInterface->record_error('Internal error in preg_match_all for pre');

    $matches = array_merge($matches_pd[0],$matches_nw[0],$matches_c[0],
      $matches_s[0], $matches_sh[0], $matches_sm[0], $matches_p[0]); 
    if ($extras)
      $retval['extras'] = $matches;
    #wwLog("find project files in $pagename\n");
    #wwLog('matches: '.print_r($matches,true)."\n");
    foreach ($matches as &$match)
      if (count($match))
      { $len = strlen($match[0]);
        $pagetext = substr($pagetext,0,$match[1]) 
          . str_repeat(' ',$len) . substr($pagetext,$match[1]+$len);
      }

    //$wwContext->wwInterface->debug_message("page $pagename after cleaning up is: "
    //  . htmlentities($pagetext));

    # regular expression code

    $sftext = null;
    
    //$wwContext->wwInterface->debug_message("Look for source-files on page '".$pagename
      //."'<br/>[".htmlentities( substr($pagetext,0,40).'...'.substr($pagetext,-60) ).']');
    #  . $pagename . ' ['.htmlentities($pagetext).']');

    # now strpos version
    $offset = 0;
    while(($openpos = stripos($pagetext,"<",$offset)) !== false)
    { //$wwContext->wwInterface->debug_message("Found candidate &lt;$tag "
      //  .'at position '.$openpos);
      $closepos = false;
      $closeendpos = false;
      # some extra chars here to avoid breakage in case the tags change
      # or something
      $n30 = substr($pagetext,$openpos,30);
      if (preg_match('{^<source-file\b}i', $n30))
      { $source = true;
        $tag = 'source-file';
      }
      else if (preg_match('{^<project-file\b}i', $n30))
      { $source = false;
        $tag = 'project-file';
      }
      else
      { if ($report_errors)
        { if (preg_match('{^</source-file\b}i', $n30))
            $wwContext->wwInterface->record_warning("Unmatched &lt;/source-file&gt; found "
              . " on page ‘{$pagename}’.");
          else if (preg_match('{^</project-file\b}i',$n30))
            $wwContext->wwInterface->record_warning("Unmatched &lt;/project-file&gt; found "
              . " on page ‘{$pagename}’.");
        }
        $offset = $openpos + 1;
        continue;
      }
      //$wwContext->wwInterface->debug_message("Found &lt;$tag at position ".$openpos);
      $sfentry = array();
      # get attributes from the opening tag
      if (($openendpos = strpos($pagetext,'>',$openpos)) === false)
      { $wwContext->wwInterface->record_warning("Malformed $tag tag "
          . ($pagename =='' ? '' : " in page ‘{$pagename}’"));
        break;
        //continue;
      }
      $opentag = substr($pagetext, $openpos, $openendpos - $openpos + 1);
      if (substr($pagetext,$openendpos-1,1) == '/')
      { $closepos = $openendpos - 1;
        $closeendpos = $openendpos;
        $openendpos = $openendpos - 1;
        //$wwContext->wwInterface->debug_message("Found &lt;$tag/&gt;"
        //  ." at positions $openpos .. $openendpos");
      }
      //else
      //  $wwContext->wwInterface->debug_message("Found &lt;$tag&gt;"
      //    ." at positions $openpos .. $openendpos");
      //$wwContext->wwInterface->debug_message("Found " . htmlspecialchars($opentag)
      //  . "at $openpos");
      $opentagtext = substr($pagetext,$openpos,$openendpos - $openpos);
      if (preg_match_all('{(\w+)=\s*(".*?"|\'.*?\'|\S+)}',$opentagtext,
            $attrmatches, PREG_SET_ORDER) === false and $report_errors)
        $wwContext->wwInterface->record_error("Error parsing attributes in $tag "
          ."tag in page ‘{$pagename}’");
      foreach ($attrmatches as $am)
      { list($attrname,$attrval) = array($am[1],trim($am[2],'"\'>'));
        if ($attrname == 'project')
        { $pn = ProjectDescription::normalized_project_name( $attrval, false );
          if ( $pn !== null )
            $attrval = $pn;
        }
        $sfentry['attributes'][$attrname] = $attrval;
        //$wwContext->wwInterface->debug_message("$am[1]=$am[2]");
      }
      if ($closepos === false)
      { # find the closing tag
        # search for four things coming after the opening tag:
        # <source-file>, <project-file>, </source-file>, </project-file>
        # but note we do not want to find <project-file/> 
        # (and if we let <source-file/> through, it's not a big deal)
        $close_found = preg_match(
          '/<\/?(source|project)-file(\b.*?[^\/]|\b|)>/i',
          $pagetext, $close_matches, PREG_OFFSET_CAPTURE, $openpos+1); 
        $check = false;
        if ($close_found)
        { $check = stripos($close_matches[0][0],"</$tag");
          #wwLog("check is $check, close_matches is " . serialize($close_matches) . "\n");
          if ($check === 0)
            $closepos = $close_matches[0][1];
          else
          { if ($report_errors)
              $wwContext->wwInterface->record_warning("Tag " . htmlspecialchars($opentag)
                . " is missing its closing tag in page '{$pagename}'");
            $closepos = $closeendpos = $close_matches[0][1] - 1;
          }
        }
        if ($closepos === false)
        { if ($report_errors)
            $wwContext->wwInterface->record_error("Missing &lt;/$tag&gt;"
              ." in page ‘{$pagename}’");
          $closepos = strlen($pagetext);
        }
      }
      //$wwContext->wwInterface->debug_message("Closing tag is at $closepos");
      
      if ($closepos > $openendpos)
      { # extract text between the tags
        # first the special case that open tag is followed by newline:
        # don't include the newline.
        if (substr_compare($pagetext,"\n",$openendpos+1,1) === 0)
          $filestart = $openendpos + 2;
        else 
          $filestart = $openendpos + 1;
        $sfentry['content'] = substr($origtext,$filestart,$closepos-$filestart);
        if ($source)
          unset($sfentry['declaration-only']);
      }
      else if ($source)
      { // case of <source-file filename="X"/> - it's important in making
        // implicit project description but should be ignored when finding
        // source files' content
        $sfentry['content'] = null;
        $sfentry['declaration-only'] = true;
      }
      $sfentry['source'] = $source;
      if ($closeendpos === false)
      { $closeendpos = strpos($pagetext,'>',$closepos);
        if ($closeendpos === false)
          $closeendpos = $closepos;
      }
      //$wwContext->wwInterface->debug_message("Found &lt;/$tag&gt; at position "
      //  . "$closepos .. $closeendpos");
      if ( ! isset( $sfentry['position'] ) ) {
	      $sfentry['position'] = array();
      }
      $sfentry['position'][] = array( $openpos, $closeendpos );
      # put it all in the cache
      if (isset($sfentry['attributes']) and isset($sfentry['attributes']['filename']))
        $filename = $sfentry['attributes']['filename'];
      else
        $filename = '';
      ## don't report this here, do it when viewing the source-file
      #if (!$filename)
      #  $wwContext->wwInterface->record_error("Source file without a "
      #    . "filename found on page ‘{$pagename}’");
      if (isset($sfentry['attributes']['project']))
        $sfprojname = $sfentry['attributes']['project'];
      else
        $sfprojname = '';
      #wwLog( "Cache $tag: [$key] [$sfprojname] [$filename]" );
      if (!array_key_exists($sfprojname,$retval))
        $retval[$sfprojname] = array();
      //global $warned;
      if ( $pagename !== null and isset($retval[$sfprojname][$filename]) 
           and $source and !isset($sfentry['attributes']['standalone'])
           and !isset($sfentry['declaration-only'])
           and !isset($retval[$sfprojname][$filename]['declaration-only'])
           and $report_errors)
           //and !isset($warned["$filename?$sfprojname"]) )
      { //$wwContext->wwInterface->debug_message("Identified a duplicate: array keys are "
        //  . print_r(array_keys($retval[$sfprojname]),true));
	wwLog( "skipping duplicate source-file $filename" );
        $wwContext->wwInterface->record_warning("Duplicate source-file ‘"
          . htmlentities($filename) . "’"
          . ($sfprojname == '' ? '' : ", project name ‘"
            . htmlentities($sfprojname) . "’,")
          . " on page ‘" . htmlentities($key) . "’");
        # don't warn multiple times, this can be an annoyance when the
        # page sometimes gets double-cached during saving or something
        //$warned["$filename?$sfprojname"] = true;
      }
      else
      { if ($source)
        { if ( isset( $retval[$sfprojname][$filename] ) )
	    $retval[$sfprojname][$filename]['position'][] = $sfentry['position'][0];
          else
            $retval[$sfprojname][$filename] = $sfentry;
	}
        else
          $retval[$sfprojname][] = $sfentry;
        //$wwContext->wwInterface->debug_message("Cached $tag "
        //  . "$filename, project '{$sfprojname}', page '$key'.  Array keys are "
        //  . print_r(array_keys($retval[$sfprojname]),true));
        //$wwContext->wwInterface->debug_message("sfentry is " . print_r($sfentry, true));
      }
      $offset = $closeendpos;
    }
    //if ($pagename !== null)
    { $retval['cache-filled'] = true;
      //$wwContext->wwInterface->debug_message( "Filled cache for $pagename: "
      //  . htmlentities(print_r($this->pagetext_cache[$key],true)) );
    }
    wwProfileOut( __METHOD__ );
    return $retval;
  }

  # the places to look for a source file when its page location isn't 
  # specified in the project description.  '?P' is replaced by the project
  # name and '?F' is replaced by the filename, and if the page is in
  # the Image: (aka File:) namespace, any '/' in the name after expanding
  # should be changed to '$'.
  # If the filename contains a '/', the defaults should also be tried
  # using only the final component of the filename.
  public function default_locations()
  { $ins = MWNamespace::getCanonicalName( NS_IMAGE );
    return array("$ins:?P$?F", "$ins:?P?F", "$ins:?F",
      '?P/?F', '?P?F', '?P', '?P/', '?F');
  }

  # locate a source-file or project-file element and get its contents,
  # whether we have an explicit page for it in the project description or not
  # $filename and $project must be given
  # $pagename can be null if not known
  # $src is true to find a source-file definition, 
  #   false to find an archived project-file
  # returns array('type'=>type, ...)
  # where type is one of 'tag', 'file', 'not found', depending on whether
  # the file is in a wikitext page, on a File: (Image:) page, or nowhere.
  # The array additionally contains
  #   'page' => page location, unless it's not found
  #   'text' => file contents, if it's on a wikitext page
  #   'touched' => last modified time, if it's on a wikitext page
  public function 
    find_file_content($filename, &$project, $pagename, $src, $as_of_revision=null)
  { global $wwContext;
    #wwLog("seeking "
    #  . ($src ? 'content of source file':'archived project file')
    #  . " {$filename}, project {$project->project_name()}"
    #  . ($pagename === null ? '' : ", page '$pagename'")
    #  . ($as_of_revision === null ? '' : ", as of $as_of_revision")
    #  . "..." );
    # If the page is given, it's easy.
    wwProfileIn( __METHOD__ );
    if ($pagename !== null)
    { #wwLog("Page name $pagename explicitly given.\n");
      $title = Title::newFromText($pagename);
      if( is_object($title) and NS_MEDIA == $title->getNamespace() )
        $title = Title::makeTitle( NS_IMAGE, $title->getDBkey() );
      if (is_object($title) and $title->getNamespace() == NS_IMAGE)
      { wwProfileOut( __METHOD__ );
        return array('type'=>'file', 'page'=>$pagename);
      }
      #else
      $sftt = $this->find_file_content_on_page($project,$filename,$pagename,$src, $as_of_revision);
      if ( ! isset($sftt['text']) )
      { #$wwContext->wwInterface->record_error('Could not locate source file '
        #  . "‘{$filename}’ on page $pagename.");
        wwProfileOut( __METHOD__ );
        return array('type'=>'not found');
      }
      else
      { list($sftext,$pagetouched)
          = array($sftt['text'],$sftt['touched']);
        wwProfileOut( __METHOD__ );
        return array('type'=>'tag', 'page'=>$pagename,
          'text'=>$sftext, 'touched'=>$pagetouched);
      }
    }
    else
    { # if the page isn't given, we have to look for it
      #$locations = $this->default_locations_for_file($filename,$project);
      $locations = $project->default_locations_for_file($filename);
      foreach ($locations as $pagename)
      { $title = Title::newFromText($pagename);
        if( is_object($title) and NS_MEDIA == $title->getNamespace() )
          $title = Title::makeTitle( NS_IMAGE, $title->getDBkey() );
        if (is_object($title) and $title->getNamespace() == NS_IMAGE)
        { $pagename = str_replace('/','$',$title->getPrefixedDBKey());
          $title = Title::newFromText($pagename);
        }
        #wwLog("Try default location '$pagename'.");
        if (is_object($title) and ($title->getNamespace() == NS_IMAGE))
        { if ($title->getArticleID() != 0)
          { #wwLog("Found at page {$title->getPrefixedText()}.");
            wwProfileOut( __METHOD__ );
            return array('type'=>'file', 'page'=>$pagename);
          }
        }
        else
        { $sftt = $this->find_file_content_on_page($project,$filename,$pagename,$src, $as_of_revision);
          if (!is_null($sftt['text']))
          { #wwLog("Found at page {$pagename}.");
            wwProfileOut( __METHOD__ );
            return array('type'=>'tag', 'page'=>$pagename,
              'text'=>$sftt['text'], 'touched'=>$sftt['touched']);
          }
        }
      }
    }
    #wwLog( "file contents not found" );
    wwProfileOut( __METHOD__ );
    return array('type'=>'not found');
  }

  # save a source-file or project-file into a wiki page permanently.
  # If the page doesn't exist or doesn't have this source file or 
  # project file already on it, it'll be added.  if it's already there,
  # it's replaced.
  # projectname should be normalized already, or it'll insert it when
  # it means to leave it out, i.e. when it's the same as the page name.
  # Returns: true for success, false otherwise.
  # in non-success case, $details is set to array of arrays, like
  # getUserPermissionsErrors does
  public function insert_file_element_in_page( 
    $src, $archived, $filename, $projectname, &$content, $title, 
    $extra_attrs='' )
  { global $wgUser;
    if ( ! wwfOKToEditPage( $title, $details ) )
      return false;
    wwProfileIn( __METHOD__ );
    $pagename = $title->getPrefixedDBKey();
    #wwLog("inserting ". ($src? 'source': 'project') ." file $filename into {$pagename}\n");
    $this->clear_from_cache($pagename);
    # possible race condition between checking here and editing later?
    if ( $title->exists() )
    { $article = new Article( $title, 0 );
      $pagetext = $article->getContent();
    }
    else
    { $article = new Article( $title, 0 );
      $pagetext = '';
    }
    $result = $this->insert_file_element_in_page_text(
      $src, $archived, $filename, $projectname, $content, $pagetext, $pagename );
    if ( $result === false )
      return false;

    list($pagetext, $verb) = $result; 
    $noun = ($src ? 'source file' :
      ($archived ? 'archived ' : '') . 'project file');
    $article->doEdit( $pagetext, 
      "WorkingWiki $verb $noun ‘{$filename}’", /*flags*/ 0 );
    # I don't remember why this is called here.  There's an issue where
    # it might not work because of doEdit() and invalidateCache() happening
    # within the same second.  The importer in ImportProjectFiles does
    # this a different way now.  Do we need to do that here?
    if ( $title->getNamespace() != NS_MEDIA and $title->getNamespace() != NS_SPECIAL )
      $title->invalidateCache();
    wwProfileOut( __METHOD__ );
    return true;
  }

  /* inner function for insert_file_element_in_page.
   * is also used by ImportProjectFiles class, to stack up several
   * insertions while only saving the page once.
   *
   * returns false or array($pagetext, $verb) where $verb is 
   * 'inserted' or 'appended'.
   */
  public function insert_file_element_in_page_text( 
    $src, $archived, $filename, $projectname, &$content, $pagetext, $pagename,
    $extra_attrs='' )
  { global $wwContext;
    //wwLog( "Insert file element in page text: "
    //  . ($archived ? 'archived ':'') . ($src ? 'source':'project') . 'file,'
    //  . " filename $filename, project ". print_r($projectname, true) . "\n" );
    if ( ! ProjectDescription::is_allowable_filename($filename) )
    { if ($report_errors)
        $wwContext->wwInterface->record_error("Rejected filename ‘"
          . htmlentities($filename) . "’.");
      return false;
    }
    global $wwMaxInsertFileSize;
    if ($content !== null and $wwMaxInsertFileSize > 0 
        and strlen($content) > $wwMaxInsertFileSize)
    { if ($report_errors)
        $wwContext->wwInterface->record_error("Content of file '" 
          . htmlentities($filename) . "' exceeds "
          ."the maximum allowed length of $wwMaxInsertFileSize." );
      // nobody gets to see the error message, and should I 
      // remove the tag altogether?
      //FIXME throw an exception!
      //return false;
      $content = '';
    }
    if ($projectname != '')
      try
      { $projectname = ProjectDescription::normalized_project_name($projectname);
      } catch (WWException $ex)
      { if ($report_errors)
          $wwContext->wwInterface->record_error(
            $wwContext->wwInterface->message('bad-project-name', htmlentities($projectname)));
        return false;
      }
    else # in case it's null or false
      $projectname = '';
    # normalize the page name as well, in case of capitalization, spaces etc.
    if ($pagename != '')
      $pagename = ProjectDescription::normalized_project_name($pagename);
    $search_attrs = array('filename' => $filename);
    if ($projectname != '')
      $search_attrs['project'] = $projectname;
    list($elst,$elend) = $this->find_element( 
      ($src?'source-file':'project-file'), $search_attrs, $pagetext, 0, $archived);
    if ($elst === false and $projectname != '')
    { $search_attrs['project'] = null;
      #wwLog("project name ''\n");
      list($elst,$elend) = $this->find_element( 
        ($src?'source-file':'project-file'), $search_attrs, $pagetext, 0, $archived);
      # if not found with project= omitted, include project= when inserting
      if ($elst === false)
        $search_attrs['project'] = $projectname;
    }
    # if looking for an archived project file element on the page failed,
    # look for a non-archived one, we'll make it into an archived one.
    if ($archived and $elst === false)
    { $search_attrs['project'] = $projectname;
      #wwLog("project name '$projectname', not archived\n");
      list($elst,$elend) = $this->find_element(
        'project-file', $search_attrs, $pagetext, 0, false);
      if ($elst === false and $projectname != '')
      { $search_attrs['project'] = null;
        #wwLog("project name '', not archived\n");
        list($elst,$elend) = $this->find_element(
          'project-file', $search_attrs, $pagetext, 0, false);
        if ($elst === false)
          $search_attrs['project'] = $projectname;
      }
    }
    $insert_attrs = array();
    if ($elst !== false)
    { $ranglepos = strpos($pagetext,'>',$elst);
      $opentag = substr($pagetext,$elst,$ranglepos-$elst+1);
      #wwLog("found $opentag\n");
      if (preg_match_all('{\b(\w+)=\s*(".*?"|\'.*?\'|[^\s/]+)}',$opentag,$attrmatches,
             PREG_SET_ORDER) == false)
        $attrmatches = array();
      //$wwContext->wwInterface->record_error("Error parsing attributes in $tag tag");
      foreach ($attrmatches as $am)
        if ($am[2] !== null)
        { //$wwContext->wwInterface->record_error("$am[1]=$am[2]");
          $am[2] = trim($am[2],'"\'>');
          $insert_attrs[$am[1]] = $am[2];
        }
      #wwLog("opentag is [$opentag], attrs_text is [$attrs_text]\n");
    }
    else
      foreach ($search_attrs as $key=>$val)
        if ($val !== null and ($key != 'project' or $val != $pagename))
          $insert_attrs[$key] = $val;
    if (!is_array($extra_attrs))
    { $ea_array = array();
      if (strpos($extra_attrs, '=') !== false)
        foreach (explode(' ', trim($extra_attrs)) as $kv)
        { $kva = explode('=', $kv, 2);
          $ea_array[$kva[0]] = trim($kva[1], '"');
        }
      $extra_attrs = $ea_array;
    }
    $insert_attrs = array_merge($insert_attrs, $extra_attrs);
    $attrs_text = '';
    foreach ($insert_attrs as $k => $v)
      $attrs_text .= " $k=\"$v\"";
    if ($src) 
      $element =
        "<source-file$attrs_text>\n"
        . $content . "</source-file>";
    else if ($archived)
      $element = "<project-file$attrs_text>\n"
        . $content . "</project-file>";
    else
      $element = "<project-file$attrs_text/>";
    if ($elst === false)
    { if ($pagetext !== '' and $pagetext[strlen($pagetext)-1] != "\n")
        $pagetext .= "\n";
      $pagetext .= $element . "\n";
      $verb = 'appended';
    }
    else
    { $pagetext 
        = substr($pagetext,0,$elst) . $element . substr($pagetext,$elend+1);
      $verb = 'inserted';
    }
    //wwLog( "$verb.\n" );
    return array($pagetext, $verb);
  }

  # Remove source or project file element from its page in the wiki.
  # If there is nothing else on the page, and we are allowed to
  # (generally, you have to be a sysop), remove the page as well.
  #
  # returns true if it did something
  public function remove_element_from_wiki($project,$filename,$src,$pagename)
  { global $wwContext;
    # first find out what page it's on
    #$pf = $project->project_files[$filename];
    wwProfileIn( __METHOD__ );
    #wwLog("remove element from wiki: $filename $src $pagename\n");
    $title = Title::newFromText($pagename);
    if (!is_object($title))
    { $wwContext->wwInterface->record_error("Problematic page title ‘"
        . htmlspecialchars($pagename) . "’.");
      //wwLog("Problematic page title ‘{$pagename}’\n");
      wwProfileOut( __METHOD__ );
      return false;
    }
    if( NS_MEDIA == $title->getNamespace() ) 
      $title = Title::makeTitle( NS_IMAGE, $title->getDBkey() );
    if ($title->getNamespace() == NS_IMAGE)
    { # in this case, it's on an Image: page
      $wwContext->wwInterface->record_message(
        'Did not remove file \'' . htmlspecialchars($filename)
        . '\', because automatic deletion of image pages is not currently supported.' );
      wwProfileOut( __METHOD__ );
      return true;
    }
    # else it's on a text page
    $article = new Article($title, 0);
    $pagetext = $article->getContent();
    $pdstart = false;
    if ($project !== null)
    { list($pdstart,$pdend) =
        $this->find_element($src?'source-file':'project-file',
          array('filename'=>$filename,'project'=>$project->project_name()),
          $pagetext,0);
      //wwLog("with project name, found $pdstart .. $pdend\n");
    }
    if ($pdstart === false)
    { list($pdstart,$pdend) =
        $this->find_element($src?'source-file':'project-file',
          array('filename'=>$filename),$pagetext,0);
      //wwLog("without project name, found $pdstart .. $pdend\n");
    }
    if ($pdstart === false)
    { # it's not there
      $wwContext->wwInterface->record_message("File '" . htmlspecialchars($filename)
        . "' not found on page " . htmlspecialchars($pagename) . '.');
      wwProfileOut( __METHOD__ );
      return true;
    }
    # if we get to here, we've got it and we mean to take it out.
    $details = null;
    if ( !wwfOKToEditPage($title, $details) )
    { wwProfileOut( __METHOD__ );
      global $wgUser;
      $wwContext->wwInterface->record_error("User " . htmlspecialchars($wgUser->getName())
        . " does not have permission to edit the wiki.");
      return false;
    }
    # is there a race condition?
    $pagetext = substr($pagetext,0,$pdstart) . substr($pagetext,$pdend+1);
    $comment = "WorkingWiki removed " . ($src?'source':'project')
        . " file ‘{$filename}’";
    if (preg_match('/\S/', $pagetext))
      $article->doEdit( $pagetext, $comment, EDIT_UPDATE );
    else # if nothing left, delete the page
    { # Better double-check that it hasn't been deleted yet!
      $dbw = wfGetDB( DB_MASTER );
      $conds = $title->pageCond();
      $latest = $dbw->selectField( 'page', 'page_latest', $conds, __METHOD__ );
      if ( $latest === false ) {
        $wwContext->wwInterface->record_error("Cannot delete page "
          .htmlspecialchars($pagename) . " because it has been deleted.");
        wwProfileOut( __METHOD__ );
        return true;
      }
      $article->doDelete( $comment, false );
    }
    $this->clear_from_cache($pagename);
    wwProfileOut( __METHOD__ );
    return true;
  }

  # delete project from wiki by deleting its project-description page.
  # user has to have permission to delete pages.
  public function delete_project( $project )
  { wwProfileIn( __METHOD__ );
    global $wwContext;
    $pdpage = $project->project_description_page;
    if (!$pdpage)
    { $wwContext->wwInterface->record_message( "Project description for "
        . htmlspecialchars($project->project_name())
        . " not found.");
      return true;
    }
    $title = Title::newFromText($pdpage);
    if (!is_object($title))
    { $wwContext->wwInterface->record_error("Problematic page title ‘"
        . htmlspecialchars($pdpage) . "’.");
      wwProfileOut( __METHOD__ );
      return false;
    }
    if ($title->getNamespace() != NS_PROJECTDESCRIPTION)
    { global $wgUser;
      $wwContext->wwInterface->record_error("Did not delete project "
        . htmlspecialchars($project->project_name)
        . " because its project description is in a strange place, "
        . $wgUser->getSkin()->link($title) . ".");
      wwProfileOut( __METHOD__ );
      return false;
    }
    $article = new Article($title, 0);
    # Better double-check that it hasn't been deleted yet!
    $dbw = wfGetDB( DB_MASTER );
    $conds = $title->pageCond();
    $latest = $dbw->selectField( 'page', 'page_latest', $conds, __METHOD__ );
    if ( $latest === false )
    { $wwContext->wwInterface->record_error("Cannot delete page "
        .htmlspecialchars($pagename) . " because it has been deleted.");
      wwProfileOut( __METHOD__ );
      return false;
    }
    $comment = "Project Description deleted by WorkingWiki";
    $article->doDelete( $comment, false );
    $this->clear_from_cache($pdpage);
    wwProfileOut( __METHOD__ );
    return true;
  }

  # there are certain times when we would ordinarily sync source files to
  # ProjectEngine, but don't - such as when we come to Special:GetProjectFile
  # during a preview, because it doesn't have access to the source files
  # on the page being previewed.
  public function ok_to_sync_source_files()
  { if ( !wwRunHooks( 'WW-OKToSyncSourceFiles', array() ) )
      return false;
    return true;
  }

  # generally when we have ProjectEngine make something we also ask it
  # to check any files that we are archiving.  But there are exceptions, 
  # for instance when we've just gotten files for archiving and we're
  # saving a wiki page with the new content, and during previewing.
  public function ok_to_archive_files(&$request)
  { if ($this->archiving_in_progress)
      return false;
    if (!wwRunHooks('WW-OKToArchiveFiles',array(&$request)))
      return false;
    return true;
  }

  # data to send to ProjectEngine: for each file that we are keeping 
  # archived, get its sha1 hash and put into the array
  #  filename => hash.
  # This allows PE to send back only the file contents that have changed.
  public function archived_file_hashes($project)
  { //return array( 'second-source-file' => '' );
    wwProfileIn( __METHOD__ );
    global $wwContext;
    $sfc = array();
    foreach ($project->project_files as $pfe)
      if (isset($pfe['archived']))
      { if ( isset($this->to_archive[$project->project_name()]) and
            $this->to_archive[$project->project_name()][$pfe['filename']])
        { $sfc[$pfe['filename']] = sha1_file(
            $this->to_archive[$project->project_name()][$pfe['filename']]);
          #wwLog("hash of " .
          #  $this->to_archive[$project->project_name()][$pfe['filename']]
          #  . " is " . $sfc[$pfe['filename']] . "\n");
        }
        else
        { #wwLog($pfe['filename']." not found in to_archive\n");
          $location = null;
          foreach ($pfe['archived'] as $location=>$t)
            break;
          $afc = $this->find_file_content(
            $pfe['filename'], $project, $location, false);
          if (isset($afc['text']))
            $sfc[$pfe['filename']] = sha1($afc['text']);
          else if ($afc['type'] == 'file')
          { $title = Title::newFromText($afc['page']);
            $storedfile = wfLocalFile($title);
            if ( ! $storedfile->exists() )
            { $wwContext->wwInterface->debug_message("Archived image '"
                . htmlspecialchars($pfe['filename']) . "' not found.");
              $sfc[$pfe['filename']] = '';
            }
            else
            { if (method_exists('File', 'getSha1'))
                $sfc[$pfe['filename']] = wfBaseConvert(
                  $storedfile->getSha1(), 36, 16, 40 );
              else
                $sfc[$pfe['filename']] = wfBaseConvert(
                  File::sha1Base36($storedfile->getPath()), 36, 16, 40 );
            }
          }
          else if ($afc['type'] == 'not found')
          { $wwContext->wwInterface->debug_message("Archived file '"
              . htmlspecialchars($pfe['filename']) . "' not found"
              . " on page " . htmlspecialchars($location)
              . " ... " . htmlspecialchars(serialize($pfe)) . ".");
            $sfc[$pfe['filename']] = '';
          }
          else
            $wwContext->wwInterface->throw_error("Internal error: type '"
              . htmlspecialchars($sfc['type']) . "' not known.");
        }
      }
    wwProfileOut( __METHOD__ );
    return $sfc;
  }

  # have things been removed from the pages? do we need to update the
  # project description?  Source files get checked when we try to sync
  # them, so here we check the 'appears' on the project-file entries.
  # Not 'archived', because if an archived project file is not there we
  # just put it back onto the page.
  public function check_all_project_files($project)
  { wwProfileIn( __METHOD__ );
    $modified = false;
    foreach ($project->project_files as &$pf)
      if (isset($pf['appears']))
        foreach ($pf['appears'] as $page=>$t)
        { $pgfs = $this->find_project_files_on_page($page);
          //$wwContext->wwInterface->debug_message("on page $page: "
          //  . print_r($pgfs,true) );
          $pflist =& $pgfs[$project->project_name()];
          if (is_array($pflist))
            foreach ($pflist as &$pfentry)
              if ($pfentry['attributes']['filename'] == $pf['filename'])
                break 2; # go on to next $page
          $pflist =& $pgfs[''];
          if (is_array($pflist))
            foreach ($pflist as &$pfentry)
              if ($pfentry['attributes']['filename'] == $pf['filename']
                  and $this->find_project_given_page($page) === $project)
                break 2; # go on to next $page
          //$wwContext->wwInterface->debug_message("Project file ‘{$pf['filename']}’"
          //  . " not found on page ‘{$page}’" );
          unset($pf['appears'][$page]);
          $modified = true;
        }
    if ($modified)
    { //$wwContext->wwInterface->debug_message( "Updated project description" );
      $this->save_project_description($project);
    }
    wwProfileOut( __METHOD__ );
  }

  # where to shove the project files temporarily before archiving them
  public function sequester_directory()
  { if ( ! $this->seq_dir )
    { global $wwTempDirectory;
      $this->seq_dir = tempnam( $wwTempDirectory, 'WW_Sequester_' );
      unlink($this->seq_dir);
    }
    return $this->seq_dir;
  }

  public function do_archiving_in_uri($uri)
  { return ($uri !== 'pe-session-dir'); }
  
  # collect the contents of all the archived project files from the
  # working directory, before unlocking.  We'll write them to the wiki
  # after the directory is unlocked.
  # returns true if there are any to archive.
  # FIXME needs to be redone
  public function sequester_archived_project_files($apf)
  { global $wwContext, $wwPEFilesAreAccessible;
    wwProfileIn( __METHOD__ );
    if (!wwRunHooks('WW-SequesterArchivedProjectFiles', array($apf, &$ret)))
    { wwProfileOut( __METHOD__ );
      return $ret;
    }
    # painfully, when we do the archive operation by updating and saving
    # a wiki page, it causes the page to be re-parsed then and there, which
    # causes project make operations, which can trigger us to do another
    # round of archive operations, which... needless to say, we have to not
    # do that.
    if ($this->archiving_in_progress)
    { #wwLog("sequester_archived_project_files is exiting because of recursion\n");
      wwProfileOut( __METHOD__ );
      return false;
    }
    #wwLog("Sequester archived files\n");
    #wwLog("uri_lookup : " . serialize(ProjectDescription::$uri_lookup). "\n");
    #wwLog("apf : " . serialize($apf) . "\n");
    foreach ($apf as $proj_uri=>$files)
      if ($this->do_archiving_in_uri($proj_uri))
      { $projname = ProjectDescription::$uri_lookup[$proj_uri]->project_name();
        foreach ($files as $filename=>$entry)
          if ($entry !== null)
          { if (!isset($this->to_archive[$projname]))
              $this->to_archive[$projname] = array();
            if ($entry[0] == 'p')
            { #wwLog("Sequester $filename, project $projname: {$entry[1]}\n");
              $this->to_archive[$projname][$filename] = $entry[1];
            }
            else
            { #wwLog("Sequester $filename, project $projname: "
              #    . serialize($entry) . "\n");
              if (!isset($pdir))
              { $pdir = self::sequester_directory()."/$projname";
                if (!is_dir($pdir) and !mkdir($pdir, 0700, true))
                  $wwContext->wwInterface->record_error("Couldn't create directory "
                    . $pdir . " for archived files");
              }
              $filepath = $pdir.'/'.$filename;
              if (($write_file = fopen($filepath,"w")) === false)
              { $wwContext->wwInterface->throw_error(
                  "Couldn't open " . htmlspecialchars("$filepath")
                  . " for writing.");
              }
              if (fwrite($write_file,$entry[1]) === false)
              { $wwContext->wwInterface->throw_error("Couldn't write to "
                  . htmlspecialchars("$filepath") . ".");
              }
              if (fclose($write_file) === false)
              { $wwContext->wwInterface->throw_error( "Couldn't close "
                  . htmlspecialchars("$filepath") . " after writing.");
              }
              $this->to_archive[$projname][$filename] = $filepath;
            }
          }
          else
            unset($this->to_archive[$projname][$filename]);
      }
    wwProfileOut( __METHOD__ );
  }

  # write contents of all archived project files from the working directory
  # to the wiki pages, as needed.  We've recorded their locations in
  # $to_archive.
  public function update_archived_project_files()
  { global $wwContext;
    //wwLog("update_archived_project_files.\n" );
    wwProfileIn( __METHOD__ );
    if ($this->archiving_in_progress)
    { //wwLog("(skipping recursive update_archived_project_files)\n");
      wwProfileOut( __METHOD__ );
      return;
    }
    if ( wwfReadOnly() ) {
	    // wwLog( "(skipping update_archived_project_files because not allowed to edit the wiki)" );
	    wwProfileOut( __METHOD__ );
	    return;
    }
    $this->archiving_in_progress = true;
    foreach ($this->to_archive as $projname=>$listing)
    { try
      { $project = $this->find_project_by_name($projname);
        if ($project != null)
          foreach ($listing as $filename=>$filepath)
          { $pf = $project->project_files[$filename];
            if (!is_array($pf['archived']))
              $wwContext->wwInterface->throw_error("File ". htmlspecialchars($filename)
                . ", project " . htmlspecialchars($project->project_name()) 
                . " is not actually archived anywhere!");
            foreach ($pf['archived'] as $page=>$t)
            { $title = Title::newFromText($page);
              if ( $title->getNamespace() == NS_MEDIA )
                $title = Title::makeTitle( NS_IMAGE, $title->getDBkey() );
              if ( $title->getNamespace() == NS_IMAGE ) 
              { $image = wfLocalFile( $title );
                //wwLog("$projname update archived file '"
                //  . $pf['filename'] . "' to page '". $title->getPrefixedDBKey() 
                //  . "' (from $filepath)\n");
                $comment = "WorkingWiki updated archived project file";
                $pagetext = "Archived project file '{$pf['filename']}' from project "
                  . $project->project_name();
                $status = $image->upload( $filepath, $comment, $pagetext );
                if (WikiError::isError($status))
                  $wwContext->wwInterface->record_error( "Error updating archived "
		  . "project file ‘" . htmlspecialchars($pf['filename'])
		  . "’ to page ‘"
		    . htmlspecialchars($title->getPrefixedDbKey()) . "’: " 
                    . htmlspecialchars($status->getMessage()) );
                else if ( ! $status->isGood() )
                  $wwContext->wwInterface->record_error( "Problem updating archived "
                    . "project file ‘" . htmlspecialchars($pf['filename'])
		  . "’ to page ‘"
		    . htmlspecialchars($title->getPrefixedBbKey()) . "’: " 
                    . htmlspecialchars($status->getWikiText()) );
              }
              else
              { $wdcontent = file_get_contents($filepath);
                //wwLog("calling insert_file_element_in_page() to insert "
                //  . $pf['filename']. " in $page: ". strlen($wdcontent)."\n");
                $this->insert_file_element_in_page(
                  /*src*/false, /*archived*/true, $pf['filename'], 
                  $project->project_name(), $wdcontent, 
                  Title::newFromText($page) );
              }
            }
          }
      } catch (WWException $ex) {}
    }
    wwfRecursiveUnlink($this->sequester_directory(), true);
    $this->to_archive = array();
    $this->archiving_in_progress = false;
    wwProfileOut( __METHOD__ );
  }

  # list revision numbers where a project's structure changes
  # (not where its files' contents change).
  public function project_revisions( $project, $fetch_from )
  { $projectname = $project->project_name();
    //wwLog("project_revisions( $projectname, $fetch_from )\n");
    $revids = array();
    $last_rev_before = -1;
    # get the revision history of the project description
    if ($project->project_description_page)
    { $pdtitle = Title::newFromText($project->project_description_page);
      $db = wfGetDB( DB_SLAVE );
      #$db->debug(true);
      # select all the revisions including the text of the page.
      $fields = array_merge(Revision::selectFields(), Revision::selectTextFields(),
        array( 'page_namespace', 'page_title' ) );
      //wwLog( "get revs of {$project->project_description_page}\n" );
      $res = $db->select( 
        array('page','revision','text'),
        $fields,
        array( 'page_namespace' => $pdtitle->getNamespace(),
               'page_title' => $pdtitle->getDBKey(),
               'page_id=rev_page',
               'old_id=rev_text_id' ),
        __FUNCTION__,
        array() );
      $revs = $db->resultObject($res);
      $pagetimes = array();
      $rows = array();
      while ( ($row = $revs->next()) !== false )
        try
        { $rev = new Revision($row);
          $revid = $rev->getId();
          #$pagetext = $rev->getText();
          #$pdxml = $this->find_project_description_in_page_text(
          #  $project->project_name(),$pagetext);
          #$projects[$revid]
          #  = ProjectDescription::newFromXML( $pdxml, 
          #      $pdtitle->getPrefixedText(), 
          #      "$projectname rev $revid", $revid );
          if ($fetch_from <= $revid)
            $revids[] = $revid;
          else
            $last_rev_before = max($last_rev_before,$revid);
        } catch (WWException $ex) {}
      //wwLog( "project description changes at revs: " 
      //  . implode(' ', $revids) . "\n" );
    }
    $pages_to_match = array();
    if ($last_rev_before == -1 and isset($pdtitle) and
        $pdtitle->getNamespace() == NS_PROJECTDESCRIPTION)
    { # look for any pages before that time that might be source of an
      # implicit project description
      //wwLog("look for earlier revs of main page\n");
      $fields = array_merge(Revision::selectFields(), 
        Revision::selectTextFields(), 
        array( 'page_namespace', 'page_title', 'page_latest' ) );
      $conds = array();
      if (count($revids) > 0)
        $conds[] = 'rev_id < ' . min($revids);
      $pages_to_match[NS_MAIN] = array($projectname => 1);
      if (substr($projectname,-1) == '/')
        $pages_to_match[NS_MAIN][substr($projectname,0,-1)] = 1;
      $conds[] = self::makeWhereFrom2d($pages_to_match,
        'page_namespace', 'page_title', $db ); 
      $conds[] = 'page_id=rev_page';
      $conds[] = 'old_id=rev_text_id';
      $pagesres = $db->select( 
        array('page','revision','text'),
        $fields,
        $conds,
        __FUNCTION__,
        array() );
      $pagerevs = $db->resultObject($pagesres);
      $rows = array();
      while ( ($row = $pagerevs->next()) !== false )
        try
        { $rev = new Revision($row); 
          $revid = $rev->getId();
          $pagename = $rev->getTitle()->getPrefixedText();
          $pagetext = $rev->getText();
          $pdxml = $this->create_implicit_project_description(
            $projectname, $pagename, $pagetext);
          $p = ProjectDescription::newFromXML($pdxml, $pagename, 
            "$projectname rev $revid", $revid);
          if (count($p->project_files))
          { if ($fetch_from <= $revid)
              $revids[] = $revid;
            else
              $last_rev_before = max($last_rev_before,$revid);
          }
        } catch (WWException $ex) {}
      //wwLog("including implicit description, project changes at revs: " 
      //  . implode(' ',$revids)."\n");
    }
    #if ($last_rev_before >= 0)
    #  $revids[] = $last_rev_before;
    sort($revids, SORT_NUMERIC);
    return $revids;
  }

  # list all changes in source files within a given range of revisions, 
  # in which range we assume the project description doesn't change.
  # return a list of tuples (page, rev.id.)
  # this is only for WorkingWikiProjectDescriptions, not external projects.
  # doesn't work yet - it outputs revisions in the wrong order because it
  # does text pages before image pages, or v. versa.
  # also, it's not going to work right with automakefiles without some help.
  public function file_revisions_in_project( 
    $projectname, $fetch_from, $fetch_to=null )
  { $tuples = array();
    if ($fetch_from > 1)
    { # if we're not starting from the beginning, we need to find changes
      # in the very first revision, which means we have to compare it to
      # what went before
      $project = $this->find_project_by_name($projectname, true, 
        $fetch_from - 1);
      $lastsf = $project->all_source_file_contents(null, $fetch_from - 1);
    }
    else
      $lastsf = array();
    $project = $this->find_project_by_name($projectname, true, $fetch_from);
    $pages_seen = $image_pages = array();
    try
    { #if (!$first_time and $fetch_from < $revid)
      #{ $revids = array($lastrevid);
        # make list of all pages where source files might be found
      $db = wfGetDB( DB_SLAVE );
      #$db->debug(true);
      $revids = array($fetch_from);
      $pages = array();
      foreach ($project->project_files as $pf)
      { if (isset($pf['source']) and !isset($pf['automatic']))
        { if ( isset($pf['page']) and $pf['page'] != '' )
            $pages[$pf['page']] = true;
          else 
          { $locations = $project->default_locations_for_file($pf['filename']);
            foreach ($locations as $l)
              $lastpages[$l] = true;
          }
        }
        else if (isset($pf['archived']))
          foreach ($pf['archived'] as $pg=>$t)
          { $pages[$pg] = true; # just the first one'll do
            break;
          }
      }
      if ($project->project_description_page != '')
        $pages[$project->project_description_page] = true;
      $project->assemble_transitive_dependencies();
      foreach ($project->depends_on_transitively as $uri=>$depinfo)
        if ($depinfo['project']->project_description_page != '')
          $pages[$depinfo['project']->project_description_page] = true;
      $pages_to_match_in_range = array();
      foreach ($pages as $page=>$t)
      { $title = Title::newFromText($page);
        $ns = $title->getNamespace();
        $dbkey = str_replace(' ','_',$title->getDBKey());
        if (!array_key_exists($ns,$pages_to_match_in_range))
          $pages_to_match_in_range[$ns] = array();
        $pages_to_match_in_range[$ns][$dbkey] = 1;
        #else
        #  $image_pages[$dbkey] = 1;
      }
      # in case of the most recent project description, no upper bound
      # on revisions.
      #wwLog("try query for rev $fetch_from..$fetch_to\n");
      #wwLog("pages is [". implode(', ', array_keys($pages)). "]\n");
      #wwLog("pages_to_match_in_range is " . serialize($pages_to_match_in_range) . "\n");
      $fields = array_merge(Revision::selectFields(), 
        Revision::selectTextFields(), 
        array( 'page_namespace', 'page_title', 'page_latest' ) );
      $match_conds = $conds = array();
      if (count($pages_to_match_in_range))
      { $range_conds = array(
          self::makeWhereFrom2d( $pages_to_match_in_range,
              'page_namespace', 'page_title', $db ),
          'rev_id > '.$fetch_from );
        if ($fetch_to !== null)
          $range_conds[] = 'rev_id < '.$fetch_to;
        $match_conds[] = $db->makeList( $range_conds, LIST_AND );
      }
      if (count($match_conds))
      { $conds[] = $db->makeList($match_conds, LIST_OR);
        #else
        #  $conds[] = '1=0'; # !!
        $conds[] = 'page_id=rev_page';
        $conds[] = 'old_id=rev_text_id';
        #wwLog("select condition is: ". implode(' and ', $conds). "\n");
        $pagesres = $db->select( 
          array('page','revision','text'),
          $fields,
          $conds,
          __FUNCTION__,
          array() );
        $pagerevs = $db->resultObject($pagesres);
        $rows = array();
        while ( ($row = $pagerevs->next()) !== false )
          $rows[] = $row;
        foreach ($rows as $row)
        { $pagerev = new Revision($row);
          $revids[] = $pagerev->getId();
        }
      }
      foreach ($revids as $ri)
      { $nextsf = $project->all_source_file_contents(null,$ri);
        #if (count($lastsf) > 0)
        { $new_tuples = self::getAllChangesInSourceFiles(
              $lastsf, $nextsf, $project, $ri);
          #$tup_texts = 
          #  array_map(create_function('$tuple',
          #    'return "(".implode(", ",$tuple).")";'), $new_tuples);
          #$tup_text = serialize($new_tuples); #implode(', ', $tup_texts);
          #wwLog("changes in rev $ri: ". $tup_text . "\n");
          $tuples = array_merge($tuples, $new_tuples);
        }
        $lastsf = $nextsf;
      }
      #}
      #$lastrevid = $revid;
      #$lastproject = $p;
      #$first_time = false;
    } catch (WWException $ex) {}
    # I think this foreach is not currently getting used because I just
    # do image pages like regular pages
    foreach ($image_pages as $pagename=>$t)
    { $title = Title::makeTitle(NS_IMAGE, $pagename);
      $filenames = array();
      foreach ($project->project_files as $pf)
      { list($hit,$src,$auto) =
          self::fileMightBeOnPage($project,$pf['filename'],$pagename);
        if ($hit)
          $filenames[] = $pf['filename'];
      }
      $file = wfFindFile($title);
      if (!$file)
        $file = wfLocalFile($title);
      if ($file)
      { $hist = array_reverse($file->getHistory());
        $hist = array_merge($hist,array($file));
        //wwLog( "In git_export(): for $pagename, \$hist = "
          //. print_r($hist,true) . "\n" );
        foreach ($hist as $f)
          foreach ($filenames as $filename)
          { $output .= "commit refs/heads/master\n";
            $author = $f->getUser();
            $output .= "committer $author <$author> "
              . wfTimestamp( TS_UNIX, $f->getTimestamp() ) . " +0000\n";
            $comment = $file->getDescription()
              . "\n\npage $pagename timestamp ".$f->getTimestamp()."\n";
            $output .= "data " . strlen($comment) . "\n";
            $output .= "$comment\n";
            $output .= "M 644 inline {$filename}\n";
            $filetext = file_get_contents($f->getPath());
            $output .= "data " . strlen($filetext) . "\n";
            $output .= "$filetext\n";
          }
      }
    }
    return $tuples;
    return $output;
    return '';
  }

  # in MW 1.18+ this is in the Database class, but we need it in older versions
  public function makeWhereFrom2d( $data, $baseKey, $subKey, $db ) {
    $conds = array();

    foreach ( $data as $base => $sub ) {
      if ( count( $sub ) ) {
        $conds[] = $db->makeList(
          array( $baseKey => $base, $subKey => array_keys( $sub ) ),
          LIST_AND );
      }
    }

    if ( $conds ) {
      return $db->makeList( $conds, LIST_OR );
    } else {
      // Nothing to search for...
      return false;
    }
  }

  public function getAllChangesInSourceFiles($lastsf, $nextsf, $project, $rev)
  { $tuples = array();
    #wwLog("getAllChangesInSourceFiles:\nlastsf: " .serialize($lastsf)
    #  ."\nnextsf: " .serialize($nextsf) ."\n");
    foreach (array_keys(array_merge($lastsf, $nextsf)) as $key)
    { $lastsf_exists = (isset($lastsf[$key]) and $lastsf[$key][1] !== null);
      $nextsf_exists = (isset($nextsf[$key]) and $nextsf[$key][1] !== null);
      #wwLog( "$key in $rev: "
	#      . ($lastsf_exists ? "\nbefore " . json_encode($lastsf[$key]). ', ' : '')
	#      . ($nextsf_exists ? "\nafter " . json_encode($nextsf[$key]) : '') . "\n" );

      if ($lastsf_exists and !$nextsf_exists)
        $tuples[] = array('remove source file', $key, $rev);
      else if (!$lastsf_exists and $nextsf_exists)
        $tuples[] = array('add source file', $key, $rev);
      else if ($lastsf_exists and $nextsf_exists and
               $lastsf[$key][0] != $nextsf[$key][0] or
               $lastsf[$key][1] != $nextsf[$key][1])
        $tuples[] = array('change source file', $key, $rev);
    }
    #wwLog( "getAllChangesInSourceFiles($rev): " . json_encode($tuples). "\n" );
    return $tuples;
  }

  /*
  static $wrote_current_commit;
  static function getAllChangesInPageRev($pagetext,$pagename,$project,$pagerev)
  { $this->wrote_current_commit = false;
    //wwLog("getAllChangesInPageRev $pagename, {$pagerev->getId()}\n");
    # clear out cache of retrieved pages
    $this->clear_from_cache($pagename);
    $this->cache_text_directly( 
      $wwContext->wwInterface->replace_inlines($pagetext), $pagename, $pagerev->getTimestamp() );
    #wwLog("put page $pagename text into cache:\n[[$pagetext]]\n");
    $tuples = array();
    foreach ($project->project_files as $pf)
    { list($hit,$src,$auto) = self::fileMightBeOnPage(
        $project,$pf['filename'],$pagename);
      #wwLog("fileMightBeOnPage {$pf['filename']}, $pagename : $hit\n");
      if ($hit)
      { $new_tuple = self::findAndOutputFile($pf['filename'], $pagename,$src,
          $auto,$project,$pagerev);
        #wwLog("findAndOutputFile {$pf['filename']}, $pagename: "
        #    . serialize($new_tuple) . "\n");
        if ($new_tuple !== null)
          $tuples[] = $new_tuple;
      }
    }
    return $tuples;
  }
   */

  public function fileMightBeOnPage($project,$filename, $pagename)
  { $hit = $src = $auto = false;
    $pf = $project->project_files[$filename];
    if (isset($pf['automatic']))
    { $title = Title::newFromText($pagename);
      if ($title->getNamespace() == NS_PROJECTDESCRIPTION)
        $hit = $src = $auto = true;
    }
    else if ($pf['source'])
    { $src = true;
      if ( $pf['page'] == $pagename )
        $hit = true;
      else 
      { $locations = $project->default_locations_for_file($pf['filename']);
        foreach ($locations as $l)
          if ($l == $pagename)
            $hit = true;
      }
    }
    else if ($pf['archived'])
      foreach ($pf['archived'] as $l)
        if ($l == $pagename)
        { $hit = true;
        }
    return array($hit,$src,$auto);
  }

  public function findAndOutputFile($filename,$pagename,$src,$auto,$project,$pagerev)
  { $ns = $pagerev->getTitle()->getNamespace();
    if ($ns == NS_IMAGE or $ns == NS_MEDIA)
    { $file = wfFindFile( $pagerev->getTitle(),
        array( 'time'=>$pagerev->getTimestamp() ) );
      if (!$file and $pagerev->isCurrent())
        $file = wfLocalFile( $pagerev->getTitle() );
      if ($file !== false)
        $path = $file->getFullPath();
      if (!$path)
      { //wwLog("Can't locate file for $pagename!\n");
        $page = null;
      }
      else
      { $filetext = file_get_contents($path);
        $page = $pagename;
      }
    }
    else
    { if ($auto)
        $fc = $project->generate_source_file_content($filename);
      else if ($src)
        $fc = $project->find_source_file_content($filename, $pagename);
      else
        $fc = $this->find_file_content($filename, $project, $pagename, $src);
      list($page,$filetext,$mtime) =
        array($fc['page'], $fc['text'], $fc['touched']);
    }
    //wwLog("findAndOutputFile( $filename, $pagename, $src, {$project->project_name()}, {$pagerev->getId()}): ($page, "
      //. (strlen($filetext) > 10 ? substr($filetext,0,10).'...' : $filetext )
      //. ", $mtime)\n");
    if ($page !== null)
    { #if (!$this->wrote_current_commit)
      return array("change source file", $filename, $pagerev->getId());

#   { $output .= "commit refs/heads/master\n";
#    $author = $pagerev->getUserText();
#    $output .= "committer $author <$author> "
#     . wfTimestamp( TS_UNIX, $pagerev->getTimestamp() ) . " +0000\n";
#    $comment = $pagerev->getComment()
#     . "\n\npage $pagename rev {$pagerev->getId()}";
#    $output .= "data " . strlen($comment) . "\n";
#    $output .= "$comment\n";
#     $this->wrote_current_commit = true;
#   }
#   $output .= "M 644 inline {$filename}\n";
#   $output .= "data " . strlen($filetext) . "\n";
#   $output .= "$filetext\n";
    }
    return null;
  }

#if (0){
  function wwfDumpProjectToGit_pd( $project )
  { $output = '';
    # get the revision history of the project description
    if (!$project->project_description_page)
      return '';
    $pdtitle = Title::newFromText($project->project_description_page);
    # Q: does this select the text along with the rev info?
    #$revs = Revision::fetchRevision($pdtitle);
    $db = wfGetDB( DB_SLAVE );
    $fields = array_merge(Revision::selectFields(), Revision::selectTextFields(),
      array( 'page_namespace', 'page_title' ) );
    $res = $db->select( 
      array('page','revision','text'),
      $fields,
      array( 'page_namespace' => $pdtitle->getNamespace(),
             'page_title' => $pdtitle->getDBKey(),
             'page_id=rev_page',
             'old_id=rev_text_id' ),
      __FUNCTION__,
      array() );
    $revs = $db->resultObject($res);
    # for each interval between revisions, track the history of the 
    # pages where our files are.
    # we create an array
    # $times[<page name>] = array( array( <from>, <to> ), ... )
    while ( ($row = $revs->next()) !== false )
    { $rev = new Revision($row);
      $output .= "commit refs/heads/master\n";
      $pdxml = $rev->getText();
      $author = $rev->getUserText();
      $output .= "committer $author <$author> "
        . wfTimestamp( TS_UNIX, $rev->getTimestamp() ) . " +0000\n";
      $comment = $rev->getComment();
      $output .= "data " . strlen($comment) . "\n";
      $output .= "$comment\n";
      $output .= "M 644 inline project-description.xml\n";
      $output .= "data " . strlen($pdxml) . "\n";
      $output .= "$pdxml\n";
      echo $output;
      $output = '';
    }
    return ''; #return $output;
  }

  function wwfDumpProjectToGit_now( $project )
  { $output = '';
    $output .= "option date-format now\n";
    $output .= "commit refs/heads/master\n";
    $output .= "committer workingwiki <workingwiki> now\n";
    $output .= "data <<EOF\n";
    $output .= "Git data generated by WorkingWiki\n";
    $output .= "EOF\n";
    foreach ( $project->project_files as $pf )
    { if ( $pf['source'] )
      { $output .= "M 644 inline {$pf['filename']}\n";
        list($page,$text,$mtime) 
          = $project->find_source_file_content($pf['filename'],null);
        $output .= "data ".strlen($text)."\n";
        $output .= $text."\n";
      }
      else if ( isset($pf['archived']) )
      { $output .= "M 644 inline {$pf['filename']}\n";
        list($text,$touched)
          = $this->find_file_content_on_page( $project,
              $pf['filename'],$pf['archived'][0],false );
        $output .= "data ".strlen($text)."\n";
        $output .= "$text\n";
      }
    }
    return $output;
  }
#}

}
