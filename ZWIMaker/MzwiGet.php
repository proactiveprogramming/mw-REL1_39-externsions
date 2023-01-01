<?php

# get ZWI by calling
# https://enhub.org/mediawiki/extensions/ZWIMaker/MzwiGet.php?title=Encycloreader
# ZWI fill show up in "tmp"
# Replace Encycloreader with any title
#

$title ="";
if (isset($_GET['title']))  $title =$_GET['title'];
$title=trim($title);
$title=str_replace(" ", "_", $title);

$actual_link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";

$url= str_replace("extensions/ZWIMaker/MzwiGet.php", "index.php", $actual_link ); 

$URL=$url . "&action=mzwi";
// Get ZWI file into tmp;
$ch=curl_init($URL);
curl_setopt_array($ch,array(
        CURLOPT_USERAGENT=>'Mozilla/5.0 (Windows NT 6.1; Win64; x64; rv:60.0) Gecko/20100101 Firefox/60.0',
        CURLOPT_ENCODING=>'gzip, deflate',
        CURLOPT_HTTPHEADER=>array(
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language: en-US,en;q=0.5',
                'Connection: keep-alive',
                'Upgrade-Insecure-Requests: 1',
        ),
));

# save to TMP
$fp = fopen ("./tmp/".$title.'.zwi', 'w+');
print("./tmp/".$title.'.zwi');


// give curl the file pointer so that it can write to it
curl_setopt($ch, CURLOPT_FILE, $fp);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

curl_exec($ch);
//done
curl_close($ch);
?>

