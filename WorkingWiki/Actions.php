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

/* Actions.php
 * WorkingWiki functions for the various operations that can be 
 * invoked by a "ww-action=" argument in an HTTP request.
 *
 * Different actions require different additional arguments to be present.
 */
global $wwExtensionDirectory;
require_once($wwExtensionDirectory."/misc.php");

# abstract parent class for action classes, one for each action.
abstract class WWAction 
{
  # Descendant classes must implement execute(), 
  # and may implement requires_edit_permission().
  # Also, they must all be named appropriately.  When 'ww-action=xyz' is
  # requested, WWAction looks for a subclass named WWAction_xyz.
  # Any '-' in the action name is changed to '_'.
  
  # execute() does a specific action, given the full request data.
  # return value is an array with
  #  'status' => WW_SUCCESS, WW_WARNING, WW_ERROR, or WW_QUESTION.
  #  'message' => string to be shown to the user.
  # this function should generally trap WWExceptions and return 
  #  WW_ERROR or WW_WARNING as needed, though there is a generic implementation
  #  of this provided for actions that don't catch their exceptions.
  # 'status'=>WW_ABORTOUTPUT can also be used in a special case: 
  #  WWAction_export uses it for example, when it serves its output as a 
  #  special HTTP response, and doesn't need a new HTML page to be output.
  # all messages should begin with a capital and end with final punctuation.
  abstract function execute(&$request);

  # requires_edit_permission() controls whether an action is available
  # to wiki users who aren't allowed to edit the wiki.  If so, that 
  # WWAction subclass should return false from this function.
  function requires_edit_permission()
  { return true;
  }

  # WWAction's interface to the outside world.
  # You pass the $wgRequest object to this function, we'll do the rest.
  # return value is
  #  array( 'status'=>status, 'html'=>raw HTML to be output ).
  # the exception is if 'status' = ABORT_OUTPUT, the caller should quit
  #  building an output page and return immediately.
  static function execute_action(&$request)
  { $action_name = $request->getText( 'ww-action' );
    # if 'cancel' is also in the request, don't do the action.
    $cancel = $request->getCheck( 'cancel' );
    if ( $action_name == '' or $cancel )
      return array( 'status' => WW_NOACTION );
    return self::execute_action_by_name($action_name, $request);
  }

  static function make_action_message( $params, $tag )
  { global $wwApiMessages;
    $argNames = null;
    $msgTag = $params['action'] . $tag;
    $msg = wfMessage( $msgTag );
    if ( $msg->exists() )
    { if ( array_key_exists( $params['action'], $wwApiMessages ) and
          array_key_exists( "args$tag", $wwApiMessages[ $params['action'] ] ) )
        $argNames = $wwApiMessages[ $params['action'] ][ "args$tag" ];
      else if ( array_key_exists( $params['action'], $wwApiMessages ) and
               array_key_exists( 'args', $wwApiMessages[ $params['action'] ] ) )
        $argNames = $wwApiMessages[ $params['action'] ][ 'args' ];
    }
    else
    { $msgTag = "ww-default$tag";
      $msg = wfMessage( $msgTag );
    }
    if ( $argNames === null )
    { if ( array_key_exists( "args$tag", $wwApiMessages[ 'default' ] ) )
        $argNames = $wwApiMessages[ 'default' ][ "args$tag" ];
      else
        $argNames = $wwApiMessages[ 'default' ][ 'args' ];
    }
    $args = array( $msgTag );
    foreach ($argNames as $arg) {
        $args[] = $params[ $arg ];
    }
    return call_user_func_array( 'wfMessage', $args );
  }
    
  static function execute_action_by_name($action_name, &$request)
  { $class_name = 'WWAction_' . strtr($action_name, '- ', '__');
    global $wgUser;
    if ( is_callable( array($class_name,'execute') ) )
    { # if there's a WWAction subclass for this operation, use it
      $operation = new $class_name;
      { if ( $operation->requires_edit_permission()
              and wwfReadOnly() )
          return array( 'status' => WW_ERROR,
            'html' => "<p class='ww-action-error'>Error: action ‘"
              . htmlspecialchars($action_name) . "’"
              . " requires permission to edit the wiki.</p>\n" );
        try {
          $result = $operation->execute($request);
        } catch ( WWException $ex )
        { $result = false;
        }
      }
      if ($result === false)
        return array( 'status' => WW_ERROR,
          'html' => "<p class='ww-action-error'>"
            . "Error executing ‘" . htmlspecialchars($action_name) 
            . "’ action.</p>\n" );
    } else {
      # if there isn't a WWAction subclass, try assuming there's an
      # ApiBase subclass that can handle it.
      $result = WWAction::passToApi( $request, $action_name );
    }
    if ($result['status'] == WW_ABORTOUTPUT 
        or $result['status'] == WW_NOACTION)
      return $result;
    $xtra = '';
    if ($result['status'] == WW_SUCCESS)
      $cssclass = 'ww-action-message';
    else if ($result['status'] == WW_WARNING)
      $cssclass = 'ww-action-warning';
    else if ($result['status'] == WW_ERROR)
      $cssclass = 'ww-action-error';
    else if ($result['status'] == WW_QUESTION)
    { $cssclass = 'ww-action-question';
      global $wgScript, $wgTitle;
      $xtra = "\n<form action='$wgScript'>\n"
        ."<input type='hidden' name='title' "
        .  "value='{$wgTitle->getPrefixedDBKey()}'/>\n";
      foreach ($request->getValues() as $key=>$val)
        $xtra .= "<input type='hidden' name='" .htmlspecialchars($key)
         . "' value='" . htmlspecialchars($val) . "'/>\n";
      $xtra .= "<input type='submit' name='cancel' value='Cancel'/>\n";
      foreach ($result['choices'] as $name=>$label)
        $xtra .= "<input type='submit' name='" . htmlspecialchars($name)
         . "' value='" . htmlspecialchars($label) . "'/>\n";
      $xtra .= "</form>\n";
    }
    else
      return array('status' => WW_ERROR,
        'html'=>'<p class="ww-action-error">Internal error: WWAction status '
          . 'code ' . $result['status'] . ' not recognized.</p>'."\n" );
    return array('status' => $result['status'],
      'html' => "<p class='$cssclass'>" 
        #. htmlspecialchars($result['message'], ENT_QUOTES, "UTF-8", false)
        . $result['message']
        . $xtra . "</p>\n" );
  }

