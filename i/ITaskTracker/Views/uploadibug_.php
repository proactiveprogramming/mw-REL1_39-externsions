<?php

require_once( dirname(dirname(__FILE__)) . '/etc/init_dir.php' );

$wgFileExtensions = array('skp','dwg','png', 'gif', 'jpg', 'jpeg', 'doc', 'xls', 
                          'pdf', 'ppt', 'tiff', 'bmp', 'docx','vsd', 
                          'xlsx', 'pptx','pptm', 'ps', 'svg', 'zip','war', 
                          'rar', 'txt', 'dll', 'iuml', 'cpp', 'dll', 'gz', 'wmv','avi','mp4','mov','m4v','flv','psd','odg','csv','html','cdr','sh','html','ai','xlsb');

require_once('/Local/'.$root_dir.'/'.$path_dir.'/Local.php');        
if ($wgDBserver) {
$host = explode(":",$wgDBserver); 
$connWiki_DB = mysqli_connect($host[0], $wgDBuser, $wgDBpassword, $wgDBname, $wgDBport)  or die( mysql_error() );
}    

$sql = "select type,owned_by,coor,approv_by,Issuerndfile from ibug_tracker Where issue_id = ? ";                                                                                                                            
$stmt = $connWiki_DB->prepare($sql);
$stmt->bind_param('s', $_GET["id"]);            
$stmt->execute();
$result = $stmt->get_result(); 
$stmt->close;                                                                         
while($rowdetail = $result->fetch_assoc()){                                                              
    $Issuerndfile = $rowdetail["Issuerndfile"];  
    $theme1 = $rowdetail["type"];  
    $arr_user[] = $rowdetail["owned_by"];     
    $arr_user[] = $rowdetail["coor"];     
    $arr_user[] = $rowdetail["approv_by"];               
}   

$sql = "select U.user_name from user_access UA, user U Where UA.user_id=U.user_id And UA.permission='edit' And UA.tag_name = ? ";                                                                                                                            
$stmt = $connWiki_DB->prepare($sql);
$stmt->bind_param('s', $theme1);            
$stmt->execute();
$result = $stmt->get_result();      
$stmt->close;                                                                    
while($rowdetail = $result->fetch_assoc()){                                                                
    $arr_user[] = $rowdetail["user_name"];                       
}   

//var_dump($_COOKIE["wiki_user"]);
//var_dump($arr_user);die;
if (!in_array($_COOKIE["wiki_user"], $arr_user)) {        
        $res['success'] = false;
        $error_msg = "<span style='font-size:100%;color:#f00;'><em>Access isn't granted to you !</em></span>";
        $res['message'] = $error_msg;                
        echo json_encode($res);
        return;              
}           

$filelimit = 536870912; //500MB	
$upload_path = '/Local/'.$root_dir.'/'.$path_dir.'/ITaskTrackerUploads/' . $Issuerndfile . '/' . $_FILES['file']['name'];
$outputfile = $_FILES['file']['name'];
$downloadpath = "/".$curHome."/ITaskTrackerUploads/".$Issuerndfile;
$path_ = '/Local/'.$root_dir.'/'.$path_dir.'/ITaskTrackerUploads/';
$dir = $path_ . $Issuerndfile . '/';
$newurl = "extensions/ITaskTracker/Views/uploadibug_.php?randfd=".$Issuerndfile;       
$ajax_del = 'onclick="return del_file(this)"';

//Delete file//
if ($_GET['del']!=""){
        $fileunlink = $path_.$_GET['randfd']."/".base64_decode($_GET['del']);        
        unlink("$fileunlink");
        $res['success'] = true;
        echo json_encode($res);
        return;
}
$ext_ = pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);

if (!(in_array(strtolower($ext_), $wgFileExtensions))) {
        $res['success'] = false;
        $res['message'] = "<span style='font-size:100%;color:#f00;'><em>File type/extension isn't allowed</em></span>";
        echo json_encode($res);
        return;
}

$k = -2;
if (is_dir($dir)){
        if ($dh = opendir($dir)){
                while (($file = readdir($dh)) !== false){
                //echo "filename:" . $file . "<br>";
                $k++;
                }
                closedir($dh);
        }
}

$link_ = "<div id='file".$k."'><a href='".$newurl."&id=".$_GET["id"]."&fileid=".$k."&del=".
                urlencode(base64_encode($outputfile))."' ".$ajax_del.">".
                "<img src='/". $curHome."/img/remove.gif' alt='Delete File' />".
                "</a>&nbsp;";
$link_ .= "<a href='".$downloadpath."/".rawurlencode($outputfile)."' target='_blank'><img src='/". $curHome."/img/attachment.gif' title='".$outputfile."' alt='".$outputfile."' />".$outputfile."</a><br /></div>";

try {
        if (
                !isset($_FILES['file']['error']) ||
                is_array($_FILES['file']['error'])
        ) {
                throw new RuntimeException("<span style='font-size:100%;color:#f00;'><em>Upload failed - Invalid configuration.</em></span>");
        }
        
        switch ($_FILES['file']['error']) {
                case UPLOAD_ERR_OK:
                break;
                case UPLOAD_ERR_NO_FILE:
                throw new RuntimeException("<span style='font-size:100%;color:#f00;'><em>No file sent.</em></span>");
                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:
                throw new RuntimeException("<span style='font-size:100%;color:#f00;'><em>File size larger than 500 MB</em></span>");
                default:
                throw new RuntimeException("<span style='font-size:100%;color:#f00;'><em>Unknown errors.</em></span>");
        }
        
        if ($_FILES['file']['size'] > $filelimit ) {  
                $res['success'] = false;                              
                throw new RuntimeException("<span  style='font-size:100%;color:#f00;'><em>File size larger than 500 MB</em></span>");
        }        

        if(!is_dir($dir)){        
        mkdir($dir);
        }

        if (!file_exists($upload_path)) {
                $Up_ = move_uploaded_file($_FILES['file']['tmp_name'], $upload_path);                
                //var_dump($Up_);die;
        }else{
                throw new RuntimeException("<span  style='font-size:100%;color:#f00;'><em>".$outputfile." already exists</em></span>");
        }

        $res['success'] = true;
        $res['link_'] = $link_; 

  } catch (RuntimeException $e) {

        $res['message'] = $e->getMessage();             

  }

  echo json_encode($res);