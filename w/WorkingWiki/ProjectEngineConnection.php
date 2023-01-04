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
 * ProjectEngineConnection - object in WorkingWiki that connects to 
 *  ProjectEngine, makes requests and handles the responses.
*/
#global $wwUseHTTPForPE, $wwPECodeDirectory;
#if ( ! $wwUseHTTPForPE )
#  $wgAutoloadClasses['PEAPI']
#    = "$wwPECodeDirectory/PEAPI.php";

class ProjectEngineConnection
{ protected static $_instance;

  # this is the only good way to get a connection object.
  public static function factory()
  { if (!isset(self::$_instance))
      self::$_instance = new ProjectEngineConnection;
    return self::$_instance;
  }

  # we've gotten some file contents from ProjectEngine and
  # might want them again before we're done.
  private static $file_cache;

  # general interface to ProjectEngine, used in various operations
  public static function call_project_engine($operation, $project, 
    $op_args=null, $request=array(), $sync_all_source_files=false)
  { $pname = ($project instanceOf ProjectDescription ?
      $project->project_name() : $project);
    $request['operation']['name'] = $operation;
    if ($op_args)
      $request['operation'] = array_merge($request['operation'],$op_args);
    if ($project instanceOf ProjectDescription) {
	    #wwLog( 'calling fill_pe_request on focal project' );
	    $project->fill_pe_request( $request, true, $sync_all_source_files );
    }
    else if ($project == 'resources')
      $request['operation']['project'] = 'pe-resources';
    return self::call_project_engine_lowlevel($request, $sync_all_source_files);
  }

  # more general alternative to call_project_engine, used when the request
  # doesn't include a project name or is atypical in some other way.
  public static function call_project_engine_lowlevel($request,
    $sync_all_source_files=false)
  { global $wwContext, $wwUseHTTPForPE, $wwPEFilesAreAccessible;
    if ($wwPEFilesAreAccessible)
      $request['pe-files-are-accessible'] = true;
    $request['password'] = 'password';
    $wwContext->wwInterface->amend_PE_request($request);
    if (isset($request['output-directly']) and $request['output-directly'])
    { #wwLog("Will pass PE output through verbatim");
    }

    #wwLog( "request to PE: ". json_encode($request) );
    if ($wwUseHTTPForPE)
      $result = self::call_project_engine_http($request);
    else
      $result = self::call_project_engine_in_process($request);

    #wwLog( "PE result: ". json_encode($result) );
    # FIXME: $project isn't set!
    if (is_array($result) and
        array_key_exists('target-file-contents', $result))
    { $pname = ((!isset($project) or $project === null) ? '' : $project->project_name());
      foreach($result['target-file-contents'] as $filename=>$entry)
        self::$file_cache[$pname][$filename] = $entry;
    }
    if (is_array($result) and
        array_key_exists('archived-file-contents', $result))
      $wwContext->wwStorage->sequester_archived_project_files(
        $result['archived-file-contents']);
    if (is_array($result) and isset($result['messages']))
    { global $msg_types;
      if (!isset($msg_types))
        $msg_types = array('message'=>WW_SUCCESS, 'error'=>WW_ERROR,
                           'warning'=>WW_WARNING, 'debug'=>WW_DEBUG);
      foreach ($result['messages'] as $pm) {
        $wwContext->wwInterface->record_message("ProjectEngine: ".$pm[1],
                                    $msg_types[$pm[0]]);
	#wwLog( "ProjectEngine: " . $pm[1] );
      }
    }
    return $result;
  }

  public static function call_project_engine_in_process($request)
  { global $wwContext;
    wwProfileIn( __METHOD__ );
    #wwLog("Call PE within process\n");
    try
    { static $api;
      if (!isset($api))
        $api = new PEAPI;
      $api->process_request($request, $result);
    } catch ( PEAbortOutputException $ex )
    { $wwContext->wwInterface->throw_error("ProjectEngine request failed.");
    }
    #wwLog("result: ". json_encode($result) . "\n");
    wwProfileOut( __METHOD__ );
    return $result;
  }

