<?php
require_once dirname(__FILE__) . '/Models/ITaskTrackerModelDefault.php';
/**
 * Issue Tracking System
 * 
 * Configuration class for the IssueTracker extension.
 *
 */

class ITaskTrackerConfig
{
	/**
	 * Actions.
	 * @var array
	 */
	protected $_permissions = null;
	
	/**
	 * Issue type array.
	 * @var array
	 */
	protected $_issueType = null;
	
	/**
	 * Issue status array.
	 * @var array
	 */
	protected $_issueStatus = null;

	protected $_issueStatusAdd = null;

	protected $_issueShortAction=null;
	protected $_issueCoor_by=null;
	
	/**
	 * ...
	 * 
	 * @return void
	 */
	protected $_issuePriority = null;
	protected $_issueApprov_by = null;
        protected $_issueApprov_by_AL = null;
	protected $_issueOwned_by = null;
        protected $_issuenomanager=null;
	public $_issueLeaving = array("Martin", "Yi");
        
        public function setIbugPermissions()
	{		
             global $wgUser;
              $ArrG = $wgUser->getGroups();
              if ( in_array('sysop',$ArrG) || in_array('itaskowner',$ArrG) || in_array('itaskcoordinator',$ArrG) || in_array('itaskapprover',$ArrG) ) {              
		$perms['list']     = array('group' => '*');
		$perms['view']     = array('group' => '*');
		$perms['add']      = array('group' => '*');
		$perms['edit']     = array('group' => '*');
              }else{
                $perms['list']     = array('group' => 'X');
		$perms['view']     = array('group' => 'X');
		$perms['add']      = array('group' => 'X');
		$perms['edit']     = array('group' => 'X');  
              }  		
		$perms['archive']  = array('group' => '*');
		$perms['unarchive']  = array('group' => '*');
		$perms['changestatus']  = array('group' => '*');
		$perms['delete']   = array('group' => '*');
		$perms['assign']   = array('group' => 'sysop');
		$perms['assignee'] = array('group' => 'sysop');		
		$this->_permissions = $perms;
	}
	
	/**
	 * Returns the permission array.
	 *
	 * @param string $action
	 * @return array self::$_permissions
	 */
	public function getIbugPermissions($action = null)
	{
		if ($this->_permissions === null) {
			$this->setIbugPermissions();
		}
		
		if ($action !== null && array_key_exists($action, $this->_permissions)) {
			return $this->_permissions[$action];
		} else {
			return $this->_permissions;
		}
	}
	
	/**
	 * Sets the issue type array.
	 * 
	 * An issue's type expresses what kind of issue it is and also allows custom 
	 * name and color to be added to an issue.
	 *
	 * @param array $type
	 * @return void
	 */
        public function setIbugIssuePriority($priority = array()){
            $priority[1] = array ('name' => 'URGENT', 'default' => false);
            $priority[2] = array ('name' => 'High', 'default' => false);
            $priority[3] = array ('name' => 'Medium', 'default' => false);
            $priority[4] = array ('name' => 'Low', 'default' => true);

            $this->_issuePriority = $priority;
        }
        
        public function getIbugIssuePriority(){            
                if($this->_issuePriority === null){
                        $this->setIbugIssuePriority();
                }
                return $this->_issuePriority;
        }
        
        public function setIbugIssueParentList(){	
        global $wgUser;        
        $objModel = new ITaskTrackerModelDefault();               
        $arrParent = $objModel->getDataParentList($wgUser->mId);                
        unset($objModel);
            $this->_parentList = $arrParent;
        }
        
        public function getIbugIssueParentList(){            
                if($this->_parentList === null){
                        $this->setIbugIssueParentList();
                }                
                return $this->_parentList;
        }
        
        public function setIbugIssueApprov_by($approv_by = Array()){		               
        $objModel = new ITaskTrackerModelDefault();
        $approv_by['None'] = array ('name' => 'None', 'default' => true);           
        $arrUsers = $objModel->getDatabaseUsers("itaskapprover");        
        foreach($arrUsers as $user){        
           $approv_by[$user["user_name"]] = array ('name' => $user["user_name"], 'default' => false );
        }
        unset($objModel);
            $this->_issueApprov_by = $approv_by;
        }
        
        public function getIbugIssueApprov_by(){
            if($this->_issueApprov_by === null){
                $this->setIbugIssueApprov_by();
            }
            return $this->_issueApprov_by;
        }        

