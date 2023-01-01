<?php

use MediaWiki\MediaWikiServices; 

error_reporting(E_ERROR | E_PARSE);

require("Html2Text.php");
require("ShortDescription.php"); 

class MzwiHooks {

	public static function onParserFirstCallInit( Parser &$parser ) {
		$parser->setFunctionHook( 'mzwitags', 'MzwiHooks::mzwitags_Render' );
		return true;
	}

       
	/**
	 *
	 * @param OutputPage $output
	 * @param Article $article
	 * @param Title $title
	 * @param User $user
	 * @param WebRequest $request
	 * @param MediaWiki $wiki
	 * @return boolean
	 */
	public static function onMediaWikiPerformAction( $output, $article, $title, $user, $request, $wiki ) {
		global $IP, $wgMzwiName, $wgVersion, $wgMzwiSubmit, $wgMzwiLicense, $wgMzwExcludedNamespaces, $wgMzwMinNumberOfWords, $wgMzwiAuthorApprove, $wgServer, $wgScriptPath, $wgMzwiSubmitUrl, $wgMzwiPassword, $wgUploadDirectory,$wgExtensionAssetsPath, $wgAutoloadClasses,$wgResourceModules; 
		// protection. Only users who can upload.
		if ($user->isAllowed('upload') == false ||  $user->isRegistered() == false) return;
		// mediawiki2dokuwiki
		// require_once "$IP/extensions/ZWIMaker/MediaWiki2DokuWiki/MediaWiki2DokuWiki.php";
		// require_once "$IP/extensions/ZWIMaker/ShortDescription.php";

                $maction=$request->getText( 'action' );
		if( $maction == 'mzwi') {


			$titletext = $title->getPrefixedText();
			$filename = str_replace( array('\\', '/', '*', '"', '<', '>', "\n", "\r", "\0" ), '_', $titletext );


                        $actual_link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
                        $newlink = str_replace("&action=mzwi", "", "$actual_link");

			// submit script
                        $ext_path = $wgExtensionAssetsPath . str_replace( "$IP/extensions", '', dirname( $wgAutoloadClasses[__CLASS__] ) );
                        $submit_script=$ext_path . "/" . "zwipush.php";


$BadArticle=false;
if(preg_match('(' .$wgMzwExcludedNamespaces .')', $titletext) === 1) { $BadArticle=true; } 

//if bad article, do nothing	
if ($BadArticle==true) { 
	
                       // disable output       
                        $output->disable();
	
$str = <<<EOD
<!DOCTYPE html>
<html class="client-nojs" lang="en" dir="ltr">
<head>
<meta charset="UTF-8"/>
<title>ZWI submit</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes, minimum-scale=0.25, maximum-scale=5.0"/>
<link rel="stylesheet" type="text/css" href="$ext_path/css/zwimaker.css" />
</head>
<body>
<p>
</p>
<center>
<h3>The article '$titletext' cannot be exported</h3>
Special pages in the namespaces $wgMzwExcludedNamespaces cannot be exported.
<p>
</p>
<input type="button" name="cancel" value="Back" onClick="window.location='$newlink';" />
</form>
<center>
</body>
</html>
EOD;
print($str);

return true;	
}; // end bad article



                        // fix: replace space with underscores.
                        $filename = str_replace(' ', '_', $filename);
                        $filename = str_replace('\'', '_', $filename);

			$filename=trim($filename);
			if (strlen($filename)<1) return;

                        //file_put_contents('/tmp/logfile', $filename); 

			//$output->setPrintable();
			$article->view();
			ob_start();
			$output->output();
			$articleHTML  = ob_get_clean();


                       // only body (Handwiki specific!)  
                        $xhtml="";
                        $start="<!-- == START-BODY == -->";
                        $end="<!-- == END-BODY == -->";
                        $ini = strpos($articleHTML, $start);
                        if ($ini >-1) {
                               $ini += strlen($start);
                               $len = strpos($articleHTML, $end, $ini) - $ini;
                               if ( $len>0)
                                        $xhtml=substr($articleHTML, $ini, $len);
                        } else {
                                 $xhtml=$articleHTML;};


                        // correct absolute links
                        $SITE_URL=$wgServer. "/wiki/";
                        $xhtml=str_replace("\"/wiki/", "\"". $SITE_URL, $xhtml);

			// see object title https://doc.wikimedia.org/mediawiki-core/master/php/classTitle.html
                        // https://doc.wikimedia.org/mediawiki-core/master/php/classWikiPage.html
                        // get content
                        $wikipage=WikiPage::factory( $title );

			//$pieces = explode(":", $stitle  );
			$revision = $wikipage->getRevisionRecord();
                        $author_name = ($revision->getUser())->getName();
			$content = $wikipage->getContent($revision);
			$articleWiki = ContentHandler::getContentText( $content );
			$namespace=$title->getNsText();


			// check if the article is too short
                        $NrOfWords=str_word_count($articleWiki, 0); 

//if bad article, do nothing    
if ($NrOfWords < $wgMzwMinNumberOfWords) {
                        // disable output       
                        $output->disable();

$str = <<<EOD
<!DOCTYPE html>
<html class="client-nojs" lang="en" dir="ltr">
<head>
<meta charset="UTF-8"/>
<title>ZWI submit</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes, minimum-scale=0.25, maximum-scale=5.0"/>
<link rel="stylesheet" type="text/css" href="$ext_path/css/zwimaker.css" />
</head>
<body>
<p>
</p>
<center>
<h3>The article '$titletext' cannot be exported</h3>
This article is too short. Number of words is $NrOfWords which is smaller than $wgMzwMinNumberOfWords .  
<p>
</p>
<input type="button" name="cancel" value="Back" onClick="window.location='$newlink';" />
</form>
<center>
</body>
</html>
EOD;
print($str);

return true;
}; // end bad article




                        //die($wgExtensionAssetsPath);
		        $path_dir = realpath($_SERVER["DOCUMENT_ROOT"]);
			$path = $path_dir . "/" . $wgExtensionAssetsPath . str_replace( "$IP/extensions", '', dirname( $wgAutoloadClasses[__CLASS__] ) );


			 $WIKDIR=$path . "/tmp";
                         $files = glob($WIKDIR."/*"); // get all file names
                         $now   = time();
                         foreach ($files as $file) {
                           if (is_file($file)) {
                                if ($now - filemtime($file) >= 3600 ) { // 1h old 
                                  unlink($file);
                                }
                           }
                        }

			// sudo apt-get install php-zip
                        $zipfilename=$WIKDIR."/".$filename.".zwi";
                        if (file_exists($zipfilename)==true) unlink($zipfilename);


			$zip = new ZipArchive();
                        if ($zip->open($zipfilename, ZipArchive::CREATE)!==TRUE) {
                        exit();
                        }
 
                        // remove styles
			$html_tmp=preg_replace('/(<(script|style)\b[^>]*>).*?(<\/\2>)/is', "$1$3", $xhtml);
			$html_tmp=strip_tags($html_tmp, '<b>');    // remove bold, so it will not move to upper case;
                        $html_tmp=strip_tags($html_tmp, '<i>'); // remove italic 
			$html2TextConverter = new \Html2Text\Html2Text($html_tmp);
                        $articleTXT = $html2TextConverter->getText();


         	       $zip->addFromString("article.wikitext", $articleWiki);
                       $zip->addFromString("article.txt", $articleTXT);
                       //if (strlen($xhtml)>10)  $zip->addFromString("article.xhtml", $xhtml);


		       $XLang=$title->getPageLanguage();
                       $Lang=$XLang->mCode;

                       //get all revisions
                       $services = MediaWikiServices::getInstance();
                       $revLookup = $services->getRevisionLookup();
                       //$rev = $services->getRevisionStore();
                       $rev = $revLookup->getRevisionByTitle( $title );
                       //$currentRevId = $rev->getId();

                       $rev_authors= array();
                       $revisions_array = array();
                       $time_created=MWTimestamp::convert( TS_UNIX, $revision->getTimestamp() );


		       $oldVersion = version_compare( $wgVersion, '1.34', '<=' );

		        // first try older versions
                        if ( $oldVersion ) { 
                          if ($title->estimateRevisionCount()>1) {
                            $firstRevision=$title->getFirstRevision();
                            $firstRevisionTimestamp=WTimestamp::convert( TS_UNIX, $title->getEarliestRevTime() );
                            $firstTitle = $firstRevision->getTitle();
                            $oldTitle=$firstTitle->getPrefixedText();
                            $contentFirst = $firstRevision->getContent( Revision::RAW );
                            $articleTextFirst = ContentHandler::getContentText( $contentFirst  );
                            $rkey=$firstRevisionTimestamp.".wikitext";
                            $rev_authors[] = ($firstRevision->getUser())->getName();
                            // original title and content
                            if (strlen($namespace)>0)
                               $zip->addFromString("data/attic/".$namepsace."/".$rkey, $articleTextFirst);
                            else
                                $zip->addFromString("data/attic/".$rkey, $articleTextFirst);
                             };

			// new Mediawiki installs 1.35 and above 
                        }  else {


		       while ( $rev ) 
		       {
                         //echo("OK"); 
        		 $rev = $revLookup->getPreviousRevision( $rev );
			 if ($rev == null) break; 
			 $content_old = $wikipage->getContent($rev);
                         $articleWiki_old = ContentHandler::getContentText( $content_old );
			 $old_time=MWTimestamp::convert( TS_UNIX, $rev->getTimestamp() );
                         $time_created=MWTimestamp::convert( TS_UNIX, $old_time ); 
			 $rkey=$old_time.".wikitext";
                         $rev_authors[] = ($rev->getUser())->getName();
			 $revisions_array[$rkey] = ($rev->getUser())->getName(); 
                         //$sub_array["rev".$n] = $rkeymple1;
			 $zip->addFromString("data/attic/".$rkey, $articleWiki_old );
			}
                        //die("done");

		        }; // done with version check 


                        // get description
           		//$description=""; // getDescription($articleWiki,$articleTXT);
		        $DESC = new ShortDescription($articleWiki,$articleTXT);
                        $description=$DESC->getDescription();


                        $author="";
                        if (preg_match('/{{Author\|(.*?)}}/', $articleWiki, $match) == 1) {
                            $author=$match[1];
                         }

		         
                        // processing LaTeX directory
                        /*
  		        $LATEXDIR="/var/www/html/handwiki/public_html/latex";
                        $inputfile=$LATEXDIR.'/tmp/'.$filename.".wiki";
                        file_put_contents($inputfile, $articleWiki);
                        $LAToutput = shell_exec($LATEXDIR.'/A_RUN '. $inputfile);
                        $zip->addFromString("article.tex", $LAToutput);
                        */


                        // to Dokuwiki converter
		        //$MW2DK = new MediaWiki2DokuWiki($articleWiki);
                        //$dokuwiki=$MW2DK->convert();
                        //$zip->addFromString("article.dokuwiki", $dokuwiki);


			$creator_name=array();
			//$user_creator=$wikipage->getCreator();
			//$user_creator->getName();
                        //$creator_name[0]=$user_creator;
                        $creator_name[]=$rev_authors[ count($rev_authors)-1]; //  = ($rev->getUser())->getName();


		        $full_url=$title->getCanonicalURL();
                        $endPoint = str_replace($titletext, "api.php", $full_url);
                        //$endPoint = str_replace("\\", "", $endPoint);


	$zwiImages= array();
		
        // add styles
        $zip->addFile($path.'/css/common.css',  "data/css/common.css");
        $zip->addFile($path.'/css/poststyle.css',  "data/css/poststyle.css");
        $zip->addFile($path.'/css/darkmode.css',  "data/css/darkmode.css");
        $zip->addFile($path.'/css/design.css',  "data/css/design.css");

        $zwiImages['data/css/common.css'] = sha1_file($path.'/css/common.css');
        $zwiImages['data/css/poststyle.css'] = sha1_file($path.'/css/poststyle.css');
        $zwiImages['data/css/darkmode.css'] = sha1_file($path.'/css/darkmode.css');
        $zwiImages['data/css/design.css'] = sha1_file($path.'/css/design.css');
		
	// extract images	
	$doc = new DOMDocument();
        $doc->loadHTML($xhtml);
        $tags = $doc->getElementsByTagName('img');
        foreach ($tags as $tag) {
      
          $imagepath_array= array(); 
	  $imagepath_array[]=$tag->getAttribute('src');
          $imagepath_array[]=$tag->getAttribute('srcset');
          $imagepath_array[]=$tag->getAttribute('data-srcset');
          $imagepath_array[]=$tag->getAttribute('data-src');

          // collect all images
	  foreach ($imagepath_array as $imagepath) {
          if ($imagepath != null) {		  
	   if (strlen($imagepath)>1) {
	      $full_image_path=$path_dir . $imagepath;
	      $img = basename($imagepath);
              if (file_exists($full_image_path)) {
                 $in_img="data/media/images/".$img; 
                 $zip->addFile($full_image_path,  $in_img);
	         $zwiImages[$in_img] = sha1_file($full_image_path);
                 $img_replace[$imagepath]=$in_img;
               }
            }
           }
	  } // end of possible src sets 
	} // end image loops 


	 //print_r($img_replace);
         //die();

         // correct image src links 
         foreach ($img_replace as $key => $value) {
                     $xhtml = str_replace($key, $value, $xhtml); 
		     //echo "{$key} => {$value} ";
         }


       // all images: $allimages
       $zip->addFromString("media.json", json_encode($zwiImages,JSON_PRETTY_PRINT));

$header = <<<EOD
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="KSF">
<title>$titletext</title>
<!-- Bootstrap core CSS -->
<link rel="stylesheet" type="text/css" href="data/css/common.css">
<link rel="stylesheet" type="text/css" href="data/css/darkmode.css">
<link rel="stylesheet" type="text/css" href="data/css/poststyle.css">
<link rel="stylesheet" type="text/css" href="data/css/design.css">

</head>

<body>

<!-- BEGIN BODY -->

<div class="container-fluid">
EOD;


$footer = <<<EOD
</div>

<!-- END BODY -->

</body>
<script src="https://polyfill.io/v3/polyfill.min.js?features=es6"></script>
<script id="MathJax-script" async src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-mml-chtml.js"></script>
<script type="text/x-mathjax-config">
MathJax.Hub.Config({
  tex2jax: {inlineMath: [ ['$','$'] ]}
});
</script>
<script>
MathJax = {
  tex: {
    inlineMath: [ ['[math]', '[/math]'] ]
  }
};
</script>
</html>
EOD;

        // add header and footer
	$main_html=$header . $xhtml . $footer;

	$zip->addFromString("article.html", $main_html);
  

                        $content_array= array();
                        $content_array["article.wikitext"]=sha1($articleWiki); //$revision->getSha1();
                        $content_array["article.html"]=sha1($main_html);
                        $content_array["article.txt"]=sha1($articleTXT);

                        $tt=array();
                        $tt['ZWIversion'] = 1.3;
                        $tt['Primary'] = "article.wikitext";
                        $tt['Title'] =$title->getBaseText();
                        //$tt['TitleFull'] = $title; 
                        //$tt['NS'] = $title->getNamespace();
                        $tt['Content'] = $content_array;
                        $tt['Namespace'] = $title->getNsText();
                        $tt['Publisher']=$wgMzwiName;
                        $tt['TimeCreated'] = MWTimestamp::convert( TS_UNIX, $time_created );
                        $tt['LastModified'] = MWTimestamp::convert( TS_UNIX, $revision->getTimestamp() );
                        if (isset($firstRevisionTimestamp)) $tt['TimeFirstCreated'] = $firstRevisionTimestamp;
                        $tt['RevisionsCount'] = $title->estimateRevisionCount ();
                        $tt['Revisions'] = $revisions_array;
                        //$tt['EditNotices'] = $title->getEditNotices (); 
                        //$tt['UserID'] = $article->getUser(); 
                        // who downloaded?
                        //$tt['UserName'] = $author_name; // $user->getName();
                        //$tt['UserRealName'] =  $user->getRealName(); 
                        // who created?
                        $tt['CreatorNames'] = $creator_name;
                        //$tt['CreatorRealName'] = $user_creator->getRealName();
                        //$tt['Sha1'] = $revision->getSha1();
                        $tt['ContributorNames'] = array_unique($rev_authors); // $wikipage->getContributors();
                        $tt['GeneratorName'] = "MediaWiki";
                        $tt['SourceURL'] = $full_url;
                        // $tt['LocalURL'] = $endPoint;
                        $tt['Lang'] = $Lang;
                        $tt['Comment'] = $title->getEditNotices ();
                        $tt['Rating'] = array("0","0");
                        $tt['License'] = $wgMzwiLicense;
                        $tt['Description']=$description;
                        $tt['Author']=$author;
                        $tt['PublicationDate']="";

                        // add metadata
                        $zip->addFromString("metadata.json", json_encode($tt,JSON_PRETTY_PRINT));
                        // $zip->addFromString("metadata.json", json_encode($tt));




			$zip->close();	
		
	                // disable output	
			$output->disable();


	     // trigger download for this action
             if( $wgMzwiSubmit == 0) {

                     // disable output       
                     //$output->disable();

		     header('Content-Description: File Transfer');
                                        if (headers_sent()){
                                                echo ('Some data has already been output to browser, can\'t send ZWI file');
                                                exit();
                                        }


   		       header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
                       header('Content-Type: application/force-download');
                       //header("Content-Transfer-Encoding: Binary"); 
                       header("Content-type: application/zip"); 
		       header("Content-disposition: attachment; filename=\"" . basename($zipfilename) . "\""); 
                       readfile($zipfilename);
			 }; 
		

		 // submit to Encyclosphere network	
	         if( $wgMzwiSubmit == 1) {

			 $permissionErr=0;
			 if ($user->isAllowed('upload') == false ||  $user->isRegistered() == false) {
         		    //echo "<script type='text/javascript'>alert('You are not registered user and cannot submit this article');</script>";
                            //echo "<script type='text/javascript'>window.location.replace('" . $newlink . "');</script>";
                            $permissionErr=1;
			    //return true;
			 }



			  // Ok, you are registered, but did you contribute to it?
			  if ($wgMzwiAuthorApprove == true) {
         		    if ($permissionErr == 0) {
  			    $canSubmit=false;
			    foreach ($rev_authors as $contributor) {
                                  //print($contributor . " ->  " . $user->getName() . "<br>"); 
				  if ($contributor == $user->getName()) $canSubmit=true;
		            };

                           if ( $canSubmit == false ) {
                             //echo "<script type='text/javascript'>alert('You did not contribute to this article and cannot submit it.');</script>";
                             //echo "<script type='text/javascript'>window.location.replace('" . $newlink . "');</script>";
                             //return true;
                             $permissionErr=2;
			   }; 
			  };
                         }; // only author can send it



                         // file exists? push!
                         if (file_exists($zipfilename)==true) {


$xsub="";
if ($permissionErr ==0)
        $xsub="<input type=\"submit\" name=\"submit\" value=\"Submit to the Encyclosphere Network\" />"; 
else if ($permissionErr == 1)
        $xsub="<input type=\"submit\" name=\"submit\" value=\"Submission to the Encyclosphere is disabled\"  readonly=\"readonly\"  onfocus=\"this.blur();\"  disabled/> <br> (not registered user)";
else if ($permissionErr == 2)
        $xsub="<input type=\"submit\" name=\"submit\" value=\"Submission to the Encyclosphere is disabled\"   readonly=\"readonly\"   onfocus=\"this.blur();\"  disabled/> <br> (did not contribute to this article)";


	
$str = <<<EOD
<!DOCTYPE html>
<html class="client-nojs" lang="en" dir="ltr">
<head>
<meta charset="UTF-8"/>
<title>ZWI submit</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes, minimum-scale=0.25, maximum-scale=5.0"/>
<link rel="stylesheet" type="text/css" href="$ext_path/css/zwimaker.css" />
</head>
<body>
<p>
</p>
<center>
<h3>Export the article '$title' ?</h3>  
<p>
</p>
<form action="$submit_script" method="post">
$xsub
<br>Submit this article to the Encyclosphere Network 
that can be viewed in <a href="https://encycloreader.org/">EncycloReader</a> supported by <a href='https://encyclosphere.org/about/'><img src='$ext_path/img/Encyclosphere_logo24px.png' alt="Encyclosphere" style='vertical-align:middle;margin:0;'/>KSF</a> 
<p></p> 
<input type="submit" name="download" value="Download" />  
<br>Save this article to your computer
<p></p>
<input type="button" name="cancel" value="Cancel" onClick="window.location='$newlink';" />
<input type="hidden" id="zwititle" name="zwititle" value='$titletext'  >
<input type="hidden" id="zwifile" name="zwifile" value='$zipfilename' >
<input type="hidden" id="posturl" name="posturl" value='$wgMzwiSubmitUrl' >
<input type="hidden" id="postkey" name="postkey" value='$wgMzwiPassword'  >
<input type="hidden" id="backlink" name="backlink" value='$newlink'  >
<input type="hidden" id="extpath" name="extpath" value='$ext_path'  >
<input type="hidden" id="permission" name="permission" value='$permissionErr'  >
</form>
<center>
</body>
</html>
EOD;
                            print($str);
                            return true;

                       };

		 } // end push to network 

			 // just create file in tmp and do nothing
                         if( $wgMzwiSubmit == 2) {

			 if (file_exists($zipfilename)==true) {
 			       echo "ZWI file /extensions/ZWIMaker/tmp/".$title.".zwi was created"; 
			   } 
   			   //echo "<script type='text/javascript'>window.location.replace('" . $newlink . "');</script>";
                           return true;
			 } 



		} // end actions 


		return true;
	}


