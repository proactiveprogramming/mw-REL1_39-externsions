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

class ProjectDescription
{ // Using a ProjectEngine project in WorkingWiki requires an
  // XML "project-description" element stored in the wiki.
  // This class represents the contents of that element.
  // Subclasses specialize the concept to local and foreign projects.

  // ProjectDescription objects are indexed by name in this array
  public static $project_cache;

  // projectname records the name of the project, as seen in 
  //  <source-file project="">
  //  <project-file project="">
  //
  // A legal project name is generally whatever is a legal page title,
  // not including namespace.  In fact, we use MediaWiki's Title
  // objects to sanity check the names.  This is partly laziness, and
  // partly that project names ARE in fact page titles in the
  // ProjectDescription: namespace, so they'd better be legal and not
  // conflict with each other.  A project name is allowed to contain
  // '.', '/', and ' ', but not '..', and is not allowed to end with
  // '/', to prevent confusion.
  //
  // ProjectDescription objects are cached by name.  To make the name
  // reliable as a cache key, it is normalized first by making it into a 
  // Title object with Title::newFromText(), then calling that object's
  // getPrefixedDBKey() for the normalized name.  This name contains
  // underscores in place of spaces, and contains slashes if they are
  // present.  However, slashes are escaped when assigning working
  // directories, so a project name with a slash in it does not
  // correspond to a working directory deeper in the directory tree.
  // '&', '<', '>', '\'' and '"' are escaped, for use in XML elements.
  public $projectname;

  // ProjectEngine identifies each project by a unique Uniform Resource
  // Identifier (URI), specifying what kind of repository the project
  // comes from, where the repository is, and how to find the project in
  // the repository.  So all our projects have to be able to provide one.
  public $uri;

  // then the reply from PE sometimes refers to projects by URI, so we need
  // to associate those back to the project objects.
  public static $uri_lookup;

  // Remember which wiki page the project-description element is
  // on.  If it isn't anywhere, it's implicitly taken to be empty,
  // and this variable is null.  This is also null in the 'standalone'
  // case.
  public $project_description_page;

  // array recording info about all project files
  // known to the project object.
  // $project_files['filename'] is an array with some of the 
  // following keys:
  //   'filename' => the same filename as above.  Always present.
  //   'display'  => how to display it.  Overridden by 'display' attribute
  //                 in a wikitext tag.
  //   'appears'  => array whose keys are names of pages where the file 
  //                 is displayed (not including pages given by a 'page'
  //                 or 'archived' attribute).
  //   (and any other attributes set in the project-description.)
  // WorkingWikiProjectDescription stores additional things here, like
  // source files' locations.
  public $project_files;

  // array recording all inter-project dependencies of this project,
  // that is, any project named in a 'depends-on' element in this project's
  // XML description.  if P1 is such a project, then $this->depends_on['P1']
  // exists.  It should be an array containing keys:
  //  'varname'  : If the value of 'varname' is XXX, the project's working
  //               directory will be referred to as $(XXX)
  //               in the make job's environment variables.
  //  'readonly' : If true, the project's working files don't need to be
  //               copied when creating a preview or background job, because
  //               they aren't modified by make rules in the primary project.
  public $depends_on;
  // corresponding array recording which projects depend on this one,
  // recorded by 'depended-on-by' elements in the project description.
  public $depended_on_by;

  // all projects this depends on, and the projects they depend on, etc.
  // keys in this array are project URIs, and each points to an array with keys
  // 'varname'
  // 'readonly'
  // 'short-dir' : directory name for this project when exporting
  public $depends_on_transitively;

  // various settings applying to the project.
  public $options;

  // is usually null, but is a MW revision number if we're representing
  // the past state of a project as of a certain revision of the wiki.
  // if so, we don't store the ProjectDescription object in the cache.
  public $as_of_revision;

  # ===== class functions =====