  public static function passToApi( $request, $action_name ) {
      global $wwContext;
      # standard API action name is simple
      $apiName = "ww-$action_name";
      # do some standard parameter renamings
      $request_mods = array();
      foreach ( $request->getValueNames() as $key ) {
	      if ( substr( $key, 0, 10 ) == 'ww-action-' ) {
		      $request_mods[ substr( $key, 10 ) ] = $request->getVal($key);
	      }
      }
      $request_mods['action'] = $apiName;
      wwLog( "in passToApi: " . json_encode( $request_mods ) );
      $drequest = new DerivativeRequest( $request, $request_mods );
      # ask for confirmation if needed
      global $wwConfirmationsForApiActions;
      if ( $request->getVal('confirm', null) === null and
      		wfMessage( $apiName . '-confirm-message' )->exists() ) {
        $result = array( 'status' => WW_QUESTION,
          'message' => self::make_action_message( $request_mods, '-confirm-message' ),
	  'choices' => array( 'confirm' => self::make_action_message( $request_mods, '-confirm-button' ) ) );
      } else {
        try {
	  global $wgEnableWriteAPI;
          $api = new ApiMain( $drequest, $wgEnableWriteAPI );
          $api->execute();
          $apiResult = $api->getResultData();
        } catch( UsageException $ex ) {
          $apiResult = array( 'error' => $ex->getMessageArray() );
	} catch ( WWException $ex ) {
		# messages will be reported
	}
        if ( array_key_exists( 'error', $apiResult ) ) {
          if ( $apiResult['error']['code'] == 'unknown_action' ) {
            $result = array( 'status' => WW_ERROR,
              'message' => $wwContext->wwInterface->message( 'ww-unknown-action', $action_name ) );
	  } else {
	    $result = array(
		'status' => WW_ERROR,
	        'message' => $apiResult['error']['info']
	    );
	  }
        } else {
          $result = array( 'status' => WW_SUCCESS,
            'message' => self::make_action_message( $request_mods, '-success' ) );
	}
      }
      return $result;
  }

  # utility function for descendants.  Returns:
  #  array( 'status'=>SUCCESS, 'project'=>(Project object) )
  # or
  #  array( 'status'=>ERROR, 'message'=>(message) )
  function look_up_project($projectname)
  { global $wwContext;
    //return array( 'status' => WW_WARNING, 'message' => $projectname );
    if ($projectname === '' or is_null($projectname))
      return array( 'status'=>WW_ERROR, 
        'message'=>"No project name given" );
    try
    { $project = $wwContext->wwStorage->find_project_by_name($projectname);
    } catch ( WWException $ex )
    { $project = null;
    }
    global $wwProjectDescriptionNamespaceName;
    if ( ! is_null($project) )
      return array( 'status'=>WW_SUCCESS, 'project'=>&$project );
    try
    { $pd = $wwProjectDescriptionNamespaceName.':'
              .ProjectDescription::normalized_project_name($projectname);
    } catch ( WWException $ex )
    { return array( 'status'=>WW_ERROR,
      'message' => "Error interpreting project name ‘"
        . htmlspecialchars($projectname) . "’." );
    }
    global $wgUser;
    return array( 'status'=>WW_ERROR,
      'message' => "Project ‘" . htmlspecialchars($projectname) 
        . "’ not found.  Try "
        . $wwContext->wwInterface->makeLink($pd,$pd,$wgUser->getSkin()).'.' );
  }

  function not_implemented()
  { global $wwContext;
    $wwContext->wwInterface->throw_error("Action not implemented.");
  }
}

