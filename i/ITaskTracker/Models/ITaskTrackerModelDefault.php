<?php
/** @see ITaskTrackerModel **/
require_once dirname(__FILE__) . '/ITaskTrackerModel.php';
require_once dirname(__FILE__) . '/../ITaskTrackerAjax.php';

/**
 * ITaskTrackerModelDefault class.
 *
 */
class ITaskTrackerModelDefault extends ITaskTrackerModel
{
	/**
	 * Database table name.
	 * @var string 
	 */  	
	protected $_tableIbug = 'ibug_tracker';
	protected $_viewIbug = 'vw_IbugTracker';	
        protected $_ibugComment = 'ibug_comment';
	protected $_vwIbugComment = 'vw_IbugComment';  //--> New VIEW        
	protected $_page = 'text';
        protected $_themes = 'team_org_matrix';
        protected $_ibugstatus = 'ibug_tracker_status';
	protected $_ibugLog = 'ibug_logging';
	protected $_ListUser ='user';        
	protected $_tableAccess ='user_access';
        protected $_ListUserGroup ='user_groups';
               
	/**
	 * Selects a limited number of issues ordered by id.
	 *
	 * @param mixed $conds Conditions
	 * @param int $offset
	 * @param int $limit
	 * @return ResultSet
	 */

	public function getPageData (){
        $dbr = wfGetDB( DB_SLAVE );    
        $query = "SELECT p.page_id AS 'Page ID',p.page_title AS 'Page Title',r.rev_text_id AS 'Revision ID',t.old_id AS 'Text_ID' FROM page p INNER JOIN revision r ON p.page_latest = r.rev_id INNER JOIN text t ON r.rev_text_id = t.old_id where page_title='Team_Organisation_Matrix' limit 0,1";                             
            $res = $dbr->query($query);
            while ( $row = $dbr->fetchObject($res) ) :     
                $old_id = $row->Text_ID;                 
            endwhile;     
            $old_id = ($old_id)? $old_id:0;               
            $qry = "select  CAST(old_text AS CHAR(50000) CHARACTER SET utf8) as PG from `".$this->_page."` where old_id=".$old_id."";            
            $res = $dbr->query($qry);
            while ($row = $dbr->fetchObject($res)) {      
                $record = $row->PG;                
                return $record;
            }            
        }

	public function cekData($tag_name){        
        $condstr = "tag_name='".$tag_name."' ";    
        $options = array ('ORDER BY'=>'theme ASC');
        return $this->_dbr->select($this->_themes, '*', $condstr, 'Database::select', $options);
        }

	public function getThemes(){
        $condsIbug='deleted = 0 and deprecated = 0';
        $options = array('ORDER BY' =>  'theme ASC');
        return $this->_dbr->select($this->_themes, '*', $condsIbug, 'Database::select');
        }
		
	public function addPageData ($themes, $tag_name, $clour){
        $dbr = wfGetDB( DB_MASTER );          
        $qry = "INSERT INTO `".$this->_themes."`( theme, tag_name, colour ) VALUES( '".$themes."', '".$tag_name."', '".$clour."' ) on duplicate key update theme='".$themes."'";        
        $dbr->query($qry);
        return $this->cekData($tag_name);
        }	
	
        public function getIbugViewOneOnly($IssueID) {                          
            $condsIbug['issue_id'] = (int) $IssueID;                        
            return $this->_dbr->select($this->_viewIbug, '*', $condsIbug, 'Database::select');            
        }
        
        public function getIbugIssues($conds, $offset, $limit = 50000){       
         global $wgUser;    
            if($conds['deleted'] == 1){                               
                if ($conds['mnth']==""){
                    $conds['mnth'] = substr(date("F",time() - date("j")*24*60*60),0,3);                   
                    if($conds['mnth']=="Dec"){
                        $conds['yr']=date("Y")-1;
                    }
                    else{
                        $conds['yr']=date("Y");
                    }
                }
            $condstr ="deleted=1 AND ";
            if ($conds['type'] != ""){
                $condstr = $condstr .  "type='".$conds['type']."' AND ";
            }
            if ($conds['owned_by'] != ""){
                $condstr = $condstr .  "LOWER(owned_by)=LOWER('". addslashes($conds['owned_by'])."') AND ";
            }
            if ($conds['approv_by'] != ""){
                $condstr = $condstr .  "LOWER(approv_by)=LOWER('". addslashes($conds['approv_by'])."') AND ";
            }
            if ($conds['coor'] != ""){
                $condstr = $condstr .  "LOWER(coor)=LOWER('". addslashes($conds['coor'])."') AND ";
            }
            if ($conds['priority'] != ""){
                $condstr = $condstr .  "priority=".$conds['priority']." AND ";
            }
            if ($conds['mnth']!="any"){
                $condstr = $condstr. "substr(targ_accom,4,3)='".$conds['mnth']."' AND ";
            }
            if ($conds['yr']!="any"){
                $condstr = $condstr. "substr(targ_accom,8,4)='".$conds['yr']."' AND ";
            }                        
            $condstr = $condstr. "deleted=1";            
            //$condstr = $condstr."substr(targ_accom,4,3)='".$conds['mnth']."' AND substr(targ_accom,8,4)='".$conds['yr']."'";                        
            $options = array(                
                'OFFSET'   => (int) $offset,
                      );                   
            return $this->_dbr->select($this->_viewIbug, '*', $condstr, 'Database::select', $options);
            }
                        
            elseif($conds['status']=='s_w1' || $conds['status']=='s_w2' || $conds['status']=='s_as' || $conds['status']=='s_an'){
                //var_dump($conds);
                if (is_array($conds)){
                unset($conds['mnth']);
                unset($conds['yr']);
			}                                              
            //$condstrstat="status LIKE '".$conds['status']."%'";
                    $Gstatus=array();
                    switch ($conds['status']) {
                    case 's_w1': 
                        $Gstatus=array("'bugw'");
                        break;
                    case 's_w2':
                        $Gstatus=array("'ori2'","'new2'");
                        break;
                    case 's_as': 
                        $Gstatus=array("'bug'","'bug1'","'bug2'");
                        break;
                    case 's_an': 
                        $Gstatus=array("'ori1'","'ori2'","'new1'","'new2'");
                        break;
                    }                    
            $condstrstat="status IN(Select status from ibug_tracker_status where Gstatus IN (".implode(",",$Gstatus)."))";
            if ($conds['type'] != ""){
                $condstrstat = $condstrstat .   " AND type='".$conds['type']."' ";
            }
            if ($conds['owned_by'] != ""){
                $condstrstat = $condstrstat .  " AND LOWER(owned_by)=LOWER('". addslashes($conds['owned_by'])."') ";
            }
            if ($conds['approv_by'] != ""){
                $condstrstat = $condstrstat .  " AND LOWER(approv_by)=LOWER('". addslashes($conds['approv_by'])."') ";
            }
            if ($conds['coor'] != ""){
                $condstrstat = $condstrstat .  " AND LOWER(coor)=LOWER('". addslashes($conds['coor'])."') ";				
            }
            if ($conds['priority'] != ""){
                $condstrstat = $condstrstat .  " AND priority=".$conds['priority'];
            }
            $options = array(			
                        'OFFSET'   => (int) $offset,
                        );			
            
            return $this->_dbr->select($this->_viewIbug, '*', $condstrstat, 'Database::select', $options);
            }            
        else{
            if (is_array($conds)){
                unset($conds['mnth']);
                unset($conds['yr']);
            }			
		$options = array(		
			'OFFSET'   => (int) $offset
		);	
                    ///Base Access Theme on TOM//  
                    $userAccesEdit = $this->getUserAccessIbug($wgUser->getName(),"read");
                    foreach ($userAccesEdit as $UserAcces):
                            $l_readtheme[]=$UserAcces['tag_name'];
                    endforeach;                                              
                    foreach ($conds as $key=>$val) {
                        $condsArr .= $key. "='".$val."' AND ";
                    }                               
                    $conds1 =$condsArr." ( type IN('".implode("','",$l_readtheme)."') OR owned_by='".$wgUser->getName()."' OR coor='".$wgUser->getName()."' OR approv_by='".$wgUser->getName()."' ) ";                                                                            
		return $this->_dbr->select($this->_viewIbug, '*', $conds1, 'Database::select', $options);
		}
	}
        