  // All ProjectDescription objects including subclasses have to be
  // created by this function.
  public static function newFromXML(
      $xml, $page=null, $cache_key=null, $as_of_revision=null)
  { global $wwContext;
    if (!$xml)
    { PEMessage::throw_error(
        "Can't create a project description without an XML description");
    }

    // Let Title: class validate the name given for dicy characters.
    // Test this to make sure it works as desired.

    // parse the xml description.
    if (substr($xml,0,2) != "<?")
      $xml = "<?xml version='1.0' standalone='yes'?".">\n".$xml;
    try
    { #wwLog("newFromXML given: $xml\n");
      $xmldata = new SimpleXMLElement($xml); 
      $projname = (string)$xmldata->project[0]['name'];
      # If the xml contains an 'external' element it's an external
      # project, or if it has an external project's name, else not.
      if ($xmldata->project[0]->external)
        $pd = new ExternalProjectDescription($xmldata, $page, $as_of_revision);
      else if ($wwContext->wwStorage->is_project_uri($projname))
      { $pd = new ExternalProjectDescription($xmldata, $page, $as_of_revision);
        $pd->location = $projname;
      }
      # if it has a 'standalone' element, a standalone project.
      else if ( $xmldata->project[0]->standalone )
      { $pd = new StandaloneProjectDescription($xmldata, '', $as_of_revision);
      }
      # otherwise regular WW project.
      else
        $pd = new WorkingWikiProjectDescription($xmldata, $page, $as_of_revision);

    }
    catch (Exception $ex)
    { // if SimpleXMLElement raises an exception, pass it upward as a
      // WWException so as to get error reporting in the browser.
      $wwContext->wwInterface->throw_error(
        "Attempting to parse project-description element: "
        . htmlspecialchars($ex->getMessage())
        . " (xml string: " . htmlspecialchars($xml) . ")");
    }

    if ($as_of_revision == null)
    { if (!is_array(ProjectDescription::$project_cache))
        ProjectDescription::$project_cache = array();
      if ($cache_key === null)
        $cache_key = $pd->project_name();
      ProjectDescription::$project_cache[$cache_key] = $pd;
      #wwLog("Cache newly created project '{$pd->project_name()}'"
      # ." as '{$cache_key}'");
    }

    return $pd;
  }

  # check a source- or project- filename for prohibited format
  public static function is_allowable_filename($filename)
  { #if (preg_match('/[^\w-+\.\/~? &:$]/',$filename))
    #  return false;
    if ($filename == '')
      return false;
    if (strpos($filename, '/../') !== false or 
        substr_compare($filename, '../', 0) == 0)
      return false;
    if ($filename{0} == '/')
      return false;
    return true;
  }

  # for algorithms for finding and inferring project descriptions, see
  # http://lalashan.mcmaster.ca/theobio/wiki/index.php/WorkingWiki/Defaults

  # the official decider about what's a correct project name.  If you're
  # going to use project names as cache keys for ProjectDescription objects,
  # or anything like that, call this first so you don't get two different
  # names for the same project.  Also, this filters out illegal project
  # names by returning null.
  public static function normalized_project_name($name, $complain=true)
  { global $wwContext;
    if ( class_exists( 'Title' ) and $name instanceOf Title )
    { $title = $name;
      $name = $title->getPrefixedDBKey();
    }
    else if ( ! is_scalar($name) )
    { if ($complain)
        $wwContext->wwInterface->throw_error( 
          "Bad argument ('" . htmlspecialchars(serialize($name)) 
           . "') to ProjectDescription::normalized_project_name()." );
      else
        return null;
    }
    else
      $name = strval($name);
    $pname = $name;
    $lb = $wwContext->wwStorage->local_uri_base();
    if (strncmp($pname, $lb, strlen($lb)) == 0)
    { #wwLog("$pname is actually local: ");
      $pname = substr($pname, strlen($lb));
      #wwLog("$pname");
    }
    if ($wwContext->wwStorage->is_project_uri($pname))
      return $pname;
    if ( class_exists( 'Title' ) ) {
	    if (!isset($title) or ($pname != $name))
	      $title = ProjectDescription::make_project_title(urldecode($pname), 
			  $complain);
	    if (!$title instanceOf Title)
	      $wwContext->wwInterface->throw_error("Couldn't make a Title from "
		. htmlspecialchars(urldecode($name)) );
	    $pname = $title->getPrefixedDBKey();
	    #wwLog("prefixedDBKey of $name is $pname.");
    }
    //$pname = htmlspecialchars($pname, ENT_QUOTES);
    //$pname = strtr($pname, array('&'=>'&amp;','<'=>'&lt;','>'=>'&gt;'));
    //while (substr($pname,-1) == '/')
    //  $pname = substr($pname,0,-1);
    return $pname;
  }

  public static function human_readable_project_name( $project, $complain=true )
  { $pname = ProjectDescription::normalized_project_name( $project, $complain );
    global $wwContext;
    if ( $wwContext->wwStorage->is_project_uri($pname) )
    { return $pname;
    }
    return str_replace( '_', ' ', $pname );
  }

