<?php
/******************
 *  variable Extension - this extension is an QBox Training extention
 *  Copyright (C) 2010 qbox4u.com <qbox4u@gmail.com> 
 *
 *  This program is not free software therefore you can-not redistribute 
 *  it and/or modify it under the terms of the GNU General Public License 
 *  as published by the Free Software Foundation; either version 2 of the 
 *  License, or (at your option) any later version.
 *
 *  Please consult and/or request the administrator of qbox4u@gmail.com
 *  to use the information and samples
 *
 *  To copy the data an written autorisation of the developer as stated in 
 *  the $wgExtensionCredits is required 
 * 
 *  You should have received a copy of the GNU General Public License along
 *  with this program; if not, write to the Free Software Foundation, Inc.,
 *  59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 *  http://www.gnu.org/copyleft/gpl.html
 *
 *  To activate this extension, add the following into your LocalSettings.php file:
 *  require_once("$IP/extensions/FlexImage/FlexImage.php");
 *
 *  @ingroup Extensions
 *  @author Jan boer <qbox4u@gmail.com>
 *  @LinkedIn https://www.linkedin.com/in/jan-boer-a24640113
 *  @version 1.0
 *  @link http://QBox4u.com
 *
 */ 
 
/**********************************
 * ID		: NA
 * Release	: NA 
 * Date		: Created 26-10-2016 by JBoe
 * Notes	: Impementation of FlexImage
 *
 * Purpose	: To retrieve the default parameters used inside the extension
 * Info		: http://php.net/manual/en/function.require-once.php	
 * Function	: Incude parameters 
 * Input	: <FlexImage.inc.php> 	data file 
 *			  <$IP>					Base impelmentation path of QBox mediawiki
 * Output	: Success ==>   
 *	     	  Failure ==>  
 * Error	:
 * Example	:      
 * Implementation :   
 *  
 */
require_once("$IP/extensions/FlexImage/FlexImage.inc.php");

 /**********************************
 * ID		: NA
 * Release	: NA 
 * Date		: Created 26-10-2016 by JBoe
 * Notes	: Impementation of FlexImage 
 *
 * Purpose	: To Protect against register_globals vulnerabilities.
 * Info		: 	
 * Function	: If the global constant 'MEDIAWIKI' is not defined, Quit the application
 * Input	: <constant:MEDIAWIKI> 	data file 
 * Output	: Success ==>  None 
 *	     	  Failure ==>  Echo an error text to the user
 * Error	: 
 * Example	:      
 * Implementation :   
 *  
 */
if( !defined( 'MEDIAWIKI' ) )  {
	echo( "This is an extension to the MediaWiki package and cannot be run standalone.<br>\n" );
	echo( "Use this php file only locally on the QNAP TS-459 proII server  <br>\n" );
	echo( "****************************   WARNING  WARNING  WARNING ******************************<br>\n" );
	echo( "This is an restricted application only tested for application purposes inside QBox4u.com<br>\n" ); 
	echo( "This application wil malfunction if illegal and/or unautorised access has been detected \n" );
	die( -1 );
}
	
/**********************************
 * ID		: $wgExtensionCredits
 * Release	: NA 
 * Date		: Created 26-10-2016 by JBoe
 * Notes	: Impementation of FlexImage 
 *
 * Purpose	: To identify basic public information regarding the extension.
 *			  Extension credits that will show up on Special:Version
 * Info		: https://www.mediawiki.org/wiki/Manual:$wgExtensionCredits	
 * Function	: Fill the $wgExtensionCredits array with appropiate data 
 * Input	: $wgExtensionCredits['other']	
 *
 * Output	: Success ==>  None 
 *	     	  Failure ==>  Echo an error text to the user
 * Error	: 
 * Example	:      
 * Implementation :   
 *  
 */ 
$wgExtensionCredits['other'][] = array(
	'path'           => __FILE__,
	'name'           => 'Flexible image',
	'description'    => 'Flexible image extension by the tag <code>&#123;&#123;&#35;Fimg:<File name>&#125;&#125;</code><br>Dynamically adapt the image size to the window',
	'version'        => '1.0',
	'author'         => array( 'https://www.linkedin.com/in/jan-boer-a24640113','qbox4u@gmail.com'), 
	'url'            => 'https://qbox4u.com:8081/conf/abc/mwk/index.php?title=FlexImage_extension_demo_page',
	'license-name' 	 => 'Private',
);

# Keep i18n globals so mergeMessageFileList.php doesn't break
# Location of the localisation files ( Tell MediaWiki to load them )
$wgMessagesDirs['FlexImage'] 	= __DIR__ . '/i18n';