        public function getIbugIssueForNumberingOnlyOne($IssueID){       
        $arrnumbering = array();    
        $arrnumbering[$IssueID]=$IssueID;
        return $arrnumbering;
        }
        
	public function getIbugIssueForNumbering($condsNumb, $SearchSTR){          
        $condsarr = "`deleted` =".$condsNumb['deleted'];                   
        if ($condsNumb['deleted']==1){            
                if ($condsNumb['mnth']==""){
                $condsNumb['mnth'] = substr(date("F",time() - date("j")*24*60*60),0,3);                                 
                if($condsNumb['mnth']=="Dec"){
                    $condsNumb['yr']=date("Y")-1;
                }
                else{
                    $condsNumb['yr']=date("Y");
                }
            }
	$consarr=$consarr." substr(targ_accom,4,3)='".$condsNumb['mnth']."' AND substr(targ_accom,8,4)='".$condsNumb['yr']."'";                              
        $condsarr =$condsarr. " AND (`issue_id` LIKE '%".$SearchSTR."%' OR `title` LIKE '%".$SearchSTR."%')";             
        //$condsarr =$condsarr. " AND (`issue_id` LIKE '%".$SearchSTR."%' OR `title` LIKE '%".$SearchSTR."%' OR `summary` LIKE '%".$SearchSTR."%')";        
        }	                         
                $res = $this->_dbr->select(
		$this->_viewIbug,
                array(
                    'issue_id',
                    'status',
                    'deleted',
                    'targ_accom'
                ),
                $condsarr,
                'Database::select',
                array(
                    'OFFSET' => '0',
                )
            );              				
        $count = $this->_dbr->numRows( $res );        
        $arrnumbering = array();
        if($count >0){        
                        $prevcode="";
			$lencurcode=0;
			$lenprevcode=0;
			$boolcode=false; 	
						
			 foreach ( $res as $row ) {
 	            if ($row->deleted==0 && $condsNumb['deleted']==0){

                    //for status
                    $curcode=substr($row->status,-1);
                    $lencurcode=strlen($row->status);
                    $Lnum=$row->issue_id;
                        if ($curcode!==$prevcode || $lencurcode!==$lenprevcode){
                            $prevcode=$curcode;
                            $lenprevcode=strlen($row->status);
					
                        if ($prevcode=="a" && $lenprevcode==5){
                            $Lcode="N";
			//$Lnum=1;
                        }
                        elseif($prevcode=="b" && $lenprevcode==5){
                            $Lcode="C";
			//$Lnum=1;
                        }
                        elseif($lenprevcode==6 && $boolcode==false){
                            $boolcode=true;
                            $Lcode="W";
			//$Lnum=1;
                        }
                        elseif($prevcode=="d" && $lenprevcode==5){
                            $Lcode="D";
			//$Lnum=1;
                        }
                        elseif($prevcode=="e" && $lenprevcode==5){
                            $Lcode="E";
			//$Lnum=1;
                        }
			else{
			//$Lnum++;
			}                                         					                        
                        }
                            else{
                            //$Lnum++;
                            }
                            $Ncode = $Lcode.$Lnum;        
                            $arrnumbering[$row->issue_id]=$Ncode;
				}

                elseif ($row->deleted==1 && $condsNumb['deleted']==1){
                    //echo $condsNumb['mnth']."-".$condsNumb['yr']."<br>";
                    //echo substr($row->targ_accom,3,3)."-".substr($row->targ_accom,7,4)."/";

                    //if ((substr($row->targ_accom,3,3)==$condsNumb['mnth']) && (substr($row->targ_accom,7,4)==$condsNumb['yr'])){
			$curcode=substr($row->status,-1);
                        $lencurcode=strlen($row->status);
                                                
                        if ($curcode!==$prevcode || $lencurcode!==$lenprevcode){
                            $prevcode=$curcode;
                            $lenprevcode=strlen($row->status);                                                                               
                            if ($prevcode=="a" && $lenprevcode==5){
                            $Lcode="N";
                            $Lnum=1;
                            }
                            elseif($prevcode=="b" && $lenprevcode==5){
                            $Lcode="C";
                            $Lnum=1;
                            }
                            elseif($lenprevcode==6 && $boolcode==false){
                            $Lcode="W";
                            $boolcode=true;
                            $Lnum=1;
                            }
                            elseif($prevcode=="d" && $lenprevcode==5){
                            $Lcode="D";
                            $Lnum=1;
                            }
                            elseif($prevcode=="e" && $lenprevcode==5){
                            $Lcode="E";
                            $Lnum=1;
                            }
                            else{
                                $Lnum++;
				}                                                
                            }
                            else{
                            	$Lnum++;
                        }

                        $Ncode = $Lcode.$Lnum;
                        $arrnumbering[$row->issue_id]=$Ncode;
                    /// }
                }
            }
        }        
        return $arrnumbering;						
    }
	