class WWAction_set_project_options extends WWAction
{ function execute(&$request)
  { global $wwContext;
    $lookup = $this->look_up_project($request->getText('project'));
    if ($lookup['status'] != WW_SUCCESS)
      return $lookup;
    $project =& $lookup['project'];
    $option_names = array('use-default-makefiles');
    foreach ($option_names as $name)
    { $option = $request->getVal($name,null);
      if ($option === null)
      { $option = 0;
        $rem_result = ProjectEngineConnection::call_project_engine(
		'remove', $project, array('target'=>'GNUmakefile') );
        $rem_success = $rem_result['succeeded'];
        if ( ! $rem_success ) {
	  $wwContext->wwInterface->throw_error( 'Failed to remove GNUmakefile from '
            . 'project ‘'
	    . htmlspecialchars($project->project_name())
            . "’ working directory." );
        }
      }
      else if ($option === 'on')
        $option = 1;
      $project->options[$name] = $option;
    }
    try {
      $wwContext->wwStorage->save_project_description($project);
      $wwContext->wwInterface->invalidate_pages( $project );
    } catch ( WWException $ex )
    { return array( 'status'=>WW_ERROR,
        'message' => "Error setting options for project ‘"
          . htmlspecialchars($project->project_name()) . "’." );
    }
    return array( 'status'=>WW_SUCCESS,
      'message' => "Updated options for project ‘"
          . htmlspecialchars($project->project_name()) . "’." );
  }
}

class WWAction_set_appears extends WWAction
{ function execute(&$request)
  { global $wwContext;
    $filename = trim($request->getText('action-filename'));
    if ($filename == '')
      return array( 'status' => WW_ERROR,
        'message' => 'Missing filename for ‘set’ action.' );
    if ( ! ProjectDescription::is_allowable_filename($filename) )
      return array( 'status' => WW_ERROR,
        'message' => "Prohibited filename ‘.".htmlspecialchars($filename)."’." );
    $page = trim($request->getText('appears'));
    $lookup = $this->look_up_project($request->getText('project'));
    if ($lookup['status'] != WW_SUCCESS)
      return $lookup;
    $project = $lookup['project'];
    try {
      $project->add_file_element(
        array( 'filename' => $filename, 'appears' => array($page=>true) ) );
      $wwContext->wwStorage->save_project_description($project);
      $wwContext->wwInterface->invalidate_pages( $project );
    } catch ( WWException $ex )
    { return array( 'status'=>WW_ERROR,
        'message' => "Error recording location for project-file ‘"
          . htmlspecialchars($filename) . "’." );
    }
    return array( 'status'=>WW_SUCCESS,
      'message' => "Recorded location of project-file ‘"
        . htmlspecialchars($filename) . "’ on ‘"
        . htmlspecialchars($page) . "’." );
  }
}

class WWAction_set_archived extends WWAction
{ function execute(&$request)
  { global $wwContext;
    $filename = trim($request->getText('action-filename'));
    if ($filename == '')
      return array( 'status' => WW_ERROR,
        'message' => 'Missing filename for ‘set’ action.' );
    if ( ! ProjectDescription::is_allowable_filename($filename) )
      return array( 'status' => WW_ERROR,
        'message' => "Prohibited filename ‘".htmlspecialchars($filename)."’." );
    $lookup = $this->look_up_project($request->getText('project'));
    if ($lookup['status'] != WW_SUCCESS)
      return $lookup;
    $project =& $lookup['project'];
    $page = trim($request->getText('archived'));
    global $wwImportImageExtensions;
    $title = Title::newFromText($page);
    if ( ! $title instanceof Title )
      $wwContext->wwInterface->throw_error("Bad archived location ‘"
        . htmlspecialchars($page) . '’.');
    $ns = $title->getNamespace();
    if (in_array(ProjectDescription::type_of_file($filename), $wwImportImageExtensions)
        and $ns != NS_IMAGE and $ns != NS_MEDIA
        and !$request->getCheck('ww-confirm-image'))
          return array( 'status' => WW_QUESTION,
            'message' => $wwContext->wwInterface->message('ww-confirm-image',
              htmlspecialchars($filename), htmlspecialchars($page)),
            'choices' => array('ww-confirm-image'=>'Set') );
    try {
      $project->add_file_element(
        array( 'filename' => $filename, 'archived' => array($page=>true) ) );
      $wwContext->wwStorage->save_project_description($project);
      $wwContext->wwInterface->invalidate_pages( $project );
      # we want it archived there, so archive it there, don't make the
      # user try to figure out what they missed
      #ProjectEngineConnection::make_target($project,$filename);
      $op_result = ProjectEngineConnection::call_project_engine(
        'retrieve-archived-files', $project, 
        array(), array(), /*sync*/false);
      $wwContext->wwStorage->update_archived_project_files();
    } catch ( WWException $ex )
    { return array( 'status'=>WW_ERROR,
      'message' => "Error recording location for project-file ‘"
          . htmlspecialchars($filename) . "’." );
    }
    return array( 'status'=>WW_SUCCESS,
      'message' => "Recorded location of archived project-file ‘"
        . htmlspecialchars($filename) . "’ on ‘"
        . htmlspecialchars($page) . "’." );
  }
}

