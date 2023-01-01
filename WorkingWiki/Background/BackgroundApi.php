<?php

class WWApiListBackgroundJobs extends WWApiBase
{
  public function execute()
  { $params = $this->extractRequestParams();
    #wwLog("WWApiListBackgroundJobs\n");
    #wwLog("Api params is: " . serialize($params) . "\n");

    $this->claimSSEkey( $params['logkey'] );

    $projectnames = $params['projects'];
    $bypass_cache = $params['bypass-cache'];
    $html = WWBackground::jobsMessage( $projectnames, $bypass_cache );
    $res = array( 'list' => $html );
    $this->getResult()->addValue( null, $this->getModuleName(), $res );
    $this->closeSSE();
  }

  public function getAllowedParams() {
    return array(
      'projects' => array( 
          ApiBase::PARAM_TYPE => 'string',
	  ApiBase::PARAM_ISMULTI => true,
          #ApiBase::PARAM_REQUIRED => false
      ),
      'bypass-cache' => array(
	  ApiBase::PARAM_TYPE => 'integer',
      )
    ) + parent::getAllowedParams();
  }

  public function getParamDescription() {
    return array(
      'projects' => 'List of project names, separated by | character',
    ) + parent::getParamDescription();
  }

  public function getDescription() {
    return 'Retrieve description of background jobs relevant to a given set of projects';
  }

  public function getVersion() {
    return __CLASS__ . ': (version unknown.  By Lee Worden.)';
  }
}

/* 'ww-kill-background-job' action */
class WWApiKillBackgroundJob extends WWApiBase
{
  public function execute()
  { $params = $this->extractRequestParams();

    $this->claimSSEkey( $params['logkey'] );

    $jobid = $params['jobid'];
    if (!$jobid)
      $this->dieUsage(
        'ww-kill-background-job action requires ‘jobid’ argument.',
     	'unknownerror' );
    $pe_result = ProjectEngineConnection::call_project_engine_lowlevel(
       array('operation'=>
               array('name'=>'kill-background-job','jobid'=>$jobid)));
    if (!$pe_result['succeeded'])
      $this->dieUsage(
        'Failed to kill background job ' . htmlspecialchars($jobid) . ".",
	'unknownerror');
    $this->getResult()->addValue( null, 'success', true );
    $this->closeSSE();
  }

  public function isWriteMode() {
    return true;
  }

  public function getAllowedParams() {
    return array(
      'jobid' => array( 
          ApiBase::PARAM_TYPE => 'string',
          #ApiBase::PARAM_REQUIRED => false
      ),
    ) + parent::getAllowedParams();
  }

  public function getParamDescription() {
    return array(
      'jobid' => 'Background job ID, as provided by ww-list-background-jobs API',
    ) + parent::getParamDescription();
  }

  public function getDescription() {
    return 'Terminate a background job.';
  }

  public function getVersion() {
    return __CLASS__ . ': (version unknown.  By Lee Worden.)';
  }
}

/* 'ww-destroy-background-job' action */
class WWApiDestroyBackgroundJob extends WWApiBase
{
  public function execute()
  { $params = $this->extractRequestParams();
    wwLog( 'in WWApiDestroyBackgroundJob with params ' . json_encode( $params ) );

    $this->claimSSEkey( $params['logkey'] );
  
    $jobid = $params['jobid'];
    if (!$jobid)
      $this->dieUsage(
        'ww-destroy-background-job action requires ‘jobid’ argument.',
     	'unknownerror' );
    #$confirm = $request->getCheck('confirm');
    #if (!$confirm) {
    #  global $wwContext;
    #  return array( 'status' => WW_QUESTION,
    #    'message' => $wwContext->wwInterface->message('wwb-confirm-destroy', $jobid),
    #    'choices' => array('confirm' => $wwContext->wwInterface->message( 'wwb-destroy' ) ) );
    #}
    $pe_result = ProjectEngineConnection::call_project_engine_lowlevel(
       array('operation'=>
               array('name'=>'destroy-background-job','jobid'=>$jobid)));
    if (!$pe_result['succeeded'])
      $this->dieUsage(
        'Failed to destroy background job ' . htmlspecialchars($jobid) . ".",
	'unknownerror');
    $this->getResult()->addValue( null, 'success', true );
    $this->closeSSE();
  }

  public function isWriteMode() {
    return true;
  }

  public function getAllowedParams() {
    return array(
      'jobid' => array( 
          ApiBase::PARAM_TYPE => 'string',
          #ApiBase::PARAM_REQUIRED => false
      ),
    ) + parent::getAllowedParams();
  }

  public function getParamDescription() {
    return array(
      'jobid' => 'Background job ID, as provided by ww-list-background-jobs API',
    ) + parent::getParamDescription();
  }

  public function getDescription() {
    return 'Kill a background job if needed, and remove its working directory.';
  }