	public function getIbugIssuesBySrting($string, $project, $offset, $Del)
	{
        global $wgUser;       
        $project = addslashes($project);
        $string = addslashes($string);
        //$Del = 0;
            $UsersOnly = $this->getDatabaseUsers("ibuguseronly");
            $i=0;$arrUserOnly=null;
            foreach($UsersOnly as $user){                
                $arrUserOnly[$i] = $user["user_name"];
                $i++;
            } 
                if (in_array($wgUser->getName(), $arrUserOnly)) {                 
                   //echo "ViewIbugUserOnly"; 
                   $condsIbug = "`deleted` = $Del 
                          AND (`issue_id` LIKE '%".$string."%' OR `title` LIKE '%".$string."%' OR `summary` LIKE '%".$string."%') "
                        . "AND owned_by='".$wgUser->getName()."'";
                }else{                    
                        $SectionOnly = $this->getDatabaseUsers("ibugsectiononly");
                        $i=0;$arrSectionOnly=null;
                        foreach($SectionOnly as $user){                
                            $arrSectionOnly[$i] = $user["user_name"];
                            $i++;
                        }
                        if (in_array($wgUser->getName(), $arrSectionOnly)) {
                            //echo "ViewSectionUserOnly";
                            $arrSecs = $this->getDatabaseUsersSection($wgUser->getName());                       
                                foreach($arrSecs as $user){                
                                $arrayUSR[] = $user["user_name"];
                                }                             
                            //$arrayUSR = array("Wayan", "Alit", "third");
                            $arrUserSec = "'". implode("', '", $arrayUSR) ."'"; 
                            //echo $arrUserSec;
                                $condsIbug = "`deleted` = $Del 
                                AND (`issue_id` LIKE '%".$string."%' OR `title` LIKE '%".$string."%' OR `summary` LIKE '%".$string."%')"
                                . "AND owned_by in(".$arrUserSec.")";                            
                            }else{
                            //echo "ViewAll";
                                $condsIbug = "`deleted` = $Del 
                                AND (`issue_id` LIKE '%".$string."%' OR `title` LIKE '%".$string."%' OR `summary` LIKE '%".$string."%')";
                            }
                }        
                
                    ///Base Access Theme on TOM//        
                    $userAccesEdit = $this->getUserAccessIbug($wgUser->getName(),"read");
                    foreach ($userAccesEdit as $UserAcces):
                            $l_readtheme[]=$UserAcces['tag_name'];
                    endforeach;  
                      
                    $condsIbug = $condsIbug." AND ( type IN('".implode("','",$l_readtheme)."') OR owned_by='".$wgUser->getName()."' OR coor='".$wgUser->getName()."' OR approv_by='".$wgUser->getName()."' )";                                         
            //echo "<br/>".$condsIbug."<br/>";                
            //return $this->getIbugIssues($condsIbug, $offset);
            return $this->_dbr->select($this->_viewIbug, '*', $condsIbug, 'Database::select', $options);               
	}
	
	/**
	 * Selects an issue based on a given id.
	 *
	 * @param int $issueId
	 * @return ResultSet
	 */
	public function getIbugIssueById($issueId)
	{
		$condsIbug['issue_id'] = (int) $issueId;
		return $this->_dbr->select($this->_tableIbug, '*', $condsIbug, 'Database::select');		
	}
	
	/**
	 * Adds a new issue to the database.
	 *
	 * @param array $postData
	 * @param int $userId
	 * @param string $userName
	 * @return bool Returns true on success or false on failure.
	 */
	public function addIbugIssue($postData, $userId, $userName)
	{
		$curDateTime=date('Y-m-d H:i:s');		
		$dataIbug = array(
			'title'         	=> $postData['bt_title'], 
			'summary'       	=> $postData['bt_summary'], 
			'type'          	=> $postData['bt_type'], 
			'status'        	=> $postData['bt_status'], 
			'assignee'      	=> $postData['bt_assignee'],
			'user_id'       	=> $userId,
			'user_name'     	=> $userName,
			'project_name'  	=> $postData['bt_project'],
			'priority_date' 	=> $curDateTime,			
			'priority'	 	=> $postData['bt_priority'],
			'start_date'     	=> $postData['bt_start_date'],
                        'target_date'           => $postData['bt_target_date'],			
                        'due_date'	 	=> $postData['bt_mod_date'],
			'perc_complete'		=> $postData['bt_perc_complete'],
			'targ_accom'	 	=> $postData['bt_targ_accom'],
			'approv_by'	 	=> $postData['bt_approv_by'],
			'owned_by'	 	=> $postData['bt_owned_by'],
			'coor'          	=> $postData['bt_coor_by'],
			'Issuerndfile'   	=> $postData['fcode'],
			'last_modifier'  	=> $postData['bt_modifier']			
		);		
		$output=$this->_dbr->insert($this->_tableIbug, $dataIbug);
		$condsIbug = "`user_name` = '".$userName."' AND `priority_date` = '".$curDateTime."'";
                $rsID = $this->_dbr->select($this->_tableIbug, 'issue_id', $condsIbug, 'Database::select');
		$rsIDTemp = $rsID;
		$rowID=$rsIDTemp->fetchObject();
                $issue_id=$rowID->issue_id;
                
                //INSERT LOGGING//
                $this->createLog_EditInterface($issue_id, "status","", "Bug Assigned","status" );      
                
                    if ($postData['bt_priority'] == '1'){		                   
                    $objIbugAjax = new ITaskTrackerAjax();    
                       $objIbugAjax->IbugEmailNotice($issue_id,"new");    
                    unset($objIbugAjax);
                    }		
		return $issue_id;
		#return $output;
	}
	
