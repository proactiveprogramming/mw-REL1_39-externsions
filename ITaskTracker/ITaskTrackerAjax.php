<?php                   

if(isset($_GET['method'])){
        $ITaskTrackerAjaxR = new ITaskTrackerAjax;
        $ITaskTrackerAjaxR->{$_GET['method']}();        
    }     

class ITaskTrackerAjax
{
        protected $_strPriority=Null;
        protected $_strType=Null;
        protected $_strStatus=Null;
        protected $_emailUser=Null;
        protected $_conn;     
        protected $_wgSMTP=Null;   
        protected $_ScriptPath;  
        protected $_URLiTaskProcess;  
        protected $connSSO_DB;
                
    public function __construct()
    {              
        global $wgCurrentDir,$wgScriptPath,$wgURLpath ;  
                
        $path= isset($_GET['local'])?$_GET['local']:$wgCurrentDir;               
        include  $path.'/Local.php';    
        
        $this->_wgSMTP = $wgSMTP;       
        $Server= explode(":",$wgDBserver); 
        $conn = mysqli_connect($Server[0], $wgDBuser, $wgDBpassword, $wgDBname, $wgDBport)  or die(mysqli_error());       
        $this->_conn = $conn;                      
        $this->_ScriptPath = $wgScriptPath;       
        $this->_URLiTaskProcess = "<br /><br /><strong>Wiki Page : </strong>"."Please follow the process https://".$_SERVER['HTTP_HOST'].$wgURLpath."/ITask_Process";        
        $this->users_unlimited_urgent_itask = $wgUsers_unlimited_urgent_itask;
        $this->MaxUrgentTask = $wgMaxUrgentTask;
        $this->MaxHighTask = $wgMaxHighTask;
        $this->MaxMediumTask = $wgMaxMediumTask;        
    }   
    
    public function closeDBsso () {
            return mysqli_close($this->connSSO_DB);
    } 
    
    public function sendmailNew($content, $emailto, $nameto, $subjectto, $outputfile){          
        $sql= "SELECT ldapgroup FROM `user` WHERE username = '".strtolower($nameto)."'";	                
        $res_ldap=mysqli_query($this->connSSO_DB,$sql);                 
        $arr_DT = mysqli_fetch_assoc($res_ldap);    
           
        if ($content!=="" && $emailto!=="" && $emailto!==NULL)  { 
            mysqli_query($this->_conn,"insert into email_queue (content,emailto,nameto,subjectto,outputfile) "
                            . "values ('".base64_encode($content)."','".base64_encode($emailto)."','".base64_encode($nameto)."','".base64_encode($subjectto)."','".base64_encode(implode('^',$outputfile))."')");      
        }
        
       
    }
    
    public function sendnotification($text, $subject, $usrName, $itask_id) {
        $arrUser1[] = ucfirst(strtolower(trim($usrName)));           
        $sql = "select * from user where `user_name` IN ('".implode("','",$arrUser1)."')";                
        $result=mysqli_query($this->_conn,$sql);                       
        while($row=mysqli_fetch_array($result)){  
            $arrId[] = $row['user_id']; 
            $agent_id =  $row['user_id'];
        }    
        
        $event_variant = $itask_id;
        $textC = strlen($text);
        $text = trim(addslashes($text));
        $textDb = 'a:4:{s:15:"newsletter-name";s:5:"Alert";s:13:"newsletter-id";i:1;s:12:"section-text";s:'.$textC.':"'.$text.'";s:11:"notifyAgent";b:1;}';
        $event_extra = $textDb;
        $is_banner = 0;        
        $last_publish_date = ($last_publish_date)? $last_publish_date : "1970-01-01";
        $sql =" INSERT INTO `echo_event` SET `event_type`='newsletter-alert',`event_agent_id`='".$agent_id."',`event_page_title`='".addslashes($subject)."',`event_extra`='".$event_extra."',`event_page_id`=1,`event_deleted`=0,`is_banner`='".$is_banner."',`event_variant`='".$event_variant."' ";
        mysqli_query($this->_conn,$sql);
        $last_id = mysqli_insert_id($this->_conn);
        
        foreach ($arrId as $val) {                   
            $subQuery[] = "(".$last_id .", ".$val.", DATE_FORMAT(NOW() - INTERVAL 8 HOUR, '%Y%m%d%H%i%s'), 1, '', '' )";            
        }	     
                    
        $strSubQuery = implode(", ", $subQuery);
        $sql = "INSERT into `echo_notification` (`notification_event`,`notification_user`,`notification_timestamp`,`notification_bundle_base`,`notification_bundle_hash`,`notification_bundle_display_hash`) VALUES ".$strSubQuery;	                
        mysqli_query($this->_conn,$sql);
    }

    public function sendmailNew0($content, $emailto, $nameto, $subjectto, $outputfile){
        if (!class_exists('PHPMailer')) {
            require_once dirname(__FILE__) . '/../PHPMailer5/class.phpmailer.php';   
        }
        //if ($emailto==!'' || $emailto ==!Null) {   
        try {                                  
            $ecMail=new PHPMailer();
            $ecMail->IsSMTP();                                      // send via SMTP        
            $ecMail->SMTPAuth   = true;                             // enable SMTP authentication
            $ecMail->SMTPSecure = "ssl";                            // sets the prefix to the server
            $ecMail->Host = str_replace("ssl://", "",  $this->_wgSMTP['host']);                      
            $ecMail->Port = 465;        
                $ecMail->Username = $this->_wgSMTP['username'];                       // SMTP username
                $ecMail->Password = $this->_wgSMTP['password'];                       // SMTP password
                $webmaster_email  = $this->_wgSMTP['username'];                       //Reply to this email ID
                                           
            $ecMail->From = $webmaster_email;        
            $ecMail->FromName = $this->_wgSMTP['username'];
            $ecMail->AddAddress($emailto,$nameto);
            $ecMail->WordWrap = 50; // set word wrap				
            $ecMail->IsHTML(true); // send as HTML
            $ecMail->Subject = $subjectto;
            
                    //now Attach all files submitted  
                    if (!empty($outputfile)){      
                        foreach($outputfile as $key => $value) { //loop the Attachments to be added ...
                        $ecMail->AddAttachment($value);           
                        }
                    }
                    $ecMail->Body = $content; //HTML Body
                    $ecMail->IsHTML(true); // send as HTML
                    if(!$ecMail->Send()) throw new Exception($ecMail->ErrorInfo);
        }
            catch(Exception $e){
            //echo $e->getMessage();
            }   
    }		                
    
    function setPriority(){        
        $strpriority['1'] = array('name' => 'Urgent');
        $strpriority['2'] = array('name' => 'High');
        $strpriority['3'] = array('name' => 'Medium');
        $strpriority['4'] = array('name' => 'Low');    
            return $this->_strPriority = $strpriority;   
    }
    
    function setType(){                          
        $qry = "select  *,substring(colour,2) as clr from `team_org_matrix` where deleted=0";        
        $tr=mysqli_query($this->_conn,$qry);
        while ($rst=mysqli_fetch_array($tr)) {
        $strtype[$rst['tag_name']] = array('name' => $rst['theme'], 'colour' => $rst['clr']);
        }
            return $this->_strType = $strtype;
    }
    
    function setStatus(){
        $qry = "select  *  from `ibug_tracker_status`";
        $tr=mysqli_query($this->_conn,$qry);
        while ($rst=mysqli_fetch_array($tr)) {            
        $strstatus[$rst['status']] = array('name' => $rst['name'], 'colour' => $rst['colour']);
        } 
             return $this->_strStatus = $strstatus;
    }
    
    function getEmail($usrName){        
        if ($usrName != ""){
            $queryUsr="select user_email from user where user_name='".$usrName."'";	
            $resultUsr=mysqli_query($this->_conn,$queryUsr);
            $rowUsr=mysqli_fetch_array($resultUsr); 
                $usrEmail=$rowUsr[user_email];
                return $this->_emailUser = $usrEmail;
        }
    }        
    