        public function setIbugIssueOwned_by($owned_by = Array()){  
        global $wgUser;                           
            $objModel = new ITaskTrackerModelDefault(); 
            $UsersOnly = $objModel->getDatabaseUsers("ibuguseronly");
            $i=0;$arrUserOnly=null;
            foreach($UsersOnly as $user){                
                $arrUserOnly[$i] = $user["user_name"];
                $i++;
            }  
            $owned_by = null; 
            //var_dump($arrUserOnly);
                if (in_array($wgUser->getName(), $arrUserOnly)) {                                                                          
                    $owned_by[$wgUser->getName()] = array ('name' => $wgUser->getName(), 'default' => false );                    
                }else{
                    $SectionOnly = $objModel->getDatabaseUsers("ibugsectiononly");
                    $i=0;$arrSectionOnly=null;
                    foreach($SectionOnly as $user){                
                        $arrSectionOnly[$i] = $user["user_name"];
                        $i++;
                    }
                        if (in_array($wgUser->getName(), $arrSectionOnly)) {
                        //echo "ViewSectionUserOnly"; 
                        $arrSecs = $objModel->getDatabaseUsersSection($wgUser->getName());                       
                            foreach($arrSecs as $user){                
                            $owned_by[$user["user_name"]] = array ('name' => $user["user_name"], 'default' => false );
                            } 
                        }   else{
                                $arrUsers = $objModel->getDatabaseUsers("itaskowner");
                                $owned_by['any'] = array ('name' => 'Any', 'default' => true); 
                                $owned_by['None'] = array ('name' => 'None', 'default' => true);                               
                                foreach($arrUsers as $user){                
                                    $owned_by[$user["user_name"]] = array ('name' => $user["user_name"], 'default' => false );
                                } 
                            }    
                }
            unset($objModel);
            $this->_issueOwned_by = $owned_by;                                
        }
        public function getIbugIssueOwned_by(){
            if($this->_issueOwned_by === null){
                $this->setIbugIssueOwned_by();
            }
            return $this->_issueOwned_by;
        }	

        public function setIbugCoordinator_by($coordinator = Array()){
            $coordinator['None'] = array ('name' => 'None', 'default' => true);          
            $objModel = new ITaskTrackerModelDefault();        
            $arrUsers = $objModel->getDatabaseUsers("itaskcoordinator");
            foreach($arrUsers as $user){                    
                $coordinator[$user["user_name"]] = array ('name' => $user["user_name"], 'default' => false );
            }
            unset($objModel); 
            $this->_issueCoor_by = $coordinator;                
        }

            public function getIbugCoordinator(){
                    if($this->_issueCoor_by === null){
                            $this->setIbugCoordinator_by();
                    }
                    return $this->_issueCoor_by;
        }	
        
        public function setIbugIssueType($type = array()) 
	{        
        $this->_issueType = $type;        
            #Running 1 Time Only When save OLD Type;
            /*$objModel = new ITaskTrackerModelDefault();                   
            foreach( $this->_issueType as $key => $type1){
               echo ($key);
               echo ($type1['name']);              
               $Addtype=$objModel->addDeprecatedType($key,$type1["name"],$type1["colour"]);                
            }
            unset($objModel);  */
	}
	
	/**
	 * Returns the issue type array.
	 *
	 * @return array self::$_issueType
	 */
	public function getIbugIssueType() 
	{
		if ($this->_issueType === null) {
			$this->setIbugIssueType();
		}
		return $this->_issueType;
	}
	
	/**
	 * Sets the issue status array.
	 *
	 * @param array $status
	 * @return void
	 */
	public function setIbugIssueStatus($status = array()) 
	{              
                $objModel = new ITaskTrackerModelDefault();        
                $arrStatus = $objModel->getIbugStatus(0);
                foreach($arrStatus as $stts){                  
                    $status[$stts["status"]] = array('name' => $stts["name"], 'colour' => $stts["colour"], 'Gstatus' => $stts["Gstatus"]);                    
                }
                unset($objModel);
		$this->_issueStatus = $status;
	}
	/**
	 * Returns the issue status array.
	 *
	 *@return array self::$_issueStatus
	 */
	public function getIbugIssueStatus() 
	{
		if ($this->_issueStatus === null) {
			$this->setIbugIssueStatus();
		}
		return $this->_issueStatus;
	}

	/**
         * Sets the issue status add array.
         *
         * @param array $statusadd
         * @return void
         */
    public function setIbugIssueStatusAdd($statusadd = array())
    {        
         $objModel = new ITaskTrackerModelDefault();        
                $arrStatus = $objModel->getIbugStatus(1);
                foreach($arrStatus as $stts){                  
                    $statusadd[$stts["status"]] = array('name' => $stts["name"], 'colour' => $stts["colour"]);                    
                }
                unset($objModel);
        $this->_issueStatusAdd = $statusadd;
    }

	/**
     * Returns the issue status add array.
     *
     *@return array self::$_issueStatusAdd
     */
    public function getIbugIssueStatusAdd()
    {
        if ($this->_issueStatusAdd === null) {
            $this->setIbugIssueStatusAdd();
        }
        return $this->_issueStatusAdd;
    }

    public function setIbugIssueShortAction( $shortaction = array()){
            $shortaction['a'] = array('name' => 'Bug Assigned');
            $shortaction['b'] = array('name' => 'Bug Working');
            $shortaction['ca'] = array('name' => 'Bug Feedback');
            $shortaction['cb'] = array('name' => 'Bug Pending Approval');
            $shortaction['d'] = array('name' => 'Bug Approved');
            $shortaction['e'] = array('name' => 'Bug Cancelled');
            $shortaction['ar0'] = array('name' => 'Unarchive');
            $shortaction['ar1'] = array('name' => 'Archive');
            ## Kronos ##                
            $shortaction['cd'] = array('name' => 'Waiting for bank e-approval');
            $this->_issueShortAction = $shortaction;
    }
    public function getIbugIssueShortAction(){
             if ($this->_issueShortAction === null) {
                    $this->setIbugIssueShortAction();
        }
    return $this->_issueShortAction;
    }        
            
}
