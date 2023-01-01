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
 * PEAPI
 *
 * ProjectEngine receives an HTTP request for a project file,
 * updates the project's cached working directory from stored project data,
 * updates the target file using make if requested, serves the up-to-data
 * file contents.
 *
 * PEAPI class handles HTTP requests and invokes the other classes.
*/

# do this explicitly, because this class can be called directly from WW
require_once dirname(__FILE__).'/ProjectEngine.php';

class PEAPI
{ # record HTTP request data
  var $ptype, $ploc, $target, $operation, $html;
  # store result data for serialized output
  var $result;
  # buffer up HTML output
  var $outputBody;

  function respondToRequest()
  { $this->outputBody = '';
    #$this->outputBody .= 
	#  'PHP_SELF : ' . htmlspecialchars( $_SERVER['PHP_SELF'] ) . "<br/>\n" .
	#  'SCRIPT_NAME : ' . htmlspecialchars( $_SERVER['SCRIPT_NAME'] ) . "<br/>\n" .
	#  'PATH_INFO : ' . htmlspecialchars( $_SERVER['PATH_INFO'] ) . "<br/>\n";
	#  $this->outputPage( 'html' );
  	#exit(0);
    # http://stackoverflow.com/a/326331
    if ( $_SERVER['PHP_SELF'] != $_SERVER['SCRIPT_NAME'] ) {
	    $result = array();
	    $this->process_request_raw( (count($_POST) > 0), $_SERVER['PATH_INFO'], $_REQUEST, $result );
	    peLog( 'project name ' . $request['operation']['project'] );
	    $output_format = 'html';
    }
    else if ( ! isset( $_REQUEST['request'] ) and ! isset( $_REQUEST['operation'] ) ) // invoked with no instructions
    { $this->outputBody .= <<<EOFORM
<form action="{$_SERVER['REQUEST_URI']}" method="post">
<label for="peProjectType">Project type:</label>
<input type="text" id="peProjectType" name="peProjectType"/><br/>
<label for="peProjectLocation">Project location:</label>
<input type="text" id="peProjectLocation" name="peProjectLocation"/><br/>
<label for="peOperation">Operation:</label>
<input type="text" id="peOperation" name="peOperation"/><br/>
<label for="peTarget">Target name:</label>
<input type="text" id="peTarget" name="peTarget"/><br/>
<label for="peSourceFileContents">Source file contents:</label>
<input type="text" id="peSourceFileContents" name="peSourceFileContents"
 length="40"/> (optional)<br/>
<label for="peArchivedFileHashes">Archived file hashes:</label>
<input type="text" id="peArchivedFileHashes" name="peArchivedFileHashes"
 length="40"/> (optional)<br/>
<input type="hidden" id="peHTMLInterface" value="1"/>
<input type="submit" id="peSubmit" value="submit"/>
</form>
EOFORM;
      $this->outputPage('html');
      return;
    }
    else
    { if ( isset($_REQUEST['input-format']) ) {
        $input_format = $_REQUEST['input-format'];
        if ($input_format == 'base64_serialized_php_array')
          #(array_key_exists('request',$_REQUEST))
        { #peLog("PE: request is ".strlen($_REQUEST['request']).' long');
          $request = unserialize(base64_decode($_REQUEST['request']));
          #peLog("PE: request is unserialized");
        }
        else
        { #peLog("PE: request is in pieces?");
          $request = $_REQUEST;
          $srf = serialize(false);
          foreach ($request as $k=>$v)
            if (is_string($v))
            { $uns = @unserialize($v);
              if ($uns !== false or $v === $srf)
                $request[$k] = $uns;
            }
        }
      } else {
        $request = $_REQUEST;
      }
      if ( isset( $_REQUEST['output-format'] ) )
        $output_format = $_REQUEST['output-format'];
      if (!is_array($request))
      { PEMessage::record_error("Bad request data");
        #peLog("PE: Bad request data", 0);
        $result = array('peMessages'=>PEMessage::report_messages_as_array());
      }
      else
      { try
        { $this->process_request($request, $result);
        } catch(PEException $ex) { }
      }
    }
    peLog( 'result: ' . json_encode( $result ) );
    if ($output_format == 'base64_serialized_php_array')
      $this->outputBody
        .= "result=".base64_encode(serialize($result))."\n";
    else if ($output_format == 'html') {
      if ( isset( $result['messages'] ) ) {
        foreach ( $result['messages'] as $m ) {
	  $this->outputBody .= $m[0] . ': ' . htmlspecialchars( $m[1] ) . "<br/>\n";
	}
      }
      $this->outputBody .= PEMessage::report_messages();
    }
    #peLog( 'output: ' . $this->outputBody );
    if ($output_format !== 'html' or !isset($result['abort-output']))
      $this->outputPage($output_format);
  }