# Location of the aliases file (Tell MediaWiki to load it)
$wgExtensionMessagesFiles['FlexImageAlias'] = __DIR__ . '/FlexImage.alias.php';

# Location of the SpecialMyExtension class (Tell MediaWiki to load this file)
$wgAutoloadClasses['SpecialFlexImage'] = __DIR__ . '/SpecialFlexImage.php';

# Tell MediaWiki about the new special page and its class name 
$wgSpecialPages['FlexImageAdmin'] = 'SpecialFlexImage';

# Add an new permissions constant for user and user groups
$wgAvailableRights[] = 'FlexImage_New_Rights';
# Set the appropiate new rights for users and user groups
$wgGroupPermissions['*']['FlexImage_New_Rights']				= false;
$wgGroupPermissions['sysop']['FlexImage_New_Rights']			= true;
$wgGroupPermissions['bureaucrat']['FlexImage_New_Rights']		= true;
$wgGroupPermissions['autoconfirmed']['FlexImage_New_Rights']	= true;

/**
 * Avoid unstubbing $wgParser too early on modern (1.12+) MW versions, as per r35980
 * Define a setup function
 *
 **/
if ( defined( 'MW_SUPPORTS_PARSERFIRSTCALLINIT' ) ) {
	$wgHooks['ParserFirstCallInit'][] 	= 'wf_QBox_FlexImage_ParserFunction_Setup';} 
     else {
	 $wgExtensionFunctions[] 		= 'wf_QBox_FlexImage_ParserFunction_Setup';}

/**
 * Add a hook to initialise the magic word
 *
 **/
$wgHooks['LanguageGetMagic'][]					= 'wf_QBox_FlexImage_Magic';
$wgHooks['EditPage::showEditForm:initial'][] 	= 'wf_QBox_FlexImage_EditPreview' ;

function wf_QBox_FlexImage_EditPreview( &$editPage, $output ) {
		$p_text = $editPage->textbox1; // also we have $editPage->textbox2;
		//print ( '<pre>' )  ;print_r($p_text);print ( '</pre>' )  ;
		//echo $p_text->textbox1;
		$sp_pos = strpos($p_text,'{{#Fimg:');
		if ($sp_pos){
			$editPage->editFormTextTop .= "<span style='color:red'>Warning, you are using the display flexible image extension {{#Fimg: }} External images are copy writed !!!!!!</span>";
			}
return true;	
}

function wf_QBox_FlexImage_ParserFunction_Setup( &$Parser ) {

    # Set a function hook associating the 'QBox_DButility_hook' magic word with our function 
	$Parser->setFunctionHook( 'Fimg', 'wf_QBox_FlexImage_ParserFunction_Render' );
	return true;
}

/**
 * Add the magic word
 * The first array element is whether to be case sensitive
 *  in this case 
 *   0 it is not case sensitive, 
 *   1 would be sensitive
 * All remaining elements are synonyms for our parser function
 *
 **/
function wf_QBox_FlexImage_Magic( &$magicWords, $langCode ) {

        $magicWords['Fimg'] = array( 0, 'Fimg' );
        # unless we return true, other parser functions extensions won't get loaded.
        return true;
}

/******************
 * Add the required CSS HTML
 *
 */
function wf_QBox_FlexImage_add_BeforeHTML(&$out ,&$text ) {
  global $wgScriptPath,$wgServer;

	$Submitted_page_text = $text;
	//exampe §§startabcfleximg§§https://qbox4u.com:8081/conf/tech/mwk/images/4/40/Degree_02.png§§endabcfleximg§§
	
	$patterns = array();
	$patterns[0] = '/§§startabcfleximg§§/';
	$patterns[1] = '/§§endabcfleximg§§/';
	$patterns[2] = '/§§startabcextfleximg§§/';
	$patterns[3] = '/§§endabcextfleximg§§/';
	$patterns[4] = '/§§startabcextfleximglinkb§§/';
    $patterns[5] = '/§§endabcextfleximglinkb§§/';

	$replacements = array();
	$replacements[0] = "<div>\n<img class='fleximg' alt  src='";
	$replacements[1] = "' />\n</div>";	
	$replacements[2] = "<div>\n<img class='fleximg' alt  src='";
	$replacements[3] = "' />\n</div>";
	$replacements[4] = "<a href='";
	$replacements[5] = "'>";


	$text = preg_replace($patterns, $replacements, $Submitted_page_text);
    //$text = $Submitted_page_text;
	

  return $out;
}  

/**
 * Modify the flex image using class='fleximg' inside the MediaWiki:Common.css
 *
 * Add this CSS inside your MediaWiki:Common.css
  **/