  public static function make_project_title($name, $complain=true)
  { global $wwContext;
    $title = Title::newFromText($name);
    if (!is_object($title))
    { if ($complain)
        $wwContext->wwInterface->throw_error("Invalid project name '"
          . htmlspecialchars($name) . "'.");
      else
        return null;
    }
    return $title;
  }

  # variable names for make should only contain letters, numbers, and _
  public static function default_varname($projectname)
  { $vstr = '';
    global $wwURIVariableNameTransformations;
    if (is_array($wwURIVariableNameTransformations) and
        count($wwURIVariableNameTransformations) > 0)
    { $repl = preg_replace(array_keys($wwURIVariableNameTransformations),
        array_values($wwURIVariableNameTransformations), $projectname,
        -1, $nmatches);
      if ($nmatches > 0)
        $vstr = $repl;
    }
    if ($vstr == '')
      $vstr = 'PROJECT_DIR_' . $projectname;
    return ProjectDescription::normalize_varname($vstr);
  }

  public static function normalize_varname($vname)
  { return strtr( $vname,
        array( ';' => urlencode(';'),
               '|' => urlencode('|'),
               "'" => urlencode("'"),
               '"' => urlencode('"'),
               '>' => urlencode('>'),
               '<' => urlencode('<'),
               '&' => urlencode('&'),
               ' ' => '_',
               '-' => '_',
               '$' => '%24',
               '(' => '%28',
               ')' => '%29',
               '%' => urlencode('%') ) );
  }

  # ===== instance functions =====

  // constructor
  protected function __construct($xmldata, $page, $as_of_revision)
  { global $wwContext;
    $this->project_description_page = $page;
    $this->project_files = array();
    $this->projectname = (string)$xmldata->project[0]['name'];
    $this->uri = ''; // generate lazily
    $this->options['use-default-makefiles'] = true;
    $this->as_of_revision = $as_of_revision;
    foreach ( $xmldata->project[0]->children() as $element )
    { $this->read_xml_element($element);
    }
    $this->projectname
      = ProjectDescription::normalized_project_name($this->projectname);
    $this->depends_on_transitively = null;

    //$wwContext->wwInterface->record_error( "parsed project-description and got "
    //  . htmlspecialchars($this->project_description_text()) );
  }

  // called by __construct.  Subclasses specialize this.
  protected function read_xml_element($element)
  { global $wwContext;
    if (((string)$element->getName()) == 'project-file')
    { $filename = (string)$element['filename'];
      if (!ProjectDescription::is_allowable_filename($filename))
      { $wwContext->wwInterface->record_error( "Prohibited filename ‘"
          . htmlspecialchars($filename) . "’." );
        continue;
      }
      # appears, archived: add the value to the list.
      # all others: just overwrite with the value.
      foreach ( $element->attributes() as $key => $val )
        if ((string)$key == 'appears')
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
          $this->project_files[$filename][(string)$key] = (string)$val;
      return true;
    }
    else if (((string)$element->getName()) == 'depends-on')
    { $depname  = (string)$element['project'];
      $varname  = (string)$element['project-dir-var'];
      $old_varname  = (string)$element['varname'];
      if ($varname == '' and $old_varname != '')
        $varname = "PROJECT_DIR_$old_varname";
      $readonly = (string)$element['readonly'];
      $this->depends_on[$depname] 
        = array('varname'=>$varname, 'readonly'=>$readonly);
      return true;
    }
    else if (((string)$element->getName()) =='depended-on-by')
    { $depname = (string)$element['project'];
      $this->depended_on_by[$depname] = true;
      return true;
    }
    else if (((string)$element->getName()) =='option')
    { $this->options[(string)$element['name']] = (string)$element['value'];
    }
    return false;
  }

  # the name of the project, for reporting and for use in wiki page names
  public function project_name()
  { return $this->projectname;
  }

  # provide URI for the project
  public function project_uri()
  { if (!$this->uri)
    { $this->uri = $this->generate_uri();
      self::$uri_lookup[$this->uri] = $this;
    }
    return $this->uri;
  }

  # create URI for the project
  # this is the subclasses' responsibility
  protected function generate_uri()
  { return '';
  }

  public function is_local()
  { global $wwContext;
    // local projects get their project names shortened, so it's sufficient
    // to test whether it's in the long form
    return ($wwContext->wwStorage->is_project_uri($this->projectname));
  }

  # text to refer to the project in a Special page url
  public function project_url_attr()
  { return 'project=' . urlencode($this->project_name());
  }