class WWAction_export_sf extends WWAction
{ function requires_edit_permission()
  { return false; }
  function save_pages(&$project,$wwpath)
  { global $wwContext;
    # save the wikitext of pages that include this project's files
    $project->ensure_directory_exists("$wwpath/pages", 0755);
    foreach ($project->pages_involving_project_files() as $pagename)
    { $title = Title::newFromText($pagename);
      $article = new Article($title);
      if ($article->getID() !== 0)
      { $pagefile = "$wwpath/pages/".urlencode($title->getPrefixedDBKey());
        wwLog("Write $pagefile");
        $pagetext = $article->getContent();
        # strip out the file contents in the tags, and 
        # replace project="this" by project=""
        #
        # this special syntax <source-file...></source-file> distinguishes
        # places where file contents go from empty tags, <source-file.../>.
        #
        # TO DO: only strip out tags belonging to this project, 
        # and, put the project name back when importing the page.
        #wwLog("original pagetext:\n---\n$pagetext\n---");
        $offset = 0;
        while (1)
        { list( $first, $last ) = $wwContext->wwStorage->find_element( 
            'source-file', array( 'project'=>$project->project_name() ),
            $pagetext, $offset );
          if ( $first === false )
            break;
          $oldlen = strlen($pagetext);
          $pagetext = substr( $pagetext, 0, $first )
            . preg_replace(
                '/<source-file([^>]*)project=\s*".*?"([^>]*)>.*?<\/source-file>/is',
                '<source-file\1project=""\2></source-file>', 
                substr( $pagetext, $first, $last - $first + 1 ) )
            . substr( $pagetext, $last+1 );
          $offset = $last + 1 + strlen($pagetext) - $oldlen;
        }
        $offset = 0;
        while (1)
        { list( $first, $last ) = $wwContext->wwStorage->find_element( 
            'project-file', array( 'project'=>$project->project_name() ),
            $pagetext, $offset );
          if ( $first === false )
            break;
          $oldlen = strlen($pagetext);
          $pagetext = substr( $pagetext, 0, $first )
            . preg_replace(
              '/<project-file([^>]*)project=\s*".*?"([^>]*)>.*?<\/project-file>/is',
              '<project-file\1project=""\2></project-file>', 
              substr( $pagetext, $first, $last - $first + 1 ) )
            . substr( $pagetext, $last+1 );
          $offset = $last + 1 + strlen($pagetext) - $oldlen;
        }
        $proj_for_page =
          $wwContext->wwStorage->find_project_given_page( $title->getPrefixedDBKey() );
        if ($proj_for_page !== null and
            $proj_for_page->project_name() == $project->project_name())
        { $offset = 0;
          while (1)
          { list( $first, $last ) = $wwContext->wwStorage->find_element( 
              'source-file', array( 'project'=>null ),
              $pagetext, $offset );
            if ( $first === false )
              break;
            $oldlen = strlen($pagetext);
            $pagetext = substr( $pagetext, 0, $first )
              . preg_replace(
                '/<source-file([^>]*)>.*?<\/source-file>/is',
                '<source-file\1></source-file>', 
                substr( $pagetext, $first, $last - $first + 1 ) )
              . substr( $pagetext, $last+1 );
            $offset = $last + 1 + strlen($pagetext) - $oldlen;
          }
          $offset = 0;
          while (1)
          { list( $first, $last ) = $wwContext->wwStorage->find_element( 
              'project-file', array( 'project'=>null ),
              $pagetext, $offset );
            if ( $first === false )
              break;
            $oldlen = strlen($pagetext);
            $pagetext = substr( $pagetext, 0, $first )
              . preg_replace(
                '/<project-file([^>]*)>.*?<\/project-file>/is',
                '<project-file\1></project-file>', 
                substr( $pagetext, $first, $last - $first + 1 ) )
              . substr( $pagetext, $last+1 );
            $offset = $last + 1 + strlen($pagetext) - $oldlen;
          }
        }
        #wwLog("eviscerated pagetext:\n---\n$pagetext\n---");
        if (!preg_match('/\S/', $pagetext) or
            preg_match('/^\s*<(source|project)-file( (project|filename)=\"[^\"]*\")*?\s*\/?'
              .'>(\s*<\/(source|project)-file>)?\s*$/', $pagetext) or
            preg_match('/^\s*Importing image file\s*$/', $pagetext))
        { wwLog( "Nothing in page, skipping." );
          continue;
        }
        if (($write_file = fopen($pagefile,"w")) === false)
          $wwContext->wwInterface->throw_error("Can't open file ‘{$pagefile}’ for writing.");
        if (fwrite($write_file,$pagetext) === false)
          $wwContext->wwInterface->throw_error("Can't write to file ‘{$pagefile}’.");
        if (fclose($write_file) === false)
          $wwContext->wwInterface->throw_error("Can't close file ‘{$pagefile}’.");
      }
    }
  }
  function execute(&$request)
  { $lookup = $this->look_up_project($request->getText('project'));
    if ($lookup['status'] != WW_SUCCESS)
      return $lookup;
    $project =& $lookup['project'];
    $sync = true;
    $jobid = $request->getText('jobid');
    $preview = $request->getText('ww-action-preview-key',
	    $request->getText('wwPreviewKey') );
    $pe_req = array();
    if ( $jobid ) {
	    $pe_req['background-job'] = $jobid;
	    $sync = false;
    } else if ( $preview ) {
	    $pe_req['preview'] = $preview;
	    $sync = false;
    }
    $op_result = ProjectEngineConnection::call_project_engine(
      'export', $project, 
      array('source-files-only'=>true, 'include-ww-directory'=>true),
      $pe_req,
      $sync);
    # note export-sf syncs the source files, export-wd doesn't.

    # if successful that call doesn't return, it just outputs the tar.gz
    # file and exits.
    return array('status'=>WW_ERROR,
      'message' => "Error in export operation." );
    return array( 'status' => WW_ABORTOUTPUT );
  }
}