	/**
	 * Updates an issue.
	 *
	 * @param array $postData
	 * @param int $userId
	 * @param string $userName
	 * @return bool Returns true on success or false on failure.
	 */
	public function updateIbugIssue($issueId, $postData)
	{
		$value = array(
                        'title'    	=> $postData['bt_title'], 
                        'summary'  	=> $postData['bt_summary'], 
                        'type'     	=> $postData['bt_type'], 
                        'status'   	=> $postData['bt_status'], 
                        'assignee' 	=> $postData['bt_assignee'],
                        'priority' 	=> $postData['bt_priority'],
			'start_date'    => $postData['bt_start_date'],
                        'target_date'   => $postData['bt_target_date'],
		        'due_date'      => $postData['bt_due_date'],
            		'perc_complete' => $postData['bt_perc_complete'],
            		'targ_accom'    => $postData['bt_targ_accom'],
            		'approv_by'     => $postData['bt_approv_by'],
			'owned_by'      => $postData['bt_owned_by'],
			'coor'          => $postData['bt_coor_by'],
			'Issuerndfile'  => $postData['fcode'],
			'last_modifier' => $postData['bt_modifier']                        

		);                 
                //Put Logging on Edit Interface
                $priority[1] = 'URGENT';
                $priority[2] = 'High';
                $priority[3] = 'Medium';
                $priority[4] = 'Low';
                $arStatus = $this->getIbugStatus(0);               
                $rs = $this->getIbugIssueById($issueId);
                $row = $rs->fetchObject();   
                $dttoday = date("d-M-Y");
                if ($value['title'] !== $row->title) {                
                $this->createLog_EditInterface($issueId, "title", $row->title, $value['title'],"title" );
                $this->createLog_EditInterface($issueId, "nextStep", $row->targ_accom, $dttoday,"" );
                }
                if ($value['type'] !== $row->type) {                         
                $this->createLog_EditInterface($issueId, "type", $row->type, $value['type'],"type" );
                $this->createLog_EditInterface($issueId, "nextStep", $row->targ_accom, $dttoday,"" );
                }
                if ($value['priority'] !== $row->priority) {                      
                $this->createLog_EditInterface($issueId, "prior", $priority[$row->priority], $priority[$value['priority']],"priority" );
                $this->createLog_EditInterface($issueId, "nextStep", $row->targ_accom, $dttoday,"" );
                }
                if ($value['start_date'] !== $row->start_date) {                      
                $this->createLog_EditInterface($issueId, "start_date",$row->start_date, $value['start_date'],"start_date" );              
                }     
                if ($value['target_date'] !== $row->target_date) {                      
                $this->createLog_EditInterface($issueId, "target_date",$row->target_date, $value['target_date'],"target_date" );              
                }  
                if ($value['status'] !== $row->status) {                      
                $this->createLog_EditInterface($issueId, "status",$arStatus[$row->status]['name'], $arStatus[$value['status']]['name'],"status" );      
                $this->createLog_EditInterface($issueId, "nextStep", $row->targ_accom, $dttoday,"" );
                }                 
                if ($value['owned_by'] !== $row->owned_by) {                      
                $this->createLog_EditInterface($issueId, "owner",$row->owned_by, $value['owned_by'],"owner" );    
                $this->createLog_EditInterface($issueId, "nextStep", $row->targ_accom, $dttoday,"" );
                }        
                if ($value['coor'] !== $row->coor) {                      
                $this->createLog_EditInterface($issueId, "coor",$row->coor, $value['coor'],"coordinator" );    
                $this->createLog_EditInterface($issueId, "nextStep", $row->targ_accom, $dttoday,"" );
                } 
                if ($value['approv_by'] !== $row->approv_by) {                      
                $this->createLog_EditInterface($issueId, "approv",$row->approv_by, $value['approv_by'],"Requester" );    
                $this->createLog_EditInterface($issueId, "nextStep", $row->targ_accom, $dttoday,"" );
                } 
                if ($value['summary'] !== $row->summary) {                      
                $this->createLog_EditInterface($issueId, "summary",$row->summary, $value['summary'],"summary" );                    
                } 
                
		if ($postData['bt_status'] == 's_new') {
			$value['priority_date'] = date('Y-m-d H:i:s');
		}		
		$condsIbug['issue_id'] = (int) $issueId;		
		$output=$this->_dbr->update($this->_tableIbug, $value, $condsIbug);
                    if ($postData['bt_priority'] == '1'){                         
                        $objIbugAjax = new ITaskTrackerAjax();                        
                           $objIbugAjax->IbugEmailNotice($issueId,"edit");                             
                        unset($objIbugAjax);
                    }                                      
		return $output;
	}
	
        
        public function createLog_EditInterface($issue_id,$type,$old_value,$new_value,$remark){
        global $wgUser;        
        /*
        mysql_query("insert into ibug_logging (issue_id, user_name, type, old_value, new_value, remark) values ($id,'$nme', 'title','".$rows['title']."','$value', '".$_SERVER['REMOTE_ADDR']." : (".$nme.") has change title to:<b> ".$value."</b> and previous title is: <b> ".$rows['title']."</b>')");
        mysql_query("insert into ibug_logging (issue_id, user_name, type, old_value, new_value, remark) values ($id, '$nme', 'type','".$rows['type']."', '$value', '".$_SERVER['REMOTE_ADDR']." : (".$nme.") has change type to:<b> ".$value."</b> and previous type is<b> ".$rows['type']."</b>')");
        mysql_query("insert into ibug_logging (issue_id, user_name, type, old_value, new_value, remark) values ($id, '$nme', 'prior','$pri', '$prt', '".$_SERVER['REMOTE_ADDR']." : (".$nme.") has change priority to:<b> ".$prt."</b> and previous priority is: <b> ".$pri."</b>')");
        mysql_query("insert into ibug_logging (issue_id, user_name, type, old_value, new_value, remark) values ($id, '$nme', 'start_date', '".$rows['start_date']."','$value', '".$_SERVER['REMOTE_ADDR']." : (".$nme.") has change start_date to:<b> ".$value."</b> and previous start_date is: <b> ".$rows['start_date']."</b>')");		
        mysql_query("insert into ibug_logging (issue_id, user_name, type, old_value, new_value, remark) values ($id, '$nme', 'target_date', '".$rows['target_date']."','$value', '".$_SERVER['REMOTE_ADDR']." : (".$nme.") has change target_date to:<b> ".$value."</b> and previous target_date is: <b> ".$rows['target_date']."</b>')");		
        mysql_query("insert into ibug_logging (issue_id, user_name, type, old_value, new_value, remark) values ($id,'$nme', 'status','$sts', '$stts', '".$_SERVER['REMOTE_ADDR']." : (".$nme.") has change status to:<b> ".$stts."</b> and previous status is: <b> ".$sts."</b>')");
        mysql_query("insert into ibug_logging (issue_id, user_name, type, old_value, new_value, remark) values ($id,'$nme', 'owner','".$rows['owned_by']."', '$value', '".$_SERVER['REMOTE_ADDR']." : (".$nme.") has change owner to:<b> ".$value."</b> and previous owner is: <b> ".$rows['owned_by']."</b>')");
        mysql_query("insert into ibug_logging (issue_id, user_name, type, old_value, new_value, remark) values ($id,'$nme', 'coor','".$rows['coor']."','$value', '".$_SERVER['REMOTE_ADDR']." : (".$nme.") has change coordinator to:<b> ".$value."</b> and previous coordinator is: <b> ".$rows['coor']."</b>')");
        mysql_query("insert into ibug_logging (issue_id, user_name, type, old_value, new_value, remark) values ($id, '$nme', 'approv', '".$rows['approv_by']."','$value', '".$_SERVER['REMOTE_ADDR']." : (".$nme.") has change Requester to:<b> ".$value."</b> and previous Requester is: <b> ".$rows['approv_by']."</b>')");
        mysql_query("insert into ibug_logging (issue_id, user_name, type, old_value, new_value, remark) values ($id,'$nme', 'nextStep','".$rows['targ_accom']."','".$dttoday."','')");                                        
        */        
        $user_name = $wgUser->getName();                 
        $remarkAdded = ($remark=="")? "" : $_SERVER['REMOTE_ADDR']." : (".$user_name.") has change ".$remark." to:<b> ".$new_value."</b> and previous ".$remark." is: <b> ".$old_value."</b>";
        $dataLog = array(
                'issue_id'      => $issue_id,
                'user_name'    	=> $user_name,
                'type'   	=> $type,
                'old_value'   	=> $old_value,
                'new_value'   	=> $new_value,
                'remark' 	=> $remarkAdded                
        );       
        $output=$this->_dbr->insert($this->_ibugLog, $dataLog);        
        return $output;
        }
        
