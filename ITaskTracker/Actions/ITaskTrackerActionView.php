<?php
/** @see ITaskTrackerAction **/
require_once dirname(__FILE__) . '/ITaskTrackerAction.php';

/**
 * ITaskTrackerActionView class.
 *
 */
class ITaskTrackerActionView extends ITaskTrackerAction
{	
	/**
	 * Executes the action.
	 *
	 * @return void 
	 */
	public function viewIbugAction()
	{
		// Mediawiki globals
		global $wgOut;
		
		$this->_setIbugDefaultVars();
		$this->_setIbugHookPreferences();
		
		if ($this->issueId) {
			$rs = $this->getIbugModel('default')->getIbugIssueById($this->issueId);
			$this->issue = $rs->fetchObject();
			if (isset($this->issue->summary)) {
			}
			$output = $this->Ibugrender();
		} else {
			$output = wfMessage('invalid_id'); 
		}
		
		$this->setIbugOutput($output);
	}
	
	/**
	 * Sets the default vars.
	 *
	 * @return void 
	 */
	protected function _setIbugDefaultVars()
	{
		// Mediawiki globals
		global $wgScript, $wgRequest, $wgUser, $wgScriptPath,$wgDBname,$wgDBuser,$wgDBpassword, $wgCurrentDir;
                
		$this->ScriptPath               = $wgScriptPath;
                $this->dbcon                    = $this->simple_encrypt($wgDBname."^^".$wgDBuser."^^".$wgDBpassword); 
		$this->action      		= $this->getIbugAction();
		$this->issueId     		= $wgRequest->getText('bt_issueid');
		$this->pageKey     		= $this->getIbugNamespace('dbKeyIbug');
		$this->pageTitle   		= $this->getIbugNamespace('textIbug');
		$this->typeArray   		= $this->_config->getIbugIssueType();
		$this->priorityArray            = $this->_config->getIbugIssuePriority();
		$this->approv_byArray           = $this->_config->getIbugIssueApprov_by();	
		$this->owned_byArray            = $this->_config->getIbugIssueOwned_by();	
		$this->coorArray 		= $this->_config->getIbugCoordinator();
		$this->statusArray 		= $this->_config->getIbugIssueStatus();
		$this->formAction  		= $wgScript;
		$this->url         		= $wgScript . '?title=' . $this->pageKey . '&bt_action=';
		//$this->editUrl   		= $this->url . 'edit&bt_issueid=' . $this->issueId;
		//$this->deleteUrl   	= $this->url . 'archive&bt_issueid=' . $this->issueId;
		//$this->undeleteUrl   	= $this->url . 'unarchive&bt_issueid=' . $this->issueId;
		//$this->listUrl     	= $this->url . 'list';
		//$this->removeUrl   	= $this->url . 'delete&bt_issueid=' . $this->issueId;
		$this->isIbugLoggedIn  	= $this->isIbugLoggedIn();
		$this->getUsrName 		= $wgUser->getName();		
		$this->filelocation 	= $this->getfilelocation();
                $this->downloadfileurl 	= $this->getdownloadfileurl();
		// Request vars
                $this->filterBy       	= $wgRequest->getVal('bt_filter_by');
                $this->filterStatus   	= $wgRequest->getVal('bt_filter_status');
                $this->filterApprov_by  = $wgRequest->getVal('bt_filter_approv_by');
                $this->filterType   	= $wgRequest->getVal('bt_filter_type');
                $this->filterOwned_by   = $wgRequest->getVal('bt_filter_owned_by');
                $this->searchString   	= $wgRequest->getVal('bt_search_string');
                $this->filterMnt        = $wgRequest->getVal('bt_filter_mnth');
                $this->filterYr        	= $wgRequest->getVal('bt_filter_yr');
		$this->filterPriority 	= $wgRequest->getVal('bt_filter_priority');
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
            $strmUrl=$strmUrl."&bt_filter_issueid=".$this->issueId;
            $strmUrl=$strmUrl."&bt_filter=Apply";
        }
	$this->editUrl     = $this->url . 'edit'.$strmUrl.'&bt_issueid=' . $this->issueId;
        $this->deleteUrl   = $this->url . 'archive'.$strmUrl.'&bt_issueid=' . $this->issueId;
        $this->undeleteUrl = $this->url . 'unarchive'.$strmUrl.'&bt_issueid=' . $this->issueId;
        $this->listUrl     = $this->url . 'list'.$strmUrl;
        $this->removeUrl   = $this->url . 'delete'.$strmUrl.'&bt_issueid=' . $this->issueId;			
	}	
	/**
	 * Processes the tag arguments.
	 *
	 * @return void 
	 */
	protected function _setIbugHookPreferences()
	{
		if (array_key_exists('project', $this->_args) && $this->_args['project'] !== '') {
			$this->pageKey = $this->_args['project'];
		}
	}
}
?>
