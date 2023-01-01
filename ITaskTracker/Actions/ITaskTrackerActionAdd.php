<?php
/** @see ITaskTrackerAction **/
require_once dirname(__FILE__) . '/ITaskTrackerAction.php';

class ITaskTrackerActionAdd extends ITaskTrackerAction
{
	/**
	 * Required form fields.
	 * @var array
	 */
	protected $_requiredFields = array('bt_title');
	
	/**
	 * Initialize class.
	 * 
	 * @return bool
	 */
	public function init()
	{
		return $this->isIbugLoggedIn();
	}
	
	/**
	 * Executes the add action.
	 *
	 * @return void 
	 */
	public function addIbugAction()
	{
		global $wgScript, $wgUser, $wgMaxUrgentTask, $wgMaxHighTask, $wgMaxMediumTask;		
		$this->_setIbugDefaultVars();
		$this->_setIbugHookPreferences();
		
		if (isset($_POST['bt_submit'])) {                    
			$errorMessages = $this->_getIbugErrors($this->_requiredFields);	
                                                                                               
			if (count($errorMessages) == 0) {
                                $total_itask  = $this->getIbugModel('default')->countTotalPriorityiTask($_POST["bt_owned_by"],$_POST["bt_priority"]);
                                $oldest_itask = $this->getIbugModel('default')->getOldestiTask($_POST["bt_owned_by"], $_POST["bt_priority"]);                                
                                switch ($_POST["bt_priority"]) {
                                    case 1:
                                        $allowed = ($total_itask >= $wgMaxUrgentTask)? 0:1 ;                                
                                        $_prior = "Urgent";
                                        break;
                                    case 2:                                        
                                        //$allowed = ($total_itask >= $wgMaxHighTask)? 0:1 ;                                
                                        $allowed = 1;
                                        $_prior = "High";
                                        break;
                                    case 3:
                                        $allowed = ($total_itask >= $wgMaxMediumTask)? 0:1 ;                                                     
                                        $_prior = "Medium";
                                        break;       
                                    case 4:
                                        $allowed = 1;
                                        break;
                                }               
									$userId = $wgUser->getID();
									$userName = $wgUser->getName();		
                                    if ($allowed==0 && $_POST["bt_priority"]==1) {
                                        //$this->errors = "Assign Urgent Priority iTask not allowed !<br/>Owner this iTask has more than 5 Urgent iTask (Total iTask Urgent : $total_itask iTasks)";   										
										$_POST["bt_priority"] = "2";																			
										$issue_id=$this->getIbugModel('default')->addIbugIssue($_POST, $userId, $userName);		
										$this->url = $wgScript . '?title=' . $this->pageKey . '&bt_action=';
                                        $this->viewUrl = $this->url . 'view'.$_POST[extstrmUrl].'&bt_issueid='.$issue_id;         
										$msg = "&msg_update=Assign Urgent Priority iTask is not allowed !<br/>Owner this iTask has more than 5 Urgent iTask (Total iTask Urgent : $total_itask iTasks)<br/>New iTask with <strong>High</strong> Priority has been created." ;
									   header('Location: ' . $this->viewUrl. $msg);
                                    }else{                                        											
                                        #saving the data to the database
                                        $issue_id=$this->getIbugModel('default')->addIbugIssue($_POST, $userId, $userName);
                                        #$rowID=$rsID->fetchObject();
                                        #$issue_id=$rowID->issue_id;
                                        $this->url = $wgScript . '?title=' . $this->pageKey . '&bt_action=';
                                        $this->viewUrl = $this->url . 'view'.$_POST[extstrmUrl].'&bt_issueid='.$issue_id;                                        
                                        if ($_prior=="High" && $total_itask >= $wgMaxHighTask) {
                                        $msg = "&msg_update=iTask id : ".$issue_id." has been added with ".$_prior." priority<br/>Owner this iTask has more than ".$wgMaxHighTask." ".$_prior." iTask (Total iTask ".$_prior." : $total_itask iTasks)<br/>Oldest iTask (id : ".$oldest_itask.") has been changed to Medium priority !" ;                                                                                   
                                        $this->getIbugModel('default')->updateIbugPriority($oldest_itask, 3);
                                        }
                                       header('Location: ' . $this->viewUrl. $msg);
                                    }
			} else {                                                                                            
                                $this->errors = implode('<br />', $errorMessages);                                    				
			}
		}
		
		$this->IbugusersList = $this->_getIbugUsers();
		$this->setIbugOutput($this->Ibugrender());
	}	
	/**
	 * Sets the default vars.
	 *
	 * @return void 
	 */
	protected function _setIbugDefaultVars()
	{
		global $wgScript, 
                       $wgRequest, 
                       $wgUser, 
                       $wgCurrentDir, 
                       $wgScriptPath, 
                       $wgCurrentHome,$wgDBname,$wgDBuser,$wgDBpassword,$wgSMTP;       
         
                $this->dbcon                    = $this->simple_encrypt($wgDBname."^^".$wgDBuser."^^".$wgDBpassword);                
                $this->path                     = $this->simple_encrypt($wgScriptPath);
                $this->smtp                     = $this->simple_encrypt($wgSMTP['host']."^^".$wgSMTP['IDHost']."^^".$wgSMTP['port']."^^".$wgSMTP['username']."^^".$wgSMTP['password']."^^".$wgSMTP['auth']);
                $this->uploadDir = urlencode($this->simple_encrypt($wgCurrentDir."/ITaskTrackerUploads/"));
                $this->currenthome = $wgCurrentHome;
		$this->action = 'add';
		$this->pageKey = $this->getIbugNamespace('dbKeyIbug');
		$this->project = $this->getIbugNamespace('dbKeyIbug');
		//$this->listUrl = $wgScript . '?title=' . $this->pageKey . '&bt_action=list';
		$this->typeArray = $this->_config->getIbugIssueType();
		$this->statusArray = $this->_config->getIbugIssueStatus();
		$this->statusArrayAdd = $this->_config->getIbugIssueStatusAdd();
		$this->getUsrName = $wgUser->getName();
		$this->issuePriority = $this->_config->getIbugIssuePriority();
		$this->issueApprov_by = $this->_config->getIbugIssueApprov_by();                
		$this->issueOwned_by = $this->_config->getIbugIssueOwned_by();
		$this->isIbugApprover = $this->isIbugApprover($strChecker);		
		$this->isIbugUrgent = $this->isIbugUrgent($strUrgent);
		$this->issueCoor_by = $this->_config->getIbugCoordinator();
		$this->numMaxfileupload=$this->getnumMaxfileupload();
		$this->formAction = $wgScript;		
		// Request vars
                $this->ScriptPath     = $wgScriptPath;//tambahanku
                $this->filterBy       = $wgRequest->getVal('bt_filter_by');
                $this->filterStatus   = $wgRequest->getVal('bt_filter_status');
		//$this->filerStatusAdd = $wgRequest->getVal('bt_filter_status_add');
                $this->filterApprov_by   = $wgRequest->getVal('bt_filter_approv_by');
                $this->filterType   = $wgRequest->getVal('bt_filter_type');
                $this->filterOwned_by   = $wgRequest->getVal('bt_filter_owned_by');
                $this->searchString   = $wgRequest->getVal('bt_search_string');
                $this->filterMnt        =  $wgRequest->getVal('bt_filter_mnth');
                $this->filterYr        =  $wgRequest->getVal('bt_filter_yr');
		$this->filterPriority = $wgRequest->getVal('bt_filter_priority');
                $this->local            = $wgCurrentDir;
		$strmUrl="";
        if ($this->searchString!="")
		{
            $strmUrl="&project=Special:ITaskTracker";
            $strmUrl=$strmUrl."&bt_search_string=".$this->searchString;
            $strmUrl=$strmUrl."&bt_search=Submit";
        }
        if ($this->filterBy!=""){
            $strmUrl="&project=Special:ITaskTracker";
            $strmUrl=$strmUrl."&bt_filter_by=".$this->filterBy;
            $strmUrl=$strmUrl."&bt_filter_type=".$this->filterType;
            $strmUrl=$strmUrl."&bt_filter_status=".$this->filterStatus;
            $strmUrl=$strmUrl."&bt_filter_mnth=".$this->filterMnt;
            $strmUrl=$strmUrl."&bt_filter_yr=".$this->filterYr;
            $strmUrl=$strmUrl."&bt_filter_owned_by=".$this->filterOwned_by;
            $strmUrl=$strmUrl."&bt_filter_approv_by=".$this->filterApprov_by;
            $strmUrl=$strmUrl."&bt_filter_priority=".$this->filterPriority;
            $strmUrl=$strmUrl."&bt_filter=Apply";

        }

        $this->extstrmUrl=$strmUrl;

		$this->listUrl = $wgScript . '?title=' . $this->pageKey . '&bt_action=list'.$strmUrl;
	}