        public function createLog($issue_id,$type,$remarkAdd){
        global $wgUser;
        $user_name = $wgUser->getName();
        $remark = $_SERVER['REMOTE_ADDR']."(".$user_name.") : ".$remarkAdd." ID:".$issue_id;
        //$qry = "INSERT INTO `".$this->_ibugLog."`(issue_id,user_name,type,remark) VALUES( '".$issue_id."', '".$user_name."', '".$type."', '".$remark."' )";        
        //mysql_query($qry);            
        $dataLog = array(
                'issue_id'      => $issue_id,
                'user_name'    	=> $user_name,
                'type'   	=> $type,
                'remark' 	=> $remark                
        );
        $output=$this->_dbr->insert($this->_ibugLog, $dataLog);        
        return $output;
        }

        /**
	 * Archives an issue.
	 *
	 * @param int $issueId
	 * @return bool Returns true on success or false on failure.
	 */
	public function archiveIbugIssue($issueId)
	{
	$value['deleted'] = 1;
	$condsIbug['issue_id'] = (int) $issueId;
            $this->createLog($issueId, "archiveIbug", "archive Ibug");
	return $this->_dbr->update($this->_tableIbug, $value, $condsIbug);
	}
        
	public function unarchiveIbugIssue($issueId){
        $value['deleted'] = 0;
        $condsIbug['issue_id'] = (int) $issueId;
            $this->createLog($issueId, "unarchiveIbug", "unarchive Ibug");
        return $this->_dbr->update($this->_tableIbug, $value, $condsIbug);
        }
    
	public function deleteIbugIssue($issueId){
        $condsIbug['issue_id'] = (int) $issueId;
            $this->createLog($issueId, "deleteIbug", "delete Ibug");
        return $this->_dbr->delete($this->_tableIbug,  $condsIbug);         
        }
	
	public function changestatusIbugIssue($issueId,$newstatus){
        $value['status'] = $newstatus;
        $condsIbug['issue_id'] = (int) $issueId;
        return $this->_dbr->update($this->_tableIbug, $value, $condsIbug);
        }

	public function changeActionIbugIssue($issueId,$newaction,$typeaction){		
		$condsIbug['issue_id'] = (int) $issueId;		
		if ($typeaction=="status"){
			$value['status'] = $newaction;
		}
		else{
			$value['deleted'] = $newaction;
		}
		return $this->_dbr->update($this->_tableIbug, $value, $condsIbug);
		
		
	}
		   
    public function getIbugCommentById($issueId)
    {
        $condsIbug['ibug_id'] = (int) $issueId;
        $condsIbug['deleted'] = 0;
        return $this->_dbr->select($this->_vwIbugComment, '*', $condsIbug, 'Database::select');
    }	

    public function getCountComment($issueId)
    {   
        $condsIbug['ibug_id'] = (int) $issueId;
        $condsIbug['deleted'] = 0;
        $res = $this->_dbr->select($this->_vwIbugComment, '*', $condsIbug, 'Database::select','ORDER BY timestamp ASC'); 
        $comment_count = $this->_dbr->numRows($res);
        return $comment_count;        
    }
	    
    public function getCountSmallComment($issueId,$secondCount)
    {               
        $dbr = wfGetDB( DB_SLAVE );
        $sql = "select * from ".$this->_ibugComment."  where ibug_id=$issueId and deleted =0 order by  timestamp ASC limit $secondCount,2";
        $results = $dbr->query($sql);
        $arr = array();
        while ($r = $dbr->fetchObject( $results )) {					
                $arr[$r->id] = array('id'=>$r->id,
                                            'user_name'=>$r->user_name,
                                            'comment'=>$r->comment,    
                                            'timestamp'=>$r->timestamp);
        }                
        return $arr;                      
    }
    public function getRestComment($issueId, $secondCount){
        $dbr = wfGetDB( DB_SLAVE );
        $sql = "select * from  ibug_comment where ibug_id=$issueId and deleted =0 order by  timestamp ASC limit 0,$secondCount";
        $results = $dbr->query($sql);
        $arr = array();
        while ($r = $dbr->fetchObject( $results )) {					
                $arr[$r->id] = array('id'=>$r->id,
                                            'user_name'=>$r->user_name,
                                            'comment'=>$r->comment,    
                                            'timestamp'=>$r->timestamp);
        }                
        return $arr;                 
    }
    public function getLog1($issueId, $type){         
        $dbr = wfGetDB( DB_SLAVE );        	
        $sql = "select n.*, time(n.timestamp) as time, date(n.timestamp) as date, n.type from ".$this->_ibugLog." n inner join(SELECT MAX(time(Timestamp)) AS Time, date(Timestamp) as Date, Type, max(Id) as Id FROM ".$this->_ibugLog." where Type='".$type."' and Issue_id=".$issueId." GROUP BY Date, Type) nm on nm.id=n.id and nm.type=n.type and Time = time and nm.date = Date order by id desc";
        $results = $dbr->query($sql);  
        return $dbr->fetchObject($results);            
    }    
    