  public function is_standalone()
  { return false;
  }

  # beware that this can return null
  public function project_page()
  { if (!is_null($this->project_description_page))
      return $this->project_description_page;
    else
      return $this->project_name();
  }

  # produce the XML project description to store on a wiki page
  public function project_description_text()
  { $xml = "<project-description>\n<project name=\""
      . htmlspecialchars($this->project_name(), ENT_QUOTES|ENT_XML1)
      . "\">\n";
    $this->add_to_project_description_text($xml);
    if (is_array($this->depends_on))
      foreach ($this->depends_on as $dep=>$info)
      { $xml .= "  <depends-on project=\"" . htmlspecialchars($dep, ENT_QUOTES|ENT_XML1) . "\"";
        if ($info['varname'] != '')
          $xml .= " project-dir-var=\"" . htmlspecialchars($info['varname'], ENT_COMPAT|ENT_QUOTES|ENT_XML1, 'UTF-8', false) . "\"";
        if ($info['readonly'] != '')
          $xml .= " readonly=\"" . ($info['readonly'] ? '1':'0') . "\"";
        $xml .= "/>\n";
      }
    if (is_array($this->depended_on_by))
      foreach ($this->depended_on_by as $dep=>$t)
        $xml .= "  <depended-on-by project=\"" . htmlspecialchars($dep, ENT_COMPAT|ENT_QUOTES|ENT_XML1, 'UTF-8', false) . "\"/>\n";
    if (is_array($this->options))
      foreach ($this->options as $name=>$value)
        $xml .= "  <option name=\"" . htmlspecialchars($name, ENT_COMPAT|ENT_QUOTES|ENT_XML1, 'UTF-8', false) 
          . "\" value=\"" . htmlspecialchars($value, ENT_COMPAT|ENT_QUOTES|ENT_XML1, 'UTF-8', false) . "\"/>\n";
    $xml .= "</project>\n</project-description>";
    return $xml;
  }

  // for subclasses to extend
  public function add_to_project_description_text(&$xml)
  { foreach ( $this->project_files as $pf )
    { if (!is_array($pf))
      { #wwLog("There's an odd entry in \$project_files...\n"
        #  .var_export($this->project_files,true));
      }
      else
      { if (isset($pf['appears']) and is_array($pf['appears']))
          foreach ($pf['appears'] as $pg=>$t)
            $xml .=
              "  <project-file filename=\"" . htmlspecialchars($pf['filename'], ENT_QUOTES|ENT_XML1)
              . "\" appears=\"" . htmlspecialchars($pg, ENT_QUOTES|ENT_XML1) . "\"/>\n";
        if (array_key_exists('archived',$pf) and is_array($pf['archived']))
          foreach ($pf['archived'] as $pg=>$t)
            $xml .=
              "  <project-file filename=\"" . htmlspecialchars($pf['filename'], ENT_QUOTES|ENT_XML1)
              . "\" archived=\"" . htmlspecialchars($pg, ENT_QUOTES|ENT_XML1) . "\"/>\n";
      }
    }
  }

  # some kinds of projects have "source files", the rest don't.
  public function is_file_source($filename)
  { return false;
  }

  public function has_source_files()
  { return false;
  }

  # WWPD and StandalonePD do something here
  public function proactively_sync_if_needed()
  { return true;
  }

  public function is_external()
  { return false;
  }

  # inform us that another project depends on us.
  # This leads to creation of a 'depended-on-by' element in the 
  # project description.  These elements aren't managed directly
  # by the user, they're created automatically as the mirror image
  # of each 'depends-on' element.
  public function add_dependent_project($depname)
  { global $wwContext;
    if (!$this->depended_on_by[$depname])
      $wwContext->wwInterface->project_is_modified($this->project_name());
    $this->depended_on_by[$depname] = true;
  }

  # what pages display a given file?
  # (not including where a source-file is defined).
  # returns an array of page names
  public function pages_involving( $filename )
  { $ar = @$this->project_files[$filename]['appears'];
    if (!is_array($ar))
      $ar = array();
    if (is_array($this->project_files[$filename]['archived']))
      $ar = array_merge($ar,$this->project_files[$filename]['archived']);
    return $ar;
  }

  # what pages display files from this project, for cache invalidation.
  public function pages_involving_project_files()
  { global $wwContext;
    $involves = array();
    foreach ($this->project_files as &$pf)
    { if (isset($pf['appears']) and is_array($pf['appears']))
        $involves = array_merge($involves,$pf['appears']);
      if (isset($pf['archived']) and is_array($pf['archived']))
        $involves = array_merge($involves,$pf['archived']);
    }
    return array_map( array( $wwContext->wwStorage, 'page_cache_key' ), array_keys($involves) );
  }