  public function process_request_raw( $posted, $path_info, $fields, $result )
  {
	  if ( $path_info !== null ) {
	    # simple case: http://URL/project-uri/filename
	    # this is when there's path info at the end of the URL
	    $path_parts = explode( '/', ltrim( $path_info, '/' ) );
	    # the first element has to be the full project name, url-escaped
	    # so it contains no slashes.  Actually for safety, it looks like
	    # it has to be double urlencoded.
	    $project = urldecode( array_shift( $path_parts ) );
	    $filename = implode( '/', $path_parts );
	    $request = array(
		    'operation' => array(
			    'name' => 'retrieve',
			    'project' => $project,
			    'target' => $filename,
			    'output-directly' => true,
		    ),
	    );
	    # TODO: if path_info is set and some GET/POST values as well
	  } else if ( isset( $fields['request'] ) ) {
		  # TODO: base 64 encoding, etc
		  $request = $fields['request'];
	  } else {
		  $request = $fields;
	  }

	  return $this->process_request( $request, $result );
  }

  # Given a request in the form of a PHP array with various key-value pairs,
  # interpret and execute the operation.
  public function process_request($request, &$result)
  { #peLog( 'PEAPI::process_request ' . json_encode( $request ) );

    # If there's a 'preview' or 'background-job' value, we'll do the 
    # operation in a preview or background session, rather than in the 
    # persistent working directory.  If so, we have to take special steps
    # to lock the right directories, including locking the persistent ones
    # as well when copying to or from them.
    # FIXME: redesign this logic - there are too many special cases.
    # Maybe it would be better to: 
    #   leave locking responsibility to the Operation objects, with 
    #     inherited default behavior.
    #   uncouple session objects from repository objects, unlike the current
    #     design where a repository object is glued to a session.
    #   directory objects are a pain.  maybe we just shouldn't have them.
    $pers_session  = PEPersistentSession::persistent_session(); 

    if (array_key_exists('preview',$request))
    { $special_session = new PEPreviewSession($request['preview']);
      #peLog("preview session: ".serialize($preview_session));
    }
    else if (array_key_exists('background-job',$request))
    { $special_session = new PEBackgroundSession($request['background-job']);
      #peLog( 'PE: background session ' . $request['background-job'] );
    }

    # if there's a project named in the operation but not in 'projects',
    # add it into 'projects' for simplicity
    if (array_key_exists('operation',$request) and
          array_key_exists('project',$request['operation']) and
        (!array_key_exists('projects',$request) or
           !array_key_exists($request['operation']['project'],
             $request['projects'])))
      $request['projects'][$request['operation']['project']] = array();
    # to do: check on 'resources' pseudo-project, make sure it's treated
    # as read-only throughout (and left out of the session nonsense)

    try
    { $operation = PEOperation::create_operation($request);

      $lock_pers_sess = $operation->need_persistent_session();
      if ($lock_pers_sess)
      { PEMessage::debug_message("keep persistent session locked");
      }

      $main_repos = array();
      if (array_key_exists('projects',$request))
      { if ( ! isset($special_session) )
          foreach ($request['projects'] as $uri=>$project_info)
          { $uri = self::resolve_uri_synonyms($uri);
            $main_repos[$uri] 
              = PERepositoryInterface::factory($uri,$pers_session,$request);
          }
        else
          foreach ($request['projects'] as $uri=>$project_info)
          { $uri = self::resolve_uri_synonyms($uri);
            $ro = isset($project_info['readonly']) ? 
              $project_info['readonly'] : false;
            if ($lock_pers_sess or $ro)
            { $readonly_repos[$uri] 
                = PERepositoryInterface::factory($uri,$pers_session,$request);
	    }
            if ( ! $ro )
            { $main_repos[$uri] 
                = PERepositoryInterface::factory($uri,$special_session,$request);
              if ($special_session->need_to_create($main_repos[$uri]))
              { PEMessage::debug_message("Need to copy "
                  . htmlspecialchars($uri));
                $copy_from_repos[$uri]
                  = PERepositoryInterface::factory($uri,$pers_session,$request);
              }
            }
          }

        # now we have one of two setups:
        # 1. in a special-session operation, $main_repos is all 
        #    special-session directories.  in this case, some of those
        #    projects' persistent directories may be in $copy_from_repos
        #    as well, while other projects may be represented only by 
        #    persistent directories in $readonly_repos.
        # 2. in a persistent-session operation, $main_repos is all
        #    persistent repos, and the other lists are unset.

        # carefully with the locking!  we need a total ordering on project 
        # directories to prevent deadlock.  we lock all special
        # session dirs (alphabetically) before all permanent directories,
        # and unlock in reverse order.
        # But with the readonly feature we wish to:
        #   lock all special dirs AND perm dirs that house dependencies
        #     for the duration
        #   lock permanent dirs that are only used for copying ONLY during
        #     the copy operation at the beginning.
        # The order has to be followed scrupulously, so we will:
        #   lock the special dirs
        #   lock the perm dirs needed for copying
        #   do the copying
        #   unlock all those perm dirs
        #   lock the perm dirs needed as readonly dependencies
        #   do the operation
        #   unlock all those perm dirs
        #   unlock special dirs.
        
        # ALSO!  Note PESpecialSession::merge_into_persistent_session()
        # does its own locking, so make sure that's consistent with this
        # and uses the same ordering convention!

        #$double_lock = ($lock_pers_sess or $copy_dirs);
      
        # note lock_repos creates the special sessions' directories, so 
        # need_to_create() needs to be called before it, not after
        $this->lock_repos($main_repos, $request);

        # update the working dirs, by copying from persistent if needed,
        # and syncing the source file contents if needed.
        if (isset($copy_from_repos))
        { $this->lock_repos($copy_from_repos, $request);
          foreach ($main_repos as $repo)
            $special_session->initialize_working_directory($repo,$request);
          $this->unlock_repos($copy_from_repos, $request);
        }
        foreach ($request['projects'] as $uri=>$project_info)
        { $uri = self::resolve_uri_synonyms($uri);
          if (array_key_exists('source-file-contents', $project_info))
          { $sfc = $project_info['source-file-contents'];
            if (isset($main_repos) and isset($main_repos[$uri])
                and isset($main_repos[$uri]->wd))
	    { log_sse_message( "Syncing source files to project "
	          . $main_repos[$uri]->short_project_name( $request )
		  . ' '
		  . $main_repos[$uri]->session->session_type()
		  . ' directory:',
		  $request
	      );
              $main_repos[$uri]->wd->syncFilesFromContents($sfc, $request,
                  $request['operation']['name'] == 'force-sync');
	      log_sse_message( "\n", $request );
	    }
            # though it's counterintuitive, we do allow syncing in readonly
            # projects.
            else if (isset($readonly_repos) and isset($readonly_repos[$uri])
                      and isset($readonly_repos[$uri]->wd))
	    { log_sse_message( "Syncing source files to project "
	          . $readonly_repos[$uri]->short_project_name( $request )
		  . ' '
		  . $readonly_repos[$uri]->session->session_type()
		  . ' directory:',
		  $request
	      );
              $readonly_repos[$uri]->wd->syncFilesFromContents($sfc, $request,
                  $request['operation']['name'] == 'force-sync');
	      log_sse_message( "\n", $request );
	    }
            else
            { PEMessage::record_error("could not sync in "
                .htmlspecialchars($uri));
	    }
          }
	  # FIXME obviously this should happen in the PEOperation_force_sync 
	  # class, not here
	  if ( $request['operation']['name'] == 'force-sync' )
          { if (isset($main_repos) and isset($main_repos[$uri]))
              $main_repos[$uri]->sync_from_repo( $request );
            else if (isset($readonly_repos) and isset($readonly_repos[$uri]))
              $readonly_repos[$uri]->sync_from_repo( $request );
            else
              PEMessage::record_error("could not sync "
                .htmlspecialchars($uri));
          }
        }
      }

      # lock any persistent repos that are readonly dependencies
      if (isset($readonly_repos))
      { $this->lock_repos($readonly_repos, $request);
      }

      # figure out which project to do the operation in
      # it's generally in $request['operation']['project']
      if (array_key_exists('project', $request['operation']))
        $op_uri = self::resolve_uri_synonyms($request['operation']['project']);
      # or else it's the first entry in $request['projects']
      # (and can't be marked readonly).
      else if (count($main_repos) > 0)
      { reset($main_repos);
        $op_uri = key($main_repos);
      }
      else
        $op_uri = null;

      # do the operation.
      try {
        $active_repos = $main_repos;
        if (isset($readonly_repos))
          $active_repos = array_merge($readonly_repos, $active_repos);
        $operation->execute($request,$op_uri,$active_repos,$result);
      } catch ( PEAbortOutputException $ex )
      { if ( $ex->succeeded )
        { if (isset($readonly_repos))
            $this->unlock_repos($readonly_repos, $request);
          if (isset($main_repos))
            $this->unlock_repos($main_repos, $request);
          exit;
          $result['abort-output'] = true;
          return true;
        }
      }

      # collect any archived project files that have changed.
      if (array_key_exists('projects',$request))
      { $acc = $request['pe-files-are-accessible'];
        foreach ($request['projects'] as $uri=>$project_info)
        { $xuri = self::resolve_uri_synonyms($uri);
          if (isset($project_info['archived-file-hashes'])
              and array_key_exists($xuri,$active_repos))
          { $afh = $project_info['archived-file-hashes'];
            $afc = $active_repos[$xuri]->wd->updatedFileContents($afh, $acc);
            if (is_array($afc))
              $result['archived-file-contents'][$uri] = $afc;
          }
        }
      }

      # unlock the locked directories.
      if (isset($readonly_repos))
        $this->unlock_repos($readonly_repos, $request);
      if (isset($main_repos))
        $this->unlock_repos($main_repos, $request, $operation->delete_lockfiles_after());
    } catch (PEException $ex) {}
    $ms = PEMessage::report_messages_as_array();
    if (count($ms))
      $result['messages'] = $ms;
    return true;
  }