  public function getVersion() {
    return __CLASS__ . ': (version unknown.  By Lee Worden.)';
  }
}

/* 'ww-merge-background-job' action.
 */
class WWApiMergeBackgroundJob extends WWApiBase { 
	public function execute() { 
		global $wwContext;
		$params = $this->extractRequestParams();

		$this->claimSSEkey( $params['logkey'] );

		#wwLog( "api params: " . json_encode( $params ) . "\n" );
		$jobid = $params['jobid'];
		wwLog( "merge job $jobid" );
		if (!$jobid) {
			// take care of this at caller
			$this->dieUsage( 
				'merge-from-background action requires ‘action-jobid’ argument.',
				'unknownerror'
			);
		}
		$pe_result = ProjectEngineConnection::call_project_engine_lowlevel( array(
			'operation' => array( 'name' => 'merge-session' ),
			'background-job' => $jobid,
		) );
		wwLog( "merged job $jobid" );
		$res = array();
		if ( ! $params['no-messages'] ) {
			$messages = $wwContext->wwInterface->report_errors();
			if ( $messages !== '' ) {
				$res[ 'messages' ] = $messages;
			}
		}
		if ( ! $pe_result['succeeded'] ) {
			#wwLog( "error in merge api with messages "
			#	.json_encode( $res ) . "\n" );
			$this->dieUsage(
				'Failed to merge background job ' 
					. htmlspecialchars($jobid) . ".",
				'unknownerror', 
				0, $res
			);
		}
		if ( isset( $pe_result['projects'] ) ) {
			foreach ($pe_result['projects'] as $pname) {
				$project = $wwContext->wwStorage->find_project_by_name($pname);
				$wwContext->wwInterface->invalidate_pages( $project );
			}
		}
		$this->getResult()->addValue( null, $this->getModuleName(), $res );
		$this->closeSSE();
	}
	public function getAllowedParams() {
		return array(
			'jobid' => array(
				ApiBase::PARAM_TYPE => 'string',
			),
			'no-messages' => array(
				ApiBase::PARAM_TYPE => 'boolean',
			),
		) + parent::getAllowedParams();
	}
	public function getParamDescription() {
		return array(
			'jobid' => 'ID of background job',
			'no-messages' => 'Flag to leave WW warnings and informational messages out of the result data',
		) + parent::getParamDescription();
	}

  public function isWriteMode() {
    return true;
  }
	public function getDescription() {
		return 'Merge a background job\'s files into projects\' persistent storage';
	}

	public function getVersion() {
		return __CLASS__ . ': (version unknown.  By Lee Worden.)';
	}
}

/* 'ww-create-background-job' action.
 */
class WWApiCreateBackgroundJob extends WWApiBase { 
	public function execute() { 
		global $wwContext;
		$params = $this->extractRequestParams();

		$this->claimSSEkey( $params['logkey'] );

		#wwLog( "api params: " . json_encode( $params ) . "\n" );
		$projectname = $params['project'];
		$project = $wwContext->wwStorage->find_project_by_name($projectname);
		$filename = $params['filename'];
		global $wgUser;
		$pe_request = array(
			'background-job'=>'', # the null job id means create a new one.
			'okay-to-create-background-job'=>true,
			'user-name'=>$wgUser->getName()
		);
		global $wwAllowBackgroundJobEmails;
		if ( $wwAllowBackgroundJobEmails and
		     $wgUser->getOption( 'ww-background-jobs-emails' ) ) {
			$pe_request['operation']['email-notifications'] 
				= array( $wgUser->getEmail() );
		}
		$make_success = ProjectEngineConnection::make_target(
			$project,$filename,$pe_request);
		if ( ! make_success ) {
			$this->dieUsage( "Failed to create background job for target '$filename'.", 
				'backgroundmakefailed', 0,
				array( 'messages' => $wwContext->wwInterface->report_errors() )
			);
		}
		$wwContext->wwInterface->invalidate_pages( $project, null, true );
		$res = array();
		$messages = $wwContext->wwInterface->report_errors();
		if ( $messages !== '' ) {
			$res[ 'messages' ] = $messages;
			$this->getResult()->addValue( null, $this->getModuleName(), $res );
		}
		$this->closeSSE();
	}

	public function isWriteMode() {
		return true;
	}

	public function getAllowedParams() {
		return array(
			'project' => array(
				ApiBase::PARAM_TYPE => 'string',
			),
			'filename' => array(
				ApiBase::PARAM_TYPE => 'string',
			),
		) + parent::getAllowedParams();
	}
	public function getParamDescription() {
		return array(
			'project' => 'Project name',
			'filename' => 'Target filename',
		) + parent::getParamDescription();
	}
	public function getDescription() {
		return 'Create a background make job';
	}

	public function getVersion() {
		return __CLASS__ . ': (version unknown.  By Lee Worden.)';
	}
}

?>
