<?php
ini_set('max_execution_time', 36000);
error_reporting(0);
//$path= isset($_GET['local'])?$_GET['local']:"";       
//var_dump($argv[1]);
$path = $argv[1];
include  $path.'/Local.php';                
$Server= explode(":",$wgDBserver);                        
$conn = mysqli_connect($Server[0], $wgDBuser, $wgDBpassword, $wgDBname, $wgDBport)  or die(mysqli_error());          

$qry = "select * from email_queue where sent='0'";        
$GetQ=mysqli_query($conn,$qry);
    $qry1 = "Update email_queue set sent='1' where sent='0'";        
    mysqli_query($conn,$qry1);
while ($rst=mysqli_fetch_array($GetQ)) {
//echo  (base64_decode($rst[emailto]));
//var_dump(explode('^',base64_decode($rst[outputfile])));
$sent = sendmailNew($wgSMTP,base64_decode($rst[content]),base64_decode($rst[emailto]),base64_decode($rst[nameto]),base64_decode($rst[subjectto]),explode('^',base64_decode($rst[outputfile])));
if ($sent=="OK") { mysqli_query($conn,"Update email_queue set sent='1' where id=".$rst[id]); }     
}

function sendmailNew($wgSMTP,$content, $emailto, $nameto, $subjectto, $outputfile){
 if (!class_exists('PHPMailer')) {
            require_once dirname(__FILE__) . '/../PHPMailer5/class.phpmailer.php';   
 }
        if ($emailto==!'' || $emailto ==!Null) {                      
            $ecMail=new PHPMailer();
            $ecMail->IsSMTP();                                      // send via SMTP        
            $ecMail->SMTPAuth   = true;                             // enable SMTP authentication
            $ecMail->SMTPSecure = "ssl";                            // sets the prefix to the server
            $ecMail->Host = str_replace("ssl://", "",  $wgSMTP['host']);                      
            $ecMail->Port = 465;        
                $ecMail->Username = $wgSMTP['username'];                       // SMTP username
                $ecMail->Password = $wgSMTP['password'];                       // SMTP password
                $webmaster_email  = $wgSMTP['username'];                       //Reply to this email ID
                                           
            $ecMail->From = $webmaster_email;        
            $ecMail->FromName = $wgSMTP['username'];
            $ecMail->AddAddress($emailto,$nameto);
            $ecMail->WordWrap = 50; // set word wrap				
            $ecMail->IsHTML(true); // send as HTML
            $ecMail->Subject = $subjectto;
                                                        
                    foreach($outputfile as $value) { //loop the Attachments to be added ...
                    if ($value!=="") {$ecMail->AddAttachment($value);}           
                    }
            
                    $ecMail->Body = $content; //HTML Body
                    $ecMail->IsHTML(true); // send as HTML                    
                    if($ecMail->Send()) { 
                    echo $emailto."->sent";    
                    return "OK";    
                    }else {
                    echo "Send Email Failed";     
                    return "Send Email Failed";                     
                    }
        }
}