	/**
	 * Add ZWI to actions tabs in MonoBook based skins
	 * @param Skin $skin
	 * @param array $actions
	 *
	 * @return bool true
	 */
	public static function onSkinTemplateTabs( $skin, &$actions ) {
		global $wgMzwiTab;

		if ( $wgMzwiTab ) {
			$actions['mzwi'] = array(
				'class' => false,
				'text' => wfMessage( 'mzwi-action' )->text(),
				'href' => $skin->getTitle()->getLocalURL( "action=mzwi" ),
			);
		}
		return true;
	}


	/**
	 * Add ZWI to actions tabs in vector based skins
	 * @param Skin $skin
	 * @param array $actions
	 *
	 * @return bool true
	 */
	public static function onSkinTemplateNavigation( $skin, &$actions ) {
		global $wgMzwiTab;

		if ( $wgMzwiTab ) {
			$actions['views']['mzwi'] = array(
				'class' => false,
				'text' => wfMessage( 'mzwi-action' )->text(),
				'href' => $skin->getTitle()->getLocalURL( "action=mzwi" ),
			);
		}
		return true;
	}

	/**
	 * @param $parser Parser
	 * @return mixed
	 */
	public static function mzwitags_Render( &$parser ) {
		// Get the parameters that were passed to this function
		$params = func_get_args();
		array_shift( $params );

		// Replace open and close tag for security reason
		$values = str_replace( array('<', '>'), array('&lt;', '&gt;'), $params );

		// Insert mzwi tags between <!--mzwi ... mzwi-->
		$return = '<!--mzwi';
		foreach ( $values as $val ) {
			$return.="<".  $val ." />\n";
		}
		$return .= "mzwi-->\n";

		//Return mzwi tags as raw html
		return $parser->insertStripItem( $return, $parser->mStripState );
	}

}