class WWAction_export_wd extends WWAction_export_sf
{ function execute(&$request)
  { $lookup = $this->look_up_project($request->getText('project'));
    if ($lookup['status'] != WW_SUCCESS)
      return $lookup;
    $project =& $lookup['project'];
    $jobid = $request->getText('jobid');
    $preview = $request->getText('ww-action-preview-key',
	    $request->getText('wwPreviewKey') );
    $pe_req = array();
    if ( $jobid ) {
	    $pe_req['background-job'] = $jobid;
    } else if ( $preview ) {
	    $pe_req['preview'] = $preview;
    }
    $op_result = ProjectEngineConnection::call_project_engine(
      'export', $project, 
      array('source-files-only'=>false, 'include-ww-directory'=>true),
      $pe_req, /*sync*/false);
    # if successful that call doesn't return, it just outputs the tar.gz
    # file and exits.
    return array('status'=>WW_ERROR,
      'message' => "Error in export operation." );
    return array( 'status' => WW_ABORTOUTPUT );
  }
}

# WWAction_remove_source_file is a shell: it does a complex confirmation if
# needed, and then calls the api's "ww-remove-file".
class WWAction_remove_source_file extends WWAction
{ function execute(&$request)
  { global $wwContext;
    return $this->_execute( $request, $wwContext->wwInterface->message( 'source-file' ) );
  }
  function _execute(&$request, $noun)
  { global $wwContext;
    $filename = $request->getText('ww-action-filename');
    wwLog("Action remove_source_file $filename");
    if ($filename == '')
      return array( 'status' => WW_ERROR,
        'message' => 'Missing filename for remove-source-file action.' );
    //if ( ! ProjectDescription::is_allowable_filename($filename) )
    //  return array( 'status' => WW_ERROR,
    //    'message' => "Prohibited filename ‘".htmlspecialchars($filename)."’." );
    $lookup = WWAction::look_up_project($request->getText('ww-action-project'));
    if ($lookup['status'] != WW_SUCCESS)
      return $lookup;
    $project =& $lookup['project'];
    # remove from the project-description and working dir only,
    # or from its page as well?
    # if one of the confirm buttons is pressed, we know
    # what to do.
    $remove_from_wiki = $request->getCheck('ww-action-projwdpage');
    $remove_from_proj =
      $remove_from_wiki || $request->getCheck('ww-action-projwd');
    $remove_from_wd =
      $remove_from_proj || $request->getCheck('ww-action-wdonly');
    wwLog( ( $remove_from_wiki ? 'remove from wiki' : 
      $remove_from_proj ? 'remove from project' : 
      $remove_from_wd ? 'remove from wd' : 'no remove yet' ) );
    # if none of these, it's because we haven't confirmed with the user.
    if (!$remove_from_wd)
    { $choices = array('wdonly'=>$wwContext->wwInterface->message('ww-wdonly'));
      //$projpage = $project->project_page();
      # don't check OKToEditPage here - better to be consistent
      # about when to offer choices, even though the edits may not work.
      if ( isset($project->project_files[$filename]) 
          /*and wwfOKToEditPage($projpage)*/ )
        $choices['projwd'] = $wwContext->wwInterface->message('ww-projwd');
      $sfc = $project->find_source_file_content($filename,null);
      if (isset($sfc['page']) /*and wwfOKToEditPage($sfc['page'])*/)
        $choices['projwdpage'] = $wwContext->wwInterface->message('ww-projwdpage');
      switch( count($choices) )
      { case 1:
          #$details = null;
          #return array( 'status'=>WW_QUESTION,
          #  'message'=>
          #  "(projpage=$projpage .. ok? ".(wwfOKToEditPage($projpage,$details)?'y':'n')
          #  ." .. defined? ". (isset($project->project_files[$filename])?'y':'n')
          #  .")", 'choices'=>$choices );
          # if there's only one choice, just do it.
          $remove_from_wd = true;
          break;
        case 2:
          # if there are options, ask the user.
          return array( 'status' => WW_QUESTION,
            'message' => $wwContext->wwInterface->message('ww-confirm-remove-projwd',$noun,
              htmlspecialchars($filename)),
            'choices' => $choices );
        case 3:
        default:
          return array( 'status' => WW_QUESTION,
            'message' => $wwContext->wwInterface->message('ww-confirm-remove-projwdpage-sf',$noun,
              htmlspecialchars($filename)),
            'choices' => $choices );
      }
    }
    # passed confirmation check, go ahead.
    return WWAction::passToApi( $request, 'remove-file' );
  }
}
  
# remove-project-file is the same as remove-source-file but I don't want to
# present a mismatched name in the interface.  It's only used in the
# wdonly case, which works for all project files, not just source files.
class WWAction_remove_project_file extends WWAction_remove_source_file
{ function execute(&$request)
  { global $wwContext;
    return $this->_execute( $request, $wwContext->wwInterface->message( 'project-file' ) );
  }
}