    public function getLog($issueId, $type){
        $dbr = wfGetDB( DB_SLAVE );        	
        $sql = "select n.*, time(n.timestamp) as time, date(n.timestamp) as date, n.type from ".$this->_ibugLog." n inner join(SELECT MAX(time(Timestamp)) AS Time, date(Timestamp) as Date, Type, max(Id) as Id FROM ".$this->_ibugLog." where Type='".$type."' and Issue_id=".$issueId." GROUP BY Date, Type) nm on nm.id=n.id and nm.type=n.type and Time = time and nm.date = Date order by id desc";
        $results = $dbr->query($sql);
        $arr = array();
        $i=0;
        while ($r = $dbr->fetchObject( $results )) {					
                $arr[$type.$i] = array('user_name'=>$r->user_name,
                                       'date'=>$r->date,
                                       'old_value'=>$r->old_value,
                                       'new_value'=>$r->new_value);
                $i++;
        }                
        return $arr;        
    }

    public function getNext($issueId, $type){        
        $dbr = wfGetDB( DB_SLAVE );        	
        $sql="select n.*, time(n.timestamp) as time, date(n.timestamp) as date, n.type from ".$this->_ibugLog." n inner join(SELECT MAX(time(Timestamp)) AS Time, date(Timestamp) as Date, Type, max(Id) as Id FROM ".$this->_ibugLog." where Type='".$type."' and Issue_id=".$issueId." GROUP BY Date, Type) nm on nm.id=n.id and nm.type=n.type and Time = time and nm.date = Date order by id desc";                
        $results = $dbr->query($sql);
        $arr = array();
        $i=0;
        while ($r = $dbr->fetchObject( $results )) {					
                $arr[$type.$i] = array('user_name'=>$r->user_name,
                                            'date'=>$r->date,
                                            'old_value'=>$r->old_value);
                 $i++;                
        }                
        return $arr;        
    }
   
    public function addIbugComment($ibug_id, $user_name, $comment){
        $dbr = wfGetDB( DB_SLAVE );
        $dataIbug = array(
                'ibug_id'      	=> $ibug_id,
                'user_name'    	=> $user_name,
                'comment' 	    => $comment,
                'deleted' 	    => 0                
        );
        $output=$this->_dbr->insert($this->_ibugComment, $dataIbug);

        $rstNext = $dbr->query("SELECT targ_accom,priority FROM ibug_tracker where issue_id =".$ibug_id."");
        $rst = $dbr->fetchObject($rstNext);          
        $old_nxt = $rst->targ_accom;        
        $priority =  $rst->priority;;
        switch ($priority) {
        case "1":
            $days = 1;
        break;
        case "2":
            $days = 5;
        break;
        case "3":
            $days = 30;
        break;
        case "4":
            $days = 60;
        break;    
        } 
        $Date = date("Y-m-d");
        $nxt = date('d-M-Y', strtotime($Date. ' + '.$days.' days'));            
        $dttoday_update = date("d-M-Y");
        $dbr->query("insert into ibug_logging (issue_id, user_name, type, old_value, new_value, remark) values ('".$ibug_id."','".$user_name."','nextStep','".$old_nxt."','".$nxt."','".$user_name." add new comment')");                
        $dbr->query("update ibug_tracker set targ_accom = '".$nxt."', due_date = '".$dttoday_update."', last_modifier = '".$user_name."' where issue_id = ".$ibug_id);            
         
    } 
    
    public function getDatabaseUsers($type){
        $tableuser = $this->_ListUser;
        $tablegroup = $this->_ListUserGroup;
        $dbr = wfGetDB( DB_SLAVE );                                   
                    
        $sql = "SELECT DISTINCT u.user_id, u.user_name, u.user_email FROM $tableuser as u, $tablegroup as g".
                " WHERE  u.user_id=g.ug_user".
                " AND g.ug_group='".$type."'" .                  
                " AND u.user_id NOT IN (select ipb_user from ipblocks)". $orderby;// LIMIT 0,100;";	         
        $res = $dbr->query($sql);  
        $arr = array();
        while ($r = $dbr->fetchObject( $res )) {					
                $arr[$r->user_id] = array('user_id'=>$r->user_id, 
                                          'user_name'=>$r->user_name, 
                                          'user_email'=>$r->user_email );
        }                    
        
        return $arr;
    }
    
    public function getDatabaseUsersSection($user){   
        $tableuser = $this->_ListUser;
        $dbr = wfGetDB( DB_SLAVE );
        $orderby = "ORDER BY em.user_name; ";
        $sql = "SELECT em.user_name,u.user_id FROM employee as em, $tableuser as u  ".
                " WHERE section = (SELECT DISTINCT section from employee where user_name='".$user."' )".
                " AND em.user_name=u.user_name".
                " AND u.user_id NOT IN (select ipb_user from ipblocks)". $orderby;	
        $res = $dbr->query($sql);  
        $arr = array();
        while ($r = $dbr->fetchObject( $res )) {					
                $arr[$r->user_id] = array('user_id'=>$r->user_id, 
                                          'user_name'=>$r->user_name);
        }

        return $arr;
    }
    
    #Temporary Function#
    public function addDeprecatedType($tag_name,$theme,$colour){        
        $dataIbug = array(
                'tag_name'      => $tag_name,
                'theme'    	=> $theme,
                'deprecated'   	=> 1,
                'colour' 	=> "#".$colour                
        );
        $output=$this->_dbr->insert($this->_themes, $dataIbug);        
        return $output;
    }
    
    public function getThemesAccess($user){          
		$tableAccess = $this->_tableAccess; 
                $tableuser = $this->_ListUser;
                $tableTOM=$this->_themes;
		$dbr = wfGetDB( DB_SLAVE );
		$orderby = "ORDER BY t.themes; ";
		$sql = "SELECT DISTINCT t.tag_name, t.themes, tom.theme FROM $tableAccess as t, $tableuser as u , $tableTOM as tom ".
                        " WHERE  t.user_id=u.user_id".
                        " AND t.tag_name=tom.tag_name".
                        " AND u.user_name='".$user."'". $orderby;                        		
                $res = $dbr->query($sql);
                #echo ($sql);
                $arr = array();
		while ($r = $dbr->fetchObject( $res )) {					
			$arr[$r->theme] = array('theme'=>$r->theme, 
                                                  'tag_name'=>$r->tag_name);                                                     
		}		
		return $arr;
    }
    