/* 
 * Auto Resizable images by eg {{#Fimg:Degree_02.png}}  
 *.fleximg { 
 *    max-width: 100%; 
 *    height: auto; 
 *    width: auto\9; 
 *   }
 *
 **/
$wgHooks['OutputPageBeforeHTML'][]	= 'wf_QBox_FlexImage_add_BeforeHTML';


/**
 * Create an demo page to understand the function
 * Note Only when an autorised user is logged in
  **/

$wgHooks['BeforePageDisplay'][] = 'wf_QBox_FlexImage_autoCreateDemoPage';

function wf_QBox_FlexImage_autoCreateDemoPage() {
	global 	$wf_QBox_FlexImage_DemoPage,
			$wgUser,
			$wf_QBox_FlexImage_DemoPage,
			$wf_QBox_FlexImage_Demo_Template;

	$titleObject = Title::newFromText( 'FlexImage extension demo page' );
	if ( $titleObject->exists() ) {} // i found the FlexImage extension demo page.
		else {
				// There is no page with the name FlexImage extension demo page . 
				// Therefore create all FlexImage related pages
				if ( $wgUser->isAllowed( 'createpage' ) ) {

					$FlexImage_Pages = array(
						'FlexImage extension demo page'	=>$wf_QBox_FlexImage_DemoPage,
						'Template:QPE'					=>$wf_QBox_FlexImage_Demo_Template
						);

					foreach ($FlexImage_Pages as $Page_Name =>$Page_Content) {
						$newtitle = $Page_Name;
						$newarticle = new Article(Title::newFromText($newtitle), 0);
						$newarticle->doEdit($Page_Content, EDIT_UPDATE);
						}	
				   } 
				else 
					{ }
			}
	return ;
}


/******************************************************************************
 ******************   	MAIN QBox_DButility TAG BODY  		***************
 ******************************************************************************
 *
 * The parser function itself for the hook 
 * The input parameters are wikitext with templates expanded
 *
 ******************************************************************************
 *
 * Function: wf_QBox_DButility_ParserFunction_Render( &$parser)
 *
 * NOTES   :	The input parameters are wikitext with templates expanded
 * Purpose : 	Operational part of the extention hook in your wiki
 * Input   : 	{{#Fimg:Degree_02.png}}
 * Output  :    an internal image URL enclosed by §§startabcfleximg§§    .... §§endabcfleximg§§ 
 *              an external image URL enclosed by §§startabcextfleximg§§ .... §§endabcextfleximg§§
 * Example : 
 * 				{|class="wikitable mw-collapsible" width=99%
 * 				! Holland!!Taiwan!!Taiwan
 * 				|-
 * 				|width=33%|{{#Fimg:https://cdn.knmi.nl/knmi/map/current/weather/forecast/kaart_verwachtingen_Vandaag_dag.gif}}
 * 				|width=33%|{{#Fimg:http://www.cwb.gov.tw/V7/observe/real/Data/Real_Image.png}}
 * 				|width=33%|{{#Fimg:MagicArtist_02.gif}}
 * 				|} 
 */
 
function wf_QBox_FlexImage_MySandbox($Wikidata){
	global $wgTitle, $wgUser;
		$myParser 			= new Parser();
		$myParserOptions 	= ParserOptions::newFromUser($wgUser);
		$result 			= $myParser->parse($Wikidata, $wgTitle, $myParserOptions);
		$parced_data 		= $result->getText();	

	return $parced_data;
	
} 
 