  function lock_repos($repos, $request)
  { ksort($repos);
    foreach($repos as $r)
      $r->lock_directory( $request );
  }

  function unlock_repos($repos, $request, $delete_lockfiles = false)
  { ksort($repos);
    foreach(array_reverse($repos) as $r)
      $r->unlock_directory($request, $delete_lockfiles);
  }

  static function uri_dir_translations()
  { static $udtrans;
    if (!isset($udtrans))
    { $udtrans = array(
        array('&','http://','https://',':','?'),
        array('&amp;', '', '', '/', '/') );
    }
    return $udtrans;
  }

  static function old_uri_dir_translations()
  { static $o_udtrans;
    if (!isset($o_udtrans))
    { $o_udtrans =  array(
          array('&','~','!',':','/'),
          array('&amp;','&tilde;','&excl;','~','!') );
      # note pipe and excl are not actual HTML/XML character references - 
      # I just made them up for this purpose. - LW
    }
    return $o_udtrans;
  }

  static function resolve_uri_synonyms($uri)
  { global $peURISynonyms;
    if (is_array($peURISynonyms))
    { $xuri = preg_replace(array_keys($peURISynonyms), 
                array_values($peURISynonyms), $uri);
      #PEMessage::debug_message("resolved URI synonyms: "
      #    .htmlspecialchars($uri) . ' to '. htmlspecialchars($xuri));
      $uri = $xuri;
    }
    return $uri;
  }