# remove-appears action not currently in use, so probably has some bugs
# if so, use remove-archived as a model, since it is in use
class WWAction_remove_appears extends WWAction
{ function execute(&$request)
  { global $wwContext;
    $filename = $request->getText('action-filename');
    if ($filename == '')
      return array( 'status' => WW_ERROR,
        'message' => 'Missing filename for remove-project-file action.' );
    if ( ! ProjectDescription::is_allowable_filename($filename) )
      return array( 'status' => WW_ERROR,
        'message' => "Prohibited filename ‘".htmlspecialchars($filename)."’." );
    $lookup = $this->look_up_project($request->getText('project'));
    if ($lookup['status'] != WW_SUCCESS)
      return $lookup;
    $project =& $lookup['project'];
    $appears = $request->getText('appears');
    # remove from the project-description and working dir only,
    # or from its page as well?
    # if one of the confirm buttons is pressed, we know
    # what to do.
    $remove_from_wiki = $request->getCheck('projwdpage');
    $remove_from_proj =
      $remove_from_wiki || $request->getCheck('projwd');
    $remove_from_wd =
      $remove_from_proj || $request->getCheck('wdonly');
    # if none of these, it's because we haven't confirmed with the user.
    if (!$remove_from_wd)
    { $choices = array('wdonly'=>$wwContext->wwInterface->message('ww-wdonly'));
      //$projpage = $project->project_page();
      # don't check OKToEditPage here - better to be consistent
      # about when to offer choices, even though the edits may not work.
      if ( $appears != '' and isset($project->project_files[$filename]) 
          /*and wwfOKToEditPage($projpage)*/ )
        $choices['projwd'] = $wwContext->wwInterface->message('ww-projwd');
      # projwdpage choice not currently implemented
      $noun = $wwContext->wwInterface->message( 'project-file' );
      switch( count($choices) )
      { case 1:
          #$details = null;
          #return array( 'status'=>WW_QUESTION,
          #  'message'=>
          #  "(projpage=$projpage .. ok? ".(wwfOKToEditPage($projpage,$details)?'y':'n')
          #  ." .. defined? ". (isset($project->project_files[$filename])?'y':'n')
          #  .")", 'choices'=>$choices );
          # if there's only one choice, just do it.
          $remove_from_wd = true;
          break;
        case 2:
        default:
          # if there are options, ask the user.
          return array( 'status' => WW_QUESTION,
            'message' => $wwContext->wwInterface->message('ww-confirm-remove-projwd',$noun,
              htmlspecialchars($filename)),
            'choices' => $choices );
        #case 3:
        #  return array( 'status' => WW_QUESTION,
        #    'message' => $wwContext->wwInterface->message('ww-confirm-remove-projwdpage-specific-pf',
        #                       $noun,$filename,$page),
        #    'choices' => $choices );
      }
    }
    # check it again, it might have changed
    if ( $remove_from_wd )
    { $message = null;
      #if ($remove_from_wiki)
      #{ try
      #  { $project->remove_source_file_from_wiki($filename);
      #    $message = $wwContext->wwInterface->message('ww-removed-projwdpage',$filename);
      #  } catch ( WWException $ex )
      #  {} # if that fails, do the remove from project anyway
      #}
      $op_result = ProjectEngineConnection::call_project_engine(
        'remove',$project,array('target'=>$filename));
      if ($remove_from_proj)
      { //$wwContext->wwInterface->debug_message('appears: '
        //  .print_r($project->project_files[$filename]['appears'], true));
        if ($appears != '')
        { unset($project->project_files[$filename]['appears'][$appears]);
          if ($message === null)
            $message = $wwContext->wwInterface->message('ww-removed-projwd-appears',
              htmlspecialchars($filename),htmlspecialchars($appears));
        }
        #unset($project->project_files[$filename]);
        $wwContext->wwStorage->save_project_description($project);
      }
      if ($message === null)
        $message = $wwContext->wwInterface->message('ww-removed','project file',
          htmlspecialchars($filename));
      $wwContext->wwInterface->invalidate_pages( $project );
      return array( 'status' => WW_SUCCESS, 'message' => $message );
    }
  }
}

