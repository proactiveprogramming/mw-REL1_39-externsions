<?php
/** @see ITaskTrackerActionAdd **/
require_once dirname(__FILE__) . '/ITaskTrackerActionAdd.php';

class ITaskTrackerActionEdit extends ITaskTrackerActionAdd
{	
	/**
	 * Executes the edit action.
	 *
	 * @return void 
	 */
	public function editIbugAction()
	{
		global $wgUser, $wgMaxUrgentTask, $wgMaxHighTask, $wgMaxMediumTask;		
		$this->_setIbugDefaultVars();
		$this->_setIbugHookPreferences();
                $rs = $this->getIbugModel('default')->getIbugIssueById($this->issueId);
		$row = $rs->fetchObject();                
		if (isset($_POST['bt_submit']) && $this->issueId !== 0) {
			$errorMessages = $this->_getIbugErrors($this->_requiredFields);	
			if (count($errorMessages) == 0) {
                                $total_itask = $this->getIbugModel('default')->countTotalPriorityiTask($_POST["bt_owned_by"], $_POST["bt_priority"]);
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
                                if ($allowed==0 && $_POST["bt_priority"] !== $row->priority ) {                                                                               
                                        $this->errors = "Change to ".$_prior." Priority iTask not allowed !<br/>Owner this iTask has more than ".$wgMaxHighTask." ".$_prior." iTask (Total iTask ".$_prior." : $total_itask iTasks)";   
                                }else{                                         
                                    $userId = $wgUser->getID();
                                    $userName = $wgUser->getName();
									$result = $this->getIbugModel('default')->updateIbugIssue($this->issueId, $_POST);
									
									//Save comment from submit button//
									if ($_POST["commentMark"] !== "Comment..."){										
										$this->getIbugModel('default')->addIbugComment($this->issueId, $userName, $_POST["commentMark"]);										
									}

                                    if ($_POST["bt_priority"] !== $row->priority && $_prior=="High" && $total_itask >= $wgMaxHighTask) {
                                    $msg = "&msg_update=iTask id : ".$this->issueId." has been changed to ".$_prior." priority<br/>Owner this iTask has more than ".$wgMaxHighTask." ".$_prior." iTask (Total iTask ".$_prior." : $total_itask iTasks)<br/>Oldest iTask (id : ".$oldest_itask.") has been changed to Medium priority !" ;   
                                    $this->getIbugModel('default')->updateIbugPriority($oldest_itask, 3);
                                    }
                                    header('Location: ' . $this->listUrl. $msg);                                                                        
                                }
			} else {
				$this->errors = implode('<br />', $errorMessages);
			}
		} elseif ($this->issueId !== 0) {			
			$dttoday = date("d-M-Y");
			$_POST = array(
				'bt_issueid' 		=> $this->issueId,
				'bt_title'   		=> $row->title,
				'bt_summary' 		=> $row->summary,
				'bt_type'    		=> $row->type,
				'bt_status'  		=> $row->status,
				'bt_assignee'   	=> $row->assignee,			
				'bt_priority' 		=> $row->priority,                            
                                'bt_creation_date'	=> date("d-M-Y H:i:s", strtotime($row->date_created)),
				'bt_start_date'		=> $row->start_date,
                                'bt_target_date'	=> $row->target_date,
				'bt_due_date'		=> $dttoday,
				'bt_perc_complete'	=> $row->perc_complete,
				'bt_targ_accom' 	=> $row->targ_accom,
				'bt_approv_by'		=> $row->approv_by,
				'bt_owned_by'		=> $row->owned_by,
				'bt_coor_by'		=> $row->coor,
				'bt_file'		=> $row->Issuerndfile,
				'bt_user_name'          => $row->user_name,				
                                'bt_mod_date'		=> $row->due_date,
				'bt_modifier'		=> $wgUser->getName()	                                
	
			);
		} else {
			header('Location: ' . $this->listUrl);
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
		global $wgRequest, $wgScriptPath, $wgCurrentHome, $wgCurrentDir,$wgDBname,$wgDBuser,$wgDBpassword,$wgSMTP;		
		parent::_setIbugDefaultVars();
                
                $this->dbcon            = $this->simple_encrypt($wgDBname."^^".$wgDBuser."^^".$wgDBpassword);                
                $this->path             = $this->simple_encrypt($wgScriptPath);
                $this->smtp             = $this->simple_encrypt($wgSMTP['host']."^^".$wgSMTP['IDHost']."^^".$wgSMTP['port']."^^".$wgSMTP['username']."^^".$wgSMTP['password']."^^".$wgSMTP['auth']);
		$this->action           = 'edit';
		$this->issueId          = (int) $wgRequest->getText('bt_issueid');
		$this->priorityArray    = $this->_config->getIbugIssuePriority();
		$this->approv_byArray   = $this->_config->getIbugIssueApprov_by();
		$this->owned_byArray    = $this->_config->getIbugIssueOwned_by();
		$this->isIbugApprover   = $this->isIbugApprover($strChecker);
		$this->isIbugUrgent     = $this->isIbugUrgent($strUrgent);
		$this->filelocation     = $this->getfilelocation();
		$this->downloadfileurl  = $this->getdownloadfileurl();
                $this->currenthome      = $wgCurrentHome;
                $this->uploadDir        = urlencode($this->simple_encrypt($wgCurrentDir."/ITaskTrackerUploads/"));                
		// Request vars
		$this->ScriptPath       = $wgScriptPath;//tambahanku
		$this->filterBy         = $wgRequest->getVal('bt_filter_by');
                $this->filterStatus   	= $wgRequest->getVal('bt_filter_status');
                $this->filterApprov_by  = $wgRequest->getVal('bt_filter_approv_by');
                $this->filterType   	= $wgRequest->getVal('bt_filter_type');
                $this->filterOwned_by   = $wgRequest->getVal('bt_filter_owned_by');
                $this->searchString   	= $wgRequest->getVal('bt_search_string');
                $this->filterMnt        = $wgRequest->getVal('bt_filter_mnth');
                $this->filterYr        	= $wgRequest->getVal('bt_filter_yr');;
		$this->filterPriority 	= $wgRequest->getVal('bt_filter_priority');
                $this->local            = $wgCurrentDir;
        $strmUrl="";
        if ($this->searchString!="")
        {
            $strmUrl="&project=Special:IssueTrackerIbug";
            $strmUrl=$strmUrl."&bt_search_string=".$this->searchString;
            $strmUrl=$strmUrl."&bt_search=Submit";
        }
        if ($this->filterBy!=""){
            $strmUrl="&project=Special:IssueTrackerIbug";
            $strmUrl=$strmUrl."&bt_filter_by=".$this->filterBy;
            $strmUrl=$strmUrl."&bt_filter_type=".$this->filterType;
            $strmUrl=$strmUrl."&bt_filter_status=".$this->filterStatus;
            $strmUrl=$strmUrl."&bt_filter_mnth=".$this->filterMnt;
            $strmUrl=$strmUrl."&bt_filter_yr=".$this->filterYr;
            $strmUrl=$strmUrl."&bt_filter_owned_by=".$this->filterOwned_by;
            $strmUrl=$strmUrl."&bt_filter_approv_by=".$this->filterApprov_by;
            $strmUrl=$strmUrl."&bt_filter_priority=".$this->filterPriority;
            $strmUrl=$strmUrl."&bt_filter_issueid=".$this->issueId;
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
		parent::_setIbugHookPreferences();
	}
}