  # Work out the type of a given file.
  # This is just the file's extension in lowercase, except in the case of a
  # makefile.
  public static function type_of_file($filename)
  { //if (is_array($attrs) && array_key_exists('type',$attrs))
    //  return $attrs['type'];
    // that isn't sufficient, I need to also check in the project
    // description.
    if (preg_match('/(^|\/)(GNU)?[Mm]akefile$/',$filename)
        or preg_match('/(^|\/)[Mm]akefile-[^\.]*$/',$filename))
      return 'makefile';
    $ext = strrchr($filename,'.');
    if ($ext === false)
      return '';
    else
      return strtolower(substr($ext,1));
  }

  # what to send to ProjectEngine
  public function fill_pe_request( &$request, $focal, $sync_sf )
  { global $wwContext;
    $uri = $this->project_uri();
    if (!isset($request['projects'][$uri]))
      $request['projects'][$uri] = array();
    if (ProjectEngineConnection::archive_files_with_operation(
            $request['operation']['name']) and 
          $wwContext->wwStorage->ok_to_archive_files($request))
    { $afh = $wwContext->wwStorage->archived_file_hashes($this);
      if (is_array($afh) and count($afh) > 0)
        $request['projects'][$uri]['archived-file-hashes'] = $afh;
    }
    if ($focal)
    { if (ProjectEngineConnection::operation_includes_make(
            $request['operation']['name']))
      { foreach ( $this->env_for_make_jobs() as $k => $v )
          $request['operation']['env'][$k] = $v;
      }
      # note export uses this option as well as make
      $request['operation']['use-default-makefiles']
        = $this->options['use-default-makefiles'];
      $request['projects'][$uri]['short-dir'] = 
        $this->short_dir_for_uri($uri, null);
      if ( ProjectEngineConnection::should_add_associated_projects_to_request(
              $request['operation']['name']))
      { $this->assemble_transitive_dependencies();
        foreach ($this->depends_on_transitively as $duri => $depinfo)
        { $request['projects'][$duri] = array(
            'varname'  => $depinfo['varname'],
            'readonly' => $depinfo['readonly'],
	    'short-dir' => $depinfo['short-dir']
          );
          $depinfo['project']->fill_pe_request( $request, false, $sync_sf );
        }
      }
      # focal project can't be readonly, even if it is a readonly prerequisite
      # of itself
      if ( $focal and isset( $request['projects'][$uri] ) )
      { $request['projects'][$uri]['readonly'] = false;
      }
    }
  }

  # some (not all) things that go in the 'env' array for make jobs
  public function env_for_make_jobs()
  { global $wwContext, $wwOutputFormat, $wgScriptPath, $wgSitename;
    $publish_url =
      $wwContext->wwInterface->get_project_file_base_url($this,'',false,'raw');
    $pagename = $wwContext->wwInterface->page_being_parsed();
    return array (
      'WW_PUBLISH_URL' => $publish_url,
      'PUBLISH_URL' => $publish_url, # backward compatibility
      'WW_GPF_BASE_URL' => $wwContext->wwInterface->get_project_file_base_url($this, '', true),
      'WG_SITENAME' => $wgSitename,
      'WG_SCRIPT_PATH' => $wgScriptPath,
      'MW_PAGENAME' => $pagename,
      'WW_OUTPUTFORMAT' => $wwOutputFormat,
    );
  }

  # When we lock and sync this project, we don't just lock and sync this 
  # project, but also
  # the ones it depends on.  We may also copy their working files, to 
  # avoid conflicting operations.  Here we list all dependency projects
  # in the PE request, so it can manage them.  Along with their URIs, we
  # include what environment variable name to associate with each, whether
  # it is treated as readonly (if not, it may be copied), and info for
  # the projects it in turn depends on.
  public function assemble_transitive_dependencies()
  { if ( $this->depends_on_transitively === null )
    { $this->depends_on_transitively = array();
      $this->add_to_transitive_dependencies(
        $this->depends_on_transitively, false);
    }
    return $this->depends_on_transitively;
  }