class WWAction_remove_archived extends WWAction
{ function execute(&$request)
  { global $wwContext;
    $filename = $request->getText('action-filename');
    if ($filename == '')
      return array( 'status' => WW_ERROR,
        'message' => 'Error: missing filename for remove-archived action.' );
    $lookup = $this->look_up_project($request->getText('project'));
    if ($lookup['status'] != WW_SUCCESS)
      return $lookup;
    $project =& $lookup['project'];
    $archived = $request->getText('archived');
    # remove from the project-description and working dir only,
    # or from its page as well?
    # if one of the confirm buttons is pressed, we know
    # what to do.
    $remove_from_wiki = $request->getCheck('projwdpage');
    $remove_from_proj =
      $remove_from_wiki || $request->getCheck('projwd');
    $remove_from_wd =
      $remove_from_proj || $request->getCheck('wdonly');
    # if none of these, it's because we haven't confirmed with the user.
    if (!$remove_from_wd)
    { $choices = array('wdonly'=>$wwContext->wwInterface->message('ww-wdonly'));
      //$projpage = $project->project_page();
      # don't check OKToEditPage here - better to be consistent
      # about when to offer choices, even though the edits may not work.
      if ( $archived != '' and isset($project->project_files[$filename]) 
          and isset($project->project_files[$filename]['archived'][$archived])
          /*and wwfOKToEditPage($projpage)*/ )
      { $choices['projwd'] = $wwContext->wwInterface->message('ww-projwd');
        $title = Title::newFromText($archived);
        if (is_object($title) and !($title->getNamespace() == NS_IMAGE)
                  and !($title->getNamespace() == NS_MEDIA))
          $choices['projwdpage'] = $wwContext->wwInterface->message('ww-projwdpage');
      }
      # projwdpage choice not currently implemented
      $noun = $wwContext->wwInterface->message( 'archived-project-file' );
      switch( count($choices) )
      { case 1:
          #$details = null;
          #return array( 'status'=>WW_QUESTION,
          #  'message'=>
          #  "(projpage=$projpage .. ok? ".(wwfOKToEditPage($projpage,$details)?'y':'n')
          #  ." .. defined? ". (isset($project->project_files[$filename])?'y':'n')
          #  .")", 'choices'=>$choices );
          # if there's only one choice, just do it.
          $remove_from_wd = true;
          break;
        case 2:
        default:
          # if there are options, ask the user.
          return array( 'status' => WW_QUESTION,
            'message' => $wwContext->wwInterface->message('ww-confirm-remove-projwd',
                          $noun, htmlspecialchars($filename)),
            'choices' => $choices );
        case 3:
          return array( 'status' => WW_QUESTION,
            'message' => $wwContext->wwInterface->message('ww-confirm-remove-projwdpage-archived',
                          $noun, htmlspecialchars($filename),
                          htmlspecialchars($archived)),
            'choices' => $choices );
      }
    }
    # check it again, it might have changed
    if ( $remove_from_wd )
    { $message = null;
      if ($remove_from_wiki)
      { try
        { $wwContext->wwStorage->remove_element_from_wiki($project,$filename,/*src*/false,$archived);
          $message 
            = $wwContext->wwInterface->message('ww-removed-projwdpage-archived',
                htmlspecialchars($filename), htmlspecialchars($archived));
        } catch ( WWException $ex )
        {} # if that fails, do the remove from project anyway
      }
      $op_result = ProjectEngineConnection::call_project_engine(
        'remove',$project,array('target'=>$filename));
      if ($remove_from_proj)
      { //$wwContext->wwInterface->debug_message('appears: '
        //  .print_r($project->project_files[$filename]['appears'], true));
        if ($archived != '')
        { unset($project->project_files[$filename]['archived'][$archived]);
          $project->project_files[$filename]['appears'][$archived] = true;
          if ($message === null)
            $message = $wwContext->wwInterface->message('ww-removed-projwd-archived',
              htmlspecialchars($filename),
              htmlspecialchars($archived));
        }
        #unset($project->project_files[$filename]);
        $wwContext->wwStorage->save_project_description($project);
      }
      if ($message === null)
        $message = $wwContext->wwInterface->message('ww-removed','project file', 
          htmlspecialchars($filename));
      $wwContext->wwInterface->invalidate_pages( $project );
      return array( 'status' => WW_SUCCESS, 'message' => $message );
    }
  }
}

class WWAction_delete_project extends WWAction
{ function execute(&$request)
  { $lookup = $this->look_up_project($request->getText('project'));
    global $wwContext;
    if ($lookup['status'] != WW_SUCCESS)
      return $lookup;
    $project =& $lookup['project'];
    $projonly = $request->getText('projonly') != '';
    $projandfiles = $request->getText('projandfiles') != '';
    $confirmed = ($projonly or $projandfiles);
    wwLog("projonly: $projonly projandfiles: $projandfiles confirmed: $confirmed");
    if ( ! $confirmed )
    { $choices = array( 'projonly' => $wwContext->wwInterface->message('ww-delete-project-only') );
      global $wwGenerateMakefile;
      if ($wwGenerateMakefile)
        $has_project_files = (count($project->project_files) > 1);
      else
        $has_project_files = (count($project->project_files) > 0);
      if ($has_project_files)
      { $choices['projandfiles'] = $wwContext->wwInterface->message('ww-delete-project-and-files');
        return array( 'status' => WW_QUESTION,
          'message' => $wwContext->wwInterface->message('ww-confirm-delete-project-and-files',
                        htmlspecialchars($project->project_name())),
          'choices' => $choices );
      }
      else
        return array( 'status' => WW_QUESTION,
          'message' => $wwContext->wwInterface->message('ww-confirm-delete-project',
                        htmlspecialchars($project->project_name())),
          'choices' => $choices );
    }
    # if we get here, we're confirmed.
    $op_result = ProjectEngineConnection::call_project_engine(
      'clear-directory', $project, array('delete-self'=>true) );
    if (! $op_result['succeeded'])
    { $wwContext->wwInterface->throw_error("Failed to delete project directory.");
    }
    if ($projandfiles)
    { foreach ($project->project_files as $filename => $pf)
      { if (!is_array($pf)) continue;
        if (isset($pf['source']))
        { if (isset($pf['automatic']))
            continue;
          if (isset($pf['page']))
            $pagename = $pf['page'];
          else
          { $find = $wwContext->wwStorage->find_file_content($filename, $project, null,
              /*src*/true);
            if ($find['type'] == 'not found')
            { $wwContext->wwInterface->record_error("Source file "
                .htmlspecialchars($filename) . " not found. Not deleted.");
              continue;
            }
            $pagename = $find['page'];
          }
          if ( ! $wwContext->wwStorage->remove_element_from_wiki($project,$filename,true,
                    $pagename) )
            $wwContext->wwInterface->throw_error("Could not delete file "
              . htmlspecialchars($filename) . " from page "
              . htmlspecialchars($pagename) . ".");
        }
        if (is_array($pf['archived']))
          foreach ($pf['archived'] as $pagename => $t)
          { if ( ! $wwContext->wwStorage->remove_element_from_wiki($project, $filename,
                      false, $pagename) )
              $wwContext->wwInterface->throw_error("Could not remove archived project file "
                . htmlspecialchars($filename) . " from page "
                . htmlspecialchars($pagename) . ".");
          }
        if (is_array($pf['appears']))
          foreach ($pf['appears'] as $pagename => $t)
          { if ( ! $wwContext->wwStorage->remove_element_from_wiki($project, $filename,
                      false, $pagename) )
              $wwContext->wwInterface->throw_error("Could not remove project-file tag "
                . htmlspecialchars($filename) . " from page "
                . htmlspecialchars($pagename) . ".");
          }
      }
    }
    if ( ! $wwContext->wwStorage->delete_project($project) )
      $wwContext->wwInterface->throw_error("Could not delete description for project "
                      . htmlspecialchars($project->project_name()) );
    if ($projandfiles)
      return array( 'status' => WW_SUCCESS,
        'message' => $wwContext->wwInterface->message('ww-delete-project-and-files-success',
                          htmlspecialchars($project->project_name())) );
    else
      return array( 'status' => WW_SUCCESS,
        'message' => $wwContext->wwInterface->message('ww-delete-project-success',
                          htmlspecialchars($project->project_name())) );
  }
}