	/**
	 * Processes the tag attributes.
	 *
	 * @return void 
	 */
	protected function _setIbugHookPreferences()
	{
		if (array_key_exists('project', $this->_args) && $this->_args['project'] !== '') {
			$this->project = $this->_args['project'];
		}
	}
	
	/**
	 * Data validation.
	 *
	 * @param array $requiredFields
	 * @return array Returns an array with error messages. 
	 */
	protected function _getIbugErrors($requiredFields)
	{
		$errors = array();
               
		foreach ($requiredFields as $field) {
			if (! isset($_POST[$field]) || '' == $_POST[$field]) {
				$errors[] = wfMessage('error_' . $field);
			}
		}		 
		return $errors;
	}
	
	/**
	 * Returns the list of users.
	 *
	 * @return string
	 */
	protected function _getIbugUsers()
	{
		$perm = $this->_config->getIbugPermissions('assignee');
		$group = ($perm['group'] != '*') ? strtolower($perm['group']) : null;
		
		/** @see SpecialListusers **/
		require_once 'includes/specials/SpecialListusers.php';
		
		$users = new UsersPager(null,$group);
		if (! $users->mQueryDone) {
			$users->doQuery();
		}
		$users->mResult->rewind();
		
		$list = '';
		while ($row = $users->mResult->fetchObject()) {
			$list .= '<option value="' . $row->user_name . '"';
			$list .= (isset($_POST['bt_assignee']) && $_POST['bt_assignee'] == $row->user_name) ? ' selected="true"' : '';
			$list .= '>' . $row->user_name . '</option>';
		}		
		return $list;
	}
}