  public function add_to_transitive_dependencies(
        &$depends_on_transitively, $readonly)
  { global $wwContext;
    $not_yet_added = array();
    if (is_array($this->depends_on))
    { # do all the varnames and readonlys first,
      # in case one of mine overrides one of theirs.
      foreach ($this->depends_on as $depname=>$depinfo)
      { $deepend = $wwContext->wwStorage->find_project_by_name(
              $depname,true,$this->as_of_revision);
        $duri = $deepend->project_uri();
        if (!isset($depends_on_transitively[$duri]))
        { $depends_on_transitively[$duri] = array();
          $depends_on_transitively[$duri]['project'] = $deepend;
          if (isset($depinfo['varname']))
            $depends_on_transitively[$duri]['varname'] = $depinfo['varname'];
          else
            $depends_on_transitively[$duri]['varname'] 
              = ProjectDescription::default_varname($deepend->project_name());
          $depends_on_transitively[$duri]['readonly'] = 
            ($readonly || (isset($depinfo['readonly']) && $depinfo['readonly']));
          $depends_on_transitively[$duri]['short-dir'] = 
            $this->short_dir_for_uri($duri, 
              $depends_on_transitively[$duri]['varname']);
          $not_yet_added[$duri] = $deepend;
        }
      }
      foreach ($not_yet_added as $duri => $deepend)
      { $deepend->add_to_transitive_dependencies($depends_on_transitively,
          $depends_on_transitively[$duri]['readonly']);
      }
    }
  }

  public function short_dir_for_uri($uri, $varname)
  { if (strncmp($uri, 'pe-ww:', 6) === 0)
      $dname = preg_replace('/^.*:/','',$uri);
    else if (strncmp($uri, 'pe-git:', 7) === 0)
      $dname = preg_replace('/^.*:(.*?)(\.git)?$/', '$1', $uri);
    else if ($varname !== null)
      $dname = preg_replace('/^PROJECT_DIR_/','', $varname);
    else if ( class_exists( 'PEAPI' ) )
      $dname = PEAPI::uri_to_dir($uri);
    else
      $dname = $uri;
    $dname = str_replace('/','_',$dname);
    return $dname;
  }

  # add a file to the project, for instance before writing the new 
  # project-description out to its page.
  # if the file is already in there, these values will overwrite the
  # old ones, one by one.  Setting a value to null in $attrs causes the
  # attribute to be removed from the file.
  # The exceptions are 'appears' and 'archived': these are arrays, and
  # the keys within will overwrite the keys in the corresponding arrays
  # in project_files.
  public function add_file_element($attrs)
  { global $wwContext;
    $filename = $attrs['filename'];
    #wwLog( $this->project_name() . " add_file_element $filename: "
    #  . serialize($attrs) );
    #wwLog( "project description before:\n" 
    #  . print_r($this->project_description_text(), true) );
    $filename = wwfSanitizeInput($filename);
    if (!ProjectDescription::is_allowable_filename($filename))
      $wwContext->wwInterface->throw_error("Prohibited filename ‘"
        . htmlspecialchars($filename) . "’.");
    $el_attrs = ((isset($this->project_files[$filename]) and 
            is_array($this->project_files[$filename])) ?
      $this->project_files[$filename] : array());
    // now put each value in $attrs in place of the old value.
    foreach ($attrs as $key=>$val)
      if ( $val !== null )
      { if ($key == 'archived' or $key == 'appears')
        { $key_not = ($key == 'archived' ? 'appears' : 'archived');
          foreach ($val as $pg=>$t)
	  { unset($el_attrs[$key_not][$pg]);
	    $san_pg = wwfSanitizeInput($pg);
            unset($el_attrs[$key_not][$san_pg]);
            $san_t = Title::newFromText($san_pg);
	    if ( $san_t instanceOf Title ) {
              $san_pg = $san_t->getPrefixedDBKey();
              unset($el_attrs[$key_not][$san_pg]);
	    }
            $el_attrs[$key][$san_pg] = true;
          }
        }
        else
          $el_attrs[$key] = wwfSanitizeInput($val);
      }
      else
        unset($el_attrs[$key]);
    $this->project_files[$filename] = $el_attrs;
    #wwLog( "project description after:\n" 
    #  . print_r($this->project_description_text(), true) );
    return true;
  }

  public function data_for_dynamic_placeholder() {
	  return 'data-project="'
		. htmlspecialchars( $this->project_name() )
		. '"';
  }

  # some kinds of projects can generate a git export...
  public function export_git()
  { global $wwContext;
    $wwContext->wwInterface->throw_error( "\"Export-git\" action not implemented for this "
      . "project." );
  }
}
