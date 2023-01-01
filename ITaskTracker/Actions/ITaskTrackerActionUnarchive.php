<?php
/** @see ITaskTrackerAction **/
require_once dirname(__FILE__) . '/ITaskTrackerAction.php';

/**
 * IssueTrackerActionArchive class.
 *
 */
class ITaskTrackerActionUnarchive extends ITaskTrackerAction
{
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
	 * Executes the action.
	 *
	 * @return void 
	 */
	public function unarchiveIbugAction()
	{
		global $wgUser, $wgScript, $wgRequest;
		
		$listUrl = $wgScript . '?title=' . $this->getIbugNamespace('dbKeyIbug') . '&bt_action=list';
		
		$userId = $wgUser->getID();
		$userName = $wgUser->getName();
		
		$issueId = $wgRequest->getText('bt_issueid');
		$this->getIbugModel('default')->unarchiveIbugIssue($issueId);
		
		 // Request vars
                $this->filterBy       = $wgRequest->getVal('bt_filter_by');
                $this->filterStatus   = $wgRequest->getVal('bt_filter_status');
                $this->filterApprov_by   = $wgRequest->getVal('bt_filter_approv_by');
                $this->filterType   = $wgRequest->getVal('bt_filter_type');
                $this->filterOwned_by   = $wgRequest->getVal('bt_filter_owned_by');
                $this->searchString   = $wgRequest->getVal('bt_search_string');
                $this->filterMnt        =  $wgRequest->getVal('bt_filter_mnth');
                $this->filterYr        =  $wgRequest->getVal('bt_filter_yr');;
		$this->filterPriority = $wgRequest->getVal('bt_filter_priority');
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

		
		header('Location: ' . $listUrl.$strmUrl);
	}
}
?>