  public static function call_project_engine_http($request)
  { 
    #foreach ($result as $k=>&$v)
    #  if (!is_string($v))
    #    $v = serialize($v);
    #curl_setopt($crl, CURLOPT_POSTFIELDS, $request);
    $postfields = array();
    $postfields['input-format'] = 'base64_serialized_php_array';
    $postfields['output-format'] = 'base64_serialized_php_array';
    $postfields['request'] = base64_encode(serialize($request));
    return self::call_project_engine_http_internal( true, $postfields );
  }

  public static function call_project_engine_http_internal( $post, $fields, $headers = array() )
  { global $wwContext;
    # TODO: GET version 
    $crl = curl_init();
    $wwPEUrl = 'http://localhost/ProjectEngine/index.php';
    curl_setopt($crl, CURLOPT_USERAGENT, 'WorkingWiki');
    curl_setopt($crl, CURLOPT_URL, $wwPEUrl);
    curl_setopt($crl, CURLOPT_POST, true);
    #global $wgServer, $wgScriptPath;
    curl_setopt($crl, CURLOPT_POSTFIELDS, $fields);
    #wwLog("request: " . serialize($request) . "\n");
    #curl_setopt($crl, CURLOPT_RETURNTRANSFER, true);
    #curl_setopt($crl, CURLOPT_HEADER, true);
    #curl_setopt($crl, CURLOPT_FAILONERROR, true);
    $headers[] = 'Expect:';
    curl_setopt($crl, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($crl, CURLOPT_HEADERFUNCTION, 
      array('ProjectEngineConnection','header_callback'));
    curl_setopt($crl, CURLOPT_WRITEFUNCTION,
      array('ProjectEngineConnection','write_callback'));

    try
    { ProjectEngineConnection::$http_result = '';
      ProjectEngineConnection::$output_directly = false;
      if (!curl_exec($crl))
        $wwContext->wwInterface->throw_error("Call to ProjectEngine failed: "
          . htmlspecialchars(curl_error($crl)) . ".");
      if (ProjectEngineConnection::$output_directly)
      { global $wgOut;
        $wgOut->disable();
        exit;
      }
      if (preg_match('/result=(.*)\n?$/', 
            ProjectEngineConnection::$http_result, $m))
      { $curl_result = base64_decode($m[1]);
        #wwLog("result: $curl_result\n");
        $result = unserialize($curl_result);
        #wwLog("result: ". wwfSanitizeInput(serialize($result)) . "\n");
        return $result;
      }
      else
      { #wwLog("curl output: " . $curl_output . "\n");
        $wwContext->wwInterface->throw_error("Call to ProjectEngine returned bad data.");
      }
    } catch (WWException $ex)
    {}
    #wwLog("result: false\n");
    return false;
  }

  static $output_directly, $pass_if_going_direct, $http_result;
  public static function header_callback($c,$h)
  { #wwLog("<$h>\n");
    $d=trim($h);
    if (preg_match("/^Content-Disposition/i",$d) or
        preg_match("/^HTTP\S* (304|50\d)\b/",$d))
    { ProjectEngineConnection::$output_directly = true;
      #wwLog("Passing PE response directly due to this line: $d\n");
      #header($d);
    }
    else if (preg_match("/\b(304|50\d)\b/",$d))
    { #wwLog("Ignore header line: $d\n");
    }
    # the response from PE can come through Apache or another server which
    # adds headers.  We only pass some of them on to the client.
    if (preg_match("/^Content-Type|^Cache-Control|^Content-Disposition/i",$d)
        or preg_match("/^Last-Modified|\b304\b|\b50\d\b/i",$d))
    { ProjectEngineConnection::$pass_if_going_direct[] = $d;
    }
    if (ProjectEngineConnection::$output_directly and 
        is_array(ProjectEngineConnection::$pass_if_going_direct))
    { foreach (ProjectEngineConnection::$pass_if_going_direct as $p)
      { #wwLog("$p\n");
        header($p);
      }
      ProjectEngineConnection::$pass_if_going_direct = null;
    }
    return strlen($h);
  }
  public static function write_callback($c,$d)
  { //wwLog("write_callback: " . substr($d,0,14) . "... ("
    //  . strlen($d) . " bytes)\n");
    if (ProjectEngineConnection::$output_directly)
      echo($d);
    else
      ProjectEngineConnection::$http_result .= $d;
    return strlen($d);
  }

  # whether the operation includes a make operation
  public static function operation_includes_make($operation)
  { return ($operation == 'make' or $operation == 'remove-and-remake'
            or $operation == 'export');
  }

  # whether to update archived files after the operation 
  public static function archive_files_with_operation($operation)
  { return (self::operation_includes_make($operation)
            or $operation == 'retrieve-archived-files'
            or $operation == 'merge-session');
  }

  # whether to send info about dependency projects as well as the 
  # project being operated on
  public static function should_add_associated_projects_to_request($operation)
  { return (self::operation_includes_make($operation)
              or $operation == 'merge-session' or $operation == 'sync'
              or $operation == 'export');
  }

  public static function session_applies_to_operation( $operation ) {
	$negatives = array(
		'claim-log-key' => 1,
		'append-to-log' => 1,
		'sse-retrieve-log' => 1,
		'prune-directories' => 1,
		'query-time-limit' => 1,
		'migrate-all-directories' => 1,
	);
	return ! isset( $negatives[$operation] );
  }

  # whether we pass the PE output through to the calling browser unmolested
  public static function pass_operation_result_through($operation)
  { return ($operation == 'export');
  }

  # Invoke make to construct a target file.
  # return: true
  # throws an exception if there's a problem.
  public static function make_target($project,$target,$req_vals=array(),$cgi_args=array())
  { global $wwContext;
    #$ex = new Exception;
    #wwLog( $ex->getTraceAsString() );
    global $wwContext, $wwMakeTemporarilyDisabled, $wwMakeCompletelyDisabled;
    if (isset($wwMakeTemporarilyDisabled)
        or isset($wwMakeCompletelyDisabled))
    { #wwLog("Abort make_target because make is disabled\n");
      return true;
    }
    foreach ($cgi_args as $key => $val)
    { # do extra make requests to validate the CGI parameters.
      # PE takes care of escapeshellarg() for the target name and
      # 'env' keys and values.
      if ( ! ProjectEngineConnection::make_target( $project, "$key.$val.validate", $req_vals, array() ) )
      { $wwContext->wwInterface->record_error( "Rejecting request to make '"
    		. htmlspecialchars($target) . "' because of "
		. 'failure to validate value \'' . htmlspecialchars($val)
		. '\' for variable \'' . htmlspecialchars($key) . '\'.' );
        return false;
      }
      if ( !isset( $req_vals['operation'] ) )
	$req_vals['operation'] = array();
      if ( !isset( $req_vals['operation']['env'] ) )
	$req_vals['operation']['env'] = array();
      $req_vals['operation']['env'][$key] = $val;
    }
    if (!wwRunHooks('WW-MakeTarget', array($project, $target, &$ret)))
    { $wwContext->wwInterface->debug_message("aborted");
      return $ret;
    }
    $pe_result 
      = self::call_project_engine('make', $project, 
          array('target'=>$target), $req_vals, true);
    if ( $project instanceOf ProjectDescription )
      $pname = $project->project_name();
    else if ( is_string($project) )
      $pname = $project;
    else
      $pname = '';
    # todo: new format
    if (isset($pe_result['target-file-contents']))
      foreach ( $pe_result['target-file-contents'] as $filename=>$content )
        $wwContext->wwInterface->cache_file_contents[$pname][$filename]
          = $content;
    $make_success = (isset($pe_result['succeeded']) and $pe_result['succeeded']);
    # note, no error message here - caller must report if make fails
    return $make_success;
  }

  /**
   * Given the path and/or contents of a file (and timestamp),
   * construct the array entry that's needed in a PE request to sync the 
   * file.
   * Either filepath or filetext can be null.
   */
  public static function make_sync_file_entry( $filepath, $filetext, $timestamp ) {
	  if ($filepath !== null) {
		  global $wwPECanReadFilesFromWiki;
		  if ( $wwPECanReadFilesFromWiki ) {
			return array( 'p', $filepath );
		  } else if ( $filetext === null ) {
			$filetext = file_get_contents( $filepath );
			$timestamp = filemtime( $filepath );
		  }
	  }
	  if ($filetext !== null) {
		  return array( 'c', $filetext, $timestamp );
	  }
	  return false;
  }
}
