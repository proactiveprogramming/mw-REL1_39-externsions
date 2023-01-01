<?php
/** @see ITaskTrackerAction **/
require_once dirname(__FILE__) . '/ITaskTrackerAction.php';

class ITaskTrackerActionList extends ITaskTrackerAction
{	
	/**
	 * Executes the action.
	 *
	 * @return void 
	 */
	public function listIbugAction()
	{
		// Mediawiki globals
		global $wgUser, $wgRequest;		
		$this->_setIbugDefaultVars();
		$this->_setIbugHookPreferences();		                
                
		if ( isset($_POST['bt_submitShortAction']) ) {
                //var_dump($_POST);die;    
                $box=$_POST['chkIssue'];
			$choice=$_POST['bt_checked_ShortAction'];	
			
			while (list ($key,$val) = @each ($box)) { 
				$nametxt="txtstatusid".$val;
				#echo $val."-".$_POST[$nametxt];
				#echo "<br />";
				
				if ( $val != "" && $_POST['bt_checked_ShortAction']!=="None" ){
					if ($choice!="ar0" && $choice!="ar1"){
					
						$newstatus=substr($_POST[$nametxt],0,4).$choice;
						$typeedit="status";					
						$result = $this->getIbugModel('default')->changeActionIbugIssue($val,$newstatus,$typeedit);
					}
					else{
						$newstatus=substr($choice,2);
						$typeedit="archive";
						$result = $this->getIbugModel('default')->changeActionIbugIssue($val,$newstatus,$typeedit);				
					}	
				}
							 
			} 
						
			header("location:".$_POST['bt_actionreturn']);
                }		
		// Initial Conditions
		$condsIbug['project_name'] = addslashes($this->project);
		$condsIbug['deleted'] = 0;                    
                    foreach ($this->issueStatus as $key => $status){
                        if ($status['name']== "Bug Working") {$codeSt=$key;}
                    }                    
                    if ($this->filterStatus == null){
                    $condsIbug['status'] = $codeSt;
                    }
                    
		if ($this->filterOwned_by == null && $this->filterBy !== "idonly"){
			$condsIbug['owned_by'] = $this->getUsrName;
			$this->filterOwned_by = $this->getUsrName;
		}                
                    if ($this->filterMnt == null) {
                        $this->filterMnt = "any";
                    }
                    if ($this->filterYr == null) {
                        $this->filterYr = date("Y");
                    }
		// Filters
        if ($this->filterBy !== null || $this->filterStatus !== null || $this->filterType !== null || $this->filterCoor !== null) {						
            if ($this->filterStatus !== null) {                
                if (array_key_exists($this->filterStatus, $this->issueStatus) || $this->filterStatus =='s_as' || $this->filterStatus =='s_an' || $this->filterStatus =='s_w1'|| $this->filterStatus =='s_w2') {
                        $condsIbug['status'] = $this->filterStatus; 
                } elseif ($this->filterStatus == 'archived') {
                        $condsIbug['deleted'] = 1; 
                }                   
            }			
	    if ($this->filterOwned_by !== null && $this->filterOwned_by !== '' && $this->filterOwned_by !== 'any') {
                $condsIbug['owned_by'] = $this->filterOwned_by;
            }           
            if ($this->filterApprov_by !== null && $this->filterApprov_by !== '' && $this->filterApprov_by !== 'any') {
                $condsIbug['approv_by'] = $this->filterApprov_by;
            }
	    if ($this->filterCoor !== null && $this->filterCoor !== '' && $this->filterCoor !== 'any') {
            $condsIbug['coor'] = $this->filterCoor;
            }
            if ($this->filterMnt !== ""){
                $condsIbug['mnth'] = $this->filterMnt;
            }
            if ($this->filterYr !== ""){
                $condsIbug['yr'] = $this->filterYr;
            }
            if ($this->filterType !== null) {
                if ($this->filterType !== "any"){
                $condsIbug['type'] = $this->filterType;
                }				
            }
            if ($this->filterPriority !== null) {
                if (array_key_exists($this->filterPriority, $this->issuePriority)) {
                    $condsIbug['priority'] = $this->filterPriority;
                }
            }
            if ($this->filterIssueID !== null && $this->filterIssueID !== '') {
            $condsIbug['issue_id'] = $this->filterIssueID;
            }
	}		
		$offset = $wgRequest->getInt('offset', 0);
		if (($this->searchString !== null) && (trim($this->searchString) !=="")) {
			$this->pget =  $this->getIbugModel('default')->getThemes();
			$this->issues = $this->getIbugModel('default')->getIbugIssuesBySrting($this->searchString, $this->project,$offset,$condsIbug['deleted']);
                        //var_dump($this->searchString);                       
		} 
		else {
			$this->pget =  $this->getIbugModel('default')->getThemes();
                            if ($this->filterBy == "idonly"){
                            //var_dump($condsIbug);
                            //echo($condsIbug['issue_id']);
                            $this->issues = $this->getIbugModel('default')->getIbugViewOneOnly($condsIbug['issue_id']);                            
                            }else{
                            $this->issues = $this->getIbugModel('default')->getIbugIssues($condsIbug, $offset);                                
                            }                              
		}
                    if ($this->filterBy == "idonly"){
                    $this->arribugnumbering = $this->getIbugModel('default')->getIbugIssueForNumberingOnlyOne($condsIbug['issue_id']);  
                        $sttDel=$this->getIbugModel('default')->getIbugIsDeleted($condsIbug['issue_id']);                    
                        //var_dump ($sttDel);
                        if ($sttDel['deleted']=="1") {$this->filterStatus = "archived";}                    
                    }else{                            
                    $this->arribugnumbering = $this->getIbugModel('default')->getIbugIssueForNumbering($condsIbug, $this->searchString);                
                    }				
                //var_dump($condsIbug);
                $this->setIbugOutput($this->Ibugrender());
	}
	
