<?php

require_once('init_dir.php');

$request = explode('/', trim($_SERVER['REQUEST_URI']));  

require_once('/Local/'.$root_dir.'/'.$path_dir.'/Local.php');        
if ($wgDBserver) {
$host = explode(":",$wgDBserver); 
$connWiki_DB = mysqli_connect($host[0], $wgDBuser, $wgDBpassword, $wgDBname, $wgDBport)  or die( mysql_error() );
}    

$sql = "select type,owned_by,coor,approv_by,Issuerndfile from ibug_tracker Where Issuerndfile = ? ";                                                                                                                            
$stmt = $connWiki_DB->prepare($sql);
$stmt->bind_param('s', $request[3]);            
$stmt->execute();
$result = $stmt->get_result(); 
$stmt->close;                                                                         
while($rowdetail = $result->fetch_assoc()){                                                                  
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


$filepath = '/Local/'.$root_dir.'/'.$path_dir.'/'.$request[2].'/'.$request[3].'/'.urldecode($request[4]);
$filename = basename($filepath);
$len = filesize($filepath);
$file_extension = strtolower(substr(strrchr($filename,"."),1));

//This will set the Content-Type to the appropriate setting for the file
switch( $file_extension ) {
    case "pdf": $ctype="application/pdf"; break;
    case "exe": $ctype="application/octet-stream"; break;
    case "zip": $ctype="application/zip"; break;
    case "doc": $ctype="application/msword"; break;
    case "xls": $ctype="application/vnd.ms-excel"; break;
    case "ppt": $ctype="application/vnd.ms-powerpoint"; break;
    case "gif": $ctype="image/gif"; break;
    case "png": $ctype="image/png"; break;
    case "jpeg":
    case "jpg": $ctype="image/jpg"; break;
    case "mp3": $ctype="audio/mpeg"; break;
    case "wav": $ctype="audio/x-wav"; break;
    case "mpeg":
    case "mpg":
    case "mpe": $ctype="video/mpeg"; break;
    case "mov": $ctype="video/quicktime"; break;
    case "avi": $ctype="video/x-msvideo"; break;
    case "txt": $ctype="application/txt"; break;   

    //The following are for extensions that shouldn't be downloaded (sensitive stuff, like php files)
    case "php":
    // case "htm":
    // case "html":    
    die("<b>Cannot be used for ". $file_extension ." files!</b>"); break;

    default: $ctype="application/force-download";
}

header("Pragma: public");
header("Expires: 0");
header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
header("Cache-Control: public"); 
header("Content-Description: File Transfer");
header("Content-Type: $ctype");

readfile($filepath);