    public function getIbugStatus($usedAdd){          
		$tableStatus = $this->_ibugstatus;                 
		$dbr = wfGetDB( DB_SLAVE );
                if ($usedAdd==1) {
		$sql = "SELECT * FROM $tableStatus  ".
                " WHERE usedAdd='".$usedAdd."' AND deprecated=0 Order by sorter ";}
                else{
                $sql = "SELECT * FROM $tableStatus WHERE deprecated=0 Order by sorter ";    
                }
                $res = $dbr->query($sql);
                //echo ($sql);
                $arr = array();
		while ($r = $dbr->fetchObject( $res )) {					
			$arr[$r->status] = array('status'=>$r->status, 
                                                 'name'=>$r->name,
                                                 'colour'=>$r->colour,
                                                 'Gstatus'=>$r->Gstatus);                                                     
		}		
		return $arr;
    }
    
    public function getUserAccessIbug($user,$status){  
        global $wgUseSSO;           
		$tableAccess = $this->_tableAccess; 
        $tableuser = $this->_ListUser;               
		$dbr = wfGetDB( DB_SLAVE );		
		$sql = "SELECT DISTINCT t.tag_name, t.permission FROM $tableAccess as t, $tableuser as u".
                        " WHERE  t.user_id=u.user_id".
                        " AND t.permission='".$status."'". 
                        " AND u.user_name='".$user."'";                        		
                $res = $dbr->query($sql);                
                $arr = array();
		while ($r = $dbr->fetchObject( $res )) {					
			$arr[$r->tag_name] = array('tag_name'=>$r->tag_name, 
                                                  'permission'=>$r->permission);                                                     
		}	                        
		return $arr;
    }

    public function getUser_XS($user) { 
        $tableAccess = $this->_tableAccess; 
        $tableuser = $this->_ListUser;               
		$dbr = wfGetDB( DB_SLAVE );		 
        $sql = "SELECT DISTINCT t.tag_name,t.access FROM $tableAccess as t, $tableuser as u".
                                    " WHERE  t.user_id=u.user_id".                                    
                                    " AND t.access IN('S','X') AND u.user_name='".$user."'";                                                                    		
        $res = $dbr->query($sql);                                            
        while ($r = $dbr->fetchObject( $res )) {					
                $arr[$r->tag_name] = array('tag_name'=>$r->tag_name, 
                                            'access'=>$r->access);                                                     
        }
        return $arr;
    }

    public function getIbugIsDeleted($ID){          		
		$dbr = wfGetDB( DB_SLAVE );               
		$sql = "SELECT * FROM $this->_viewIbug WHERE issue_id='".$ID."'";                        		
                $res = $dbr->query($sql);                
                $arr = array();
		while ($r = $dbr->fetchObject( $res )) {					
			$arr = $r->deleted;                                                     
		}		
		return $arr;
    }
    
    public function updateImage($theme,$imgid){        			
		$query="UPDATE image set img_theme='".$theme."' where img_id=".$imgid."";
		mysql_query($query);	
    }
    
    public function getImageData($img_name){          		
		$dbr = wfGetDB( DB_SLAVE );               
		$sql = "SELECT * FROM image WHERE img_name='".$img_name."'";                        		
                $res = $dbr->query($sql);                
                $arr = array();		
		while ($r = $dbr->fetchObject( $res )) {					
			$arr[$r->img_id] = array('img_id'=>$r->img_id, 
                                                 'img_user_text'=>$r->img_user_text,
                                                 'img_theme'=>$r->img_theme);                                                     
		}	                
		return $arr;
    }
    
    public function getCommentArrayList($Arr_issueId)
    {        
        $dbr = wfGetDB( DB_SLAVE );               
        $sql = "SELECT *,count(ibug_id) as totC,GROUP_CONCAT(id ORDER by id SEPARATOR '~^~') as Cid,GROUP_CONCAT(comment ORDER by id SEPARATOR '~^~') as C,GROUP_CONCAT(user_name ORDER by id SEPARATOR '~^~') as U,GROUP_CONCAT(timestamp ORDER by id SEPARATOR '~^~') as T "
                . " FROM $this->_vwIbugComment WHERE deleted=0 and ibug_id IN ('".implode("','",$Arr_issueId)."') group by ibug_id";                        		        
        $res = $dbr->query($sql);                
        $arr = array();		
        while ($r = $dbr->fetchObject($res)) {					
                $arr[$r->ibug_id] = array('ibug_id'=>$r->ibug_id, 
                                     'totC'=>$r->totC,
                                     'Cid'=>$r->Cid,
                                     'comment'=>$r->C,
                                     'users'=>$r->U,
                                     'timestamp'=>$r->T);                                                     
        }	                               
        return $arr;
    }	
        
    public function getStatusArrayList($Arr_issueId,$type)
    {        
        $dbr = wfGetDB( DB_SLAVE );               
        $sql = "SELECT *,count(issue_id) as totS,GROUP_CONCAT(id ORDER by id desc SEPARATOR '~^~') as Lid,GROUP_CONCAT(user_name ORDER by id desc SEPARATOR '~^~') as U,GROUP_CONCAT(timestamp ORDER by id desc SEPARATOR '~^~') as T,GROUP_CONCAT(new_value ORDER by id desc SEPARATOR '~^~') as N  "
                . " FROM $this->_ibugLog WHERE type='".$type."' and issue_id IN ('".implode("','",$Arr_issueId)."') group by issue_id";                        		        
        $res = $dbr->query($sql);                
        $arr = array();		
        while ($r = $dbr->fetchObject($res)) {					
                $arr[$r->issue_id] = array('issue_id'=>$r->issue_id,  
                                     'totS'=>$r->totS, 
                                     'Lid'=>$r->Lid,                                     
                                     'users'=>$r->U,                    
                                     'timestamp'=>$r->T,
                                     'newvalue'=>$r->N);                                                     
        }	                               
        return $arr;
    }	
    
    public function getNextArrayList($Arr_issueId, $type){        
        $dbr = wfGetDB( DB_SLAVE );        	
        $sql="select n.*,count(issue_id) as totS , GROUP_CONCAT(old_value ORDER by n.id desc SEPARATOR '~^~') as Lold , time(n.timestamp) as time, date(n.timestamp) as date, n.type from ".$this->_ibugLog." n inner join(SELECT MAX(time(Timestamp)) AS Time, date(Timestamp) as Date, Type, max(Id) as Id FROM ".$this->_ibugLog." where Type='".$type."' and Issue_id IN ('".implode("','",$Arr_issueId)."') GROUP BY Date, Type) nm on nm.id=n.id and nm.type=n.type and Time = time and nm.date = Date group by issue_id";                       
        $res = $dbr->query($sql);                
        $arr = array();		
        while ($r = $dbr->fetchObject($res)) {					
                $arr[$r->issue_id] = array('issue_id'=>$r->issue_id,  
                                     'totS'=>$r->totS, 
                                     'Lold'=>$r->Lold                                     
                                     );                                                     
        }	         
        return $arr;        
    }
    