    function simple_decrypt($text){  
            $salt="IbugMisKronos201";
            return trim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256,  $salt, base64_decode($text), MCRYPT_MODE_ECB, mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB), MCRYPT_RAND)));
    } 
            
    public function ContentUpdateToEmail($usrName,$arrDetails,$strType,$strID,$outputfile,$urlFooter,$regard){
            ?>
            <style> 
            p.P1
            {
            width:9em; 
            border:1px solid #000000;
            word-break:break-all;
            }
            </style>
            <?php
        $strpriority = $this->setPriority();
        $strtype = $this->setType();
        $strstatus = $this->setStatus();                           
        $usrEmail=$this->getEmail($usrName);
                $queryCmd="SELECT * FROM ibug_comment WHERE ibug_id='".$strID."' and deleted=0 order by timestamp desc";
                $resultCmd=mysqli_query($this->_conn,$queryCmd);            
                    $strContent="Dear ".$usrName.",<br />";
                    $strContent=$strContent."<em>This is an autogenerated email, please do not reply this email</em><br />";
                    if ($strType=="owner"){
                            $strContent=$strContent."<br />Please find below New Updated iTask that you as an <b>Owner</b>.<br />";
                    }
                    else if ($strType=="approval"){
                            $strContent=$strContent."<br />Please find below New Updated iTask that you as an <b>Requester</b>.<br />";
                    }
                    else if ($strType=="coordinator"){
                            $strContent=$strContent."<br />Please find below New Updated iTask that you as a <b>Coordinator</b>.<br />";
                    }
                    $strContent=$strContent."<br /><strong>ID : </strong>".$strID;
                    $strContent=$strContent."<br /><strong>Title : </strong>".$arrDetails['title'];
                    $strContent=$strContent."<br /><strong>Theme : </strong><span style='background-color:#".$strtype[$arrDetails['type']]['colour'].";'>".$strtype[$arrDetails['type']]['name']."</span>";
                    $strContent=$strContent."<br /><strong>Creation Date : </strong>".date("d-M-Y H:i:s", strtotime($arrDetails['date_created']));
                    $strContent=$strContent."<br /><strong>Start Date : </strong>".$arrDetails['start_date'];
                    $strContent=$strContent."<br /><strong>Target Date : </strong>".$arrDetails['target_date'];
                    $strContent=$strContent."<br /><strong>Last Modified : </strong>".$arrDetails['due_date'];
                    $strContent=$strContent."<br /><strong>Next Step Date : </strong>".$arrDetails['targ_accom'];
                    $strContent=$strContent."<br /><strong>Priority : </strong>".$strpriority[$arrDetails['priority']]['name'];
                    $strContent=$strContent."<br /><strong>Status : </strong><span style='background-color:#".$strstatus[$arrDetails['status']]['colour'].";'>".str_replace('Bug','iTask',$strstatus[$arrDetails['status']]['name'])."</span>";		
                    $strContent=$strContent."<br /><strong>Owner : </strong>".$arrDetails['owned_by'];
                    $strContent=$strContent."<br /><strong>Requested By : </strong>".$arrDetails['approv_by'];
                    $strContent=$strContent."<br /><strong>Remark : </strong>".$arrDetails['summary'];
                    $strContent=$strContent."<br /><strong>URL : </strong>".$urlFooter.$strID;
                    $strContent=$strContent.$this->_URLiTaskProcess;
                        $strContent=$strContent."<br /><strong>All Comments History : </strong><br />" ;
                        while ($rowCmd = mysqli_fetch_array($resultCmd)){
                            $strContent=$strContent."<br />Update On ".$rowCmd[timestamp]." :" ;
                            $strContent=$strContent."<p class='P1'>".$rowCmd[comment]."</p>" ;
                            $strContent=$strContent."<br />" ;
                        }
                    $strsubject="iTask being forwarded to you by ".$usrName;
                    $strContent=$strContent."<br /><br />Best regards,<br /><br />".$regard;
                    $this->sendmailNew($strContent, $usrEmail, $usrName, $strsubject, $outputfile);     
                    $this->sendnotification($strContent, $strsubject, $usrName, $strID);      
    }
    
    public function UpdateToEmail(){                       
            $id = $_POST['elementid'];     
            $id = substr($id,8);
            $value = $_POST['newvalue'];    
            $UpDir = $this->simple_decrypt($_POST['UpDir']);                             
                $regard=$this->_wgSMTP['username'];
                $path=$this->_ScriptPath;               
                $urlFooter = "https://".$_SERVER['HTTP_HOST'].$path."/index.php?title=Special%3AITaskTracker&project=Special%3AITaskTracker&bt_action=list&bt_filter_by=idonly&bt_filter=Apply&bt_filter_issueid=";                
            $rslt=mysqli_query($this->_conn,"SELECT * FROM `ibug_tracker` WHERE issue_id=".$id);
            while ($rows = mysqli_fetch_array($rslt)){    
                    $outputfile=null;
                    if (is_dir($UpDir.$rows['Issuerndfile'])) {             
                        $outputdir=dir($UpDir.$rows['Issuerndfile']);
                        //var_dump($outputdir);
                        $j=0;
                        while (false !== ($entry = $outputdir->read())) {
                            if($entry != '.' && $entry != '..' && !is_dir($dir.$entry))
                            {
                              $outputfile[$j]=$UpDir.$rows['Issuerndfile']."/".$entry;
                              $j++;
                            }
                        }
                        $outputdir->close();               
                        //var_dump($outputfile);die;
                    }
                if ($value == 'Own'){
                    $this->ContentUpdateToEmail($rows['owned_by'],$rows,"owner",$id,$outputfile,$urlFooter,$regard);
                }
                if ($value == 'Appr'){
                    $this->ContentUpdateToEmail($rows['approv_by'],$rows,"approval",$id,$outputfile,$urlFooter,$regard);
                }
                if ($value == 'Coord'){
                    $this->ContentUpdateToEmail($rows['coor'],$rows,"coordinator",$id,$outputfile,$urlFooter,$regard);
                }
                if ($value == 'Sall'){
                    $this->ContentUpdateToEmail($rows['owned_by'],$rows,"owner",$id,$outputfile,$urlFooter,$regard);
                    $this->ContentUpdateToEmail($rows['approv_by'],$rows,"approval",$id,$outputfile,$urlFooter,$regard);
                    $this->ContentUpdateToEmail($rows['coor'],$rows,"coordinator",$id,$outputfile,$urlFooter,$regard);
                }
                if ($value == 'OwnAppr'){
                    $this->ContentUpdateToEmail($rows['owned_by'],$rows,"owner",$id,$outputfile,$urlFooter,$regard);
                    $this->ContentUpdateToEmail($rows['approv_by'],$rows,"approval",$id,$outputfile,$urlFooter,$regard);           
                }
            }
        echo "[Send Email]";
        //echo $value;
    }
    
    function getStatus(){
        $qry = "select  *  from `ibug_tracker_status`";
        $tr=mysqli_query($this->_conn,$qry);
        while ($rst=mysqli_fetch_array($tr)) {            
        $codestatus[$rst['name']] = $rst['status'];
        } 
             return $this->_codeStatus = $codestatus;
    }
    
    public function change_status($id,$value){     
	$nme = $_POST['user_name'];	    
        $dttoday_update = date("d-M-Y");
        $dttoday = $this->create_next($id);
        
        $regard=$this->_wgSMTP['username'];
        $path=$this->_ScriptPath;
        //$urlFooter = "http://".$_SERVER['HTTP_HOST'].$path."/index.php?title=Special:ITaskTracker&bt_action=edit&bt_issueid=";                                                                                     
        $urlFooter = "https://".$_SERVER['HTTP_HOST'].$path."/index.php?title=Special%3AITaskTracker&project=Special%3AITaskTracker&bt_action=list&bt_filter_by=idonly&bt_filter=Apply&bt_filter_issueid=";              
        
        $rsl=mysqli_query($this->_conn,"SELECT targ_accom, tr.status as status, Rtrim(Ltrim(name)) as sts FROM ibug_tracker as tr , ibug_tracker_status as st where  tr.status=st.status and issue_id =".$id."");
        while ($row = mysqli_fetch_array($rsl)){
        $sts=$row['sts'];
        $targ_accom=$row['targ_accom']; 
            mysqli_query($this->_conn,"update ibug_tracker set status = '".$value."', last_modifier='".$nme."',due_date='".$dttoday_update."',targ_accom='".$dttoday."' where issue_id = ".$id);                   
        }               
                $rsl=mysqli_query($this->_conn,"SELECT *, tr.status as status, Rtrim(Ltrim(name)) as sts FROM ibug_tracker as tr , ibug_tracker_status as st where  tr.status=st.status and issue_id =".$id."");
                while ($row = mysqli_fetch_array($rsl)){                                        
                    $rsl1=mysqli_query($this->_conn,"SELECT Rtrim(Ltrim(name)) as sts FROM ibug_tracker_status where status ='".$value ."'");                        
                    while ($rowst = mysqli_fetch_array($rsl1)){
                    $stts=$rowst['sts'];
                        //if ($row['priority'] == 1 || $row['priority'] == 2) {
                        if ($row['priority'] == 1) {    
                        switch ($row['sts'] ) :
                        case 'Bug Assigned' :
                            $this->ContentIbugGetEmail($row['owned_by'],$row,"edit",$id,$urlFooter,$regard);
                            break;
                        case 'Bug Working' :
                            if ($sts=="Bug Assigned"){                            
                            $this->ContentIbugAppGetEmail($row['approv_by'],$row,"edit",$id,$urlFooter,$regard);                            
                            }else{                               
                            $this->ContentIbugGetEmail($row['owned_by'],$row,"edit",$id,$urlFooter,$regard);             
                            }
                            break;
                        case 'Bug Pending Development' :
                            $this->ContentIbugGetEmail($row['owned_by'],$row,"edit",$id,$urlFooter,$regard); 
                            break;
                        case 'Bug Feedback':                               
                            $this->ContentIbugAppGetEmail($row['approv_by'],$row,"edit",$id,$urlFooter,$regard);
                            break;
                        case 'Bug Pending Approval':
                            $this->ContentIbugAppGetEmail($row['approv_by'],$row,"edit",$id,$urlFooter,$regard);
                            break;
                        case 'Bug Approved':
                            $this->ContentIbugApproved($row['owned_by'],$row,"edit",$id,$urlFooter,$regard); 
                            break;
                        case 'Bug Cancelled' :
                            $this->ContentIbugGetEmail($row['owned_by'],$row,"edit",$id,$urlFooter,$regard);                               
                            $this->ContentIbugAppGetEmail($row['approv_by'],$row,"edit",$id,$urlFooter,$regard);
                            break;
                        endswitch;
                        }  
                        //Checking if any dependencies iTask
                        if ($row['sts']=='Bug Pending Approval' || $row['sts']=='Bug Approved'){                                                                    
                        mysqli_query($this->_conn,"Update ibug_tracker set status=(select status from ibug_tracker_status where name='Bug Working'), last_modifier='".$nme."',due_date='".$dttoday_update."',targ_accom='".$dttoday."' where status=(select status from ibug_tracker_status where name='Bug Pending Development') and reason=".$id);                   
                        }
                    }
                }
            mysqli_query($this->_conn,"insert into ibug_logging (issue_id,user_name,  type, old_value, new_value, remark) values ($id,'$nme', 'status','$sts', '$stts', '".$_SERVER['REMOTE_ADDR']." : (".$nme.") has change status to:<b> ".$stts."</b> and previous status is: <b> ".$sts."</b>')");
            mysqli_query($this->_conn,"insert into ibug_logging (issue_id, user_name, type, old_value, new_value, remark) values ($id,'$nme', 'nextStep','".$targ_accom."','".$dttoday."','')");                		                                 
    }
    
    public function add_comment(){        
         
           function checkValues($value){               
               // Use this function on all those values where you want to check for both sql injection and cross site scripting
               //Trim the value
               $value = trim($value);
               // Stripslashes
               if (get_magic_quotes_gpc()) {
                       $value = stripslashes($value);
               }
                // Convert all &lt;, &gt; etc. to normal html and then strip these
                $value = strtr($value,array_flip(get_html_translation_table(HTML_ENTITIES)));
                // Strip HTML Tags
                $value = strip_tags($value);
                 
               // Quote the value                
               //$value = mysqli_real_escape_string($value);
               $value =  str_replace("'", "''", $value);
               //echo $value;
               $value = htmlspecialchars ($value);              
               return $value;
            }	            
          
             function html_cleanup($text) 	
	            { 	
	                //$string = htmlentities($text);	
	                //$out_ = str_replace(array("&lt;i&gt;", "&lt;b&gt;", "&lt;/i&gt;", "&lt;/b&gt;", "&lt;br /&gt;", "&lt;br/&gt;"), array("<i>", "<b>", "</i>", "</b>", "<br />", "<br/>"), $string);                                                    	
                    $out_ = str_replace("&not","&amp;not",$text);
                    return $out_;	                    
	            }		
	                 	
	        //var_dump(checkValues($_REQUEST['comment_text']) );            	
		      //$comments=mysqli_real_escape_string($_REQUEST['comment_text']);          	
	        //$comments=checkValues($_REQUEST['comment_text']);                      	
	        //$comments=str_replace("'", "''", $_REQUEST['comment_text']);           	
	        $comments= $_REQUEST['comment_text'];       	 
        
        if(checkValues($_REQUEST['comment_text']) && $_REQUEST['post_id'] && $_REQUEST['user_name'] && $_REQUEST['nxt']) 
        {       
             /*       	
	            $query="INSERT INTO ibug_comment (ibug_id,user_name,comment,deleted) VALUES('".$_REQUEST['post_id']."','".$_REQUEST['user_name']."','".$comments."',0)";	
	            mysqli_query($this->_conn,$query);       	
	            $newid = mysqli_insert_id($this->_conn);   	
	            */	
		
	            $deleted = 0;	
	            $sql = "INSERT INTO ibug_comment (ibug_id,user_name,comment,deleted) VALUES (?, ?, ?, ?)";                	
	            $stmt = $this->_conn->prepare($sql);            	
	            $stmt->bind_param("sssi", $_REQUEST['post_id'], $_REQUEST['user_name'], $comments, $deleted);  	
	            $result = $stmt->execute();  	
	            $newid = $stmt->insert_id;	
	            $stmt->close();            
        }      
      
        $qry = "select count(*) as tot from `ibug_comment` where deleted=0 And ibug_id =".$_REQUEST['post_id'];            
        $tr=mysqli_query($this->_conn,$qry);
        $rst=mysqli_fetch_array($tr);                     
            ?>            
                <div id="record-<?php  echo $newid;?>" tot_comment="<?php  echo $rst["tot"];?>" align="left" style="background-color:#d3d3d3; padding-top:5px;padding-left:5px; padding-right:5px; margin-top:3px">
                        <label class="postedComments">
                                <?php  echo "<strong>".$_REQUEST['user_name']." : </strong><br />".html_cleanup($comments);
                                ?>
                        </label>
                        <br clear="all" />
                        <span style="margin-left:2px; color:#666666; font-size:11px; text-align:left;">
                        <?php echo date('d-M-Y : H:i:s'); ?>		
                        </span>
                        <a href="#" style="text-align:right" id="CID-<?php  echo $newid;?>" class="c_delete" onclick="return delete_comment('<?php echo $newid; ?>')" name="c_del<?php echo $_REQUEST['post_id'];?>">Delete</a>
                </div>
            <?php                       
                                                  
            $qry = "SELECT targ_accom FROM ibug_tracker where issue_id =".$_REQUEST['post_id']."";            
            $tr=mysqli_query($this->_conn,$qry);
            $rst=mysqli_fetch_array($tr);                  
            $old_nxt = $rst["targ_accom"];
            $nxt = $this->create_next($_REQUEST['post_id']);     
            $dttoday_update = date("d-M-Y");
            mysqli_query($this->_conn,"insert into ibug_logging (issue_id, user_name, type, old_value, new_value, remark) values ('".$_REQUEST['post_id']."','".$_REQUEST['user_name']."','nextStep','".$old_nxt."','".$nxt."','".$_REQUEST['user_name']." add new comment')");            
            mysqli_query($this->_conn,"update ibug_tracker set targ_accom = '".$nxt."', due_date = '".$dttoday_update."', last_modifier = '".$_REQUEST['user_name']."' where issue_id = ".$_REQUEST['post_id']);            
            
            $codestatus = $this->getStatus();            
            //Change Status//            
            switch ($_REQUEST['status']) {
                case "working":
                $this->change_status($_REQUEST['post_id'],$codestatus["Bug Working"]);
                break;
                
                case "feedback":
                $this->change_status($_REQUEST['post_id'],$codestatus["Bug Feedback"]);
                break;

                case "approve":
                $this->change_status($_REQUEST['post_id'],$codestatus["Bug Approved"]);
                break;
            
                case "cancel":
                $this->change_status($_REQUEST['post_id'],$codestatus["Bug Cancelled"]);
                break;
            
                case "pendingapprove":                   
                $this->change_status($_REQUEST['post_id'],$codestatus["Bug Pending Approval"]);
                break;
            
                case "pending":                
                $this->change_status($_REQUEST['post_id'],$codestatus["Bug Pending Development"]);
                break;
            
                default:
                    break;
            }
            
    }
    
    public function dependIbug(){       
	if($_POST){
		$id=$_POST['id'];		
                $depend_id=($_POST['depend']);
		$query="UPDATE ibug_tracker set depend_id='".$depend_id."' where issue_id=".$id."";
		mysqli_query($this->_conn,$query);
	}
    }
    
    public function getemailcontent($vars_) {                                           
        $vars =  $vars_ + array("url_dashboard" => $url_dashboard);    
        foreach($vars as $search => $replace){
        $emailcontent = str_ireplace('{{' . $search . '}}', $replace, $emailcontent); 
        }
        $emailcontent = mb_convert_encoding($emailcontent, 'HTML-ENTITIES', "UTF-8"); 
        
        return $emailcontent;
    }

    public function ContentIbugGetEmail($usrName,$arrDetails,$strType,$strID,$urlFooter,$regard){
        $strpriority = $this->setPriority();
        $strtype = $this->setType();
        $strstatus = $this->setStatus();
        $usrEmail=$this->getEmail($usrName);       
            $strContent="Dear ".$usrName.",<br />";
            $strContent=$strContent."<em>This is an autogenerated email, please do not reply this email</em><br />";
            if ($strType=="new"){
                    $strContent=$strContent."<br />Please find below the new task that was assigned to you.<br />";
            }
            else if ($strType=="edit"){
                    $strContent=$strContent."<br />Please find below your updated task.<br />";
            }
            $strContent=$strContent."<br /><strong>ID : </strong>".$strID;
            $strContent=$strContent."<br /><strong>Title : </strong>".$arrDetails['title'];
            $strContent=$strContent."<br /><strong>Theme : </strong><span style='background-color:#".$strtype[$arrDetails['type']]['colour'].";'>".$strtype[$arrDetails['type']]['name']."</span>";
            $strContent=$strContent."<br /><strong>Creation Date : </strong>".date("d-M-Y H:i:s", strtotime($arrDetails['date_created']));
            $strContent=$strContent."<br /><strong>Start Date : </strong>".$arrDetails['start_date'];
            $strContent=$strContent."<br /><strong>Target Date : </strong>".$arrDetails['target_date'];
            $strContent=$strContent."<br /><strong>Last Modified : </strong>".$arrDetails['due_date'];
            $strContent=$strContent."<br /><strong>Next Step Date : </strong>".$arrDetails['targ_accom'];            
            $strContent=$strContent."<br /><strong>Priority : </strong>".$strpriority[$arrDetails['priority']]['name'];
            $strContent=$strContent."<br /><strong>Status : </strong><span style='background-color:#".$strstatus[$arrDetails['status']]['colour'].";'>".str_replace('Bug','iTask',$strstatus[$arrDetails['status']]['name'])."</span>";		
            $strContent=$strContent."<br /><strong>Owner : </strong>".$arrDetails['owned_by'];
            $strContent=$strContent."<br /><strong>Requested By : </strong>".$arrDetails['approv_by'];
            $strContent=$strContent."<br /><strong>Remark : </strong>".$arrDetails['summary'];
            $strContent=$strContent."<br /><strong>URL : </strong>".$urlFooter.$strID;
            $strContent=$strContent.$this->_URLiTaskProcess;
            $strsubject="iTask New Task Notification for ".$usrName;
            $strContent=$strContent."<br /><br />Best regards,<br /><br />".$regard;

            /*
            if (strpos($arrDetails['title'], 'Submitted Notebook') !== false) {
                $notebook_name0 = explode("-",$arrDetails['title']);                
                $notebook_name =  trim($notebook_name0[1]);
                $msg_body = "NOT APPROVED";
                $vars_ = array( "notebook_id_param" =>$notebook_id_param, "notebook_name" =>$notebook_name, "msg_body" =>$msg_body, "Coordinator" =>$Coordinator, "Approval_By" =>$Approval_By); 
                $strContent = $this->getemailcontent($vars_);                               
            }
            */
            $this->sendmailNew($strContent, $usrEmail, $usrName, $strsubject, $outputfile);                 
            $this->sendnotification($strContent, $strsubject, $usrName, $strID);
    } 
    
    public function ContentIbugAppGetEmail($usrName,$arrDetails,$strType,$strID,$urlFooter,$regard){
        $strpriority = $this->setPriority();
        $strtype = $this->setType();
        $strstatus = $this->setStatus();
        $usrEmail=$this->getEmail($usrName); 
            $strContent="Dear ".$usrName.",<br />";
            $strContent=$strContent."<em>This is an autogenerated email, please do not reply this email</em><br />";
            if ($strType=="new"){
                    $strContent=$strContent."<br />Please find below the new task that was assigned to you.<br />";
            }
            else if ($strType=="edit"){
                    $strContent=$strContent."<br />Please find below the new updated task that you as an <b>Requester</b>.<br />";
            }
            $strContent=$strContent."<br /><strong>ID : </strong>".$strID;
            $strContent=$strContent."<br /><strong>Title : </strong>".$arrDetails['title'];
            $strContent=$strContent."<br /><strong>Theme : </strong><span style='background-color:#".$strtype[$arrDetails['type']]['colour'].";'>".$strtype[$arrDetails['type']]['name']."</span>";
            $strContent=$strContent."<br /><strong>Creation Date : </strong>".date("d-M-Y H:i:s", strtotime($arrDetails['date_created']));
            $strContent=$strContent."<br /><strong>Start Date : </strong>".$arrDetails['start_date'];
            $strContent=$strContent."<br /><strong>Target Date : </strong>".$arrDetails['target_date'];
            $strContent=$strContent."<br /><strong>Last Modified : </strong>".$arrDetails['due_date'];
            $strContent=$strContent."<br /><strong>Next Step Date : </strong>".$arrDetails['targ_accom'];            
            $strContent=$strContent."<br /><strong>Priority : </strong>".$strpriority[$arrDetails['priority']]['name'];
            $strContent=$strContent."<br /><strong>Status : </strong><span style='background-color:#".$strstatus[$arrDetails['status']]['colour'].";'>".str_replace('Bug','iTask',$strstatus[$arrDetails['status']]['name'])."</span>";		
            $strContent=$strContent."<br /><strong>Owner : </strong>".$arrDetails['owned_by'];
            $strContent=$strContent."<br /><strong>Requested By : </strong>".$arrDetails['approv_by'];
            $strContent=$strContent."<br /><strong>Remark : </strong>".$arrDetails['summary'];
            $strContent=$strContent."<br /><strong>URL : </strong>".$urlFooter.$strID;
            $strContent=$strContent.$this->_URLiTaskProcess;
            $strsubject="iTask New Task Notification for ".$usrName;
            $strContent=$strContent."<br /><br />Best regards,<br /><br />".$regard;
                $this->sendmailNew($strContent, $usrEmail, $usrName, $strsubject, $outputfile);
                $this->sendnotification($strContent, $strsubject, $usrName, $strID);
    }
    
    public function ContentIbugCoorGetEmail($usrName,$arrDetails,$strType,$strID,$urlFooter,$regard){
        $strpriority = $this->setPriority();
        $strtype = $this->setType();
        $strstatus = $this->setStatus();
        $usrEmail=$this->getEmail($usrName); 
            $strContent="Dear ".$usrName.",<br />";
            $strContent=$strContent."<em>This is an autogenerated email, please do not reply this email</em><br />";
            if ($strType=="new"){
                    $strContent=$strContent."<br />Please find below the new task that was assigned to you.<br />";
            }
            else if ($strType=="edit"){
                    $strContent=$strContent."<br />Please find below the new updated task that you as a <b>coordinator</b>.<br />";
            }
            $strContent=$strContent."<br /><strong>ID : </strong>".$strID;
            $strContent=$strContent."<br /><strong>Title : </strong>".$arrDetails['title'];
            $strContent=$strContent."<br /><strong>Theme : </strong><span style='background-color:#".$strtype[$arrDetails['type']]['colour'].";'>".$strtype[$arrDetails['type']]['name']."</span>";
            $strContent=$strContent."<br /><strong>Creation Date : </strong>".date("d-M-Y H:i:s", strtotime($arrDetails['date_created']));
            $strContent=$strContent."<br /><strong>Start Date : </strong>".$arrDetails['start_date'];
            $strContent=$strContent."<br /><strong>Target Date : </strong>".$arrDetails['target_date'];
            $strContent=$strContent."<br /><strong>Last Modified : </strong>".$arrDetails['due_date'];
            $strContent=$strContent."<br /><strong>Next Step Date : </strong>".$arrDetails['targ_accom'];            
            $strContent=$strContent."<br /><strong>Priority : </strong>".$strpriority[$arrDetails['priority']]['name'];
            $strContent=$strContent."<br /><strong>Status : </strong><span style='background-color:#".$strstatus[$arrDetails['status']]['colour'].";'>".str_replace('Bug','iTask',$strstatus[$arrDetails['status']]['name'])."</span>";		
            $strContent=$strContent."<br /><strong>Owner : </strong>".$arrDetails['owned_by'];
            $strContent=$strContent."<br /><strong>Requested By : </strong>".$arrDetails['approv_by'];
            $strContent=$strContent."<br /><strong>Remark : </strong>".$arrDetails['summary'];
            $strContent=$strContent."<br /><strong>URL : </strong>".$urlFooter.$strID;
            $strContent=$strContent.$this->_URLiTaskProcess;
            $strsubject="iTask New Task Notification for ".$usrName;
            $strContent=$strContent."<br /><br />Best regards,<br /><br />".$regard;
                $this->sendmailNew($strContent, $usrEmail, $usrName, $strsubject, $outputfile);
                $this->sendnotification($strContent, $strsubject, $usrName, $strID);
    }
    
    public function ContentIbugApproved($usrName,$arrDetails,$strType,$strID,$urlFooter,$regard){
        $strpriority = $this->setPriority();
        $strtype = $this->setType();
        $strstatus = $this->setStatus();
        $usrEmail=$this->getEmail($usrName);
            $strContent="Dear ".$usrName.",<br />";
            $strContent=$strContent."<em>This is an autogenerated email, please do not reply this email</em><br />";
            if ($strType=="New"){
                    $strContent=$strContent."<br />Please find below the new task that was assigned to you.<br />";
            }
            else if($strType=="Update"){
                    $strContent=$strContent."<br />Please find below the new task that was assigned to you.<br />";
            }
            //total interaction
            $querytotalbug="SELECT count(new_value) as total FROM `ibug_logging` WHERE issue_id='".$strID."' and (new_value='Bug approved' or new_value='Bug working' or new_value='Bug feedback' or new_value='Bug pending approval')";
            $resulttotalbug=mysqli_query($this->_conn,$querytotalbug);
            $rowtotalbug=mysqli_fetch_array($resulttotalbug);
            $totalbug=$rowtotalbug[total];
                $start=date('Y-m-d', strtotime($arrDetails['start_date']));
                $end  =date('Y-m-d', strtotime($arrDetails['due_date']));
                    $pecah1 = explode("-", $start);
                    $date1 = $pecah1[2];
                    $month1 = $pecah1[1];
                    $year1 = $pecah1[0];
                    $pecah2 = explode("-", $end);
                    $date2 = $pecah2[2];
                    $month2 = $pecah2[1];
                    $year2 =  $pecah2[0];
                    $jd1 = GregorianToJD($month1, $date1, $year1);
                    $jd2 = GregorianToJD($month2, $date2, $year2);
                    $total = $jd2 - $jd1;
            //final comment
            $querycomment="SELECT * FROM `ibug_comment` WHERE ibug_id='".$strID."' and user_name!='".$usrName."' and user_name in(SELECT approv_by FROM `ibug_tracker` WHERE issue_id='".$strID."') ORDER BY `id` DESC limit 1";		
            $resultcomm=mysqli_query($this->_conn,$querycomment);
            $rowcomm=mysqli_fetch_array($resultcomm);
            $rowcommname=$rowcomm['user_name'];
            $rowcomment= $rowcomm['comment'];
            $strContent=$strContent."<br /><strong>Title : </strong>".$arrDetails['title'];
            $strContent=$strContent."<br /><strong>Theme : </strong><span style='background-color:#".$strtype[$arrDetails['type']]['colour'].";'>".$strtype[$arrDetails['type']]['name']."</span>";
            $strContent=$strContent."<br /><strong>Creation Date : </strong>".date("d-M-Y H:i:s", strtotime($arrDetails['date_created']));
            $strContent=$strContent."<br /><strong>Start Date : </strong>".$arrDetails['start_date'];
            $strContent=$strContent."<br /><strong>Target Date : </strong>".$arrDetails['target_date'];
            $strContent=$strContent."<br /><strong>Last Modified : </strong>".$arrDetails['due_date'];
            $strContent=$strContent."<br /><strong>Next Step Date : </strong>".$arrDetails['targ_accom'];
            $strContent=$strContent."<br /><strong>Priority : </strong>".$strpriority[$arrDetails['priority']]['name'];
            $strContent=$strContent."<br /><strong>Status : </strong><span style='background-color:#".$strstatus[$arrDetails['status']]['colour'].";'>".str_replace('Bug','iTask',$strstatus[$arrDetails['status']]['name'])."</span>";
            $strContent=$strContent."<br /><strong>Owner : </strong>".$arrDetails['owned_by'];
            $strContent=$strContent."<br /><strong>Requested By : </strong>".$arrDetails['approv_by'];
            $strContent=$strContent."<br /><strong>Remark : </strong>".$arrDetails['summary'];
            $strContent=$strContent."<br /><strong>URL : </strong>".$urlFooter.$strID;
            $strContent=$strContent."<br /><strong>Total Time Task Finished(days) : </strong>".$total;
            $strContent=$strContent."<br /><strong>Total Back to back : </strong>".$totalbug;
            $strContent=$strContent."<br /><strong>Final Comment : <br>".$rowcommname. "</strong> : ".$rowcomment;
            $strsubject="Congratulations ".$usrName." - Your iTask is Approved";
            $strContent=$strContent."<br /><br />Best regards,<br /><br />".$regard;
                $this->sendmailNew($strContent, $usrEmail, $usrName, $strsubject, $outputfile); 
                $this->sendnotification($strContent, $strsubject, $usrName, $strID);
    }
            
    public function create_next($id){ 
        $rslt = mysqli_query($this->_conn,"SELECT priority FROM ibug_tracker where issue_id =".$id."");
        $rows = mysqli_fetch_array($rslt);
        $priority = $rows["priority"];
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
          
    public function save(){                         
        $id = $_POST['elementid'];                              
        $value = $_POST['newvalue'];
        $nme = $_POST['foo'];
        $dttoday_update = date("d-M-Y");
            $id_ = substr($id,7);  
            $dttoday = $this->create_next($id_);
            
            $regard=$this->_wgSMTP['username'];
            $path=$this->_ScriptPath;        
            $urlFooter = "https://".$_SERVER['HTTP_HOST'].$path."/index.php?title=Special%3AITaskTracker&project=Special%3AITaskTracker&bt_action=list&bt_filter_by=idonly&bt_filter=Apply&bt_filter_issueid=";              
        if (substr($id,0,7) == 'div_own'){
            $id = substr($id,7);
            $rslt=mysqli_query($this->_conn,"SELECT owned_by, targ_accom FROM ibug_tracker where issue_id =".$id."");
                while ($rows = mysqli_fetch_array($rslt)){
                mysqli_query($this->_conn,"update ibug_tracker set owned_by = '".$value."', last_modifier='".$nme."',due_date='".$dttoday_update."', targ_accom='".$dttoday."' where issue_id = ".$id);
                mysqli_query($this->_conn,"insert into ibug_logging (issue_id, user_name, type, old_value, new_value, remark) values ($id,'$nme', 'owner','".$rows['owned_by']."', '$value', '".$_SERVER['REMOTE_ADDR']." : (".$nme.") has change owner to:<b> ".$value."</b> and previous owner is: <b> ".$rows['owned_by']."</b>')");
                mysqli_query($this->_conn,"insert into ibug_logging (issue_id, user_name, type, old_value, new_value, remark) values ($id,'$nme', 'nextStep','".$rows['targ_accom']."','".$dttoday."','')");
                echo $value;
            }
        }

        if (substr($id,0,7) == 'div_pcr'){
                    $id = substr($id,7);
                    mysqli_query($this->_conn,"update ibug_tracker set perc_complete = '".$value."' where issue_id = ".$id);
                    echo $value;
            }

        if (substr($id,0,7) == 'div_pry'){
                    $sql_status = "select * from  `ibug_tracker_status` Where deprecated=0 order by sorter";	
                    $resultAL=mysqli_query($this->_conn,$sql_status);                  
                    while ($row = mysqli_fetch_array($resultAL)):                         
                        //$itask = str_replace('Bug','iTask', $row[name]) ;                    
                        $STbugArr[trim($row[name])] = $row[status]; 
                    endwhile;                     
                    //$arr_code_stattus[] = $STbugArr["Bug Assigned"];
                    $arr_code_stattus[] = $STbugArr["Bug Working"]; 
                    //$arr_code_stattus[] = $STbugArr["Bug Feedback"];                
                
                $id = substr($id,7);                
                    $sql = " SELECT priority, targ_accom, ( select count(*) from ibug_tracker where priority='".$value."' and owned_by=IB.owned_by and deleted=0 and owned_by NOT IN('".implode("','",$this->users_unlimited_urgent_itask)."')  and status IN('".implode("','",$arr_code_stattus)."') ) as tot_itask_p,"
                        . " ( select issue_id from ibug_tracker where priority='".$value."' and owned_by=IB.owned_by and deleted=0 and status IN('".implode("','",$arr_code_stattus)."') order by date_created Asc limit 1 ) as oldest_task FROM ibug_tracker IB where issue_id =".$id." ";                                                                                                             
                    //var_dump($sql); die;
            $rslt=mysqli_query($this->_conn,$sql);
                while ($rows = mysqli_fetch_array($rslt)){
                        if ($rows['priority'] == 1){ $pri='URGENT';}
                        elseif ($rows['priority'] == 2){ $pri='High';}
                        elseif ($rows['priority'] == 3){ $pri='Medium';}
                        else {$pri='Low';}
                            $dttoday = $this->create_next_priority($value);			
                if ($value == 1){                       
                                if ( ($rows['tot_itask_p']) >= $this->MaxUrgentTask ) { 
                                    echo "<span id='max_limit_itask' priority='Urgent' limit_itask='".$this->MaxUrgentTask."' tot_itask='".$rows['tot_itask_p']."'>".$pri."</span>"; die();                             
                                } 
                                echo 'URGENT';$prt='URGENT';                           
                            }elseif ($value == 2){
                                if ( ($rows['tot_itask_p']) >= $this->MaxHighTask ) { 
                                    $prt= 'High';
                                    mysqli_query($this->_conn,"update ibug_tracker set priority = '".$value."', last_modifier='".$nme."',due_date='".$dttoday_update."', targ_accom='".$dttoday."' where issue_id = ".$id);   
                                    mysqli_query($this->_conn,"insert into ibug_logging (issue_id, user_name, type, old_value, new_value, remark) values ($id, '$nme', 'prior','$pri', '$prt', '".$_SERVER['REMOTE_ADDR']." : (".$nme.") has change priority to:<b> ".$prt."</b> and previous priority is: <b> ".$pri."</b>')");
                                    mysqli_query($this->_conn,"insert into ibug_logging (issue_id, user_name, type, old_value, new_value, remark) values ($id,'$nme', 'nextStep','".$rows['targ_accom']."','".$dttoday."','')");
                                        //change oldest itask to Medium priority                                    
                                        $id = $rows['oldest_task']; 
                                        $prt= 'Medium';
                                        $pri= 'High';
                                        mysqli_query($this->_conn,"update ibug_tracker set priority = '3', last_modifier='".$nme."',due_date='".$dttoday_update."', targ_accom='".$dttoday."' where issue_id = ".$rows['oldest_task']);   
                                        mysqli_query($this->_conn,"insert into ibug_logging (issue_id, user_name, type, old_value, new_value, remark) values ($id, '$nme', 'prior','$pri', '$prt', '".$_SERVER['REMOTE_ADDR']." : (".$nme.") has change priority to:<b> ".$prt."</b> and previous priority is: <b> ".$pri."</b>')");
                                        mysqli_query($this->_conn,"insert into ibug_logging (issue_id, user_name, type, old_value, new_value, remark) values ($id,'$nme', 'nextStep','".$rows['targ_accom']."','".$dttoday."','')");
                                    echo "<span id='".$rows['oldest_task']."' priority='High' limit_itask='".$this->MaxHighTask."' tot_itask='".$rows['tot_itask_p']."'>High</span>"; die();                                                               
                                }  
                            }
                elseif ($value == 3){
                                if ( ($rows['tot_itask_p']) >= $this->MaxMediumTask ) { 
                                    echo "<span id='max_limit_itask' priority='Medium' limit_itask='".$this->MaxMediumTask."' tot_itask='".$rows['tot_itask_p']."'>".$pri."</span>"; die();                             
                                }
                                echo 'Medium';$prt='Medium';}
                else{                            
                                echo 'Low';$prt='Low';                           
                            }
                            mysqli_query($this->_conn,"update ibug_tracker set priority = '".$value."', last_modifier='".$nme."',due_date='".$dttoday_update."', targ_accom='".$dttoday."' where issue_id = ".$id);   
                mysqli_query($this->_conn,"insert into ibug_logging (issue_id, user_name, type, old_value, new_value, remark) values ($id, '$nme', 'prior','$pri', '$prt', '".$_SERVER['REMOTE_ADDR']." : (".$nme.") has change priority to:<b> ".$prt."</b> and previous priority is: <b> ".$pri."</b>')");
                mysqli_query($this->_conn,"insert into ibug_logging (issue_id, user_name, type, old_value, new_value, remark) values ($id,'$nme', 'nextStep','".$rows['targ_accom']."','".$dttoday."','')");
            }
        }
        
        if (substr($id,0,7) == 'div_coo'){
                    $id = substr($id,7);
            $rslt=mysqli_query($this->_conn,"SELECT coor, targ_accom FROM ibug_tracker where issue_id =".$id."");
                    while ($rows = mysqli_fetch_array($rslt)){
                mysqli_query($this->_conn,"update ibug_tracker set coor = '".$value."', last_modifier='".$nme."',due_date='".$dttoday_update."', targ_accom='".$dttoday."' where issue_id = ".$id);
                mysqli_query($this->_conn,"insert into ibug_logging (issue_id, user_name, type, old_value, new_value, remark) values ($id,'$nme', 'coor','".$rows['coor']."','$value', '".$_SERVER['REMOTE_ADDR']." : (".$nme.") has change coordinator to:<b> ".$value."</b> and previous coordinator is: <b> ".$rows['coor']."</b>')");
                mysqli_query($this->_conn,"insert into ibug_logging (issue_id, user_name, type, old_value, new_value, remark) values ($id,'$nme', 'nextStep','".$rows['targ_accom']."','".$dttoday."','')");
                        echo $value;
            }
            }

        if (substr($id,0,7) == 'div_app'){
                    $id = substr($id,7);
            $rslt=mysqli_query($this->_conn,"SELECT approv_by, targ_accom FROM ibug_tracker where issue_id =".$id."");
                    while ($rows = mysqli_fetch_array($rslt)){
                mysqli_query($this->_conn,"update ibug_tracker set approv_by = '".$value."', last_modifier='".$nme."',due_date='".$dttoday_update."', targ_accom='".$dttoday."' where issue_id = ".$id);
                mysqli_query($this->_conn,"insert into ibug_logging (issue_id, user_name, type, old_value, new_value, remark) values ($id, '$nme', 'approv', '".$rows['approv_by']."','$value', '".$_SERVER['REMOTE_ADDR']." : (".$nme.") has change Requester to:<b> ".$value."</b> and previous Requester is: <b> ".$rows['approv_by']."</b>')");
                mysqli_query($this->_conn,"insert into ibug_logging (issue_id, user_name, type, old_value, new_value, remark) values ($id,'$nme', 'nextStep','".$rows['targ_accom']."','".$dttoday."','')");
                        echo $value;
            }
            }

        if (substr($id,0,7) == 'div_due'){
                    $id = substr($id,7);
            $value = date('d-M-Y',strtotime($value));
                    mysqli_query($this->_conn,"update ibug_tracker set due_date = '".$value."', targ_accom='".$dttoday."' where issue_id = ".$id);
                    echo $value;
            }
        
            if (substr($id,0,7) == 'div_str'){
                    $id = substr($id,7);
                    $value = date('d-M-Y',strtotime($value));
                    $rslt=mysqli_query($this->_conn,"SELECT start_date FROM ibug_tracker where issue_id =".$id."");
                    while ($rows = mysqli_fetch_array($rslt)){
                        mysqli_query($this->_conn,"update ibug_tracker set start_date = '".$value."', last_modifier='".$nme."', due_date='".$dttoday_update."' where issue_id = ".$id);
                        mysqli_query($this->_conn,"insert into ibug_logging (issue_id, user_name, type, old_value, new_value, remark) values ($id, '$nme', 'start_date', '".$rows['start_date']."','$value', '".$_SERVER['REMOTE_ADDR']." : (".$nme.") has change start_date to:<b> ".$value."</b> and previous start_date is: <b> ".$rows['start_date']."</b>')");		
                        echo $value;
                    }
            }
        
            if (substr($id,0,7) == 'div_tar'){
                    $id = substr($id,7);
                    $value = date('d-M-Y',strtotime($value));
                    $rslt=mysqli_query($this->_conn,"SELECT target_date FROM ibug_tracker where issue_id =".$id."");
                    while ($rows = mysqli_fetch_array($rslt)){
                        mysqli_query($this->_conn,"update ibug_tracker set target_date = '".$value."', last_modifier='".$nme."', due_date='".$dttoday_update."' where issue_id = ".$id);
                        mysqli_query($this->_conn,"insert into ibug_logging (issue_id, user_name, type, old_value, new_value, remark) values ($id, '$nme', 'target_date', '".$rows['target_date']."','$value', '".$_SERVER['REMOTE_ADDR']." : (".$nme.") has change target_date to:<b> ".$value."</b> and previous target_date is: <b> ".$rows['target_date']."</b>')");		
                        echo $value;
                    }
            }
                
        if (substr($id,0,7) == 'div_tgr'){
                    $id = substr($id,7);
                    $value = date('d-M-Y',strtotime($value));
                    mysqli_query($this->_conn,"update ibug_tracker set targ_accom = '".$value."', targ_accom='".$dttoday."' where issue_id = ".$id);
                    echo $value;
            }

        if (substr($id,0,7) == 'div_sts'){
                    $id = substr($id,7);                
                    $rsl=mysqli_query($this->_conn,"SELECT targ_accom, tr.status as status, Rtrim(Ltrim(name)) as sts FROM ibug_tracker as tr , ibug_tracker_status as st where  tr.status=st.status and issue_id =".$id."");
                    while ($row = mysqli_fetch_array($rsl)){
                    $sts=$row['sts'];
                    $targ_accom=$row['targ_accom']; 
                        mysqli_query($this->_conn,"update ibug_tracker set status = '".$value."', last_modifier='".$nme."',due_date='".$dttoday_update."',targ_accom='".$dttoday."' where issue_id = ".$id);                   
                    }               
                            $rsl=mysqli_query($this->_conn,"SELECT *, tr.status as status, Rtrim(Ltrim(name)) as sts FROM ibug_tracker as tr , ibug_tracker_status as st where  tr.status=st.status and issue_id =".$id."");
                            while ($row = mysqli_fetch_array($rsl)){
                                $rsl1=mysqli_query($this->_conn,"SELECT Rtrim(Ltrim(name)) as sts FROM ibug_tracker_status where status ='".$value ."'");                        
                                while ($rowst = mysqli_fetch_array($rsl1)){
                                $stts=$rowst['sts'];
                                    //if ($row['priority'] == 1 || $row['priority'] == 2) {
                                    if ($row['priority'] == 1) {
                                    switch ($row['sts'] ) :
                                    case 'Bug Assigned' :
                                        $this->ContentIbugGetEmail($row['owned_by'],$row,"edit",$id,$urlFooter,$regard);
                                        break;
                                    case 'Bug Working' :                                    
                                        if ($sts=="Bug Assigned"){                                                                    
                                        $this->ContentIbugAppGetEmail($row['approv_by'],$row,"edit",$id,$urlFooter,$regard);                                                                
                                        }else{                               
                                        $this->ContentIbugGetEmail($row['owned_by'],$row,"edit",$id,$urlFooter,$regard);                                       
                                        }
                                        break;
                                    case 'Bug Feedback':                                                                                 
                                        $this->ContentIbugAppGetEmail($row['approv_by'],$row,"edit",$id,$urlFooter,$regard);
                                        break;
                                    case 'Bug Pending Approval':
                                        $this->ContentIbugAppGetEmail($row['approv_by'],$row,"edit",$id,$urlFooter,$regard);
                                        break;
                                    case 'Bug Approved':
                                        $this->ContentIbugApproved($row['owned_by'],$row,"edit",$id,$urlFooter,$regard); 
                                        break;
                                    case 'Bug Cancelled' :
                                        $this->ContentIbugGetEmail($row['owned_by'],$row,"edit",$id,$urlFooter,$regard);                               
                                        $this->ContentIbugAppGetEmail($row['approv_by'],$row,"edit",$id,$urlFooter,$regard);
                                        break;
                                    endswitch;
                                    }
                                    //Checking if any dependencies iTask
                                    if ($row['sts']=='Bug Pending Approval' || $row['sts']=='Bug Approved'){                                                                    
                                    mysqli_query($this->_conn,"Update ibug_tracker set status=(select status from ibug_tracker_status where name='Bug Working'), last_modifier='".$nme."',due_date='".$dttoday_update."',targ_accom='".$dttoday."' where status=(select status from ibug_tracker_status where name='Bug Pending Development') and reason=".$id);                   
                                    }
                                }
                            }                    
                        mysqli_query($this->_conn,"insert into ibug_logging (issue_id,user_name,  type, old_value, new_value, remark) values ($id,'$nme', 'status','$sts', '$stts', '".$_SERVER['REMOTE_ADDR']." : (".$nme.") has change status to:<b> ".$stts."</b> and previous status is: <b> ".$sts."</b>')");                    
                        mysqli_query($this->_conn,"insert into ibug_logging (issue_id, user_name, type, old_value, new_value, remark) values ($id,'$nme', 'nextStep','".$targ_accom."','".$dttoday."','')");                		                                    
                    echo str_replace('Bug','iTask',$stts);
            }
        
        if (substr($id,0,7) == 'div_ttl'){
                    $id = substr($id,7);
            $rslt=mysqli_query($this->_conn,"SELECT title, targ_accom FROM ibug_tracker where issue_id =".$id."");
                    while ($rows = mysqli_fetch_array($rslt)){
                mysqli_query($this->_conn,"update ibug_tracker set title = '".$value."', last_modifier='".$nme."',due_date='".$dttoday_update."', targ_accom='".$dttoday."' where issue_id = ".$id);
                mysqli_query($this->_conn,"insert into ibug_logging (issue_id, user_name, type, old_value, new_value, remark) values ($id,'$nme', 'title','".$rows['title']."','$value', '".$_SERVER['REMOTE_ADDR']." : (".$nme.") has change title to:<b> ".$value."</b> and previous title is: <b> ".$rows['title']."</b>')");
                mysqli_query($this->_conn,"insert into ibug_logging (issue_id, user_name, type, old_value, new_value, remark) values ($id,'$nme', 'nextStep','".$rows['targ_accom']."','".$dttoday."','')");
                        echo $value;
            }
            }

        if (substr($id,0,7) == 'div_tpy'){
                    $id = substr($id,7);
                    mysqli_query($this->_conn,"update ibug_tracker set type = '".$value."' where issue_id = ".$id);		
            }
                     
        if (substr($id,0,7) == 'div_sct'){
                    $id = substr($id,7);
                    $rslt=mysqli_query($this->_conn,"SELECT type, targ_accom FROM ibug_tracker where issue_id =".$id."");
                    while ($rows = mysqli_fetch_array($rslt)){
                            mysqli_query($this->_conn,"update ibug_tracker set type = '".$value."', targ_accom='".$dttoday."', last_modifier='".$nme."',due_date='".$dttoday_update."' where issue_id = ".$id);
                            mysqli_query($this->_conn,"insert into ibug_logging (issue_id, user_name, type, old_value, new_value, remark) values ($id, '$nme', 'type','".$rows['type']."', '$value', '".$_SERVER['REMOTE_ADDR']." : (".$nme.") has change type to:<b> ".$value."</b> and previous type is<b> ".$rows['type']."</b>')");
                mysqli_query($this->_conn,"insert into ibug_logging (issue_id, user_name, type, old_value, new_value, remark) values ($id,'$nme', 'nextStep','".$rows['targ_accom']."','".$dttoday."','')");
                            $rst=mysqli_query($this->_conn,"SELECT theme FROM team_org_matrix where tag_name ='".$value."'");
                            while ($row = mysqli_fetch_array($rst)){
                                    echo $row['theme'];
                            }
                    }
            }      
            
        $id2 = $_POST['elementid'];                        
        $id2_ = substr($id2,9);  
        $dttoday2 = $this->create_next($id2_);
        if (substr($id2,0,9) == 'div_sct_2'){
            $id2 = substr($id2,9);
            $rslt=mysqli_query($this->_conn,"SELECT type1, targ_accom FROM ibug_tracker where issue_id =".$id2."");
            while ($rows = mysqli_fetch_array($rslt)){
                    mysqli_query($this->_conn,"update ibug_tracker set type1 = '".$value."', targ_accom='".$dttoday2."', last_modifier='".$nme."',due_date='".$dttoday_update."' where issue_id = ".$id2);
                    mysqli_query($this->_conn,"insert into ibug_logging (issue_id, user_name, type, old_value, new_value, remark) values ($id2, '$nme', 'type1','".$rows['type1']."', '$value', '".$_SERVER['REMOTE_ADDR']." : (".$nme.") has change type1 to:<b> ".$value."</b> and previous type1 is<b> ".$rows['type1']."</b>')");
                    mysqli_query($this->_conn,"insert into ibug_logging (issue_id, user_name, type, old_value, new_value, remark) values ($id2,'$nme', 'nextStep','".$rows['targ_accom']."','".$dttoday2."','')");
                    $rst=mysqli_query($this->_conn,"SELECT theme FROM team_org_matrix where tag_name ='".$value."'");
                    while ($row = mysqli_fetch_array($rst)){
                            echo $row['theme'];
                    }
            }
        }    

    }
    
    public function IbugEmailNotice($issue_id,$type){  //$type:"new" or "edit"       	        
        $regard=$this->_wgSMTP['username'];                           
        $path=$this->_ScriptPath;
        $urlFooter = "https://".$_SERVER['HTTP_HOST'].$path."/index.php?title=Special%3AITaskTracker&project=Special%3AITaskTracker&bt_action=list&bt_filter_by=idonly&bt_filter=Apply&bt_filter_issueid=";        
             $rsl=mysqli_query($this->_conn,"SELECT *, tr.status as status, Rtrim(Ltrim(name)) as sts FROM ibug_tracker as tr , ibug_tracker_status as st where  tr.status=st.status and issue_id =".$issue_id."");
             while ($row = mysqli_fetch_array($rsl)){
                $this->ContentIbugGetEmail($row['owned_by'],$row,$type,$issue_id,$urlFooter,$regard);
             }
    }
        
    public function delete_comment(){        
        $userip = $_SERVER['REMOTE_ADDR'];        
        
        $rstNext=mysqli_query($this->_conn,"SELECT ibug_id,user_name,timestamp FROM ibug_comment where id =".$_REQUEST['id']);
        while ($nxt = mysqli_fetch_array($rstNext)){
        $ibug_id = $nxt['ibug_id'];    
        $user_name = $nxt['user_name']; 
        $timestamp = $nxt['timestamp']; 
        }
        $Today = date("Y-m-d");
        $iTask = substr($timestamp,0,10);                
        //var_dump($Today>$iTask);        
        if ($Today>$iTask) {
            $res['success'] = false;
            $res['message'] = "Delete past comment is not allowed !";              
            echo json_encode($res);
            return;
        }

        if ($user_name!==$_REQUEST['user_name']) {
            $res['success'] = false;
            $res['message'] = "Delete comment from other user is not allowed !";              
            echo json_encode($res);
            return;
        }
        
        mysqli_query($this->_conn,"update ibug_comment set deleted = 1 where id =".$_REQUEST['id']);
        mysqli_query($this->_conn,"insert into ibug_logging (issue_id, user_name, type, remark) values ('".$ibug_id."','".$_REQUEST['user_name']."','DelComment','".$_REQUEST['user_name']." delete comment From IP: ".$userip."')");

        $qry = "select count(*) as tot from `ibug_comment` where deleted=0 And ibug_id =".$ibug_id;            
        $tr=mysqli_query($this->_conn,$qry);
        $rst=mysqli_fetch_array($tr);      
        $res['success'] = true;
        $res['message'] = "success";  
        $res['total_comment'] = $rst["tot"];        
        echo json_encode($res);
    }
    
    public function reasonPending(){        
	if($_POST){
		$id=$_POST['id'];
		$reason=$_POST['reason'];	
		$query="UPDATE ibug_tracker set reason='".$reason."' where issue_id=".$id."";
		mysqli_query($this->_conn,$query);
	}
    }
            
    public function viewajax(){       
        if(isSet($_POST['issue_id']))
            {
                $id=$_POST['issue_id'];
                $com=mysqli_query($this->_conn,"select * from  `ibug_comment` where ibug_id=$id and deleted =0 order by `timestamp` ASC");
                $i=0;
                while($r=mysqli_fetch_array($com))
                    {	
                    $uname=$r['user_name'];
                    $comment=$r['comment'];
        ?>
            <div id="record-<?php  echo $r['id'];?>" align="left" style="background-color:#d3d3d3; padding-top:5px;padding-left:5px; padding-right:5px; margin-top:3px">
                <label class="postedComments">
                        <?php  echo "<strong>".$i.".".$uname." : </strong> <br />".str_replace("\n","<br />",$comment);?>
                </label>
                <br clear="all" />
                <span style="margin-left:2px; color:#666666; font-size:11px; text-align:left">
                        <?php echo date('d-M-Y : H:i:s',strtotime($r['timestamp'])); ?>
                </span>
                <a href="#" style="text-align:right" id="CID-<?php  echo $r['id'];?>" class="c_delete">Delete</a>
            </div>
        <?php } }  
    }
    
    public function get_access_list(){ 
        $qry = "select * from `ibug_tracker` where issue_id=  ".$_POST['ids']." ";        
        $tr=mysqli_query($this->_conn,$qry);
        $rst=mysqli_fetch_array($tr);    
        
        $qry_1 = "select user_name,themes from user_access ua, `user` u where ua.user_id=u.user_id and tag_name='".$rst["type"]."' and permission='read' "
               . " and user_name NOT IN('".$rst["owned_by"]."','".$rst["coor"]."','".$rst["approv_by"]."') order by user_name";        
        $tr_1=mysqli_query($this->_conn,$qry_1);        
        while($r=mysqli_fetch_array($tr_1)) {
            $arr_user[] = $r["user_name"]; 
            $theme = $r["themes"]; 
        }
        $users = implode(", ",$arr_user);            
        $text = '
                <div align="left" style="text-align:left;">
                    <ul>
                        <li><p>edit is restricted to users <b>'.$rst["owned_by"].'</b>,  <b>'.$rst["coor"].'</b>  and  <b>'.$rst["approv_by"].'</b>  (applies because user\'s is owner,coordinator and requester of iTask)</p></li>                         
                        <li><p>read is restricted to users '.$users.'  (applies because this iTask is in the "'.$theme.' theme")</p></li>     
                    </ul>
                </div>
                ';
        echo $text;
    }
    
    public function get_data(){           
        $id  = $_GET["elementid"];
        $id_ = substr($id,7); 
        $id_2 = substr($id,8);  
        if ( isset($_GET["2ndtheme"]) ) {
            $sql = "SELECT TM.theme as thm FROM ibug_tracker IB, team_org_matrix TM where IB.type1=TM.tag_name And IB.issue_id =".$id_2."";    
        }else {
            $sql = "SELECT TM.theme as thm FROM ibug_tracker IB, team_org_matrix TM where IB.type=TM.tag_name And IB.issue_id =".$id_."";
        }
        $rslt= mysqli_query($this->_conn,$sql);
        $res = mysqli_fetch_array($rslt);               
        $arr_theme[] = $res["thm"];
        $Or_cond = " ('0'='1' ";
        foreach ($arr_theme as $val_) {
            $Or_cond .=  ($val_=="")? " OR p_themes LIKE '".$val_."'" : " OR p_themes LIKE '%".$val_."%'";
        }
        $Or_cond .= ") ";          
        $sql_parent = "select * from `project_list` Where p_page_id>0 AND ".$Or_cond."order by p_page_id";	   
        //var_dump($sql_parent);     
        $res_parent = mysqli_query($this->_conn,$sql_parent);                
        $arr_parent = array();	                
        $arr_parent[""] = "None";
        while ($r = mysqli_fetch_array($res_parent)) {	                             
            $arrP = json_decode($r["p_parent_list"]); 
            $arrP_title = json_decode($r["p_parent_list_title"]);    
            $idx=0;        
            foreach ($arrP as $key=>$val) {
                //$arr_parent[$val."-".$r["p_title"]] = $val." : ".$r["p_title"];
                //$arr_parent[$val."-".$r["p_title"]] = $val." : ".substr($r["p_title"],0,20)."...";
                if ( ($arrP_title[$idx])!=="" ) {
                $arr_parent[$val."-".$r["p_title"]] = ($arrP_title)? substr($arrP_title[$idx],0,29)."..." : $val." : ".substr($r["p_title"],0,20)."...";
                //$arr_parent[$val."-".$r["p_title"]] = ($arrP_title)? substr($arrP_title[$idx],0) : $val." : ".substr($r["p_title"],0);
                }
                $idx++;
            }
        }                        
	echo json_encode($arr_parent);
    }    
    
}//END Class  