  static function uri_to_dir($uri)
  { $trans = self::uri_dir_translations();
    $r = $uri;
    # given "pe-ww:http://a.b.c/d/e:f/g"
    $cp = strrpos($r,':');
    if ($cp !== false)
    { $r = substr($r, 0, $cp+1) . str_replace('/','_',substr($r,$cp+1));
    }
    # now we have "pe-ww:http://a.b.c/d/e:f_g"
    $r = str_replace( $trans[0], $trans[1], self::resolve_uri_synonyms($r) );
    # new we have "pe-ww/a.b.c/d/e/f_g"
    return $r;
  }

  static function uri_to_dir_obsolete($uri)
  { global $peURISynonyms;
    $ruri = preg_replace( array_keys($peURISynonyms),
              array_values($peURISynonyms), $uri);
    $ar = array($uri);
    if ($ruri !== $uri)
      $ar[] = $ruri;
    $trans = self::old_uri_dir_translations();
    return( str_replace( $trans[0], $trans[1], $ar ) );
  }

  static function dir_uri_translations()
  { static $dutrans;
    if (!isset($dutrans))
    { $udtrans = self::uri_dir_translations();
      $dutrans = array( 
        array_reverse($udtrans[1]), array_reverse($udtrans[0]) );
    }
    return $dutrans;
  }

  static function dir_to_uri($dir)
  { $trans = self::dir_uri_translations();
    return str_replace( $trans[0], $trans[1], $dir);
  }

  function outputPage($output_format)
  { if ($output_format == 'html') {
      # html5
      echo <<<EOPAGE
<!DOCTYPE html>
<html>
<head>
<title>ProjectEngine</title>
</head>
<body>
{$this->outputBody}
</body>
</html>
EOPAGE;
    } else {
      echo $this->outputBody;
    }
  }
}