	/**
	 * Sets the default vars.
	 *
	 * @return void 
	 */
	protected function _setIbugDefaultVars()
	{
		// Mediawiki globals
		global $wgScript, $wgUser, $wgRequest,$wgScriptPath,$wgArticlePath,$wgCurrentDir,$wgDBname,$wgDBuser,$wgDBpassword,$wgSMTP; 
                $this->encripted_user           = $this->simple_encrypt(strtolower($wgUser->getName())); 
                $this->uploadDir                = $this->simple_encrypt($wgCurrentDir."/ITaskTrackerUploads/"); 
                $this->dbcon                    = $this->simple_encrypt($wgDBname."^^".$wgDBuser."^^".$wgDBpassword);                
                $this->path                     = $this->simple_encrypt($wgScriptPath);
                $this->smtp                     = $this->simple_encrypt($wgSMTP['host']."^^".$wgSMTP['IDHost']."^^".$wgSMTP['port']."^^".$wgSMTP['username']."^^".$wgSMTP['password']."^^".$wgSMTP['auth']);
		$this->action         		= $this->getIbugAction();
		$this->pageKey        		= $this->getIbugNamespace('dbKeyIbug');
		$this->project        		= $this->getIbugNamespace('dbKeyIbug');		
		$this->pageTitle      		= $this->getIbugNamespace('textIbug');
		$this->ScriptPath     		= $wgScriptPath;
                $ArticlePath     		= explode("/",$wgArticlePath);
                $this->ArticlePath     		= "/".$ArticlePath[1];
		$this->formAction     		= $wgScript;
		$this->url            		= $wgScript . '?title=' . $this->pageKey . '&bt_action=';
		$this->isIbugLoggedIn     	= $wgUser->isLoggedIn();
		$this->isAllowed      		= $wgUser->isAllowed('protect');		
		$this->hasIbugDeletePerms 	= $this->hasIbugPermission('delete');
		$this->hasIbugEditPerms   	= $this->hasIbugPermission('edit');
		$this->hasIbugViewPerms   	= $this->hasIbugPermission('view');
		$this->search         		= true;
		$this->filter         		= true;
		$this->auth           		= true;
		$this->issueType      		= $this->_config->getIbugIssueType();
		$this->getUsrName     		= $wgUser->getName();
		$this->issuePriority  		= $this->_config->getIbugIssuePriority();
		$this->issueApprov_by  		= $this->_config->getIbugIssueApprov_by();        
		$this->issueOwned_by  		= $this->_config->getIbugIssueOwned_by();
		$this->issueCoor_by 		= $this->_config->getIbugCoordinator();             		
		$this->issueStatus    		= $this->_config->getIbugIssueStatus();
		$this->issueShortAction    	= $this->_config->getIbugIssueShortAction();		
		$this->projectParent  		= $this->_config->getIbugIssueParentList();		
		$this->all_project_list		= $this->getIbugModel('default')->get_all_project_list();
		$this->arribugnumbering 	= null;		
		$this->filelocation 		= $this->getfilelocation();
        $this->downloadfileurl 		= $this->getdownloadfileurl();		
		// Request vars
		$this->filterBy       		= $wgRequest->getVal('bt_filter_by');
                $this->filterStatus   		= $wgRequest->getVal('bt_filter_status');
                $this->filterApprov_by   	= $wgRequest->getVal('bt_filter_approv_by');
                $this->filterType   		= $wgRequest->getVal('bt_filter_type');
                $this->filterOwned_by   	= $wgRequest->getVal('bt_filter_owned_by');
                $this->searchString   		= $wgRequest->getVal('bt_search_string');
		$this->filterCoor   		= $wgRequest->getVal('bt_filter_coor');
                $this->filterMnt        	= $wgRequest->getVal('bt_filter_mnth');
                $this->filterYr        		= $wgRequest->getVal('bt_filter_yr');
		$this->sortKey			= $wgRequest->getVal('sortKey');
		$this->sortType          	= $wgRequest->getVal('sortType');
		$this->filterPriority 		= $wgRequest->getVal('bt_filter_priority');
                $this->filterIssueID    	= $wgRequest->getVal('bt_filter_issueid');       
                $this->local                    = $wgCurrentDir;
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
	    $strmUrl=$strmUrl."&bt_filter_coor=".$this->filterCoor;
            $strmUrl=$strmUrl."&bt_filter_approv_by=".$this->filterApprov_by;
            $strmUrl=$strmUrl."&bt_filter_priority=".$this->filterPriority;
            $strmUrl=$strmUrl."&bt_filter_issueid=".$this->filterIssueID;
	    $strmUrl=$strmUrl."&bt_filter=Apply";

        }			
		$this->viewUrl        		= $this->url . 'view'.$strmUrl.'&bt_issueid=';
                $this->resetUrl       		= $this->url . 'list';
		$this->curUrl	      		= $this->url . 'list'.$strmUrl;	
		$this->addUrl         		= $this->url . 'add'.$strmUrl;
                $this->editUrl        		= $this->url . 'edit'.$strmUrl.'&bt_issueid=';
                $this->deleteUrl      		= $this->url . 'archive'.$strmUrl.'&bt_issueid=';
                $this->undeleteUrl    		= $this->url . 'unarchive'.$strmUrl.'&bt_issueid=';	
		$this->changestatusUrl 		= $this->url . 'changestatus'.$strmUrl.'&bt_issueid=';		
		$this->ArrchangestatusUrl 	= $this->url . 'arrchangestatus'.$strmUrl;	      
						
	
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
		if (array_key_exists('search', $this->_args) && $this->_args['search'] == 'false') {
			$this->search = false;
		}
		if (array_key_exists('filter', $this->_args) && $this->_args['filter'] == 'false') {
			$this->filter = false;
		}
		if (array_key_exists('authenticate', $this->_args) && $this->_args['authenticate'] == 'false') {
			$this->auth = false;
		}
	}                
}