class WWAction_update_prerequisite extends WWAction
{ function execute(&$request)
  { if (!is_null($request->getVal('remove')))
    { return WWAction::passToApi( $request, 'remove-prerequisite' );
    }
    else
    { return WWAction::passToApi( $request, 'set-prerequisite' );
    }
  }
}

# if this succeeds it redirects to GetProjectFile, which means that
# some WW messages might go unreported.
class WWAction_make extends WWAction
{ function execute(&$request)
  { global $wwContext;
    $lookup = $this->look_up_project($request->getText('ww-action-project'));
    if ($lookup['status'] != WW_SUCCESS)
      return $lookup;
    $project =& $lookup['project'];
    $filename = $request->getText('ww-action-filename');
    if ($filename == '')
      return array( 'status' => WW_ERROR,
        'message' => 'Error: missing filename for make action.' );
    $make_success = ProjectEngineConnection::make_target($project,$filename);
    $wwContext->wwInterface->invalidate_pages( $project );
    $wwContext->wwStorage->update_archived_project_files();
    if ($make_success)
    { if ($wwContext->wwInterface->does_file_exist($project,$filename))
      { global $wgOut;
        $wgOut->redirect(
          SpecialPage::getTitleFor( 'GetProjectFile' )->getFullUrl(
            $wwContext->wwInterface->get_project_file_query($project,$filename,'',
              false,null,false) ) );
        return array( 'status' => WW_ABORTOUTPUT );
      }
      else
        return array( 'status'=>WW_SUCCESS,
          'message' => "‘make " . htmlspecialchars($filename) 
            . "’ succeeded but did not create file ‘"
            . htmlspecialchars($filename) . "’." );
    }
    else
    { $logfilename = $filename.'.make.log';
      $wwContext->wwInterface->record_error( 
        $wwContext->wwInterface->altlinks_text($project,$filename,array(),true) .
          $wwContext->wwInterface->message( 'ww-make-failed', htmlspecialchars($filename),
            $wwContext->wwInterface->make_get_project_file_url($project,$logfilename,false) ) );
      return array( 'status' => WW_ERROR, 
        'message' => "Failed to make '" . htmlspecialchars($filename) . "'." );
    }
  }
}

# if this succeeds it redirects to GetProjectFile, which means that
# some WW messages might go unreported.
class WWAction_remove_and_remake extends WWAction
{ function execute(&$request)
  { global $wwContext;
    $lookup = $this->look_up_project($request->getText('ww-action-project'));
    if ($lookup['status'] != WW_SUCCESS)
      return $lookup;
    $project =& $lookup['project'];
    $filename = $request->getText('ww-action-filename');
    if ($filename == '')
      return array( 'status' => WW_ERROR,
        'message' => 'Error: missing filename for remove-and-remake action.' );
    $op_result = ProjectEngineConnection::call_project_engine(
      'remove',$project,array('target'=>$filename));
    $success = $op_result['succeeded'];
    #$exists = array_key_exists('target-file-contents',$op_result);
    #$wwContext->wwStorage->update_archived_project_files();
    if ($success)
    { global $wgOut;
      $wgOut->redirect(
        SpecialPage::getTitleFor( 'GetProjectFile' )->getFullUrl(
          $wwContext->wwInterface->get_project_file_query($project,$filename,'',
            true,null,false) ) );
      return array( 'status' => WW_ABORTOUTPUT );
    }
    return array( 'status' => WW_ERROR, 
      'message' => "Failed to remove '" . htmlspecialchars($filename) . "'." );
  }
}

class WWAction_prune_working_directories extends WWAction
{ function execute(&$request)
  { $op_result = ProjectEngineConnection::call_project_engine_lowlevel(
      array('operation'=> array('name'=>'prune-directories')));
    $success = $op_result['succeeded'];
    if ($success)
      return array( 'status'=>WW_SUCCESS,
        'message' => "Pruned working directory cache." );
    return array( 'status' => WW_ERROR, 
      'message' => "Error pruning working directory cache." );
  }
}

?>