    public function countTotalPriorityiTask($user, $priority)
    {   
        global $wgUsers_unlimited_urgent_itask;
        $dbr = wfGetDB( DB_SLAVE );                 
        $sql_status = "select * from  `ibug_tracker_status` Where deprecated=0 order by sorter";	                   
        $res_status = $dbr->query($sql_status);                
        $arr_code_stattus = array();	        
        while ($r = $dbr->fetchObject($res_status)) {	    
            $STbugArr[trim($r->name)] = $r->status; 
        }
        //$arr_code_stattus[] = $STbugArr["Bug Assigned"];
        $arr_code_stattus[] = $STbugArr["Bug Working"]; 
        //$arr_code_stattus[] = $STbugArr["Bug Feedback"];      
        $sql = "select count(*) as total from ibug_tracker where priority='".$priority."' and owned_by='".$user."' and owned_by NOT IN('".implode("','",$wgUsers_unlimited_urgent_itask)."') and status IN('".implode("','",$arr_code_stattus)."') and deleted=0";                                             
        $res = $dbr->query($sql);       
        $r = $dbr->fetchObject($res);             
        return $r->total;
    }    
    
    public function getOldestiTask($user, $priority)
    {           
        $dbr = wfGetDB( DB_SLAVE );                 
        $sql_status = "select * from  `ibug_tracker_status` Where deprecated=0 order by sorter";	                   
        $res_status = $dbr->query($sql_status);                
        $arr_code_stattus = array();	        
        while ($r = $dbr->fetchObject($res_status)) {	    
            $STbugArr[trim($r->name)] = $r->status; 
        }        
        $arr_code_stattus[] = $STbugArr["Bug Working"];               
        $sql = "select issue_id from ibug_tracker where priority='".$priority."' and owned_by='".$user."' and deleted=0 and status IN('".implode("','",$arr_code_stattus)."') order by date_created Asc limit 1";                                     
        $res = $dbr->query($sql);       
        $r = $dbr->fetchObject($res);             
        return $r->issue_id;
    }   
    
    public function updateIbugPriority($issueId, $priority_)
    {
        //var_dump($_POST);die;
        $value = array(                    
            'priority'    => $priority_,    
            'last_modifier' =>  $_POST["bt_modifier"],
            'due_date' => date("d-M-Y"),
            'targ_accom' => $dttoday = $this->create_next_priority($priority_),	
        );                 
        //Put Logging on Edit Interface            
        $priority[2] = 'High';
        $priority[3] = 'Medium';        
        $arStatus = $this->getIbugStatus(0);               
        $rs = $this->getIbugIssueById($issueId);
        $row = $rs->fetchObject();   
        $dttoday = date("d-M-Y");      
                
        if ($value['priority'] !== $row->priority) {                      
        $this->createLog_EditInterface($issueId, "prior", $priority[$row->priority], $priority[$value['priority']],"priority" );
        $this->createLog_EditInterface($issueId, "nextStep", $row->targ_accom, $dttoday,"" );
        }        		
        $condsIbug['issue_id'] = (int) $issueId;		
        $output=$this->_dbr->update($this->_tableIbug, $value, $condsIbug);                                                  
        return $output;
    }        
    
    public function create_next_priority($priority){         
        switch ($priority) {
        case "1":
            $days = 1;
        break;
        case "2":
            $days = 5;
        break;
        case "3":
            $days = 30;
        break;
        case "4":
            $days = 60;
        break;    
        } 
        $Date = date("Y-m-d");
        $next = date('d-M-Y', strtotime($Date. ' + '.$days.' days'));        
        return $next;
    }
    
    public function getDataParentList($user_id)
    {               
        $dbr = wfGetDB( DB_SLAVE );        
        $sql_theme = "select * from user_access where user_id='".$user_id."' and permission='edit';";
        $res_theme = $dbr->query($sql_theme);                
        $arr_theme = array();	        
        while ($r = $dbr->fetchObject($res_theme)) {	    
            $arr_theme[] = $r->themes; 
            
        }           
        $Or_cond = " ('0'='1' ";
        foreach ($arr_theme as $val_) {
        $Or_cond .= " OR p_themes LIKE '%".$val_."%'";
        }
        $Or_cond .= ") ";                  
        $sql_parent = "select * from `project_list` Where p_page_id>0 AND ".$Or_cond."order by p_page_id";	        
        $res_parent = $dbr->query($sql_parent);                
        $arr_parent = array();	                
        while ($r = $dbr->fetchObject($res_parent)) {	                 
            //echo $r->p_parent_list;
            $arrP = json_decode($r->p_parent_list);
            foreach ($arrP as $key=>$val) {
                $arr_parent[] = $val."-".$r->p_title;
            }
        }          
        //var_dump($arr_parent);
        return $arr_parent;
    }   

    public function get_all_project_list(){
        $dbr = wfGetDB( DB_SLAVE );                                   
        $Or_cond = " ('0'='0' ";
        $Or_cond .= ") ";                  
        $sql_parent = "select * from `project_list` Where p_page_id>0 AND ".$Or_cond."order by p_page_id";	                 
        $res_parent = $dbr->query($sql_parent);                
        $arr_parent = array();                       
        while ($r = $dbr->fetchObject($res_parent)) {	                                         
            $arrP = json_decode($r->p_parent_list);
            $arrP_title = json_decode($r->p_parent_list_title);             
            $idx=0;
            foreach ($arrP as $key=>$val) {
                //$arr_parent[] = $val."-".$r->p_title;
                //$arr_parent[$val] = $arrP_title[$idx];                                
                $arr_parent[$val] = ($arrP_title[$idx])? $arrP_title[$idx] : $val." : ".$r->p_title;                       
                //$arr_parent[$val] = ($arrP_title[$idx])? substr($arrP_title[$idx],0,29)."..." : $val." : ".substr($r->p_title,0,20)."...";
                $idx++;
            }
        }          
        //var_dump($arr_parent);        
        return $arr_parent;
    }
    
} //End Class