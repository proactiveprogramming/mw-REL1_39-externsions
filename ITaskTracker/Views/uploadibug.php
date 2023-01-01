<?php
        function simple_decrypt($ciphertext)
        {  
            //$salt="IbugMisKronos201";
            //return trim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256,  $salt, base64_decode($text), MCRYPT_MODE_ECB, mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_ECB), MCRYPT_RAND)));
            $cipher = "aes-128-gcm";
	    $original_plaintext = openssl_decrypt($ciphertext, $cipher, $key, $options=0, $iv, $tag);		
	    return $original_plaintext;
        }        
        
        if (isset($_GET['parentpath']) || isset($_POST['filelocation'])){
            $_filelocationEncrypted = (isset($_GET['parentpath'])) ? $_GET['parentpath'] : $_POST['filelocation'] ;
            ##var_dump($_GET['parentpath']);
            ##var_dump($_POST['filelocation']);
            $_filelocation = simple_decrypt($_filelocationEncrypted);
        } else if (!isset($_POST[btnyesAddFile]) && !isset($_GET['parentpath'])) {
            ##die ("Ibug Upload not Supported");
        }
        ##var_dump($_filelocation);
        
        if (isset($_GET['curhome'])){
            $curHome = $_GET['curhome'];
        } else {
            $curHome = $_POST['curhome'];
        }
        
	$filelimit=536870912; //500MB		
	if (!empty($_SERVER['HTTPS'])){
            $type = "https://";
        } else {
            $type = "https://"; 
        }
        
        if (!isset($_POST['returnurl'])){
            $url = $type.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'] ;
        } else {
            $url = $_POST['returnurl'];
        }
        
//	echo $url."<br />";
	$arrurl=(explode("?",$url));
	$arrpathurl = explode("/",$url);
        $arruploadurl = explode("/", $_filelocation);
        $_downloadfileurl="/".$curHome."/".$arruploadurl[count($arruploadurl)-2]."/";
        ##var_dump($_filelocation);
                
	if ($_GET[randfd]!=""){
                $randfile=$_GET['randfd'];
                $basepath=$_filelocation.$_GET['randfd'];
                $downloadpath=$_downloadfileurl.$_GET['randfd'];
        }
                else{
                $randfile=$_POST['randlocation'];
                $basepath=$_filelocation.$_POST['randlocation'];
                $downloadpath=$_downloadfileurl.$_POST['randlocation'];
        }
	
	$newurl= $arrurl[0].
                "?randfd=".$randfile.
                "&parentpath=".urlencode($_filelocationEncrypted).
                "&curhome=".$curHome;
	
	
	if ($_GET['numfileupload'] !=""){	
		$numupload=$_GET['numfileupload']*1;
	}
	else if ($_POST['NumUpload'] !=""){
		$numupload=$_POST['NumUpload']*1;
	}
	else{
		 $numupload=1;
	}
	//$maxupload=$_GET[numMaxfileupload]*1;
	if ($numupload==1){	
		$reduceurl= $newurl."&numfileupload=".($numupload);
	}
	else{
		$reduceurl= $newurl."&numfileupload=".($numupload-1);
	}
	$addurl= $newurl."&numfileupload=".($numupload+1);
        
        $errMessages=null;
	
if(isset($_POST[btnyesAddFile])){
	//echo $basepath;
	if(!is_dir($basepath)){
           #var_dump($basepath);
           mkdir($basepath);
        }
	
	$numuploadedfile=$_POST['NumUpload']*1;
		
	for($x=1;$x<=$numuploadedfile;$x++){
            $flvar="file".$x;
            #var_dump($_FILES);
            if ( $_FILES[$flvar]["name"]!=""){
                if (file_exists( $basepath."/".$_FILES[$flvar]["name"])){
                       $errMessages[]= "<span  style='font-size:90%;color:#f00;'><em>".$_FILES[$flvar]["name"]." already exists</em></span>";
                }
                else{
                        if (filesize($_FILES[$flvar]['tmp_name'])<$filelimit){
                                 $boolOutput = move_uploaded_file($_FILES[$flvar]["tmp_name"],$basepath."/".$_FILES[$flvar]["name"]);
                                 #var_dump($boolOutput);
                                 #var_dump($_FILES[$flvar]["tmp_name"]);
                        }
                        else{
                                $errMessages[]="<span  style='font-size:90%;color:#f00;'><em>File size larger than 5MB</em></span>";
                        }
                }

           }
	}
}

if ($_GET[del]!=""){
	$fileunlink = $basepath."/".base64_decode($_GET[del]);
	unlink("$fileunlink");
}

?>        

<?php

if ($randfile == ""){
	 $errMessages[]="<span  style='font-size:90%;color:#f00;'><em>File size larger than 500MB</em></span>";
}
else{
     #var_dump($basepath);
if(!is_dir($basepath)){
	echo "<span  style='font-size:95%;color:#999;'>No uploaded file</span>";
}
else{
		$outputfile=null;
		$outputdir=dir($basepath);
		$j=0;
                #var_dump($outputdir);
		while (false !== ($entry = $outputdir->read())) {
			if($entry != '.' && $entry != '..' && !is_dir($dir.$entry))
			{
				$outputfile[$j]=$entry;
				
				$j++;
			}
		}
		$outputdir->close();	
		
		
        if($j>0){
            for ($k=0;$k<$j;$k++)
            {
                if (!is_dir($downloadpath."/".$outputfile[$k])){ 
                        echo "<a href='".
                                $newurl.
                                "&del=".
                                urlencode(base64_encode($outputfile[$k]))."'>".
                                "<img src='/". $curHome."/img/remove.gif' alt='Delete File' />".
                                "</a>&nbsp;";
                        echo "<a href='".$downloadpath."/".rawurlencode($outputfile[$k])."' target='_blank'><img src='/". $curHome."/img/attachment.gif' title='".$outputfile[$k]."' alt='".$outputfile[$k]."' />".$outputfile[$k]."</a><br />";
                }
            }
        }
        else{
                 echo "<span  style='font-size:95%;color:#999;'>No uploaded file</span>";
        }
		
		
}
}
if (count($errMessages) > 0){
        echo implode('<br />', $errMessages);
        echo "<br />";
}

?>


<hr />
<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" enctype="multipart/form-data">
<span style="color:#fa4040;font-size:95%;">Maximum Upload File Size: 500MB </span>
<input type="hidden" name="NumUpload" value="<?php echo $numupload; ?>" >
<input type="hidden" name="randlocation" value="<?php echo $randfile; ?>" >
<input type="hidden" name="filelocation" value="<?php echo $_filelocationEncrypted; ?>" >
<input type="hidden" name="returnurl" value="<?php echo $url; ?>" >
<input type="hidden" name="curhome" value="<?php echo $curHome; ?>" >
<?php
	
    for ($i=1;$i<=$numupload;$i++){
            $dvname="dvfile".$i;
            $flname="file".$i;
            $txtflname="txtfile".$i;

           $strsty="style=display:inline-block;width:100%;";


            echo '<div '.$strsty.' id="'.$dvname.'"><input type="file" name="'.$flname.'" id="'.$flname.'" />';
            if ($i>1){
                     ?>
                            <a href="<?php echo $reduceurl; ?>">remove</a>
                    <?php
            }
            if ($i==$numupload && $numupload<=10){
                    ?>
                            <a href="<?php echo $addurl; ?>">add</a>
                    <?php	
            }

            echo '</div>';
    }
?>
					
									
<br />
<input type="submit" name="btnyesAddFile" value="Upload" />
</form>