function wf_QBox_FlexImage_ParserFunction_Render( &$parser){
	global $wgTitle, $wgUser, $wgServer, $wgServerName,$wgScriptPath,$wgAllowExternalImagesFrom;
	
		//wf_QBox_FlexImage_autoCreateDemoPage();
	
		$arg = func_get_args();
		array_shift($arg); // 0th parameter is the $parser
		
		# Initialise parameters
		$wiki_internal_page 			= FALSE;
		$final_QBox_Internal_page_link 	= '';
		$final_QBox_External_page_link  = ''; 
		$img_link_raw   				= '';
		$link_available 				= '';
		$wg_local_html 					= '';

		
		# Protect against bad characters
		$img_location 			= htmlspecialchars($arg[0], ENT_QUOTES);

		# Check if we have an link in the parcer tag
		if(isset($arg[1])){ # set the link

					$link_available 		= TRUE;
					$img_link_raw			= htmlspecialchars($arg[1], ENT_QUOTES);
					# Lets check if we have an internal page link
					# This should look as [[Main_Page]]
					preg_match_all( "/\[\[(.*?)\]\]/si", $img_link_raw, $wikilink, PREG_SET_ORDER );		
					if( isset($wikilink[0][1]) ){ 
							# yes, we need to create an internal page link
							$wiki_internal_page 	= TRUE;
							
							$QBox_page_name = $wikilink[0][1]; 
							
							# Lets find out if the page exist
							$parced_link_location	= '';
							$parce_notation_link 	= "{{#ifexist: ".$QBox_page_name." |Yes this is an QBox WIKI page is available|no this is not an QBox WIKI page}}";							
							$parced_link_location 	= wf_QBox_FlexImage_MySandbox($parce_notation_link);
							if ( strpos($parced_link_location, 'Yes this is an QBox WIKI page is available') ){ 
								# Yes...Now we need to create an full internal page URL link
								$final_QBox_Internal_page_link = "$wgServer$wgScriptPath/index.php?title=".$QBox_page_name;
								}
								else { 	# NO ...The internal QBox wiki page is not found
										$final_QBox_Internal_page_link = "$wgServer$wgScriptPath/index.php?title=".$QBox_page_name;
									}
								
						}
					
							else { 
								$wiki_internal_page 	= FALSE;
								# The requested Link is not an internal page but an external URL-Link
								# This should ... MUST ... look as [qbox4u.com]

								preg_match_all( "/\[(.*?)\]/si", $img_link_raw, $externallink, PREG_SET_ORDER );


								if( isset($externallink[0][1]) ){ 

									$QBox_page_name = $externallink[0][1]; 
									# for every external link, we need to assure that the link starts with http:// 
									$http_ok = strpos($QBox_page_name,'http');

									if ( $http_ok > -1 ){$final_QBox_External_page_link = $QBox_page_name;}
										else { # No http found, so we will add this to the URL
												$final_QBox_External_page_link = "http://".$QBox_page_name;}
									
								}	
							}
		}	
		
		# Prepare the QBox wiki for the right parcer notation 
		# 
		$parce_notation_img_location 	= "{{filepath:".$img_location. "|nowiki}}";	
		$parced_img_location			= wf_QBox_FlexImage_MySandbox($parce_notation_img_location);
		


		// Check the existance of the server name and the path
		$sn_pos = strpos($parced_img_location,$wgServerName);
		$sp_pos = strpos($parced_img_location,$wgScriptPath);

		if ( $sn_pos && $sp_pos ){

					# we have an valid image inside the QBox, so enabe the image
					$link_stat ='';
			        if ($link_available) { 
						if( $wiki_internal_page) { $link_stat = '§§startabcextfleximglinkb§§<nowiki>'.$final_QBox_Internal_page_link.'</nowiki>§§endabcextfleximglinkb§§';}
					        else { $link_stat = '§§startabcextfleximglinkb§§<nowiki>'.$final_QBox_External_page_link.'</nowiki>§§endabcextfleximglinkb§§';}
					}
					$img_stat  = '§§startabcfleximg§§'.$parce_notation_img_location.'§§endabcfleximg§§';
					$wg_local_html  .=	$link_stat.$img_stat;
				}
			else {	

				   # the image is not inside the QBox. lets see if we aow the image by $wgAllowExternalImagesFrom				   
				   //$wg_local_html  = "<span style='color:red'>Picture:-- '''$arg[0]''' -- not found inside the QBox4u server, or the external URL image is not allowed</span>";
				   
				   # lets see if we allow the image by $wgAllowExternalImagesFrom
				   # 
				   # first of all, chec if we allow by  $wgAllowExternalImagesFrom external images 
				   # todo $wgAllowExternalImages and $wgEnableImageWhitelist
				   if (isset ( $wgAllowExternalImagesFrom[0] ) ){
						$img_stat = "<span style='color:red'>Picture:-- '''$arg[0]''' -- not found inside the QBox4u server, or the external URL image is not allowed</span>";
						$link_stat ='';
						foreach ($wgAllowExternalImagesFrom as $key => $Allowed_url) {
										
										$allowed = strpos($img_location,$Allowed_url);
										if ($allowed> -1) {	# we have an valid externa immage
															if ($link_available){
																	if($wiki_internal_page) { $link_stat   = '§§startabcextfleximglinkb§§<nowiki>'.$final_QBox_Internal_page_link.'</nowiki>§§endabcextfleximglinkb§§';}
																		else{ $link_stat   = '§§startabcextfleximglinkb§§<nowiki>'.$final_QBox_External_page_link.'</nowiki>§§endabcextfleximglinkb§§';}
																}
															$img_stat  = '§§startabcextfleximg§§<nowiki>'.$img_location.'</nowiki>§§endabcextfleximg§§';
															break;}										
						}
						$wg_local_html  .=	$link_stat.$img_stat;
				   }	
			}

	return array( $wg_local_html, 'noparse' => false, 'isHTML' => false);

}


/**
 * Finalise the PHP
 *  
 **/
?>