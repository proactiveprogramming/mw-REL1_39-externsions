<?php

/* Process submit request.
 * S.Chekanov
**/


if(isset($_POST["submit"]) == false && isset($_POST["download"]) == false ) die("Nothing to post");
if(isset($_POST["zwifile"]) == false) die("No ZWI name");
if(isset($_POST["posturl"]) == false) die("No post URL name");
if(isset($_POST["postkey"]) == false) die("No key for URL");
if(isset($_POST["zwititle"]) == false) die("No title");
if(isset($_POST["backlink"]) == false) die("No backlink");
if(isset($_POST["extpath"]) == false) die("No extension path");
if(isset($_POST["permission"]) == false) die("No permission is given");


//print("OK");
// 0 means nothing to be done
$Xsubmit=0;
if(isset($_POST["submit"]) == true) $Xsubmit=1; // send to network
if(isset($_POST["download"]) == true) $Xsubmit=2; // download 
if ($Xsubmit == 0) die("No action");


                            $zwifile=$_POST['zwifile'];
                            if (file_exists($zwifile)==false) die("ZWI file is missing: $zwifile");
                            $filePath=$_POST['zwifile'];
                            $target_url= $_POST['posturl'] . 'put.php';
                            $postkey=$_POST["postkey"];
                            $title=$_POST["zwititle"];
                            $backlink=$_POST["backlink"];
                            $extpath=$_POST["extpath"];
                            $permission=$_POST["permission"];


// download first
if ($Xsubmit == 2){
                    header('Content-Description: File Transfer');
                                        if (headers_sent()){
                                                echo ('Some data has already been output to browser, can\'t send ZWI file');
                                                exit();
                                        }


                       header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
                       header('Content-Type: application/force-download');
                       //header("Content-Transfer-Encoding: Binary"); 
                       header("Content-type: application/zip");
                       header("Content-disposition: attachment; filename=\"" . basename($zwifile) . "\"");
                       readfile($zwifile);
                       //unlink($zwifile); // delete file
                       //die();
                       //echo "<script type='text/javascript'>window.location.replace('" . $newlink . "');</script>";

}



// submit
if ($Xsubmit == 1){ 

			    $ch = curl_init();
                            curl_setopt($ch, CURLOPT_URL,$target_url);
                            curl_setopt($ch, CURLOPT_POST,true);
                            curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
                            //If the function curl_file_create exists
                            if(function_exists('curl_file_create')){
                              //Use the recommended way, creating a CURLFile object.
                              $filePath = curl_file_create($filePath);
                            } else{
                               //Otherwise, do it the old way.
                               //Get the canonicalized pathname of our file and prepend
                               //the @ character.
                               $filePath = '@' . realpath($filePath);
                               //Turn off SAFE UPLOAD so that it accepts files
                               //starting with an @
                               curl_setopt($ch, CURLOPT_SAFE_UPLOAD, false);
                            }

                             //Setup our POST fields
                             $postFields = array(
                               'zwi' => $filePath,
                               'pass' => $postkey
                             );
                             curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
                             $txt=curl_exec($ch);
                             curl_close($ch);
                             $pos1 = strpos($txt, "successfully");
                             $pos2 = strpos($txt, "updated");

			     if ($txt == false)
				     die("Failed to send the ZWI file. No server response?");
                             //$isOK=false;
                             //if ($pos1 !== false ) $isOK=true;
                             //if ($pos2 !== false ) $isOK=true;

                             //if ($isOK == false) 
                             //      die("Failed to send the ZWI file. No server response? File: ". $filePath. "mess:" . $txt);

                             $txt=nl2br($txt);
                             print($txt);


$str = <<<EOD
<!DOCTYPE html>
<html class="client-nojs" lang="en" dir="ltr">
<head>
<meta charset="UTF-8"/>
<title>ZWI confirm</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes, minimum-scale=0.25, maximum-scale=5.0"/>
<link rel="stylesheet" type="text/css" href="$extpath/css/zwimaker.css" />
</head>
<body>
<p>
</p>
<center>
<h3>'$title' submitted!</h3> 
<p>
</p>
Read this article in the Encyclosphere Network in 10 minutes using 
<a href="https://encycloreader.org/">EncycloReader</a>  created by the <a href='https://encyclosphere.org/about/'><img src='$extpath/img/Encyclosphere_logo24px.png' alt="Encyclosphere" style='vertical-align:middle;margin:0;'/>KSF</a> 
<p>
</p>
<form>
<input type="reset" name="reset" value="Back" onClick="window.location='$backlink';" />
</form>
<center>
</body>
</html>
EOD;
                              print($str);
                              //unlink($zwifile); // delete file

} // end submit







?>
